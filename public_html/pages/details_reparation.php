<?php
// Vérifier si l'ID de la réparation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_message("ID réparation non spécifié.", "danger");
    redirect("reparations");
}

$reparation_id = (int)$_GET['id'];

// Récupérer les informations de la réparation
try {
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email
        FROM reparations r
        JOIN clients c ON r.client_id = c.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reparation_id]);
    $reparation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reparation) {
        set_message("Réparation non trouvée.", "danger");
        redirect("reparations");
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des informations de la réparation: " . $e->getMessage(), "danger");
    redirect("reparations");
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupérer et nettoyer les données du formulaire
    $statut = cleanInput($_POST['statut']);
    $notes_techniques = isset($_POST['notes_techniques']) ? cleanInput($_POST['notes_techniques']) : '';
    $prix = isset($_POST['prix_reparation']) ? floatval(str_replace(',', '.', $_POST['prix_reparation'])) : null;
    
    // Mettre à jour la réparation dans la base de données
    try {
        $stmt = $pdo->prepare("
            UPDATE reparations 
            SET statut = ?, notes_techniques = ?, prix_reparation = ?
            WHERE id = ?
        ");
        $stmt->execute([$statut, $notes_techniques, $prix, $reparation_id]);
        
        set_message("Réparation mise à jour avec succès!", "success");
        // Rediriger sur la même page pour rafraîchir les données
        redirect("details_reparation", ["id" => $reparation_id]);
    } catch (PDOException $e) {
        set_message("Erreur lors de la mise à jour de la réparation: " . $e->getMessage(), "danger");
    }
}

// Formater la date d'entrée
$date_reception = date('d/m/Y', strtotime($reparation['date_reception']));
?>

<div class="mobile-details container-fluid p-0">
    <!-- En-tête fixe -->
    <div class="header-fixed">
        <div class="d-flex justify-content-between align-items-center px-3 py-2">
            <a href="index.php?page=reparations" class="btn-circle">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h4 class="mb-0 text-truncate mx-2">Réparation #<?php echo $reparation_id; ?></h4>
            <div class="status-badge">
                <?php 
                    $statusClass = 'primary';
                    if (strpos(strtolower($reparation['statut']), 'termin') !== false) {
                        $statusClass = 'success';
                    } elseif (strpos(strtolower($reparation['statut']), 'attente') !== false) {
                        $statusClass = 'warning';
                    } elseif (strpos(strtolower($reparation['statut']), 'cours') !== false) {
                        $statusClass = 'info';
                    } elseif (strpos(strtolower($reparation['statut']), 'annul') !== false) {
                        $statusClass = 'danger';
                    }
                ?>
                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($reparation['statut']); ?></span>
            </div>
        </div>
        
        <!-- Info clients/appareil -->
        <div class="info-panel px-3 py-2">
            <div class="client-info mb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="info-item">
                            <i class="fas fa-user text-primary"></i>
                            <span><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone text-primary"></i>
                            <span><?php echo htmlspecialchars($reparation['client_telephone']); ?></span>
                        </div>
                        <?php if (!empty($reparation['client_email'])): ?>
                        <div class="info-item">
                            <i class="fas fa-envelope text-primary"></i>
                            <span><?php echo htmlspecialchars($reparation['client_email']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <div class="info-price" id="prixContainer">
                            <div class="info-label">Prix:</div>
                            <div class="price-value" id="prixValue"><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'Non défini'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Bouton pour changer le statut rapidement -->
            <div class="mt-2 text-center">
                <button type="button" class="btn btn-sm btn-primary w-75" id="btn-statut-rapide">
                    <i class="fas fa-tag me-1"></i> STATUT
                </button>
            </div>
            <div class="device-info">
                <div class="info-item">
                    <i class="fas fa-laptop text-primary"></i>
                    <span><?php echo htmlspecialchars($reparation['type_appareil']); ?> - <?php echo htmlspecialchars($reparation['modele']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-calendar-alt text-primary"></i>
                    <span>Reçu le <?php echo $date_reception; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contenu principal avec défilement -->
    <div class="main-content">
        <!-- Description du problème et notes techniques -->
        <div class="card detail-card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Problème signalé</h5>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($reparation['description_probleme'])); ?></p>
            </div>
        </div>
        
        <!-- Formulaire de mise à jour -->
        <form method="POST" action="index.php?page=details_reparation&id=<?php echo $reparation_id; ?>" class="needs-validation" novalidate>
            <!-- Statut -->
            <div class="card detail-card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Statut de la réparation</h5>
                </div>
                <div class="card-body">
                    <select class="form-select form-select-lg mb-3" id="statut" name="statut" required>
                        <option value="En attente" <?php echo $reparation['statut'] == 'En attente' ? 'selected' : ''; ?>>En attente</option>
                        <option value="En cours" <?php echo $reparation['statut'] == 'En cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="Terminé" <?php echo $reparation['statut'] == 'Terminé' ? 'selected' : ''; ?>>Terminé</option>
                        <option value="Livré" <?php echo $reparation['statut'] == 'Livré' ? 'selected' : ''; ?>>Livré</option>
                    </select>
                </div>
            </div>
            
            <!-- Prix -->
            <div class="card detail-card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-euro-sign me-2"></i>Prix de la réparation</h5>
                </div>
                <div class="card-body">
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control" id="prix_reparation" name="prix_reparation" 
                               value="<?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', '') : ''; ?>" 
                               placeholder="0,00">
                        <span class="input-group-text">€</span>
                    </div>
                </div>
            </div>
            
            <!-- Notes techniques -->
            <div class="card detail-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Notes techniques</h5>
                </div>
                <div class="card-body">
                    <textarea class="form-control" id="notes_techniques" name="notes_techniques" rows="5" placeholder="Ajoutez des détails sur le diagnostic et les réparations effectuées"><?php echo htmlspecialchars($reparation['notes_techniques']); ?></textarea>
                </div>
            </div>
            
            <!-- Bouton de mise à jour -->
            <div class="mb-4">
                <button type="submit" class="btn btn-lg btn-primary w-100">
                    <i class="fas fa-save me-2"></i>Mettre à jour
                </button>
            </div>
        </form>
        
        <!-- Actions rapides -->
        <div class="action-buttons mb-5">
            <div class="action-title">
                <span class="line"></span>
                <h6>Actions rapides</h6>
                <span class="line"></span>
            </div>
            
            <div class="action-grid">
                <a href="#" class="action-card" id="btn-ajouter-photo">
                    <div class="action-icon bg-primary">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div class="action-text">Ajouter une photo</div>
                </a>
                
                <a href="#" class="action-card" id="btn-commander-piece">
                    <div class="action-icon bg-warning">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="action-text">Commander pièce</div>
                </a>
                
                <a href="tel:<?php echo htmlspecialchars($reparation['client_telephone']); ?>" class="action-card">
                    <div class="action-icon bg-info">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div class="action-text">Appeler client</div>
                </a>
                
                <a href="#" class="action-card" 
                   data-client-id="<?php echo htmlspecialchars($reparation['client_id']); ?>"
                   data-client-nom="<?php echo htmlspecialchars($reparation['client_nom']); ?>"
                   data-client-prenom="<?php echo htmlspecialchars($reparation['client_prenom']); ?>"
                   data-client-tel="<?php echo htmlspecialchars($reparation['client_telephone']); ?>"
                   onclick="openSmsModal(
                       '<?php echo htmlspecialchars($reparation['client_id']); ?>', 
                       '<?php echo htmlspecialchars($reparation['client_nom']); ?>', 
                       '<?php echo htmlspecialchars($reparation['client_prenom']); ?>', 
                       '<?php echo htmlspecialchars($reparation['client_telephone']); ?>'
                   ); return false;">
                    <div class="action-icon bg-success">
                        <i class="fas fa-sms"></i>
                    </div>
                    <div class="action-text">Envoyer SMS</div>
                </a>
                
                <a href="#" class="action-card" id="btn-partager-suivi">
                    <div class="action-icon" style="background: linear-gradient(135deg, #0078e8 0%, #37a1ff 100%);">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="action-text">Suivi réparation</div>
                </a>
                
                <?php if ($reparation['statut'] === 'en_attente_accord_client'): ?>
                <a href="#" class="action-card" id="btn-envoyer-devis">
                    <div class="action-icon" style="background: linear-gradient(135deg, #28a745 0%, #1d943a 100%);">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="action-text">Envoyer devis</div>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($reparation['client_email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($reparation['client_email']); ?>" class="action-card">
                    <div class="action-icon bg-danger">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="action-text">Email client</div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter une photo -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-bottom-0">
                <h5 class="modal-title" id="cameraModalLabel">
                    <i class="fas fa-camera me-2"></i>Prendre une photo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <form id="cameraForm">
                    <input type="hidden" name="reparation_id" value="<?php echo $reparation_id; ?>">
                    
                    <!-- Zone caméra -->
                    <div id="cameraContainer" class="text-center mb-3">
                        <video id="camera" autoplay playsinline class="img-fluid rounded" style="max-height: 400px;"></video>
                        <canvas id="cameraCanvas" class="d-none"></canvas>
                    </div>

                    <!-- Zone de prévisualisation -->
                    <div id="photoPreview" class="text-center mb-3 d-none">
                        <div class="position-relative d-inline-block">
                            <img id="previewImage" src="" alt="Aperçu" class="img-fluid rounded" style="max-height: 400px;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" id="retakePhoto">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label for="photoDescription" class="form-label">Description (optionnelle)</label>
                        <textarea class="form-control" id="photoDescription" name="description" rows="2" placeholder="Décrivez ce que montre la photo..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="button" class="btn btn-primary" id="captureBtn">
                    <i class="fas fa-camera me-2"></i> Capturer
                </button>
                <button type="button" class="btn btn-success d-none" id="savePhotoBtn">
                    <i class="fas fa-save me-2"></i> Sauvegarder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour commander une pièce détachée -->
<div class="modal fade" id="ajouterCommandeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-shopping-cart me-2 text-primary"></i>
                    Nouvelle commande de pièces
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="commandeForm">
                    <!-- Section Client -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-2">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-user me-2 text-primary"></i>
                                Client
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="client_selectionne_commande" class="alert alert-success mt-1">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-check me-2"></i>
                                    <div class="flex-grow-1">
                                        <strong>Client sélectionné :</strong> 
                                        <span id="nom_client_selectionne_commande"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="client_id" id="client_id_commande" value="<?php echo $reparation['client_id']; ?>">
                        </div>
                    </div>

                    <!-- Section Réparation -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white py-2">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-tools me-2 text-primary"></i>
                                Réparation associée
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-tools me-2"></i>
                                    <div>
                                        <strong>Réparation #<?php echo $reparation_id; ?></strong><br>
                                        <?php echo htmlspecialchars($reparation['type_appareil']); ?> - <?php echo htmlspecialchars($reparation['modele']); ?>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="reparation_id" id="reparation_id" value="<?php echo $reparation_id; ?>">
                        </div>
                    </div>

                    <!-- Section Commande -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-2">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-box me-2 text-primary"></i>
                                Détails de la commande
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Fournisseur</label>
                                        <div class="select-container position-relative">
                                            <select class="form-select custom-select" name="fournisseur_id" id="fournisseur_id">
                                                <option value="">Sélectionner un fournisseur</option>
                                                <!-- Les fournisseurs seront chargés dynamiquement -->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Pièces commandées</label>
                                        <input type="text" class="form-control" name="nom_piece">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Code barre</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="code_barre" id="code_barre" placeholder="Saisir le code barre">
                                            <button type="button" class="btn btn-outline-secondary" id="scan-code-btn">
                                                <i class="fas fa-barcode"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Quantité</label>
                                        <div class="input-group">
                                            <button type="button" class="btn btn-outline-secondary" onclick="decrementQuantity()">-</button>
                                            <input type="number" class="form-control text-center" name="quantite" min="1" value="1">
                                            <button type="button" class="btn btn-outline-secondary" onclick="incrementQuantity()">+</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Prix estimé (€)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="prix_estime" step="0.01">
                                            <span class="input-group-text">€</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-warning flex-grow-1 status-btn active" data-status="en_attente">
                                                <i class="fas fa-clock me-1"></i> En attente
                                            </button>
                                            <button type="button" class="btn btn-outline-primary flex-grow-1 status-btn" data-status="commande">
                                                <i class="fas fa-shopping-cart me-1"></i> Commandé
                                            </button>
                                            <button type="button" class="btn btn-outline-success flex-grow-1 status-btn" data-status="recue">
                                                <i class="fas fa-check me-1"></i> Reçu
                                            </button>
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-outline-danger w-100 status-btn" data-status="urgent">
                                                <i class="fas fa-exclamation-triangle me-1"></i> URGENT
                                            </button>
                                        </div>
                                        <input type="hidden" name="statut" id="selectedStatus" value="en_attente">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="saveCommande()">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Statut Rapide -->
<div class="modal fade" id="statutRapideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-bottom-0">
                <h5 class="modal-title">
                    <i class="fas fa-tag me-2"></i>Changer le statut
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="container-fluid p-0">
                    <!-- Onglets pour les catégories -->
                    <div class="status-tabs">
                        <ul class="nav nav-pills mb-3 d-flex flex-nowrap overflow-auto py-2 px-2" id="status-tab" role="tablist">
                            <li class="nav-item me-2" role="presentation">
                                <button class="nav-link active" id="reception-tab" data-bs-toggle="pill" data-bs-target="#reception" type="button" role="tab">Réception</button>
                            </li>
                            <li class="nav-item me-2" role="presentation">
                                <button class="nav-link" id="diagnostic-tab" data-bs-toggle="pill" data-bs-target="#diagnostic" type="button" role="tab">Diagnostic</button>
                            </li>
                            <li class="nav-item me-2" role="presentation">
                                <button class="nav-link" id="encours-tab" data-bs-toggle="pill" data-bs-target="#encours" type="button" role="tab">En cours</button>
                            </li>
                            <li class="nav-item me-2" role="presentation">
                                <button class="nav-link" id="finalisation-tab" data-bs-toggle="pill" data-bs-target="#finalisation" type="button" role="tab">Finalisation</button>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Contenu des onglets -->
                    <div class="tab-content" id="status-tabContent">
                        <!-- Onglet Réception -->
                        <div class="tab-pane fade show active" id="reception" role="tabpanel">
                            <div class="status-container p-3">
                                <form method="post" action="" id="form-statut-reception">
                                    <input type="hidden" name="categorie_id" value="1">
                                    
                                    <!-- Options de statut -->
                                    <div class="status-options">
                                        <div class="status-option mb-3" data-status-id="1" onclick="selectStatut(this, 'En attente', 1)">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon bg-warning">
                                                    <i class="fas fa-clock"></i>
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="status-title fw-bold">En attente</div>
                                                    <div class="status-description text-muted small">Réparation mise en attente</div>
                                                </div>
                                                <div class="status-check">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="status-option mb-3" data-status-id="2" onclick="selectStatut(this, 'Devis à préparer', 1)">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon bg-info">
                                                    <i class="fas fa-file-invoice-dollar"></i>
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="status-title fw-bold">Devis à préparer</div>
                                                    <div class="status-description text-muted small">Un devis doit être préparé</div>
                                                </div>
                                                <div class="status-check">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="status-option mb-3" data-status-id="3" onclick="selectStatut(this, 'En attente de pièces', 1)">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon bg-warning">
                                                    <i class="fas fa-box"></i>
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="status-title fw-bold">En attente de pièces</div>
                                                    <div class="status-description text-muted small">Commande de pièces en cours</div>
                                                </div>
                                                <div class="status-check">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="nouveau_statut" id="nouveau_statut_reception">
                                    <input type="hidden" name="statut_id" id="statut_id_reception">
                                </form>
                            </div>
                        </div>
                        
                        <!-- Onglet Diagnostic -->
                        <div class="tab-pane fade" id="diagnostic" role="tabpanel">
                            <div class="status-container p-3">
                                <form method="post" action="" id="form-statut-diagnostic">
                                    <input type="hidden" name="categorie_id" value="2">
                                    
                                    <!-- Options de statut -->
                                    <div class="status-options">
                                        <div class="status-option mb-3" data-status-id="4" onclick="selectStatut(this, 'Diagnostic en cours', 2)">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon bg-info">
                                                    <i class="fas fa-search"></i>
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="status-title fw-bold">Diagnostic en cours</div>
                                                    <div class="status-description text-muted small">Analyse du problème en cours</div>
                                                </div>
                                                <div class="status-check">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="status-option mb-3" data-status-id="5" onclick="selectStatut(this, 'En attente d\'accord client', 2)">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon bg-warning">
                                                    <i class="fas fa-user-check"></i>
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="status-title fw-bold">En attente d'accord client</div>
                                                    <div class="status-description text-muted small">Validation du devis nécessaire</div>
                                                </div>
                                                <div class="status-check">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="nouveau_statut" id="nouveau_statut_diagnostic">
                                    <input type="hidden" name="statut_id" id="statut_id_diagnostic">
                                </form>
                            </div>
                        </div>
                        
                        <!-- Onglet En cours -->
                        <div class="tab-pane fade" id="encours" role="tabpanel">
                            <div class="status-container p-3">
                                <form method="post" action="" id="form-statut-encours">
                                    <input type="hidden" name="categorie_id" value="3">
                                    
                                    <!-- Options de statut -->
                                    <div class="status-options">
                                        <div class="status-option mb-3" data-status-id="6" onclick="selectStatut(this, 'Réparation en cours', 3)">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon bg-primary">
                                                    <i class="fas fa-tools"></i>
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="status-title fw-bold">Réparation en cours</div>
                                                    <div class="status-description text-muted small">Travaux de réparation en cours</div>
                                                </div>
                                                <div class="status-check">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="status-option mb-3" data-status-id="7" onclick="selectStatut(this, 'Tests en cours', 3)">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon bg-info">
                                                    <i class="fas fa-vial"></i>
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="status-title fw-bold">Tests en cours</div>
                                                    <div class="status-description text-muted small">Vérification de la réparation</div>
                                                </div>
                                                <div class="status-check">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="status-option mb-3" data-status-id="8" onclick="selectStatut(this, 'Réparation en pause', 3)">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon bg-warning">
                                                    <i class="fas fa-pause"></i>
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="status-title fw-bold">Réparation en pause</div>
                                                    <div class="status-description text-muted small">Réparation mise en pause temporairement</div>
                                                </div>
                                                <div class="status-check">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="nouveau_statut" id="nouveau_statut_encours">
                                    <input type="hidden" name="statut_id" id="statut_id_encours">
                                </form>
                            </div>
                        </div>
                        
                        <!-- Onglet Finalisation -->
                        <div class="tab-pane fade" id="finalisation" role="tabpanel">
                            <div class="status-container p-3">
                                <form method="post" action="" id="form-statut-finalisation">
                                    <input type="hidden" name="categorie_id" value="4">
                                    
                                    <!-- Options de statut -->
                                    <div class="status-options">
                                        <div class="status-option mb-3" data-status-id="9" onclick="selectStatut(this, 'Réparation terminée', 4)">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon bg-success">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="status-title fw-bold">Réparation terminée</div>
                                                    <div class="status-description text-muted small">Prêt à être récupéré par le client</div>
                                                </div>
                                                <div class="status-check">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="status-option mb-3" data-status-id="10" onclick="selectStatut(this, 'Réparation annulée', 4)">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon bg-danger">
                                                    <i class="fas fa-times"></i>
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="status-title fw-bold">Réparation annulée</div>
                                                    <div class="status-description text-muted small">Annulation de la réparation</div>
                                                </div>
                                                <div class="status-check">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="status-option mb-3" data-status-id="11" onclick="selectStatut(this, 'Appareil restitué', 4)">
                                            <div class="d-flex align-items-center">
                                                <div class="status-icon bg-success">
                                                    <i class="fas fa-check-double"></i>
                                                </div>
                                                <div class="ms-3 flex-grow-1">
                                                    <div class="status-title fw-bold">Appareil restitué</div>
                                                    <div class="status-description text-muted small">Récupéré par le client</div>
                                                </div>
                                                <div class="status-check">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="nouveau_statut" id="nouveau_statut_finalisation">
                                    <input type="hidden" name="statut_id" id="statut_id_finalisation">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="btn-appliquer-statut">
                    <i class="fas fa-save me-2"></i>Appliquer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour partager le lien de suivi avec le client -->
<div class="modal fade" id="modalSuiviClient" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title">
                    <i class="fas fa-link me-2"></i>Partager le lien de suivi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label">Lien de suivi</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="lienSuivi" value="<?php echo get_site_url() . '/index.php?page=suivi_reparation&id=' . $reparation_id; ?>" readonly>
                        <button class="btn btn-outline-primary" type="button" id="btnCopierLien">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <div class="alert alert-success mt-2 d-none" id="alertCopieLien">
                        <i class="fas fa-check-circle me-2"></i>Lien copié !
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0">
                            <i class="fas fa-sms me-2 text-primary"></i>
                            Envoyer par SMS
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" id="texteSMS" rows="3">Bonjour, vous pouvez suivre l'état de votre réparation ici: <?php echo get_site_url() . '/index.php?page=suivi_reparation&id=' . $reparation_id; ?></textarea>
                        </div>
                        <button type="button" class="btn btn-success w-100" id="btnEnvoyerSMS">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer par SMS
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour confirmer l'envoi du lien d'acceptation de devis -->
<div class="modal fade" id="modalConfirmDevis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white border-0">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Envoyer lien d'acceptation de devis
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="icon-circle bg-success text-white mx-auto mb-3">
                        <i class="fas fa-paper-plane fa-2x"></i>
                    </div>
                    <h5>Confirmer l'envoi du lien</h5>
                    <p class="text-muted">
                        Un email sera envoyé à <strong><?php echo htmlspecialchars($reparation['client_email']); ?></strong> contenant un lien permettant au client d'accepter le devis d'un montant de <strong><?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : 'non défini'; ?></strong>.
                    </p>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span>Assurez-vous que le montant du devis est correctement renseigné avant d'envoyer le lien.</span>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="smsToggle">
                        <label class="form-check-label" for="smsToggle">Envoyer également par SMS</label>
                    </div>
                </div>
                
                <div id="smsContent" class="card border-0 shadow-sm mb-3 d-none">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0">
                            <i class="fas fa-sms me-2 text-primary"></i>
                            Contenu du SMS
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" id="texteSMSDevis" rows="3">Bonjour, votre devis de réparation pour un montant de <?php echo !empty($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . '€' : 'XX'; ?> est prêt. Pour l'accepter, cliquez sur ce lien: <?php echo get_site_url() . '/pages/accepter_devis.php?id=' . $reparation_id; ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annuler
                </button>
                <button type="button" class="btn btn-success" id="btnConfirmEnvoyerDevis">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <span class="btn-text">Envoyer</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast de succès -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="toastSuccess" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i>
                <span id="toastSuccessMessage">Message de succès</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Toast d'erreur -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="toastError" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-exclamation-circle me-2"></i>
                <span id="toastErrorMessage">Message d'erreur</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<style>
/* Styles pour l'interface détaillée mobile */
body, html {
    height: 100%;
    margin: 0;
    padding: 0;
    background: #f5f7fa;
    overflow: hidden;
    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
}

.mobile-details {
    display: flex;
    flex-direction: column;
    height: 100vh;
    width: 100%;
    overflow: hidden;
    background: #f5f7fa;
}

.header-fixed {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    width: 100%;
    border-bottom-left-radius: 20px;
    border-bottom-right-radius: 20px;
}

.main-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 1rem 1rem 100px 1rem;
    -webkit-overflow-scrolling: touch;
}

.btn-circle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f3f7;
    color: #495057;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    flex-shrink: 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.btn-circle:hover, .btn-circle:focus {
    background: #e9ecef;
    color: #212529;
    transform: scale(1.05);
}

.status-badge {
    margin-left: auto;
}

.status-badge .badge {
    font-size: 0.9rem;
    font-weight: 500;
    padding: 0.5em 0.85em;
    border-radius: 50px;
    letter-spacing: 0.3px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.info-panel {
    background: #f8f9fa;
    padding: 12px 15px;
    border-bottom-left-radius: 20px;
    border-bottom-right-radius: 20px;
    margin-top: 5px;
}

.client-info, .device-info {
    font-size: 0.9rem;
}

.info-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
}

.info-item i {
    width: 22px;
    margin-right: 10px;
    text-align: center;
    font-size: 1rem;
}

.info-price {
    background-color: #f0f7ff;
    padding: 8px 12px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    cursor: pointer;
    transition: all 0.2s;
}

.info-price:hover {
    background-color: #e0f0ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.info-label {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.price-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0d6efd;
}

/* Cartes de détails */
.detail-card {
    border-radius: 15px;
    overflow: hidden;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.detail-card:hover {
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.detail-card .card-header {
    background-color: #fff;
    padding: 15px 20px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.detail-card .card-body {
    padding: 20px;
}

/* Formulaires */
.form-control, .form-select {
    border-radius: 10px;
    padding: 12px 15px;
    font-size: 1rem;
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    transition: all 0.3s;
}

.form-control:focus, .form-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.2);
}

textarea.form-control {
    min-height: 120px;
}

.input-group-text {
    border-radius: 0 10px 10px 0;
    background-color: #f8f9fa;
    border: 1px solid rgba(0,0,0,0.1);
    font-weight: 600;
}

/* Actions rapides */
.action-buttons {
    margin-top: 30px;
}

.action-title {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.action-title h6 {
    margin: 0 12px;
    font-weight: 600;
    color: #6c757d;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.action-title .line {
    flex: 1;
    height: 1px;
    background-color: #dee2e6;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: #fff;
    border-radius: 15px;
    padding: 20px 15px;
    text-decoration: none;
    color: #495057;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.action-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    color: #0d6efd;
}

.action-icon {
    width: 50px;
    height: 50px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    color: white;
    font-size: 1.5rem;
}

.action-text {
    font-weight: 600;
    font-size: 0.9rem;
    text-align: center;
}

/* Responsive */
@media (min-width: 768px) {
    .action-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .main-content {
        padding: 1.5rem 2rem 100px 2rem;
    }
}

@media (min-width: 992px) {
    .mobile-details {
        max-width: 900px;
        margin: 0 auto;
    }
}

/* Mode sombre */
.dark-mode {
    background-color: #111827;
    color: #f8fafc;
}

.dark-mode .mobile-details {
    background-color: #111827;
}

.dark-mode .header-fixed {
    background-color: #1f2937;
    box-shadow: 0 1px 10px rgba(0,0,0,0.3);
}

.dark-mode .info-panel {
    background-color: #2d3748;
}

.dark-mode .info-item {
    color: #f8fafc;
}

.dark-mode .info-item i {
    color: #60a5fa;
}

.dark-mode .info-price {
    background-color: #3b4c67;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.dark-mode .info-price:hover {
    background-color: #4b5c77;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.dark-mode .info-label {
    color: #cfd8e3;
}

.dark-mode .price-value {
    color: #60a5fa;
}

.dark-mode .device-info {
    color: #e2e8f0;
}

.dark-mode .btn-circle {
    background-color: #374151;
    color: #f8fafc;
}

.dark-mode .btn-circle:hover {
    background-color: #4b5563;
    color: #f8fafc;
}

.dark-mode .detail-card {
    background-color: #1f2937;
    box-shadow: 0 3px 15px rgba(0,0,0,0.3);
    border-color: #374151;
}

.dark-mode .detail-card:hover {
    box-shadow: 0 5px 20px rgba(0,0,0,0.4);
}

.dark-mode .detail-card .card-header {
    background-color: #111827;
    border-bottom-color: #374151;
    color: #f8fafc;
}

.dark-mode .detail-card .card-body {
    background-color: #1f2937;
    color: #f8fafc;
}

.dark-mode .client-info,
.dark-mode .device-info {
    color: #e2e8f0;
}

.dark-mode .main-content {
    background-color: #111827;
}

.dark-mode .form-control,
.dark-mode .form-select,
.dark-mode .input-group-text {
    background-color: #1f2937;
    border-color: #374151;
    color: #f8fafc;
}

.dark-mode .form-control:focus,
.dark-mode .form-select:focus {
    background-color: #2d3748;
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
}

.dark-mode .input-group-text {
    background-color: #2d3748;
    color: #f8fafc;
}

.dark-mode .action-buttons {
    background-color: #111827;
}

.dark-mode .action-title h6 {
    color: #e2e8f0;
}

.dark-mode .action-title .line {
    background-color: #4b5563;
}

.dark-mode .action-card {
    background-color: #1f2937;
    color: #f8fafc;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    border-color: #374151;
}

.dark-mode .action-card:hover {
    background-color: #2d3748;
    box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    color: #60a5fa;
}

.dark-mode .action-text {
    color: #f8fafc;
}

.dark-mode .btn-primary {
    background-color: #3b82f6;
    border-color: #2563eb;
}

.dark-mode .btn-primary:hover {
    background-color: #2563eb;
    border-color: #1d4ed8;
}

.dark-mode .btn-success {
    background-color: #10b981;
    border-color: #059669;
}

.dark-mode .btn-success:hover {
    background-color: #059669;
    border-color: #047857;
}

.dark-mode .btn-info {
    background-color: #06b6d4;
    border-color: #0891b2;
}

.dark-mode .btn-info:hover {
    background-color: #0891b2;
    border-color: #0e7490;
}

.dark-mode .btn-warning {
    background-color: #f59e0b;
    border-color: #d97706;
}

.dark-mode .btn-warning:hover {
    background-color: #d97706;
    border-color: #b45309;
}

.dark-mode .btn-danger {
    background-color: #ef4444;
    border-color: #dc2626;
}

.dark-mode .btn-danger:hover {
    background-color: #dc2626;
    border-color: #b91c1c;
}

.dark-mode .btn-secondary {
    background-color: #6b7280;
    border-color: #4b5563;
}

.dark-mode .btn-secondary:hover {
    background-color: #4b5563;
    border-color: #374151;
}

.dark-mode .badge {
    background-color: #3b82f6;
}

.dark-mode .badge.bg-success {
    background-color: #10b981 !important;
}

.dark-mode .badge.bg-warning {
    background-color: #f59e0b !important;
}

.dark-mode .badge.bg-info {
    background-color: #06b6d4 !important;
}

.dark-mode .badge.bg-danger {
    background-color: #ef4444 !important;
}

.dark-mode .modal-content {
    background-color: #1f2937;
    color: #f8fafc;
    border-color: #374151;
}

.dark-mode .modal-header {
    background-color: #111827 !important;
    border-bottom-color: #374151;
}

.dark-mode .modal-footer {
    background-color: #111827 !important;
    border-top-color: #374151;
}

.dark-mode .modal-body {
    background-color: #1f2937;
}

.dark-mode .card {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode .card-header {
    background-color: #111827 !important;
    border-bottom-color: #374151;
    color: #f8fafc;
}

.dark-mode .card-body {
    background-color: #1f2937;
}

.dark-mode .alert-success {
    background-color: rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.3);
    color: #d1fae5;
}

.dark-mode .alert-info {
    background-color: rgba(6, 182, 212, 0.2);
    border-color: rgba(6, 182, 212, 0.3);
    color: #cffafe;
}

.dark-mode .alert-warning {
    background-color: rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.3);
    color: #fef3c7;
}

.dark-mode .alert-danger {
    background-color: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.3);
    color: #fee2e2;
}

.dark-mode .text-muted {
    color: #9ca3af !important;
}

.dark-mode .form-text {
    color: #9ca3af;
}

.dark-mode .status-btn.active {
    background-color: #3b82f6;
    color: #fff;
    border-color: #2563eb;
}

.dark-mode .btn-outline-primary {
    color: #60a5fa;
    border-color: #60a5fa;
}

.dark-mode .btn-outline-primary:hover {
    background-color: #3b82f6;
    color: #f8fafc;
    border-color: #3b82f6;
}

.dark-mode .btn-outline-secondary {
    color: #9ca3af;
    border-color: #4b5563;
}

.dark-mode .btn-outline-secondary:hover {
    background-color: #4b5563;
    color: #f8fafc;
    border-color: #4b5563;
}

.dark-mode .btn-outline-success {
    color: #34d399;
    border-color: #34d399;
}

.dark-mode .btn-outline-success:hover {
    background-color: #10b981;
    color: #f8fafc;
    border-color: #10b981;
}

.dark-mode .btn-outline-warning {
    color: #fbbf24;
    border-color: #fbbf24;
}

.dark-mode .btn-outline-warning:hover {
    background-color: #f59e0b;
    color: #f8fafc;
    border-color: #f59e0b;
}

.dark-mode .btn-outline-danger {
    color: #f87171;
    border-color: #f87171;
}

.dark-mode .btn-outline-danger:hover {
    background-color: #ef4444;
    color: #f8fafc;
    border-color: #ef4444;
}

/* Styles pour le modal de statut rapide */
.status-tabs .nav-link {
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 500;
    color: #495057;
    background-color: #e9ecef;
    border: none;
    transition: all 0.3s;
    white-space: nowrap;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.status-tabs .nav-link::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(to bottom, rgba(255,255,255,0.3), transparent);
    border-radius: 10px 10px 0 0;
}

.status-tabs .nav-link.active {
    background-color: #0d6efd;
    color: #fff;
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
    transform: translateY(-2px);
}

.status-option {
    background-color: #fff;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 12px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.25s;
    position: relative;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.status-option:hover,
.status-option:focus {
    border-color: #0d6efd;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.status-option.selected {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
    box-shadow: 0 5px 15px rgba(13, 110, 253, 0.15);
}

.status-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.status-description {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 2px;
}

.status-check {
    opacity: 0;
    transition: all 0.3s;
}

.status-option.selected .status-check {
    opacity: 1;
}

/* Mode sombre pour le modal de statut */
.dark-mode .status-tabs .nav-link {
    background-color: #374151;
    color: #e2e8f0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.dark-mode .status-tabs .nav-link::before {
    background: linear-gradient(to bottom, rgba(255,255,255,0.15), transparent);
}

.dark-mode .status-tabs .nav-link.active {
    background-color: #3b82f6;
    box-shadow: 0 4px 8px rgba(59, 130, 246, 0.25);
}

.dark-mode .status-option {
    background-color: #1f2937;
    border-color: #374151;
    color: #f8fafc;
}

.dark-mode .status-option:hover, 
.dark-mode .status-option:focus {
    border-color: #60a5fa;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.dark-mode .status-option.selected {
    border-color: #3b82f6;
    background-color: rgba(59, 130, 246, 0.15);
}

.dark-mode .status-description {
    color: #94a3b8;
}

.dark-mode .status-icon {
    background-color: #111827;
    color: #f8fafc;
}

.icon-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables pour la gestion de la caméra
    let stream = null;
    let cameraModal = null;
    
    // Initialisation des modal
    const photoModal = new bootstrap.Modal(document.getElementById('cameraModal'));
    const commandeModal = new bootstrap.Modal(document.getElementById('ajouterCommandeModal'));
    
    // Feedback tactile pour les boutons d'action
    const actionCards = document.querySelectorAll('.action-card');
    if (actionCards && actionCards.length > 0) {
        actionCards.forEach(card => {
            card.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.97)';
            });
            
            card.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
    }
    
    // Rendre le champ prix compatible avec la saisie numérique
    const prixInput = document.getElementById('prix_reparation');
    if (prixInput) {
        prixInput.addEventListener('input', function(e) {
            // Remplacer les virgules par des points
            let value = this.value.replace(',', '.');
            
            // Filtrer uniquement les nombres et un seul point
            value = value.replace(/[^0-9.]/g, '');
            
            // S'assurer qu'il n'y a qu'un seul point
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            
            // Limiter à deux décimales
            if (parts.length > 1 && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].substring(0, 2);
            }
            
            // Mettre à jour la valeur
            this.value = value.replace('.', ',');
        });
    }
    
    // Bouton pour ajouter une photo
    const btnAjouterPhoto = document.getElementById('btn-ajouter-photo');
    if (btnAjouterPhoto) {
        btnAjouterPhoto.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Initialiser le flux de la caméra
            startCamera();
            
            // Afficher le modal
            photoModal.show();
        });
    }
    
    // Bouton pour commander une pièce
    const btnCommanderPiece = document.getElementById('btn-commander-piece');
    if (btnCommanderPiece) {
        btnCommanderPiece.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Charger les fournisseurs
            loadFournisseurs();
            
            // Afficher le modal
            commandeModal.show();
        });
    }
    
    // Fonction pour démarrer la caméra
    function startCamera() {
        const videoElement = document.getElementById('camera');
        const captureBtn = document.getElementById('captureBtn');
        const savePhotoBtn = document.getElementById('savePhotoBtn');
        const cameraContainer = document.getElementById('cameraContainer');
        const photoPreview = document.getElementById('photoPreview');
        const previewImage = document.getElementById('previewImage');
        const retakeBtn = document.getElementById('retakePhoto');
        
        if (!videoElement || !captureBtn) return;
        
        // Contraintes pour la caméra
        const constraints = {
            video: {
                facingMode: 'environment',
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false
        };
        
        // Arrêter tout flux actif
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        
        // Accéder à la caméra
        navigator.mediaDevices.getUserMedia(constraints)
            .then(function(mediaStream) {
                stream = mediaStream;
                videoElement.srcObject = mediaStream;
                
                // Afficher la vidéo et masquer la prévisualisation
                cameraContainer.classList.remove('d-none');
                photoPreview.classList.add('d-none');
                captureBtn.classList.remove('d-none');
                savePhotoBtn.classList.add('d-none');
                
                // Gestionnaire pour capturer l'image
                captureBtn.addEventListener('click', function() {
                    const canvas = document.getElementById('cameraCanvas');
                    const context = canvas.getContext('2d');
                    
                    // Définir les dimensions du canvas
                    canvas.width = videoElement.videoWidth;
                    canvas.height = videoElement.videoHeight;
                    
                    // Dessiner l'image
                    context.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
                    
                    // Convertir en URL de données
                    const dataUrl = canvas.toDataURL('image/jpeg');
                    
                    // Afficher la prévisualisation
                    previewImage.src = dataUrl;
                    cameraContainer.classList.add('d-none');
                    photoPreview.classList.remove('d-none');
                    captureBtn.classList.add('d-none');
                    savePhotoBtn.classList.remove('d-none');
                });
                
                // Gestionnaire pour reprendre une photo
                retakeBtn.addEventListener('click', function() {
                    cameraContainer.classList.remove('d-none');
                    photoPreview.classList.add('d-none');
                    captureBtn.classList.remove('d-none');
                    savePhotoBtn.classList.add('d-none');
                });
                
                // Gestionnaire pour sauvegarder la photo
                savePhotoBtn.addEventListener('click', async function() {
                    const form = document.getElementById('cameraForm');
                    if (!form) return;
                    
                    const formData = new FormData(form);
                    
                    try {
                        this.disabled = true;
                        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sauvegarde en cours...';
                        
                        // Convertir la photo du canvas en blob
                        const canvas = document.getElementById('cameraCanvas');
                        if (!canvas) {
                            throw new Error('Canvas non trouvé');
                        }
                        
                        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg'));
                        formData.set('photo', blob, 'photo.jpg');
                        
                        // Envoyer la photo
                        const response = await fetch('ajax/upload_photo.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        if (!response.ok) {
                            throw new Error(`Erreur HTTP: ${response.status}`);
                        }
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Afficher un message de succès
                            alert('Photo ajoutée avec succès !');
                            
                            // Fermer le modal et réinitialiser
                            photoModal.hide();
                            form.reset();
                            
                            // Recharger la page pour afficher la nouvelle photo
                            window.location.reload();
                        } else {
                            throw new Error(data.error || 'Erreur lors de l\'upload de la photo');
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        alert(`Erreur lors de la sauvegarde de la photo: ${error.message}`);
                    } finally {
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-save me-2"></i> Sauvegarder';
                    }
                });
                
                // Arrêter la caméra à la fermeture du modal
                document.getElementById('cameraModal').addEventListener('hidden.bs.modal', function() {
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }
                });
            })
            .catch(function(err) {
                console.error("Erreur lors de l'accès à la caméra: ", err);
                alert("Impossible d'accéder à la caméra. Vérifiez que vous avez accordé les permissions nécessaires.");
            });
    }
    
    // Fonction pour charger les fournisseurs dans le modal de commande
    function loadFournisseurs() {
        const select = document.getElementById('fournisseur_id');
        if (!select) return;
        
        // Nettoyer les options existantes sauf la première
        while (select.options.length > 1) {
            select.remove(1);
        }
        
        // Charger les fournisseurs depuis l'API
        fetch('ajax/get_fournisseurs.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.fournisseurs.forEach(fournisseur => {
                        const option = document.createElement('option');
                        option.value = fournisseur.id;
                        option.textContent = fournisseur.nom;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des fournisseurs:', error);
            });
    }
    
    // Gestionnaire pour les boutons de statut dans le modal de commande
    document.querySelectorAll('.status-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Supprimer la classe active de tous les boutons
            document.querySelectorAll('.status-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Mettre à jour le champ caché
            document.getElementById('selectedStatus').value = this.getAttribute('data-status');
        });
    });
    
    // Fonctions pour incrémenter/décrémenter la quantité
    window.incrementQuantity = function() {
        const input = document.querySelector('input[name="quantite"]');
        if (input) {
            input.value = parseInt(input.value, 10) + 1;
        }
    };
    
    window.decrementQuantity = function() {
        const input = document.querySelector('input[name="quantite"]');
        if (input && parseInt(input.value, 10) > 1) {
            input.value = parseInt(input.value, 10) - 1;
        }
    };
    
    // Fonction pour sauvegarder la commande
    window.saveCommande = function() {
        const form = document.getElementById('commandeForm');
        if (!form) return;
        
        const formData = new FormData(form);
        
        // Validation des champs
        const fournisseurId = formData.get('fournisseur_id');
        const nomPiece = formData.get('nom_piece');
        const prixEstime = formData.get('prix_estime');
        
        if (!fournisseurId) {
            alert('Veuillez sélectionner un fournisseur');
            return;
        }
        
        if (!nomPiece) {
            alert('Veuillez saisir le nom de la pièce');
            return;
        }
        
        if (!prixEstime) {
            alert('Veuillez saisir un prix estimé');
            return;
        }
        
        // Envoyer la commande
        fetch('ajax/ajouter_commande.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Commande enregistrée avec succès !');
                commandeModal.hide();
                // Recharger la page
                window.location.reload();
            } else {
                alert(data.message || 'Erreur lors de l\'enregistrement de la commande');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'enregistrement de la commande');
        });
    };

    // Modal statut rapide
    const btnStatutRapide = document.getElementById('btn-statut-rapide');
    const statutRapideModal = new bootstrap.Modal(document.getElementById('statutRapideModal'));
    
    // Variables pour le statut sélectionné
    let selectedStatus = null;
    let selectedStatusName = '';
    let selectedCategory = null;
    
    // Gestionnaire pour ouvrir le modal de statut
    if (btnStatutRapide) {
        btnStatutRapide.addEventListener('click', function() {
            // Réinitialiser les statuts sélectionnés
            resetSelectedStatus();
            
            // Ouvrir le modal
            statutRapideModal.show();
        });
    }
    
    // Fonction pour sélectionner un statut
    window.selectStatut = function(element, statusName, categoryId) {
        // Réinitialiser tous les statuts
        resetSelectedStatus();
        
        // Marquer l'élément comme sélectionné
        element.classList.add('selected');
        
        // Stocker les valeurs du statut sélectionné
        selectedStatus = element.getAttribute('data-status-id');
        selectedStatusName = statusName;
        selectedCategory = categoryId;
        
        // Mettre à jour les champs cachés dans tous les formulaires
        updateHiddenFields(selectedStatus, selectedStatusName, selectedCategory);
    };
    
    // Fonction pour réinitialiser les statuts sélectionnés
    function resetSelectedStatus() {
        const options = document.querySelectorAll('.status-option');
        options.forEach(option => {
            option.classList.remove('selected');
        });
        
        selectedStatus = null;
        selectedStatusName = '';
        selectedCategory = null;
    }
    
    // Fonction pour mettre à jour les champs cachés
    function updateHiddenFields(statusId, statusName, categoryId) {
        // Mettre à jour les champs appropriés selon l'onglet actif
        document.getElementById('nouveau_statut_reception').value = statusName;
        document.getElementById('statut_id_reception').value = statusId;
        
        document.getElementById('nouveau_statut_diagnostic').value = statusName;
        document.getElementById('statut_id_diagnostic').value = statusId;
        
        document.getElementById('nouveau_statut_encours').value = statusName;
        document.getElementById('statut_id_encours').value = statusId;
        
        document.getElementById('nouveau_statut_finalisation').value = statusName;
        document.getElementById('statut_id_finalisation').value = statusId;
    }
    
    // Gestionnaire pour le bouton Appliquer
    const btnAppliquerStatut = document.getElementById('btn-appliquer-statut');
    if (btnAppliquerStatut) {
        btnAppliquerStatut.addEventListener('click', function() {
            if (!selectedStatus) {
                alert('Veuillez sélectionner un statut');
                return;
            }
            
            // Déterminer quel formulaire soumettre en fonction de la catégorie
            let formId = 'form-statut-reception';
            if (selectedCategory === 2) {
                formId = 'form-statut-diagnostic';
            } else if (selectedCategory === 3) {
                formId = 'form-statut-encours';
            } else if (selectedCategory === 4) {
                formId = 'form-statut-finalisation';
            }
            
            // Obtenir le formulaire correct
            const form = document.getElementById(formId);
            
            // Mettre à jour le statut dans le sélecteur de formulaire principal
            const statutSelect = document.getElementById('statut');
            if (statutSelect) {
                // Vérifier si l'option existe déjà
                let optionExists = false;
                for (let i = 0; i < statutSelect.options.length; i++) {
                    if (statutSelect.options[i].value === selectedStatusName) {
                        statutSelect.options[i].selected = true;
                        optionExists = true;
                        break;
                    }
                }
                
                // Si l'option n'existe pas, la créer
                if (!optionExists) {
                    const newOption = document.createElement('option');
                    newOption.value = selectedStatusName;
                    newOption.textContent = selectedStatusName;
                    newOption.selected = true;
                    statutSelect.appendChild(newOption);
                }
            }
            
            // Fermer le modal
            statutRapideModal.hide();
            
            // Mettre à jour le badge de statut
            updateStatusBadge(selectedStatusName);
            
            // Afficher un message de confirmation
            const message = document.createElement('div');
            message.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            message.style.zIndex = '9999';
            message.innerHTML = `
                <strong>Statut mis à jour !</strong> Le statut a été changé en "${selectedStatusName}".
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(message);
            
            // Supprimer le message après 3 secondes
            setTimeout(() => {
                message.remove();
            }, 3000);
        });
    }
    
    // Fonction pour mettre à jour le badge de statut
    function updateStatusBadge(statusName) {
        const badge = document.querySelector('.status-badge .badge');
        if (badge) {
            // Déterminer la classe de couleur appropriée
            let statusClass = 'primary';
            if (statusName.toLowerCase().includes('termin') || statusName.toLowerCase().includes('restitué')) {
                statusClass = 'success';
            } else if (statusName.toLowerCase().includes('attente')) {
                statusClass = 'warning';
            } else if (statusName.toLowerCase().includes('cours') || statusName.toLowerCase().includes('diagnostic')) {
                statusClass = 'info';
            } else if (statusName.toLowerCase().includes('annul')) {
                statusClass = 'danger';
            }
            
            // Mettre à jour la classe et le texte du badge
            badge.className = `badge bg-${statusClass}`;
            badge.textContent = statusName;
        }
    }

    // Gestion du bouton de partage du lien de suivi
    const btnPartagerSuivi = document.getElementById('btn-partager-suivi');
    const modalSuiviClient = new bootstrap.Modal(document.getElementById('modalSuiviClient'));
    const btnCopierLien = document.getElementById('btnCopierLien');
    const lienSuivi = document.getElementById('lienSuivi');
    const alertCopieLien = document.getElementById('alertCopieLien');
    const btnEnvoyerSMS = document.getElementById('btnEnvoyerSMS');
    const texteSMS = document.getElementById('texteSMS');
    
    if (btnPartagerSuivi) {
        btnPartagerSuivi.addEventListener('click', function(e) {
            e.preventDefault();
            modalSuiviClient.show();
        });
    }
    
    if (btnCopierLien) {
        btnCopierLien.addEventListener('click', function() {
            lienSuivi.select();
            document.execCommand('copy');
            
            alertCopieLien.classList.remove('d-none');
            setTimeout(() => {
                alertCopieLien.classList.add('d-none');
            }, 3000);
        });
    }
    
    if (btnEnvoyerSMS) {
        btnEnvoyerSMS.addEventListener('click', function() {
            // Utiliser la fonction existante pour envoyer un SMS
            const clientId = '<?php echo htmlspecialchars($reparation['client_id']); ?>';
            const clientNom = '<?php echo htmlspecialchars($reparation['client_nom']); ?>';
            const clientPrenom = '<?php echo htmlspecialchars($reparation['client_prenom']); ?>';
            const clientTel = '<?php echo htmlspecialchars($reparation['client_telephone']); ?>';
            
            // Fermer la modale de suivi
            modalSuiviClient.hide();
            
            // Ouvrir la modale d'envoi de SMS avec le texte pré-rempli
            openSmsModal(clientId, clientNom, clientPrenom, clientTel, texteSMS.value);
        });
    }
    
    // Gestion du bouton d'envoi de lien d'acceptation de devis
    const btnEnvoyerDevis = document.getElementById('btn-envoyer-devis');
    const modalConfirmDevis = new bootstrap.Modal(document.getElementById('modalConfirmDevis'));
    
    if (btnEnvoyerDevis) {
        btnEnvoyerDevis.addEventListener('click', function(e) {
            e.preventDefault();
            modalConfirmDevis.show();
        });
    }
    
    // Gestion de l'envoi du lien d'acceptation de devis
    const btnConfirmEnvoyerDevis = document.getElementById('btnConfirmEnvoyerDevis');
    
    if (btnConfirmEnvoyerDevis) {
        btnConfirmEnvoyerDevis.addEventListener('click', function() {
            // Afficher le spinner
            const spinner = this.querySelector('.spinner-border');
            const textElement = this.querySelector('.btn-text');
            spinner.classList.remove('d-none');
            textElement.textContent = 'Envoi en cours...';
            this.disabled = true;
            
            // Vérifier si l'envoi par SMS est activé
            const smsActivated = document.getElementById('smsToggle').checked;
            const texteSMS = smsActivated ? document.getElementById('texteSMSDevis').value : '';
            
            // Envoi de la demande AJAX
            fetch('../ajax/envoyer_lien_devis.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    repair_id: <?php echo $reparation_id; ?>,
                    send_sms: smsActivated,
                    sms_text: texteSMS
                }),
            })
            .then(response => response.json())
            .then(data => {
                // Fermer la modale
                modalConfirmDevis.hide();
                
                // Réinitialiser le bouton
                spinner.classList.add('d-none');
                textElement.textContent = 'Envoyer';
                this.disabled = false;
                
                // Afficher le message de succès ou d'erreur
                if (data.success) {
                    // Afficher un toast de succès
                    const toastEl = document.getElementById('toastSuccess');
                    const toastMessage = document.getElementById('toastSuccessMessage');
                    toastMessage.textContent = data.message;
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                } else {
                    // Afficher un toast d'erreur
                    const toastEl = document.getElementById('toastError');
                    const toastMessage = document.getElementById('toastErrorMessage');
                    toastMessage.textContent = data.error;
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                }
            })
            .catch(error => {
                // Réinitialiser le bouton
                spinner.classList.add('d-none');
                textElement.textContent = 'Envoyer';
                this.disabled = false;
                
                // Fermer la modale
                modalConfirmDevis.hide();
                
                // Afficher un toast d'erreur
                const toastEl = document.getElementById('toastError');
                const toastMessage = document.getElementById('toastErrorMessage');
                toastMessage.textContent = 'Erreur lors de l\'envoi du lien: ' + error.message;
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            });
        });
    }
    
    // Gestion du toggle SMS pour l'envoi du devis
    const smsToggle = document.getElementById('smsToggle');
    const smsContent = document.getElementById('smsContent');
    
    if (smsToggle && smsContent) {
        smsToggle.addEventListener('change', function() {
            if (this.checked) {
                smsContent.classList.remove('d-none');
            } else {
                smsContent.classList.add('d-none');
            }
        });
    }
});
</script> 