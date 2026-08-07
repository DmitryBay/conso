<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'public_id', 'name', 'slug', 'legal_name', 'email', 'phone', 'timezone',
    'currency', 'status', 'plan', 'rooms_count', 'trial_ends_at', 'background_set_id',
])]
class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'trial_ends_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'company_owner');
    }

    public function serviceNodes(): HasMany
    {
        return $this->hasMany(ServiceNode::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function guestSessions(): HasMany
    {
        return $this->hasMany(GuestSession::class);
    }

    public function guestStays(): HasMany
    {
        return $this->hasMany(GuestStay::class);
    }

    public function backgroundSet(): BelongsTo
    {
        return $this->belongsTo(BackgroundSet::class);
    }

    public function backgroundSets(): HasMany
    {
        return $this->hasMany(BackgroundSet::class);
    }
}
