<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\Comment;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    public function index()
    {
        return Blog::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required',
            'content' => 'required',
            'date' => 'required|date',
            'author' => 'required',
            'category' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'read_time' => 'required',
            'plan_type' => 'required|string|in:free,basic,pro',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blogImages', 'public');
            $validated['image'] = Storage::url($imagePath);
        }

        return Blog::create($validated);
    }

    public function show($id)
    {
        return Blog::with('comments.user')->findOrFail($id);
    }

    public function comments($id)
    {
        $comments = Comment::with(['user', 'blog'])
            ->where('blog_id', $id)
            ->get();

        return response()->json($comments);
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required',
            'content' => 'required',
            'date' => 'required|date',
            'author' => 'required',
            'category' => 'required',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'read_time' => 'required',
            'plan_type' => 'required|string|in:free,basic,pro',
        ]);

        if ($request->hasFile('image')) {
            if ($blog->image) {
                $oldImagePath = str_replace('/storage', '', $blog->image);
                Storage::disk('public')->delete($oldImagePath);
            }

            $imagePath = $request->file('image')->store('blogImages', 'public');
            $validated['image'] = Storage::url($imagePath);
        }

        $blog->update($validated);
        return $blog;
    }

    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);

        if ($blog->image) {
            $imagePath = str_replace('/storage', '', $blog->image);
            Storage::disk('public')->delete($imagePath);
        }

        $blog->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
