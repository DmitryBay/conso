<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\GuestStayStatus;
use App\Http\Controllers\Controller;
use App\Models\GuestStay;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GuestStayController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $rooms = Room::where('company_id', $companyId)->where('is_active', true)->orderBy('number')->get();
        $stays = GuestStay::where('company_id', $companyId)->with(['room', 'requests'])
            ->latest('check_in_at')->get();

        return view('workspace.stays.index', compact('rooms', 'stays'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer'],
            'guest_name' => ['required', 'string', 'max:160'],
            'check_in_at' => ['required', 'date'],
            'nights' => ['required', 'integer', 'min:1', 'max:90'],
            'access_pin' => ['nullable', 'digits_between:4,8'],
        ]);
        $company = $request->user()->company;
        $room = Room::where('company_id', $company->id)->where('is_active', true)->findOrFail($data['room_id']);
        $checkIn = Carbon::parse($data['check_in_at'], $company->timezone)->utc();
        $checkOut = $checkIn->copy()->addDays((int) $data['nights'])->setTimezone($company->timezone)->setTime(12, 0)->utc();

        $overlap = GuestStay::where('company_id', $company->id)->where('room_id', $room->id)
            ->whereNotIn('status', [GuestStayStatus::CheckedOut, GuestStayStatus::Cancelled])
            ->where('check_in_at', '<', $checkOut)
            ->where('check_out_at', '>', $checkIn)
            ->exists();
        if ($overlap) {
            return back()->withInput()->withErrors(['room_id' => __('workspace.stay_overlap')]);
        }

        $pin = $data['access_pin'] ?: (string) random_int(1000, 9999);
        GuestStay::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'room_id' => $room->id,
            'guest_name' => $data['guest_name'],
            'check_in_at' => $checkIn,
            'check_out_at' => $checkOut,
            'nights' => (int) $data['nights'],
            'access_pin_hash' => Hash::make($pin),
            'access_pin' => $pin,
            'status' => $checkIn->lte(now()) ? GuestStayStatus::CheckedIn : GuestStayStatus::Upcoming,
        ]);

        return back()->with('success', __('workspace.stay_created'))->with('stay_pin', $pin)->with('stay_room', $room->number);
    }

    public function extend(Request $request, GuestStay $guestStay): RedirectResponse
    {
        $this->ensureTenant($request, $guestStay);
        abort_unless(in_array($guestStay->status, [GuestStayStatus::Upcoming, GuestStayStatus::CheckedIn], true), 422);
        $data = $request->validate(['extra_nights' => ['required', 'integer', 'min:1', 'max:30']]);
        $extraNights = (int) $data['extra_nights'];
        $newCheckout = $guestStay->check_out_at->copy()->addDays($extraNights);

        $overlap = GuestStay::where('company_id', $guestStay->company_id)->where('room_id', $guestStay->room_id)
            ->whereKeyNot($guestStay->id)
            ->whereNotIn('status', [GuestStayStatus::CheckedOut, GuestStayStatus::Cancelled])
            ->where('check_in_at', '<', $newCheckout)
            ->where('check_out_at', '>', $guestStay->check_out_at)
            ->exists();
        if ($overlap) {
            return back()->withErrors(['extra_nights' => __('workspace.stay_overlap')]);
        }

        DB::transaction(function () use ($guestStay, $extraNights, $newCheckout): void {
            $guestStay->update(['nights' => $guestStay->nights + $extraNights, 'check_out_at' => $newCheckout]);
            $guestStay->sessions()->whereNull('revoked_at')->update(['expires_at' => $newCheckout]);
        });

        return back()->with('success', __('workspace.stay_extended'));
    }

    public function checkout(Request $request, GuestStay $guestStay): RedirectResponse
    {
        $this->ensureTenant($request, $guestStay);
        abort_unless(in_array($guestStay->status, [GuestStayStatus::Upcoming, GuestStayStatus::CheckedIn], true), 422);

        DB::transaction(function () use ($guestStay): void {
            $guestStay->update(['status' => GuestStayStatus::CheckedOut, 'checked_out_at' => now()]);
            $guestStay->revokeSessions();
        });

        return back()->with('success', __('workspace.stay_checked_out'));
    }

    public function updatePin(Request $request, GuestStay $guestStay): RedirectResponse
    {
        $this->ensureTenant($request, $guestStay);
        abort_unless(in_array($guestStay->status, [GuestStayStatus::Upcoming, GuestStayStatus::CheckedIn], true), 422);

        $data = $request->validate(['access_pin' => ['nullable', 'digits_between:4,8']]);
        $pin = $data['access_pin'] ?: (string) random_int(1000, 9999);

        DB::transaction(function () use ($guestStay, $pin): void {
            $guestStay->update([
                'access_pin_hash' => Hash::make($pin),
                'access_pin' => $pin,
            ]);
            $guestStay->revokeSessions();
        });

        return back()->with('success', __('workspace.pin_updated'))
            ->with('stay_pin', $pin)
            ->with('stay_room', $guestStay->room->number);
    }

    private function ensureTenant(Request $request, GuestStay $stay): void
    {
        abort_unless($stay->company_id === $request->user()->company_id, 404);
    }
}
