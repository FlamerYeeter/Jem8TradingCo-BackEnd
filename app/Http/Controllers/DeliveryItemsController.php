<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateDeliveryItemsRequest;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use Illuminate\Support\Facades\DB;

class DeliveryItemsController extends Controller
{
    public function update(UpdateDeliveryItemsRequest $req, $id)
    {
        $items = $req->input('items', []);
        $delivery = Delivery::with('checkout','items')->findOrFail($id);

        DB::transaction(function() use($items, $delivery) {
            foreach ($items as $it) {
                $itemId = $it['id'] ?? null;
                if ($itemId) {
                    $row = DeliveryItem::find($itemId);
                    if ($row) {
                        $row->price = $it['price'];
                        $row->save();
                    }
                }
            }

            $checkout = $delivery->checkout;
            if ($checkout) {
                $sum = $checkout->items()->sum(DB::raw('COALESCE(price,0) * COALESCE(quantity,1)')) + ($checkout->shipping_fee ?? 0);
                $checkout->paid_amount = $sum;
                $checkout->save();
            }
        });

        return response()->json(['message' => 'Prices updated', 'delivery_id' => $delivery->id]);
    }
}
