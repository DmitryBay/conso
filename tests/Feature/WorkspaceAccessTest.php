<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_and_manager_can_open_only_company_workspace(): void
    {
        $company = $this->company('Alpha Hotel');
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyOwner]);
        $manager = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Manager]);

        $this->actingAs($owner)->get('/workspace')->assertOk()->assertSee('Alpha Hotel');
        $this->actingAs($manager)->get('/workspace')->assertOk()->assertSee('Alpha Hotel');
    }

    public function test_super_admin_and_suspended_company_cannot_open_workspace(): void
    {
        $admin = User::factory()->create(['company_id' => null, 'role' => UserRole::SuperAdmin]);
        $company = $this->company('Suspended Hotel', CompanyStatus::Suspended);
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyOwner]);

        $this->actingAs($admin)->get('/workspace')->assertForbidden();
        $this->actingAs($owner)->get('/workspace')->assertForbidden();
    }

    public function test_login_redirects_owner_to_workspace(): void
    {
        $company = $this->company('Alpha Hotel');
        User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::CompanyOwner,
            'email' => 'owner@example.test',
            'password' => 'Secret123!',
        ]);

        $this->post('/login', ['email' => 'owner@example.test', 'password' => 'Secret123!'])
            ->assertRedirect(route('workspace.dashboard'));
    }

    public function test_root_redirects_authenticated_users_to_their_own_panel(): void
    {
        $company = $this->company('Alpha Hotel');
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyOwner]);
        $admin = User::factory()->create(['company_id' => null, 'role' => UserRole::SuperAdmin]);

        $this->actingAs($owner)->get('/')->assertRedirect(route('workspace.dashboard'));
        $this->actingAs($admin)->get('/')->assertRedirect(route('platform.dashboard'));
    }

    public function test_manager_can_switch_workspace_language_and_hebrew_is_rtl(): void
    {
        $company = $this->company('Alpha Hotel');
        $manager = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Manager]);

        $this->actingAs($manager)->get('/workspace/requests?lang=he')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('בקשות אורחים');
        $this->assertSame('he', session('workspace_locale'));

        $this->get('/workspace?lang=uk')
            ->assertOk()
            ->assertSee('Операційний центр');
        $this->assertSame('uk', session('workspace_locale'));
    }

    private function company(string $name, CompanyStatus $status = CompanyStatus::Active): Company
    {
        return Company::create([
            'public_id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => $status,
            'currency' => 'IDR',
            'timezone' => 'Asia/Makassar',
            'plan' => 'MVP',
        ]);
    }
}
