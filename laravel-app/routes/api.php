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

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
// routes/api.php
Route::post('/seed', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        return response()->json([
            'message' => 'Seeders executed',
            'output' => $output
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Seeder failed',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// =============================
// AUTH ROUTES
// =============================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/forgot-password', [PasswordResetController::class, 'requestReset']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// =============================
// CUSTOM ISSUE ROUTES
// =============================
// Estas van ANTES de apiResource('issues')

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/issues/feed', [IssueController::class, 'feed']);
    Route::post('/issues/{issue}/toggle-upvote', [UpvoteController::class, 'toggle']);
    Route::post('/issues/{issue}/comments', [CommentController::class, 'store']);
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
Route::apiResource('upvotes', UpvoteController::class);
Route::apiResource('users', UserController::class);
Route::post('invitation-codes/redeem', [InvitationCodeController::class, 'redeem'])->middleware('auth:sanctum');
Route::apiResource('invitation-codes', InvitationCodeController::class);

// =============================
// ROLE TEST ROUTES
// =============================

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('/admin-only', function () {
        return response()->json([
            'message' => 'Solo Admin'
        ]);
    });
});

Route::middleware(['auth:sanctum', 'role:Worker,Admin'])->group(function () {
    Route::get('/worker-or-admin', function () {
        return response()->json([
            'message' => 'Worker o Admin'
        ]);
    });
});

Route::get('users', [UserController::class, 'index']);
