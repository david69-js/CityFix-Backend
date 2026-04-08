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
            // Add your validation rules
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
            // Add your validation rules
        ]);
        $assignment->update($validated);
        return response()->json($assignment);
    }

    public function destroy(Assignment $assignment)
    {
        $assignment->delete();
        return response()->json(null, 204);
    }
}
