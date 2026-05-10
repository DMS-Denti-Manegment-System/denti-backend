<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Health\HealthController;
use App\Http\Controllers\Web\AdminCompanyPageController;
use App\Http\Controllers\Web\AuthPageController;
use App\Http\Controllers\Web\DashboardPageController;
use App\Http\Controllers\Web\OperationsPageController;
use App\Http\Controllers\Web\RolePageController;

Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('login', [AuthenticatedSessionController::class, 'store']);
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::get('/admin/login', [AuthPageController::class, 'adminLoginForm'])->name('admin.login');
Route::post('/admin/login', [AuthPageController::class, 'adminLogin'])->name('admin.login.store');
Route::get('/accept-invitation/{token}', [AuthPageController::class, 'invitationForm'])->name('invitation.accept');
Route::post('/accept-invitation', [AuthPageController::class, 'acceptInvitation'])->name('invitation.accept.store');
Route::get('/up', HealthController::class)->name('health.up');

Route::middleware(['auth', 'role:Super Admin'])->group(function () {
    Route::get('/admin/companies', AdminCompanyPageController::class)->name('admin.companies');
    Route::get('/admin/companies/create', [AdminCompanyPageController::class, 'create'])->name('admin.companies.create');
    Route::post('/admin/companies', [AdminCompanyPageController::class, 'store'])->name('admin.companies.store');
    Route::get('/admin/companies/{company}/edit', [AdminCompanyPageController::class, 'edit'])->name('admin.companies.edit');
    Route::put('/admin/companies/{company}', [AdminCompanyPageController::class, 'update'])->name('admin.companies.update');
});

