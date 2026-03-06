<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index($productId)
    {
        $product = Product::where('product_id', $productId)->first();
        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $reviews = Review::where('product_id', $productId)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($reviews);
    }

    public function show($id)
    {
        $review = Review::with('user', 'product')->find($id);
        if (! $review) {
            return response()->json(['message' => 'Review not found'], 404);
        }
        return response()->json($review);
    }

    // Return all reviews (public)
    public function all()
    {
        $reviews = Review::with('user', 'product')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($reviews);
    }

    public function store(Request $request, $productId)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $product = Product::where('product_id', $productId)->first();
        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:2000',
        ]);

        $review = Review::create([
            'product_id' => $productId,
            'user_id' => $user->id,
            'rating' => $data['rating'],
            'review_text' => $data['review_text'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json($review, 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $review = Review::find($id);
        if (! $review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        if ($review->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:2000',
        ]);

        $review->update($data);

        return response()->json($review);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $review = Review::find($id);
        if (! $review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        if ($review->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted']);
    }
}
