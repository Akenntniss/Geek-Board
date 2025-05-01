/**
 * Script pour gérer le modal de commande sur la page d'accueil
 */

document.addEventListener('DOMContentLoaded', function() {
    // Charger les fournisseurs dans le select
    loadFournisseurs();
    
    // Charger les réparations dans le select
    loadReparations();
    
    // Écouteurs d'événements pour les boutons de statut
    setupStatusButtons();
});

// Fonction pour charger les fournisseurs
function loadFournisseurs() {
    fetch('ajax/get_fournisseurs.php')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('fournisseur_id');
            if (select) {
                // Vider le select sauf l'option par défaut
                while (select.options.length > 1) {
                    select.remove(1);
                }
                
                // Ajouter les options
                data.fournisseurs.forEach(fournisseur => {
                    const option = document.createElement('option');
                    option.value = fournisseur.id;
                    option.textContent = fournisseur.nom;
                    select.appendChild(option);
                });
            }
        }
    })
    .catch(error => console.error('Erreur lors du chargement des fournisseurs:', error));
}

// Fonction pour charger les réparations
function loadReparations() {
    console.log("Chargement des réparations...");
    fetch('ajax/get_reparations.php')
    .then(response => {
        console.log("Réponse reçue de get_reparations.php:", response.status);
        return response.json();
    })
    .then(data => {
        console.log("Données reçues pour les réparations:", data);
        if (data.success) {
            const select = document.getElementById('reparation_id');
            console.log("Élément select pour les réparations:", select);
            if (select) {
                // Vider le select sauf l'option par défaut
                while (select.options.length > 1) {
                    select.remove(1);
                }
                
                // Ajouter les options
                data.reparations.forEach(reparation => {
                    const option = document.createElement('option');
                    option.value = reparation.id;
                    option.setAttribute('data-client-id', reparation.client_id);
                    option.setAttribute('data-client-nom', reparation.client_nom);
                    option.setAttribute('data-client-prenom', reparation.client_prenom);
                    option.textContent = `${reparation.type_appareil} - ${reparation.marque} ${reparation.modele} - ${reparation.client_nom} ${reparation.client_prenom}`;
                    select.appendChild(option);
                });
                
                console.log("Réparations chargées avec succès:", data.count);
            } else {
                console.error("Élément 'reparation_id' introuvable dans le DOM");
            }
        } else {
            console.error("Erreur lors du chargement des réparations:", data.message);
        }
    })
    .catch(error => {
        console.error('Erreur lors du chargement des réparations:', error);
    });
}

// Configurer les boutons de statut
function setupStatusButtons() {
    document.querySelectorAll('#ajouterCommandeModal .status-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            document.querySelectorAll('#ajouterCommandeModal .status-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Mettre à jour la valeur du champ caché
            document.getElementById('selectedStatus').value = this.dataset.status;
        });
    });
}

let isSubmitting = false;
let submissionTimeout = null;

