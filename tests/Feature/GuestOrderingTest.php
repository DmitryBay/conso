<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\GuestStayStatus;
use App\Enums\RequestStatus;
use App\Enums\ServiceNodeType;
use App\Enums\UserRole;
use App\Events\ServiceRequestChanged;
use App\Models\Company;
use App\Models\GuestSession;
use App\Models\GuestStay;
use App\Models\Room;
use App\Models\ServiceNode;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\ServiceCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_enters_with_room_and_pin(): void
    {
        [$company, $room] = $this->hotel('Alpha Hotel');
        $this->guestStay($company, $room, null);

        $this->post(route('guest.access.store', $company), [
            'room_number' => $room->number,
            'pin' => '1234',
            'guest_name' => 'Anna',
            'country_code' => 'ID',
        ])->assertRedirect(route('guest.catalog', $company));

        $stay = GuestSession::firstOrFail();
        $this->assertSame('Anna', $stay->guest_name);
        $this->assertSame($company->id, $stay->company_id);
        $this->assertSame('ID', $stay->country_code);
        $this->assertSame('id', $stay->locale);
        $this->assertSame('id', session('guest_locale'));
        $this->assertDatabaseCount('guest_sessions', 1);
    }

    public function test_wrong_pin_is_rejected(): void
    {
        [$company, $room] = $this->hotel('Alpha Hotel');
        $this->guestStay($company, $room, null);

        $this->from(route('guest.access', $company))->post(route('guest.access.store', $company), [
            'room_number' => $room->number,
            'pin' => '9999',
            'country_code' => 'RU',
        ])->assertRedirect(route('guest.access', $company))->assertSessionHas('guest_error');

        $this->assertDatabaseCount('guest_sessions', 0);
    }

    public function test_guest_can_log_out_and_revoke_the_current_session(): void
    {
        [$company, $room] = $this->hotel('Alpha Hotel');
        $guestSession = $this->stay($company, $room);

        $this->withSession(['guest_session.'.$company->id => $guestSession->public_id])
            ->post(route('guest.logout', $company))
            ->assertRedirect(route('guest.access', $company))
            ->assertSessionMissing('guest_session.'.$company->id);

        $this->assertNotNull($guestSession->refresh()->revoked_at);
    }

    public function test_guest_app_manifest_is_scoped_to_the_hotel(): void
    {
        [$company] = $this->hotel('Alpha Hotel');

        $this->get(route('guest.manifest', $company))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJson([
                'name' => 'Alpha Hotel Concierge',
                'start_url' => '/guest/'.$company->slug,
                'scope' => '/guest/'.$company->slug,
                'display' => 'fullscreen',
            ]);
    }

    public function test_revoked_guest_session_status_returns_unauthorized_json(): void
    {
        [$company, $room] = $this->hotel('Alpha Hotel');
        $guestSession = $this->stay($company, $room);
        $guestSession->update(['revoked_at' => now()]);

        $this->withSession(['guest_session.'.$company->id => $guestSession->public_id])
            ->getJson(route('guest.session.status', $company))
            ->assertUnauthorized()
            ->assertJson(['authenticated' => false]);
    }

    public function test_guest_session_cannot_cross_company_boundary(): void
    {
        [$companyA, $roomA] = $this->hotel('Alpha Hotel');
        [$companyB] = $this->hotel('Beta Hotel');
        $stay = $this->stay($companyA, $roomA);

        $this->withSession(['guest_session.'.$companyA->id => $stay->public_id])
            ->get(route('guest.catalog', $companyB))
            ->assertRedirect(route('guest.access', $companyB));
    }

    public function test_guest_cannot_order_service_from_another_company(): void
    {
        [$companyA, $roomA] = $this->hotel('Alpha Hotel');
        [$companyB] = $this->hotel('Beta Hotel');
        $foreignService = $this->service($companyB, 'Foreign massage', 150000);
        $stay = $this->stay($companyA, $roomA);

        $this->withSession(['guest_session.'.$companyA->id => $stay->public_id])
            ->post(route('guest.orders.store', [$companyA, $foreignService]), ['quantity' => 1, 'payment_method' => 'room_charge'])
            ->assertNotFound();
    }

    public function test_direct_order_creates_item_history_notification_and_combined_bill(): void
    {
        Event::fake([ServiceRequestChanged::class]);
        [$company, $room] = $this->hotel('Alpha Hotel');
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyOwner, 'is_active' => true]);
        $service = $this->service($company, 'Balinese breakfast', 210000);
        $stay = $this->stay($company, $room);
        $session = ['guest_session.'.$company->id => $stay->public_id];

        $response = $this->withSession($session)->post(route('guest.orders.store', [$company, $service]), [
            'quantity' => 2,
            'payment_method' => 'room_charge',
            'comment' => 'Please deliver at 08:00',
        ]);

        $order = ServiceRequest::firstOrFail();
        $response->assertRedirect(route('guest.orders.show', [$company, $order]));
        $this->assertSame(RequestStatus::New, $order->status);
        $this->assertSame(420000, $order->price_minor);
        $this->assertSame($stay->id, $order->guest_session_id);
        $this->assertDatabaseHas('service_request_items', [
            'service_request_id' => $order->id,
            'name_snapshot' => 'Balinese breakfast',
            'quantity' => 2,
            'total_price_minor' => 420000,
        ]);
        $this->assertDatabaseHas('service_request_status_histories', ['service_request_id' => $order->id, 'to_status' => 'new']);
        $this->assertSame(1, $owner->notifications()->count());
        $this->get(route('guest.orders.show', [$company, $order]))
            ->assertOk()
            ->assertSee('Balinese breakfast')
            ->assertSee('Ход выполнения')
            ->assertSee('Звонки временно недоступны')
            ->assertDontSee('Свяжитесь со стойкой регистрации по телефону в номере.');
        $this->get(route('guest.bill', $company))
            ->assertOk()
            ->assertSee('Rp 420.000')
            ->assertSee('≈ $25.45');
        Event::assertDispatched(ServiceRequestChanged::class);
    }

    public function test_guest_can_switch_to_arabic_with_rtl_and_localized_service(): void
    {
        [$company, $room] = $this->hotel('Alpha Hotel');
        $service = $this->service($company, 'Breakfast', 210000);
        $service->update(['translations' => ['ar' => ['name' => 'إفطار بالي', 'description' => 'إفطار طازج']]]);
        $stay = $this->stay($company, $room);

        $this->withSession(['guest_session.'.$company->id => $stay->public_id])
            ->get(route('guest.catalog', $company).'?lang=ar')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('إفطار بالي');

        $this->assertSame('ar', session('guest_locale'));
    }

    public function test_guest_can_switch_to_hebrew_and_ukrainian(): void
    {
        [$company, $room] = $this->hotel('Alpha Hotel');
        $service = $this->service($company, 'Дополнительные полотенца', 0);
        $stay = $this->stay($company, $room);
        $session = ['guest_session.'.$company->id => $stay->public_id];

        $this->withSession($session)->get(route('guest.catalog', $company).'?lang=he')
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('מגבות נוספות');

        $this->get(route('guest.catalog', $company).'?lang=uk')
            ->assertOk()
            ->assertDontSee('Чим можемо допомогти?')
            ->assertSee('Додаткові рушники');
    }

    public function test_catalog_template_is_hierarchical_and_idempotent(): void
    {
        [$company, $room] = $this->hotel('Nusa Bay Hotel');
        $this->seed(ServiceCatalogSeeder::class);
        $firstCount = ServiceNode::where('company_id', $company->id)->count();
        $this->seed(ServiceCatalogSeeder::class);

        $localCuisine = ServiceNode::where('company_id', $company->id)->where('name', 'Местная кухня Бали')->firstOrFail();
        $nasiCampur = ServiceNode::where('company_id', $company->id)->where('name', 'Nasi Campur Bali')->firstOrFail();
        $stay = $this->stay($company, $room);

        $this->assertSame($localCuisine->id, $nasiCampur->parent_id);
        $this->assertSame($firstCount, ServiceNode::where('company_id', $company->id)->count());
        $this->withSession(['guest_session.'.$company->id => $stay->public_id])
            ->get(route('guest.catalog', $company).'?lang=en')
            ->assertOk()
            ->assertSee('Food &amp; Drinks', false)
            ->assertSee('Balinese local cuisine')
            ->assertSee('Balinese Nasi Campur')
            ->assertSee('Bali itineraries')
            ->assertSee('A 2-day escape')
            ->assertSee('Fits your stay')
            ->assertSee('data-guest-menu', false)
            ->assertSee('data-page-size="6"', false)
            ->assertSee('data-bs-target="#guestGuideMenu"', false)
            ->assertDontSee('guest-hero', false)
            ->assertSee('guest-guide-modal', false)
            ->assertSee('guest-subcategory', false);
    }

    public function test_guest_cannot_open_another_stays_order(): void
    {
        [$company, $room] = $this->hotel('Alpha Hotel');
        $stayA = $this->stay($company, $room, 'Anna');
        $stayB = $this->stay($company, $room, 'Maria');
        $order = ServiceRequest::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'guest_stay_id' => $stayB->guest_stay_id,
            'guest_session_id' => $stayB->id,
            'room_number' => $room->number,
            'title' => 'Private order',
            'status' => RequestStatus::New,
            'priority' => 'normal',
        ]);

        $this->withSession(['guest_session.'.$company->id => $stayA->public_id])
            ->get(route('guest.orders.show', [$company, $order]))
            ->assertNotFound();
    }

    public function test_manager_can_install_editable_bali_area_guides_without_duplicates(): void
    {
        [$company, $room] = $this->hotel('Alpha Hotel');
        $manager = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);

        $this->actingAs($manager)->post(route('workspace.services.guides.bali'))->assertRedirect();
        $this->post(route('workspace.services.guides.bali'))->assertRedirect();

        $root = ServiceNode::where('company_id', $company->id)->where('name', 'Бали и острова: гид')->firstOrFail();
        $canggu = ServiceNode::where('company_id', $company->id)->where('name', 'Чангу')->firstOrFail();

        $this->assertSame(ServiceNodeType::Category, $root->type);
        $this->assertSame(ServiceNodeType::Guide, $canggu->type);
        $this->assertSame(13, $root->children()->count());
        $this->assertSame('Canggu', $canggu->localizedName('en'));
        $this->assertStringContainsString('selancar', $canggu->localizedDescription('id'));
        $this->assertCount(5, $canggu->external_links);

        $stay = $this->stay($company, $room);
        $this->withSession(['guest_session.'.$company->id => $stay->public_id])
            ->get(route('guest.catalog', $company).'?lang=en')
            ->assertOk()
            ->assertSee('Bali &amp; islands guide', false)
            ->assertSee('Canggu')
            ->assertSee('Atmosphere: a young, lively area')
            ->assertSee('Concierge tip:')
            ->assertSee('choose Pererenan')
            ->assertSee('Places on Google Maps')
            ->assertSee('Batu Bolong Beach')
            ->assertSee('google.com/maps/search/', false)
            ->assertSee('data-bs-target="#guestAreaGuide-'.$canggu->id.'"', false)
            ->assertSee('id="guestAreaGuide-'.$canggu->id.'"', false)
            ->assertDontSee(route('guest.orders.create', [$company, $canggu]), false);

        $this->put(route('workspace.services.update', $canggu), [
            'type' => ServiceNodeType::Guide->value,
            'name' => 'Чангу',
            'description' => 'Отдельный текст на русском языке.',
            'parent_id' => $root->id,
            'icon' => 'bi-water',
            'sort_order' => 10,
            'is_active' => 1,
            'translations' => [
                'en' => ['name' => 'Canggu', 'description' => 'A different message written for English-speaking guests.'],
                'id' => ['name' => 'Canggu', 'description' => 'Pesan khusus yang berbeda untuk tamu Indonesia.'],
            ],
        ])->assertRedirect();

        $canggu->refresh();
        $this->assertSame('A different message written for English-speaking guests.', $canggu->localizedDescription('en'));
        $this->assertSame('Pesan khusus yang berbeda untuk tamu Indonesia.', $canggu->localizedDescription('id'));
    }

    private function hotel(string $name): array
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
        $room = Room::create([
            'company_id' => $company->id,
            'number' => '305',
            'floor' => '3',
            'pin_hash' => Hash::make('1234'),
            'is_active' => true,
        ]);

        return [$company, $room];
    }

    private function stay(Company $company, Room $room, string $name = 'Guest'): GuestSession
    {
        $stay = $this->guestStay($company, $room, $name);

        return GuestSession::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'guest_stay_id' => $stay->id,
            'room_id' => $room->id,
            'guest_name' => $name,
            'expires_at' => $stay->check_out_at,
            'last_seen_at' => now(),
        ]);
    }

    private function guestStay(Company $company, Room $room, ?string $name, string $pin = '1234'): GuestStay
    {
        return GuestStay::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'room_id' => $room->id,
            'guest_name' => $name,
            'check_in_at' => now()->subHour(),
            'check_out_at' => now()->addDay(),
            'nights' => 1,
            'access_pin_hash' => Hash::make($pin),
            'status' => GuestStayStatus::CheckedIn,
        ]);
    }

    private function service(Company $company, string $name, int $price): ServiceNode
    {
        $category = ServiceNode::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'Test services',
        ], [
            'type' => ServiceNodeType::Category,
            'icon' => 'bi-grid',
            'is_active' => true,
        ]);

        return ServiceNode::create([
            'company_id' => $company->id,
            'parent_id' => $category->id,
            'type' => ServiceNodeType::Service,
            'name' => $name,
            'description' => 'Guest service',
            'background_key' => 'food',
            'price_minor' => $price,
            'sla_minutes' => 30,
            'is_active' => true,
        ]);
    }
}
