<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Health\HealthController;
use App\Http\Controllers\Web\AlertController;
use App\Http\Controllers\Web\AuthPageController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\ClinicController;
use App\Http\Controllers\Web\DashboardPageController;
use App\Http\Controllers\Web\EmployeeController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\RolePageController;
use App\Http\Controllers\Web\StockController;
use App\Http\Controllers\Web\StockRequestController;
use App\Http\Controllers\Web\SupplierController;
use App\Http\Controllers\Web\TodoController;

Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('login', [AuthenticatedSessionController::class, 'store']);
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::get('/accept-invitation/{token}', [AuthPageController::class, 'invitationForm'])->name('invitation.accept');
Route::post('/accept-invitation', [AuthPageController::class, 'acceptInvitation'])->name('invitation.accept.store');
Route::get('/up', HealthController::class)->name('health.up');

Route::middleware(['auth'])->group(function () {
    Route::get('/', DashboardPageController::class)->name('dashboard');

    // Stocks & Products
    Route::controller(StockController::class)->group(function () {
        Route::get('/stocks', 'index')->middleware('permission:view-stocks')->name('stocks.index');
        Route::get('/stocks/search-ajax', 'search')->middleware('permission:view-stocks')->name('stocks.search-ajax');
        Route::get('/stocks/create', 'create')->middleware('permission:create-stocks')->name('stocks.create');
        Route::post('/stocks', 'store')->middleware('permission:create-stocks')->name('stocks.store');
        Route::get('/stocks/{product}/edit', 'edit')->middleware('permission:update-stocks')->name('stocks.edit');
        Route::put('/stocks/{product}', 'update')->middleware('permission:update-stocks')->name('stocks.update');
        Route::delete('/stocks/{product}', 'destroy')->middleware('permission:delete-stocks')->name('stocks.destroy');
        Route::get('/stock/products/{id}', 'show')->middleware('permission:view-stocks')->name('products.show');
        Route::post('/stock/products/{product}/adjust', 'adjust')->middleware('permission:adjust-stocks')->name('products.adjust-stock');
        Route::post('/stock/batches/{stock}/use', 'use')->middleware('permission:use-stocks')->name('stocks.use');
        Route::post('/stock/products/{product}/batches', 'storeBatch')->middleware('permission:create-stocks')->name('stocks.batches.store');
    });

    // Categories
    Route::controller(CategoryController::class)->group(function () {
        Route::get('/stock-categories', 'index')->middleware('permission:view-categories')->name('categories.index');
        Route::get('/stock-categories/create', 'create')->middleware('permission:create-categories')->name('categories.create');
        Route::post('/stock-categories', 'store')->middleware('permission:create-categories')->name('categories.store');
        Route::get('/stock-categories/{category}/edit', 'edit')->middleware('permission:update-categories')->name('categories.edit');
        Route::put('/stock-categories/{category}', 'update')->middleware('permission:update-categories')->name('categories.update');
        Route::delete('/stock-categories/{category}', 'destroy')->middleware('permission:delete-categories')->name('categories.destroy');
    });

    // Suppliers
    Route::controller(SupplierController::class)->group(function () {
        Route::get('/suppliers', 'index')->middleware('permission:view-suppliers')->name('suppliers.index');
        Route::get('/suppliers/create', 'create')->middleware('permission:create-suppliers')->name('suppliers.create');
        Route::post('/suppliers', 'store')->middleware('permission:create-suppliers')->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit', 'edit')->middleware('permission:update-suppliers')->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', 'update')->middleware('permission:update-suppliers')->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', 'destroy')->middleware('permission:delete-suppliers')->name('suppliers.destroy');
    });

    // Clinics
    Route::controller(ClinicController::class)->group(function () {
        Route::get('/clinics', 'index')->middleware('permission:view-clinics')->name('clinics.index');
        Route::get('/clinics/create', 'create')->middleware('permission:create-clinics')->name('clinics.create');
        Route::post('/clinics', 'store')->middleware('permission:create-clinics')->name('clinics.store');
        Route::get('/clinics/{clinic}/edit', 'edit')->middleware('permission:update-clinics')->name('clinics.edit');
        Route::put('/clinics/{clinic}', 'update')->middleware('permission:update-clinics')->name('clinics.update');
        Route::delete('/clinics/{clinic}', 'destroy')->middleware('permission:delete-clinics')->name('clinics.destroy');
    });

    // Stock Requests
    Route::controller(StockRequestController::class)->group(function () {
        Route::get('/stock-requests', 'index')->middleware('permission:view-stocks|transfer-stocks|approve-transfers|cancel-transfers')->name('stock-requests.index');
        Route::get('/stock-requests/create', 'create')->middleware('permission:transfer-stocks')->name('stock-requests.create');
        Route::post('/stock-requests', 'store')->middleware('permission:transfer-stocks')->name('stock-requests.store');
        Route::get('/stock-requests/{stockRequest}', 'show')->middleware('permission:view-stocks')->name('stock-requests.show');
        Route::post('/stock-requests/{stockRequest}/approve', 'approve')->middleware('permission:approve-transfers')->name('stock-requests.approve');
        Route::post('/stock-requests/{stockRequest}/reject', 'reject')->middleware('permission:approve-transfers')->name('stock-requests.reject');
        Route::post('/stock-requests/{stockRequest}/ship', 'ship')->middleware('permission:approve-transfers')->name('stock-requests.ship');
        Route::post('/stock-requests/{stockRequest}/complete', 'complete')->middleware('permission:approve-transfers')->name('stock-requests.complete');
    });

    // Alerts
    Route::controller(AlertController::class)->group(function () {
        Route::get('/alerts', 'index')->middleware('permission:view-stocks')->name('alerts.index');
        Route::post('/alerts/{stockAlert}/resolve', 'resolve')->middleware('permission:adjust-stocks')->name('alerts.resolve');
        Route::post('/alerts/bulk/resolve', 'bulkResolve')->middleware('permission:adjust-stocks')->name('alerts.bulk-resolve');
        Route::get('/alerts/sync', 'sync')->middleware('permission:view-stocks')->name('alerts.sync');
        Route::get('/alerts/settings', 'settings')->middleware('permission:view-stocks')->name('alerts.settings');
    });

    // Todos
    Route::controller(TodoController::class)->group(function () {
        Route::get('/todos', 'index')->middleware('permission:view-todos')->name('todos.index');
        Route::get('/todos/create', 'create')->middleware('permission:manage-todos')->name('todos.create');
        Route::post('/todos', 'store')->middleware('permission:manage-todos')->name('todos.store');
        Route::get('/todos/{todo}/edit', 'edit')->middleware('permission:manage-todos')->name('todos.edit');
        Route::put('/todos/{todo}', 'update')->middleware('permission:manage-todos')->name('todos.update');
        Route::post('/todos/{todo}/toggle', 'toggle')->middleware('permission:manage-todos')->name('todos.toggle');
        Route::delete('/todos/{todo}', 'destroy')->middleware('permission:manage-todos')->name('todos.destroy');
    });

    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:view-reports')->name('reports.index');

    // Employees
    Route::controller(EmployeeController::class)->group(function () {
        Route::get('/employees', 'index')->middleware('permission:manage-users')->name('employees.index');
        Route::get('/employees/create', 'create')->middleware('permission:manage-users')->name('employees.create');
        Route::post('/employees', 'store')->middleware('permission:manage-users')->name('employees.store');
        Route::get('/employees/{user}/edit', 'edit')->middleware('permission:manage-users')->name('employees.edit');
        Route::put('/employees/{user}', 'update')->middleware('permission:manage-users')->name('employees.update');
        Route::delete('/employees/{user}', 'destroy')->middleware('permission:manage-users')->name('employees.destroy');
    });

    Route::get('/roles', RolePageController::class)->middleware('permission:manage-users')->name('roles.index');
    Route::get('/roles/create', [RolePageController::class, 'create'])->middleware('permission:manage-users')->name('roles.create');
    Route::post('/roles', [RolePageController::class, 'store'])->middleware('permission:manage-users')->name('roles.store');
    Route::get('/roles/{role}/edit', [RolePageController::class, 'edit'])->middleware('permission:manage-users')->name('roles.edit');
    Route::put('/roles/{role}', [RolePageController::class, 'update'])->middleware('permission:manage-users')->name('roles.update');

    // Profile
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'index')->name('profile.index');
        Route::put('/profile/info', 'updateInfo')->name('profile.update.info');
        Route::put('/profile/password', 'updatePassword')->name('profile.update.password');
        Route::post('/profile/2fa/generate', 'generate2fa')->name('profile.2fa.generate');
        Route::post('/profile/2fa/confirm', 'confirm2fa')->name('profile.2fa.confirm');
        Route::post('/profile/2fa/disable', 'disable2fa')->name('profile.2fa.disable');
        Route::post('/profile/2fa/recovery-codes', 'recoveryCodes')->name('profile.2fa.recovery-codes');
    });

});
