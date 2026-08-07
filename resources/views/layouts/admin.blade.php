@php($platformName = \App\Models\PlatformSetting::read('platform_name', 'Luma Concierge'))
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <title>@yield('title', 'Platform') · {{ $platformName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">@include('layouts.partials.sidebar')</aside>
    <header class="mobile-header">
        <a class="brand p-0" href="{{ route('platform.dashboard') }}"><span class="brand-mark" style="width:36px;height:36px"><i class="bi bi-building-check"></i></span><span class="brand-name">{{ str($platformName)->limit(18) }}</span></a>
        <button class="btn btn-sm text-white fs-4 p-0" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="Открыть меню"><i class="bi bi-list"></i></button>
    </header>
    <div class="offcanvas offcanvas-start sidebar-mobile" tabindex="-1" id="mobileMenu">
        <div class="offcanvas-header border-bottom border-light border-opacity-10"><div class="brand p-0"><span class="brand-mark"><i class="bi bi-building-check"></i></span><span class="brand-name">{{ $platformName }}</span></div><button class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div>
        <div class="offcanvas-body position-relative">@include('layouts.partials.sidebar', ['mobile' => true])</div>
    </div>
    <main class="main-area">
        <div class="topbar">
            <div class="breadcrumb-copy"><div class="small fw-semibold">Управление платформой</div><div class="text-secondary" style="font-size:11px">{{ now()->translatedFormat('d F Y') }}</div></div>
            <div class="d-flex align-items-center gap-2 ms-auto"><a class="btn btn-light btn-sm position-relative" href="{{ route('platform.notifications.index') }}" aria-label="Уведомления"><i class="bi bi-bell"></i>@if(auth()->user()->unreadNotifications()->count())<span class="notification-count">{{ min(auth()->user()->unreadNotifications()->count(), 9) }}</span>@endif</a><a class="avatar" href="{{ route('platform.users.edit', auth()->user()) }}" aria-label="Мой профиль">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</a></div>
        </div>
        <div class="content-wrap">
            @if(session('success'))<div class="alert alert-success d-flex align-items-center gap-2 mb-4"><i class="bi bi-check-circle-fill"></i>{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger mb-4"><strong>Проверьте данные.</strong><div>{{ $errors->first() }}</div></div>@endif
            @yield('content')
        </div>
    </main>
</div>
@stack('scripts')
</body>
</html>
