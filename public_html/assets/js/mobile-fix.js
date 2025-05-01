/**
 * Correctifs spécifiques pour l'interface mobile
 * Résout les problèmes d'interactivité sur les appareils mobiles
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation des correctifs mobiles...');
    
    // Détection de l'appareil mobile
    const isMobile = window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    if (isMobile) {
        console.log('Appareil mobile détecté, application des correctifs...');
        
        // Correctif pour le bouton "Nouvelle commande" sur la page d'accueil
        fixNewOrderButton();
        
        // Amélioration du tactile pour tous les boutons d'action
        enhanceTouchResponsiveness();
        
        // Corriger les problèmes de modaux sur iOS
        fixiOSModals();
    }
    
    // Fonction pour corriger le bouton "Nouvelle commande"
    function fixNewOrderButton() {
        console.log('Application du correctif pour le bouton Nouvelle commande...');
        
        // Sélectionner tous les boutons qui ouvrent le modal de nouvelle commande
        const newOrderButtons = document.querySelectorAll('.action-card[data-bs-target="#ajouterCommandeModal"], [data-bs-target="#ajouterCommandeModal"]');
        
        if (newOrderButtons.length === 0) {
            console.warn('Aucun bouton "Nouvelle commande" trouvé');
            return;
        }
        
        console.log(`${newOrderButtons.length} boutons "Nouvelle commande" trouvés`);
        
        newOrderButtons.forEach((button, index) => {
            console.log(`Traitement du bouton ${index + 1}...`);
            
            // Ajouter une classe pour l'identification
            button.classList.add('mobile-fixed-order-button');
            
            // Supprimer tous les gestionnaires d'événements existants
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Ajouter des gestionnaires d'événements touch spécifiques
            newButton.addEventListener('touchstart', function(e) {
                // Empêcher la propagation des événements tactiles
                e.preventDefault();
                e.stopPropagation();
                
                // Ajouter une classe visible pour le feedback
                this.classList.add('touch-active');
                
                // Vibration tactile si disponible
                if ('vibrate' in navigator) {
                    navigator.vibrate(50);
                }
                
                console.log('Toucher détecté sur le bouton "Nouvelle commande"');
            }, { passive: false });
            
            newButton.addEventListener('touchend', function(e) {
                // Empêcher la propagation
                e.preventDefault();
                e.stopPropagation();
                
                // Retirer la classe de feedback
                this.classList.remove('touch-active');
                
                console.log('Ouverture forcée du modal "ajouterCommandeModal"');
                
                // Forcer l'ouverture du modal
                openModalForcefully('ajouterCommandeModal');
            }, { passive: false });
            
            // Ajouter également un gestionnaire de clic normal
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Clic sur le bouton "Nouvelle commande"');
                
                // Forcer l'ouverture du modal
                openModalForcefully('ajouterCommandeModal');
            });
        });
    }
    
    // Fonction pour forcer l'ouverture d'un modal
    function openModalForcefully(modalId) {
        const modalElement = document.getElementById(modalId);
        
        if (!modalElement) {
            console.error(`Modal #${modalId} non trouvé.`);
            return;
        }
        
        try {
            // Première tentative : utiliser l'API Bootstrap
            if (typeof bootstrap !== 'undefined') {
                let modalInstance = bootstrap.Modal.getInstance(modalElement);
                
                if (!modalInstance) {
                    modalInstance = new bootstrap.Modal(modalElement);
                }
                
                modalInstance.show();
                console.log(`Modal #${modalId} ouvert avec Bootstrap`);
            } else {
                throw new Error('Bootstrap non disponible');
            }
        } catch (error) {
            console.warn('Échec de l\'ouverture avec Bootstrap:', error);
            
            // Méthode manuelle en fallback
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            modalElement.setAttribute('aria-modal', 'true');
            modalElement.removeAttribute('aria-hidden');
            
            // Ajouter un backdrop
            let backdrop = document.querySelector('.modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.classList.add('modal-backdrop', 'fade', 'show');
                document.body.appendChild(backdrop);
            }
            
            // Empêcher le défilement du body
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            
            console.log(`Modal #${modalId} ouvert manuellement`);
        }
    }
    
    // Améliorer la réactivité tactile des boutons
    function enhanceTouchResponsiveness() {
        // Sélectionner tous les boutons d'action
        const actionButtons = document.querySelectorAll('.action-card, .btn');
        
        actionButtons.forEach(button => {
            // Ajouter des styles pour un feedback visuel immédiat
            button.style.webkitTapHighlightColor = 'rgba(0,0,0,0)';
            button.style.touchAction = 'manipulation';
            
            // Réduire le délai de clic sur iOS
            button.addEventListener('touchstart', function() {}, { passive: true });
        });
    }
    
    // Correction des problèmes de modaux sur iOS
    function fixiOSModals() {
        // Détection d'iOS
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        
        if (!isIOS) return;
        
        console.log('Appareil iOS détecté, application des correctifs spécifiques...');
        
        // Correctif pour le problème de scrolling dans les modaux iOS
        const modals = document.querySelectorAll('.modal');
        
        modals.forEach(modal => {
            // Empêcher le scrolling du body quand le modal est ouvert
            modal.addEventListener('shown.bs.modal', function() {
                document.body.style.position = 'fixed';
                document.body.style.width = '100%';
            });
            
            // Restaurer le scrolling quand le modal est fermé
            modal.addEventListener('hidden.bs.modal', function() {
                document.body.style.position = '';
                document.body.style.width = '';
            });
        });
    }
});

// Styles pour améliorer le feedback tactile
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter des styles pour le feedback tactile
    const style = document.createElement('style');
    style.textContent = `
        .touch-active {
            transform: scale(0.95) !important;
            opacity: 0.8 !important;
            transition: transform 0.1s, opacity 0.1s !important;
        }
        
        .mobile-fixed-order-button {
            position: relative;
            z-index: 1000;
        }
        
        /* Améliorer la taille des éléments tactiles sur mobile */
        @media (max-width: 768px) {
            .action-card, .btn {
                min-height: 44px; /* Recommandation Apple pour la taille minimale */
            }
            
            .modal-backdrop {
                opacity: 0.7 !important; /* Améliorer le contraste du backdrop sur mobile */
            }
        }
    `;
    document.head.appendChild(style);
});

// Ajouter un gestionnaire pour le changement d'orientation
window.addEventListener('resize', function() {
    if (window.innerWidth <= 768) {
        console.log('Réapplication des correctifs après changement d\'orientation...');
        // Réinitialiser pour les nouveaux éléments ou après rotation
        setTimeout(function() {
            const event = new Event('DOMContentLoaded');
            document.dispatchEvent(event);
        }, 300);
    }
}); 