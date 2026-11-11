/* Midas POS — Service Worker stub (offline foundation)
 *
 * TODO: cache shell assets, queue failed POS requests, background sync.
 * This file registers successfully but intentionally does minimal caching.
 */
const CACHE = 'midas-pos-shell-v0';

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(['/pos', '/manifest.webmanifest'])).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
  // Pass-through for now; offline strategies land in later iterations.
  event.respondWith(fetch(event.request).catch(() => caches.match(event.request)));
});
