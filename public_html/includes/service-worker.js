// MD Geek - Service Worker pour Progressive Web App
const CACHE_NAME = 'mdgeek-pwa-cache-v1';
const OFFLINE_PAGE = '/offline.html';

// Ressources à mettre en cache lors de l'installation
const PRECACHE_ASSETS = [
  '/',
  '/index.php',
  '/pages/login.php',
  '/offline.html',
  '/manifest.json',
  '/assets/css/dashboard-new.css',
  '/assets/css/style.css',
  '/assets/js/app.js',
  '/assets/js/modern-interactions.js',
  '/assets/fonts/materialdesignicons-webfont.woff2',
  '/assets/images/logo/mdgeek-logo.png',
  // Ajoutez d'autres ressources importantes ici
];

// Installation du Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      console.log('[Service Worker] Mise en cache préventive des ressources');
      return cache.addAll(PRECACHE_ASSETS);
    })
    .then(() => self.skipWaiting()) // Forcer l'activation du nouveau SW
  );
});

// Activation du Service Worker (nettoyage des anciens caches)
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.filter(cacheName => {
          return cacheName !== CACHE_NAME;
        }).map(cacheName => {
          console.log('[Service Worker] Suppression de l\'ancien cache:', cacheName);
          return caches.delete(cacheName);
        })
      );
    }).then(() => {
      console.log('[Service Worker] Revendication des clients');
      return self.clients.claim();
    })
  );
});

// Gestion des requêtes réseau
self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);

  // Ne pas intercepter les requêtes pour le panneau de développement
  if (url.pathname.startsWith('/devtools/')) {
    return;
  }

  // Stratégie pour les requêtes API (Network First puis Cache)
  if (url.pathname.includes('/api/') || request.url.includes('action=')) {
    event.respondWith(networkFirstStrategy(request));
    return;
  }

  // Stratégie pour les ressources statiques (Cache First)
  if (
    request.destination === 'style' || 
    request.destination === 'script' || 
    request.destination === 'font' || 
    request.destination === 'image' ||
    url.pathname.endsWith('.css') ||
    url.pathname.endsWith('.js') ||
    url.pathname.endsWith('.woff2') ||
    url.pathname.endsWith('.png') ||
    url.pathname.endsWith('.jpg') ||
    url.pathname.endsWith('.jpeg') ||
    url.pathname.endsWith('.svg') ||
    url.pathname.endsWith('.gif')
  ) {
    event.respondWith(cacheFirstStrategy(request));
    return;
  }

  // Stratégie pour les pages HTML (Network First, puis Cache, puis Page Hors-ligne)
  event.respondWith(networkThenCacheThenOfflineStrategy(request));
});

// Stratégie : Network First, puis Cache
async function networkFirstStrategy(request) {
  try {
    // Essayer d'abord depuis le réseau
    const networkResponse = await fetch(request);
    
    // Si la requête API a réussi, mettre à jour le cache
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    // En cas d'échec réseau, essayer le cache
    console.log('[Service Worker] Réseau indisponible, utilisation du cache pour:', request.url);
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Si pas dans le cache, renvoyer une réponse d'erreur personnalisée
    return new Response(
      JSON.stringify({ error: 'Réseau indisponible et ressource non mise en cache' }),
      { 
        status: 503, 
        headers: { 'Content-Type': 'application/json' } 
      }
    );
  }
}

// Stratégie : Cache First, puis Network (pour ressources statiques)
async function cacheFirstStrategy(request) {
  const cachedResponse = await caches.match(request);
  
  if (cachedResponse) {
    // Utiliser le cache si disponible
    return cachedResponse;
  }
  
  try {
    // Sinon, aller sur le réseau
    const networkResponse = await fetch(request);
    
    // Mettre en cache la nouvelle ressource
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('[Service Worker] Erreur lors de la récupération de la ressource:', request.url);
    // Pour les ressources statiques, pas de fallback spécifique
    return new Response('Ressource non disponible', { status: 404 });
  }
}

// Stratégie : Network, puis Cache, puis Page Hors-ligne
async function networkThenCacheThenOfflineStrategy(request) {
  try {
    // Essayer d'abord depuis le réseau
    const networkResponse = await fetch(request);
    
    // Mettre à jour le cache avec la dernière version
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('[Service Worker] Réseau indisponible pour page HTML, utilisation du cache');
    
    // Essayer de servir depuis le cache
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Si la page n'est pas dans le cache, servir la page hors-ligne
    return await caches.match(OFFLINE_PAGE);
  }
}

// Gestion des événements de synchronisation en arrière-plan
self.addEventListener('sync', event => {
  if (event.tag === 'sync-data') {
    event.waitUntil(syncPendingRequests());
  }
});

// Fonction pour synchroniser les requêtes en attente
async function syncPendingRequests() {
  try {
    const pendingRequests = await getPendingRequestsFromIndexedDB();
    
    // Traiter chaque requête en attente
    for (const request of pendingRequests) {
      try {
        await fetch(request.url, {
          method: request.method,
          headers: request.headers,
          body: request.body,
          credentials: 'include'
        });
        
        // Si réussi, marquer comme synchronisée dans IndexedDB
        await markRequestAsSynced(request.id);
      } catch (error) {
        console.error('[Service Worker] Échec de synchronisation de la requête:', request.url);
      }
    }
  } catch (error) {
    console.error('[Service Worker] Erreur lors de la synchronisation des requêtes:', error);
  }
}

// Fonction fictive pour récupérer les requêtes en attente depuis IndexedDB
// Cette fonction doit être implémentée côté client
function getPendingRequestsFromIndexedDB() {
  // Implémentation à faire
  return Promise.resolve([]);
}

// Fonction fictive pour marquer une requête comme synchronisée
function markRequestAsSynced(requestId) {
  // Implémentation à faire
  return Promise.resolve();
}

// Gestion des notifications push
self.addEventListener('push', event => {
  const data = event.data.json();
  
  const options = {
    body: data.body,
    icon: '/assets/images/icons/icon-192x192.png',
    badge: '/assets/images/icons/badge-icon.png',
    vibrate: [100, 50, 100],
    data: {
      url: data.url || '/'
    }
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Gestion des clics sur notifications
self.addEventListener('notificationclick', event => {
  event.notification.close();
  
  event.waitUntil(
    clients.openWindow(event.notification.data.url)
  );
}); 