<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductRequest;
use Illuminate\Support\Facades\Auth;

class ProductRequestController extends Controller
{
    // Public: create a new product request
    public function store(Request $request)
    {
        try {
            // Accept camelCase from some frontends, normalize to snake_case
            if ($request->has('productId') && !$request->has('product_id')) {
                $request->merge(['product_id' => $request->input('productId')]);
            }
            if ($request->has('productName') && !$request->has('product_name')) {
                $request->merge(['product_name' => $request->input('productName')]);
            }

            $request->validate([
                'product_id'   => 'nullable|numeric',
                'product_name' => 'nullable|string|max:255',
                'description'  => 'nullable|string|max:2000',
                'image'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('product_requests', 'public');
            }

            $pr = ProductRequest::create([
                'user_id'      => optional(Auth::user())->id,
                'product_id'   => $request->input('product_id'),
                'product_name' => $request->input('product_name'),
                'description'  => $request->input('description'),
                'image_path'   => $imagePath,
                'status'       => 'pending',
            ]);

            return response()->json(['status' => 'success', 'data' => $pr], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Public: simple product search to support frontend auto-resolve
    public function searchProducts(Request $request)
    {
        try {
            $q = $request->query('q');
            if (empty($q)) {
                return response()->json(['status' => 'error', 'message' => 'Query required'], 400);
            }

            $results = \App\Models\Product::where('product_name', 'like', "%{$q}%")
                ->limit(10)
                ->get(['product_id as id', 'product_name as name', 'price']);

            return response()->json(['status' => 'success', 'data' => $results], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Admin: list all product requests
    public function index()
    {
        try {
            $list = ProductRequest::orderBy('created_at', 'desc')->get();
            return response()->json(['status' => 'success', 'data' => $list], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Public: list or filter product requests. Supports optional query params
    // - delivery_id: integer
    // - product_id: integer
    // If authenticated, limits to current user's requests when no filters provided.
    public function indexPublic(Request $request)
    {
        try {
            $q = ProductRequest::query();

            if ($request->has('delivery_id')) {
                $q->where('delivery_id', $request->input('delivery_id'));
            }
            if ($request->has('product_id')) {
                $q->where('product_id', $request->input('product_id'));
            }

            if (! $request->hasAny(['delivery_id','product_id'])) {
                // if user is authenticated, return their requests; otherwise return empty array
                if (optional(Auth::user())->id) {
                    $q->where('user_id', Auth::id());
                } else {
                    return response()->json(['status' => 'success', 'data' => []], 200);
                }
            }

            $results = $q->orderBy('created_at', 'desc')->get();
            return response()->json(['status' => 'success', 'data' => $results], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Admin: show single
    public function show($id)
    {
        try {
            $pr = ProductRequest::findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $pr], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Admin: update status (pending, found, not-available)
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate(['status' => 'required|in:pending,found,not-available']);
            $pr = ProductRequest::findOrFail($id);
            $pr->status = $request->input('status');
            $pr->save();
            return response()->json(['status' => 'success', 'data' => $pr], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Admin: delete
    public function destroy($id)
    {
        try {
            $pr = ProductRequest::findOrFail($id);
            $pr->delete();
            return response()->json(['status' => 'success', 'message' => 'Deleted'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // Admin: generic update to set status and attach to a delivery
    public function update(Request $request, $id)
    {
        try {
            // accept camelCase keys from frontend
            if ($request->has('deliveryId') && !$request->has('delivery_id')) {
                $request->merge(['delivery_id' => $request->input('deliveryId')]);
            }

            $request->validate([
                'status' => 'nullable|in:pending,found,not-available',
                'delivery_id' => 'nullable|numeric',
            ]);

            $pr = ProductRequest::findOrFail($id);

            if ($request->has('status')) {
                $pr->status = $request->input('status');
            }
            if ($request->has('delivery_id')) {
                $pr->delivery_id = $request->input('delivery_id');
            }

            $pr->save();
            return response()->json(['status' => 'success', 'data' => $pr], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
