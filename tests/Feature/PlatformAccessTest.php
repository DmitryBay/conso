<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/platform')->assertRedirect('/login');
    }

    public function test_super_admin_can_open_platform(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);

        $this->actingAs($admin)->get('/platform')->assertOk()->assertSee('Обзор платформы');
    }

    public function test_company_owner_cannot_open_platform(): void
    {
        $owner = User::factory()->create(['role' => UserRole::CompanyOwner]);

        $this->actingAs($owner)->get('/platform')->assertForbidden();
    }

    public function test_local_demo_login_switches_an_owner_to_platform_admin(): void
    {
        $owner = User::factory()->create(['role' => UserRole::CompanyOwner]);
        $admin = User::factory()->create([
            'email' => 'admin@luma.test',
            'role' => UserRole::SuperAdmin,
            'company_id' => null,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('demo.platform-login'))
            ->assertRedirect(route('platform.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }
}
