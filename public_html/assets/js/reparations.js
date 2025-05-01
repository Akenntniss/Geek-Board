document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour les boutons de filtre
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Récupérer les IDs de statut depuis l'attribut data-statut-ids
            const statutIds = this.getAttribute('data-statut-ids');
            
            // Mettre à jour l'état actif des boutons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Afficher un indicateur de chargement
            const tableBody = document.querySelector('.table tbody');
            tableBody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </td>
                </tr>
            `;
            
            // Construire l'URL de la requête AJAX
            let url = 'ajax/get_reparations.php';
            if (statutIds) {
                url += `?statut_ids=${statutIds}`;
            }
            
            // Récupérer les données via AJAX
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mettre à jour le tableau
                        tableBody.innerHTML = data.html;
                        
                        // Mettre à jour les compteurs dans les boutons
                        Object.entries(data.counts).forEach(([ids, count]) => {
                            const btn = document.querySelector(`.filter-btn[data-statut-ids="${ids}"]`);
                            if (btn) {
                                const countElement = btn.querySelector('.count');
                                if (countElement) {
                                    countElement.textContent = count;
                                }
                            }
                        });
                        
                        // Réinitialiser les gestionnaires d'événements pour les boutons d'action
                        initializeActionButtons();
                    } else {
                        throw new Error(data.error || 'Une erreur est survenue');
                    }
                })
                .catch(error => {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    ${error.message}
                                </div>
                            </td>
                        </tr>
                    `;
                });
        });
    });
    
    // Fonction pour initialiser les gestionnaires d'événements des boutons d'action
    function initializeActionButtons() {
        // Gestionnaire pour le bouton de détails
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                window.location.href = `index.php?page=reparation_details&id=${id}`;
            });
        });
        
        // Gestionnaire pour le bouton de suppression
        document.querySelectorAll('.delete-repair').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                if (confirm('Êtes-vous sûr de vouloir supprimer cette réparation ?')) {
                    window.location.href = `index.php?page=reparations&action=delete&id=${id}`;
                }
            });
        });
    }
    
    // Initialiser les boutons d'action au chargement de la page
    initializeActionButtons();
}); 