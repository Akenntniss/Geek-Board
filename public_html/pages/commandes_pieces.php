<?php
// Démarrer la session avant la vérification, seulement si aucune session n'est active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Fonction pour formater le statut
function get_status_class($statut) {
    switch($statut) {
        case 'en_attente': return 'bg-warning text-dark';
        case 'commande': return 'bg-info text-white';
        case 'recue': return 'bg-success text-white';
        case 'annulee': return 'bg-danger text-white';
        case 'urgent': return 'bg-danger text-white';
        case 'utilise': return 'bg-primary text-white';
        case 'a_retourner': return 'bg-secondary text-white';
        default: return 'bg-secondary text-white';
    }
}

// Fonction pour obtenir la couleur du fournisseur
function getSupplierColor($fournisseur_id) {
    // Palette de 10 couleurs distinctes
    $colors = [
        '#4e73df', // Bleu royal
        '#36b9cc', // Cyan
        '#1cc88a', // Vert
        '#f6c23e', // Jaune
        '#e74a3b', // Rouge
        '#8a6d3b', // Brun
        '#6610f2', // Violet foncé
        '#20c997', // Turquoise
        '#fd7e14', // Orange
        '#6f42c1'  // Violet
    ];
    
    // Utiliser le modulo pour obtenir un index entre 0 et 9
    $index = $fournisseur_id % 10;
    
    return $colors[$index];
}

// Fonction pour obtenir la couleur de la date
function getDateColor($day_of_week) {
    // Palette de couleurs pour les jours de la semaine
    $colors = [
        1 => '#cfe2ff', // Lundi - Bleu clair
        2 => '#d1e7dd', // Mardi - Vert clair
        3 => '#f8d7da', // Mercredi - Rose clair
        4 => '#fff3cd', // Jeudi - Jaune clair
        5 => '#e7f5ff', // Vendredi - Bleu très clair
        6 => '#e2e3e5', // Samedi - Gris clair
        7 => '#e0cffc'  // Dimanche - Violet clair
    ];
    
    return $colors[$day_of_week] ?? '#f8f9fa'; // Couleur par défaut si jour invalide
}

// Fonction pour obtenir le libellé du statut
function get_status_label($statut) {
    switch($statut) {
        case 'en_attente': return 'En attente';
        case 'commande': return 'Commandé';
        case 'recue': return 'Reçu';
        case 'annulee': return 'Annulé';
        case 'urgent': return 'URGENT';
        case 'utilise': return 'Utilisé';
        case 'a_retourner': return 'Retour';
        default: return $statut;
    }
}

