<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookCopyController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category:slug}', [CategoryController::class, 'show']);
    Route::get('/categories/{category:slug}/books', [BookController::class, 'categoryBooks']);

    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/search', [BookController::class, 'search']);
    Route::get('/books/{book:slug}', [BookController::class, 'show']);

    Route::middleware('admin')->group(function (): void {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category:slug}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category:slug}', [CategoryController::class, 'destroy']);

        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{book:slug}', [BookController::class, 'update']);
        Route::delete('/books/{book:slug}', [BookController::class, 'destroy']);

        Route::get('/book-copies', [BookCopyController::class, 'index']);
        Route::post('/book-copies', [BookCopyController::class, 'store']);
        Route::put('/book-copies/{bookCopy}', [BookCopyController::class, 'update']);
        Route::delete('/book-copies/{bookCopy}', [BookCopyController::class, 'destroy']);

        Route::get('/admin/stats', [StatsController::class, 'index']);
    });
});
