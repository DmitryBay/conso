<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamMemberController extends Controller
{
    public function index(Request $request): View
    {
        $members = User::where('company_id', $request->user()->company_id)->orderByRaw("role = 'company_owner' desc")->orderBy('name')->get();

        return view('workspace.team.index', compact('members'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'preferred_locale' => ['sometimes', Rule::in(['ru', 'uk', 'id', 'en', 'ar', 'he', 'zh', 'ko'])],
            'email_notifications' => ['nullable', 'boolean'],
        ]);

        User::create([
            ...$data,
            'company_id' => $request->user()->company_id,
            'role' => UserRole::Manager,
            'email_verified_at' => now(),
            'is_active' => true,
            'email_notifications' => $request->has('email_notifications') ? $request->boolean('email_notifications') : true,
            'preferred_locale' => $data['preferred_locale'] ?? 'ru',
        ]);

        return back()->with('success', 'Менеджер добавлен в команду.');
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        $this->ensureManager($request, $member);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($member)],
            'phone' => ['nullable', 'string', 'max:40'],
            'preferred_locale' => ['sometimes', Rule::in(['ru', 'uk', 'id', 'en', 'ar', 'he', 'zh', 'ko'])],
            'email_notifications' => ['nullable', 'boolean'],
        ]);
        if ($request->has('email_notifications')) {
            $data['email_notifications'] = $request->boolean('email_notifications');
        }
        $member->update($data);

        return back()->with('success', 'Данные менеджера обновлены.');
    }

    public function toggle(Request $request, User $member): RedirectResponse
    {
        $this->ensureManager($request, $member);
        $member->update(['is_active' => ! $member->is_active]);

        return back()->with('success', $member->is_active ? 'Доступ менеджера включён.' : 'Доступ менеджера приостановлен.');
    }

    private function ensureManager(Request $request, User $member): void
    {
        abort_unless($member->company_id === $request->user()->company_id && $member->role === UserRole::Manager, 404);
    }
}
