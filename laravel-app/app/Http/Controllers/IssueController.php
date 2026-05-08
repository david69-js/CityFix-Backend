<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\IssueImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class IssueController extends Controller
{
    public function index()
    {
        return response()->json(Issue::withCount('comments')->get());
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20480', // 20MB max
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
            $path = $this->storeOptimizedImage($request->file('image'), 'issues');

            IssueImage::create([
                'issue_id' => $issue->id,
                'image_url' => Storage::disk('public')->url($path)
            ]);
        }

        return response()->json($issue->load('images'), 201);
    }

    /**
     * Optimiza y guarda una imagen:
     * - Redimensiona a máximo 1920px de ancho (manteniendo proporción)
     * - Convierte a JPEG con calidad 80%
     * - Resultado típico: < 300KB independientemente del tamaño original
     */
    private function storeOptimizedImage($file, string $folder): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());

        // Redimensionar si es más ancho que 1920px
        if ($image->width() > 1920) {
            $image->scaleDown(width: 1920);
        }

        // Codificar como JPEG al 80% de calidad
        $encoded = $image->toJpeg(quality: 80);

        $filename = $folder . '/' . uniqid('img_', true) . '.jpg';
        Storage::disk('public')->put($filename, $encoded);

        return $filename;
    }

    public function feed(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $issues = Issue::with([
            'user:id,first_name,last_name,avatar', 
            'category', 
            'status', 
            'images',
            'comments' => function($query) {
                $query->with('user:id,first_name,last_name,avatar')
                      ->latest()
                      ->limit(3);
            }
        ])
            ->withCount(['upvotes', 'comments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($issues);
    }

    public function show(Issue $issue)
    {
        return response()->json($issue->load([
            'user:id,first_name,last_name,avatar',
            'category',
            'status',
            'images',
            'comments' => function($query) {
                $query->with('user:id,first_name,last_name,avatar')
                      ->latest();
            }
        ]));
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
            'status_id' => 'required|exists:issue_status,id',
        ]);

        DB::transaction(function () use ($issue, $validated) {
            $issue->update([
                'status_id' => $validated['status_id']
            ]);

            $issue->history()->create([
                'status_id' => $validated['status_id'],
                'changed_by' => auth('api')->id(),
                'changed_at' => now(),
            ]);
        });

        return response()->json($issue->load('status', 'history'));
    }
}
