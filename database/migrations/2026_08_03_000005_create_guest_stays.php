<?php

use App\Enums\GuestStayStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_stays', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('guest_name')->nullable();
            $table->timestamp('check_in_at');
            $table->timestamp('check_out_at');
            $table->unsignedSmallInteger('nights');
            $table->string('access_pin_hash');
            $table->string('status', 30)->default(GuestStayStatus::Upcoming->value);
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['room_id', 'check_in_at', 'check_out_at']);
        });

        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->foreignId('guest_stay_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });
        Schema::table('service_requests', function (Blueprint $table) {
            $table->foreignId('guest_stay_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });

        DB::table('guest_sessions')->orderBy('id')->each(function (object $session): void {
            $room = DB::table('rooms')->where('id', $session->room_id)->first();
            if (! $room) {
                return;
            }

            $expired = Carbon::parse($session->expires_at)->isPast() || $session->revoked_at;
            $stayId = DB::table('guest_stays')->insertGetId([
                'public_id' => (string) Str::uuid(),
                'company_id' => $session->company_id,
                'room_id' => $session->room_id,
                'guest_name' => $session->guest_name,
                'check_in_at' => $session->created_at,
                'check_out_at' => $session->expires_at,
                'nights' => max(1, Carbon::parse($session->created_at)->diffInDays(Carbon::parse($session->expires_at))),
                'access_pin_hash' => $room->pin_hash,
                'status' => $expired ? GuestStayStatus::CheckedOut->value : GuestStayStatus::CheckedIn->value,
                'checked_out_at' => $expired ? ($session->revoked_at ?: $session->expires_at) : null,
                'created_at' => $session->created_at,
                'updated_at' => $session->updated_at,
            ]);

            DB::table('guest_sessions')->where('id', $session->id)->update(['guest_stay_id' => $stayId]);
            DB::table('service_requests')->where('guest_session_id', $session->id)->update(['guest_stay_id' => $stayId]);
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_stay_id');
        });
        Schema::table('guest_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guest_stay_id');
        });
        Schema::dropIfExists('guest_stays');
    }
};
