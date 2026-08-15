@extends('layouts.workspace')
@section('title', __('workspace.room_inventory'))
@section('content')
<div class="d-flex align-items-end justify-content-between gap-3 mb-4"><div><div class="eyebrow">{{ __('workspace.management') }}</div><h1 class="page-title">{{ __('workspace.room_inventory') }}</h1><p class="page-subtitle">{{ __('workspace.room_inventory_intro') }}</p></div></div>

<div class="surface-card form-section mb-4">
    <div class="form-section-title">{{ __('workspace.add_room') }}</div>
    <div class="form-section-copy">{{ __('workspace.add_room_hint') }}</div>
    <form class="row g-3 align-items-end" method="POST" action="{{ route('workspace.rooms.store') }}">@csrf
        <div class="col-md-3"><label class="form-label">{{ __('workspace.room_code') }} *</label><input class="form-control" name="number" required maxlength="30" value="{{ old('number') }}" placeholder="305"></div>
        <div class="col-md-5 room-field-with-hint"><label class="form-label">{{ __('workspace.room_name') }}</label><input class="form-control" name="name" maxlength="160" value="{{ old('name') }}" placeholder="{{ __('workspace.room_name_placeholder') }}"><div class="form-hint room-floating-hint">{{ __('workspace.room_name_hint') }}</div></div>
        <div class="col-md-2"><label class="form-label">{{ __('workspace.floor') }}</label><input class="form-control" name="floor" maxlength="30" value="{{ old('floor') }}" placeholder="3"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-2"></i>{{ __('workspace.add') }}</button></div>
    </form>
</div>

<div class="room-inventory-list">
@forelse($rooms as $room)
    <form class="surface-card room-inventory-row" method="POST" action="{{ route('workspace.rooms.update', $room) }}">@csrf @method('PUT')
        <span class="room-inventory-icon"><i class="bi bi-door-closed"></i></span>
        <div><label class="form-label">{{ __('workspace.room_code') }}</label><input class="form-control" name="number" required maxlength="30" value="{{ $room->number }}"></div>
        <div class="room-field-with-hint"><label class="form-label">{{ __('workspace.room_name') }}</label><input class="form-control" name="name" maxlength="160" value="{{ $room->name }}" placeholder="{{ __('workspace.room_name_placeholder') }}"><div class="form-hint room-floating-hint">{{ __('workspace.room_name_hint') }}</div></div>
        <div><label class="form-label">{{ __('workspace.floor') }}</label><input class="form-control" name="floor" maxlength="30" value="{{ $room->floor }}"></div>
        <label class="room-active-toggle"><input type="checkbox" name="is_active" value="1" @checked($room->is_active)><span>{{ __('workspace.room_active') }}</span><small>{{ trans_choice('workspace.room_stays_count', $room->guest_stays_count, ['count' => $room->guest_stays_count]) }}</small></label>
        <button class="btn btn-light"><i class="bi bi-check2 me-1"></i>{{ __('workspace.save') }}</button>
    </form>
@empty
    <div class="surface-card empty-builder"><span class="empty-builder-icon"><i class="bi bi-houses"></i></span><h3>{{ __('workspace.no_rooms') }}</h3><p>{{ __('workspace.no_rooms_hint') }}</p></div>
@endforelse
</div>
@endsection
