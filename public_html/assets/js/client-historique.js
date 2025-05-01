/**
 * Gestion de la recherche de client et de l'affichage de son historique
 * Compatible avec la recherche avancée
 */

// Initialisation lorsque le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log("Initialisation du module de recherche client et historique");
    
    // Injecter le CSS pour corriger les problèmes d'affichage
    injectFixCSS();
    
    // Configurer les boutons d'action
    setupActionButtons();
    
    // Adaptation pour vérifier si nous sommes en mode recherche avancée
    if (document.getElementById('recherche_avancee')) {
        console.log("Mode recherche avancée détecté - pas de diagnostic d'anciens éléments nécessaire");
    } else {
        // Test d'initialisation pour vérifier les éléments de l'ancienne recherche
        runDiagnostics();
        
        // Configurer l'ancienne recherche de client si elle existe
        setupClientHistorySearch();
    }
});

// Injecter du CSS pour corriger les problèmes d'affichage
function injectFixCSS() {
    const style = document.createElement('style');
    style.textContent = `
        /* Fix pour les résultats de recherche client */
        #resultats_recherche_client {
            display: block !important;
        }
        
        #resultats_recherche_client.d-none {
            display: block !important;
        }
        
        /* Amélioration de la visibilité des lignes de résultat */
        #liste_clients_recherche tr {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        #liste_clients_recherche tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }
        
        /* Style pour les messages d'erreur */
        .text-danger {
            color: #dc3545 !important;
        }
    `;
    document.head.appendChild(style);
    console.log("CSS de correction injecté");
}

// Exécuter des tests de diagnostic pour l'ancienne UI
function runDiagnostics() {
    console.log("Exécution des diagnostics de la fonctionnalité de recherche client...");
    
    // Éléments essentiels à vérifier
    const elementsToCheck = [
        { id: 'recherche_client_historique', type: 'input', name: 'Champ de recherche' },
        { id: 'btn-recherche-client-historique', type: 'button', name: 'Bouton de recherche' },
        { id: 'resultats_recherche_client', type: 'div', name: 'Conteneur des résultats' },
        { id: 'liste_clients_recherche', type: 'tbody', name: 'Liste des clients trouvés' },
        { id: 'aucun_client_trouve', type: 'div', name: 'Message aucun résultat' },
        { id: 'info_client_selectionne', type: 'div', name: 'Information du client sélectionné' },
        { id: 'rechercheClientModal', type: 'div', name: 'Modal de recherche' }
    ];
    
    // Vérifier chaque élément
    let allElementsFound = true;
    elementsToCheck.forEach(element => {
        const el = document.getElementById(element.id);
        const found = el !== null;
        console.log(`${element.name} (${element.id}): ${found ? 'TROUVÉ ✅' : 'MANQUANT ❌'}`);
        
        if (!found) {
            allElementsFound = false;
        } else {
            // Vérifier si c'est bien l'élément attendu
            console.log(`  - Type: ${el.tagName.toLowerCase()}, Classes: ${el.className}`);
            
            // Si c'est le tbody des résultats, vérifier sa structure parente
            if (element.id === 'liste_clients_recherche') {
                const parent = el.parentElement;
                const grandParent = parent ? parent.parentElement : null;
                
                console.log(`  - Parent: ${parent ? parent.tagName : 'AUCUN'}`);
                console.log(`  - Grand-parent: ${grandParent ? grandParent.tagName : 'AUCUN'}`);
                console.log(`  - Est visible: ${isElementVisible(el) ? 'OUI' : 'NON'}`);
            }
        }
    });
    
    if (!allElementsFound) {
        console.log("ℹ️ Certains éléments de l'ancienne recherche sont manquants - Vous utilisez probablement la recherche avancée");
    } else {
        console.log("✅ Tous les éléments nécessaires ont été trouvés dans le DOM");
    }
}

