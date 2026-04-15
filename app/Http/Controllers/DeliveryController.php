<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Models\Delivery;
use App\Models\Account;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeliveryController extends Controller
{
    // =========================================================
    // ADMIN: ALL DELIVERIES
    // =========================================================
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $query = Delivery::with([
            'checkout.user',
            'checkout.items.product.images',
            'checkout.receipt',
        ]);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $deliveries = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($delivery) {

                $checkout = $delivery->checkout;

                return [
                    'delivery_id' => $delivery->delivery_id,
                    'status' => $delivery->status,
                    'notes' => $delivery->notes,
                    'created_at' => $delivery->created_at,

                    'checkout' => $checkout ? [
                        'checkout_id' => $checkout->checkout_id,
                        'payment_method' => $checkout->payment_method,
                        'paid_amount' => $checkout->paid_amount,
                        'created_at' => $checkout->created_at,

                        // RECEIPT
                        'receipt' => $checkout->receipt ? [
                            'receipt_id' => $checkout->receipt->receipt_id,
                            'receipt_number' => $checkout->receipt->receipt_number,
                            'receipt_image_url' => $checkout->receipt->receipt_image
                                ? asset('storage/' . $checkout->receipt->receipt_image)
                                : null,
                        ] : null,

                        // ITEMS
                        'items' => $checkout->items->map(function ($item) {

                            $product = $item->product;

                            return [
                                'product_id'   => $item->product_id,
                                'quantity'     => $item->quantity,
                                'price'        => $item->price,
                                'total'        => $item->price * $item->quantity,

                                'product' => $product ? [
                                    'product_id'   => $product->product_id,
                                    'product_name' => $product->product_name,
                                    'description'  => $product->description,
                                    'price'        => $product->price,
                                    'status'       => $product->status,
                                    'stock'        => $product->stock,

                                    'image' => $product->primary_image_url
                                        ?? (optional($product->images->first())->image_path
                                            ? asset('storage/' . $product->images->first()->image_path)
                                            : null),
                                ] : null,
                            ];
                        }),
                    ] : null,
                ];
            });

        ActivityLog::log($user, 'Viewed deliveries list', 'orders', [
            'description' => $user->first_name . ' viewed deliveries list',
            'reference_table' => 'deliveries',
        ]);

        return response()->json(['deliveries' => $deliveries], 200);
    }

    // =========================================================
    // USER: OWN DELIVERIES
    // =========================================================
    public function indexUser(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // ✅ ACCOUNT (WITH TIN + COMPANY + IMAGE)
        $account = Account::select(
            'id',
            'first_name',
            'last_name',
            'phone_number',
            'email',
            'profile_image',
            'company_name',
            'tin_number'
        )
        ->where('id', $user->id)
        ->first();

        $checkouts = Checkout::with([
            'items.product.images',
            'receipt',
            'delivery'
        ])
        ->where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get();

        $orders = $checkouts->map(function ($checkout) {

            return [
                'checkout' => [
                    'checkout_id' => $checkout->checkout_id,
                    'payment_method' => $checkout->payment_method,
                    'payment_details' => $checkout->payment_details,
                    'shipping_fee' => $checkout->shipping_fee,
                    'paid_amount' => $checkout->paid_amount,
                    'paid_at' => $checkout->paid_at,
                    'special_instructions' => $checkout->special_instructions,
                    'created_at' => $checkout->created_at,

                    // RECEIPT
                    'receipt' => $checkout->receipt ? [
                        'receipt_id' => $checkout->receipt->receipt_id,
                        'receipt_number' => $checkout->receipt->receipt_number,
                        'receipt_image_url' => $checkout->receipt->receipt_image
                            ? asset('storage/' . $checkout->receipt->receipt_image)
                            : null,
                    ] : null,

                    // ITEMS + IMAGE FIX
                    'items' => $checkout->items->map(function ($item) {

                        $product = $item->product;

                        return [
                            'product_id'   => $item->product_id,
                            'product_name' => $product->product_name ?? null,
                            'price'        => $item->price,
                            'quantity'     => $item->quantity,
                            'total'        => $item->price * $item->quantity,

                            'image' => $product?->primary_image_url
                                ?? (optional($product->images->first())->image_path
                                    ? asset('storage/' . $product->images->first()->image_path)
                                    : null),
                        ];
                    }),
                ],

                'delivery' => $checkout->delivery
            ];
        });

        ActivityLog::log($user, 'Viewed delivery status', 'orders', [
            'description' => $user->first_name . ' checked delivery status',
            'reference_table' => 'deliveries',
        ]);

        return response()->json([
            'account' => [
                'id'           => $account->id,
                'first_name'   => $account->first_name,
                'last_name'    => $account->last_name,
                'phone_number' => $account->phone_number,
                'email'        => $account->email,
                'profile_image'=> $account->profile_image,
                'company_name' => $account->company_name,
                'tin_number'   => $account->tin_number,
            ],
            'orders' => $orders
        ], 200);
    }

    // =========================================================
    // UPDATE DELIVERY STATUS
    // =========================================================
    public function updateStatus(Request $request, $deliveryId)
    {
        $request->validate([
            'status' => 'required|in:processing,ready,on_the_way,delivered',
        ]);

        $delivery = Delivery::find($deliveryId);

        if (!$delivery) {
            return response()->json(['message' => 'Delivery not found'], 404);
        }

        $oldStatus = $delivery->status;
        $delivery->status = $request->input('status');
        $delivery->save();

        ActivityLog::log(Auth::user(), 'Updated delivery status', 'orders', [
            'description' =>
                Auth::user()->first_name .
                " updated delivery #{$deliveryId} from {$oldStatus} to {$delivery->status}",
            'reference_table' => 'deliveries',
            'reference_id' => $deliveryId,
        ]);

        return response()->json([
            'delivery_id' => $delivery->delivery_id,
            'checkout_id' => $delivery->checkout_id,
            'status' => $delivery->status,
            'updated_at' => $delivery->updated_at,
        ]);
    }
}