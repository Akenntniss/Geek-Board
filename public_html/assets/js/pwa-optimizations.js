/**
 * Optimisations pour le mode PWA
 * Ce script ajoute des fonctionnalités avancées pour améliorer l'expérience utilisateur
 * lorsque l'application est en mode PWA (Progressive Web App)
 */

// Configuration des variables globales
const PWA = {
    enabled: false,
    isIOS: false,
    isAndroid: false,
    hasDynamicIsland: false,
    isOffline: !navigator.onLine,
    prefetchedPages: [],
    powerSaveMode: false,
    cachedData: {},
    pageTransition: true
};

// Initialisation des optimisations PWA
document.addEventListener('DOMContentLoaded', function() {
    // Détection du mode PWA
    detectPWAMode();
    
    // Initialiser les fonctionnalités si nous sommes en mode PWA
    if (PWA.enabled) {
        initPWAFeatures();
    }
    
    // Écouter les changements de connectivité même en mode navigateur
    setupConnectivityListeners();
});

/**
 * Détecter si l'application est en mode PWA
 */
function detectPWAMode() {
    // Vérifier si l'application est installée en tant que PWA
    const isPWA = window.matchMedia('(display-mode: standalone)').matches || 
                  window.navigator.standalone || 
                  document.referrer.includes('android-app://');
                  
    // Vérifier le mode test depuis l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const testPWA = urlParams.get('test_pwa') === 'true';
    
    // Mettre à jour la configuration PWA
    PWA.enabled = isPWA || testPWA || document.body.classList.contains('pwa-mode');
    PWA.isIOS = /iPhone|iPad|iPod/.test(navigator.userAgent) || urlParams.get('test_ios') === 'true';
    PWA.isAndroid = /Android/.test(navigator.userAgent) && !PWA.isIOS;
    PWA.hasDynamicIsland = (PWA.isIOS && window.screen.height >= 844) || urlParams.get('test_dynamic_island') === 'true';
    
    // Détecter le mode économie d'énergie
    if ('connection' in navigator) {
        PWA.powerSaveMode = navigator.connection.saveData || false;
    }
    
    // Écouter les changements du mode d'affichage
    window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
        PWA.enabled = e.matches;
        if (PWA.enabled) {
            initPWAFeatures();
        }
    });
}

/**
 * Initialiser les fonctionnalités PWA
 */
function initPWAFeatures() {
    // Empêcher les comportements de défilement par défaut pour une expérience plus native
    preventDefaultScrollBehavior();
    
    // Améliorer la réactivité tactile
    enhanceTouchResponsiveness();
    
    // Configurer les transitions de page
    setupPageTransitions();
    
    // Optimiser pour le mode hors ligne
    setupOfflineMode();
    
    // Précharger les assets et pages populaires
    prefetchPopularContent();
    
    // Optimiser pour le mode économie d'énergie si activé
    if (PWA.powerSaveMode) {
        applyPowerSavingMode();
    }
    
    // Ajouter un système de navigation par gestes si sur mobile
    if (window.innerWidth < 992) {
        setupGestureNavigation();
    }
    
    // Optimisations spécifiques à la plateforme
    if (PWA.isIOS) {
        applyIOSOptimizations();
    } else if (PWA.isAndroid) {
        applyAndroidOptimizations();
    }
    
    // Ajouter la fonctionnalité de rafraîchissement par tirer vers le bas (pull-to-refresh)
    setupPullToRefresh();
    
    // Optimiser le stockage local des données (caching avancé)
    setupAdvancedCaching();
    
    // Ajouter indicateur de latence réseau
    setupNetworkLatencyIndicator();
}

/**
 * Empêcher les comportements de défilement par défaut
 */
