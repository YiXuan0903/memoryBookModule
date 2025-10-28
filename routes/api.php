<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\MemoryApiController;
use App\Http\Controllers\MemoryController;
use App\Http\Controllers\ProfileController;

// 🔓 Public routes
Route::post('/register', [AuthApiController::class, 'register']);
Route::post('/login', [AuthApiController::class, 'login']);

// Shared memory (public via token)
Route::get('/memories/shared/{token}', [MemoryController::class, 'ViewShared']);

// 🔒 Web frontend routes (using session authentication)
Route::middleware('web')->group(function () {
    Route::middleware('auth')->group(function () {
        // Memory operations for web frontend
        Route::post('/memories', [MemoryApiController::class, 'store']);
        Route::get('/memories', [MemoryApiController::class, 'index']);
        
        // ===== MOVED HERE: Friends Management for Web (Session Auth) =====
        Route::get('/friends', [MemoryController::class, 'apiFriends']);
        Route::post('/friends', [MemoryController::class, 'apiAddFriend']);
        Route::patch('/friends/{id}/category', [MemoryController::class, 'apiChangeCategory']);
        Route::delete('/friends/{id}', [MemoryController::class, 'apiDeleteFriend']);
        
        // Share Memory (Web)
        Route::post('/memories/{id}/share', [MemoryController::class, 'apiShare']);
    });
});

// 🔒 Protected routes (Token only: Sanctum) - For Mobile/External APIs
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/me', [AuthApiController::class, 'me']);
    Route::post('/logout', [AuthApiController::class, 'logout']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'apiShow']);
    Route::put('/profile', [ProfileController::class, 'apiUpdate']);

    // Memories CRUD (v2 for API only)
    Route::prefix('v2')->group(function() {
        Route::apiResource('memories', MemoryApiController::class)->names([
            'index'   => 'api.memories.index',
            'store'   => 'api.memories.store',
            'show'    => 'api.memories.show',
            'update'  => 'api.memories.update',
            'destroy' => 'api.memories.destroy',
        ]);
    });

    // Friends Management (Token Auth - for mobile apps)
    Route::prefix('v2')->group(function() {
        Route::get('/friends', [MemoryController::class, 'apiFriends']);
        Route::post('/friends', [MemoryController::class, 'apiAddFriend']);
        Route::patch('/friends/{id}/category', [MemoryController::class, 'apiChangeCategory']);
        Route::delete('/friends/{id}', [MemoryController::class, 'apiDeleteFriend']);
        
        // Share Memory (Token Auth)
        Route::post('/memories/{id}/share', [MemoryController::class, 'apiShare']);
    });
});