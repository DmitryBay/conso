<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SystemUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_find_and_edit_a_system_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);
        $company = $this->company('Alpha Hotel');
        $manager = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Manager, 'email' => 'manager@example.test']);

        $this->actingAs($admin)->get(route('platform.users.index', ['search' => 'manager@example.test']))
            ->assertOk()
            ->assertSee('manager@example.test');

        $this->put(route('platform.users.update', $manager), [
            'name' => 'Updated Manager',
            'email' => 'updated@example.test',
            'phone' => '+62 800 100',
            'role' => UserRole::Manager->value,
            'company_id' => $company->id,
            'is_active' => 0,
            'password' => 'NewSecret123!',
            'password_confirmation' => 'NewSecret123!',
        ])->assertRedirect(route('platform.users.index'));

        $manager->refresh();
        $this->assertSame('Updated Manager', $manager->name);
        $this->assertSame('updated@example.test', $manager->email);
        $this->assertFalse($manager->is_active);
        $this->assertTrue(Hash::check('NewSecret123!', $manager->password));
    }

    public function test_admin_cannot_lock_themselves_out(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);

        $this->actingAs($admin)->put(route('platform.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => UserRole::Manager->value,
            'company_id' => $this->company('Alpha Hotel')->id,
            'is_active' => 0,
        ])->assertSessionHasErrors('role');

        $this->assertSame(UserRole::SuperAdmin, $admin->refresh()->role);
        $this->assertTrue($admin->is_active);
    }

    public function test_company_user_cannot_open_platform_user_management(): void
    {
        $company = $this->company('Alpha Hotel');
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyOwner]);

        $this->actingAs($owner)->get(route('platform.users.index'))->assertForbidden();
    }

    public function test_admin_can_enter_a_company_user_account_and_return(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);
        $company = $this->company('Alpha Hotel');
        $owner = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::CompanyOwner,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('platform.users.impersonate', $owner))
            ->assertRedirect(route('workspace.dashboard'))
            ->assertSessionHas('impersonator_id', $admin->id);

        $this->assertAuthenticatedAs($owner);

        $this->post(route('impersonation.stop'))
            ->assertRedirect(route('platform.users.index'))
            ->assertSessionMissing('impersonator_id');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_cannot_enter_an_inactive_or_admin_account(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);
        $company = $this->company('Alpha Hotel');
        $inactiveManager = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Manager,
            'is_active' => false,
        ]);
        $otherAdmin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);

        $this->actingAs($admin)->post(route('platform.users.impersonate', $inactiveManager))->assertForbidden();
        $this->actingAs($admin)->post(route('platform.users.impersonate', $otherAdmin))->assertForbidden();
    }

    public function test_company_user_cannot_start_impersonation(): void
    {
        $company = $this->company('Alpha Hotel');
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyOwner]);
        $manager = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Manager]);

        $this->actingAs($owner)->post(route('platform.users.impersonate', $manager))->assertForbidden();
    }

    private function company(string $name): Company
    {
        return Company::create([
            'public_id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => CompanyStatus::Active,
            'currency' => 'IDR',
            'timezone' => 'Asia/Makassar',
            'plan' => 'MVP',
        ]);
    }
}
