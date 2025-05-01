/**
 * Script pour gérer la transition entre les modaux
 * Permet d'ouvrir un modal depuis un autre modal
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation des transitions entre modaux...');
    
    // Gérer la transition du modal nouvelles_actions_modal vers ajouterCommandeModal
    const btnAjouterCommande = document.querySelector('a[data-bs-target="#ajouterCommandeModal"][data-bs-dismiss="modal"]');
    
    if (btnAjouterCommande) {
        console.log('Bouton Ajouter Commande trouvé dans le modal d\'actions');
        
        btnAjouterCommande.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Référence au modal d'actions
            const actionsModal = bootstrap.Modal.getInstance(document.getElementById('nouvelles_actions_modal'));
            
            // Fermer d'abord le modal d'actions
            if (actionsModal) {
                actionsModal.hide();
                
                // Attendre que le modal soit complètement fermé avant d'ouvrir le nouveau
                setTimeout(function() {
                    // Nettoyer tous les backdrops résiduels
                    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    
                    // Ouvrir le modal de commande
                    const commandeModal = new bootstrap.Modal(document.getElementById('ajouterCommandeModal'));
                    if (commandeModal) {
                        commandeModal.show();
                    } else {
                        console.error('Modal de commande non trouvé!');
                    }
                }, 300);
            } else {
                // Si on ne peut pas accéder à l'instance du modal, ouvrir directement le nouveau
                const commandeModal = new bootstrap.Modal(document.getElementById('ajouterCommandeModal'));
                if (commandeModal) {
                    commandeModal.show();
                }
            }
        });
        
        console.log('Écouteur d\'événement ajouté au bouton Ajouter Commande');
    } else {
        console.error('Bouton Ajouter Commande non trouvé dans le modal d\'actions');
    }
    
    console.log('Initialisation des transitions entre modaux terminée');
}); 