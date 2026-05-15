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
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
// routes/api.php
Route::post('/seed', function () {
    // Extend PHP execution time to accommodate the ~9s seeding operation
    set_time_limit(60);

    // Prevent proxies and load balancers from closing the connection early
    header('Connection: keep-alive');

    try {
        \Illuminate\Support\Facades\Log::info('[Seed] Starting db:seed via HTTP request');

        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Log::info('[Seed] Completed successfully', ['output' => $output]);

        // Write output to a dedicated log file for easier debugging
        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'logs/seed-output.log',
            '[' . now()->toDateTimeString() . '] SUCCESS' . PHP_EOL . $output
        );

        return response()->json([
            'message' => 'Seeders executed',
            'output' => $output
        ]);
    } catch (\Throwable $e) {
        $errorDetails = [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ];

        \Illuminate\Support\Facades\Log::error('[Seed] Seeder failed', $errorDetails);

        // Write failure details to the same dedicated log file
        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'logs/seed-output.log',
            '[' . now()->toDateTimeString() . '] FAILED' . PHP_EOL
                . 'Error: ' . $e->getMessage() . PHP_EOL
                . 'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL
                . $e->getTraceAsString()
        );

        return response()->json([
            'message' => 'Seeder failed',
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

    Route::post('/check-email', [PasswordResetController::class, 'checkEmail']);
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
// PÚBLICO: Verificar código de invitación
// =============================
Route::post('/invitation-codes/verify', [InvitationCodeController::class, 'verify']);

// =============================
// API RESOURCES — Solo lectura (públicos)
// =============================
Route::apiResource('issues', IssueController::class)->only(['index', 'show']);
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('issue-statuses', IssueStatusController::class)->only(['index', 'show']);
Route::apiResource('assignment-statuses', AssignmentStatusController::class)->only(['index', 'show']);
Route::apiResource('roles', RoleController::class)->only(['index', 'show']);

// =============================
// API RESOURCES — Protegidos (requieren autenticación)
// =============================
Route::middleware('auth:api')->group(function () {
    Route::apiResource('issues', IssueController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('comments', CommentController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('upvotes', UpvoteController::class)->only(['store', 'destroy']);
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show', 'update']);
    Route::apiResource('assignments', AssignmentController::class);
    Route::apiResource('issue-histories', IssueHistoryController::class);
    Route::apiResource('issue-images', IssueImageController::class);
    Route::apiResource('invitation-codes', InvitationCodeController::class);
});

// =============================
// ADMIN ROUTES
// =============================
Route::middleware(['auth:api', 'role:Admin'])->prefix('admin')->group(function () {
    // User management
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive']);

    // Issue management (view all, edit, hide/show)
    Route::get('/issues', [IssueController::class, 'adminIndex']);
    Route::put('/issues/{issue}', [IssueController::class, 'adminUpdate']);
    Route::patch('/issues/{issue}/toggle-hidden', [IssueController::class, 'toggleHidden']);

    // Notification campaigns
    Route::post('/notifications/campaign', [NotificationController::class, 'storeCampaign']);

    // Reports
    Route::get('/reports/summary', [ReportController::class, 'summary']);
    Route::get('/reports/by-category', [ReportController::class, 'byCategory']);
    Route::get('/reports/by-worker', [ReportController::class, 'byWorker']);
    Route::get('/reports/by-date', [ReportController::class, 'byDate']);
    Route::get('/reports/resolution-times', [ReportController::class, 'resolutionTimes']);
    Route::get('/reports/details', [ReportController::class, 'details']);

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
