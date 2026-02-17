<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobAssignment extends Model
{
    protected $fillable = [
        'job_id',
        'technician_user_id',
        'assigned_by_user_id',
        'accepted_at',
        'is_active',
        'deactivated_at',
        'deactivated_by_user_id',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}