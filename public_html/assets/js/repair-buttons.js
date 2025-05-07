/**
 * Gestion des boutons de démarrage de réparation
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('Chargement du module repair-buttons.js');
    
    // Vérification des boutons démarrer
    const startButtons = document.querySelectorAll('.start-repair');
    console.log('Nombre de boutons "démarrer" trouvés:', startButtons.length);
    
    if (startButtons.length === 0) {
        console.error('ERREUR: Aucun bouton avec la classe .start-repair n\'a été trouvé!');
    } else {
        startButtons.forEach((btn, index) => {
            console.log(`Bouton #${index} - ID de réparation:`, btn.getAttribute('data-id'));
        });
    }

    // Ajouter des écouteurs d'événements à chaque bouton
    startButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Récupérer l'ID de la réparation depuis l'attribut data-id
            const repairId = this.getAttribute('data-id');
            console.log('Démarrage de réparation demandé:', repairId);
            
            // Vérifier d'abord si l'utilisateur a déjà une réparation active
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
            .then(response => {
                console.log('Réponse reçue:', response);
                if (!response.ok) {
                    throw new Error('Erreur réseau: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Données reçues:', data);
                if (data.success) {
                    if (data.has_active_repair) {
                        // L'utilisateur a déjà une réparation active, afficher le modal
                        const activeRepair = data.active_repair;
                        document.getElementById('activeRepairId').textContent = `#${activeRepair.id}`;
                        document.getElementById('activeRepairDevice').textContent = `${activeRepair.type_appareil} ${activeRepair.modele}`;
                        document.getElementById('activeRepairClient').textContent = `${activeRepair.client_nom} ${activeRepair.client_prenom}`;
                        document.getElementById('activeRepairStatus').textContent = activeRepair.statut_nom || activeRepair.statut;
                        
                        // Afficher le modal
                        const activeRepairModal = new bootstrap.Modal(document.getElementById('activeRepairModal'));
                        activeRepairModal.show();
                    } else {
                        // L'utilisateur n'a pas de réparation active, attribuer la réparation
                        assignRepair(repairId);
                    }
                } else {
                    alert(data.message || 'Une erreur est survenue lors de la vérification des réparations actives.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur: ' + error.message);
            });
        });
    });
    
    // Ajouter des écouteurs aux boutons de statut dans le modal de réparation active
    const completeButtons = document.querySelectorAll(".complete-btn");
    completeButtons.forEach(button => {
        button.addEventListener("click", function() {
            const status = this.getAttribute("data-status");
            const repairId = document.getElementById('activeRepairId').textContent.replace('#', '');
            completeActiveRepair(repairId, status);
        });
    });
    
    // Fonction pour assigner une réparation
    function assignRepair(repairId) {
        console.log('Début de l\'attribution de la réparation:', repairId);
        
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'assign_repair',
                reparation_id: repairId
            }),
        })
        .then(response => {
            console.log('Réponse assignRepair reçue:', response);
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Données assignRepair reçues:', data);
            
            if (data.success) {
                // Rafraîchir la page avec les réparations en cours
                window.location.href = `index.php?page=reparations&statut_ids=4,5`;
            } else {
                // Afficher une alerte en cas d'erreur
                alert(data.message || 'Une erreur est survenue lors de l\'attribution de la réparation.');
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'attribution:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur: ' + error.message);
        });
    }
    
    // Fonction pour terminer la réparation active
    function completeActiveRepair(repairId, finalStatus) {
        // Vérifier si nous avons un statut
        if (!finalStatus) {
            alert('Veuillez sélectionner un statut final');
            return;
        }
        
        console.log(`Complétion de la réparation ${repairId} avec le statut ${finalStatus}`);
        
        // Si le statut est "en_attente_accord_client", ouvrir le modal d'envoi de devis
        if (finalStatus === 'en_attente_accord_client' || finalStatus === 'nouvelle_commande') {
            // Fermer le modal actif
            const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
            activeRepairModal.hide();
        }
        
        // Changer le statut de la réparation
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'complete_active_repair',
                reparation_id: repairId,
                final_status: finalStatus
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès
                let successMessage = 'Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.';
                
                // Ajouter le statut d'envoi du SMS au message si disponible
                if (data.hasOwnProperty('sms_sent')) {
                    if (data.sms_sent) {
                        successMessage += '\nSMS envoyé au client avec succès.';
                    } else {
                        successMessage += '\nAucun SMS envoyé. ' + (data.sms_message || '');
                    }
                }
                
                alert(successMessage);
                
                // Actions supplémentaires selon le statut
                if (finalStatus === 'en_attente_accord_client') {
                    // Ouvrir le modal d'envoi de devis si disponible
                    if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                        window.RepairModal.executeAction('devis', repairId);
                    } else {
                        // Recharger la page après un court délai
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else if (finalStatus === 'nouvelle_commande') {
                    // Ouvrir le modal de commande si disponible
                    if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                        window.RepairModal.executeAction('order', repairId);
                    } else {
                        // Recharger la page après un court délai
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    // Recharger la page après un court délai
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                alert(data.message || 'Une erreur est survenue lors de la mise à jour du statut.');
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
            window.location.reload();
        });
    }
}); 