<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_profile', function (Blueprint $table) {
            $table->uuid("id")->primary();

            $table->uuid("user_id")->unique();
            $table->string("city_node");

            $table->timestampTz("verified_at")->nullable();
            $table->uuid("verified_by_user_id")->nullable();

            $table->string("license_number")->nullable();
            $table->text("bio")->nullable();

            $table->decimal("rating_avg", 3, 2)->default(0);
            $table->integer("jobs_completed")->default(0);

            $table->timestampTz("created_at")->useCurrent();
            $table->timestampTz("updated_at")->useCurrent();

            $table->foreign("user_id")->references("id")->on("users")->cascadeOnDelete();
            $table->foreign("verified_by_user_id")->references("id")->on("users")->nullOnDelete();

            $table->index("city_code");
            $table->index("verified_at");
        });

        // City CHECK constraint (v1: tbilisi, batumi)
        DB::statement("
            ALTER TABLE technician_profiles
            ADD CONSTRAINT technician_profiles_city_code_check
            CHECK (city_code IN ('tbilisi','batumi'))
        ");
    }
    
    public function down(): void
    {
        Schema::dropIfExists('technician_profile');
    }
};
