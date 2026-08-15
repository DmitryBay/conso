import * as bootstrap from 'bootstrap';
import './echo';

window.bootstrap = bootstrap;

const guestKiosk = document.body.classList.contains('guest-kiosk');

if (guestKiosk) {
    const standalone = window.matchMedia('(display-mode: standalone)').matches
        || window.matchMedia('(display-mode: fullscreen)').matches
        || window.navigator.standalone === true;

    document.documentElement.classList.toggle('pwa-standalone', standalone);
    document.addEventListener('dragstart', event => event.preventDefault());
    document.addEventListener('gesturestart', event => event.preventDefault());
}

if (document.body.classList.contains('guest-catalog-page')) {
    window.scrollTo(0, 0);
    document.addEventListener('touchmove', event => {
        if (!event.target.closest('.modal')) event.preventDefault();
    }, { passive: false });
}

const serviceWorkerUrl = document.querySelector('meta[name="webpush-service-worker"]')?.content;
const serviceWorkerScope = document.querySelector('meta[name="webpush-scope"]')?.content;
let serviceWorkerRegistration;

const registerServiceWorker = () => {
    if (!serviceWorkerUrl || !serviceWorkerScope || !('serviceWorker' in navigator) || !window.isSecureContext) {
        return Promise.resolve(null);
    }

    serviceWorkerRegistration ??= navigator.serviceWorker.register(serviceWorkerUrl, {
        scope: serviceWorkerScope,
        updateViaCache: 'none',
    }).then(registration => {
        registration.update().catch(() => {});
        return registration;
    });

    return serviceWorkerRegistration;
};

if (serviceWorkerUrl && 'serviceWorker' in navigator && window.isSecureContext) {
    const hadController = Boolean(navigator.serviceWorker.controller);
    const reloadKey = 'service-worker-reloading';
    window.setTimeout(() => sessionStorage.removeItem(reloadKey), 5000);
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        if (!hadController || sessionStorage.getItem(reloadKey)) return;
        sessionStorage.setItem(reloadKey, '1');
        window.location.reload();
    });
    window.addEventListener('load', () => registerServiceWorker().catch(() => {}));
}

document.querySelectorAll('[data-guest-menu]').forEach(menu => {
    const track = menu.querySelector('[data-menu-track]');
    const pages = [...menu.querySelectorAll('[data-menu-page]')];
    const previous = menu.querySelector('[data-menu-prev]');
    const next = menu.querySelector('[data-menu-next]');
    const dots = [...menu.querySelectorAll('[data-menu-dot]')];
    let activePage = 0;
    let scrollFrame;

    const update = index => {
        activePage = Math.max(0, Math.min(index, pages.length - 1));
        if (previous) previous.disabled = activePage === 0;
        if (next) next.disabled = activePage === pages.length - 1;
        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle('active', dotIndex === activePage);
            if (dotIndex === activePage) dot.setAttribute('aria-current', 'true');
            else dot.removeAttribute('aria-current');
        });
    };

    const goTo = index => {
        const target = pages[Math.max(0, Math.min(index, pages.length - 1))];
        if (!target) return;
        track.scrollTo({ left: target.offsetLeft - pages[0].offsetLeft, behavior: 'smooth' });
        update(pages.indexOf(target));
    };

    previous?.addEventListener('click', () => goTo(activePage - 1));
    next?.addEventListener('click', () => goTo(activePage + 1));
    dots.forEach(dot => dot.addEventListener('click', () => goTo(Number(dot.dataset.menuDot))));
    track.addEventListener('scroll', () => {
        cancelAnimationFrame(scrollFrame);
        scrollFrame = requestAnimationFrame(() => {
            const closest = pages.reduce((best, page, index) => (
                Math.abs(page.offsetLeft - pages[0].offsetLeft - track.scrollLeft) < Math.abs(pages[best].offsetLeft - pages[0].offsetLeft - track.scrollLeft) ? index : best
            ), 0);
            update(closest);
        });
    }, { passive: true });
    window.addEventListener('resize', () => track.scrollTo({ left: (pages[activePage]?.offsetLeft ?? pages[0].offsetLeft) - pages[0].offsetLeft }));
    update(0);
});

const ordersPoller = document.querySelector('[data-order-status-poller]');
const ordersStatusUrl = document.querySelector('meta[name="guest-orders-status-url"]')?.content;

if (ordersPoller && ordersStatusUrl) {
    const refreshButton = document.querySelector('[data-orders-refresh]');
    const refreshStatus = document.querySelector('[data-orders-refresh-status]');
    let requestInFlight = false;

    const localOrders = () => new Map(
        [...document.querySelectorAll('[data-order-id]')].map(element => [element.dataset.orderId, {
            status: element.dataset.orderStatus,
            payment_status: element.dataset.paymentStatus || '',
        }])
    );

    const changed = remoteOrders => {
        const local = localOrders();
        const currentOrderId = ordersPoller.dataset.orderId;
        if (currentOrderId) {
            const remote = remoteOrders.find(order => order.id === currentOrderId);
            const current = local.get(currentOrderId);
            return !remote || !current || remote.status !== current.status || (remote.payment_status || '') !== current.payment_status;
        }

        if (remoteOrders.length !== local.size) return true;
        return remoteOrders.some(order => {
            const current = local.get(order.id);
            return !current || order.status !== current.status || (order.payment_status || '') !== current.payment_status;
        });
    };

    const announce = message => {
        if (refreshStatus) refreshStatus.textContent = message;
    };

    const checkStatuses = async manual => {
        if (requestInFlight) return;
        requestInFlight = true;
        refreshButton?.setAttribute('aria-busy', 'true');
        refreshButton?.classList.add('is-refreshing');

        try {
            const response = await fetch(ordersStatusUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Status request failed');
            const payload = await response.json();

            if (changed(payload.orders || [])) {
                announce(ordersPoller.dataset.updatedMessage);
                window.location.reload();
                return;
            }
            if (manual) announce(ordersPoller.dataset.currentMessage);
        } catch {
            if (manual) announce(ordersPoller.dataset.errorMessage);
        } finally {
            requestInFlight = false;
            refreshButton?.removeAttribute('aria-busy');
            refreshButton?.classList.remove('is-refreshing');
        }
    };

    refreshButton?.addEventListener('click', () => checkStatuses(true));
    window.setInterval(() => {
        if (document.visibilityState === 'visible') checkStatuses(false);
    }, 15000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') checkStatuses(false);
    });
}

