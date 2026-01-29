<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SparepartController;
use App\Http\Controllers\SparepartStockController;



Route::get('/', [AuthController::class, 'login'])->name('login');
Route::post('/login/auth', [AuthController::class, 'loginAuth'])->name('auth.login');
Route::get('/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::middleware(['auth', 'nocache'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::prefix('{site}')
        ->whereIn('site', [
            'ebeam',
            'fsjkt',
            'fssby',
            'fssmg',
            'ctmic'
        ])
        ->group(function () {

            Route::get('/', [SparepartController::class, 'index'])
                ->name('sparepart.index');

            Route::get('/create', [SparepartController::class, 'create'])
                ->name('sparepart.create');

            Route::post('/', [SparepartController::class, 'store'])
                ->name('sparepart.store');

            // Route::get('/{id}/edit', [SparepartController::class, 'edit'])
            //     ->name('sparepart.edit');

            // Route::put('/{id}', [SparepartController::class, 'update'])
            //     ->name('sparepart.update');
            // MASTER SPAREPART (GLOBAL)
            Route::get('/sparepart/{id}/edit', [SparepartController::class, 'edit']);
            Route::put('/sparepart/{id}', [SparepartController::class, 'update']);

            // STOCK PER SITE
            Route::get('/stock/{stockId}/edit', [SparepartStockController::class, 'edit']);
            Route::put('/stock/{stockId}', [SparepartStockController::class, 'update']);

            Route::delete('/{id}', [SparepartController::class, 'destroy'])
                ->name('sparepart.destroy');

            Route::get('/sparepart/search', [SparepartController::class, 'search'])
                ->name('sparepart.search');

            Route::post(
                '/sparepart/bulk-delete',
                [SparepartController::class, 'bulkDelete']
            )->name('sparepart.bulkDelete');

            Route::get('/export', [SparepartController::class, 'exportExcel'])
                ->name('sparepart.export');

            Route::post('/sparepart/{sparepart}/move', [SparepartStockController::class, 'move'])->name('sparepart.move');
            Route::post('/sparepart/{sparepart}/change-condition', [SparepartStockController::class, 'changeCondition'])->name('sparepart.changeCondition');
        });
});




Route::middleware(['auth', 'nocache', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class);
});