Route::middleware(['auth', 'not_super_admin'])->group(function () {
    Route::get('/', DashboardPageController::class)->name('dashboard');

    Route::get('/stocks', [OperationsPageController::class, 'stocks'])->middleware('permission:view-stocks')->name('stocks.index');
    Route::get('/stocks/create', [OperationsPageController::class, 'stockCreate'])->middleware('permission:create-stocks')->name('stocks.create');
    Route::post('/stocks', [OperationsPageController::class, 'stockStore'])->middleware('permission:create-stocks')->name('stocks.store');
    Route::delete('/stocks/{product}', [OperationsPageController::class, 'stockDestroy'])->middleware('permission:delete-stocks')->name('stocks.destroy');
    Route::get('/stocks/{product}/edit', [OperationsPageController::class, 'stockEdit'])->middleware('permission:update-stocks')->name('stocks.edit');
    Route::put('/stocks/{product}', [OperationsPageController::class, 'stockUpdate'])->middleware('permission:update-stocks')->name('stocks.update');
    Route::get('/stock/products/{id}', [OperationsPageController::class, 'stockShow'])->middleware('permission:view-stocks')->name('products.show');
    Route::post('/stock/products/{product}/adjust', [OperationsPageController::class, 'stockAdjust'])->middleware('permission:adjust-stocks')->name('products.adjust-stock');
    Route::post('/stock/products/{product}/batches', [OperationsPageController::class, 'stockBatchStore'])->middleware('permission:create-stocks')->name('stocks.batches.store');
    Route::post('/stock/batches/{stock}/use', [OperationsPageController::class, 'stockUse'])->middleware('permission:use-stocks')->name('stocks.use');

    Route::get('/stock-categories', [OperationsPageController::class, 'categories'])->middleware('permission:view-stocks')->name('categories.index');
    Route::get('/stock-categories/create', [OperationsPageController::class, 'categoryCreate'])->middleware('permission:create-stocks')->name('categories.create');
    Route::post('/stock-categories', [OperationsPageController::class, 'categoryStore'])->middleware('permission:create-stocks')->name('categories.store');
    Route::get('/stock-categories/{category}/edit', [OperationsPageController::class, 'categoryEdit'])->middleware('permission:update-stocks')->name('categories.edit');
    Route::put('/stock-categories/{category}', [OperationsPageController::class, 'categoryUpdate'])->middleware('permission:update-stocks')->name('categories.update');

    Route::get('/suppliers', [OperationsPageController::class, 'suppliers'])->middleware('permission:view-stocks')->name('suppliers.index');
    Route::get('/suppliers/create', [OperationsPageController::class, 'supplierCreate'])->middleware('permission:create-stocks')->name('suppliers.create');
    Route::post('/suppliers', [OperationsPageController::class, 'supplierStore'])->middleware('permission:create-stocks')->name('suppliers.store');
    Route::get('/suppliers/{supplier}/edit', [OperationsPageController::class, 'supplierEdit'])->middleware('permission:update-stocks')->name('suppliers.edit');
    Route::put('/suppliers/{supplier}', [OperationsPageController::class, 'supplierUpdate'])->middleware('permission:update-stocks')->name('suppliers.update');
    Route::delete('/suppliers/{supplier}', [OperationsPageController::class, 'supplierDestroy'])->middleware('permission:delete-stocks')->name('suppliers.destroy');

    Route::get('/clinics', [OperationsPageController::class, 'clinics'])->middleware('permission:view-clinics')->name('clinics.index');
    Route::get('/clinics/create', [OperationsPageController::class, 'clinicCreate'])->middleware('permission:create-clinics')->name('clinics.create');
    Route::post('/clinics', [OperationsPageController::class, 'clinicStore'])->middleware('permission:create-clinics')->name('clinics.store');
    Route::get('/clinics/{clinic}/edit', [OperationsPageController::class, 'clinicEdit'])->middleware('permission:update-clinics')->name('clinics.edit');
    Route::put('/clinics/{clinic}', [OperationsPageController::class, 'clinicUpdate'])->middleware('permission:update-clinics')->name('clinics.update');
    Route::delete('/clinics/{clinic}', [OperationsPageController::class, 'clinicDestroy'])->middleware('permission:delete-clinics')->name('clinics.destroy');

    Route::get('/stock-requests', [OperationsPageController::class, 'stockRequests'])->middleware('permission:view-stocks|transfer-stocks|approve-transfers|cancel-transfers')->name('stock-requests.index');
    Route::get('/stock-requests/create', [OperationsPageController::class, 'stockRequestCreate'])->middleware('permission:transfer-stocks')->name('stock-requests.create');
    Route::post('/stock-requests', [OperationsPageController::class, 'stockRequestStore'])->middleware('permission:transfer-stocks')->name('stock-requests.store');
    Route::post('/stock-requests/{stockRequest}/approve', [OperationsPageController::class, 'stockRequestApprove'])->middleware('permission:approve-transfers')->name('stock-requests.approve');
    Route::post('/stock-requests/{stockRequest}/reject', [OperationsPageController::class, 'stockRequestReject'])->middleware('permission:approve-transfers')->name('stock-requests.reject');
    Route::post('/stock-requests/{stockRequest}/ship', [OperationsPageController::class, 'stockRequestShip'])->middleware('permission:approve-transfers')->name('stock-requests.ship');
    Route::post('/stock-requests/{stockRequest}/complete', [OperationsPageController::class, 'stockRequestComplete'])->middleware('permission:approve-transfers')->name('stock-requests.complete');

    Route::get('/alerts', [OperationsPageController::class, 'alerts'])->middleware('permission:view-stocks')->name('alerts.index');
    Route::post('/alerts/{stockAlert}/resolve', [OperationsPageController::class, 'alertResolve'])->middleware('permission:adjust-stocks')->name('alerts.resolve');
    Route::post('/alerts/{stockAlert}/dismiss', [OperationsPageController::class, 'alertDismiss'])->middleware('permission:adjust-stocks')->name('alerts.dismiss');
    Route::post('/alerts/bulk/resolve', [OperationsPageController::class, 'alertBulkResolve'])->middleware('permission:adjust-stocks')->name('alerts.bulk-resolve');
    Route::post('/alerts/bulk/dismiss', [OperationsPageController::class, 'alertBulkDismiss'])->middleware('permission:adjust-stocks')->name('alerts.bulk-dismiss');
    Route::post('/alerts/bulk/delete', [OperationsPageController::class, 'alertBulkDelete'])->middleware('permission:delete-stocks')->name('alerts.bulk-delete');
    Route::get('/alerts/sync', [OperationsPageController::class, 'alertSync'])->middleware('permission:view-stocks')->name('alerts.sync');
    Route::get('/alerts/settings', [OperationsPageController::class, 'alertSettings'])->middleware('permission:view-stocks')->name('alerts.settings');
    Route::put('/alerts/settings', [OperationsPageController::class, 'alertUpdateSettings'])->middleware('permission:adjust-stocks')->name('alerts.update-settings');

    Route::get('/todos', [OperationsPageController::class, 'todos'])->middleware('permission:view-todos')->name('todos.index');
    Route::get('/todos/create', [OperationsPageController::class, 'todoCreate'])->middleware('permission:manage-todos')->name('todos.create');
    Route::post('/todos', [OperationsPageController::class, 'todoStore'])->middleware('permission:manage-todos')->name('todos.store');
    Route::get('/todos/{todo}/edit', [OperationsPageController::class, 'todoEdit'])->middleware('permission:manage-todos')->name('todos.edit');
    Route::put('/todos/{todo}', [OperationsPageController::class, 'todoUpdate'])->middleware('permission:manage-todos')->name('todos.update');
    Route::post('/todos/{todo}/toggle', [OperationsPageController::class, 'todoToggle'])->middleware('permission:manage-todos')->name('todos.toggle');
    Route::delete('/todos/{todo}', [OperationsPageController::class, 'todoDestroy'])->middleware('permission:manage-todos')->name('todos.destroy');

    Route::get('/reports', [OperationsPageController::class, 'reports'])->middleware('permission:view-reports')->name('reports.index');

    Route::get('/employees', [OperationsPageController::class, 'employees'])->middleware('permission:manage-users')->name('employees.index');
    Route::get('/employees/create', [OperationsPageController::class, 'employeeCreate'])->middleware('permission:manage-users')->name('employees.create');
    Route::post('/employees', [OperationsPageController::class, 'employeeStore'])->middleware('permission:manage-users')->name('employees.store');
    Route::get('/employees/{user}/edit', [OperationsPageController::class, 'employeeEdit'])->middleware('permission:manage-users')->name('employees.edit');
    Route::put('/employees/{user}', [OperationsPageController::class, 'employeeUpdate'])->middleware('permission:manage-users')->name('employees.update');
    Route::delete('/employees/{user}', [OperationsPageController::class, 'employeeDestroy'])->middleware('permission:manage-users')->name('employees.destroy');
    Route::get('/roles', RolePageController::class)->middleware('permission:manage-users')->name('roles.index');
    Route::get('/roles/create', [RolePageController::class, 'create'])->middleware('permission:manage-users')->name('roles.create');
    Route::post('/roles', [RolePageController::class, 'store'])->middleware('permission:manage-users')->name('roles.store');
    Route::get('/roles/{role}/edit', [RolePageController::class, 'edit'])->middleware('permission:manage-users')->name('roles.edit');
    Route::put('/roles/{role}', [RolePageController::class, 'update'])->middleware('permission:manage-users')->name('roles.update');
    Route::get('/profile', [OperationsPageController::class, 'profile'])->name('profile.index');
    Route::put('/profile/info', [OperationsPageController::class, 'profileUpdateInfo'])->name('profile.update.info');
    Route::put('/profile/password', [OperationsPageController::class, 'profileUpdatePassword'])->name('profile.update.password');
    Route::post('/profile/2fa/generate', [OperationsPageController::class, 'profile2faGenerate'])->name('profile.2fa.generate');
    Route::post('/profile/2fa/confirm', [OperationsPageController::class, 'profile2faConfirm'])->name('profile.2fa.confirm');
    Route::post('/profile/2fa/disable', [OperationsPageController::class, 'profile2faDisable'])->name('profile.2fa.disable');
    Route::post('/profile/2fa/recovery-codes', [OperationsPageController::class, 'profile2faRecoveryCodes'])->name('profile.2fa.recovery-codes');
});
