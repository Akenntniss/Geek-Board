// Fonction pour sauvegarder la commande
function saveCommande() {
    console.log("Début de la sauvegarde de la commande");
    
    const modal = document.getElementById('ajouterCommandeModal');
    if (!modal) {
        console.error("Erreur: Le modal 'ajouterCommandeModal' n'a pas été trouvé");
        return;
    }
    
    const form = modal.querySelector('form');
    
    // Vérifier que le formulaire existe
    if (!form) {
        console.error("Erreur: Le formulaire n'a pas été trouvé dans le modal");
        alert("Erreur: formulaire non trouvé");
        return;
    }
    
    console.log("Formulaire trouvé:", form);
    
    // Récupérer les éléments du formulaire
    const clientIdInput = modal.querySelector('input[name="client_id"]');
    const fournisseurIdSelect = modal.querySelector('#fournisseur_id_ajout');
    const nomPieceInput = modal.querySelector('#nom_piece_input');
    const quantiteInput = modal.querySelector('input[name="quantite"]');
    const prixEstimeInput = modal.querySelector('input[name="prix_estime"]');
    const codeBarreInput = modal.querySelector('input[name="code_barre"]');
    const statutInput = modal.querySelector('input[name="statut"]');
    const reparationIdInput = modal.querySelector('input[name="reparation_id"]');
    
    console.log("Éléments du formulaire trouvés:", {
        clientIdInput: !!clientIdInput,
        fournisseurIdSelect: !!fournisseurIdSelect,
        nomPieceInput: !!nomPieceInput,
        quantiteInput: !!quantiteInput,
        prixEstimeInput: !!prixEstimeInput,
        codeBarreInput: !!codeBarreInput,
        statutInput: !!statutInput,
        reparationIdInput: !!reparationIdInput
    });
    
    // Vérifier que tous les éléments existent
    if (!clientIdInput || !fournisseurIdSelect || !nomPieceInput || !quantiteInput || !prixEstimeInput) {
        console.error("Erreur: Certains champs du formulaire sont manquants");
        alert("Erreur: formulaire incomplet");
        return;
    }
    
    // Récupérer les valeurs
    const clientId = clientIdInput.value;
    const fournisseurId = fournisseurIdSelect.value;
    const nomPiece = nomPieceInput.value.trim();
    const quantite = quantiteInput.value;
    const prixEstime = prixEstimeInput.value;
    const codeBarre = codeBarreInput ? codeBarreInput.value.trim() : '';
    const statut = statutInput ? statutInput.value : 'en_attente';
    const reparationId = reparationIdInput ? reparationIdInput.value : '';
    
    console.log("Valeurs des champs:", {
        clientId,
        fournisseurId,
        nomPiece,
        quantite,
        prixEstime,
        codeBarre,
        statut,
        reparationId
    });
    
    // Vérifier les valeurs
    if (!clientId) {
        console.error("Erreur: Client non sélectionné");
        alert('Veuillez sélectionner un client');
        return;
    }
    
    if (!fournisseurId) {
        console.error("Erreur: Fournisseur non sélectionné");
        alert('Veuillez sélectionner un fournisseur');
        return;
    }
    
    if (!nomPiece) {
        console.error("Erreur: Nom de pièce non saisi");
        alert('Veuillez saisir le nom de la pièce');
        return;
    }
    
    if (!quantite || quantite < 1) {
        console.error("Erreur: Quantité invalide");
        alert('Veuillez saisir une quantité valide');
        return;
    }
    
    // Vérification du prix estimé (facultatif)
    if (prixEstime && prixEstime < 0) {
        console.error("Erreur: Prix estimé invalide");
        alert('Le prix estimé doit être un nombre positif');
        return;
    }
    
    // Créer l'objet FormData avec les valeurs explicites
    const formData = new FormData();
    formData.append('client_id', clientId);
    formData.append('fournisseur_id', fournisseurId);
    formData.append('nom_piece', nomPiece);
    formData.append('quantite', quantite);
    formData.append('prix_estime', prixEstime);
    formData.append('code_barre', codeBarre);
    formData.append('statut', statut);
    
    // Ajouter l'ID de réparation si présent
    if (reparationId) {
        console.log("ID de réparation trouvé:", reparationId);
        formData.append('reparation_id', reparationId);
        console.log("Association à la réparation ID:", reparationId);
    } else {
        console.log("Aucun ID de réparation trouvé");
    }
    
    // Log des données envoyées
    console.log("Données envoyées:");
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Envoyer les données au serveur
    fetch('ajax/ajouter_commande.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("Réponse reçue:", response.status);
        return response.json();
    })
    .then(data => {
        console.log("Données reçues:", data);
        if (data.success) {
            // Fermer le modal
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                console.log("Fermeture du modal");
                modalInstance.hide();
            }
            
            // Rediriger vers la page des commandes
            console.log("Redirection vers la liste des commandes");
            window.location.href = 'index.php?page=commandes_pieces';
        } else {
            console.error("Erreur serveur:", data.message);
            alert(data.message || 'Erreur lors de l\'enregistrement de la commande');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de l\'enregistrement de la commande');
    });
}

// Fonctions pour incrémenter/décrémenter la quantité
function incrementQuantity() {
    const input = document.querySelector('input[name="quantite"]');
    if (input) {
        input.value = parseInt(input.value) + 1;
    }
}

function decrementQuantity() {
    const input = document.querySelector('input[name="quantite"]');
    if (input) {
        const newValue = parseInt(input.value) - 1;
        if (newValue >= 1) {
            input.value = newValue;
        }
    }
}

function incrementEditQuantity() {
    const input = document.getElementById('edit_quantite');
    if (input) {
        input.value = parseInt(input.value) + 1;
    }
}

function decrementEditQuantity() {
    const input = document.getElementById('edit_quantite');
    if (input) {
        const newValue = parseInt(input.value) - 1;
        if (newValue >= 1) {
            input.value = newValue;
        }
    }
}

