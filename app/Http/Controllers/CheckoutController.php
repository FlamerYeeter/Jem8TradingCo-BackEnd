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

        $cartIds = $request->input('cart_ids', []);
        if (empty($cartIds)) {
            return response()->json(['message' => 'No items selected'], 400);
        }
        $cartItems = Cart::where('user_id', $user->id)
            ->whereIn('cart_id', $cartIds)
            ->with('product')
            ->get();

    if ($cartItems->isEmpty()) {
        return response()->json(['message' => 'Cart is empty'], 400);
    }

    $request->validate([
    'cart_ids'             => 'required|array|min:1',
    'cart_ids.*'           => 'integer',
    'payment_method'       => 'required|string|max:255',
    'payment_details'      => 'nullable|array',
    'shipping_fee'         => 'sometimes|numeric|min:0',
    'special_instructions' => 'sometimes|nullable|string|max:2000',
]);

    $method      = $request->input('payment_method');
    $details     = $request->input('payment_details', []);
    $shippingFee = (float) $request->input('shipping_fee', 0);

    switch ($method) {
        case 'gcash':
        case 'maya':
            $request->validate(['payment_details.mobile_number' => 'required|string', 'payment_details.account_name' => 'required|string']);
            $paymentDetails = ['mobile_number' => $details['mobile_number'], 'account_name' => $details['account_name']];
            break;
        case 'bank_transfer':
            $request->validate(['payment_details.bank_name' => 'required|string', 'payment_details.account_name' => 'required|string', 'payment_details.account_number' => 'required|string', 'payment_details.reference_number' => 'nullable|string']);
            $paymentDetails = ['bank_name' => $details['bank_name'], 'account_name' => $details['account_name'], 'account_number' => $details['account_number'], 'reference_number' => $details['reference_number'] ?? null];
            break;
        case 'check':
            $request->validate(['payment_details.bank_name' => 'required|string', 'payment_details.check_number' => 'required|string', 'payment_details.check_date' => 'required|date', 'payment_details.check_amount' => 'required|numeric']);
            $paymentDetails = ['bank_name' => $details['bank_name'], 'check_number' => $details['check_number'], 'check_date' => $details['check_date'], 'check_amount' => $details['check_amount']];
            break;
        case 'cod':
            $paymentDetails = ['type' => 'cash_on_delivery'];
            break;
        default:
            return response()->json(['message' => 'Invalid payment method'], 400);
    }

    $grandTotal = 0;
    $preOrderItems = [];
    $inStockItems = [];

    foreach ($cartItems as $item) {
    if (!$item->product) continue;
    $price = (float) ($item->product->price ?? 0);
        $grandTotal += $price * (int) $item->quantity;


        $productStatus = $item->product->status ?? 'in_stock';

        if ($productStatus === 'pre_order') {
            $preOrderItems[] = [
                'product_id' => $item->product_id,
                'name' => $item->product->product_name,
                'quantity' => $item->quantity
            ];
        } else {
            $inStockItems[] = [
                'product_id' => $item->product_id,
                'name' => $item->product->product_name,
                'quantity' => $item->quantity
            ];
        }


    }

    $paidAmount = $grandTotal + $shippingFee;
    $paidAt     = in_array($method, ['gcash', 'maya']) ? now() : null;

    DB::beginTransaction();
    try {

        $paymentDetailsWithStatus = $paymentDetails;
        $paymentDetailsWithStatus['pre_order_items'] = $preOrderItems;
        $paymentDetailsWithStatus['in_stock_items'] = $inStockItems;
        $paymentDetailsWithStatus['has_pre_order'] = !empty($preOrderItems);

        $checkout = Checkout::create([
            'user_id'              => $user->id,
            'cart_id'              => $cartItems->first()->cart_id,
            'discount_id'          => null,
            'payment_method'       => $method,
            'payment_details'      => json_encode($paymentDetailsWithStatus),
            'shipping_fee'         => $shippingFee,
            'paid_amount'          => $paidAmount,
            'paid_at'              => $paidAt,
            'special_instructions' => $request->input('special_instructions', null),
        ]);

        $paymentReference = json_encode($paymentDetailsWithStatus);
        do {
            $receiptNumber = 'RCPT-' . time() . '-' . rand(1000, 9999);
        } while (DB::table('receipts')->where('receipt_number', $receiptNumber)->exists());

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

        $delivery = Delivery::create([
            'checkout_id' => $checkout->checkout_id,
            'status'      => 'processing',
            'notes'       => $request->input('special_instructions', null),
        ]);

        if (in_array($method, ['gcash', 'maya', 'cod', 'bank_transfer', 'check'])) {
    foreach ($cartItems as $item) {
    if (!$item->product) continue;
    $productStatus = $item->product->status ?? 'in_stock';
    if ($productStatus === 'in_stock') {
            $product = Product::find($item->product_id);
            if ($product && isset($product->product_stocks)) {
                $product->product_stocks = max(0, $product->product_stocks - $item->quantity);
                $product->save();
            }
        }

    }
}

       Cart::whereIn('cart_id', $cartItems->pluck('cart_id'))
    ->update(['is_checkout' => true]);


        $statusMessage = !empty($preOrderItems)
            ? $user->first_name . ' placed an order with ' . count($preOrderItems) . ' pre-order item(s) and ' . count($inStockItems) . ' in-stock item(s)'
            : $user->first_name . ' placed an order with ' . count($inStockItems) . ' in-stock item(s)';

        ActivityLog::log($user, 'Made a payment', 'payments', [
            'product_unique_code' => $receiptNumber,
            'amount'              => $paidAmount,
            'mode_of_payment'     => $method,
            'description'         => $statusMessage . ' — Total: ₱' . number_format($paidAmount, 2),
            'reference_table'     => 'checkouts',
            'reference_id'        => $checkout->checkout_id,
            'has_pre_order'       => !empty($preOrderItems),
            'pre_order_count'     => count($preOrderItems)
        ]);

        DB::commit();

        return response()->json([
            'checkout_id'  => $checkout->checkout_id,
            'user_id'      => $user->id,
            'paid_amount'  => number_format($paidAmount, 2, '.', ''),
            'shipping_fee' => number_format($shippingFee, 2, '.', ''),
            'receipt_id'   => $receiptId,
            'has_pre_order' => !empty($preOrderItems),
            'pre_order_items' => $preOrderItems,
            'items'        => $cartItems->map(fn($i) => [
                'product_id' => $i->product_id,
                'quantity'   => $i->quantity,
                'price'      => (string) $i->product->price,
                'status'     => $i->product->status ?? 'in_stock',
                'total'      => number_format((float) $i->product->price * $i->quantity, 2, '.', ''),
            ]),
        ], 201);

    } catch (\Exception $e) {
    DB::rollBack();
    Log::error('Checkout failed', ['message' => $e->getMessage(), 'user_id' => $user->id]);
    return response()->json([
        'message' => 'Checkout failed',
        'error' => $e->getMessage(),
        'trace' => $e->getLine()
    ], 500);
}
}
}
