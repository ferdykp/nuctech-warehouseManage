<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SparepartHistory extends Model
{
    protected $fillable = [
        'sparepart_id',
        'from_site_id',
        'to_site_id',
        'action',
        'condition',
        'qty',
        'note',
    ];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }

    public function fromSite()
    {
        return $this->belongsTo(Site::class, 'from_site_id');
    }

    public function toSite()
    {
        return $this->belongsTo(Site::class, 'to_site_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
