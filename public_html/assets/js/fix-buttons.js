/**
 * Script de correction pour les boutons qui ne fonctionnent pas
 * - Bouton "+" flottant en bas de page
 * - Bouton hamburger du menu mobile
 */
document.addEventListener('DOMContentLoaded', function() {
    // Fixe pour le bouton "+" (nouvelleActionModal)
    const fixPlusButton = () => {
        const plusButton = document.querySelector('.btn-nouvelle-action');
        if (plusButton) {
            // Réinitialisez les gestionnaires d'événements existants
            const newPlusButton = plusButton.cloneNode(true);
            plusButton.parentNode.replaceChild(newPlusButton, plusButton);
            
            // Ajoutez un gestionnaire d'événements direct
            newPlusButton.addEventListener('click', function() {
                const modalId = this.getAttribute('data-bs-target');
                const modal = document.querySelector(modalId);
                if (modal && window.bootstrap) {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                }
            });
        }
    };

    // Fixe pour le bouton hamburger (menuPrincipalModal)
    const fixHamburgerButton = () => {
        const hamburgerButton = document.querySelector('a.dock-item[data-bs-target="#menuPrincipalModal"]');
        if (hamburgerButton) {
            // Réinitialisez les gestionnaires d'événements existants
            const newHamburgerButton = hamburgerButton.cloneNode(true);
            hamburgerButton.parentNode.replaceChild(newHamburgerButton, hamburgerButton);
            
            // Ajoutez un gestionnaire d'événements direct
            newHamburgerButton.addEventListener('click', function(e) {
                e.preventDefault();
                const modalId = this.getAttribute('data-bs-target');
                const modal = document.querySelector(modalId);
                if (modal && window.bootstrap) {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                }
            });
        }
    };

    // Vérifiez si Bootstrap est disponible
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap n\'est pas disponible. Tentative de chargement...');
        
        // En cas d'absence de Bootstrap, essayons de le charger
        const bootstrapScript = document.createElement('script');
        bootstrapScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js';
        bootstrapScript.onload = function() {
            console.log('Bootstrap chargé avec succès. Application des correctifs...');
            setTimeout(() => {
                fixPlusButton();
                fixHamburgerButton();
            }, 500);
        };
        document.head.appendChild(bootstrapScript);
    } else {
        // Bootstrap est disponible, application directe des correctifs
        setTimeout(() => {
            fixPlusButton();
            fixHamburgerButton();
        }, 500);
    }
}); 