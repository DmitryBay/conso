<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30);
            $table->string('floor', 30)->nullable();
            $table->string('pin_hash');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'number']);
        });

        Schema::create('guest_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('guest_name')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'expires_at']);
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('guest_session_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->string('payment_method', 30)->nullable()->after('price_minor');
            $table->string('payment_status', 30)->default('not_required')->after('payment_method');
        });

        Schema::create('service_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_node_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name_snapshot');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price_minor')->default(0);
            $table->unsignedBigInteger('total_price_minor')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_items');
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropForeign(['guest_session_id']);
            $table->dropColumn(['guest_session_id', 'payment_method', 'payment_status']);
        });
        Schema::dropIfExists('guest_sessions');
        Schema::dropIfExists('rooms');
    }
};
