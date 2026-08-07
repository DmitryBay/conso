<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Не удалось войти. Проверьте email и пароль.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();

        $target = $request->user()->isSuperAdmin()
            ? route('platform.dashboard')
            : route('workspace.dashboard');

        return redirect()->intended($target);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function demoPlatformLogin(Request $request): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $admin = User::query()
            ->where('email', 'admin@luma.test')
            ->where('role', UserRole::SuperAdmin)
            ->where('is_active', true)
            ->firstOrFail();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::login($admin, remember: true);
        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('platform.dashboard');
    }
}
