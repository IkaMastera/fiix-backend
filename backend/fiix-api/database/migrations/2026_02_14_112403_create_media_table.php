<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();

            //Polymorphic owner (job/services/technician_profile/dispute)
            $table->string('owner_type');
            $table->uuid('owner_id');

            // Object storage reference (R2/S3 lets do this later dont forget)
            $table->string("file_key");
            $table->string("file_url")->nullable();

            $table->string("mime_type")->nullable();
            $table->integer("file_size")->nullable();

            $table->boolean("is_primary")->default(false);
            $table->integer("sort_order")->default(0);

            $table->timestampTz("created_at")->useCurrent();
            $table->timestampTz("updated_at")->useCurrent();

            $table->index(['owner_type', 'owner_id']);
        });

        // Restrict owner types to a controlled list
        DB::statement("
            ALTER TABLE media
            ADD CONSTRAINT media_owner_type_check
            CHECK (owner_type IN ('service','job','technician_profile','dispute'))
        ");

        // Only one primary image for services (useful for service cards)
        DB::statement("
            CREATE UNIQUE INDEX media_one_primary_service
            ON media (owner_type, owner_id)
            WHERE owner_type = 'service' AND is_primary = true
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
