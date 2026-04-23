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
        Route::get('/', [StockController::class, 'index']);
        Route::post('/', [StockController::class, 'store']);
        Route::get('/stats', [StockController::class, 'getStats']);
        Route::get('/low-level', [StockController::class, 'getLowLevel']);
        Route::get('/critical-level', [StockController::class, 'getCriticalLevel']);
        Route::get('/expiring', [StockController::class, 'getExpiring']);
        
        Route::put('/{id}/deactivate', [StockController::class, 'deactivate']);
        Route::delete('/{id}/force', [StockController::class, 'forceDelete']);
        Route::put('/{id}/reactivate', [StockController::class, 'reactivate']);

        Route::get('/{id}', [StockController::class, 'show']);
        Route::put('/{id}', [StockController::class, 'update']);
        Route::delete('/{id}', [StockController::class, 'destroy']);

        Route::post('/{id}/adjust', [StockController::class, 'adjustStock']);
        Route::post('/{id}/use', [StockController::class, 'useStock']);
    });

    // Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);
        Route::post('/', [SupplierController::class, 'store']);
        Route::get('/active/list', [SupplierController::class, 'getActive']);
        Route::get('/{id}', [SupplierController::class, 'show']);
        Route::put('/{id}', [SupplierController::class, 'update']);
        Route::delete('/{id}', [SupplierController::class, 'destroy']);
    });

    // Clinics
    Route::prefix('clinics')->group(function () {
        Route::get('/', [ClinicController::class, 'index']);
        Route::post('/', [ClinicController::class, 'store']);
        Route::get('/active/list', [ClinicController::class, 'getActive']);
        Route::get('/stats', [ClinicController::class, 'getStats']);
        Route::get('/{id}', [ClinicController::class, 'show']);
        Route::put('/{id}', [ClinicController::class, 'update']);
        Route::delete('/{id}', [ClinicController::class, 'destroy']);
        Route::get('/{id}/stocks', [ClinicController::class, 'getStocks']);
        Route::get('/{id}/summary', [ClinicController::class, 'getSummary']);
    });

    // Stock Requests
    Route::prefix('stock-requests')->group(function () {
        Route::get('/', [StockRequestController::class, 'index']);
        Route::post('/', [StockRequestController::class, 'store']);
        Route::get('/pending/list', [StockRequestController::class, 'getPendingRequests']);
        Route::get('/stats', [StockRequestController::class, 'getStats']);
        Route::get('/{id}', [StockRequestController::class, 'show']);
        Route::put('/{id}/approve', [StockRequestController::class, 'approve']);
        Route::put('/{id}/reject', [StockRequestController::class, 'reject']);
        Route::put('/{id}/complete', [StockRequestController::class, 'complete']);
    });

    // Stock Transactions
    Route::prefix('stock-transactions')->group(function () {
        Route::get('/', [StockTransactionController::class, 'index']);
        Route::get('/stock/{stockId}', [StockTransactionController::class, 'getByStock']);
        Route::get('/clinic/{clinicId}', [StockTransactionController::class, 'getByClinic']);
        Route::get('/{id}', [StockTransactionController::class, 'show']);
    });

    // Stock Alerts
    Route::prefix('stock-alerts')->group(function () {
        Route::get('/', [StockAlertController::class, 'index']);
        Route::get('/pending/count', [StockAlertController::class, 'getPendingCount']);
        Route::post('/sync', [StockAlertController::class, 'sync']);
        Route::get('/active', [StockAlertController::class, 'getActive']);
        Route::get('/statistics', [StockAlertController::class, 'getStatistics']);
        Route::get('/settings', [StockAlertController::class, 'getSettings']);
        Route::put('/settings', [StockAlertController::class, 'updateSettings']);
        Route::post('/bulk/resolve', [StockAlertController::class, 'bulkResolve']);
        Route::post('/bulk/dismiss', [StockAlertController::class, 'bulkDismiss']);
        Route::post('/bulk/delete', [StockAlertController::class, 'bulkDelete']);
        Route::get('/{id}', [StockAlertController::class, 'show']);
        Route::post('/{id}/resolve', [StockAlertController::class, 'resolve']);
        Route::post('/{id}/dismiss', [StockAlertController::class, 'dismiss']);
        Route::delete('/{id}', [StockAlertController::class, 'destroy']);
    });

    // Stock Reports
    Route::prefix('stock-reports')->group(function () {
        Route::get('/summary', [StockReportController::class, 'summary']);
        Route::get('/movements', [StockReportController::class, 'movements']);
        Route::get('/top-used', [StockReportController::class, 'topUsedItems']);
        Route::get('/expiry', [StockReportController::class, 'expiryReport']);
        Route::get('/clinic-comparison', [StockReportController::class, 'clinicComparison']);
        Route::get('/trends', [StockReportController::class, 'trends']);
        Route::get('/categories', [StockReportController::class, 'categories']);
        Route::get('/forecast', [StockReportController::class, 'forecast']);
    });
});
