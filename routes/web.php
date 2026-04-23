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

Route::get('/', [AuthController::class, 'login'])->name('login');
Route::post('/login/auth', [AuthController::class, 'loginAuth'])->name('auth.login');
Route::get('/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::middleware(['auth', 'nocache'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('branches', BranchController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('sites', SiteController::class);
    Route::get('inventory/{slug}', [SiteController::class, 'showInventory'])->name('sites.inventory');

    Route::resource('report', ReportController::class);
    Route::get('/report', [ReportController::class, 'index'])->name('report.index');
    Route::get('/report-export', [ReportController::class, 'export'])->name('report.export');
    Route::post('/report/bulk-delete', [ReportController::class, 'bulkDelete'])->name('report.bulk-delete');
    Route::post('/report/search', [ReportController::class, 'search'])->name('report.search');

    Route::prefix('inventory/{slug}')->group(function () {
        Route::get('/', [SparepartController::class, 'index'])->name('sparepart.index');
        Route::get('/search', [SparepartController::class, 'index'])->name('sparepart.search');
        Route::get('/create', [SparepartController::class, 'create'])->name('sparepart.create');
        Route::post('/store', [SparepartController::class, 'store'])->name('sparepart.store');
        Route::get('/export', [SparepartController::class, 'exportExcel'])->name('sparepart.export');
        Route::post('/import', [SparepartController::class, 'importExcel'])->name('sparepart.import');
    });

    Route::get('/sparepart/{slug}/{id}/edit', [SparepartController::class, 'edit'])->name('sparepart.edit');
    Route::put('/sparepart/{slug}/{id}', [SparepartController::class, 'update'])->name('sparepart.update');
    Route::delete('/sparepart/{slug}/{id}', [SparepartController::class, 'destroy'])->name('sparepart.destroy');
    Route::post('/sparepart/bulk-delete', [SparepartController::class, 'bulkDelete'])->name('sparepart.bulkDelete');

    Route::post('/movement/move/{id}', [SparepartStockController::class, 'move'])->name('stock.move');

});

Route::middleware(['auth', 'nocache', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('site', SiteController::class);
});


