<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function index()
    {
        return response()->json(Issue::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $issue = Issue::create($validated);
        return response()->json($issue, 201);
    }

    public function show(Issue $issue)
    {
        return response()->json($issue);
    }

    public function update(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $issue->update($validated);
        return response()->json($issue);
    }

    public function destroy(Issue $issue)
    {
        $issue->delete();
        return response()->json(null, 204);
    }
}
