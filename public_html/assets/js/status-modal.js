/**
 * Module de gestion du modal de changement de statut
 */
const StatusModal = {
    // Éléments DOM
    elements: {
        modal: null,
        container: null,
        repairIdInput: null,
        categoryIdInput: null,
        title: null
    },

    // Configuration
    config: {
        apiUrl: 'ajax/get_statuts_by_category.php',
        updateApiUrl: 'ajax/update_repair_status.php'
    },

    /**
     * Initialise le module
     */
    init() {
        // Récupérer les éléments du DOM
        this.elements.modal = document.getElementById('chooseStatusModal');
        
        if (!this.elements.modal) {
            this.createModal();
        }
        
        this.elements.container = document.getElementById('statusButtonsContainer');
        this.elements.title = document.getElementById('chooseStatusModalLabel');
        this.elements.repairIdInput = document.getElementById('chooseStatusRepairId');
        this.elements.categoryIdInput = document.getElementById('chooseStatusCategoryId');

        console.log('StatusModal initialisé');
        
        // Assurer que tous les modals peuvent être cliqués
        document.addEventListener('show.bs.modal', function(event) {
            // Désactiver les événements pointer-events sur tous les backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.style.pointerEvents = 'none';
            });
            
            // Activer les événements pointer sur le modal actuel
            event.target.style.pointerEvents = 'auto';
            
            // S'assurer que le dialogue du modal est cliquable
            const modalDialog = event.target.querySelector('.modal-dialog');
            if (modalDialog) {
                modalDialog.style.pointerEvents = 'auto';
            }
        });
    },

    /**
     * Crée le modal s'il n'existe pas
     */
    createModal() {
        const modalHTML = `
            <div class="modal fade" id="chooseStatusModal" tabindex="-1" aria-labelledby="chooseStatusModalLabel" aria-hidden="true" style="z-index: 1060;">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="chooseStatusModalLabel">
                                <i class="fas fa-tasks me-2"></i>
                                Choisir un statut
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-center mb-4">
                                Veuillez sélectionner le statut pour cette réparation
                            </p>
                            <div id="statusButtonsContainer" class="d-flex flex-column gap-2">
                                <!-- Les boutons de statut seront générés dynamiquement ici -->
                            </div>
                            
                            <input type="hidden" id="chooseStatusRepairId" value="">
                            <input type="hidden" id="chooseStatusCategoryId" value="">
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.elements.modal = document.getElementById('chooseStatusModal');
    },

    /**
     * Ouvre le modal pour sélectionner un statut
     * @param {string} repairId - ID de la réparation
     */
    show(repairId) {
        if (!repairId) {
            console.error('ID de réparation non spécifié');
            return;
        }
        
        console.log('Ouverture du modal de statut pour la réparation', repairId);
        
        // Afficher le loader
        if (this.elements.container) {
            this.elements.container.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2 text-muted">Chargement des statuts...</p>
                </div>
            `;
        }
        
        // Stocker l'ID de la réparation
        if (this.elements.repairIdInput) {
            this.elements.repairIdInput.value = repairId;
        }
        
        // Récupérer l'ID du magasin
        let shopId = null;
        if (typeof SessionHelper !== 'undefined' && SessionHelper.getShopId) {
            shopId = SessionHelper.getShopId();
        } else if (localStorage.getItem('shop_id')) {
            shopId = localStorage.getItem('shop_id');
        } else if (document.body.hasAttribute('data-shop-id')) {
            shopId = document.body.getAttribute('data-shop-id');
        }
        
        // Mettre à jour le titre
        if (this.elements.title) {
            this.elements.title.innerHTML = `<i class="fas fa-tasks me-2"></i> Sélectionner un statut`;
        }
        
        // S'assurer que le modal est bien positionné au-dessus des autres modals
        if (this.elements.modal) {
            // Ajouter un z-index élevé pour garantir que le modal est au-dessus des autres
            this.elements.modal.style.zIndex = "1060";
            
            // Rendre le modal et son contenu cliquable
            this.elements.modal.style.pointerEvents = "auto";
            const dialog = this.elements.modal.querySelector('.modal-dialog');
            if (dialog) {
                dialog.style.pointerEvents = "auto";
            }
            
            // S'assurer également que le backdrop est au bon niveau
            document.addEventListener('shown.bs.modal', function handleModalShown(event) {
                if (event.target.id === 'chooseStatusModal') {
                    // Trouver tous les backdrops et ajuster leurs z-index
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    if (backdrops.length > 0) {
                        backdrops.forEach((backdrop, index) => {
                            // Désactiver les événements pointer sur tous les backdrops
                            backdrop.style.pointerEvents = 'none';
                            // Le dernier backdrop doit avoir le z-index le plus élevé
                            if (index === backdrops.length - 1) {
                                backdrop.style.zIndex = "1059";
                            } else {
                                backdrop.style.zIndex = "1040";
                            }
                        });
                    }
                    // Supprimer cet écouteur après usage
                    document.removeEventListener('shown.bs.modal', handleModalShown);
                }
            });
        }
        
        // Créer l'URL de chargement des statuts avec l'ID du magasin si disponible
        let apiUrl = 'ajax/get_all_statuts.php';
        if (shopId) {
            apiUrl += `?shop_id=${shopId}`;
            console.log("URL de l'API avec ID du magasin:", apiUrl);
        }
        
        // Charger tous les statuts disponibles
        fetch(apiUrl)
            .then(response => {
                console.log("Réponse statut:", response.status);
                return response.json();
            })
            .then(data => {
                console.log("Données de réponse des statuts:", data);
                if (data.success && data.statuts) {
                    this.renderAllStatusButtons(data.statuts, repairId);
                } else {
                    this.showError('Erreur lors du chargement des statuts: ' + (data.error || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                this.showError('Erreur de communication avec le serveur');
            });
        
        // Afficher le modal
        const modal = bootstrap.Modal.getOrCreateInstance(this.elements.modal);
        modal.show();
    },

    /**
     * Affiche les boutons pour tous les statuts disponibles
     * @param {Array} statusGroups - Groupes de statuts organisés par catégorie
     * @param {string} repairId - ID de la réparation
     */
    renderAllStatusButtons(statusGroups, repairId) {
        if (!this.elements.container) return;
        
        this.elements.container.innerHTML = '';

        // Créer une section pour chaque catégorie de statut
        Object.entries(statusGroups).forEach(([categoryCode, categoryData]) => {
            // Créer un titre pour la catégorie
            const categoryTitle = document.createElement('h6');
            categoryTitle.className = `bg-${categoryData.couleur || 'secondary'} text-white p-2 rounded mb-2`;
            categoryTitle.innerHTML = `<i class="fas fa-${this.getCategoryIcon(categoryCode)} me-2"></i> ${categoryData.nom}`;
            this.elements.container.appendChild(categoryTitle);
            
            // Créer un bouton pour chaque statut de cette catégorie
            categoryData.statuts.forEach(statut => {
                const button = document.createElement('button');
                button.className = `btn btn-outline-${categoryData.couleur || 'secondary'} w-100 mb-2 text-start`;
                button.setAttribute('data-status-id', statut.id);
                button.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    ${statut.nom}
                `;
                
                button.addEventListener('click', () => {
                    this.updateStatus(repairId, statut.id);
                });
                
                this.elements.container.appendChild(button);
            });
            
            // Ajouter un séparateur après chaque catégorie (sauf la dernière)
            this.elements.container.appendChild(document.createElement('hr'));
        });
        
        // Supprimer le dernier séparateur
        const separators = this.elements.container.querySelectorAll('hr');
        if (separators.length > 0) {
            separators[separators.length - 1].remove();
        }
    },

    /**
     * Retourne l'icône à utiliser pour une catégorie
     * @param {string} categoryCode - Code de la catégorie
     * @returns {string} - Nom de l'icône FontAwesome
     */
    getCategoryIcon(categoryCode) {
        const icons = {
            'nouvelle': 'bell',
            'en_cours': 'tools',
            'en_attente': 'clock',
            'termine': 'check-circle',
            'annule': 'times-circle',
            'archive': 'archive'
        };
        
        return icons[categoryCode] || 'circle';
    },

    /**
     * Met à jour le statut d'une réparation
     * @param {string} repairId - ID de la réparation
     * @param {string} statusId - ID du statut
     */
    updateStatus(repairId, statusId) {
        // Afficher un indicateur de chargement
        this.elements.container.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Mise à jour...</span>
                </div>
                <p class="mt-2 text-muted">Mise à jour du statut...</p>
            </div>
        `;
        
        // Récupérer l'ID du magasin
        let shopId = null;
        if (typeof SessionHelper !== 'undefined' && SessionHelper.getShopId) {
            shopId = SessionHelper.getShopId();
        } else if (localStorage.getItem('shop_id')) {
            shopId = localStorage.getItem('shop_id');
        } else if (document.body.hasAttribute('data-shop-id')) {
            shopId = document.body.getAttribute('data-shop-id');
        }
        
        // Récupérer l'état d'envoi de SMS
        const sendSms = document.getElementById('sendSmsSwitch') ? document.getElementById('sendSmsSwitch').value === '1' : false;
        console.log('Envoi de SMS:', sendSms ? 'Activé' : 'Désactivé');
        
        // Préparer les données
        const data = {
            repair_id: repairId,
            status_id: statusId,
            send_sms: sendSms
        };
        
        // Ajouter l'ID du magasin s'il est disponible
        if (shopId) {
            data.shop_id = shopId;
            console.log("ID du magasin ajouté à la requête:", shopId);
        }
        
        console.log("Données à envoyer:", data);
        
        // Envoyer la requête
        fetch(this.config.updateApiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log("Réponse statut:", response.status);
            return response.json();
        })
        .then(data => {
            console.log("Données de réponse:", data);
            if (data.success) {
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(this.elements.modal);
                modal.hide();
                
                // Afficher une notification de succès
                alert('Statut mis à jour avec succès');
                
                // Recharger la page pour voir les modifications
                window.location.reload();
            } else {
                this.showError('Erreur lors de la mise à jour du statut: ' + (data.error || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            this.showError('Erreur de communication avec le serveur');
        });
    },

    /**
     * Affiche une erreur dans le conteneur
     * @param {string} message - Message d'erreur
     */
    showError(message) {
        if (!this.elements.container) return;
        
        this.elements.container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        `;
    }
};

// Initialiser le module au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    StatusModal.init();
    
    // Exposer le module à la portée globale pour qu'il puisse être utilisé par d'autres scripts
    window.statusModal = StatusModal;
}); 