// Exposer les fonctions globalement
window.incrementQuantity = incrementQuantity;
window.decrementQuantity = decrementQuantity;
window.incrementEditQuantity = incrementEditQuantity;
window.decrementEditQuantity = decrementEditQuantity;

// Fonction pour afficher le modal de statut
function showStatusModal(commandeId, currentStatus) {
    console.log("Ouverture du modal de statut pour la commande:", commandeId);
    
    const modal = document.getElementById('statusModal');
    if (!modal) {
        console.error("Modal de statut non trouvé");
        return;
    }
    
    // Stocker l'ID de la commande dans le modal
    modal.dataset.commandeId = commandeId;
    
    // Supprimer les anciens gestionnaires d'événements pour éviter les doublons
    const statusButtons = modal.querySelectorAll('.status-option');
    statusButtons.forEach(button => {
        // Supprimer tous les gestionnaires d'événements click existants
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        // Mettre à jour la classe active
        if (newButton.dataset.status === currentStatus) {
            newButton.classList.add('active');
        } else {
            newButton.classList.remove('active');
        }
        
        // Ajouter le nouveau gestionnaire d'événements
        newButton.addEventListener('click', function() {
            const newStatus = this.dataset.status;
            const commandeId = modal.dataset.commandeId;
            
            // Ajouter une indication visuelle que le bouton a été cliqué
            statusButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            this.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${this.textContent}`;
            
            console.log("Mise à jour du statut en cours:", {
                commandeId: commandeId,
                newStatus: newStatus
            });
            
            // Mettre à jour le statut
            fetch('ajax/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${commandeId}&statut=${newStatus}`
            })
            .then(response => {
                console.log("Réponse reçue:", response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error("Réponse non-JSON reçue:", text);
                        throw new Error("Réponse invalide du serveur");
                    }
                });
            })
            .then(data => {
                console.log("Données reçues:", data);
                if (data.success) {
                    // Fermer le modal immédiatement
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) modalInstance.hide();
                    
                    // Recharger la page
                    window.location.reload();
                } else {
                    console.error("Erreur serveur:", data.message);
                    alert(data.message || 'Erreur lors de la mise à jour du statut');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la mise à jour du statut. Veuillez réessayer.');
            });
        });
    });
    
    // Afficher le modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
}

// Fonction pour afficher les informations du client
function showClientInfo(clientId, nom, prenom, telephone) {
    console.log("Affichage des informations du client:", { clientId, nom, prenom, telephone });
    
    // Mettre à jour les champs dans le modal
    const clientNameInput = document.getElementById('edit_client_name');
    const clientIdInput = document.getElementById('edit_client_id');
    
    if (clientNameInput && clientIdInput) {
        clientIdInput.value = clientId;
        clientNameInput.value = `${nom} ${prenom}`;
        
        // Mettre à jour le bouton d'information
        const infoButton = clientNameInput.nextElementSibling;
        if (infoButton && infoButton.classList.contains('btn-outline-secondary')) {
            infoButton.onclick = () => {
                // Afficher les informations détaillées du client
                const modal = new bootstrap.Modal(document.getElementById('clientInfoModal'));
                document.getElementById('clientFullName').textContent = `${nom} ${prenom}`;
                document.getElementById('clientPhone').textContent = telephone;
                modal.show();
            };
        }
    }
}

// Fonction pour éditer une commande
function editCommande(commandeId) {
    console.log("Édition de la commande:", commandeId);
    
    fetch('ajax/get_commande.php?id=' + commandeId)
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const commande = data.commande;
            const modal = document.getElementById('editCommandeModal');
            
            if (!modal) {
                console.error("Modal d'édition non trouvé");
                return;
            }
            
            // Remplir les champs du formulaire
            const fields = {
                'edit_id': commande.id,
                'edit_commande_id_display': commande.id,
                'edit_fournisseur_id': commande.fournisseur_id,
                'edit_nom_piece': commande.nom_piece,
                'edit_quantite': commande.quantite,
                'edit_prix_estime': commande.prix_estime,
                'edit_code_barre': commande.code_barre || '',
                'edit_statut': commande.statut
            };
            
            for (const [id, value] of Object.entries(fields)) {
                const element = document.getElementById(id);
                if (element) {
                    if (id === 'edit_commande_id_display') {
                        element.textContent = value;
                    } else {
                    element.value = value;
                    }
                    console.log(`Champ ${id} mis à jour avec la valeur:`, value);
                } else {
                    console.error(`Champ ${id} non trouvé`);
                }
            }
            
            // Mettre à jour l'état des boutons de statut
            const statusButtons = document.querySelectorAll('#editCommandeModal .btn-status-choice');
            statusButtons.forEach(button => {
                if (button.dataset.status === commande.statut) {
                    button.classList.add('active');
            } else {
                    button.classList.remove('active');
                }
            });
            
            // Initialiser les événements des boutons de statut
            setupStatusButtons();
            
            // Initialiser le toggle SMS
            setupSmsToggle();
            
            // Afficher le modal
            const modalInstance = new bootstrap.Modal(modal, {
                backdrop: 'static',
                keyboard: false
            });
            modalInstance.show();
            
            console.log("Modal affiché avec succès");
        } else {
            console.error("Erreur serveur:", data.message);
            if (data.message.includes('Session invalide')) {
                // Rediriger vers la page de connexion si la session est invalide
                window.location.href = 'index.php?page=login';
            } else {
            alert(data.message || 'Erreur lors de la récupération de la commande');
            }
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        if (error.message.includes('401') || error.message.includes('403')) {
            // Rediriger vers la page de connexion en cas d'erreur d'authentification
            window.location.href = 'index.php?page=login';
        } else {
        alert('Erreur lors de la récupération de la commande');
        }
    });
}

