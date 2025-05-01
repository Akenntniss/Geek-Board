// Module de recherche client pour GestiRep
// Ce fichier gère la recherche client dans les différents contextes de l'application

// Fonction pour sélectionner un client (définie globalement)
window.selectClient = function(clientId, clientName) {
    console.log("Sélection du client:", { clientId, clientName });
    
    // Tenter de trouver les éléments dans le contexte actuel
    const clientIdInput = document.getElementById('client_id');
    const nomClientSelectionne = document.getElementById('nom_client_selectionne');
    const clientSelectionneDiv = document.getElementById('client_selectionne');
    const resultatsDiv = document.getElementById('resultats_clients');
    
    if (clientIdInput) clientIdInput.value = clientId;
    if (nomClientSelectionne) nomClientSelectionne.textContent = clientName;
    if (clientSelectionneDiv) clientSelectionneDiv.classList.remove('d-none');
    if (resultatsDiv) resultatsDiv.classList.add('d-none');
}

// Fonction qui initialise la recherche client dans un contexte donné
function initRechercheClient(context) {
    // Déterminer le préfixe ID en fonction du contexte
    let prefix = '';
    if (context === 'commande') {
        prefix = 'commande_';
    } else if (context === 'reparation') {
        prefix = 'reparation_';
    }
    
    // Trouver les éléments avec le préfixe approprié
    const searchInput = document.getElementById(`recherche_client_${prefix}piece`);
    if (!searchInput) {
        console.log(`Recherche client (${context}): Élément de recherche non trouvé`);
        return false;
    }
    
    const resultatsDiv = document.getElementById('resultats_clients');
    const noResultsDiv = document.getElementById('no_results');
    const clientSelectionneDiv = document.getElementById(`client_selectionne${prefix ? '_' + prefix : ''}`);
    const nomClientSelectionne = document.getElementById(`nom_client_selectionne${prefix ? '_' + prefix : ''}`);
    const clientIdInput = document.getElementById(`client_id${prefix ? '_' + prefix : ''}`);
    const resetClientBtn = document.getElementById(`reset_client${prefix ? '_' + prefix : ''}`);
    
    console.log(`Configuration de recherche client (${context}):`, {
        searchInput: searchInput.id,
        resultatsExistent: !!resultatsDiv,
        noResultsExistent: !!noResultsDiv,
        clientSelectionneExiste: !!clientSelectionneDiv,
        nomClientSelectionneExiste: !!nomClientSelectionne,
        clientIdInputExiste: !!clientIdInput,
        resetBtnExiste: !!resetClientBtn
    });
    
    // Configuration de la recherche
    if (searchInput) {
        let timeoutId;
        searchInput.addEventListener('input', function() {
            clearTimeout(timeoutId);
            const query = this.value.trim();
            
            if (query.length < 2) {
                if (resultatsDiv) resultatsDiv.classList.add('d-none');
                if (noResultsDiv) noResultsDiv.classList.add('d-none');
                return;
            }
            
            timeoutId = setTimeout(() => {
                // Recherche AJAX
                fetch('ajax/recherche_clients.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `terme=${encodeURIComponent(query)}`
                })
                .then(response => response.json())
                .then(data => {
                    const listeClients = document.getElementById('liste_clients');
                    if (!listeClients) return;
                    
                    // Effacer les résultats précédents
                    listeClients.innerHTML = '';
                    
                    if (data.success && data.clients && data.clients.length > 0) {
                        // Ajouter les clients trouvés
                        data.clients.forEach(client => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${client.nom || ''}</td>
                                <td>${client.prenom || ''}</td>
                                <td>${client.telephone || ''}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary selectionner-client" 
                                        data-id="${client.id}" 
                                        data-nom="${client.nom}" 
                                        data-prenom="${client.prenom}">
                                        <i class="fas fa-check me-1"></i>Sélectionner
                                    </button>
                                </td>
                            `;
                            listeClients.appendChild(row);
                        });
                        
                        // Ajouter les écouteurs d'événements pour les boutons
                        document.querySelectorAll('#liste_clients .selectionner-client').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const id = this.getAttribute('data-id');
                                const nom = this.getAttribute('data-nom');
                                const prenom = this.getAttribute('data-prenom');
                                
                                if (clientIdInput) clientIdInput.value = id;
                                if (nomClientSelectionne) nomClientSelectionne.textContent = `${nom} ${prenom}`;
                                if (clientSelectionneDiv) clientSelectionneDiv.classList.remove('d-none');
                                if (resultatsDiv) resultatsDiv.classList.add('d-none');
                                if (searchInput) searchInput.value = '';
                            });
                        });
                        
                        if (resultatsDiv) resultatsDiv.classList.remove('d-none');
                        if (noResultsDiv) noResultsDiv.classList.add('d-none');
                    } else {
                        if (resultatsDiv) resultatsDiv.classList.add('d-none');
                        if (noResultsDiv) noResultsDiv.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    console.error('Erreur de recherche client:', error);
                });
            }, 300);
        });
    }
    
    // Configuration du bouton de réinitialisation
    if (resetClientBtn) {
        resetClientBtn.addEventListener('click', function() {
            if (clientIdInput) clientIdInput.value = '';
            if (clientSelectionneDiv) clientSelectionneDiv.classList.add('d-none');
            if (searchInput) searchInput.value = '';
            if (resultatsDiv) resultatsDiv.classList.add('d-none');
            if (noResultsDiv) noResultsDiv.classList.add('d-none');
        });
    }
    
    return true;
}

// Initialisation quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    console.log("Initialisation de la recherche client");
    
    // Tenter d'initialiser les différents modules de recherche
    const commandeSuccess = initRechercheClient('commande');
    const standardSuccess = initRechercheClient('standard');
    
    if (!commandeSuccess && !standardSuccess) {
        console.log("Aucun module de recherche client n'a pu être initialisé");
    }
}); 