function preventDefaultScrollBehavior() {
    // Empêcher le défilement du body
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';
    
    // Permettre le défilement dans les conteneurs principaux
    const mainContainer = document.querySelector('.main-container');
    if (mainContainer) {
        mainContainer.style.overflow = 'auto';
        mainContainer.style.height = '100vh';
        mainContainer.style.WebkitOverflowScrolling = 'touch';
    }
    
    // Empêcher le comportement de rebond sur iOS (bouncing)
    document.addEventListener('touchmove', function(e) {
        // Vérifier si l'élément ou l'un de ses parents a un défilement
        let element = e.target;
        while (element) {
            if (element.scrollHeight > element.clientHeight + 10) {
                const scrollTop = element.scrollTop;
                const scrollHeight = element.scrollHeight;
                const height = element.clientHeight;
                
                if ((scrollTop <= 0 && e.touches[0].screenY > e.touches[0].screenY) ||
                    (scrollTop + height >= scrollHeight && e.touches[0].screenY < e.touches[0].screenY)) {
                    e.preventDefault();
                }
                return;
            }
            element = element.parentElement;
        }
        e.preventDefault();
    }, { passive: false });
}

/**
 * Améliorer la réactivité tactile
 */
function enhanceTouchResponsiveness() {
    // Ajouter un retour visuel sur les éléments tactiles
    const interactiveElements = document.querySelectorAll('a, button, .card, .btn, .nav-link, .list-group-item');
    interactiveElements.forEach(element => {
        // Feedback visuel au toucher
        element.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.97)';
        }, { passive: true });
        
        element.addEventListener('touchend', function() {
            this.style.transform = '';
        }, { passive: true });
        
        // Éliminer le délai de 300ms sur les clics mobiles
        element.addEventListener('touchend', function(e) {
            e.preventDefault();
            this.click();
        }, { passive: false });
    });
}

/**
 * Configurer des transitions de page fluides
 */
function setupPageTransitions() {
    if (PWA.pageTransition && window.history) {
        // Ajouter un gestionnaire pour les clics sur les liens
        document.addEventListener('click', function(e) {
            // Trouver si le clic était sur un lien
            let target = e.target;
            while (target && target !== document) {
                if (target.tagName === 'A' && target.href && !target.getAttribute('download') && target.target !== '_blank') {
                    // URL sur le même domaine
                    if (target.hostname === window.location.hostname) {
                        e.preventDefault();
                        
                        // Créer une animation de sortie
                        const mainContent = document.querySelector('.main-container') || document.body;
                        mainContent.style.opacity = '0';
                        mainContent.style.transform = 'translateY(20px)';
                        mainContent.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        
                        // Naviguer après la transition
                        setTimeout(() => {
                            window.location.href = target.href;
                        }, 300);
                        return;
                    }
                }
                target = target.parentElement;
            }
        });
        
        // Animer l'entrée de page
        window.addEventListener('pageshow', function() {
            const mainContent = document.querySelector('.main-container') || document.body;
            mainContent.style.opacity = '0';
            mainContent.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                mainContent.style.opacity = '1';
                mainContent.style.transform = 'translateY(0)';
                mainContent.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            }, 10);
        });
    }
}

/**
 * Optimiser pour le mode hors ligne
 */
