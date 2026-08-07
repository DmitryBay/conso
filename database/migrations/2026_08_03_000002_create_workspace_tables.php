<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('service_nodes')->cascadeOnDelete();
            $table->string('type', 20)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon', 60)->default('bi-stars');
            $table->unsignedBigInteger('price_minor')->nullable();
            $table->unsignedSmallInteger('sla_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['company_id', 'parent_id', 'sort_order']);
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_node_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 20)->default('manual');
            $table->string('room_number', 30);
            $table->string('guest_name')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 30)->default('new');
            $table->string('priority', 20)->default('normal');
            $table->unsignedBigInteger('price_minor')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'created_at']);
            $table->index(['company_id', 'assigned_to', 'status']);
        });

        Schema::create('service_request_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('note')->nullable();
            $table->timestamp('created_at');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('service_request_status_histories');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('service_nodes');
    }
};
