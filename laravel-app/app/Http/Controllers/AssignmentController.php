<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index()
    {
        return response()->json(Assignment::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'issue_id'    => 'required|exists:issues,id',
            'worker_id'   => 'required|exists:users,id',
            'status_id'   => 'required|exists:assignment_statuses,id',
            'notes'       => 'nullable|string',
            'assigned_at' => 'required|date',
        ]);
        $assignment = Assignment::create($validated);
        return response()->json($assignment, 201);
    }

    public function show(Assignment $assignment)
    {
        return response()->json($assignment);
    }

    public function update(Request $request, Assignment $assignment)
    {
        $validated = $request->validate([
            'issue_id'    => 'sometimes|exists:issues,id',
            'worker_id'   => 'sometimes|exists:users,id',
            'status_id'   => 'sometimes|exists:assignment_statuses,id',
            'notes'       => 'nullable|string',
            'assigned_at' => 'sometimes|date',
        ]);
        $assignment->update($validated);
        return response()->json($assignment);
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return response()->json(null, 204);
    }

    public function myTray(Request $request)
    {
        $assignments = Assignment::where('worker_id', $request->user()->id)
            ->with(['issue.category', 'issue.status', 'status'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($assignments);
    }
}
