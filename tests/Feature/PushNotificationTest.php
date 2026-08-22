<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_subscribe_and_unsubscribe_the_current_browser(): void
    {
        $manager = $this->manager();
        $endpoint = 'https://push.example.test/subscriptions/device-one';

        $this->actingAs($manager)->postJson(route('workspace.push-subscriptions.store'), [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => Str::random(87), 'auth' => Str::random(22)],
            'content_encoding' => 'aes128gcm',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_type' => User::class,
            'subscribable_id' => $manager->id,
            'endpoint' => $endpoint,
        ]);

        $this->actingAs($manager)->deleteJson(route('workspace.push-subscriptions.destroy'), [
            'endpoint' => $endpoint,
        ])->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $endpoint]);
    }

    public function test_one_manager_cannot_remove_another_managers_subscription(): void
    {
        $company = $this->company();
        $first = $this->manager($company);
        $second = $this->manager($company);
        $endpoint = 'https://push.example.test/subscriptions/private-device';

        $first->updatePushSubscription($endpoint, Str::random(87), Str::random(22), 'aes128gcm');

        $this->actingAs($second)->deleteJson(route('workspace.push-subscriptions.destroy'), [
            'endpoint' => $endpoint,
        ])->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_id' => $first->id,
            'endpoint' => $endpoint,
        ]);
    }

    public function test_platform_admin_can_subscribe_and_receive_a_test_push(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'company_id' => null]);
        $endpoint = 'https://push.example.test/subscriptions/platform-device';

        $this->actingAs($admin)->postJson(route('platform.push-subscriptions.store'), [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => Str::random(87), 'auth' => Str::random(22)],
            'content_encoding' => 'aes128gcm',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->actingAs($admin)->postJson(route('platform.push-subscriptions.test'))
            ->assertOk()->assertJson(['ok' => true]);

        Notification::assertSentTo($admin, WorkspaceNotification::class, function (WorkspaceNotification $notification, array $channels) {
            return $channels === ['database', WebPushChannel::class]
                && $notification->toArray(new \stdClass)['url'] === route('platform.notifications.index');
        });
    }

    public function test_push_payload_adds_webpush_channel(): void
    {
        Notification::fake();
        $manager = $this->manager();

        $manager->notify(new WorkspaceNotification([
            'title_key' => 'workspace.push_test_title',
            'body_key' => 'workspace.push_test_body',
            'params' => [],
            'url' => 'https://luma.dev/workspace/notifications',
            'push' => true,
        ]));

        Notification::assertSentTo(
            $manager,
            WorkspaceNotification::class,
            fn ($notification, array $channels) => $channels === ['database', WebPushChannel::class],
        );
    }

    private function manager(?Company $company = null): User
    {
        return User::factory()->create([
            'company_id' => ($company ?? $this->company())->id,
            'role' => UserRole::Manager,
        ]);
    }

    private function company(): Company
    {
        return Company::create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Push Test Hotel',
            'slug' => 'push-test-'.Str::lower(Str::random(6)),
            'status' => 'active',
        ]);
    }
}
