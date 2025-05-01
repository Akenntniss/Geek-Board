/**
 * Script pour gérer les événements de clic sur les badges de statut des commandes
 * sur la page d'accueil
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les badges de statut dans le tableau des commandes
    const statusBadges = document.querySelectorAll('span.status-badge');
    
    // Ajouter un gestionnaire d'événement de clic à chaque badge
    statusBadges.forEach(badge => {
        badge.addEventListener('click', function() {
            // Récupérer l'ID de la commande et le statut actuel depuis les attributs data
            const commandeId = this.getAttribute('data-commande-id');
            const currentStatus = this.getAttribute('data-status');
            
            console.log(`Badge de statut cliqué: commande #${commandeId}, statut actuel: ${currentStatus}`);
            
            // Ouvrir le modal de statut
            openStatusModal(commandeId, currentStatus);
        });
    });
    
    console.log(`${statusBadges.length} badges de statut initialisés sur la page d'accueil`);
    
    // Configuration des boutons dans le modal
    setupStatusModal();
});

// Fonction pour ouvrir le modal de statut
function openStatusModal(commandeId, currentStatus) {
    const modal = document.getElementById('statusModal');
    if (!modal) {
        console.error("Modal de statut non trouvé");
        return;
    }
    
    // Mettre à jour les champs du modal
    const commandeIdInput = document.getElementById('commandeIdInput');
    const currentStatusInput = document.getElementById('currentStatusInput');
    const commandeIdText = document.getElementById('commandeIdText');
    
    if (commandeIdInput) commandeIdInput.value = commandeId;
    if (currentStatusInput) currentStatusInput.value = currentStatus;
    if (commandeIdText) commandeIdText.textContent = commandeId;
    
    // Afficher le statut actuel (maintenant géré via le badge "Annulé")
    // Note: le bouton d'annulation est toujours visible dans le nouveau design
    
    // Afficher le modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Déboguer le modal et ses éléments
    console.log("Modal ouvert pour commande:", { id: commandeId, status: currentStatus });
    console.log("Cartes de statut dans le modal:", document.querySelectorAll('.status-option-card').length);
}

// Fonction pour configurer le modal de statut
function setupStatusModal() {
    console.log("Configuration du modal de statut");
    
    // Attacher les événements aux éléments de statut (maintenant des boutons)
    document.querySelectorAll('.status-option-card').forEach(card => {
        console.log("Configuration de la carte:", card.getAttribute('data-status'));
        
        card.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const commandeId = document.getElementById('commandeIdInput').value;
            const newStatus = this.getAttribute('data-status');
            
            console.log("Clic sur la carte de statut:", { commande_id: commandeId, new_status: newStatus });
            
            if (!commandeId) {
                console.error("ID de commande manquant");
                return;
            }
            
            // Ajouter une classe de chargement à la carte cliquée
            this.classList.add('loading');
            
            // Trouver et animer l'icône
            const iconWrapper = this.querySelector('.status-icon-wrapper');
            if (iconWrapper) {
                iconWrapper.classList.add('loading');
            }
            
            // Débogage du chemin de l'URL
            const ajaxUrl = window.location.pathname.includes('index.php') ? 
                'ajax/update_commande_status.php' : 
                '../ajax/update_commande_status.php';
            
            console.log("Envoi de la requête AJAX à:", ajaxUrl);
            
            // Préparer les données pour l'API
            const jsonData = JSON.stringify({
                commande_id: commandeId,
                new_status: newStatus
            });
            
            console.log("Données envoyées:", jsonData);
            
            // Envoyer la requête au serveur avec le chemin corrigé et au format JSON
            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: jsonData
            })
            .then(response => {
                console.log("Réponse reçue:", response.status);
                if (!response.ok) {
                    throw new Error(`Erreur réseau: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Données reçues:", data);
                
                if (data.success) {
                    // Mise à jour réussie, fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
                    if (modal) {
                        console.log("Fermeture du modal");
                        modal.hide();
                    } else {
                        console.error("Instance du modal non trouvée");
                    }
                    
                    // Mettre à jour l'affichage du badge dans le tableau
                    updateStatusBadge(commandeId, newStatus);
                    
                    // Afficher une notification de succès
                    showNotification('Statut mis à jour avec succès', 'success');
                    
                    // Recharger la page pour refléter les changements
                    setTimeout(() => { 
                        console.log("Rechargement de la page");
                        window.location.reload(); 
                    }, 800);
                } else {
                    console.error("Erreur lors de la mise à jour du statut:", data.message);
                    showNotification(data.message || 'Erreur lors de la mise à jour du statut', 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de communication avec le serveur', 'danger');
            })
            .finally(() => {
                this.classList.remove('loading');
                if (iconWrapper) {
                    iconWrapper.classList.remove('loading');
                }
            });
        });
    });
    
    // Gérer le clic sur le bouton d'annulation en haut du modal
    const cancelButton = document.querySelector('#statusModal .btn-danger');
    if (cancelButton) {
        console.log("Configuration du bouton d'annulation");
        
        cancelButton.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const commandeId = document.getElementById('commandeIdInput').value;
            if (!commandeId) {
                console.error("ID de commande manquant");
                return;
            }
            
            // Mettre à jour le statut à "annulee"
            console.log("Annulation de la commande:", { commande_id: commandeId });
            
            // Ajouter une classe de chargement
            this.classList.add('loading');
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Annulation...';
            
            // Débogage du chemin de l'URL
            const ajaxUrl = window.location.pathname.includes('index.php') ? 
                'ajax/update_commande_status.php' : 
                '../ajax/update_commande_status.php';
            
            // Préparer les données pour l'API
            const jsonData = JSON.stringify({
                commande_id: commandeId,
                new_status: 'annulee'
            });
            
            // Envoyer la requête au serveur avec le chemin corrigé et au format JSON
            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: jsonData
            })
            .then(response => {
                console.log("Réponse reçue:", response.status);
                if (!response.ok) {
                    throw new Error(`Erreur réseau: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Données reçues:", data);
                
                if (data.success) {
                    // Mise à jour réussie, fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
                    if (modal) {
                        console.log("Fermeture du modal");
                        modal.hide();
                    }
                    
                    // Mettre à jour l'affichage du badge dans le tableau
                    updateStatusBadge(commandeId, 'annulee');
                    
                    // Afficher une notification de succès
                    showNotification('Commande annulée avec succès', 'success');
                    
                    // Recharger la page pour refléter les changements
                    setTimeout(() => { window.location.reload(); }, 800);
                } else {
                    console.error("Erreur lors de l'annulation:", data.message);
                    showNotification(data.message || "Erreur lors de l'annulation", 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de communication avec le serveur', 'danger');
            })
            .finally(() => {
                this.classList.remove('loading');
                this.innerHTML = originalText;
            });
        });
    }
}

// Fonction pour mettre à jour l'affichage du badge dans le tableau
function updateStatusBadge(commandeId, newStatus) {
    const badge = document.querySelector(`span.status-badge[data-commande-id="${commandeId}"]`);
    if (badge) {
        console.log("Mise à jour du badge dans le tableau:", { commande_id: commandeId, new_status: newStatus });
        
        // Mettre à jour les attributs et classes du badge
        badge.setAttribute('data-status', newStatus);
        badge.className = `status-badge status-badge-${getStatusColor(newStatus)}`;
        badge.textContent = getStatusLabel(newStatus);
    } else {
        console.warn("Badge non trouvé dans le tableau pour la commande:", commandeId);
    }
}

// Fonctions utilitaires pour les statuts
function getStatusClass(status) {
    switch(status) {
        case 'en_attente': return 'bg-warning text-dark';
        case 'commande': return 'bg-info text-white';
        case 'recue': return 'bg-success text-white';
        case 'annulee': return 'bg-danger text-white';
        case 'urgent': return 'bg-danger text-white';
        case 'utilise': return 'bg-primary text-white';
        case 'a_retourner': return 'bg-secondary text-white';
        default: return 'bg-secondary text-white';
    }
}

function getStatusColor(status) {
    switch(status) {
        case 'en_attente': return 'warning';
        case 'commande': return 'info';
        case 'recue': return 'success';
        case 'annulee': return 'danger';
        case 'urgent': return 'danger';
        case 'utilise': return 'primary';
        case 'a_retourner': return 'secondary';
        default: return 'secondary';
    }
}

function getStatusLabel(status) {
    switch(status) {
        case 'en_attente': return 'En attente';
        case 'commande': return 'Commandé';
        case 'recue': return 'Reçu';
        case 'annulee': return 'Annulé';
        case 'urgent': return 'URGENT';
        case 'utilise': return 'Utilisé';
        case 'a_retourner': return 'À retourner';
        default: return status;
    }
}

function getStatusIcon(status) {
    switch(status) {
        case 'en_attente': return 'clock';
        case 'commande': return 'shopping-cart';
        case 'recue': return 'box';
        case 'annulee': return 'times';
        case 'urgent': return 'exclamation-triangle';
        case 'utilise': return 'check-double';
        case 'a_retourner': return 'undo';
        default: return 'question-circle';
    }
}

// Fonction pour afficher une notification
function showNotification(message, type = 'info') {
    console.log("Affichage d'une notification:", { message, type });
    
    // Créer la notification
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Ajouter au container de notifications
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(toast);
    
    // Initialiser et afficher le toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 3000
    });
    
    bsToast.show();
}