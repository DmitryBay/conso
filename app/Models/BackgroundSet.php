<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'slug', 'is_system', 'blur_px', 'overlay_percent'])]
class BackgroundSet extends Model
{
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'blur_px' => 'integer',
            'overlay_percent' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(BackgroundImage::class)->orderBy('sort_order')->orderBy('id');
    }
}
