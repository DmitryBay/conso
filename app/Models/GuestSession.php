<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['public_id', 'company_id', 'guest_stay_id', 'room_id', 'guest_name', 'locale', 'country_code', 'expires_at', 'last_seen_at', 'revoked_at'])]
class GuestSession extends Model
{
    use HasPushSubscriptions, Notifiable;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
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

    public function stay(): BelongsTo
    {
        return $this->belongsTo(GuestStay::class, 'guest_stay_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function isValid(): bool
    {
        return ! $this->revoked_at
            && $this->expires_at->isFuture()
            && $this->room->is_active
            && $this->stay?->isActive();
    }

    public function routeNotificationForMail(): ?string
    {
        return $this->stay?->guest_email;
    }
}
