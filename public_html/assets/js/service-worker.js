const CACHE_VERSION = 'v2';
const OFFLINE_URL = '/offline.html';

const CORE_ASSETS = [
  '/',
  '/assets/css/style.css',
  '/assets/js/app.js',
  '/assets/images/logo.png',
  '/manifest.json',
  OFFLINE_URL
];

const GOOGLE_FONTS = [
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap'
];
const CACHE_NAME = 'mdgeek-cache-v1';
const CACHE_WHITELIST = [CACHE_NAME];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache ouvert');
        return cache.addAll(CORE_ASSETS);
      })
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then(keys => 
      Promise.all(
        keys.filter(key => key !== CACHE_VERSION && !key.includes(CACHE_VERSION))
          .map(key => caches.delete(key))
      )
    )
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  const url = new URL(req.url);

  // Cache stratégie Network-Falling-Back-to-Cache pour les pages
  if (req.mode === 'navigate') {
    e.respondWith(
      fetch(req)
        .catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }

  // Cache First pour les assets statiques
  if (CORE_ASSETS.some(asset => url.pathname.endsWith(asset)) || 
      GOOGLE_FONTS.some(font => url.href.startsWith(font))) {
    e.respondWith(
      caches.match(req)
        .then(cachedRes => cachedRes || fetch(req)
          .then(res => {
            const resClone = res.clone();
            caches.open(CACHE_NAME)
              .then(cache => cache.put(req, resClone));
            return res;
          })
        )
    );
  } else {
    // Stale-While-Revalidate pour le reste
    e.respondWith(
      caches.match(req)
        .then(cachedRes => {
          const fetchPromise = fetch(req).then(networkRes => {
            const resClone = networkRes.clone();
            caches.open(CACHE_NAME)
              .then(cache => cache.put(req, resClone));
          });
          return cachedRes || fetchPromise;
        })
    );
  }
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (!CACHE_WHITELIST.includes(cacheName)) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});