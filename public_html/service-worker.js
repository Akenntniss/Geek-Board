const CACHE_VERSION = 'v3';
const OFFLINE_URL = '/offline.html';

const CORE_ASSETS = [
  '/',
  '/assets/css/style.css',
  '/assets/js/app.js',
  '/assets/images/logo.png',
  '/manifest.json',
  OFFLINE_URL
];

// Ajout d'assets supplémentaires à mettre en cache
const ADDITIONAL_ASSETS = [
  '/assets/js/utils.js',
  '/assets/css/responsive.css',
  '/assets/images/pwa-icons/icon-192x192.png',
  '/assets/images/pwa-icons/icon-512x512.png',
  '/assets/sounds/notification.mp3'
];

const GOOGLE_FONTS = [
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap'
];
// Utiliser la version du cache dans le nom
const CACHE_NAME = `mdgeek-cache-${CACHE_VERSION}`;
const CACHE_WHITELIST = [CACHE_NAME];

// Gestion des notifications push
self.addEventListener('push', function(event) {
  if (event.data) {
    const notificationData = event.data.json();
    
    const title = notificationData.title || 'GeekBoard';
    const options = {
      body: notificationData.body || 'Nouvelle notification',
      icon: notificationData.icon || '/assets/images/pwa-icons/icon-192x192.png',
      badge: '/assets/images/pwa-icons/icon-72x72.png',
      data: {
        url: notificationData.url || '/',
        id: notificationData.id || null,
        timestamp: notificationData.timestamp || new Date().getTime()
      },
      tag: notificationData.tag || 'default',
      renotify: notificationData.renotify || false,
      actions: notificationData.actions || [],
      vibrate: notificationData.vibrate || [100, 50, 100],
      // Ajouter un son pour les notifications
      silent: notificationData.silent || false,
      sound: '/assets/sounds/notification.mp3'
    };
    
    event.waitUntil(
      self.registration.showNotification(title, options)
    );
  }
});