// Fonction pour gérer les boutons de statut dans le modal d'édition
function setupStatusButtons() {
    const statusButtons = document.querySelectorAll('#editCommandeModal .btn-status-choice');
    const statusInput = document.getElementById('edit_statut');
    
    if (!statusButtons.length || !statusInput) {
        console.error("Boutons de statut ou input non trouvés");
        return;
    }
    
    statusButtons.forEach(button => {
        // Supprimer les anciens gestionnaires d'événements
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        // Ajouter le nouveau gestionnaire d'événements
        newButton.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            statusButtons.forEach(btn => btn.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Mettre à jour la valeur du champ caché
            statusInput.value = this.dataset.status;
            
            console.log("Statut mis à jour:", this.dataset.status);
        });
    });
}

// Fonction pour gérer le bouton de toggle SMS
function setupSmsToggle() {
    const smsToggleButton = document.getElementById('smsToggleButton');
    const sendSmsSwitch = document.getElementById('sendSmsSwitch');
    
    if (!smsToggleButton || !sendSmsSwitch) {
        console.error("Bouton SMS ou switch non trouvés");
        return;
    }
    
    // Supprimer les anciens gestionnaires d'événements
    const newButton = smsToggleButton.cloneNode(true);
    smsToggleButton.parentNode.replaceChild(newButton, smsToggleButton);
    
    // Ajouter le nouveau gestionnaire d'événements
    newButton.addEventListener('click', function() {
        const currentValue = sendSmsSwitch.value === '1';
        sendSmsSwitch.value = currentValue ? '0' : '1';
        
        if (currentValue) {
            // Désactiver l'envoi de SMS
            this.className = 'btn btn-danger w-100 py-3';
            this.innerHTML = '<i class="fas fa-ban me-2"></i>NE PAS ENVOYER DE SMS AU CLIENT';
        } else {
            // Activer l'envoi de SMS
            this.className = 'btn btn-success w-100 py-3';
            this.innerHTML = '<i class="fas fa-check me-2"></i>ENVOYER UN SMS AU CLIENT';
        }
        
        console.log("État SMS mis à jour:", sendSmsSwitch.value);
    });
}

// Fonction pour supprimer une commande
function deleteCommande(commandeId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')) {
        return;
    }
    
    console.log("Suppression de la commande:", commandeId);
    
    // Afficher un indicateur de chargement
    const loadingOverlay = document.createElement('div');
    loadingOverlay.id = 'loading-overlay';
    loadingOverlay.innerHTML = `<div class="spinner-border text-primary" role="status"></div>`;
    loadingOverlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 9999;';
    document.body.appendChild(loadingOverlay);
    
    // Charger d'abord une clé d'authentification (méthode alternative pour contourner les problèmes de session)
    fetchAuthKey()
        .then(authKey => {
            // Construire l'URL avec tous les paramètres nécessaires
            const url = buildSecureUrl('ajax/delete_commande.php', authKey);
            
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'X-Auth-Key': authKey
                },
                body: JSON.stringify({ 
                    id: commandeId,
                    timestamp: Date.now() // Éviter la mise en cache
                }),
                credentials: 'same-origin' // Envoyer les cookies
            });
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status} - ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            // Supprimer l'indicateur de chargement
            if (document.getElementById('loading-overlay')) {
                document.getElementById('loading-overlay').remove();
            }
            
            console.log("Réponse du serveur:", data);
            
            if (data.success) {
                // Notification de succès
                showNotification('Commande supprimée avec succès', 'success');
                
                // Trouver et masquer la ligne du tableau
                const commandeRow = document.querySelector(`tr[data-fournisseur-id][data-statut][data-date]`);
                if (commandeRow) {
                    commandeRow.style.display = 'none';
                    // Animation optionnelle de suppression
                    commandeRow.style.transition = 'all 0.5s';
                    commandeRow.style.opacity = '0';
                    commandeRow.style.transform = 'translateX(100%)';
                }
                
                // Recharger la page après un court délai
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                console.error('Erreur lors de la suppression:', data.message);
                
                // Afficher le message d'erreur
                showNotification(data.message || 'Erreur lors de la suppression de la commande', 'danger');
                
                // Si persistant, proposer un rechargement de la page
                if (data.message === 'Utilisateur non connecté') {
                    setTimeout(() => {
                        if (confirm('Problème d\'authentification détecté. Voulez-vous recharger la page pour réessayer?')) {
                            window.location.reload();
                        }
                    }, 1500);
                }
            }
        })
        .catch(error => {
            // Supprimer l'indicateur de chargement
            if (document.getElementById('loading-overlay')) {
                document.getElementById('loading-overlay').remove();
            }
            
            console.error('Erreur:', error);
            showNotification('Erreur de connexion au serveur. Veuillez réessayer.', 'danger');
        });
}

// Fonction pour obtenir une clé d'authentification
function fetchAuthKey() {
    // URL unique pour éviter la mise en cache
    const url = `ajax/auth_key.php?t=${Date.now()}`;
    
    return fetch(url, { 
        credentials: 'same-origin',
        headers: {
            'Cache-Control': 'no-cache, no-store, must-revalidate'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            return data.auth_key;
        } else {
            throw new Error('Impossible d\'obtenir une clé d\'authentification');
        }
    })
    .catch(error => {
        console.error('Erreur lors de la récupération de la clé d\'authentification:', error);
        // En cas d'échec, générer une clé basique à partir de l'heure actuelle
        // C'est une solution de secours, moins sécurisée
        return 'fallback_' + Date.now().toString();
    });
}

// Fonction pour construire une URL sécurisée avec tous les paramètres nécessaires
function buildSecureUrl(baseUrl, authKey) {
    // Obtenir l'ID de session actuel depuis le cookie
    let sessionId = '';
    const cookies = document.cookie.split(';');
    for (let i = 0; i < cookies.length; i++) {
        const cookie = cookies[i].trim();
        if (cookie.startsWith('PHPSESSID=')) {
            sessionId = cookie.substring('PHPSESSID='.length);
            break;
        }
    }
    
    // Construire l'URL avec tous les paramètres de sécurité
    const url = `${baseUrl}?sid=${encodeURIComponent(sessionId)}&auth_key=${encodeURIComponent(authKey)}&t=${Date.now()}`;
    return url;
}

