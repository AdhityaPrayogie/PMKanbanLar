<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\HelpdeskMasterController;
use App\Http\Controllers\HelpdeskPengajuanController;
use App\Http\Controllers\HelpdeskReportController;
use App\Http\Controllers\HelpdeskTicketController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\MasterUnitController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public (or own error contract)
Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('login', 'login')->middleware('throttle:login');
    Route::post('logout', 'logout');
    Route::get('me', 'me');
    Route::post('register', 'register')->middleware('throttle:register');
});
// Order 404 (type) -> 401 -> 403 is checked inside the controller
Route::controller(HelpdeskMasterController::class)->prefix('helpdesk/master/{type}')->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::patch('/', 'update');
});
// Guests get 403 too (Next.js behaviour), so no 'auth' here
Route::controller(AdminUserController::class)->prefix('admin/users')->middleware('can:admin')->group(function () {
    Route::get('/', 'index');
    Route::patch('/', 'update');
    Route::delete('/', 'destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::patch('settings', [UserController::class, 'updateSettings']);
    Route::get('board', BoardController::class);

    Route::controller(ColumnController::class)->group(function () {
        Route::post('columns', 'store');
        Route::patch('columns', 'update');
        Route::delete('columns', 'destroy');
    });
    Route::controller(NotificationController::class)->group(function () {
        Route::get('notifications', 'index');
        Route::patch('notifications', 'update');
    });
    Route::controller(ProjectController::class)->group(function () {
        Route::get('units', 'index');
        Route::post('units', 'store');
        Route::patch('units', 'update');
        Route::delete('units', 'destroy');
    });
    Route::controller(MasterUnitController::class)->group(function () {
        Route::get('master-units', 'index');
        Route::post('master-units', 'store');
        Route::patch('master-units', 'update');
    });

    Route::controller(TaskController::class)->group(function () {
        Route::post('tasks', 'store');
        Route::patch('tasks', 'patch');
        Route::delete('tasks', 'destroy');
        Route::get('tasks/{id}', 'show');
        Route::put('tasks/{id}', 'update');
    });
    Route::post('tasks/{id}/comments', [TaskCommentController::class, 'store']);
    Route::post('tasks/{id}/attachments', [TaskAttachmentController::class, 'store']);
    Route::delete('tasks/{id}/attachments', [TaskAttachmentController::class, 'destroy']);
    Route::post('upload/image', ImageUploadController::class);

    Route::controller(MessageController::class)->group(function () {
        Route::get('messages', 'index');
        Route::post('messages', 'store');
        Route::post('messages/read', 'read');
        Route::post('messages/typing', 'typing');
    });

    // Any logged-in user. Static segments before helpdesk/{id}.
    Route::controller(HelpdeskPengajuanController::class)->prefix('helpdesk/pengajuan')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::delete('/', 'destroy');
    });
    Route::middleware('can:helpdesk')->prefix('helpdesk')->group(function () {
        Route::get('stats', [HelpdeskReportController::class, 'stats']);
        Route::get('export', [HelpdeskReportController::class, 'export']);
        Route::controller(HelpdeskTicketController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('{id}', 'show');
            Route::put('{id}', 'update');
            Route::delete('{id}', 'destroy');
            Route::post('{id}/ambil', 'ambil');
        });
    });
});
