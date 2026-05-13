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
            'name'        => 'required|string|max:50|unique:issue_status,name',
            'description' => 'nullable|string|max:255',
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
            'name'        => 'sometimes|string|max:50|unique:issue_status,name,' . $issueStatus->id,
            'description' => 'nullable|string|max:255',
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