function setupOfflineMode() {
    // Ajouter un indicateur de mode hors ligne
    const offlineIndicator = document.createElement('div');
    offlineIndicator.classList.add('offline-indicator');
    offlineIndicator.innerHTML = '<i class="fas fa-wifi-slash"></i> Mode hors ligne';
    offlineIndicator.style.cssText = `
        display: none;
        position: fixed;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        background-color: #ff9800;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        z-index: 9999;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    
    document.body.appendChild(offlineIndicator);
    
    // Afficher/masquer l'indicateur selon l'état de la connexion
    if (!navigator.onLine) {
        offlineIndicator.style.display = 'block';
    }
    
    window.addEventListener('online', () => {
        offlineIndicator.style.display = 'none';
        
        // Afficher une notification toast si disponible
        if (typeof toastr !== 'undefined') {
            toastr.success('Connexion Internet rétablie', 'En ligne', {timeOut: 3000});
        }
        
        // Synchroniser les données locales si nécessaire
        syncOfflineActions();
    });
    
    window.addEventListener('offline', () => {
        offlineIndicator.style.display = 'block';
        
        // Afficher une notification toast si disponible
        if (typeof toastr !== 'undefined') {
            toastr.warning('Connexion Internet perdue. L\'application fonctionne en mode hors ligne.', 'Hors ligne', {timeOut: 5000});
        }
    });
}

/**
 * Synchroniser les actions effectuées hors ligne
 */
function syncOfflineActions() {
    // Récupérer les actions en attente du stockage local
    const pendingActions = localStorage.getItem('pwa_pending_actions');
    
    if (pendingActions) {
        try {
            const actions = JSON.parse(pendingActions);
            
            // Traiter chaque action en attente
            if (actions.length > 0 && typeof toastr !== 'undefined') {
                toastr.info(`Synchronisation de ${actions.length} action(s) en attente...`, 'Synchronisation', {timeOut: 3000});
                
                // Implémentation de la synchronisation ici
                // ...
                
                // Effacer les actions synchronisées
                localStorage.removeItem('pwa_pending_actions');
            }
        } catch (e) {
            console.error('Erreur lors de la synchronisation des actions hors ligne:', e);
        }
    }
}

/**
 * Précharger les pages et contenus populaires
 */
function prefetchPopularContent() {
    // Liste des pages populaires à précharger
    const popularPages = [
        '/index.php?page=accueil',
        '/index.php?page=reparations',
        '/index.php?page=clients',
        '/index.php?page=taches'
    ];
    
    // Si le service worker est actif, lui demander de précharger ces pages
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({
            type: 'PREFETCH_PAGES',
            pages: popularPages
        });
    } else {
        // Fallback : préchargement via le cache de l'application
        if (!navigator.connection || (navigator.connection.saveData !== true)) {
            popularPages.forEach(url => {
                if (!PWA.prefetchedPages.includes(url)) {
                    const prefetchLink = document.createElement('link');
                    prefetchLink.rel = 'prefetch';
                    prefetchLink.href = url;
                    document.head.appendChild(prefetchLink);
                    PWA.prefetchedPages.push(url);
                }
            });
        }
    }
}

/**
 * Appliquer les optimisations pour le mode économie d'énergie
 */
function applyPowerSavingMode() {
    // Réduire les animations
    document.body.classList.add('power-save-mode');
    
    // Désactiver les transitions
    const style = document.createElement('style');
    style.textContent = `
        .power-save-mode * {
            transition-duration: 0ms !important;
            animation-duration: 0ms !important;
        }
    `;
    document.head.appendChild(style);
    
    // Désactiver le préchargement
    PWA.prefetchedPages = [];
}

/**
 * Configuration de la navigation par gestes
 */
function setupGestureNavigation() {
    let touchStartX = 0;
    let touchStartY = 0;
    
    // Détecter le début du toucher
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });
    
    // Détecter la fin du toucher et déterminer le geste
    document.addEventListener('touchend', function(e) {
        const touchEndX = e.changedTouches[0].screenX;
        const touchEndY = e.changedTouches[0].screenY;
        
        const deltaX = touchEndX - touchStartX;
        const deltaY = touchEndY - touchStartY;
        
        // Ignorer les petits mouvements (probablement des clics)
        if (Math.abs(deltaX) < 100 && Math.abs(deltaY) < 100) return;
        
        // Détecter les gestes horizontaux
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            if (deltaX > 0) {
                // Glissement vers la droite (retour en arrière)
                if (touchStartX < 50 && window.history.length > 1) {
                    window.history.back();
                }
            } else {
                // Glissement vers la gauche (action personnalisée)
                // Implémenter selon les besoins de l'application
            }
        } 
    }, { passive: true });
}

/**
 * Appliquer des optimisations spécifiques à iOS
 */
function applyIOSOptimizations() {
    // Optimisations pour les appareils iOS
    document.documentElement.style.WebkitTouchCallout = 'none';
    
    // Ajustements pour Dynamic Island sur iPhone
    if (PWA.hasDynamicIsland) {
        // Ajouter un padding pour la Dynamic Island
        document.body.style.paddingTop = 'env(safe-area-inset-top)';
        
        // Ajuster la barre de navigation
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            navbar.style.paddingTop = 'calc(env(safe-area-inset-top) + 8px)';
        }
    }
    
    // Éviter le double-tap pour zoomer
    const meta = document.querySelector('meta[name="viewport"]');
    if (meta) {
        meta.setAttribute('content', meta.getAttribute('content') + ', maximum-scale=1.0');
    }
}

/**
 * Appliquer des optimisations spécifiques à Android
 */
function applyAndroidOptimizations() {
    // Ajouter les optimisations spécifiques à Android ici
}

/**
 * Configurer pull-to-refresh personnalisé
 */
function setupPullToRefresh() {
    // Elements pour le pull-to-refresh
    const ptrElement = document.createElement('div');
    ptrElement.className = 'ptr-element';
    ptrElement.innerHTML = '<div class="ptr-refresh"></div>';
    ptrElement.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        color: #0078e8;
        z-index: 999;
        text-align: center;
        height: 50px;
        transition: all 0.25s ease;
        transform: translateY(-70px);
    `;
    
    document.body.appendChild(ptrElement);
    
    let mainElement = document.querySelector('.main-container');
    if (!mainElement) {
        mainElement = document.body;
    }
    
    let ptrStart = 0;
    let ptrRefreshing = false;
    let ptrThreshold = 60;
    
    mainElement.addEventListener('touchstart', function(e) {
        if (mainElement.scrollTop === 0) {
            ptrStart = e.touches[0].screenY;
        }
    }, { passive: true });
    
    mainElement.addEventListener('touchmove', function(e) {
        if (ptrRefreshing) return;
        
        if (mainElement.scrollTop === 0) {
            const touchY = e.touches[0].screenY;
            const ptrDiff = touchY - ptrStart;
            
            if (ptrDiff > 0) {
                // Empêcher le défilement natif
                e.preventDefault();
                
                // Calculer la position du PTR avec un effet de résistance
                const resistance = ptrDiff < 100 ? 1 : 0.5;
                const ptrOffset = Math.min(ptrDiff * resistance, 100);
                
                ptrElement.style.transform = `translateY(${ptrOffset - 70}px)`;
                
                if (ptrOffset > ptrThreshold) {
                    ptrElement.classList.add('ptr-ready');
                } else {
                    ptrElement.classList.remove('ptr-ready');
                }
            }
        }
    }, { passive: false });
    
    mainElement.addEventListener('touchend', function(e) {
        if (ptrRefreshing) return;
        
        const ptrReady = ptrElement.classList.contains('ptr-ready');
        ptrElement.classList.remove('ptr-ready');
        
        if (ptrReady) {
            // Déclencher le rechargement
            ptrRefreshing = true;
            ptrElement.style.transform = 'translateY(0)';
            ptrElement.innerHTML = '<div class="ptr-refresh" style="animation: ptr-refreshing-animation 0.75s linear infinite;"></div>';
            
            // Rafraîchir la page
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // Remettre à sa position initiale
            ptrElement.style.transform = 'translateY(-70px)';
        }
    }, { passive: true });
}

