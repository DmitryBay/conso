@extends('layouts.workspace')
@section('title', __('workspace.stays'))
@section('content')
@php
    $active = $stays->where('status', \App\Enums\GuestStayStatus::CheckedIn);
    $upcoming = $stays->where('status', \App\Enums\GuestStayStatus::Upcoming);
    $completed = $stays->where('status', \App\Enums\GuestStayStatus::CheckedOut);
    $money = app(\App\Support\Money::class);
@endphp
<div class="d-flex align-items-end justify-content-between gap-3 mb-4"><div><div class="eyebrow">{{ __('workspace.stay_control') }}</div><h1 class="page-title">{{ __('workspace.stays') }}</h1><p class="page-subtitle">{{ __('workspace.stays_intro') }}</p></div><div class="page-actions"><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newStayModal"><i class="bi bi-plus-lg me-sm-2"></i><span>{{ __('workspace.new_stay') }}</span></button></div></div>

@if(session('stay_pin'))<div class="stay-pin-card mb-4"><span><i class="bi bi-key"></i></span><div><small>{{ __('workspace.guest_access_pin') }}</small><strong>{{ session('stay_pin') }}</strong><p>{{ __('workspace.pin_for_room',['room'=>session('stay_room')]) }}</p></div><div class="ms-auto text-end"><small>{{ __('workspace.show_once') }}</small></div></div>@endif

