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

// Calculer les statistiques
$total_produits = count($produits);
$produits_en_alerte = count($produits_alerte);
$produits_epuises = array_filter($produits, function($p) { return $p['quantite'] == 0; });
$total_produits_epuises = count($produits_epuises);
?>

<div class="container py-4" id="inventaire-container">
    <!-- En-tête et actions principales -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-boxes text-primary me-2"></i>
            Gestion de l'Inventaire
        </h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" id="scanBtn">
                <i class="fas fa-barcode me-1"></i> Scanner
            </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus me-1"></i> Nouveau Produit
        </button>
        </div>
    </div>

    <!-- Statistiques en ligne -->
    <div class="stats-bar mb-4">
        <div class="stat-item">
            <i class="fas fa-box text-primary"></i>
            <span class="stat-label">Total</span>
            <span class="stat-value"><?php echo $total_produits; ?></span>
        </div>
        <div class="stat-item">
            <i class="fas fa-exclamation-triangle text-warning"></i>
            <span class="stat-label">En Alerte</span>
            <span class="stat-value"><?php echo $produits_en_alerte; ?></span>
        </div>
        <div class="stat-item">
            <i class="fas fa-times-circle text-danger"></i>
            <span class="stat-label">Épuisés</span>
            <span class="stat-value"><?php echo $total_produits_epuises; ?></span>
        </div>
        <div class="stat-item">
            <i class="fas fa-check-circle text-success"></i>
            <span class="stat-label">En Stock</span>
            <span class="stat-value"><?php echo $total_produits - $total_produits_epuises; ?></span>
        </div>
    </div>

    <!-- Barre de recherche et filtres -->
    <div class="search-bar mb-4">
        <div class="input-group">
            <span class="input-group-text bg-light">
                <i class="fas fa-search text-muted"></i>
            </span>
            <input type="text" class="form-control" id="searchInput" placeholder="Rechercher un produit...">
            <select class="form-select w-auto" id="filterStatus">
                <option value="all">Tous les statuts</option>
                <option value="stock">En stock</option>
                <option value="alert">En alerte</option>
                <option value="out">Épuisés</option>
            </select>
            <button type="button" class="btn btn-outline-secondary" id="exportBtn">
                <i class="fas fa-download me-1"></i> Exporter
            </button>
        </div>
    </div>

    <!-- Tableau principal -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="produitsTable" class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Référence</th>
                            <th>Produit</th>
                            <th>Prix Achat</th>
                            <th>Prix Vente</th>
                            <th class="text-center">Stock</th>
                            <th>Seuil</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produits as $produit): ?>
                        <tr data-produit-id="<?php echo $produit['id']; ?>" data-seuil-alerte="<?php echo $produit['seuil_alerte']; ?>">
                            <td class="text-muted small"><?php echo htmlspecialchars($produit['reference']); ?></td>
                            <td class="fw-medium"><?php echo htmlspecialchars($produit['nom']); ?></td>
                            <td><?php echo number_format($produit['prix_achat'], 2); ?> €</td>
                            <td><?php echo number_format($produit['prix_vente'], 2); ?> €</td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?php echo $produit['quantite'] <= $produit['seuil_alerte'] ? 'bg-danger' : ($produit['quantite'] <= $produit['seuil_alerte'] * 2 ? 'bg-warning' : 'bg-success'); ?>">
                                    <?php echo $produit['quantite']; ?>
                                </span>
                            </td>
                            <td><?php echo $produit['seuil_alerte']; ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="ajusterStock(<?php echo $produit['id']; ?>)" data-bs-toggle="tooltip" title="Ajuster le stock">
                                        <i class="fas fa-boxes"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="modifierProduit(<?php echo $produit['id']; ?>)" data-bs-toggle="tooltip" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="supprimerProduit(<?php echo $produit['id']; ?>)" data-bs-toggle="tooltip" title="Supprimer">
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
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2 text-primary"></i>
                    Nouveau Produit
                </h5>
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
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_temporaire" id="is_temporaire" value="1">
                            <label class="form-check-label" for="is_temporaire">
                                Produit temporaire (susceptible d'être retourné)
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="submit" form="addProductForm" class="btn btn-primary">
                    <i class="fas fa-check me-1"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajustement Stock -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-boxes me-2"></i>
                    Ajustement du Stock
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="stockForm">
                    <input type="hidden" name="produit_id" id="stock_produit_id">
                    <input type="hidden" name="prix_vente" id="stock_prix_vente">
                    
                    <!-- Informations du produit -->
                    <div class="product-info mb-4 p-3 bg-light rounded border">
                        <h6 class="mb-1 fw-bold" id="stock_product_name">-</h6>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-secondary me-2" id="stock_product_ref">-</span>
                            <span class="text-muted small" id="stock_current_stock">Stock actuel: -</span>
                        </div>
                    </div>
                    
                    <!-- Type de mouvement (entrée/sortie) -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Type de mouvement</label>
                        <div class="d-flex gap-2">
                            <div class="form-check form-check-inline flex-fill m-0">
                                <input class="form-check-input visually-hidden" type="radio" name="type_mouvement" id="type_entree" value="entree" checked>
                                <label class="form-check-label btn btn-outline-success w-100 text-start" for="type_entree">
                                    <i class="fas fa-arrow-circle-down me-2"></i> Entrée
                                </label>
                            </div>
                            <div class="form-check form-check-inline flex-fill m-0">
                                <input class="form-check-input visually-hidden" type="radio" name="type_mouvement" id="type_sortie" value="sortie">
                                <label class="form-check-label btn btn-outline-danger w-100 text-start" for="type_sortie">
                                    <i class="fas fa-arrow-circle-up me-2"></i> Sortie
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quantité -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Quantité</label>
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary" id="decreaseQty">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="form-control text-center" name="quantite" id="stock_quantite" required min="1" value="1">
                            <button type="button" class="btn btn-outline-secondary" id="increaseQty">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Motif du mouvement -->
                    <div class="form-group mb-4">
                        <label class="form-label fw-semibold">Motif du mouvement</label>
                        
                        <!-- Boutons pour les entrées de stock -->
                        <div id="motifs-entree" class="d-flex flex-wrap gap-2" style="display: none;">
                            <button type="button" class="btn btn-motif motif-btn" data-motif="Commande Reçu">
                                <i class="fas fa-truck-loading me-2"></i> Commande Reçu
                            </button>
                            <button type="button" class="btn btn-motif motif-btn" data-motif="Retour de prêt" id="btn-retour-pret">
                                <i class="fas fa-undo me-2"></i> Retour de prêt
                            </button>
                    </div>
                        
                        <!-- Boutons pour les sorties de stock -->
                        <div id="motifs-sortie" class="d-flex flex-wrap gap-2" style="display: none;">
                            <button type="button" class="btn btn-motif motif-btn" data-motif="Utilisé pour une réparation">
                                <i class="fas fa-tools me-2"></i> Utilisé pour une réparation
                            </button>
                            <button type="button" class="btn btn-motif motif-btn" data-motif="Utilisé pour un SAV">
                                <i class="fas fa-headset me-2"></i> Utilisé pour un SAV
                            </button>
                            <button type="button" class="btn btn-motif motif-btn" data-motif="Prêté à un partenaire" id="btn-prete-partenaire">
                                <i class="fas fa-handshake me-2"></i> Prêté à un partenaire
                            </button>
                        </div>
                        
                        <!-- Champ caché pour stocker le motif sélectionné -->
                        <input type="hidden" name="motif" id="motif" value="">
                        <div id="motif-display" class="alert alert-info mt-3 rounded-3 border-0 small" style="display: none;"></div>
                    </div>
                    
                    <!-- Sélection du partenaire (caché par défaut) -->
                    <div class="mb-4" id="partenaire-selection" style="display: none;">
                        <label class="form-label fw-semibold">Sélectionner un partenaire</label>
                        <select class="form-select" id="partenaire_id" name="partenaire_id">
                            <option value="">Choisir un partenaire...</option>
                            <!-- Options seront chargées dynamiquement -->
                        </select>
                    </div>
                    
                    <div id="stock-alert" class="alert" style="display: none;"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="button" id="submitStockBtn" class="btn btn-primary">
                    <i class="fas fa-check me-1"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modification Produit -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2 text-warning"></i>
                    Modifier le Produit
                </h5>
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
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_temporaire" id="edit_is_temporaire" value="1">
                            <label class="form-check-label" for="edit_is_temporaire">
                                Produit temporaire (susceptible d'être retourné)
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="submit" form="editProductForm" class="btn btn-primary">
                    <i class="fas fa-check me-1"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Scanner -->
<div class="modal fade" id="scannerModal" tabindex="-1" role="dialog" aria-labelledby="scannerModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="scannerModalLabel">
                    <i class="fas fa-barcode me-2"></i>
                    Scanner un Code-Barres
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-0">
                <div class="text-center p-3">
                    <div id="scanner-status" class="alert alert-info" role="status">
                        Initialisation de la caméra...
                            </div>
                        </div>
                <div id="interactive" class="viewport position-relative">
                    <video id="scanner-video" autoplay playsinline></video>
                    <canvas class="drawingBuffer" style="display: none;" willReadFrequently="true"></canvas>
                    <div class="scanner-overlay">
                        <div class="scanner-laser"></div>
                    </div>
                    </div>
                <div class="p-3">
                    <select id="camera-select" class="form-select mb-2" style="display: none;">
                        <option value="">Sélectionner une caméra...</option>
                    </select>
                </div>
                    </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fermer
                </button>
                        </div>
        </div>
    </div>
</div>

<style>
/* Styles modernes pour la page d'inventaire */
.container {
    max-width: 1200px;
    margin: 0 auto;
}

/* Style spécifique pour le conteneur d'inventaire */
#inventaire-container {
    margin-top: 60px !important; /* Utilisation de margin au lieu de transform */
    padding-bottom: 60px !important; /* Compensation pour éviter de couper le contenu en bas */
    position: relative; /* Assurer que le z-index est correctement appliqué */
    z-index: 1; /* Valeur inférieure à celle de l'en-tête */
}

/* Style pour la barre de statistiques */
.stats-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    background: #fff;
    padding: 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 0.5rem;
}

