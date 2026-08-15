<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_request_id', 'service_node_id', 'name_snapshot', 'quantity', 'unit_price_minor', 'total_price_minor', 'notes', 'options_snapshot'])]
class ServiceRequestItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_minor' => 'integer',
            'total_price_minor' => 'integer',
            'options_snapshot' => 'array',
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->name_snapshot;
    }

    public function getTotalMinorAttribute(): int
    {
        return $this->total_price_minor;
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ServiceNode::class, 'service_node_id');
    }
}
