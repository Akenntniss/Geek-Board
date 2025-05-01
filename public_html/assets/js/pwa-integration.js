/**
 * GeekBoard - Intégration PWA Optimisée
 * 
 * Ce script permet d'intégrer les fonctionnalités PWA améliorées
 * et d'activer la gestion hors ligne des commandes.
 */

document.addEventListener('DOMContentLoaded', initPwaIntegration);

// Initialiser l'intégration PWA
function initPwaIntegration() {
    console.log('Initialisation de l\'intégration PWA optimisée');
    
    // Vérifier si l'application est en mode PWA
    const isPwa = window.matchMedia('(display-mode: standalone)').matches || 
                 window.navigator.standalone === true || 
                 document.body.classList.contains('pwa-mode');
    
    // Activer les fonctionnalités PWA si en mode PWA
    if (isPwa) {
        console.log('Mode PWA détecté, activation des fonctionnalités optimisées');
        document.body.classList.add('pwa-mode');
        
        // Enregistrer le service worker optimisé
        registerOptimizedServiceWorker();
        
        // Charger les scripts nécessaires pour le mode hors ligne
        loadOfflineScripts();
        
        // Configurer l'interface utilisateur pour le mode PWA
        setupPwaInterface();
        
        // Activer la synchronisation en arrière-plan
        setupBackgroundSync();
    } else {
        // Enregistrer le service worker standard
        registerServiceWorker();
    }
    
    // Ajouter les méta-tags pour iOS
    addIosMetaTags();
    
    // Ajouter le bouton d'installation
    setupInstallButton();
}

// Enregistrer le service worker optimisé
function registerOptimizedServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker-optimized.js')
            .then(registration => {
                console.log('Service Worker optimisé enregistré avec succès:', registration.scope);
            })
            .catch(error => {
                console.error('Erreur lors de l\'enregistrement du Service Worker optimisé:', error);
                // Fallback sur le service worker standard
                registerServiceWorker();
            });
    }
}

// Enregistrer le service worker standard
function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                console.log('Service Worker standard enregistré avec succès:', registration.scope);
            })
            .catch(error => {
                console.error('Erreur lors de l\'enregistrement du Service Worker standard:', error);
            });
    }
}

// Charger les scripts nécessaires pour le mode hors ligne
function loadOfflineScripts() {
    // Vérifier si le script de synchronisation hors ligne est déjà chargé
    if (!document.querySelector('script[src*="offline-sync.js"]')) {
        loadScript('/assets/js/offline-sync.js');
    }
    
    // Vérifier si le script de gestion des commandes hors ligne est déjà chargé
    if (!document.querySelector('script[src*="commandes-offline.js"]')) {
        loadScript('/assets/js/commandes-offline.js');
    }
}

// Charger un script dynamiquement
function loadScript(src) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        
        script.onload = () => resolve(script);
        script.onerror = () => reject(new Error(`Erreur de chargement du script: ${src}`));
        
        document.head.appendChild(script);
    });
}

// Configurer l'interface utilisateur pour le mode PWA
function setupPwaInterface() {
    // Masquer les éléments non nécessaires en mode PWA
    document.querySelectorAll('[data-hide-in-pwa="true"]').forEach(el => {
        el.style.display = 'none';
    });
    
    // Afficher les éléments spécifiques au mode PWA
    document.querySelectorAll('[data-show-in-pwa="true"]').forEach(el => {
        el.style.display = '';
    });
    
    // Ajouter un indicateur de mode hors ligne
    addOfflineIndicator();
    
    // Détecter si on est sur iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                 (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    
    if (isIOS) {
        document.body.classList.add('ios-pwa');
        
        // Ajustements spécifiques pour iOS
        const viewportMeta = document.querySelector('meta[name="viewport"]');
        if (viewportMeta) {
            viewportMeta.content = 'width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover';
        }
    }
}

// Ajouter un indicateur de mode hors ligne
function addOfflineIndicator() {
    // Créer l'indicateur s'il n'existe pas déjà
    if (!document.getElementById('offline-indicator')) {
        const indicator = document.createElement('div');
        indicator.id = 'offline-indicator';
        indicator.className = 'offline-indicator';
        indicator.innerHTML = `
            <div class="offline-indicator-content">
                <i class="fas fa-wifi-slash"></i>
                <span>Mode hors ligne</span>
            </div>
        `;
        
        // Ajouter des styles
        const style = document.createElement('style');
        style.textContent = `
            .offline-indicator {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background-color: #f44336;
                color: white;
                text-align: center;
                padding: 8px;
                z-index: 9999;
                font-size: 14px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                transition: transform 0.3s ease;
                transform: translateY(-100%);
            }
            .offline-indicator.visible {
                transform: translateY(0);
                display: block;
            }
            .offline-indicator-content {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
            body.ios-pwa .offline-indicator {
                padding-top: env(safe-area-inset-top, 8px);
            }
        `;
        
        document.head.appendChild(style);
        document.body.appendChild(indicator);
        
        // Ajouter les écouteurs d'événements pour la connectivité
        window.addEventListener('online', updateOfflineIndicator);
        window.addEventListener('offline', updateOfflineIndicator);
        
        // Initialiser l'état
        updateOfflineIndicator();
    }
}

