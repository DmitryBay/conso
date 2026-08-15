<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Enums\ServiceNodeType;
use App\Events\ServiceRequestChanged;
use App\Http\Controllers\Controller;
use App\Models\GuestStay;
use App\Models\Room;
use App\Models\ServiceNode;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\GuestRequestStatusNotification;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;
        $filters = $request->validate([
            'mine' => ['nullable', 'boolean'],
            'priority' => ['nullable', Rule::enum(RequestPriority::class)],
            'guest_stay_id' => ['nullable', 'integer'],
            'guest_name' => ['nullable', 'string', 'max:160'],
            'refund' => ['nullable', 'boolean'],
            'cancelled' => ['nullable', 'boolean'],
            'archive' => ['nullable', 'boolean'],
            'all_stays' => ['nullable', 'boolean'],
        ]);
        $query = ServiceRequest::where('company_id', $companyId)
            ->with(['assignee', 'service', 'guestStay.room'])
            ->when($request->boolean('mine'), fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->when($request->string('priority')->toString(), fn ($query, $priority) => $query->where('priority', $priority))
            ->when($filters['guest_stay_id'] ?? null, fn ($query, $stayId) => $query->where('guest_stay_id', $stayId))
            ->when($filters['guest_name'] ?? null, fn ($query, $name) => $query->whereHas('guestStay', fn ($stay) => $stay->where('guest_name', 'like', '%'.$name.'%')))
            ->when($request->boolean('refund'), fn ($query) => $query->whereNotNull('refund_status'))
            ->when($request->boolean('cancelled'), fn ($query) => $query->where('status', RequestStatus::Cancelled))
            ->when(! $request->boolean('cancelled'), fn ($query) => $query->where('status', '!=', RequestStatus::Cancelled))
            ->when($request->boolean('archive'), fn ($query) => $query->whereNotNull('archived_at'))
            ->when(! $request->boolean('all_stays') && ! $request->boolean('archive'), fn ($query) => $query->where(function ($active) {
                $active->whereNull('guest_stay_id')->orWhereHas('guestStay', fn ($stay) => $stay->where('status', 'checked_in'));
            }))
            ->latest();

        $requests = $query->get()->groupBy(fn (ServiceRequest $item) => $item->status === RequestStatus::Completed
            ? RequestStatus::Ready->value
            : $item->status->value);
        $members = User::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $services = ServiceNode::where('company_id', $companyId)->where('type', ServiceNodeType::Service)->where('is_active', true)->orderBy('name')->get();
        $rooms = Room::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->orderBy('number')->get();
        $clients = GuestStay::where('company_id', $companyId)->whereIn('status', ['checked_in', 'upcoming'])->with('room')->orderBy('guest_name')->get();

        return view('workspace.requests.index', compact('requests', 'members', 'services', 'rooms', 'clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_node_id' => ['nullable', 'integer'],
            'room_number' => ['required', 'string', 'max:30'],
            'guest_name' => ['nullable', 'string', 'max:160'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', Rule::enum(RequestPriority::class)],
            'assigned_to' => ['nullable', 'integer'],
            'due_at' => ['nullable', 'date'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);
        $companyId = $request->user()->company_id;
        $room = Room::where('company_id', $companyId)->where('is_active', true)->where('number', $data['room_number'])->firstOrFail();
        $guestStay = GuestStay::where('company_id', $companyId)->where('room_id', $room->id)
            ->where('status', 'checked_in')->latest('check_in_at')->first();
        $service = $this->tenantService($companyId, $data['service_node_id'] ?? null);
        $assignee = $this->tenantMember($companyId, $data['assigned_to'] ?? null);
        $currency = $request->user()->company->currency;
        $snapshotPrice = filled($data['price'] ?? null)
            ? (int) ($this->moneyToMinor($data['price'], $currency) ?? 0)
            : (int) ($service?->price_minor ?? 0);

        $serviceRequest = DB::transaction(function () use ($request, $data, $companyId, $service, $assignee, $guestStay, $snapshotPrice) {
            $item = ServiceRequest::create([
                ...$data,
                'public_id' => (string) Str::uuid(),
                'company_id' => $companyId,
                'guest_stay_id' => $guestStay?->id,
                'service_node_id' => $service?->id,
                'assigned_to' => $assignee?->id,
                'created_by' => $request->user()->id,
                'source' => 'manual',
                'status' => $assignee ? RequestStatus::Accepted : RequestStatus::New,
                'accepted_at' => $assignee ? now() : null,
                'price_minor' => $snapshotPrice,
                'payment_method' => $guestStay && $snapshotPrice > 0 ? 'room_charge' : null,
                'payment_status' => $guestStay && $snapshotPrice > 0 ? 'pending' : 'not_required',
            ]);
            if ($service) {
                $item->items()->create([
                    'service_node_id' => $service->id,
                    'name_snapshot' => $service->name,
                    'quantity' => 1,
                    'unit_price_minor' => $snapshotPrice,
                    'total_price_minor' => $snapshotPrice,
                    'notes' => $data['description'] ?? null,
                ]);
            }
            $item->history()->create([
                'user_id' => $request->user()->id,
                'to_status' => $item->status->value,
                'note' => 'workspace.history_manual',
                'created_at' => now(),
            ]);

            return $item;
        });

        $this->notifyCompany($request->user(), $serviceRequest, 'workspace.notification_new', 'workspace.notification_room_request', [
            'room' => $room->displayLabel(),
            'request' => $serviceRequest->title,
        ]);
        ServiceRequestChanged::dispatch($serviceRequest, 'created');
        $request->attributes->set('audit_service_request_id', $serviceRequest->id);

        return back()->with('success', __('workspace.request_added'));
    }

    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        $this->ensureTenant($request, $serviceRequest);
        $serviceRequest->load(['service', 'assignee', 'creator', 'items', 'guestSession.room', 'guestStay.room', 'history.user', 'priceAdjustments.manager']);
        $members = User::where('company_id', $request->user()->company_id)->where('is_active', true)->orderBy('name')->get();

        return view('workspace.requests.show', compact('serviceRequest', 'members'));
    }

    public function take(Request $request, ServiceRequest $serviceRequest): RedirectResponse|JsonResponse
    {
        $this->ensureTenant($request, $serviceRequest);
        $from = $serviceRequest->status;

        DB::transaction(function () use ($request, $serviceRequest, $from) {
            $serviceRequest->update([
                'assigned_to' => $request->user()->id,
                'status' => RequestStatus::Accepted,
                'accepted_at' => $serviceRequest->accepted_at ?? now(),
            ]);
            $serviceRequest->history()->create([
                'user_id' => $request->user()->id,
                'from_status' => $from->value,
                'to_status' => RequestStatus::Accepted->value,
                'note' => 'workspace.history_taken',
                'created_at' => now(),
            ]);
        });

        $this->notifyCompany($request->user(), $serviceRequest, 'workspace.notification_taken', 'workspace.notification_taken_body', [
            'actor' => $request->user()->name,
            'room' => $serviceRequest->roomDisplayLabel(),
        ]);
        $this->notifyGuest($serviceRequest);
        ServiceRequestChanged::dispatch($serviceRequest->fresh(), 'taken');

        return $this->response($request, __('workspace.request_assigned'));
    }

    public function status(Request $request, ServiceRequest $serviceRequest): RedirectResponse|JsonResponse
    {
        $this->ensureTenant($request, $serviceRequest);
        $data = $request->validate([
            'status' => ['required', Rule::enum(RequestStatus::class)],
            'assigned_to' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $to = RequestStatus::from($data['status']);
        $assignee = $this->tenantMember($request->user()->company_id, $data['assigned_to'] ?? $serviceRequest->assigned_to);

        $from = DB::transaction(function () use ($request, $serviceRequest, $data, $to, $assignee) {
            $lockedRequest = ServiceRequest::query()->lockForUpdate()->findOrFail($serviceRequest->id);
            $from = $lockedRequest->status;
            if (
                $lockedRequest->guest_stay_id
                && $to === RequestStatus::Completed
                && $from !== RequestStatus::Completed
                && ! in_array($from, [RequestStatus::Ready, RequestStatus::WaitingGuest], true)
            ) {
                throw ValidationException::withMessages(['status' => __('workspace.guest_confirmation_required')]);
            }

            $isRoomCharge = $lockedRequest->guest_stay_id
                && $lockedRequest->payment_method === 'room_charge'
                && $lockedRequest->price_minor > 0;
            $paymentStatus = match (true) {
                $isRoomCharge && $to === RequestStatus::Cancelled => 'cancelled',
                $isRoomCharge && $to === RequestStatus::Completed => 'invoiced',
                $isRoomCharge && $from === RequestStatus::Cancelled && $lockedRequest->payment_status === 'cancelled' => 'pending',
                default => $lockedRequest->payment_status,
            };

            $lockedRequest->update([
                'status' => $to,
                'assigned_to' => $assignee?->id,
                'accepted_at' => $to !== RequestStatus::New ? ($lockedRequest->accepted_at ?? now()) : $lockedRequest->accepted_at,
                'completed_at' => $to === RequestStatus::Completed ? ($lockedRequest->completed_at ?? now()) : null,
                'payment_status' => $paymentStatus,
                'clarification_requested_at' => ($from !== $to || filled($data['note'] ?? null)) ? null : $lockedRequest->clarification_requested_at,
            ]);
            $lockedRequest->history()->create([
                'user_id' => $request->user()->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'note' => $data['note'] ?? ($lockedRequest->guest_stay_id && $to === RequestStatus::Completed && $from !== RequestStatus::Completed
                    ? 'workspace.history_manager_confirmed'
                    : null),
                'created_at' => now(),
            ]);

            return $from;
        });
        $serviceRequest->refresh();

        $this->notifyCompany($request->user(), $serviceRequest, 'workspace.notification_status_changed', 'workspace.notification_status_body', [
            'room' => $serviceRequest->roomDisplayLabel(),
            'status' => $to->value,
        ]);
        if ($from !== $to) {
            $this->notifyGuest($serviceRequest);
        }
        ServiceRequestChanged::dispatch($serviceRequest->fresh(), 'status_changed');

        return $this->response($request, __('workspace.request_updated'));
    }

    public function archive(Request $request, ServiceRequest $serviceRequest): RedirectResponse|JsonResponse
    {
        $this->ensureTenant($request, $serviceRequest);
        $data = $request->validate(['archived' => ['required', 'boolean']]);
        $archived = (bool) $data['archived'];

        if ($archived !== (bool) $serviceRequest->archived_at) {
            DB::transaction(function () use ($request, $serviceRequest, $archived) {
                $serviceRequest->update(['archived_at' => $archived ? now() : null]);
                $serviceRequest->history()->create([
                    'user_id' => $request->user()->id,
                    'from_status' => $serviceRequest->status->value,
                    'to_status' => $serviceRequest->status->value,
                    'note' => $archived ? 'workspace.history_archived' : 'workspace.history_restored',
                    'created_at' => now(),
                ]);
            });

            ServiceRequestChanged::dispatch($serviceRequest->fresh(), $archived ? 'archived' : 'restored');
        }

        return $this->response($request, __($archived ? 'workspace.request_archived' : 'workspace.request_restored'));
    }

    public function adjustPrice(Request $request, ServiceRequest $serviceRequest): RedirectResponse|JsonResponse
    {
        $this->ensureTenant($request, $serviceRequest);
        $data = $request->validate([
            'service_request_item_id' => ['nullable', 'integer'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'comment' => ['required', 'string', 'max:1000'],
        ]);
        $newLinePrice = (int) ($this->moneyToMinor($data['price'], $request->user()->company->currency) ?? 0);

        $updatedRequest = DB::transaction(function () use ($request, $serviceRequest, $data, $newLinePrice): ServiceRequest {
            $lockedRequest = ServiceRequest::query()->with('items')->lockForUpdate()->findOrFail($serviceRequest->id);
            abort_unless($lockedRequest->company_id === $request->user()->company_id, 404);

            $item = null;
            if ($lockedRequest->items->isNotEmpty()) {
                $itemId = $data['service_request_item_id'] ?? ($lockedRequest->items->count() === 1 ? $lockedRequest->items->first()->id : null);
                if (! $itemId) {
                    throw ValidationException::withMessages(['service_request_item_id' => __('workspace.choose_price_service')]);
                }
                $item = $lockedRequest->items->firstWhere('id', (int) $itemId);
                abort_unless($item, 404);
            }

            $previousLinePrice = (int) ($item?->total_price_minor ?? $lockedRequest->price_minor ?? 0);
            if ($previousLinePrice === $newLinePrice) {
                throw ValidationException::withMessages(['price' => __('workspace.price_must_change')]);
            }

            if ($item) {
                $item->update([
                    'unit_price_minor' => (int) round($newLinePrice / max($item->quantity, 1)),
                    'total_price_minor' => $newLinePrice,
                ]);
                $newRequestPrice = (int) $lockedRequest->items()->sum('total_price_minor');
                $serviceName = $item->name_snapshot;
            } else {
                $newRequestPrice = $newLinePrice;
                $serviceName = $lockedRequest->title;
            }

            if ($lockedRequest->refund_status === 'partial' && (int) $lockedRequest->refund_amount_minor > $newRequestPrice) {
                throw ValidationException::withMessages(['price' => __('workspace.price_below_refund')]);
            }

            $paymentMethod = $lockedRequest->payment_method;
            if ($newRequestPrice > 0 && ! $paymentMethod && $lockedRequest->guest_stay_id) {
                $paymentMethod = 'room_charge';
            }
            $paymentStatus = match (true) {
                $lockedRequest->status === RequestStatus::Cancelled => 'cancelled',
                $newRequestPrice === 0 => 'not_required',
                $paymentMethod === 'cash' => 'paid',
                $paymentMethod === 'room_charge' && $lockedRequest->status === RequestStatus::Completed => 'invoiced',
                $paymentMethod === 'room_charge' => 'pending',
                default => $lockedRequest->payment_status,
            };

            $lockedRequest->update([
                'price_minor' => $newRequestPrice,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'refund_amount_minor' => $lockedRequest->refund_status === 'full' ? $newRequestPrice : $lockedRequest->refund_amount_minor,
            ]);
            $lockedRequest->priceAdjustments()->create([
                'service_request_item_id' => $item?->id,
                'user_id' => $request->user()->id,
                'service_name_snapshot' => $serviceName,
                'previous_price_minor' => $previousLinePrice,
                'new_price_minor' => $newLinePrice,
                'comment' => $data['comment'],
                'created_at' => now(),
            ]);
            $lockedRequest->history()->create([
                'user_id' => $request->user()->id,
                'from_status' => $lockedRequest->status->value,
                'to_status' => $lockedRequest->status->value,
                'note' => 'workspace.history_price_adjusted',
                'created_at' => now(),
            ]);

            return $lockedRequest;
        });

        ServiceRequestChanged::dispatch($updatedRequest->fresh(), 'price_adjusted');

        return $this->response($request, __('workspace.price_adjusted'));
    }

    public function refund(Request $request, ServiceRequest $serviceRequest): RedirectResponse|JsonResponse
    {
        $this->ensureTenant($request, $serviceRequest);
        $data = $request->validate([
            'refund_type' => ['required', Rule::in(['partial', 'full', 'none'])],
            'refund_amount' => ['nullable', 'required_if:refund_type,partial', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $currency = $request->user()->company->currency;
        $amount = $data['refund_type'] === 'full'
            ? (int) $serviceRequest->price_minor
            : ($data['refund_type'] === 'partial' ? (int) ($this->moneyToMinor($data['refund_amount'] ?? 0, $currency) ?? 0) : null);
        if ($amount !== null && $amount > (int) $serviceRequest->price_minor) {
            throw ValidationException::withMessages(['refund_amount' => __('workspace.refund_amount_too_high')]);
        }

        DB::transaction(function () use ($request, $serviceRequest, $data, $amount): void {
            $serviceRequest->update([
                'refund_status' => $data['refund_type'] === 'none' ? null : $data['refund_type'],
                'refund_amount_minor' => $amount,
                'refund_requested_at' => $data['refund_type'] === 'none' ? null : ($serviceRequest->refund_requested_at ?? now()),
                'refunded_at' => $data['refund_type'] === 'none' ? null : now(),
                'payment_status' => $data['refund_type'] === 'none' ? $serviceRequest->payment_status : 'refunded',
            ]);
            $serviceRequest->history()->create([
                'user_id' => $request->user()->id,
                'from_status' => $serviceRequest->status->value,
                'to_status' => $serviceRequest->status->value,
                'note' => $data['refund_type'] === 'none' ? 'workspace.history_refund_cleared' : ($data['note'] ?? 'workspace.history_refunded'),
                'created_at' => now(),
            ]);
        });

        ServiceRequestChanged::dispatch($serviceRequest->fresh(), 'refund_updated');

        return $this->response($request, __('workspace.refund_updated'));
    }

    private function ensureTenant(Request $request, ServiceRequest $item): void
    {
        abort_unless($item->company_id === $request->user()->company_id, 404);
    }

    private function tenantMember(int $companyId, mixed $id): ?User
    {
        return $id ? User::where('company_id', $companyId)->where('is_active', true)->findOrFail($id) : null;
    }

    private function tenantService(int $companyId, mixed $id): ?ServiceNode
    {
        return $id ? ServiceNode::where('company_id', $companyId)->where('type', ServiceNodeType::Service)->findOrFail($id) : null;
    }

    private function notifyCompany(User $actor, ServiceRequest $item, string $titleKey, string $bodyKey, array $params): void
    {
        User::where('company_id', $actor->company_id)->where('is_active', true)->whereKeyNot($actor->id)->get()
            ->each->notify(new WorkspaceNotification([
                'title_key' => $titleKey,
                'body_key' => $bodyKey,
                'params' => $params,
                'request_id' => $item->id,
                'url' => route('workspace.requests.show', $item),
                'icon' => 'bi-bell',
                'email' => true,
                'push' => true,
            ]));
    }

    private function notifyGuest(ServiceRequest $item): void
    {
        if (! $item->guest_stay_id) {
            return;
        }

        $item->loadMissing(['company', 'service', 'guestStay.sessions.stay']);
        $sessions = $item->guestStay->sessions
            ->whereNull('revoked_at')
            ->filter(fn ($session) => $session->expires_at?->isFuture())
            ->values();
        $emailSession = $sessions->firstWhere('id', $item->guest_session_id) ?? $sessions->first();

        foreach ($sessions as $session) {
            $session->notify(new GuestRequestStatusNotification($item, $session->is($emailSession)));
        }
    }

    private function moneyToMinor(mixed $value, string $currency): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round((float) $value * ($currency === 'IDR' ? 1 : 100));
    }

    private function response(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $message]) : back()->with('success', $message);
    }
}
