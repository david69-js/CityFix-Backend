<?php

namespace App\Http\Controllers\Api;

use App\Models\Issue;
use App\Models\IssueStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueRequest;

class IssueController extends Controller
{
    public function store(StoreIssueRequest $request)
    {
        $defaultStatus = IssueStatus::orderBy('sort_order')->first();

        if (!$defaultStatus) {
            return response()->json([
                'message' => 'No existe un estado inicial configurado en issue_status.'
            ], 500);
        }

        $issue = DB::transaction(function () use ($request, $defaultStatus) {
            $issue = Issue::create([
                'user_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'status_id' => $defaultStatus->id,
            ]);

            $issue->history()->create([
                'status_id' => $defaultStatus->id,
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('issues', config('filesystems.default'));

                    $issue->images()->create([
                        'image_url' => $path,
                    ]);
                }
            }

            return $issue;
        });

        $issue->load([
            'user:id,first_name,last_name,email,avatar',
            'category:id,name',
            'status:id,name,color,sort_order',
            'images:id,issue_id,image_url,created_at',
        ]);

        return response()->json([
            'message' => 'Reporte creado correctamente.',
            'data' => $issue,
        ], 201);
    }

    public function feed(Request $request)
    {
        $authUserId = $request->user()->id;

        $issues = Issue::query()
            ->with([
                'user:id,first_name,last_name,email,avatar,role_id',
                'category:id,name',
                'status:id,name,color,sort_order',
                'images:id,issue_id,image_url,created_at',
                'comments' => function ($query) {
                    $query->latest()
                        ->take(3)
                        ->with('user:id,first_name,last_name,avatar');
                },
            ])
            ->withCount(['upvotes', 'comments'])
            ->withExists([
                'upvotes as has_upvoted' => function ($query) use ($authUserId) {
                    $query->where('user_id', $authUserId);
                }
            ])
            ->latest()
            ->paginate(10);

        return response()->json($issues);
    }

    public function show(Issue $issue, Request $request)
    {
        $authUserId = $request->user()->id;

        $issue->load([
            'user:id,first_name,last_name,email,avatar,role_id',
            'category:id,name',
            'status:id,name,color,sort_order',
            'images:id,issue_id,image_url,created_at',
            'comments' => function ($query) {
                $query->latest()
                    ->with('user:id,first_name,last_name,avatar');
            },
        ]);

        $issue->loadCount(['upvotes', 'comments']);

        $issue->has_upvoted = $issue->upvotes()
            ->where('user_id', $authUserId)
            ->exists();

        return response()->json([
            'data' => $issue
        ]);
    }
}