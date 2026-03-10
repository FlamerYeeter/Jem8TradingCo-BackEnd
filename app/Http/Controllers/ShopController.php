<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductImage;

class ShopController extends Controller
{
    // gawin nyo nalang yung mga nasa list jan
    // Add product to cart
public function addToCart(Request $request)
{
    // Use auth() helper instead of $request->user() for reliability
    $user = auth('sanctum')->user();

    if (!$user) {
        \Log::warning('Cart add failed – no authenticated user', ['ip' => $request->ip()]);
        return response()->json(['message' => 'Unauthorized – please log in'], 401);
    }

    // Validate request
    $request->validate([
        'quantity'   => 'required|integer|min:1',
        'product_id' => 'required|integer|exists:products,product_id',
    ]);

    // Find product
    $product = Product::find($request->product_id);
    if (!$product) {
        \Log::warning("Cart add failed – product_id {$request->product_id} not found");
        return response()->json(['message' => 'Product not found'], 404);
    }

    try {
        // Create or update cart item
        $cart = Cart::updateOrCreate(
            [
                'user_id'    => $user->id,
                'product_id' => $product->product_id,
                'status'     => 'pending',
            ],
            [
                'quantity' => $request->quantity,
                'total'    => $product->price * $request->quantity,
            ]
        );

        return response()->json([
            'message' => 'Product added to cart successfully',
            'cart'    => $cart,
        ], 201);

    } catch (\Exception $e) {
        \Log::error('Cart add failed: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to add product to cart'], 500);
    }
}

    public function addProduct(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string',
            'category_id' => 'required|integer',
            'product_stocks' => 'required|integer',
            'description' => 'required|string',
            'price' => 'required|numeric',
            
            'image' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $isSale = $request->boolean('isSale');

        // Store image
        $imagePath = $request->file('image')->store('products', 'public');

        $product = Product::create([
            'product_name' => $request->product_name,
            'category_id' => $request->category_id,
            'product_stocks' => $request->product_stocks,
            'description' => $request->description,
            'price' => $request->price,
            'isSale' => $isSale,
            'image' => $imagePath
        ]);

        return response()->json([
            'message' => 'Product added successfully',
            'data' => $product
        ]);
    }   

    // Show single product details with all images.(kukunin yung id ah)
    public function showProduct($id){
        $product = Product::with('images')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json([
            'product' => $product,
            'image' => $product->image
        ],200);
    }
    // Remove item from cart (kukunin yung id)
    public function deleteFromCart(string $id){
        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cart->delete();

        return response()->json([
            'message' => 'Product removed from cart successfully'
        ], 200);
    }
    // Update quantity of a cart item (kukunin id)
    public function updateCartQuantity(Request $request, string $id){
        $request->validate([
            'quantity' => 'required|integer|min:1'

        ]);

        $Cart = Cart::find($id);

        if(!$Cart){
            return response()->json(['message' => 'Cart item not found'], 404);
        }


        $Cart->quantity = $request->quantity;
        $Cart->save();

        return response()->json([
            'message' => 'Cart quantity updated successfully',
            'cart' => $Cart
        ], 200);

    }

    // View current user's cart (kukunin yung id nag user galing sa cookie)

    public function viewCart(Request $request){
        $user = $request -> user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();
        
        return response()->json([
            'cartItems' => $cartItems
        ], 200);

    }


}
