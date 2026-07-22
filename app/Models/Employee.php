<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    /**
     * Kolom yang diizinkan untuk pengisian massal (Mass Assignment)
     * Menampung seluruh data Core HR, Pribadi, Kontak Darurat, dan Payroll.
     */
    protected $fillable = [
        // 1. Identitas Utama & Core HR
        'site_id',
        'branch_id',
        // 'nik',
        'name',
        'position',
        // 'department',
        'status',
        'join_date',
        'contract_start_date',
        // 'contract_end_date',

        // 2. Data Pribadi Legal & Kontak
        // 'ktp_number',
        // 'place_of_birth',
        // 'date_of_birth',
        // 'gender',
        // 'phone_number',
        // 'email',
        // 'address_ktp',
        // 'address_domicile',

        // 3. Kontak Darurat
        // 'emergency_contact_name',
        // 'emergency_contact_relation',
        // 'emergency_contact_phone',

        // 4. Finansial & Jaminan Sosial
        // 'bank_name',
        // 'bank_account_number',
        // 'bank_account_holder',
        // 'npwp_number',
        // 'bpjs_ketenagakerjaan',
        // 'bpjs_kesehatan',
        // 'ptkp_status',

        // 5. Status Sistem
        'is_active'
    ];

    /**
     * Mutasi Tipe Data (Casting)
     * Memastikan string tanggal dari database otomatis diubah menjadi objek Carbon/Date
     * dan boolean tetap bertipe true/false saat dipanggil di Blade.
     */
    protected $casts = [
        'join_date'           => 'date',
        'contract_start_date' => 'date',
        'contract_end_date'   => 'date',
        'date_of_birth'       => 'date',
        'is_active'           => 'boolean',
    ];

    /**
     * Relasi balik ke Site (Satu karyawan ditempatkan di satu Site)
     */
    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Hubungkan Karyawan ke Banyak Data Absensi (Satu karyawan punya banyak rekap bulanan)
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }
    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class, 'employee_id');
    }
}
