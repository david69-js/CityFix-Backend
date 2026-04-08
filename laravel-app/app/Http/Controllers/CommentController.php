<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Issue;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index()
    {
        return response()->json(Comment::all());
    }

    public function store(Request $request, ?Issue $issue = null)
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
            'issue_id' => 'required_without:issue|exists:issues,id'
        ]);

        $issueId = $issue ? $issue->id : $request->input('issue_id');

        $comment = Comment::create([
            'issue_id' => $issueId,
            'user_id' => auth('api')->id(),
            'comment' => $validated['comment']
        ]);

        return response()->json($comment->load('user:id,first_name,last_name,avatar'), 201);
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
