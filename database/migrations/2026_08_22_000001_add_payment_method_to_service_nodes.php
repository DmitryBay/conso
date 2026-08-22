<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_nodes', function (Blueprint $table) {
            $table->string('payment_method', 30)->nullable()->after('price_minor');
        });

        DB::table('service_nodes')->where('price_minor', '>', 0)->update(['payment_method' => 'room_charge']);
    }

    public function down(): void
    {
        Schema::table('service_nodes', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
