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
        // $user = $request->user();

        // if (!$user) {
        //     return response()->json(['message' => 'Unauthorized'], 401);
        // }

        $request -> validate([
            'quantity' => 'required|integer|min:1',
            'product_id' => 'required|integer|exists:products,id'
        ]);
        
        $Cart = Cart::create([
            'quantity' => $request->quantity,
            'product_id' => $request->product_id,
            // 'account_id' => $user->id   // need updates
        ]);
        
        if (!$Cart) {
            return response()->json(['message' => 'Failed to add product to cart'], 500);
        }

        return response()->json([
            'message' => 'Product added to cart successfully',
            'cart' => $Cart
        ], 201);

    }

    public function addProduct(Request $request)
{
    $request->validate([
        'product_name'   => 'required|string',
        'category_id'    => 'required|integer|exists:categories,category_id',
        'product_stocks' => 'required|integer|min:0',
        'description'    => 'nullable|string',
        'price'          => 'required|numeric',
        'isSale'         => 'boolean',
    ]);

    $product = Product::create([
        'product_name'   => $request->product_name,   // ✅ matches migration
        'category_id'    => $request->category_id,    // ✅ matches migration
        'product_stocks' => $request->product_stocks, // ✅ matches migration
        'description'    => $request->description,
        'price'          => $request->price,
        'isSale'         => $request->isSale ?? false,
    ]);

    return response()->json([
        'message' => 'Product created successfully',
        'product' => $product
    ], 201); // ✅ 201 Created
}


    // Show single product details with all images.(kukunin yung id ah)
<<<<<<< HEAD
public function 
=======
    public function showProduct($request, $id){
        $product = Product::with('ProductImages')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        
        return response()->json([
            'product' => $product,
            'images' => $product->ProductImages
        ],200);
    }
>>>>>>> 8ee739177731040e4861b1a584d56f3d1a77b920
    // Remove item from cart (kukunin yung id)
    public function deleteFromCart(string $id){
        $Cart = Cart::find($id);
        $Cart->delete();

        if (!$Cart) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

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

        $cartItems = Cart::with('Product') -> where ('account_id',$user->id) -> get();
        return response()->json([
            'cartItems' => $cartItems
        ], 200);
    }


}
