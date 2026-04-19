<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserInvitationController;
use App\Http\Controllers\Api\Admin\CompanyController;
use App\Http\Controllers\Api\RoleController;

// Auth Routes (Public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/invitations/accept', [UserInvitationController::class, 'accept']);

// Auth Routes (Protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

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