// Mettre à jour l'indicateur de mode hors ligne
function updateOfflineIndicator() {
    const indicator = document.getElementById('offline-indicator');
    if (indicator) {
        if (navigator.onLine) {
            indicator.classList.remove('visible');
        } else {
            indicator.classList.add('visible');
        }
    }
}

// Ajouter les méta-tags pour iOS
function addIosMetaTags() {
    // Vérifier et ajouter les méta-tags pour iOS si nécessaire
    const metaTags = [
        { name: 'apple-mobile-web-app-capable', content: 'yes' },
        { name: 'apple-mobile-web-app-status-bar-style', content: 'black-translucent' },
        { name: 'apple-mobile-web-app-title', content: 'GeekBoard' }
    ];
    
    metaTags.forEach(meta => {
        if (!document.querySelector(`meta[name="${meta.name}"]`)) {
            const metaTag = document.createElement('meta');
            metaTag.name = meta.name;
            metaTag.content = meta.content;
            document.head.appendChild(metaTag);
        }
    });
    
    // Ajouter les liens pour les icônes Apple
    const appleTouchIconSizes = [180, 167, 152, 120, 114, 76, 72];
    
    appleTouchIconSizes.forEach(size => {
        if (!document.querySelector(`link[rel="apple-touch-icon"][sizes="${size}x${size}"]`)) {
            const link = document.createElement('link');
            link.rel = 'apple-touch-icon';
            link.sizes = `${size}x${size}`;
            link.href = `/assets/images/pwa-icons/apple-touch-icon-${size}x${size}.png`;
            document.head.appendChild(link);
        }
    });
    
    // Ajouter le lien pour le manifest optimisé
    if (!document.querySelector('link[rel="manifest"][href*="manifest-optimized.json"]')) {
        // Supprimer l'ancien manifest s'il existe
        const oldManifest = document.querySelector('link[rel="manifest"]');
        if (oldManifest) {
            oldManifest.href = '/manifest-optimized.json';
        } else {
            const link = document.createElement('link');
            link.rel = 'manifest';
            link.href = '/manifest-optimized.json';
            document.head.appendChild(link);
        }
    }
    
    // Ajouter le splash screen pour iOS
    const splashScreens = [
        { href: '/assets/images/pwa-icons/apple-splash-2048-2732.png', media: '(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)' },
        { href: '/assets/images/pwa-icons/apple-splash-1668-2388.png', media: '(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2)' },
        { href: '/assets/images/pwa-icons/apple-splash-1668-2224.png', media: '(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2)' },
        { href: '/assets/images/pwa-icons/apple-splash-1536-2048.png', media: '(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)' },
        { href: '/assets/images/pwa-icons/apple-splash-1242-2688.png', media: '(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3)' },
        { href: '/assets/images/pwa-icons/apple-splash-1125-2436.png', media: '(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)' },
        { href: '/assets/images/pwa-icons/apple-splash-828-1792.png', media: '(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)' },
        { href: '/assets/images/pwa-icons/apple-splash-750-1334.png', media: '(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)' },
        { href: '/assets/images/pwa-icons/apple-splash-640-1136.png', media: '(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)' }
    ];
    
    splashScreens.forEach(splash => {
        if (!document.querySelector(`link[rel="apple-touch-startup-image"][media="${splash.media}"]`)) {
            const link = document.createElement('link');
            link.rel = 'apple-touch-startup-image';
            link.href = splash.href;
            link.media = splash.media;
            document.head.appendChild(link);
        }
    });
}