<section class="surface-card occupancy-card mb-4" id="occupancyCalendar">
    <div class="occupancy-toolbar">
        <div>
            <div class="eyebrow">{{ __('workspace.occupancy_planning') }}</div>
            <h2 class="section-title mt-1">{{ __('workspace.occupancy_calendar') }}</h2>
            <p>{{ __('workspace.occupancy_calendar_intro') }}</p>
        </div>
        <form class="occupancy-room-filter" method="GET" action="{{ route('workspace.stays.index') }}#occupancyCalendar">
            <input type="hidden" name="calendar_month" value="{{ $calendarStart->format('Y-m') }}">
            @if(request('available_from'))<input type="hidden" name="available_from" value="{{ request('available_from') }}">@endif
            @if(request('available_to'))<input type="hidden" name="available_to" value="{{ request('available_to') }}">@endif
            <label class="form-label" for="calendarRoom">{{ __('workspace.filter_by_room') }}</label>
            <select class="form-select form-select-sm" id="calendarRoom" name="room_id" onchange="this.form.submit()">
                <option value="">{{ __('workspace.all_villas') }}</option>
                @foreach($rooms as $room)<option value="{{ $room->id }}" @selected($selectedRoomId === $room->id)>{{ $room->displayLabel() }}</option>@endforeach
            </select>
        </form>
    </div>

    <div class="occupancy-nav">
        <a class="btn btn-light btn-sm" href="{{ request()->fullUrlWithQuery(['calendar_month'=>$calendarStart->copy()->subMonthsNoOverflow(3)->format('Y-m')]) }}#occupancyCalendar" aria-label="{{ __('workspace.previous_three_months') }}"><i class="bi bi-chevron-left"></i></a>
        <strong>{{ $calendarStart->locale(app()->getLocale())->isoFormat('MMMM YYYY') }} — {{ $calendarStart->copy()->addMonthsNoOverflow(2)->locale(app()->getLocale())->isoFormat('MMMM YYYY') }}</strong>
        <a class="btn btn-light btn-sm" href="{{ request()->fullUrlWithQuery(['calendar_month'=>$calendarStart->copy()->addMonthsNoOverflow(3)->format('Y-m')]) }}#occupancyCalendar" aria-label="{{ __('workspace.next_three_months') }}"><i class="bi bi-chevron-right"></i></a>
        <div class="occupancy-scroll-actions ms-auto">
            <button class="btn btn-light btn-sm" type="button" data-calendar-scroll="-1" aria-label="{{ __('workspace.scroll_left') }}"><i class="bi bi-arrow-left"></i></button>
            <button class="btn btn-light btn-sm" type="button" data-calendar-scroll="1" aria-label="{{ __('workspace.scroll_right') }}"><i class="bi bi-arrow-right"></i></button>
        </div>
    </div>

    <div class="occupancy-scroll" data-occupancy-scroll>
        <div class="occupancy-grid" style="--calendar-days:{{ $calendar['days']->count() }}">
            <div class="occupancy-month-row">
                <div class="occupancy-corner">{{ __('workspace.villa') }}</div>
                @foreach($calendar['months'] as $month)<div class="occupancy-month" style="grid-column:span {{ $month['days'] }}">{{ $month['date']->locale(app()->getLocale())->isoFormat('MMMM YYYY') }}</div>@endforeach
            </div>
            <div class="occupancy-day-row">
                <div class="occupancy-row-label"><span>{{ __('workspace.daily_load') }}</span><small>{{ __('workspace.occupied_count') }}</small></div>
                @foreach($calendar['days'] as $day)
                    <div class="occupancy-day-head {{ $day['date']->isWeekend() ? 'is-weekend' : '' }} {{ $day['date']->isToday() ? 'is-today' : '' }}" title="{{ $day['date']->format('d.m.Y') }} · {{ $day['percent'] }}%">
                        <small>{{ mb_substr($day['date']->locale(app()->getLocale())->isoFormat('dd'),0,2) }}</small><strong>{{ $day['date']->format('d') }}</strong>
                        <span class="load-dot load-level-{{ $day['level'] }}"></span>
                    </div>
                @endforeach
            </div>
            @forelse($calendar['rooms'] as $row)
                @if(!$selectedRoomId || $selectedRoomId === $row['room']->id)
                <div class="occupancy-room-row">
                    <div class="occupancy-row-label"><strong>{{ $row['room']->displayLabel() }}</strong></div>
                    @foreach($row['cells'] as $index => $cell)
                        @php($day=$calendar['days'][$index])
                        <{{ $cell['occupied'] ? 'a' : 'div' }} class="occupancy-cell {{ $cell['occupied'] ? 'is-occupied' : 'is-free' }} {{ $day['date']->isWeekend() ? 'is-weekend' : '' }} {{ $day['date']->isToday() ? 'is-today' : '' }}" @if($cell['occupied']) href="{{ route('workspace.stays.show',$cell['stay_id']) }}" @endif title="{{ $cell['label'] ?: __('workspace.free_on_date',['date'=>$day['date']->format('d.m.Y')]) }}">
                            @if($cell['occupied'])<span>{{ mb_strtoupper(mb_substr($cell['guest'] ?: '•',0,1)) }}</span>@endif
                        </{{ $cell['occupied'] ? 'a' : 'div' }}>
                    @endforeach
                </div>
                @endif
            @empty
                <div class="occupancy-empty">{{ __('workspace.no_rooms_hint') }}</div>
            @endforelse
        </div>
    </div>
    <div class="occupancy-legend">
        <span class="legend-free"><i></i>{{ __('workspace.available') }}</span>
        @foreach([20,40,60,80,100] as $index => $percent)<span><i class="load-level-{{ $index+1 }}"></i>{{ $percent }}%</span>@endforeach
        <small>{{ __('workspace.load_legend_hint') }}</small>
    </div>
</section>