.stat-item i {
    font-size: 1.5rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-right: 0.5rem;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
}

/* Style pour la barre de recherche */
.search-bar {
    background: #fff;
    padding: 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.search-bar .input-group {
    flex-wrap: nowrap;
}

.search-bar .form-select {
    max-width: 200px;
}

/* Style pour le tableau */
.card {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85em;
    letter-spacing: 0.3px;
    color: #495057;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    font-size: 0.85em;
    padding: 0.5em 0.8em;
}

.badge.rounded-pill {
    min-width: 60px;
}

/* Style pour les boutons d'action */
.btn-group .btn {
    padding: 0.25rem 0.5rem;
    margin: 0 1px;
    box-shadow: none !important;
}

/* Style pour la pagination */
.dataTables_wrapper .dataTables_paginate {
    padding: 1rem;
    display: flex;
    justify-content: center;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    margin: 0 0.25rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: var(--bs-primary);
    color: white !important;
    border: 1px solid var(--bs-primary);
    border-radius: 0.5rem;
}

/* Styles pour le scanner */
.viewport {
    position: relative;
    width: 100%;
    height: 400px;
    overflow: hidden;
    background: #000;
}

#scanner-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
}

.scanner-laser {
    position: absolute;
    left: 0;
    right: 0;
    height: 2px;
    background: red;
    box-shadow: 0 0 4px red;
    animation: scan 2s infinite;
    top: 50%;
    transform: translateY(-50%);
}

