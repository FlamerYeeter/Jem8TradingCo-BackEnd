<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AdminProductController extends Controller
{
    // CREATE Product with multiple images
    public function addProduct(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'product_name'   => 'required|string',
                'category_id'    => 'required|integer|exists:categories,category_id',
                'product_stocks' => 'required|integer|min:0',
                'description'    => 'nullable|string',
                'price'          => 'required|numeric',
                'isSale'         => 'boolean',
                'images'         => 'sometimes|array',
                'images.*'       => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            ]);

            // Log request info
            Log::info('=== ADD PRODUCT REQUEST ===');
            Log::info('Product data:', $request->except('images'));
            Log::info('Has images?', [
                'has_files' => $request->hasFile('images'),
                'file_count' => $request->hasFile('images') ? count($request->file('images')) : 0
            ]);

            // Create product
            $product = Product::create([
                'product_name'   => $request->product_name,
                'category_id'    => $request->category_id,
                'product_stocks' => $request->product_stocks,
                'description'    => $request->description,
                'price'          => $request->price,
                'isSale'         => $request->isSale ?? false,
            ]);

            Log::info('Product created with ID: ' . $product->product_id);

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

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product,
                'debug' => [
                    'images_uploaded' => $product->images->count(),
                    'has_files' => $request->hasFile('images'),
                    'file_count' => $request->hasFile('images') ? count($request->file('images')) : 0
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
            $request->validate([
                'product_name'   => 'sometimes|required|string',
                'category_id'    => 'sometimes|required|integer|exists:categories,category_id',
                'product_stocks' => 'sometimes|required|integer|min:0',
                'description'    => 'nullable|string',
                'price'          => 'sometimes|required|numeric',
                'isSale'         => 'boolean',
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

            // Update product details
            $product->update($request->only([
                'product_name',
                'category_id',
                'product_stocks',
                'description',
                'price',
                'isSale'
            ]));

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

            $product->load(['category', 'images']);

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
