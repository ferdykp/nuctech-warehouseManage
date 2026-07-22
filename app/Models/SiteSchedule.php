<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSchedule extends Model
{
    protected $fillable = ['site_id', 'schedule_type', 'work_days', 'off_days'];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
