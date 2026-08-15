<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('completed_at');
            $table->index(['company_id', 'archived_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'archived_at', 'created_at']);
            $table->dropColumn('archived_at');
        });
    }
};