function saveCommande() {
    if (isSubmitting) {
        console.log('Une soumission est déjà en cours...');
        return;
    }

    // Vérifier si une soumission récente a eu lieu (dans les 2 dernières secondes)
    if (submissionTimeout) {
        console.log('Soumission trop rapide, ignorée');
        return;
    }

    isSubmitting = true;
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...';
    }

    // Créer un timeout pour empêcher les soumissions rapides
    submissionTimeout = setTimeout(() => {
        submissionTimeout = null;
    }, 2000);

    console.log("Début de la sauvegarde de la commande");
    
    const modal = document.getElementById('ajouterCommandeModal');
    if (!modal) {
        console.error("Erreur: Le modal 'ajouterCommandeModal' n'a pas été trouvé");
        return;
    }
    
    const form = modal.querySelector('form');
    if (!form) {
        console.error("Erreur: Le formulaire n'a pas été trouvé dans le modal");
        alert("Erreur: formulaire non trouvé");
        return;
    }
    
    // Récupérer les valeurs
    const clientIdInput = document.getElementById('client_id_commande') || document.getElementById('client_id');
    console.log("Élément client_id:", clientIdInput);
    const clientId = clientIdInput ? clientIdInput.value : '';
    console.log("ID client récupéré:", clientId);
    
    const fournisseurId = document.getElementById('fournisseur_id').value;
    const nomPiece = document.querySelector('input[name="nom_piece"]').value.trim();
    const quantite = document.querySelector('input[name="quantite"]').value;
    const prixEstime = document.querySelector('input[name="prix_estime"]').value;
    const codeBarre = document.getElementById('code_barre').value.trim();
    const statut = document.getElementById('selectedStatus').value;
    const reparationId = document.getElementById('reparation_id').value;
    
    console.log("Valeurs récupérées:", {
        clientId,
        fournisseurId,
        nomPiece,
        quantite,
        prixEstime,
        codeBarre,
        statut,
        reparationId
    });
    
    // Vérifier si le client a été sélectionné via la réparation
    if (!clientId && reparationId) {
        const reparationOption = document.querySelector(`#reparation_id option[value="${reparationId}"]`);
        if (reparationOption && reparationOption.dataset.clientId) {
            const clientIdFromRepair = reparationOption.dataset.clientId;
            console.log("Client ID trouvé via la réparation:", clientIdFromRepair);
            
            if (clientIdInput) clientIdInput.value = clientIdFromRepair;
            
            // Afficher le client sélectionné via la réparation
            const clientNom = reparationOption.dataset.clientNom || '';
            const clientPrenom = reparationOption.dataset.clientPrenom || '';
            if (clientNom && clientPrenom) {
                const nomClientElement = document.getElementById('nom_client_selectionne_commande') || document.getElementById('nom_client_selectionne');
                const clientSelectionneElement = document.getElementById('client_selectionne_commande') || document.getElementById('client_selectionne');
                
                if (nomClientElement) nomClientElement.textContent = `${clientNom} ${clientPrenom}`;
                if (clientSelectionneElement) clientSelectionneElement.classList.remove('d-none');
            }
        }
    }
    
    // Récupérer à nouveau l'ID client après une éventuelle mise à jour
    const finalClientId = clientIdInput ? clientIdInput.value : '';
    console.log("ID client final:", finalClientId);
    
    // Vérifications
    if (!finalClientId) {
        console.error("Erreur: Client non sélectionné");
        alert('Veuillez sélectionner un client');
        return;
    }
    
    if (!fournisseurId) {
        console.error("Erreur: Fournisseur non sélectionné");
        alert('Veuillez sélectionner un fournisseur');
        return;
    }
    
    if (!nomPiece) {
        console.error("Erreur: Nom de pièce non saisi");
        alert('Veuillez saisir le nom de la pièce');
        return;
    }
    
    if (!quantite || quantite < 1) {
        console.error("Erreur: Quantité invalide");
        alert('Veuillez saisir une quantité valide');
        return;
    }
    
    if (!prixEstime || prixEstime <= 0) {
        console.error("Erreur: Prix estimé invalide");
        alert('Veuillez saisir un prix estimé valide');
        return;
    }
    
    // Créer l'objet FormData
    const formData = new FormData();
    formData.append('client_id', finalClientId);
    formData.append('fournisseur_id', fournisseurId);
    formData.append('nom_piece', nomPiece);
    formData.append('quantite', quantite);
    formData.append('prix_estime', prixEstime);
    formData.append('code_barre', codeBarre);
    formData.append('statut', statut);
    
    if (reparationId) {
        formData.append('reparation_id', reparationId);
    }
    
    console.log("Envoi des données de commande...");
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Envoyer les données
    fetch('ajax/add_commande.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Commande ajoutée avec succès', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Erreur lors de l\'ajout de la commande', 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de communication avec le serveur', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Créer la commande';
        }
    });
}

// Fonction pour incrémenter la quantité
function incrementQuantity() {
    const input = document.querySelector('input[name="quantite"]');
    if (input) {
        input.value = parseInt(input.value) + 1;
    }
}

// Fonction pour décrémenter la quantité
function decrementQuantity() {
    const input = document.querySelector('input[name="quantite"]');
    if (input) {
        const newValue = parseInt(input.value) - 1;
        if (newValue >= 1) {
            input.value = newValue;
        }
    }
}

// Exposer les fonctions pour utilisation globale
window.incrementQuantity = incrementQuantity;
window.decrementQuantity = decrementQuantity;
window.saveCommande = saveCommande; 