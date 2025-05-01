// Variables globales
let currentProduitId = null;
let currentColisId = null;

// Fonction pour afficher les détails d'un produit
function voirDetails(produitId) {
    currentProduitId = produitId;
    
    fetch('../ajax/get_produit_temporaire_details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'produit_id=' + produitId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const produit = data.data;
            
            // Informations générales
            document.getElementById('produitReference').textContent = produit.reference;
            document.getElementById('produitNom').textContent = produit.nom;
            document.getElementById('produitQuantite').textContent = produit.quantite;
            document.getElementById('produitDateAjout').textContent = new Date(produit.date_ajout).toLocaleDateString();
            document.getElementById('produitDateLimite').textContent = new Date(produit.date_limite).toLocaleDateString();
            document.getElementById('produitStatut').textContent = produit.statut;
            
            // Informations de remboursement
            const remboursementInfo = document.getElementById('remboursementInfo');
            if (produit.montant_rembourse || produit.montant_rembourse_client) {
                remboursementInfo.style.display = 'block';
                remboursementInfo.querySelector('.alert').innerHTML = `
                    <p><strong>Montant remboursé par le fournisseur:</strong> ${produit.montant_rembourse || 'Non renseigné'}</p>
                    <p><strong>Montant remboursé au client:</strong> ${produit.montant_rembourse_client || 'Non renseigné'}</p>
                `;
            } else {
                remboursementInfo.style.display = 'none';
            }
            
            // Informations du colis
            const colisInfo = document.getElementById('colisInfo');
            if (produit.colis) {
                colisInfo.style.display = 'block';
                colisInfo.querySelector('.alert').innerHTML = `
                    <p><strong>N° Suivi:</strong> ${produit.colis.numero_suivi}</p>
                    <p><strong>Transporteur:</strong> ${produit.colis.transporteur}</p>
                    <p><strong>Statut:</strong> ${produit.colis.statut}</p>
                `;
            } else {
                colisInfo.style.display = 'none';
            }
            
            // Historique des vérifications
            const historiqueVerifications = document.getElementById('historiqueVerifications');
            if (produit.verifications && produit.verifications.length > 0) {
                historiqueVerifications.style.display = 'block';
                const tbody = historiqueVerifications.querySelector('tbody');
                tbody.innerHTML = produit.verifications.map(v => `
                    <tr>
                        <td>${new Date(v.date_verification).toLocaleDateString()}</td>
                        <td>${v.verifie_par}</td>
                        <td>${v.montant_rembourse}</td>
                        <td>${v.montant_rembourse_client}</td>
                    </tr>
                `).join('');
            } else {
                historiqueVerifications.style.display = 'none';
            }
            
            // Bouton de vérification
            const btnVerifierRetour = document.getElementById('btnVerifierRetour');
            btnVerifierRetour.style.display = produit.statut === 'retourne' ? 'block' : 'none';
            
            new bootstrap.Modal(document.getElementById('modalDetailsProduit')).show();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
    });
}

// Fonction pour afficher les détails d'un colis
function voirColis(colisId) {
    currentColisId = colisId;
    
    fetch('../ajax/get_colis_details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'colis_id=' + colisId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const colis = data.data;
            
            // Informations générales
            document.getElementById('colisNumeroSuivi').textContent = colis.numero_suivi;
            document.getElementById('colisTransporteur').textContent = colis.transporteur;
            document.getElementById('colisStatut').textContent = colis.statut;
            document.getElementById('colisDateCreation').textContent = new Date(colis.date_creation).toLocaleDateString();
            document.getElementById('colisDateExpedition').textContent = colis.date_expedition ? new Date(colis.date_expedition).toLocaleDateString() : 'Non renseignée';
            document.getElementById('colisDateReception').textContent = colis.date_reception ? new Date(colis.date_reception).toLocaleDateString() : 'Non renseignée';
            
            // Historique des statuts
            const historique = document.getElementById('colisHistorique');
            historique.innerHTML = `
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${colis.historique.map(h => `
                            <tr>
                                <td>${new Date(h.date).toLocaleDateString()}</td>
                                <td>${h.statut}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            
            // Produits dans le colis
            const tbody = document.getElementById('colisProduits');
            tbody.innerHTML = colis.produits.map(p => `
                <tr>
                    <td>${p.reference}</td>
                    <td>${p.nom}</td>
                    <td>${p.quantite}</td>
                    <td>${p.statut}</td>
                </tr>
            `).join('');
            
            new bootstrap.Modal(document.getElementById('modalDetailsColis')).show();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
    });
}

// Fonction pour mettre à jour le statut d'un colis
function mettreAJourStatut() {
    const statuts = {
        'en_preparation': 'En préparation',
        'expedie': 'Expédié',
        'en_transit': 'En transit',
        'livre': 'Livré',
        'verifie': 'Vérifié'
    };
    
    const statut = prompt('Choisissez le nouveau statut:\n' + Object.entries(statuts).map(([key, value]) => `${key}: ${value}`).join('\n'));
    
    if (statut && statuts[statut]) {
        fetch('../ajax/mettre_a_jour_statut_colis.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `colis_id=${currentColisId}&statut=${statut}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

// Fonction pour créer un nouveau colis
function creerColis() {
    const numeroSuivi = prompt('Numéro de suivi:');
    const transporteur = prompt('Transporteur:');
    
    if (numeroSuivi && transporteur) {
        fetch('../ajax/creer_colis.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `numero_suivi=${numeroSuivi}&transporteur=${transporteur}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

// Fonction pour vérifier un retour
function verifierRetour() {
    const montantRembourse = prompt('Montant remboursé par le fournisseur:');
    const montantRembourseClient = prompt('Montant remboursé au client:');
    
    if (montantRembourse && montantRembourseClient) {
        fetch('../ajax/verifier_retour.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `produit_id=${currentProduitId}&montant_rembourse=${montantRembourse}&montant_rembourse_client=${montantRembourseClient}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter le bouton de création de colis
    const tabs = document.querySelector('.nav-tabs');
    const createButton = document.createElement('button');
    createButton.className = 'btn btn-primary ms-2';
    createButton.textContent = 'Nouveau colis';
    createButton.onclick = creerColis;
    tabs.appendChild(createButton);
}); 