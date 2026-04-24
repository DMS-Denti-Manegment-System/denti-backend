<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TwoFactorAuthController;
use App\Http\Controllers\Api\UserInvitationController;
use App\Http\Controllers\Api\Admin\CompanyController;
use App\Http\Controllers\Api\RoleController;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;

// Auth Routes (Public)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/invitations/accept', [UserInvitationController::class, 'accept']);

// Auth Routes (Protected)
Route::middleware(['auth:sanctum', '2fa.verified'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Profile Settings
    Route::put('/profile/info', [ProfileController::class, 'updateInfo']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // 2FA Routes (Refactored to TwoFactorAuthController)
    Route::prefix('auth/2fa')->group(function () {
        Route::post('/generate', [TwoFactorAuthController::class, 'generate']);
        Route::post('/confirm', [TwoFactorAuthController::class, 'confirm'])->middleware('throttle:5,1');
        Route::post('/verify', [TwoFactorAuthController::class, 'verify'])->middleware('throttle:5,1');
        Route::post('/recovery-codes', [TwoFactorAuthController::class, 'regenerateRecoveryCodes'])->middleware('throttle:5,1');
    });

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
