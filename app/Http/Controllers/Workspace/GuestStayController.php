<?php

namespace App\Http\Controllers\Workspace;

use App\Enums\GuestStayStatus;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Mail\FinalBillMail;
use App\Models\GuestStay;
use App\Models\Room;
use App\Support\GuestColor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GuestStayController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;
        $companyId = $company->id;
        $rooms = Room::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->orderBy('number')->get();
        $stays = GuestStay::where('company_id', $companyId)->with(['room', 'requests'])
            ->whereIn('status', [GuestStayStatus::CheckedIn, GuestStayStatus::Upcoming])
            ->latest('check_in_at')->get();

        $filters = $request->validate([
            'calendar_month' => ['nullable', 'date_format:Y-m'],
            'room_id' => ['nullable', 'integer'],
            'available_from' => ['nullable', 'date_format:Y-m-d', 'required_with:available_to'],
            'available_to' => ['nullable', 'date_format:Y-m-d', 'required_with:available_from', 'after:available_from'],
        ]);

        $selectedRoomId = isset($filters['room_id']) && $rooms->contains('id', (int) $filters['room_id'])
            ? (int) $filters['room_id']
            : null;
        $calendarStart = isset($filters['calendar_month'])
            ? Carbon::createFromFormat('Y-m-d', $filters['calendar_month'].'-01', $company->timezone)->startOfDay()
            : now($company->timezone)->startOfMonth();
        $calendarEnd = $calendarStart->copy()->addMonthsNoOverflow(3)->startOfMonth();

        $calendarStays = GuestStay::where('company_id', $companyId)
            ->where('check_in_at', '<', $calendarEnd->copy()->utc())
            ->where('check_out_at', '>', $calendarStart->copy()->utc())
            ->where('status', '!=', GuestStayStatus::Cancelled)
            ->get();
        $calendar = $this->buildCalendar($rooms, $calendarStays, $calendarStart, $calendarEnd, $company->timezone);

        $availabilityRequested = isset($filters['available_from'], $filters['available_to']);
        $availableRooms = collect();
        if ($availabilityRequested) {
            $availableFrom = Carbon::createFromFormat('Y-m-d', $filters['available_from'], $company->timezone)->startOfDay()->utc();
            $availableTo = Carbon::createFromFormat('Y-m-d', $filters['available_to'], $company->timezone)->startOfDay()->utc();
            $availableRooms = $this->findAvailableRooms($companyId, $rooms, $availableFrom, $availableTo, $company->timezone);
        }

        return view('workspace.stays.index', compact(
            'rooms', 'stays', 'calendar', 'calendarStart', 'selectedRoomId',
            'availabilityRequested', 'availableRooms',
        ));
    }

    public function availability(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        $filters = $request->validate([
            'available_from' => ['required', 'date_format:Y-m-d'],
            'available_to' => ['required', 'date_format:Y-m-d', 'after:available_from'],
        ]);
        $rooms = Room::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('number')
            ->get();
        $availableFrom = Carbon::createFromFormat('Y-m-d', $filters['available_from'], $company->timezone)->startOfDay()->utc();
        $availableTo = Carbon::createFromFormat('Y-m-d', $filters['available_to'], $company->timezone)->startOfDay()->utc();
        $availableRooms = $this->findAvailableRooms($company->id, $rooms, $availableFrom, $availableTo, $company->timezone);

        return response()->json([
            'count_label' => trans_choice('workspace.available_villas_count', $availableRooms->count(), ['count' => $availableRooms->count()]),
            'period_label' => Carbon::parse($filters['available_from'])->format('d.m.Y').' — '.Carbon::parse($filters['available_to'])->format('d.m.Y'),
            'empty_label' => __('workspace.no_available_villas'),
            'rooms' => $availableRooms->map(fn (Room $room): array => [
                'id' => $room->id,
                'label' => $room->displayLabel(),
            ])->values(),
        ]);
    }

    private function findAvailableRooms(int $companyId, Collection $rooms, Carbon $availableFrom, Carbon $availableTo, string $timezone): Collection
    {
        $busyRoomIds = GuestStay::where('company_id', $companyId)
            ->whereIn('status', [GuestStayStatus::Upcoming, GuestStayStatus::CheckedIn])
            ->where('check_in_at', '<', $availableTo->copy()->addDay())
            ->where('check_out_at', '>', $availableFrom->copy()->subDay())
            ->get()
            ->filter(function (GuestStay $stay) use ($availableFrom, $availableTo, $timezone): bool {
                $stayFrom = $stay->check_in_at->copy()->setTimezone($timezone)->startOfDay()->utc();
                $stayTo = $stay->check_out_at->copy()->setTimezone($timezone)->startOfDay()->utc();

                return $stayFrom->lt($availableTo) && $stayTo->gt($availableFrom);
            })
            ->pluck('room_id');

        return $rooms->whereNotIn('id', $busyRoomIds)->values();
    }

    private function buildCalendar(Collection $rooms, Collection $stays, Carbon $start, Carbon $end, string $timezone): array
    {
        $days = collect();
        for ($day = $start->copy(); $day->lt($end); $day->addDay()) {
            $busyRoomIds = $stays->filter(function (GuestStay $stay) use ($day, $timezone): bool {
                $effectiveEnd = $stay->checked_out_at && $stay->checked_out_at->lt($stay->check_out_at)
                    ? $stay->checked_out_at
                    : $stay->check_out_at;
                $stayFrom = $stay->check_in_at->copy()->setTimezone($timezone)->startOfDay();
                $stayTo = $effectiveEnd->copy()->setTimezone($timezone)->startOfDay();

                return $stayFrom->lte($day) && $stayTo->gt($day);
            })->pluck('room_id')->unique();
            $percent = $rooms->isEmpty() ? 0 : (int) round(($busyRoomIds->count() / $rooms->count()) * 100);

            $days->push([
                'date' => $day->copy(),
                'key' => $day->format('Y-m-d'),
                'percent' => $percent,
                'level' => $percent === 0 ? 0 : min(5, (int) ceil($percent / 20)),
                'busy_count' => $busyRoomIds->count(),
            ]);
        }

        $roomRows = $rooms->map(function (Room $room) use ($days, $stays, $timezone): array {
            $roomStays = $stays->where('room_id', $room->id);
            $cells = $days->map(function (array $day) use ($roomStays, $timezone): array {
                $stay = $roomStays->first(function (GuestStay $stay) use ($day, $timezone): bool {
                    $effectiveEnd = $stay->checked_out_at && $stay->checked_out_at->lt($stay->check_out_at)
                        ? $stay->checked_out_at
                        : $stay->check_out_at;
                    $stayFrom = $stay->check_in_at->copy()->setTimezone($timezone)->startOfDay();
                    $stayTo = $effectiveEnd->copy()->setTimezone($timezone)->startOfDay();

                    return $stayFrom->lte($day['date']) && $stayTo->gt($day['date']);
                });

                return [
                    'occupied' => (bool) $stay,
                    'stay_id' => $stay?->id,
                    'guest' => $stay?->guest_name,
                    'guest_color' => $stay ? GuestColor::index($stay->guest_name) : null,
                    'label' => $stay
                        ? ($stay->guest_name.' · '.$stay->check_in_at->copy()->setTimezone($timezone)->format('d.m').'–'.$stay->check_out_at->copy()->setTimezone($timezone)->format('d.m'))
                        : null,
                ];
            });

            return ['room' => $room, 'cells' => $cells];
        });

        $months = $days->groupBy(fn (array $day) => $day['date']->format('Y-m'))->map(fn (Collection $month) => [
            'date' => $month->first()['date'],
            'days' => $month->count(),
        ])->values();

        return ['days' => $days, 'months' => $months, 'rooms' => $roomRows];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer'],
            'guest_name' => ['required', 'string', 'max:160'],
            'guest_email' => ['nullable', 'email', 'max:190'],
            'emergency_phone' => ['nullable', 'string', 'max:40'],
            'check_in_at' => ['required', 'date'],
            'nights' => ['required', 'integer', 'min:1', 'max:90'],
            'access_pin' => ['nullable', 'digits_between:4,8'],
        ]);
        $company = $request->user()->company;
        $room = Room::where('company_id', $company->id)->where('is_active', true)->findOrFail($data['room_id']);
        $checkIn = Carbon::parse($data['check_in_at'], $company->timezone)->utc();
        $checkOut = $checkIn->copy()->addDays((int) $data['nights'])->setTimezone($company->timezone)->setTime(12, 0)->utc();

        $overlap = GuestStay::where('company_id', $company->id)->where('room_id', $room->id)
            ->whereNotIn('status', [GuestStayStatus::CheckedOut, GuestStayStatus::Cancelled])
            ->where('check_in_at', '<', $checkOut)
            ->where('check_out_at', '>', $checkIn)
            ->exists();
        if ($overlap) {
            return back()->withInput()->withErrors(['room_id' => __('workspace.stay_overlap')]);
        }

        $pin = $data['access_pin'] ?: (string) random_int(1000, 9999);
        GuestStay::create([
            'public_id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'room_id' => $room->id,
            'guest_name' => $data['guest_name'],
            'guest_email' => $data['guest_email'] ?? null,
            'emergency_phone' => $data['emergency_phone'] ?? null,
            'check_in_at' => $checkIn,
            'check_out_at' => $checkOut,
            'nights' => (int) $data['nights'],
            'access_pin_hash' => Hash::make($pin),
            'access_pin' => $pin,
            'status' => $checkIn->lte(now()) ? GuestStayStatus::CheckedIn : GuestStayStatus::Upcoming,
        ]);

        return back()->with('success', __('workspace.stay_created'))->with('stay_pin', $pin)->with('stay_room', $room->displayLabel());
    }

    public function show(Request $request, GuestStay $guestStay): View
    {
        $this->ensureTenant($request, $guestStay);
        $guestStay->load(['room', 'requests.items.service', 'requests.assignee']);
        $orders = $guestStay->requests->sortByDesc('created_at')->values();
        $billableOrders = $orders->filter(fn ($order) => $order->price_minor > 0
            && $order->status !== RequestStatus::Cancelled
            && ! $order->hasRefund()
            && in_array($order->payment_status, ['pending', 'paid', 'invoiced'], true));

        return view('workspace.stays.show', compact('guestStay', 'orders', 'billableOrders'));
    }

    public function update(Request $request, GuestStay $guestStay): RedirectResponse
    {
        $this->ensureTenant($request, $guestStay);
        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:160'],
            'guest_email' => ['nullable', 'email', 'max:190'],
            'emergency_phone' => ['nullable', 'string', 'max:40'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $guestStay->update($data);

        return back()->with('success', __('workspace.client_card_saved'));
    }

    public function bill(Request $request, GuestStay $guestStay): View
    {
        $this->ensureTenant($request, $guestStay);
        $data = $request->validate([
            'selection' => ['nullable', 'boolean'],
            'order_ids' => ['nullable', 'array'],
            'order_ids.*' => ['integer', 'distinct'],
        ]);
        $selectedIds = collect($data['order_ids'] ?? [])->map(fn ($id) => (int) $id);
        $orders = $guestStay->requests()
            ->where('price_minor', '>', 0)
            ->where('status', '!=', RequestStatus::Cancelled)
            ->whereNull('refund_status')
            ->whereIn('payment_status', ['pending', 'paid', 'invoiced'])
            ->when(($data['selection'] ?? false) && $selectedIds->isEmpty(), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($selectedIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $selectedIds))
            ->when(! ($data['selection'] ?? false) && $selectedIds->isEmpty(), fn ($query) => $query->whereIn('payment_status', ['pending', 'invoiced']))
            ->with('items.service')
            ->oldest()
            ->get();
        $total = (int) $orders->sum('price_minor');

        return view('workspace.stays.bill', compact('guestStay', 'orders', 'total'));
    }

    public function emailBill(Request $request, GuestStay $guestStay): RedirectResponse
    {
        $this->ensureTenant($request, $guestStay);
        abort_unless(filled($guestStay->guest_email), 422);
        $data = $request->validate([
            'order_ids' => ['nullable', 'array'],
            'order_ids.*' => ['integer', 'distinct'],
            'additional_description' => ['nullable', 'string', 'max:10000'],
        ]);
        $selectedIds = collect($data['order_ids'] ?? [])->map(fn ($id) => (int) $id);
        $orders = $guestStay->requests()
            ->where('price_minor', '>', 0)
            ->where('status', '!=', RequestStatus::Cancelled)
            ->whereNull('refund_status')
            ->whereIn('payment_status', ['pending', 'invoiced'])
            ->when($selectedIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $selectedIds))
            ->with('items.service')->oldest()->get();
        $guestStay->loadMissing(['room', 'company']);
        $total = (int) $orders->sum('price_minor');
        $pdf = Pdf::loadView('workspace.stays.bill-pdf', [
            'guestStay' => $guestStay,
            'orders' => $orders,
            'total' => $total,
            'company' => $guestStay->company,
        ])->setPaper('a4');

        Mail::to($guestStay->guest_email)->send(new FinalBillMail(
            $guestStay,
            $pdf->output(),
            $data['additional_description'] ?? null,
        ));

        return back()->with('success', __('workspace.bill_emailed'));
    }

    public function archive(Request $request): View
    {
        $company = $request->user()->company;
        $filters = $request->validate([
            'calendar_month' => ['nullable', 'date_format:Y-m'],
            'room_id' => ['nullable', 'integer'],
        ]);
        $rooms = Room::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->orderBy('number')->get();
        $stays = GuestStay::where('company_id', $company->id)
            ->whereIn('status', [GuestStayStatus::CheckedOut, GuestStayStatus::Cancelled])
            ->with(['room', 'requests'])->orderByDesc('check_in_at')->paginate(50)->withQueryString();
        $selectedRoomId = isset($filters['room_id']) && $rooms->contains('id', (int) $filters['room_id']) ? (int) $filters['room_id'] : null;
        $calendarStart = isset($filters['calendar_month'])
            ? Carbon::createFromFormat('Y-m-d', $filters['calendar_month'].'-01', $company->timezone)->startOfDay()
            : now($company->timezone)->subMonthsNoOverflow(2)->startOfMonth();
        $calendarEnd = $calendarStart->copy()->addMonthsNoOverflow(3)->startOfMonth();
        $calendarStays = GuestStay::where('company_id', $company->id)
            ->where('check_in_at', '<', $calendarEnd->copy()->utc())
            ->where('check_out_at', '>', $calendarStart->copy()->utc())
            ->whereIn('status', [GuestStayStatus::CheckedOut, GuestStayStatus::Cancelled])
            ->get();
        $calendar = $this->buildCalendar($rooms, $calendarStays, $calendarStart, $calendarEnd, $company->timezone);

        return view('workspace.stays.archive', compact('rooms', 'stays', 'calendar', 'calendarStart', 'selectedRoomId'));
    }

    public function extend(Request $request, GuestStay $guestStay): RedirectResponse
    {
        $this->ensureTenant($request, $guestStay);
        abort_unless(in_array($guestStay->status, [GuestStayStatus::Upcoming, GuestStayStatus::CheckedIn], true), 422);
        $data = $request->validate(['extra_nights' => ['required', 'integer', 'min:1', 'max:30']]);
        $extraNights = (int) $data['extra_nights'];
        $newCheckout = $guestStay->check_out_at->copy()->addDays($extraNights);

        $overlap = GuestStay::where('company_id', $guestStay->company_id)->where('room_id', $guestStay->room_id)
            ->whereKeyNot($guestStay->id)
            ->whereNotIn('status', [GuestStayStatus::CheckedOut, GuestStayStatus::Cancelled])
            ->where('check_in_at', '<', $newCheckout)
            ->where('check_out_at', '>', $guestStay->check_out_at)
            ->exists();
        if ($overlap) {
            return back()->withErrors(['extra_nights' => __('workspace.stay_overlap')]);
        }

        DB::transaction(function () use ($guestStay, $extraNights, $newCheckout): void {
            $guestStay->update(['nights' => $guestStay->nights + $extraNights, 'check_out_at' => $newCheckout]);
            $guestStay->sessions()->whereNull('revoked_at')->update(['expires_at' => $newCheckout]);
        });

        return back()->with('success', __('workspace.stay_extended'));
    }

    public function checkout(Request $request, GuestStay $guestStay): RedirectResponse
    {
        $this->ensureTenant($request, $guestStay);
        abort_unless(in_array($guestStay->status, [GuestStayStatus::Upcoming, GuestStayStatus::CheckedIn], true), 422);

        DB::transaction(function () use ($guestStay): void {
            $guestStay->update(['status' => GuestStayStatus::CheckedOut, 'checked_out_at' => now()]);
            $guestStay->revokeSessions();
        });

        return back()->with('success', __('workspace.stay_checked_out'));
    }

    public function updatePin(Request $request, GuestStay $guestStay): RedirectResponse
    {
        $this->ensureTenant($request, $guestStay);
        abort_unless(in_array($guestStay->status, [GuestStayStatus::Upcoming, GuestStayStatus::CheckedIn], true), 422);

        $data = $request->validate(['access_pin' => ['nullable', 'digits_between:4,8']]);
        $pin = $data['access_pin'] ?: (string) random_int(1000, 9999);

        DB::transaction(function () use ($guestStay, $pin): void {
            $guestStay->update([
                'access_pin_hash' => Hash::make($pin),
                'access_pin' => $pin,
            ]);
            $guestStay->revokeSessions();
        });

        return back()->with('success', __('workspace.pin_updated'))
            ->with('stay_pin', $pin)
            ->with('stay_room', $guestStay->room->displayLabel());
    }

    private function ensureTenant(Request $request, GuestStay $stay): void
    {
        abort_unless($stay->company_id === $request->user()->company_id, 404);
    }
}
