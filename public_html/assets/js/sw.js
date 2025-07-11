// Service Worker basique pour PWA
self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open('atelier-v1').then((cache) => {
      return cache.addAll([
        '/',
        '/index.php',
        '/assets/css/professional-desktop.css',
        '/assets/js/professional-desktop.js',
        '/manifest.json'
      ]);
    })
  );
});

self.addEventListener('fetch', (e) => {
  e.respondWith(
    caches.match(e.request).then((response) => {
      return response || fetch(e.request);
    })
  );
});