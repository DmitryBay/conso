@extends('layouts.guest')
@section('title', __('guest.my_orders'))
@section('content')
@php $money = app(\App\Support\Money::class); @endphp
<div class="guest-page-heading no-back" data-order-status-poller data-updated-message="{{ __('guest.orders_updated') }}" data-current-message="{{ __('guest.orders_up_to_date') }}" data-error-message="{{ __('guest.refresh_failed') }}"><div><span class="eyebrow">{{ __('guest.stay_history') }}</span><h1>{{ __('guest.my_orders') }}</h1></div><button class="guest-refresh-button" type="button" data-orders-refresh><i class="bi bi-arrow-clockwise"></i><span>{{ __('guest.refresh_orders') }}</span></button></div>
<div data-orders-list>
    @if($orders->isEmpty())
        <section class="guest-empty guest-empty-page"><span><i class="bi bi-bell"></i></span><h2>{{ __('guest.no_orders') }}</h2><p>{{ __('guest.no_orders_hint') }}</p><a class="guest-primary-button" href="{{ route('guest.catalog', $company) }}">{{ __('guest.choose_service') }}</a></section>
    @else
        <div class="guest-order-list">@foreach($orders as $order)@include('guest.orders._card', ['order' => $order])@endforeach</div>
    @endif
</div>
<p class="visually-hidden" aria-live="polite" data-orders-refresh-status></p>
@endsection