@keyframes scan {
    0% {
        top: 20%;
    }
    50% {
        top: 80%;
    }
    100% {
        top: 20%;
    }
}

/* Ajustements pour le modal scanner */
#scannerModal .modal-dialog {
    max-width: 800px;
}

#scannerModal .modal-body {
    background: #000;
}

#scannerModal .alert {
    margin-bottom: 0;
}

/* Styles spécifiques pour le modal d'ajustement de stock */
.gradient-primary {
    background: linear-gradient(135deg, #3a7bd5, #4a90e2) !important;
}

#stockModal .modal-content {
    border-radius: 1rem;
    overflow: hidden;
}

.btn-movement {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
    position: relative;
}

.btn-movement:hover {
    border-color: #adb5bd;
    background-color: #e9ecef;
}

input[name="type_mouvement"]:checked + .btn-movement {
    border-color: var(--bs-primary);
    background-color: rgba(13, 110, 253, 0.08);
    box-shadow: 0 0 0 0.15rem rgba(13, 110, 253, 0.2);
}

input[name="type_mouvement"]:checked + .btn-movement::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 8px;
    height: 100%;
    background-color: var(--bs-primary);
    border-radius: 3px 0 0 3px;
}

input[value="entree"]:checked + .btn-movement::before {
    background-color: var(--bs-success);
}

input[value="sortie"]:checked + .btn-movement::before {
    background-color: var(--bs-danger);
}

input[value="entree"]:checked + .btn-movement {
    border-color: var(--bs-success);
    background-color: rgba(25, 135, 84, 0.08);
    box-shadow: 0 0 0 0.15rem rgba(25, 135, 84, 0.2);
}

input[value="sortie"]:checked + .btn-movement {
    border-color: var(--bs-danger);
    background-color: rgba(220, 53, 69, 0.08);
    box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.2);
}

.input-quantity {
    max-width: 180px;
    margin: 0 auto;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    border-radius: 8px;
    overflow: hidden;
}

.btn-quantity {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #495057;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-quantity:hover {
    background-color: #e9ecef;
    color: #212529;
}

#stock_quantite {
    height: 40px;
    border-left: 0;
    border-right: 0;
    font-size: 1.1rem;
}

.motif-buttons-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.btn-motif {
    border: 1px solid #e0e0e0;
    background-color: #f8f9fa;
    transition: all 0.2s ease;
    margin-bottom: 0.5rem;
    border-radius: 0.5rem;
    padding: 0.75rem 1.25rem;
}

.btn-motif:hover {
    background-color: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.btn-motif.active {
    background-color: #e7f3ff;
    border-color: #0d6efd;
    color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

#motif-display {
    background-color: rgba(13, 110, 253, 0.08);
}

#stock-alert {
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stats-bar {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .search-bar .input-group {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .search-bar .form-select,
    .search-bar .btn {
        width: auto;
    }
    
    .viewport {
        height: 300px;
    }
    
    #scannerModal .modal-dialog {
        margin: 0.5rem;
    }
    
    .btn-motif {
        flex: 1 0 100%;
        max-width: 100%;
    }
    
    .d-flex.gap-3 {
        flex-direction: column;
        gap: 1rem !important;
    }
    
    /* Ajustement pour les modaux sur mobile */
    #addProductModal .modal-dialog,
    #stockModal .modal-dialog,
    #editProductModal .modal-dialog {
        margin-top: 60px;
    }
}

@media (max-width: 576px) {
    .modal-dialog {
        margin: 0.5rem;
    }
    
    /* Ajustement pour les modaux sur petit écran mobile */
    #addProductModal .modal-dialog,
    #stockModal .modal-dialog,
    #editProductModal .modal-dialog {
        margin-top: 80px;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .product-info {
        padding: 0.75rem;
    }
    
    .motif-buttons-container {
        flex-direction: column;
    }
}

/* Styles spécifiques pour les boutons de motif */
.btn-motif {
    border: 1px solid #e0e0e0;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    height: 100%;
}

.btn-motif:hover {
    background-color: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.btn-motif.active {
    background-color: #e7f3ff;
    border-color: #0d6efd;
    color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Style pour les boutons radio personnalisés */
.form-check-input:checked + .btn-outline-success {
    background-color: #198754;
    color: white;
    border-color: #198754;
}

.form-check-input:checked + .btn-outline-danger {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}
</style>

<!-- Scripts externes -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>

<script>
// Initialisation des DataTables
$(document).ready(function() {
    try {
        if ($.fn.DataTable) {
            $('#produitsTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json'
                },
                order: [[1, 'asc']],
                pageLength: 15,
                responsive: true,
                dom: '<"row"<"col-12"f>><"table-responsive"t><"row align-items-center"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                lengthChange: false
            });
        }
    } catch (e) {
        console.error("Erreur lors de l'initialisation de DataTables:", e);
    }
});

// Variables globales pour le scanner
let currentStream = null;
let scannerInitialized = false;
let scanAttempts = 0;
const MAX_SCAN_ATTEMPTS = 3;
let scannerModal = null;

