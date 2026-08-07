<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_manager_receives_database_and_email_channels(): void
    {
        Notification::fake();
        $manager = User::factory()->create([
            'company_id' => $this->company()->id,
            'role' => UserRole::Manager,
            'email_notifications' => true,
        ]);

        $manager->notify($this->notification());

        Notification::assertSentTo(
            $manager,
            WorkspaceNotification::class,
            fn ($notification, array $channels) => $channels === ['database', 'mail'],
        );
    }

    public function test_disabled_manager_keeps_only_in_app_notification(): void
    {
        Notification::fake();
        $manager = User::factory()->create([
            'company_id' => $this->company()->id,
            'role' => UserRole::Manager,
            'email_notifications' => false,
        ]);

        $manager->notify($this->notification());

        Notification::assertSentTo(
            $manager,
            WorkspaceNotification::class,
            fn ($notification, array $channels) => $channels === ['database'],
        );
    }

    public function test_email_uses_the_managers_preferred_language(): void
    {
        $manager = User::factory()->create([
            'company_id' => $this->company()->id,
            'role' => UserRole::Manager,
            'preferred_locale' => 'id',
            'email_notifications' => true,
        ]);

        $mail = $this->notification()->toMail($manager);

        $this->assertSame('Pesanan baru dari kamar 305 · '.$manager->company->name, $mail->subject);
        $this->assertSame('Halo, '.$manager->name.'!', $mail->greeting);
        $this->assertSame('Buka permintaan', $mail->actionText);
    }

    private function notification(): WorkspaceNotification
    {
        return new WorkspaceNotification([
            'title_key' => 'workspace.notification_guest_order',
            'body_key' => 'workspace.notification_room_request',
            'params' => ['room' => '305', 'request' => 'Breakfast'],
            'url' => 'https://luma.dev/workspace/requests/1',
            'email' => true,
        ]);
    }

    private function company(): Company
    {
        return Company::create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Nusa Bay Hotel',
            'slug' => 'nusa-bay-'.Str::lower(Str::random(6)),
            'status' => 'active',
        ]);
    }
}
