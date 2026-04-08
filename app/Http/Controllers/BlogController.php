<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CategoryBlog;
use App\Models\Blog;
use App\Models\BlogImg;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class BlogController extends Controller
{
    // GET ALL BLOGS
    public function indexBlog(Request $request)
    {
        try {
            $query = Blog::with(['category', 'images']);

            if ($request->has('category')) {
                $categoryName = $request->input('category');
                $query->whereHas('category', function ($q) use ($categoryName) {
                    $q->where('category_name', $categoryName);
                });
            }

            $blogs = $query->get();

            if (Auth::check()) {
                ActivityLog::log(Auth::user(), 'Viewed blogs list', 'blogs', [
                    'description'     => Auth::user()->first_name . ' viewed the blogs list',
                    'reference_table' => 'blogs',
                ]);
            }

            return response()->json(['status' => 'success', 'data' => $blogs], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // CREATE BLOG
    public function storeBlog(Request $request)
    {
        try {
            $request->validate([
                'category_name'  => 'required|string|max:255',
                'blog_title'     => 'required|string|max:255',
                'blog_text'      => 'required|string',
                'featured_image' => 'nullable|image|max:5120',
                'images'         => 'nullable|array|max:3',
                'images.*'       => 'image|max:5120',
                'status'         => 'required|in:draft,published,archived',
            ]);

            $category = CategoryBlog::firstOrCreate([
                'category_name' => $request->input('category_name')
            ]);

            $data = [
                'category_blog_id' => $category->category_blog_id,
                'blog_title'       => $request->blog_title,
                'blog_text'        => $request->blog_text,
                'status'           => $request->status,
            ];

            // ✅ Fixed: store path first, then wrap with asset()
            if ($request->hasFile('featured_image')) {
                $path = $request->file('featured_image')->store('featured_images', 'public');
                $data['featured_image'] = asset('storage/' . $path);
            }

            $blog = Blog::create($data);

            // ✅ Fixed: blog images also use asset()
            if ($request->hasFile('images')) {
                $sideImages = array_slice($request->file('images'), 0, 3);
                foreach ($sideImages as $index => $image) {
                    $path = $image->store('blog_images', 'public');
                    BlogImg::create([
                        'blog_id' => $blog->blog_id,
                        'url'     => asset('storage/' . $path),
                        'order'   => $index,
                    ]);
                }
            }

            ActivityLog::log(Auth::user(), 'Created a blog post', 'blogs', [
                'description'     => Auth::user()->first_name . ' created blog: ' . $blog->blog_title,
                'reference_table' => 'blogs',
                'reference_id'    => $blog->blog_id,
            ]);

            return response()->json(['status' => 'success', 'data' => $blog->load('images')], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 'error', 'type' => 'validation', 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // GET BLOG BY ID
    public function showAllBlog($id)
    {
        try {
            $blog = Blog::with(['category', 'images'])->find($id);

            if (!$blog) {
                return response()->json(['status' => 'error', 'message' => 'Blog not found'], 404);
            }

            if (Auth::check()) {
                ActivityLog::log(Auth::user(), 'Viewed a blog post', 'blogs', [
                    'description'     => Auth::user()->first_name . ' viewed blog: ' . $blog->blog_title,
                    'reference_table' => 'blogs',
                    'reference_id'    => $id,
                ]);
            }

            return response()->json(['status' => 'success', 'data' => $blog], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // UPDATE BLOG
    public function blogUpdates(Request $request, $id)
    {
        try {
            $blog = Blog::find($id);

            if (!$blog) {
                return response()->json(['status' => 'error', 'message' => 'Blog not found'], 404);
            }

            $request->validate([
                'category_blog_id' => 'sometimes|exists:category_blog,category_blog_id',
                'blog_title'       => 'sometimes|string|max:255',
                'blog_text'        => 'sometimes|string',
                'featured_image'   => 'sometimes|image|max:2048',
                'images'           => 'sometimes|array',
                'images.*'         => 'image|max:2048',
                'status'           => 'sometimes|in:draft,published,archived',
            ]);

            // ✅ Fixed: only update plain fields, not raw request->all()
            $data = $request->only(['category_blog_id', 'blog_title', 'blog_text', 'status']);

            if ($request->hasFile('featured_image')) {
                $path = $request->file('featured_image')->store('featured_images', 'public');
                $data['featured_image'] = asset('storage/' . $path);
            }

            $blog->update($data);

            // ✅ Fixed: replace blog images on update
            if ($request->hasFile('images')) {
                $blog->images()->delete();
                foreach (array_slice($request->file('images'), 0, 3) as $index => $image) {
                    $path = $image->store('blog_images', 'public');
                    BlogImg::create([
                        'blog_id' => $blog->blog_id,
                        'url'     => asset('storage/' . $path),
                        'order'   => $index,
                    ]);
                }
            }
    
            ActivityLog::log(Auth::user(), 'Updated a blog post', 'blogs', [
                'description'     => Auth::user()->first_name . ' updated blog: ' . $blog->blog_title,
                'reference_table' => 'blogs',
                'reference_id'    => $id,
            ]);

            return response()->json(['status' => 'success', 'data' => $blog->load('images')], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => 'error', 'type' => 'validation', 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }

    // DELETE BLOG
    public function deleteBlog($id)
    {
        try {
            $blog = Blog::find($id);

            if (!$blog) {
                return response()->json(['status' => 'error', 'message' => 'Blog not found'], 404);
            }

            $title = $blog->blog_title;
            $blog->delete();

            ActivityLog::log(Auth::user(), 'Deleted a blog post', 'blogs', [
                'description'     => Auth::user()->first_name . ' deleted blog: ' . $title,
                'reference_table' => 'blogs',
                'reference_id'    => $id,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Blog deleted successfully'], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'type' => 'server', 'message' => $e->getMessage()], 500);
        }
    }
}