// Fonction pour afficher une notification
function showNotification(message, type = 'info') {
    // Créer un élément de notification
    const notification = document.createElement('div');
    notification.className = `toast align-items-center text-white bg-${type} border-0`;
    notification.role = 'alert';
    notification.setAttribute('aria-live', 'assertive');
    notification.setAttribute('aria-atomic', 'true');
    
    notification.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Ajouter à un conteneur de notifications ou créer un nouveau
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(notification);
    
    // Initialiser et afficher la notification
    const toast = new bootstrap.Toast(notification, {
        delay: 5000
    });
    toast.show();
    
    // Ajouter un effet de disparition
    notification.addEventListener('hidden.bs.toast', function() {
        notification.remove();
    });
}

// Fonction pour obtenir la classe CSS du statut
function get_status_class(status) {
    switch(status) {
        case 'en_attente':
            return 'bg-warning';
        case 'commande':
            return 'bg-info';
        case 'recue':
            return 'bg-success';
        case 'termine':
            return 'bg-success';
        case 'annulee':
            return 'bg-danger';
        case 'urgent':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Fonction pour obtenir le libellé du statut
function get_status_label(status) {
    switch(status) {
        case 'en_attente':
            return 'En attente';
        case 'commande':
            return 'Commandé';
        case 'recue':
            return 'Reçu';
        case 'termine':
            return 'Terminé';
        case 'annulee':
            return 'Annulé';
        case 'urgent':
            return 'URGENT';
        default:
            return status;
    }
}

// Fonction pour filtrer les commandes
function filterCommandes(type) {
    console.log("Filtrage des commandes par statut:", type);
    
    // Mettre à jour le bouton actif
    const buttons = document.querySelectorAll('.status-filter');
    buttons.forEach(button => {
        if (button.dataset.status === type) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });
    
    // Obtenir toutes les lignes du tableau
    const rows = document.querySelectorAll('#commandesTableBody tr[data-statut]');
    let visibleCount = 0;
    const totalCount = rows.length;
    
    // Si aucune ligne trouvée, sortir
    if (rows.length === 0) {
        console.warn("Aucune ligne de commande trouvée dans le tableau");
        return;
    }
    
    console.log(`Filtrage de ${rows.length} lignes...`);
    
    // Filtrer les lignes
    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-statut');
        
        if (type === 'all' || rowStatus === type) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    console.log(`Résultat du filtrage: ${visibleCount} lignes visibles sur ${totalCount}`);
    
    // Mettre à jour les compteurs
    const visibleRowsCountElement = document.getElementById('visibleRowsCount');
    const totalRowsCountElement = document.getElementById('totalRowsCount');
    
    if (visibleRowsCountElement) visibleRowsCountElement.textContent = visibleCount;
    if (totalRowsCountElement) totalRowsCountElement.textContent = totalCount;
    
    // Afficher ou masquer le bouton de réinitialisation des filtres
    const resetButton = document.getElementById('resetFilters');
    if (resetButton) {
        if (type !== 'all' || (window.currentFournisseurFilter && window.currentFournisseurFilter !== 'all')) {
            resetButton.classList.remove('d-none');
        } else {
            resetButton.classList.add('d-none');
        }
    }
    
    // Sauvegarder le filtre actuel
    window.currentStatusFilter = type;
}

// Fonction pour exporter en PDF
function exportToPDF(includeCompleted = false) {
    console.log("Export PDF...");
    
    // Récupérer les données du tableau actif
    const activeTab = document.querySelector('.tab-pane.active');
    if (!activeTab) return;
    
    const rows = Array.from(activeTab.querySelectorAll('tbody tr')).map(row => {
        return {
            codeBarre: row.cells[0].textContent,
            client: row.cells[1].textContent,
            piece: row.cells[2].textContent,
            quantite: row.cells[3].textContent,
            prix: row.cells[4].textContent,
            reparation: row.cells[5].textContent,
            statut: row.cells[6].textContent,
            date: row.cells[7].textContent
        };
    });
    
    // Créer le PDF
    const doc = new jsPDF();
    
    // Ajouter le titre
    doc.setFontSize(16);
    doc.text('Liste des commandes', 20, 20);
    
    // Ajouter la date et le type
    doc.setFontSize(12);
    doc.text(`Date: ${new Date().toLocaleDateString()}`, 20, 30);
    doc.text(`Type: ${includeCompleted ? 'Commandes terminées' : 'Commandes en cours'}`, 20, 37);
    
    // Ajouter le tableau
    doc.autoTable({
        head: [['Code barre', 'Client', 'Pièce', 'Quantité', 'Prix', 'Réparation', 'Statut', 'Date']],
        body: rows.map(row => [
            row.codeBarre,
            row.client,
            row.piece,
            row.quantite,
            row.prix,
            row.reparation,
            row.statut,
            row.date
        ]),
        startY: 45,
        theme: 'grid'
    });
    
    // Sauvegarder le PDF
    doc.save(`commandes_${includeCompleted ? 'terminees' : 'en_cours'}.pdf`);
}

// Fonction pour mettre à jour une commande
function updateCommande() {
    console.log("Mise à jour de la commande...");
    
    const form = document.getElementById('editCommandeForm');
    if (!form) {
        console.error("Formulaire d'édition non trouvé");
        return;
    }
    
    // Récupérer l'ID de la commande
    const commandeId = document.getElementById('edit_id').value;
    if (!commandeId) {
        console.error("ID de la commande non trouvé");
        alert("Erreur: ID de la commande manquant");
        return;
    }
    
    // Créer un objet FormData avec les données du formulaire
    const formData = new FormData();
    formData.append('commande_id', commandeId); // Ajouter l'ID de la commande
    
    // Ajouter tous les champs du formulaire
    const formFields = form.querySelectorAll('input, select, textarea');
    formFields.forEach(field => {
        if (field.name && field.name !== 'id') { // Exclure le champ id car on utilise commande_id
            formData.append(field.name, field.value);
        }
    });
    
    // Log des données envoyées
    console.log("Données envoyées:");
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    fetch('ajax/update_commande.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editCommandeModal'));
            if (modal) modal.hide();
            
            // Recharger la page
            window.location.reload();
        } else {
            console.error("Erreur serveur:", data.message);
            alert(data.message || 'Erreur lors de la mise à jour de la commande');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour de la commande');
    });
}

