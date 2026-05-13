<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// Auth
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\PasswordResetController;

// Controllers
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AssignmentStatusController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\IssueHistoryController;
use App\Http\Controllers\IssueImageController;
use App\Http\Controllers\IssueStatusController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UpvoteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InvitationCodeController;
use App\Http\Controllers\GoogleMapsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Admin Secret Guard (shared by all database management endpoints)
|--------------------------------------------------------------------------
| All /seed, /reset, and /reset-hard endpoints require the caller to
| supply the ADMIN_SECRET environment variable value in the
| X-Admin-Secret request header.  This keeps the endpoints out of reach
| of ordinary users without adding a full auth layer to stateless calls.
*/
$guardAdminSecret = function (Request $request) {
    $secret = env('ADMIN_SECRET');

    // If no secret is configured, block the request entirely so the
    // endpoint is never accidentally left open in production.
    if (empty($secret)) {
        \Illuminate\Support\Facades\Log::warning('[AdminGuard] ADMIN_SECRET is not set – request blocked');
        return response()->json([
            'message' => 'This endpoint is disabled: ADMIN_SECRET environment variable is not configured.',
        ], 503);
    }

    if ($request->header('X-Admin-Secret') !== $secret) {
        \Illuminate\Support\Facades\Log::warning('[AdminGuard] Unauthorized attempt', [
            'ip'  => $request->ip(),
            'uri' => $request->getRequestUri(),
        ]);
        return response()->json(['message' => 'Unauthorized.'], 401);
    }

    return null; // Passes the guard
};

/*
|--------------------------------------------------------------------------
| Ordered list of tables to truncate (leaf → root to respect FK constraints)
|--------------------------------------------------------------------------
| The list follows the reverse dependency order so that child rows are
| removed before the parent rows they reference.
*/
$truncateTables = [
    'audits',
    'comments',
    'upvotes',
    'issue_images',
    'issue_histories',
    'assignments',
    'notifications',
    'issues',
    'invitation_codes',
    'categories',
    'assignment_statuses',
    'issue_statuses',
    'password_resets',
    'sessions',
    'cache',
    'cache_locks',
    'users',
    'role_permissions',
    'permissions',
    'roles',
];

