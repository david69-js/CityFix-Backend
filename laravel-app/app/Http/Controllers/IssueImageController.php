<?php

namespace App\Http\Controllers;

use App\Models\IssueImage;
use Illuminate\Http\Request;

class IssueImageController extends Controller
{
    public function index()
    {
        return response()->json(IssueImage::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $issueImage = IssueImage::create($validated);
        return response()->json($issueImage, 201);
    }

    public function show(IssueImage $issueImage)
    {
        return response()->json($issueImage);
    }

    public function update(Request $request, IssueImage $issueImage)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $issueImage->update($validated);
        return response()->json($issueImage);
    }

    public function destroy(IssueImage $issueImage)
    {
        $issueImage->delete();
        return response()->json(null, 204);
    }
}