// Initialiser le modal scanner
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le modal
    scannerModal = new bootstrap.Modal(document.getElementById('scannerModal'));
    
    // Ajouter le gestionnaire de clic pour le bouton scanner
    document.getElementById('scanBtn').addEventListener('click', function() {
        console.log('Bouton scanner cliqué');
        scanAttempts = 0;
        scannerModal.show();
    });
    
    // Gestionnaires d'événements pour le modal scanner
    document.getElementById('scannerModal').addEventListener('shown.bs.modal', function() {
        console.log("Modal scanner affiché");
        startCameraSimple();
    });
    
    document.getElementById('scannerModal').addEventListener('hidden.bs.modal', function() {
        console.log("Modal scanner fermé");
        stopCamera();
    });
});

// Fonction simplifiée pour arrêter la caméra
function stopCamera() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
    }
    
    if (scannerInitialized && Quagga) {
        try {
            Quagga.stop();
            scannerInitialized = false;
        } catch (e) {
            console.error("Erreur lors de l'arrêt de Quagga:", e);
        }
    }
}

// Initialisation directe de Quagga avec un stream existant
function initQuaggaDirectly(stream) {
    const status = document.getElementById('scanner-status');
    
    // Configuration simplifiée pour mobiles
    const config = {
        inputStream: {
            type: "LiveStream",
            constraints: {
                width: 640,
                height: 480
            },
            // Ne pas utiliser target: element, mais fournir directement le stream
            target: document.getElementById('interactive'),
            area: { top: "10%", right: "10%", left: "10%", bottom: "10%" } // Zone de scan plus large
        },
        locator: {
            patchSize: "large", // Augmenter la taille du patch pour une meilleure détection
            halfSample: false   // Désactiver halfSample pour plus de précision
        },
        numOfWorkers: 2,      
        frequency: 5,         // Analyse plus fréquente
        decoder: {
            readers: ["ean_reader", "ean_8_reader", "code_128_reader", "code_39_reader"],
            multiple: false,
            debug: {
                drawBoundingBox: true,
                showPattern: true,
                showFrequency: true
            }
        },
        locate: true
    };
    
    try {
        console.log("Initialisation de Quagga avec configuration:", config);
        
        Quagga.init(config, function(err) {
            if (err) {
                console.error("Erreur d'initialisation de Quagga:", err);
                status.className = 'alert alert-danger';
                status.textContent = "Erreur d'initialisation du scanner: " + err.message;
                return;
            }
            
            console.log("Quagga initialisé avec succès");
            scannerInitialized = true;
            
            // Mettre à jour le statut
            status.className = 'alert alert-success';
            status.textContent = "Scanner actif. Placez un code-barres devant la caméra.";
            
            // Ajouter les contrôles de zoom
            setupZoomControls(stream);
            
            // Enregistrer le gestionnaire de détection de codes
            Quagga.onDetected(function(result) {
                if (result && result.codeResult && result.codeResult.code) {
                    const code = result.codeResult.code;
                    console.log("Code détecté:", code);
                    
                    // Ajouter un beep
                    beepSuccess();
                    
                    // Traiter le code détecté
                    verifierProduit(code);
                }
            });
            
            // Démarrer le scanner
            Quagga.start();
        });
    } catch (e) {
        console.error("Exception lors de l'initialisation de Quagga:", e);
        status.className = 'alert alert-danger';
        status.textContent = "Erreur critique du scanner: " + e.message;
    }
}

// Fonction pour configurer les contrôles de zoom
function setupZoomControls(stream) {
    // Ajouter les contrôles de zoom à l'interface
    const scannerBody = document.querySelector('#scannerModal .modal-body');
    
    // Créer les éléments de contrôle de zoom s'ils n'existent pas déjà
    if (!document.getElementById('zoom-controls')) {
        const zoomControls = document.createElement('div');
        zoomControls.id = 'zoom-controls';
        zoomControls.className = 'my-3 text-center';
        zoomControls.innerHTML = `
            <div class="mb-2 d-flex align-items-center justify-content-center">
                <span class="me-2"><i class="fas fa-search-minus"></i></span>
                <input type="range" id="zoom-slider" min="1" max="10" step="0.5" value="1" class="form-range mx-2" style="max-width: 60%;">
                <span class="ms-2"><i class="fas fa-search-plus"></i></span>
            </div>
            <div class="mb-2">
                <span id="zoom-level" class="badge bg-primary">Zoom: 1×</span>
            </div>
            <div class="btn-group">
                <button id="toggle-torch" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-lightbulb"></i> Flash
                </button>
                <button id="switch-camera" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-sync-alt"></i> Changer de caméra
                </button>
            </div>
        `;
        
        // Insérer les contrôles avant le bouton de fermeture
        scannerBody.appendChild(zoomControls);
        
        // Vérifier les capacités de zoom et du flash
        checkZoomCapabilities(stream);
    }
}

