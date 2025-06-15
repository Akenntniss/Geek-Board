function editCommande(id) {
    console.log('Édition de la commande:', id);
    
    // Afficher le modal de chargement
    showLoadingModal();
    
    // Récupérer les informations de la commande
    fetch(`/ajax/get_commande.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            hideLoadingModal();
            
            if (data.success) {
                // Remplir le formulaire avec les données
                document.getElementById('commande_id').value = data.commande.id;
                document.getElementById('fournisseur_id').value = data.commande.fournisseur_id || '';
                document.getElementById('client_id').value = data.commande.client_id || '';
                document.getElementById('date_commande').value = data.commande.date_commande || '';
                document.getElementById('date_reception').value = data.commande.date_reception || '';
                document.getElementById('statut').value = data.commande.statut || '';
                document.getElementById('notes').value = data.commande.notes || '';
                
                // Afficher le modal
                const modal = new bootstrap.Modal(document.getElementById('commandeModal'));
                modal.show();
            } else {
                // Vérifier si une redirection est demandée
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    showError(data.message || 'Erreur lors de la récupération des informations de la commande');
                }
            }
        })
        .catch(error => {
            hideLoadingModal();
            console.error('Erreur serveur:', error);
            showError('Erreur de communication avec le serveur');
        });
} 