// Fonction utilitaire pour vérifier si un élément est visible
function isElementVisible(el) {
    if (!el) return false;
    
    const rect = el.getBoundingClientRect();
    const isNotHidden = window.getComputedStyle(el).display !== 'none';
    const hasSize = rect.width > 0 && rect.height > 0;
    
    // Vérifier également tous les parents
    let currentEl = el;
    while (currentEl) {
        const style = window.getComputedStyle(currentEl);
        if (style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0') {
            return false;
        }
        currentEl = currentEl.parentElement;
    }
    
    return isNotHidden && hasSize;
}

// Configurer la recherche de client (ancienne version)
function setupClientHistorySearch() {
    console.log("Configuration de la recherche client");
    
    // Champ de recherche
    const searchInput = document.getElementById('recherche_client_historique');
    const searchButton = document.getElementById('btn-recherche-client-historique');
    
    console.log("Éléments recherchés:", {
        searchInput: searchInput ? true : false,
        searchButton: searchButton ? true : false
    });
    
    // Si les éléments n'existent pas, on ignore (recherche avancée utilisée à la place)
    if (!searchInput || !searchButton) {
        console.log("Éléments de recherche introuvables - la recherche client classique est désactivée");
        return;
    }
    
    // Recherche au clic sur le bouton
    searchButton.addEventListener('click', function() {
        console.log("Bouton de recherche cliqué");
        const query = searchInput.value.trim();
        console.log("Requête de recherche:", query);
        
        if (query.length >= 2) {
            searchClientsHistory(query);
        } else {
            alert('Veuillez saisir au moins 2 caractères pour la recherche');
        }
    });
    
    // Recherche en appuyant sur Entrée
    searchInput.addEventListener('keyup', function(event) {
        console.log("Keyup sur le champ de recherche, touche:", event.key);
        
        if (event.key === 'Enter') {
            const query = this.value.trim();
            console.log("Requête de recherche (Enter):", query);
            
            if (query.length >= 2) {
                searchClientsHistory(query);
            } else {
                alert('Veuillez saisir au moins 2 caractères pour la recherche');
            }
        } else if (this.value.trim().length >= 2) {
            // Recherche automatique après 500ms d'inactivité
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                console.log("Recherche automatique déclenchée");
                searchClientsHistory(this.value.trim());
            }, 500);
        }
    });
}