// Vérification des capacités de zoom et initialisation des contrôles
async function checkZoomCapabilities(stream) {
    const zoomSlider = document.getElementById('zoom-slider');
    const zoomLevel = document.getElementById('zoom-level');
    const toggleTorch = document.getElementById('toggle-torch');
    const switchCamera = document.getElementById('switch-camera');
    
    // Obtenir la piste vidéo
    const videoTrack = stream.getVideoTracks()[0];
    
    if (videoTrack) {
        console.log("Piste vidéo récupérée:", videoTrack.label);
        
        try {
            // Récupérer les capacités de la piste
            const capabilities = videoTrack.getCapabilities ? videoTrack.getCapabilities() : {};
            console.log("Capacités de la caméra:", capabilities);
            
            // Vérifier si le zoom est supporté
            if (capabilities.zoom) {
                console.log("Zoom supporté! Min:", capabilities.zoom.min, "Max:", capabilities.zoom.max);
                
                // Configurer le slider de zoom
                zoomSlider.min = capabilities.zoom.min;
                zoomSlider.max = capabilities.zoom.max;
                zoomSlider.step = (capabilities.zoom.max - capabilities.zoom.min) / 20;
                zoomSlider.value = capabilities.zoom.min;
                zoomLevel.textContent = `Zoom: ${capabilities.zoom.min}×`;
                
                // Activer le contrôle du zoom
                zoomSlider.addEventListener('input', function() {
                    const zoomValue = parseFloat(this.value);
                    videoTrack.applyConstraints({
                        advanced: [{ zoom: zoomValue }]
                    })
                    .then(() => {
                        zoomLevel.textContent = `Zoom: ${zoomValue.toFixed(1)}×`;
                    })
                    .catch(err => {
                        console.error("Erreur lors de l'application du zoom:", err);
                    });
                });
                
                // Rendre le contrôle visible
                zoomSlider.disabled = false;
                zoomSlider.style.display = 'block';
            } else {
                console.log("Le zoom n'est pas supporté par cette caméra");
                zoomSlider.disabled = true;
                zoomLevel.textContent = "Zoom non supporté";
            }
            
            // Vérifier si le flash/torch est supporté
            if (capabilities.torch) {
                console.log("Flash supporté!");
                toggleTorch.style.display = 'inline-block';
                
                // État du flash
                let torchOn = false;
                
                toggleTorch.addEventListener('click', function() {
                    torchOn = !torchOn;
                    videoTrack.applyConstraints({
                        advanced: [{ torch: torchOn }]
                    })
                    .then(() => {
                        this.classList.toggle('btn-outline-secondary', !torchOn);
                        this.classList.toggle('btn-warning', torchOn);
                        this.innerHTML = torchOn ? 
                            '<i class="fas fa-lightbulb"></i> Flash ON' : 
                            '<i class="fas fa-lightbulb"></i> Flash';
                    })
                    .catch(err => {
                        console.error("Erreur lors de l'activation du flash:", err);
                    });
                });
            } else {
                toggleTorch.style.display = 'none';
            }
            
            // Configuration du bouton pour changer de caméra
            switchCamera.addEventListener('click', function() {
                // Arrêter le scanner actuel
                stopCamera();
                
                // Obtenir le type de caméra actuel
                const currentFacingMode = videoTrack.getSettings().facingMode;
                console.log("Mode caméra actuel:", currentFacingMode);
                
                // Basculer entre les caméras avant et arrière
                const newFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
                
                // Redémarrer la caméra avec le nouveau mode
                startCameraWithFacingMode(newFacingMode);
            });
            
        } catch (err) {
            console.error("Erreur lors de la vérification des capacités:", err);
            zoomSlider.disabled = true;
            zoomLevel.textContent = "Contrôles avancés non disponibles";
            toggleTorch.style.display = 'none';
        }
    }
}

// Démarrer la caméra avec un mode spécifique (avant/arrière)
async function startCameraWithFacingMode(facingMode) {
    console.log("Démarrage de la caméra avec mode:", facingMode);
    const status = document.getElementById('scanner-status');
    
    status.className = 'alert alert-info';
    status.textContent = 'Changement de caméra...';
    
    try {
        const constraints = {
            audio: false,
            video: {
                facingMode: facingMode,
                width: { ideal: 1280 },  // Augmenter pour une meilleure résolution
                height: { ideal: 720 },
                focusMode: 'continuous'  // Auto-focus continu
            }
        };
        
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        currentStream = stream;
        
        // Initialiser Quagga avec le nouveau stream
        initQuaggaDirectly(stream);
        
    } catch (err) {
        console.error("Erreur lors du changement de caméra:", err);
        status.className = 'alert alert-danger';
        status.textContent = "Erreur lors du changement de caméra: " + err.message;
        
        // Essayer de revenir à la configuration par défaut
        startCameraSimple();
    }
}

// Modification de la fonction startCameraSimple pour une meilleure qualité d'image
async function startCameraSimple() {
    console.log("Démarrage de la caméra avec l'approche simplifiée");
    const status = document.getElementById('scanner-status');
    
    // Mettre à jour le statut
    status.className = 'alert alert-info';
    status.textContent = 'Initialisation de la caméra...';
    
    // Arrêter tout flux existant
    stopCamera();
    
    try {
        // Utiliser des contraintes pour une meilleure qualité d'image
        const constraints = {
            audio: false,
            video: {
                facingMode: "environment",        // Utiliser la caméra arrière
                width: { ideal: 1280 },           // Résolution plus élevée
                height: { ideal: 720 },
                focusMode: "continuous",          // Auto-focus continu
                exposureMode: "continuous",       // Exposition automatique
                whiteBalanceMode: "continuous"    // Balance des blancs automatique
            }
        };
        
        console.log("Demande d'accès à la caméra avec contraintes:", constraints);
        
        // Obtenir l'accès à la caméra
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        currentStream = stream;
        
        console.log("Accès à la caméra obtenu");
        
        // Initialiser Quagga avec le stream obtenu
        initQuaggaDirectly(stream);
    } catch (err) {
        console.error("Erreur d'accès à la caméra:", err);
        status.className = 'alert alert-danger';
        
        if (err.name === 'NotAllowedError') {
            status.textContent = "Accès à la caméra refusé. Veuillez autoriser l'accès.";
        } else if (err.name === 'NotFoundError') {
            status.textContent = "Caméra non trouvée sur votre appareil.";
        } else if (err.name === 'OverconstrainedError') {
            // Essayer avec des contraintes plus basiques si les contraintes avancées échouent
            console.log("Tentative avec des contraintes plus basiques");
            try {
                const basicConstraints = {
                    audio: false,
                    video: {
                        facingMode: "environment"
                    }
                };
                
                const stream = await navigator.mediaDevices.getUserMedia(basicConstraints);
                currentStream = stream;
                
                initQuaggaDirectly(stream);
            } catch (fallbackErr) {
                console.error("Échec du fallback:", fallbackErr);
                status.textContent = `Erreur d'accès à la caméra: ${fallbackErr.message || fallbackErr.name}`;
            }
        } else {
            status.textContent = `Erreur d'accès à la caméra: ${err.message || err.name}`;
        }
    }
}

