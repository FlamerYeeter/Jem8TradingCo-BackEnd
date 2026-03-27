<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // ✅ GET - Reviews for a specific product (public)
    public function index($productId)
    {
        try {
            $product = Product::where('product_id', $productId)->first();

            if (!$product) {
                return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
            }

            $reviews = Review::where('product_id', $productId)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 'success', 'data' => $reviews], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ GET - Single review (public)
    public function show($id)
    {
        try {
            $review = Review::with('user', 'product')->findOrFail($id);

            return response()->json(['status' => 'success', 'data' => $review], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Review not found'], 404);
        }
    }

    // ✅ GET - All reviews (public)
    public function all()
    {
        try {
            $reviews = Review::with('user', 'product')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 'success', 'data' => $reviews], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ POST - Create review (authenticated user)
    public function store(Request $request, $productId)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $product = Product::where('product_id', $productId)->first();
            if (!$product) {
                return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
            }

            $request->validate([
                'rating'      => 'required|integer|min:1|max:5',
                'review_text' => 'nullable|string|max:2000',
            ]);

            $review = Review::create([
                'product_id'  => $productId,
                'user_id'     => $user->id,
                'rating'      => $request->rating,
                'review_text' => $request->review_text,
                'status'      => 'pending',
            ]);

            // ✅ Log: user submitted a review (safe: log failures won't break response)
            try {
                ActivityLog::log($user, 'Submitted a review', 'stock', [
                    'product_name'    => $product->product_name,
                    'description'     => $user->first_name . ' submitted a ' . $request->rating . '-star review on ' . $product->product_name,
                    'reference_table' => 'reviews',
                    'reference_id'    => $review->id,
                ]);
                $request->attributes->set('activity_logged', true);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('ActivityLog error (review store): ' . $e->getMessage());
            }

            return response()->json(['status' => 'success', 'data' => $review], 201);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // UPDATE review — no log needed
    public function update(Request $request, $id)
    {
        try {
            $review = Review::findOrFail($id);

            $request->validate([
                'rating'      => 'sometimes|integer|min:1|max:5',
                'review_text' => 'nullable|string|max:2000',
            ]);

            $review->update([
                'rating'      => $request->rating ?? $review->rating,
                'review_text' => $request->review_text ?? $review->review_text,
            ]);

            // Log update
            $user = $request->user() ?? Auth::user();
            if ($user) {
                try {
                    ActivityLog::log($user, 'Updated a review', 'stock', [
                        'description'     => ($user->first_name ?? $user->name ?? 'User') . ' updated a review',
                        'reference_table' => 'reviews',
                        'reference_id'    => $review->id,
                    ]);
                    $request->attributes->set('activity_logged', true);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('ActivityLog error (review update): ' . $e->getMessage());
                }
            }

            return response()->json(['status' => 'success', 'data' => $review], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // POST - Reply to review (admin) — no log needed
    public function reply(Request $request, $id)
    {
        try {
            $review = Review::findOrFail($id);

            $request->validate([
                'admin_reply' => 'required|string|max:2000',
            ]);

            $review->update([
                'admin_reply' => $request->admin_reply,
                'replied_at'  => now(),
            ]);

            // Log admin reply
            $admin = $request->user() ?? Auth::user();
            if ($admin) {
                try {
                    ActivityLog::log($admin, 'Replied to a review', 'stock', [
                        'description'     => ($admin->first_name ?? $admin->name ?? 'Admin') . ' replied to review #' . $review->id,
                        'reference_table' => 'reviews',
                        'reference_id'    => $review->id,
                    ]);
                    $request->attributes->set('activity_logged', true);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('ActivityLog error (review reply): ' . $e->getMessage());
                }
            }

            return response()->json(['status' => 'success', 'message' => 'Reply submitted successfully', 'data' => $review], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ DELETE - Delete review (authenticated user)
    public function destroy($id)
    {
        try {
            $review   = Review::findOrFail($id);
            $reviewId = $review->id;
            $review->delete();

            // ✅ Log: user deleted their review
            ActivityLog::log(Auth::user(), 'Deleted a review', 'stock', [
                'description'     => Auth::user()->first_name . ' deleted their review',
                'reference_table' => 'reviews',
                'reference_id'    => $reviewId,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Review deleted successfully'], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // DELETE reply (admin) — no log needed
    public function deleteReply($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->update(['admin_reply' => null, 'replied_at' => null]);

            // Log admin deleting reply
            $admin = Auth::user();
            if ($admin) {
                try {
                    ActivityLog::log($admin, 'Deleted a review reply', 'stock', [
                        'description'     => ($admin->first_name ?? $admin->name ?? 'Admin') . ' deleted reply for review #' . $review->id,
                        'reference_table' => 'reviews',
                        'reference_id'    => $review->id,
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('ActivityLog error (delete review reply): ' . $e->getMessage());
                }
            }
            return response()->json(['status' => 'success', 'message' => 'Reply deleted'], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}