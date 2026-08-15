<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_stays', function (Blueprint $table) {
            $table->string('emergency_phone', 40)->nullable()->after('guest_email');
            $table->text('internal_notes')->nullable()->after('emergency_phone');
        });
    }

    public function down(): void
    {
        Schema::table('guest_stays', function (Blueprint $table) {
            $table->dropColumn(['emergency_phone', 'internal_notes']);
        });
    }
};
