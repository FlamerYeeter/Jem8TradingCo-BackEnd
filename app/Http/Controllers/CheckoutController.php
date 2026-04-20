<?php


// ============================================================
// CheckoutController.php
// ============================================================


namespace App\Http\Controllers;


use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Delivery;
use Illuminate\Support\Facades\Cache;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $orders = Checkout::with(['cart.product', 'delivery', 'receipt'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                $order->receipt_image_url = $order->receipt && $order->receipt->receipt_image
                    ? asset('storage/' . $order->receipt->receipt_image)
                    : null;

                return $order;
            });

        ActivityLog::log($user, 'Viewed orders', 'orders', [
            'description'     => $user->first_name . ' viewed their orders list',
            'reference_table' => 'checkouts',
        ]);

        return response()->json($orders);
    }


    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'cart_ids'             => 'required|array|min:1',
            'payment_method'       => 'required|string|max:255',
            'payment_details'      => 'nullable|array',
            'shipping_fee'         => 'sometimes|numeric|min:0',
            'special_instructions' => 'sometimes|nullable|string|max:2000',
            'receipt_image'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $cartIds = $request->input('cart_ids');

        $cartItems = Cart::where('user_id', $user->id)
            ->whereIn('cart_id', $cartIds)
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'No valid cart items found'], 400);
        }

        $method      = $request->input('payment_method');
        $details     = $request->input('payment_details', []);
        $shippingFee = (float) $request->input('shipping_fee', 0);
        $deliveryAddress = $details['billing_address'] ?? null;

        // ✅ PAYMENT VALIDATION
        switch ($method) {
            case 'gcash':
            case 'maya':
                $request->validate([
                    'payment_details.mobile_number' => 'required|string',
                    'payment_details.account_name'  => 'required|string',
                ]);
                $paymentDetails = [
                    'mobile_number' => $details['mobile_number'],
                    'account_name'  => $details['account_name'],
                ];
                break;

            case 'bank_transfer':
                $request->validate([
                    'payment_details.bank_name'      => 'required|string',
                    'payment_details.account_name'   => 'required|string',
                    'payment_details.account_number' => 'required|string',
                ]);
                $paymentDetails = $details;
                break;

            case 'cod':
                $paymentDetails = ['type' => 'cash_on_delivery'];
                break;

            default:
                return response()->json(['message' => 'Invalid payment method'], 400);
        }

        // ✅ COMPUTE TOTAL
        $grandTotal = 0;
        foreach ($cartItems as $item) {
            $grandTotal += (float)$item->product->price * $item->quantity;
        }

        // Enforce COD limit: maximum 3 COD orders per user
        if (strtolower($method) === 'cod') {
            $codCount = Checkout::where('user_id', $user->id)
                ->whereRaw("LOWER(payment_method) = 'cod'")
                ->count();

            if ($codCount >= 3) {
                return response()->json([
                    'message' => 'Cash on Delivery disabled: you have reached the maximum of 3 COD orders.',
                    'action' => 'Please select a prepaid payment method or apply for payment terms via /payment-terms/apply'
                ], 403);
            }
        }

        $paidAmount = $grandTotal + $shippingFee;
        $paidAt     = in_array($method, ['gcash', 'maya']) ? now() : null;

        // =====================================================
        // RECEIPT IMAGE UPLOAD (FIXED LIKE PROFILE IMAGE STYLE)
        // =====================================================
        $receiptImagePath = null;

        if ($request->hasFile('receipt_image')) {

            $image = $request->file('receipt_image');

            Log::info('=== RECEIPT UPLOAD ===');
            Log::info('Has file?', ['has_file' => $request->hasFile('receipt_image')]);

            if (!$image->isValid()) {
                throw new \Exception('Invalid receipt image file');
            }

            // Create directory: public/storage/receipts
            $uploadPath = public_path('storage/receipts');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
                Log::info('Created receipts directory: ' . $uploadPath);
            }

            // Delete old file if needed (optional safety)
            // not needed for checkout usually

            // Generate unique filename
            $extension = $image->getClientOriginalExtension();
            $filename  = time() . '_' . uniqid() . '.' . $extension;

            Log::info('Saving receipt image:', [
                'filename' => $filename,
                'size'     => $image->getSize(),
                'mime'     => $image->getMimeType(),
            ]);

            // MOVE FILE
            $image->move($uploadPath, $filename);

            // SAVE RELATIVE PATH (IMPORTANT)
            $receiptImagePath = 'receipts/' . $filename;
        }

        DB::beginTransaction();
        try {

            // ✅ CREATE ONE CHECKOUT ONLY
            $checkout = Checkout::create([
                'user_id'              => $user->id,
                'payment_method'       => $method,
                'payment_details'      => $paymentDetails,
                'delivery_address'     => $deliveryAddress,
                'shipping_fee'         => $shippingFee,
                'paid_amount'          => $paidAmount,
                'paid_at'              => $paidAt,
                'special_instructions' => $request->input('special_instructions'),
            ]);

            // ✅ INSERT MULTIPLE ITEMS
            foreach ($cartItems as $item) {
                DB::table('checkout_items')->insert([
                    'checkout_id' => $checkout->checkout_id,
                    'product_id'  => $item->product_id,
                    'quantity'    => $item->quantity,
                    'price'       => $item->product->price,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            // ✅ UNIQUE RECEIPT (NO DUPLICATE)
            do {
                $receiptNumber = 'RCPT-' . now()->timestamp . '-' . random_int(100000, 999999);
            } while (DB::table('receipts')->where('receipt_number', $receiptNumber)->exists());

            $receiptId = DB::table('receipts')->insertGetId([
                'user_id'           => $user->id,
                'checkout_id'       => $checkout->checkout_id,
                'receipt_number'    => $receiptNumber,
                'payment_method'    => $method,
                'receipt_image'     => $receiptImagePath,
                'payment_reference' => json_encode($paymentDetails),
                'paid_amount'       => $paidAmount,
                'paid_at'           => $paidAt,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            // ✅ DELIVERY (ONE ONLY)
            Delivery::create([
                'checkout_id' => $checkout->checkout_id,
                'status'      => 'processing',
                'notes'       => $request->input('special_instructions'),
            ]);

            // ✅ MARK CART AS CHECKED OUT
            Cart::whereIn('cart_id', $cartIds)
                ->update(['is_checkout' => true]);

            ActivityLog::log($user, 'Made a payment', 'payments', [
                'product_unique_code' => $receiptNumber,
                'amount'              => $paidAmount,
                'mode_of_payment'     => $method,
                'description'         => $user->first_name
                    . ' placed an order — ₱' . number_format($paidAmount, 2),
                'reference_table'     => 'checkouts',
                'reference_id'        => $checkout->checkout_id,
            ]);

            DB::commit();
                    DB::table('notifications')->insert([
                    'user_id'    => $checkout->user_id,
                    'type'       => 'order',
                    'title'      => 'New Order Placed',
                    'message'    => "{$user->first_name} {$user->last_name} placed an order of ₱" . number_format($checkout->paid_amount, 2),
                    'is_read'    => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Cache::forget('dashboard.notifications');
            return response()->json([
                'checkout_id'  => $checkout->checkout_id,
                'receipt_id'   => $receiptId,
                'receipt_number' => $receiptNumber,
                'paid_amount'  => number_format($paidAmount, 2, '.', ''),
                'receipt_image_url' => $receiptImagePath
                    ? asset('storage/' . $receiptImagePath)
                    : null,

                'items' => $cartItems->map(fn($i) => [
                    'product_id' => $i->product_id,
                    'quantity'   => $i->quantity,
                    'price'      => $i->product->price,
                ]),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Checkout failed', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Checkout failed',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
