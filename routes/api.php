<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────
// Public Routes (without auth)
// ─────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});


// ─────────────────────────────────────────
// Protected Routes (with auth)
// ─────────────────────────────────────────
Route::middleware('auth.jwt')->group(function () {
    Route::apiResource('clients', ClientController::class);
});
