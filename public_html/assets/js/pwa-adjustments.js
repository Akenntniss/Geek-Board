/**
 * Script d'ajustements pour le mode PWA
 * Ajuste la position des éléments selon le mode d'affichage
 * Optimisé pour déplacer le contenu de la page d'accueil et des réparations
 */

(function() {
    // S'assurer que le script s'exécute au bon moment
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPWAAdjustments);
    } else {
        initPWAAdjustments();
    }
    
    function initPWAAdjustments() {
        console.log("Initialisation des ajustements PWA");
        
        // Vérifier si nous sommes en mode PWA
        const isPWA = document.body.classList.contains('pwa-mode');
        const isDynamicIsland = document.body.classList.contains('ios-dynamic-island');
        
        if (!isPWA && !isDynamicIsland) {
            console.log("Non en mode PWA, aucun ajustement nécessaire");
            return;
        }
        
        console.log("Mode PWA détecté, application des ajustements");
        
        // Déterminer si nous sommes sur la page des réparations
        const isRepairPage = detectRepairPage();
        
        if (isRepairPage) {
            console.log("Page de réparations détectée, ajustement de 40px");
            applyRepairPageAdjustments();
        } else {
            console.log("Page d'accueil ou autre page, ajustement de 25px");
            applyHomePageAdjustments();
        }
        
        // Exécuter à nouveau après un délai pour les éléments chargés dynamiquement
        setTimeout(function() {
            if (isRepairPage) {
                applyRepairPageAdjustments();
            } else {
                applyHomePageAdjustments();
            }
        }, 500);
    }
    
    // Détecte si la page actuelle est une page de réparations
    function detectRepairPage() {
        const url = window.location.href.toLowerCase();
        
        // Vérification par URL
        if (url.includes('repair') || 
            url.includes('reparation') || 
            url.includes('repara') || 
            url.includes('fix')) {
            return true;
        }
        
        // Vérification par contenu de la page
        const repairElements = document.querySelectorAll(
            '[class*="repair"], [id*="repair"], ' +
            '[class*="reparation"], [id*="reparation"], ' +
            '.repair-cards-container, .repair-table'
        );
        
        if (repairElements.length > 0) {
            return true;
        }
        
        // Recherche de titres ou en-têtes contenant des mots liés aux réparations
        const headings = document.querySelectorAll('h1, h2, h3, h4, h5, h6, .card-title, .page-title');
        for (const heading of headings) {
            const text = heading.textContent.toLowerCase();
            if (text.includes('repair') || 
                text.includes('réparation') || 
                text.includes('dépannage') || 
                text.includes('intervention')) {
                return true;
            }
        }
        
        return false;
    }
    
    // Applique les ajustements pour la page d'accueil (25px vers le haut)
    function applyHomePageAdjustments() {
        applyDirectStyles([
            '.modern-dashboard',
            '.dashboard-container'
        ], {
            marginTop: '-25px',
            transform: 'translateY(-25px)',
            position: 'relative',
            zIndex: '5'
        });
        
        // Ajouter un peu d'espace au bas de la page
        document.body.style.paddingBottom = '25px';
    }
    
    // Applique les ajustements pour la page des réparations (40px vers le haut)
    function applyRepairPageAdjustments() {
        // Liste étendue de sélecteurs pour cibler tous les conteneurs possibles
        const containers = [
            // Conteneurs principaux
            '.modern-dashboard',
            '.dashboard-container',
            'main',
            '.main-content',
            '.content',
            '.container-fluid',
            '.container',
            
            // Conteneurs spécifiques aux réparations
            '.repair-cards-container',
            '.repair-table',
            '.table-responsive',
            '[class*="repair"]',
            '[id*="repair"]',
            '[class*="reparation"]',
            '[id*="reparation"]',
            
            // Cartes et sections
            '.card',
            'section',
            '.section',
            '.page-content',
            '.content-wrapper'
        ];
        
        applyDirectStyles(containers, {
            marginTop: '-40px',
            transform: 'translateY(-40px)',
            position: 'relative',
            zIndex: '10'
        });
        
        // Forcer le style directement sur le premier élément trouvé
        const firstContainer = document.querySelector(containers.join(', '));
        if (firstContainer) {
            Object.assign(firstContainer.style, {
                marginTop: '-40px',
                transform: 'translateY(-40px)',
                position: 'relative',
                zIndex: '10'
            });
        }
        
        // Ajouter un peu d'espace au bas de la page
        document.body.style.paddingBottom = '40px';
    }
    
    // Applique les styles directement aux éléments correspondant aux sélecteurs
    function applyDirectStyles(selectors, styles) {
        const elementsSet = new Set(); // Pour éviter les doublons
        
        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => elementsSet.add(el));
        });
        
        // Convertir en tableau et appliquer les styles
        Array.from(elementsSet).forEach(el => {
            Object.assign(el.style, styles);
        });
    }
    
    // Ajouter un écouteur pour les modifications du DOM pour les éléments chargés dynamiquement
    if (window.MutationObserver) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                    // Réappliquer les ajustements si de nouveaux éléments sont ajoutés
                    if (detectRepairPage()) {
                        applyRepairPageAdjustments();
                    } else {
                        applyHomePageAdjustments();
                    }
                }
            });
        });
        
        // Observer les changements dans le corps du document
        observer.observe(document.body, { childList: true, subtree: true });
    }
})(); 