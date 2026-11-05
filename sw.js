/* Minimal service worker: offline-tolerant network-first for pages, cache-first for assets */
const CACHE = 'ech-v1';
self.addEventListener('install', e => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(clients.claim()));
self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);
  if (e.request.method !== 'GET') return;
  if (/\.(css|js|png|jpe?g|webp|woff2?)$/.test(url.pathname)) {
    e.respondWith(
      caches.open(CACHE).then(c => c.match(e.request).then(hit => hit ||
        fetch(e.request).then(res => { if (res.ok) c.put(e.request, res.clone()); return res; })))
    );
  }
});
