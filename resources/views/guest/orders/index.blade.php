@extends('layouts.guest')
@section('title', __('guest.my_orders'))
@section('content')
@php $money = app(\App\Support\Money::class); @endphp
<div class="guest-page-heading no-back"><div><span class="eyebrow">{{ __('guest.stay_history') }}</span><h1>{{ __('guest.my_orders') }}</h1></div></div>
@if($orders->isEmpty())
    <section class="guest-empty guest-empty-page"><span><i class="bi bi-bell"></i></span><h2>{{ __('guest.no_orders') }}</h2><p>{{ __('guest.no_orders_hint') }}</p><a class="guest-primary-button" href="{{ route('guest.catalog', $company) }}">{{ __('guest.choose_service') }}</a></section>
@else
    <div class="guest-order-list">@foreach($orders as $order)<a class="guest-order-card" href="{{ route('guest.orders.show', [$company, $order]) }}"><span class="order-status-icon status-{{ $order->status->value }}"><i class="bi {{ $order->status === \App\Enums\RequestStatus::Completed ? 'bi-check-lg' : 'bi-hourglass-split' }}"></i></span><div class="order-card-copy"><div><strong>{{ __('guest.order_number', ['number'=>str($order->public_id)->substr(0, 8)->upper()]) }}</strong><time>{{ $order->created_at->format('d.m · H:i') }}</time></div><h2>{{ $order->items->first()?->service?->localizedName() ?? $order->title }}</h2><p>{{ __('guest.items_count', ['count'=>$order->items->sum('quantity')]) }} · @if($order->price_minor){{ $money->format($order->price_minor, $company->currency) }}@else{{ __('guest.free') }}@endif</p></div><span class="order-status-label status-{{ $order->status->value }}">{{ __('guest.status.'.$order->status->value) }}</span><i class="bi bi-chevron-right order-chevron"></i></a>@endforeach</div>
@endif
@endsection
