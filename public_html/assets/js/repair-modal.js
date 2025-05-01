/**
 * Module de gestion du modal des réparations
 */
window.RepairModal = window.RepairModal || {
    // Éléments DOM
    elements: {
        modal: null,
        detailsContainer: null,
        loader: null
    },

    // Configuration
    config: {
        apiUrl: 'ajax/get_repair_details.php',
    },
    
    // Flag d'initialisation
    _isInitialized: false,

    /**
     * Initialise le module
     */
    init() {
        // Vérifier si déjà initialisé
        if (this._isInitialized) {
            console.log('RepairModal déjà initialisé, initialisation ignorée');
            return;
        }
        
        // Récupérer les éléments
        this.elements.modal = document.getElementById('repairDetailsModal');
        this.elements.detailsContainer = document.getElementById('repairDetailsContent');
        this.elements.loader = document.getElementById('repairDetailsLoader');
        
        if (!this.elements.modal || !this.elements.detailsContainer || !this.elements.loader) {
            console.error('Éléments du modal de réparations manquants');
            return;
        }
        
        // Ajouter les écouteurs d'événements pour les boutons de détails
        document.querySelectorAll('.view-repair-details').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const repairId = button.getAttribute('data-id');
                if (repairId) {
                    this.loadRepairDetails(repairId);
                }
            });
        });
        
        // Écouter les événements de clic sur les cartes réparation
        document.querySelectorAll('.repair-card, .draggable-card').forEach(card => {
            card.addEventListener('click', (e) => {
                // Ne pas déclencher si on clique sur un bouton
                if (e.target.closest('button, a')) return;
                
                const repairId = card.getAttribute('data-repair-id');
                if (repairId) {
                    this.loadRepairDetails(repairId);
                }
            });
        });
        
        // Initialiser les écouteurs pour les actions du modal
        this.initModalActions();
        
        // Marquer comme initialisé
        this._isInitialized = true;
        
        console.log('RepairModal initialisé avec succès');
    },

    /**
     * Charge les détails d'une réparation
     * @param {string} repairId - ID de la réparation
     */
    loadRepairDetails(repairId) {
        // Afficher le loader
        this.showLoader();
        
        // Vérifier si bootstrap est défini
        if (typeof bootstrap === 'undefined') {
            console.log('Bootstrap non défini, chargement dynamique...');
            // Créer un élément script pour charger bootstrap
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js';
            script.onload = () => {
                console.log('Bootstrap chargé avec succès');
                // Continuer avec l'ouverture du modal une fois Bootstrap chargé
                this.showModal(repairId);
            };
            script.onerror = () => {
                console.error('Erreur lors du chargement de Bootstrap');
                alert('Erreur lors du chargement des ressources nécessaires. Veuillez rafraîchir la page.');
            };
            document.head.appendChild(script);
        } else {
            // Bootstrap est déjà défini, ouvrir directement le modal
            this.showModal(repairId);
        }
    },

    /**
     * Affiche le modal et charge les détails de la réparation
     * @param {string} repairId - ID de la réparation
     */
    showModal(repairId) {
        // Ouvrir le modal
        const modal = bootstrap.Modal.getOrCreateInstance(this.elements.modal);
        modal.show();
        
        console.log('Chargement des détails pour la réparation ID:', repairId);
        console.log('URL de l\'API:', this.config.apiUrl);
        
        // Récupérer les données
        fetch(`${this.config.apiUrl}?id=${repairId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP ${response.status}`);
                }
                
                // Vérifier si la réponse est du JSON valide
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Réponse non-JSON reçue:', text);
                        throw new Error('La réponse n\'est pas au format JSON');
                    });
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Données reçues:', data);
                
                if (!data.success) {
                    throw new Error(data.error || 'Erreur lors du chargement des détails');
                }
                
                // Mettre à jour le titre du modal avec l'ID de la réparation
                document.getElementById('repairDetailsModalLabel').innerHTML = `
                    <i class="fas fa-tools me-2 text-primary"></i>
                    Réparation #${repairId}
                `;
                
                // Afficher les détails
                this.renderRepairDetails(data);
            })
            .catch(error => {
                console.error('Erreur lors du chargement des détails:', error);
                this.showError(`Erreur lors du chargement des détails: ${error.message}`);
            });
    },

    /**
     * Affiche les détails de la réparation dans le modal
     * @param {Object} data - Données de la réparation
     */
    renderRepairDetails(data) {
        const repair = data.repair;
        const photos = data.photos || [];
        const pieces = data.pieces || [];
        const logs = data.logs || [];
        
        console.log('[RepairModal] Rendering details. Repair data:', repair); // Log repair data
        console.log('[RepairModal] Photos data:', photos); // Log photos data
        console.log('[RepairModal] Mot de passe:', repair.mot_de_passe); // Déboguer le mot de passe

        // Vérifier si l'appareil a une photo et l'ajouter au début des photos s'il y en a une
        let appareilPhoto = null;
        if (repair.photo_appareil) {
            console.log('[RepairModal] Found photo_appareil:', repair.photo_appareil); // Log if found
            appareilPhoto = {
                id: 'appareil-' + repair.id,
                url: repair.photo_appareil,
                chemin: repair.photo_appareil,
                description: 'Photo de l\'appareil ' + repair.type_appareil + ' ' + repair.marque + ' ' + repair.modele,
                is_device_photo: true
            };
            console.log('[RepairModal] Created appareilPhoto object:', appareilPhoto); // Log created object
        } else {
            console.log('[RepairModal] No photo_appareil found in repair data.'); // Log if not found
        }
        
        // Traiter les photos pour s'assurer qu'elles ont une URL valide
        const processedPhotos = photos.map(photo => {
            // Vérifier si la photo a une URL valide
            const photoUrl = photo.url || photo.chemin || '';
            console.log(`[RepairModal] Processing photo ID: ${photo.id}, URL: ${photoUrl}`);
            
            // Si l'URL ne commence pas par http:// ou https:// ou /, on ajoute un / au début
            let finalUrl = photoUrl;
            if (photoUrl && !photoUrl.startsWith('http://') && !photoUrl.startsWith('https://') && !photoUrl.startsWith('/')) {
                finalUrl = '/' + photoUrl;
                console.log(`[RepairModal] Adding leading slash to URL: ${finalUrl}`);
            }
            
            return {
                ...photo,
                url: finalUrl,
                description: photo.description || 'Photo'
            };
        });
        
        // Stocker l'ID de la réparation dans le modal
        this.elements.modal.setAttribute('data-repair-id', repair.id);
        
        // Générer le contenu HTML
        let html = `
            <div class="row g-4">
                <!-- Actions -->
                <div class="col-12 mb-2">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-2">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-cogs text-primary me-2"></i>
                                Actions
                            </h5>
                        </div>
                        <div class="card-body pb-0">
                            <div class="action-buttons">
                                <!-- Première ligne de 3 boutons -->
                                <div class="col-4">
                                    <button class="btn btn-outline-primary w-100 action-btn" data-action="devis">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                        <span class="d-block small mt-1">ENVOYER UN DEVIS</span>
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button class="btn btn-outline-success w-100 action-btn" data-action="status">
                                        <i class="fas fa-tasks"></i>
                                        <span class="d-block small mt-1">STATUT</span>
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button class="btn btn-outline-warning w-100 action-btn" data-action="price">
                                        <i class="fas fa-euro-sign"></i>
                                        <span class="d-block small mt-1">PRIX</span>
                                    </button>
                                </div>
                                
                                <!-- Deuxième ligne de 3 boutons -->
                                <div class="col-4">
                                    <button class="btn btn-outline-info w-100 action-btn" data-action="order">
                                        <i class="fas fa-shopping-cart"></i>
                                        <span class="d-block small mt-1">COMMANDER</span>
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button class="btn ${repair.urgent == 1 ? 'btn-danger' : 'btn-outline-danger'} w-100 action-btn toggle-urgent-btn" data-urgent="${repair.urgent == 1 ? '1' : '0'}">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span class="d-block small mt-1">URGENT</span>
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button class="btn btn-outline-secondary w-100 action-btn" data-action="print">
                                        <i class="fas fa-print"></i>
                                        <span class="d-block small mt-1">IMPRIMER</span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Ligne pour les boutons client juste en dessous -->
                            <div class="client-action-buttons mt-2">
                                <div class="col-4">
                                    <a href="tel:${repair.client_telephone}" class="btn btn-outline-success w-100 client-action-btn">
                                        <i class="fas fa-phone-alt"></i>
                                        <span class="d-block small mt-1">APPEL</span>
                                    </a>
                                </div>
                                <div class="col-4">
                                    <button class="btn btn-outline-primary w-100 client-action-btn send-sms-btn" 
                                            data-client-id="${repair.client_id}"
                                            data-client-nom="${repair.client_nom}"
                                            data-client-prenom="${repair.client_prenom}"
                                            data-client-tel="${repair.client_telephone}">
                                        <i class="fas fa-comment-alt"></i>
                                        <span class="d-block small mt-1">SMS</span>
                                    </button>
                                </div>
                                <div class="col-4">
                                    <a href="index.php?page=clients&id=${repair.client_id}" class="btn btn-outline-info w-100 client-action-btn">
                                        <i class="fas fa-user"></i>
                                        <span class="d-block small mt-1">DÉTAILS</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations client et appareil -->
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-circle text-primary me-2"></i>
                                Client
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="contact-info">
                                <div class="contact-info-item small">
                                    <i class="fas fa-user text-primary"></i>
                                    <div>
                                        <div class="fw-medium">${repair.client_nom} ${repair.client_prenom}</div>
                                    </div>
                                </div>
                                
                                <div class="contact-info-item small">
                                    <i class="fas fa-phone-alt text-success"></i>
                                    <div>
                                        <a href="tel:${repair.client_telephone}" class="text-decoration-none">
                                            ${repair.client_telephone}
                                        </a>
                                    </div>
                                </div>
                                
                                ${repair.client_email ? `
                                <div class="contact-info-item small">
                                    <i class="fas fa-envelope text-primary"></i>
                                    <div>
                                        <a href="mailto:${repair.client_email}" class="text-decoration-none">
                                            ${repair.client_email}
                                        </a>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <div class="contact-info-item small">
                                    <i class="fas fa-calendar-alt text-primary"></i>
                                    <div>
                                        <div>
                                            <span class="fw-medium">Date:</span> ${repair.date_reception || 'Non spécifiée'}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="contact-info-item small">
                                    <i class="fas fa-tasks ${repair.statut_couleur ? 'text-'+repair.statut_couleur : 'text-secondary'}"></i>
                                    <div>
                                        <div>
                                            <span class="fw-medium">Statut:</span> 
                                            ${repair.statut_nom || repair.statut}
                                            ${repair.urgent == 1 ? '<span class="badge bg-danger ms-2">URGENT</span>' : ''}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="contact-info-item small">
                                    <i class="fas fa-euro-sign text-success"></i>
                                    <div>
                                        <div>
                                            <span class="fw-medium">Prix:</span> <span class="price-value clickable" data-repair-id="${repair.id}" style="cursor: pointer;">${repair.prix_reparation_formatte ? repair.prix_reparation_formatte + ' €' : 'Non spécifié'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3 d-flex justify-content-center gap-2">
                                <a href="tel:${repair.client_telephone}" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-phone-alt"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-primary send-sms-btn" 
                                        data-client-id="${repair.client_id}"
                                        data-client-nom="${repair.client_nom}"
                                        data-client-prenom="${repair.client_prenom}"
                                        data-client-tel="${repair.client_telephone}">
                                    <i class="fas fa-comment-alt"></i>
                                </button>
                                <a href="index.php?page=clients&id=${repair.client_id}" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-user"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card h-100 border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-mobile-alt text-primary me-2"></i>
                                Appareil
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="device-info">
                                <div class="device-info-item">
                                    <div class="device-info-label">Type</div>
                                    <div class="device-info-value">${repair.type_appareil || 'Non spécifié'}</div>
                                </div>
                                
                                <div class="device-info-item">
                                    <div class="device-info-label">Marque/Modèle</div>
                                    <div class="device-info-value">${repair.marque || 'Non spécifiée'} ${repair.modele || ''}</div>
                                </div>
                                
                                ${repair.mot_de_passe ? `
                                <div class="device-info-item" style="background-color: #f8f9fa; padding: 8px; border-radius: 6px; margin-top: 10px; margin-bottom: 10px; border-left: 4px solid #6c757d;">
                                    <div class="device-info-label"><i class="fas fa-key me-2"></i>Mot de passe</div>
                                    <div class="device-info-value">${repair.mot_de_passe}</div>
                                </div>
                                ` : ''}
                                
                                <div class="device-info-item">
                                    <div class="device-info-label">Problème</div>
                                    <div class="device-info-value small problem-description">
                                        ${repair.description_probleme || 'Non spécifié'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notes techniques -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clipboard-list text-primary me-2"></i>
                                Notes techniques
                            </h5>
                            <button class="btn btn-sm btn-outline-primary edit-notes-btn">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <div class="card-body py-2">
                            <div class="technical-notes small">
                                ${repair.notes_techniques 
                                    ? repair.notes_techniques.replace(/\\n/g, '<br>') 
                                    : '<p class="text-muted">Aucune note technique</p>'}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Photos -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-images text-primary me-2"></i>
                                Photos ${(appareilPhoto || processedPhotos.length > 0) ? `(${processedPhotos.length + (appareilPhoto ? 1 : 0)})` : ''}
                            </h5>
                            <button class="btn btn-sm btn-outline-primary add-photo-btn">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        <div class="card-body py-2">
                            ${(appareilPhoto || processedPhotos.length > 0) ? `
                            <div class="row g-2 photo-gallery">
                                ${appareilPhoto ? `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="photo-item photo-appareil">
                                        <div class="badge-appareil">Appareil</div>
                                        <img src="${appareilPhoto.url}" alt="${appareilPhoto.description}" class="img-fluid rounded">
                                        <div class="photo-overlay">
                                            <div class="photo-actions">
                                                <button class="btn btn-sm btn-light view-photo-btn" data-photo-id="${appareilPhoto.id}">
                                                    <i class="fas fa-search-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                ${processedPhotos.map(photo => `
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="photo-item">
                                        <img src="${photo.url}" alt="${photo.description}" class="img-fluid rounded">
                                        <div class="photo-overlay">
                                            <div class="photo-actions">
                                                <button class="btn btn-sm btn-light view-photo-btn" data-photo-id="${photo.id}">
                                                    <i class="fas fa-search-plus"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-photo-btn" data-photo-id="${photo.id}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                `).join('')}
                            </div>
                            ` : `
                            <div class="text-center py-3">
                                <div class="empty-state">
                                    <i class="fas fa-camera text-muted fa-2x mb-2"></i>
                                    <p class="text-muted small">Aucune photo disponible</p>
                                </div>
                            </div>
                            `}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Injecter le HTML
        this.elements.detailsContainer.innerHTML = html;
        
        // Cacher le loader et afficher le contenu
        this.hideLoader();
        
        // Initialiser les comportements spécifiques
        this.initRepairDetailsActions();
    },

    /**
     * Initialise les actions du modal
     */
    initModalActions() {
        if (!this.elements.modal) return;
        
        // Réinitialiser à la fermeture du modal
        this.elements.modal.addEventListener('hidden.bs.modal', () => {
            this.elements.detailsContainer.innerHTML = '';
        });
    },

    /**
     * Initialise les actions spécifiques aux détails d'une réparation
     */
    initRepairDetailsActions() {
        const repairId = this.elements.modal.getAttribute('data-repair-id');
        if (!repairId) return;
        
        // Bouton d'envoi de SMS
        document.querySelectorAll('.send-sms-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const clientId = btn.getAttribute('data-client-id');
                const clientNom = btn.getAttribute('data-client-nom');
                const clientPrenom = btn.getAttribute('data-client-prenom');
                const clientTel = btn.getAttribute('data-client-tel');
                if (window.openSmsModal && clientId) {
                    window.openSmsModal(clientId, clientNom, clientPrenom, clientTel);
                }
            });
        });
        
        // Bouton de modification des prix
        document.querySelectorAll('.price-value.clickable').forEach(element => {
            element.addEventListener('click', () => {
                // Récupérer le prix actuel (sans le symbole €)
                let currentPrice = element.textContent.trim().replace(' €', '');
                if (currentPrice === 'Non spécifié') currentPrice = '0';
                
                // Ouvrir le modal de clavier numérique
                if (window.priceModal) {
                    window.priceModal.show(repairId, currentPrice);
                }
            });
        });
        
        // Bouton de modification des notes
        document.querySelectorAll('.edit-notes-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Récupérer les notes techniques depuis l'élément DOM
                const technicalNotesElement = document.querySelector('.technical-notes');
                let currentNotes = '';
                
                if (technicalNotesElement) {
                    // Récupérer le contenu HTML
                    const htmlContent = technicalNotesElement.innerHTML;
                    
                    // Si le contenu contient un message indiquant qu'il n'y a pas de notes
                    if (htmlContent.includes('Aucune note technique')) {
                        currentNotes = '';
                    } else {
                        // Sinon, extraire le texte et remplacer les <br> par des sauts de ligne
                        currentNotes = htmlContent.replace(/<br\s*\/?>/gi, '\n').trim();
                    }
                }
                
                // Ouvrir le modal des notes
                this.openNotesModal(repairId, currentNotes);
            });
        });
        
        // Bouton d'ajout de photo
        document.querySelectorAll('.add-photo-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Ouvrir le modal d'ajout de photo
                console.log('Ajouter une photo pour la réparation', repairId);
                this.openPhotoModal(repairId);
            });
        });
        
        // Bouton urgent
        document.querySelectorAll('.toggle-urgent-btn').forEach(btn => {
            const isUrgent = btn.getAttribute('data-urgent') === '1';
            
            // Mettre à jour l'apparence du bouton
            if (isUrgent) {
                btn.classList.remove('btn-outline-danger');
                btn.classList.add('btn-danger');
            }
            
            btn.addEventListener('click', () => {
                const currentState = btn.getAttribute('data-urgent') === '1';
                const newState = !currentState;
                
                // Désactiver le bouton pendant l'envoi
                btn.disabled = true;
                
                // Préparer les données à envoyer
                const formData = new FormData();
                formData.append('repair_id', repairId);
                formData.append('urgent', newState ? 1 : 0);
                
                // Effectuer la requête AJAX
                fetch('ajax/toggle_repair_urgent.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Réactiver le bouton
                    btn.disabled = false;
                    
                    if (data.success) {
                        // Mettre à jour l'apparence du bouton
                        if (newState) {
                            btn.classList.remove('btn-outline-danger');
                            btn.classList.add('btn-danger');
                        } else {
                            btn.classList.remove('btn-danger');
                            btn.classList.add('btn-outline-danger');
                        }
                        
                        // Mettre à jour l'attribut data-urgent
                        btn.setAttribute('data-urgent', newState ? '1' : '0');
                        
                        // Mettre à jour le badge urgent dans les infos du client
                        const statusElements = document.querySelectorAll('.contact-info-item .fw-medium');
                        let statusElement = null;
                        
                        // Trouver l'élément qui contient le texte "Statut:"
                        for (const elem of statusElements) {
                            if (elem.textContent.includes('Statut:')) {
                                statusElement = elem.parentNode;
                                break;
                            }
                        }
                        
                        if (statusElement) {
                            const badgeElement = statusElement.querySelector('.badge.bg-danger');
                            if (newState && !badgeElement) {
                                // Ajouter le badge
                                statusElement.innerHTML += ' <span class="badge bg-danger ms-2">URGENT</span>';
                            } else if (!newState && badgeElement) {
                                // Supprimer le badge
                                badgeElement.remove();
                            }
                        }
                        
                        // Notification
                        alert(newState ? 'Réparation marquée comme urgente' : 'État urgent supprimé');
                    } else {
                        alert('Erreur lors de la mise à jour: ' + (data.error || 'Erreur inconnue'));
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la mise à jour de l\'état urgent:', error);
                    btn.disabled = false;
                    alert('Erreur de connexion: ' + error.message);
                });
            });
        });
        
        // Boutons d'action
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const action = btn.getAttribute('data-action');
                if (!action) return;
                
                // Exécuter l'action
                this.executeAction(action, repairId);
            });
        });
        
        // Initialiser les écouteurs d'événements pour les boutons d'action client
        document.querySelectorAll('.client-action-btn.send-sms-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const clientId = btn.getAttribute('data-client-id');
                const clientNom = btn.getAttribute('data-client-nom');
                const clientPrenom = btn.getAttribute('data-client-prenom');
                const clientTel = btn.getAttribute('data-client-tel');
                if (window.openSmsModal && clientId) {
                    window.openSmsModal(clientId, clientNom, clientPrenom, clientTel);
                }
            });
        });
    },

    /**
     * Exécute une action sur une réparation
     * @param {string} action - Action à exécuter
     * @param {string} repairId - ID de la réparation
     */
    executeAction(action, repairId) {
        console.log(`Exécution de l'action ${action} pour la réparation ${repairId}`);
        
        switch (action) {
            case 'devis':
                // Récupérer les détails de la réparation pour obtenir le prix
                fetch(`ajax/get_repair_details.php?id=${repairId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.repair) {
                            const repair = data.repair;
                            const prix = repair.prix_reparation || '0';
                            
                            // Créer une boîte de dialogue personnalisée
                            const dialogHTML = `
                                <div class="modal fade" id="devisConfirmModal" tabindex="-1" aria-labelledby="devisConfirmModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary text-white">
                                                <h5 class="modal-title" id="devisConfirmModalLabel">
                                                    <i class="fas fa-paper-plane me-2"></i>Envoi du devis par SMS
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                            </div>
                                            <div class="modal-body pb-0">
                                                <div class="mb-4">
                                                    <label for="prixDevis" class="form-label fw-bold fs-5">Montant du devis :</label>
                                                    <div class="input-group input-group-lg">
                                                        <input type="number" class="form-control form-control-lg" id="prixDevis" value="${prix}" step="0.01" min="0">
                                                        <span class="input-group-text">€</span>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-4">
                                                    <label class="form-label fw-bold fs-5">Type de message :</label>
                                                    <div class="message-options-container d-flex gap-3">
                                                        <div class="form-check p-0 message-type-option flex-grow-1 active" id="simpleMessageOption">
                                                            <label class="w-100 d-flex flex-column align-items-center justify-content-center p-3 text-center">
                                                                <input class="form-check-input mb-2" type="radio" name="typeMessage" id="messageSimple" value="simple" checked>
                                                                <i class="fas fa-comment-dollar fs-3 mb-2 option-icon"></i>
                                                                <strong class="d-block mb-1">Message simple</strong>
                                                                <span class="text-muted small">Prix uniquement</span>
                                                            </label>
                                                        </div>
                                                        <div class="form-check p-0 message-type-option flex-grow-1" id="detailleMessageOption">
                                                            <label class="w-100 d-flex flex-column align-items-center justify-content-center p-3 text-center">
                                                                <input class="form-check-input mb-2" type="radio" name="typeMessage" id="messageDetaille" value="detaille">
                                                                <i class="fas fa-comment-dots fs-3 mb-2 option-icon"></i>
                                                                <strong class="d-block mb-1">Message détaillé</strong>
                                                                <span class="text-muted small">Avec notes techniques</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div id="notesTechniquesSection" class="mb-4 d-none">
                                                    <label for="notesTechniques" class="form-label fw-bold fs-5">Notes techniques à inclure :</label>
                                                    <div class="text-muted mb-2 example-text">
                                                        <small><i>Exemple : Remplacement Controlleur HS - 89 euro</i></small>
                                                    </div>
                                                    <textarea class="form-control notes-techniques" id="notesTechniques" rows="4" placeholder="Exemple:
Carte Mere HS
Remplacement du Micro-Controlleur 89 euro">${repair.notes_techniques || ''}</textarea>
                                                    <small class="text-muted mt-2 d-block notes-help">Ces notes seront ajoutées à la fin du SMS.</small>
                                                </div>
                                                
                                                <div class="alert alert-warning text-dark border border-warning mb-4" style="background-color: rgba(255, 193, 7, 0.15);">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <span>Le statut de la réparation sera changé à "En attente d'accord client".</span>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-lg btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <button type="button" class="btn btn-lg btn-primary" id="confirmerDevisBtn">
                                                    <i class="fas fa-paper-plane me-2"></i>Envoyer le devis
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            // Ajouter la boîte de dialogue au DOM
                            const dialogContainer = document.createElement('div');
                            dialogContainer.innerHTML = dialogHTML;
                            document.body.appendChild(dialogContainer);
                            
                            // Afficher la boîte de dialogue
                            const devisModal = new bootstrap.Modal(document.getElementById('devisConfirmModal'));
                            devisModal.show();
                            
                            // Gérer l'affichage des notes techniques en fonction du choix
                            const messageType = document.querySelectorAll('input[name="typeMessage"]');
                            const notesTechniquesSection = document.getElementById('notesTechniquesSection');
                            const simpleOption = document.getElementById('simpleMessageOption');
                            const detailleOption = document.getElementById('detailleMessageOption');
                            
                            messageType.forEach(radio => {
                                radio.addEventListener('change', function() {
                                    if (this.value === 'detaille') {
                                        notesTechniquesSection.classList.remove('d-none');
                                        simpleOption.classList.remove('active');
                                        detailleOption.classList.add('active');
                                    } else {
                                        notesTechniquesSection.classList.add('d-none');
                                        simpleOption.classList.add('active');
                                        detailleOption.classList.remove('active');
                                    }
                                });
                            });
                            
                            // Ajouter des écouteurs d'événements pour les options entières (pas juste le radio)
                            simpleOption.addEventListener('click', function() {
                                document.getElementById('messageSimple').checked = true;
                                notesTechniquesSection.classList.add('d-none');
                                simpleOption.classList.add('active');
                                detailleOption.classList.remove('active');
                            });
                            
                            detailleOption.addEventListener('click', function() {
                                document.getElementById('messageDetaille').checked = true;
                                notesTechniquesSection.classList.remove('d-none');
                                simpleOption.classList.remove('active');
                                detailleOption.classList.add('active');
                            });
                            
                            // Gérer la soumission du formulaire
                            document.getElementById('confirmerDevisBtn').addEventListener('click', () => {
                                // Récupérer les valeurs du formulaire
                                const prix = document.getElementById('prixDevis').value;
                                const typeMessage = document.querySelector('input[name="typeMessage"]:checked').value;
                                const notesTechniques = document.getElementById('notesTechniques').value;
                                
                                // Fermer la boîte de dialogue
                                devisModal.hide();
                                
                                // Supprimer la boîte de dialogue du DOM après fermeture
                                document.getElementById('devisConfirmModal').addEventListener('hidden.bs.modal', function () {
                                    this.remove();
                                });
                                
                                // Préparer les données
                                const formData = new FormData();
                                formData.append('repair_id', repairId);
                                formData.append('sms_type', 4); // ID du SMS de devis
                                formData.append('prix', prix);
                                formData.append('type_message', typeMessage);
                                if (typeMessage === 'detaille') {
                                    formData.append('notes_techniques', notesTechniques);
                                }
                                
                                console.log("FormData préparée:", {
                                    repair_id: repairId, 
                                    sms_type: 4, 
                                    prix: prix,
                                    type_message: typeMessage,
                                    ...(typeMessage === 'detaille' ? {notes_techniques: notesTechniques} : {})
                                });
                                
                                // Désactiver les boutons d'action pendant l'envoi
                                document.querySelectorAll('.action-btn').forEach(btn => {
                                    btn.disabled = true;
                                });
                                
                                // Envoyer la requête
                                console.log("Envoi de la requête AJAX à send_devis_sms.php...");
                                
                                try {
                                    fetch('ajax/send_devis_sms.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => {
                                        console.log("Réponse reçue:", response.status, response.statusText);
                                        if (!response.ok) {
                                            throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
                                        }
                                        return response.json();
                                    })
                                    .then(data => {
                                        console.log("Données de réponse:", data);
                                        // Réactiver les boutons
                                        document.querySelectorAll('.action-btn').forEach(btn => {
                                            btn.disabled = false;
                                        });
                                        
                                        if (data.success) {
                                            console.log("Devis envoyé avec succès:", data);
                                            alert('Devis envoyé avec succès');
                                            // Rafraîchir la page pour voir les modifications
                                            window.location.reload();
                                        } else {
                                            console.error("Erreur d'envoi de devis:", data.error || data.message || "Erreur inconnue");
                                            alert('Erreur: ' + (data.error || data.message || 'Erreur lors de l\'envoi du devis'));
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Erreur lors de l\'envoi du devis:', error);
                                        // Réactiver les boutons
                                        document.querySelectorAll('.action-btn').forEach(btn => {
                                            btn.disabled = false;
                                        });
                                        alert('Erreur de connexion: ' + error.message);
                                    });
                                } catch (e) {
                                    console.error("Exception critique lors de l'envoi du devis:", e);
                                    // Réactiver les boutons même en cas d'erreur critique
                                    document.querySelectorAll('.action-btn').forEach(btn => {
                                        btn.disabled = false;
                                    });
                                    alert('Erreur critique: ' + e.message);
                                }
                            });
                        } else {
                            console.error('Erreur lors de la récupération des détails de la réparation');
                            alert('Erreur lors de la récupération des détails de la réparation');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        alert('Erreur de connexion: ' + error.message);
                    });
                break;
                
            case 'edit':
                // Rediriger vers la page de modification
                window.location.href = `index.php?page=modifier_reparation&id=${repairId}`;
                break;
                
            case 'status':
                // Ouvrir la modal de changement de statut
                if (window.statusModal) {
                    window.statusModal.show(repairId);
                }
                break;
                
            case 'price':
                // Ouvrir la modal de modification du prix
                if (window.priceModal) {
                    window.priceModal.show(repairId);
                }
                break;
                
            case 'order':
                // Ouvrir le modal de nouvelle commande de pièces qui est dans le footer
                const modalElement = document.getElementById('ajouterCommandeModal');
                if (modalElement) {
                    // Préparer le modal avec les infos de la réparation
                    this.prepareCommandeModal(repairId);
                    
                    // Afficher le modal
                    const commandeModal = new bootstrap.Modal(modalElement);
                    commandeModal.show();
                } else {
                    console.error("Modal de commande non trouvé dans le DOM");
                }
                break;
                
            case 'print':
                // Ouvrir la page d'impression d'étiquette au lieu de print_repair.php
                window.open(`index.php?page=imprimer_etiquette&id=${repairId}`, '_blank');
                break;
        }
    },

    /**
     * Prépare le modal de commande avec les informations de la réparation
     * @param {string} repairId - ID de la réparation
     */
    prepareCommandeModal(repairId) {
        // Récupérer les données de la réparation
        fetch(`ajax/get_repair_details.php?id=${repairId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.repair) {
                    const repair = data.repair;
                    
                    // Remplir le formulaire avec les données de la réparation
                    const reparationSelect = document.querySelector('#ajouterCommandeModal select[name="reparation_id"]');
                    const clientIdInput = document.querySelector('#ajouterCommandeModal #client_id');
                    const nomClientElement = document.querySelector('#ajouterCommandeModal #nom_client_selectionne');
                    const clientSelectElement = document.querySelector('#ajouterCommandeModal #client_selectionne');
                    
                    if (reparationSelect) {
                        // Trouver ou créer l'option pour cette réparation
                        let option = Array.from(reparationSelect.options).find(opt => opt.value === repairId);
                        
                        if (!option) {
                            option = document.createElement('option');
                            option.value = repairId;
                            option.text = `Réparation #${repairId} - ${repair.type_appareil} ${repair.marque} ${repair.modele}`;
                            reparationSelect.appendChild(option);
                        }
                        
                        // Sélectionner cette réparation
                        option.selected = true;
                        
                        // Déclencher l'événement change pour activer les éventuels listeners
                        const event = new Event('change');
                        reparationSelect.dispatchEvent(event);
                    }
                    
                    // Remplir les infos du client
                    if (clientIdInput && repair.client_id) {
                        clientIdInput.value = repair.client_id;
                    }
                    
                    if (nomClientElement && clientSelectElement && repair.client_nom && repair.client_prenom) {
                        nomClientElement.textContent = `${repair.client_prenom} ${repair.client_nom}`;
                        clientSelectElement.classList.remove('d-none');
                    }
                    
                    console.log('Modal de commande préparé avec les données de la réparation', repairId);
                } else {
                    console.error('Erreur lors de la récupération des détails de la réparation');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    },

    /**
     * Affiche le loader et cache le contenu
     */
    showLoader() {
        this.elements.loader.style.display = 'block';
        this.elements.detailsContainer.style.display = 'none';
    },

    /**
     * Cache le loader et affiche le contenu
     */
    hideLoader() {
        this.elements.loader.style.display = 'none';
        this.elements.detailsContainer.style.display = 'block';
    },

    /**
     * Affiche un message d'erreur
     * @param {string} message - Message d'erreur
     */
    showError(message) {
        this.elements.detailsContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        `;
        this.hideLoader();
    },
    
    /**
     * Ouvre le modal d'édition des notes techniques
     * @param {string} repairId - ID de la réparation
     * @param {string} currentNotes - Notes techniques actuelles
     */
    openNotesModal(repairId, currentNotes) {
        // Vérifier si le modal existe déjà
        let modal = document.getElementById('notesModal');
        
        // Si le modal n'existe pas, le créer
        if (!modal) {
            const modalHTML = `
                <div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="notesModalLabel">
                                    <i class="fas fa-clipboard-list me-2"></i>
                                    Modifier les notes techniques
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <form id="notesForm">
                                    <input type="hidden" id="notes_repair_id" name="repair_id">
                                    <div class="mb-3">
                                        <label for="notes_content" class="form-label">Notes techniques</label>
                                        <textarea class="form-control" id="notes_content" name="notes" rows="6" placeholder="Saisissez vos notes techniques ici..."></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-primary" id="saveNotesBtn">
                                    <i class="fas fa-save me-1"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Ajouter le modal au document
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            modal = document.getElementById('notesModal');
        }
        
        // Récupérer les éléments du modal
        const notesContent = document.getElementById('notes_content');
        const notesRepairId = document.getElementById('notes_repair_id');
        const saveBtn = document.getElementById('saveNotesBtn');
        
        // Remplir le formulaire avec les données existantes
        notesRepairId.value = repairId;
        notesContent.value = currentNotes;
        
        // Gérer l'événement de sauvegarde
        const saveHandler = () => {
            // Récupérer les données du formulaire
            const notes = notesContent.value;
            
            // Désactiver le bouton pendant l'envoi
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sauvegarde en cours...';
            
            // Envoyer les données via AJAX
            fetch('ajax/update_repair_notes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `repair_id=${repairId}&notes=${encodeURIComponent(notes)}`
            })
            .then(response => {
                // Vérifier si la réponse est de type JSON
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        console.error("Réponse non-JSON:", text);
                        throw new Error("La réponse n'est pas au format JSON");
                    });
                }
            })
            .then(data => {
                // Fermer le modal
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
                
                // Réactiver le bouton
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Enregistrer';
                
                // Afficher une notification avec alert au lieu de toastr
                if (data.success) {
                    alert('Notes techniques mises à jour avec succès');
                    
                    // Rafraîchir la page pour voir les modifications
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Erreur: ' + (data.message || 'Erreur lors de la mise à jour des notes'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Réactiver le bouton
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Enregistrer';
                
                // Utiliser alert au lieu de toastr
                alert('Erreur de connexion: ' + error.message);
            });
        };
        
        // Supprimer les anciens écouteurs d'événements si nécessaire
        saveBtn.removeEventListener('click', saveHandler);
        
        // Ajouter le nouvel écouteur d'événements
        saveBtn.addEventListener('click', saveHandler);
        
        // Afficher le modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    },

    /**
     * Ouvre le modal d'ajout de photo
     * @param {string} repairId - ID de la réparation
     */
    openPhotoModal(repairId) {
        // Vérifier si le modal existe déjà
        let modal = document.getElementById('photoModal');
        
        // Si le modal n'existe pas, le créer
        if (!modal) {
            const modalHTML = `
                <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="photoModalLabel">
                                    <i class="fas fa-camera me-2"></i>
                                    Ajouter une photo
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <form id="photoForm">
                                    <input type="hidden" id="photo_repair_id" name="repair_id" value="${repairId}">
                                    
                                    <!-- Zone de la caméra -->
                                    <div id="cameraContainer" class="text-center mb-4">
                                        <video id="cameraVideo" autoplay playsinline class="img-fluid rounded" style="max-height: 300px; background-color: #f8f9fa;"></video>
                                        <canvas id="cameraCanvas" class="d-none"></canvas>
                                    </div>
                                    
                                    <!-- Prévisualisation de la photo -->
                                    <div id="photoPreviewContainer" class="text-center mb-4 d-none">
                                        <div class="position-relative d-inline-block">
                                            <img id="photoPreviewImage" src="" alt="Prévisualisation" class="img-fluid rounded" style="max-height: 300px;">
                                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" id="retakePhotoBtn">
                                                <i class="fas fa-redo"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Description de la photo -->
                                    <div class="mb-3">
                                        <label for="photoDescription" class="form-label">Description (optionnelle)</label>
                                        <input type="text" class="form-control" id="photoDescription" name="description" placeholder="Description de la photo">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-primary" id="capturePhotoBtn">
                                    <i class="fas fa-camera me-1"></i> Prendre la photo
                                </button>
                                <button type="button" class="btn btn-success d-none" id="savePhotoBtn">
                                    <i class="fas fa-save me-1"></i> Enregistrer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Ajouter le modal au document
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            modal = document.getElementById('photoModal');
        }
        
        // Variables pour la gestion de la caméra
        let stream = null;
        let photoData = null;
        
        // Récupérer les éléments du modal
        const cameraContainer = document.getElementById('cameraContainer');
        const previewContainer = document.getElementById('photoPreviewContainer');
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        const previewImage = document.getElementById('photoPreviewImage');
        const retakeBtn = document.getElementById('retakePhotoBtn');
        const captureBtn = document.getElementById('capturePhotoBtn');
        const saveBtn = document.getElementById('savePhotoBtn');
        
        // Fonction pour démarrer la caméra
        const startCamera = async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'environment',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                
                video.srcObject = stream;
                cameraContainer.classList.remove('d-none');
                previewContainer.classList.add('d-none');
                captureBtn.classList.remove('d-none');
                saveBtn.classList.add('d-none');
                
            } catch (err) {
                console.error('Erreur d\'accès à la caméra:', err);
                alert('Impossible d\'accéder à la caméra: ' + err.message);
            }
        };
        
        // Fonction pour arrêter la caméra
        const stopCamera = () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        };
        
        // Fonction pour capturer une photo
        const capturePhoto = () => {
            // Configurer le canvas aux dimensions de la vidéo
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Dessiner l'image de la vidéo sur le canvas
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Récupérer les données de l'image
            photoData = canvas.toDataURL('image/jpeg');
            
            // Afficher la prévisualisation
            previewImage.src = photoData;
            cameraContainer.classList.add('d-none');
            previewContainer.classList.remove('d-none');
            captureBtn.classList.add('d-none');
            saveBtn.classList.remove('d-none');
        };
        
        // Fonction pour reprendre une photo
        const retakePhoto = () => {
            photoData = null;
            previewImage.src = '';
            
            cameraContainer.classList.remove('d-none');
            previewContainer.classList.add('d-none');
            captureBtn.classList.remove('d-none');
            saveBtn.classList.add('d-none');
        };
        
        // Fonction pour enregistrer la photo
        const savePhoto = () => {
            if (!photoData) {
                alert('Aucune photo à enregistrer');
                return;
            }
            
            const description = document.getElementById('photoDescription').value;
            
            // Désactiver le bouton pendant l'envoi
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enregistrement...';
            
            // Créer le formulaire à envoyer
            const formData = new FormData();
            formData.append('repair_id', repairId);
            formData.append('photo', photoData);
            formData.append('description', description);
            
            console.log('Envoi de la photo pour la réparation ID:', repairId);
            
            // Envoyer la requête
            fetch('ajax/upload_repair_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Vérifier si la réponse est de type JSON
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return response.json();
                } else {
                    return response.text().then(text => {
                        console.error("Réponse non-JSON:", text);
                        throw new Error("La réponse n'est pas au format JSON");
                    });
                }
            })
            .then(data => {
                console.log('Réponse du serveur:', data);
                
                // Fermer le modal
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
                
                // Arrêter la caméra
                stopCamera();
                
                // Afficher une notification
                if (data.success) {
                    // Utiliser alert au lieu de toastr pour éviter les erreurs
                    alert('Photo ajoutée avec succès');
                    
                    // Rafraîchir la page pour voir les modifications
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Erreur: ' + (data.message || 'Erreur lors de l\'ajout de la photo'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Réactiver le bouton
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Enregistrer';
                
                // Utiliser alert au lieu de toastr
                alert('Erreur de connexion: ' + error.message);
            });
        };
        
        // Configurer les écouteurs d'événements
        captureBtn.onclick = capturePhoto;
        retakeBtn.onclick = retakePhoto;
        saveBtn.onclick = savePhoto;
        
        // Gérer la fermeture du modal
        modal.addEventListener('hidden.bs.modal', () => {
            stopCamera();
        });
        
        // Afficher le modal et démarrer la caméra
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        // Démarrer la caméra après l'affichage du modal
        modal.addEventListener('shown.bs.modal', () => {
            startCamera();
        });
    }
};

// Initialiser le module au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    RepairModal.init();
}); 