/**
 * Configurer un cache avancé pour les données
 */
function setupAdvancedCaching() {
    // API fetch originale
    const originalFetch = window.fetch;
    
    // Remplacer fetch par une version avec mise en cache
    window.fetch = async function(...args) {
        const request = args[0];
        const options = args[1] || {};
        
        // Ne pas mettre en cache les requêtes non GET
        if (options.method && options.method !== 'GET') {
            return originalFetch.apply(this, args);
        }
        
        try {
            // Tenter une requête réseau d'abord
            const response = await originalFetch.apply(this, args);
            
            // Si la réponse est OK, mettre à jour le cache
            if (response.ok) {
                const url = typeof request === 'string' ? request : request.url;
                const responseClone = response.clone();
                const data = await responseClone.text();
                
                // Stocker dans le cache mémoire
                PWA.cachedData[url] = {
                    timestamp: Date.now(),
                    data: data,
                    headers: Array.from(response.headers.entries())
                };
                
                // Stocker dans localStorage si c'est une petite donnée
                if (data.length < 100000) {
                    try {
                        const storageKey = 'pwa_cache_' + url.replace(/[^a-z0-9]/gi, '_');
                        localStorage.setItem(storageKey, JSON.stringify({
                            timestamp: Date.now(),
                            data: data
                        }));
                    } catch (e) {
                        // Erreur localStorage (probablement quota dépassé)
                        console.warn('Erreur localStorage lors de la mise en cache:', e);
                    }
                }
            }
            
            return response;
        } catch (error) {
            // Erreur réseau, essayer depuis le cache
            const url = typeof request === 'string' ? request : request.url;
            
            // Vérifier dans le cache mémoire
            if (PWA.cachedData[url]) {
                const cachedItem = PWA.cachedData[url];
                const headers = new Headers(cachedItem.headers);
                
                return new Response(cachedItem.data, {
                    status: 200,
                    headers: headers,
                    statusText: 'OK (from cache)'
                });
            }
            
            // Vérifier dans localStorage
            try {
                const storageKey = 'pwa_cache_' + url.replace(/[^a-z0-9]/gi, '_');
                const stored = localStorage.getItem(storageKey);
                
                if (stored) {
                    const parsedData = JSON.parse(stored);
                    return new Response(parsedData.data, {
                        status: 200,
                        statusText: 'OK (from localStorage)'
                    });
                }
            } catch (e) {
                // Erreur localStorage
                console.warn('Erreur lors de la récupération depuis localStorage:', e);
            }
            
            // Si pas de cache disponible, propager l'erreur
            throw error;
        }
    };
}

