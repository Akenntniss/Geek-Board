<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Récupérer les produits
try {
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM produits p 
        ORDER BY p.nom ASC
    ");
    $stmt->execute();
    $produits = $stmt->fetchAll();
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des produits: " . $e->getMessage(), 'danger');
    $produits = [];
}

// Récupérer les produits en alerte de stock
try {
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM produits p 
        WHERE p.quantite <= p.seuil_alerte 
        ORDER BY p.quantite ASC
    ");
    $stmt->execute();
    $produits_alerte = $stmt->fetchAll();
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des alertes: " . $e->getMessage(), 'danger');
    $produits_alerte = [];
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Gestion de l'Inventaire</h1>
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" id="nouveauProduitDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-plus"></i> Nouveau Produit
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nouveauProduitDropdown">
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-box"></i> Ajouter un produit
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#scanProductModal">
                        <i class="fas fa-barcode"></i> Scanner un produit
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <?php if (!empty($produits_alerte)): ?>
    <div class="alert alert-warning mb-4">
        <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Alertes de Stock</h5>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Produit</th>
                        <th>Stock</th>
                        <th>Seuil</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits_alerte as $produit): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($produit['reference']); ?></td>
                        <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                        <td><span class="badge bg-danger"><?php echo $produit['quantite']; ?></span></td>
                        <td><?php echo $produit['seuil_alerte']; ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="ajusterStock(<?php echo $produit['id']; ?>)">
                                <i class="fas fa-boxes"></i> Ajuster
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="produitsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Produit</th>
                            <th>Prix Achat</th>
                            <th>Prix Vente</th>
                            <th>Stock</th>
                            <th>Seuil</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produits as $produit): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($produit['reference']); ?></td>
                            <td><?php echo htmlspecialchars($produit['nom']); ?></td>
                            <td><?php echo number_format($produit['prix_achat'], 2); ?> €</td>
                            <td><?php echo number_format($produit['prix_vente'], 2); ?> €</td>
                            <td>
                                <span class="badge <?php echo $produit['quantite'] <= $produit['seuil_alerte'] ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo $produit['quantite']; ?>
                                </span>
                            </td>
                            <td><?php echo $produit['seuil_alerte']; ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="ajusterStock(<?php echo $produit['id']; ?>)">
                                        <i class="fas fa-boxes"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="modifierProduit(<?php echo $produit['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="supprimerProduit(<?php echo $produit['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajout Produit -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau Produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addProductForm" method="POST" action="index.php?page=inventaire_actions">
                    <input type="hidden" name="action" value="ajouter_produit">
                    <div class="mb-3">
                        <label class="form-label">Référence</label>
                        <input type="text" class="form-control" name="reference" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prix d'achat</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="prix_achat" required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prix de vente</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="prix_vente" required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Stock initial</label>
                                <input type="number" class="form-control" name="quantite" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Seuil d'alerte</label>
                                <input type="number" class="form-control" name="seuil_alerte" value="5" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="addProductForm" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajustement Stock -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajustement du Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="stockForm" method="POST" action="index.php?page=inventaire_actions">
                    <input type="hidden" name="action" value="mouvement_stock">
                    <input type="hidden" name="produit_id" id="stock_produit_id">
                    <div class="mb-3">
                        <label class="form-label">Type de mouvement</label>
                        <select class="form-select" name="type_mouvement" required>
                            <option value="entree">Entrée</option>
                            <option value="sortie">Sortie</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantité</label>
                        <input type="number" class="form-control" name="quantite" required min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motif</label>
                        <textarea class="form-control" name="motif" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="stockForm" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modification Produit -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier le Produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editProductForm" method="POST" action="index.php?page=inventaire_actions">
                    <input type="hidden" name="action" value="modifier_produit">
                    <input type="hidden" name="produit_id" id="edit_produit_id">
                    <div class="mb-3">
                        <label class="form-label">Référence</label>
                        <input type="text" class="form-control" name="reference" id="edit_reference" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="nom" id="edit_nom" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prix d'achat</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="prix_achat" id="edit_prix_achat" required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prix de vente</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="prix_vente" id="edit_prix_vente" required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Seuil d'alerte</label>
                        <input type="number" class="form-control" name="seuil_alerte" id="edit_seuil_alerte" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="editProductForm" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Scanner Produit -->
<div class="modal fade" id="scanProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scanner un Produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="scanStep" class="text-center mb-4">
                    <h6 class="mb-3">Scannez le code-barres du produit</h6>
                    <div id="reader"></div>
                </div>
                <form id="scanProductForm" method="POST" action="index.php?page=inventaire_actions" style="display: none;">
                    <input type="hidden" name="action" value="ajouter_produit">
                    <input type="hidden" name="reference" id="scannedReference">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="nom" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prix d'achat</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="prix_achat" required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Prix de vente</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="prix_vente" required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Stock initial</label>
                                <input type="number" class="form-control" name="quantite" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Seuil d'alerte</label>
                                <input type="number" class="form-control" name="seuil_alerte" value="5" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="scanProductForm" class="btn btn-primary" style="display: none;">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let html5QrCode = null;

function onScanSuccess(decodedText, decodedResult) {
    // Jouer un son de succès (optionnel)
    try {
        const audio = new Audio('assets/sounds/beep.mp3');
        audio.play().catch(e => console.log('Son non supporté'));
    } catch (e) {
        console.log('Son non supporté');
    }
    
    // Arrêter le scanner
    html5QrCode.stop();
    
    // Vérifier si le produit existe déjà
    fetch(`index.php?page=ajax/check_produit&reference=${encodeURIComponent(decodedText)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Réponse reçue:', text);
                    throw new Error('Réponse invalide du serveur');
                }
            });
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (data.exists) {
                // Produit existant - Afficher le modal de mise à jour du stock
                document.getElementById('stock_produit_id').value = data.produit.id;
                document.getElementById('scanStep').style.display = 'none';
                document.getElementById('scanProductForm').style.display = 'none';
                document.querySelector('#scanProductModal .modal-footer .btn-primary').style.display = 'none';
                
                // Afficher les informations du produit
                const infoDiv = document.createElement('div');
                infoDiv.className = 'alert alert-info mb-3';
                infoDiv.innerHTML = `
                    <h6 class="mb-2">Produit trouvé</h6>
                    <p class="mb-2"><strong>${data.produit.nom}</strong></p>
                    <p class="mb-0">Stock actuel: <span class="badge ${data.produit.quantite <= data.produit.seuil_alerte ? 'bg-danger' : 'bg-success'}">${data.produit.quantite}</span></p>
                `;
                document.getElementById('scanStep').appendChild(infoDiv);
                
                // Afficher le formulaire de mise à jour du stock
                const stockForm = document.createElement('form');
                stockForm.id = 'updateStockForm';
                stockForm.method = 'POST';
                stockForm.action = 'index.php?page=inventaire_actions';
                stockForm.innerHTML = `
                    <input type="hidden" name="action" value="mouvement_stock">
                    <input type="hidden" name="produit_id" value="${data.produit.id}">
                    <input type="hidden" name="type_mouvement" value="entree">
                    <div class="mb-3">
                        <label class="form-label">Quantité à ajouter</label>
                        <input type="number" class="form-control" name="quantite" required min="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motif</label>
                        <textarea class="form-control" name="motif" rows="2" required>Réapprovisionnement par scan</textarea>
                    </div>
                `;
                document.getElementById('scanStep').appendChild(stockForm);
                
                // Afficher le bouton de validation
                const submitBtn = document.createElement('button');
                submitBtn.type = 'submit';
                submitBtn.form = 'updateStockForm';
                submitBtn.className = 'btn btn-primary';
                submitBtn.innerHTML = '<i class="fas fa-plus"></i> Mettre à jour le stock';
                document.querySelector('#scanProductModal .modal-footer').appendChild(submitBtn);
            } else {
                // Nouveau produit - Afficher le formulaire d'ajout
                document.getElementById('scannedReference').value = decodedText;
                document.getElementById('scanStep').style.display = 'none';
                document.getElementById('scanProductForm').style.display = 'block';
                document.querySelector('#scanProductModal .modal-footer .btn-primary').style.display = 'block';
                
                // Focus sur le champ nom
                document.querySelector('#scanProductForm input[name="nom"]').focus();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-danger mt-3';
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-circle"></i> Erreur lors de la vérification du produit
                <br>
                <small class="text-muted">${error.message}</small>
                <br>
                <small class="text-muted">Veuillez réessayer ou ajouter le produit manuellement.</small>
            `;
            document.getElementById('scanStep').appendChild(errorDiv);
        });
}

function onScanFailure(error) {
    // Ne rien faire en cas d'erreur de scan
    console.debug(`Code scan error = ${error}`);
}

// Démarrer le scanner quand le modal s'ouvre
document.getElementById('scanProductModal').addEventListener('shown.bs.modal', function () {
    if (!html5QrCode) {
        html5QrCode = new Html5Qrcode("reader");
        const config = { 
            fps: 30,
            qrbox: { width: 300, height: 300 },
            aspectRatio: 1.0,
            showTorchButtonIfSupported: true,
            showZoomSliderIfSupported: true,
            supportedScanTypes: [
                Html5QrcodeScanType.SCAN_TYPE_CAMERA
            ],
            formatsToSupport: [
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.CODE_93,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E
            ]
        };
        
        // Vérifier si la caméra est disponible
        navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
            .then(stream => {
                stream.getTracks().forEach(track => track.stop());
                return html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    onScanSuccess,
                    onScanFailure
                );
            })
            .catch(err => {
                console.error('Erreur caméra:', err);
                let message = 'Erreur lors de l\'accès à la caméra. ';
                if (err.name === 'NotAllowedError') {
                    message += 'Veuillez autoriser l\'accès à la caméra dans les paramètres de votre navigateur.';
                } else if (err.name === 'NotFoundError') {
                    message += 'Aucune caméra n\'a été trouvée sur votre appareil.';
                } else if (err.name === 'NotReadableError') {
                    message += 'La caméra est déjà utilisée par une autre application.';
                } else {
                    message += 'Veuillez vérifier que votre caméra est bien connectée et fonctionne correctement.';
                }
                
                // Afficher le message d'erreur dans le modal
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger mt-3';
                errorDiv.innerHTML = `
                    <i class="fas fa-exclamation-circle"></i> ${message}
                    <br>
                    <small class="text-muted">Si le problème persiste, essayez de rafraîchir la page ou d'utiliser un autre navigateur.</small>
                `;
                document.getElementById('scanStep').appendChild(errorDiv);
                
                // Désactiver le scanner
                html5QrCode = null;
            });
    }
});

