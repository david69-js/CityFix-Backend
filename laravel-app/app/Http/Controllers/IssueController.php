<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function updateStatus(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'status_id' => 'required|exists:issue_statuses,id',
        ]);

        DB::transaction(function () use ($issue, $validated) {
            $issue->update([
                'status_id' => $validated['status_id']
            ]);

            $issue->history()->create([
                'status_id' => $validated['status_id'],
                'changed_by' => auth()->id(),
                'changed_at' => now(),
            ]);
        });

        return response()->json($issue->load('status', 'history'));
    }
}
