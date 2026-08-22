<?php

namespace Tests\Feature;

use App\Actions\CreateGuestOrder;
use App\Enums\CompanyStatus;
use App\Enums\GuestStayStatus;
use App\Enums\ServiceNodeType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\GuestSession;
use App\Models\GuestStay;
use App\Models\ManagerActionLog;
use App\Models\Room;
use App\Models\ServiceNode;
use App\Models\ServiceRequestPriceAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServicePriceAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_price_and_name_changes_do_not_change_existing_order_or_bill(): void
    {
        [$company, $manager, $room, $stay, $session, $service] = $this->fixtures();
        $order = app(CreateGuestOrder::class)->handle($session->load('room'), $service, 1, null);

        $service->update(['name' => 'New airport transfer', 'price_minor' => 250000]);

        $this->assertSame(100000, $order->fresh()->price_minor);
        $this->assertDatabaseHas('service_request_items', [
            'service_request_id' => $order->id,
            'name_snapshot' => 'Airport transfer',
            'unit_price_minor' => 100000,
            'total_price_minor' => 100000,
        ]);

        $this->actingAs($manager)->get(route('workspace.stays.bill', $stay))
            ->assertOk()
            ->assertSee('Airport transfer')
            ->assertDontSee('New airport transfer')
            ->assertSee('Rp 100.000')
            ->assertDontSee('Rp 250.000');
    }

    public function test_manager_can_adjust_snapshot_price_with_service_comment_and_audit_trail(): void
    {
        [$company, $manager, $room, $stay, $session, $service] = $this->fixtures();
        $order = app(CreateGuestOrder::class)->handle($session->load('room'), $service, 1, null);
        $item = $order->items()->firstOrFail();

        $this->actingAs($manager)->patch(route('workspace.requests.price', $order), [
            'service_request_item_id' => $item->id,
            'price' => 150000,
            'comment' => 'Added child seat to airport transfer.',
        ])->assertRedirect();

        $this->assertSame(150000, $order->fresh()->price_minor);
        $this->assertSame(150000, $item->fresh()->total_price_minor);
        $this->assertSame(100000, $service->fresh()->price_minor);

        $adjustment = ServiceRequestPriceAdjustment::firstOrFail();
        $this->assertSame($manager->id, $adjustment->user_id);
        $this->assertSame($item->id, $adjustment->service_request_item_id);
        $this->assertSame('Airport transfer', $adjustment->service_name_snapshot);
        $this->assertSame(100000, $adjustment->previous_price_minor);
        $this->assertSame(150000, $adjustment->new_price_minor);
        $this->assertSame('Added child seat to airport transfer.', $adjustment->comment);

        $log = ManagerActionLog::where('action', 'workspace.requests.price')->firstOrFail();
        $this->assertSame($order->id, $log->service_request_id);
        $this->assertSame('150000', (string) $log->metadata['price']);
        $this->assertSame('Added child seat to airport transfer.', $log->metadata['comment']);

        $this->get(route('workspace.requests.show', $order))
            ->assertOk()
            ->assertSee('Airport transfer')
            ->assertSee('Rp 100.000')
            ->assertSee('Rp 150.000')
            ->assertSee('Added child seat to airport transfer.');

        $this->patch(route('workspace.requests.price', $order), [
            'service_request_item_id' => $item->id,
            'price' => 170000,
        ])->assertSessionHasErrors('comment');
        $this->assertSame(150000, $order->fresh()->price_minor);
    }

    public function test_manual_request_uses_catalog_price_when_manager_does_not_override_it(): void
    {
        [$company, $manager, $room, $stay, $session, $service] = $this->fixtures();

        $this->actingAs($manager)->post(route('workspace.requests.store'), [
            'service_node_id' => $service->id,
            'room_number' => $room->number,
            'guest_name' => $stay->guest_name,
            'title' => $service->name,
            'priority' => 'normal',
        ])->assertRedirect();

        $order = $stay->requests()->latest('id')->firstOrFail();
        $this->assertSame(100000, $order->price_minor);
        $this->assertDatabaseHas('service_request_items', [
            'service_request_id' => $order->id,
            'service_node_id' => $service->id,
            'name_snapshot' => 'Airport transfer',
            'unit_price_minor' => 100000,
            'total_price_minor' => 100000,
        ]);
    }

    private function fixtures(): array
    {
        Notification::fake();
        Event::fake();

        $company = Company::create([
            'public_id' => (string) Str::uuid(),
            'name' => 'Alpha Hotel',
            'slug' => 'alpha-hotel',
            'status' => CompanyStatus::Active,
            'currency' => 'IDR',
            'timezone' => 'Asia/Makassar',
            'plan' => 'MVP',
        ]);
        $manager = User::factory()->create([
            'company_id' => $company->id,
            'role' => UserRole::Manager,
            'is_active' => true,
        ]);
        $room = Room::create([
            'company_id' => $company->id,
            'number' => '305',
            'name' => 'Emerald villa',
            'pin_hash' => password_hash('1234', PASSWORD_DEFAULT),
            'is_active' => true,
        ]);
        $stay = GuestStay::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'room_id' => $room->id,
            'guest_name' => 'Alex Guest',
            'check_in_at' => now()->subDay(),
            'check_out_at' => now()->addDays(4),
            'nights' => 5,
            'access_pin_hash' => password_hash('1234', PASSWORD_DEFAULT),
            'status' => GuestStayStatus::CheckedIn,
        ]);
        $session = GuestSession::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'guest_stay_id' => $stay->id,
            'room_id' => $room->id,
            'guest_name' => $stay->guest_name,
            'locale' => 'en',
            'expires_at' => now()->addDays(4),
        ]);
        $service = ServiceNode::create([
            'company_id' => $company->id,
            'type' => ServiceNodeType::Service,
            'name' => 'Airport transfer',
            'price_minor' => 100000,
            'sla_minutes' => 30,
            'is_active' => true,
        ]);

        return [$company, $manager, $room, $stay, $session, $service];
    }
}
