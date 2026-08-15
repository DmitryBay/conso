<?php

namespace App\Support;

use App\Enums\ServiceNodeType;
use App\Models\Company;
use App\Models\ServiceNode;

class SmartHomeDemo
{
    public static function install(Company $company): ServiceNode
    {
        $category = ServiceNode::query()->firstOrCreate([
            'company_id' => $company->id,
            'parent_id' => null,
            'name' => 'Умный дом',
        ], [
            'type' => ServiceNodeType::Category,
            'description' => 'Управление комфортом номера с телефона.',
            'translations' => ['en' => ['name' => 'Smart home', 'description' => 'Control room comfort from your phone.']],
            'icon' => 'bi-house-gear',
            'is_active' => true,
            'sort_order' => 90,
        ]);

        return ServiceNode::query()->updateOrCreate([
            'company_id' => $company->id,
            'parent_id' => $category->id,
            'name' => 'Управление номером',
        ], [
            'type' => ServiceNodeType::Service,
            'description' => 'Свет, шторы, климат и сценарии комфорта.',
            'translations' => ['en' => ['name' => 'Room controls', 'description' => 'Lights, curtains, climate and comfort scenes.']],
            'icon' => 'bi-sliders2-vertical',
            'price_minor' => null,
            'sla_minutes' => null,
            'is_active' => true,
            'sort_order' => 10,
            'smart_home_enabled' => true,
        ]);
    }
}
