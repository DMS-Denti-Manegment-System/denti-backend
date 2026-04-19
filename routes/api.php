<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserInvitationController;

// Auth Routes (Public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/invitations/accept', [UserInvitationController::class, 'accept']);

// Auth Routes (Protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Invitation Routes
    Route::post('/invitations/invite', [UserInvitationController::class, 'invite']);
});

// Modül route'ları service provider'lardan otomatik yükleniyor.
// Tüm modül route'ları kendi içlerinde 'auth:sanctum' middleware'i ile korunmaktadır.
