<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\BlogApproval;
use App\Models\Blog;

class BlogApprovalController extends Controller
{
    public function index()
    {
        return BlogApproval::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'date' => 'required|date',
            'author' => 'required|string|max:255',
            'category' => 'required|string|in:constitutional,contract,criminal',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'read_time' => 'required|string|max:50',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('blogImages', 'public');
            $validated['image'] = Storage::url($imagePath);
        }

        return BlogApproval::create($validated);
    }

    public function show($id)
    {
        return BlogApproval::findOrFail($id);
    }

    public function approve($id)
    {
        $blogApproval = BlogApproval::findOrFail($id);
        
        // Move to main blogs table
        $blog = Blog::create([
            'title' => $blogApproval->title,
            'content' => $blogApproval->content,
            'date' => $blogApproval->date,
            'author' => $blogApproval->author,
            'category' => $blogApproval->category,
            'image' => $blogApproval->image,
            'read_time' => $blogApproval->read_time,
        ]);

        // Delete from approvals
        $blogApproval->delete();

        return response()->json([
            'message' => 'Blog approved successfully',
            'blog' => $blog
        ]);
    }

    public function reject($id)
    {
        $blogApproval = BlogApproval::findOrFail($id);

        // Delete image if exists
        if ($blogApproval->image) {
            Storage::disk('public')->delete($blogApproval->image);
        }

        // Delete the approval record
        $blogApproval->delete();

        return response()->json([
            'message' => 'Blog rejected successfully'
        ]);
    }

    public function destroy($id)
    {
        $blogApproval = BlogApproval::findOrFail($id);

        // Delete image if exists
        if ($blogApproval->image) {
            Storage::disk('public')->delete($blogApproval->image);
        }

        $blogApproval->delete();

        return response()->json([
            'message' => 'Blog approval deleted successfully'
        ]);
    }
}