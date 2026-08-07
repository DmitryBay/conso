<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('company')
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('company', fn ($company) => $company->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->string('role')->toString(), fn ($query, string $role) => $query->where('role', $role))
            ->when($request->integer('company_id'), fn ($query, int $companyId) => $query->where('company_id', $companyId))
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->orderByRaw("role = 'super_admin' desc")
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $companies = Company::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', compact('users', 'companies'));
    }

    public function edit(User $user): View
    {
        $user->load('company');
        $companies = Company::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.users.edit', compact('user', 'companies'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['required', Rule::in([UserRole::SuperAdmin->value, UserRole::CompanyOwner->value, UserRole::Manager->value])],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'is_active' => ['nullable', 'boolean'],
            'email_notifications' => ['nullable', 'boolean'],
            'preferred_locale' => ['sometimes', Rule::in(['ru', 'uk', 'id', 'en', 'ar', 'he', 'zh', 'ko'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]) + ['is_active' => $request->boolean('is_active')];

        $role = UserRole::from($data['role']);
        $companyId = $role === UserRole::SuperAdmin ? null : ($data['company_id'] ?? null);

        if ($role !== UserRole::SuperAdmin && ! $companyId) {
            throw ValidationException::withMessages(['company_id' => 'Для владельца или менеджера выберите компанию.']);
        }
        if ($request->user()->is($user) && ($role !== UserRole::SuperAdmin || ! $data['is_active'])) {
            throw ValidationException::withMessages(['role' => 'Нельзя отключить собственный аккаунт администратора или изменить его роль.']);
        }
        if ($role === UserRole::CompanyOwner && User::query()->where('company_id', $companyId)->where('role', UserRole::CompanyOwner)->whereKeyNot($user->id)->exists()) {
            throw ValidationException::withMessages(['company_id' => 'У выбранной компании уже есть владелец.']);
        }
        if ($user->role === UserRole::CompanyOwner && ($role !== UserRole::CompanyOwner || $companyId !== $user->company_id)) {
            $hasReplacement = User::query()->where('company_id', $user->company_id)->where('role', UserRole::CompanyOwner)->whereKeyNot($user->id)->exists();
            if (! $hasReplacement) {
                throw ValidationException::withMessages(['role' => 'Сначала назначьте компании другого владельца.']);
            }
        }

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $role,
            'company_id' => $companyId,
            'is_active' => $data['is_active'],
            'email_notifications' => $request->has('email_notifications') ? $request->boolean('email_notifications') : $user->email_notifications,
            'preferred_locale' => $data['preferred_locale'] ?? $user->preferred_locale,
        ];
        if ($request->filled('password')) {
            $update['password'] = $data['password'];
        }
        $user->update($update);

        return redirect()->route('platform.users.index')->with('success', 'Данные пользователя обновлены.');
    }
}
