/**
 * Module de gestion des attributions de réparations
 * Ce script gère l'attribution des réparations aux employés
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les boutons de statut rapide
    initStartRepairButtons();
});

/**
 * Initialise les boutons d'accès au statut rapide
 */
function initStartRepairButtons() {
    // Sélectionner tous les boutons de démarrage de réparation
    document.querySelectorAll('.start-repair').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const repairId = this.getAttribute('data-id');
            if (!repairId) {
                console.error('Attribut data-id manquant sur le bouton');
                return;
            }
            
            // Rediriger vers la page de statut rapide avec l'ID
            window.location.href = `index.php?page=statut_rapide&id=${repairId}`;
        });
    });
} 