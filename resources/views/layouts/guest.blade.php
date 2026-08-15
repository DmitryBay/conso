<!doctype html>
<html class="@yield('html-class')" lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar','he'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#183c36">
    <meta name="application-name" content="{{ $company->name }} Concierge">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ $company->name }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($hasStay = app()->bound('guestStay'))
    @if($hasStay)<meta name="guest-session-status-url" content="{{ route('guest.session.status', $company) }}"><meta name="guest-access-url" content="{{ route('guest.access', $company) }}"><meta name="webpush-public-key" content="{{ config('webpush.vapid.public_key') }}"><meta name="webpush-store-url" content="{{ route('guest.push-subscriptions.store', $company) }}"><meta name="webpush-service-worker" content="/guest-sw.js?v={{ filemtime(public_path('guest-sw.js')) }}"><meta name="webpush-scope" content="/guest/"><meta name="guest-orders-status-url" content="{{ route('guest.orders.statuses', $company) }}">@endif
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('app-icons/luma-180.png') }}">
    <link rel="manifest" href="{{ route('guest.manifest', $company) }}">
    <title>@yield('title', __('guest.services')) · {{ $company->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="guest-body guest-kiosk @yield('body-class') {{ in_array(app()->getLocale(), ['ar','he'], true) ? 'guest-rtl' : '' }}">
<div class="guest-app">
    <header class="guest-header">
        <a class="guest-hotel" href="{{ $hasStay ? route('guest.catalog', $company) : route('guest.access', $company) }}">
            <span class="guest-hotel-mark"><i class="bi bi-buildings"></i></span>
            <span><small>{{ __('guest.hotel') }}</small><strong>{{ $company->name }}</strong></span>
        </a>
        <div class="guest-header-actions">
            <div class="dropdown guest-language"><button class="guest-language-button" data-bs-toggle="dropdown" aria-label="{{ __('guest.language') }}"><i class="bi bi-translate"></i><span>{{ mb_strtoupper(app()->getLocale()) }}</span></button><div class="dropdown-menu dropdown-menu-end">@foreach(['ru'=>'Русский','uk'=>'Українська','id'=>'Bahasa Indonesia','en'=>'English','ar'=>'العربية','he'=>'עברית','zh'=>'中文','ko'=>'한국어'] as $code=>$label)<a class="dropdown-item {{ app()->getLocale()===$code ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['lang'=>$code]) }}"><span>{{ $label }}</span><small>{{ mb_strtoupper($code) }}</small></a>@endforeach</div></div>
            @if($hasStay)<button class="guest-notification-button" type="button" data-bs-toggle="modal" data-bs-target="#guestNotificationModal" title="{{ __('guest.notifications') }}" aria-label="{{ __('guest.notifications') }}"><i class="bi bi-bell"></i></button><div class="guest-stay-meta"><div class="guest-room"><small>{{ __('guest.room') }}</small><strong>{{ app('guestStay')->room->displayName() }}</strong></div><div class="guest-stay-until"><small>{{ __('guest.stay_until') }}</small><strong>{{ app('guestStay')->stay->check_out_at->setTimezone($company->timezone)->format('d.m') }}</strong></div></div><form class="guest-logout-form" method="POST" action="{{ route('guest.logout', $company) }}">@csrf<button class="guest-logout-button" type="submit" title="{{ __('guest.logout') }}" aria-label="{{ __('guest.logout') }}"><i class="bi bi-box-arrow-right"></i><span>{{ __('guest.logout') }}</span></button></form>@endif
        </div>
    </header>

    <main class="guest-main @yield('main-class')">
        @if(session('guest_success'))
            <div class="guest-alert success"><i class="bi bi-check-circle-fill"></i><span>{{ session('guest_success') }}</span></div>
        @endif
        @if(session('guest_error'))
            <div class="guest-alert error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ session('guest_error') }}</span></div>
        @endif
        @if($errors->any())
            <div class="guest-alert error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $errors->first() }}</span></div>
        @endif
        @yield('content')
    </main>

    @if($hasStay)
        <nav class="guest-bottom-nav" aria-label="{{ __('guest.services') }}">
            <a class="{{ request()->routeIs('guest.catalog') || request()->routeIs('guest.orders.create') ? 'active' : '' }}" href="{{ route('guest.catalog', $company) }}"><i class="bi bi-grid"></i><span>{{ __('guest.services') }}</span></a>
            <a class="{{ request()->routeIs('guest.orders.index') || request()->routeIs('guest.orders.show') ? 'active' : '' }}" href="{{ route('guest.orders.index', $company) }}"><i class="bi bi-bell"></i><span>{{ __('guest.orders') }}</span></a>
            <a class="{{ request()->routeIs('guest.bill') ? 'active' : '' }}" href="{{ route('guest.bill', $company) }}"><i class="bi bi-receipt"></i><span>{{ __('guest.bill') }}</span></a>
        </nav>
        <div class="modal fade guest-notification-modal" id="guestNotificationModal" tabindex="-1" aria-labelledby="guestNotificationTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><span class="guest-notification-modal-icon"><i class="bi bi-bell"></i></span><div><small>{{ __('guest.notification_eyebrow') }}</small><h2 class="modal-title" id="guestNotificationTitle">{{ __('guest.notifications') }}</h2></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="{{ __('guest.close') }}"></button></div>
            <div class="modal-body">
                <section class="guest-notification-option" id="pushSettings" data-enabled="{{ __('guest.push_enabled') }}" data-disabled="{{ __('guest.push_disabled') }}" data-enable="{{ __('guest.push_enable') }}" data-disable="{{ __('guest.push_disable') }}" data-unsupported="{{ __('guest.push_unsupported') }}" data-denied="{{ __('guest.push_denied') }}" data-error="{{ __('guest.push_error') }}"><span><i class="bi bi-phone-vibrate"></i></span><div><strong>{{ __('guest.push_title') }}</strong><small id="pushStatus">{{ __('guest.push_checking') }}</small></div><button class="btn btn-primary" id="pushToggleButton" type="button"><span>{{ __('guest.push_enable') }}</span></button></section>
                <form class="guest-notification-email" method="POST" action="{{ route('guest.notifications.email', $company) }}">@csrf @method('PATCH')<label class="guest-label" for="notification_email">{{ __('guest.email_notifications') }}</label><div class="guest-input-wrap"><i class="bi bi-envelope"></i><input id="notification_email" name="guest_email" type="email" value="{{ app('guestStay')->stay->guest_email }}" placeholder="{{ __('guest.email_hint') }}" autocomplete="email"></div><small>{{ __('guest.email_notification_hint') }}</small><button class="guest-guide-close" type="submit">{{ __('guest.save_email') }}</button></form>
                <p class="guest-notification-expiry"><i class="bi bi-shield-check"></i> {{ __('guest.notification_expiry_hint', ['date' => app('guestStay')->stay->check_out_at->setTimezone($company->timezone)->format('d.m.Y')]) }}</p>
            </div>
        </div></div></div>
    @endif
</div>
@stack('scripts')
</body>
</html>
