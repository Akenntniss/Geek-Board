/**
 * Force l'ouverture des modales qui ne fonctionnent pas
 * Solution d'urgence pour les modales qui ne s'ouvrent pas avec les méthodes standard
 */
document.addEventListener('DOMContentLoaded', function() {
    // Force l'initialisation de Bootstrap avec un délai
    setTimeout(() => {
        // Fonction d'ouverture forcée d'une modale
        const forceOpenModal = (modalId) => {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.error(`Modal #${modalId} non trouvé!`);
                return;
            }
            
            // Ajouter les classes nécessaires pour afficher la modale
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            modalElement.setAttribute('aria-modal', 'true');
            modalElement.setAttribute('role', 'dialog');
            modalElement.removeAttribute('aria-hidden');
            
            // Ajouter le backdrop
            const backdrop = document.createElement('div');
            backdrop.classList.add('modal-backdrop', 'fade', 'show');
            document.body.appendChild(backdrop);
            
            // Empêcher le défilement du body
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = '15px';
            
            // Ajouter un gestionnaire pour fermer la modale
            const closeModal = () => {
                modalElement.classList.remove('show');
                modalElement.style.display = 'none';
                modalElement.setAttribute('aria-hidden', 'true');
                modalElement.removeAttribute('aria-modal');
                modalElement.removeAttribute('role');
                
                // Supprimer le backdrop
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(el => el.remove());
                
                // Restaurer le défilement
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            };
            
            // Fermer la modale en cliquant sur les boutons de fermeture
            const closeButtons = modalElement.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
            closeButtons.forEach(button => {
                button.addEventListener('click', closeModal);
            });
            
            // Fermer la modale en cliquant sur le backdrop
            backdrop.addEventListener('click', closeModal);
            
            // Fermer la modale avec la touche Escape
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        };
        
        // Réparer le bouton "+" (nouvelle action)
        const fixPlusButton = () => {
            const plusButton = document.querySelector('.btn-nouvelle-action');
            if (plusButton) {
                const newPlusButton = plusButton.cloneNode(true);
                plusButton.parentNode.replaceChild(newPlusButton, plusButton);
                
                newPlusButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    forceOpenModal('nouvelles_actions_modal');
                });
                console.log('Bouton "+" réparé avec ouverture forcée');
            }
        };
        
        // Réparer le bouton hamburger (menu principal)
        const fixHamburgerButton = () => {
            const hamburgerButton = document.querySelector('a.dock-item[data-bs-target="#menu_navigation_modal"]');
            if (hamburgerButton) {
                const newHamburgerButton = hamburgerButton.cloneNode(true);
                hamburgerButton.parentNode.replaceChild(newHamburgerButton, hamburgerButton);
                
                newHamburgerButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    forceOpenModal('menu_navigation_modal');
                });
                console.log('Bouton hamburger réparé avec ouverture forcée');
            }
        };
        
        // Exécuter les réparations
        fixPlusButton();
        fixHamburgerButton();
        
    }, 1000);
}); 