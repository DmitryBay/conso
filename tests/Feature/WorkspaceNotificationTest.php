<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Models\Company;
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
}
