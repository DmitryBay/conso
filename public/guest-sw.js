const OFFLINE_HTML = `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#183c36"><title>Concierge offline</title><style>body{align-items:center;background:#f6f3ed;color:#183c36;display:flex;font:16px -apple-system,BlinkMacSystemFont,sans-serif;justify-content:center;margin:0;min-height:100vh;text-align:center}.card{max-width:360px;padding:40px}.mark{align-items:center;background:#183c36;border-radius:20px;color:#e6bd8f;display:flex;font-size:32px;height:72px;justify-content:center;margin:0 auto 24px;width:72px}h1{font-family:Georgia,serif;font-weight:400}p{color:#6e7d78;line-height:1.6}</style></head><body><main class="card"><div class="mark">●</div><h1>Connection paused</h1><p>The hotel concierge will return automatically when Wi-Fi is available.</p></main><script>setInterval(()=>location.reload(),15000)</script></body></html>`;

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', event => {
    if (event.request.mode !== 'navigate') return;

    event.respondWith(fetch(event.request, { cache: 'no-store' }).catch(() => new Response(OFFLINE_HTML, {
        headers: { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' },
    })));
});
