/**
 * Script pour corriger le problème de drag & drop des boutons de filtres
 */
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les boutons de filtres droppables
    const dropZones = document.querySelectorAll('.filter-btn.droppable');
    
    // Ajouter les écouteurs d'événements pour empêcher leur drag & drop
    dropZones.forEach(zone => {
        // Désactiver la propriété draggable
        zone.setAttribute('draggable', 'false');
        
        // Empêcher le début du drag
        zone.addEventListener('dragstart', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
        
        // Empêcher le comportement de drag par défaut lors du clic
        zone.addEventListener('mousedown', function(e) {
            const isLink = e.target.tagName === 'A' || e.target.closest('a');
            if (isLink) {
                e.target.draggable = false;
                if (e.target.closest('a')) {
                    e.target.closest('a').draggable = false;
                }
            }
        });
    });
}); 