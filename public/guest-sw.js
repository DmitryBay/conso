const OFFLINE_HTML = `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#183c36"><title>Concierge offline</title><style>body{align-items:center;background:#f6f3ed;color:#183c36;display:flex;font:16px -apple-system,BlinkMacSystemFont,sans-serif;justify-content:center;margin:0;min-height:100vh;text-align:center}.card{max-width:360px;padding:40px}.mark{align-items:center;background:#183c36;border-radius:20px;color:#e6bd8f;display:flex;font-size:32px;height:72px;justify-content:center;margin:0 auto 24px;width:72px}h1{font-family:Georgia,serif;font-weight:400}p{color:#6e7d78;line-height:1.6}</style></head><body><main class="card"><div class="mark">●</div><h1>Connection paused</h1><p>The hotel concierge will return automatically when Wi-Fi is available.</p></main><script>setInterval(()=>location.reload(),15000)</script></body></html>`;

const APP_VERSION = '2026-08-15.3';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));

self.addEventListener('push', event => {
    if (!event.data) return;

    const payload = event.data.json();
    const { title, ...options } = payload;
    event.waitUntil(self.registration.showNotification(title || 'Luma Concierge', options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const target = event.notification.data?.url || '/';

    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
        const existing = clients.find(client => client.url.startsWith(self.location.origin));
        if (existing) return existing.navigate(target).then(client => client.focus());
        return self.clients.openWindow(target);
    }));
});

self.addEventListener('message', event => {
    if (event.data?.type !== 'CLEAR_GUEST_NOTIFICATIONS') return;
    event.waitUntil(self.registration.getNotifications().then(notifications => {
        notifications.forEach(notification => notification.close());
    }));
});

self.addEventListener('fetch', event => {
    if (event.request.mode !== 'navigate') return;

    event.respondWith(fetch(event.request, { cache: 'no-store' }).catch(() => new Response(OFFLINE_HTML, {
        headers: { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' },
    })));
});
