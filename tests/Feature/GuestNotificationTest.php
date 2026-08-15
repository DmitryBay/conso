<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\GuestStayStatus;
use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Enums\ServiceNodeType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\GuestSession;
use App\Models\GuestStay;
use App\Models\Room;
use App\Models\ServiceNode;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\GuestRequestStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

class GuestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_email_is_saved_for_only_the_current_stay(): void
    {
        [$company, $room, $stay] = $this->hotel();

        $this->post(route('guest.access.store', $company), [
            'room_number' => $room->number,
            'pin' => '1234',
            'guest_name' => 'Anna',
            'guest_email' => 'anna@example.com',
            'country_code' => 'INT',
        ])->assertRedirect(route('guest.catalog', $company));

        $this->assertSame('anna@example.com', $stay->fresh()->guest_email);
        $this->get(route('guest.catalog', $company))
            ->assertOk()
            ->assertSee('id="guestNotificationModal"', false)
            ->assertSee(route('guest.push-subscriptions.store', $company), false);
    }

    public function test_guest_can_subscribe_the_current_session_for_push(): void
    {
        [$company, $room, $stay] = $this->hotel();
        $session = $this->guestSession($company, $room, $stay);
        $endpoint = 'https://push.example.test/subscriptions/guest-device';

        $this->withSession(['guest_session.'.$company->id => $session->public_id])
            ->postJson(route('guest.push-subscriptions.store', $company), [
                'endpoint' => $endpoint,
                'keys' => ['p256dh' => Str::random(87), 'auth' => Str::random(22)],
                'content_encoding' => 'aes128gcm',
            ])->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_type' => GuestSession::class,
            'subscribable_id' => $session->id,
            'endpoint' => $endpoint,
        ]);

        $this->withSession(['guest_session.'.$company->id => $session->public_id])
            ->post(route('guest.logout', $company))
            ->assertRedirect(route('guest.access', $company));
        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => $endpoint]);
    }

    public function test_shared_tablet_subscription_moves_to_the_new_guest_session(): void
    {
        [$company, $room, $stay] = $this->hotel();
        $previousSession = $this->guestSession($company, $room, $stay);
        $newSession = $this->guestSession($company, $room, $stay);
        $endpoint = 'https://push.example.test/subscriptions/shared-tablet';
        $keys = ['p256dh' => Str::random(87), 'auth' => Str::random(22)];

        $previousSession->updatePushSubscription($endpoint, $keys['p256dh'], $keys['auth'], 'aes128gcm');

        $this->withSession(['guest_session.'.$company->id => $newSession->public_id])
            ->postJson(route('guest.push-subscriptions.store', $company), [
                'endpoint' => $endpoint,
                'keys' => $keys,
                'content_encoding' => 'aes128gcm',
            ])->assertOk();

        $this->assertDatabaseMissing('push_subscriptions', [
            'subscribable_type' => GuestSession::class,
            'subscribable_id' => $previousSession->id,
            'endpoint' => $endpoint,
        ]);
        $this->assertDatabaseHas('push_subscriptions', [
            'subscribable_type' => GuestSession::class,
            'subscribable_id' => $newSession->id,
            'endpoint' => $endpoint,
        ]);
    }

    public function test_status_change_notifies_the_guest_by_push_and_email(): void
    {
        Notification::fake();
        [$company, $room, $stay, $manager] = $this->hotel();
        $stay->update(['guest_email' => 'anna@example.com']);
        $session = $this->guestSession($company, $room, $stay, 'id');
        $service = ServiceNode::create([
            'company_id' => $company->id,
            'type' => ServiceNodeType::Service,
            'name' => 'Breakfast',
            'translations' => ['id' => ['name' => 'Sarapan', 'description' => '']],
            'is_active' => true,
        ]);
        $serviceRequest = ServiceRequest::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'guest_stay_id' => $stay->id,
            'guest_session_id' => $session->id,
            'service_node_id' => $service->id,
            'source' => 'guest',
            'room_number' => $room->number,
            'guest_name' => 'Anna',
            'title' => 'Breakfast',
            'status' => RequestStatus::New,
            'priority' => RequestPriority::Normal,
        ]);

        $this->actingAs($manager)->patch(route('workspace.requests.status', $serviceRequest), [
            'status' => RequestStatus::InProgress->value,
        ])->assertRedirect();

        Notification::assertSentTo(
            $session,
            GuestRequestStatusNotification::class,
            fn ($notification, array $channels) => $channels === [WebPushChannel::class, 'mail'],
        );

        $mail = (new GuestRequestStatusNotification($serviceRequest->fresh()->load(['company', 'service']), true))->toMail($session->fresh()->load('stay'));
        $this->assertSame('Permintaan: Sedang diproses · '.$company->name, $mail->subject);
        $this->assertSame('Buka permintaan', $mail->actionText);

        foreach ([
            RequestStatus::WaitingGuest->value => 'Menunggu respons tamu',
            RequestStatus::Ready->value => 'Siap — konfirmasi penerimaan',
            RequestStatus::Completed->value => 'Selesai',
        ] as $status => $label) {
            $serviceRequest->update(['status' => $status]);
            $push = (new GuestRequestStatusNotification(
                $serviceRequest->fresh()->load(['company', 'service', 'guestStay.room']),
            ))->toWebPush($session)->toArray();

            $this->assertSame('Permintaan: '.$label, $push['title']);
            $this->assertStringContainsString($label, $push['body']);
        }
    }

    private function hotel(): array
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
        $room = Room::create([
            'company_id' => $company->id,
            'number' => '305',
            'floor' => '3',
            'pin_hash' => Hash::make('legacy'),
            'is_active' => true,
        ]);
        $stay = GuestStay::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'room_id' => $room->id,
            'guest_name' => 'Anna',
            'check_in_at' => now()->subMinute(),
            'check_out_at' => now()->addDays(2),
            'nights' => 2,
            'access_pin_hash' => Hash::make('1234'),
            'status' => GuestStayStatus::CheckedIn,
        ]);
        $manager = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        return [$company, $room, $stay, $manager];
    }

    private function guestSession(Company $company, Room $room, GuestStay $stay, string $locale = 'en'): GuestSession
    {
        return GuestSession::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'guest_stay_id' => $stay->id,
            'room_id' => $room->id,
            'guest_name' => $stay->guest_name,
            'locale' => $locale,
            'country_code' => 'INT',
            'expires_at' => $stay->check_out_at,
            'last_seen_at' => now(),
        ]);
    }
}
