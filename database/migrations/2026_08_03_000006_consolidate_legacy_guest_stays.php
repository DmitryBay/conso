<?php

use App\Enums\GuestStayStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('guest_stays')
            ->whereIn('status', [GuestStayStatus::Upcoming->value, GuestStayStatus::CheckedIn->value])
            ->select(['company_id', 'room_id', DB::raw('COUNT(*) as stay_count')])
            ->groupBy('company_id', 'room_id')
            ->having('stay_count', '>', 1)
            ->get()
            ->each(function (object $group): void {
                $stays = DB::table('guest_stays')->where('company_id', $group->company_id)->where('room_id', $group->room_id)
                    ->whereIn('status', [GuestStayStatus::Upcoming->value, GuestStayStatus::CheckedIn->value])
                    ->orderBy('id')->get();
                $canonical = $stays->first();
                $duplicateIds = $stays->skip(1)->pluck('id');
                $checkIn = $stays->min('check_in_at');
                $checkOut = $stays->max('check_out_at');

                DB::transaction(function () use ($stays, $canonical, $duplicateIds, $checkIn, $checkOut): void {
                    DB::table('guest_sessions')->whereIn('guest_stay_id', $duplicateIds)->update(['guest_stay_id' => $canonical->id]);
                    DB::table('service_requests')->whereIn('guest_stay_id', $duplicateIds)->update(['guest_stay_id' => $canonical->id]);
                    DB::table('guest_stays')->where('id', $canonical->id)->update([
                        'guest_name' => $stays->pluck('guest_name')->filter()->last(),
                        'check_in_at' => $checkIn,
                        'check_out_at' => $checkOut,
                        'nights' => max(1, (int) ceil(Carbon::parse($checkIn)->floatDiffInDays(Carbon::parse($checkOut)))),
                        'updated_at' => now(),
                    ]);
                    DB::table('guest_stays')->whereIn('id', $duplicateIds)->delete();
                });
            });
    }

    public function down(): void {}
};
