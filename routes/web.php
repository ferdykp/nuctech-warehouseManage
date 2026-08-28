<?php

use App\Http\Controllers\AdminReimbursementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SparepartController;
use App\Http\Controllers\SparepartStockController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ShiftController;
// use App\Models\User;
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
| Authenticated Routes (Semua halaman yang butuh login)
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

    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/schedules/generate', [ScheduleController::class, 'generate'])->name('schedule.generate');
    Route::post('/schedules/site-pattern/{site}', [ScheduleController::class, 'updateSitePattern'])->name('schedule.site.update');
    Route::post('/schedule/update-single', [ScheduleController::class, 'updateSingle'])->name('schedule.updateSingle');
    Route::delete('/schedule/clear', [ScheduleController::class, 'clearSchedule'])->name('schedule.clear');
    Route::get('/schedule/export', [ScheduleController::class, 'exportExcel'])->name('schedule.export');

    Route::get('/shift', [ShiftController::class, 'index'])->name('shift.index');
    Route::post('/shift', [ShiftController::class, 'store'])->name('shift.store');
    Route::put('/shift/{shift}', [ShiftController::class, 'update'])->name('shift.update'); // ROUTE UPDATE BARU
    Route::delete('/shift/{shift}', [ShiftController::class, 'destroy'])->name('shift.destroy');

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

    // Fitur Reimbursement System
    Route::get('/reimbursements/export-pdf', [AdminReimbursementController::class, 'exportApprovedPdf'])->name('reimbursements.export_pdf');
    Route::get('/reimbursements/export-excel', [AdminReimbursementController::class, 'exportExcel'])->name('reimbursements.export_excel');
    Route::get('/reimbursements/{id}/export-single-pdf', [AdminReimbursementController::class, 'exportSinglePdf'])->name('reimbursements.export_single_pdf');
    Route::get('/reimbursements', [AdminReimbursementController::class, 'index'])->name('reimbursements.index');
    Route::get('/reimbursements/create', [AdminReimbursementController::class, 'create'])->name('reimbursements.create');
    Route::post('/reimbursements/store', [AdminReimbursementController::class, 'store'])->name('reimbursements.store');
    Route::get('/reimbursements/{id}', [AdminReimbursementController::class, 'show'])->name('reimbursements.show');
    Route::get('/reimbursements/{id}/approval', [AdminReimbursementController::class, 'approval'])->name('reimbursements.approval');

    // Filter Khusus Pemeriksa Berwenang
    Route::middleware(['role:superadmin|admin_site|manager|station_master|team_leader'])->group(function () {
        Route::put('/reimbursements/{id}/approve', [AdminReimbursementController::class, 'approve'])->name('reimbursements.approve');
        Route::put('/reimbursements/{id}/reject', [AdminReimbursementController::class, 'reject'])->name('reimbursements.reject');
        Route::delete('/reimbursements/{id}', [AdminReimbursementController::class, 'destroy'])->name('reimbursements.destroy');
        Route::get('/reimbursements/{id}/edit', [AdminReimbursementController::class, 'edit'])->name('reimbursements.edit');
        Route::put('/reimbursements/{id}', [AdminReimbursementController::class, 'update'])->name('reimbursements.update');

        // PANDUAN PERBAIKAN: Rute-rute ini dimasukkan ke dalam auth grup agar aman
        // Manajemen Profil
        // Route::get('/profile/profile/{id}', [UserController::class, 'show'])->name('users.show');

        // Route::put('profile/profileEdit/{id}', [UserController::class, 'update'])->name('users.update');
        Route::get('/profile/profile', [UserController::class, 'index'])->name('profile.profile');
        Route::get('/profile/create', [UserController::class, 'create'])->name('profile.create');
        Route::post('/profile/store', [UserController::class, 'store'])->name('profile.store');

        // 2. Rute Dinamis dengan Parameter {id} (Taruh di BAWAH)
        Route::get('/profile/profile/{id}', [UserController::class, 'show'])->name('profile.profileShow');
        Route::get('/profile/profileEdit/{id}', [UserController::class, 'edit'])->name('profile.profileEdit');
        Route::put('/profile/profileEdit/{id}', [UserController::class, 'update'])->name('profile.profileUpdate');
        Route::delete('/profile/{id}', [UserController::class, 'delete'])->name('profile.destroy');

        Route::post('/salary/generate-monthly', [SalaryController::class, 'generateMonthlySalaries'])->name('salary.generateMonthly');
        Route::get('/salary/export-excel', [SalaryController::class, 'exportExcel'])->name('salary.exportExcel');
        Route::resource('salary', SalaryController::class);
    });





    // Manajemen Employee
    // Manajemen Employee
    Route::prefix('employee')->name('employee.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/export', [EmployeeController::class, 'export'])->name('export');
        Route::post('/import', [EmployeeController::class, 'import'])->name('import');
        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/store', [EmployeeController::class, 'store'])->name('store');
        Route::get('/{id}', [EmployeeController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('destroy');
    });

    // API Internal (Fetch JS)
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/branches/{site_id}/employees', [EmployeeController::class, 'getEmployeesByBranch'])->name('employees.by-branch');
    });

    // Absensi
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/store', [AttendanceController::class, 'storeAttendance'])->name('attendance.store');
    Route::get('/attendance/export', [AttendanceController::class, 'exportExcel'])->name('attendance.export');
    Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');


    // Leave Management Routes
    Route::get('/leave', [LeaveRequestController::class, 'index'])->name('leave.index');
    Route::get('/leave/create', [LeaveRequestController::class, 'create'])->name('leave.create');
    Route::post('/leave', [LeaveRequestController::class, 'store'])->name('leave.store');
    Route::post('/leave/{id}/approve', [LeaveRequestController::class, 'approve'])->name('leave.approve');
    Route::post('/leave/{id}/reject', [LeaveRequestController::class, 'reject'])->name('leave.reject');
    Route::get('/leave/{id}/edit', [LeaveRequestController::class, 'edit'])->name('leave.edit');
    Route::put('/leave/{id}', [LeaveRequestController::class, 'update'])->name('leave.update');
    Route::delete('/leave/{id}', [LeaveRequestController::class, 'destroy'])->name('leave.destroy');

    /*
    |--------------------------------------------------------------------------
    | Superadmin Only Routes (Khusus Khusus Superadmin)
    |--------------------------------------------------------------------------
    |*/
    Route::middleware(['role:superadmin'])->group(function () {
        Route::get('profile/profileList', [UserController::class, 'profileList'])->name('profile.profileList');
        // Route::resource('users', UserController::class);
        // Route::get('/profile/store', [UserController::class, 'store'])->name('users.store');
        // Route::get('/profile/create', [UserController::class, 'create'])->name('users.create');
        // Route::get('/profile/edit', [UserController::class, 'edit'])->name('users.edit');
        // Route::delete('/profile/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