// Rechercher des clients (ancienne version)
function searchClientsHistory(query) {
    console.log("Lancement de la recherche pour:", query);
    
    // Récupérer les éléments du DOM
    const resultsContainer = document.getElementById('resultats_recherche_client');
    const clientsList = document.getElementById('liste_clients_recherche');
    const noResultsContainer = document.getElementById('aucun_client_trouve');
    
    // Vérification des éléments critiques
    if (!resultsContainer || !clientsList) {
        console.log("Éléments de résultat introuvables - la recherche avancée est probablement utilisée");
        return;
    }
    
    // Forcer l'affichage du conteneur des résultats
    resultsContainer.style.display = 'block';
    resultsContainer.classList.remove('d-none');
    
    // Afficher un indicateur de chargement
    clientsList.innerHTML = `
        <tr>
            <td colspan="4" class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
            </td>
        </tr>
    `;
    
    // Masquer les autres sections
    if (noResultsContainer) noResultsContainer.classList.add('d-none');
    const infoClientContainer = document.getElementById('info_client_selectionne');
    if (infoClientContainer) infoClientContainer.classList.add('d-none');
    
    // Utiliser Fetch API pour la recherche
    fetch(`ajax/search_clients.php?query=${encodeURIComponent(query)}`)
        .then(response => {
            console.log("Réponse reçue:", response.status);
            return response.json();
        })
        .then(data => {
            console.log("Résultats reçus:", data);
            processClientHistorySearchResults(data);
        })
        .catch(error => {
            console.error("Erreur lors de la recherche:", error);
            clientsList.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Erreur lors de la recherche. Veuillez réessayer.
                    </td>
                </tr>
            `;
        });
}

// Traiter les résultats de recherche
function processClientHistorySearchResults(data) {
    // Cette fonction reste inchangée car elle est appelée par l'ancienne recherche uniquement
    // Si on utilise la recherche avancée, cette fonction n'est pas utilisée
    
    const clientsList = document.getElementById('liste_clients_recherche');
    const resultsContainer = document.getElementById('resultats_recherche_client');
    const noResultsContainer = document.getElementById('aucun_client_trouve');
    
    if (!clientsList || !resultsContainer) {
        console.log("Éléments de résultat non trouvés - ignoré");
        return;
    }
    
    // Vider la liste
    clientsList.innerHTML = '';
    
    if (data.success && data.clients && data.clients.length > 0) {
        console.log(`${data.clients.length} clients trouvés`);
        
        // Afficher les résultats
        data.clients.forEach(client => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${client.nom || ''}</td>
                <td>${client.prenom || ''}</td>
                <td>${client.telephone || ''}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary selectionner-client" 
                        data-id="${client.id}" 
                        data-nom="${client.nom || ''}" 
                        data-prenom="${client.prenom || ''}"
                        data-tel="${client.telephone || ''}">
                        <i class="fas fa-check me-1"></i>Sélectionner
                    </button>
                </td>
            `;
            clientsList.appendChild(row);
        });
        
        // Ajouter les gestionnaires d'événements
        document.querySelectorAll('.selectionner-client').forEach(button => {
            button.addEventListener('click', function() {
                const clientId = this.getAttribute('data-id');
                const nom = this.getAttribute('data-nom');
                const prenom = this.getAttribute('data-prenom');
                const telephone = this.getAttribute('data-tel');
                
                console.log(`Client sélectionné: ${prenom} ${nom} (ID: ${clientId})`);
                afficherHistoriqueClient(clientId, nom, prenom, telephone);
            });
        });
        
        // Afficher les résultats
        if (resultsContainer) resultsContainer.classList.remove('d-none');
        if (noResultsContainer) noResultsContainer.classList.add('d-none');
    } else {
        console.log("Aucun client trouvé");
        
        // Afficher le message "aucun résultat"
        if (resultsContainer) resultsContainer.classList.add('d-none');
        if (noResultsContainer) {
            noResultsContainer.classList.remove('d-none');
            
            // Vérifier s'il y a un bouton pour ajouter un nouveau client
            const btnNouveauClient = document.getElementById('btn_nouveau_client');
            if (btnNouveauClient) {
                btnNouveauClient.addEventListener('click', function() {
                    window.location.href = 'index.php?page=ajouter_client';
                });
            }
        }
    }
}

// Afficher l'historique d'un client
function afficherHistoriqueClient(clientId, nom, prenom, telephone) {
    console.log(`Affichage de l'historique pour le client ${prenom} ${nom} (ID: ${clientId})`);
    
    // Récupérer les éléments
    const resultsContainer = document.getElementById('resultats_recherche_client');
    const infoClientContainer = document.getElementById('info_client_selectionne');
    
    if (!infoClientContainer) {
        console.log("Conteneur d'info client non trouvé - ignoré");
        return;
    }
    
    // Masquer les résultats de recherche
    if (resultsContainer) resultsContainer.classList.add('d-none');
    
    // Mettre à jour les informations du client
    const clientNomElement = infoClientContainer.querySelector('.client-nom');
    const clientTelephoneElement = infoClientContainer.querySelector('.client-telephone');
    
    if (clientNomElement) clientNomElement.textContent = `${prenom} ${nom}`;
    if (clientTelephoneElement) {
        clientTelephoneElement.innerHTML = `<i class="fas fa-phone-alt me-1"></i> ${telephone}`;
    }
    
    // Mettre à jour les boutons d'action
    const btnAppeler = infoClientContainer.querySelector('.btn-appeler');
    const btnSms = infoClientContainer.querySelector('.btn-sms');
    const btnEditer = infoClientContainer.querySelector('.btn-editer-client');
    
    if (btnAppeler) btnAppeler.href = `tel:${telephone}`;
    if (btnSms) btnSms.href = `sms:${telephone}`;
    if (btnEditer) btnEditer.href = `index.php?page=modifier_client&id=${clientId}`;
    
    // Initialiser les onglets
    initializeClientHistoryTabs();
    
    // Charger l'historique des réparations et commandes
    chargerHistoriqueReparations(clientId);
    chargerHistoriqueCommandes(clientId);
    
    // Afficher le conteneur d'information
    infoClientContainer.classList.remove('d-none');
    
    // Configurer le bouton nouvelle réparation
    const btnNouvelleReparation = document.getElementById('nouvelle-reparation-client');
    if (btnNouvelleReparation) {
        btnNouvelleReparation.href = `index.php?page=ajouter_reparation&client_id=${clientId}`;
    }
    
    // Configurer le bouton nouvelle commande
    const btnNouvelleCommande = document.getElementById('nouvelle-commande-client');
    if (btnNouvelleCommande) {
        btnNouvelleCommande.href = `index.php?page=nouvelle_commande&client_id=${clientId}`;
    }
}

