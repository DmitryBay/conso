<?php

namespace Database\Seeders;

use App\Models\BackgroundImage;
use App\Models\BackgroundSet;
use App\Models\Company;
use App\Models\ServiceNode;
use Illuminate\Database\Seeder;

class BackgroundSetSeeder extends Seeder
{
    public function run(): void
    {
        $packs = [
            'tropical-bali' => [
                'name' => 'Tropical Bali',
                'images' => [
                    ['Food & Dining', 'images/service-backgrounds/food.jpg', 'center', 'cover'],
                    ['Room', 'images/service-backgrounds/room.jpg', 'center', 'cover'],
                    ['Transfer', 'images/service-backgrounds/transport.jpg', 'center', 'cover'],
                    ['Wellness', 'images/service-backgrounds/wellness.jpg', 'center', 'cover'],
                ],
            ],
            'minimal-luxury' => $this->spritePack('Minimal Luxury', 'images/background-packs/minimal-luxury.webp'),
            'ocean-resort' => $this->spritePack('Ocean Resort', 'images/background-packs/ocean-resort.webp'),
            'urban-business' => $this->spritePack('Urban Business', 'images/background-packs/urban-business.webp'),
        ];

        foreach ($packs as $slug => $definition) {
            $set = BackgroundSet::query()->whereNull('company_id')->where('slug', $slug)->first()
                ?? new BackgroundSet(['company_id' => null, 'slug' => $slug]);
            $set->fill(['name' => $definition['name'], 'is_system' => true, 'blur_px' => 3, 'overlay_percent' => 52])->save();

            foreach ($definition['images'] as $index => [$name, $path, $position, $size]) {
                BackgroundImage::updateOrCreate(
                    ['background_set_id' => $set->id, 'name' => $name],
                    ['path' => $path, 'background_position' => $position, 'background_size' => $size, 'sort_order' => ($index + 1) * 10, 'is_active' => true]
                );
            }
        }

        $defaultSet = BackgroundSet::query()->whereNull('company_id')->where('slug', 'tropical-bali')->firstOrFail();
        Company::query()->whereNull('background_set_id')->update(['background_set_id' => $defaultSet->id]);

        Company::query()->with('backgroundSet.images')->each(function (Company $company) {
            $images = $company->backgroundSet?->images->keyBy('name');
            if (! $images) {
                return;
            }

            ServiceNode::query()->where('company_id', $company->id)->whereNull('background_image_id')->each(function (ServiceNode $node) use ($images) {
                $name = match ($node->background_key) {
                    'food' => 'Food & Dining',
                    'room' => 'Room',
                    'transport' => 'Transfer',
                    'wellness' => 'Wellness',
                    default => $node->parent_id === null ? ['Food & Dining', 'Room', 'Transfer', 'Wellness'][intdiv(max($node->sort_order, 10) - 10, 10) % 4] : null,
                };

                if ($name && $images->has($name)) {
                    $node->update(['background_image_id' => $images[$name]->id]);
                }
            });
        });
    }

    private function spritePack(string $name, string $path): array
    {
        return [
            'name' => $name,
            'images' => [
                ['Food & Dining', $path, '0% center', '400% 100%'],
                ['Room', $path, '33.333% center', '400% 100%'],
                ['Transfer', $path, '66.667% center', '400% 100%'],
                ['Wellness', $path, '100% center', '400% 100%'],
            ],
        ];
    }
}
