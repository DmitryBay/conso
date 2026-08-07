<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'number', 'floor', 'pin_hash', 'is_active'])]
class Room extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function guestSessions(): HasMany
    {
        return $this->hasMany(GuestSession::class);
    }

    public function guestStays(): HasMany
    {
        return $this->hasMany(GuestStay::class);
    }
}
