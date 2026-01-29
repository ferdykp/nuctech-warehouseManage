<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sparepart extends Model
{
    protected $fillable = [
        'item_name',
        'type',
        'uom',
        'image',
        'note',
    ];

    public function stocks()
    {
        return $this->hasMany(SparepartStock::class);
    }

    public function histories()
    {
        return $this->hasMany(SparepartHistory::class);
    }
}
