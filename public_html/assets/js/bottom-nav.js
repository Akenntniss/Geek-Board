document.addEventListener('DOMContentLoaded', function() {
    console.log("Script de navigation améliorée chargé");
    
    // Fonction pour vérifier et désactiver les barres dupliquées
    function checkForDuplicateNavbars() {
        const mainNav = document.querySelector('.bottom-nav-container');
        const emergencyNav = document.getElementById('emergency-bottom-nav');
        
        if (mainNav && emergencyNav) {
            console.log("Deux barres de navigation détectées, désactivation de la barre d'urgence");
            emergencyNav.style.display = 'none';
            // Si possible, supprimer la barre d'urgence pour éviter des conflits
            if (emergencyNav.parentNode) {
                emergencyNav.parentNode.removeChild(emergencyNav);
            }
        }
    }
    
    // Détection améliorée du mode PWA
    function isPwaMode() {
        // Vérifier si l'application est en mode PWA
        return window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone ||
               document.referrer.includes('android-app://') ||
               window.location.search.includes('test_pwa=true');
    }
    
    // Fonction d'initialisation de la barre de navigation
    function initBottomNav() {
        console.log("Initialisation de la barre de navigation inférieure");
        
        // Vérifier le mode d'affichage
        if (isPwaMode()) {
            document.body.classList.add('pwa-mode');
            console.log("Mode PWA détecté et activé");
        }
        
        // Vérifier et désactiver les barres dupliquées
        checkForDuplicateNavbars();
        
        // Trouver la barre de navigation
        const bottomNav = document.getElementById('bottom-nav-container') || document.querySelector('.bottom-nav-container');
        
        if (bottomNav) {
            console.log("Barre de navigation trouvée, initialisation...");
            
            // Forcer l'affichage en mode PWA
            if (isPwaMode()) {
                bottomNav.style.display = 'flex';
                bottomNav.style.visibility = 'visible';
                bottomNav.style.opacity = '1';
                
                // Appliquer un effet subtil d'apparition
                bottomNav.style.transform = 'translateY(100%)';
                setTimeout(() => {
                    bottomNav.style.transform = 'translateY(0)';
                }, 100);
            } else {
                // En mode navigateur, masquer la barre
                bottomNav.style.display = 'none';
            }
            
            // Ajouter des effets de pression pour iOS
            const navItems = bottomNav.querySelectorAll('.bottom-nav-item');
            navItems.forEach(item => {
                // Utiliser des événements touch pour les appareils mobiles
                item.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.95)';
                }, { passive: true });
                
                item.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                }, { passive: true });
                
                // Ajouter les événements mouse pour le desktop
                item.addEventListener('mousedown', function() {
                    this.style.transform = 'scale(0.95)';
                });
                
                item.addEventListener('mouseup', function() {
                    this.style.transform = 'scale(1)';
                });
            });
            
            // Ajouter les effets pour le bouton d'ajout
            const addButton = bottomNav.querySelector('.bottom-nav-add');
            if (addButton) {
                // Utiliser des événements touch pour les appareils mobiles
                addButton.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.9) translateY(15px)';
                }, { passive: true });
                
                addButton.addEventListener('touchend', function() {
                    this.style.transform = 'translateY(15px)';
                }, { passive: true });
                
                // Ajouter les événements mouse pour le desktop
                addButton.addEventListener('mousedown', function() {
                    this.style.transform = 'scale(0.9) translateY(15px)';
                });
                
                addButton.addEventListener('mouseup', function() {
                    this.style.transform = 'translateY(15px)';
                });
                
                // Retour haptique pour le bouton d'ajout
                addButton.addEventListener('click', function() {
                    if (navigator.vibrate) {
                        navigator.vibrate(10);
                    }
                });
            }
            
            console.log("Animations de la barre de navigation configurées");
        } else {
            console.error("Barre de navigation non trouvée dans le DOM");
        }
    }
    
    // Initialiser la barre
    initBottomNav();
    
    // Revérifier après un court délai pour s'assurer qu'aucune barre n'est dupliquée
    setTimeout(checkForDuplicateNavbars, 500);
    
    // S'assurer que la barre est bien visible en mode PWA
    setTimeout(function() {
        if (isPwaMode()) {
            const bottomNav = document.getElementById('bottom-nav-container') || document.querySelector('.bottom-nav-container');
            if (bottomNav) {
                bottomNav.style.display = 'flex';
                bottomNav.style.visibility = 'visible';
                bottomNav.style.opacity = '1';
            }
        }
    }, 1000);
    
    // Variables pour la gestion du défilement
    const bottomNav = document.getElementById('bottom-nav-container') || document.querySelector('.bottom-nav-container');
    let lastScrollTop = 0;
    let scrollTimer;
    let touchStartY = 0;
    let touchEndY = 0;
    let isScrolling = false;
    
    // Gestion améliorée du défilement avec detection de la direction du swipe
    window.addEventListener('scroll', function() {
        if (!bottomNav || !isPwaMode()) return;
        
        const st = window.pageYOffset || document.documentElement.scrollTop;
        
        // Nettoyer le timer existant
        clearTimeout(scrollTimer);
        
        // Si on défile vers le bas et que la barre est visible, la masquer avec un délai
        if (st > lastScrollTop + 30 && !bottomNav.classList.contains('hidden') && !isScrolling) {
            isScrolling = true;
            bottomNav.classList.add('hidden');
            
            setTimeout(() => {
                isScrolling = false;
            }, 400);
        } 
        // Si on défile vers le haut et que la barre est masquée, l'afficher immédiatement
        else if (st < lastScrollTop - 10 && bottomNav.classList.contains('hidden') && !isScrolling) {
            isScrolling = true;
            bottomNav.classList.remove('hidden');
            
            setTimeout(() => {
                isScrolling = false;
            }, 400);
        }
        
        // Définir un timer pour réafficher la barre après 3 secondes d'inactivité
        scrollTimer = setTimeout(() => {
            if (bottomNav) {
                bottomNav.classList.remove('hidden');
            }
        }, 3000);
        
        lastScrollTop = st <= 0 ? 0 : st; // Pour les mobiles ou les comportements étranges
    }, { passive: true });
    
    // Gestion des événements tactiles pour améliorer la détection du swipe
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        touchEndY = e.changedTouches[0].screenY;
        const differenceY = touchEndY - touchStartY;
        
        // Si on swipe vers le haut significativement, afficher la barre
        if (differenceY < -70 && bottomNav && bottomNav.classList.contains('hidden') && isPwaMode()) {
            bottomNav.classList.remove('hidden');
        }
        // Si on swipe vers le bas significativement, masquer la barre
        else if (differenceY > 70 && bottomNav && !bottomNav.classList.contains('hidden') && isPwaMode()) {
            bottomNav.classList.add('hidden');
        }
    }, { passive: true });
    
    // Correction pour iOS Safari - Restaurer la barre lors d'un swipe depuis le bas
    document.addEventListener('touchmove', function(e) {
        if (!bottomNav || !isPwaMode()) return;
        
        const touch = e.touches[0];
        const screenHeight = window.innerHeight;
        
        // Si le toucher est près du bas de l'écran, faire apparaître la barre
        if (touch.clientY > screenHeight - 40 && bottomNav.classList.contains('hidden')) {
            bottomNav.classList.remove('hidden');
        }
    }, { passive: true });
    
    // Mettre à jour la classe active sur les liens de navigation
    const navItems = document.querySelectorAll('.bottom-nav-item');
    
    if (navItems.length > 0) {
        // Récupérer le paramètre page de l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get('page') || 'accueil';
        
        // Activer le lien correspondant à la page actuelle
        navItems.forEach(item => {
            const pageTarget = item.getAttribute('data-page');
            if (pageTarget === currentPage) {
                item.classList.add('active');
                
                // Ajouter une subtile animation pour l'élément actif
                item.style.transform = 'translateY(-3px)';
                setTimeout(() => {
                    item.style.transform = '';
                }, 300);
            }
            
            // Ajouter des effets de pression
            item.addEventListener('click', function() {
                // Retour haptique léger
                if (navigator.vibrate) {
                    navigator.vibrate(5);
                }
            });
        });
    } else {
        console.warn("Aucun élément de navigation n'a été trouvé dans le DOM");
    }
    
    // Event listener pour détecter les changements de mode d'affichage
    // Utile pour les PWA installées qui sont ouvertes plus tard
    window.matchMedia('(display-mode: standalone)').addEventListener('change', function(e) {
        if (e.matches) {
            console.log("Application passée en mode standalone PWA");
            document.body.classList.add('pwa-mode');
            const bottomNav = document.getElementById('bottom-nav-container') || document.querySelector('.bottom-nav-container');
            if (bottomNav) {
                bottomNav.style.display = 'flex';
                bottomNav.style.visibility = 'visible';
                bottomNav.style.opacity = '1';
            }
        } else {
            console.log("Application sortie du mode standalone PWA");
            document.body.classList.remove('pwa-mode');
        }
    });
    
    // Gestion spécifique pour iOS
    if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
        console.log("Appareil iOS détecté, application des correctifs spécifiques");
        
        // Vérifier si nous sommes en PWA sur iOS
        if (window.navigator.standalone) {
            console.log("Mode PWA iOS activé");
            document.body.classList.add('ios-pwa');
            
            // Forcer une marge au bas du body pour iOS
            document.body.style.paddingBottom = 'env(safe-area-inset-bottom, 20px)';
            
            // S'assurer que la barre est visible
            const bottomNav = document.getElementById('bottom-nav-container') || document.querySelector('.bottom-nav-container');
            if (bottomNav) {
                bottomNav.style.display = 'flex';
                bottomNav.style.visibility = 'visible';
                bottomNav.style.opacity = '1';
            }
        }
    }
});

