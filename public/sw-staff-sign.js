// Unregister legacy service worker that cached /staff/sign HTML (caused stale logged-in pages).
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});
self.addEventListener('fetch', () => {});
