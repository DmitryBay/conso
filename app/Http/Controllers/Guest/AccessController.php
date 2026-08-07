<?php

namespace App\Http\Controllers\Guest;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestSession;
use App\Models\GuestStay;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccessController extends Controller
{
    private const COUNTRY_LOCALES = [
        'INT' => 'en',
        'ID' => 'id',
        'RU' => 'ru',
        'UA' => 'uk',
        'AE' => 'ar',
        'IL' => 'he',
        'CN' => 'zh',
        'KR' => 'ko',
    ];

    public function show(Request $request, Company $company): View|RedirectResponse
    {
        $existing = $this->currentStay($request, $company);
        if ($existing?->isValid()) {
            return redirect()->route('guest.catalog', $company);
        }

        return view('guest.access', compact('company'));
    }

    public function manifest(Company $company): JsonResponse
    {
        $startUrl = route('guest.access', $company, absolute: false);

        return response()->json([
            'id' => $startUrl,
            'name' => $company->name.' Concierge',
            'short_name' => $company->name,
            'description' => 'Private in-room concierge for '.$company->name,
            'start_url' => $startUrl,
            'scope' => '/guest/'.$company->slug,
            'display' => 'fullscreen',
            'display_override' => ['fullscreen', 'standalone'],
            'orientation' => 'any',
            'background_color' => '#f6f3ed',
            'theme_color' => '#183c36',
            'icons' => [
                ['src' => asset('app-icons/luma-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('app-icons/luma-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    public function status(Company $company): JsonResponse
    {
        /** @var GuestSession $guestSession */
        $guestSession = app('guestStay');

        return response()->json([
            'authenticated' => true,
            'expires_at' => $guestSession->expires_at?->toIso8601String(),
        ]);
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        abort_if($company->status === CompanyStatus::Suspended, 403, 'Сервис отеля временно недоступен.');
        $data = $request->validate([
            'room_number' => ['required', 'string', 'max:30'],
            'pin' => ['required', 'string', 'min:4', 'max:10'],
            'guest_name' => ['nullable', 'string', 'max:160'],
            'country_code' => ['required', 'string', Rule::in(array_keys(self::COUNTRY_LOCALES))],
        ]);
        $key = 'guest-access:'.$company->id.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withInput($request->only('room_number', 'guest_name', 'country_code'))->with('guest_error', __('guest.too_many_attempts'));
        }

        $room = Room::where('company_id', $company->id)->where('number', trim($data['room_number']))->where('is_active', true)->first();
        $stay = $room ? GuestStay::where('company_id', $company->id)
            ->where('room_id', $room->id)
            ->whereIn('status', [\App\Enums\GuestStayStatus::Upcoming, \App\Enums\GuestStayStatus::CheckedIn])
            ->where('check_in_at', '<=', now())
            ->where('check_out_at', '>', now())
            ->latest('check_in_at')
            ->first() : null;

        if (! $room || ! $stay || ! Hash::check($data['pin'], $stay->access_pin_hash)) {
            RateLimiter::hit($key, 60);

            return back()->withInput($request->only('room_number', 'guest_name', 'country_code'))->with('guest_error', __('guest.invalid_stay_access'));
        }

        RateLimiter::clear($key);
        if ($stay->status === \App\Enums\GuestStayStatus::Upcoming) {
            $stay->update(['status' => \App\Enums\GuestStayStatus::CheckedIn]);
        }
        if (! $stay->guest_name && $data['guest_name']) {
            $stay->update(['guest_name' => $data['guest_name']]);
        }

        $locale = self::COUNTRY_LOCALES[$data['country_code']];
        $request->session()->put('guest_locale', $locale);
        $guestSession = GuestSession::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'guest_stay_id' => $stay->id,
            'room_id' => $room->id,
            'guest_name' => $stay->guest_name ?: ($data['guest_name'] ?: null),
            'locale' => $locale,
            'country_code' => $data['country_code'],
            'expires_at' => $stay->check_out_at,
            'last_seen_at' => now(),
        ]);
        $request->session()->put('guest_session.'.$company->id, $guestSession->public_id);
        $request->session()->regenerate();

        return redirect()->route('guest.catalog', $company);
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        $stay = $this->currentStay($request, $company);
        $stay?->update(['revoked_at' => now()]);
        $request->session()->forget('guest_session.'.$company->id);

        return redirect()->route('guest.access', $company);
    }

    private function currentStay(Request $request, Company $company): ?GuestSession
    {
        $publicId = $request->session()->get('guest_session.'.$company->id);

        return $publicId ? GuestSession::with(['room', 'stay'])->where('company_id', $company->id)->where('public_id', $publicId)->first() : null;
    }
}
