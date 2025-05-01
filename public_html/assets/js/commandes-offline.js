/**
 * GeekBoard - Module de gestion des commandes hors ligne
 * 
 * Ce script permet de gérer les commandes en mode hors ligne
 * et assure leur synchronisation lorsque la connexion est rétablie.
 */

class CommandesOfflineManager {
    constructor() {
        this.storage = new OfflineStorage();
        this.syncManager = new Synchronizer();
        this.dbStoreCommandes = 'commandes';
        this.initStorage();
        this.setupEventListeners();
    }

    // Initialiser le stockage pour les commandes
    async initStorage() {
        // Vérifier si le store commandes existe déjà
        if (!this.storage.db) {
            await this.storage.initDB();
        }

        // Créer le store commandes s'il n'existe pas
        if (!this.storage.db.objectStoreNames.contains(this.dbStoreCommandes)) {
            // Fermer la connexion existante
            this.storage.db.close();
            
            // Augmenter la version pour créer un nouveau store
            const newVersion = this.storage.dbVersion + 1;
            const request = indexedDB.open(this.storage.dbName, newVersion);
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Créer le store commandes
                if (!db.objectStoreNames.contains(this.dbStoreCommandes)) {
                    const commandesStore = db.createObjectStore(this.dbStoreCommandes, { keyPath: 'id', autoIncrement: true });
                    commandesStore.createIndex('client_id', 'client_id', { unique: false });
                    commandesStore.createIndex('fournisseur_id', 'fournisseur_id', { unique: false });
                    commandesStore.createIndex('statut', 'statut', { unique: false });
                    commandesStore.createIndex('sync_status', 'sync_status', { unique: false });
                    commandesStore.createIndex('date_creation', 'date_creation', { unique: false });
                }
            };
            
            request.onsuccess = (event) => {
                this.storage.db = event.target.result;
                this.storage.dbVersion = newVersion;
                console.log('Store commandes créé avec succès');
            };
            
            request.onerror = (event) => {
                console.error('Erreur lors de la création du store commandes', event);
            };
        }
    }

    // Configurer les écouteurs d'événements
    setupEventListeners() {
        // Intercepter les formulaires d'ajout de commande
        document.addEventListener('DOMContentLoaded', () => {
            this.interceptForms();
            this.setupStatusButtons();
            this.setupConnectionListeners();
        });
    }

    // Intercepter les formulaires pour le traitement hors ligne
    interceptForms() {
        // Formulaire d'ajout de commande
        const addCommandeForm = document.querySelector('form[data-form="ajouter-commande"]');
        if (addCommandeForm) {
            addCommandeForm.addEventListener('submit', async (event) => {
                if (!navigator.onLine) {
                    event.preventDefault();
                    await this.handleOfflineCommandeSubmit(addCommandeForm);
                }
            });

            // Ajouter un attribut pour le traitement hors ligne
            addCommandeForm.setAttribute('data-offline-form', 'commande');
        }

        // Formulaire de modification de commande
        const editCommandeForm = document.querySelector('form[data-form="modifier-commande"]');
        if (editCommandeForm) {
            editCommandeForm.addEventListener('submit', async (event) => {
                if (!navigator.onLine) {
                    event.preventDefault();
                    await this.handleOfflineCommandeEdit(editCommandeForm);
                }
            });

            // Ajouter un attribut pour le traitement hors ligne
            editCommandeForm.setAttribute('data-offline-form', 'commande-edit');
        }

        // Remplacer la fonction saveCommande globale
        if (typeof window.saveCommande === 'function') {
            const originalSaveCommande = window.saveCommande;
            window.saveCommande = async () => {
                if (!navigator.onLine) {
                    const modal = document.getElementById('ajouterCommandeModal');
                    if (modal) {
                        const form = modal.querySelector('form');
                        if (form) {
                            await this.handleOfflineCommandeSubmit(form);
                            return;
                        }
                    }
                }
                originalSaveCommande();
            };
        }

        // Remplacer la fonction updateCommande globale
        if (typeof window.updateCommande === 'function') {
            const originalUpdateCommande = window.updateCommande;
            window.updateCommande = async () => {
                if (!navigator.onLine) {
                    const form = document.getElementById('editCommandeForm');
                    if (form) {
                        await this.handleOfflineCommandeEdit(form);
                        return;
                    }
                }
                originalUpdateCommande();
            };
        }
    }

    // Configurer les boutons de statut pour le mode hors ligne
    setupStatusButtons() {
        // Remplacer la fonction showStatusModal globale
        if (typeof window.showStatusModal === 'function') {
            const originalShowStatusModal = window.showStatusModal;
            window.showStatusModal = (commandeId, currentStatus) => {
                if (!navigator.onLine) {
                    this.handleOfflineStatusChange(commandeId, currentStatus);
                    return;
                }
                originalShowStatusModal(commandeId, currentStatus);
            };
        }
    }

    // Configurer les écouteurs de connexion
    setupConnectionListeners() {
        window.addEventListener('online', () => {
            this.syncOfflineCommandes();
            this.showConnectionStatus(true);
        });

        window.addEventListener('offline', () => {
            this.showConnectionStatus(false);
        });

        // Afficher le statut de connexion initial
        this.showConnectionStatus(navigator.onLine);
    }

    // Afficher le statut de connexion
    showConnectionStatus(isOnline) {
        const statusElement = document.getElementById('connection-status');
        if (!statusElement) {
            // Créer l'élément de statut s'il n'existe pas
            const statusDiv = document.createElement('div');
            statusDiv.id = 'connection-status';
            statusDiv.className = isOnline ? 'connection-online' : 'connection-offline';
            statusDiv.innerHTML = `
                <div class="status-indicator ${isOnline ? 'online' : 'offline'}"></div>
                <span>${isOnline ? 'Connecté' : 'Mode hors ligne'}</span>
            `;
            
            // Ajouter des styles
            const style = document.createElement('style');
            style.textContent = `
                #connection-status {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    padding: 8px 16px;
                    border-radius: 20px;
                    display: flex;
                    align-items: center;
                    z-index: 9999;
                    font-size: 14px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    transition: all 0.3s ease;
                }
                .connection-online {
                    background-color: #e8f5e9;
                    color: #2e7d32;
                }
                .connection-offline {
                    background-color: #ffebee;
                    color: #c62828;
                }
                .status-indicator {
                    width: 10px;
                    height: 10px;
                    border-radius: 50%;
                    margin-right: 8px;
                }
                .status-indicator.online {
                    background-color: #4caf50;
                }
                .status-indicator.offline {
                    background-color: #f44336;
                }
            `;
            document.head.appendChild(style);
            document.body.appendChild(statusDiv);
        } else {
            // Mettre à jour l'élément existant
            statusElement.className = isOnline ? 'connection-online' : 'connection-offline';
            const indicator = statusElement.querySelector('.status-indicator');
            if (indicator) {
                indicator.className = `status-indicator ${isOnline ? 'online' : 'offline'}`;
            }
            const text = statusElement.querySelector('span');
            if (text) {
                text.textContent = isOnline ? 'Connecté' : 'Mode hors ligne';
            }
        }
    }

    // Gérer la soumission de commande hors ligne
    async handleOfflineCommandeSubmit(form) {
        try {
            // Récupérer les données du formulaire
            const formData = new FormData(form);
            const commandeData = {};
            
            formData.forEach((value, key) => {
                commandeData[key] = value;
            });
            
            // Ajouter des informations supplémentaires
            commandeData.date_creation = new Date().toISOString().slice(0, 19).replace('T', ' ');
            commandeData.statut = commandeData.statut || 'en_attente';
            commandeData.sync_status = 'pending';
            
            // Stocker la commande localement
            const id = await this.storage.add(this.dbStoreCommandes, commandeData);
            
            // Afficher un message de succès
            this.showNotification('Commande enregistrée en mode hors ligne. Elle sera synchronisée automatiquement lors de la reconnexion.', 'success');
            
            // Fermer le modal si présent
            const modal = document.getElementById('ajouterCommandeModal');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) modalInstance.hide();
            }
            
            // Ajouter la commande à la liste si elle existe
            this.addCommandeToList(id, commandeData);
            
            // Rediriger vers la page des commandes
            setTimeout(() => {
                window.location.href = 'index.php?page=commandes_pieces&offline=1';
            }, 1500);
        } catch (error) {
            console.error('Erreur lors de l\'enregistrement hors ligne', error);
            this.showNotification('Erreur lors de l\'enregistrement en mode hors ligne.', 'danger');
        }
    }

    // Gérer la modification de commande hors ligne
    async handleOfflineCommandeEdit(form) {
        try {
            // Récupérer l'ID de la commande
            const commandeId = form.querySelector('input[name="commande_id"]').value;
            if (!commandeId) {
                throw new Error('ID de commande manquant');
            }
            
            // Récupérer les données du formulaire
            const formData = new FormData(form);
            const commandeData = {};
            
            formData.forEach((value, key) => {
                commandeData[key] = value;
            });
            
            // Mettre à jour la commande localement
            await this.storage.update(this.dbStoreCommandes, parseInt(commandeId), commandeData);
            
            // Afficher un message de succès
            this.showNotification('Commande mise à jour en mode hors ligne. Les modifications seront synchronisées automatiquement lors de la reconnexion.', 'success');
            
            // Fermer le modal si présent
            const modal = document.getElementById('editCommandeModal');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) modalInstance.hide();
            }
            
            // Mettre à jour la commande dans la liste si elle existe
            this.updateCommandeInList(commandeId, commandeData);
            
            // Recharger la page
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } catch (error) {
            console.error('Erreur lors de la mise à jour hors ligne', error);
            this.showNotification('Erreur lors de la mise à jour en mode hors ligne.', 'danger');
        }
    }

    // Gérer le changement de statut hors ligne
    async handleOfflineStatusChange(commandeId, currentStatus) {
        try {
            // Créer un modal personnalisé pour le changement de statut hors ligne
            const modalHtml = `
                <div class="modal fade" id="offlineStatusModal" tabindex="-1" aria-labelledby="offlineStatusModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="offlineStatusModalLabel">Modifier le statut (Mode hors ligne)</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    <i class="fas fa-wifi-slash me-2"></i> Vous êtes actuellement en mode hors ligne. Le changement de statut sera synchronisé lorsque la connexion sera rétablie.
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <button class="btn btn-outline-secondary status-option ${currentStatus === 'en_attente' ? 'active' : ''}" data-status="en_attente">En attente</button>
                                    <button class="btn btn-outline-primary status-option ${currentStatus === 'commande' ? 'active' : ''}" data-status="commande">Commandé</button>
                                    <button class="btn btn-outline-info status-option ${currentStatus === 'expedie' ? 'active' : ''}" data-status="expedie">Expédié</button>
                                    <button class="btn btn-outline-success status-option ${currentStatus === 'recu' ? 'active' : ''}" data-status="recu">Reçu</button>
                                    <button class="btn btn-outline-danger status-option ${currentStatus === 'annule' ? 'active' : ''}" data-status="annule">Annulé</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Ajouter le modal au document
            const modalContainer = document.createElement('div');
            modalContainer.innerHTML = modalHtml;
            document.body.appendChild(modalContainer);
            
            // Initialiser le modal
            const modal = new bootstrap.Modal(document.getElementById('offlineStatusModal'));
            modal.show();
            
            // Ajouter les écouteurs d'événements aux boutons de statut
            const statusButtons = document.querySelectorAll('#offlineStatusModal .status-option');
            statusButtons.forEach(button => {
                button.addEventListener('click', async () => {
                    const newStatus = button.dataset.status;
                    
                    // Mettre à jour le statut localement
                    await this.storage.update(this.dbStoreCommandes, parseInt(commandeId), {
                        statut: newStatus,
                        sync_status: 'pending'
                    });
                    
                    // Mettre à jour l'interface
                    this.updateCommandeStatusInList(commandeId, newStatus);
                    
                    // Fermer le modal
                    modal.hide();
                    
                    // Afficher un message de succès
                    this.showNotification('Statut mis à jour en mode hors ligne. Les modifications seront synchronisées automatiquement lors de la reconnexion.', 'success');
                    
                    // Supprimer le modal du DOM après fermeture
                    setTimeout(() => {
                        modalContainer.remove();
                    }, 500);
                });
            });
        } catch (error) {
            console.error('Erreur lors du changement de statut hors ligne', error);
            this.showNotification('Erreur lors du changement de statut en mode hors ligne.', 'danger');
        }
    }

    // Ajouter une commande à la liste
    addCommandeToList(id, commandeData) {
        const tableBody = document.querySelector('table.commandes-table tbody');
        if (!tableBody) return;
        
        // Créer une nouvelle ligne
        const newRow = document.createElement('tr');
        newRow.dataset.commandeId = id;
        newRow.dataset.offline = 'true';
        
        // Obtenir les classes de statut
        const statusClass = this.getStatusClass(commandeData.statut);
        const statusLabel = this.getStatusLabel(commandeData.statut);
        
        // Formater la date
        const date = new Date();
        const formattedDate = `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getFullYear()}`;
        
        // Remplir la ligne avec les données
        newRow.innerHTML = `
            <td>${id}</td>
            <td>${commandeData.client_id || 'N/A'}</td>
            <td>${commandeData.fournisseur_id || 'N/A'}</td>
            <td>${commandeData.nom_piece || 'N/A'}</td>
            <td>${commandeData.quantite || '1'}</td>
            <td>${commandeData.prix_estime || '0'} €</td>
            <td><span class="badge ${statusClass}">${statusLabel}</span></td>
            <td>${formattedDate}</td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary" onclick="editCommande(${id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-info" onclick="showStatusModal(${id}, '${commandeData.statut}')">
                        <i class="fas fa-tag"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCommande(${id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        
        // Ajouter un indicateur visuel pour les commandes hors ligne
        newRow.style.backgroundColor = 'rgba(255, 248, 225, 0.3)';
        
        // Ajouter la ligne au tableau
        tableBody.prepend(newRow);
    }

    // Mettre à jour une commande dans la liste
    updateCommandeInList(id, commandeData) {
        const row = document.querySelector(`tr[data-commande-id="${id}"]`);
        if (!row) return;
        
        // Mettre à jour les cellules pertinentes
        const cells = row.querySelectorAll('td');
        if (cells.length >= 8) {
            if (commandeData.nom_piece) cells[3].textContent = commandeData.nom_piece;
            if (commandeData.quantite) cells[4].textContent = commandeData.quantite;
            if (commandeData.prix_estime) cells[5].textContent = `${commandeData.prix_estime} €`;
            
            // Mettre à jour le statut si présent
            if (commandeData.statut) {
                const statusBadge = cells[6].querySelector('.badge');
                if (statusBadge) {
                    statusBadge.className = `badge ${this.getStatusClass(commandeData.statut)}`;
                    statusBadge.textContent = this.getStatusLabel(commandeData.statut);
                }
            }
        }
        
        // Marquer comme modifié hors ligne
        row.dataset.offline = 'true';
        row.style.backgroundColor = 'rgba(255, 248, 225, 0.3)';
    }

    // Mettre à jour le statut d'une commande dans la liste
    updateCommandeStatusInList(id, newStatus) {
        const row = document.querySelector(`tr[data-commande-id="${id}"]`);
        if (!row) return;
        
        // Mettre à jour le badge de statut
        const statusCell = row.querySelector('td:nth-child(7)');
        if (statusCell) {
            const statusBadge = statusCell.querySelector('.badge');
            if (statusBadge) {
                statusBadge.className = `badge ${this.getStatusClass(newStatus)}`;
                statusBadge.textContent = this.getStatusLabel(newStatus);
            }
        }
        
        // Marquer comme modifié hors ligne
        row.dataset.offline = 'true';
        row.style.backgroundColor = 'rgba(255, 248, 225, 0.3)';
    }

    // Obtenir la classe CSS pour un statut
    getStatusClass(status) {
        const statusClasses = {
            'en_attente': 'bg-secondary',
            'commande': 'bg-primary',
            'expedie': 'bg-info',
            'recu': 'bg-success',
            'annule': 'bg-danger'
        };
        
        return statusClasses[status] || 'bg-secondary';
    }

    // Obtenir le libellé pour un statut
    getStatusLabel(status) {
        const statusLabels = {
            'en_attente': 'En attente',
            'commande': 'Commandé',
            'expedie': 'Expédié',
            'recu': 'Reçu',
            'annule': 'Annulé'
        };
        
        return statusLabels[status] || 'En attente';
    }

    // Synchroniser les commandes hors ligne
    async syncOfflineCommandes() {
        try {
            // Récupérer toutes les commandes non synchronisées
            const commandes = await this.storage.getAll(this.dbStoreCommandes);
            const pendingCommandes = commandes.filter(commande => commande.sync_status === 'pending');
            
            if (pendingCommandes.length === 0) return;
            
            console.log(`Synchronisation de ${pendingCommandes.length} commandes...`);
            
            // Afficher un message de synchronisation
            this.showNotification(`Synchronisation de ${pendingCommandes.length} commandes en cours...`, 'info');
            
            // Synchroniser chaque commande
            for (const commande of pendingCommandes) {
                try {
                    // Déterminer l'action (ajout ou mise à jour)
                    const action = commande.id < 1000 ? 'add' : 'update';
                    
                    // Préparer les données pour la synchronisation
                    const syncData = { ...commande };
                    delete syncData.sync_status;
                    
                    // Envoyer les données au serveur
                    const response = await fetch('ajax/sync_commande.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action,
                            data: syncData
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Mettre à jour le statut de synchronisation
                        await this.storage.update(this.dbStoreCommandes, commande.id, {
                            sync_status: 'synced',
                            server_id: result.id || commande.id
                        });
                        
                        console.log(`Commande ${commande.id} synchronisée avec succès`);
                    } else {
                        console.error(`Erreur lors de la synchronisation de la commande ${commande.id}:`, result.message);
                    }
                } catch (error) {
                    console.error(`Erreur lors de la synchronisation de la commande ${commande.id}:`, error);
                }
            }
            
            // Afficher un message de succès
            this.showNotification('Synchronisation des commandes terminée', 'success');
            
            // Recharger la page après synchronisation
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } catch (error) {
            console.error('Erreur lors de la synchronisation des commandes:', error);
            this.showNotification('Erreur lors de la synchronisation des commandes', 'danger');
        }
    }

    // Afficher une notification
    showNotification(message, type = 'info') {
        // Utiliser toastr si disponible
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
            return;
        }
        
        // Utiliser bootstrap toast si disponible
        if (typeof bootstrap !== 'undefined' && typeof bootstrap.Toast !== 'undefined') {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                const container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(container);
            }
            
            const toastId = `toast-${Date.now()}`;
            const toastHtml = `
                <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" id="${toastId}">
                    <div class="toast-header bg-${type} text-white">
                        <strong class="me-auto">GeekBoard</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Fermer"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            document.getElementById('toast-container').insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 5000 });
            toast.show();
            
            return;
        }
        
        // Fallback sur alert
        alert(message);
    }
}

// Initialiser le gestionnaire de commandes hors ligne
document.addEventListener('DOMContentLoaded', () => {
    // Vérifier si les classes nécessaires sont disponibles
    if (typeof OfflineStorage !== 'undefined' && typeof Synchronizer !== 'undefined') {
        window.commandesOfflineManager = new CommandesOfflineManager();
        console.log('Gestionnaire de commandes hors ligne initialisé');
    } else {
        console.warn('Les classes OfflineStorage et/ou Synchronizer ne sont pas disponibles. Le gestionnaire de commandes hors ligne ne sera pas initialisé.');
    }
});