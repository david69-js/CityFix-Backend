<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\IssueImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IssueController extends Controller
{
    public function index()
    {
        return response()->json(Issue::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ]);

        $issue = Issue::create([
            'user_id' => auth('api')->id(),
            'category_id' => $validated['category_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'location' => $validated['location'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status_id' => 1, // Default status (e.g. Pendiente)
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('issues', 'public');
            
            IssueImage::create([
                'issue_id' => $issue->id,
                'image_url' => Storage::disk('public')->url($path)
            ]);
        }

        return response()->json($issue->load('images'), 201);
    }

    public function feed(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $issues = Issue::with(['user:id,first_name,last_name,avatar', 'category', 'status', 'images'])
            ->withCount(['upvotes', 'comments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($issues);
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
