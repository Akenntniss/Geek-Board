/**
 * Script pour gérer la sélection des réparations dans le modal
 */

class ReparationSelector {
    constructor() {
        this.reparations = [];
        this.filteredReparations = [];
        this.selectedReparation = null;
        
        this.initEventListeners();
    }
    
    initEventListeners() {
        // Événement d'ouverture du modal
        const modal = document.getElementById('selectReparationModal');
        if (modal) {
            modal.addEventListener('show.bs.modal', () => this.loadReparations());
        }
        
        // Recherche en temps réel
        const searchInput = document.getElementById('searchReparations');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.filterReparations(e.target.value));
        }
        
        // Bouton pour effacer la sélection
        const clearBtn = document.getElementById('clearReparationSelection');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearSelection());
        }
    }
    
    async loadReparations() {
        try {
            this.showLoader();
            
            const response = await fetch('ajax/get_reparations.php');
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.reparations = data.reparations;
                this.filteredReparations = [...this.reparations];
                this.renderTable();
                
                if (this.reparations.length === 0) {
                    this.showNoResults();
                } else {
                    this.showTable();
                }
            } else {
                throw new Error(data.message || 'Erreur lors du chargement des réparations');
            }
        } catch (error) {
            console.error('Erreur lors du chargement des réparations:', error);
            this.showError(error.message);
        }
    }
    
    filterReparations(searchTerm) {
        if (!searchTerm.trim()) {
            this.filteredReparations = [...this.reparations];
        } else {
            const term = searchTerm.toLowerCase();
            this.filteredReparations = this.reparations.filter(rep => {
                return (
                    rep.client_nom.toLowerCase().includes(term) ||
                    rep.client_prenom.toLowerCase().includes(term) ||
                    rep.type_appareil.toLowerCase().includes(term) ||
                    rep.marque.toLowerCase().includes(term) ||
                    rep.modele.toLowerCase().includes(term) ||
                    rep.description_probleme.toLowerCase().includes(term) ||
                    rep.statut_nom.toLowerCase().includes(term)
                );
            });
        }
        
        this.renderTable();
        
        if (this.filteredReparations.length === 0) {
            this.showNoResults();
        } else {
            this.showTable();
        }
    }
    
    renderTable() {
        const tbody = document.getElementById('reparationsTableBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        this.filteredReparations.forEach(rep => {
            const row = this.createTableRow(rep);
            tbody.appendChild(row);
        });
    }
    
    createTableRow(reparation) {
        const row = document.createElement('tr');
        row.className = 'reparation-row';
        row.style.cursor = 'pointer';
        
        // Formatage de la date (DD/MM seulement)
        const date = new Date(reparation.date_reception);
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const formattedDate = `${day}/${month}`;
        
        // Formatage du prix
        const prix = reparation.prix_reparation ? 
            parseFloat(reparation.prix_reparation).toFixed(2) + ' €' : 
            'Non défini';
        
        // Couleur du statut
        const statusColor = this.getStatusColor(reparation.statut_nom);
        
        row.innerHTML = `
            <td class="py-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-calendar-alt text-muted me-2"></i>
                    <span class="fw-medium">${formattedDate}</span>
                </div>
            </td>
            <td class="py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                        ${reparation.client_nom.charAt(0)}${reparation.client_prenom.charAt(0)}
                    </div>
                    <div>
                        <div class="fw-medium">${reparation.client_nom} ${reparation.client_prenom}</div>
                        <small class="text-muted">#${reparation.id}</small>
                    </div>
                </div>
            </td>
            <td class="py-3">
                <div>
                    <div class="fw-medium">${reparation.type_appareil}</div>
                    <small class="text-muted">${reparation.marque} ${reparation.modele}</small>
                </div>
            </td>
            <td class="py-3">
                <div class="text-truncate" style="max-width: 200px;" title="${reparation.description_probleme}">
                    ${reparation.description_probleme}
                </div>
            </td>
            <td class="py-3">
                <span class="badge ${statusColor} px-3 py-2">
                    ${reparation.statut_nom}
                </span>
            </td>
            <td class="py-3">
                <div class="fw-medium text-success">
                    ${prix}
                </div>
            </td>
            <td class="py-3 text-center">
                <button type="button" class="btn btn-primary btn-sm px-3" onclick="reparationSelector.selectReparation(${reparation.id})">
                    <i class="fas fa-check me-1"></i>Sélectionner
                </button>
            </td>
        `;
        
        // Effet hover
        row.addEventListener('mouseenter', () => {
            row.style.backgroundColor = 'rgba(13, 110, 253, 0.1)';
        });
        
        row.addEventListener('mouseleave', () => {
            row.style.backgroundColor = '';
        });
        
        // Clic sur la ligne pour sélectionner
        row.addEventListener('click', (e) => {
            if (!e.target.closest('button')) {
                this.selectReparation(reparation.id);
            }
        });
        
        return row;
    }
    
    selectReparation(reparationId) {
        const reparation = this.reparations.find(r => r.id == reparationId);
        if (!reparation) return;
        
        this.selectedReparation = reparation;
        
        // Mettre à jour l'input caché
        const hiddenInput = document.getElementById('reparation_id');
        if (hiddenInput) {
            hiddenInput.value = reparation.id;
        }
        
        // Mettre à jour le texte du bouton
        const buttonText = document.getElementById('selectedReparationText');
        if (buttonText) {
            buttonText.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <div class="text-start">
                        <div class="fw-medium">#${reparation.id} - ${reparation.client_nom} ${reparation.client_prenom}</div>
                        <small class="text-muted">${reparation.type_appareil} ${reparation.marque} ${reparation.modele}</small>
                    </div>
                </div>
            `;
        }
        
        // Remplir automatiquement le client si disponible
        this.fillClientInfo(reparation);
        
        // Fermer le modal de sélection et rouvrir le modal de commande
        const selectModal = bootstrap.Modal.getInstance(document.getElementById('selectReparationModal'));
        if (selectModal) {
            selectModal.hide();
        }
        
        // Attendre que le modal se ferme complètement avant de rouvrir l'autre
        setTimeout(() => {
            const commandeModal = new bootstrap.Modal(document.getElementById('ajouterCommandeModal'));
            commandeModal.show();
        }, 300);
        
        // Notification de succès
        this.showNotification('Réparation sélectionnée avec succès', 'success');
    }
    
    fillClientInfo(reparation) {
        // Remplir le client_id
        const clientIdInput = document.getElementById('client_id');
        if (clientIdInput) {
            clientIdInput.value = reparation.client_id;
        }
        
        // Mettre à jour l'affichage du client sélectionné si disponible
        const nomClientInput = document.getElementById('nom_client_selectionne');
        if (nomClientInput) {
            nomClientInput.value = `${reparation.client_nom} ${reparation.client_prenom}`;
        }
        
        // Afficher la section du client sélectionné si elle existe
        const clientSelectionne = document.getElementById('client_selectionne');
        if (clientSelectionne) {
            const nomClientSpan = clientSelectionne.querySelector('.nom_client');
            if (nomClientSpan) {
                nomClientSpan.textContent = `${reparation.client_nom} ${reparation.client_prenom}`;
            }
            clientSelectionne.classList.remove('d-none');
        }
    }
    
    clearSelection() {
        this.selectedReparation = null;
        
        // Vider l'input caché
        const hiddenInput = document.getElementById('reparation_id');
        if (hiddenInput) {
            hiddenInput.value = '';
        }
        
        // Remettre le texte par défaut
        const buttonText = document.getElementById('selectedReparationText');
        if (buttonText) {
            buttonText.innerHTML = '<i class="fas fa-search me-2"></i>Choisir une réparation...';
        }
        
        // Fermer le modal de sélection et rouvrir le modal de commande
        const selectModal = bootstrap.Modal.getInstance(document.getElementById('selectReparationModal'));
        if (selectModal) {
            selectModal.hide();
        }
        
        // Attendre que le modal se ferme complètement avant de rouvrir l'autre
        setTimeout(() => {
            const commandeModal = new bootstrap.Modal(document.getElementById('ajouterCommandeModal'));
            commandeModal.show();
        }, 300);
        
        this.showNotification('Sélection effacée', 'info');
    }
    
    getStatusColor(statusName) {
        const statusColors = {
            'Nouveau': 'bg-info',
            'Diagnostique': 'bg-warning',
            'Intervention': 'bg-primary',
            'En attente': 'bg-secondary',
            'En cours': 'bg-success'
        };
        
        // Recherche par mot-clé dans le nom du statut
        for (const [key, color] of Object.entries(statusColors)) {
            if (statusName.toLowerCase().includes(key.toLowerCase())) {
                return color;
            }
        }
        
        return 'bg-secondary'; // Couleur par défaut
    }
    
    showLoader() {
        document.getElementById('reparationsLoader').classList.remove('d-none');
        document.getElementById('reparationsTableContainer').classList.add('d-none');
        document.getElementById('noReparationsMessage').classList.add('d-none');
    }
    
    showTable() {
        document.getElementById('reparationsLoader').classList.add('d-none');
        document.getElementById('reparationsTableContainer').classList.remove('d-none');
        document.getElementById('noReparationsMessage').classList.add('d-none');
    }
    
    showNoResults() {
        document.getElementById('reparationsLoader').classList.add('d-none');
        document.getElementById('reparationsTableContainer').classList.add('d-none');
        document.getElementById('noReparationsMessage').classList.remove('d-none');
    }
    
    showError(message) {
        document.getElementById('reparationsLoader').classList.add('d-none');
        document.getElementById('reparationsTableContainer').classList.add('d-none');
        
        const noResultsDiv = document.getElementById('noReparationsMessage');
        noResultsDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
            <h5 class="text-danger">Erreur de chargement</h5>
            <p class="text-muted">${message}</p>
        `;
        noResultsDiv.classList.remove('d-none');
    }
    
    showNotification(message, type = 'info') {
        // Réutiliser la fonction showNotification existante si elle existe
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }
}

// Initialiser le sélecteur de réparations au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    window.reparationSelector = new ReparationSelector();
    console.log('Sélecteur de réparations initialisé');
}); 