const pushSettings = document.getElementById('pushSettings');

if (pushSettings) {
    const publicKey = document.querySelector('meta[name="webpush-public-key"]')?.content;
    const storeUrl = document.querySelector('meta[name="webpush-store-url"]')?.content;
    const testUrl = document.querySelector('meta[name="webpush-test-url"]')?.content;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const toggleButton = document.getElementById('pushToggleButton');
    const testButton = document.getElementById('pushTestButton');
    const status = document.getElementById('pushStatus');
    let registration;
    let subscription;

    const decodeKey = value => {
        const padding = '='.repeat((4 - value.length % 4) % 4);
        const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(base64), character => character.charCodeAt(0));
    };

    const request = async (url, method, payload = {}) => {
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        if (!response.ok) throw new Error('Push request failed');
        return response.json();
    };

    const updateState = () => {
        const enabled = Boolean(subscription);
        status.textContent = pushSettings.dataset[enabled ? 'enabled' : 'disabled'];
        toggleButton.querySelector('span').textContent = pushSettings.dataset[enabled ? 'disable' : 'enable'];
        toggleButton.classList.toggle('btn-primary', !enabled);
        toggleButton.classList.toggle('btn-light', enabled);
        if (testButton) testButton.hidden = !enabled;
    };

    const initializePush = async () => {
        if (!window.isSecureContext || !publicKey || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            status.textContent = pushSettings.dataset.unsupported;
            toggleButton.disabled = true;
            return;
        }

        registration = await registerServiceWorker();
        if (!registration) throw new Error('Service worker unavailable');
        await navigator.serviceWorker.ready;
        subscription = await registration.pushManager.getSubscription();
        if (Notification.permission === 'denied') {
            status.textContent = pushSettings.dataset.denied;
            toggleButton.disabled = true;
            return;
        }
        if (!subscription && Notification.permission === 'granted') {
            subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: decodeKey(publicKey),
            });
        }
        if (subscription) {
            const data = subscription.toJSON();
            await request(storeUrl, 'POST', {
                endpoint: data.endpoint,
                keys: data.keys,
                content_encoding: PushManager.supportedContentEncodings?.[0] || 'aes128gcm',
            });
        }
        updateState();
    };

    toggleButton.addEventListener('click', async () => {
        toggleButton.disabled = true;
        try {
            if (subscription) {
                await request(storeUrl, 'DELETE', { endpoint: subscription.endpoint });
                await subscription.unsubscribe();
                subscription = null;
            } else {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    status.textContent = pushSettings.dataset.denied;
                    return;
                }
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: decodeKey(publicKey),
                });
                const data = subscription.toJSON();
                await request(storeUrl, 'POST', {
                    endpoint: data.endpoint,
                    keys: data.keys,
                    content_encoding: PushManager.supportedContentEncodings?.[0] || 'aes128gcm',
                });
            }
            updateState();
        } catch {
            status.textContent = pushSettings.dataset.error;
        } finally {
            toggleButton.disabled = false;
        }
    });

    testButton?.addEventListener('click', async () => {
        testButton.disabled = true;
        try {
            await request(testUrl, 'POST');
            status.textContent = pushSettings.dataset.testSent;
        } catch {
            status.textContent = pushSettings.dataset.error;
        } finally {
            testButton.disabled = false;
        }
    });

    initializePush().catch(() => {
        status.textContent = pushSettings.dataset.error;
        toggleButton.disabled = true;
    });
}

const guestStatusUrl = document.querySelector('meta[name="guest-session-status-url"]')?.content;
const guestAccessUrl = document.querySelector('meta[name="guest-access-url"]')?.content;

const clearGuestNotifications = async () => {
    if (!('serviceWorker' in navigator)) return;
    const registration = await navigator.serviceWorker.getRegistration('/guest/');
    registration?.active?.postMessage({ type: 'CLEAR_GUEST_NOTIFICATIONS' });
};

document.querySelector('.guest-logout-form')?.addEventListener('submit', () => {
    clearGuestNotifications().catch(() => {});
});

if (guestStatusUrl && guestAccessUrl) {
    const verifyGuestSession = async () => {
        try {
            const response = await fetch(guestStatusUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin',
            });

            if (response.status === 401) {
                await clearGuestNotifications();
                window.location.replace(guestAccessUrl);
            }
        } catch {
            // Keep the current screen available during a temporary hotel Wi-Fi outage.
        }
    };

    window.setInterval(verifyGuestSession, 60000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') verifyGuestSession();
    });
}

window.addEventListener('workspace:request-changed', () => {
    const notice = document.createElement('div');
    notice.className = 'realtime-notice';
    notice.innerHTML = '<i class="bi bi-lightning-charge-fill"></i><span><strong>Доска обновилась</strong><small>Получено новое событие заявки</small></span><button type="button" aria-label="Закрыть">&times;</button>';
    document.body.appendChild(notice);
    notice.querySelector('button').addEventListener('click', () => notice.remove());
    setTimeout(() => notice.remove(), 6000);
});
