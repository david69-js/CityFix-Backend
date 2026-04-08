<?php

namespace App\Http\Controllers;

use App\Models\AssignmentStatus;
use Illuminate\Http\Request;

class AssignmentStatusController extends Controller
{
    public function index()
    {
        return response()->json(AssignmentStatus::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $assignmentStatus = AssignmentStatus::create($validated);
        return response()->json($assignmentStatus, 201);
    }

    public function show(AssignmentStatus $assignmentStatus)
    {
        return response()->json($assignmentStatus);
    }

    public function update(Request $request, AssignmentStatus $assignmentStatus)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $assignmentStatus->update($validated);
        return response()->json($assignmentStatus);
    }

    public function destroy(AssignmentStatus $assignmentStatus)
    {
        $assignmentStatus->delete();
        return response()->json(null, 204);
    }
}