// Fonction pour éditer une réparation
function editReparation(reparationId) {
    console.log("Édition de la réparation:", reparationId);
    
    fetch(`ajax/get_reparation.php?id=${reparationId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const reparation = data.reparation;
            console.log("Données de la réparation reçues:", reparation);
            
            // Remplir le formulaire d'édition
            document.getElementById('edit_reparation_id').value = reparation.id;
            document.getElementById('edit_reparation_id_display').textContent = reparation.id;
            
            // Afficher les informations du client
            const clientNameInput = document.getElementById('edit_reparation_client_name');
            const clientIdInput = document.getElementById('edit_reparation_client_id');
            
            if (clientNameInput && clientIdInput) {
                clientIdInput.value = reparation.client_id;
                
                // Récupérer les informations du client
                fetch(`ajax/get_client.php?id=${reparation.client_id}`)
                .then(response => response.json())
                .then(clientData => {
                    if (clientData.success) {
                        const client = clientData.client;
                        clientNameInput.value = `${client.nom} ${client.prenom}`;
                    } else {
                        clientNameInput.value = 'Client non spécifié';
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des informations client:', error);
                    clientNameInput.value = 'Client non spécifié';
                });
            }
            
            // Remplir les autres champs
            const fields = {
                'edit_type_appareil': reparation.type_appareil,
                'edit_marque': reparation.marque,
                'edit_modele': reparation.modele,
                'edit_numero_serie': reparation.numero_serie,
                'edit_date_depot': reparation.date_depot,
                'edit_date_reception': reparation.date_reception,
                'edit_probleme_signe': reparation.probleme_signe,
                'edit_diagnostic': reparation.diagnostic,
                'edit_solution': reparation.solution,
                'edit_pieces_utilisees': reparation.pieces_utilisees,
                'edit_cout_pieces': reparation.cout_pieces,
                'edit_cout_main_oeuvre': reparation.cout_main_oeuvre,
                'edit_prix_total': reparation.prix_total,
                'edit_statut_reparation': reparation.statut,
                'edit_date_fin': reparation.date_fin,
                'edit_garantie': reparation.garantie
            };
            
            for (const [id, value] of Object.entries(fields)) {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value;
                }
            }
            
            // Calculer le prix total
            calculateTotalPrice();
            
            // Afficher le modal
            const modal = new bootstrap.Modal(document.getElementById('editReparationModal'));
            modal.show();
        } else {
            console.error("Erreur serveur:", data.message);
            alert(data.message || 'Erreur lors de la récupération de la réparation');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la récupération de la réparation');
    });
}

// Fonction pour calculer le prix total
function calculateTotalPrice() {
    const coutPieces = parseFloat(document.getElementById('edit_cout_pieces').value) || 0;
    const coutMainOeuvre = parseFloat(document.getElementById('edit_cout_main_oeuvre').value) || 0;
    const prixTotal = coutPieces + coutMainOeuvre;
    
    document.getElementById('edit_prix_total').value = prixTotal.toFixed(2);
}

// Fonction pour mettre à jour une réparation
function updateReparation() {
    const form = document.getElementById('editReparationForm');
    if (!form) {
        console.error("Formulaire d'édition non trouvé");
        return;
    }
    
    const formData = new FormData(form);
    
    fetch('ajax/update_reparation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Réparation mise à jour avec succès');
            location.reload();
        } else {
            console.error("Erreur serveur:", data.message);
            alert(data.message || 'Erreur lors de la mise à jour de la réparation');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour de la réparation');
    });
}

// Fonction pour charger les commandes archivées
function loadArchivedCommandes(page = 1, filter = 'all', searchTerm = '') {
    console.log("Chargement des commandes archivées...", { page, filter, searchTerm });
    
    // Construire l'URL avec les paramètres
    let url = `ajax/get_archived_commandes.php?page=${page}`;
    if (filter !== 'all') {
        url += `&filter=${filter}`;
    }
    if (searchTerm !== '') {
        url += `&search=${encodeURIComponent(searchTerm)}`;
    }
    
    console.log("URL de requête:", url);
    
    // Afficher un indicateur de chargement
    const tableBody = document.getElementById('archived-commandes-body');
    if (tableBody) {
        tableBody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Chargement des données...</p></td></tr>';
    }
    
    fetch(url)
    .then(response => {
        console.log("Statut de la réponse:", response.status);
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log("Données reçues:", data);
        if (data.success) {
            displayArchivedCommandes(data.commandes, data.pagination);
        } else {
            console.error("Erreur lors du chargement des commandes:", data.message);
            if (tableBody) {
                tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-4"><div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i>${data.message || 'Erreur lors du chargement des commandes'}</div></td></tr>`;
            }
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        if (tableBody) {
            tableBody.innerHTML = `<tr><td colspan="9" class="text-center py-4"><div class="alert alert-danger mb-0"><i class="fas fa-times-circle me-2"></i>Erreur de connexion au serveur: ${error.message}</div></td></tr>`;
        }
    });
}

