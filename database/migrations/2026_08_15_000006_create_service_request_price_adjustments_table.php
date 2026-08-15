<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_request_price_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('service_request_id');
            $table->unsignedBigInteger('service_request_item_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('service_name_snapshot');
            $table->unsignedBigInteger('previous_price_minor');
            $table->unsignedBigInteger('new_price_minor');
            $table->text('comment');
            $table->timestamp('created_at');

            $table->foreign('service_request_id', 'price_adj_request_fk')->references('id')->on('service_requests')->cascadeOnDelete();
            $table->foreign('service_request_item_id', 'price_adj_item_fk')->references('id')->on('service_request_items')->nullOnDelete();
            $table->foreign('user_id', 'price_adj_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['service_request_id', 'created_at'], 'price_adj_request_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_price_adjustments');
    }
};
