<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeContract extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'contract_date' => 'date', 'start_date' => 'date', 'end_date' => 'date', 'notified_at' => 'date', 'responded_at' => 'date', 'activated_at' => 'datetime'];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function template()
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }

    public function renewals()
    {
        return $this->hasMany(self::class, 'previous_contract_id');
    }

    public function scopeNeedsRenewal($query)
    {
        return $query->where('status', 'active')->whereDoesntHave('renewals', fn ($q) => $q->where('status', 'active'));
    }

    public function previous()
    {
        return $this->belongsTo(self::class, 'previous_contract_id');
    }

    public function events()
    {
        return $this->hasMany(ContractEvent::class)->latest('id');
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'active') {
            if ($this->start_date?->isAfter(today())) {
                return 'Terjadwal';
            }
            if ($this->end_date?->isBefore(today())) {
                return 'Berakhir';
            }
        }

        return ['needs_verification' => 'Perlu Verifikasi', 'draft' => 'Draft', 'pending_signature' => 'Menunggu Tanda Tangan', 'active' => 'Aktif', 'cancelled' => 'Tidak Diperpanjang / Dibatalkan'][$this->status] ?? $this->status;
    }

    public function getEditableAttribute(): bool
    {
        return in_array($this->status, ['draft', 'needs_verification'], true);
    }

    public function fingerprint(): string
    {
        return hash('sha256', json_encode([$this->contract_number, $this->contract_date?->toDateString(), $this->start_date?->toDateString(), $this->end_date?->toDateString(), $this->snapshot, $this->template_id]));
    }
}
