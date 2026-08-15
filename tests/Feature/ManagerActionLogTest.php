<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\ManagerActionLog;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManagerActionLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_request_changes_are_logged_and_visible_only_to_owner(): void
    {
        $company = Company::create([
            'public_id' => (string) Str::uuid(), 'name' => 'Alpha Hotel', 'slug' => 'alpha-hotel',
            'status' => CompanyStatus::Active, 'currency' => 'IDR', 'timezone' => 'Asia/Makassar', 'plan' => 'MVP',
        ]);
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyOwner, 'is_active' => true]);
        $manager = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Manager, 'is_active' => true]);
        $serviceRequest = ServiceRequest::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'room_number' => '305',
            'title' => 'Airport transfer', 'status' => RequestStatus::New, 'priority' => 'normal',
        ]);

        $this->actingAs($manager)->patch(route('workspace.requests.status', $serviceRequest), [
            'status' => RequestStatus::InProgress->value,
            'note' => 'Driver confirmed.',
        ])->assertRedirect();

        $log = ManagerActionLog::firstOrFail();
        $this->assertSame($manager->id, $log->user_id);
        $this->assertSame($serviceRequest->id, $log->service_request_id);
        $this->assertSame('workspace.requests.status', $log->action);
        $this->assertSame(RequestStatus::InProgress->value, $log->metadata['status']);

        $this->actingAs($owner)->get(route('workspace.manager-actions.index', [
            'service_request_id' => $serviceRequest->id,
        ]))->assertOk()
            ->assertSee('Airport transfer')
            ->assertSee($manager->name)
            ->assertSee(__('workspace.action_log_actions.workspace_requests_status'));

        $this->actingAs($owner)->get(route('workspace.requests.show', $serviceRequest))
            ->assertOk()
            ->assertSee(route('workspace.manager-actions.index', ['service_request_id' => $serviceRequest->id]), false);

        $this->actingAs($manager)->get(route('workspace.manager-actions.index'))->assertForbidden();
    }

    public function test_refund_block_is_hidden_for_free_service_and_full_amount_is_exposed_for_paid_service(): void
    {
        $company = Company::create([
            'public_id' => (string) Str::uuid(), 'name' => 'Alpha Hotel', 'slug' => 'alpha-hotel',
            'status' => CompanyStatus::Active, 'currency' => 'IDR', 'timezone' => 'Asia/Makassar', 'plan' => 'MVP',
        ]);
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyOwner, 'is_active' => true]);
        $free = ServiceRequest::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'room_number' => '305',
            'title' => 'Extra towels', 'status' => RequestStatus::Ready, 'priority' => 'normal', 'price_minor' => 0,
        ]);
        $paid = ServiceRequest::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'room_number' => '305',
            'title' => 'Airport transfer', 'status' => RequestStatus::Ready, 'priority' => 'normal', 'price_minor' => 500000,
        ]);

        $this->actingAs($owner)->get(route('workspace.requests.show', $free))
            ->assertOk()->assertDontSee('data-refund-form', false);
        $this->get(route('workspace.requests.show', $paid))
            ->assertOk()->assertSee('data-service-amount="500000"', false);
        $this->patch(route('workspace.requests.refund', $paid), [
            'refund_type' => 'full',
            'refund_amount' => 1,
        ])->assertRedirect();
        $this->assertSame(500000, $paid->fresh()->refund_amount_minor);
    }
}
