<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Enums\ServiceNodeType;
use App\Events\ServiceRequestChanged;
use App\Http\Controllers\Controller;
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
        $query = ServiceRequest::where('company_id', $companyId)
            ->with(['assignee', 'service'])
            ->when($request->boolean('mine'), fn ($query) => $query->where('assigned_to', $request->user()->id))
            ->when($request->string('priority')->toString(), fn ($query, $priority) => $query->where('priority', $priority))
            ->latest();

        $requests = (clone $query)->whereNull('archived_at')->get()->groupBy(fn (ServiceRequest $item) => $item->status->value);
        $archivedCount = (clone $query)->whereNotNull('archived_at')->count();
        $archivedRequests = $request->boolean('archive')
            ? (clone $query)->whereNotNull('archived_at')->get()
            : collect();
        $members = User::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $services = ServiceNode::where('company_id', $companyId)->where('type', ServiceNodeType::Service)->where('is_active', true)->orderBy('name')->get();

        return view('workspace.requests.index', compact('requests', 'archivedRequests', 'archivedCount', 'members', 'services'));
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
        $service = $this->tenantService($companyId, $data['service_node_id'] ?? null);
        $assignee = $this->tenantMember($companyId, $data['assigned_to'] ?? null);

        $serviceRequest = DB::transaction(function () use ($request, $data, $companyId, $service, $assignee) {
            $item = ServiceRequest::create([
                ...$data,
                'public_id' => (string) Str::uuid(),
                'company_id' => $companyId,
                'service_node_id' => $service?->id,
                'assigned_to' => $assignee?->id,
                'created_by' => $request->user()->id,
                'source' => 'manual',
                'status' => $assignee ? RequestStatus::Accepted : RequestStatus::New,
                'accepted_at' => $assignee ? now() : null,
                'price_minor' => $this->moneyToMinor($data['price'] ?? null, $request->user()->company->currency),
            ]);
            $item->history()->create([
                'user_id' => $request->user()->id,
                'to_status' => $item->status->value,
                'note' => 'workspace.history_manual',
                'created_at' => now(),
            ]);

            return $item;
        });

        $this->notifyCompany($request->user(), $serviceRequest, 'workspace.notification_new', 'workspace.notification_room_request', [
            'room' => $serviceRequest->room_number,
            'request' => $serviceRequest->title,
        ]);
        ServiceRequestChanged::dispatch($serviceRequest, 'created');

        return back()->with('success', __('workspace.request_added'));
    }

    public function show(Request $request, ServiceRequest $serviceRequest): View
    {
        $this->ensureTenant($request, $serviceRequest);
        $serviceRequest->load(['service', 'assignee', 'creator', 'items', 'guestSession.room', 'history.user']);
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
            'room' => $serviceRequest->room_number,
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
            'room' => $serviceRequest->room_number,
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
