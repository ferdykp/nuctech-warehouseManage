<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 1. Import Trait

class DailyReport extends Model
{
    use HasFactory;
    use SoftDeletes; // 2. Gunakan Trait


    protected $fillable = [
        'site_id',
        'user_id',
        'report_date',
        'description',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    protected $dates = ['deleted_at'];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function photos()
    {
        return $this->hasMany(DailyReportPhoto::class);
    }
}
