<?php

namespace App\Http\Controllers\Guest;

use App\Actions\CreateGuestOrder;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GuestSession;
use App\Models\ServiceNode;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function show(Company $company, ServiceRequest $serviceRequest): View
    {
        /** @var GuestSession $stay */
        $stay = app('guestStay');
        abort_unless($serviceRequest->guest_stay_id === $stay->guest_stay_id && $serviceRequest->company_id === $company->id, 404);
        $serviceRequest->load(['items.service', 'history']);

        return view('guest.orders.show', compact('company', 'serviceRequest'));
    }

    public function bill(Company $company): View
    {
        /** @var GuestSession $stay */
        $stay = app('guestStay');
        $orders = ServiceRequest::where('guest_stay_id', $stay->guest_stay_id)->with('items.service')->oldest()->get();
        $total = (int) $orders->reject(fn (ServiceRequest $order) => $order->status === \App\Enums\RequestStatus::Cancelled)->sum('price_minor');

        return view('guest.bill', compact('company', 'stay', 'orders', 'total'));
    }

    private function ensureService(Company $company, ServiceNode $service): void
    {
        abort_unless($service->company_id === $company->id && $service->is_active && ! $service->isCategory(), 404);
    }
}
