<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_nodes', function (Blueprint $table): void {
            $table->json('option_keys')->nullable()->after('external_links');
            $table->boolean('smart_home_enabled')->default(false)->after('option_keys');
        });
        Schema::table('service_request_items', function (Blueprint $table): void {
            $table->json('options_snapshot')->nullable()->after('notes');
        });

        DB::table('service_nodes')->where('type', 'service')->orderBy('id')->each(function (object $node): void {
            $name = mb_strtolower($node->name);
            $options = match (true) {
                str_contains($name, 'breakfast'), str_contains($name, 'завтрак'), str_contains($name, 'room service') => ['table_setting', 'in_room_service', 'contactless_delivery', 'allergy_friendly', 'child_friendly'],
                str_contains($name, 'cleaning'), str_contains($name, 'уборк') => ['preferred_time', 'eco_friendly'],
                str_contains($name, 'laundry'), str_contains($name, 'прачеч') => ['express_service', 'delicate_care'],
                str_contains($name, 'transfer'), str_contains($name, 'трансфер') => ['child_seat', 'meet_and_greet', 'extra_luggage'],
                default => [],
            };

            if ($options !== []) {
                DB::table('service_nodes')->where('id', $node->id)->update([
                    'option_keys' => json_encode($options, JSON_UNESCAPED_UNICODE),
                ]);
            }
        });

        DB::table('companies')->orderBy('id')->each(function (object $company): void {
            $categoryId = DB::table('service_nodes')
                ->where('company_id', $company->id)
                ->whereNull('parent_id')
                ->whereIn('name', ['Умный дом', 'Smart home'])
                ->value('id');

            if (! $categoryId) {
                $categoryId = DB::table('service_nodes')->insertGetId([
                    'company_id' => $company->id,
                    'parent_id' => null,
                    'type' => 'category',
                    'name' => 'Умный дом',
                    'description' => 'Управление комфортом номера с телефона.',
                    'translations' => json_encode([
                        'en' => ['name' => 'Smart home', 'description' => 'Control room comfort from your phone.'],
                    ], JSON_UNESCAPED_UNICODE),
                    'icon' => 'bi-house-gear',
                    'is_active' => true,
                    'sort_order' => 90,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $control = DB::table('service_nodes')
                ->where('company_id', $company->id)
                ->where('parent_id', $categoryId)
                ->whereIn('name', ['Управление номером', 'Room controls'])
                ->first();

            if ($control) {
                DB::table('service_nodes')->where('id', $control->id)->update(['smart_home_enabled' => true]);
            } else {
                DB::table('service_nodes')->insert([
                    'company_id' => $company->id,
                    'parent_id' => $categoryId,
                    'type' => 'service',
                    'name' => 'Управление номером',
                    'description' => 'Свет, шторы, климат и сценарии комфорта.',
                    'translations' => json_encode([
                        'en' => ['name' => 'Room controls', 'description' => 'Lights, curtains, climate and comfort scenes.'],
                    ], JSON_UNESCAPED_UNICODE),
                    'icon' => 'bi-sliders2-vertical',
                    'price_minor' => null,
                    'sla_minutes' => null,
                    'is_active' => true,
                    'sort_order' => 10,
                    'smart_home_enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('service_nodes')->where('smart_home_enabled', true)->update(['smart_home_enabled' => false]);

        Schema::table('service_request_items', function (Blueprint $table): void {
            $table->dropColumn('options_snapshot');
        });
        Schema::table('service_nodes', function (Blueprint $table): void {
            $table->dropColumn(['option_keys', 'smart_home_enabled']);
        });
    }
};