// Fonction pour afficher les commandes archivées
function displayArchivedCommandes(commandes, pagination) {
    console.log("Affichage des commandes archivées:", commandes);
    const tableBody = document.getElementById('archived-commandes-body');
    const paginationContainer = document.getElementById('archived-commandes-pagination');
    
    if (!tableBody) {
        console.error("Conteneur de tableau des commandes archivées non trouvé");
        return;
    }
    
    // Vider le tableau
    tableBody.innerHTML = '';
    
    if (!commandes || commandes.length === 0) {
        console.log("Aucune commande archivée trouvée");
        tableBody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>Aucune commande terminée ou annulée trouvée. Les commandes apparaîtront ici une fois qu\'elles seront marquées comme "Terminé" ou "Annulé".</div></td></tr>';
        
        // Masquer la pagination
        if (paginationContainer) {
            paginationContainer.innerHTML = '';
        }
        return;
    }
    
    // Ajouter chaque commande au tableau
    commandes.forEach(commande => {
        const row = document.createElement('tr');
        row.className = 'align-middle';
        
        // Code barre
        let cell = document.createElement('td');
        cell.className = 'fw-medium';
        cell.textContent = commande.code_barre || '-';
        row.appendChild(cell);
        
        // Client
        cell = document.createElement('td');
        cell.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="avatar-circle me-2">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div class="fw-medium client-name" style="cursor: pointer;" onclick="showClientInfo(${commande.client_id}, '${commande.client_nom}', '${commande.client_prenom}', '${commande.telephone || ''}')">
                        ${commande.client_nom} ${commande.client_prenom}
                    </div>
                    <small class="text-muted">${commande.fournisseur_nom}</small>
                </div>
            </div>
        `;
        row.appendChild(cell);
        
        // Pièce
        cell = document.createElement('td');
        cell.className = 'text-truncate';
        cell.style.maxWidth = '200px';
        cell.textContent = commande.nom_piece || 'Non spécifié';
        row.appendChild(cell);
        
        // Quantité
        cell = document.createElement('td');
        cell.className = 'text-center';
        cell.textContent = commande.quantite || 'Non spécifié';
        row.appendChild(cell);
        
        // Prix
        cell = document.createElement('td');
        cell.className = 'text-end fw-medium';
        cell.textContent = parseFloat(commande.prix_estime).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
        row.appendChild(cell);
        
        // Réparation
        cell = document.createElement('td');
        if (commande.reparation_id) {
            const appareil = `${commande.type_appareil || ''} ${commande.marque || ''} ${commande.modele || ''}`.trim();
            cell.innerHTML = `
                <div class="d-flex flex-column">
                    <span class="badge bg-info bg-opacity-10 text-info">#${commande.reparation_id}</span>
                    <small class="text-muted mt-1">${appareil}</small>
                </div>
            `;
        } else {
            cell.innerHTML = '<span class="text-muted">-</span>';
        }
        row.appendChild(cell);
        
        // Statut
        cell = document.createElement('td');
        cell.innerHTML = `
            <button type="button" class="btn btn-sm ${get_status_class(commande.statut)} status-btn" 
                data-status="${commande.statut}"
                onclick="showStatusModal(${commande.id}, '${commande.statut}')">
                <i class="fas fa-circle me-1"></i>
                ${get_status_label(commande.statut)}
            </button>
        `;
        row.appendChild(cell);
        
        // Date
        cell = document.createElement('td');
        cell.className = 'text-nowrap';
        const date = new Date(commande.date_creation);
        cell.innerHTML = `
            <div class="d-flex flex-column">
                <span class="fw-medium">${date.toLocaleDateString('fr-FR')}</span>
                <small class="text-muted">${date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</small>
            </div>
        `;
        row.appendChild(cell);
        
        // Actions
        cell = document.createElement('td');
        cell.className = 'text-end';
        cell.innerHTML = `
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary" onclick="editCommande(${commande.id})" title="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteCommande(${commande.id})" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
                <a href="https://www.google.com/search?q=${encodeURIComponent(commande.fournisseur_nom + ' ' + (commande.code_barre || '') + ' ' + commande.nom_piece)}" target="_blank" class="btn btn-sm btn-outline-info" title="Rechercher '${commande.fournisseur_nom} ${commande.code_barre} ${commande.nom_piece}' sur Google">
                    <i class="fab fa-google"></i>
                </a>
            </div>
        `;
        row.appendChild(cell);
        
        tableBody.appendChild(row);
    });
    
    // Afficher la pagination
    if (paginationContainer) {
        paginationContainer.innerHTML = '';
        
        if (pagination && pagination.total_pages > 1) {
            const ul = document.createElement('ul');
            ul.className = 'pagination';
            
            // Bouton précédent
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${pagination.current_page === 1 ? 'disabled' : ''}`;
            const prevLink = document.createElement('button');
            prevLink.className = 'page-link';
            prevLink.innerHTML = '<i class="fas fa-chevron-left"></i>';
            if (pagination.current_page > 1) {
                prevLink.addEventListener('click', () => loadArchivedCommandes(pagination.current_page - 1, getCurrentFilter(), getCurrentSearch()));
            }
            prevLi.appendChild(prevLink);
            ul.appendChild(prevLi);
            
            // Pages
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === pagination.current_page ? 'active' : ''}`;
                const link = document.createElement('button');
                link.className = 'page-link';
                link.textContent = i;
                if (i !== pagination.current_page) {
                    link.addEventListener('click', () => loadArchivedCommandes(i, getCurrentFilter(), getCurrentSearch()));
                }
                li.appendChild(link);
                ul.appendChild(li);
            }
            
            // Bouton suivant
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${pagination.current_page === pagination.total_pages ? 'disabled' : ''}`;
            const nextLink = document.createElement('button');
            nextLink.className = 'page-link';
            nextLink.innerHTML = '<i class="fas fa-chevron-right"></i>';
            if (pagination.current_page < pagination.total_pages) {
                nextLink.addEventListener('click', () => loadArchivedCommandes(pagination.current_page + 1, getCurrentFilter(), getCurrentSearch()));
            }
            nextLi.appendChild(nextLink);
            ul.appendChild(nextLi);
            
            paginationContainer.appendChild(ul);
        }
    }
}

// Fonction pour récupérer le filtre actuel
function getCurrentFilter() {
    const activeFilterBtn = document.querySelector('.archived-filter.active');
    if (activeFilterBtn) {
        if (activeFilterBtn.dataset.status) {
            return activeFilterBtn.dataset.status;
        } else if (activeFilterBtn.dataset.repair) {
            return activeFilterBtn.dataset.repair;
        }
    }
    return 'all';
}

