/**
 * Script dédié à la barre de navigation mobile en style dock
 */

document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour vérifier si l'appareil est mobile
    function isMobile() {
        return window.innerWidth <= 768;
    }
    
    // Fonction pour mettre à jour la visibilité de la barre
    function updateBottomNavVisibility() {
        const bottomNav = document.querySelector('.mobile-bottom-nav');
        if (bottomNav) {
            if (isMobile()) {
                bottomNav.style.display = 'block';
                
                // Ajuster le padding du body pour éviter que le contenu soit masqué
                const paddingBottom = /iPhone|iPad|iPod/.test(navigator.userAgent) 
                    ? 'calc(70px + env(safe-area-inset-bottom, 0px))'
                    : '70px';
                
                document.body.style.paddingBottom = paddingBottom;
                
                // Gestion spéciale pour iOS
                if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
                    bottomNav.style.paddingBottom = 'env(safe-area-inset-bottom, 0px)';
                    bottomNav.style.height = 'calc(60px + env(safe-area-inset-bottom, 0px))';
                }
            } else {
                bottomNav.style.display = 'none';
                document.body.style.paddingBottom = '0';
            }
        }
    }
    
    // Mettre en surbrillance l'élément actif
    function highlightActiveNavItem() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.php';
        const navItems = document.querySelectorAll('.mobile-nav-item');
        
        navItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && (href === currentPage || (currentPage === 'index.php' && href === '/'))) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }
    
    // Ajouter des effets visuels lors du clic
    function addTapEffects() {
        const navItems = document.querySelectorAll('.mobile-nav-item');
        
        navItems.forEach(item => {
            item.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            item.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
            
            item.addEventListener('touchcancel', function() {
                this.style.transform = 'scale(1)';
            });
        });
    }
    
    // S'assurer que la barre est bien positionnée sur iOS avec l'inset-bottom
    function fixIOSBottomNav() {
        if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
            const bottomNav = document.querySelector('.mobile-bottom-nav');
            if (bottomNav) {
                // Ajouter du padding pour iOS notch si nécessaire
                bottomNav.style.paddingBottom = 'env(safe-area-inset-bottom, 0px)';
                bottomNav.style.height = 'calc(60px + env(safe-area-inset-bottom, 0px))';
            }
        }
    }
    
    // Gestion des événements de défilement pour masquer/afficher la barre
    function handleScrollEffects() {
        let lastScrollTop = 0;
        let scrollTimer = null;
        
        window.addEventListener('scroll', function() {
            if (scrollTimer !== null) {
                clearTimeout(scrollTimer);
            }
            
            const bottomNav = document.querySelector('.mobile-bottom-nav');
            if (!bottomNav || !isMobile()) return;
            
            const st = window.pageYOffset || document.documentElement.scrollTop;
            
            // Masquer la barre lors du défilement vers le bas
            if (st > lastScrollTop && st > 100) {
                bottomNav.style.transform = 'translateY(100%)';
            } 
            // Afficher la barre lors du défilement vers le haut
            else if (st < lastScrollTop) {
                bottomNav.style.transform = 'translateY(0)';
            }
            
            lastScrollTop = st <= 0 ? 0 : st;
            
            // Réafficher la barre après 2 secondes d'inactivité
            scrollTimer = setTimeout(function() {
                bottomNav.style.transform = 'translateY(0)';
            }, 2000);
        }, { passive: true });
    }
    
    // Initialiser
    updateBottomNavVisibility();
    highlightActiveNavItem();
    addTapEffects();
    fixIOSBottomNav();
    handleScrollEffects();
    
    // Mettre à jour lors du redimensionnement
    window.addEventListener('resize', updateBottomNavVisibility);
    
    // Mettre à jour lors du changement d'orientation
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            updateBottomNavVisibility();
            fixIOSBottomNav();
        }, 300);
    });
    
    // Vérifier à nouveau la visibilité lorsque la page est complètement chargée
    window.addEventListener('load', function() {
        updateBottomNavVisibility();
        fixIOSBottomNav();
    });
}); 