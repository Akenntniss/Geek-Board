<?php
// Définir la page actuelle pour le menu
$current_page = 'nouvelle_commande';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Récupérer les fournisseurs pour le formulaire
$fournisseurs = [];
try {
    $stmt = $pdo->query("SELECT id, nom, email, telephone FROM fournisseurs ORDER BY nom");
    $fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des fournisseurs: " . $e->getMessage(), "danger");
}

// Récupérer les clients pour le formulaire
$clients = [];
try {
    $stmt = $pdo->query("SELECT id, nom, prenom FROM clients ORDER BY nom, prenom");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des clients: " . $e->getMessage(), "danger");
}

// Récupérer les réparations en cours pour le formulaire
$reparations = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.id, r.modele, c.nom AS client_nom, c.prenom AS client_prenom
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.statut IN ('en_attente', 'en_cours') OR r.commande_requise = 1
        ORDER BY r.date_reception DESC
    ");
    $stmt->execute();
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des réparations: " . $e->getMessage(), "danger");
}

// Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer et valider les données du formulaire
    $fournisseur_id = isset($_POST['fournisseur_id']) ? (int)$_POST['fournisseur_id'] : 0;
    $client_id = isset($_POST['client_id']) ? (int)$_POST['client_id'] : null;
    $reparation_id = isset($_POST['reparation_id']) ? (int)$_POST['reparation_id'] : null;
    $date_commande = date('Y-m-d H:i:s');
    $urgence = cleanInput($_POST['urgence']);
    $notes = cleanInput($_POST['notes']);
    $user_id = $_SESSION['user_id'];
    
    // Validation
    $errors = [];
    
    if ($fournisseur_id <= 0) {
        $errors[] = "Veuillez sélectionner un fournisseur.";
    }
    
    // Produits de la commande
    $produits = [];
    $total_produits = isset($_POST['nombre_produits']) ? (int)$_POST['nombre_produits'] : 0;
    
    if ($total_produits <= 0) {
        $errors[] = "Veuillez ajouter au moins un produit à la commande.";
    } else {
        for ($i = 1; $i <= $total_produits; $i++) {
            if (isset($_POST["nom_piece_$i"]) && !empty($_POST["nom_piece_$i"])) {
                $produit = [
                    'nom_piece' => cleanInput($_POST["nom_piece_$i"]),
                    'reference' => isset($_POST["reference_$i"]) ? cleanInput($_POST["reference_$i"]) : '',
                    'description' => isset($_POST["description_$i"]) ? cleanInput($_POST["description_$i"]) : '',
                    'quantite' => isset($_POST["quantite_$i"]) ? (int)$_POST["quantite_$i"] : 1,
                    'prix_estime' => isset($_POST["prix_$i"]) ? (float)str_replace(',', '.', $_POST["prix_$i"]) : 0
                ];
                
                if ($produit['quantite'] <= 0) {
                    $errors[] = "La quantité du produit {$produit['nom_piece']} doit être supérieure à 0.";
                }
                
                $produits[] = $produit;
            }
        }
    }
    
    if (empty($errors)) {
        try {
            // Générer une référence unique pour la commande
            $reference = 'CMD-' . date('Ymd') . '-' . uniqid();
            
            // Démarrer une transaction
            $pdo->beginTransaction();
            
            // Insérer la commande
            $stmt = $pdo->prepare("
                INSERT INTO commandes_pieces (
                    reference, fournisseur_id, client_id, reparation_id, 
                    date_creation, date_commande, urgence, statut, 
                    notes, created_by
                ) VALUES (?, ?, ?, ?, NOW(), NOW(), ?, 'en_attente', ?, ?)
            ");
            $stmt->execute([
                $reference, 
                $fournisseur_id, 
                $client_id, 
                $reparation_id, 
                $urgence, 
                $notes, 
                $user_id
            ]);
            
            $commande_id = $pdo->lastInsertId();
            
            // Insérer les produits de la commande
            foreach ($produits as $produit) {
                $stmt = $pdo->prepare("
                    INSERT INTO commandes_pieces_items (
                        commande_id, nom_piece, reference_piece, description, 
                        quantite, prix_estime
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $commande_id,
                    $produit['nom_piece'],
                    $produit['reference'],
                    $produit['description'],
                    $produit['quantite'],
                    $produit['prix_estime']
                ]);
            }
            
            // Si la commande est liée à une réparation, mettre à jour le statut de la réparation
            if ($reparation_id) {
                $stmt = $pdo->prepare("
                    UPDATE reparations 
                    SET commande_requise = 1, statut = 'en_attente_pieces'
                    WHERE id = ?
                ");
                $stmt->execute([$reparation_id]);
            }
            
            // Valider la transaction
            $pdo->commit();
            
            set_message("La commande {$reference} a été créée avec succès.", "success");
            header('Location: index.php?page=commandes_pieces');
            exit();
        } catch (PDOException $e) {
            // En cas d'erreur, annuler la transaction
            $pdo->rollBack();
            set_message("Erreur lors de la création de la commande: " . $e->getMessage(), "danger");
        }
    } else {
        // Afficher les erreurs
        foreach ($errors as $error) {
            set_message($error, "danger");
        }
    }
}
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center my-4">
        <h1 class="mb-0"><i class="fas fa-shopping-cart me-2 text-primary"></i>Nouvelle commande de pièces</h1>
        <a href="index.php?page=commandes_pieces" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour aux commandes
        </a>
    </div>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Informations de la commande</h5>
            <span class="badge bg-white text-primary">Étape 1/2</span>
        </div>
        <div class="card-body">
            <form method="post" action="" id="commande-form">
                <?php 
                // Générer un nouveau token de soumission
                $_SESSION['last_submission_token'] = bin2hex(random_bytes(32));
                ?>
                <input type="hidden" name="submission_token" value="<?php echo $_SESSION['last_submission_token']; ?>">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="fournisseur_id" class="form-label fw-bold">Fournisseur *</label>
                        <select class="form-select form-select-lg" id="fournisseur_id" name="fournisseur_id" required>
                            <option value="">- Sélectionner un fournisseur -</option>
                            <?php foreach ($fournisseurs as $fournisseur): ?>
                                <option value="<?php echo $fournisseur['id']; ?>" <?php echo (isset($_POST['fournisseur_id']) && $_POST['fournisseur_id'] == $fournisseur['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($fournisseur['nom']); ?> - <?php echo htmlspecialchars($fournisseur['telephone']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text"><i class="fas fa-info-circle me-1"></i>Sélectionnez le fournisseur auprès duquel passer la commande</div>
                    </div>
                    <div class="col-md-6">
                        <label for="urgence" class="form-label fw-bold">Niveau d'urgence</label>
                        <select class="form-select form-select-lg" id="urgence" name="urgence">
                            <option value="normal" <?php echo (isset($_POST['urgence']) && $_POST['urgence'] === 'normal') ? 'selected' : ''; ?>>Normal</option>
                            <option value="urgent" <?php echo (isset($_POST['urgence']) && $_POST['urgence'] === 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                            <option value="tres_urgent" <?php echo (isset($_POST['urgence']) && $_POST['urgence'] === 'tres_urgent') ? 'selected' : ''; ?>>Très urgent</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="client_id" class="form-label fw-bold">Client (optionnel)</label>
                        <select class="form-select" id="client_id" name="client_id">
                            <option value="">- Aucun client spécifique -</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>" <?php echo (isset($_POST['client_id']) && $_POST['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text"><i class="fas fa-user me-1"></i>Sélectionner un client si la commande est pour un client spécifique</div>
                    </div>
                    <div class="col-md-6">
                        <label for="reparation_id" class="form-label fw-bold">Réparation associée (optionnel)</label>
                        <select class="form-select" id="reparation_id" name="reparation_id">
                            <option value="">- Aucune réparation associée -</option>
                            <?php foreach ($reparations as $reparation): ?>
                                <option value="<?php echo $reparation['id']; ?>" <?php echo (isset($_POST['reparation_id']) && $_POST['reparation_id'] == $reparation['id']) ? 'selected' : ''; ?>>
                                    #<?php echo $reparation['id']; ?> - <?php echo htmlspecialchars($reparation['modele']); ?> (<?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="notes" class="form-label fw-bold">Notes / Instructions</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Informations supplémentaires pour le fournisseur..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                </div>
                
                <hr class="my-4">
                
                <h5 class="mb-3 d-flex align-items-center">
                    <i class="fas fa-list-ul me-2 text-primary"></i>Produits de la commande
                    <span class="badge bg-primary ms-2" id="produits-count">1</span>
                </h5>
                
                <div id="produits-container">
                    <!-- Les produits seront ajoutés ici dynamiquement -->
                    <?php if (isset($produits) && !empty($produits)): ?>
                        <?php foreach ($produits as $index => $produit): ?>
                            <div class="produit-item card mb-3 border-primary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0 d-flex align-items-center">
                                            <i class="fas fa-cube me-2 text-primary"></i>Produit #<?php echo $index + 1; ?>
                                        </h6>
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-produit">
                                            <i class="fas fa-trash me-1"></i>Supprimer
                                        </button>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nom du produit *</label>
                                            <input type="text" class="form-control" name="nom_piece_<?php echo $index + 1; ?>" value="<?php echo htmlspecialchars($produit['nom_piece']); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Référence</label>
                                            <input type="text" class="form-control" name="reference_<?php echo $index + 1; ?>" value="<?php echo htmlspecialchars($produit['reference']); ?>">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Quantité *</label>
                                            <div class="input-group">
                                                <button type="button" class="btn btn-outline-secondary quantity-minus">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="form-control text-center" name="quantite_<?php echo $index + 1; ?>" value="<?php echo (int)$produit['quantite']; ?>" min="1" required>
                                                <button type="button" class="btn btn-outline-secondary quantity-plus">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Prix estimé</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control prix-input" name="prix_<?php echo $index + 1; ?>" value="<?php echo number_format($produit['prix_estime'], 2, '.', ''); ?>">
                                                <span class="input-group-text">€</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description_<?php echo $index + 1; ?>" rows="2"><?php echo htmlspecialchars($produit['description']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="produit-item card mb-3 border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 d-flex align-items-center">
                                        <i class="fas fa-cube me-2 text-primary"></i>Produit #1
                                    </h6>
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-produit">
                                        <i class="fas fa-trash me-1"></i>Supprimer
                                    </button>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nom du produit *</label>
                                        <input type="text" class="form-control" name="nom_piece_1" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Référence</label>
                                        <input type="text" class="form-control" name="reference_1">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Quantité *</label>
                                        <div class="input-group">
                                            <button type="button" class="btn btn-outline-secondary quantity-minus">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control text-center" name="quantite_1" value="1" min="1" required>
                                            <button type="button" class="btn btn-outline-secondary quantity-plus">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Prix estimé</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control prix-input" name="prix_1" value="0.00">
                                            <span class="input-group-text">€</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description_1" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <input type="hidden" id="nombre_produits" name="nombre_produits" value="<?php echo isset($produits) ? count($produits) : 1; ?>">
                
                <div class="d-flex justify-content-center mb-4">
                    <button type="button" id="add-produit" class="btn btn-success">
                        <i class="fas fa-plus-circle me-2"></i>Ajouter un produit
                    </button>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php?page=commandes_pieces'">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-primary btn-lg" id="submitBtn" onclick="saveCommande()">
                        <i class="fas fa-save me-2"></i>Créer la commande
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sélection des éléments du DOM
    const produitsContainer = document.getElementById('produits-container');
    const addProduitButton = document.getElementById('add-produit');
    const nombreProduitsInput = document.getElementById('nombre_produits');
    const commandeForm = document.getElementById('commande-form');
    
    // Nombre actuel de produits
    let nombreProduits = parseInt(nombreProduitsInput.value);
    
    // Ajouter un nouveau produit
    addProduitButton.addEventListener('click', function() {
        nombreProduits++;
        nombreProduitsInput.value = nombreProduits;
        
        const produitHtml = `
            <div class="produit-item card mb-3 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 d-flex align-items-center">
                            <i class="fas fa-cube me-2 text-primary"></i>Produit #${nombreProduits}
                        </h6>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-produit">
                            <i class="fas fa-trash me-1"></i>Supprimer
                        </button>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom du produit *</label>
                            <input type="text" class="form-control" name="nom_piece_${nombreProduits}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Référence</label>
                            <input type="text" class="form-control" name="reference_${nombreProduits}">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Quantité *</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary quantity-minus">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" class="form-control text-center" name="quantite_${nombreProduits}" value="1" min="1" required>
                                <button type="button" class="btn btn-outline-secondary quantity-plus">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Prix estimé</label>
                            <div class="input-group">
                                <input type="text" class="form-control prix-input" name="prix_${nombreProduits}" value="0.00">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description_${nombreProduits}" rows="2"></textarea>
                    </div>
                </div>
            </div>
        `;
        
        // Créer un élément div temporaire pour convertir la chaîne HTML en éléments DOM
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = produitHtml.trim();
        
        // Ajouter le nouveau produit au conteneur
        produitsContainer.appendChild(tempDiv.firstChild);
        
        // Ajouter les écouteurs d'événements pour le nouveau produit
        initializeProduitListeners();
    });
    
    // Initialiser les écouteurs d'événements pour les boutons de suppression de produits
    function initializeProduitListeners() {
        // Boutons de suppression
        document.querySelectorAll('.remove-produit').forEach(button => {
            button.removeEventListener('click', handleRemoveProduit);
            button.addEventListener('click', handleRemoveProduit);
        });
        
        // Validation des prix (n'accepter que les nombres)
        document.querySelectorAll('.prix-input').forEach(input => {
            input.removeEventListener('input', handlePriceInput);
            input.addEventListener('input', handlePriceInput);
        });
    }
    
    // Supprimer un produit
    function handleRemoveProduit() {
        // Ne pas supprimer si c'est le dernier produit
        if (nombreProduits <= 1) {
            alert('La commande doit contenir au moins un produit.');
            return;
        }
        
        // Supprimer l'élément
        this.closest('.produit-item').remove();
        
        // Mettre à jour le nombre de produits
        nombreProduits--;
        nombreProduitsInput.value = nombreProduits;
        
        // Réindexer les produits restants
        const produitItems = document.querySelectorAll('.produit-item');
        produitItems.forEach((item, index) => {
            const newIndex = index + 1;
            item.querySelector('h6').textContent = `Produit #${newIndex}`;
            
            // Mettre à jour les noms des champs
            item.querySelectorAll('input, textarea').forEach(field => {
                const name = field.getAttribute('name');
                if (name) {
                    const baseName = name.split('_')[0];
                    field.setAttribute('name', `${baseName}_${newIndex}`);
                }
            });
        });
    }
    
    // Gérer la validation des prix
    function handlePriceInput() {
        let value = this.value;
        // Remplacer les virgules par des points et supprimer les caractères non numériques sauf le point
        value = value.replace(/,/g, '.').replace(/[^\d.]/g, '');
        
        // S'assurer qu'il n'y a qu'un seul point décimal
        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }
        
        this.value = value;
    }
    
    // Validation du formulaire avant la soumission
    commandeForm.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Vérifier le fournisseur
        const fournisseurSelect = document.getElementById('fournisseur_id');
        if (!fournisseurSelect.value) {
            isValid = false;
            alert('Veuillez sélectionner un fournisseur.');
        }
        
        // Vérifier si tous les produits ont un nom et une quantité
        const produitItems = document.querySelectorAll('.produit-item');
        produitItems.forEach((item, index) => {
            const nomInput = item.querySelector(`input[name="nom_piece_${index + 1}"]`);
            const quantiteInput = item.querySelector(`input[name="quantite_${index + 1}"]`);
            
            if (!nomInput.value.trim()) {
                isValid = false;
                alert(`Veuillez saisir un nom pour le produit #${index + 1}.`);
            }
            
            if (!quantiteInput.value || parseInt(quantiteInput.value) <= 0) {
                isValid = false;
                alert(`La quantité du produit #${index + 1} doit être supérieure à 0.`);
            }
        });
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // Initialiser les écouteurs au chargement de la page
    initializeProduitListeners();
    
    // Synchronisation client et réparation
    const clientSelect = document.getElementById('client_id');
    const reparationSelect = document.getElementById('reparation_id');
    
    // Lorsqu'une réparation est sélectionnée, mettre à jour le client
    reparationSelect.addEventListener('change', function() {
        const reparationId = this.value;
        
        if (reparationId) {
            // Trouver les détails de la réparation
            const selectedOption = this.options[this.selectedIndex];
            const clientInfo = selectedOption.textContent.match(/\(([^)]+)\)/);
            
            if (clientInfo) {
                // Trouver l'ID du client
                const clientName = clientInfo[1].trim();
                
                for (let i = 0; i < clientSelect.options.length; i++) {
                    if (clientSelect.options[i].textContent.trim() === clientName) {
                        clientSelect.value = clientSelect.options[i].value;
                        break;
                    }
                }
            }
        }
    });
});
</script> 