<section class="surface-card availability-card mb-4">
    <form id="availabilityForm" method="GET" action="{{ route('workspace.stays.index') }}" data-endpoint="{{ route('workspace.stays.availability') }}">
        <input type="hidden" name="calendar_month" value="{{ $calendarStart->format('Y-m') }}">
        @if($selectedRoomId)<input type="hidden" name="room_id" value="{{ $selectedRoomId }}">@endif
        <div><div class="eyebrow">{{ __('workspace.availability') }}</div><h2 class="section-title mt-1">{{ __('workspace.find_free_villas') }}</h2><p>{{ __('workspace.find_free_villas_intro') }}</p></div>
        <div><label class="form-label" for="availableFrom">{{ __('workspace.arrival_date') }}</label><input class="form-control" id="availableFrom" type="date" name="available_from" value="{{ request('available_from',now($currentCompany->timezone)->format('Y-m-d')) }}" required></div>
        <div><label class="form-label" for="availableTo">{{ __('workspace.departure_date') }}</label><input class="form-control" id="availableTo" type="date" name="available_to" value="{{ request('available_to',now($currentCompany->timezone)->addDay()->format('Y-m-d')) }}" required></div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-search me-2"></i><span>{{ __('workspace.show_free_villas') }}</span></button>
    </form>
    <div class="availability-results" id="availabilityResults" aria-live="polite" @if(!$availabilityRequested) hidden @endif>
        <div><strong data-availability-count>@if($availabilityRequested){{ trans_choice('workspace.available_villas_count',$availableRooms->count(),['count'=>$availableRooms->count()]) }}@endif</strong><small data-availability-period>@if($availabilityRequested){{ \Illuminate\Support\Carbon::parse(request('available_from'))->format('d.m.Y') }} — {{ \Illuminate\Support\Carbon::parse(request('available_to'))->format('d.m.Y') }}@endif</small></div>
        <div class="available-villa-list" data-availability-list>@if($availabilityRequested)@forelse($availableRooms as $room)<button type="button" class="available-villa-option" data-room-id="{{ $room->id }}"><i class="bi bi-house-check"></i>{{ $room->displayLabel() }}</button>@empty<em>{{ __('workspace.no_available_villas') }}</em>@endforelse @endif</div>
    </div>
</section>

<div class="row g-3 mb-4">
    <div class="col-4"><div class="surface-card metric-card"><span class="metric-icon metric-green"><i class="bi bi-door-open"></i></span><div class="metric-value">{{ $active->count() }}</div><div class="metric-label">{{ __('workspace.stay_checked_in_plural') }}</div></div></div>
    <div class="col-4"><div class="surface-card metric-card"><span class="metric-icon metric-blue"><i class="bi bi-calendar2-check"></i></span><div class="metric-value">{{ $upcoming->count() }}</div><div class="metric-label">{{ __('workspace.stay_upcoming_plural') }}</div></div></div>
    <div class="col-4"><div class="surface-card metric-card"><span class="metric-icon"><i class="bi bi-archive"></i></span><div class="metric-value">{{ $completed->count() }}</div><div class="metric-label">{{ __('workspace.stay_completed_plural') }}</div></div></div>
</div>

<div class="surface-card overflow-hidden"><div class="table-responsive"><table class="table"><thead><tr><th>{{ __('workspace.guest') }}</th><th>{{ __('workspace.room') }}</th><th>PIN</th><th>{{ __('workspace.stay_period') }}</th><th>{{ __('workspace.nights') }}</th><th>{{ __('workspace.status_label') }}</th><th>{{ __('workspace.stay_bill') }}</th><th></th></tr></thead><tbody>
@forelse($stays as $stay)
<tr>
    <td><a class="fw-semibold small text-dark" href="{{ route('workspace.stays.show',$stay) }}">{{ $stay->guest_name }}</a><div class="text-secondary" style="font-size:10px">#{{ str($stay->public_id)->substr(0,6)->upper() }}</div></td>
    <td><span class="room-chip">{{ $stay->room->displayLabel() }}</span></td>
    <td>@if($stay->access_pin)<div class="stay-pin-inline"><code>{{ $stay->access_pin }}</code><button type="button" class="copy-stay-pin" data-pin="{{ $stay->access_pin }}" title="{{ __('workspace.copy_pin') }}" aria-label="{{ __('workspace.copy_pin') }}"><i class="bi bi-copy"></i></button></div>@elseif(in_array($stay->status,[\App\Enums\GuestStayStatus::Upcoming,\App\Enums\GuestStayStatus::CheckedIn],true))<span class="badge text-bg-light border">{{ __('workspace.pin_not_saved') }}</span>@else<span class="text-secondary">—</span>@endif</td>
    <td class="small"><div>{{ $stay->check_in_at->setTimezone($currentCompany->timezone)->format('d.m.Y H:i') }}</div><div class="text-secondary">{{ $stay->check_out_at->setTimezone($currentCompany->timezone)->format('d.m.Y H:i') }}</div></td>
    <td class="small fw-semibold">{{ $stay->nights }}</td>
    <td><span class="badge-soft-{{ $stay->status->color() }}">{{ __('workspace.stay_status.'.$stay->status->value) }}</span></td>
    <td class="small fw-semibold">{{ $money->format((int)$stay->requests->where('payment_method','room_charge')->whereIn('payment_status',['pending','invoiced'])->whereNull('refund_status')->where('status','!=',\App\Enums\RequestStatus::Cancelled)->sum('price_minor'),$currentCompany->currency) }}</td>
    <td><div class="d-flex gap-1"><a class="btn btn-light btn-sm" href="{{ route('workspace.stays.show',$stay) }}" title="{{ __('workspace.open_client_card') }}"><i class="bi bi-person-vcard"></i></a>@if(in_array($stay->status,[\App\Enums\GuestStayStatus::Upcoming,\App\Enums\GuestStayStatus::CheckedIn],true))<div class="dropdown"><button class="btn btn-light btn-sm" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button><div class="dropdown-menu dropdown-menu-end p-2 stay-actions">
        <form class="d-flex gap-2 mb-2" method="POST" action="{{ route('workspace.stays.pin',$stay) }}">@csrf @method('PATCH')<input class="form-control form-control-sm" name="access_pin" inputmode="numeric" maxlength="8" placeholder="{{ __('workspace.new_pin_auto') }}"><button class="btn btn-light btn-sm text-nowrap">{{ $stay->access_pin ? __('workspace.change_pin') : __('workspace.set_pin') }}</button></form>
        <form class="d-flex gap-2 mb-2" method="POST" action="{{ route('workspace.stays.extend',$stay) }}">@csrf @method('PATCH')<select class="form-select form-select-sm" name="extra_nights">@foreach([1,2,3,7] as $days)<option value="{{ $days }}">+{{ $days }} {{ __('workspace.nights_short') }}</option>@endforeach</select><button class="btn btn-light btn-sm">{{ __('workspace.extend') }}</button></form>
        <form method="POST" action="{{ route('workspace.stays.checkout',$stay) }}" onsubmit="return confirm('{{ __('workspace.checkout_confirm') }}')">@csrf @method('PATCH')<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>{{ __('workspace.checkout_guest') }}</button></form>
    </div></div>@endif</div></td>
