<?php

namespace App\Http\Controllers;

use App\Models\IssueStatus;
use Illuminate\Http\Request;

class IssueStatusController extends Controller
{
    public function index()
    {
        return response()->json(IssueStatus::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $issueStatus = IssueStatus::create($validated);
        return response()->json($issueStatus, 201);
    }

    public function show(IssueStatus $issueStatus)
    {
        return response()->json($issueStatus);
    }

    public function update(Request $request, IssueStatus $issueStatus)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $issueStatus->update($validated);
        return response()->json($issueStatus);
    }

    public function destroy(IssueStatus $issueStatus)
    {
        $issueStatus->delete();
        return response()->json(null, 204);
    }
}
