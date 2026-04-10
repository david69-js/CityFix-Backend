<?php

namespace App\Http\Controllers\Api;

use App\Models\Issue;
use App\Models\Upvote;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;

class UpvoteController extends Controller
{
    public function toggle(Issue $issue)
    {
        $userId = auth()->id();

        $existing = Upvote::where('issue_id', $issue->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'message' => 'Upvote removido correctamente.',
                'upvoted' => false,
                'upvotes_count' => $issue->upvotes()->count(),
            ]);
        }

        try {
            Upvote::create([
                'issue_id' => $issue->id,
                'user_id' => $userId,
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'No se pudo registrar el upvote.',
            ], 409);
        }

        return response()->json([
            'message' => 'Upvote agregado correctamente.',
            'upvoted' => true,
            'upvotes_count' => $issue->upvotes()->count(),
        ]);
    }
}