// Récupérer les commandes de pièces avec les informations associées
try {
    $stmt = $pdo->query("
        SELECT c.*, f.nom as fournisseur_nom, cl.nom as client_nom, cl.prenom as client_prenom, cl.telephone,
         r.type_appareil, r.marque, r.modele
         FROM commandes_pieces c 
         LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id 
         LEFT JOIN clients cl ON c.client_id = cl.id 
         LEFT JOIN reparations r ON c.reparation_id = r.id 
         ORDER BY c.date_creation DESC
    ");
    $commandes = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des commandes: " . $e->getMessage() . "</div>";
    $commandes = [];
}

// Fonction pour formater l'urgence
function formatUrgence($urgence) {
    $classes = [
        'normal' => 'secondary',
        'urgent' => 'warning',
        'tres_urgent' => 'danger'
    ];
    
    $labels = [
        'normal' => 'Normal',
        'urgent' => 'Urgent',
        'tres_urgent' => 'Très urgent'
    ];
    
    return sprintf(
        '<span class="badge bg-%s">%s</span>',
        $classes[$urgence],
        $labels[$urgence]
    );
}
?>

<!-- Inclure le header -->
<?php include_once 'includes/header.php'; ?>

<!-- Contenu principal de la page -->
<div class="container-fluid py-4">
    <style>
        /* Style pour le bouton Google */
        .btn-google {
            background-color: white;
            color: #4285F4;
            border: 1px solid #4285F4;
            transition: all 0.2s ease;
        }
        .btn-google:hover {
            background-color: #4285F4;
            color: white;
            box-shadow: 0 2px 5px rgba(66, 133, 244, 0.3);
        }
        /* Animation pour indiquer que la recherche a été lancée */
        .btn-google.clicked {
            animation: pulse-google 0.5s;
        }
        @keyframes pulse-google {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Styles pour le mode nuit */
        .dark-mode .btn-google {
            background-color: #1a1a1a;
            color: #4285F4;
            border: 1px solid #4285F4;
        }
        .dark-mode .btn-google:hover {
            background-color: #4285F4;
            color: white;
            box-shadow: 0 2px 5px rgba(66, 133, 244, 0.5);
        }
    </style>
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">Gestion des commandes de pièces</h1>
            
<!-- Modal pour changer le statut d'une commande -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true" data-commande-id="">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- En-tête du modal avec effet de dégradé -->
            <div class="modal-header position-relative py-3" style="background: linear-gradient(135deg, rgba(13, 110, 253, 0.1), rgba(13, 110, 253, 0.05));">
                <div class="position-absolute start-0 top-0 w-100 d-flex justify-content-center">
                    <div class="status-progress-bar"></div>
                </div>
                <h5 class="modal-title d-flex align-items-center" id="changeStatusModalLabel">
                    <i class="fas fa-exchange-alt me-2 text-primary"></i>
                    Changement de statut
                </h5>
                <div class="position-absolute end-0 top-0 p-3">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-0">
                <!-- En-tête de contexte -->
                <div class="bg-light p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 text-muted">Commande <span id="commandeIdText" class="text-dark fw-bold"></span></h6>
                        </div>
                        <div class="status-context" id="statusContext">
                            <!-- Le statut actuel sera affiché ici par JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Grille des options de statut -->
                <div class="p-4">
                    <p class="text-muted mb-4">Sélectionnez le nouveau statut pour cette commande:</p>
                    
                    <div class="row g-3 status-options-grid">
                        <!-- Option En attente -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="en_attente">
                                <div class="status-icon-wrapper bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">En attente</span>
                                    <small class="text-muted">Pas encore commandé</small>
                                </div>
                            </button>
                        </div>

                        <!-- Option Commandé -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="commande">
                                <div class="status-icon-wrapper bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">Commandé</span>
                                    <small class="text-muted">Commande en cours</small>
                                </div>
                            </button>
                        </div>

                        <!-- Option Reçu -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="recue">
                                <div class="status-icon-wrapper bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">Reçu</span>
                                    <small class="text-muted">Pièce réceptionnée</small>
                                </div>
                            </button>
                        </div>

                        <!-- Option Utilisé -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="utilise">
                                <div class="status-icon-wrapper bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">Utilisé</span>
                                    <small class="text-muted">Pièce installée</small>
                                </div>
                            </button>
                        </div>

                        <!-- Option À retourner -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="a_retourner">
                                <div class="status-icon-wrapper bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-undo"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">À retourner</span>
                                    <small class="text-muted">Retour fournisseur</small>
                                </div>
                            </button>
                        </div>

                        <!-- Option Annulé -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 d-flex align-items-center p-3" data-status="annulee">
                                <div class="status-icon-wrapper bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-times"></i>
                                </div>
                                <div class="status-text-content">
                                    <span class="d-block fw-medium">Annulé</span>
                                    <small class="text-muted">Commande annulée</small>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Inputs cachés pour stocker les valeurs -->
                <input type="hidden" id="commandeIdInput" value="">
                <input type="hidden" id="currentStatusInput" value="">
            </div>
            <div class="modal-footer border-top-0 d-flex justify-content-end py-3">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>
            
            <!-- Filtres améliorés -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-4 col-sm-6">
                            <div class="d-flex flex-column">
                                <label class="form-label small text-muted mb-1">Filtrer par statut</label>
                                <div class="btn-group w-100" role="group" id="status-filter-group">
                                    <button type="button" class="btn btn-outline-secondary status-filter active" data-status="all">
                                        <i class="fas fa-list me-1"></i> Tous
                                    </button>
                                    <button type="button" class="btn btn-outline-warning status-filter" data-status="en_attente">
                                        <i class="fas fa-clock me-1"></i> En attente
                                    </button>
                                    <button type="button" class="btn btn-outline-info status-filter" data-status="commande">
                                        <i class="fas fa-truck me-1"></i> Commandé
                                    </button>
                                    <button type="button" class="btn btn-outline-success status-filter" data-status="recue">
                                        <i class="fas fa-check me-1"></i> Reçu
                                    </button>
                        <button type="button" class="btn btn-outline-primary status-filter" data-status="utilise">
                            <i class="fas fa-check-double me-1"></i> Utilisé
                    </button>
                        <button type="button" class="btn btn-outline-secondary status-filter" data-status="a_retourner">
                            <i class="fas fa-undo me-1"></i> Retour
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="d-flex flex-column">
                                <label class="form-label small text-muted mb-1">Filtrer par fournisseur</label>
                                <button id="fournisseurBouton" class="btn btn-outline-secondary dropdown-toggle w-100" data-bs-toggle="modal" data-bs-target="#fournisseursModal">
                                    <i class="fas fa-filter"></i> Choisir un fournisseur
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12">
                            <div class="d-flex flex-column">
                                <label class="form-label small text-muted mb-1">Filtrer par période</label>
                                <button id="periodeButton" class="btn btn-outline-secondary dropdown-toggle w-100" data-bs-toggle="modal" data-bs-target="#periodesModal">
                                    <i class="fas fa-calendar-alt"></i> Toutes les périodes
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group position-relative">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 shadow-sm rounded-start-3">
                                        <i class="fas fa-search text-primary"></i>
                                    </span>
                                    <input type="text" id="searchCommandes" class="form-control form-control-lg bg-light border-0 shadow-sm py-2" placeholder="Rechercher une commande, un client, une pièce...">
                                    <button type="button" id="clearSearch" class="btn btn-light border-0 shadow-sm rounded-end-3 d-none">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tableau des commandes -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><span id="commandesCount" class="badge bg-primary rounded-pill me-2"></span>Liste des commandes</h5>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success d-flex align-items-center" id="export-pdf-btn">
                                <i class="fas fa-file-pdf me-2"></i>
                                Exporter PDF
                            </button>
                            <button type="button" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#ajouterCommandeModal">
                                <i class="fas fa-plus-circle me-2"></i>
                                Nouvelle commande
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="commandes-table-container">
                        <div id="commandesLoader" class="text-center py-5 d-none">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <p class="text-muted">Chargement des commandes...</p>
                        </div>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Pièce</th>
                                    <th>Statut</th>
                                    <th>Quantité</th>
                                    <th>Prix</th>
                                    <th>Date</th>
                                    <th>Fournisseur</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                                <tbody id="commandesTableBody">
                                <?php if (empty($commandes)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Aucune commande de pièces trouvée
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($commandes as $commande): ?>
                                        <tr data-fournisseur-id="<?= $commande['fournisseur_id'] ?>" data-statut="<?= $commande['statut'] ?>" data-date="<?= date('Y-m-d', strtotime($commande['date_creation'])) ?>" data-search="<?= strtolower(htmlspecialchars($commande['client_nom'] . ' ' . $commande['client_prenom'] . ' ' . $commande['nom_piece'] . ' ' . $commande['fournisseur_nom'])) ?>">
                                        <td>#<?= $commande['id'] ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-2">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                        <a href="#" class="text-decoration-none fw-medium client-info-link" 
                                                           onclick="showClientInfo(<?= $commande['client_id'] ?>, '<?= htmlspecialchars($commande['client_nom']) ?>', '<?= htmlspecialchars($commande['client_prenom']) ?>', '<?= htmlspecialchars($commande['telephone']) ?>')">
                                                    <?= htmlspecialchars($commande['client_nom'] . ' ' . $commande['client_prenom']) ?>
                                                        </a>
                                                    <?php if ($commande['reparation_id']): ?>
                                                        <div class="small text-muted">
                                                                <a href="index.php?page=reparation&id=<?= $commande['reparation_id'] ?>" class="text-decoration-none">
                                                            #<?= $commande['reparation_id'] ?> - 
                                                            <?= htmlspecialchars($commande['type_appareil'] . ' ' . $commande['marque'] . ' ' . $commande['modele']) ?>
                                                                </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="editable-field" data-field="nom_piece" data-id="<?= $commande['id'] ?>" data-bs-toggle="modal" data-bs-target="#editFieldModal">
                                            <?= htmlspecialchars($commande['nom_piece']) ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= get_status_class($commande['statut']) ?> status-badge" 
                                                  data-id="<?= $commande['id'] ?>" 
                                                  data-status="<?= $commande['statut'] ?>" 
                                                  data-bs-toggle="modal" 
                                                  data-bs-target="#changeStatusModal" 
                                                  style="cursor: pointer;">
                                                <?= get_status_label($commande['statut']) ?>
                                            </span>
                                        </td>
                                        <td><?= $commande['quantite'] ?></td>
                                        <td class="editable-field" data-field="prix_estime" data-id="<?= $commande['id'] ?>" data-bs-toggle="modal" data-bs-target="#editFieldModal">
                                            <?= number_format($commande['prix_estime'], 2, ',', ' ') ?> €
                                        </td>
                                        <td>
                                            <span class="badge date-badge" style="background-color: <?= getDateColor(date('N', strtotime($commande['date_creation']))) ?>; color: #333; font-weight: bold; padding: 6px 10px; border-radius: 6px; display: inline-block; width: 100%; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                                <?= date('d/m/Y', strtotime($commande['date_creation'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge editable-field" data-field="fournisseur_id" data-id="<?= $commande['id'] ?>" data-current-value="<?= $commande['fournisseur_id'] ?>" data-bs-toggle="modal" data-bs-target="#editFournisseurModal" style="background-color: <?= getSupplierColor($commande['fournisseur_id']) ?>; color: white; font-weight: bold; padding: 6px 10px; border-radius: 6px; display: inline-block; width: 100%; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.5px; cursor: pointer;">
                                                <?= htmlspecialchars($commande['fournisseur_nom']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-outline-primary btn-sm" onclick="editCommande(<?= $commande['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" onclick="deleteCommande(<?= $commande['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <a href="https://www.google.com/search?q=<?= urlencode(htmlspecialchars($commande['nom_piece'] . ' ' . ($commande['description'] ? $commande['description'] . ' ' : '') . $commande['fournisseur_nom'])) ?>" target="_blank" class="btn btn-google btn-sm" title="Rechercher '<?= htmlspecialchars($commande['nom_piece']) ?>' sur Google">
                                                    <i class="fab fa-google"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <span id="visibleRowsCount">0</span> commandes affichées sur <span id="totalRowsCount">0</span>
                        </div>
                        <button id="resetFilters" class="btn btn-outline-secondary btn-sm d-none">
                            <i class="fas fa-undo me-1"></i> Réinitialiser les filtres
                        </button>
        </div>
    </div>
</div>

<!-- Inclure le footer qui contient déjà le modal "Nouvelle commande de pièces" -->
<?php include_once 'includes/footer.php'; ?>

<!-- Bibliothèque jsPDF pour l'exportation PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<!-- Style spécifique pour le modal de statut -->
<style>
/* Style pour les cartes de statut */
.status-option-card {
    background-color: rgba(255, 255, 255, 0.5);
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    cursor: pointer;
    height: 100%;
}

.status-option-card:hover {
    background-color: rgba(13, 110, 253, 0.05);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
    transform: translateY(-2px);
}

.status-option-card:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.1);
}

.status-icon-wrapper {
    width: 40px;
    height: 40px;
    min-width: 40px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.status-option-card:hover .status-icon-wrapper {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Barre de progression en haut du modal */
.status-progress-bar {
    height: 3px;
    width: 95%;
    background: linear-gradient(90deg, #0d6efd, #83c5fd);
    border-radius: 0 0 4px 4px;
    opacity: 0.8;
}

/* Animation des cartes */
.status-options-grid .col-md-6 {
    transition: opacity 0.3s ease, transform 0.3s ease;
    opacity: 0;
    transform: translateY(10px);
}

/* Animation séquentielle des cartes */
.status-options-grid.animated .col-md-6 {
    opacity: 1;
    transform: translateY(0);
}

.status-options-grid.animated .col-md-6:nth-child(1) { transition-delay: 0.05s; }
.status-options-grid.animated .col-md-6:nth-child(2) { transition-delay: 0.1s; }
.status-options-grid.animated .col-md-6:nth-child(3) { transition-delay: 0.15s; }
.status-options-grid.animated .col-md-6:nth-child(4) { transition-delay: 0.2s; }
.status-options-grid.animated .col-md-6:nth-child(5) { transition-delay: 0.25s; }
.status-options-grid.animated .col-md-6:nth-child(6) { transition-delay: 0.3s; }

/* Style pour le statut actuel */
.current-status-badge {
    padding: 0.35rem 0.65rem;
    border-radius: 0.5rem;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.current-status-badge i {
    margin-right: 0.35rem;
    font-size: 0.75rem;
}

/* Styles adaptés pour le mode sombre */
.dark-mode .status-option-card {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .status-option-card:hover {
    background-color: rgba(13, 110, 253, 0.1);
}

/* Animation de chargement dans le bouton */
.status-option-card.loading {
    pointer-events: none;
}

.status-option-card.loading .status-icon-wrapper {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}

/* Support des médias pour mobile */
@media (max-width: 767.98px) {
    .status-icon-wrapper {
    width: 36px;
    height: 36px;
        min-width: 36px;
    }
    
    .status-options-grid .col-md-6 {
        padding-left: 8px;
        padding-right: 8px;
    }
    
    .status-option-card {
        padding: 10px !important;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
}
</style>

<!-- Scripts spécifiques à la page -->
<script src="assets/js/commandes.js"></script>
<script src="assets/js/export-pdf.js"></script>

<!-- Script pour le changement de statut -->
<script>
// Fonction pour mettre à jour le statut d'une commande
function updateCommandeStatus(commandeId, newStatus) {
    // Vérifications préliminaires
    if (!commandeId) {
        console.error("Erreur: ID de commande manquant");
        showNotification('Identifiant de commande manquant', 'danger');
        return;
    }
    
    console.log("Mise à jour du statut:", { commande_id: commandeId, new_status: newStatus });
    
    // Ajouter une classe de chargement au bouton cliqué
    const clickedButton = document.querySelector(`.status-option-card[data-status="${newStatus}"]`);
    if (clickedButton) {
        clickedButton.classList.add('loading');
    }
    
    // Envoyer la requête au serveur
    fetch('ajax/update_commande_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ commande_id: commandeId, new_status: newStatus }),
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch(e) {
                console.error("Erreur de parsing JSON:", e, "Texte reçu:", text);
                throw new Error("Format de réponse invalide");
            }
        });
    })
    .then(data => {
        if (data && data.success) {
            // Mettre à jour l'interface utilisateur
            updateUIAfterStatusChange(commandeId, newStatus);
            showNotification('Statut mis à jour avec succès', 'success');
            
            // Fermer le modal après un court délai
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('changeStatusModal'));
                if (modal) modal.hide();
            }, 300);
        } else {
            showNotification(data.message || 'Erreur lors de la mise à jour', 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur de communication avec le serveur', 'danger');
    })
    .finally(() => {
        // Retirer la classe de chargement
        if (clickedButton) {
            clickedButton.classList.remove('loading');
        }
    });
}

// Mise à jour de l'interface après changement de statut
function updateUIAfterStatusChange(commandeId, newStatus) {
    // Mettre à jour le badge de statut dans la liste
    const badge = document.querySelector(`.status-badge[data-id="${commandeId}"]`);
    if (badge) {
        badge.setAttribute('data-status', newStatus);
        badge.className = `badge ${getStatusClassJS(newStatus)} status-badge`;
        badge.textContent = getStatusLabelJS(newStatus);
    }
    
    // Mettre à jour l'attribut data-statut de la ligne de table
    const row = document.querySelector(`tr td .status-badge[data-id="${commandeId}"]`).closest('tr');
    if (row) {
        row.setAttribute('data-statut', newStatus);
    }
    
    // Appliquer les filtres pour mettre à jour l'affichage
    if (typeof filterCommandes === 'function' && window.currentStatusFilter) {
        filterCommandes(window.currentStatusFilter);
    }
}

// Fonction pour obtenir la classe CSS pour un statut
function getStatusClassJS(statut) {
    switch(statut) {
        case 'en_attente': return 'bg-warning text-dark';
        case 'commande': return 'bg-info text-white';
        case 'recue': return 'bg-success text-white';
        case 'annulee': return 'bg-danger text-white';
        case 'urgent': return 'bg-danger text-white';
        case 'utilise': return 'bg-primary text-white';
        case 'a_retourner': return 'bg-secondary text-white';
        default: return 'bg-secondary text-white';
    }
}

// Fonction pour obtenir le libellé d'un statut
function getStatusLabelJS(statut) {
    switch(statut) {
        case 'en_attente': return 'En attente';
        case 'commande': return 'Commandé';
        case 'recue': return 'Reçu';
        case 'annulee': return 'Annulé';
        case 'urgent': return 'URGENT';
        case 'utilise': return 'Utilisé';
        case 'a_retourner': return 'À retourner';
        default: return statut;
    }
}

// Fonction pour obtenir l'icône d'un statut
function getStatusIconJS(statut) {
    switch(statut) {
        case 'en_attente': return 'clock';
        case 'commande': return 'shopping-cart';
        case 'recue': return 'box';
        case 'annulee': return 'times';
        case 'urgent': return 'exclamation-triangle';
        case 'utilise': return 'check-double';
        case 'a_retourner': return 'undo';
        default: return 'question-circle';
    }
}
    
// Fonction pour afficher une notification
function showNotification(message, type = 'info') {
    // Créer la notification
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
// Ajouter au container de notifications
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(toast);
    
// Initialiser et afficher le toast
    const bsToast = new bootstrap.Toast(toast, {
        autohide: true,
        delay: 3000
    });
    
    bsToast.show();
    
// Vibration pour feedback tactile
    if ('vibrate' in navigator) {
        navigator.vibrate([30, 20, 50]);
    }
}

// Initialisation des événements une fois le DOM chargé
document.addEventListener('DOMContentLoaded', function() {
    const statusModal = document.getElementById('changeStatusModal');
    
    if (statusModal) {
        // Gérer l'ouverture du modal
        statusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const commandeId = button.getAttribute('data-id');
            const currentStatus = button.getAttribute('data-status');
            
            console.log("Modal ouvert pour commande:", { id: commandeId, status: currentStatus });
            
            // Mettre à jour les champs cachés
            document.getElementById('commandeIdInput').value = commandeId;
            document.getElementById('currentStatusInput').value = currentStatus;
            document.getElementById('commandeIdText').textContent = '#' + commandeId;
            
            // Afficher le statut actuel
            const statusContext = document.getElementById('statusContext');
            statusContext.innerHTML = `
                <span class="current-status-badge ${getStatusClassJS(currentStatus)}">
                    <i class="fas fa-${getStatusIconJS(currentStatus)}"></i>
                    ${getStatusLabelJS(currentStatus)}
                </span>
            `;
            
            // Animer l'apparition des options
            const optionsGrid = document.querySelector('.status-options-grid');
            optionsGrid.classList.remove('animated');
            
            // Forcer un reflow pour réinitialiser l'animation
            void optionsGrid.offsetWidth;
            
            // Puis ajouter la classe pour lancer l'animation
            setTimeout(() => {
                optionsGrid.classList.add('animated');
            }, 50);
        });
        
        // Attacher les événements aux boutons de statut
        document.querySelectorAll('.status-option-card').forEach(button => {
            button.addEventListener('click', function() {
                const commandeId = document.getElementById('commandeIdInput').value;
                const newStatus = this.getAttribute('data-status');
                
                updateCommandeStatus(commandeId, newStatus);
            });
        });
    }
});
</script>

<!-- Modal d'édition de commande -->
<div class="modal fade" id="editCommandeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-edit me-2 text-primary"></i>
                    Modifier la commande #<span id="edit_commande_id_display"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="editCommandeForm">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <!-- Informations client -->
                    <div class="mb-4">
                        <label class="form-label">Client</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="edit_client_name" readonly>
                            <input type="hidden" id="edit_client_id" name="client_id">
                            <button type="button" class="btn btn-outline-secondary" onclick="showClientInfo(edit_client_id.value)">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Informations de la commande -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Fournisseur</label>
                            <select class="form-select" id="edit_fournisseur_id" name="fournisseur_id" required>
                                <option value="">Sélectionner un fournisseur</option>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
                                    while ($fournisseur = $stmt->fetch()) {
                                        echo "<option value='{$fournisseur['id']}'>" . 
                                             htmlspecialchars($fournisseur['nom']) . "</option>";
                                    }
                                } catch (PDOException $e) {
                                    echo "<option value=''>Erreur de chargement des fournisseurs</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Pièce</label>
                            <input type="text" class="form-control" id="edit_nom_piece" name="nom_piece" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Code barre</label>
                            <input type="text" class="form-control" id="edit_code_barre" name="code_barre">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Quantité</label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" onclick="decrementEditQuantity()">-</button>
                                <input type="number" class="form-control text-center" id="edit_quantite" name="quantite" min="1" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="incrementEditQuantity()">+</button>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Prix estimé (€)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="edit_prix_estime" name="prix_estime" step="0.01" required>
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Date de création</label>
                            <input type="datetime-local" class="form-control" id="edit_date_creation" name="date_creation" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Statut</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-warning flex-grow-1 btn-status-choice" data-status="en_attente">
                                    <i class="fas fa-clock me-1"></i> En attente
                                </button>
                                <button type="button" class="btn btn-outline-primary flex-grow-1 btn-status-choice" data-status="commande">
                                    <i class="fas fa-shopping-cart fa-lg"></i> Commandé
                                </button>
                                <button type="button" class="btn btn-outline-success flex-grow-1 btn-status-choice" data-status="recue">
                                    <i class="fas fa-box fa-lg"></i> Reçu
                                </button>
                                <button type="button" class="btn btn-outline-primary flex-grow-1 btn-status-choice" data-status="utilise">
                                    <i class="fas fa-check-double me-1"></i> Utilisé
                                </button>
                                <button type="button" class="btn btn-outline-secondary flex-grow-1 btn-status-choice" data-status="a_retourner">
                                    <i class="fas fa-undo me-1"></i> Retour
                                </button>
                                <button type="button" class="btn btn-outline-danger flex-grow-1 btn-status-choice" data-status="annulee">
                                    <i class="fas fa-times me-1"></i> Annulé
                                </button>
                            </div>
                            <input type="hidden" id="edit_statut" name="statut" value="en_attente">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="updateCommande()">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>