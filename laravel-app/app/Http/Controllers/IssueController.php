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
        return response()->json(
            Issue::where('is_hidden', false)
                ->withCount('comments')
                ->get()
        );
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
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
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

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $this->storeOptimizedImage($image, 'issues');
                IssueImage::create([
                    'issue_id' => $issue->id,
                    'image_url' => $path,
                ]);
            }
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

        if ($image->width() > 1920) {
            $image->scaleDown(width: 1920);
        }

        $encoded = $image->toJpeg(quality: 80);

        $filename = $folder . '/' . uniqid('img_', true) . '.jpg';
        Storage::disk($this->imageDisk())->put($filename, $encoded);

        return $filename;
    }

    private function imageDisk(): string
    {
        return env('FILESYSTEM_DISK', 'local') === 'r2' ? 'r2' : 'public';
    }

    public function feed(Request $request)
    {
        $perPage = $request->query('per_page', 15);

        $query = Issue::with([
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
        ->where('is_hidden', false)
        ->whereHas('user', function ($q) {
            $q->where('is_active', true);
        })
        ->withCount(['upvotes', 'comments']);

        // Filtro por búsqueda de texto (título, descripción, ubicación)
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Filtro por usuario
        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        // Filtro por estado
        if ($request->has('status_id')) {
            $query->where('status_id', $request->query('status_id'));
        }

        // Filtro por categoría
        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        $issues = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($issues);
    }

    public function show(Issue $issue)
    {
        return response()->json($issue->loadCount('comments')->load([
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
        $user = auth('api')->user();

        if ($issue->user_id !== $user->id && !$user->hasRole('Admin')) {
            return response()->json(['message' => 'No autorizado para editar este reporte.'], 403);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'location'    => 'sometimes|string|max:255',
            'latitude'    => 'sometimes|numeric',
            'longitude'   => 'sometimes|numeric',
            'status_id'   => 'sometimes|exists:issue_status,id',
            'images'      => 'nullable|array|max:5',
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'deleted_images' => 'nullable|array',
            'deleted_images.*' => 'integer|exists:issue_images,id',
        ]);

        DB::transaction(function () use ($issue, $validated, $request) {
            $issue->update($validated);

            if (!empty($validated['deleted_images'])) {
                $images = IssueImage::whereIn('id', $validated['deleted_images'])
                    ->where('issue_id', $issue->id)
                    ->get();

                foreach ($images as $image) {
                    $this->deleteIssueImageFile($image);
                    $image->delete();
                }
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $this->storeOptimizedImage($image, 'issues');
                    $issue->images()->create([
                        'image_url' => $path,
                    ]);
                }
            }
        });

        return response()->json(
            $issue->fresh()->load('images')
        );
    }

    private function deleteIssueImageFile(IssueImage $image): void
    {
        $url = $image->image_url;

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $url = parse_url($url, PHP_URL_PATH);
        }

        $relativePath = ltrim($url, '/');

        if (str_starts_with($relativePath, 'storage/')) {
            $relativePath = substr($relativePath, 8);
        }

        if (!empty($relativePath)) {
            Storage::disk($this->imageDisk())->delete($relativePath);
        }
    }

    public function destroy(Issue $issue)
    {
        $issue->delete();
        return response()->json(null, 204);
    }

    public function updateStatus(Request $request, Issue $issue)
    {
        $user = auth('api')->user();

        if (!$user->hasRole('Admin')) {
            $isAssigned = $issue->assignments()
                ->where('worker_id', $user->id)
                ->whereHas('status', function ($q) {
                    $q->whereIn('name', ['Pending', 'In Progress', 'On Hold']);
                })
                ->exists();

            if (!$isAssigned) {
                return response()->json(['message' => 'No autorizado para cambiar el estado de este reporte.'], 403);
            }
        }

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

    // =============================
    // ADMIN METHODS
    // =============================

    /**
     * Admin: List ALL issues (including hidden ones).
     * GET /api/admin/issues
     */
    public function adminIndex(Request $request)
    {
        $perPage = $request->query('per_page', 20);

        $query = Issue::with([
            'user:id,first_name,last_name,avatar',
            'category',
            'status',
            'images',
        ])->withCount(['upvotes', 'comments']);

        // Optional filter by hidden status
        if ($request->has('is_hidden')) {
            $query->where('is_hidden', filter_var($request->query('is_hidden'), FILTER_VALIDATE_BOOLEAN));
        }

        // Optional filter by status
        if ($request->has('status_id')) {
            $query->where('status_id', $request->query('status_id'));
        }

        // Optional filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        // Optional filter by user_id
        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        // Optional search
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $issues = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($issues);
    }

    /**
     * Admin: Update any issue.
     * PUT /api/admin/issues/{issue}
     */
    public function adminUpdate(Request $request, Issue $issue)
    {
        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'location'    => 'sometimes|string|max:255',
            'latitude'    => 'sometimes|numeric',
            'longitude'   => 'sometimes|numeric',
            'status_id'   => 'sometimes|exists:issue_status,id',
            'images'      => 'nullable|array|max:5',
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'deleted_images' => 'nullable|array',
            'deleted_images.*' => 'integer|exists:issue_images,id',
        ]);

        DB::transaction(function () use ($issue, $validated, $request) {
            if (isset($validated['status_id']) && $validated['status_id'] != $issue->status_id) {
                $issue->history()->create([
                    'status_id'  => $validated['status_id'],
                    'changed_by' => auth('api')->id(),
                    'changed_at' => now(),
                ]);
            }

            $issue->update($validated);

            if (!empty($validated['deleted_images'])) {
                $images = IssueImage::whereIn('id', $validated['deleted_images'])
                    ->where('issue_id', $issue->id)
                    ->get();

                foreach ($images as $image) {
                    $this->deleteIssueImageFile($image);
                    $image->delete();
                }
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $this->storeOptimizedImage($image, 'issues');
                    $issue->images()->create([
                        'image_url' => $path,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Issue actualizado correctamente.',
            'issue'   => $issue->fresh()->load(['user:id,first_name,last_name,avatar', 'category', 'status', 'images']),
        ]);
    }

    /**
     * Admin: Toggle issue visibility (hide/show).
     * PATCH /api/admin/issues/{issue}/toggle-hidden
     */
    public function toggleHidden(Request $request, Issue $issue)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $newHiddenState = !$issue->is_hidden;

        $issue->update([
            'is_hidden'     => $newHiddenState,
            'hidden_reason' => $newHiddenState ? ($request->reason ?? 'Ocultado por administrador') : null,
        ]);

        return response()->json([
            'message'   => $newHiddenState ? 'Issue ocultado del feed público.' : 'Issue visible nuevamente.',
            'issue'     => $issue->fresh(['category', 'status']),
            'is_hidden' => $newHiddenState,
        ]);
    }
}
