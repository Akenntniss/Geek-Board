/**
 * Interactions JavaScript optimisées pour les tablettes
 * Améliore l'expérience utilisateur sur les appareils tactiles
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Détecter si l'appareil est tactile
    detectTouchDevice();
    
    // Initialiser les optimisations pour tablette si nécessaire
    if (document.body.classList.contains('touch-device')) {
        // Optimiser les zones tactiles
        optimizeTouchTargets();
        
        // Optimiser les tableaux pour le tactile
        optimizeTablesForTouch();
        
        // Ajouter des effets de feedback tactile
        addTouchFeedback();
        
        // Optimiser les formulaires pour le tactile
        optimizeFormsForTouch();
        
        // Optimiser le défilement
        optimizeScrolling();
        
        // Gérer l'orientation de l'écran
        handleOrientation();
        
        // Écouter les changements d'orientation
        window.addEventListener('orientationchange', handleOrientation);
    }
});

/**
 * Détecte si l'appareil est tactile et ajoute une classe au body
 */
function detectTouchDevice() {
    const isTouchDevice = 'ontouchstart' in window || 
                          navigator.maxTouchPoints > 0 || 
                          navigator.msMaxTouchPoints > 0;
    
    if (isTouchDevice) {
        document.body.classList.add('touch-device');
        
        // Ajouter des attributs pour améliorer l'accessibilité tactile
        const interactiveElements = document.querySelectorAll('a, button, .btn, .nav-link, .card');
        interactiveElements.forEach(element => {
            element.classList.add('touch-friendly');
        });
    } else {
        document.body.classList.add('no-touch');
    }
}

/**
 * Optimise les zones tactiles pour une meilleure expérience
 */
function optimizeTouchTargets() {
    // Augmenter la taille des zones cliquables
    const touchTargets = document.querySelectorAll('a, button, .btn, .nav-link, .form-control, .form-select');
    touchTargets.forEach(element => {
        // S'assurer que les éléments sont suffisamment grands pour être facilement touchés
        if (element.offsetHeight < 44) {
            element.style.minHeight = '44px';
        }
        
        // Ajouter un peu d'espace entre les éléments
        element.style.marginBottom = '8px';
    });
    
    // Optimiser les icônes pour qu'elles soient plus faciles à toucher
    const icons = document.querySelectorAll('.fa, .fas, .far, .fab, .bi');
    icons.forEach(icon => {
        const parent = icon.parentElement;
        if (parent.tagName === 'A' || parent.tagName === 'BUTTON') {
            icon.style.fontSize = '1.25em';
        }
    });
}

/**
 * Optimise les tableaux pour une utilisation tactile
 */
function optimizeTablesForTouch() {
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        // Ajouter une classe pour les styles CSS
        table.classList.add('table-touch-optimized');
        
        // Augmenter l'espacement des cellules pour faciliter le toucher
        const cells = table.querySelectorAll('td, th');
        cells.forEach(cell => {
            cell.style.padding = '12px 16px';
        });
        
        // Rendre les lignes du tableau cliquables si elles contiennent un lien
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const link = row.querySelector('a');
            if (link) {
                row.style.cursor = 'pointer';
                row.addEventListener('click', function(e) {
                    // Ne pas déclencher si on a cliqué sur un bouton ou un lien
                    if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A' && 
                        !e.target.closest('button') && !e.target.closest('a')) {
                        link.click();
                    }
                });
            }
        });
    });
}

/**
 * Ajoute des effets de feedback tactile aux éléments interactifs
 */
function addTouchFeedback() {
    // Ajouter un effet de pression sur les éléments interactifs
    const interactiveElements = document.querySelectorAll('.btn, .card, .nav-link, .list-group-item, .dropdown-item');
    
    interactiveElements.forEach(element => {
        element.addEventListener('touchstart', function() {
            this.style.opacity = '0.8';
            this.style.transform = 'scale(0.98)';
        });
        
        element.addEventListener('touchend', function() {
            this.style.opacity = '1';
            this.style.transform = 'scale(1)';
        });
    });
}

/**
 * Optimise les formulaires pour une utilisation tactile
 */
function optimizeFormsForTouch() {
    // Optimiser les champs de formulaire
    const formFields = document.querySelectorAll('.form-control, .form-select');
    formFields.forEach(field => {
        // Augmenter la taille de la police pour éviter le zoom sur iOS
        field.style.fontSize = '16px';
        
        // Augmenter la taille des champs
        field.style.padding = '12px 16px';
        field.style.height = 'auto';
        
        // Ajouter un effet de focus amélioré
        field.addEventListener('focus', function() {
            this.parentElement.classList.add('touch-focus');
        });
        
        field.addEventListener('blur', function() {
            this.parentElement.classList.remove('touch-focus');
        });
    });
    
    // Optimiser les cases à cocher et boutons radio
    const checkboxes = document.querySelectorAll('.form-check-input');
    checkboxes.forEach(checkbox => {
        checkbox.style.width = '24px';
        checkbox.style.height = '24px';
    });
}

/**
 * Optimise le défilement pour une expérience tactile fluide
 */
function optimizeScrolling() {
    // Améliorer le défilement sur les conteneurs avec défilement
    const scrollContainers = document.querySelectorAll('.table-responsive, .modal-body, .card-body, .overflow-auto');
    scrollContainers.forEach(container => {
        container.style.webkitOverflowScrolling = 'touch';
        container.style.overscrollBehavior = 'contain';
    });
    
    // Ajouter un défilement fluide au document
    document.documentElement.style.scrollBehavior = 'smooth';
}

/**
 * Gère les changements d'orientation de l'écran
 */
function handleOrientation() {
    const isLandscape = window.innerWidth > window.innerHeight;
    
    if (isLandscape) {
        document.body.classList.add('landscape');
        document.body.classList.remove('portrait');
    } else {
        document.body.classList.add('portrait');
        document.body.classList.remove('landscape');
    }
    
    // Ajuster la mise en page en fonction de l'orientation
    adjustLayoutForOrientation(isLandscape);
}

/**
 * Ajuste la mise en page en fonction de l'orientation
 * @param {boolean} isLandscape - True si l'orientation est paysage
 */
function adjustLayoutForOrientation(isLandscape) {
    // Ajuster la barre latérale
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        if (isLandscape) {
            sidebar.style.width = '280px';
        } else {
            sidebar.style.width = '100%';
        }
    }
    
    // Ajuster les cartes
    const cardDecks = document.querySelectorAll('.card-deck, .row');
    cardDecks.forEach(deck => {
        if (isLandscape) {
            deck.style.display = 'flex';
        } else {
            deck.style.display = 'block';
        }
    });
}

/**
 * Fonction utilitaire pour limiter la fréquence d'exécution d'une fonction
 * @param {Function} func - La fonction à exécuter
 * @param {number} wait - Le délai d'attente en millisecondes
 * @returns {Function} - La fonction avec limite de fréquence
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}