// Fonction qui détecte si l'appareil est un PC ou mobile
function isDesktop() {
    return window.innerWidth >= 992;
}

// Fonction qui détecte si l'application est en mode PWA
function isPWA() {
    return window.matchMedia('(display-mode: standalone)').matches || 
           window.navigator.standalone || 
           document.referrer.includes('android-app://') ||
           (document.body && document.body.classList.contains('pwa-mode'));
}

// Fonction pour masquer/afficher la barre de navigation inférieure
function toggleBottomNavVisibility() {
    // Sélectionner tous les éléments liés à la navigation inférieure
    const bottomNavElements = document.querySelectorAll('.bottom-nav-container, #bottom-nav-container, [class*="bottom-nav"], .mobile-action-button');
    
    // Si c'est un PC, masquer la barre de navigation
    if (isDesktop()) {
        bottomNavElements.forEach(element => {
            if (element) {
                element.style.display = 'none';
                element.style.opacity = '0';
                element.style.visibility = 'hidden';
                element.style.pointerEvents = 'none';
            }
        });
    } else {
        // En mobile, afficher uniquement si mode PWA
        if (isPWA()) {
            bottomNavElements.forEach(element => {
                if (element) {
                    element.style.display = 'flex';
                    element.style.opacity = '1';
                    element.style.visibility = 'visible';
                    element.style.pointerEvents = 'auto';
                }
            });
        }
    }
}

// Exécuter immédiatement
document.addEventListener('DOMContentLoaded', function() {
    // Appliquer au chargement
    toggleBottomNavVisibility();
    
    // Surveiller les changements de taille d'écran
    window.addEventListener('resize', toggleBottomNavVisibility);
    
    // Vérifier si la classe PWA est ajoutée dynamiquement
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                toggleBottomNavVisibility();
            }
        });
    });
    
    // Observer les changements de classe sur le body
    if (document.body) {
        observer.observe(document.body, { attributes: true });
    }
}); 