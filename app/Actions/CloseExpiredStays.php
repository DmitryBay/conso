<?php

namespace App\Actions;

use App\Enums\GuestStayStatus;
use App\Models\GuestStay;
use Illuminate\Support\Facades\DB;

class CloseExpiredStays
{
    public function handle(): int
    {
        $count = 0;

        GuestStay::whereIn('status', [GuestStayStatus::Upcoming, GuestStayStatus::CheckedIn])
            ->where('check_out_at', '<=', now())
            ->chunkById(100, function ($stays) use (&$count): void {
                foreach ($stays as $stay) {
                    DB::transaction(function () use ($stay): void {
                        $stay->update([
                            'status' => GuestStayStatus::CheckedOut,
                            'checked_out_at' => $stay->check_out_at,
                        ]);
                        $stay->sessions()->whereNull('revoked_at')->update(['revoked_at' => now()]);
                    });
                    $count++;
                }
            });

        return $count;
    }
}
