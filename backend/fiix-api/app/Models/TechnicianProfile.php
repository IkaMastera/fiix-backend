<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianProfile extends Model
{
    // UUID primary key
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'city_code',
        'verified_at',
        'verified_by_user_id',
        'license_number',
        'bio',
        'rating_avg',
        'jobs_completed',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'rating_avg' => 'decimal:2',
        'jobs_completed' => 'integer',
    ];

    // --- Relationships ---

    // The user account this profile belongs to
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // The operator/admin who verified this technician
    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    // --- Helper ---

    // Quick check if this technician is verified and eligible for assignments
    public function isEligibleForAssignment(): bool
    {
        return $this->verified_at !== null
            && $this->user->status === 'active';
    }
}