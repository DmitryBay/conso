<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\GuestStayStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\GuestStay;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoomInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_room_inventory_and_active_count_is_synchronized(): void
    {
        [$company, $owner] = $this->hotel();

        $this->actingAs($owner)->post(route('workspace.rooms.store'), [
            'number' => 'CV-305',
            'name' => 'Coral Villa',
            'floor' => 'Beach',
        ])->assertRedirect()->assertSessionHas('success');

        $room = Room::firstOrFail();
        $this->assertSame('Coral Villa', $room->displayName());
        $this->assertSame('Coral Villa · CV-305', $room->displayLabel());
        $this->assertSame(1, $company->fresh()->rooms_count);

        $this->put(route('workspace.rooms.update', $room), [
            'number' => 'CV-305',
            'name' => 'Sunset Villa',
            'floor' => 'Beach',
        ])->assertRedirect();

        $this->assertSame('Sunset Villa', $room->fresh()->name);
        $this->assertFalse($room->fresh()->is_active);
        $this->assertSame(0, $company->fresh()->rooms_count);
    }

    public function test_manager_cannot_manage_room_inventory_or_update_another_company_room(): void
    {
        [$companyA, $ownerA] = $this->hotel('Alpha Hotel');
        [$companyB] = $this->hotel('Beta Hotel');
        $manager = User::factory()->create(['company_id' => $companyA->id, 'role' => UserRole::Manager]);
        $foreignRoom = $this->room($companyB, 'B-1', 'Beta Villa');

        $this->actingAs($manager)->get(route('workspace.rooms.index'))->assertForbidden();
        $this->actingAs($ownerA)->put(route('workspace.rooms.update', $foreignRoom), [
            'number' => 'B-2',
            'name' => 'Changed Villa',
            'floor' => '2',
            'is_active' => 1,
        ])->assertNotFound();
    }

    public function test_room_code_and_name_cannot_collide_with_another_room_identifier(): void
    {
        [$company, $owner] = $this->hotel();
        $this->room($company, '305', 'Coral Villa');

        $this->actingAs($owner)->post(route('workspace.rooms.store'), [
            'number' => 'Coral Villa',
            'name' => 'Garden Villa',
        ])->assertSessionHasErrors('number');

        $this->assertDatabaseCount('rooms', 1);
    }

    public function test_guest_can_sign_in_with_room_display_name(): void
    {
        [$company] = $this->hotel();
        $room = $this->room($company, 'CV-305', 'Coral Villa');
        GuestStay::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'room_id' => $room->id,
            'guest_name' => 'Anna',
            'check_in_at' => now()->subHour(),
            'check_out_at' => now()->addDay(),
            'nights' => 1,
            'access_pin_hash' => Hash::make('5678'),
            'status' => GuestStayStatus::CheckedIn,
        ]);

        $this->post(route('guest.access.store', $company), [
            'room_number' => 'Coral Villa',
            'pin' => '5678',
            'country_code' => 'INT',
        ])->assertRedirect(route('guest.catalog', $company));

        $this->get(route('guest.bill', $company))->assertOk()->assertSee('Coral Villa');
    }

    private function hotel(string $name = 'Nusa Bay Hotel'): array
    {
        $company = Company::create([
            'public_id' => (string) Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'status' => CompanyStatus::Active,
            'currency' => 'IDR',
            'timezone' => 'Asia/Makassar',
            'plan' => 'MVP',
        ]);
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyOwner]);

        return [$company, $owner];
    }

    private function room(Company $company, string $number, ?string $name = null): Room
    {
        return Room::create([
            'company_id' => $company->id,
            'number' => $number,
            'name' => $name,
            'pin_hash' => Hash::make(Str::random(40)),
            'is_active' => true,
        ]);
    }
}
