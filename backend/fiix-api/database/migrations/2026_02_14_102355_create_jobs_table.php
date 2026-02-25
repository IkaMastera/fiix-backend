<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->uuid("id")->primary();

            //Ownership
            $table->uuid("customer_user_id");

            //Contact snapshot(for audit safety)
            $table->string("customer_phone_snapshot");
            $table->string("customer_email_snapshot")->nullable();

            //Service (current and original)
            $table->uuid("service_id");
            $table->uuid("original_service_id");

            //Content
            $table->string("title")->nullable();
            $table->text("description");

            //Location (v1 is static for now)
            $table->string("address_text");
            $table->string("city_code");

            //Priority + state machine
            $table->string("urgency");
            $table->string("status");

            //Operator review/edit metadata
            $table->timestampTz("reviewed_at")->nullable();
            $table->uuid("reviewed_by_user_id")->nullable();

            //Cancellation
            $table->timestampTz("cancelled_at")->nullable();
            $table->uuid("cancelled_by_user_id")->nullable();
            $table->text("cancel_reason")->nullable();

            $table->timestampTz("created_at")->useCurrent();
            $table->timestampTz("updated_at")->useCurrent();

            //Foreign keys
            $table->foreign("customer_user_id")
                ->references("id")
                ->on("users")
                ->restrictOnDelete();

            $table->foreign("service_id")
                ->references("id")
                ->on("services")
                ->restrictOnDelete();

            $table->foreign("original_service_id")
                ->references("id")
                ->on("services")
                ->restrictOnDelete();

            $table->foreign("reviewed_by_user_id")
                ->references("id")
                ->on("users")
                ->nullOnDelete();

            $table->foreign("cancelled_by_user_id")
                ->references("id")
                ->on("users")
                ->nullOnDelete();

            //Indexes
            $table->index("customer_user_id");
            $table->index("status");
            $table->index(["city_code", "status"]);
            $table->index(["service_id", "status"]);
            $table->index('created_at');
        });

        // CHECK: city_code (v1 list)
        DB::statement("
            ALTER TABLE jobs
            ADD CONSTRAINT jobs_city_code_check
            CHECK (city_code IN ('tbilisi','batumi'))
        ");

        // CHECK: urgency
        DB::statement("
            ALTER TABLE jobs
            ADD CONSTRAINT jobs_urgency_check
            CHECK (urgency IN ('low','normal','high','emergency'))
        ");

        // CHECK: status (v1 locked in statuses baby!)
        DB::statement("
            ALTER TABLE jobs
            ADD CONSTRAINT jobs_status_check
            CHECK (status IN (
                'submitted',
                'triaged',
                'assigned',
                'in_progress',
                'done',
                'blocked',
                'canceled',
                'disputed'
            ))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