// Fonction pour émettre un beep lors de la détection
function beepSuccess() {
    try {
        const beep = new Audio("data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU..." /* base64 audio data */);
        beep.volume = 0.2;
        beep.play();
    } catch (e) {
        console.log("Notification sonore non supportée");
    }
}

// Version alternative pour vérifier les capacités de la caméra
function checkCameraCapabilities() {
    const status = document.getElementById('scanner-status');
    status.className = 'alert alert-info';
    status.textContent = "Vérification des capacités de la caméra...";
    
    navigator.mediaDevices.enumerateDevices()
        .then(devices => {
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            console.log("Caméras disponibles:", videoDevices.length);
            
            if (videoDevices.length === 0) {
                status.className = 'alert alert-danger';
                status.textContent = "Aucune caméra détectée sur votre appareil";
                return Promise.reject("Aucune caméra disponible");
            }
            
            // Afficher les informations sur les caméras disponibles
            videoDevices.forEach((device, i) => {
                console.log(`Caméra ${i+1}: ${device.label || 'sans nom'} (${device.deviceId.substring(0, 10)}...)`);
            });
            
            status.textContent = `${videoDevices.length} caméra(s) trouvée(s). Initialisation...`;
            
            // Après vérification, démarrer la caméra
            return startCameraSimple();
        })
        .catch(err => {
            console.error("Erreur lors de la vérification des caméras:", err);
            status.className = 'alert alert-danger';
            status.textContent = "Erreur: " + (err.message || err);
        });
}

// Fonctions pour gérer les actions de stock et de produit
function ajusterStock(produitId, produitData = null) {
    console.log('Ajustement du stock pour le produit ID:', produitId);
    
    // Si les données du produit sont déjà fournies, utiliser ces données
    if (produitData) {
        console.log('Utilisation des données produit déjà récupérées:', produitData);
        afficherModalStock(produitData);
        return;
    }
    
    // Sinon, récupérer les données via Ajax
    fetch('ajax/get_produit.php?id=' + produitId)
        .then(response => {
            console.log('Réponse du serveur:', response.status);
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Données du produit reçues:', data);
            afficherModalStock(data);
        })
        .catch(error => {
            console.error('Erreur lors de l\'ajustement du stock:', error);
            alert('Erreur lors de la récupération des données du produit: ' + error.message);
        });
}

// Fonction pour afficher le modal d'ajustement de stock
function afficherModalStock(data) {
    // Vérifier que les données nécessaires sont présentes
    if (!data || !data.id) {
        throw new Error('Données du produit incomplètes');
    }
    
    // Définir une valeur par défaut pour la quantité si elle est undefined
    const quantite = data.quantite !== undefined ? data.quantite : 0;
    
    document.getElementById('stock_produit_id').value = data.id;
    document.getElementById('stock_product_name').textContent = data.nom || 'Produit sans nom';
    document.getElementById('stock_product_ref').textContent = data.reference || 'Sans référence';
    document.getElementById('stock_current_stock').textContent = `Stock actuel: ${quantite}`;
    document.getElementById('stock_quantite').value = 1;
    // Stocker le prix d'achat pour les transactions partenaires (au lieu du prix de vente)
    document.getElementById('stock_prix_vente').value = data.prix_achat || 0;
    
    // Réinitialiser le formulaire
    document.getElementById('motif').value = '';
    document.getElementById('motif-display').style.display = 'none';
    document.getElementById('partenaire-selection').style.display = 'none';
    document.getElementById('stock-alert').style.display = 'none';
    document.querySelectorAll('.motif-btn').forEach(btn => btn.classList.remove('active'));
    
    const stockModal = new bootstrap.Modal(document.getElementById('stockModal'));
    stockModal.show();
}

function modifierProduit(id) {
    fetch('ajax/get_product.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#edit_produit_id').val(data.produit.id);
                $('#edit_reference').val(data.produit.reference);
                $('#edit_nom').val(data.produit.nom);
                $('#edit_description').val(data.produit.description || '');
                $('#edit_prix_achat').val(data.produit.prix_achat);
                $('#edit_prix_vente').val(data.produit.prix_vente);
                $('#edit_seuil_alerte').val(data.produit.seuil_alerte);
                $('#edit_is_temporaire').prop('checked', data.produit.status === 'temporaire');
                $('#editProductModal').modal('show');
            } else {
                alert('Erreur: ' + data.message);
            }
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

