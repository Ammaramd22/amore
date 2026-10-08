/* QRPOS Waiter Panel — PWA service worker (notifications + offline shell) */
const CACHE = 'qrpos-waiter-v1';
const PRECACHE = [
    '/pwa/waiter/offline.html',
    '/pwa/waiter/icon-96.png',
    '/pwa/waiter/icon-192.png',
    '/pwa/waiter/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // App shell navigations: network first, offline fallback
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match('/pwa/waiter/offline.html'))
        );
        return;
    }

    // Static PWA assets: cache first
    if (url.pathname.startsWith('/pwa/waiter/')) {
        event.respondWith(
            caches.match(req).then((cached) => cached || fetch(req).then((res) => {
                const copy = res.clone();
                caches.open(CACHE).then((c) => c.put(req, copy));
                return res;
            }))
        );
    }
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = event.notification.data?.url || '/waiter/dashboard?view=kitchen';
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (const client of clients) {
                if ('focus' in client) {
                    if (client.navigate) client.navigate(target);
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(target);
            }
        })
    );
});

self.addEventListener('message', (event) => {
    const data = event.data || {};
    if (data.type !== 'notify' || !data.title) return;
    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body || '',
            tag: data.tag || 'waiter-kitchen',
            renotify: true,
            icon: data.icon || '/pwa/waiter/icon-192.png',
            badge: data.badge || '/pwa/waiter/icon-96.png',
            data: { url: data.url || '/waiter/dashboard?view=kitchen' },
        })
    );
});
