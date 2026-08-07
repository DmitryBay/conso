<?php

namespace Database\Seeders;

use App\Enums\ServiceNodeType;
use App\Models\Company;
use App\Models\ServiceNode;
use App\Support\BaliDistrictGuides;
use Illuminate\Database\Seeder;

class BaliDistrictGuideSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('slug', 'nusa-bay-hotel')->firstOrFail();
        $root = ServiceNode::query()
            ->where('company_id', $company->id)
            ->whereNull('parent_id')
            ->whereIn('name', ['Гид по районам Бали', 'Бали и острова: гид'])
            ->first() ?? new ServiceNode(['company_id' => $company->id, 'parent_id' => null]);
        $root->fill([
            'type' => ServiceNodeType::Category,
            'name' => 'Бали и острова: гид',
            'description' => 'Подробные гайды по районам Бали, соседним островам и направлениям для отдельных поездок.',
            'translations' => [
                'en' => ['name' => 'Bali & islands guide', 'description' => 'Detailed guides to Bali areas, nearby islands and destinations for separate trips.'],
                'id' => ['name' => 'Panduan Bali & pulau', 'description' => 'Panduan lengkap area Bali, pulau sekitar, dan destinasi untuk perjalanan khusus.'],
            ],
            'icon' => 'bi-map',
            'is_active' => true,
            'sort_order' => 70,
        ])->save();

        foreach (BaliDistrictGuides::all() as $index => $guide) {
            ServiceNode::query()->updateOrCreate(
                ['company_id' => $company->id, 'parent_id' => $root->id, 'name' => $guide['name']],
                [
                    'type' => ServiceNodeType::Guide,
                    'description' => $guide['description'],
                    'translations' => $guide['translations'],
                    'external_links' => BaliDistrictGuides::mapsFor($guide['name']),
                    'icon' => $guide['icon'],
                    'price_minor' => null,
                    'sla_minutes' => null,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }
    }
}
