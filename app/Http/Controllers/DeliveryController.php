<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
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