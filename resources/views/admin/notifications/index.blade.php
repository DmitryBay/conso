@extends('layouts.admin')
@section('title', 'Уведомления')
@section('content')
<div class="d-flex align-items-end justify-content-between gap-3 mb-4">
    <div>
        <div class="eyebrow">Центр событий</div>
        <h1 class="page-title">Уведомления</h1>
        <p class="page-subtitle">Важные события и изменения на платформе.</p>
    </div>
    @if(auth()->user()->unreadNotifications()->exists())
        <form method="POST" action="{{ route('platform.notifications.read-all') }}">
            @csrf
            @method('PATCH')
            <button class="btn btn-light"><i class="bi bi-check2-all me-2"></i>Прочитать все</button>
        </form>
    @endif
</div>
<div class="surface-card push-settings-card mb-3" id="pushSettings" data-enabled="Push-уведомления включены" data-disabled="Push-уведомления выключены" data-enable="Включить push" data-disable="Выключить push" data-unsupported="Push-уведомления не поддерживаются этим браузером" data-denied="Разрешение на уведомления отключено в настройках браузера" data-error="Не удалось настроить push-уведомления" data-test-sent="Тестовое уведомление отправлено">
    <div class="push-settings-icon"><i class="bi bi-phone-vibrate"></i></div>
    <div class="flex-grow-1"><h2>Push-уведомления</h2><p>Получайте важные события платформы, даже когда кабинет закрыт.</p><small id="pushStatus">Проверяем настройки уведомлений…</small></div>
    <div class="d-flex gap-2 flex-wrap"><button class="btn btn-light" id="pushTestButton" type="button" hidden><i class="bi bi-send me-2"></i>Проверить</button><button class="btn btn-primary" id="pushToggleButton" type="button"><i class="bi bi-bell me-2"></i><span>Включить push</span></button></div>
</div>
<div class="surface-card overflow-hidden">
    <div class="notification-list">
        @forelse($notifications as $notification)
            @php($notificationParams = $notification->data['params'] ?? [])
            @php($notificationParams['status'] = isset($notificationParams['status']) ? __('workspace.status.'.$notificationParams['status']) : null)
            <a class="notification-item {{ $notification->read_at ? '' : 'is-unread' }}" href="{{ route('platform.notifications.read', $notification->id) }}">
                <span class="notification-icon"><i class="bi {{ $notification->data['icon'] ?? 'bi-bell' }}"></i></span>
                <span class="flex-grow-1">
                    <span class="d-flex align-items-center gap-2">
                        <strong>{{ isset($notification->data['title_key']) ? __($notification->data['title_key'], $notificationParams) : ($notification->data['title'] ?? 'Событие платформы') }}</strong>
                        @unless($notification->read_at)<span class="unread-dot"></span>@endunless
                    </span>
                    <span class="notification-body">{{ isset($notification->data['body_key']) ? __($notification->data['body_key'], $notificationParams) : ($notification->data['body'] ?? '') }}</span>
                    <span class="notification-time">{{ $notification->created_at->diffForHumans() }}</span>
                </span>
                <i class="bi bi-chevron-right text-secondary"></i>
            </a>
        @empty
            <div class="empty-builder">
                <span class="empty-builder-icon"><i class="bi bi-bell-slash"></i></span>
                <h3>Уведомлений пока нет</h3>
                <p>Новые события платформы появятся здесь.</p>
            </div>
        @endforelse
    </div>
    @if($notifications->hasPages())<div class="p-3 border-top">{{ $notifications->links() }}</div>@endif
</div>
@endsection
