/**
 * Gestionnaire de Recherche Universelle - Version 2.0
 * Recherche dans clients, réparations et commandes
 * 
 * @author GeekBoard
 * @version 2.0
 */

class RechercheUniverselle {
    constructor() {
        this.searchInput = null;
        this.searchBtn = null;
        this.loadingContainer = null;
        this.searchResults = null;
        this.noResults = null;
        this.modal = null;
        this.searchTimeout = null;
        this.minSearchLength = 2;
        
        // Compteurs
        this.clientsCount = null;
        this.reparationsCount = null;
        this.commandesCount = null;
        
        // Conteneurs de résultats
        this.clientsTableBody = null;
        this.reparationsTableBody = null;
        this.commandesTableBody = null;
        
        this.init();
    }

    init() {
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupElements());
        } else {
            this.setupElements();
        }
    }

    setupElements() {
        // Récupérer les éléments du DOM
        this.searchInput = document.getElementById('universalSearchInput');
        this.searchBtn = document.getElementById('universalSearchBtn');
        this.loadingContainer = document.getElementById('loadingContainer');
        this.searchResults = document.getElementById('searchResults');
        this.noResults = document.getElementById('noResults');
        this.modal = document.getElementById('rechercheUniverselleModal');
        
        // Compteurs
        this.clientsCount = document.getElementById('clientsCount');
        this.reparationsCount = document.getElementById('reparationsCount');
        this.commandesCount = document.getElementById('commandesCount');
        
        // Conteneurs de résultats
        this.clientsTableBody = document.getElementById('clientsTableBody');
        this.reparationsTableBody = document.getElementById('reparationsTableBody');
        this.commandesTableBody = document.getElementById('commandesTableBody');
        
        // Vérifier que les éléments existent
        if (!this.searchInput || !this.searchBtn) {
            console.error('🔴 Recherche Universelle : Éléments manquants');
            return;
        }
        
        console.log('✅ Recherche Universelle : Éléments trouvés et initialisés');
        
        this.setupEventListeners();
        this.resetSearch();
    }

    setupEventListeners() {
        // Recherche en temps réel
        this.searchInput.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });
        
        // Bouton de recherche
        this.searchBtn.addEventListener('click', () => {
            this.performSearch(this.searchInput.value);
        });
        
        // Recherche avec Entrée
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch(this.searchInput.value);
            }
        });
        
        // Reset quand le modal se ferme
        if (this.modal) {
            this.modal.addEventListener('hidden.bs.modal', () => {
                this.resetSearch();
            });
        }
    }

    handleSearchInput(query) {
        // Annuler la recherche précédente
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Si trop court, cacher les résultats
        if (query.length < this.minSearchLength) {
            this.hideResults();
            return;
        }
        
        // Délai avant recherche (debounce)
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, 300);
    }

    async performSearch(query) {
        if (!query || query.length < this.minSearchLength) {
            this.hideResults();
            return;
        }
        
        console.log('🔍 Recherche pour :', query);
        
        this.showLoading();
        
        try {
            const response = await fetch('ajax/recherche-universelle-new.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `terme=${encodeURIComponent(query)}`
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.error) {
                console.error('❌ Erreur de recherche :', data.error);
                this.showNoResults();
                return;
            }
            
            this.displayResults(data);
            
        } catch (error) {
            console.error('❌ Erreur lors de la recherche :', error);
            this.showNoResults();
        } finally {
            this.hideLoading();
        }
    }

    displayResults(data) {
        const totalResults = (data.clients?.length || 0) + 
                           (data.reparations?.length || 0) + 
                           (data.commandes?.length || 0);
        
        if (totalResults === 0) {
            this.showNoResults();
            return;
        }
        
        console.log('📊 Résultats trouvés :', totalResults);
        
        // Mettre à jour les compteurs
        this.updateCounters(data);
        
        // Afficher les résultats
        this.showResults();
        
        // Remplir les tableaux
        this.populateClientsTable(data.clients || []);
        this.populateReparationsTable(data.reparations || []);
        this.populateCommandesTable(data.commandes || []);
    }

    updateCounters(data) {
        if (this.clientsCount) {
            this.clientsCount.textContent = data.clients?.length || 0;
        }
        if (this.reparationsCount) {
            this.reparationsCount.textContent = data.reparations?.length || 0;
        }
        if (this.commandesCount) {
            this.commandesCount.textContent = data.commandes?.length || 0;
        }
    }

    populateClientsTable(clients) {
        if (!this.clientsTableBody) return;
        
        this.clientsTableBody.innerHTML = '';
        
        clients.forEach(client => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <strong>${this.escapeHtml(client.nom || '')}</strong>
                    ${client.prenom ? '<br><small class="text-muted">' + this.escapeHtml(client.prenom) + '</small>' : ''}
                </td>
                <td>
                    ${client.telephone ? '<a href="tel:' + this.escapeHtml(client.telephone) + '">' + this.escapeHtml(client.telephone) + '</a>' : '<span class="text-muted">N/A</span>'}
                </td>
                <td>
                    ${client.email ? '<a href="mailto:' + this.escapeHtml(client.email) + '">' + this.escapeHtml(client.email) + '</a>' : '<span class="text-muted">N/A</span>'}
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="voirClient(${client.id})">
                        <i class="fas fa-eye"></i> Voir
                    </button>
                </td>
            `;
            this.clientsTableBody.appendChild(row);
        });
    }

    populateReparationsTable(reparations) {
        if (!this.reparationsTableBody) return;
        
        this.reparationsTableBody.innerHTML = '';
        
        reparations.forEach(reparation => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>#${reparation.id}</strong></td>
                <td>
                    ${this.escapeHtml(reparation.client_nom || '')}
                    ${reparation.client_prenom ? '<br><small class="text-muted">' + this.escapeHtml(reparation.client_prenom) + '</small>' : ''}
                </td>
                <td>
                    ${this.escapeHtml(reparation.type_appareil || '')}
                    ${reparation.modele ? '<br><small class="text-muted">' + this.escapeHtml(reparation.modele) + '</small>' : ''}
                </td>
                <td>
                    <span class="badge ${this.getStatusClass(reparation.statut)}">${this.escapeHtml(reparation.statut || '')}</span>
                </td>
                <td>
                    <small>${this.formatDate(reparation.date_reception)}</small>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="voirReparation(${reparation.id})">
                        <i class="fas fa-eye"></i> Voir
                    </button>
                </td>
            `;
            this.reparationsTableBody.appendChild(row);
        });
    }

    populateCommandesTable(commandes) {
        if (!this.commandesTableBody) return;
        
        this.commandesTableBody.innerHTML = '';
        
        commandes.forEach(commande => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>#${commande.id}</strong></td>
                <td>
                    ${this.escapeHtml(commande.client_nom || '')}
                    ${commande.client_prenom ? '<br><small class="text-muted">' + this.escapeHtml(commande.client_prenom) + '</small>' : ''}
                </td>
                <td>${this.escapeHtml(commande.nom_piece || '')}</td>
                <td>
                    ${commande.fournisseur ? '<span class="badge bg-secondary">' + this.escapeHtml(commande.fournisseur) + '</span>' : '<span class="text-muted">N/A</span>'}
                </td>
                <td>
                    <span class="badge ${this.getStatusClass(commande.statut)}">${this.escapeHtml(commande.statut || '')}</span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="voirCommande(${commande.id})">
                        <i class="fas fa-eye"></i> Voir
                    </button>
                </td>
            `;
            this.commandesTableBody.appendChild(row);
        });
    }

    getStatusClass(statut) {
        switch (statut?.toLowerCase()) {
            case 'terminé':
            case 'termine':
            case 'livré':
            case 'livre':
                return 'bg-success';
            case 'en cours':
            case 'en_cours':
                return 'bg-warning';
            case 'en attente':
            case 'en_attente':
                return 'bg-info';
            case 'annulé':
            case 'annule':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit'
        });
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showLoading() {
        this.hideResults();
        this.hideNoResults();
        if (this.loadingContainer) {
            this.loadingContainer.classList.remove('d-none');
        }
    }

    hideLoading() {
        if (this.loadingContainer) {
            this.loadingContainer.classList.add('d-none');
        }
    }

    showResults() {
        this.hideLoading();
        this.hideNoResults();
        if (this.searchResults) {
            this.searchResults.classList.remove('d-none');
        }
    }

    hideResults() {
        if (this.searchResults) {
            this.searchResults.classList.add('d-none');
        }
    }

    showNoResults() {
        this.hideLoading();
        this.hideResults();
        if (this.noResults) {
            this.noResults.classList.remove('d-none');
        }
    }

    hideNoResults() {
        if (this.noResults) {
            this.noResults.classList.add('d-none');
        }
    }

    resetSearch() {
        if (this.searchInput) {
            this.searchInput.value = '';
        }
        this.hideResults();
        this.hideNoResults();
        this.hideLoading();
        
        // Reset compteurs
        if (this.clientsCount) this.clientsCount.textContent = '0';
        if (this.reparationsCount) this.reparationsCount.textContent = '0';
        if (this.commandesCount) this.commandesCount.textContent = '0';
    }
}

// Fonctions globales pour les actions
window.voirClient = function(clientId) {
    window.location.href = `index.php?page=client_details&id=${clientId}`;
};

window.voirReparation = function(reparationId) {
    window.location.href = `index.php?page=reparation_details&id=${reparationId}`;
};

window.voirCommande = function(commandeId) {
    window.location.href = `index.php?page=commandes_pieces&id=${commandeId}`;
};

// Initialisation
console.log('🔄 Initialisation de la Recherche Universelle...');
const rechercheUniverselle = new RechercheUniverselle();

// Export pour utilisation globale
window.RechercheUniverselle = RechercheUniverselle;
window.rechercheUniverselle = rechercheUniverselle; 