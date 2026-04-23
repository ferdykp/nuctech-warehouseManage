<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SparepartTransfer extends Model
{
    protected $fillable = [
        'sparepart_id',
        'from_site_id',
        'to_site_id',
        'qty',
        'condition',
        'status', // 'pending', 'approved', 'rejected', 'received'
        'note',
        'approved_at',
        'received_at'
    ];

    // Relasi agar mudah memanggil nama barang/site di view
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
}
