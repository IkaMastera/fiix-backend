<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("phone")->nullable()->unique();
            $table->string("email")->nullable()->unique();
            $table->string("password_hash")->nullable();

            // Role + status
            $table->string("role");
            $table->string("status");

             // Verification timestamps
            $table->timestampTz("email_verified_at")->nullable();
            $table->timestampTz("phone_verified_at")->nullable();

            // Profile
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();

            // Timestamps + soft delete
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->softDeletesTz();

            $table->index('role');
            $table->index('status');
        });

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT users_role_check
            CHECK (role IN ('customer', 'technician', 'operator', 'admin'))
        ");

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT users_status_check
            CHECK (status IN ('active','blocked','pending_verification'))
        ");

        DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT users_email_or_phone_check
            CHECK (email IS NOT NULL OR phone IS NOT NULL)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};