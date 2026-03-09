<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class JobAssignment extends Model
{
    use HasUuids;  

    protected $fillable = [
        'job_id',
        'technician_user_id',
        'assigned_by_user_id',
        'assigned_at',
        'accepted_at',
        'accepted_by_user_id',
        'is_active',
        'deactivated_at',
        'deactivated_by_user_id',
        'deactivation_reason',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // --- Relationships ---

    // The job this assignment belongs to
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    // The technician assigned to this job
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_user_id');
    }

    // The operator who created this assignment
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    // The user who accepted this assignment (always equals technician)
    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    // The operator/admin who deactivated this assignment
    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by_user_id');
    }
}