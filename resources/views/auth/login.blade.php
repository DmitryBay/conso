<!doctype html>
<html lang="ru">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2"><title>Вход · Luma Concierge</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="login-page"><div class="container-fluid p-0"><div class="row g-0">
    <div class="col-lg-6 login-visual"><div class="brand p-0"><span class="brand-mark"><i class="bi bi-building-check"></i></span><span><span class="brand-name d-block">Luma Concierge</span><span class="brand-caption d-block">Hotel service platform</span></span></div><div class="login-quote"><div class="eyebrow mb-3" style="color:#e6bd8f">Единый центр управления</div><h2 class="display-5 fw-semibold mb-3" style="letter-spacing:-.045em">Сервис, который гости запоминают.</h2><p class="text-white-50 mb-0">Управляйте отелями, командой и качеством обслуживания из одной спокойной и понятной системы.</p></div></div>
    <div class="col-lg-6 login-panel"><div class="login-card"><div class="d-lg-none mb-5"><span class="brand-mark" style="background:var(--hc-navy)"><i class="bi bi-building-check"></i></span></div><div class="eyebrow">Platform console</div><h1 class="mt-2 mb-2">С возвращением</h1><p class="text-secondary mb-4">Войдите в аккаунт администратора платформы.</p>
        <form method="POST" action="{{ route('login.store') }}">@csrf
            <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="admin@luma.test" autofocus>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><div class="d-flex justify-content-between"><label class="form-label" for="password">Пароль</label><span class="form-hint">Минимум 8 символов</span></div><input class="form-control" id="password" type="password" name="password" placeholder="••••••••" required></div>
            <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="remember" value="1" id="remember"><label class="form-check-label small" for="remember">Запомнить меня</label></div>
            <button class="btn btn-primary w-100 py-3">Войти в платформу <i class="bi bi-arrow-right ms-2"></i></button>
        </form>
        @if(app()->environment('local'))
            <div class="d-flex align-items-center gap-3 my-4"><span class="border-top flex-grow-1"></span><span class="text-secondary small">демо</span><span class="border-top flex-grow-1"></span></div>
            <form method="POST" action="{{ route('demo.platform-login') }}">@csrf<button class="btn btn-light border w-100 py-3"><i class="bi bi-lightning-charge-fill me-2"></i>Войти администратором без пароля</button></form>
        @endif
        <p class="text-secondary text-center mt-4 mb-0" style="font-size:11px">Защищённая зона · Доступ только для администраторов</p>
    </div></div>
</div></div></body></html>
