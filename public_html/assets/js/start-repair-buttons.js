/**
 * Gestion des boutons "démarrer" dans la liste des réparations
 */
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les boutons de démarrage
    document.querySelectorAll('.start-repair').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const repairId = this.getAttribute('data-id');
            if (!repairId) {
                console.error('Attribut data-id manquant sur le bouton démarrer');
                return;
            }
            
            // Rediriger vers la page de statut rapide avec l'ID
            window.location.href = `index.php?page=statut_rapide&id=${repairId}`;
        });
    });

    console.log('Initialisation des boutons de démarrage terminée');
}); 