// Initialiser les onglets d'historique
function initializeClientHistoryTabs() {
    // Rien à faire ici pour le moment, Bootstrap gère les onglets
}

// Charger l'historique des réparations
function chargerHistoriqueReparations(clientId) {
    console.log(`Chargement des réparations pour le client ID: ${clientId}`);
    
    const historiqueContainer = document.getElementById('historique_reparations');
    if (!historiqueContainer) {
        console.log("Conteneur d'historique des réparations non trouvé - ignoré");
        return;
    }
    
    // Afficher un indicateur de chargement
    historiqueContainer.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mb-0 mt-2">Chargement des réparations...</p>
            </td>
        </tr>
    `;
    
    // Charger les réparations via AJAX
    fetch(`ajax/get_client_reparations.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `client_id=${clientId}`
    })
    .then(response => response.json())
    .then(data => {
        console.log("Réparations reçues:", data);
        
        // Vider le conteneur
        historiqueContainer.innerHTML = '';
        
        if (data.success && data.reparations && data.reparations.length > 0) {
            // Afficher les réparations
            data.reparations.forEach(reparation => {
                const row = document.createElement('tr');
                
                // Déterminer le badge de statut
                let statusBadge = '';
                let statusClass = 'secondary';
                
                switch(reparation.statut) {
                    case '1':
                    case 'en_attente':
                        statusClass = 'info';
                        statusBadge = 'En attente';
                        break;
                    case '2':
                    case 'en_cours':
                        statusClass = 'warning';
                        statusBadge = 'En cours';
                        break;
                    case '3':
                    case 'termine':
                        statusClass = 'success';
                        statusBadge = 'Terminé';
                        break;
                    case '4':
                    case 'livre':
                        statusClass = 'secondary';
                        statusBadge = 'Livré';
                        break;
                    default:
                        statusBadge = reparation.statut;
                }
                
                row.innerHTML = `
                    <td>${reparation.id}</td>
                    <td>${reparation.appareil || ''} ${reparation.modele || ''}</td>
                    <td>${formatDate(reparation.date_reception)}</td>
                    <td><span class="badge bg-${statusClass}">${statusBadge}</span></td>
                    <td>
                        <a href="index.php?page=modifier_reparation&id=${reparation.id}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="index.php?page=statut_rapide&id=${reparation.id}" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                `;
                
                historiqueContainer.appendChild(row);
            });
        } else {
            // Aucune réparation
            historiqueContainer.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3">
                        <p class="mb-0">Aucune réparation trouvée pour ce client</p>
                    </td>
                </tr>
            `;
        }
    })
    .catch(error => {
        console.error("Erreur lors du chargement des réparations:", error);
        historiqueContainer.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-3 text-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Erreur lors du chargement des réparations. Veuillez réessayer.
                </td>
            </tr>
        `;
    });
}