// Réinitialiser le modal quand il se ferme
document.getElementById('scanProductModal').addEventListener('hidden.bs.modal', function () {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            html5QrCode = null;
        });
    }
    document.getElementById('scanStep').style.display = 'block';
    document.getElementById('scanProductForm').style.display = 'none';
    document.querySelector('#scanProductModal .modal-footer .btn-primary').style.display = 'none';
    
    // Supprimer les messages d'erreur précédents
    const errorDiv = document.querySelector('#scanStep .alert-danger');
    if (errorDiv) {
        errorDiv.remove();
    }
});

// Initialisation de DataTables
$(document).ready(function() {
    $('#produitsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json'
        },
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true
    });
});

function ajusterStock(produitId) {
    $('#stock_produit_id').val(produitId);
    $('#stockModal').modal('show');
}

function modifierProduit(produitId) {
    // Récupérer les données du produit via AJAX
    fetch(`index.php?page=ajax/get_produit&id=${produitId}`)
        .then(response => response.json())
        .then(data => {
            $('#edit_produit_id').val(data.id);
            $('#edit_reference').val(data.reference);
            $('#edit_nom').val(data.nom);
            $('#edit_description').val(data.description);
            $('#edit_prix_achat').val(data.prix_achat);
            $('#edit_prix_vente').val(data.prix_vente);
            $('#edit_seuil_alerte').val(data.seuil_alerte);
            $('#editProductModal').modal('show');
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la récupération des données du produit');
        });
}

