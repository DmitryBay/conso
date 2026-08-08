@extends('layouts.guest')
@section('title', __('guest.bill_title'))
@section('content')
@php $money = app(\App\Support\Money::class); @endphp
<div class="guest-page-heading no-back"><div><span class="eyebrow">{{ __('guest.bill_eyebrow') }}</span><h1>{{ __('guest.bill_title') }}</h1><p class="page-subtitle">{{ __('guest.bill_intro') }}</p></div></div>
<section class="guest-bill-paper">
    <div class="bill-hotel"><span class="guest-hotel-mark"><i class="bi bi-buildings"></i></span><div><strong>{{ $company->name }}</strong><small>{{ __('guest.base_currency', ['currency'=>$company->currency]) }}</small></div><span>{{ now()->format('d.m.Y') }}</span></div>
    <div class="bill-person"><div><small>{{ __('guest.guest') }}</small><strong>{{ $stay->guest_name ?: __('guest.not_specified') }}</strong></div><div><small>{{ __('guest.room') }}</small><strong>{{ $stay->room->number }}</strong></div></div>
    @if($orders->where('price_minor', '>', 0)->isEmpty())
        <div class="bill-empty"><i class="bi bi-receipt"></i><h2>{{ __('guest.bill_empty') }}</h2><p>{{ __('guest.bill_empty_hint') }}</p></div>
    @else
        <div class="bill-lines">@foreach($orders->where('price_minor', '>', 0) as $order)<div class="bill-line {{ $order->status === \App\Enums\RequestStatus::Cancelled ? 'cancelled' : '' }}"><span><strong>{{ $order->items->first()?->service?->localizedName() ?? $order->title }}</strong><small>{{ $order->created_at->format('d.m · H:i') }} · {{ __('guest.status.'.$order->status->value) }}</small></span><span><strong>{{ $money->format($order->price_minor, $company->currency) }}</strong>@if($money->approximateUsd($order->price_minor, $company->currency))<small>{{ $money->approximateUsd($order->price_minor, $company->currency) }}</small>@endif</span></div>@endforeach</div>
        <div class="bill-grand-total"><span><small>{{ __('guest.grand_total') }}</small><strong>{{ $money->format($total, $company->currency) }}</strong></span>@if($money->approximateUsd($total, $company->currency))<span><small>{{ __('guest.usd_estimate') }}</small><strong>{{ $money->approximateUsd($total, $company->currency) }}</strong></span>@endif</div>
    @endif
    <div class="bill-note"><i class="bi bi-info-circle"></i><span>{{ __('guest.rate_note') }} {{ __('guest.cancelled_excluded') }} {{ __('guest.cash_excluded') }}</span></div>
</section>
@endsection
