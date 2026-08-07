<?php

namespace Database\Seeders;

use App\Enums\GuestStayStatus;
use App\Models\Company;
use App\Models\GuestStay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoStaySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'nusa-bay-hotel')->with('rooms')->firstOrFail();
        $pins = ['101' => '1101', '118' => '1118', '204' => '1204', '221' => '1221', '305' => '1234', '412' => '1412'];

        foreach ($company->rooms as $room) {
            $checkOut = now()->addDays(30)->setTime(12, 0);
            $activeStay = GuestStay::where('company_id', $company->id)->where('room_id', $room->id)
                ->whereIn('status', [GuestStayStatus::Upcoming, GuestStayStatus::CheckedIn])
                ->where('check_out_at', '>', now())
                ->latest('check_in_at')
                ->first();

            if ($activeStay) {
                $pin = $pins[$room->number] ?? '1234';
                $activeStay->update([
                    'check_out_at' => $checkOut,
                    'nights' => 30,
                    'access_pin_hash' => Hash::make($pin),
                    'access_pin' => $pin,
                ]);
                $activeStay->sessions()->whereNull('revoked_at')->update(['expires_at' => $checkOut]);

                continue;
            }

            $pin = $pins[$room->number] ?? '1234';
            GuestStay::create([
                'public_id' => (string) Str::uuid(),
                'company_id' => $company->id,
                'room_id' => $room->id,
                'guest_name' => 'Demo Guest '.$room->number,
                'check_in_at' => now()->subHours(2),
                'check_out_at' => $checkOut,
                'nights' => 30,
                'access_pin_hash' => Hash::make($pin),
                'access_pin' => $pin,
                'status' => GuestStayStatus::CheckedIn,
            ]);
        }
    }
}
