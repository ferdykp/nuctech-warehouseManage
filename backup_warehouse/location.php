<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class location extends Model
{
    use HasFactory;

    protected $table = 'locations';

    protected $fillable = [
        'machine_type',
        'location_name'
    ];

    // ✅ Accessor untuk label machine type
    public function getMachineTypeLabelAttribute()
    {
        return match ($this->machine_type) {
            'fs6000jkt' => 'FS6000 Jakarta',
            'fs6000sby' => 'FS6000 Surabaya',
            'fs6000smg' => 'FS6000 Semarang',
            'ebeam'     => 'E-Beam',
            'ctmic'        => 'CTMIC',
            default     => $this->machine_type,
        };
    }
}
