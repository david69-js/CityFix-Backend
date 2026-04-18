<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Issue;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Issue $issue)
    {
        $comments = $issue->comments()
            ->with('user:id,first_name,last_name,avatar,email')
            ->latest()
            ->get();

        return response()->json($comments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $comment = Comment::create($validated);
        return response()->json($comment, 201);
    }

    public function show(Comment $comment)
    {
        return response()->json($comment);
    }

    public function update(Request $request, Comment $comment)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $comment->update($validated);
        return response()->json($comment);
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
        return response()->json(null, 204);
    }
}
