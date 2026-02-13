<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_profiles', function (Blueprint $table) {
            $table->uuid("id")->primary();

            $table->uuid("user_id")->unique();

            $table->string("display_name")->nullable();
            $table->text("notes")->nullable();

            $table->timestampTz("created_at")->useCurrent();
            $table->timestampTz("updated_at")->useCurrent();

            $table->foreign("user_id")
                  ->references("id")
                  ->on("users")
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_profiles');
    }
};
