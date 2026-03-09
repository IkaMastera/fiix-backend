<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCategory extends Model
{
    use SoftDeletes, HasUuids;

    protected $fillable = [
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

    // All services that belong to this category
    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }

    // Only active services in this category
    public function activeServices()
    {
        return $this->hasMany(Service::class, 'category_id')
            ->where('is_active', true);
    }
}