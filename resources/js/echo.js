import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const key = import.meta.env.VITE_PUSHER_APP_KEY;
const companyId = document.querySelector('meta[name="current-company-id"]')?.content;

if (key && companyId) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
        wsHost: import.meta.env.VITE_PUSHER_HOST || undefined,
        wsPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 80),
        wssPort: Number(import.meta.env.VITE_PUSHER_PORT ?? 443),
        forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    window.Echo.private(`company.${companyId}`).listen('.request.changed', event => {
        window.dispatchEvent(new CustomEvent('workspace:request-changed', { detail: event }));
    });
}
