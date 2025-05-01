/**
 * Interactions Mobiles Modernes
 * Améliore l'expérience utilisateur mobile avec des animations fluides et des gestes
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Configurer les variables basées sur l'environnement
    const IS_IOS = /iPhone|iPad|iPod/.test(navigator.userAgent);
    const IS_PWA = window.matchMedia('(display-mode: standalone)').matches || 
                   window.navigator.standalone || 
                   document.referrer.includes('android-app://');
    
    // Appliquer les classes appropriées au body
    if (IS_PWA) {
        document.body.classList.add('pwa-mode');
        
        if (IS_IOS) {
            document.body.classList.add('ios-pwa');
            
            // Optimisations pour les iPhones avec Dynamic Island/Notch
            const hasNotch = window.screen.height >= 812 && window.screen.width >= 375;
            if (hasNotch) {
                document.body.classList.add('ios-dynamic-island');
            }
        }
    }
    
    /**
     * Navigation et barre d'en-tête
     */
    
    // Gestion du défilement pour la barre de navigation
    let lastScrollTop = 0;
    const scrollThreshold = 50;
    const topNav = document.querySelector('.top-nav');
    const mobileNav = document.querySelector('.mobile-dock');
    
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Ajout de la classe 'scrolled' pour un aspect plus compact
        if (topNav) {
            if (scrollTop > scrollThreshold) {
                topNav.classList.add('scrolled');
            } else {
                topNav.classList.remove('scrolled');
            }
            
            // Masquage/affichage basé sur la direction de défilement
            if (scrollTop > 100 && scrollTop > lastScrollTop + 10) {
                topNav.classList.add('hidden');
            } else if (scrollTop < lastScrollTop - 10 || scrollTop < 50) {
                topNav.classList.remove('hidden');
            }
        }
        
        lastScrollTop = scrollTop;
    }
    
    // Ajout de l'écouteur avec optimisation pour les performances
    let scrollTimeout;
    window.addEventListener('scroll', function() {
        if (!scrollTimeout) {
            scrollTimeout = setTimeout(function() {
                handleScroll();
                scrollTimeout = null;
            }, 10);
        }
    });
    
    /**
     * Retour tactile
     */
    
    // Ajouter un retour tactile sur les éléments interactifs
    function addTactileFeedback() {
        const interactiveElements = document.querySelectorAll('button, .btn, .dock-item, .card, a[href]:not([disabled])');
        
        interactiveElements.forEach(element => {
            if (!element.classList.contains('has-feedback')) {
                // Ajouter un feedback tactile
                element.addEventListener('touchstart', function() {
                    this.classList.add('touch-feedback');
                    
                    // Vibreur si disponible (fonctionne sur la plupart des appareils Android)
                    if (navigator.vibrate) {
                        navigator.vibrate(20);
                    }
                }, { passive: true });
                
                element.addEventListener('touchend', function() {
                    this.classList.remove('touch-feedback');
                }, { passive: true });
                
                element.classList.add('has-feedback');
            }
        });
    }
    
    // Exécuter au chargement initial
    addTactileFeedback();
    
    // Puis après le chargement de tout contenu AJAX
    const observeDOM = (function(){
        const MutationObserver = window.MutationObserver || window.WebKitMutationObserver;
        
        return function(obj, callback){
            if(!obj || obj.nodeType !== 1) return; 
            
            if(MutationObserver){
                const mutationObserver = new MutationObserver(callback)
                mutationObserver.observe(obj, { childList: true, subtree: true });
                return mutationObserver;
            }
        }
    })();
    
    // Observer les changements du DOM et appliquer le feedback tactile aux nouveaux éléments
    observeDOM(document.body, function() {
        addTactileFeedback();
    });
    
    /**
     * Transitions de page
     */
    
    // Gestion des transitions entre les pages
    const pageTransition = function() {
        const contentArea = document.querySelector('.main-container') || document.querySelector('main');
        
        if (contentArea) {
            contentArea.classList.add('page-transition');
            
            // Force le reflow pour déclencher l'animation
            void contentArea.offsetWidth;
        }
    };
    
    // Préchargeur pour les transitions de page
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    function showLoading() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
    }
    
    function hideLoading() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }
    
    // Appliquer des transitions lors des navigations
    document.querySelectorAll('a:not([href^="#"]):not([target="_blank"]):not(.no-transition)').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Ignorer les liens vers des ressources non-HTML
            if (href && !href.match(/\.(jpg|jpeg|png|gif|pdf|doc|xls|mp4|mp3|zip|rar)$/i)) {
                // Ne pas afficher le chargement pour certains éléments
                if (!this.closest('.fab-menu-item') && !this.closest('.no-loading')) {
                    showLoading();
                    setTimeout(hideLoading, 1500); // Sécurité
                }
            }
        });
    });
    
    // Initialiser les transitions
    pageTransition();
    document.addEventListener('DOMContentLoaded', hideLoading);
    window.addEventListener('load', hideLoading);
    
    /**
     * Optimisations pour les PWA
     */
    
    if (IS_PWA) {
        // Augmenter les zones de touche pour les éléments critiques
        const criticalElements = document.querySelectorAll('.brand, #mobileMenuToggle, .nav-icon-btn');
        
        criticalElements.forEach(element => {
            // Créer une zone de touche étendue
            const touchArea = document.createElement('div');
            touchArea.classList.add('extended-touch-area');
            
            // Positionnement relatif au parent
            const rect = element.getBoundingClientRect();
            touchArea.style.cssText = `
                position: absolute;
                top: -15px;
                left: -15px;
                right: -15px;
                bottom: -15px;
                z-index: -1;
            `;
            
            // Ne l'ajouter que si l'élément a position relative
            const computedStyle = window.getComputedStyle(element);
            if (computedStyle.position === 'static') {
                element.style.position = 'relative';
            }
            
            element.appendChild(touchArea);
        });
        
        // Désactiver les gestes de navigation du navigateur sur iOS
        if (IS_IOS) {
            const style = document.createElement('style');
            style.textContent = `
                body {
                    position: fixed;
                    width: 100%;
                    height: 100%;
                    overflow: auto;
                    -webkit-overflow-scrolling: touch;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    /**
     * Gestion de la barre de dock mobile
     */
    
    // Ajouter un effet de rebond lors de la sélection
    const dockItems = document.querySelectorAll('.dock-item');
    
    dockItems.forEach(item => {
        item.addEventListener('click', function() {
            if (!this.classList.contains('active')) {
                // Animation de l'icône
                const icon = this.querySelector('i');
                if (icon) {
                    icon.style.animation = 'none';
                    void icon.offsetHeight; // Force reflow
                    icon.style.animation = 'bounce 0.4s ease';
                }
                
                // Désactiver les autres éléments actifs
                dockItems.forEach(otherItem => {
                    otherItem.classList.remove('active');
                });
                
                // Activer l'élément courant
                this.classList.add('active');
            }
        });
    });
    
    /**
     * Double-tap to top
     */
    
    // Retour au début de la page avec double-tap sur la barre d'état
    let lastTap = 0;
    const statusBar = document.createElement('div');
    statusBar.classList.add('status-bar-tap-area');
    statusBar.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 20px;
        z-index: 1100;
        display: block;
    `;
    
    statusBar.addEventListener('touchend', function(e) {
        const currentTime = new Date().getTime();
        const tapLength = currentTime - lastTap;
        
        if (tapLength < 300 && tapLength > 0) {
            // Double-tap détecté
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
            
            // Vibrer
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
        }
        
        lastTap = currentTime;
    });
    
    document.body.appendChild(statusBar);
    
    /**
     * Détection de l'orientation
     */
    
    function handleOrientationChange() {
        if (window.matchMedia("(orientation: portrait)").matches) {
            document.body.classList.remove('landscape');
            document.body.classList.add('portrait');
        } else {
            document.body.classList.remove('portrait');
            document.body.classList.add('landscape');
        }
    }
    
    // Initial check
    handleOrientationChange();
    
    // Listener for changes
    window.addEventListener('orientationchange', handleOrientationChange);
    window.matchMedia("(orientation: portrait)").addListener(handleOrientationChange);
    
    /**
     * Rafraîchissement par tirage (Pull-to-refresh)
     */
    
    let touchStartY = 0;
    let touchEndY = 0;
    const MIN_PULL_DISTANCE = 150;
    
    // Créer l'indicateur de rafraîchissement
    const refreshIndicator = document.createElement('div');
    refreshIndicator.classList.add('pull-to-refresh-indicator');
    refreshIndicator.innerHTML = '<i class="fas fa-sync-alt"></i>';
    refreshIndicator.style.cssText = `
        position: fixed;
        top: -50px;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 50px;
        border-radius: 25px;
        background-color: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: top 0.3s ease;
        z-index: 1001;
        opacity: 0;
        box-shadow: var(--shadow-md);
    `;
    
    document.body.appendChild(refreshIndicator);
    
    // Gérer les événements tactiles pour le pull-to-refresh
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.touches[0].clientY;
        // Vérifier si l'utilisateur est au début de la page
        if (window.scrollY <= 0) {
            refreshIndicator.style.transition = 'opacity 0.3s ease';
            refreshIndicator.style.opacity = '1';
        }
    }, { passive: true });
    
    document.addEventListener('touchmove', function(e) {
        if (window.scrollY <= 0) {
            touchEndY = e.touches[0].clientY;
            const pullDistance = touchEndY - touchStartY;
            
            if (pullDistance > 0 && pullDistance < MIN_PULL_DISTANCE) {
                const newPosition = Math.min(0, -50 + pullDistance / 2);
                refreshIndicator.style.top = newPosition + 'px';
                refreshIndicator.style.transform = `translateX(-50%) rotate(${pullDistance * 2}deg)`;
            }
        }
    }, { passive: true });
    
    document.addEventListener('touchend', function() {
        const pullDistance = touchEndY - touchStartY;
        
        if (window.scrollY <= 0 && pullDistance > MIN_PULL_DISTANCE) {
            // L'utilisateur a tiré suffisamment pour rafraîchir
            refreshIndicator.style.top = '20px';
            refreshIndicator.style.transform = 'translateX(-50%) rotate(360deg)';
            refreshIndicator.querySelector('i').classList.add('fa-spin');
            
            // Rafraîchir la page après une courte animation
            setTimeout(() => {
                window.location.reload();
            }, 800);
        } else {
            // Réinitialiser l'indicateur
            refreshIndicator.style.top = '-50px';
            refreshIndicator.style.transform = 'translateX(-50%) rotate(0deg)';
            setTimeout(() => {
                refreshIndicator.style.opacity = '0';
            }, 300);
        }
    }, { passive: true });
}); 