// Fonction pour récupérer le terme de recherche actuel
function getCurrentSearch() {
    const searchInput = document.getElementById('archived-search');
    return searchInput ? searchInput.value : '';
}

// Fonction pour exporter les commandes archivées en PDF
function exportArchivedToPDF() {
    console.log("Export PDF des commandes archivées...");
    
    // Récupérer les données du tableau
    const rows = Array.from(document.querySelectorAll('#archived-commandes-body tr')).map(row => {
        // Vérifier que la ligne n'est pas un message "Aucune commande trouvée"
        if (row.cells.length === 1 && row.cells[0].colSpan === 9) {
            return null;
        }
        
        return {
            codeBarre: row.cells[0].textContent,
            client: row.cells[1].textContent.replace(/\s+/g, ' ').trim(),
            piece: row.cells[2].textContent,
            quantite: row.cells[3].textContent,
            prix: row.cells[4].textContent,
            reparation: row.cells[5].textContent.replace(/\s+/g, ' ').trim(),
            statut: row.cells[6].textContent.replace(/\s+/g, ' ').trim(),
            date: row.cells[7].textContent.replace(/\s+/g, ' ').trim()
        };
    }).filter(Boolean);
    
    if (rows.length === 0) {
        alert('Aucune donnée à exporter.');
        return;
    }
    
    // Créer le PDF
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Ajouter le titre
    doc.setFontSize(16);
    doc.text('Liste des commandes archivées', 20, 20);
    
    // Ajouter la date et le filtre
    const filter = getCurrentFilter();
    const search = getCurrentSearch();
    
    doc.setFontSize(12);
    doc.text(`Date d'exportation: ${new Date().toLocaleDateString('fr-FR')}`, 20, 30);
    doc.text(`Filtre appliqué: ${filter === 'all' ? 'Toutes les commandes' : 
              filter === 'termine' ? 'Commandes terminées' : 
              filter === 'annulee' ? 'Commandes annulées' : 
              filter === 'with' ? 'Avec réparation' : 'Sans réparation'}`, 20, 37);
    
    if (search) {
        doc.text(`Recherche: "${search}"`, 20, 44);
    }
    
    // Ajouter le tableau
    doc.autoTable({
        head: [['Code barre', 'Client', 'Pièce', 'Quantité', 'Prix', 'Réparation', 'Statut', 'Date']],
        body: rows.map(row => [
            row.codeBarre,
            row.client,
            row.piece,
            row.quantite,
            row.prix,
            row.reparation,
            row.statut,
            row.date
        ]),
        startY: search ? 50 : 44,
        theme: 'grid',
        styles: { fontSize: 8 },
        columnStyles: {
            0: { cellWidth: 20 },  // Code barre
            1: { cellWidth: 20 },  // Client - réduit
            2: { cellWidth: 35 },  // Pièce - augmenté
            3: { cellWidth: 12 },  // Quantité
            4: { cellWidth: 18 },  // Prix
            5: { cellWidth: 25 },  // Réparation
            6: { cellWidth: 18 },  // Statut
            7: { cellWidth: 22 }   // Date
        }
    });
    
    // Pied de page
    doc.setFontSize(10);
    doc.text('© Système de Gestion d\'Atelier - Document généré automatiquement', 14, doc.internal.pageSize.height - 10);
    
    // Enregistrement du PDF
    doc.save(`commandes_archivees_${new Date().toLocaleDateString('fr-FR').replace(/\//g, '-')}.pdf`);
}

// Fonction pour filtrer les commandes par date
function filterCommandesByDate() {
    const rows = document.querySelectorAll('tbody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        // Vérifier d'abord si la ligne est déjà cachée par un autre filtre
        if (row.style.display === 'none' && currentPeriode !== 'all') {
            return; // Ne pas modifier les lignes déjà cachées
        }
        
        const dateCell = row.querySelector('td:nth-child(8)');
        if (!dateCell) return;
        
        const dateText = dateCell.textContent.trim();
        const dateParts = dateText.split(' ')[0].split('/');
        if (dateParts.length !== 3) return;
        
        const rowDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
        let showRow = true;
        
        switch (currentPeriode) {
            case 'today':
                const today = new Date();
                showRow = rowDate.getDate() === today.getDate() && 
                         rowDate.getMonth() === today.getMonth() && 
                         rowDate.getFullYear() === today.getFullYear();
                break;
                
            case 'last3days':
                const threeDaysAgo = new Date();
                threeDaysAgo.setDate(threeDaysAgo.getDate() - 3);
                showRow = rowDate >= threeDaysAgo;
                break;
                
            case 'last7days':
                const sevenDaysAgo = new Date();
                sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
                showRow = rowDate >= sevenDaysAgo;
                break;
                
            case 'last10days':
                const tenDaysAgo = new Date();
                tenDaysAgo.setDate(tenDaysAgo.getDate() - 10);
                showRow = rowDate >= tenDaysAgo;
                break;
                
            case 'custom':
                if (startDate && endDate) {
                    // Régler l'heure à minuit pour startDate et 23:59:59 pour endDate
                    const start = new Date(startDate);
                    start.setHours(0, 0, 0, 0);
                    
                    const end = new Date(endDate);
                    end.setHours(23, 59, 59, 999);
                    
                    rowDate.setHours(12, 0, 0, 0); // Midi pour éviter les problèmes de fuseau horaire
                    showRow = rowDate >= start && rowDate <= end;
                }
                break;
                
            default: // 'all'
                showRow = true;
                break;
        }
        
        if (showRow) {
            // Ne pas modifier l'affichage si déjà caché par un autre filtre
            if (row.style.display !== 'none') {
                visibleCount++;
            }
        } else {
            row.style.display = 'none';
        }
    });
    
    return visibleCount;
}

