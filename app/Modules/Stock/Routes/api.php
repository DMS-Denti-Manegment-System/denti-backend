<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Controllers\StockController;
use App\Modules\Stock\Controllers\SupplierController;
use App\Modules\Stock\Controllers\ClinicController;
use App\Modules\Stock\Controllers\StockRequestController;
use App\Modules\Stock\Controllers\StockTransactionController;
use App\Modules\Stock\Controllers\StockAlertController;
use App\Modules\Stock\Controllers\StockReportController;

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->group(function () {

    // Stocks
    Route::prefix('stocks')->group(function () {
        Route::get('/', [StockController::class, 'index'])->middleware('permission:view-stocks');
        Route::post('/', [StockController::class, 'store'])->middleware('permission:create-stocks');
        Route::get('/stats', [StockController::class, 'getStats'])->middleware('permission:view-reports');
        Route::get('/low-level', [StockController::class, 'getLowLevel'])->middleware('permission:view-stocks');
        Route::get('/critical-level', [StockController::class, 'getCriticalLevel'])->middleware('permission:view-stocks');
        Route::get('/expiring', [StockController::class, 'getExpiring'])->middleware('permission:view-stocks');
        
        Route::put('/{id}/deactivate', [StockController::class, 'deactivate'])->middleware('permission:update-stocks');
        Route::delete('/{id}/force', [StockController::class, 'forceDelete'])->middleware('permission:delete-stocks');
        Route::put('/{id}/reactivate', [StockController::class, 'reactivate'])->middleware('permission:update-stocks');

        Route::get('/{id}', [StockController::class, 'show'])->middleware('permission:view-stocks');
        Route::put('/{id}', [StockController::class, 'update'])->middleware('permission:update-stocks');
        Route::delete('/{id}', [StockController::class, 'destroy'])->middleware('permission:delete-stocks');

        Route::post('/{id}/adjust', [StockController::class, 'adjustStock'])->middleware('permission:adjust-stocks');
        Route::post('/{id}/use', [StockController::class, 'useStock'])->middleware('permission:use-stocks');
    });

    // Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->middleware('permission:view-stocks');
        Route::post('/', [SupplierController::class, 'store'])->middleware('permission:create-stocks');
        Route::get('/active/list', [SupplierController::class, 'getActive'])->middleware('permission:view-stocks');
        Route::get('/{id}', [SupplierController::class, 'show'])->middleware('permission:view-stocks');
        Route::put('/{id}', [SupplierController::class, 'update'])->middleware('permission:update-stocks');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->middleware('permission:delete-stocks');
    });

    // Clinics
    Route::prefix('clinics')->group(function () {
        Route::get('/', [ClinicController::class, 'index'])->middleware('permission:view-clinics');
        Route::post('/', [ClinicController::class, 'store'])->middleware('permission:create-clinics');
        Route::get('/active/list', [ClinicController::class, 'getActive'])->middleware('permission:view-clinics');
        Route::get('/stats', [ClinicController::class, 'getStats'])->middleware('permission:view-reports');
        Route::get('/{id}', [ClinicController::class, 'show'])->middleware('permission:view-clinics');
        Route::put('/{id}', [ClinicController::class, 'update'])->middleware('permission:update-clinics');
        Route::delete('/{id}', [ClinicController::class, 'destroy'])->middleware('permission:delete-clinics');
        Route::get('/{id}/stocks', [ClinicController::class, 'getStocks'])->middleware('permission:view-stocks');
        Route::get('/{id}/summary', [ClinicController::class, 'getSummary'])->middleware('permission:view-reports');
    });

    // Stock Requests
    Route::prefix('stock-requests')->group(function () {
        Route::get('/', [StockRequestController::class, 'index'])->middleware('permission:view-stocks');
        Route::post('/', [StockRequestController::class, 'store'])->middleware('permission:create-stocks');
        Route::get('/pending/list', [StockRequestController::class, 'getPendingRequests'])->middleware('permission:view-stocks');
        Route::get('/stats', [StockRequestController::class, 'getStats'])->middleware('permission:view-reports');
        Route::get('/{id}', [StockRequestController::class, 'show'])->middleware('permission:view-stocks');
        Route::put('/{id}/approve', [StockRequestController::class, 'approve'])->middleware('permission:adjust-stocks');
        Route::put('/{id}/reject', [StockRequestController::class, 'reject'])->middleware('permission:adjust-stocks');
        Route::put('/{id}/complete', [StockRequestController::class, 'complete'])->middleware('permission:adjust-stocks');
    });

    // Stock Transactions
    Route::prefix('stock-transactions')->group(function () {
        Route::get('/', [StockTransactionController::class, 'index'])->middleware('permission:view-audit-logs');
        Route::get('/stock/{stockId}', [StockTransactionController::class, 'getByStock'])->middleware('permission:view-audit-logs');
        Route::get('/clinic/{clinicId}', [StockTransactionController::class, 'getByClinic'])->middleware('permission:view-audit-logs');
        Route::get('/{id}', [StockTransactionController::class, 'show'])->middleware('permission:view-audit-logs');
    });

    // Stock Alerts
    Route::prefix('stock-alerts')->group(function () {
        Route::get('/', [StockAlertController::class, 'index'])->middleware('permission:view-stocks');
        Route::get('/pending/count', [StockAlertController::class, 'getPendingCount'])->middleware('permission:view-stocks');
        Route::post('/sync', [StockAlertController::class, 'sync'])->middleware('permission:adjust-stocks');
        Route::get('/active', [StockAlertController::class, 'getActive'])->middleware('permission:view-stocks');
        Route::get('/statistics', [StockAlertController::class, 'getStatistics'])->middleware('permission:view-reports');
        Route::get('/settings', [StockAlertController::class, 'getSettings'])->middleware('permission:manage-company');
        Route::put('/settings', [StockAlertController::class, 'updateSettings'])->middleware('permission:manage-company');
        Route::post('/bulk/resolve', [StockAlertController::class, 'bulkResolve'])->middleware('permission:adjust-stocks');
        Route::post('/bulk/dismiss', [StockAlertController::class, 'bulkDismiss'])->middleware('permission:adjust-stocks');
        Route::post('/bulk/delete', [StockAlertController::class, 'bulkDelete'])->middleware('permission:delete-stocks');
        Route::get('/{id}', [StockAlertController::class, 'show'])->middleware('permission:view-stocks');
        Route::post('/{id}/resolve', [StockAlertController::class, 'resolve'])->middleware('permission:adjust-stocks');
        Route::post('/{id}/dismiss', [StockAlertController::class, 'dismiss'])->middleware('permission:adjust-stocks');
        Route::delete('/{id}', [StockAlertController::class, 'destroy'])->middleware('permission:delete-stocks');
    });

    // Stock Reports
    Route::prefix('stock-reports')->group(function () {
        Route::get('/summary', [StockReportController::class, 'summary'])->middleware('permission:view-reports');
        Route::get('/movements', [StockReportController::class, 'movements'])->middleware('permission:view-reports');
        Route::get('/top-used', [StockReportController::class, 'topUsedItems'])->middleware('permission:view-reports');
        Route::get('/expiry', [StockReportController::class, 'expiryReport'])->middleware('permission:view-reports');
        Route::get('/clinic-comparison', [StockReportController::class, 'clinicComparison'])->middleware('permission:view-reports');
        Route::get('/trends', [StockReportController::class, 'trends'])->middleware('permission:view-reports');
        Route::get('/categories', [StockReportController::class, 'categories'])->middleware('permission:view-reports');
        Route::get('/forecast', [StockReportController::class, 'forecast'])->middleware('permission:view-reports');
    });
});
