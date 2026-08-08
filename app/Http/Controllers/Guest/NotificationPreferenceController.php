<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationPreferenceController extends Controller
{
    public function storePush(Request $request, Company $company): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['nullable', Rule::in(['aes128gcm', 'aesgcm'])],
        ]);

        $this->guestSession()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['content_encoding'] ?? 'aes128gcm',
        );

        return response()->json(['ok' => true]);
    }

    public function destroyPush(Request $request, Company $company): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'url', 'max:500']]);
        $this->guestSession()->deletePushSubscription($data['endpoint']);

        return response()->json(['ok' => true]);
    }

    public function updateEmail(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate(['guest_email' => ['nullable', 'email:rfc', 'max:255']]);
        $guestSession = $this->guestSession();
        $guestSession->stay->update(['guest_email' => $data['guest_email'] ?: null]);

        return back()->with('guest_success', __('guest.notification_email_saved'));
    }

    private function guestSession(): GuestSession
    {
        /** @var GuestSession $guestSession */
        $guestSession = app('guestStay');

        return $guestSession;
    }
}
