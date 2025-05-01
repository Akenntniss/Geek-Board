// Script de débogage pour la barre de navigation en bas de page
(function() {
    console.log("=== OUTIL DE DIAGNOSTIC DE LA BARRE DE NAVIGATION ===");
    
    // Vérifier si une autre barre existe déjà (barre d'urgence)
    const checkEmergencyNav = () => {
        const emergencyNav = document.getElementById('emergency-bottom-nav');
        if (emergencyNav) {
            console.log("Barre d'urgence trouvée, la masquer pour éviter les doublons");
            emergencyNav.style.display = 'none';
        }
    };
    
    // Fonction pour vérifier si un élément existe dans le DOM
    function checkElement(selector, name) {
        const element = document.querySelector(selector);
        const exists = !!element;
        console.log(`${name} (${selector}): ${exists ? "TROUVÉ ✅" : "NON TROUVÉ ❌"}`);
        if (exists) {
            console.log(`  - Style display: ${getComputedStyle(element).display}`);
            console.log(`  - Style visibility: ${getComputedStyle(element).visibility}`);
            console.log(`  - Style opacity: ${getComputedStyle(element).opacity}`);
            console.log(`  - Style z-index: ${getComputedStyle(element).zIndex}`);
            console.log(`  - Position: ${getComputedStyle(element).position}`);
            console.log(`  - Classes: ${element.className}`);
            console.log(`  - Parent: ${element.parentElement ? element.parentElement.tagName : "NONE"}`);
        }
        return element;
    }
    
    // Fonction pour vérifier le mode d'affichage
    function checkDisplayMode() {
        const isPwa = window.matchMedia('(display-mode: standalone)').matches || 
                      window.navigator.standalone ||
                      document.referrer.includes('android-app://') ||
                      window.location.search.includes('test_pwa=true');
        console.log(`Mode PWA: ${isPwa ? "ACTIF ✅" : "INACTIF ❌"}`);
        return isPwa;
    }
    
    // Fonction pour vérifier les styles appliqués
    function checkStyles() {
        console.log("=== STYLES CSS CHARGÉS ===");
        const styleSheets = Array.from(document.styleSheets);
        styleSheets.forEach(sheet => {
            try {
                console.log(`Feuille de style: ${sheet.href || "inline"}`);
                if (sheet.href && sheet.href.includes("bottom-nav.css")) {
                    console.log(`  - Feuille de style de la barre de navigation trouvée ✅`);
                }
            } catch (e) {
                console.log(`  - Erreur lors de la lecture d'une feuille de style: ${e.message}`);
            }
        });
    }
    
    // Fonction pour forcer l'affichage de la barre de navigation
    function forceNavbarDisplay() {
        console.log("=== FORCE L'AFFICHAGE DE LA BARRE DE NAVIGATION ===");
        
        // Masquer la barre d'urgence pour éviter les doublons
        checkEmergencyNav();
        
        // Forcer le mode PWA si ce n'est pas déjà le cas
        if (!document.body.classList.contains('pwa-mode')) {
            document.body.classList.add('pwa-mode');
            console.log("Mode PWA forcé sur le body");
        }
        
        // Trouver la barre de navigation
        const navbar = document.querySelector('.bottom-nav-container') || document.getElementById('bottom-nav-container');
        
        if (navbar) {
            console.log("Barre trouvée, application des styles forcés");
            // Appliquer les styles directement
            Object.assign(navbar.style, {
                display: 'flex',
                position: 'fixed',
                bottom: '0',
                left: '0',
                right: '0',
                zIndex: '99999',
                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                height: '60px',
                boxShadow: '0 -5px 15px rgba(0, 0, 0, 0.2)',
                visibility: 'visible',
                opacity: '1',
                borderTop: '3px solid red',
                justifyContent: 'space-around',
                alignItems: 'center'
            });
            
            // Supprimer toutes les classes qui pourraient masquer la barre
            navbar.classList.remove('hidden', 'd-none');
            
            // Forcer un padding au body
            document.body.style.paddingBottom = '70px';
            
            return true;
        } else {
            console.error("Barre de navigation non trouvée dans le DOM, impossible de l'afficher");
            
            // Vérifier si nous pouvons créer la barre de navigation
            console.log("Tentative de création de la barre de navigation...");
            
            // Vérifier si une barre d'urgence existe déjà
            const emergencyNav = document.getElementById('emergency-bottom-nav');
            if (emergencyNav) {
                console.log("Une barre d'urgence existe déjà, utilisons-la plutôt");
                emergencyNav.style.display = 'flex';
                return true;
            }
            
            // Si non, créer une nouvelle barre
            const newNavbar = document.createElement('div');
            newNavbar.id = 'bottom-nav-container';
            newNavbar.className = 'bottom-nav-container';
            
            // Ajouter le HTML de la barre
            newNavbar.innerHTML = `
                <a href="index.php" class="bottom-nav-item" data-page="accueil">
                    <i class="fas fa-home"></i>
                    <span>Accueil</span>
                </a>
                <a href="index.php?page=reparations" class="bottom-nav-item" data-page="reparations">
                    <i class="fas fa-tools"></i>
                    <span>Réparation</span>
                </a>
                <div class="bottom-nav-fab" id="bottom-fab-button">
                    <i class="fas fa-plus"></i>
                </div>
                <a href="index.php?page=taches" class="bottom-nav-item" data-page="taches">
                    <i class="fas fa-tasks"></i>
                    <span>Tâches</span>
                </a>
                <a href="#launchpad-menu" class="bottom-nav-item" data-toggle="modal" data-target="#launchpadModal">
                    <i class="fas fa-th-large"></i>
                    <span>Menu</span>
                </a>
            `;
            
            // Appliquer les styles
            Object.assign(newNavbar.style, {
                display: 'flex',
                position: 'fixed',
                bottom: '0',
                left: '0',
                right: '0',
                zIndex: '99999',
                backgroundColor: 'rgba(255, 255, 255, 0.95)',
                height: '60px',
                boxShadow: '0 -5px 15px rgba(0, 0, 0, 0.2)',
                visibility: 'visible',
                opacity: '1',
                borderTop: '3px solid red',
                justifyContent: 'space-around',
                alignItems: 'center'
            });
            
            // Ajouter la barre au body
            document.body.appendChild(newNavbar);
            
            console.log("Nouvelle barre de navigation créée et ajoutée au DOM");
            return true;
        }
    }
    
    // Lancer les vérifications
    function runDiagnostics() {
        console.log("=== DÉBUT DU DIAGNOSTIC ===");
        
        // Vérifier et gérer les barres doubles
        checkEmergencyNav();
        
        const isPwa = checkDisplayMode();
        
        console.log("=== ÉLÉMENTS DU DOM ===");
        const bottomNav = checkElement('.bottom-nav-container', 'Barre de navigation');
        const bottomNavById = checkElement('#bottom-nav-container', 'Barre de navigation (par ID)');
        const fabButton = checkElement('.bottom-nav-fab', 'Bouton FAB');
        const fabOptions = checkElement('.fab-options-container', 'Container options FAB');
        const fabOverlay = checkElement('.fab-overlay', 'Overlay FAB');
        
        console.log("=== ÉTAT DU BODY ===");
        console.log(`Classes du body: ${document.body.className}`);
        console.log(`Body a la classe pwa-mode: ${document.body.classList.contains('pwa-mode') ? "OUI ✅" : "NON ❌"}`);
        
        // Vérifier les feuilles de style
        checkStyles();
        
        // Forcer l'affichage si nécessaire
        if (!bottomNav && !bottomNavById) {
            console.log("Barre de navigation manquante, tentative de création...");
            forceNavbarDisplay();
        } else if (getComputedStyle(bottomNav || bottomNavById).display === 'none') {
            console.log("Barre de navigation masquée, forçage de l'affichage...");
            forceNavbarDisplay();
        }
        
        console.log("=== FIN DU DIAGNOSTIC ===");
    }
    
    // Exécuter le diagnostic au chargement du document
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runDiagnostics);
    } else {
        runDiagnostics();
    }
    
    // Réexécuter le diagnostic après un délai (pour attraper les changements dynamiques)
    setTimeout(runDiagnostics, 1000);
    
    // Exposer les fonctions de diagnostic globalement pour un usage via la console
    window.bottomNavDiagnostics = {
        run: runDiagnostics,
        force: forceNavbarDisplay,
        checkEmergency: checkEmergencyNav
    };
})(); 