<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_open_and_read_a_notification(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);
        $admin->notify(new WorkspaceNotification([
            'title' => 'Новая компания',
            'body' => 'На платформе зарегистрирована новая компания.',
            'url' => route('platform.dashboard'),
            'icon' => 'bi-buildings',
        ]));

        $notification = $admin->notifications()->firstOrFail();

        $this->actingAs($admin)->get(route('platform.notifications.index'))
            ->assertOk()
            ->assertSee('Новая компания')
            ->assertSee('На платформе зарегистрирована новая компания.');

        $this->assertFalse($admin->unreadNotifications()->exists());

        $this->actingAs($admin)->get(route('platform.notifications.read', $notification->id))
            ->assertRedirect(route('platform.dashboard'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_platform_admin_can_mark_all_notifications_as_read(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);
        $admin->notify(new WorkspaceNotification(['title' => 'Событие', 'body' => 'Описание']));

        $this->actingAs($admin)->patch(route('platform.notifications.read-all'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($admin->unreadNotifications()->exists());
    }

    public function test_platform_live_status_returns_the_current_notification_counter(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);
        $admin->notify(new WorkspaceNotification(['title' => 'Событие', 'body' => 'Описание']));

        $this->actingAs($admin)->getJson(route('platform.live-status'))
            ->assertOk()
            ->assertJsonPath('unread_notifications', 1)
            ->assertJsonStructure(['app_version']);
    }

    public function test_company_user_cannot_open_platform_notifications(): void
    {
        $owner = User::factory()->create(['role' => UserRole::CompanyOwner]);

        $this->actingAs($owner)->get(route('platform.notifications.index'))->assertForbidden();
    }
}
