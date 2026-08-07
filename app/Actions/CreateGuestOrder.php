<?php

namespace App\Actions;

use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Events\ServiceRequestChanged;
use App\Models\GuestSession;
use App\Models\ServiceNode;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateGuestOrder
{
    public function handle(GuestSession $stay, ServiceNode $service, int $quantity, string $paymentMethod, ?string $comment): ServiceRequest
    {
        abort_unless($service->company_id === $stay->company_id && $service->is_active && ! $service->isCategory(), 404);
        $total = ($service->price_minor ?? 0) * $quantity;

        $order = DB::transaction(function () use ($stay, $service, $quantity, $paymentMethod, $comment, $total) {
            $order = ServiceRequest::create([
                'public_id' => (string) Str::uuid(),
                'company_id' => $stay->company_id,
                'guest_stay_id' => $stay->guest_stay_id,
                'guest_session_id' => $stay->id,
                'service_node_id' => $service->id,
                'source' => 'guest',
                'room_number' => $stay->room->number,
                'guest_name' => $stay->guest_name,
                'title' => $service->name,
                'description' => $comment,
                'status' => RequestStatus::New,
                'priority' => RequestPriority::Normal,
                'price_minor' => $total,
                'payment_method' => $total > 0 ? $paymentMethod : null,
                'payment_status' => $total > 0 ? 'pending' : 'not_required',
                'due_at' => now()->addMinutes(max($service->sla_minutes ?? 30, 10)),
            ]);

            $order->items()->create([
                'service_node_id' => $service->id,
                'name_snapshot' => $service->name,
                'quantity' => $quantity,
                'unit_price_minor' => $service->price_minor ?? 0,
                'total_price_minor' => $total,
                'notes' => $comment,
            ]);

            $order->history()->create([
                'to_status' => RequestStatus::New->value,
                'note' => 'workspace.history_guest',
                'created_at' => now(),
            ]);

            return $order;
        });

        User::where('company_id', $stay->company_id)->where('is_active', true)->get()->each->notify(
            new WorkspaceNotification([
                'title_key' => 'workspace.notification_guest_order',
                'body_key' => 'workspace.notification_room_request',
                'params' => ['room' => $stay->room->number, 'request' => $order->title],
                'request_id' => $order->id,
                'url' => route('workspace.requests.show', $order),
                'icon' => 'bi-bag-check',
                'email' => true,
                'push' => true,
            ])
        );
        ServiceRequestChanged::dispatch($order, 'guest_created');

        return $order;
    }
}
