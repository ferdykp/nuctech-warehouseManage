<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reimbursement extends Model
{
    use HasFactory;

    /**
     * Atribut yang dapat diisi secara massal (Mass Assignable).
     * Semua parameter baru dan kolom total_amount dimasukkan ke sini.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'person_name',
        'date',
        'category',
        'from_location',
        'to_location',
        'amount',
        'total_amount', // Kolom total jika Anda menambahkannya ke skema database
        'comment',
        'receipt_attachment',
        'status',
        'approved_by',
        'rejected_reason',
    ];

    /**
     * Konversi (Casting) tipe data kolom database ke tipe data PHP.
     * Memastikan tanggal menjadi objek Carbon dan nominal uang dihitung dengan benar.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Relasi Balik ke Model User (Staf yang mengajukan Klaim).
     * Menghubungkan kolom 'user_id' di tabel reimbursements ke tabel users.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi Balik ke Model User (Manager/Superadmin yang melakukan Approval).
     * Menghubungkan kolom 'approved_by' ke tabel users.
     *
     * @return BelongsTo
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
