<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // List current user's cart items
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $items = Cart::where('user_id', $user->id)
            ->with('product')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($items);
    }

    // Add item to cart (or increase quantity)
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Support both a single item payload and an array of items
        // Single: { product_id, quantity }
        // Multiple: { items: [ { product_id, quantity }, ... ] }

        $payload = $request->all();

        $responses = [];

        $items = [];
        if (isset($payload['items']) && is_array($payload['items'])) {
            $items = $payload['items'];
        } else {
            $items = [
                [
                    'product_id' => $request->input('product_id'),
                    'quantity' => $request->input('quantity', 1),
                ]
            ];
        }

        foreach ($items as $it) {
            $validator = \Illuminate\Support\Facades\Validator::make($it, [
                'product_id' => 'required|integer|exists:products,product_id',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                $responses[] = ['error' => $validator->errors(), 'item' => $it];
                continue;
            }

            $product = Product::where('product_id', $it['product_id'])->first();
            if (! $product) {
                $responses[] = ['error' => 'Product not found', 'item' => $it];
                continue;
            }

            $cartItem = Cart::where('user_id', $user->id)
                ->where('product_id', $product->product_id)
                ->first();

            $quantity = intval($it['quantity']);

            if ($cartItem) {
                $cartItem->quantity += $quantity;
                $cartItem->total = floatval($product->price) * $cartItem->quantity;
                $cartItem->save();
                $responses[] = $cartItem;
                continue;
            }

            $new = Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->product_id,
                'quantity' => $quantity,
                'total' => floatval($product->price) * $quantity,
                'status' => 'active',
            ]);

            $responses[] = $new;
        }

        // If only one item was in request, return that resource and status 201 when created
        if (count($responses) === 1 && isset($payload['product_id'])) {
            $single = $responses[0];
            if (is_array($single) && isset($single['error'])) {
                return response()->json($single, 422);
            }
            return response()->json($single, 201);
        }

        return response()->json($responses);
    }

    // Remove cart item by product id
    public function destroyByProduct(Request $request, $productId)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $cart = Cart::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if (! $cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cart->delete();
        return response()->json(['message' => 'Cart item removed']);
    }

    // Clear entire cart for user
    public function clear(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        Cart::where('user_id', $user->id)->delete();
        return response()->json(['message' => 'Cart cleared']);
    }

    // Update cart item quantity
    public function update(Request $request, $cartId)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::find($cartId);
        if (! $cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        if ($cart->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $product = Product::where('product_id', $cart->product_id)->first();
        $cart->quantity = intval($data['quantity']);
        $cart->total = floatval($product->price) * $cart->quantity;
        $cart->save();

        return response()->json($cart);
    }

    // Remove cart item
    public function destroy(Request $request, $cartId)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $cart = Cart::find($cartId);
        if (! $cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        if ($cart->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cart->delete();
        return response()->json(['message' => 'Cart item removed']);
    }
}
