<?php

namespace App\Models;

use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'public_id', 'company_id', 'guest_stay_id', 'guest_session_id', 'service_node_id', 'assigned_to', 'created_by', 'source',
    'room_number', 'guest_name', 'title', 'description', 'status', 'priority',
    'price_minor', 'payment_method', 'payment_status', 'due_at', 'accepted_at', 'completed_at', 'archived_at',
    'clarification_requested_at', 'refund_status', 'refund_amount_minor', 'refund_requested_at', 'refunded_at',
])]
class ServiceRequest extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'priority' => RequestPriority::class,
            'due_at' => 'datetime',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
            'clarification_requested_at' => 'datetime',
            'refund_amount_minor' => 'integer',
            'refund_requested_at' => 'datetime',
            'refunded_at' => 'datetime',
            'price_minor' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceNode::class, 'service_node_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function guestSession(): BelongsTo
    {
        return $this->belongsTo(GuestSession::class);
    }

    public function guestStay(): BelongsTo
    {
        return $this->belongsTo(GuestStay::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceRequestItem::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ServiceRequestStatusHistory::class)->latest('created_at');
    }

    public function priceAdjustments(): HasMany
    {
        return $this->hasMany(ServiceRequestPriceAdjustment::class)->latest('created_at');
    }

    public function isOverdue(): bool
    {
        return $this->due_at?->isPast() && ! in_array($this->status, [RequestStatus::Ready, RequestStatus::Completed, RequestStatus::Cancelled], true);
    }

    public function hasRefund(): bool
    {
        return filled($this->refund_status);
    }

    public function isRefunded(): bool
    {
        return in_array($this->refund_status, ['partial', 'full'], true);
    }

    public function roomDisplayName(): string
    {
        return $this->guestStay?->room?->displayName() ?? $this->room_number;
    }

    public function roomDisplayLabel(): string
    {
        $room = $this->guestStay?->room ?? $this->guestSession?->room;
        $room ??= Room::where('company_id', $this->company_id)->where('number', $this->room_number)->first();

        return $room?->displayLabel() ?? $this->room_number;
    }
}
