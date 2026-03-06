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

        $request->validate([
            'payment_method' => 'sometimes|string|max:255',
            'payment_reference' => 'sometimes|string|max:255',
            'shipping_fee' => 'sometimes|numeric|min:0',
            'special_instructions' => 'sometimes|string|max:2000',
        ]);

        $shippingFee = $request->input('shipping_fee', 0);

        // Calculate totals and validate stock
        $grandTotal = 0.0;
        foreach ($cartItems as $item) {
            $price = floatval($item->product->price ?? 0);
            $grandTotal += $price * intval($item->quantity);
            // Stock check
            if (isset($item->product->product_stocks) && $item->product->product_stocks < $item->quantity) {
                return response()->json(['message' => 'Insufficient stock for product', 'product_id' => $item->product_id], 400);
            }
        }

        $paidAmount = $grandTotal + floatval($shippingFee);

        DB::beginTransaction();
        try {
            $checkout = DB::table('checkout')->insertGetId([
                'user_id' => $user->id,
                'cart_id' => null,
                'discount_id' => null,
                'payment_method' => $request->input('payment_method'),
                'payment_reference' => $request->input('payment_reference'),
                'shipping_fee' => $shippingFee,
                'paid_amount' => $paidAmount,
                'paid_at' => $request->has('payment_method') ? now() : null,
                'special_instructions' => $request->input('special_instructions'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create receipt
            $receiptNumber = null;
            do {
                $receiptNumber = 'RCPT-' . time() . '-' . rand(1000, 9999);
            } while (Receipt::where('receipt_number', $receiptNumber)->exists());

            $receipt = Receipt::create([
                'user_id' => $user->id,
                'checkout_id' => $checkout,
                'receipt_number' => $receiptNumber,
                'payment_method' => $request->input('payment_method'),
                'payment_reference' => $request->input('payment_reference'),
                'paid_amount' => $paidAmount,
                'paid_at' => $request->has('payment_method') ? now() : null,
            ]);

            // Create invoice
            $invoiceNumber = null;
            do {
                $invoiceNumber = 'INV-' . time() . '-' . rand(1000, 9999);
            } while (Invoice::where('invoice_number', $invoiceNumber)->exists());

            $invoice = Invoice::create([
                'user_id' => $user->id,
                'checkout_id' => $checkout,
                'receipt_id' => $receipt->receipt_id,
                'invoice_number' => $invoiceNumber,
                'billing_address' => null,
                'tax_amount' => 0,
                'total_amount' => $paidAmount,
                'status' => $request->has('payment_method') ? 'paid' : 'unpaid',
                'issued_at' => now(),
            ]);

            // Deduct stock and optionally create order items if you have an order_items table
            foreach ($cartItems as $item) {
                $product = Product::find($item->product_id);
                if ($product && isset($product->product_stocks)) {
                    $product->product_stocks = max(0, $product->product_stocks - $item->quantity);
                    $product->save();
                }
            }

            // Clear user's cart
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            $response = [
                'checkout_id' => $checkout,
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
            ];

            return response()->json($response, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Checkout failed', 'error' => $e->getMessage()], 500);
        }
    }
}
