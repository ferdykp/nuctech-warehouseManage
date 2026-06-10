<?php

use App\Http\Controllers\AdminReimbursementController;
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
    Route::resource('users', UserController::class);


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
        Route::get('/global/export', [ReportController::class, 'exportAll'])->name('export_all');
    });

    // Inventory / Sparepart
    Route::get('/spareparts/all', [SparepartController::class, 'allSpareparts'])->name('sparepart.all');

    Route::prefix('inventory/{slug}')->name('sparepart.')->group(function () {
        Route::post('/adjust/{id}', [SparepartController::class, 'adjust'])->name('adjust');
        Route::get('/', [SparepartController::class, 'index'])->name('index');
        Route::get('/search', [SparepartController::class, 'index'])->name('search');
        Route::get('/create', [SparepartController::class, 'create'])->name('create');
        Route::post('/store', [SparepartController::class, 'store'])->name('store');
        Route::get('/export', [SparepartController::class, 'exportExcel'])->name('export');
        Route::post('/import', [SparepartController::class, 'importExcel'])->name('import');
    });

    Route::delete('/inventory/{site}/stock/{id}', [SparepartController::class, 'destroyStock'])->name('sparepart.stock.destroy');

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

    // =================================================================
    // 4. FITUR BARU: REIMBURSEMENT SYSTEM
    // =================================================================
    // Akses Bersama: Semua admin yang login bisa melihat daftar klaimnya & membuat klaim baru
    // Route::get('/reimbursements', [AdminReimbursementController::class, 'index'])->name('reimbursements.index');
    // Route::get('/reimbursements/create', [AdminReimbursementController::class, 'create'])->name('reimbursements.create');
    // Route::post('/reimbursements/store', [AdminReimbursementController::class, 'store'])->name('reimbursements.store');
    // Route::get('/reimbursements/{id}', [AdminReimbursementController::class, 'show'])->name('reimbursements.show');
    // Route::get('/reimbursements/{id}/approval', [AdminReimbursementController::class, 'approval'])
    //     ->name('reimbursements.approval');


    // // Khusus Superadmin & Manager: Tombol Approval / Reject klaim dana uang masuk
    // Route::middleware(['role:superadmin|admin_site'])->group(function () {
    //     Route::put('/reimbursements/{id}/approve', [AdminReimbursementController::class, 'approve'])->name('reimbursements.approve');
    //     Route::put('/reimbursements/{id}/reject', [AdminReimbursementController::class, 'reject'])->name('reimbursements.reject');
    // });
    // =================================================================
    // 4. FITUR BARU: REIMBURSEMENT SYSTEM
    // =================================================================
    Route::get('/reimbursements/export-pdf', [AdminReimbursementController::class, 'exportApprovedPdf'])
        ->name('reimbursements.export_pdf');
    Route::get('/reimbursements/export-excel', [AdminReimbursementController::class, 'exportExcel'])->name('reimbursements.export_excel');
    Route::get('/reimbursements/{id}/export-single-pdf', [AdminReimbursementController::class, 'exportSinglePdf'])
        ->name('reimbursements.export_single_pdf');

    // Akses Bersama: Semua user yang login bisa memantau antrean/daftar klaimnya & mengawali pembuatan berkas klaim baru
    Route::get('/reimbursements', [AdminReimbursementController::class, 'index'])->name('reimbursements.index');
    Route::get('/reimbursements/create', [AdminReimbursementController::class, 'create'])->name('reimbursements.create');
    Route::post('/reimbursements/store', [AdminReimbursementController::class, 'store'])->name('reimbursements.store');
    Route::get('/reimbursements/{id}', [AdminReimbursementController::class, 'show'])->name('reimbursements.show');

    // Workspace Peninjauan Tanda Tangan: Diperbolehkan diakses oleh user-user pemeriksa maupun staff pembuat berkas
    Route::get('/reimbursements/{id}/approval', [AdminReimbursementController::class, 'approval'])->name('reimbursements.approval');

    // Filter Khusus Pemeriksa Berwenang: Mencegah staff biasa mengeksekusi API persetujuan (Approve) atau penolakan (Reject)
    // Ditambahkan role baru: team_leader, station_master, manager
    Route::middleware(['role:superadmin|admin_site|manager|station_master|team_leader'])->group(function () {
        Route::put('/reimbursements/{id}/approve', [AdminReimbursementController::class, 'approve'])->name('reimbursements.approve');
        Route::put('/reimbursements/{id}/reject', [AdminReimbursementController::class, 'reject'])->name('reimbursements.reject');
        Route::delete('/reimbursements/{id}', [AdminReimbursementController::class, 'destroy'])->name('reimbursements.destroy');
    });
});



/*
|--------------------------------------------------------------------------
| Superadmin Routes
|--------------------------------------------------------------------------
*/

// Route::middleware(['auth', 'nocache', 'role:superadmin'])->group(function () {
//     Route::resource('users', UserController::class);
// });
