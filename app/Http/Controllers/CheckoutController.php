<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Checkout;
use App\Models\Product;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Delivery;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

class CheckoutController extends Controller
{
public function index(Request $request)
{
    $user = $request->user();
    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $checkouts = Checkout::with(['items.product', 'delivery', 'receipt'])
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

    $orders = $checkouts->map(function ($checkout) {
        // Append receipt_image_url onto the receipt relation
        if ($checkout->receipt && $checkout->receipt->receipt_image) {
            $checkout->receipt->receipt_image_url = asset('storage/' . $checkout->receipt->receipt_image);
        }

        // Build a normalized delivery_address object from flat columns so
        // clients (including PDF exporters) can reliably read the address.
        $deliveryAddress = [
            'street'   => $checkout->delivery_street ?? null,
            'barangay' => $checkout->delivery_barangay ?? null,
            'city'     => $checkout->delivery_city ?? null,
            'province' => $checkout->delivery_province ?? null,
            'postal'   => $checkout->delivery_zip ?? null,
            'country'  => $checkout->delivery_country ?? null,
        ];

        // A formatted single-line address for simple PDF templates.
        $parts = array_filter([
            $deliveryAddress['street'],
            $deliveryAddress['barangay'],
            $deliveryAddress['city'],
            $deliveryAddress['province'],
            $deliveryAddress['postal'],
            $deliveryAddress['country'],
        ]);
        $formatted = $parts ? implode(', ', $parts) : null;

        // Attach to the model instance for convenience for any other consumers
        $checkout->delivery_address = $deliveryAddress;
        $checkout->delivery_address_formatted = $formatted;

        return [
            'checkout' => $checkout,
            'delivery' => $checkout->delivery,
            'delivery_address' => $deliveryAddress,
            'delivery_address_formatted' => $formatted,
        ];
    });

    ActivityLog::log($user, 'Viewed orders', 'orders', [
        'description'     => $user->first_name . ' viewed their orders list',
        'reference_table' => 'checkouts',
    ]);

    return response()->json([
        'account' => $user,
        'orders'  => $orders,
    ]);
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

        // ── Resolve delivery address ──────────────────────────────────────
$billingAddress = $details['billing_address'] ?? null;
if (is_string($billingAddress)) {
    $billingAddress = json_decode($billingAddress, true);
}

        // ── Payment validation ────────────────────────────────────────────
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

            case 'deposit':
            case 'deposit_payment':
                $request->validate([
                    'payment_details.bank_name'        => 'required|string',
                    'payment_details.account_name'     => 'required|string',
                    'payment_details.account_number'   => 'required_without:payment_details.reference_number|string',
                    'payment_details.reference_number' => 'required_without:payment_details.account_number|string',
                ]);
                $paymentDetails = [
                    'bank_name'        => $details['bank_name']        ?? null,
                    'account_name'     => $details['account_name']     ?? null,
                    'account_number'   => $details['account_number']   ?? null,
                    'reference_number' => $details['reference_number'] ?? null,
                ];
                break;

            case 'check':
            case 'check_payment':
                $request->validate([
                    'payment_details.bank_name'    => 'required|string',
                    'payment_details.check_number' => 'required|string',
                    'payment_details.check_date'   => 'required|date',
                    'payment_details.check_amount' => 'required|numeric',
                ]);
                $paymentDetails = [
                    'bank_name'    => $details['bank_name']    ?? null,
                    'check_number' => $details['check_number'] ?? null,
                    'check_date'   => $details['check_date']   ?? null,
                    'check_amount' => $details['check_amount'] ?? null,
                ];
                break;

            case 'cod':
                $paymentDetails = ['type' => 'cash_on_delivery'];
                break;

            default:
                return response()->json(['message' => 'Invalid payment method'], 400);
        }

        // ── Compute total ─────────────────────────────────────────────────
        $grandTotal = 0;
        foreach ($cartItems as $item) {
            $grandTotal += (float) $item->product->price * $item->quantity;
        }

