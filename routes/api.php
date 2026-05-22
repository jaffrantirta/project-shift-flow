<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ShiftSwapController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TimeClockController;
use App\Http\Controllers\Api\TimesheetController;
use Illuminate\Support\Facades\Route;

// ─── Public ──────────────────────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);

// ─── Authenticated ────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', \App\Http\Middleware\EnsureEmployeeRole::class])->group(function () {

    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Timesheets
    Route::get('/timesheets', [TimesheetController::class, 'index']);
    Route::get('/timesheets/{id}', [TimesheetController::class, 'show']);
    Route::post('/timesheets/{id}/submit', [TimesheetController::class, 'submit']);

    // Clock In/Out
    Route::get('/time-clock/status', [TimeClockController::class, 'status']);
    Route::post('/time-clock', [TimeClockController::class, 'store']);
    Route::get('/time-clock/history', [TimeClockController::class, 'history']);

    // Leave
    Route::get('/leave/balances', [LeaveController::class, 'balances']);
    Route::get('/leave/types', [LeaveController::class, 'types']);
    Route::get('/leave/requests', [LeaveController::class, 'index']);
    Route::post('/leave/requests', [LeaveController::class, 'store']);
    Route::get('/leave/requests/{id}', [LeaveController::class, 'show']);
    Route::post('/leave/requests/{id}/cancel', [LeaveController::class, 'cancel']);

    // Availability
    Route::get('/availability', [AvailabilityController::class, 'index']);
    Route::post('/availability', [AvailabilityController::class, 'store']);
    Route::put('/availability/{id}', [AvailabilityController::class, 'update']);
    Route::delete('/availability/{id}', [AvailabilityController::class, 'destroy']);

    // Shift Swaps
    Route::get('/shift-swaps', [ShiftSwapController::class, 'index']);
    Route::post('/shift-swaps', [ShiftSwapController::class, 'store']);
    Route::get('/shift-swaps/{id}', [ShiftSwapController::class, 'show']);
    Route::post('/shift-swaps/{id}/cancel', [ShiftSwapController::class, 'cancel']);

    // Messages
    Route::get('/conversations', [MessageController::class, 'conversations']);
    Route::post('/conversations/direct', [MessageController::class, 'startDirect']);
    Route::get('/conversations/{id}/messages', [MessageController::class, 'messages']);
    Route::post('/conversations/{id}/messages', [MessageController::class, 'send']);

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
    Route::post('/profile/change-pin', [ProfileController::class, 'changePin']);
});
