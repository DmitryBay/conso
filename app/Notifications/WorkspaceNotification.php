<?php

namespace App\Notifications;

use App\Models\PlatformSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class WorkspaceNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $payload) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (($this->payload['email'] ?? false) && PlatformSetting::enabled('email_notifications_enabled') && $notifiable->email_notifications && $notifiable->email) {
            $channels[] = 'mail';
        }
        if (($this->payload['push'] ?? false) && PlatformSetting::enabled('push_notifications_enabled')) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return Arr::except($this->payload, ['email', 'push']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locales = ['ru', 'uk', 'id', 'en', 'ar', 'he', 'zh', 'ko'];
        $locale = in_array($notifiable->preferred_locale, $locales, true) ? $notifiable->preferred_locale : 'ru';
        $params = $this->payload['params'] ?? [];
        $title = trans($this->payload['title_key'], $params, $locale);
        $body = trans($this->payload['body_key'], $params, $locale);
        $company = $notifiable->company?->name ?? config('app.name');

        return (new MailMessage)
            ->subject($title.' · '.$company)
            ->greeting(trans('workspace.email_greeting', ['name' => $notifiable->name], $locale))
            ->line(trans('workspace.email_attention', [], $locale))
            ->line($body)
            ->action(trans('workspace.email_open_request', [], $locale), $this->payload['url'])
            ->line(trans('workspace.email_footer', ['company' => $company], $locale));
    }

    public function toWebPush(object $notifiable): WebPushMessage
    {
        $locales = ['ru', 'uk', 'id', 'en', 'ar', 'he', 'zh', 'ko'];
        $locale = in_array($notifiable->preferred_locale, $locales, true) ? $notifiable->preferred_locale : 'ru';
        $params = $this->payload['params'] ?? [];

        return (new WebPushMessage)
            ->title(trans($this->payload['title_key'], $params, $locale))
            ->body(trans($this->payload['body_key'], $params, $locale))
            ->icon(asset('app-icons/luma-192.png'))
            ->badge(asset('app-icons/luma-192.png'))
            ->lang($locale)
            ->tag('luma-request-'.($this->payload['request_id'] ?? 'test'))
            ->renotify()
            ->data(['url' => $this->payload['url']])
            ->options(['TTL' => 600]);
    }
}
