<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

$registerApiRoutes = static function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{id}', [PostController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/posts', [PostController::class, 'store']);
        Route::post('/posts/{id}/comments', [CommentController::class, 'store']);
        Route::post('/posts/{id}/like', [PostController::class, 'like']);
        Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    });
};

Route::prefix('v1')->group($registerApiRoutes);

// Keep the original endpoints available while clients migrate to /v1.
$registerApiRoutes();
