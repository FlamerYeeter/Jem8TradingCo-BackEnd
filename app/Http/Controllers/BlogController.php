<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CategoryBlog;
use App\Models\Blog;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = Blog::with('category');
        
        if ($request->has('category')){
            $categoryName = $request->input('category');
            $query->whereHas('category', function($q) use ($categoryName) {
                $q->where('category_name', $categoryName);
            });
        }
        
            $blogs = $query->get();

        return response()->json([
            'status'  => 'success',
            'data'    => $blogs,
        ], 200);
    }
}