<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;

// Rutas públicas de autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Consultar publicaciones
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{id}', [PostController::class, 'show']);

// Dar Me gusta
Route::post('/posts/{id}/like', [PostController::class, 'like']);

// Rutas protegidas: requieren un token válido de Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/posts', [PostController::class, 'store']);

    Route::post(
        '/posts/{id}/comments',
        [CommentController::class, 'store']
    );

    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
});