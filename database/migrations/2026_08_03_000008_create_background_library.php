<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('background_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_system')->default(false);
            $table->unsignedTinyInteger('blur_px')->default(3);
            $table->unsignedTinyInteger('overlay_percent')->default(52);
            $table->timestamps();
            $table->unique(['company_id', 'slug']);
        });

        Schema::create('background_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('background_set_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('background_position', 40)->default('center');
            $table->string('background_size', 40)->default('cover');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('background_set_id')->nullable()->constrained('background_sets')->nullOnDelete();
        });

        Schema::table('service_nodes', function (Blueprint $table) {
            $table->foreignId('background_image_id')->nullable()->constrained('background_images')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('background_image_id');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('background_set_id');
        });
        Schema::dropIfExists('background_images');
        Schema::dropIfExists('background_sets');
    }
};
