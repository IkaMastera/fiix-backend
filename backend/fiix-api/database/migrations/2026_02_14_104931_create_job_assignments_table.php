<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('job_id');
            $table->uuid('technician_user_id');

            $table->uuid('assigned_by_user_id');
            $table->timestampTz('assigned_at');

            // Technician acceptance
            $table->timestampTz('accepted_at')->nullable();
            $table->uuid('accepted_by_user_id')->nullable();

            // Assignment lifecycle
            $table->boolean('is_active')->default(true);
            $table->timestampTz('deactivated_at')->nullable();
            $table->uuid('deactivated_by_user_id')->nullable();
            $table->string('deactivation_reason')->nullable();

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            // FKs
            $table->foreign('job_id')
                ->references('id')
                ->on('jobs')
                ->cascadeOnDelete();

            $table->foreign('technician_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('assigned_by_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('accepted_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('deactivated_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Indexes for real queries
            $table->index('job_id');
            $table->index('technician_user_id');
            $table->index('is_active');
            $table->index(['technician_user_id', 'is_active']);
        });

        // Invariant: only ONE active assignment per job
        DB::statement("
            CREATE UNIQUE INDEX job_assignments_one_active_per_job
            ON job_assignments (job_id)
            WHERE is_active = true
        ");

        // Safety: if accepted_by_user_id is set, it must equal technician_user_id
        DB::statement("
            ALTER TABLE job_assignments
            ADD CONSTRAINT job_assignments_acceptor_matches_technician_check
            CHECK (accepted_by_user_id IS NULL OR accepted_by_user_id = technician_user_id)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('job_assignments');
    }
};