// Charger l'historique des commandes
function chargerHistoriqueCommandes(clientId) {
    console.log(`Chargement des commandes pour le client ID: ${clientId}`);
    
    const historiqueContainer = document.getElementById('historique_commandes');
    if (!historiqueContainer) {
        console.log("Conteneur d'historique des commandes non trouvé - ignoré");
        return;
    }
    
    // Afficher un indicateur de chargement
    historiqueContainer.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mb-0 mt-2">Chargement des commandes...</p>
            </td>
        </tr>
    `;
    
    // Charger les commandes via AJAX
    fetch(`ajax/get_client_commandes.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `client_id=${clientId}`
    })
    .then(response => response.json())
    .then(data => {
        console.log("Commandes reçues:", data);
        
        // Vider le conteneur
        historiqueContainer.innerHTML = '';
        
        if (data.success && data.commandes && data.commandes.length > 0) {
            // Afficher les commandes
            data.commandes.forEach(commande => {
                const row = document.createElement('tr');
                
                // Déterminer le badge de statut
                let statusBadge = '';
                let statusClass = 'secondary';
                
                switch(commande.statut) {
                    case 'en_attente':
                        statusClass = 'info';
                        statusBadge = 'En attente';
                        break;
                    case 'commande':
                        statusClass = 'primary';
                        statusBadge = 'Commandé';
                        break;
                    case 'recue':
                        statusClass = 'success';
                        statusBadge = 'Reçue';
                        break;
                    case 'annulee':
                        statusClass = 'danger';
                        statusBadge = 'Annulée';
                        break;
                    case 'urgent':
                        statusClass = 'warning';
                        statusBadge = 'Urgente';
                        break;
                    default:
                        statusBadge = commande.statut;
                }
                
                row.innerHTML = `
                    <td>${commande.nom_piece || 'N/A'}</td>
                    <td>${formatDate(commande.date_creation)}</td>
                    <td><span class="badge bg-${statusClass}">${statusBadge}</span></td>
                    <td>
                        <a href="index.php?page=commandes_pieces&action=modifier&id=${commande.id}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                `;
                
                historiqueContainer.appendChild(row);
            });
        } else {
            // Aucune commande
            historiqueContainer.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-3">
                        <p class="mb-0">Aucune commande trouvée pour ce client</p>
                    </td>
                </tr>
            `;
        }
    })
    .catch(error => {
        console.error("Erreur lors du chargement des commandes:", error);
        historiqueContainer.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-3 text-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Erreur lors du chargement des commandes. Veuillez réessayer.
                </td>
            </tr>
        `;
    });
}

// Configurer les boutons d'action
function setupActionButtons() {
    console.log("Configuration des boutons d'action");
    
    // Gérer le bouton de nouvelle commande
    const btnNouvelleCommande = document.getElementById('nouvelle-commande-client');
    if (btnNouvelleCommande) {
        console.log("Bouton nouvelle commande trouvé");
        btnNouvelleCommande.addEventListener('click', function(e) {
            // Vérifier s'il y a un client sélectionné
            const clientId = document.querySelector('input[name="client_id"]') ? 
                             document.querySelector('input[name="client_id"]').value : null;
            
            if (!clientId) {
                e.preventDefault();
                alert("Veuillez d'abord sélectionner un client");
            }
        });
    }
    
    // Gérer le bouton de nouvelle réparation
    const btnNouvelleReparation = document.getElementById('nouvelle-reparation-client');
    if (btnNouvelleReparation) {
        btnNouvelleReparation.addEventListener('click', function(e) {
            // Vérifier s'il y a un client sélectionné
            const clientId = document.querySelector('input[name="client_id"]') ? 
                             document.querySelector('input[name="client_id"]').value : null;
            
            if (!clientId) {
                e.preventDefault();
                alert("Veuillez d'abord sélectionner un client");
            }
        });
    }
}

// Fonction utilitaire pour formater les dates
function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;
    
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

// Fonction utilitaire pour formater les prix
function formatPrix(prix) {
    if (!prix) return '0,00 €';
    
    // Convertir en nombre
    const prixNum = parseFloat(prix);
    if (isNaN(prixNum)) return '0,00 €';
    
    // Formater avec 2 décimales et remplacer le point par une virgule
    return prixNum.toFixed(2).replace('.', ',') + ' €';
}

// Exposer les fonctions pour utilisation globale
window.afficherHistoriqueClient = afficherHistoriqueClient; 