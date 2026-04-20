<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
class AdminProductController extends Controller
{
    // CREATE Product with multiple images
    public function addProduct(Request $request)
    {
        try {
            // Validate request - ADDED missing fields
            $request->validate([
                'product_name'   => 'required|string',
                'category_id'    => 'required|integer|exists:categories,category_id',
                'description'    => 'nullable|string',
                'price'          => 'required|numeric',
                'isSale'         => 'boolean',
                'acquired_price' => 'nullable|numeric|min:0',  // ADDED
                'unit'           => 'nullable|string|max:255',  // ADDED
                'size'           => 'nullable|string|max:255',  // ADDED
                'color'          => 'nullable|string|max:255',  // ADDED
                'images'         => 'sometimes|array',
                'images.*'       => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
                'status' => 'nullable|string|in:in_stock,pre_order',
                'receipt_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            // Log request info
            Log::info('=== ADD PRODUCT REQUEST ===');
            Log::info('Product data:', $request->except('images'));
            Log::info('Has images?', [
                'has_files' => $request->hasFile('images'),
                'file_count' => $request->hasFile('images') ? count($request->file('images')) : 0
            ]);

            // Create product - ADDED missing fields
            $product = Product::create([
                'product_name'   => $request->product_name,
                'category_id'    => $request->category_id,
                'description'    => $request->description,
                'status' => $request->status ?? 'in_stock',
                'price'          => $request->price,
                'isSale'         => $request->isSale ?? false,
                'acquired_price' => $request->acquired_price ?? 0,  // ADDED
                'unit'           => $request->unit ?? null,         // ADDED
                'size'           => $request->size ?? null,         // ADDED
                'color'          => $request->color ?? null,        // ADDED
            ]);

            Log::info('Product created with ID: ' . $product->product_id);
            Log::info('Product color saved: ' . ($product->color ?? 'null')); // Debug

            // Handle image upload
            if ($request->hasFile('images')) {
                $images = $request->file('images');

                // Create directory if it doesn't exist
                $uploadPath = public_path('storage/products');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                    Log::info('Created directory: ' . $uploadPath);
                }

                foreach ($images as $index => $image) {
                    try {
                        // Check if file is valid
                        if (!$image->isValid()) {
                            Log::error('Invalid image file', [
                                'index' => $index,
                                'error' => $image->getErrorMessage()
                            ]);
                            continue;
                        }

                        // Generate unique filename
                        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                        $extension = $image->getClientOriginalExtension();
                        $filename = time() . '_' . uniqid() . '.' . $extension;

                        Log::info('Processing image:', [
                            'index' => $index,
                            'original' => $originalName,
                            'filename' => $filename,
                            'size' => $image->getSize(),
                            'mime' => $image->getMimeType()
                        ]);

                        // Move file to public/storage/products
                        $image->move($uploadPath, $filename);

                        // Save to database
                        $productImage = ProductImage::create([
                            'product_id' => $product->product_id,
                            'image_path' => 'products/' . $filename,
                            'is_primary' => $index === 0
                        ]);

                        Log::info('Image saved to database', [
                            'image_id' => $productImage->image_id,
                            'path' => $productImage->image_path
                        ]);

                    } catch (\Exception $e) {
                        Log::error('Error uploading image: ' . $e->getMessage(), [
                            'index' => $index,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
            } else {
                Log::warning('No images found in request');
                Log::info('Request files:', $request->allFiles());
            }

            // Load relationships
            $product->load(['category', 'images']);
            DB::table('notifications')->insert([
                'user_id' => Auth::id(),
                'type'       => 'product',
                'title'      => 'New Product Added',
                'message'    => "\"{$product->product_name}\" was added to the catalog.",
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Cache::forget('dashboard.notifications');
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product,
                'debug' => [
                    'images_uploaded' => $product->images->count(),
                    'has_files' => $request->hasFile('images'),
                    'file_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
                    'color_saved' => $product->color  // Debug info
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Add product failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // SHOW all products
    public function showAllProducts()
    {
        try {
            $products = Product::with(['category', 'images'])->get();

            return response()->json([
                'success' => true,
                'products' => $products
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // SHOW single product
    public function showProduct($id)
    {
        try {
            $product = Product::with(['category', 'images'])->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'product' => $product
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // UPDATE Product
    public function updateProduct(Request $request, $id)
    {
        try {
            // Normalize remove_images input so validation sees an array of IDs
            if ($request->has('remove_images')) {
                $remove = $request->input('remove_images');

                if (is_string($remove)) {
                    // If it's a JSON array string, decode it
                    $decoded = json_decode($remove, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $request->merge(['remove_images' => array_values($decoded)]);
                    } elseif (strpos($remove, ',') !== false) {
                        // Comma-separated list
                        $parts = array_filter(array_map('trim', explode(',', $remove)), function ($v) {
                            return $v !== '';
                        });
                        $request->merge(['remove_images' => array_values($parts)]);
                    } else {
                        // Single scalar value
                        $request->merge(['remove_images' => [$remove]]);
                    }
                } elseif (!is_array($remove)) {
                    // Coerce other scalar types into array
                    $request->merge(['remove_images' => [$remove]]);
                }

                // Cast numeric-looking values to int where possible
                $normalized = array_map(function ($v) {
                    if (is_numeric($v)) {
                        return (int)$v;
                    }
                    return $v;
                }, $request->input('remove_images'));

                $request->merge(['remove_images' => $normalized]);
            }

            $request->validate([
                'product_name'   => 'sometimes|required|string',
                'category_id'    => 'sometimes|required|integer|exists:categories,category_id',
                'description'    => 'nullable|string',
                'status' => 'nullable|string|in:in_stock,pre_order',
                'price'          => 'sometimes|required|numeric',
                'isSale'         => 'boolean',
                'acquired_price' => 'nullable|numeric|min:0',  // ADDED
                'unit'           => 'nullable|string|max:255',  // ADDED
                'size'           => 'nullable|string|max:255',  // ADDED
                'color'          => 'nullable|string|max:255',  // ADDED
                'images'         => 'sometimes|array',
                'images.*'       => 'image|mimes:jpeg,png,jpg,gif|max:5120',
                'remove_images'  => 'sometimes|array',
                'remove_images.*' => 'integer|exists:product_images,image_id',
                'set_primary_image' => 'sometimes|integer|exists:product_images,image_id'
            ]);

            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Update product details - ADDED missing fields
            $product->update($request->only([
            'product_name',
            'category_id',
            'description',
            'price',
            'isSale',
            'acquired_price',
            'unit',
            'size',
            'color',
            'status'
        ]));

            Log::info('Product updated - ID: ' . $product->product_id);
            Log::info('Updated color: ' . ($product->color ?? 'null'));

            // Remove specified images
            if ($request->has('remove_images')) {
                $imagesToRemove = ProductImage::whereIn('image_id', $request->remove_images)
                                            ->where('product_id', $product->product_id)
                                            ->get();

                foreach ($imagesToRemove as $image) {
                    // Delete file
                    $filePath = public_path('storage/' . $image->image_path);
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    // Delete record
                    $image->delete();
                }
            }

            // Upload new images
            if ($request->hasFile('images')) {
                $uploadPath = public_path('storage/products');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                foreach ($request->file('images') as $image) {
                    try {
                        $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                        $image->move($uploadPath, $filename);

                        ProductImage::create([
                            'product_id' => $product->product_id,
                            'image_path' => 'products/' . $filename,
                            'is_primary' => false
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Error uploading image during update: ' . $e->getMessage());
                    }
                }
            }

            // Set primary image if specified
            if ($request->has('set_primary_image')) {
                $product->images()->update(['is_primary' => false]);
                ProductImage::where('image_id', $request->set_primary_image)
                        ->where('product_id', $product->product_id)
                        ->update(['is_primary' => true]);
            }

            // If no primary image, set first as primary
            if ($product->images()->where('is_primary', true)->count() === 0 && $product->images()->count() > 0) {
                $product->images()->first()->update(['is_primary' => true]);
            }


            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update product failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // DELETE Product
    public function deleteProduct($id)
    {
        try {
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Delete image files
            foreach ($product->images as $image) {
                $filePath = public_path('storage/' . $image->image_path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Delete product (images will cascade delete)
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // TEST UPLOAD METHOD
    public function testUpload(Request $request)
    {
        try {
            Log::info('=== TEST UPLOAD ===');

            if (!$request->hasFile('test_image')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file uploaded',
                    'request_keys' => array_keys($request->all()),
                    'files' => $request->allFiles()
                ], 400);
            }

            $file = $request->file('test_image');

            // Create directory
            $uploadPath = public_path('storage/test');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Save file
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($uploadPath, $filename);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'file' => [
                    'original_name' => $file->getClientOriginalName(),
                    'saved_as' => $filename,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'url' => asset('storage/test/' . $filename)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // STORAGE INFO METHOD
    public function storageInfo()
    {
        $storagePath = public_path('storage');
        $productsPath = public_path('storage/products');

        $files = [];
        if (is_dir($productsPath)) {
            $files = scandir($productsPath);
            $files = array_values(array_diff($files, ['.', '..']));
        }

        return response()->json([
            'storage_link_exists' => file_exists($storagePath),
            'storage_link_target' => $storagePath,
            'products_directory' => [
                'path' => $productsPath,
                'exists' => is_dir($productsPath),
                'writable' => is_writable($productsPath),
                'files' => $files
            ],
            'storage_app_public' => [
                'path' => storage_path('app/public'),
                'exists' => is_dir(storage_path('app/public')),
                'writable' => is_writable(storage_path('app/public'))
            ]
        ]);
    }
}
