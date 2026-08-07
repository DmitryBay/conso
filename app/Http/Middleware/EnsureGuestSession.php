<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\GuestSession;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestSession
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        /** @var Company $company */
        $company = $request->route('company');
        $publicId = $request->session()->get('guest_session.'.$company->id);
        $stay = $publicId ? GuestSession::with(['room', 'company', 'stay'])->where('company_id', $company->id)->where('public_id', $publicId)->first() : null;

        if (! $stay?->isValid() || $company->status === CompanyStatus::Suspended) {
            $request->session()->forget('guest_session.'.$company->id);

            if ($request->expectsJson()) {
                return response()->json([
                    'authenticated' => false,
                    'redirect' => route('guest.access', $company),
                ], 401);
            }

            return redirect()->route('guest.access', $company)->with('guest_error', __('guest.access_required'));
        }

        if (! $stay->last_seen_at || $stay->last_seen_at->lt(now()->subMinutes(5))) {
            $stay->update(['last_seen_at' => now()]);
        }

        app()->instance('guestStay', $stay);
        view()->share([
            'guestStay' => $stay,
            'guestCompany' => $company,
        ]);

        return $next($request);
    }
}
