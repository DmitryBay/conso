<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_company_and_owner_atomically(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);

        $response = $this->actingAs($admin)->post('/platform/companies', [
            'name' => 'Bali Garden Hotel',
            'legal_name' => 'PT Bali Garden',
            'email' => 'hotel@example.test',
            'phone' => '+62 111 222',
            'timezone' => 'Asia/Makassar',
            'currency' => 'IDR',
            'status' => 'trial',
            'plan' => 'MVP',
            'rooms_count' => 32,
            'owner_name' => 'Nadia Putri',
            'owner_email' => 'nadia@example.test',
            'owner_phone' => '+62 333 444',
            'owner_password' => 'Secret123!',
            'owner_password_confirmation' => 'Secret123!',
        ]);

        $company = Company::where('slug', 'bali-garden-hotel')->firstOrFail();
        $response->assertRedirect(route('platform.companies.show', $company));
        $this->assertDatabaseHas('users', [
            'company_id' => $company->id,
            'email' => 'nadia@example.test',
            'role' => UserRole::CompanyOwner->value,
        ]);
    }

    public function test_non_admin_cannot_create_company(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);

        $this->actingAs($manager)->post('/platform/companies', [])->assertForbidden();
        $this->assertDatabaseCount('companies', 0);
    }
}
