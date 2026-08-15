@extends('layouts.guest')
@section('title', __('guest.my_orders'))
@section('content')
@php $money = app(\App\Support\Money::class); @endphp
<div class="guest-page-heading no-back" data-order-status-poller data-updated-message="{{ __('guest.orders_updated') }}" data-current-message="{{ __('guest.orders_up_to_date') }}" data-error-message="{{ __('guest.refresh_failed') }}"><div><span class="eyebrow">{{ __('guest.stay_history') }}</span><h1>{{ __('guest.my_orders') }}</h1></div><button class="guest-refresh-button" type="button" data-orders-refresh><i class="bi bi-arrow-clockwise"></i><span>{{ __('guest.refresh_orders') }}</span></button></div>
@if($orders->isEmpty())
    <section class="guest-empty guest-empty-page"><span><i class="bi bi-bell"></i></span><h2>{{ __('guest.no_orders') }}</h2><p>{{ __('guest.no_orders_hint') }}</p><a class="guest-primary-button" href="{{ route('guest.catalog', $company) }}">{{ __('guest.choose_service') }}</a></section>
@else
    <div class="guest-order-list">@foreach($orders as $order)<a class="guest-order-card" data-order-id="{{ $order->public_id }}" data-order-status="{{ $order->status->value }}" data-payment-status="{{ $order->payment_status }}" href="{{ route('guest.orders.show', [$company, $order]) }}"><span class="order-status-icon status-{{ $order->status->value }}"><i class="bi {{ in_array($order->status, [\App\Enums\RequestStatus::Ready, \App\Enums\RequestStatus::Completed], true) ? 'bi-check-lg' : ($order->status === \App\Enums\RequestStatus::Cancelled ? 'bi-x-lg' : 'bi-hourglass-split') }}"></i></span><div class="order-card-copy"><div><strong>{{ __('guest.order_number', ['number'=>str($order->public_id)->substr(0, 8)->upper()]) }}</strong><time>{{ $order->created_at->format('d.m · H:i') }}</time></div><h2>{{ $order->items->first()?->service?->localizedName() ?? $order->title }}</h2><p>{{ __('guest.items_count', ['count'=>$order->items->sum('quantity')]) }} · @if($order->price_minor){{ $money->format($order->price_minor, $company->currency) }}@else{{ __('guest.free') }}@endif</p>@include('guest.orders._progress', ['order' => $order, 'compact' => true])</div><i class="bi bi-chevron-right order-chevron"></i></a>@endforeach</div>
@endif
<p class="visually-hidden" aria-live="polite" data-orders-refresh-status></p>
@endsection
