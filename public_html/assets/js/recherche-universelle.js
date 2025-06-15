/**
 * Gestionnaire de recherche universelle
 * Gère la recherche en temps réel et l'affichage des résultats
 */

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('btnSearch');
    const searchStatus = document.querySelector('.search-status');
    const searchTabs = document.getElementById('searchTabs');
    const searchTabContent = document.getElementById('searchTabContent');
    
    // Compteurs de résultats
    const countClients = document.getElementById('count-clients');
    const countReparations = document.getElementById('count-reparations');
    const countCommandes = document.getElementById('count-commandes');
    
    // Conteneurs de résultats
    const clientsResults = document.getElementById('clientsResults');
    const reparationsResults = document.getElementById('reparationsResults');
    const commandesResults = document.getElementById('commandesResults');
    
    let searchTimeout;
    let currentSearch = '';
    
    // Fonction de recherche
    async function performSearch(query) {
        if (!query || query.length < 2) {
            hideResults();
            return;
        }
        
        currentSearch = query;
        showLoading();
        
        try {
            const response = await fetch('ajax/recherche_universelle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `terme=${encodeURIComponent(query)}`
            });
            
            const data = await response.json();
            
            if (currentSearch === query) {
                updateResults(data);
            }
        } catch (error) {
            console.error('Erreur lors de la recherche:', error);
            showError('Une erreur est survenue lors de la recherche');
        } finally {
            if (currentSearch === query) {
                hideLoading();
            }
        }
    }
    
    // Mise à jour des résultats
    function updateResults(data) {
        // Mise à jour des compteurs
        countClients.textContent = data.clients?.length || 0;
        countReparations.textContent = data.reparations?.length || 0;
        countCommandes.textContent = data.commandes?.length || 0;
        
        // Mise à jour des résultats
        updateClientsResults(data.clients || []);
        updateReparationsResults(data.reparations || []);
        updateCommandesResults(data.commandes || []);
        
        // Afficher l'onglet avec le plus de résultats
        showMostRelevantTab();
    }
    
    // Mise à jour des résultats clients
    function updateClientsResults(clients) {
        if (clients.length === 0) {
            clientsResults.innerHTML = '<div class="text-center p-4 text-muted">Aucun client trouvé</div>';
            return;
        }
        
        const html = `
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${clients.map(client => `
                        <tr>
                            <td>${client.nom} ${client.prenom}</td>
                            <td>${client.telephone}</td>
                            <td>${client.email || '-'}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewClient(${client.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        
        clientsResults.innerHTML = html;
    }
    
    // Mise à jour des résultats réparations
    function updateReparationsResults(reparations) {
        if (reparations.length === 0) {
            reparationsResults.innerHTML = '<div class="text-center p-4 text-muted">Aucune réparation trouvée</div>';
            return;
        }
        
        const html = `
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Appareil</th>
                        <th>Problème</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${reparations.map(rep => `
                        <tr>
                            <td>${rep.client_nom}</td>
                            <td>${rep.appareil}</td>
                            <td>${rep.probleme}</td>
                            <td>
                                <span class="badge bg-${getStatusColor(rep.statut)}">
                                    ${rep.statut}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewReparation(${rep.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        
        reparationsResults.innerHTML = html;
    }
    
    // Mise à jour des résultats commandes
    function updateCommandesResults(commandes) {
        if (commandes.length === 0) {
            commandesResults.innerHTML = '<div class="text-center p-4 text-muted">Aucune commande trouvée</div>';
            return;
        }
        
        const html = `
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${commandes.map(cmd => `
                        <tr>
                            <td>${cmd.reference}</td>
                            <td>${cmd.client_nom}</td>
                            <td>${formatDate(cmd.date_commande)}</td>
                            <td>${formatMontant(cmd.montant)}</td>
                            <td>
                                <span class="badge bg-${getStatusColor(cmd.statut)}">
                                    ${cmd.statut}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewCommande(${cmd.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        
        commandesResults.innerHTML = html;
    }
    
    // Afficher l'onglet le plus pertinent
    function showMostRelevantTab() {
        const counts = {
            clients: parseInt(countClients.textContent),
            reparations: parseInt(countReparations.textContent),
            commandes: parseInt(countCommandes.textContent)
        };
        
        let maxType = 'clients';
        let maxCount = counts.clients;
        
        if (counts.reparations > maxCount) {
            maxType = 'reparations';
            maxCount = counts.reparations;
        }
        if (counts.commandes > maxCount) {
            maxType = 'commandes';
        }
        
        const tab = document.querySelector(`#${maxType}-tab`);
        if (tab) {
            const tabInstance = new bootstrap.Tab(tab);
            tabInstance.show();
        }
    }
    
    // Fonctions utilitaires
    function showLoading() {
        searchStatus.classList.remove('d-none');
    }
    
    function hideLoading() {
        searchStatus.classList.add('d-none');
    }
    
    function showError(message) {
        // Implémenter l'affichage des erreurs
    }
    
    function hideResults() {
        countClients.textContent = '0';
        countReparations.textContent = '0';
        countCommandes.textContent = '0';
        
        clientsResults.innerHTML = '';
        reparationsResults.innerHTML = '';
        commandesResults.innerHTML = '';
    }
    
    function getStatusColor(statut) {
        const colors = {
            'En attente': 'warning',
            'En cours': 'info',
            'Terminé': 'success',
            'Annulé': 'danger'
        };
        return colors[statut] || 'secondary';
    }
    
    function formatDate(date) {
        return new Date(date).toLocaleDateString('fr-FR');
    }
    
    function formatMontant(montant) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(montant);
    }
    
    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch(this.value.trim());
            }
        });
    }
    
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            if (searchInput) {
                performSearch(searchInput.value.trim());
            }
        });
    }
    
    // Initialisation
    if (searchInput) {
        searchInput.focus();
    }
}); 