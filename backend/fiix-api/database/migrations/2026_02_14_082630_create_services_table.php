<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->uuid("id")->primary();
            
            $table->uuid("category_id");
            
            $table->string("name");
            $table->string("slug")->unique();
            $table->text("description")->nullable();
            
            $table->boolean("is_active")->default(true);
            $table->integer("sort_order")->default(0);

            $table->timestampTz("created_at")->useCurrent();
            $table->timestampTz("updated_at")->useCurrent();
            $table->softDeletesTz();

            $table->foreign("category_id")
                ->references("id")
                ->on("service_categories")
                ->restrictOnDelete();

            $table->index("category_id");
            $table->index("is_active");
            $table->index("sort_order");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
