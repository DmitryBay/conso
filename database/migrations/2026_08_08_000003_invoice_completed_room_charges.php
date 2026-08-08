<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_requests')
            ->where('payment_method', 'room_charge')
            ->where('status', 'completed')
            ->where('payment_status', 'pending')
            ->update(['payment_status' => 'invoiced']);
    }

    public function down(): void
    {
        DB::table('service_requests')
            ->where('payment_method', 'room_charge')
            ->where('payment_status', 'invoiced')
            ->update(['payment_status' => 'pending']);
    }
};
