<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{id}', [PostController::class, 'show']);
Route::post('/posts', [PostController::class, 'store']);
Route::post('/posts/{id}/comments', [CommentController::class, 'store']);
Route::post('/posts/{id}/like', [PostController::class, 'like']);
Route::delete('/posts/{id}', [PostController::class, 'destroy']);