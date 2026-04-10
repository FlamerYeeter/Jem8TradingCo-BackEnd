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


class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }


        $orders = Checkout::with(['cart.product', 'delivery'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

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

        // ── Validate incoming request ─────────────────────────────────────
        $request->validate([
            'cart_ids'             => 'required|array|min:1',
            'payment_method'       => 'required|string|max:255',
            'payment_details'      => 'nullable|array',
            'shipping_fee'         => 'sometimes|numeric|min:0',
            'special_instructions' => 'sometimes|nullable|string|max:2000',
        ]);

        $cartIds = $request->input('cart_ids');

        // ── Fetch only the selected cart items for this user ──────────────
        $cartItems = Cart::where('user_id', $user->id)
            ->whereIn('cart_id', $cartIds)
            ->with('product')
            ->get();

        // Add this temporarily
        Log::info('Cart lookup', [
            'requested_ids' => $cartIds,
            'found_ids'     => $cartItems->pluck('cart_id')->toArray(),
            'user_id'       => $user->id,
        ]);

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'No valid cart items found'], 400);
        }

        $method      = $request->input('payment_method');
        $details     = $request->input('payment_details', []);
        $shippingFee = (float) $request->input('shipping_fee', 0);

        // ── Validate payment-method-specific fields ───────────────────────
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
                    'payment_details.bank_name'        => 'required|string',
                    'payment_details.account_name'     => 'required|string',
                    'payment_details.account_number'   => 'required|string',
                    'payment_details.reference_number' => 'nullable|string',
                ]);
                $paymentDetails = [
                    'bank_name'        => $details['bank_name'],
                    'account_name'     => $details['account_name'],
                    'account_number'   => $details['account_number'],
                    'reference_number' => $details['reference_number'] ?? null,
                ];
                break;

            case 'check':
                $request->validate([
                    'payment_details.bank_name'    => 'required|string',
                    'payment_details.check_number' => 'required|string',
                    'payment_details.check_date'   => 'required|date',
                    'payment_details.check_amount' => 'required|numeric',
                ]);
                $paymentDetails = [
                    'bank_name'    => $details['bank_name'],
                    'check_number' => $details['check_number'],
                    'check_date'   => $details['check_date'],
                    'check_amount' => $details['check_amount'],
                ];
                break;

            case 'cod':
                $paymentDetails = ['type' => 'cash_on_delivery'];
                break;

            default:
                return response()->json(['message' => 'Invalid payment method'], 400);
        }

        // ── Calculate grand total & check stock ───────────────────────────
        $grandTotal = 0;
        foreach ($cartItems as $item) {
            $price = (float) ($item->product->price ?? 0);
            $grandTotal += $price * (int) $item->quantity;


        }

        $paidAmount = $grandTotal + $shippingFee;
        $paidAt     = in_array($method, ['gcash', 'maya']) ? now() : null;

        DB::beginTransaction();
        try {
            // ── Generate a unique receipt number ──────────────────────────
            do {
                $receiptNumber = 'RCPT-' . time() . '-' . rand(1000, 9999);
            } while (DB::table('receipts')->where('receipt_number', $receiptNumber)->exists());

            $paymentReference = json_encode($paymentDetails);
            $lastCheckout     = null;
            $receiptId        = null;

            foreach ($cartItems as $cartItem) {
                // ── Create one Checkout row per cart item ─────────────────
                $checkout = Checkout::create([
                    'user_id'              => $user->id,
                    'cart_id'              => $cartItem->cart_id,
                    'discount_id'          => null,
                    'payment_method'       => $method,
                    'payment_details'      => $paymentDetails,
                    'shipping_fee'         => $shippingFee,
                    'paid_amount'          => $paidAmount,
                    'paid_at'              => $paidAt,
                    'special_instructions' => $request->input('special_instructions'),
                ]);

                $lastCheckout = $checkout;

                // ── Receipt per checkout row ───────────────────────────────
                $receiptId = DB::table('receipts')->insertGetId([
                    'user_id'           => $user->id,
                    'checkout_id'       => $checkout->checkout_id,
                    'receipt_number'    => $receiptNumber,
                    'payment_method'    => $method,
                    'payment_reference' => $paymentReference,
                    'paid_amount'       => $paidAmount,
                    'paid_at'           => $paidAt,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                // ── Delivery row per checkout ──────────────────────────────
                Delivery::create([
                    'checkout_id' => $checkout->checkout_id,
                    'status'      => 'processing',
                    'notes'       => $request->input('special_instructions', null),
                ]);



            }

            // ── Mark only the selected cart items as checked out ──────────
            Cart::where('user_id', $user->id)
                ->whereIn('cart_id', $cartIds)
                ->update(['is_checkout' => true]);

            // ── Activity log ──────────────────────────────────────────────
            ActivityLog::log($user, 'Made a payment', 'payments', [
                'product_unique_code' => $receiptNumber,
                'amount'              => $paidAmount,
                'mode_of_payment'     => $method,
                'description'         => $user->first_name
                    . ' placed an order and paid via ' . $method
                    . ' — Total: ₱' . number_format($paidAmount, 2),
                'reference_table'     => 'checkouts',
                'reference_id'        => $lastCheckout->checkout_id,
            ]);

            DB::commit();

            return response()->json([
                'checkout_id'  => $lastCheckout->checkout_id,
                'user_id'      => $user->id,
                'paid_amount'  => number_format($paidAmount, 2, '.', ''),
                'shipping_fee' => number_format($shippingFee, 2, '.', ''),
                'receipt_id'   => $receiptId,
                'receipt_number' => $receiptNumber,
                'items'        => $cartItems->map(fn($i) => [
                    'cart_id'    => $i->cart_id,
                    'product_id' => $i->product_id,
                    'quantity'   => $i->quantity,
                    'price'      => (string) $i->product->price,
                    'total'      => number_format(
                        (float) $i->product->price * $i->quantity, 2, '.', ''
                    ),
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
