<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
class CommentController extends Controller
{
    public function index()
    {
        return Comment::with(['user', 'blog'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'blog_id' => 'required|exists:blogs,id',
            'comment' => 'required',
        ]);

        return Comment::create($validated);
    }

    public function show($id)
    {
        return Comment::with(['user', 'blog'])->findOrFail($id);
    }
    
    public function update(Request $request, $id)
    {
        $comment = Comment::findOrFail($id);
        $comment->update($request->all());
        return $comment;
    }

    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();
        return response()->json(['message' => 'Deleted']);
    }

}
