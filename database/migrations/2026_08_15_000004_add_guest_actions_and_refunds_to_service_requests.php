<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table): void {
            $table->timestamp('clarification_requested_at')->nullable()->after('archived_at');
            $table->string('refund_status', 30)->nullable()->after('clarification_requested_at');
            $table->unsignedBigInteger('refund_amount_minor')->nullable()->after('refund_status');
            $table->timestamp('refund_requested_at')->nullable()->after('refund_amount_minor');
            $table->timestamp('refunded_at')->nullable()->after('refund_requested_at');
            $table->index(['company_id', 'refund_status']);
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'refund_status']);
            $table->dropColumn([
                'clarification_requested_at', 'refund_status', 'refund_amount_minor',
                'refund_requested_at', 'refunded_at',
            ]);
        });
    }
};
