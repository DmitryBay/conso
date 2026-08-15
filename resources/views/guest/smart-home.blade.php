@extends('layouts.guest')
@section('title', __('guest.smart_home_title'))
@section('content')
<div class="guest-page-heading"><a href="{{ route('guest.catalog', $company) }}"><i class="bi bi-arrow-left"></i></a><div><span class="eyebrow">{{ __('guest.smart_home_eyebrow') }}</span><h1>{{ __('guest.smart_home_title') }}</h1></div></div>
@include('guest.orders._smart-home')
@endsection
