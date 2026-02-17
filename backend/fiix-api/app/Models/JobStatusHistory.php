<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobStatusHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'job_id',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'reason_code',
        'reason_note',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
