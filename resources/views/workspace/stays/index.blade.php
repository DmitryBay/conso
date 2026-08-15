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

<div class="row g-3 mb-4">
    <div class="col-4"><div class="surface-card metric-card"><span class="metric-icon metric-green"><i class="bi bi-door-open"></i></span><div class="metric-value">{{ $active->count() }}</div><div class="metric-label">{{ __('workspace.stay_checked_in_plural') }}</div></div></div>
    <div class="col-4"><div class="surface-card metric-card"><span class="metric-icon metric-blue"><i class="bi bi-calendar2-check"></i></span><div class="metric-value">{{ $upcoming->count() }}</div><div class="metric-label">{{ __('workspace.stay_upcoming_plural') }}</div></div></div>
    <div class="col-4"><div class="surface-card metric-card"><span class="metric-icon"><i class="bi bi-archive"></i></span><div class="metric-value">{{ $completed->count() }}</div><div class="metric-label">{{ __('workspace.stay_completed_plural') }}</div></div></div>
</div>

<div class="surface-card overflow-hidden"><div class="table-responsive"><table class="table"><thead><tr><th>{{ __('workspace.guest') }}</th><th>{{ __('workspace.room') }}</th><th>PIN</th><th>{{ __('workspace.stay_period') }}</th><th>{{ __('workspace.nights') }}</th><th>{{ __('workspace.status_label') }}</th><th>{{ __('workspace.stay_bill') }}</th><th></th></tr></thead><tbody>
@forelse($stays as $stay)
<tr>
    <td><div class="fw-semibold small">{{ $stay->guest_name }}</div><div class="text-secondary" style="font-size:10px">#{{ str($stay->public_id)->substr(0,6)->upper() }}</div></td>
    <td><span class="room-chip">{{ $stay->room->displayName() }}</span></td>
    <td>@if($stay->access_pin)<div class="stay-pin-inline"><code>{{ $stay->access_pin }}</code><button type="button" class="copy-stay-pin" data-pin="{{ $stay->access_pin }}" title="{{ __('workspace.copy_pin') }}" aria-label="{{ __('workspace.copy_pin') }}"><i class="bi bi-copy"></i></button></div>@elseif(in_array($stay->status,[\App\Enums\GuestStayStatus::Upcoming,\App\Enums\GuestStayStatus::CheckedIn],true))<span class="badge text-bg-light border">{{ __('workspace.pin_not_saved') }}</span>@else<span class="text-secondary">—</span>@endif</td>
    <td class="small"><div>{{ $stay->check_in_at->setTimezone($currentCompany->timezone)->format('d.m.Y H:i') }}</div><div class="text-secondary">{{ $stay->check_out_at->setTimezone($currentCompany->timezone)->format('d.m.Y H:i') }}</div></td>
    <td class="small fw-semibold">{{ $stay->nights }}</td>
    <td><span class="badge-soft-{{ $stay->status->color() }}">{{ __('workspace.stay_status.'.$stay->status->value) }}</span></td>
    <td class="small fw-semibold">{{ $money->format((int)$stay->requests->where('payment_method','room_charge')->where('payment_status','invoiced')->where('status','!=',\App\Enums\RequestStatus::Cancelled)->sum('price_minor'),$currentCompany->currency) }}</td>
    <td>@if(in_array($stay->status,[\App\Enums\GuestStayStatus::Upcoming,\App\Enums\GuestStayStatus::CheckedIn],true))<div class="dropdown"><button class="btn btn-light btn-sm" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button><div class="dropdown-menu dropdown-menu-end p-2 stay-actions">
        <form class="d-flex gap-2 mb-2" method="POST" action="{{ route('workspace.stays.pin',$stay) }}">@csrf @method('PATCH')<input class="form-control form-control-sm" name="access_pin" inputmode="numeric" maxlength="8" placeholder="{{ __('workspace.new_pin_auto') }}"><button class="btn btn-light btn-sm text-nowrap">{{ $stay->access_pin ? __('workspace.change_pin') : __('workspace.set_pin') }}</button></form>
        <form class="d-flex gap-2 mb-2" method="POST" action="{{ route('workspace.stays.extend',$stay) }}">@csrf @method('PATCH')<select class="form-select form-select-sm" name="extra_nights">@foreach([1,2,3,7] as $days)<option value="{{ $days }}">+{{ $days }} {{ __('workspace.nights_short') }}</option>@endforeach</select><button class="btn btn-light btn-sm">{{ __('workspace.extend') }}</button></form>
        <form method="POST" action="{{ route('workspace.stays.checkout',$stay) }}" onsubmit="return confirm('{{ __('workspace.checkout_confirm') }}')">@csrf @method('PATCH')<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>{{ __('workspace.checkout_guest') }}</button></form>
    </div></div>@endif</td>
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
</div></div><div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">{{ __('workspace.cancel') }}</button><button class="btn btn-primary">{{ __('workspace.create_stay') }}</button></div></form></div></div>
@endpush
@push('scripts')
<script>document.addEventListener('click',async event=>{const button=event.target.closest('.copy-stay-pin');if(!button)return;await navigator.clipboard.writeText(button.dataset.pin);const icon=button.querySelector('i');icon.className='bi bi-check-lg';setTimeout(()=>icon.className='bi bi-copy',1400);});</script>
@endpush
