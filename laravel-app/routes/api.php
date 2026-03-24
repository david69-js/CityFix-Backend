<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
use App\Http\Controllers\WorkerController;

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
Route::apiResource('workers', WorkerController::class);
