<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $fillable = [
        'customer_user_id',
        'service_id',
        'original_service_id',
        'title',
        'description',
        'address_text',
        'city_code',
        'urgency',
        'status',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancel_reason_code',
        'cancel_reason_note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(JobStatusHistory::class);
    }

    public function assignments()
    {
        return $this->hasMany(JobAssignment::class);
    }

    public function activeAssignment()
    {
        return $this->hasOne(JobAssignment::class)
            ->where('is_active', true);
    }
}