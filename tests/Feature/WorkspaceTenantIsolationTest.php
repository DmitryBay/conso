<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Enums\ServiceNodeType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\ServiceNode;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_request_from_another_company(): void
    {
        [$companyA, $ownerA] = $this->companyWithOwner('Alpha Hotel');
        [$companyB] = $this->companyWithOwner('Beta Hotel');
        $foreignRequest = $this->request($companyB);

        $this->actingAs($ownerA)->get(route('workspace.requests.show', $foreignRequest))->assertNotFound();
    }

    public function test_request_board_exposes_ajax_modal_and_detail_fragment(): void
    {
        [$company, $owner] = $this->companyWithOwner('Alpha Hotel');
        $item = $this->request($company);

        $this->actingAs($owner)->get(route('workspace.requests.index'))
            ->assertOk()
            ->assertSee('data-request-modal-link', false)
            ->assertSee('id="requestDetailModal"', false)
            ->assertSee('data-loading-label=', false);

        $this->actingAs($owner)->get(route('workspace.requests.show', $item))
            ->assertOk()
            ->assertSee('data-request-detail', false)
            ->assertSee('Extra towels');
    }

    public function test_foreign_category_cannot_be_used_as_parent(): void
    {
        [$companyA, $ownerA] = $this->companyWithOwner('Alpha Hotel');
        [$companyB] = $this->companyWithOwner('Beta Hotel');
        $foreignCategory = ServiceNode::create(['company_id' => $companyB->id, 'type' => ServiceNodeType::Category, 'name' => 'Foreign']);

        $this->actingAs($ownerA)->post(route('workspace.services.store'), [
            'type' => 'service',
            'name' => 'Room service',
            'parent_id' => $foreignCategory->id,
            'is_active' => 1,
        ])->assertNotFound();

        $this->assertDatabaseMissing('service_nodes', ['company_id' => $companyA->id, 'name' => 'Room service']);
    }

    public function test_manager_can_configure_guest_options_and_smart_home_in_service_tree(): void
    {
        [$company, $owner] = $this->companyWithOwner('Alpha Hotel');
        $category = ServiceNode::create([
            'company_id' => $company->id,
            'type' => ServiceNodeType::Category,
            'name' => 'Room',
            'is_active' => true,
        ]);

        $this->actingAs($owner)->post(route('workspace.services.store'), [
            'type' => ServiceNodeType::Service->value,
            'name' => 'Room controls',
            'parent_id' => $category->id,
            'is_active' => 1,
            'option_keys' => ['in_room_service', 'preferred_time'],
            'smart_home_enabled' => 1,
        ])->assertRedirect();

        $service = ServiceNode::where('company_id', $company->id)->where('name', 'Room controls')->firstOrFail();
        $this->assertSame(['in_room_service', 'preferred_time'], $service->option_keys);
        $this->assertTrue($service->smart_home_enabled);

        $this->get(route('workspace.services.index'))
            ->assertOk()
            ->assertSee('Обслуживание в номере')
            ->assertSee('Умный дом — демо-панель');

        $this->post(route('workspace.services.store'), [
            'type' => ServiceNodeType::Service->value,
            'name' => 'Invalid options',
            'is_active' => 1,
            'option_keys' => ['unsupported_option'],
        ])->assertSessionHasErrors('option_keys.0');
    }

    public function test_manager_can_take_request_and_owner_receives_notification(): void
    {
        [$company, $owner] = $this->companyWithOwner('Alpha Hotel');
        $manager = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Manager]);
        $item = $this->request($company);

        $this->actingAs($manager)->patch(route('workspace.requests.take', $item))->assertRedirect();

        $item->refresh();
        $this->assertSame($manager->id, $item->assigned_to);
        $this->assertSame(RequestStatus::Accepted, $item->status);
        $this->assertDatabaseHas('service_request_status_histories', ['service_request_id' => $item->id, 'to_status' => 'accepted']);
        $this->assertSame(1, $owner->notifications()->count());
    }

    public function test_archived_request_stays_in_its_status_column_and_can_be_restored(): void
    {
        [$company, $owner] = $this->companyWithOwner('Alpha Hotel');
        $item = $this->request($company);

        $this->actingAs($owner)->patch(route('workspace.requests.archive', $item), [
            'archived' => true,
        ])->assertRedirect();

        $this->assertNotNull($item->fresh()->archived_at);
        $this->actingAs($owner)->get(route('workspace.requests.index'))
            ->assertOk()
            ->assertSee('Extra towels')
            ->assertViewHas('requests', fn ($requests) => $requests->get(RequestStatus::New->value)?->contains('id', $item->id));
        $this->actingAs($owner)->get(route('workspace.dashboard'))
            ->assertOk()
            ->assertSee('Extra towels');

        $this->actingAs($owner)->patch(route('workspace.requests.archive', $item), [
            'archived' => false,
        ])->assertRedirect();

        $this->assertNull($item->fresh()->archived_at);
        $this->assertDatabaseHas('service_request_status_histories', [
            'service_request_id' => $item->id,
            'note' => 'workspace.history_restored',
        ]);
    }

    public function test_user_cannot_archive_request_from_another_company(): void
    {
        [, $ownerA] = $this->companyWithOwner('Alpha Hotel');
        [$companyB] = $this->companyWithOwner('Beta Hotel');
        $foreignRequest = $this->request($companyB);

        $this->actingAs($ownerA)->patch(route('workspace.requests.archive', $foreignRequest), [
            'archived' => true,
        ])->assertNotFound();

        $this->assertNull($foreignRequest->fresh()->archived_at);
    }

    public function test_only_owner_can_manage_team(): void
    {
        [$company, $owner] = $this->companyWithOwner('Alpha Hotel');
        $manager = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Manager]);

        $this->actingAs($manager)->get(route('workspace.team.index'))->assertForbidden();
        $this->actingAs($owner)->post(route('workspace.team.store'), [
            'name' => 'New Manager',
            'email' => 'manager@example.test',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ])->assertRedirect();
        $this->assertDatabaseHas('users', ['company_id' => $company->id, 'email' => 'manager@example.test', 'role' => 'manager']);
    }

    private function companyWithOwner(string $name): array
    {
        $company = Company::create([
            'public_id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => CompanyStatus::Active,
            'currency' => 'IDR',
            'timezone' => 'Asia/Makassar',
            'plan' => 'MVP',
        ]);
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyOwner]);

        return [$company, $owner];
    }

    private function request(Company $company): ServiceRequest
    {
        return ServiceRequest::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'room_number' => '101',
            'title' => 'Extra towels',
            'status' => RequestStatus::New,
            'priority' => RequestPriority::Normal,
        ]);
    }
}
