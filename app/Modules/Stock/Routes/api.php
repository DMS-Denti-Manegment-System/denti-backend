<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Controllers\StockController;
use App\Modules\Stock\Controllers\SupplierController;
use App\Modules\Stock\Controllers\ClinicController;
use App\Modules\Stock\Controllers\StockRequestController;
use App\Modules\Stock\Controllers\StockTransactionController;
use App\Modules\Stock\Controllers\StockAlertController;
use App\Modules\Stock\Controllers\StockReportController;

// Stocks
Route::prefix('api/stocks')->group(function () {
    Route::get('/', [StockController::class, 'index']);
    Route::post('/', [StockController::class, 'store']);
    Route::get('/{id}', [StockController::class, 'show']);
    Route::put('/{id}', [StockController::class, 'update']);
    Route::delete('/{id}', [StockController::class, 'destroy']);

    // Stock Operations
    Route::post('/{id}/adjust', [StockController::class, 'adjustStock']);
    Route::post('/{id}/use', [StockController::class, 'useStock']);

    // Stock Levels
    Route::get('/levels/low', [StockController::class, 'getLowStockItems']);
    Route::get('/levels/critical', [StockController::class, 'getCriticalStockItems']);
    Route::get('/levels/expiring', [StockController::class, 'getExpiringItems']);
});

// Suppliers
Route::prefix('api/suppliers')->group(function () {
    Route::get('/', [SupplierController::class, 'index']);
    Route::post('/', [SupplierController::class, 'store']);
    Route::get('/{id}', [SupplierController::class, 'show']);
    Route::put('/{id}', [SupplierController::class, 'update']);
    Route::delete('/{id}', [SupplierController::class, 'destroy']);
    Route::get('/active/list', [SupplierController::class, 'getActive']);
});

// Clinics
Route::prefix('api/clinics')->group(function () {
    Route::get('/', [ClinicController::class, 'index']);
    Route::post('/', [ClinicController::class, 'store']);
    Route::get('/{id}', [ClinicController::class, 'show']);
    Route::put('/{id}', [ClinicController::class, 'update']);
    Route::delete('/{id}', [ClinicController::class, 'destroy']);
    Route::get('/active/list', [ClinicController::class, 'getActive']);
    Route::get('/{id}/stocks', [ClinicController::class, 'getStocks']);
    Route::get('/{id}/summary', [ClinicController::class, 'getSummary']);
});

// Stock Requests
Route::prefix('api/stock-requests')->group(function () {
    Route::get('/', [StockRequestController::class, 'index']);
    Route::post('/', [StockRequestController::class, 'store']);
    Route::get('/{id}', [StockRequestController::class, 'show']);
    Route::put('/{id}/approve', [StockRequestController::class, 'approve']);
    Route::put('/{id}/reject', [StockRequestController::class, 'reject']);
    Route::put('/{id}/complete', [StockRequestController::class, 'complete']);
    Route::get('/pending/list', [StockRequestController::class, 'getPendingRequests']);
});

// Stock Transactions
Route::prefix('api/stock-transactions')->group(function () {
    Route::get('/', [StockTransactionController::class, 'index']);
    Route::get('/{id}', [StockTransactionController::class, 'show']);
    Route::get('/stock/{stockId}', [StockTransactionController::class, 'getByStock']);
    Route::get('/clinic/{clinicId}', [StockTransactionController::class, 'getByClinic']);
});

// Stock Alerts
Route::prefix('api/stock-alerts')->group(function () {
    Route::get('/', [StockAlertController::class, 'index']);
    Route::get('/{id}', [StockAlertController::class, 'show']);
    Route::put('/{id}/resolve', [StockAlertController::class, 'resolve']);
    Route::get('/statistics/summary', [StockAlertController::class, 'getStatistics']);
});

// Stock Reports
Route::prefix('api/stock-reports')->group(function () {
    Route::get('/summary', [StockReportController::class, 'summary']);
    Route::get('/movements', [StockReportController::class, 'movements']);
    Route::get('/top-used', [StockReportController::class, 'topUsedItems']);
    Route::get('/supplier-performance', [StockReportController::class, 'supplierPerformance']);
    Route::get('/expiry', [StockReportController::class, 'expiryReport']);
    Route::get('/clinic-comparison', [StockReportController::class, 'clinicComparison']);
    Route::get('/custom', [StockReportController::class, 'customReport']);
});