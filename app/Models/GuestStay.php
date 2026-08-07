<?php

namespace App\Models;

use App\Enums\GuestStayStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'public_id', 'company_id', 'room_id', 'guest_name', 'check_in_at', 'check_out_at',
    'nights', 'access_pin_hash', 'access_pin', 'status', 'checked_out_at',
])]
class GuestStay extends Model
{
    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'nights' => 'integer',
            'access_pin' => 'encrypted',
            'status' => GuestStayStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GuestSession::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function isActive(): bool
    {
        return $this->status === GuestStayStatus::CheckedIn
            && ! $this->checked_out_at
            && $this->check_in_at->lte(now())
            && $this->check_out_at->isFuture();
    }
}
