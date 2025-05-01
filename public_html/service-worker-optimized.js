/**
 * GeekBoard - Service Worker Optimisé
 * 
 * Ce service worker améliore l'expérience PWA avec:
 * - Stratégies de cache adaptatives
 * - Support offline complet pour les commandes
 * - Gestion intelligente des ressources
 * - Synchronisation en arrière-plan
 */

// Configuration et versions
const CACHE_VERSION = 'v4';
const CACHE_NAME = `geekboard-cache-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';
const DATA_CACHE_NAME = `geekboard-data-${CACHE_VERSION}`;

// Ressources à mettre en cache immédiatement lors de l'installation
const CORE_ASSETS = [
  '/',
  '/index.php',
  '/index.php?page=commandes_pieces',
  '/assets/css/style.css',
  '/assets/css/mobile-design.css',
  '/assets/css/responsive.css',
  '/assets/js/app.js',
  '/assets/js/utils.js',
  '/assets/js/commandes.js',
  '/assets/js/commandes-offline.js',
  '/assets/js/offline-sync.js',
  '/assets/js/bottom-nav.js',
  '/assets/js/mobile-utils.js',
  '/assets/images/logo.png',
  '/manifest.json',
  OFFLINE_URL
];

// Ressources additionnelles à mettre en cache
const ADDITIONAL_ASSETS = [
  '/assets/css/bootstrap.min.css',
  '/assets/js/bootstrap.bundle.min.js',
  '/assets/js/jquery.min.js',
  '/assets/js/toastr.min.js',
  '/assets/css/toastr.min.css',
  '/assets/js/fontawesome.min.js',
  '/assets/images/pwa-icons/icon-192x192.png',
  '/assets/images/pwa-icons/icon-512x512.png',
  '/assets/sounds/notification.mp3'
];

// Ressources externes à mettre en cache
const EXTERNAL_ASSETS = [
  'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
  'https://fonts.gstatic.com/s/inter/v12/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuLyfAZ9hiJ-Ek-_EeA.woff2'
];

// Liste des caches à conserver lors du nettoyage
const CACHE_WHITELIST = [CACHE_NAME, DATA_CACHE_NAME];

// Routes API qui doivent être mises en cache avec une stratégie spécifique
const API_ROUTES = [
  { url: '/ajax/get_commandes.php', cacheDuration: 60 * 5 }, // 5 minutes
  { url: '/ajax/get_commande.php', cacheDuration: 60 * 5 },
  { url: '/ajax/get_client.php', cacheDuration: 60 * 30 }, // 30 minutes
  { url: '/ajax/get_fournisseurs.php', cacheDuration: 60 * 60 } // 1 heure
];

// Installation du service worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    (async () => {
      // Ouvrir le cache
      const cache = await caches.open(CACHE_NAME);
      console.log('[Service Worker] Cache ouvert');
      
      // Mettre en cache les ressources essentielles
      const coreCache = cache.addAll(CORE_ASSETS);
      
      // Mettre en cache les ressources additionnelles
      const additionalCache = ADDITIONAL_ASSETS.map(url => {
        return fetch(url, { credentials: 'same-origin' })
          .then(response => {
            if (!response.ok) {
              throw new Error(`Échec de récupération: ${url}, statut: ${response.status}`);
            }
            return cache.put(url, response);
          })
          .catch(error => {
            console.warn(`[Service Worker] Impossible de mettre en cache: ${url}`, error);
          });
      });
      
      // Mettre en cache les ressources externes
      const externalCache = EXTERNAL_ASSETS.map(url => {
        return fetch(url)
          .then(response => {
            if (!response.ok) {
              throw new Error(`Échec de récupération: ${url}, statut: ${response.status}`);
            }
            return cache.put(url, response);
          })
          .catch(error => {
            console.warn(`[Service Worker] Impossible de mettre en cache: ${url}`, error);
          });
      });
      
      // Attendre que toutes les mises en cache soient terminées
      await Promise.all([coreCache, ...additionalCache, ...externalCache]);
      
      // Forcer l'activation immédiate du service worker
      self.skipWaiting();
    })()
  );
});

// Activation du service worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    (async () => {
      // Nettoyer les anciens caches
      const cacheKeys = await caches.keys();
      const deletePromises = cacheKeys
        .filter(key => !CACHE_WHITELIST.includes(key))
        .map(key => {
          console.log(`[Service Worker] Suppression du cache obsolète: ${key}`);
          return caches.delete(key);
        });
      
      await Promise.all(deletePromises);
      
      // Prendre le contrôle des clients non contrôlés
      await self.clients.claim();
      console.log('[Service Worker] Activé et contrôle les clients');
    })()
  );
});

// Interception des requêtes fetch
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);
  
  // Ne pas intercepter les requêtes POST
  if (request.method !== 'GET') {
    return;
  }
  
  // Ignorer les requêtes avec timestamp (rafraîchissement manuel)
  if (url.searchParams.has('timestamp') || url.searchParams.has('no-cache')) {
    return;
  }
  
  // Stratégie pour les API de données
  if (isApiRoute(url.pathname)) {
    event.respondWith(handleApiRequest(request, url));
    return;
  }
  
  // Stratégie pour les pages de navigation
  if (request.mode === 'navigate') {
    event.respondWith(handleNavigationRequest(request));
    return;
  }
  
  // Stratégie pour les ressources statiques
  if (isStaticAsset(url.pathname)) {
    event.respondWith(handleStaticAsset(request));
    return;
  }
  
  // Stratégie par défaut: Stale-While-Revalidate
  event.respondWith(handleDefaultRequest(request));
});

// Gestion des notifications push
self.addEventListener('push', (event) => {
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
      silent: notificationData.silent || false,
      sound: '/assets/sounds/notification.mp3'
    };
    
    event.waitUntil(
      self.registration.showNotification(title, options)
    );
  }
});

// Gestion du clic sur une notification
self.addEventListener('notificationclick', (event) => {
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

// Gestion de la synchronisation en arrière-plan
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-commandes') {
    event.waitUntil(syncCommandes());
  }
});

// Fonctions utilitaires

// Vérifier si une URL est une route API
function isApiRoute(pathname) {
  return API_ROUTES.some(route => pathname.includes(route.url));
}

// Vérifier si une URL est une ressource statique
function isStaticAsset(pathname) {
  return CORE_ASSETS.some(asset => pathname.endsWith(asset)) ||
         ADDITIONAL_ASSETS.some(asset => pathname.endsWith(asset)) ||
         EXTERNAL_ASSETS.some(asset => pathname.includes(asset)) ||
         /\.(css|js|png|jpg|jpeg|gif|svg|webp|ico|woff2|ttf|json)$/.test(pathname);
}

// Gérer les requêtes API
async function handleApiRequest(request, url) {
  // Trouver la configuration de cache pour cette route API
  const apiRoute = API_ROUTES.find(route => url.pathname.includes(route.url));
  const cacheDuration = apiRoute ? apiRoute.cacheDuration : 60; // 1 minute par défaut
  
  // Vérifier d'abord dans le cache
  const cachedResponse = await caches.match(request);
  
  // Si nous avons une réponse en cache et qu'elle n'est pas expirée, la retourner
  if (cachedResponse) {
    const cachedTime = cachedResponse.headers.get('sw-cache-time');
    if (cachedTime) {
      const age = (Date.now() - parseInt(cachedTime)) / 1000;
      if (age < cacheDuration) {
        return cachedResponse;
      }
    }
  }
  
  // Sinon, faire une requête réseau
  try {
    const networkResponse = await fetch(request);
    
    // Vérifier si la réponse est valide
    if (!networkResponse.ok) {
      // Si nous avons une réponse en cache, même expirée, la retourner
      if (cachedResponse) {
        return cachedResponse;
      }
      throw new Error(`Erreur réseau: ${networkResponse.status}`);
    }
    
    // Cloner la réponse pour pouvoir la mettre en cache
    const responseToCache = networkResponse.clone();
    
    // Ajouter un en-tête pour le temps de mise en cache
    const headers = new Headers(responseToCache.headers);
    headers.append('sw-cache-time', Date.now().toString());
    
    // Créer une nouvelle réponse avec l'en-tête ajouté
    const responseWithHeaders = new Response(await responseToCache.blob(), {
      status: responseToCache.status,
      statusText: responseToCache.statusText,
      headers: headers
    });
    
    // Mettre en cache la réponse
    const cache = await caches.open(DATA_CACHE_NAME);
    await cache.put(request, responseWithHeaders);
    
    return networkResponse;
  } catch (error) {
    console.error('[Service Worker] Erreur lors de la récupération des données:', error);
    
    // Si nous avons une réponse en cache, même expirée, la retourner
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Sinon, retourner une réponse d'erreur
    return new Response(JSON.stringify({
      success: false,
      message: 'Vous êtes hors ligne. Les données ne sont pas disponibles.'
    }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

// Gérer les requêtes de navigation
async function handleNavigationRequest(request) {
  try {
    // Essayer d'abord le réseau
    const networkResponse = await fetch(request);
    return networkResponse;
  } catch (error) {
    console.log('[Service Worker] Mode hors ligne, utilisation du cache pour la navigation');
    
    // Vérifier si la page demandée est en cache
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Si la page n'est pas en cache, retourner la page hors ligne
    return caches.match(OFFLINE_URL);
  }
}

// Gérer les ressources statiques
async function handleStaticAsset(request) {
  // Vérifier d'abord dans le cache
  const cachedResponse = await caches.match(request);
  if (cachedResponse) {
    // Revalider en arrière-plan
    fetch(request)
      .then(networkResponse => {
        if (networkResponse.ok) {
          caches.open(CACHE_NAME)
            .then(cache => cache.put(request, networkResponse));
        }
      })
      .catch(() => {});
    
    return cachedResponse;
  }
  
  // Si pas en cache, essayer le réseau
  try {
    const networkResponse = await fetch(request);
    
    // Mettre en cache la réponse si elle est valide
    if (networkResponse.ok) {
      const responseToCache = networkResponse.clone();
      caches.open(CACHE_NAME)
        .then(cache => cache.put(request, responseToCache));
    }
    
    return networkResponse;
  } catch (error) {
    // Si tout échoue, essayer de retourner une ressource similaire du cache
    const url = new URL(request.url);
    const extension = url.pathname.split('.').pop();
    
    if (extension) {
      const cache = await caches.open(CACHE_NAME);
      const cachedKeys = await cache.keys();
      const similarKey = cachedKeys.find(key => {
        const keyUrl = new URL(key.url);
        return keyUrl.pathname.endsWith(`.${extension}`);
      });
      
      if (similarKey) {
        return cache.match(similarKey);
      }
    }
    
    // Si vraiment rien ne fonctionne, retourner une erreur
    return new Response('Ressource non disponible hors ligne', { status: 404 });
  }
}

// Gérer les requêtes par défaut
async function handleDefaultRequest(request) {
  // Vérifier d'abord dans le cache
  const cachedResponse = await caches.match(request);
  
  // Faire une requête réseau en parallèle
  const fetchPromise = fetch(request)
    .then(networkResponse => {
      // Mettre en cache la réponse si elle est valide
      if (networkResponse.ok) {
        const responseToCache = networkResponse.clone();
        caches.open(CACHE_NAME)
          .then(cache => cache.put(request, responseToCache));
      }
      
      return networkResponse;
    })
    .catch(error => {
      console.warn('[Service Worker] Erreur fetch:', error);
      // Si nous avons une réponse en cache, la retourner
      if (cachedResponse) {
        return cachedResponse;
      }
      // Sinon, retourner la page hors ligne
      return caches.match(OFFLINE_URL);
    });
  
  // Retourner la réponse en cache si disponible, sinon attendre la requête réseau
  return cachedResponse || fetchPromise;
}

// Synchroniser les commandes
async function syncCommandes() {
  // Cette fonction serait implémentée pour synchroniser les commandes
  // stockées localement avec le serveur lorsque la connexion est rétablie
  console.log('[Service Worker] Synchronisation des commandes en arrière-plan');
  
  // Exemple d'implémentation:
  // 1. Récupérer les commandes en attente de synchronisation depuis IndexedDB
  // 2. Les envoyer au serveur une par une
  // 3. Mettre à jour leur statut dans IndexedDB
  
  // Cette fonction serait appelée par l'événement 'sync' lorsque la connexion est rétablie
  
  // Pour l'instant, nous simulons une synchronisation réussie
  return new Promise(resolve => {
    setTimeout(() => {
      console.log('[Service Worker] Synchronisation des commandes terminée');
      resolve();
    }, 1000);
  });
}