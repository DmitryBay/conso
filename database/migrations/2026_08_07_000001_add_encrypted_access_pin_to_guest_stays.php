<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_stays', function (Blueprint $table) {
            $table->text('access_pin')->nullable()->after('access_pin_hash');
        });
    }

    public function down(): void
    {
        Schema::table('guest_stays', function (Blueprint $table) {
            $table->dropColumn('access_pin');
        });
    }
};
