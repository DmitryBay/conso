<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar','he'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="current-company-id" content="{{ $currentCompany->id }}"><meta name="theme-color" content="#183c36"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-title" content="Luma Workspace"><meta name="webpush-public-key" content="{{ config('webpush.vapid.public_key') }}"><meta name="webpush-store-url" content="{{ route('workspace.push-subscriptions.store') }}"><meta name="webpush-test-url" content="{{ route('workspace.push-subscriptions.test') }}"><link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2"><link rel="apple-touch-icon" sizes="180x180" href="{{ asset('app-icons/luma-180.png') }}"><link rel="manifest" href="{{ asset('workspace.webmanifest') }}">
    <title>@yield('title', 'Workspace') · {{ $currentCompany->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="workspace-body {{ in_array(app()->getLocale(), ['ar','he'], true) ? 'workspace-rtl' : '' }}">
<div class="app-shell">
    <aside class="sidebar workspace-sidebar">@include('layouts.partials.workspace-sidebar')</aside>
    <header class="mobile-header workspace-mobile-header"><a class="brand p-0" href="{{ route('workspace.dashboard') }}"><span class="brand-mark" style="width:36px;height:36px"><i class="bi bi-bell-fill"></i></span><span class="brand-name">{{ str($currentCompany->name)->limit(18) }}</span></a><button class="btn btn-sm text-white fs-4 p-0" data-bs-toggle="offcanvas" data-bs-target="#workspaceMenu"><i class="bi bi-list"></i></button></header>
    <div class="offcanvas offcanvas-start workspace-sidebar sidebar-mobile" id="workspaceMenu"><div class="offcanvas-header border-bottom border-light border-opacity-10"><div class="brand p-0"><span class="brand-mark"><i class="bi bi-bell-fill"></i></span><span class="brand-name">{{ $currentCompany->name }}</span></div><button class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div><div class="offcanvas-body position-relative">@include('layouts.partials.workspace-sidebar', ['mobile' => true])</div></div>
    <main class="main-area">
        <div class="topbar workspace-topbar">
            <div class="breadcrumb-copy"><div class="small fw-semibold">{{ $currentCompany->name }}</div><div class="text-secondary" style="font-size:11px">{{ __('workspace.service_shift') }} · {{ now()->format('H:i') }}</div></div>
            <div class="d-flex align-items-center gap-2 ms-auto"><div class="dropdown workspace-language"><button class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-label="{{ __('workspace.language') }}"><i class="bi bi-translate me-1"></i>{{ mb_strtoupper(app()->getLocale()) }}</button><div class="dropdown-menu dropdown-menu-end">@foreach(['ru'=>'Русский','uk'=>'Українська','id'=>'Bahasa Indonesia','en'=>'English','ar'=>'العربية','he'=>'עברית','zh'=>'中文','ko'=>'한국어'] as $code=>$label)<a class="dropdown-item d-flex justify-content-between gap-3 {{ app()->getLocale()===$code ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['lang'=>$code]) }}"><span>{{ $label }}</span><small>{{ mb_strtoupper($code) }}</small></a>@endforeach</div></div><a class="btn btn-light btn-sm position-relative" href="{{ route('workspace.notifications.index') }}" aria-label="{{ __('workspace.notifications') }}"><i class="bi bi-bell"></i>@if(auth()->user()->unreadNotifications()->count())<span class="notification-count">{{ min(auth()->user()->unreadNotifications()->count(), 9) }}</span>@endif</a><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,2)) }}</span></div>
        </div>
        <div class="content-wrap workspace-content">
            @if(session()->has('impersonator_id'))
                <div class="impersonation-bar mb-4"><div class="d-flex align-items-center gap-2"><i class="bi bi-person-badge-fill"></i><span>Вы смотрите кабинет как <strong>{{ auth()->user()->name }}</strong></span></div><form method="POST" action="{{ route('impersonation.stop') }}">@csrf<button class="btn btn-sm btn-dark"><i class="bi bi-arrow-return-left me-1"></i>Вернуться в админку</button></form></div>
            @endif
            @if(session('success'))<div class="alert alert-success d-flex align-items-center gap-2 mb-4"><i class="bi bi-check-circle-fill"></i>{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger mb-4"><strong>{{ __('workspace.check_data') }}</strong><div>{{ $errors->first() }}</div></div>@endif
            @yield('content')
        </div>
    </main>
</div>
@stack('modals')
@stack('scripts')
</body></html>
