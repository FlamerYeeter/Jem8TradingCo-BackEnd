<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
class ReviewController extends Controller
{

    // Get reviews for a specific product
    public function index($productId)
    {
        try {

            $product = Product::where('product_id', $productId)->first();

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found'
                ], 404);
            }

            $reviews = Review::where('product_id', $productId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $reviews
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);

        }
    }


    // Get single review
    public function show($id)
    {
        try {

            $review = Review::with('user','product')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $review
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => 'Review not found'
            ], 404);

        }
    }


    // Get all reviews
    public function all()
    {
        try {

            $reviews = Review::with('user','product')
                ->orderBy('created_at','desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $reviews
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);

        }
    }


    // Create review
    public function store(Request $request, $productId)
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $product = Product::where('product_id',$productId)->first();

            if (!$product) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Product not found'
                ],404);
            }

            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'review_text' => 'nullable|string|max:2000'
            ]);

            $review = Review::create([
                'product_id' => $productId,
                'user_id' => $user->id,
                'rating' => $request->rating,
                'review_text' => $request->review_text,
                'status' => 'pending'
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $review
            ],201);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ],500);

        }
    }


    // Update review
    public function update(Request $request, $id)
    {
        try {

            $review = Review::findOrFail($id);

            $request->validate([
                'rating' => 'sometimes|integer|min:1|max:5',
                'review_text' => 'nullable|string|max:2000'
            ]);

            $review->update([
                'rating' => $request->rating ?? $review->rating,
                'review_text' => $request->review_text ?? $review->review_text
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $review
            ],200);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ],500);

        }
    }


    // Delete review
    public function destroy($id)
    {
        try {

            $review = Review::findOrFail($id);

            $review->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Review deleted successfully'
            ],200);

        } catch (\Exception $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ],500);

        }
    }

}