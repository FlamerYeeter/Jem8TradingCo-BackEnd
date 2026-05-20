<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ProductRequestController extends Controller
{
    // Public: create a new product request
    public function store(Request $request)
    {
        try {
            // Log incoming payload for debugging frontend integration
            Log::info('ProductRequest store called', ['payload' => $request->all(), 'headers' => $request->headers->all()]);

            // Accept camelCase from some frontends, normalize to snake_case
            if ($request->has('productId') && !$request->has('product_id')) {
                $request->merge(['product_id' => $request->input('productId')]);
            }
            if ($request->has('productName') && !$request->has('product_name')) {
                $request->merge(['product_name' => $request->input('productName')]);
            }

            // Accept a `product` object (array or JSON) from some frontends
            if ($request->has('product') && !$request->has('product_id')) {
                $prod = $request->input('product');
                if (is_string($prod)) {
                    $decoded = json_decode($prod, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $prod = $decoded;
                    }
                }
                if (is_array($prod)) {
                    if (isset($prod['id'])) {
                        $request->merge(['product_id' => $prod['id']]);
                    } elseif (isset($prod['product_id'])) {
                        $request->merge(['product_id' => $prod['product_id']]);
                    }
                    if (!$request->has('product_name')) {
                        if (isset($prod['name'])) {
                            $request->merge(['product_name' => $prod['name']]);
                        } elseif (isset($prod['product_name'])) {
                            $request->merge(['product_name' => $prod['product_name']]);
                        }
                    }
                }
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

            return response()->json(['data' => $pr], 201);
        } catch (ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
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

            return response()->json(['data' => $results], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
        }
    }

    // Admin: list all product requests
    public function index()
    {
        try {
            $list = ProductRequest::with(['product','user'])->orderBy('created_at', 'desc')->get();
            return response()->json(['data' => $list], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
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
            return response()->json(['data' => $results], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
        }
    }

    // Admin: show single
    public function show($id)
    {
        try {
            $pr = ProductRequest::findOrFail($id);
            return response()->json(['data' => $pr], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
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
            return response()->json(['data' => $pr], 200);
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
        }
    }

    // Admin: delete
    public function destroy($id)
    {
        try {
            $pr = ProductRequest::findOrFail($id);
            $pr->delete();
            return response()->json(['data' => ['message' => 'Deleted']], 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
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
            return response()->json(['data' => $pr], 200);
        } catch (ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
        }
    }

    // Admin: create a minimal checkout + delivery from a product request and attach it
    public function createOrder(Request $request, $id)
    {
        try {
            $pr = ProductRequest::findOrFail($id);

            if ($pr->delivery_id) {
                return response()->json(['status' => 'error', 'message' => 'Product request already attached to a delivery'], 409);
            }

            $request->validate([
                'payment_method' => 'sometimes|string|max:255',
                'shipping_fee'   => 'sometimes|numeric|min:0',
                'special_instructions' => 'sometimes|nullable|string|max:2000',
            ]);

            $userId = $pr->user_id ?? optional($request->user())->id;

            \DB::beginTransaction();
            // Create a minimal checkout record
            $checkoutData = [
                'user_id' => $userId,
                'payment_method' => $request->input('payment_method', 'admin_created'),
                'shipping_fee' => $request->input('shipping_fee', 0),
                'paid_amount' => 0,
                'special_instructions' => $request->input('special_instructions', $pr->description),
            ];

            $checkout = \App\Models\Checkout::create($checkoutData);

            // Create delivery linked to the checkout
            $delivery = \App\Models\Delivery::create([
                'checkout_id' => $checkout->checkout_id,
                'status' => 'processing',
                'notes' => $pr->description,
            ]);

            // Attach delivery to product request and mark as found
            $pr->delivery_id = $delivery->delivery_id;
            $pr->status = 'found';
            $pr->save();

            \DB::commit();

            return response()->json([
                'data' => [
                    'product_request' => $pr,
                    'checkout' => $checkout,
                    'delivery' => $delivery,
                ],
            ], 201);
        } catch (\Illuminate\Database\QueryException $qe) {
            \DB::rollBack();
            return response()->json(['errors' => ['server' => [$qe->getMessage()]]], 500);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
        }
    }
}