</tr>
@empty<tr><td colspan="8"><div class="empty-builder"><span class="empty-builder-icon"><i class="bi bi-door-open"></i></span><h3>{{ __('workspace.no_stays') }}</h3><p>{{ __('workspace.no_stays_hint') }}</p></div></td></tr>@endforelse
</tbody></table></div></div>
@endsection

@push('modals')
<div class="modal fade" id="newStayModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('workspace.stays.store') }}">@csrf<div class="modal-header"><div><h2 class="modal-title fs-5">{{ __('workspace.new_stay') }}</h2><div class="text-secondary small">{{ __('workspace.new_stay_hint') }}</div></div><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-4"><div class="row g-3">
    <div class="col-md-7"><label class="form-label">{{ __('workspace.guest_name') }} *</label><input class="form-control" name="guest_name" required value="{{ old('guest_name') }}"></div>
    <div class="col-md-5"><label class="form-label">{{ __('workspace.room') }} *</label><select class="form-select" name="room_id" required><option value="">{{ __('workspace.choose_room') }}</option>@foreach($rooms as $room)<option value="{{ $room->id }}" @selected(old('room_id')==$room->id)>{{ $room->displayLabel() }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">{{ __('workspace.check_in') }} *</label><input class="form-control" type="datetime-local" name="check_in_at" required value="{{ old('check_in_at',now()->setTimezone($currentCompany->timezone)->format('Y-m-d\TH:i')) }}"></div>
    <div class="col-md-3"><label class="form-label">{{ __('workspace.nights') }} *</label><select class="form-select" name="nights">@foreach([1,2,3,4,5,7,10,14,21,30] as $nights)<option value="{{ $nights }}" @selected(old('nights',1)==$nights)>{{ $nights }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">PIN</label><input class="form-control" name="access_pin" inputmode="numeric" maxlength="8" placeholder="{{ __('workspace.pin_auto') }}"><div class="form-hint mt-1">{{ __('workspace.pin_hint') }}</div></div>
    <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="guest_email" value="{{ old('guest_email') }}" placeholder="guest@example.com"></div>
    <div class="col-md-6"><label class="form-label">{{ __('workspace.emergency_phone') }}</label><input class="form-control" type="tel" name="emergency_phone" value="{{ old('emergency_phone') }}" placeholder="+62 812 3456 7890"></div>
</div></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">{{ __('workspace.cancel') }}</button><button class="btn btn-primary">{{ __('workspace.create_stay') }}</button></div></form></div></div>
@endpush
@push('scripts')
<script>
const availabilityForm=document.querySelector('#availabilityForm');
let availabilityRequest;
availabilityForm?.addEventListener('submit',async event=>{
    event.preventDefault();
    availabilityRequest?.abort();
    const requestController=new AbortController();
    availabilityRequest=requestController;
    const submitButton=availabilityForm.querySelector('button[type="submit"]');
    const originalIcon=submitButton.querySelector('i').className;
    submitButton.disabled=true;
    submitButton.querySelector('i').className='spinner-border spinner-border-sm me-2';
    try{
        const params=new URLSearchParams({available_from:availabilityForm.available_from.value,available_to:availabilityForm.available_to.value});
        const response=await fetch(`${availabilityForm.dataset.endpoint}?${params}`,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},signal:requestController.signal});
        const payload=await response.json();
        if(!response.ok)throw new Error(payload.message||'Request failed');
        renderAvailability(payload);
    }catch(error){
        if(error.name!=='AbortError'){
            const results=document.querySelector('#availabilityResults');
            results.hidden=false;
            results.querySelector('[data-availability-count]').textContent=error.message;
            results.querySelector('[data-availability-period]').textContent='';
            results.querySelector('[data-availability-list]').replaceChildren();
        }
    }finally{
        if(availabilityRequest===requestController){
            submitButton.disabled=false;
            submitButton.querySelector('i').className=originalIcon;
        }
    }
});

