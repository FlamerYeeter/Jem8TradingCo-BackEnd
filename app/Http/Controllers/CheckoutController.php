<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    // Create a checkout from the current user's cart
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // Validate main checkout data
        $request->validate([
            'payment_method' => 'required|string|max:255',
            'payment_details' => 'required|array',
            'shipping_fee' => 'sometimes|numeric|min:0',
            'special_instructions' => 'sometimes|string|max:2000',
        ]);

        $shippingFee = $request->input('shipping_fee', 0);

        // Validate stock and calculate total
        $grandTotal = 0.0;
        foreach ($cartItems as $item) {
            $price = floatval($item->product->price ?? 0);
            $grandTotal += $price * intval($item->quantity);

            if (isset($item->product->product_stocks) && $item->product->product_stocks < $item->quantity) {
                return response()->json([
                    'message' => 'Insufficient stock for product',
                    'product_id' => $item->product_id
                ], 400);
            }
        }

        $paidAmount = $grandTotal + floatval($shippingFee);

        // Determine payment_status and order_status based on payment method
        $method = $request->input('payment_method');
        if (in_array($method, ['gcash','maya'])) {
            $paymentStatus = 'paid';
            $orderStatus = 'processing';
            $paidAt = now();
        } elseif ($method === 'cod') {
            $paymentStatus = 'pending';
            $orderStatus = 'processing';
            $paidAt = null;
        } else { // bank_transfer or check
            $paymentStatus = 'pending';
            $orderStatus = 'waiting_payment';
            $paidAt = null;
        }

        DB::beginTransaction();
        try {
            // Insert checkout
            $checkoutId = DB::table('checkouts')->insertGetId([
                'user_id' => $user->id,
                'cart_id' => null,
                'discount_id' => null,
                'payment_method' => $method,
                'payment_details' => json_encode($request->input('payment_details')),
                'payment_status' => $paymentStatus,
                'order_status' => $orderStatus,
                'shipping_fee' => $shippingFee,
                'paid_amount' => $paidAmount,
                'paid_at' => $paidAt,
                'special_instructions' => $request->input('special_instructions'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Generate receipt
            do {
                $receiptNumber = 'RCPT-' . time() . '-' . rand(1000, 9999);
            } while (Receipt::where('receipt_number', $receiptNumber)->exists());

            $receipt = Receipt::create([
                'user_id' => $user->id,
                'checkout_id' => $checkoutId,
                'receipt_number' => $receiptNumber,
                'payment_method' => $method,
                'payment_details' => json_encode($request->input('payment_details')),
                'paid_amount' => $paidAmount,
                'paid_at' => $paidAt,
            ]);

            // Generate invoice
            do {
                $invoiceNumber = 'INV-' . time() . '-' . rand(1000, 9999);
            } while (Invoice::where('invoice_number', $invoiceNumber)->exists());

            $invoice = Invoice::create([
                'user_id' => $user->id,
                'checkout_id' => $checkoutId,
                'receipt_id' => $receipt->receipt_id,
                'invoice_number' => $invoiceNumber,
                'billing_address' => null,
                'tax_amount' => 0,
                'total_amount' => $paidAmount,
                'status' => $paymentStatus === 'paid' ? 'paid' : 'unpaid',
                'issued_at' => now(),
            ]);

            // Deduct stock immediately for GCash, Maya, and COD
            if (in_array($method, ['gcash','maya','cod'])) {
                foreach ($cartItems as $item) {
                    $product = Product::find($item->product_id);
                    if ($product && isset($product->product_stocks)) {
                        $product->product_stocks = max(0, $product->product_stocks - $item->quantity);
                        $product->save();
                    }
                }
            }

            // Clear cart
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json([
                'checkout_id' => $checkoutId,
                'user_id' => $user->id,
                'paid_amount' => number_format($paidAmount, 2, '.', ''),
                'shipping_fee' => number_format(floatval($shippingFee), 2, '.', ''),
                'receipt' => $receipt,
                'invoice' => $invoice,
                'items' => $cartItems->map(function ($i) {
                    return [
                        'product_id' => $i->product_id,
                        'quantity' => $i->quantity,
                        'price' => (string) $i->product->price,
                        'total' => number_format(floatval($i->product->price) * $i->quantity, 2, '.', ''),
                    ];
                })->values(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Checkout failed', 'error' => $e->getMessage()], 500);
        }
    }
}