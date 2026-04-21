<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserInvitationController;
use App\Http\Controllers\Api\Admin\CompanyController;
use App\Http\Controllers\Api\RoleController;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;

// Auth Routes (Public)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:60,1');
Route::post('/invitations/accept', [UserInvitationController::class, 'accept']);

// Auth Routes (Protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Profile Settings
    Route::put('/profile/info', [ProfileController::class, 'updateInfo']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // 2FA Routes
    Route::post('/auth/2fa/generate', [AuthController::class, 'generate2FA']);
    Route::post('/auth/2fa/confirm', [AuthController::class, 'confirm2FA']);
    Route::post('/auth/2fa/verify', [AuthController::class, 'verify2FA']);

    // User Management (Employee Management)
    Route::apiResource('users', UserController::class);

    // Invitation Routes
    Route::post('/invitations/invite', [UserInvitationController::class, 'invite']);

    // Role Management (Company Owners)
    Route::get('/roles/permissions', [RoleController::class, 'permissions']);
    Route::apiResource('roles', RoleController::class);
});

// Super Admin Panel
Route::middleware(['auth:sanctum', 'role:Super Admin'])->prefix('admin')->group(function () {
    Route::apiResource('companies', CompanyController::class);
});

// Modül route'ları service provider'lardan otomatik yükleniyor.
// Tüm modül route'ları kendi içlerinde 'auth:sanctum' middleware'i ile korunmaktadır.