function renderAvailability(payload){
    const results=document.querySelector('#availabilityResults');
    results.hidden=false;
    results.querySelector('[data-availability-count]').textContent=payload.count_label;
    results.querySelector('[data-availability-period]').textContent=payload.period_label;
    const list=results.querySelector('[data-availability-list]');
    list.replaceChildren();
    if(!payload.rooms.length){const empty=document.createElement('em');empty.textContent=payload.empty_label;list.append(empty);return;}
    payload.rooms.forEach(room=>{
        const button=document.createElement('button');
        button.type='button';button.className='available-villa-option';button.dataset.roomId=room.id;
        const icon=document.createElement('i');icon.className='bi bi-house-check';
        button.append(icon,document.createTextNode(room.label));list.append(button);
    });
}

document.addEventListener('click',async event=>{
    const roomButton=event.target.closest('.available-villa-option');
    if(roomButton){
        const modal=document.querySelector('#newStayModal');
        const from=availabilityForm.available_from.value;
        const to=availabilityForm.available_to.value;
        modal.querySelector('[name="room_id"]').value=roomButton.dataset.roomId;
        modal.querySelector('[name="check_in_at"]').value=`${from}T14:00`;
        const nights=Math.round((Date.parse(`${to}T00:00:00Z`)-Date.parse(`${from}T00:00:00Z`))/86400000);
        const nightsSelect=modal.querySelector('[name="nights"]');
        if(!nightsSelect.querySelector(`option[value="${nights}"]`))nightsSelect.add(new Option(nights,nights));
        nightsSelect.value=nights;
        modal.addEventListener('shown.bs.modal',()=>modal.querySelector('[name="guest_name"]').focus(),{once:true});
        bootstrap.Modal.getOrCreateInstance(modal).show();
        return;
    }
    const copyButton=event.target.closest('.copy-stay-pin');
    if(copyButton){await navigator.clipboard.writeText(copyButton.dataset.pin);const icon=copyButton.querySelector('i');icon.className='bi bi-check-lg';setTimeout(()=>icon.className='bi bi-copy',1400);return;}
    const scrollButton=event.target.closest('[data-calendar-scroll]');
    if(scrollButton){document.querySelector('[data-occupancy-scroll]')?.scrollBy({left:Number(scrollButton.dataset.calendarScroll)*420,behavior:'smooth'});}
});
</script>
@endpush
