/**
 * Module de gestion des détails de réparation
 * Ce script gère l'affichage et les interactions avec le modal de détails des réparations
 */

document.addEventListener('DOMContentLoaded', function() {
    // Référence au modal
    const repairModal = document.getElementById('repairDetailsModal');
    let currentRepairId = null;
    
    if (!repairModal) {
        console.error('Modal de détails de réparation non trouvé');
        return;
    }
    
    // Initialiser le modal Bootstrap
    const modal = new bootstrap.Modal(repairModal);
    
    // Écouteurs d'événements pour les cartes de réparation
    const repairCards = document.querySelectorAll('.dashboard-card.repair-row');
    repairCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Vérifier que l'utilisateur n'a pas cliqué sur un bouton ou un lien
            if (!e.target.closest('button') && !e.target.closest('a')) {
                const repairId = this.getAttribute('data-id');
                if (repairId) {
                    showRepairDetails(repairId);
                }
            }
        });
    });
    
    // Écouteurs d'événements pour les boutons de détails
    const detailButtons = document.querySelectorAll('.btn-details');
    detailButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const repairId = this.getAttribute('data-id');
            if (repairId) {
                showRepairDetails(repairId);
            }
        });
    });
    
    /**
     * Affiche le modal avec les détails de la réparation
     * @param {string} repairId - L'identifiant de la réparation
     */
    function showRepairDetails(repairId) {
        currentRepairId = repairId;
        
        // Afficher le loader et masquer le contenu
        document.querySelector('.modal-loader').style.display = 'flex';
        document.querySelector('.modal-content-data').style.display = 'none';
        
        // Afficher le modal
        modal.show();
        
        // Charger les détails de la réparation
        loadRepairDetails(repairId);
    }
    
    /**
     * Charge les détails de la réparation via AJAX
     * @param {string} repairId - L'identifiant de la réparation
     */
    function loadRepairDetails(repairId) {
        // Simuler une requête AJAX pour charger les détails
        // À remplacer par une vraie requête AJAX
        setTimeout(() => {
            fetch(`ajax/get_repair_details.php?repair_id=${repairId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateModalWithRepairData(data.repair);
                        
                        // Charger les statuts disponibles
                        loadAvailableStatuses();
                        
                        // Afficher le contenu et masquer le loader
                        document.querySelector('.modal-loader').style.display = 'none';
                        document.querySelector('.modal-content-data').style.display = 'block';
                    } else {
                        showToast('Erreur', data.error || 'Impossible de charger les détails de la réparation', 'danger');
                        modal.hide();
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des détails:', error);
                    showToast('Erreur', 'Une erreur est survenue lors du chargement des détails', 'danger');
                    modal.hide();
                });
        }, 500); // Simuler un délai réseau
    }
    
    /**
     * Met à jour le contenu du modal avec les données de la réparation
     * @param {Object} repair - Les données de la réparation
     */
    function updateModalWithRepairData(repair) {
        // Mettre à jour le titre du modal
        document.getElementById('repairDetailsModalLabel').textContent = `Réparation #${repair.id}`;
        
        // Informations client
        document.getElementById('repair-client-name').textContent = `${repair.client_prenom} ${repair.client_nom}`;
        
        // Statut actuel
        const statusBadge = document.getElementById('repair-status');
        statusBadge.textContent = repair.statut_nom || '-';
        statusBadge.className = `badge rounded-pill bg-${repair.statut_categorie_couleur || 'secondary'}`;
        
        // Informations appareil
        document.getElementById('repair-device').textContent = repair.appareil_nom || '-';
        document.getElementById('repair-model').textContent = repair.modele || '-';
        document.getElementById('repair-serial').textContent = repair.numero_serie || '-';
        document.getElementById('repair-date-creation').textContent = formatDate(repair.date_creation) || '-';
        
        // Description du problème
        document.getElementById('repair-problem-textarea').value = repair.probleme || '';
        
        // Prix
        document.getElementById('repair-price-input').value = repair.prix || '';
        
        // Charger les notes techniques
        loadTechnicalNotes(repair.id);
        
        // Charger les photos
        loadRepairPhotos(repair.id);
    }
    
    /**
     * Charge les notes techniques de la réparation
     * @param {string} repairId - L'identifiant de la réparation
     */
    function loadTechnicalNotes(repairId) {
        const notesContainer = document.getElementById('technical-notes-container');
        notesContainer.innerHTML = '<p class="text-muted">Chargement des notes...</p>';
        
        fetch(`ajax/get_repair_notes.php?repair_id=${repairId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.notes && data.notes.length > 0) {
                        notesContainer.innerHTML = '';
                        data.notes.forEach(note => {
                            const noteElement = document.createElement('div');
                            noteElement.className = 'technical-note p-3 mb-3 rounded';
                            noteElement.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">${formatDate(note.date_creation)}</small>
                                </div>
                                <p class="mb-0">${note.contenu}</p>
                            `;
                            notesContainer.appendChild(noteElement);
                        });
                    } else {
                        notesContainer.innerHTML = '<p class="text-muted">Aucune note technique pour cette réparation</p>';
                    }
                } else {
                    notesContainer.innerHTML = '<p class="text-danger">Erreur lors du chargement des notes</p>';
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des notes:', error);
                notesContainer.innerHTML = '<p class="text-danger">Erreur lors du chargement des notes</p>';
            });
    }
    
    /**
     * Charge les photos de la réparation
     * @param {string} repairId - L'identifiant de la réparation
     */
    function loadRepairPhotos(repairId) {
        const photosContainer = document.getElementById('photos-container');
        photosContainer.innerHTML = '<p class="text-muted">Chargement des photos...</p>';
        
        fetch(`ajax/get_repair_photos.php?repair_id=${repairId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.photos && data.photos.length > 0) {
                        photosContainer.innerHTML = '<div class="row row-cols-1 row-cols-md-3"></div>';
                        const photoRow = photosContainer.querySelector('.row');
                        
                        data.photos.forEach(photo => {
                            const photoCol = document.createElement('div');
                            photoCol.className = 'col mb-4';
                            photoCol.innerHTML = `
                                <div class="photo-item card h-100">
                                    <img src="uploads/photos/${photo.nom_fichier}" class="card-img-top" alt="Photo de réparation">
                                    <div class="photo-actions">
                                        <a href="uploads/photos/${photo.nom_fichier}" class="btn btn-sm btn-primary" target="_blank" title="Voir">
                                            <i class="las la-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-photo" data-photo="${photo.nom_fichier}" title="Supprimer">
                                            <i class="las la-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                            photoRow.appendChild(photoCol);
                        });
                        
                        // Ajouter les écouteurs d'événements pour les boutons de suppression
                        document.querySelectorAll('.delete-photo').forEach(button => {
                            button.addEventListener('click', function() {
                                const photoName = this.getAttribute('data-photo');
                                deleteRepairPhoto(repairId, photoName);
                            });
                        });
                    } else {
                        photosContainer.innerHTML = '<p class="text-muted">Aucune photo pour cette réparation</p>';
                    }
                } else {
                    photosContainer.innerHTML = '<p class="text-danger">Erreur lors du chargement des photos</p>';
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des photos:', error);
                photosContainer.innerHTML = '<p class="text-danger">Erreur lors du chargement des photos</p>';
            });
    }
    
    /**
     * Charge les statuts disponibles
     */
    function loadAvailableStatuses() {
        const statusContainer = document.getElementById('status-buttons-container');
        
        fetch('ajax/get_all_statuts.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusContainer.innerHTML = '';
                    
                    // Regrouper les statuts par catégorie
                    const categorizedStatuses = {};
                    data.statuts.forEach(status => {
                        if (!categorizedStatuses[status.categorie_id]) {
                            categorizedStatuses[status.categorie_id] = {
                                name: status.categorie_nom,
                                color: status.categorie_couleur,
                                statuses: []
                            };
                        }
                        categorizedStatuses[status.categorie_id].statuses.push(status);
                    });
                    
                    // Créer les boutons de statut par catégorie
                    Object.values(categorizedStatuses).forEach(category => {
                        const categoryDiv = document.createElement('div');
                        categoryDiv.className = 'mb-4';
                        categoryDiv.innerHTML = `<h6 class="mb-3">${category.name}</h6><div class="d-flex flex-wrap"></div>`;
                        
                        const buttonsContainer = categoryDiv.querySelector('.d-flex');
                        category.statuses.forEach(status => {
                            const statusButton = document.createElement('button');
                            statusButton.type = 'button';
                            statusButton.className = `btn btn-${category.color} status-btn`;
                            statusButton.setAttribute('data-status-id', status.id);
                            statusButton.innerHTML = status.nom;
                            statusButton.addEventListener('click', function() {
                                updateRepairStatus(currentRepairId, status.id);
                            });
                            buttonsContainer.appendChild(statusButton);
                        });
                        
                        statusContainer.appendChild(categoryDiv);
                    });
                } else {
                    statusContainer.innerHTML = '<p class="text-danger">Erreur lors du chargement des statuts</p>';
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des statuts:', error);
                statusContainer.innerHTML = '<p class="text-danger">Erreur lors du chargement des statuts</p>';
            });
    }
    
    /**
     * Met à jour le problème de la réparation
     */
    document.getElementById('save-problem-btn').addEventListener('click', function() {
        const problemText = document.getElementById('repair-problem-textarea').value;
        
        const formData = new FormData();
        formData.append('repair_id', currentRepairId);
        formData.append('problem', problemText);
        
        fetch('ajax/update_repair_all.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Succès', 'Description du problème mise à jour', 'success');
            } else {
                showToast('Erreur', data.error || 'Impossible de mettre à jour la description', 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour du problème:', error);
            showToast('Erreur', 'Une erreur est survenue lors de la mise à jour', 'danger');
        });
    });
    
    /**
     * Met à jour le prix de la réparation
     */
    document.getElementById('save-price-btn').addEventListener('click', function() {
        const price = document.getElementById('repair-price-input').value;
        
        if (!price) {
            showToast('Erreur', 'Veuillez entrer un prix', 'warning');
            return;
        }
        
        const formData = new FormData();
        formData.append('repair_id', currentRepairId);
        formData.append('price', price);
        
        fetch('ajax/update_repair_price.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Succès', 'Prix mis à jour', 'success');
            } else {
                showToast('Erreur', data.error || 'Impossible de mettre à jour le prix', 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour du prix:', error);
            showToast('Erreur', 'Une erreur est survenue lors de la mise à jour', 'danger');
        });
    });
    
    /**
     * Ajoute une note technique
     */
    document.getElementById('add-note-btn').addEventListener('click', function() {
        const noteText = document.getElementById('new-note-textarea').value;
        
        if (!noteText.trim()) {
            showToast('Erreur', 'Veuillez entrer une note', 'warning');
            return;
        }
        
        const formData = new FormData();
        formData.append('repair_id', currentRepairId);
        formData.append('note', noteText);
        
        fetch('ajax/add_repair_note.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Vider le champ de texte
                document.getElementById('new-note-textarea').value = '';
                
                // Recharger les notes
                loadTechnicalNotes(currentRepairId);
                
                showToast('Succès', 'Note ajoutée', 'success');
            } else {
                showToast('Erreur', data.error || 'Impossible d\'ajouter la note', 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'ajout de la note:', error);
            showToast('Erreur', 'Une erreur est survenue lors de l\'ajout de la note', 'danger');
        });
    });
    
    /**
     * Gère l'upload de photo
     */
    document.getElementById('photo-upload-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('photo-file');
        if (!fileInput.files || fileInput.files.length === 0) {
            showToast('Erreur', 'Veuillez sélectionner une image', 'warning');
            return;
        }
        
        const formData = new FormData();
        formData.append('repair_id', currentRepairId);
        formData.append('photo_file', fileInput.files[0]);
        
        fetch('ajax/upload_repair_photo.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Réinitialiser le formulaire
                this.reset();
                
                // Recharger les photos
                loadRepairPhotos(currentRepairId);
                
                showToast('Succès', 'Photo téléchargée', 'success');
            } else {
                showToast('Erreur', data.error || 'Impossible de télécharger la photo', 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur lors du téléchargement de la photo:', error);
            showToast('Erreur', 'Une erreur est survenue lors du téléchargement', 'danger');
        });
    });
    
    /**
     * Met à jour le statut de la réparation
     * @param {string} repairId - L'identifiant de la réparation
     * @param {string} statusId - L'identifiant du statut
     */
    function updateRepairStatus(repairId, statusId) {
        const formData = new FormData();
        formData.append('repair_id', repairId);
        formData.append('status_id', statusId);
        
        fetch('ajax/update_repair_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'affichage du statut
                if (data.status) {
                    const statusBadge = document.getElementById('repair-status');
                    statusBadge.textContent = data.status.nom || '-';
                    statusBadge.className = `badge rounded-pill bg-${data.status.categorie_couleur || 'secondary'}`;
                }
                
                showToast('Succès', 'Statut mis à jour', 'success');
            } else {
                showToast('Erreur', data.error || 'Impossible de mettre à jour le statut', 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour du statut:', error);
            showToast('Erreur', 'Une erreur est survenue lors de la mise à jour', 'danger');
        });
    }
    
    /**
     * Supprime une photo de réparation
     * @param {string} repairId - L'identifiant de la réparation
     * @param {string} photoName - Le nom de la photo
     */
    function deleteRepairPhoto(repairId, photoName) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette photo ?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('repair_id', repairId);
        formData.append('photo_name', photoName);
        
        fetch('ajax/delete_repair_photo.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger les photos
                loadRepairPhotos(repairId);
                
                showToast('Succès', 'Photo supprimée', 'success');
            } else {
                showToast('Erreur', data.error || 'Impossible de supprimer la photo', 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur lors de la suppression de la photo:', error);
            showToast('Erreur', 'Une erreur est survenue lors de la suppression', 'danger');
        });
    }
    
    /**
     * Affiche une notification toast
     * @param {string} title - Le titre du toast
     * @param {string} message - Le message à afficher
     * @param {string} type - Le type de toast (success, danger, warning, info)
     */
    function showToast(title, message, type = 'info') {
        // Créer le conteneur de toast s'il n'existe pas
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '5000';
            document.body.appendChild(toastContainer);
        }
        
        // Créer le toast
        const toastId = `toast-${Date.now()}`;
        const toastElement = document.createElement('div');
        toastElement.id = toastId;
        toastElement.className = `toast align-items-center text-white bg-${type} border-0`;
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');
        
        // Contenu du toast
        toastElement.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong>: ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
            </div>
        `;
        
        // Ajouter le toast au conteneur
        toastContainer.appendChild(toastElement);
        
        // Initialiser et afficher le toast
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
        toast.show();
        
        // Supprimer le toast du DOM une fois qu'il est caché
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }
    
    /**
     * Formate une date en format lisible
     * @param {string} dateString - La date au format ISO
     * @returns {string} - La date formatée
     */
    function formatDate(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).replace(',', ' à');
    }
}); 