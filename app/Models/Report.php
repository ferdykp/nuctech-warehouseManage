<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'site_id',
        'attendant',
        'site_machine',
        'series_machine',
        'failure_date',
        'failure_note',
        'ts_procedure',
        'image',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeAccessible($query)
    {
        return auth()->user()->isSuperAdmin() ? $query
            : $query->whereIn('site_id', \App\Services\SiteAccess::sites(auth()->user())->select('id'));
    }
}
