<?php

namespace App\Http\Controllers\Guest;

use App\Actions\CreateGuestOrder;
use App\Enums\RequestStatus;
use App\Events\ServiceRequestChanged;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestSession;
use App\Models\ServiceNode;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function create(Company $company, ServiceNode $serviceNode): View
    {
        $this->ensureService($company, $serviceNode);
        $company->load('backgroundSet.images');
        $serviceNode->load('backgroundImage');

        return view('guest.orders.create', compact('company', 'serviceNode'));
    }

    public function store(Request $request, Company $company, ServiceNode $serviceNode, CreateGuestOrder $action): RedirectResponse
    {
        $this->ensureService($company, $serviceNode);
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'payment_method' => [$serviceNode->price_minor ? 'required' : 'nullable', Rule::in(['room_charge', 'cash'])],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
        /** @var GuestSession $stay */
        $stay = app('guestStay');
        $order = $action->handle($stay, $serviceNode, (int) $data['quantity'], $data['payment_method'] ?? 'room_charge', $data['comment'] ?? null);

        return redirect()->route('guest.orders.show', [$company, $order])->with('guest_success', __('guest.order_sent'));
    }

    public function index(Company $company): View
    {
        /** @var GuestSession $stay */
        $stay = app('guestStay');
        $orders = ServiceRequest::where('guest_stay_id', $stay->guest_stay_id)->with('items.service')->latest()->get();

        return view('guest.orders.index', compact('company', 'orders'));
    }

    public function statuses(Company $company): JsonResponse
    {
        /** @var GuestSession $stay */
        $stay = app('guestStay');
        $orders = ServiceRequest::where('guest_stay_id', $stay->guest_stay_id)
            ->with('items.service')
            ->latest()
            ->get();
        $money = app(\App\Support\Money::class);

        return response()->json([
            'orders' => $orders->map(fn (ServiceRequest $order) => [
                'id' => $order->public_id,
                'status' => $order->status->value,
                'payment_status' => $order->payment_status,
                'updated_at' => $order->updated_at?->toISOString(),
                'status_label' => __('guest.status.'.$order->status->value),
                'status_hint' => $this->statusHint($order->status),
                'hero_icon' => in_array($order->status, [RequestStatus::Ready, RequestStatus::Completed], true)
                    ? 'bi-check-lg'
                    : ($order->status === RequestStatus::Cancelled ? 'bi-x-lg' : 'bi-bell'),
                'requires_confirmation' => in_array($order->status, [RequestStatus::Ready, RequestStatus::WaitingGuest], true),
                'show_bill' => $order->status === RequestStatus::Completed && $order->payment_status === 'invoiced',
                'card_html' => view('guest.orders._card', [
                    'company' => $company,
                    'order' => $order,
                    'money' => $money,
                ])->render(),
                'detail_progress_html' => view('guest.orders._progress', ['order' => $order])->render(),
            ])->values(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function show(Company $company, ServiceRequest $serviceRequest): View
    {
        /** @var GuestSession $stay */
        $stay = app('guestStay');
        $this->ensureOrder($company, $serviceRequest, $stay);
        $serviceRequest->load(['items.service', 'history']);

        return view('guest.orders.show', compact('company', 'serviceRequest'));
    }

    public function confirm(Company $company, ServiceRequest $serviceRequest): RedirectResponse
    {
        /** @var GuestSession $stay */
        $stay = app('guestStay');
        $this->ensureOrder($company, $serviceRequest, $stay);

        [$order, $confirmed] = DB::transaction(function () use ($company, $serviceRequest, $stay) {
            $order = ServiceRequest::query()->lockForUpdate()->findOrFail($serviceRequest->id);
            $this->ensureOrder($company, $order, $stay);

            if ($order->status === RequestStatus::Completed) {
                return [$order, false];
            }

            abort_unless(in_array($order->status, [RequestStatus::Ready, RequestStatus::WaitingGuest], true), 409);
            $from = $order->status;
            $paymentStatus = $order->payment_method === 'room_charge' && $order->price_minor > 0
                ? 'invoiced'
                : $order->payment_status;

            $order->update([
                'status' => RequestStatus::Completed,
                'completed_at' => now(),
                'payment_status' => $paymentStatus,
            ]);
            $order->history()->create([
                'from_status' => $from->value,
                'to_status' => RequestStatus::Completed->value,
                'note' => 'workspace.history_guest_confirmed',
                'created_at' => now(),
            ]);

            return [$order, true];
        });

        if ($confirmed) {
            User::where('company_id', $company->id)->where('is_active', true)->get()->each->notify(
                new WorkspaceNotification([
                    'title_key' => 'workspace.notification_guest_confirmed',
                    'body_key' => 'workspace.notification_guest_confirmed_body',
                    'params' => ['room' => $order->room_number, 'request' => $order->title],
                    'request_id' => $order->id,
                    'url' => route('workspace.requests.show', $order),
                    'icon' => 'bi-person-check',
                    'email' => true,
                    'push' => true,
                ])
            );
            ServiceRequestChanged::dispatch($order->fresh(), 'guest_confirmed');
        }

        if ($order->payment_status === 'invoiced') {
            return redirect()->route('guest.bill', $company)->with('guest_success', __('guest.confirmed_and_billed'));
        }

        return redirect()->route('guest.orders.show', [$company, $order])->with('guest_success', __('guest.order_confirmed'));
    }

    public function bill(Company $company): View
    {
        /** @var GuestSession $stay */
        $stay = app('guestStay');
        $orders = ServiceRequest::where('guest_stay_id', $stay->guest_stay_id)
            ->where('payment_method', 'room_charge')
            ->where('payment_status', 'invoiced')
            ->where('status', '!=', RequestStatus::Cancelled)
            ->with('items.service')
            ->oldest()
            ->get();
        $total = (int) $orders->sum('price_minor');

        return view('guest.bill', compact('company', 'stay', 'orders', 'total'));
    }

    private function ensureService(Company $company, ServiceNode $service): void
    {
        abort_unless($service->company_id === $company->id && $service->is_active && ! $service->isCategory(), 404);
    }

    private function ensureOrder(Company $company, ServiceRequest $serviceRequest, GuestSession $stay): void
    {
        abort_unless($serviceRequest->guest_stay_id === $stay->guest_stay_id && $serviceRequest->company_id === $company->id, 404);
    }

    private function statusHint(RequestStatus $status): string
    {
        return match ($status) {
            RequestStatus::New => __('guest.team_received'),
            RequestStatus::Accepted => __('guest.accepted_hint'),
            RequestStatus::InProgress => __('guest.progress_hint'),
            RequestStatus::WaitingGuest => __('guest.waiting_hint'),
            RequestStatus::Ready => __('guest.ready_hint'),
            RequestStatus::Completed => __('guest.completed_hint'),
            RequestStatus::Cancelled => __('guest.cancelled_hint'),
        };
    }
}
