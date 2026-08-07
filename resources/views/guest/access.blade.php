@extends('layouts.guest')
@section('title', __('guest.welcome_to'))
@section('main-class', 'guest-access-main')
@section('content')
<section class="guest-access-card">
    <div class="guest-access-art">
        <span class="access-sun"></span><span class="access-wave one"></span><span class="access-wave two"></span>
        <div><span>{{ __('guest.welcome_to') }}</span><h1>{{ $company->name }}</h1><p>{{ __('guest.access_intro') }}</p></div>
    </div>
    <form class="guest-access-form" method="POST" action="{{ route('guest.access.store', $company) }}">
        @csrf
        <div class="access-form-heading"><span class="eyebrow">{{ __('guest.guest_service') }}</span><h2>{{ __('guest.access_title') }}</h2><p>{{ __('guest.access_hint') }}</p></div>
        @php
            $localeCountries = ['en' => 'INT', 'id' => 'ID', 'ru' => 'RU', 'uk' => 'UA', 'ar' => 'AE', 'he' => 'IL', 'zh' => 'CN', 'ko' => 'KR'];
            $selectedCountry = old('country_code', request('country', $localeCountries[app()->getLocale()] ?? 'INT'));
        @endphp
        <fieldset class="guest-country-fieldset"><legend class="guest-label">{{ __('guest.country') }}</legend><div class="guest-country-primary">
            @foreach(['INT' => ['🌐', 'en'], 'ID' => ['🇮🇩', 'id'], 'RU' => ['🇷🇺', 'ru']] as $countryCode => [$flag, $locale])
                <label class="guest-country-option"><input type="radio" name="country_code" value="{{ $countryCode }}" data-country-locale="{{ $locale }}" @checked($selectedCountry === $countryCode) required><span><b>{{ $flag }}</b><strong>{{ __('guest.countries.'.$countryCode) }}</strong><small>{{ mb_strtoupper($locale) }}</small></span></label>
            @endforeach
        </div><details class="guest-country-more" @if(!in_array($selectedCountry, ['INT','ID','RU'], true)) open @endif><summary>{{ __('guest.other_countries') }} <i class="bi bi-chevron-down"></i></summary><div>
            @foreach(['UA' => ['🇺🇦', 'uk'], 'AE' => ['🇦🇪', 'ar'], 'IL' => ['🇮🇱', 'he'], 'CN' => ['🇨🇳', 'zh'], 'KR' => ['🇰🇷', 'ko']] as $countryCode => [$flag, $locale])
                <label class="guest-country-option compact"><input type="radio" name="country_code" value="{{ $countryCode }}" data-country-locale="{{ $locale }}" @checked($selectedCountry === $countryCode)><span><b>{{ $flag }}</b><strong>{{ __('guest.countries.'.$countryCode) }}</strong><small>{{ mb_strtoupper($locale) }}</small></span></label>
            @endforeach
        </div></details></fieldset>
        <label class="guest-label" for="room_number">{{ __('guest.room_number') }}</label>
        <div class="guest-input-wrap"><i class="bi bi-door-open"></i><input id="room_number" name="room_number" value="{{ old('room_number') }}" placeholder="{{ __('guest.room_example') }}" autocomplete="off" required autofocus></div>
        <label class="guest-label" for="pin">{{ __('guest.pin') }}</label>
        <div class="guest-input-wrap"><i class="bi bi-shield-lock"></i><input id="pin" name="pin" type="password" inputmode="numeric" placeholder="{{ __('guest.pin_hint') }}" maxlength="10" required></div>
        <label class="guest-label" for="guest_name">{{ __('guest.your_name') }} <small>{{ __('guest.optional') }}</small></label>
        <div class="guest-input-wrap"><i class="bi bi-person"></i><input id="guest_name" name="guest_name" value="{{ old('guest_name') }}" placeholder="{{ __('guest.name_hint') }}"></div>
        <button class="guest-primary-button" type="submit">{{ __('guest.continue') }} <i class="bi bi-arrow-right"></i></button>
        <div class="guest-secure-note"><i class="bi bi-lock"></i> {{ __('guest.secure_access') }}</div>
    </form>
</section>
@endsection
@push('scripts')
<script>document.querySelectorAll('[data-country-locale]').forEach(input=>input.addEventListener('change',()=>{const url=new URL(window.location.href);url.searchParams.set('lang',input.dataset.countryLocale);url.searchParams.set('country',input.value);window.location.assign(url.toString())}));</script>
@endpush
