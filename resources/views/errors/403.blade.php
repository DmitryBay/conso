<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    <title>Нет доступа · Luma Concierge</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
<div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="login-card text-center" style="max-width:560px">
        <span class="brand-mark mx-auto mb-4" style="background:var(--hc-navy)"><i class="bi bi-shield-lock"></i></span>
        <div class="eyebrow">Доступ ограничен</div>
        <h1 class="mt-2 mb-3">Вы вошли не в тот кабинет</h1>
        <p class="text-secondary mb-4">Главная админ-панель доступна администратору платформы. Сейчас открыт аккаунт {{ auth()->user()?->role?->label() ?? 'без нужных прав' }}.</p>

        @if(app()->environment('local'))
            <form method="POST" action="{{ route('demo.platform-login') }}">
                @csrf
                <button class="btn btn-primary w-100 py-3"><i class="bi bi-lightning-charge-fill me-2"></i>Перейти в главную админку</button>
            </form>
        @endif

        <div class="d-flex gap-2 mt-3">
            @auth
                @if(!auth()->user()->isSuperAdmin())<a class="btn btn-light border flex-grow-1" href="{{ route('workspace.dashboard') }}">Вернуться в кабинет отеля</a>@endif
                <form class="flex-grow-1" method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-light border w-100">Выйти</button></form>
            @else
                <a class="btn btn-light border w-100" href="{{ route('login') }}">Открыть страницу входа</a>
            @endauth
        </div>
    </div>
</div>
</body>
</html>
