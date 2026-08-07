@php($mobile = $mobile ?? false)
@unless($mobile)
<a class="brand" href="{{ route('platform.dashboard') }}"><span class="brand-mark"><i class="bi bi-building-check"></i></span><span><span class="brand-name d-block">{{ $platformName ?? 'Luma Concierge' }}</span><span class="brand-caption d-block">Platform console</span></span></a>
@endunless
<div class="sidebar-label">Платформа</div>
<nav>
    <a class="sidebar-link {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}" href="{{ route('platform.dashboard') }}"><i class="bi bi-grid-1x2"></i>Обзор</a>
    <a class="sidebar-link {{ request()->routeIs('platform.companies.*') ? 'active' : '' }}" href="{{ route('platform.companies.index') }}"><i class="bi bi-buildings"></i>Компании</a>
    <a class="sidebar-link {{ request()->routeIs('platform.users.*') ? 'active' : '' }}" href="{{ route('platform.users.index') }}"><i class="bi bi-people"></i>Пользователи</a>
    <a class="sidebar-link" href="#"><i class="bi bi-credit-card"></i>Тарифы <span class="sidebar-coming">СКОРО</span></a>
</nav>
<div class="sidebar-label mt-4">Система</div>
<nav><a class="sidebar-link {{ request()->routeIs('platform.system') ? 'active' : '' }}" href="{{ route('platform.system') }}"><i class="bi bi-activity"></i>Состояние</a><a class="sidebar-link {{ request()->routeIs('platform.settings.*') ? 'active' : '' }}" href="{{ route('platform.settings.edit') }}"><i class="bi bi-sliders"></i>Настройки</a></nav>
<div class="sidebar-user">
    <div class="d-flex align-items-center gap-2"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span><div class="overflow-hidden flex-grow-1"><div class="text-white small fw-semibold text-truncate">{{ auth()->user()->name }}</div><div class="text-white-50 text-truncate" style="font-size:10px">{{ auth()->user()->email }}</div></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm text-white-50 p-1" aria-label="Выйти"><i class="bi bi-box-arrow-right"></i></button></form></div>
</div>
