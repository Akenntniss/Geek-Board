/**
 * Script pour gérer les fonctions liées aux clients
 */

// Charger les réparations dans le select au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    loadReparationsSelect();
});

// Fonction pour charger les réparations dans le select
function loadReparationsSelect() {
    const reparationSelect = document.getElementById('reparation_id');
    if (!reparationSelect) {
        console.error("Élément 'reparation_id' non trouvé dans le DOM");
        return;
    }
    
    console.log("Chargement des réparations dans le select...");
    
    fetch('ajax/get_reparations.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Conserver l'option par défaut
                const defaultOption = reparationSelect.options[0];
                reparationSelect.innerHTML = '';
                reparationSelect.appendChild(defaultOption);
                
                // Ajouter les réparations au select
                data.reparations.forEach(rep => {
                    const option = document.createElement('option');
                    option.value = rep.id;
                    option.setAttribute('data-client-id', rep.client_id);
                    option.setAttribute('data-client-nom', rep.client_nom || '');
                    option.setAttribute('data-client-prenom', rep.client_prenom || '');
                    
                    let displayText = `#${rep.id} - ${rep.type_appareil} ${rep.marque} ${rep.modele}`;
                    if (rep.client_nom) {
                        displayText += ` - ${rep.client_nom} ${rep.client_prenom}`;
                    }
                    
                    option.textContent = displayText;
                    reparationSelect.appendChild(option);
                });
                
                console.log(`${data.count} réparations chargées dans le select`);
                
                // Afficher le select s'il était caché
                reparationSelect.classList.remove('d-none');
            } else {
                console.error("Erreur lors du chargement des réparations:", data.message);
            }
        })
        .catch(error => {
            console.error("Erreur lors du chargement des réparations:", error);
        });
}

// Fonction pour récupérer les informations du client à partir d'une réparation
function getClientFromReparation(reparationId) {
    console.log("Récupération du client pour la réparation ID:", reparationId);
    
    if (!reparationId) {
        console.log("Aucun ID de réparation fourni");
        return;
    }
    
    // Vérifier d'abord si on peut trouver les informations dans le select des réparations
    const reparationSelect = document.getElementById('reparation_id');
    if (reparationSelect) {
        const selectedOption = reparationSelect.querySelector(`option[value="${reparationId}"]`);
        if (selectedOption) {
            const clientId = selectedOption.getAttribute('data-client-id');
            const clientNom = selectedOption.getAttribute('data-client-nom');
            const clientPrenom = selectedOption.getAttribute('data-client-prenom');
            
            console.log("Client trouvé via le select:", {clientId, clientNom, clientPrenom});
            
            if (clientId) {
                // Mettre à jour le champ client_id
                const clientIdInput = document.getElementById('client_id');
                if (clientIdInput) clientIdInput.value = clientId;
                
                // Mettre à jour l'affichage du client sélectionné
                const nomClientInput = document.getElementById('nom_client_selectionne');
                if (nomClientInput) nomClientInput.value = `${clientNom} ${clientPrenom}`;
                
                // Afficher la section du client sélectionné
                const clientSelectionne = document.getElementById('client_selectionne');
                if (clientSelectionne) {
                    const nomClientSpan = clientSelectionne.querySelector('.nom_client');
                    if (nomClientSpan) nomClientSpan.textContent = `${clientNom} ${clientPrenom}`;
                    
                    clientSelectionne.classList.remove('d-none');
                }
                
                return;
            }
        }
    }
    
    // Si on n'a pas trouvé les informations dans le select, faire une requête AJAX
    fetch(`ajax/get_client_from_reparation.php?reparation_id=${reparationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.client) {
                console.log("Client récupéré via AJAX:", data.client);
                
                // Mettre à jour le champ client_id
                const clientIdInput = document.getElementById('client_id');
                if (clientIdInput) clientIdInput.value = data.client.id;
                
                // Mettre à jour l'affichage du client sélectionné
                const nomClientInput = document.getElementById('nom_client_selectionne');
                if (nomClientInput) nomClientInput.value = `${data.client.nom} ${data.client.prenom}`;
                
                // Afficher la section du client sélectionné
                const clientSelectionne = document.getElementById('client_selectionne');
                if (clientSelectionne) {
                    const nomClientSpan = clientSelectionne.querySelector('.nom_client');
                    const telClientSpan = clientSelectionne.querySelector('.tel_client');
                    
                    if (nomClientSpan) nomClientSpan.textContent = `${data.client.nom} ${data.client.prenom}`;
                    if (telClientSpan) telClientSpan.textContent = data.client.telephone || 'Pas de téléphone';
                    
                    clientSelectionne.classList.remove('d-none');
                }
            } else {
                console.error("Erreur lors de la récupération du client:", data.message);
            }
        })
        .catch(error => {
            console.error("Erreur lors de la requête AJAX:", error);
        });
} 