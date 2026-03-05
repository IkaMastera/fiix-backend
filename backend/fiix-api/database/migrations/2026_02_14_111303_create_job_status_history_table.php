<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_status_history', function (Blueprint $table) {
            $table->uuid("id")->primary();

            $table->uuid("job_id");

            $table->string("from_status");
            $table->string("to_status");

            $table->timestampTz("changed_at");
            $table->uuid("changed_by_user_id")->nullable();

            $table->string("reason_code")->nullable();
            $table->text("reason_note")->nullable();

            // For append only
            $table->timestampTz("created_at")->useCurrent();

            $table->foreign("job_id")
                ->references("id")
                ->on("jobs")
                ->cascadeOnDelete();

            $table->foreign("changed_by_user_id")
                ->references("id")
                ->on("users")
                ->nullOnDelete();

            //Indexes
            $table->index("job_id");
            $table->index(["job_id", "changed_at"]);
        });

        // Optional: basic CHECK to prevent empty strings (light safety)
        DB::statement("
            ALTER TABLE job_status_history
            ADD CONSTRAINT job_status_history_from_to_not_empty_check
            CHECK (from_status <> '' AND to_status <> '')
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('job_status_history');
    }
};
