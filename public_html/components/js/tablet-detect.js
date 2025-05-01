/**
 * Détection des tablettes et gestion de l'orientation pour GeekBoard
 * Ce script détecte les tablettes (particulièrement iPad) et ajuste l'affichage
 * selon l'orientation (portrait ou paysage).
 */

document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour détecter si nous sommes sur un iPad
    function detectIPad() {
        // Modern iPad detection
        const isIpad = navigator.userAgent.match(/(iPad)/) ||
                      (navigator.userAgent.match(/(Macintosh)/) && 'ontouchend' in document);
        
        return isIpad;
    }
    
    // Fonction pour détecter l'orientation
    function detectOrientation() {
        return window.innerWidth < window.innerHeight ? 'portrait' : 'landscape';
    }
    
    // Fonction pour détecter Safari
    function detectSafari() {
        return /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    }
    
    // Fonction pour détecter si c'est un appareil mobile
    function detectMobile() {
        return /iPhone|iPod|Android|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    // Log de diagnostic pour vérifier la détection et l'affichage
    console.log("---- DIAGNOSTIC BARRE DE NAVIGATION ----");
    console.log("User Agent:", navigator.userAgent);
    console.log("Résolution:", window.innerWidth, "x", window.innerHeight);
    console.log("Est iPad:", detectIPad());
    console.log("Est Safari:", detectSafari());
    console.log("Est Mobile:", detectMobile());
    
    // Fonction pour appliquer les classes selon l'appareil et l'orientation
    function applyDeviceClasses() {
        const isIpad = detectIPad();
        const orientation = detectOrientation();
        const isSafari = detectSafari();
        const isMobile = detectMobile();
        
        console.log("Application des classes - Safari:", isSafari, "iPad:", isIpad, "Mobile:", isMobile);
        
        // Appliquer la classe iPad si détecté
        if (isIpad) {
            document.body.classList.add('ipad-device');
            
            // Appliquer les classes d'orientation
            if (orientation === 'portrait') {
                document.body.classList.add('ipad-portrait');
                document.body.classList.remove('ipad-landscape');
            } else {
                document.body.classList.add('ipad-landscape');
                document.body.classList.remove('ipad-portrait');
            }
        } else {
            document.body.classList.remove('ipad-device', 'ipad-portrait', 'ipad-landscape');
        }
        
        if (isMobile) {
            document.body.classList.add('mobile-device');
        } else {
            document.body.classList.remove('mobile-device');
        }
        
        // FORCER L'AFFICHAGE DE LA BARRE DE NAVIGATION DESKTOP SUR SAFARI
        if (isSafari) {
            document.body.classList.add('safari-browser');
            console.log("Safari détecté - Forçage de l'affichage");
            
            // Seulement pour Safari sur desktop (pas iPad, pas mobile)
            if (!isIpad && !isMobile && window.innerWidth >= 992) {
                console.log("Safari Desktop détecté (non iPad/mobile)");
                
                // Force l'affichage de la navbar desktop
                const desktopNavbar = document.getElementById('desktop-navbar');
                const mobileDock = document.getElementById('mobile-dock');
                
                if (desktopNavbar) {
                    console.log("Barre desktop trouvée, application du style forcé");
                    // Force l'affichage sans tenir compte d'autres règles CSS avec !important
                    desktopNavbar.setAttribute('style', 'display: block !important; visibility: visible !important; opacity: 1 !important; height: var(--navbar-height) !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; z-index: 1030 !important;');
                } else {
                    console.log("ERREUR: Barre desktop non trouvée");
                }
                
                if (mobileDock) {
                    console.log("Dock mobile trouvé, masquage forcé sur desktop");
                    // Force le masquage du dock mobile
                    mobileDock.setAttribute('style', 'display: none !important; visibility: hidden !important; opacity: 0 !important;');
                } else {
                    console.log("ERREUR: Dock mobile non trouvé");
                }
                
                // Ajouter une classe spécifique pour Safari desktop
                document.body.classList.add('safari-desktop');
                
                // Ajuster le padding du body pour la barre de navigation
                document.body.style.paddingTop = 'var(--navbar-height)';
                document.body.style.paddingBottom = '0';
            } 
            // Pour Safari sur mobile ou iPad
            else if (isIpad || isMobile || window.innerWidth < 992) {
                console.log("Safari Mobile/iPad détecté - Forçage du dock mobile");
                
                const desktopNavbar = document.getElementById('desktop-navbar');
                const mobileDock = document.getElementById('mobile-dock');
                
                // Cacher la barre desktop
                if (desktopNavbar) {
                    desktopNavbar.setAttribute('style', 'display: none !important; visibility: hidden !important; opacity: 0 !important;');
                }
                
                // Afficher le dock mobile
                if (mobileDock) {
                    mobileDock.setAttribute('style', 'display: block !important; visibility: visible !important; opacity: 1 !important; position: fixed !important; bottom: 0 !important; left: 0 !important; right: 0 !important; z-index: 1030 !important;');
                }
                
                // Classes spécifiques
                document.body.classList.add('mobile-safari');
                if (isIpad) document.body.classList.add('ipad-device');
                if (isMobile) document.body.classList.add('mobile-device');
                
                // Ajuster le padding du body
                document.body.style.paddingTop = '0';
                document.body.style.paddingBottom = 'var(--dock-height)';
            }
        } else {
            document.body.classList.remove('safari-browser', 'safari-desktop', 'mobile-safari');
        }
        
        // Diagnostic des classes appliquées
        console.log("Classes finales sur body:", document.body.className);
        console.log("État final de la barre desktop:", document.getElementById('desktop-navbar')?.style.display);
        console.log("État final du dock mobile:", document.getElementById('mobile-dock')?.style.display);
    }
    
    // Appliquer les classes initiales
    applyDeviceClasses();
    
    // Mettre à jour les classes lors du changement d'orientation ou redimensionnement
    window.addEventListener('resize', applyDeviceClasses);
    window.addEventListener('orientationchange', applyDeviceClasses);
    
    // Forcer des vérifications supplémentaires après différents délais
    // pour résoudre les problèmes de timing sur Safari
    setTimeout(applyDeviceClasses, 100);
    setTimeout(applyDeviceClasses, 500);
    setTimeout(applyDeviceClasses, 1000);
    setTimeout(function() {
        console.log("Vérification finale après 2 secondes");
        applyDeviceClasses();
        
        // Récupérer l'état actuel
        const isIpad = detectIPad();
        const isMobile = detectMobile();
        const isSafari = detectSafari();
        
        // SOLUTION DE SECOURS: ajouter manuellement la barre de navigation si elle n'existe pas
        if (isSafari && !isIpad && !isMobile && window.innerWidth >= 992 && !document.getElementById('desktop-navbar')) {
            console.log("SOLUTION D'URGENCE: Création manuelle de la barre de navigation desktop");
            const navbarHTML = `
            <nav id="desktop-navbar" class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: var(--navbar-height) !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; z-index: 1030 !important;">
                <div class="container-fluid px-3">
                    <a class="navbar-brand me-0 me-lg-4 d-flex align-items-center" href="index.php">
                        <img src="assets/images/logo/AppIcons_lightMode/appstore.png" alt="GeekBoard" height="40" class="light-mode-logo">
                        <img src="assets/images/logo/AppIcons-darkmode/playstore.png" alt="GeekBoard" height="40" class="dark-mode-logo" style="display: none;">
                        <div class="ms-2 d-flex flex-column justify-content-center" style="height: 40px;">
                            <div class="fw-bold brand-text-primary" style="font-size: 1.25rem; line-height: 1.2;">TechBoard</div>
                            <div class="fw-bold brand-text-secondary" style="font-size: 1rem; line-height: 1;">Assistant</div>
                        </div>
                    </a>
                </div>
            </nav>`;
            
            // Ajouter la barre au début du body
            document.body.insertAdjacentHTML('afterbegin', navbarHTML);
            
            // Masquer le dock mobile
            const mobileDock = document.getElementById('mobile-dock');
            if (mobileDock) {
                mobileDock.style.display = 'none';
            }
        }
        
        // SOLUTION DE SECOURS: ajouter manuellement le dock mobile s'il n'existe pas
        if ((isIpad || isMobile || window.innerWidth < 992) && !document.getElementById('mobile-dock')) {
            console.log("SOLUTION D'URGENCE: Création manuelle du dock mobile");
            const dockHTML = `
            <div id="mobile-dock" style="display: block !important; visibility: visible !important; opacity: 1 !important; position: fixed !important; bottom: 0 !important; left: 0 !important; right: 0 !important; z-index: 1030 !important; background-color: white !important; box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1) !important;">
                <div class="mobile-dock-container">
                    <a href="index.php" class="dock-item active">
                        <i class="fas fa-home"></i>
                        <span>Accueil</span>
                    </a>
                    <a href="index.php?page=reparations" class="dock-item">
                        <i class="fas fa-tools"></i>
                        <span>Réparations</span>
                    </a>
                    <div class="dock-item-center">
                        <button class="btn-nouvelle-action" type="button">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <a href="index.php?page=taches" class="dock-item">
                        <i class="fas fa-tasks"></i>
                        <span>Tâches</span>
                    </a>
                    <a href="#" class="dock-item">
                        <i class="fas fa-bars"></i>
                        <span>Menu</span>
                    </a>
                </div>
            </div>`;
            
            // Ajouter le dock à la fin du body
            document.body.insertAdjacentHTML('beforeend', dockHTML);
            
            // Masquer la barre desktop si elle existe
            const desktopNavbar = document.getElementById('desktop-navbar');
            if (desktopNavbar) {
                desktopNavbar.style.display = 'none';
            }
        }
    }, 2000);
    
    // ===== NOUVELLE FONCTIONNALITÉ: MASQUAGE AU DÉFILEMENT =====
    
    // Sélectionner le dock mobile
    const mobileDock = document.getElementById('mobile-dock');
    if (!mobileDock) return;
    
    let lastScrollTop = 0;
    let scrollThreshold = 20; // Seuil de défilement avant de cacher/montrer la barre
    let isScrolling;
    let hideTimeout;
    
    // Fonction de gestion du défilement
    function handleScroll() {
        if (!mobileDock) return;
        
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
        
        // Ignorer les petits mouvements de défilement (comme le rebond sur iOS)
        if (Math.abs(lastScrollTop - currentScroll) < scrollThreshold) return;
        
        // Déterminer la direction du défilement
        if (currentScroll > lastScrollTop && currentScroll > 100) {
            // Défilement vers le bas et pas tout en haut de la page
            mobileDock.classList.add('hidden');
        } else {
            // Défilement vers le haut ou tout en haut de la page
            mobileDock.classList.remove('hidden');
        }
        
        lastScrollTop = currentScroll;
        
        // Effacer le timeout existant
        clearTimeout(hideTimeout);
        
        // Définir un nouveau timeout pour réafficher la barre après une période d'inactivité
        hideTimeout = setTimeout(() => {
            mobileDock.classList.remove('hidden');
        }, 3000);
    }
    
    // Ajouter un écouteur d'événement de défilement avec throttling
    window.addEventListener('scroll', function() {
        clearTimeout(isScrolling);
        
        // Throttle des événements de défilement pour de meilleures performances
        isScrolling = setTimeout(handleScroll, 50);
    }, { passive: true });
    
    // Écouteur d'événement tactile pour les appareils tactiles
    let touchStartY = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.touches[0].clientY;
    }, { passive: true });
    
    document.addEventListener('touchmove', function(e) {
        const touchY = e.touches[0].clientY;
        const diff = touchStartY - touchY;
        
        // Si le défilement tactile est significatif
        if (Math.abs(diff) > scrollThreshold) {
            if (diff > 0) {
                // Défilement vers le bas
                mobileDock.classList.add('hidden');
            } else {
                // Défilement vers le haut
                mobileDock.classList.remove('hidden');
            }
        }
    }, { passive: true });
    
    // Afficher la barre à la fin du défilement tactile
    document.addEventListener('touchend', function() {
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(() => {
            mobileDock.classList.remove('hidden');
        }, 3000);
    }, { passive: true });
}); 