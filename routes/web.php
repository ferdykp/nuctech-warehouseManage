<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SparepartController;
use App\Http\Controllers\SparepartStockController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [AuthController::class, 'login'])->name('login');
Route::post('/login/auth', [AuthController::class, 'loginAuth'])->name('auth.login');
Route::get('/logout', [AuthController::class, 'logout'])->name('auth.logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'nocache'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Resources
    Route::resource('branches', BranchController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('sites', SiteController::class);
    Route::resource('site', SiteController::class);

    // Report
    Route::prefix('report')->name('report.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
        Route::post('/bulk-delete', [ReportController::class, 'bulkDelete'])->name('bulk-delete');
        Route::post('/search', [ReportController::class, 'search'])->name('search');
        Route::resource('/', ReportController::class)->except(['index']);
    });

    // Inventory / Sparepart
    Route::get('/spareparts/all', [SparepartController::class, 'allSpareparts'])->name('sparepart.all');

    Route::prefix('inventory/{slug}')->name('sparepart.')->group(function () {
        Route::get('/', [SparepartController::class, 'index'])->name('index');
        Route::get('/search', [SparepartController::class, 'index'])->name('search');
        Route::get('/create', [SparepartController::class, 'create'])->name('create');
        Route::post('/store', [SparepartController::class, 'store'])->name('store');
        Route::get('/export', [SparepartController::class, 'exportExcel'])->name('export');
        Route::post('/import', [SparepartController::class, 'importExcel'])->name('import');
    });

    Route::prefix('sparepart/{slug}/{id}')->name('sparepart.')->group(function () {
        Route::get('/edit', [SparepartController::class, 'edit'])->name('edit');
        Route::put('/', [SparepartController::class, 'update'])->name('update');
        Route::delete('/', [SparepartController::class, 'destroy'])->name('destroy');
    });

    Route::post('/sparepart/bulk-delete', [SparepartController::class, 'bulkDelete'])->name('sparepart.bulkDelete');

    // Stock Movement
    Route::prefix('movement')->name('movement.')->group(function () {
        Route::post('/move/{id}', [SparepartStockController::class, 'move'])->name('move');
        Route::post('/request/{id}', [SparepartStockController::class, 'requestMove'])->name('request');
        Route::post('/approve/{id}', [SparepartStockController::class, 'approveMove'])->name('approve');
        Route::post('/receive/{id}', [SparepartStockController::class, 'receiveMove'])->name('receive');
    });
});

/*
|--------------------------------------------------------------------------
| Superadmin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'nocache', 'role:superadmin'])->group(function () {
    Route::resource('users', UserController::class);
});
