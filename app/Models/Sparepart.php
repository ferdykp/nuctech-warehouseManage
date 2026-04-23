<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sparepart extends Model
{
    protected $fillable = [
        'item_name',
        'serial_number',
        'category_id',
        'type',
        'uom',
        'image',
        'note',
        'source_data',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stocks()
    {
        return $this->hasMany(SparepartStock::class)->with('site');
    }

    public function histories()
    {
        return $this->hasMany(SparepartHistory::class);
    }
}
