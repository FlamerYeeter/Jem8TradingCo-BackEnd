<?php

// ============================================================
// DeliveryController.php
// ============================================================

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Models\Delivery;
use App\Models\Account;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    // Admin list of all deliveries
    public function index(Request $request)
    {
        $query = Delivery::with([
            'checkout.user',
            'checkout.cart.product',
        ]);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $deliveries = $query->get();

        ActivityLog::log(Auth::user(), 'Viewed deliveries list', 'orders', [
            'description'     => Auth::user()->first_name . ' viewed the deliveries list',
            'reference_table' => 'deliveries',
        ]);

        return response()->json(['deliveries' => $deliveries], 200);
    }

    // User's own deliveries
    public function indexUser(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $account = Account::select('id', 'first_name', 'last_name', 'phone_number', 'email', 'profile_image')
            ->where('id', $user->id)
            ->first();

        // ✅ Eager-load cart and product in one query instead of N+1 loops
        $checkouts = Checkout::with(['cart.product'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $orders = $checkouts->map(function ($checkout) {
            $delivery = Delivery::where('checkout_id', $checkout->checkout_id)
                ->select('delivery_id', 'checkout_id', 'status', 'driver_id', 'notes', 'created_at')
                ->first();

            return [
                'checkout' => [
                    'checkout_id'          => $checkout->checkout_id,
                    'user_id'              => $checkout->user_id,
                    'cart_id'              => $checkout->cart_id,
                    'discount_id'          => $checkout->discount_id,
                    'payment_method'       => $checkout->payment_method,
                    'payment_details'      => $checkout->payment_details,
                    'shipping_fee'         => $checkout->shipping_fee,
                    'paid_amount'          => $checkout->paid_amount,
                    'paid_at'              => $checkout->paid_at,
                    'special_instructions' => $checkout->special_instructions,
                    'created_at'           => $checkout->created_at,

                    'cart' => $checkout->cart ? [
                        'cart_id'     => $checkout->cart->cart_id,
                        'quantity'    => $checkout->cart->quantity,
                        'total'       => $checkout->cart->total,
                        'status'      => $checkout->cart->status,
                        'is_checkout' => $checkout->cart->is_checkout,
                        'product'     => $checkout->cart->product ? [
                            'product_id'        => $checkout->cart->product->product_id,
                            'product_name'      => $checkout->cart->product->product_name,
                            'price'             => $checkout->cart->product->price,
                            'status'            => $checkout->cart->product->status,
                            'product_stocks'    => $checkout->cart->product->product_stocks,
                            'primary_image_url' => $checkout->cart->product->primary_image_url,
                            'images'            => $checkout->cart->product->images ?? [],
                        ] : null,
                    ] : null,
                ],
                'delivery' => $delivery,
            ];
        });

        ActivityLog::log($user, 'Viewed delivery status', 'orders', [
            'description'     => $user->first_name . ' checked their delivery status',
            'reference_table' => 'deliveries',
        ]);

        return response()->json(['account' => $account, 'orders' => $orders], 200);
    }

    // Update delivery status
    public function updateStatus(Request $request, $deliveryId)
    {
        $request->validate([
            'status' => 'required|in:processing,ready,on_the_way,delivered',
        ]);

        $delivery = Delivery::find($deliveryId);
        if (!$delivery) {
            return response()->json(['message' => 'Delivery not found'], 404);
        }

        $oldStatus        = $delivery->status;
        $delivery->status = $request->input('status');
        $delivery->save();

        ActivityLog::log(Auth::user(), 'Updated delivery status', 'orders', [
            'description'     => Auth::user()->first_name . ' updated delivery #' . $deliveryId . ' from ' . $oldStatus . ' to ' . $delivery->status,
            'reference_table' => 'deliveries',
            'reference_id'    => $deliveryId,
        ]);

        return response()->json([
            'delivery_id' => $delivery->delivery_id,
            'checkout_id' => $delivery->checkout_id,
            'status'      => $delivery->status,
            'updated_at'  => $delivery->updated_at,
        ]);
    }
}
