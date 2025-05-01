/**
 * Modal Helper - Résout les problèmes d'interaction avec les modals superposés
 * 
 * Ce script permet de gérer correctement les modals Bootstrap quand ils sont imbriqués
 * ou ouverts les uns sur les autres, en s'assurant que:
 * 1. Les z-index sont correctement gérés
 * 2. Les événements de clic fonctionnent sur tous les modals
 * 3. Les backdrops n'interfèrent pas avec l'interaction
 */

const ModalHelper = {
    /**
     * Initialise le helper de modal
     */
    init() {
        console.log('ModalHelper initialisé');
        this.setupGlobalHandlers();
    },

    /**
     * Configure les gestionnaires d'événements globaux pour tous les modals
     */
    setupGlobalHandlers() {
        // Gérer l'ouverture d'un modal
        document.addEventListener('show.bs.modal', (event) => {
            this.handleModalShow(event);
        });

        // Gérer l'affichage complet d'un modal
        document.addEventListener('shown.bs.modal', (event) => {
            this.handleModalShown(event);
        });

        // Gérer la fermeture d'un modal
        document.addEventListener('hide.bs.modal', (event) => {
            this.handleModalHide(event);
        });
        
        // Gérer la fermeture complète d'un modal
        document.addEventListener('hidden.bs.modal', (event) => {
            this.handleModalHidden(event);
        });
    },

    /**
     * Gère l'événement d'ouverture d'un modal
     * @param {Event} event - L'événement de modal
     */
    handleModalShow(event) {
        const modal = event.target;
        
        // Compter le nombre de modals déjà ouverts
        const openModals = document.querySelectorAll('.modal.show');
        const modalIndex = openModals.length;
        
        // Calculer le z-index du modal
        const baseZIndex = 1050;
        const zIndex = baseZIndex + (modalIndex * 10);
        
        // Appliquer le z-index
        modal.style.zIndex = zIndex.toString();
        
        console.log(`Modal ${modal.id} en cours d'ouverture avec z-index: ${zIndex}`);
    },

    /**
     * Gère l'événement après qu'un modal est complètement affiché
     * @param {Event} event - L'événement de modal
     */
    handleModalShown(event) {
        const modal = event.target;
        
        // Assurer que tous les modals et leurs dialogues ont des événements pointer activés
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(m => {
            m.style.pointerEvents = 'auto';
            const dialog = m.querySelector('.modal-dialog');
            if (dialog) {
                dialog.style.pointerEvents = 'auto';
            }
        });
        
        // Désactiver les événements pointer sur tous les backdrops sauf pour les clics de fermeture
        this.fixBackdrops();
        
        console.log(`Modal ${modal.id} ouvert et prêt à interagir`);
    },

    /**
     * Gère l'événement de fermeture d'un modal
     * @param {Event} event - L'événement de modal
     */
    handleModalHide(event) {
        console.log(`Modal ${event.target.id} en cours de fermeture`);
    },

    /**
     * Gère l'événement après qu'un modal est complètement fermé
     * @param {Event} event - L'événement de modal
     */
    handleModalHidden(event) {
        // Réorganiser les z-index des modals restants
        this.reorderModals();
        
        // Corriger les backdrops
        this.fixBackdrops();
        
        console.log(`Modal ${event.target.id} fermé`);
    },

    /**
     * Réordonne les z-index des modals ouverts
     */
    reorderModals() {
        const openModals = document.querySelectorAll('.modal.show');
        const baseZIndex = 1050;
        
        openModals.forEach((modal, index) => {
            const zIndex = baseZIndex + (index * 10);
            modal.style.zIndex = zIndex.toString();
        });
    },

    /**
     * Corrige les backdrops pour s'assurer qu'ils n'interfèrent pas avec l'interaction
     */
    fixBackdrops() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        
        backdrops.forEach((backdrop, index) => {
            // Le backdrop doit bloquer les clics sur le contenu en dessous
            // mais ne doit pas empêcher les clics sur les modals au-dessus
            backdrop.style.pointerEvents = 'none';
            
            // Ajuster le z-index des backdrops
            const baseZIndex = 1040;
            const zIndex = baseZIndex + (index * 10);
            backdrop.style.zIndex = zIndex.toString();
        });
        
        // Trouver le dernier backdrop (qui correspond au modal actif)
        if (backdrops.length > 0) {
            const lastBackdrop = backdrops[backdrops.length - 1];
            // Permettre les clics sur le dernier backdrop pour fermer le modal si nécessaire
            lastBackdrop.style.pointerEvents = 'auto';
        }
    }
};

// Initialiser le helper de modal au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    ModalHelper.init();
}); 