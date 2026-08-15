<?php

namespace Tests\Feature;

use App\Actions\CloseExpiredStays;
use App\Enums\CompanyStatus;
use App\Enums\GuestStayStatus;
use App\Enums\UserRole;
use App\Mail\FinalBillMail;
use App\Models\Company;
use App\Models\GuestSession;
use App\Models\GuestStay;
use App\Models\Room;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

    public function test_owner_and_manager_can_view_the_three_month_occupancy_calendar(): void
    {
        [$company, $room, $manager] = $this->hotel();
        $owner = User::factory()->create([
            'company_id' => $company->id, 'role' => UserRole::CompanyOwner, 'is_active' => true,
        ]);
        GuestStay::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'room_id' => $room->id,
            'guest_name' => 'Calendar guest', 'check_in_at' => now()->addDay(), 'check_out_at' => now()->addDays(3),
            'nights' => 2, 'access_pin_hash' => Hash::make('1234'), 'status' => GuestStayStatus::Upcoming,
        ]);

        foreach ([$owner, $manager] as $user) {
            $this->actingAs($user)->get(route('workspace.stays.index', [
                'calendar_month' => now($company->timezone)->format('Y-m'),
                'room_id' => $room->id,
            ]))->assertOk()
                ->assertSee(__('workspace.occupancy_calendar'))
                ->assertSee('Calendar guest');
        }
    }

    public function test_availability_search_returns_only_rooms_without_overlapping_active_stays(): void
    {
        [$company, $room, $manager] = $this->hotel();
        Room::create([
            'company_id' => $company->id, 'number' => '306', 'name' => 'Garden Villa',
            'pin_hash' => Hash::make('legacy'), 'is_active' => true,
        ]);
        $from = now($company->timezone)->addDay()->startOfDay();
        GuestStay::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'room_id' => $room->id,
            'guest_name' => 'Busy guest', 'check_in_at' => $from->copy()->utc(),
            'check_out_at' => $from->copy()->addDays(2)->utc(), 'nights' => 2,
            'access_pin_hash' => Hash::make('1234'), 'status' => GuestStayStatus::Upcoming,
        ]);

        $this->actingAs($manager)->get(route('workspace.stays.index', [
            'available_from' => $from->format('Y-m-d'),
            'available_to' => $from->copy()->addDays(2)->format('Y-m-d'),
        ]))->assertOk()
            ->assertSee('Garden Villa')
            ->assertSee(trans_choice('workspace.available_villas_count', 1, ['count' => 1]));

        $this->actingAs($manager)->get(route('workspace.stays.index', [
            'available_from' => $from->copy()->addDays(2)->format('Y-m-d'),
            'available_to' => $from->copy()->addDays(3)->format('Y-m-d'),
        ]))->assertOk()
            ->assertSee(trans_choice('workspace.available_villas_count', 2, ['count' => 2]));
    }

    public function test_availability_ajax_endpoint_returns_prefill_data_for_free_rooms(): void
    {
        [$company, $room, $manager] = $this->hotel();
        $freeRoom = Room::create([
            'company_id' => $company->id, 'number' => '306', 'name' => 'Garden Villa',
            'pin_hash' => Hash::make('legacy'), 'is_active' => true,
        ]);
        $from = now($company->timezone)->addDay()->startOfDay();
        GuestStay::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'room_id' => $room->id,
            'guest_name' => 'Busy guest', 'check_in_at' => $from->copy()->utc(),
            'check_out_at' => $from->copy()->addDays(2)->utc(), 'nights' => 2,
            'access_pin_hash' => Hash::make('1234'), 'status' => GuestStayStatus::Upcoming,
        ]);

        $this->actingAs($manager)->getJson(route('workspace.stays.availability', [
            'available_from' => $from->format('Y-m-d'),
            'available_to' => $from->copy()->addDays(2)->format('Y-m-d'),
        ]))->assertOk()
            ->assertJsonPath('rooms.0.id', $freeRoom->id)
            ->assertJsonPath('rooms.0.label', $freeRoom->displayLabel())
            ->assertJsonCount(1, 'rooms');
    }

    public function test_manager_can_update_client_card_and_print_selected_paid_services_in_english(): void
    {
        [$company, $room, $manager] = $this->hotel();
        $stay = GuestStay::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'room_id' => $room->id,
            'guest_name' => 'Anna Petrova', 'check_in_at' => now()->subDay(), 'check_out_at' => now()->addDay(),
            'nights' => 2, 'access_pin_hash' => Hash::make('1234'), 'status' => GuestStayStatus::CheckedIn,
        ]);
        $included = ServiceRequest::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'guest_stay_id' => $stay->id,
            'source' => 'guest', 'room_number' => $room->number, 'guest_name' => $stay->guest_name,
            'title' => 'Airport transfer', 'status' => 'completed', 'priority' => 'normal',
            'price_minor' => 450000, 'payment_method' => 'cash', 'payment_status' => 'paid',
        ]);
        $excluded = ServiceRequest::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'guest_stay_id' => $stay->id,
            'source' => 'guest', 'room_number' => $room->number, 'guest_name' => $stay->guest_name,
            'title' => 'Dinner', 'status' => 'completed', 'priority' => 'normal',
            'price_minor' => 300000, 'payment_method' => 'cash', 'payment_status' => 'paid',
        ]);

        $this->actingAs($manager)->patch(route('workspace.stays.update', $stay), [
            'guest_name' => 'Anna Petrova',
            'guest_email' => 'anna@example.com',
            'emergency_phone' => '+62 812 3456 7890',
            'internal_notes' => 'Allergic to peanuts. Contact only after 09:00.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('guest_stays', [
            'id' => $stay->id,
            'emergency_phone' => '+62 812 3456 7890',
            'internal_notes' => 'Allergic to peanuts. Contact only after 09:00.',
        ]);
        $this->get(route('workspace.stays.show', $stay))->assertOk()
            ->assertSee('Anna Petrova')
            ->assertSee('Airport transfer')
            ->assertSee('Dinner');
        $this->get(route('workspace.stays.bill', ['guestStay' => $stay, 'order_ids' => [$included->id]]))
            ->assertOk()
            ->assertSee('BILL')
            ->assertSee('Airport transfer')
            ->assertDontSee('Dinner')
            ->assertSee('Rp 450.000');

        $otherCompany = Company::create([
            'public_id' => (string) Str::uuid(), 'name' => 'Other Hotel', 'slug' => 'other-hotel',
            'status' => CompanyStatus::Active, 'currency' => 'IDR', 'timezone' => 'Asia/Makassar', 'plan' => 'MVP',
        ]);
        $otherManager = User::factory()->create([
            'company_id' => $otherCompany->id, 'role' => UserRole::Manager, 'is_active' => true,
        ]);
        $this->actingAs($otherManager)->get(route('workspace.stays.show', $stay))->assertNotFound();
        $this->get(route('workspace.stays.bill', $stay))->assertNotFound();
    }

    public function test_manager_can_email_final_unpaid_bill_with_additional_description(): void
    {
        Mail::fake();
        [$company, $room, $manager] = $this->hotel();
        $stay = GuestStay::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'room_id' => $room->id,
            'guest_name' => 'Anna Petrova', 'guest_email' => 'anna@example.com',
            'check_in_at' => now()->subDay(), 'check_out_at' => now()->addDay(), 'nights' => 2,
            'access_pin_hash' => Hash::make('1234'), 'status' => GuestStayStatus::CheckedIn,
        ]);
        ServiceRequest::create([
            'public_id' => (string) Str::uuid(), 'company_id' => $company->id, 'guest_stay_id' => $stay->id,
            'room_number' => $room->number, 'title' => 'Airport transfer', 'status' => 'completed', 'priority' => 'normal',
            'price_minor' => 450000, 'payment_method' => 'room_charge', 'payment_status' => 'invoiced',
        ]);

        $this->actingAs($manager)->post(route('workspace.stays.bill.email', $stay), [
            'additional_description' => 'Transfer includes luggage assistance.',
        ])->assertRedirect()->assertSessionHas('success');

        Mail::assertSent(FinalBillMail::class, fn (FinalBillMail $mail) => $mail->hasTo('anna@example.com')
            && collect($mail->attachments())->pluck('as')->contains('final-bill.pdf')
            && collect($mail->attachments())->pluck('as')->contains('additional-services.txt'));
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
