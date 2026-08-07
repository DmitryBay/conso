<?php

namespace App\Events;

use App\Models\ServiceRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ServiceRequest $request, public string $action) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('company.'.$this->request->company_id)];
    }

    public function broadcastAs(): string
    {
        return 'request.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->request->id,
            'public_id' => $this->request->public_id,
            'status' => $this->request->status->value,
            'assigned_to' => $this->request->assigned_to,
            'action' => $this->action,
        ];
    }
}
