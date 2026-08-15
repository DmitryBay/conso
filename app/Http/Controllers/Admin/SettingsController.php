<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $settings = [
            'platform_name' => PlatformSetting::read('platform_name', 'Luma Concierge'),
            'support_email' => PlatformSetting::read('support_email', config('mail.from.address')),
            'system_email' => PlatformSetting::read('system_email', config('mail.from.address')),
            'default_trial_days' => PlatformSetting::read('default_trial_days', 14),
            'default_timezone' => PlatformSetting::read('default_timezone', 'Asia/Makassar'),
            'default_currency' => PlatformSetting::read('default_currency', 'IDR'),
            'email_notifications_enabled' => PlatformSetting::enabled('email_notifications_enabled'),
            'push_notifications_enabled' => PlatformSetting::enabled('push_notifications_enabled'),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:100'],
            'support_email' => ['required', 'email', 'max:255'],
            'system_email' => ['required', 'email', 'max:255'],
            'default_trial_days' => ['required', 'integer', 'min:1', 'max:365'],
            'default_timezone' => ['required', 'timezone'],
            'default_currency' => ['required', Rule::in(['IDR', 'USD', 'EUR', 'AUD'])],
            'email_notifications_enabled' => ['nullable', 'boolean'],
            'push_notifications_enabled' => ['nullable', 'boolean'],
        ]);

        foreach (['platform_name', 'support_email', 'system_email', 'default_trial_days', 'default_timezone', 'default_currency'] as $key) {
            PlatformSetting::write($key, $data[$key]);
        }
        PlatformSetting::write('email_notifications_enabled', $request->boolean('email_notifications_enabled') ? '1' : '0');
        PlatformSetting::write('push_notifications_enabled', $request->boolean('push_notifications_enabled') ? '1' : '0');

        return back()->with('success', 'Настройки платформы сохранены.');
    }
}
