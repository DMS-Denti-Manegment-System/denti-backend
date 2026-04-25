<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Todo\Controllers\TodoController;

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::prefix('todos')->group(function () {
        Route::get('/', [TodoController::class, 'index'])->middleware('permission:view-todos');
        Route::post('/', [TodoController::class, 'store'])->middleware('permission:manage-todos');
        Route::get('/stats', [TodoController::class, 'stats'])->middleware('permission:view-todos');
        Route::get('/category/{categoryId}', [TodoController::class, 'byCategory'])->middleware('permission:view-todos');
        Route::get('/{id}', [TodoController::class, 'show'])->middleware('permission:view-todos');
        Route::put('/{id}', [TodoController::class, 'update'])->middleware('permission:manage-todos');
        Route::patch('/{id}/toggle', [TodoController::class, 'toggle'])->middleware('permission:manage-todos');
        Route::delete('/{id}', [TodoController::class, 'destroy'])->middleware('permission:manage-todos');
    });
});
