<?php

use Illuminate\Support\Facades\Route;
use Timmonaghan\SecurityAgent\Http\Controllers\AdminController;
use Timmonaghan\SecurityAgent\Http\Middleware\AdminAuthMiddleware;

$path = config('lsa.admin.path', 'lsa-admin');

// Login routes — outside auth middleware
Route::prefix($path)->middleware('web')->group(function () {
    Route::get('login', [AdminController::class, 'showLogin'])->name('lsa.admin.login');
    Route::post('login', [AdminController::class, 'login'])->name('lsa.admin.login.post');
});

// Protected routes
Route::prefix($path)->middleware(['web', AdminAuthMiddleware::class])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('lsa.admin.dashboard');
    Route::get('settings', [AdminController::class, 'showSettings'])->name('lsa.admin.settings');
    Route::post('settings', [AdminController::class, 'saveSettings'])->name('lsa.admin.settings.save');
});
