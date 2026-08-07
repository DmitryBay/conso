<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\ServiceNodeType;
use App\Enums\UserRole;
use App\Models\BackgroundSet;
use App\Models\Company;
use App\Models\ServiceNode;
use App\Models\User;
use Database\Seeders\BackgroundSetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackgroundLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_activate_a_pack_and_existing_assignments_follow_it(): void
    {
        [$company, $owner] = $this->companyWithOwner('Alpha Hotel');
        $this->seed(BackgroundSetSeeder::class);
        $company->refresh()->load('backgroundSet.images');
        $food = $company->backgroundSet->images->firstWhere('name', 'Food & Dining');
        $service = ServiceNode::create([
            'company_id' => $company->id,
            'type' => ServiceNodeType::Service,
            'name' => 'Breakfast',
            'background_image_id' => $food->id,
        ]);
        $ocean = BackgroundSet::where('slug', 'ocean-resort')->firstOrFail();

        $this->actingAs($owner)->patch(route('workspace.backgrounds.activate', $ocean))->assertRedirect();

        $this->assertSame($ocean->id, $company->refresh()->background_set_id);
        $this->assertSame('Food & Dining', $service->refresh()->backgroundImage->name);
        $this->assertSame($ocean->id, $service->backgroundImage->background_set_id);
    }

    public function test_manager_can_view_but_cannot_change_the_hotel_pack(): void
    {
        [$company] = $this->companyWithOwner('Alpha Hotel');
        $manager = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::Manager]);
        $this->seed(BackgroundSetSeeder::class);
        $ocean = BackgroundSet::where('slug', 'ocean-resort')->firstOrFail();

        $this->actingAs($manager)->get(route('workspace.backgrounds.index'))->assertOk();
        $this->actingAs($manager)->patch(route('workspace.backgrounds.activate', $ocean))->assertForbidden();
    }

    public function test_uploaded_images_are_private_to_the_company(): void
    {
        Storage::fake('public');
        [$companyA, $ownerA] = $this->companyWithOwner('Alpha Hotel');
        [$companyB, $ownerB] = $this->companyWithOwner('Beta Hotel');

        $this->actingAs($ownerA)->post(route('workspace.backgrounds.store'), [
            'name' => 'Pool Villa',
            'image' => UploadedFile::fake()->image('villa.jpg', 1200, 800),
        ])->assertRedirect();

        $customSet = BackgroundSet::where('company_id', $companyA->id)->firstOrFail();
        $image = $customSet->images()->firstOrFail();
        Storage::disk('public')->assertExists($image->path);
        $this->assertDatabaseMissing('background_sets', ['company_id' => $companyB->id, 'slug' => 'custom-library']);
        $this->actingAs($ownerB)->patch(route('workspace.backgrounds.activate', $customSet))->assertNotFound();
    }

    private function companyWithOwner(string $name): array
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
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => UserRole::CompanyOwner]);

        return [$company, $owner];
    }
}
