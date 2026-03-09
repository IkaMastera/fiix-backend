<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Service extends Model
{
    use SoftDeletes, HasUuids;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // --- Relationships ---

    // The category this service belongs to
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    // All jobs that used this service
    public function jobs()
    {
        return $this->hasMany(Job::class, 'service_id');
    }
}