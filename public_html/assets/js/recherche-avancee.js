/**
 * Module de recherche avancée pour GestiRep
 * Permet de rechercher clients, réparations et commandes avec un seul champ de recherche
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const searchInput = document.getElementById('recherche_avancee');
    const searchButton = document.getElementById('btn-recherche-avancee');
    const resultsContainer = document.getElementById('resultats_recherche');
    const noResultsContainer = document.getElementById('aucun_resultat_trouve');
    
    // Compteurs de résultats
    const clientsCount = document.getElementById('count-clients');
    const reparationsCount = document.getElementById('count-reparations');
    const commandesCount = document.getElementById('count-commandes');
    
    // Conteneurs de résultats
    const clientsContainer = document.getElementById('liste_clients_recherche');
    const reparationsContainer = document.getElementById('liste_reparations_recherche');
    const commandesContainer = document.getElementById('liste_commandes_recherche');
    
    // Vérifier que les éléments existent
    if (!searchInput || !searchButton) {
        console.log("Module de recherche avancée: Éléments de recherche non trouvés");
        return;
    }
    
    console.log("Initialisation du module de recherche avancée");
    
    // Variable pour le délai de recherche (debounce)
    let timeoutRecherche;
    
    // Fonction de recherche
    function rechercheAvancee(terme) {
        if (terme.length < 2) {
            if (resultsContainer) resultsContainer.classList.add('d-none');
            if (noResultsContainer) noResultsContainer.classList.add('d-none');
            return;
        }
        
        // Recherche AJAX
        fetch('ajax/recherche_avancee.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'terme=' + encodeURIComponent(terme)
        })
        .then(response => {
            if (!response.ok) {
                console.error('Erreur réseau:', response.status, response.statusText);
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Log des données reçues pour le débogage
            console.log('Données reçues de recherche_avancee.php:', data);
            
            if (!data.success) {
                console.error('Erreur retournée par le serveur:', data.message);
                throw new Error(data.message || 'Erreur lors de la recherche');
            }
            
            // Mettre à jour les compteurs
            if (clientsCount) clientsCount.textContent = data.counts.clients;
            if (reparationsCount) reparationsCount.textContent = data.counts.reparations;
            if (commandesCount) commandesCount.textContent = data.counts.commandes;
            
            // Effacer les résultats précédents
            if (clientsContainer) clientsContainer.innerHTML = '';
            if (reparationsContainer) reparationsContainer.innerHTML = '';
            if (commandesContainer) commandesContainer.innerHTML = '';
            
            // Si aucun résultat
            if (data.counts.total === 0) {
                if (resultsContainer) resultsContainer.classList.add('d-none');
                if (noResultsContainer) noResultsContainer.classList.remove('d-none');
                return;
            }
            
            // Afficher les clients
            if (clientsContainer && data.resultats.clients.length > 0) {
                data.resultats.clients.forEach(client => {
                    const row = document.createElement('tr');
                    row.className = 'search-result-row'; // Ajouter une classe pour le styling
                    
                    // Préparer les données avec des valeurs par défaut
                    const nom = client.nom || 'Non spécifié';
                    const prenom = client.prenom || '';
                    const telephone = client.telephone || 'Non spécifié';
                    
                    row.innerHTML = `
                        <td class="ps-3 fw-medium">${nom}</td>
                        <td>${prenom}</td>
                        <td><i class="fas fa-phone-alt text-primary me-1"></i>${telephone}</td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-primary rounded-pill voir-client-btn me-1" 
                                data-id="${client.id}" 
                                data-nom="${nom}" 
                                data-prenom="${prenom}"
                                data-telephone="${telephone}">
                                <i class="fas fa-eye me-1"></i>Voir
                            </button>
                        </td>
                    `;
                    clientsContainer.appendChild(row);
                });
            }
            
            // Afficher les réparations
            if (reparationsContainer && data.resultats.reparations.length > 0) {
                data.resultats.reparations.forEach(reparation => {
                    const row = document.createElement('tr');
                    row.className = 'search-result-row'; // Ajouter une classe pour le styling
                    
                    // Préparer les données avec des valeurs par défaut pour éviter les valeurs null
                    const id = reparation.id || '';
                    const clientName = [reparation.client_nom || '', reparation.client_prenom || ''].filter(Boolean).join(' ');
                    const appareil = reparation.appareil || 'Non spécifié';
                    const modele = reparation.modele || 'Non spécifié';
                    const statut = reparation.statut || '';
                    
                    row.innerHTML = `
                        <td class="ps-3 fw-medium">${id}</td>
                        <td>${clientName}</td>
                        <td>${appareil}</td>
                        <td>${modele}</td>
                        <td><span class="badge bg-${getStatusColor(statut)} rounded-pill px-3 py-2">${formatStatus(statut)}</span></td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-primary rounded-pill voir-reparation-btn" 
                                data-id="${id}">
                                <i class="fas fa-eye me-1"></i>Voir
                            </button>
                        </td>
                    `;
                    reparationsContainer.appendChild(row);
                });
            }
            
            // Afficher les commandes
            if (commandesContainer && data.resultats.commandes.length > 0) {
                data.resultats.commandes.forEach(commande => {
                    const row = document.createElement('tr');
                    row.className = 'search-result-row'; // Ajouter une classe pour le styling
                    
                    // Préparer les données avec des valeurs par défaut
                    const id = commande.id || '';
                    const clientName = [commande.client_nom || '', commande.client_prenom || ''].filter(Boolean).join(' ');
                    const nomPiece = commande.nom_piece || 'Non spécifié';
                    const dateCreation = formatDate(commande.date_creation) || 'Non spécifié';
                    const statut = commande.statut || '';
                    
                    row.innerHTML = `
                        <td class="ps-3 fw-medium">${id}</td>
                        <td>${clientName}</td>
                        <td>${nomPiece}</td>
                        <td>${dateCreation}</td>
                        <td><span class="badge bg-${getCommandeStatusColor(statut)} rounded-pill px-3 py-2">${formatCommandeStatus(statut)}</span></td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-primary rounded-pill voir-commande-btn" 
                                data-id="${id}">
                                <i class="fas fa-eye me-1"></i>Voir
                            </button>
                        </td>
                    `;
                    commandesContainer.appendChild(row);
                });
            }
            
            // Afficher les résultats
            if (resultsContainer) {
                resultsContainer.classList.remove('d-none');
            }
            if (noResultsContainer) {
                noResultsContainer.classList.add('d-none');
            }
            
            // Activer automatiquement l'onglet approprié
            try {
                if (data.resultats.reparations.length > 0) {
                    window.showResultTab('reparations-container');
                } else if (data.resultats.clients.length > 0) {
                    window.showResultTab('clients-container');
                } else if (data.resultats.commandes.length > 0) {
                    window.showResultTab('commandes-container');
                }
            } catch (e) {
                console.error('Erreur lors de l\'activation des onglets:', e);
            }
            
            // Ajouter les gestionnaires d'événements pour les boutons
            attachEventListeners();
        })
        .catch(error => {
            console.error('Erreur détaillée lors de la recherche:', error);
            console.error('Message d\'erreur:', error.message);
            console.error('Stack trace:', error.stack);
            
            // Afficher un message d'erreur plus précis
            if (resultsContainer) resultsContainer.classList.add('d-none');
            if (noResultsContainer) {
                noResultsContainer.classList.remove('d-none');
                const titleElement = noResultsContainer.querySelector('h6');
                const messageElement = noResultsContainer.querySelector('p');
                
                if (titleElement) titleElement.textContent = 'Erreur de recherche';
                if (messageElement) {
                    messageElement.innerHTML = `
                        <div class="alert alert-danger">
                            <strong>Message d'erreur:</strong> ${error.message}
                            <br><small>Veuillez réessayer ultérieurement ou contacter le support.</small>
                        </div>
                    `;
                }
            }
        });
    }
    
    // Fonction pour formater le statut d'une réparation
    function formatStatus(statut) {
        const statusMap = {
            '1': 'Reçu',
            '2': 'En cours',
            '3': 'Terminé',
            '4': 'Livré',
            'en_attente': 'En attente',
            'en_cours': 'En cours',
            'termine': 'Terminé',
            'livre': 'Livré'
        };
        
        return statusMap[statut] || statut;
    }
    
    // Fonction pour obtenir la couleur du statut
    function getStatusColor(statut) {
        const colorMap = {
            '1': 'info',
            '2': 'warning',
            '3': 'success',
            '4': 'secondary',
            'en_attente': 'info',
            'en_cours': 'warning',
            'termine': 'success',
            'livre': 'secondary'
        };
        
        return colorMap[statut] || 'primary';
    }
    
    // Fonction pour formater le statut d'une commande
    function formatCommandeStatus(statut) {
        const statusMap = {
            'en_attente': 'En attente',
            'commande': 'Commandé',
            'recue': 'Reçue',
            'annulee': 'Annulée',
            'urgent': 'Urgente'
        };
        
        return statusMap[statut] || statut;
    }
    
    // Fonction pour obtenir la couleur du statut d'une commande
    function getCommandeStatusColor(statut) {
        const colorMap = {
            'en_attente': 'info',
            'commande': 'primary',
            'recue': 'success',
            'annulee': 'danger',
            'urgent': 'warning'
        };
        
        return colorMap[statut] || 'secondary';
    }
    
    // Fonction pour formater une date
    function formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString; // Si la date est invalide
        
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }
    
    // Fonction pour attacher les gestionnaires d'événements aux boutons
    function attachEventListeners() {
        // Pour les clients
        document.querySelectorAll('.voir-client-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const clientId = this.getAttribute('data-id');
                const clientNom = this.getAttribute('data-nom');
                const clientPrenom = this.getAttribute('data-prenom');
                const clientTelephone = this.getAttribute('data-telephone');
                
                // Fermer le modal de recherche
                const rechercheModal = bootstrap.Modal.getInstance(document.getElementById('rechercheAvanceeModal'));
                if (rechercheModal) {
                    rechercheModal.hide();
                }
                
                // Mettre à jour les informations du client dans le modal client
                document.querySelector('#clientInfoModal .client-nom').textContent = `${clientNom} ${clientPrenom}`;
                document.querySelector('#clientInfoModal .client-telephone').innerHTML = `<i class="fas fa-phone-alt me-1"></i> ${clientTelephone}`;
                
                // Configurer les boutons d'action
                document.querySelector('#clientInfoModal .btn-appeler').href = `tel:${clientTelephone}`;
                document.querySelector('#clientInfoModal .btn-sms').href = `sms:${clientTelephone}`;
                document.querySelector('#clientInfoModal .btn-editer-client').href = `index.php?page=modifier_client&id=${clientId}`;
                
                // Charger l'historique des réparations et commandes
                chargerHistoriqueClient(clientId);
                
                // Configurer le bouton de nouvelle réparation
                document.getElementById('nouvelle-reparation-client').href = `index.php?page=ajouter_reparation&client_id=${clientId}`;
                document.getElementById('nouvelle-commande-client').href = `index.php?page=nouvelle_commande&client_id=${clientId}`;
                
                // Ouvrir le modal client
                const clientInfoModal = new bootstrap.Modal(document.getElementById('clientInfoModal'));
                clientInfoModal.show();
            });
        });
        
        // Pour les réparations
        document.querySelectorAll('.voir-reparation-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const reparationId = this.getAttribute('data-id');
                
                // Redirection vers la page des réparations avec le paramètre pour ouvrir le modal
                window.location.href = `index.php?page=reparations&showRepId=${reparationId}`;
            });
        });
        
        // Pour les commandes
        document.querySelectorAll('.voir-commande-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const commandeId = this.getAttribute('data-id');
                
                // Fermer le modal de recherche
                const rechercheModal = bootstrap.Modal.getInstance(document.getElementById('rechercheAvanceeModal'));
                if (rechercheModal) {
                    rechercheModal.hide();
                }
                
                // Charger les détails de la commande
                chargerDetailsCommande(commandeId);
                
                // Ouvrir le modal de commande
                const commandeInfoModal = new bootstrap.Modal(document.getElementById('commandeInfoModal'));
                commandeInfoModal.show();
            });
        });
    }
    
    // Fonction pour charger l'historique client (réparations et commandes)
    function chargerHistoriqueClient(clientId) {
        // Charger l'historique des réparations
        fetch('ajax/get_client_reparations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `client_id=${clientId}`
        })
        .then(response => response.json())
        .then(data => {
            const historiqueReparations = document.getElementById('historique_reparations');
            if (!historiqueReparations) return;
            
            historiqueReparations.innerHTML = '';
            
            if (data.success && data.reparations && data.reparations.length > 0) {
                data.reparations.forEach(rep => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${rep.id}</td>
                        <td>${rep.appareil} ${rep.modele}</td>
                        <td>${formatDate(rep.date_reception)}</td>
                        <td><span class="badge bg-${getStatusColor(rep.statut)}">${formatStatus(rep.statut)}</span></td>
                        <td>
                            <a href="index.php?page=modifier_reparation&id=${rep.id}" class="btn btn-sm btn-outline-primary me-1">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="index.php?page=statut_rapide&id=${rep.id}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    `;
                    historiqueReparations.appendChild(row);
                });
            } else {
                historiqueReparations.innerHTML = `<tr><td colspan="5" class="text-center">Aucune réparation trouvée</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des réparations:', error);
            document.getElementById('historique_reparations').innerHTML = `
                <tr><td colspan="5" class="text-center text-danger">Erreur lors du chargement des réparations</td></tr>
            `;
        });
        
        // Charger l'historique des commandes
        fetch('ajax/get_client_commandes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `client_id=${clientId}`
        })
        .then(response => response.json())
        .then(data => {
            const historiqueCommandes = document.getElementById('historique_commandes');
            if (!historiqueCommandes) return;
            
            historiqueCommandes.innerHTML = '';
            
            if (data.success && data.commandes && data.commandes.length > 0) {
                data.commandes.forEach(cmd => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${cmd.nom_piece}</td>
                        <td>${formatDate(cmd.date_creation)}</td>
                        <td><span class="badge bg-${getCommandeStatusColor(cmd.statut)}">${formatCommandeStatus(cmd.statut)}</span></td>
                        <td>
                            <a href="index.php?page=commandes_pieces&action=modifier&id=${cmd.id}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    `;
                    historiqueCommandes.appendChild(row);
                });
            } else {
                historiqueCommandes.innerHTML = `<tr><td colspan="4" class="text-center">Aucune commande trouvée</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des commandes:', error);
            document.getElementById('historique_commandes').innerHTML = `
                <tr><td colspan="4" class="text-center text-danger">Erreur lors du chargement des commandes</td></tr>
            `;
        });
    }
    
    // Fonction pour charger les détails d'une réparation
    function chargerDetailsReparation(reparationId) {
        const detailsContainer = document.getElementById('details-reparation-content');
        if (!detailsContainer) return;
        
        // Afficher un indicateur de chargement
        detailsContainer.innerHTML = `
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-3">Chargement des détails...</p>
            </div>
        `;
        
        // Charger les détails via AJAX
        fetch('ajax/get_reparation_details.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${reparationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.reparation) {
                throw new Error(data.message || 'Erreur lors du chargement des détails de la réparation');
            }
            
            const rep = data.reparation;
            
            // Afficher les détails
            detailsContainer.innerHTML = `
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                ${rep.appareil} ${rep.modele}
                                <span class="badge bg-${getStatusColor(rep.statut)} ms-2">${formatStatus(rep.statut)}</span>
                            </h5>
                            <div>
                                <a href="index.php?page=modifier_reparation&id=${rep.id}" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-edit me-1"></i>Modifier
                                </a>
                                <a href="index.php?page=statut_rapide&id=${rep.id}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye me-1"></i>Détails
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Client</p>
                                <p class="mb-3 fw-bold">${rep.client_nom} ${rep.client_prenom}</p>
                                
                                <p class="mb-1 text-muted">Date de réception</p>
                                <p class="mb-3 fw-bold">${formatDate(rep.date_reception)}</p>
                                
                                <p class="mb-1 text-muted">Prix</p>
                                <p class="mb-0 fw-bold">${rep.prix || '0'}€</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Appareil</p>
                                <p class="mb-3 fw-bold">${rep.appareil} ${rep.modele}</p>
                                
                                <p class="mb-1 text-muted">Problème</p>
                                <p class="mb-0 fw-bold">${rep.probleme || 'Non spécifié'}</p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <p class="mb-1 text-muted">Diagnostic</p>
                            <p class="mb-0">${rep.diagnostic || 'Aucun diagnostic enregistré'}</p>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails de la réparation:', error);
            detailsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Erreur: ${error.message}
                </div>
                <div class="text-center mt-3">
                    <a href="index.php?page=statut_rapide&id=${reparationId}" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Voir la page complète
                    </a>
                </div>
            `;
        });
    }
    
    // Fonction pour charger les détails d'une commande
    function chargerDetailsCommande(commandeId) {
        const detailsContainer = document.getElementById('details-commande-content');
        if (!detailsContainer) return;
        
        // Afficher un indicateur de chargement
        detailsContainer.innerHTML = `
            <div class="text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-3">Chargement des détails...</p>
            </div>
        `;
        
        // Charger les détails via AJAX
        fetch('ajax/get_commande_details.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${commandeId}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.commande) {
                throw new Error(data.message || 'Erreur lors du chargement des détails de la commande');
            }
            
            const cmd = data.commande;
            
            // Afficher les détails
            detailsContainer.innerHTML = `
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                Commande #${cmd.id}
                                <span class="badge bg-${getCommandeStatusColor(cmd.statut)} ms-2">${formatCommandeStatus(cmd.statut)}</span>
                            </h5>
                            <div>
                                <a href="index.php?page=commandes_pieces&action=modifier&id=${cmd.id}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit me-1"></i>Modifier
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Client</p>
                                <p class="mb-3 fw-bold">${cmd.client_nom} ${cmd.client_prenom}</p>
                                
                                <p class="mb-1 text-muted">Date de commande</p>
                                <p class="mb-3 fw-bold">${formatDate(cmd.date_creation)}</p>
                                
                                <p class="mb-1 text-muted">Prix</p>
                                <p class="mb-0 fw-bold">${cmd.prix || '0'}€</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1 text-muted">Pièce</p>
                                <p class="mb-3 fw-bold">${cmd.nom_piece || 'Non spécifié'}</p>
                                
                                <p class="mb-1 text-muted">Référence</p>
                                <p class="mb-0 fw-bold">${cmd.reference || 'Non spécifié'}</p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <p class="mb-1 text-muted">Notes</p>
                            <p class="mb-0">${cmd.notes || 'Aucune note'}</p>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Erreur lors du chargement des détails de la commande:', error);
            detailsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Erreur: ${error.message}
                </div>
                <div class="text-center mt-3">
                    <a href="index.php?page=commandes_pieces&action=voir&id=${commandeId}" class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Voir la page complète
                    </a>
                </div>
            `;
        });
    }
    
    // Événement input pour la recherche avec debounce
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(timeoutRecherche);
            const terme = this.value.trim();
            
            timeoutRecherche = setTimeout(() => {
                rechercheAvancee(terme);
            }, 500); // 500ms de délai
        });
    }
    
    // Événement clic pour le bouton de recherche
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            const terme = searchInput.value.trim();
            rechercheAvancee(terme);
        });
    }
    
    // Événement entrée pour lancer la recherche
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const terme = this.value.trim();
                rechercheAvancee(terme);
            }
        });
    }
    
    // Initialiser le bouton de correction d'urgence
    addEmergencyFixButton();
    
    // Ajouter un écouteur d'événement pour le modal de recherche
    const rechercheModal = document.getElementById('rechercheAvanceeModal');
    if (rechercheModal) {
        // Ajouter la classe futuristic-modal si elle n'existe pas déjà
        if (!rechercheModal.classList.contains('futuristic-modal')) {
            rechercheModal.classList.add('futuristic-modal');
        }
        
        // Ajouter l'effet de fade-in aux résultats
        if (resultsContainer) {
            resultsContainer.classList.add('fade-in-sequence');
        }
        
        // Ajouter des effets aux champs et boutons
        if (searchInput && searchButton) {
            // Ajouter un effet spécial sur le focus
            searchInput.addEventListener('focus', function() {
                this.classList.add('holographic');
                searchButton.classList.add('pulse-effect');
            });
            
            searchInput.addEventListener('blur', function() {
                this.classList.remove('holographic');
                searchButton.classList.remove('pulse-effect');
            });
        }
        
        // Ajouter effet de pulse aux boutons d'onglets
        const tabButtons = document.querySelectorAll('#rechercheBtns button');
        tabButtons.forEach(button => {
            button.classList.add('pulse-effect');
        });
        
        // Ajouter des particules après l'affichage du modal
        if (typeof createParticles === 'function') {
            rechercheModal.addEventListener('shown.bs.modal', function() {
                createParticles(rechercheModal);
                
                // Forcer l'affichage des tableaux après un court délai
                setTimeout(function() {
                    // Réactiver l'onglet actif si des résultats sont déjà présents
                    const clientsCount = parseInt(document.getElementById('count-clients')?.textContent || '0');
                    const repsCount = parseInt(document.getElementById('count-reparations')?.textContent || '0');
                    const cmdsCount = parseInt(document.getElementById('count-commandes')?.textContent || '0');
                    
                    if (repsCount > 0) {
                        window.showResultTab('reparations-container');
                    } else if (clientsCount > 0) {
                        window.showResultTab('clients-container');
                    } else if (cmdsCount > 0) {
                        window.showResultTab('commandes-container');
                    }
                }, 300);
            });
        }
        
        // Modifier le comportement de la recherche pour inclure des effets
        const originalSearchFunction = window.performRechercheAvancee;
        if (originalSearchFunction) {
            window.performRechercheAvancee = function() {
                // Appliquer un effet holographique pendant la recherche
                if (typeof startProcessingEffect === 'function') {
                    startProcessingEffect('rechercheAvanceeModal')
                        .then(() => {
                            // Exécuter la fonction de recherche originale
                            originalSearchFunction();
                            
                            // Ajouter une classe pour l'animation de fade-in
                            if (resultsContainer) {
                                resultsContainer.classList.add('fade-in');
                                setTimeout(() => {
                                    resultsContainer.classList.remove('fade-in');
                                }, 1000);
                            }
                        });
                } else {
                    // Exécuter directement si l'effet n'est pas disponible
                    originalSearchFunction();
                }
            };
        }
        
        // Remplacer la fonction showResultTab pour ajouter des effets
        window.originalShowResultTab = window.showResultTab;
        window.showResultTab = function(tabId) {
            // Appliquer l'effet holographique avant de changer d'onglet
            if (typeof startProcessingEffect === 'function') {
                startProcessingEffect('rechercheAvanceeModal')
                    .then(() => {
                        // Exécuter la fonction originale
                        if (window.originalShowResultTab) {
                            window.originalShowResultTab(tabId);
                        } else {
                            // Comportement par défaut si la fonction originale n'est pas disponible
                            document.querySelectorAll('.result-container').forEach(container => {
                                container.style.display = 'none';
                            });
                            
                            const targetContainer = document.getElementById(tabId);
                            if (targetContainer) {
                                targetContainer.style.display = 'block';
                            }
                            
                            document.querySelectorAll('#rechercheBtns button').forEach(btn => {
                                btn.classList.remove('btn-primary');
                                btn.classList.add('btn-outline-primary');
                            });
                            
                            if (tabId === 'clients-container') {
                                document.getElementById('btn-clients').classList.remove('btn-outline-primary');
                                document.getElementById('btn-clients').classList.add('btn-primary');
                            } else if (tabId === 'reparations-container') {
                                document.getElementById('btn-reparations').classList.remove('btn-outline-primary');
                                document.getElementById('btn-reparations').classList.add('btn-primary');
                            } else if (tabId === 'commandes-container') {
                                document.getElementById('btn-commandes').classList.remove('btn-outline-primary');
                                document.getElementById('btn-commandes').classList.add('btn-primary');
                            }
                        }
                        
                        // Ajouter un effet de fade-in au conteneur affiché
                        const targetContainer = document.getElementById(tabId);
                        if (targetContainer) {
                            targetContainer.classList.add('fade-in');
                            setTimeout(() => {
                                targetContainer.classList.remove('fade-in');
                            }, 1000);
                        }
                    });
            } else if (window.originalShowResultTab) {
                // Exécuter directement si l'effet n'est pas disponible
                window.originalShowResultTab(tabId);
            }
        };
    }
    
    // Fonction pour forcer l'affichage des conteneurs de résultats
    function forceShowTabContent(containerId) {
        console.log(`Forçage de l'affichage du contenu pour le conteneur ${containerId}`);
        
        // Trouver le conteneur
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Conteneur ${containerId} introuvable`);
            return;
        }
        
        // Force le style d'affichage
        container.style.display = 'block';
        container.style.visibility = 'visible';
        container.style.opacity = '1';
        
        // Forcer l'affichage du tableau à l'intérieur
        const tableContainer = container.querySelector('.table-responsive');
        if (tableContainer) {
            tableContainer.style.display = 'block';
            tableContainer.style.visibility = 'visible';
            tableContainer.style.opacity = '1';
            tableContainer.style.height = 'auto';
            tableContainer.style.minHeight = '200px';
            tableContainer.style.overflow = 'auto';
            
            // Forcer le tableau lui-même
            const table = tableContainer.querySelector('table');
            if (table) {
                table.style.display = 'table';
                table.style.visibility = 'visible';
                table.style.opacity = '1';
                table.style.width = '100%';
            }
        }
        
        // Déclencher un événement de redimensionnement pour forcer le rendu
        window.dispatchEvent(new Event('resize'));
    }
    
    // Ajouter un bouton de correction d'urgence
    function addEmergencyFixButton() {
        // Vérifier si le bouton existe déjà
        if (document.getElementById('emergency-fix-btn')) return;
        
        // Créer le bouton
        const fixButton = document.createElement('button');
        fixButton.id = 'emergency-fix-btn';
        fixButton.className = 'btn btn-warning position-absolute';
        fixButton.style.top = '0';
        fixButton.style.right = '0';
        fixButton.style.zIndex = '9999';
        fixButton.style.margin = '5px';
        fixButton.style.padding = '5px 10px';
        fixButton.style.fontSize = '12px';
        fixButton.textContent = 'Afficher';
        fixButton.title = 'Cliquez pour forcer l\'affichage des tableaux';
        
        // Ajouter le gestionnaire d'événement
        fixButton.addEventListener('click', function() {
            // Forcer l'affichage de tous les onglets
            forceShowTabContent('clients-container');
            forceShowTabContent('reparations-container');
            forceShowTabContent('commandes-container');
            
            // Activer l'onglet approprié
            try {
                const activeButton = document.querySelector('#rechercheBtns .btn-primary');
                if (activeButton) {
                    const targetId = activeButton.getAttribute('data-target') || 
                                    (activeButton.id === 'btn-clients' ? 'clients-container' :
                                     activeButton.id === 'btn-reparations' ? 'reparations-container' :
                                     activeButton.id === 'btn-commandes' ? 'commandes-container' : null);
                    
                    if (targetId) {
                        window.showResultTab(targetId);
                    }
                }
            } catch (e) {
                console.error('Erreur lors de l\'activation de l\'onglet actif:', e);
            }
        });
        
        // Ajouter au modal
        const modalBody = document.querySelector('#rechercheAvanceeModal .modal-body');
        if (modalBody) {
            modalBody.style.position = 'relative';
            modalBody.appendChild(fixButton);
        }
    }
}); 