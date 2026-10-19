<?php
$root = dirname(__DIR__, 2);
if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'sqlite'
    || !preg_match('~^/(?:private/)?tmp/nuctech-ux-[a-zA-Z0-9_-]+\.sqlite$~', (string) getenv('DB_DATABASE'))) exit('Isolated database required.');
if (is_file(getenv('DB_DATABASE')) && filesize(getenv('DB_DATABASE')) > 0) exit('Choose a new QA database filename.');
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$branch = App\Models\Branch::create(['branch_name' => 'Cabang Pengujian Antarmuka', 'branch_code' => 'QA']);
$site = App\Models\Site::create(['branch_id' => $branch->id, 'machine_name' => 'Mesin Pengujian dengan Nama Panjang Jakarta', 'slug' => 'qa-jakarta', 'location' => 'Jakarta']);
foreach (['superadmin', 'administration', 'team_leader', 'employee_role', 'station_master', 'manager'] as $role) {
    App\Models\User::factory()->create(['username' => 'qa_'.$role, 'name' => 'Akun Uji '.$role, 'role' => $role, 'site_id' => $site->id,
        'password' => Illuminate\Support\Facades\Hash::make('UxPreview-2026!')]);
}
$shift = App\Models\Shift::create(['shift_name' => 'Shift 1', 'start_time' => '08:00', 'end_time' => '16:00', 'is_off' => false]);
for ($i = 1; $i <= 14; $i++) {
    $employee = App\Models\Employee::create(['name' => 'Karyawan Uji '.$i.' dengan Nama Panjang', 'site_id' => $site->id, 'branch_id' => $branch->id,
        'nik' => str_pad((string) $i, 16, '0', STR_PAD_LEFT), 'phone_number' => '0800000000', 'mcu' => 'no', 'tld' => 'no', 'is_active' => true,
        'status' => 'Permanent', 'join_date' => '2026-01-01', 'basic_salary' => 5000000, 'bank_name' => 'BCA', 'bank_account_number' => '0000000000']);
    App\Models\EmployeeSchedule::create(['employee_id' => $employee->id, 'site_id' => $site->id, 'shift_id' => $shift->id, 'date' => '2026-10-19']);
    App\Models\Attendance::create(['employee_id' => $employee->id, 'month' => '2026-10', 'working_days' => 22, 'attendance_count' => 1, 'matrix_details' => json_encode([19 => ['s1' => 1, 's2' => 0, 's3' => 0]])]);
    App\Models\Salary::create(['employee_id' => $employee->id, 'name' => $employee->name, 'position' => 'Engineer', 'placement' => 'Jakarta', 'bank' => 'BCA', 'account_no' => '0000000000', 'amount' => 5000000, 'information' => 'regular salary']);
    App\Models\Reimbursement::create(['user_id' => ($i % 6) + 1, 'person_name' => $employee->name, 'date' => '2026-10-19',
        'category' => 'office', 'amount' => 100000, 'receipt_attachment' => 'qa-receipt.png', 'status' => ['pending', 'pending_leader', 'pending_station', 'pending_manager', 'approved', 'rejected'][$i % 6], 'comment' => str_repeat('Catatan pengujian panjang. ', 8)]);
}
require __DIR__.'/fixtures.php';
echo "Isolated QA fixture ready.\n";