function supprimerProduit(produitId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?page=inventaire_actions';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'supprimer_produit';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'produit_id';
        idInput.value = produitId;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<style>
/* Styles modernes */
:root {
    --primary-color: #4CAF50;
    --primary-hover: #45a049;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
    --border-radius: 12px;
}

/* Layout */
.container-fluid {
    padding: 2rem;
    background: #f8f9fa;
    min-height: 100vh;
}

/* En-tête */
.d-flex.justify-content-between {
    background: white;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    margin-bottom: 2rem;
}

.h3 {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
}

/* Boutons */
.btn-primary {
    background: var(--primary-color);
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

/* Tableau */
.card {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.table th {
    background: #f8f9fa;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85em;
    color: #6c757d;
    padding: 1rem;
}

.table td {
    padding: 1rem;
    vertical-align: middle;
}

/* Badges */
.badge {
    font-size: 0.9em;
    padding: 0.5em 0.8em;
    border-radius: 6px;
    font-weight: 500;
}

.badge.bg-success { background: var(--primary-color) !important; }
.badge.bg-danger { background: #f44336 !important; }

/* Alertes */
.alert {
    border: none;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border-left: 4px solid #ffc107;
}

/* Modals */
.modal-content {
    border: none;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
}

.modal-header, .modal-footer {
    background: #f8f9fa;
    border-color: rgba(0,0,0,0.05);
    padding: 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

/* Formulaires */
.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 0.6rem 1rem;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
}

/* Scanner */
#reader {
    width: 100%;
    max-width: 500px;
    margin: 0 auto;
    background: #000;
    padding: 15px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
}

#reader__scan_region {
    background: white;
    border: 3px solid var(--primary-color);
    border-radius: 8px;
}

#scanStep {
    padding: 25px;
    background: #f8f9fa;
    border-radius: var(--border-radius);
    text-align: center;
}

/* DataTables */
.dataTables_wrapper .dataTables_length select,
.dataTables_wrapper .dataTables_filter input {
    border-radius: 6px;
    border: 1px solid #dee2e6;
    padding: 0.4rem 0.8rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: white !important;
}

/* Responsive */
@media (max-width: 768px) {
    .container-fluid { padding: 1rem; }
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    .table-responsive {
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-md);
    }
}
</style> 