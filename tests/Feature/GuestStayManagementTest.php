<?php

namespace Tests\Feature;

use App\Actions\CloseExpiredStays;
use App\Enums\CompanyStatus;
use App\Enums\GuestStayStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\GuestSession;
use App\Models\GuestStay;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\PushSubscription;
use Tests\TestCase;

class GuestStayManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_creates_a_two_night_stay_with_its_own_pin_and_checks_guest_out(): void
    {
        [$company, $room, $manager] = $this->hotel();
        $checkIn = now()->setTimezone($company->timezone)->subMinute()->format('Y-m-d\TH:i');

        $this->actingAs($manager)->post(route('workspace.stays.store'), [
            'room_id' => $room->id,
            'guest_name' => 'Anna Petrova',
            'check_in_at' => $checkIn,
            'nights' => 2,
            'access_pin' => '5678',
        ])->assertRedirect()->assertSessionHas('stay_pin', '5678');

        $stay = GuestStay::firstOrFail();
        $this->assertSame(2, $stay->nights);
        $this->assertSame(GuestStayStatus::CheckedIn, $stay->status);
        $this->assertSame('5678', $stay->access_pin);
        $this->assertTrue(Hash::check('5678', $stay->access_pin_hash));

        $this->get(route('workspace.stays.index'))->assertOk()->assertSee('5678');

        $this->post(route('guest.access.store', $company), [
            'room_number' => $room->number,
            'pin' => '5678',
            'country_code' => 'INT',
        ])->assertRedirect(route('guest.catalog', $company));
        $session = GuestSession::firstOrFail();
        $this->assertSame($stay->id, $session->guest_stay_id);
        $session->updatePushSubscription(
            'https://push.example.test/subscriptions/checkout-device',
            Str::random(87),
            Str::random(22),
            'aes128gcm',
        );
        $this->assertSame(1, PushSubscription::count());

        $this->actingAs($manager)->patch(route('workspace.stays.checkout', $stay))->assertRedirect();
        $this->assertSame(GuestStayStatus::CheckedOut, $stay->fresh()->status);
        $this->assertNotNull($session->fresh()->revoked_at);
        $this->assertSame(0, PushSubscription::count());
        $this->get(route('guest.catalog', $company))->assertRedirect(route('guest.access', $company));
    }

    public function test_room_stays_cannot_overlap(): void
    {
        [$company, $room, $manager] = $this->hotel();
        GuestStay::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'room_id' => $room->id,
            'guest_name' => 'First guest', 'check_in_at' => now(), 'check_out_at' => now()->addDays(2),
            'nights' => 2, 'access_pin_hash' => Hash::make('1111'), 'status' => GuestStayStatus::CheckedIn,
        ]);

        $this->actingAs($manager)->post(route('workspace.stays.store'), [
            'room_id' => $room->id, 'guest_name' => 'Second guest',
            'check_in_at' => now()->addDay()->format('Y-m-d\TH:i'), 'nights' => 2,
        ])->assertSessionHasErrors('room_id');
        $this->assertDatabaseCount('guest_stays', 1);
    }

    public function test_expired_stay_is_closed_automatically(): void
    {
        [$company, $room] = $this->hotel();
        $stay = GuestStay::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'room_id' => $room->id,
            'guest_name' => 'Expired guest', 'check_in_at' => now()->subDays(2), 'check_out_at' => now()->subMinute(),
            'nights' => 2, 'access_pin_hash' => Hash::make('2222'), 'status' => GuestStayStatus::CheckedIn,
        ]);
        $session = GuestSession::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'guest_stay_id' => $stay->id,
            'room_id' => $room->id, 'guest_name' => 'Expired guest', 'expires_at' => now()->addDay(),
        ]);

        $this->assertSame(1, app(CloseExpiredStays::class)->handle());
        $this->assertSame(GuestStayStatus::CheckedOut, $stay->fresh()->status);
        $this->assertNotNull($session->fresh()->revoked_at);
    }

    public function test_manager_can_replace_a_legacy_stay_pin_and_old_sessions_are_revoked(): void
    {
        [$company, $room, $manager] = $this->hotel();
        $stay = GuestStay::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'room_id' => $room->id,
            'guest_name' => 'Current guest', 'check_in_at' => now()->subHour(), 'check_out_at' => now()->addDay(),
            'nights' => 1, 'access_pin_hash' => Hash::make('1111'), 'status' => GuestStayStatus::CheckedIn,
        ]);
        $session = GuestSession::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'guest_stay_id' => $stay->id,
            'room_id' => $room->id, 'guest_name' => 'Current guest', 'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($manager)->patch(route('workspace.stays.pin', $stay), ['access_pin' => '9876'])
            ->assertRedirect()
            ->assertSessionHas('stay_pin', '9876');

        $stay->refresh();
        $this->assertSame('9876', $stay->access_pin);
        $this->assertTrue(Hash::check('9876', $stay->access_pin_hash));
        $this->assertNotNull($session->fresh()->revoked_at);

        $this->post(route('guest.access.store', $company), [
            'room_number' => $room->number,
            'pin' => '9876',
            'country_code' => 'ID',
        ])->assertRedirect(route('guest.catalog', $company));
    }

    private function hotel(): array
    {
        $company = Company::create([
            'public_id' => (string) Str::uuid(), 'name' => 'Alpha Hotel', 'slug' => 'alpha-hotel',
            'status' => CompanyStatus::Active, 'currency' => 'IDR', 'timezone' => 'Asia/Makassar', 'plan' => 'MVP',
        ]);
        $room = Room::create([
            'company_id' => $company->id, 'number' => '305', 'floor' => '3',
            'pin_hash' => Hash::make('legacy'), 'is_active' => true,
        ]);
        $manager = User::factory()->create([
            'company_id' => $company->id, 'role' => UserRole::Manager, 'is_active' => true,
        ]);

        return [$company, $room, $manager];
    }
}
