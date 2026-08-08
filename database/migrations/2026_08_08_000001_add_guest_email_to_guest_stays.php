<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_stays', function (Blueprint $table): void {
            $table->string('guest_email')->nullable()->after('guest_name');
        });
    }

    public function down(): void
    {
        Schema::table('guest_stays', function (Blueprint $table): void {
            $table->dropColumn('guest_email');
        });
    }
};
