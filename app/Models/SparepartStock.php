<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SparepartStock extends Model
{
    protected $fillable = [
        'sparepart_id',
        'site_id',
        'condition',
        'qty',
    ];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
