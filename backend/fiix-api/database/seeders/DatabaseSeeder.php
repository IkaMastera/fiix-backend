<?php

namespace Database\Seeders;

use App\Domain\Users\Enums\UserRole;
use App\Domain\Users\Enums\UserStatus;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Service Categories
        $electrical = ServiceCategory::create([
            'id'          => Str::uuid(),
            'name'        => 'Electrical',
            'slug'        => 'electrical',
            'description' => 'Electrical installations and repairs',
            'is_active'   => true,
            'sort_order'  => 1,
        ]);

        $hvac = ServiceCategory::create([
            'id'          => Str::uuid(),
            'name'        => 'Air Conditioning / Heating',
            'slug'        => 'hvac',
            'description' => 'HVAC installations and repairs',
            'is_active'   => true,
            'sort_order'  => 2,
        ]);

        // Services
        Service::create([
            'id'          => Str::uuid(),
            'category_id' => $electrical->id,
            'name'        => 'Electrical Wiring',
            'slug'        => 'electrical-wiring',
            'description' => 'Full electrical wiring service',
            'is_active'   => true,
            'sort_order'  => 1,
        ]);

        Service::create([
            'id'          => Str::uuid(),
            'category_id' => $hvac->id,
            'name'        => 'AC Repair',
            'slug'        => 'ac-repair',
            'description' => 'Air conditioning repair and maintenance',
            'is_active'   => true,
            'sort_order'  => 1,
        ]);

        // Users
        // Admin
        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'Admin',
            'last_name'  => 'Fiix',
            'email'      => 'admin@fiix.ge',
            'password'   => Hash::make('password123'),
            'role'       => UserRole::ADMIN->value,
            'status'     => UserStatus::ACTIVE->value,
            'phone'      => '+995500000001',
            'phone_verified_at' => now(),
        ]);

        // Operator
        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'Operator',
            'last_name'  => 'Fiix',
            'email'      => 'operator@fiix.ge',
            'password'   => Hash::make('password123'),
            'role'       => UserRole::OPERATOR->value,
            'status'     => UserStatus::ACTIVE->value,
            'phone'      => '+995500000002',
            'phone_verified_at' => now(),
        ]);

        // Technician
        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'Giorgi',
            'last_name'  => 'Technician',
            'email'      => 'tech@fiix.ge',
            'password'   => Hash::make('password123'),
            'role'       => UserRole::TECHNICIAN->value,
            'status'     => UserStatus::ACTIVE->value,
            'phone'      => '+995500000003',
            'phone_verified_at' => now(),
        ]);

        // Customer
        User::create([
            'id'         => Str::uuid(),
            'first_name' => 'Irakli',
            'last_name'  => 'Customer',
            'email'      => 'customer@fiix.ge',
            'password'   => Hash::make('password123'),
            'role'       => UserRole::CUSTOMER->value,
            'status'     => UserStatus::ACTIVE->value,
            'phone'      => '+995500000004',
            'phone_verified_at' => now(),
        ]);
    }
}