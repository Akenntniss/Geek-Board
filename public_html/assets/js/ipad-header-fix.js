/**
 * Correction pour le problème de boutons du header non cliquables sur iPad en mode paysage
 * Ce script améliore la zone cliquable des éléments de navigation sur iPad
 */

document.addEventListener('DOMContentLoaded', function() {
    // Détecter si l'appareil est un iPad
    const isIPad = /iPad/.test(navigator.userAgent) || 
                  (/Macintosh/.test(navigator.userAgent) && 'ontouchend' in document);
    
    // Si ce n'est pas un iPad, ne pas exécuter le reste du code
    if (!isIPad) return;
    
    console.log("iPad détecté, application des corrections pour le header");
    
    // Ajouter une classe pour identifier l'iPad
    document.body.classList.add('ipad-device');
    
    // Vérifier si l'application est en mode PWA
    const isPwa = window.matchMedia('(display-mode: standalone)').matches || 
                 window.navigator.standalone ||
                 document.body.classList.contains('pwa-mode');
    
    if (isPwa) {
        document.body.classList.add('ipad-pwa');
        console.log("Mode PWA détecté sur iPad");
    }
    
    // Fonction pour appliquer les correctifs en fonction de l'orientation
    function applyOrientationFixes() {
        const isLandscape = window.innerWidth > window.innerHeight;
        
        if (isLandscape) {
            // Mode paysage
            document.body.classList.add('landscape-mode');
            document.body.classList.remove('portrait-mode');
            
            // Sélecteur plus large pour capturer tous les boutons possibles dans l'en-tête
            const headerButtons = document.querySelectorAll('.navbar .btn, header .btn, .navbar-nav .nav-item, .navbar-nav .nav-link, #desktop-navbar .btn, .nav-item a, .nav-link, header a, #launchpad a, .action-icon, .tool-button, .btn-group .btn');
            
            headerButtons.forEach(button => {
                // Augmenter la zone cliquable
                button.style.position = 'relative';
                button.style.zIndex = '1060';
                button.style.padding = '15px';
                button.style.margin = '0';
                button.style.pointerEvents = 'auto';
                
                // Ajouter une classe pour identifier les éléments corrigés
                button.classList.add('ipad-landscape-fixed');
                
                // Ajouter un effet de feedback tactile
                button.addEventListener('touchstart', function(e) {
                    this.style.opacity = '0.8';
                    this.style.transform = 'scale(0.98)';
                    // Log pour débogage
                    console.log("Toucher détecté sur:", this);
                }, { passive: false });
                
                button.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                    this.style.transform = 'scale(1)';
                }, { passive: false });
                
                // Forcer le clic sur les touches tactiles
                button.addEventListener('touchstart', function(e) {
                    if (isPwa && isLandscape) {
                        // Stocker la position du toucher
                        const touch = e.touches[0];
                        const touchStartX = touch.clientX;
                        const touchStartY = touch.clientY;
                        
                        // Fonction pour vérifier si le toucher est toujours sur le bouton
                        const touchEndHandler = () => {
                            // Simuler un clic si le toucher est terminé sur le bouton
                            const rect = this.getBoundingClientRect();
                            if (touchStartX >= rect.left && touchStartX <= rect.right && 
                                touchStartY >= rect.top && touchStartY <= rect.bottom) {
                                // Déclencher un événement de clic
                                this.click();
                                console.log("Clic forcé sur:", this);
                            }
                            
                            // Supprimer l'écouteur après utilisation
                            this.removeEventListener('touchend', touchEndHandler);
                        };
                        
                        // Ajouter un gestionnaire temporaire pour touchend
                        this.addEventListener('touchend', touchEndHandler, { passive: false });
                    }
                }, { passive: false });
            });
            
            // Assurer que les éléments parents ne bloquent pas les événements
            const navbarContainers = document.querySelectorAll('header, .navbar, .navbar-collapse, #desktop-navbar, #launchpad, .header-fixed, .fixed-top, .sticky-top');
            navbarContainers.forEach(container => {
                container.style.pointerEvents = 'auto';
            });
            
            // En mode PWA paysage, on force une élévation encore plus importante du z-index
            if (isPwa) {
                const pwaHeader = document.querySelector('header, .navbar, #desktop-navbar, .header-fixed, .fixed-top');
                if (pwaHeader) {
                    pwaHeader.style.transform = 'translateZ(0)';
                    pwaHeader.style.webkitTransform = 'translateZ(0)';
                    pwaHeader.style.zIndex = '9999';
                }
            }
        } else {
            // Mode portrait - restaurer les styles normaux si nécessaire
            document.body.classList.add('portrait-mode');
            document.body.classList.remove('landscape-mode');
            
            // Réinitialiser les styles des éléments corrigés
            const fixedElements = document.querySelectorAll('.ipad-landscape-fixed');
            fixedElements.forEach(element => {
                element.style.position = '';
                element.style.zIndex = '';
                element.style.padding = '';
                element.style.margin = '';
                
                // Conserver pointerEvents à auto pour s'assurer de la cliquabilité
                element.style.pointerEvents = 'auto';
            });
        }
    }
    
    // Appliquer les correctifs lors du chargement initial
    applyOrientationFixes();
    
    // Écouter les changements d'orientation
    window.addEventListener('orientationchange', function() {
        // Attendre un court instant pour que les dimensions soient mises à jour
        setTimeout(applyOrientationFixes, 100);
    });
    
    // Écouter également les redimensionnements (pour plus de fiabilité)
    window.addEventListener('resize', debounce(applyOrientationFixes, 250));
    
    // Fonction utilitaire pour limiter la fréquence d'exécution
    function debounce(func, wait) {
        let timeout;
        return function() {
            clearTimeout(timeout);
            timeout = setTimeout(func, wait);
        };
    }
}); 