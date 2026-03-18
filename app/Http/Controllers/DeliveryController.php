<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Models\Delivery;
use App\Models\Account;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    // Admin list of deliveries with checkout + user
    public function index(Request $request)
    {
        $query = Delivery::with(['checkout.user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $deliveries = $query->get();

        return response()->json([
            'deliveries' => $deliveries
        ], 200);
    }

    public function indexUser(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 1️⃣ Get account info
        $account = Account::select(
            'id',
            'first_name',
            'last_name',
            'phone_number',
            'email',
            'profile_image'
        )
        ->where('id', $user->id)
        ->first();

        // 2️⃣ Get checkouts
        $checkouts = Checkout::select(
            'checkout_id',
            'user_id',
            'cart_id',
            'discount_id',
            'payment_method',
            'payment_details',
            'shipping_fee',
            'paid_amount',
            'paid_at',
            'special_instructions',
            'created_at'
        )
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

        // 3️⃣ Get delivery for each checkout
        $orders = [];

        foreach ($checkouts as $checkout) {

            $delivery = Delivery::where('checkout_id', $checkout->checkout_id)
                ->select(
                    'delivery_id',
                    'checkout_id',
                    'status',
                    'driver_id',
                    'notes',
                    'created_at'
                )
                ->first();

            $orders[] = [
                'checkout' => $checkout,
                'delivery' => $delivery
            ];
        }

        return response()->json([
            'account' => $account,
            'orders' => $orders
        ], 200);
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

        $delivery->status = $request->input('status');
        $delivery->save();

        return response()->json([
            'delivery_id' => $delivery->delivery_id,
            'checkout_id' => $delivery->checkout_id,
            'status'      => $delivery->status,
            'updated_at'  => $delivery->updated_at,
        ]);
    }
}