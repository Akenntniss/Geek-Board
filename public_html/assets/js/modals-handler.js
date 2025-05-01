/**
 * Script pour gérer les modaux de l'application
 * Ce script initialise les modaux de Bootstrap et gère les interactions
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation des modaux... (script modals-handler.js chargé)');
    
    // Récupérer tous les modaux
    const nouvelles_actions_modal = document.getElementById('nouvelles_actions_modal');
    const menu_navigation_modal = document.getElementById('menu_navigation_modal');
    
    // Vérifier si les modaux existent
    if (nouvelles_actions_modal) {
        console.log('Modal trouvé: nouvelles_actions_modal');
    } else {
        console.error('Modal NON trouvé: nouvelles_actions_modal');
    }
    
    if (menu_navigation_modal) {
        console.log('Modal trouvé: menu_navigation_modal');
    } else {
        console.error('Modal NON trouvé: menu_navigation_modal');
    }
    
    // Liste des modaux à initialiser
    const modals = [nouvelles_actions_modal, menu_navigation_modal];
    
    // Initialiser les modaux Bootstrap (si nécessaire)
    // Note: Avec Bootstrap 5, les modaux sont généralement auto-initialisés
    modals.forEach(modal => {
        if (modal) {
            // Écouter l'événement d'ouverture du modal
            modal.addEventListener('shown.bs.modal', function() {
                console.log('Modal ouvert:', modal.id);
            });
            
            // Écouter l'événement de fermeture du modal
            modal.addEventListener('hidden.bs.modal', function() {
                console.log('Modal fermé:', modal.id);
            });
        }
    });
    
    // Gestionnaire de clic pour le bouton "+"
    const btnNouvelle = document.querySelector('.btn-nouvelle-action');
    if (btnNouvelle) {
        console.log('Bouton + trouvé');
        btnNouvelle.addEventListener('click', function() {
            console.log('Bouton + cliqué');
        });
    } else {
        console.error('Bouton + NON trouvé');
    }
    
    // Gestionnaire de clic pour le bouton "Menu"
    const btnMenu = document.querySelector('.dock-item[data-bs-target="#menu_navigation_modal"]');
    if (btnMenu) {
        console.log('Bouton Menu trouvé');
        btnMenu.addEventListener('click', function() {
            console.log('Bouton Menu cliqué');
        });
    } else {
        console.error('Bouton Menu NON trouvé');
    }
    
    // Fonction pour forcer l'affichage des modaux (en cas de problème)
    window.forceShowModal = function(modalId) {
        console.log('Tentative de forcer l\'affichage du modal:', modalId);
        const modalElement = document.getElementById(modalId);
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            console.log('Modal forcé avec succès:', modalId);
            return true;
        }
        console.error('Modal non trouvé pour forceShowModal:', modalId);
        return false;
    };
    
    console.log('Initialisation des modaux terminée');
}); 