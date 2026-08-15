<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Support\SmartHomeDemo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $companies = Company::query()
            ->with('owner')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('owner', fn ($owner) => $owner->where('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.companies.index', compact('companies'));
    }

    public function create(): View
    {
        $defaults = [
            'timezone' => PlatformSetting::read('default_timezone', 'Asia/Makassar'),
            'currency' => PlatformSetting::read('default_currency', 'IDR'),
        ];

        return view('admin.companies.create', compact('defaults'));
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = DB::transaction(function () use ($request) {
            $company = Company::create([
                ...$request->safe()->except(['owner_name', 'owner_email', 'owner_phone', 'owner_password', 'owner_password_confirmation']),
                'public_id' => (string) Str::uuid(),
                'slug' => $this->uniqueSlug($request->string('name')),
                'trial_ends_at' => $request->status === 'trial' ? now()->addDays((int) PlatformSetting::read('default_trial_days', 14)) : null,
            ]);

            User::create([
                'company_id' => $company->id,
                'name' => $request->owner_name,
                'email' => $request->owner_email,
                'phone' => $request->owner_phone,
                'role' => UserRole::CompanyOwner,
                'password' => $request->owner_password,
                'email_verified_at' => now(),
            ]);

            SmartHomeDemo::install($company);

            return $company;
        });

        return redirect()->route('platform.companies.show', $company)->with('success', 'Компания и владелец успешно созданы.');
    }

    public function show(Company $company): View
    {
        $company->load('owner');

        return view('admin.companies.show', compact('company'));
    }

    public function edit(Company $company): View
    {
        $company->load('owner');

        return view('admin.companies.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        DB::transaction(function () use ($request, $company) {
            $company->update($request->safe()->except([
                'owner_name', 'owner_email', 'owner_phone', 'owner_password', 'owner_password_confirmation',
            ]));

            $ownerData = [
                'name' => $request->owner_name,
                'email' => $request->owner_email,
                'phone' => $request->owner_phone,
            ];

            if ($request->filled('owner_password')) {
                $ownerData['password'] = $request->owner_password;
            }

            $company->owner()->updateOrCreate([], [
                ...$ownerData,
                'role' => UserRole::CompanyOwner,
                'is_active' => true,
            ]);
        });

        return redirect()->route('platform.companies.show', $company)->with('success', 'Данные компании обновлены.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'hotel';
        $slug = $base;
        $suffix = 2;

        while (Company::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
