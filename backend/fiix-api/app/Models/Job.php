<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Job extends Model
{
    use HasUuids;  

    protected $fillable = [
        'customer_user_id',
        'customer_phone_snapshot',
        'customer_email_snapshot',
        'service_id',
        'original_service_id',
        'title',
        'description',
        'address_text',
        'lat',
        'lng',
        'location_source',
        'location_accuracy',
        'city_code',
        'urgency',
        'status',
        'reviewed_at',
        'reviewed_by_user_id',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancel_reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    // --- Relationships ---

    // The customer who submitted this job
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    // The service selected for this job
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    // The original service selected by customer at submission (never changes)
    public function originalService()
    {
        return $this->belongsTo(Service::class, 'original_service_id');
    }

    // Full assignment history for this job
    public function assignments()
    {
        return $this->hasMany(JobAssignment::class);
    }

    // Only the current active assignment
    public function activeAssignment()
    {
        return $this->hasOne(JobAssignment::class)
            ->where('is_active', true);
    }

    // Full status transition history
    public function statusHistory()
    {
        return $this->hasMany(JobStatusHistory::class);
    }

    // Operator who reviewed this job
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    // Who cancelled this job
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }
}