function verifierProduit(code) {
    const url = 'ajax/verifier_produit.php?code=' + encodeURIComponent(code);
    console.log('Vérification du produit avec le code:', code);
    
    fetch(url)
        .then(response => {
            console.log('Statut de la réponse:', response.status);
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            console.log('Type de contenu:', contentType);
            
            if (!contentType || !contentType.includes('application/json')) {
                console.error('Type de contenu reçu:', contentType);
                throw new Error('La réponse n\'est pas au format JSON');
            }
            
            return response.text().then(text => {
                try {
                    console.log('Réponse brute:', text);
                    const data = JSON.parse(text);
                    console.log('Données parsées:', data);
                    return data;
            } catch (e) {
                    console.error('Erreur de parsing JSON:', e);
                    throw new Error('Réponse invalide du serveur');
                }
            });
        })
        .then(data => {
            console.log('Données reçues:', data);
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Arrêter le scanner et fermer le modal
            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
            }
            if (scannerInitialized) {
            Quagga.stop();
                scannerInitialized = false;
            }
            scannerModal.hide();
            
            if (data.existe && data.id) {
                console.log('Produit trouvé, ID:', data.id);
                // Vérifier que l'ID est bien un nombre
                const produitId = parseInt(data.id, 10);
                if (isNaN(produitId)) {
                    throw new Error('ID de produit invalide');
                }
                
                // Utiliser directement les données du produit récupérées
                const produitData = {
                    id: data.id,
                    nom: data.nom,
                    reference: data.reference,
                    quantite: data.quantite,
                    is_temporaire: data.is_temporaire
                };
                
                ajusterStock(produitId, produitData);
            } else if (data.existe) {
                throw new Error('Produit trouvé mais sans ID valide');
                    } else {
                console.log('Produit non trouvé, ajout d\'un nouveau produit avec le code:', code);
                const referenceInput = document.querySelector('#addProductModal input[name="reference"]');
                if (!referenceInput) {
                    throw new Error('Élément du formulaire non trouvé');
                }
                referenceInput.value = code;
                const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
                addProductModal.show();
            }
        })
        .catch(error => {
            console.error('Erreur détaillée:', error);
            alert('Erreur lors de la vérification du produit: ' + error.message);
            
            if (scanAttempts < MAX_SCAN_ATTEMPTS) {
                console.log('Nouvelle tentative de scan (' + (scanAttempts + 1) + '/' + MAX_SCAN_ATTEMPTS + ')');
                scanAttempts++;
                startCameraSimple();
            } else {
                alert('Nombre maximum de tentatives atteint. Veuillez réessayer plus tard.');
                scannerModal.hide();
            }
        });
}

// Styles pour la vidéo
const styles = `
.viewport {
    position: relative;
    width: 100%;
    height: 400px;
    overflow: hidden;
    background: #000;
}

#scanner-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
}

.scanner-laser {
    position: absolute;
    left: 0;
    right: 0;
    height: 2px;
    background: red;
    box-shadow: 0 0 4px red;
    animation: scan 2s infinite;
    top: 50%;
    transform: translateY(-50%);
}

@keyframes scan {
    0% { top: 20%; }
    50% { top: 80%; }
    100% { top: 20%; }
}

#camera-select {
    max-width: 300px;
    margin: 0 auto;
}
`;

const styleSheet = document.createElement("style");
styleSheet.textContent = styles;
document.head.appendChild(styleSheet);

// Initialiser l'affichage des boutons de motifs
window.addEventListener('DOMContentLoaded', function() {
    // Par défaut, on affiche les boutons d'entrée (car "Entrée" est généralement l'option par défaut)
    const typeActuel = document.querySelector('input[name="type_mouvement"]:checked').value;
    if (typeActuel === 'entree') {
        document.getElementById('motifs-entree').style.display = 'block';
        document.getElementById('motifs-sortie').style.display = 'none';
    } else {
        document.getElementById('motifs-entree').style.display = 'none';
        document.getElementById('motifs-sortie').style.display = 'block';
    }
    
    // Ajouter les gestionnaires d'événements pour les boutons des partenaires
    document.getElementById('btn-prete-partenaire').addEventListener('click', function() {
        chargerPartenaires();
        document.getElementById('partenaire-selection').style.display = 'block';
    });
    
    document.getElementById('btn-retour-pret').addEventListener('click', function() {
        chargerPartenaires();
        document.getElementById('partenaire-selection').style.display = 'block';
    });
});