        // ── Enforce COD limit (max 3 per user) ───────────────────────────
        if (strtolower($method) === 'cod') {
            $codCount = Checkout::where('user_id', $user->id)
                ->whereRaw("LOWER(payment_method) = 'cod'")
                ->count();

            if ($codCount >= 3) {
                return response()->json([
                    'message' => 'Cash on Delivery disabled: you have reached the maximum of 3 COD orders.',
                    'action'  => 'Please select a prepaid payment method or apply for payment terms via /payment-terms/apply',
                ], 403);
            }
        }

        $paidAmount = $grandTotal + $shippingFee;
        $paidAt     = in_array($method, ['gcash', 'maya']) ? now() : null;

        // ── Receipt image upload ──────────────────────────────────────────
        $receiptImagePath = null;

        if ($request->hasFile('receipt_image')) {
            $image = $request->file('receipt_image');

            Log::info('=== RECEIPT UPLOAD ===');

            if (!$image->isValid()) {
                throw new \Exception('Invalid receipt image file');
            }

            $uploadPath = public_path('storage/receipts');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $extension        = $image->getClientOriginalExtension();
            $filename         = time() . '_' . uniqid() . '.' . $extension;
            $image->move($uploadPath, $filename);
            $receiptImagePath = 'receipts/' . $filename;

            Log::info('Receipt saved', ['path' => $receiptImagePath]);
        }

        // ── Database transaction ──────────────────────────────────────────
        DB::beginTransaction();
        try {

            // delivery_address is passed as an array — the model cast (array)
            // handles json_encode automatically, so do NOT json_encode() it here.
$checkoutData = [
    'user_id'              => $user->id,
    'payment_method'       => $method,
    'shipping_fee'         => $shippingFee,
    'paid_amount'          => $paidAmount,
    'paid_at'              => $paidAt,
    'special_instructions' => $request->input('special_instructions'),
    // flat address columns
    'delivery_street'      => $billingAddress['street']   ?? null,
    'delivery_barangay'    => $billingAddress['barangay'] ?? null,
    'delivery_city'        => $billingAddress['city']     ?? null,
    'delivery_province'    => $billingAddress['province'] ?? null,
    'delivery_zip'         => $billingAddress['zip']      ?? null,
    'delivery_country'     => $billingAddress['country']  ?? 'Philippines',
];

if (Schema::hasColumn('checkouts', 'payment_details')) {
    $checkoutData['payment_details'] = $paymentDetails;
}

            $checkout = Checkout::create($checkoutData);

            // ── Insert checkout items ─────────────────────────────────────
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

            // ── Create receipt record ─────────────────────────────────────
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

            // ── Create delivery record ────────────────────────────────────
            $delivery = Delivery::create([
                'checkout_id' => $checkout->checkout_id,
                'status'      => 'processing',
                'notes'       => $request->input('special_instructions'),
            ]);

            // ── Mark cart items as checked out ────────────────────────────
            if (Schema::hasTable('cart') && Schema::hasColumn('cart', 'is_checkout')) {
                Cart::whereIn('cart_id', $cartIds)->update(['is_checkout' => true]);
            }

            // ── Activity log ──────────────────────────────────────────────
            ActivityLog::log($user, 'Made a payment', 'payments', [
                'product_unique_code' => $receiptNumber,
                'amount'              => $paidAmount,
                'mode_of_payment'     => $method,
                'description'         => $user->first_name . ' placed an order — ₱' . number_format($paidAmount, 2),
                'reference_table'     => 'checkouts',
                'reference_id'        => $checkout->checkout_id,
            ]);

            DB::commit();

            // ── Notification: keep existing "New Order Placed" behaviour
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

            // If the delivery record was created already with status 'delivered', notify immediately.
            try {
                if (isset($delivery) && $delivery->status === 'delivered') {
                    \App\Services\NotificationService::createAndBroadcast(
                        $checkout->user_id,
                        'order_status',
                        'Order delivered',
                        "Your order #{$checkout->checkout_id} has been delivered.",
                        'checkout',
                        $checkout->checkout_id
                    );
                }
            } catch (\Exception $e) {
                logger()->error('Failed to create initial delivery notification: ' . $e->getMessage());
            }

            return response()->json([
                'checkout_id'       => $checkout->checkout_id,
                'receipt_id'        => $receiptId,
                'receipt_number'    => $receiptNumber,
                'paid_amount'       => number_format($paidAmount, 2, '.', ''),
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
