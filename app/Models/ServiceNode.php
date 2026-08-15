<?php

namespace App\Models;

use App\Enums\ServiceNodeType;
use App\Support\ServiceTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'parent_id', 'type', 'name', 'description', 'translations', 'external_links', 'option_keys', 'smart_home_enabled', 'icon', 'background_key', 'background_image_id', 'price_minor', 'sla_minutes', 'is_active', 'sort_order'])]
class ServiceNode extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => ServiceNodeType::class,
            'is_active' => 'boolean',
            'price_minor' => 'integer',
            'translations' => 'array',
            'external_links' => 'array',
            'option_keys' => 'array',
            'smart_home_enabled' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function backgroundImage(): BelongsTo
    {
        return $this->belongsTo(BackgroundImage::class);
    }

    public function resolvedBackground(?Company $company = null): ?BackgroundImage
    {
        $node = $this;

        while ($node) {
            if ($node->backgroundImage) {
                return $node->backgroundImage;
            }
            $node = $node->parent;
        }

        $company ??= $this->company;

        return $company?->backgroundSet?->images
            ?->where('is_active', true)
            ->sortBy('sort_order')
            ->first();
    }

    public function isCategory(): bool
    {
        return $this->type === ServiceNodeType::Category;
    }

    public function isGuide(): bool
    {
        return $this->type === ServiceNodeType::Guide;
    }

    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $translation = $this->translations[$locale] ?? ServiceTranslations::for($this->name)[$locale] ?? null;

        return is_array($translation) ? ($translation['name'] ?? $this->name) : ($translation ?: $this->name);
    }

    public function localizedDescription(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $translation = $this->translations[$locale] ?? ServiceTranslations::for($this->name)[$locale] ?? null;

        return is_array($translation) ? ($translation['description'] ?? $this->description) : $this->description;
    }
}
