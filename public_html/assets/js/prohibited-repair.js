// Fonction pour vérifier si une réparation est déjà attribuée à un autre technicien
function initializeStartRepairButtons() {
    // Obtenir tous les boutons de démarrage
    const allStartButtons = document.querySelectorAll('.start-repair');
    
    // Pour chaque bouton, vérifier si la réparation est attribuée
    allStartButtons.forEach(button => {
        const repairId = button.getAttribute('data-id');
        
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'check_active_repair',
                reparation_id: repairId
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.already_assigned) {
                // La réparation est déjà attribuée, remplacer le bouton par un bouton sens interdit
                const techName = `${data.technician.prenom} ${data.technician.nom}`;
                
                // Créer un nouveau bouton désactivé avec l'icône sens interdit
                const prohibitedButton = document.createElement('button');
                prohibitedButton.className = 'btn btn-sm btn-secondary rounded-pill disabled';
                prohibitedButton.title = `Attribuée à ${techName}`;
                prohibitedButton.innerHTML = '<i class="fas fa-ban"></i>';
                prohibitedButton.style.opacity = '0.7';
                prohibitedButton.style.cursor = 'not-allowed';
                
                // Ajouter un gestionnaire pour afficher un message d'information
                prohibitedButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    alert(`Cette réparation est déjà attribuée à ${techName}`);
                });
                
                // Remplacer le bouton original
                button.parentNode.replaceChild(prohibitedButton, button);
            }
        })
        .catch(error => {
            console.error("Erreur lors de la vérification de l'attribution:", error);
        });
    });
}

// Appeler la fonction d'initialisation des boutons quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    initializeStartRepairButtons();
}); 