// Fonction pour charger les partenaires via AJAX
function chargerPartenaires() {
    console.log('Début du chargement des partenaires');
    
    fetch('ajax/get_partenaires.php')
        .then(response => {
            console.log('Réponse reçue:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Données reçues:', data);
            
            if (data.success) {
                const select = document.getElementById('partenaire_id');
                console.log('Élément select trouvé:', select);
                
                // Vider le select sauf l'option par défaut
                select.innerHTML = '<option value="">Choisir un partenaire...</option>';
                
                // Vérifier s'il y a des partenaires
                if (data.partenaires && data.partenaires.length > 0) {
                    console.log(`Ajout de ${data.partenaires.length} partenaires au select`);
                    
                    // Ajouter les options des partenaires
                    data.partenaires.forEach(partenaire => {
                        const option = document.createElement('option');
                        option.value = partenaire.id;
                        option.textContent = partenaire.nom;
                        select.appendChild(option);
                    });
                } else {
                    console.warn('Aucun partenaire retourné par le serveur');
                    const option = document.createElement('option');
                    option.disabled = true;
                    option.textContent = 'Aucun partenaire disponible';
                    select.appendChild(option);
                }
            } else {
                console.error('Erreur lors du chargement des partenaires:', data.message);
                const select = document.getElementById('partenaire_id');
                select.innerHTML = '<option value="">Erreur lors du chargement</option>';
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des partenaires:', error);
            const select = document.getElementById('partenaire_id');
            select.innerHTML = '<option value="">Erreur lors du chargement</option>';
            
            // Afficher une alerte pour informer l'utilisateur
            const stockAlert = document.getElementById('stock-alert');
            stockAlert.className = 'alert alert-danger';
            stockAlert.textContent = 'Erreur lors du chargement des partenaires: ' + error.message;
            stockAlert.style.display = 'block';
        });
}

// Fonction pour masquer le sélecteur de partenaires lorsqu'un autre motif est sélectionné
document.querySelectorAll('.motif-btn').forEach(function(button) {
    button.addEventListener('click', function() {
        // Si ce n'est pas un bouton lié aux partenaires, masquer le sélecteur
        if (this.id !== 'btn-prete-partenaire' && this.id !== 'btn-retour-pret') {
            document.getElementById('partenaire-selection').style.display = 'none';
        }
    });
});

// Gestionnaire pour le bouton d'enregistrement du mouvement de stock
document.getElementById('submitStockBtn').addEventListener('click', function() {
    const form = document.getElementById('stockForm');
    const stockAlert = document.getElementById('stock-alert');
    
    // Vérifier que le formulaire est valide
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const motif = document.getElementById('motif').value;
    const partenaireId = document.getElementById('partenaire_id').value;
    
    // Vérifier si un partenaire est requis mais non sélectionné
    if ((motif === 'Prêté à un partenaire' || motif === 'Retour de prêt') && !partenaireId) {
        stockAlert.className = 'alert alert-danger';
        stockAlert.textContent = 'Veuillez sélectionner un partenaire';
        stockAlert.style.display = 'block';
        return;
    }
    
    // Récupérer les données du formulaire
    const formData = {
        produit_id: document.getElementById('stock_produit_id').value,
        type_mouvement: document.querySelector('input[name="type_mouvement"]:checked').value,
        quantite: document.getElementById('stock_quantite').value,
        motif: motif,
        partenaire_id: partenaireId,
        prix_vente: document.getElementById('stock_prix_vente').value
    };
    
    console.log('Envoi des données de stock:', formData);
    
    // Envoyer les données au serveur
    fetch('ajax/update_stock.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        console.log('Statut de la réponse:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Réponse du serveur:', data);
        
        if (data.error) {
            // Afficher l'erreur
            stockAlert.className = 'alert alert-danger';
            stockAlert.textContent = data.error;
            stockAlert.style.display = 'block';
        } else if (data.success) {
            // Fermer le modal
            const stockModal = bootstrap.Modal.getInstance(document.getElementById('stockModal'));
            stockModal.hide();
            
            // Mettre à jour l'affichage du produit dans le tableau
            const produitRow = document.querySelector(`tr[data-produit-id="${data.produit.id}"]`);
            if (produitRow) {
                const quantiteCell = produitRow.querySelector('.badge');
                if (quantiteCell) {
                    quantiteCell.textContent = data.produit.quantite;
                    
                    // Mettre à jour la classe de la badge selon le niveau de stock
                    const seuilAlerte = parseInt(produitRow.getAttribute('data-seuil-alerte'));
                    if (data.produit.quantite <= 0) {
                        quantiteCell.className = 'badge rounded-pill bg-danger';
                    } else if (data.produit.quantite <= seuilAlerte) {
                        quantiteCell.className = 'badge rounded-pill bg-warning';
                    } else {
                        quantiteCell.className = 'badge rounded-pill bg-success';
                    }
                }
            }
            
            // Afficher un message de succès
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Erreur lors de l\'ajustement du stock:', error);
        stockAlert.className = 'alert alert-danger';
        stockAlert.textContent = 'Erreur lors de la communication avec le serveur';
        stockAlert.style.display = 'block';
    });
});

// Gestionnaires pour les boutons + et - de quantité
document.getElementById('decreaseQty').addEventListener('click', function() {
    const input = document.getElementById('stock_quantite');
    const value = parseInt(input.value);
    if (value > 1) {
        input.value = value - 1;
    }
});

document.getElementById('increaseQty').addEventListener('click', function() {
    const input = document.getElementById('stock_quantite');
    const value = parseInt(input.value);
    input.value = value + 1;
});

// Gestion des boutons de motif
document.querySelectorAll('.motif-btn').forEach(function(button) {
    button.addEventListener('click', function() {
        const motif = this.getAttribute('data-motif');
        document.getElementById('motif').value = motif;
        
        // Afficher le motif sélectionné
        const motifDisplay = document.getElementById('motif-display');
        motifDisplay.textContent = 'Motif sélectionné: ' + motif;
        motifDisplay.style.display = 'block';
        
        // Marquer ce bouton comme actif et désactiver les autres
        document.querySelectorAll('.motif-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        this.classList.add('active');
    });
});

// Gestion du type de mouvement pour afficher les boutons appropriés
document.querySelectorAll('input[name="type_mouvement"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        if (this.value === 'entree') {
            document.getElementById('motifs-entree').style.display = 'block';
            document.getElementById('motifs-sortie').style.display = 'none';
        } else {
            document.getElementById('motifs-entree').style.display = 'none';
            document.getElementById('motifs-sortie').style.display = 'block';
        }
    });
});

// Traitement ajax du formulaire pour ajuster le stock
$(document).on("click", ".submitBtn", function(e) {
    e.preventDefault();
    var form = $(this).parents('form');
    var quantity = form.find('input[name="quantity"]').val();
    var operation = form.find('select[name="operation"]').val();
    var motif = form.find('input[name="motif"]').val();

    // Validation des entrées
    if (quantity === "" || isNaN(quantity) || quantity <= 0) {
        showAlert('error', 'Veuillez entrer une quantité valide.');
        return false;
    }

    if (motif === "") {
        showAlert('error', 'Veuillez sélectionner un motif.');
        return false;
    }

    // ... reste du code existant ...
});
</script> 