<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('timezone', 80)->default('Asia/Makassar');
            $table->char('currency', 3)->default('IDR');
            $table->string('status', 30)->default('trial')->index();
            $table->string('plan', 50)->default('MVP');
            $table->unsignedSmallInteger('rooms_count')->default(0);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role', 30)->default('manager')->after('email')->index();
            $table->string('phone', 40)->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->index(['company_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id', 'role']);
            $table->dropColumn(['company_id', 'role', 'phone', 'is_active', 'last_login_at']);
        });

        Schema::dropIfExists('companies');
    }
};
