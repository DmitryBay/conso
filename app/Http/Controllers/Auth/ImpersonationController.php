<?php

namespace App\Http\Controllers\Auth;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        abort_if($request->session()->has('impersonator_id'), 409, 'Сначала вернитесь в аккаунт администратора.');
        abort_unless(
            $user->is_active
            && in_array($user->role, [UserRole::CompanyOwner, UserRole::Manager], true)
            && $user->company
            && $user->company->status !== CompanyStatus::Suspended,
            403,
            'Вход в этот аккаунт недоступен.'
        );

        $request->session()->put([
            'impersonator_id' => $request->user()->id,
            'impersonated_user_id' => $user->id,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('workspace.dashboard')
            ->with('success', "Вы вошли как {$user->name}.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $adminId = $request->session()->get('impersonator_id');
        abort_unless($adminId, 403, 'Режим входа в пользователя не активен.');

        $admin = User::query()
            ->whereKey($adminId)
            ->where('role', UserRole::SuperAdmin)
            ->where('is_active', true)
            ->first();

        abort_unless($admin, 403, 'Аккаунт администратора недоступен.');

        Auth::login($admin);
        $request->session()->forget(['impersonator_id', 'impersonated_user_id']);
        $request->session()->regenerate();

        return redirect()->route('platform.users.index')
            ->with('success', 'Вы вернулись в аккаунт администратора.');
    }
}