// Gestion du clic sur une notification
self.addEventListener('notificationclick', function(event) {
  event.notification.close();
  
  // Récupérer les données de la notification
  const notificationData = event.notification.data;
  const targetUrl = notificationData.url;
  
  // Ouvrir ou focaliser un onglet existant
  event.waitUntil(
    clients.matchAll({type: 'window'}).then(windowClients => {
      // Vérifier si un onglet est déjà ouvert sur l'URL cible
      for (const client of windowClients) {
        if (client.url === targetUrl && 'focus' in client) {
          return client.focus();
        }
      }
      
      // Si aucun onglet n'est ouvert sur cette URL, en ouvrir un nouveau
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});

// Gestion des événements de fermeture de notification
self.addEventListener('notificationclose', function(event) {
  // On pourrait ajouter ici une logique pour suivre quand les utilisateurs ferment les notifications
  console.log('Notification fermée', event.notification.data);
});

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache ouvert');
        return Promise.allSettled(
          [...CORE_ASSETS, ...ADDITIONAL_ASSETS].map(url => 
            fetch(url)
              .then(response => {
                if (!response.ok) {
                  throw new Error(`Échec de récupération: ${url}, statut: ${response.status}`);
                }
                return cache.put(url, response);
              })
              .catch(error => {
                console.warn(`Impossible de mettre en cache l'asset: ${url}`, error);
              })
          )
        );
      })
  );
  // Forcer l'activation immédiate du service worker
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then(keys => 
      Promise.all(
        keys.filter(key => key !== CACHE_NAME && !CACHE_WHITELIST.includes(key))
          .map(key => {
            console.log(`Suppression du cache obsolète: ${key}`);
            return caches.delete(key);
          })
      )
    ).then(() => {
      // Prendre le contrôle des clients non contrôlés
      return self.clients.claim();
    })
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  const url = new URL(req.url);

  // Ne pas traiter les requêtes POST
  if (req.method === 'POST') {
    return;
  }

  // Ignorer les requêtes avec timestamp (rafraîchissement manuel)
  if (url.searchParams.has('timestamp')) {
    e.respondWith(fetch(req));
    return;
  }

  // Ne pas traiter les requêtes vers les fichiers PHP (AJAX)
  if (url.pathname.endsWith('.php') || url.pathname.includes('.php')) {
    // Ignorer complètement les requêtes vers des fichiers PHP
    return;
  }

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
      ADDITIONAL_ASSETS.some(asset => url.pathname.endsWith(asset)) ||
      GOOGLE_FONTS.some(font => url.href.startsWith(font)) ||
      /\.(css|js|png|jpg|jpeg|gif|svg|webp|ico|woff2|ttf)$/.test(url.pathname)) {
    e.respondWith(
      caches.match(req)
        .then(cachedRes => cachedRes || fetch(req)
          .then(res => {
            // Vérifier si la réponse peut être mise en cache
            if (!res.ok || res.status === 206 || (res.type !== 'basic' && res.type !== 'cors')) {
              return res;
            }
            
            // Cloner la réponse pour pouvoir la mettre en cache
            const resClone = res.clone();
            
            // Essayer de mettre en cache la réponse
            caches.open(CACHE_NAME)
              .then(cache => {
                try {
                  // Pour éviter l'erreur 206, on vérifie à nouveau le statut avant de mettre en cache
                  if (resClone.status !== 206 && resClone.ok) {
                    return cache.put(req, resClone);
                  }
                } catch(err) {
                  console.warn('Erreur lors de la mise en cache:', err);
                }
              })
              .catch(err => console.warn('Erreur lors de la mise en cache:', err));
            
            return res;
          })
          .catch(err => {
            console.warn('Erreur fetch:', err);
            return caches.match(OFFLINE_URL);
          })
        )
    );
  } else {
    // Stale-While-Revalidate pour le reste
    e.respondWith(
      caches.match(req)
        .then(cachedRes => {
          const fetchPromise = fetch(req)
            .then(networkRes => {
              // Vérifier si la réponse peut être mise en cache
              if (!networkRes.ok || networkRes.status === 206 || (networkRes.type !== 'basic' && networkRes.type !== 'cors')) {
                return networkRes;
              }
              
              // Cloner la réponse pour pouvoir la mettre en cache
              const resClone = networkRes.clone();
              
              // Essayer de mettre en cache la réponse
              caches.open(CACHE_NAME)
                .then(cache => {
                  try {
                    // Pour éviter l'erreur 206, on vérifie à nouveau le statut avant de mettre en cache
                    if (resClone.status !== 206 && resClone.ok) {
                      return cache.put(req, resClone);
                    }
                  } catch(err) {
                    console.warn('Erreur lors de la mise en cache:', err);
                  }
                })
                .catch(err => console.warn('Erreur lors de la mise en cache:', err));
              
              return networkRes;
            })
            .catch(err => {
              console.warn('Erreur fetch:', err);
              return cachedRes || caches.match(OFFLINE_URL);
            });
          
          return cachedRes || fetchPromise;
        })
    );
  }
});

async function networkFirstWithOfflineFallback(event) {
  const req = event.request;
  try {
    // Vérifier si la requête est mise en cache par défaut
    if (!shouldCache(req.url)) {
      return fetch(req);
    }
    
    // Essayer de récupérer depuis le réseau d'abord
    const networkResponse = await fetch(req);
    
    // Ne pas mettre en cache les réponses partielles ou en erreur
    if (networkResponse.ok && networkResponse.status !== 206) {
      // Cloner la réponse pour pouvoir la mettre en cache
      const responseToCache = networkResponse.clone();
      
      // Mettre en cache de manière asynchrone
      caches.open(CACHE_NAME)
        .then(cache => {
          try {
            cache.put(req, responseToCache);
          } catch(err) {
            console.warn('Erreur lors de la mise en cache:', err);
          }
        })
        .catch(err => console.warn('Erreur lors de l\'ouverture du cache:', err));
    }
    
    return networkResponse;
  } catch (error) {
    // En cas d'erreur réseau, essayer le cache
    const cachedResponse = await caches.match(req);
    
    // Retourner la réponse mise en cache ou la page hors ligne
    return cachedResponse || caches.match(OFFLINE_URL);
  }
}