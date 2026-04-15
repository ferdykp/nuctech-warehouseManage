<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SparepartController;
use App\Http\Controllers\SparepartStockController;
// use Symfony\Component\Routing\Route;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'login'])->name('login');
Route::post('/login/auth', [AuthController::class, 'loginAuth'])->name('auth.login');
Route::get('/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::middleware(['auth', 'nocache'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::resource('branches', BranchController::class);

    Route::resource('sites', SiteController::class);
    Route::get('inventory/{slug}', [SiteController::class, 'showInventory'])->name('sites.inventory');



    Route::resource('report', ReportController::class);
    Route::get('/report', [ReportController::class, 'index'])->name('report.index');
    /*Route::get('/report', [ReportController::class, 'index'])->name('report');*/
    // Route::get('/report/create', [ReportController::class, 'create'])->name('report.create');
    Route::get('/report-export', [ReportController::class, 'export'])->name('report.export');
    Route::post('/report/bulk-delete', [ReportController::class, 'bulkDelete'])->name('report.bulk-delete');
    Route::post('/report/search', [ReportController::class, 'search'])->name('report.search');

    Route::get('/inventory/{slug}', [SparepartController::class, 'index'])->name('sparepart.index');
    Route::get('/inventory/{slug}/create', [SparepartController::class, 'create'])->name('sparepart.create');
    Route::post('/inventory/{slug}/store', [SparepartController::class, 'store'])->name('sparepart.store');

    Route::get('/{id}/edit', [SparepartController::class, 'edit'])
        ->name('sparepart.edit');

    Route::put('/{id}', [SparepartController::class, 'update'])
        ->name('sparepart.update');
    // MASTER SPAREPART (GLOBAL)
    // Route::get('/sparepart/{id}/edit', [SparepartController::class, 'edit']);
    // Route::put('/sparepart/{id}', [SparepartController::class, 'update']);

    // // STOCK PER SITE
    // Route::get('/stock/{stockId}/edit', [SparepartStockController::class, 'edit']);
    // Route::put('/stock/{stockId}', [SparepartStockController::class, 'update']);

    Route::delete('/{id}', [SparepartController::class, 'destroy'])
        ->name('sparepart.destroy');

    // Route::get('/sparepart/search', [SparepartController::class, 'search'])
    //     ->name('sparepart.search');
    // Cari baris ini dan ubah
    Route::get('/inventory/{slug}/search', [SparepartController::class, 'search'])
        ->name('sparepart.search');

    Route::post(
        '/sparepart/bulk-delete',
        [SparepartController::class, 'bulkDelete']
    )->name('sparepart.bulkDelete');

    Route::get('/export', [SparepartController::class, 'exportExcel'])
        ->name('sparepart.export');


    Route::post('/movement/move/{id}', [SparepartStockController::class, 'move'])->name('stock.move');
    // Route::post('/sparepart/{sparepart}/move', [SparepartStockController::class, 'move'])->name('sparepart.move');
    // Route::post('/sparepart/{sparepart}/change-condition', [SparepartStockController::class, 'changeCondition'])->name('sparepart.changeCondition');


});




Route::middleware(['auth', 'nocache', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('site', SiteController::class);
});
