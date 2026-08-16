<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\RequestStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_notifications_marks_every_notification_as_read(): void
    {
        $company = Company::create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Alpha Hotel',
            'slug' => 'alpha-hotel',
            'status' => CompanyStatus::Active,
            'currency' => 'IDR',
            'timezone' => 'Asia/Makassar',
            'plan' => 'MVP',
        ]);
        $owner = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::CompanyOwner,
        ]);
        $owner->notify(new WorkspaceNotification([
            'title' => 'Новая заявка',
            'body' => 'Поступила новая заявка гостя.',
        ]));

        $this->actingAs($owner)->get(route('workspace.notifications.index'))
            ->assertOk()
            ->assertSee('Новая заявка');

        $this->assertFalse($owner->unreadNotifications()->exists());
    }

    public function test_live_status_returns_request_and_notification_counters(): void
    {
        $company = Company::create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Status Hotel',
            'slug' => 'status-hotel',
            'status' => CompanyStatus::Active,
            'currency' => 'IDR',
            'timezone' => 'Asia/Makassar',
            'plan' => 'MVP',
        ]);
        $owner = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::CompanyOwner,
        ]);
        ServiceRequest::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'room_number' => '101',
            'title' => 'Новая заявка',
            'status' => RequestStatus::New,
            'priority' => 'normal',
        ]);
        $owner->notify(new WorkspaceNotification(['title' => 'Событие', 'body' => 'Описание']));

        $this->actingAs($owner)->getJson(route('workspace.live-status'))
            ->assertOk()
            ->assertJsonPath('new_requests', 1)
            ->assertJsonPath('unread_notifications', 1)
            ->assertJsonStructure(['app_version']);
    }
}
