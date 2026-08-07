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