// routes/api.php
/*
|--------------------------------------------------------------------------
| POST /api/seed  –  Run all seeders (idempotent, uses firstOrCreate)
|--------------------------------------------------------------------------
*/
Route::post('/seed', function (Request $request) use ($guardAdminSecret) {
    if ($denied = $guardAdminSecret($request)) {
        return $denied;
    }

    // Extend PHP execution time to accommodate the ~9s seeding operation
    set_time_limit(120);

    // Prevent proxies and load balancers from closing the connection early
    header('Connection: keep-alive');

    $startedAt = now();
    \Illuminate\Support\Facades\Log::info('[Seed] Starting db:seed via HTTP request');

    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Log::info('[Seed] Completed successfully', [
            'duration_ms' => $startedAt->diffInMilliseconds(now()),
            'output'      => $output,
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'logs/seed-output.log',
            '[' . now()->toDateTimeString() . '] SUCCESS' . PHP_EOL . $output
        );

        return response()->json([
            'message'     => 'Seeders executed successfully.',
            'started_at'  => $startedAt->toDateTimeString(),
            'finished_at' => now()->toDateTimeString(),
            'output'      => $output,
        ]);
    } catch (\Throwable $e) {
        $errorDetails = [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ];

        \Illuminate\Support\Facades\Log::error('[Seed] Seeder failed', $errorDetails);

        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'logs/seed-output.log',
            '[' . now()->toDateTimeString() . '] FAILED' . PHP_EOL
                . 'Error: ' . $e->getMessage() . PHP_EOL
                . 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL
                . $e->getTraceAsString()
        );

        return response()->json([
            'message' => 'Seeder failed.',
            'error'   => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| POST /api/reset  –  Truncate all data tables then re-seed
|--------------------------------------------------------------------------
| Drops all rows while keeping the schema intact, then runs every seeder
| in the correct dependency order.  Safe to call multiple times.
|
| Required header:  X-Admin-Secret: <value of ADMIN_SECRET env var>
*/
Route::post('/reset', function (Request $request) use ($guardAdminSecret, $truncateTables) {
    if ($denied = $guardAdminSecret($request)) {
        return $denied;
    }

    set_time_limit(180);
    header('Connection: keep-alive');

    $startedAt = now();
    $log       = [];

    \Illuminate\Support\Facades\Log::info('[Reset] Starting table truncation + reseed via HTTP request');

    try {
        $db = \Illuminate\Support\Facades\DB::connection();

        // Disable FK checks so we can truncate in any order
        $db->statement('SET FOREIGN_KEY_CHECKS=0');
        $log[] = 'Foreign key checks disabled.';

        foreach ($truncateTables as $table) {
            try {
                $db->table($table)->truncate();
                $log[] = "Truncated: {$table}";
                \Illuminate\Support\Facades\Log::info("[Reset] Truncated table: {$table}");
            } catch (\Throwable $te) {
                // Table may not exist yet (e.g. audits before laravel-auditing is installed)
                $log[] = "Skipped (not found): {$table} — {$te->getMessage()}";
                \Illuminate\Support\Facades\Log::warning("[Reset] Could not truncate {$table}", ['error' => $te->getMessage()]);
            }
        }

        $db->statement('SET FOREIGN_KEY_CHECKS=1');
        $log[] = 'Foreign key checks re-enabled.';

        \Illuminate\Support\Facades\Log::info('[Reset] All tables truncated, starting seeders');

        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $seedOutput = \Illuminate\Support\Facades\Artisan::output();
        $log[]      = 'Seeders output: ' . trim($seedOutput);

        \Illuminate\Support\Facades\Log::info('[Reset] Completed successfully', [
            'duration_ms' => $startedAt->diffInMilliseconds(now()),
            'seed_output' => $seedOutput,
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'logs/reset-output.log',
            '[' . now()->toDateTimeString() . '] SUCCESS' . PHP_EOL . implode(PHP_EOL, $log)
        );

        return response()->json([
            'message'     => 'Database reset and reseeded successfully.',
            'started_at'  => $startedAt->toDateTimeString(),
            'finished_at' => now()->toDateTimeString(),
            'steps'       => $log,
            'seed_output' => $seedOutput,
        ]);
    } catch (\Throwable $e) {
        // Ensure FK checks are always re-enabled even on failure
        try {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Throwable $_) {}

        $log[] = 'FAILED: ' . $e->getMessage();

        \Illuminate\Support\Facades\Log::error('[Reset] Reset failed', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'logs/reset-output.log',
            '[' . now()->toDateTimeString() . '] FAILED' . PHP_EOL
                . 'Error: ' . $e->getMessage() . PHP_EOL
                . 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL
                . $e->getTraceAsString()
        );

        return response()->json([
            'message' => 'Reset failed.',
            'error'   => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
            'steps'   => $log,
        ], 500);
    }
});

/*
|--------------------------------------------------------------------------
| POST /api/reset-hard  –  migrate:fresh --seed (full schema rebuild)
|--------------------------------------------------------------------------
| Drops every table, re-runs all migrations from scratch, then seeds.
| Use this when you need a completely clean schema, not just empty tables.
| This is destructive and irreversible — all data will be lost.
|
| Required header:  X-Admin-Secret: <value of ADMIN_SECRET env var>
*/
Route::post('/reset-hard', function (Request $request) use ($guardAdminSecret) {
    if ($denied = $guardAdminSecret($request)) {
        return $denied;
    }

    set_time_limit(300);
    header('Connection: keep-alive');

    $startedAt = now();

    \Illuminate\Support\Facades\Log::info('[ResetHard] Starting migrate:fresh --seed via HTTP request');

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate:fresh', [
            '--seed'  => true,
            '--force' => true,
        ]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Log::info('[ResetHard] Completed successfully', [
            'duration_ms' => $startedAt->diffInMilliseconds(now()),
            'output'      => $output,
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'logs/reset-hard-output.log',
            '[' . now()->toDateTimeString() . '] SUCCESS' . PHP_EOL . $output
        );

        return response()->json([
            'message'     => 'Hard reset completed: schema rebuilt and reseeded successfully.',
            'started_at'  => $startedAt->toDateTimeString(),
            'finished_at' => now()->toDateTimeString(),
            'output'      => $output,
        ]);
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('[ResetHard] Hard reset failed', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'logs/reset-hard-output.log',
            '[' . now()->toDateTimeString() . '] FAILED' . PHP_EOL
                . 'Error: ' . $e->getMessage() . PHP_EOL
                . 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL
                . $e->getTraceAsString()
        );

        return response()->json([
            'message' => 'Hard reset failed.',
            'error'   => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ], 500);
    }
});

// =============================
// AUTH ROUTES
// =============================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/google', [AuthController::class, 'loginWithGoogle']);

    Route::post('/forgot-password', [PasswordResetController::class, 'requestReset']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// =============================
// CUSTOM ISSUE ROUTES
// =============================
// Estas van ANTES de apiResource('issues')

Route::get('/issues/feed', [IssueController::class, 'feed']);

Route::middleware('auth:api')->group(function () {
    Route::post('/issues/{issue}/toggle-upvote', [UpvoteController::class, 'toggle']);
    Route::post('/issues/{issue}/comments', [CommentController::class, 'store']);
    Route::get('/issues/{issue}/comments', [CommentController::class, 'index']);
    Route::get('/my-assignments', [AssignmentController::class, 'myTray']);
    Route::patch('/issues/{issue}/status', [IssueController::class, 'updateStatus']);
    Route::get('/issues/{issue}/history-logs', [IssueHistoryController::class, 'historyLogs']);
    Route::post('/users/fcm-token', [UserController::class, 'updateFcmToken']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/user/profile', [UserController::class, 'updateProfile']);
});

// =============================
// GOOGLE MAPS PROXY ROUTES
// =============================
Route::middleware('auth:api')->prefix('maps')->group(function () {
    Route::get('/geocode', [GoogleMapsController::class, 'geocode']);
    Route::get('/reverse-geocode', [GoogleMapsController::class, 'reverseGeocode']);
    Route::get('/places/autocomplete', [GoogleMapsController::class, 'placesAutocomplete']);
    Route::get('/places/details', [GoogleMapsController::class, 'placeDetails']);
});

// =============================
// API RESOURCES
// =============================

Route::apiResource('assignments', AssignmentController::class);
Route::apiResource('assignment-statuses', AssignmentStatusController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('comments', CommentController::class);
Route::apiResource('issues', IssueController::class);
Route::apiResource('issue-histories', IssueHistoryController::class);
Route::apiResource('issue-images', IssueImageController::class);
Route::apiResource('issue-statuses', IssueStatusController::class);
Route::apiResource('notifications', NotificationController::class);
Route::apiResource('permissions', PermissionController::class);
Route::apiResource('roles', RoleController::class);
Route::apiResource('invitation-codes', InvitationCodeController::class);
Route::apiResource('upvotes', UpvoteController::class);
// Users resource moved to Admin middleware

// =============================
// ADMIN ROUTES
// =============================
Route::middleware(['auth:api', 'role:Admin'])->prefix('admin')->group(function () {
    // User management
    Route::apiResource('users', UserController::class);

    // Issue management (view all, edit, hide/show)
    Route::get('/issues', [IssueController::class, 'adminIndex']);
    Route::put('/issues/{issue}', [IssueController::class, 'adminUpdate']);
    Route::patch('/issues/{issue}/toggle-hidden', [IssueController::class, 'toggleHidden']);

    // Notification campaigns
    Route::post('/notifications/campaign', [NotificationController::class, 'storeCampaign']);

    Route::get('/admin-only', function () {
        return response()->json([
            'message' => 'Solo Admin'
        ]);
    });
});

// =============================
// ROLE TEST ROUTES
// =============================
Route::middleware(['auth:api', 'role:Worker,Admin'])->group(function () {
    Route::get('/worker-or-admin', function () {
        return response()->json([
            'message' => 'Worker o Admin'
        ]);
    });
});

Route::get('users', [UserController::class, 'index']);
