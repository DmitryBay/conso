<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('service_requests')
            ->where('payment_method', 'cash')
            ->where('payment_status', 'pending')
            ->update(['payment_status' => 'paid']);
    }

    public function down(): void
    {
        // Payment history is intentionally preserved when rolling back.
    }
};
