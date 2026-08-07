<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['background_set_id', 'name', 'path', 'background_position', 'background_size', 'sort_order', 'is_active'])]
class BackgroundImage extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(BackgroundSet::class, 'background_set_id');
    }

    public function serviceNodes(): HasMany
    {
        return $this->hasMany(ServiceNode::class);
    }

    public function url(): string
    {
        return str_starts_with($this->path, 'images/')
            ? asset($this->path)
            : Storage::disk('public')->url($this->path);
    }
}