// Fonction pour exporter les commandes en PDF
function exportPDF() {
    console.log("Exportation des commandes en PDF...");
    
    // Récupération des données du tableau
    const rows = [];
    const tableRows = document.querySelectorAll('#commandesTableBody tr');
    
    // Vérifier s'il y a des données à exporter
    if (tableRows.length === 0 || (tableRows.length === 1 && tableRows[0].querySelector('td[colspan]'))) {
        showNotification('Aucune donnée à exporter', 'warning');
        return;
    }
    
    // Collecter les données visibles uniquement (celles qui ne sont pas filtrées)
    tableRows.forEach(row => {
        // Ne pas inclure les lignes masquées par les filtres
        if (row.style.display !== 'none') {
            try {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 7) {
                    rows.push({
                        client: cells[0].textContent.replace(/\s+/g, ' ').trim(),
                        date: cells[1].textContent.trim(),
                        fournisseur: cells[2].textContent.trim(),
                        piece: cells[3].textContent.trim(),
                        quantite: cells[4].textContent.trim(),
                        prix: cells[5].textContent.trim(),
                        statut: cells[6].textContent.trim()
                    });
                }
            } catch (e) {
                console.error("Erreur lors de l'extraction des données:", e);
            }
        }
    });
    
    if (rows.length === 0) {
        showNotification('Aucune donnée visible à exporter', 'warning');
        return;
    }
    
    try {
        // Initialiser jsPDF
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Titre
        doc.setFontSize(18);
        doc.text('Liste des Commandes de Pièces', 14, 22);
        
        // Sous-titre avec les filtres appliqués
        doc.setFontSize(11);
        doc.setTextColor(100);
        
        const filtres = [];
        if (currentStatusFilter !== 'all') filtres.push(`Statut: ${get_status_label(currentStatusFilter)}`);
        if (currentFournisseurId) {
            const fournisseurLabel = document.querySelector('#fournisseurBouton').textContent.trim();
            filtres.push(`Fournisseur: ${fournisseurLabel}`);
        }
        if (currentPeriode !== 'all') {
            const periodeLabel = document.querySelector('#periodeButton').textContent.trim();
            filtres.push(`Période: ${periodeLabel}`);
        }
        if (currentSearchTerm) filtres.push(`Recherche: "${currentSearchTerm}"`);
        
        // Ajouter les filtres si présents
        if (filtres.length > 0) {
            doc.text(`Filtres: ${filtres.join(' | ')}`, 14, 30);
        }
        
        // Date d'exportation
        const exportDate = new Date().toLocaleDateString('fr-FR', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        doc.text(`Exporté le: ${exportDate}`, 14, filtres.length > 0 ? 38 : 30);
        
        // Créer le tableau
        doc.autoTable({
            head: [['Client', 'Date', 'Fournisseur', 'Pièce', 'Qté', 'Prix', 'Statut']],
            body: rows.map(row => [
                row.client,
                row.date,
                row.fournisseur,
                row.piece,
                row.quantite,
                row.prix,
                row.statut
            ]),
            startY: filtres.length > 0 ? 42 : 34,
            styles: { fontSize: 8 },
            headStyles: { fillColor: [41, 128, 185], textColor: 255 },
            alternateRowStyles: { fillColor: [242, 242, 242] },
            margin: { top: 40 },
            columnStyles: {
                0: { cellWidth: 20 },  // Client - réduit
                1: { cellWidth: 15 },  // Date
                2: { cellWidth: 20 },  // Fournisseur
                3: { cellWidth: 40 },  // Pièce - augmenté
                4: { cellWidth: 12 },  // Quantité
                5: { cellWidth: 18 },  // Prix
                6: { cellWidth: 20 }   // Statut
            }
        });
        
        // Pied de page
        doc.setFontSize(10);
        doc.setTextColor(100);
        const pageCount = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.text(`Page ${i} sur ${pageCount}`, doc.internal.pageSize.width - 20, doc.internal.pageSize.height - 10);
            doc.text('© Système de Gestion - Document généré automatiquement', 14, doc.internal.pageSize.height - 10);
        }
        
        // Enregistrer le PDF
        doc.save(`commandes_pieces_${new Date().toLocaleDateString('fr-FR').replace(/\//g, '-')}.pdf`);
        
        // Notification de succès
        showNotification(`Exportation réussie: ${rows.length} commandes exportées`, 'success');
        
    } catch (error) {
        console.error("Erreur lors de l'exportation PDF:", error);
        showNotification(`Erreur lors de l'exportation: ${error.message}`, 'danger');
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Attacher l'événement au bouton d'exportation PDF
    const exportPdfBtn = document.getElementById('export-pdf-btn');
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function() {
            console.log("Bouton d'exportation PDF cliqué");
            exportPDF();
        });
        console.log("Événement click attaché au bouton d'exportation PDF");
    } else {
        console.warn("Bouton d'exportation PDF non trouvé");
    }
    
    // Initialiser toutes les modales avec les options par défaut
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        try {
            const modalInstance = new bootstrap.Modal(modal, {
                backdrop: true,
                keyboard: true,
                focus: true
            });
            
            // Stocker l'instance dans l'élément pour référence future
            modal._modalInstance = modalInstance;
            
            // Ajouter les gestionnaires d'événements
            modal.addEventListener('show.bs.modal', function(event) {
                console.log(`Modale ${modal.id} en cours d'ouverture`);
            });
            
            modal.addEventListener('shown.bs.modal', function(event) {
                console.log(`Modale ${modal.id} ouverte`);
            });
            
            modal.addEventListener('hide.bs.modal', function(event) {
                console.log(`Modale ${modal.id} en cours de fermeture`);
            });
            
            modal.addEventListener('hidden.bs.modal', function(event) {
                console.log(`Modale ${modal.id} fermée`);
            });
        } catch (error) {
            console.error(`Erreur lors de l'initialisation de la modale ${modal.id}:`, error);
        }
    });
    
    // Filtrer automatiquement par "en_attente" au chargement
    filterCommandes('en_attente');
    
    // Attacher des gestionnaires d'événements aux boutons de filtre
    const filterButtons = document.querySelectorAll('.status-filter');
    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const status = this.getAttribute('data-status');
                filterCommandes(status);
            });
        });
    }
});