<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Auth;

class ShopController extends Controller
{
    // gawin nyo nalang yung mga nasa list jan
    // Add product to cart
    public function addToCart(Request $request){
        try{

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'bruhhh'], 401);
        }

        $request->validate([
            'quantity'   => 'required|integer|min:1',
            'product_id' => 'required|integer|exists:products,product_id'
        ]);

        $product = Product::find($request->product_id);

        $cart = Cart::create([
            'user_id'    => $user->id,
            'quantity'   => $request->quantity,
            'product_id' => $request->product_id,
            'total'      => floatval($product->price) * intval($request->quantity),
            'status'     => 'pending',
        ]);

        ActivityLog::log($user, 'added product to cart', 'orders', [
                'product_name'    => $product->product_name,
                'amount'          => $cart->total,
                'description'     => $user->name . ' added ' . $product->product_name . ' x' . $request->quantity . ' to cart',
                'reference_table' => 'carts',
                'reference_id'    => $cart->id,
        ]);

        return response()->json([
            'message' => 'Product added to cart successfully',
            'cart'    => $cart
        ], 201);


        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->errors()
            ], 422);
        }
    }

    public function addProduct(Request $request)
    {

        try{

        $request->validate([
            'product_name' => 'required|string',
            'category_id' => 'required|integer',
            'description' => 'required|string',
            'price' => 'required|numeric',

            'image' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $isSale = $request->boolean('isSale');


        // Store image
        $imagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'product_name' => $request->product_name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'price' => $request->price,
            'isSale' => $isSale,
            'image' => $imagePath
        ]);

         ActivityLog::log(Auth::user(), 'Added a product', 'stock', [
                'product_name'        => $product->product_name,
                'product_unique_code' => 'PRD-' . $product->product_id,
                'amount'              => $product->price,
                'description'         => Auth::user()->name . ' added product: ' . $product->product_name . ' (Stocks: ' . $product->product_stocks . ')',
                'reference_table'     => 'products',
                'reference_id'        => $product->product_id,
            ]);

        return response()->json([
            'message' => 'Product added successfully',
            'data' => $product
        ]);


        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }catch(\Illuminate\Validation\ValidationException $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->errors()
            ], 422);
        }
    }

    // Show single product details with all images.(kukunin yung id ah)
    public function showProduct($id){

        try{

        $product = Product::with('images')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if (Auth::check()) {
                ActivityLog::log(Auth::user(), 'Viewed a product', 'stock', [
                    'product_name'        => $product->product_name,
                    'product_unique_code' => 'PRD-' . $product->product_id,
                    'description'         => Auth::user()->first_name . ' viewed product: ' . $product->product_name,
                    'reference_table'     => 'products',
                    'reference_id'        => $product->product_id,
                ]);
            }

        return response()->json([
            'product' => $product,
            'image' => $product->image
        ],200);

        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return response()->json([
                'status' => 'error',
                'type' => 'not_found',
                'message' => 'Product not found'
            ], 404);

        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'type' => 'server',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // Remove item from cart (kukunin yung id)
    public function deleteFromCart(string $id){

        try{

        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $cart->delete();

        return response()->json([
            'message' => 'Product removed from cart successfully'
        ], 200);

        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Cart item not found'
            ], 404);
        }
    }
    // Update quantity of a cart item (kukunin id)
    public function updateCartQuantity(Request $request, string $id){

        try{

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

        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);

        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Cart item not found'
            ], 404);
        }

    }

    // View current user's cart (kukunin yung id nag user galing sa cookie)

    public function viewCart(Request $request){
        try{

        $user = $request -> user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = Cart::with('product')
            ->where('user_id', $user->id);

        // Filter by isCheckout true or false
        if ($request->has('isCheckout')) {
            $query->where('is_checkout', $request->boolean('isCheckout'));
        }

        $cartItems = $query->get();

        return response()->json([
            'cartItems' => $cartItems
        ], 200);


        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);

        }catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Cart not found'
            ], 404);
        }

    }
}
