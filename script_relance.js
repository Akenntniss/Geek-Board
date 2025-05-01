document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const relanceClientBtn = document.getElementById('relanceClientBtn');
    const relanceDelayDays = document.getElementById('relanceDelayDays');
    const previewResults = document.getElementById('previewResults');
    const previewResultsBody = document.getElementById('previewResultsBody');
    const noClientsMessage = document.getElementById('noClientsMessage');
    const previewRelanceBtn = document.getElementById('previewRelanceBtn');
    const sendRelanceBtn = document.getElementById('sendRelanceBtn');
    const selectAllClients = document.getElementById('selectAllClients');
    const btnCommandeRecu = document.getElementById('btnCommandeRecu');
    const btnReparationTerminee = document.getElementById('btnReparationTerminee');
    
    // Variable pour stocker le type de filtre actif
    let activeFilter = 'default'; // 'default', 'commande', 'reparation'
    
    // Initialiser le modal
    let relanceModal;
    if (relanceClientBtn) {
        relanceClientBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Réinitialiser les filtres
            activeFilter = 'default';
            document.getElementById('modalTitle').textContent = 'Relance des clients';
            document.getElementById('alertInfoText').textContent = 
                'Vous êtes sur le point d\'envoyer un SMS de relance aux clients dont les réparations sont terminées ou archivées mais pas encore récupérées.';
            
            // Réinitialiser l'apparence des boutons
            btnCommandeRecu.classList.remove('btn-primary');
            btnCommandeRecu.classList.add('btn-outline-primary');
            btnReparationTerminee.classList.remove('btn-success');
            btnReparationTerminee.classList.add('btn-outline-success');
            
            // Ouvrir le modal
            relanceModal = new bootstrap.Modal(document.getElementById('relanceClientModal'));
            relanceModal.show();
        });
    }
    
    // Écouter les changements sur le champ de jours
    if (relanceDelayDays) {
        relanceDelayDays.addEventListener('input', function() {
            // Réinitialiser l'aperçu
            previewResults.classList.add('d-none');
            sendRelanceBtn.disabled = true;
        });
    }
    
    // Gestionnaire pour le bouton "Commande Reçu"
    if (btnCommandeRecu) {
        btnCommandeRecu.addEventListener('click', function() {
            // Mettre à jour l'apparence des boutons
            btnCommandeRecu.classList.remove('btn-outline-primary');
            btnCommandeRecu.classList.add('btn-primary');
            btnReparationTerminee.classList.remove('btn-success');
            btnReparationTerminee.classList.add('btn-outline-success');
            
            // Mettre à jour le filtre actif
            activeFilter = 'commande';
            
            // Mettre à jour le titre et le message d'information
            document.getElementById('modalTitle').textContent = 'Relance des commandes reçues';
            document.getElementById('alertInfoText').textContent = 
                'Vous êtes sur le point d\'envoyer un SMS aux clients dont les commandes ont été reçues.';
            
            // Réinitialiser l'aperçu
            previewResults.classList.add('d-none');
            sendRelanceBtn.disabled = true;
        });
    }
    
    // Gestionnaire pour le bouton "Réparation Terminée"
    if (btnReparationTerminee) {
        btnReparationTerminee.addEventListener('click', function() {
            // Mettre à jour l'apparence des boutons
            btnReparationTerminee.classList.remove('btn-outline-success');
            btnReparationTerminee.classList.add('btn-success');
            btnCommandeRecu.classList.remove('btn-primary');
            btnCommandeRecu.classList.add('btn-outline-primary');
            
            // Mettre à jour le filtre actif
            activeFilter = 'reparation';
            
            // Mettre à jour le titre et le message d'information
            document.getElementById('modalTitle').textContent = 'Relance des réparations terminées';
            document.getElementById('alertInfoText').textContent = 
                'Vous êtes sur le point d\'envoyer un SMS aux clients dont les réparations sont terminées ou annulées.';
            
            // Réinitialiser l'aperçu
            previewResults.classList.add('d-none');
            sendRelanceBtn.disabled = true;
        });
    }
    
    // Action du bouton d'aperçu
    if (previewRelanceBtn) {
        previewRelanceBtn.addEventListener('click', function() {
            // Récupérer les valeurs
            const days = parseInt(relanceDelayDays.value) || 3;
            
            // Appeler l'API pour obtenir un aperçu avec le filtre actif
            getPreviewRelance(days, activeFilter);
        });
    }
    
    // Action du bouton d'envoi
    if (sendRelanceBtn) {
        sendRelanceBtn.addEventListener('click', function() {
            // Demander confirmation
            if (!confirm('ATTENTION: Vous êtes sur le point d\'envoyer réellement des SMS de relance aux clients. Continuer?')) {
                return;
            }
            
            // Récupérer les valeurs
            const days = parseInt(relanceDelayDays.value) || 3;
            
            // Appeler l'API pour envoyer les relances
            sendRelanceSMS(days);
        });
    }
    
    // Gestionnaire pour la case à cocher "Sélectionner tous"
    if (selectAllClients) {
        selectAllClients.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.client-select').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
        
        // Ajouter un écouteur d'événements pour les clics sur les cases individuelles
        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('client-select')) {
                // Vérifier si toutes les cases sont cochées
                const allCheckboxes = document.querySelectorAll('.client-select');
                const allChecked = [...allCheckboxes].every(checkbox => checkbox.checked);
                
                // Mettre à jour la case "Sélectionner tous"
                selectAllClients.checked = allChecked;
            }
        });
    }
    
    // Fonction pour obtenir un aperçu des relances
    function getPreviewRelance(days, filterType = 'default') {
        // Afficher un indicateur de chargement
        previewResultsBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <span class="ms-2">Recherche des clients à relancer...</span>
                </td>
            </tr>
        `;
        previewResults.classList.remove('d-none');
        noClientsMessage.classList.add('d-none');
        
        // Appeler l'API
        fetch('ajax/client_relance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'preview',
                days: days,
                filterType: filterType
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'aperçu
                if (data.clients && data.clients.length > 0) {
                    previewResultsBody.innerHTML = '';
                    
                    // Ajouter chaque client à la liste
                    data.clients.forEach(client => {
                        // Déterminer le statut et sa couleur
                        let statusText = "Inconnu";
                        let statusClass = "secondary";
                        
                        if (client.statut_id == 9) {
                            statusText = "Terminé";
                            statusClass = "success";
                        } else if (client.statut_id == 10) {
                            statusText = "Prêt à récupérer";
                            statusClass = "info";
                        } else if (client.statut_id == 11) {
                            statusText = "Archivé";
                            statusClass = "dark";
                        }
                        
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="text-center">
                                <div class="form-check">
                                    <input class="form-check-input client-select" type="checkbox" checked data-client-id="${client.id}">
                                </div>
                            </td>
                            <td>${client.client_nom} ${client.client_prenom}</td>
                            <td>${client.type_appareil} ${client.modele}</td>
                            <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                            <td>${client.days_since} jours</td>
                        `;
                        previewResultsBody.appendChild(row);
                    });
                    
                    // Activer le bouton d'envoi
                    sendRelanceBtn.disabled = false;
                } else {
                    // Aucun client à relancer
                    noClientsMessage.classList.remove('d-none');
                    previewResultsBody.innerHTML = '';
                    sendRelanceBtn.disabled = true;
                }
            } else {
                // Afficher l'erreur
                previewResultsBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-3 text-danger">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            ${data.message || 'Une erreur est survenue lors de la recherche des clients.'}
                        </td>
                    </tr>
                `;
                sendRelanceBtn.disabled = true;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            previewResultsBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3 text-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Une erreur est survenue lors de la communication avec le serveur.
                    </td>
                </tr>
            `;
            sendRelanceBtn.disabled = true;
        });
    }
    
    // Fonction pour envoyer les SMS de relance
    function sendRelanceSMS(days) {
        // Désactiver le bouton et afficher un indicateur de chargement
        sendRelanceBtn.disabled = true;
        sendRelanceBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi en cours...';
        
        // Récupérer les IDs des clients sélectionnés
        const selectedClientIds = [];
        document.querySelectorAll('.client-select:checked').forEach(checkbox => {
            selectedClientIds.push(checkbox.getAttribute('data-client-id'));
        });
        
        // Si aucun client n'est sélectionné, afficher une alerte
        if (selectedClientIds.length === 0) {
            alert('Aucun client sélectionné. Veuillez sélectionner au moins un client.');
            sendRelanceBtn.disabled = false;
            sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
            return;
        }
        
        // Appeler l'API
        fetch('ajax/client_relance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'send',
                days: days,
                clientIds: selectedClientIds,
                filterType: activeFilter
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer le modal
                relanceModal.hide();
                
                // Afficher un message de succès
                alert(`${data.count} SMS de relance envoyés avec succès.`);
                
                // Recharger la page
                window.location.reload();
            } else {
                // Afficher l'erreur
                alert('Erreur: ' + (data.message || 'Une erreur est survenue lors de l\'envoi des SMS.'));
                sendRelanceBtn.disabled = false;
                sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
            sendRelanceBtn.disabled = false;
            sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
        });
    }
}); 