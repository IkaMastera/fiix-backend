<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class JobStatusHistory extends Model
{
    // UUID primary key
    use HasUuids;

    // Table name
    protected $table = 'job_status_history';

    // This table has no updated_at - it is append only, never updated
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
        'created_at' => 'datetime',
    ];

    // --- Relationships ---

    // The job this history entry belongs to
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    // Who triggered this status change
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}