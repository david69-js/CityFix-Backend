<?php

namespace App\Http\Controllers;

use App\Models\IssueHistory;
use Illuminate\Http\Request;

class IssueHistoryController extends Controller
{
    public function index()
    {
        return response()->json(IssueHistory::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $issueHistory = IssueHistory::create($validated);
        return response()->json($issueHistory, 201);
    }

    public function show(IssueHistory $issueHistory)
    {
        return response()->json($issueHistory);
    }

    public function update(Request $request, IssueHistory $issueHistory)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $issueHistory->update($validated);
        return response()->json($issueHistory);
    }

    public function destroy(IssueHistory $issueHistory)
    {
        $issueHistory->delete();
        return response()->json(null, 204);
    }
}
