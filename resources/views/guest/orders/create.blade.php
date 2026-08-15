@extends('layouts.guest')
@section('title', __('guest.order'))
@section('content')
@php
    $money = app(\App\Support\Money::class);
    $backgroundImage = $serviceNode->resolvedBackground($company);
    $legacyBackground = asset('images/service-backgrounds/'.($serviceNode->background_key ?: 'wellness').'.jpg');
    $unitPrice = $serviceNode->price_minor ?? 0;
    $orderTag = $serviceNode->smart_home_enabled ? 'div' : 'form';
@endphp
<div class="guest-page-heading"><a href="{{ route('guest.catalog', $company) }}"><i class="bi bi-arrow-left"></i></a><div><span class="eyebrow">{{ __('guest.order_step') }}</span><h1>{{ __('guest.order') }}</h1></div></div>
<{{ $orderTag }} class="single-order-grid" @unless($serviceNode->smart_home_enabled) method="POST" action="{{ route('guest.orders.store', [$company, $serviceNode]) }}" @endunless>@unless($serviceNode->smart_home_enabled) @csrf @endunless
    <section class="single-service-hero" style="--service-bg:url('{{ $backgroundImage?->url() ?? $legacyBackground }}');--service-position:{{ $backgroundImage?->background_position ?? 'center' }};--service-size:{{ $backgroundImage?->background_size ?? 'cover' }}"><div><span class="service-card-icon"><i class="bi {{ $serviceNode->icon ?: 'bi-stars' }}"></i></span><h2>{{ $serviceNode->localizedName() }}</h2><p>{{ $serviceNode->localizedDescription() }}</p>@if($serviceNode->sla_minutes)<small><i class="bi bi-clock"></i> {{ __('guest.minutes', ['count'=>$serviceNode->sla_minutes]) }}</small>@endif</div></section>
    @if($serviceNode->smart_home_enabled)
        @include('guest.orders._smart-home')
    @else
    <section class="guest-form-card single-order-form">
        <div class="order-price-block"><div><small>{{ __('guest.price_per_item') }}</small><strong>@if($unitPrice){{ $money->format($unitPrice, $company->currency) }}@else{{ __('guest.free') }}@endif</strong>@if($unitPrice && $money->approximateUsd($unitPrice, $company->currency))<span>{{ $money->approximateUsd($unitPrice, $company->currency) }}</span>@endif</div><small>{{ __('guest.base_currency', ['currency'=>$company->currency]) }}</small></div>
        <label class="guest-label" for="quantity">{{ __('guest.quantity') }}</label><select class="guest-select" id="quantity" name="quantity" data-unit-price="{{ $unitPrice }}">@foreach(range(1,10) as $quantity)<option value="{{ $quantity }}">{{ $quantity }}</option>@endforeach</select>
        @if($serviceNode->option_keys)<div class="mt-4"><div class="guest-card-heading"><span><i class="bi bi-ui-checks"></i></span><div><h2>{{ __('guest.additional_options') }}</h2><p>{{ __('guest.additional_options_hint') }}</p></div></div><div class="guest-service-options">@foreach($serviceNode->option_keys as $optionKey)<label><input type="checkbox" name="selected_options[]" value="{{ $optionKey }}" @checked(in_array($optionKey,old('selected_options',[]),true))><span><i class="bi {{ \App\Support\ServiceOptionCatalog::icon($optionKey) }}"></i><strong>{{ __('guest.service_options.'.$optionKey) }}</strong><i class="bi bi-check-circle-fill"></i></span></label>@endforeach</div></div>@endif
        @if($unitPrice)<div class="mt-4"><div class="guest-card-heading"><span><i class="bi bi-credit-card"></i></span><div><h2>{{ __('guest.payment_method') }}</h2><p>{{ __('guest.payment_hint') }}</p></div></div><label class="guest-choice"><input type="radio" name="payment_method" value="room_charge" checked><span class="choice-icon"><i class="bi bi-door-open"></i></span><span><strong>{{ __('guest.room_charge') }}</strong><small>{{ __('guest.room_charge_hint') }}</small></span><i class="bi bi-check-circle-fill choice-check"></i></label><label class="guest-choice"><input type="radio" name="payment_method" value="cash"><span class="choice-icon"><i class="bi bi-cash-stack"></i></span><span><strong>{{ __('guest.cash') }}</strong><small>{{ __('guest.cash_hint') }}</small></span><i class="bi bi-check-circle-fill choice-check"></i></label></div>@endif
        <label class="guest-label mt-4" for="comment">{{ __('guest.comment') }} <small>{{ __('guest.optional') }}</small></label><textarea class="guest-textarea" id="comment" name="comment" rows="3" placeholder="{{ __('guest.comment_hint') }}">{{ old('comment') }}</textarea>
        <div class="single-order-total"><span>{{ __('guest.total') }}</span><strong id="orderTotal" data-template="{{ $company->currency === 'IDR' ? 'Rp %s' : $company->currency.' %s' }}">@if($unitPrice){{ $money->format($unitPrice, $company->currency) }}@else{{ __('guest.free') }}@endif</strong><small id="orderUsd">{{ $unitPrice ? $money->approximateUsd($unitPrice, $company->currency) : '' }}</small></div>
        <button class="guest-primary-button" type="submit">{{ __('guest.submit_order') }} <i class="bi bi-arrow-right"></i></button><p class="checkout-consent"><i class="bi bi-shield-check"></i> {{ __('guest.order_notice') }}</p>
    </section>
    @endif
</{{ $orderTag }}>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{const q=document.getElementById('quantity'),total=document.getElementById('orderTotal'),usd=document.getElementById('orderUsd'),unit={{ $unitPrice }},rate={{ (float) config('concierge.usd_rates.'.$company->currency, 0) }},currency=@json($company->currency);if(!q||!unit)return;q.addEventListener('change',()=>{const value=unit*Number(q.value);total.textContent=currency==='IDR'?'Rp '+new Intl.NumberFormat('id-ID').format(value):currency+' '+new Intl.NumberFormat(undefined,{minimumFractionDigits:2}).format(value/100);if(usd&&currency!=='USD'&&rate)usd.textContent='≈ $'+new Intl.NumberFormat('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}).format((currency==='IDR'?value:value/100)/rate);});});</script>
@endpush
