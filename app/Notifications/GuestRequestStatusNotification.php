<?php

namespace App\Notifications;

use App\Models\GuestSession;
use App\Models\PlatformSetting;
use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class GuestRequestStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ServiceRequest $serviceRequest,
        private readonly bool $sendEmail = false,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [];

        if (PlatformSetting::enabled('push_notifications_enabled')) {
            $channels[] = WebPushChannel::class;
        }
        if ($this->sendEmail && PlatformSetting::enabled('email_notifications_enabled') && $notifiable->routeNotificationForMail()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(GuestSession $notifiable): MailMessage
    {
        $locale = $this->resolveLocale($notifiable);
        $params = $this->params($locale);

        return (new MailMessage)
            ->subject(trans('guest.notification_status_title', $params, $locale).' · '.$this->serviceRequest->company->name)
            ->greeting(trans('guest.notification_email_greeting', ['name' => $notifiable->guest_name ?: trans('guest.guest', [], $locale)], $locale))
            ->line(trans('guest.notification_status_body', $params, $locale))
            ->action(trans('guest.notification_open_request', [], $locale), route('guest.orders.show', [$this->serviceRequest->company, $this->serviceRequest]))
            ->line(trans('guest.notification_email_footer', ['company' => $this->serviceRequest->company->name], $locale));
    }

    public function toWebPush(GuestSession $notifiable): WebPushMessage
    {
        $locale = $this->resolveLocale($notifiable);
        $params = $this->params($locale);

        return (new WebPushMessage)
            ->title(trans('guest.notification_status_title', $params, $locale))
            ->body(trans('guest.notification_status_body', $params, $locale))
            ->icon(asset('app-icons/luma-192.png'))
            ->badge(asset('app-icons/luma-192.png'))
            ->lang($locale)
            ->tag('luma-guest-request-'.$this->serviceRequest->id)
            ->renotify()
            ->data(['url' => route('guest.orders.show', [$this->serviceRequest->company, $this->serviceRequest])])
            ->options(['TTL' => 600]);
    }

    private function resolveLocale(GuestSession $notifiable): string
    {
        return in_array($notifiable->locale, ['ru', 'uk', 'id', 'en', 'ar', 'he', 'zh', 'ko'], true)
            ? $notifiable->locale
            : 'en';
    }

    private function params(string $locale): array
    {
        return [
            'request' => $this->serviceRequest->service?->localizedName($locale) ?? $this->serviceRequest->title,
            'room' => $this->serviceRequest->roomDisplayName(),
            'status' => trans('guest.status.'.$this->serviceRequest->status->value, [], $locale),
        ];
    }
}