/**
 * Configuration des écouteurs de connectivité
 */
function setupConnectivityListeners() {
    // Ajouter un écouteur de latence réseau
    window.addEventListener('online', () => {
        PWA.isOffline = false;
        document.body.classList.remove('offline-mode');
        document.documentElement.classList.remove('offline-mode');
    });
    
    window.addEventListener('offline', () => {
        PWA.isOffline = true;
        document.body.classList.add('offline-mode');
        document.documentElement.classList.add('offline-mode');
    });
}

/**
 * Configurer un indicateur de latence réseau
 */
function setupNetworkLatencyIndicator() {
    // Ne s'exécute qu'en mode PWA et lorsque la connexion est disponible
    if (PWA.enabled && navigator.onLine && 'connection' in navigator) {
        // Créer l'indicateur
        const networkIndicator = document.createElement('div');
        networkIndicator.classList.add('network-latency-indicator');
        networkIndicator.style.cssText = `
            position: fixed;
            bottom: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 6px 10px;
            border-radius: 15px;
            font-size: 12px;
            z-index: 9999;
            display: none;
        `;
        
        document.body.appendChild(networkIndicator);
        
        // Tester la latence régulièrement
        function checkNetworkLatency() {
            if (!navigator.onLine) return;
            
            const startTime = Date.now();
            
            fetch('/index.php?ping=' + Date.now(), { method: 'HEAD' })
                .then(() => {
                    const latency = Date.now() - startTime;
                    
                    // Afficher si la latence est élevée
                    if (latency > 300) {
                        networkIndicator.textContent = `Réseau: ${latency}ms`;
                        networkIndicator.style.display = 'block';
                        
                        // Couleur selon la latence
                        if (latency > 1000) {
                            networkIndicator.style.backgroundColor = 'rgba(220, 53, 69, 0.7)'; // rouge
                        } else if (latency > 500) {
                            networkIndicator.style.backgroundColor = 'rgba(255, 193, 7, 0.7)'; // jaune
                        } else {
                            networkIndicator.style.backgroundColor = 'rgba(40, 167, 69, 0.7)'; // vert
                        }
                        
                        // Masquer après quelques secondes
                        setTimeout(() => {
                            networkIndicator.style.display = 'none';
                        }, 5000);
                    }
                })
                .catch(() => {
                    // Erreur de connexion
                    networkIndicator.textContent = 'Réseau: déconnecté';
                    networkIndicator.style.backgroundColor = 'rgba(220, 53, 69, 0.7)';
                    networkIndicator.style.display = 'block';
                });
        }
        
        // Vérifier la latence périodiquement
        setInterval(checkNetworkLatency, 30000);
        
        // Vérifier aussi lors des changements de connectivité
        window.addEventListener('online', checkNetworkLatency);
    }
} 