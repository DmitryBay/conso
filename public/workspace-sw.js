self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));

self.addEventListener('message', event => {
    if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
});

self.addEventListener('push', event => {
    if (!event.data) return;

    const payload = event.data.json();
    const { title, ...options } = payload;
    event.waitUntil(self.registration.showNotification(title || 'Luma Concierge', options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const target = event.notification.data?.url || '/workspace/notifications';

    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
        const existing = clients.find(client => client.url.startsWith(self.location.origin));
        if (existing) return existing.navigate(target).then(client => client.focus());
        return self.clients.openWindow(target);
    }));
});
