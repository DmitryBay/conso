<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_request_id', 'service_request_item_id', 'user_id', 'service_name_snapshot',
    'previous_price_minor', 'new_price_minor', 'comment', 'created_at',
])]
class ServiceRequestPriceAdjustment extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'previous_price_minor' => 'integer',
            'new_price_minor' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ServiceRequestItem::class, 'service_request_item_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
