<?php

namespace App\Http\Controllers\Api;

use App\Models\Issue;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Issue $issue)
    {
        $comment = $issue->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => $request->comment,
        ]);

        $comment->load('user:id,first_name,last_name,email,avatar');

        return response()->json([
            'message' => 'Comentario agregado correctamente.',
            'data' => $comment,
        ], 201);
    }
}