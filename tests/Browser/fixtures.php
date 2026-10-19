<?php
// Supplemental long-content records and files, exclusively in the isolated QA database.
if (!app()->environment('testing') || config('database.default') !== 'sqlite'
    || !preg_match('~^/(?:private/)?tmp/nuctech-ux-[a-zA-Z0-9_-]+\.sqlite$~', config('database.connections.sqlite.database'))) exit('QA database required.');
$site = App\Models\Site::firstOrFail();
$category = App\Models\Category::firstOrCreate(['name' => 'Kategori Pengujian Antarmuka']);
if (!App\Models\Sparepart::exists()) {
    for ($i = 1; $i <= 14; $i++) {
        $part = App\Models\Sparepart::create(['item_name' => 'Komponen Pengujian '.$i.' dengan Nama Panjang', 'serial_number' => 'QA-'.str_repeat('1234567890', 4).'-'.$i,
            'category_id' => $category->id, 'type' => 'QA', 'uom' => 'pcs', 'image' => 'qa-receipt.png', 'note' => str_repeat('Catatan panjang untuk pemeriksaan layout. ', 6)]);
        App\Models\SparepartStock::create(['sparepart_id' => $part->id, 'site_id' => $site->id, 'condition' => ['new', 'used-good', 'damaged', 'repair'][$i % 4], 'qty' => $i]);
    }
}
if (!App\Models\Report::exists()) {
    for ($i = 1; $i <= 14; $i++) {
        App\Models\Report::create(['site_id' => $site->id, 'site_machine' => $site->machine_name, 'series_machine' => 'QA-'.str_repeat('1234567890', 4), 'attendant' => 'Petugas Uji '.$i,
            'failure_date' => '2026-10-19', 'failure_note' => str_repeat('Catatan kerusakan panjang. ', 8), 'ts_procedure' => 'Prosedur pengujian layout', 'image' => 'qa-receipt.png']);
    }
}
if (!App\Models\DailyReport::exists()) {
    for ($i = 1; $i <= 14; $i++) App\Models\DailyReport::create(['site_id' => $site->id, 'user_id' => 1, 'report_date' => '2026-10-19', 'description' => str_repeat('Deskripsi pekerjaan untuk pemeriksaan tampilan. ', 10)]);
}
$type = App\Models\LeaveType::firstOrCreate(['name' => 'Cuti Pengujian'], ['default_quota' => 12, 'is_paid' => true, 'cut_annual_quota' => false, 'requires_file' => false]);
if (!App\Models\LeaveRequest::exists()) {
    foreach (App\Models\Employee::all() as $employee) App\Models\LeaveRequest::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id,
        'start_date' => '2026-10-20', 'end_date' => '2026-10-20', 'total_days' => 1, 'reason' => str_repeat('Alasan pengujian layout. ', 6), 'status' => 'pending']);
}
$pdf = new FPDF();
$pdf->AddPage(); $pdf->SetFont('Arial', '', 16); $pdf->Cell(0, 20, 'QA invoice - sample only');
$pdf->AddPage(); $pdf->Cell(0, 20, 'Second sample page');
$pdf->Output('F', '/tmp/nuctech-ux-receipt.pdf');
$image = imagecreatetruecolor(640, 960); imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
imagestring($image, 5, 20, 20, 'QA INVOICE - SAMPLE ONLY', imagecolorallocate($image, 30, 41, 59));
imagepng($image, '/tmp/nuctech-ux-receipt.png'); imagedestroy($image);
App\Models\Reimbursement::whereKey(1)->update(['receipt_attachment' => 'qa-receipt.pdf']);
