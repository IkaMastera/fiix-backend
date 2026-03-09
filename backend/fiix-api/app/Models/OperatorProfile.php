<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OperatorProfile extends Model
{
    // UUID primary key
    use HasUuids;  

    protected $fillable = [
        'user_id',
        'display_name',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // --- Relationships ---

    // The user account this profile belongs to
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}