// Configurer le bouton d'installation
function setupInstallButton() {
    let deferredPrompt;
    
    // Écouter l'événement beforeinstallprompt
    window.addEventListener('beforeinstallprompt', (e) => {
        // Empêcher Chrome d'afficher automatiquement la boîte de dialogue d'installation
        e.preventDefault();
        // Stocker l'événement pour l'utiliser plus tard
        deferredPrompt = e;
        // Afficher le bouton d'installation
        showInstallButton();
    });
    
    // Fonction pour afficher le bouton d'installation
    function showInstallButton() {
        // Vérifier si le bouton existe déjà
        if (document.getElementById('pwa-install-button')) {
            document.getElementById('pwa-install-button').style.display = 'flex';
            return;
        }
        
        // Créer le bouton d'installation
        const installButton = document.createElement('div');
        installButton.id = 'pwa-install-button';
        installButton.className = 'pwa-install-button';
        installButton.innerHTML = `
            <div class="pwa-install-content">
                <i class="fas fa-download"></i>
                <span>Installer l'application</span>
            </div>
        `;
        
        // Ajouter des styles
        const style = document.createElement('style');
        style.textContent = `
            .pwa-install-button {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background-color: #0078e8;
                color: white;
                padding: 12px 24px;
                border-radius: 24px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                cursor: pointer;
                z-index: 9998;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 500;
                transition: all 0.3s ease;
            }
            .pwa-install-button:hover {
                background-color: #0061c1;
                box-shadow: 0 6px 16px rgba(0,0,0,0.3);
            }
            .pwa-install-content {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            body.pwa-mode .pwa-install-button {
                display: none;
            }
            body.ios-pwa .pwa-install-button {
                display: none;
            }
            @media (display-mode: standalone) {
                .pwa-install-button {
                    display: none !important;
                }
            }
        `;
        
        document.head.appendChild(style);
        document.body.appendChild(installButton);
        
        // Ajouter l'écouteur d'événement pour l'installation
        installButton.addEventListener('click', async () => {
            if (!deferredPrompt) {
                // Si deferredPrompt n'est pas disponible, afficher des instructions manuelles
                showManualInstallInstructions();
                return;
            }
            
            // Afficher la boîte de dialogue d'installation
            deferredPrompt.prompt();
            
            // Attendre que l'utilisateur réponde à la boîte de dialogue
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`Résultat de l'installation: ${outcome}`);
            
            // Réinitialiser deferredPrompt
            deferredPrompt = null;
            
            // Masquer le bouton d'installation
            installButton.style.display = 'none';
        });
    }
    
    // Fonction pour afficher les instructions d'installation manuelles
    function showManualInstallInstructions() {
        // Détecter le navigateur/OS
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                     (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const isAndroid = /Android/.test(navigator.userAgent);
        const isSafari = /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent);
        const isChrome = /Chrome/.test(navigator.userAgent) && !/Edge/.test(navigator.userAgent);
        
        let instructions = '';
        
        if (isIOS && isSafari) {
            instructions = `
                <h5>Installation sur iOS</h5>
                <ol>
                    <li>Appuyez sur <i class="fas fa-share-square"></i> en bas de l'écran</li>
                    <li>Faites défiler et appuyez sur "Ajouter à l'écran d'accueil"</li>
                    <li>Appuyez sur "Ajouter" en haut à droite</li>
                </ol>
                <img src="/assets/images/pwa-guide/ios-install.png" alt="Guide d'installation iOS" class="img-fluid rounded">
            `;
        } else if (isAndroid && isChrome) {
            instructions = `
                <h5>Installation sur Android</h5>
                <ol>
                    <li>Appuyez sur <i class="fas fa-ellipsis-v"></i> en haut à droite</li>
                    <li>Sélectionnez "Ajouter à l'écran d'accueil"</li>
                    <li>Confirmez en appuyant sur "Ajouter"</li>
                </ol>
                <img src="/assets/images/pwa-guide/android-install.png" alt="Guide d'installation Android" class="img-fluid rounded">
            `;
        } else {
            instructions = `
                <h5>Installation sur ce navigateur</h5>
                <p>Pour installer cette application :</p>
                <ol>
                    <li>Ouvrez le menu de votre navigateur</li>
                    <li>Recherchez l'option "Installer" ou "Ajouter à l'écran d'accueil"</li>
                    <li>Suivez les instructions à l'écran</li>
                </ol>
            `;
        }
        
        // Créer le modal d'instructions
        const modalHtml = `
            <div class="modal fade" id="installInstructionsModal" tabindex="-1" aria-labelledby="installInstructionsModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="installInstructionsModalLabel">Comment installer GeekBoard</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            ${instructions}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Ajouter le modal au document
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHtml;
        document.body.appendChild(modalContainer);
        
        // Afficher le modal
        const modal = new bootstrap.Modal(document.getElementById('installInstructionsModal'));
        modal.show();
    }
}

// Configurer la synchronisation en arrière-plan
function setupBackgroundSync() {
    if ('serviceWorker' in navigator && 'SyncManager' in window) {
        navigator.serviceWorker.ready
            .then(registration => {
                // Enregistrer une tâche de synchronisation pour les commandes
                return registration.sync.register('sync-commandes');
            })
            .then(() => {
                console.log('Synchronisation en arrière-plan configurée');
            })
            .catch(error => {
                console.error('Erreur lors de la configuration de la synchronisation en arrière-plan:', error);
            });
    }
}

// Exporter les fonctions utiles
window.PwaIntegration = {
    isPwa: () => {
        return window.matchMedia('(display-mode: standalone)').matches || 
               window.navigator.standalone === true || 
               document.body.classList.contains('pwa-mode');
    },
    isOffline: () => {
        return !navigator.onLine;
    },
    showInstallPrompt: () => {
        const installButton = document.getElementById('pwa-install-button');
        if (installButton) {
            installButton.click();
        }
    }
};