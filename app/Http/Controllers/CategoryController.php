<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    // Get all categories
    public function index()
    {
        try {
            $categories = Category::withCount('products')->get();

            return response()->json([
                'success' => true,
                'categories' => $categories
            ], 200);

        } catch (\Exception $e) {
            Log::error('Fetch categories failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories'
            ], 500);
        }
    }

    // Create category
    public function store(Request $request)
    {
        try {

            $request->validate([
                'category_name' => 'required|string|max:255|unique:categories,category_name',
                'description' => 'nullable|string'
            ]);

            $category = Category::create([
                'category_name' => $request->category_name,
                'description' => $request->description
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'category' => $category
            ], 201);

        } catch (\Exception $e) {

            Log::error('Create category failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create category'
            ], 500);
        }
    }

    // Show single category
    public function show($id)
    {
        try {

            $category = Category::with('products')->findOrFail($id);

            return response()->json([
                'success' => true,
                'category' => $category
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }
    }

    // Update category
    public function update(Request $request, $id)
    {
        try {

            $category = Category::findOrFail($id);

            $request->validate([
                'category_name' => 'required|string|max:255|unique:categories,category_name,' . $id . ',category_id',
                'description' => 'nullable|string'
            ]);

            $category->update([
                'category_name' => $request->category_name,
                'description' => $request->description
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'category' => $category
            ], 200);

        } catch (\Exception $e) {

            Log::error('Update category failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update category'
            ], 500);
        }
    }

    // Delete category
    public function destroy($id)
    {
        try {

            $category = Category::findOrFail($id);

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ], 200);

        } catch (\Exception $e) {

            Log::error('Delete category failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category'
            ], 500);
        }
    }
}