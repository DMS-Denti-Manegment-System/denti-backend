<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TwoFactorAuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserInvitationController;
use Illuminate\Support\Facades\Route;

// Auth Routes (Public)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/invitations/accept', [UserInvitationController::class, 'accept']);

// Auth Routes (Protected)
Route::middleware(['auth:sanctum', 'set_permissions_team', '2fa.verified'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/dashboard/stats', [DashboardController::class, 'index']);
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
    Route::apiResource('users', UserController::class)->middleware('permission:manage-users');

    // Invitation Routes
    Route::post('/invitations/invite', [UserInvitationController::class, 'invite'])->middleware('permission:manage-users');

    // Role Management
    Route::get('/roles/permissions', [RoleController::class, 'permissions'])->middleware('permission:manage-users');
    Route::apiResource('roles', RoleController::class)->middleware('permission:manage-users')->names('api.roles');

    // Categories
    Route::prefix('categories')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CategoryController::class, 'index'])->middleware('permission:view-stocks');
        Route::post('/', [\App\Http\Controllers\Api\CategoryController::class, 'store'])->middleware('permission:create-stocks');
        Route::get('/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'show'])->middleware('permission:view-stocks');
        Route::get('/{category}/stats', [\App\Http\Controllers\Api\CategoryController::class, 'stats'])->middleware('permission:view-reports');
        Route::put('/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'update'])->middleware('permission:update-stocks');
        Route::delete('/{category}', [\App\Http\Controllers\Api\CategoryController::class, 'destroy'])->middleware('permission:delete-stocks');
    });

    // Todo
    Route::prefix('todos')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\TodoController::class, 'index'])->middleware('permission:view-todos');
        Route::post('/', [\App\Http\Controllers\Api\TodoController::class, 'store'])->middleware('permission:manage-todos');
        Route::get('/stats', [\App\Http\Controllers\Api\TodoController::class, 'stats'])->middleware('permission:view-todos');
        Route::get('/category/{categoryId}', [\App\Http\Controllers\Api\TodoController::class, 'byCategory'])->middleware('permission:view-todos');
        Route::get('/{todo}', [\App\Http\Controllers\Api\TodoController::class, 'show'])->middleware('permission:view-todos');
        Route::put('/{todo}', [\App\Http\Controllers\Api\TodoController::class, 'update'])->middleware('permission:manage-todos');
        Route::patch('/{todo}/toggle', [\App\Http\Controllers\Api\TodoController::class, 'toggle'])->middleware('permission:manage-todos');
        Route::delete('/{todo}', [\App\Http\Controllers\Api\TodoController::class, 'destroy'])->middleware('permission:manage-todos');
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ProductController::class, 'index'])->middleware('permission:view-stocks');
        Route::post('/', [\App\Http\Controllers\Api\ProductController::class, 'store'])->middleware('permission:create-stocks');
        Route::get('/{product}', [\App\Http\Controllers\Api\ProductController::class, 'show'])->middleware('permission:view-stocks');
        Route::put('/{product}', [\App\Http\Controllers\Api\ProductController::class, 'update'])->middleware('permission:update-stocks');
        Route::delete('/{product}', [\App\Http\Controllers\Api\ProductController::class, 'destroy'])->middleware('permission:delete-stocks');
        Route::get('/{product}/transactions', [\App\Http\Controllers\Api\ProductController::class, 'transactions'])->middleware('permission:view-audit-logs');
    });

    // Stocks
    Route::prefix('stocks')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\StockController::class, 'index'])->middleware('permission:view-stocks');
        Route::post('/', [\App\Http\Controllers\Api\StockController::class, 'store'])->middleware('permission:create-stocks');
        Route::get('/stats', [\App\Http\Controllers\Api\StockController::class, 'getStats'])->middleware('permission:view-reports');
        Route::get('/low-level', [\App\Http\Controllers\Api\StockController::class, 'getLowLevel'])->middleware('permission:view-stocks');
        Route::get('/critical-level', [\App\Http\Controllers\Api\StockController::class, 'getCriticalLevel'])->middleware('permission:view-stocks');
        Route::get('/expiring', [\App\Http\Controllers\Api\StockController::class, 'getExpiring'])->middleware('permission:view-stocks');
        Route::put('/{stock}/deactivate', [\App\Http\Controllers\Api\StockController::class, 'deactivate'])->middleware('permission:update-stocks');
        Route::delete('/{stock}/force', [\App\Http\Controllers\Api\StockController::class, 'forceDelete'])->middleware('permission:delete-stocks');
        Route::put('/{stock}/reactivate', [\App\Http\Controllers\Api\StockController::class, 'reactivate'])->middleware('permission:update-stocks');
        Route::get('/{stock}', [\App\Http\Controllers\Api\StockController::class, 'show'])->middleware('permission:view-stocks');
        Route::put('/{stock}', [\App\Http\Controllers\Api\StockController::class, 'update'])->middleware('permission:update-stocks');
        Route::delete('/{stock}', [\App\Http\Controllers\Api\StockController::class, 'destroy'])->middleware('permission:delete-stocks');
        Route::post('/{stock}/adjust', [\App\Http\Controllers\Api\StockController::class, 'adjustStock'])->middleware(['permission:adjust-stocks', 'throttle:30,1']);
        Route::post('/{stock}/use', [\App\Http\Controllers\Api\StockController::class, 'useStock'])->middleware(['permission:use-stocks', 'throttle:60,1']);
        Route::get('/{stock}/transactions', [\App\Http\Controllers\Api\StockController::class, 'transactions'])->middleware('permission:view-audit-logs');
    });

    // Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SupplierController::class, 'index'])->middleware('permission:view-stocks');
        Route::post('/', [\App\Http\Controllers\Api\SupplierController::class, 'store'])->middleware('permission:create-stocks');
        Route::get('/active/list', [\App\Http\Controllers\Api\SupplierController::class, 'getActive'])->middleware('permission:view-stocks');
        Route::get('/{supplier}', [\App\Http\Controllers\Api\SupplierController::class, 'show'])->middleware('permission:view-stocks');
        Route::put('/{supplier}', [\App\Http\Controllers\Api\SupplierController::class, 'update'])->middleware('permission:update-stocks');
        Route::delete('/{supplier}', [\App\Http\Controllers\Api\SupplierController::class, 'destroy'])->middleware('permission:delete-stocks');
    });

    // Clinics
    Route::prefix('clinics')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ClinicController::class, 'index'])->middleware('permission:view-clinics');
        Route::post('/', [\App\Http\Controllers\Api\ClinicController::class, 'store'])->middleware('permission:create-clinics');
        Route::get('/active/list', [\App\Http\Controllers\Api\ClinicController::class, 'getActive'])->middleware('permission:view-stocks');
        Route::get('/stats', [\App\Http\Controllers\Api\ClinicController::class, 'getStats'])->middleware('permission:view-reports');
        Route::get('/{clinic}', [\App\Http\Controllers\Api\ClinicController::class, 'show'])->middleware('permission:view-clinics');
        Route::put('/{clinic}', [\App\Http\Controllers\Api\ClinicController::class, 'update'])->middleware('permission:update-clinics');
        Route::delete('/{clinic}', [\App\Http\Controllers\Api\ClinicController::class, 'destroy'])->middleware('permission:delete-clinics');
        Route::get('/{id}/stocks', [\App\Http\Controllers\Api\ClinicController::class, 'getStocks'])->middleware('permission:view-stocks');
        Route::get('/{id}/summary', [\App\Http\Controllers\Api\ClinicController::class, 'getSummary'])->middleware('permission:view-reports');
    });

    // Stock Requests
    Route::prefix('stock-requests')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\StockRequestController::class, 'index'])->middleware('permission:view-stocks');
        Route::post('/', [\App\Http\Controllers\Api\StockRequestController::class, 'store'])->middleware(['permission:create-stocks', 'throttle:30,1']);
        Route::get('/pending/list', [\App\Http\Controllers\Api\StockRequestController::class, 'getPendingRequests'])->middleware('permission:view-stocks');
        Route::get('/stats', [\App\Http\Controllers\Api\StockRequestController::class, 'getStats'])->middleware('permission:view-stocks');
        Route::get('/{id}', [\App\Http\Controllers\Api\StockRequestController::class, 'show'])->middleware('permission:view-stocks');
        Route::put('/{id}/approve', [\App\Http\Controllers\Api\StockRequestController::class, 'approve'])->middleware(['permission:adjust-stocks', 'throttle:30,1']);
        Route::put('/{id}/reject', [\App\Http\Controllers\Api\StockRequestController::class, 'reject'])->middleware(['permission:adjust-stocks', 'throttle:30,1']);
        Route::put('/{id}/ship', [\App\Http\Controllers\Api\StockRequestController::class, 'ship'])->middleware(['permission:adjust-stocks', 'throttle:30,1']);
        Route::put('/{id}/complete', [\App\Http\Controllers\Api\StockRequestController::class, 'complete'])->middleware(['permission:adjust-stocks', 'throttle:30,1']);
    });

    // Stock Transfers
    Route::prefix('stock-transfers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\StockTransferController::class, 'index'])->middleware('permission:view-stocks');
        Route::get('/pending/count', [\App\Http\Controllers\Api\StockTransferController::class, 'getPendingCount'])->middleware('permission:view-stocks');
        Route::post('/', [\App\Http\Controllers\Api\StockTransferController::class, 'store'])->middleware(['permission:transfer-stocks', 'throttle:30,1']);
        Route::get('/{id}', [\App\Http\Controllers\Api\StockTransferController::class, 'show'])->middleware('permission:view-stocks');
        Route::post('/{id}/approve', [\App\Http\Controllers\Api\StockTransferController::class, 'approve'])->middleware(['permission:approve-transfers', 'throttle:30,1']);
        Route::post('/{id}/reject', [\App\Http\Controllers\Api\StockTransferController::class, 'reject'])->middleware(['permission:approve-transfers', 'throttle:30,1']);
        Route::post('/{id}/cancel', [\App\Http\Controllers\Api\StockTransferController::class, 'cancel'])->middleware(['permission:cancel-transfers', 'throttle:30,1']);
    });

    // Stock Transactions
    Route::prefix('stock-transactions')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\StockTransactionController::class, 'index'])->middleware('permission:view-audit-logs');
        Route::get('/stock/{stockId}', [\App\Http\Controllers\Api\StockTransactionController::class, 'getByStock'])->middleware('permission:view-audit-logs');
        Route::get('/clinic/{clinicId}', [\App\Http\Controllers\Api\StockTransactionController::class, 'getByClinic'])->middleware('permission:view-audit-logs');
        Route::get('/{id}', [\App\Http\Controllers\Api\StockTransactionController::class, 'show'])->middleware('permission:view-audit-logs');
        Route::post('/{id}/reverse', [\App\Http\Controllers\Api\StockTransactionController::class, 'reverse'])->middleware('permission:adjust-stocks');
    });

    // Stock Alerts
    Route::prefix('stock-alerts')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\StockAlertController::class, 'index'])->middleware('permission:view-stocks');
        Route::get('/pending/count', [\App\Http\Controllers\Api\StockAlertController::class, 'getPendingCount'])->middleware('permission:view-stocks');
        Route::post('/sync', [\App\Http\Controllers\Api\StockAlertController::class, 'sync'])->middleware('permission:adjust-stocks');
        Route::get('/active', [\App\Http\Controllers\Api\StockAlertController::class, 'getActive'])->middleware('permission:view-stocks');
        Route::get('/statistics', [\App\Http\Controllers\Api\StockAlertController::class, 'getStatistics'])->middleware('permission:view-reports');
        Route::get('/settings', [\App\Http\Controllers\Api\StockAlertController::class, 'getSettings'])->middleware('permission:manage-company');
        Route::put('/settings', [\App\Http\Controllers\Api\StockAlertController::class, 'updateSettings'])->middleware('permission:manage-company');
        Route::post('/bulk/resolve', [\App\Http\Controllers\Api\StockAlertController::class, 'bulkResolve'])->middleware('permission:adjust-stocks');
        Route::post('/bulk/dismiss', [\App\Http\Controllers\Api\StockAlertController::class, 'bulkDismiss'])->middleware('permission:adjust-stocks');
        Route::post('/bulk/delete', [\App\Http\Controllers\Api\StockAlertController::class, 'bulkDelete'])->middleware('permission:delete-stocks');
        Route::get('/{id}', [\App\Http\Controllers\Api\StockAlertController::class, 'show'])->middleware('permission:view-stocks');
        Route::post('/{id}/resolve', [\App\Http\Controllers\Api\StockAlertController::class, 'resolve'])->middleware('permission:adjust-stocks');
        Route::post('/{id}/dismiss', [\App\Http\Controllers\Api\StockAlertController::class, 'dismiss'])->middleware('permission:adjust-stocks');
        Route::delete('/{id}', [\App\Http\Controllers\Api\StockAlertController::class, 'destroy'])->middleware('permission:delete-stocks');
    });
});
