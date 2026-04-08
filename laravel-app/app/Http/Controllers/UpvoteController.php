<?php

namespace App\Http\Controllers;

use App\Models\Upvote;
use App\Models\Issue;
use Illuminate\Http\Request;

class UpvoteController extends Controller
{
    public function index()
    {
        return response()->json(Upvote::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $upvote = Upvote::create($validated);
        return response()->json($upvote, 201);
    }

    public function show(Upvote $upvote)
    {
        return response()->json($upvote);
    }

    public function update(Request $request, Upvote $upvote)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $upvote->update($validated);
        return response()->json($upvote);
    }

    public function destroy(Upvote $upvote)
    {
        $upvote->delete();
        return response()->json(null, 204);
    }

    public function toggle(Issue $issue)
    {
        $userId = auth('api')->id();

        $upvote = Upvote::where('issue_id', $issue->id)->where('user_id', $userId)->first();

        if ($upvote) {
            $upvote->delete();
            return response()->json(['message' => 'Upvote removido'], 200);
        } else {
            Upvote::create([
                'issue_id' => $issue->id,
                'user_id' => $userId
            ]);
            return response()->json(['message' => 'Upvote agregado'], 201);
        }
    }
}
