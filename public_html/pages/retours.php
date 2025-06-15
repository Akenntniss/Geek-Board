<?php
// Vérifier que functions.php est bien inclus
if (!function_exists('redirect')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Fonctions helper pour les statuts
if (!function_exists('getStatusColor')) {
    function getStatusColor($status) {
        switch ($status) {
            case 'en_attente':
                return 'warning';
            case 'en_cours':
                return 'info';
            case 'expedie':
                return 'primary';
            case 'termine':
                return 'success';
            case 'annule':
                return 'danger';
            case 'livre':
                return 'success';
            default:
                return 'secondary';
        }
    }
}

if (!function_exists('getStatusLabel')) {
    function getStatusLabel($status) {
        switch ($status) {
            case 'en_attente':
                return 'En attente';
            case 'en_cours':
                return 'En cours';
            case 'expedie':
                return 'Expédié';
            case 'termine':
                return 'Terminé';
            case 'annule':
                return 'Annulé';
            case 'livre':
                return 'Livré';
            default:
                return ucfirst($status);
        }
    }
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('index');
}

// Récupérer les retours en cours
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->prepare("
        SELECT r.*, s.name as produit_nom, s.barcode, s.price as prix_achat
        FROM retours r
        JOIN stock s ON r.produit_id = s.id
        WHERE r.statut != 'termine'
        ORDER BY r.date_limite ASC
    ");
    $stmt->execute();
    $retours = $stmt->fetchAll();
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des retours: " . $e->getMessage(), 'danger');
    $retours = [];
}

// Récupérer les colis en cours
try {
    $stmt = $shop_pdo->prepare("
        SELECT c.*, COUNT(r.id) as nombre_produits
        FROM colis_retour c
        LEFT JOIN retours r ON c.id = r.colis_id
        WHERE c.statut != 'livre'
        GROUP BY c.id
        ORDER BY c.date_creation DESC
    ");
    $stmt->execute();
    $colis = $stmt->fetchAll();
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des colis: " . $e->getMessage(), 'danger');
    $colis = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Retours</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
    /* Styles spécifiques pour la page retours */
    :root {
        --primary-gradient: linear-gradient(135deg, #5e72e4 0%, #825ee4 100%);
        --secondary-gradient: linear-gradient(135deg, rgba(20, 30, 48, 0.02) 0%, rgba(36, 59, 85, 0.05) 100%);
        --card-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
        --hover-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
        --border-radius: 15px;
        --transition-speed: 0.3s;
    }

    /* Animation de fade-in pour les éléments */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Animation de pulse pour les badges */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .futuristic-container {
        background: var(--secondary-gradient);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
        animation: fadeIn 0.5s ease-out;
    }

    .futuristic-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--primary-gradient);
    }

    .futuristic-card {
        background: #ffffff;
        border-radius: var(--border-radius);
        border: none;
        box-shadow: var(--card-shadow);
        transition: all var(--transition-speed) ease;
        overflow: hidden;
        position: relative;
    }

    .futuristic-card:hover {
        box-shadow: var(--hover-shadow);
        transform: translateY(-5px);
    }

    .futuristic-card .card-header {
        background: linear-gradient(90deg, #f8f9fa 0%, #ffffff 100%);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1.25rem;
    }

    .futuristic-title {
        font-weight: 600;
        color: #3a3f51;
        margin-bottom: 0;
        display: flex;
        align-items: center;
        position: relative;
    }

    .futuristic-title i {
        margin-right: 10px;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 1.2em;
    }

    .futuristic-badge {
        font-size: 0.75rem;
        padding: 0.5em 1em;
        border-radius: 30px;
        font-weight: 500;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        transition: all var(--transition-speed) ease;
        animation: pulse 2s infinite;
    }

    .futuristic-badge.bg-danger {
        animation: pulse 1s infinite;
    }

    .futuristic-btn {
        border-radius: 12px;
        font-weight: 500;
        letter-spacing: 0.3px;
        padding: 0.6rem 1.2rem;
        transition: all var(--transition-speed) ease;
        box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11);
        position: relative;
        overflow: hidden;
    }

    .futuristic-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0.2));
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .futuristic-btn:hover::before {
        transform: translateX(0);
    }

    .futuristic-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1);
    }

    .futuristic-btn i {
        transition: transform var(--transition-speed) ease;
    }

    .futuristic-btn:hover i {
        transform: translateX(3px);
    }

    .futuristic-table {
        width: 100%;
        margin-bottom: 1rem;
        background: #ffffff;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--card-shadow);
    }

    .futuristic-table th {
        background: var(--secondary-gradient);
        color: #3a3f51;
        font-weight: 600;
        padding: 1rem;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        border: none;
    }

    .futuristic-table td {
        padding: 1rem;
        vertical-align: middle;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        transition: background var(--transition-speed) ease;
    }

    .futuristic-table tr:hover td {
        background: rgba(94, 114, 228, 0.05);
    }

    .futuristic-form-control {
        border-radius: 12px;
        border: 2px solid #edf2f7;
        padding: 0.75rem 1rem;
        transition: all var(--transition-speed) ease;
        background: #f8fafc;
    }

    .futuristic-form-control:focus {
        border-color: #5e72e4;
        box-shadow: 0 0 0 3px rgba(94, 114, 228, 0.1);
        background: #ffffff;
    }

    .futuristic-modal .modal-content {
        border-radius: var(--border-radius);
        border: none;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .futuristic-modal .modal-header {
        background: var(--secondary-gradient);
        border-bottom: none;
        padding: 1.5rem;
    }

    .futuristic-modal .modal-title {
        color: #3a3f51;
        font-weight: 600;
    }

    .futuristic-modal .modal-body {
        padding: 1.5rem;
    }

    .futuristic-modal .modal-footer {
        border-top: none;
        padding: 1.5rem;
        background: #f8fafc;
        border-radius: 0 0 var(--border-radius) var(--border-radius);
    }

    /* Animations pour les statistiques */
    .stats-counter {
        font-size: 2rem;
        font-weight: 700;
        color: #5e72e4;
        margin-bottom: 0.5rem;
        opacity: 0;
        transform: translateY(20px);
        animation: fadeIn 0.5s ease-out forwards;
    }

    .stats-label {
        color: #8898aa;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .futuristic-container {
            padding: 1rem;
        }

        .stats-counter {
            font-size: 1.5rem;
        }

        .futuristic-table {
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

    /* Styles pour rendre le champ de recherche cliquable */
    .select2-container {
        width: 100% !important;
        z-index: 9999;
    }

    .select2-container .select2-selection--single {
        height: 38px !important;
        padding: 5px 10px;
        cursor: text !important;
    }

    .select2-container--default .select2-selection--single {
        border: 1px solid #ced4da;
        border-radius: 4px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px;
        cursor: text !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }

    /* S'assurer que le dropdown est au-dessus des autres éléments */
    .select2-dropdown {
        z-index: 99999;
    }
    </style>
</head>
<body>
    <!-- Page Container -->
    <div class="container-fluid py-4">
        <style>
            /* Styles pour forcer l'affichage en une seule colonne */
            #single-column-layout {
                display: block;
                width: 100%;
                margin: 0;
                padding: 0;
            }
            
            #single-column-layout > div {
                width: 100% !important;
                max-width: 100% !important;
                margin-bottom: 2rem;
                display: block !important;
            }
            
            /* Désactiver les propriétés qui pourraient causer l'affichage en colonnes */
            .dashboard-tables-container,
            .row,
            .col-12,
            .col-md-3 {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
            
            /* Styles pour les cartes de statistiques */
            .stats-cards {
                display: flex;
                flex-wrap: wrap;
                gap: 1rem;
                margin-bottom: 2rem;
            }
            
            .stat-card {
                flex: 1 1 200px;
                min-width: 200px;
                max-width: 300px;
            }
        </style>

        <!-- CONTENEUR PRINCIPAL EN UNE SEULE COLONNE -->
        <div id="single-column-layout">
            <!-- En-tête et boutons d'action -->
            <div class="futuristic-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 futuristic-title">
                        <i class="fas fa-exchange-alt"></i>
                        Gestion des Retours
                    </h1>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn futuristic-btn btn-primary" data-bs-toggle="modal" data-bs-target="#nouveauRetourModal">
                            <i class="fas fa-plus me-2"></i> Nouveau Retour
                        </button>
                        <button type="button" class="btn futuristic-btn btn-info" data-bs-toggle="modal" data-bs-target="#nouveauColisModal">
                            <i class="fas fa-box me-2"></i> Nouveau Colis
                        </button>
                    </div>
                </div>

                <!-- Statistiques rapides -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="card futuristic-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-shape bg-gradient-primary shadow-primary text-center rounded-circle me-3">
                                        <i class="fas fa-undo text-white opacity-10"></i>
                                    </div>
                                    <div>
                                        <span class="data-label">Total retours</span>
                                        <h3 class="data-value mb-0"><?= count($retours) ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="card futuristic-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-shape bg-gradient-success shadow-success text-center rounded-circle me-3">
                                        <i class="fas fa-box text-white opacity-10"></i>
                                    </div>
                                    <div>
                                        <span class="data-label">Total colis</span>
                                        <h3 class="data-value mb-0"><?= count($colis) ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="card futuristic-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-shape bg-gradient-warning shadow-warning text-center rounded-circle me-3">
                                        <i class="fas fa-exclamation-triangle text-white opacity-10"></i>
                                    </div>
                                    <div>
                                        <?php 
                                        $retours_en_retard = 0;
                                        foreach ($retours as $retour) {
                                            if (strtotime($retour['date_limite']) < time()) {
                                                $retours_en_retard++;
                                            }
                                        }
                                        ?>
                                        <span class="data-label">Retours en retard</span>
                                        <h3 class="data-value mb-0"><?= $retours_en_retard ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="card futuristic-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="icon-shape bg-gradient-info shadow-info text-center rounded-circle me-3">
                                        <i class="fas fa-euro-sign text-white opacity-10"></i>
                                    </div>
                                    <div>
                                        <?php 
                                        $montant_total = 0;
                                        foreach ($retours as $retour) {
                                            if (!empty($retour['montant_rembourse'])) {
                                                $montant_total += $retour['montant_rembourse'];
                                            }
                                        }
                                        ?>
                                        <span class="data-label">Montant total</span>
                                        <h3 class="data-value mb-0"><?= number_format($montant_total, 2, ',', ' ') ?> €</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: Liste des Retours -->
            <div class="card futuristic-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="futuristic-title">
                            <i class="fas fa-undo-alt"></i> Liste des retours
                        </h5>
                        <div class="input-group" style="width: 250px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 ps-0" id="searchRetours" placeholder="Rechercher...">
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table futuristic-table table-hover align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Date limite</th>
                                    <th>Statut</th>
                                    <th>N° Suivi</th>
                                    <th>Montant</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($retours) > 0): ?>
                                    <?php foreach ($retours as $retour): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="icon-shape bg-gradient-primary shadow-primary text-center rounded-circle me-3" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-box text-white opacity-10"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($retour['produit_nom']) ?></h6>
                                                    <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($retour['barcode']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <p class="text-sm mb-0"><?= date('d/m/Y', strtotime($retour['date_limite'])) ?></p>
                                                <?php if (strtotime($retour['date_limite']) < time()): ?>
                                                    <span class="futuristic-badge bg-danger text-white">En retard</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="futuristic-badge bg-<?= getStatusColor($retour['statut']) ?> text-white">
                                                <?= getStatusLabel($retour['statut']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $retour['numero_suivi'] ? htmlspecialchars($retour['numero_suivi']) : '<span class="text-muted">-</span>' ?>
                                        </td>
                                        <td>
                                            <?= $retour['montant_rembourse'] ? '<span class="fw-bold">' . number_format($retour['montant_rembourse'], 2, ',', ' ') . ' €</span>' : '<span class="text-muted">-</span>' ?>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-action btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editRetourModal" data-retour-id="<?= $retour['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-action btn-outline-info" data-bs-toggle="tooltip" title="Détails">
                                                <i class="fas fa-info"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-box-open"></i>
                                                </div>
                                                <h4>Aucun retour en cours</h4>
                                                <p class="text-muted">Commencez par ajouter un nouveau retour</p>
                                                <button type="button" class="btn futuristic-btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#nouveauRetourModal">
                                                    <i class="fas fa-plus me-2"></i> Ajouter un retour
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Colis de Retour -->
            <div class="card futuristic-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="futuristic-title">
                            <i class="fas fa-shipping-fast"></i> Colis de Retour
                        </h5>
                        <div class="input-group" style="width: 250px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 ps-0" id="searchColis" placeholder="Rechercher...">
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table futuristic-table table-hover align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>N° Suivi</th>
                                    <th>Date création</th>
                                    <th>Statut</th>
                                    <th>Produits</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($colis) > 0): ?>
                                    <?php foreach ($colis as $colis_item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="icon-shape bg-gradient-info shadow-info text-center rounded-circle me-3" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-truck text-white opacity-10"></i>
                                                </div>
                                                <span class="fw-bold"><?= htmlspecialchars($colis_item['numero_suivi']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y H:i', strtotime($colis_item['date_creation'])) ?>
                                        </td>
                                        <td>
                                            <span class="futuristic-badge bg-<?= getStatusColor($colis_item['statut']) ?> text-white">
                                                <?= getStatusLabel($colis_item['statut']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?= $colis_item['nombre_produits'] ?> produit(s)</span>
                                                <?php if ($colis_item['nombre_produits'] > 0): ?>
                                                <div class="progress" style="width: 100px; height: 6px;">
                                                    <div class="progress-bar bg-gradient-success" style="width: 100%;"></div>
                                                </div>
                                                <?php else: ?>
                                                <div class="progress" style="width: 100px; height: 6px;">
                                                    <div class="progress-bar bg-gradient-danger" style="width: 0%;"></div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-action btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editColisModal" data-colis-id="<?= $colis_item['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-action btn-outline-info" data-bs-toggle="tooltip" title="Tracker">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-shipping-fast"></i>
                                                </div>
                                                <h4>Aucun colis en cours</h4>
                                                <p class="text-muted">Commencez par créer un nouveau colis</p>
                                                <button type="button" class="btn futuristic-btn btn-info mt-3" data-bs-toggle="modal" data-bs-target="#nouveauColisModal">
                                                    <i class="fas fa-plus me-2"></i> Créer un colis
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nouveau Retour -->
    <div class="modal fade futuristic-modal" id="nouveauRetourModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>
                        Nouveau Retour
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="nouveauRetourForm" class="futuristic-form" method="POST" action="index.php?page=retours_actions">
                        <input type="hidden" name="action" value="ajouter_retour">
                        <div class="mb-3">
                            <label class="form-label">Produit</label>
                            <select class="form-select produit-select" name="produit_id" required>
                                <option></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date limite</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                <input type="date" class="form-control" name="date_limite" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Informations complémentaires..."></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn futuristic-btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i> Annuler
                            </button>
                            <button type="submit" class="btn futuristic-btn btn-primary">
                                <i class="fas fa-save me-2"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nouveau Colis -->
    <div class="modal fade futuristic-modal" id="nouveauColisModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-box me-2 text-info"></i>
                        Nouveau Colis
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="nouveauColisForm" class="futuristic-form" method="POST" action="index.php?page=retours_actions">
                        <input type="hidden" name="action" value="ajouter_colis">
                        <div class="mb-3">
                            <label class="form-label">Numéro de suivi</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-truck"></i></span>
                                <input type="text" class="form-control" name="numero_suivi" placeholder="Ex: CB123456789FR" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Informations complémentaires..."></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn futuristic-btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i> Annuler
                            </button>
                            <button type="submit" class="btn futuristic-btn btn-info">
                                <i class="fas fa-save me-2"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Édition Retour -->
    <div class="modal fade futuristic-modal" id="editRetourModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2 text-warning"></i>
                        Modifier le Retour
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editRetourForm" class="futuristic-form" method="POST" action="index.php?page=retours_actions">
                        <input type="hidden" name="action" value="modifier_retour">
                        <input type="hidden" name="retour_id" id="edit_retour_id">
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-flag"></i></span>
                                <select class="form-select" name="statut" required>
                                    <option value="en_attente">En attente</option>
                                    <option value="en_preparation">En préparation</option>
                                    <option value="expedie">Expédié</option>
                                    <option value="livre">Livré</option>
                                    <option value="a_verifier">À vérifier</option>
                                    <option value="termine">Terminé</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Numéro de suivi</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-truck"></i></span>
                                <input type="text" class="form-control" name="numero_suivi" id="edit_numero_suivi" placeholder="Ex: CB123456789FR">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Montant remboursé</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                        <input type="number" step="0.01" class="form-control" name="montant_rembourse" id="edit_montant_rembourse">
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Montant remboursé client</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                        <input type="number" step="0.01" class="form-control" name="montant_rembourse_client" id="edit_montant_rembourse_client">
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="edit_notes" rows="3" placeholder="Informations complémentaires..."></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn futuristic-btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i> Annuler
                            </button>
                            <button type="submit" class="btn futuristic-btn btn-warning">
                                <i class="fas fa-save me-2"></i> Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Édition Colis -->
    <div class="modal fade futuristic-modal" id="editColisModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2 text-warning"></i>
                        Modifier le Colis
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editColisForm" class="futuristic-form" method="POST" action="index.php?page=retours_actions">
                        <input type="hidden" name="action" value="modifier_colis">
                        <input type="hidden" name="colis_id" id="edit_colis_id">
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-flag"></i></span>
                                <select class="form-select" name="statut" required>
                                    <option value="en_preparation">En préparation</option>
                                    <option value="en_expedition">En expédition</option>
                                    <option value="livre">Livré</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" id="edit_colis_notes" rows="3" placeholder="Informations complémentaires..."></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn futuristic-btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i> Annuler
                            </button>
                            <button type="submit" class="btn futuristic-btn btn-warning">
                                <i class="fas fa-save me-2"></i> Mettre à jour
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    // Fonctions utilitaires pour les statuts
    function getStatusColor(status) {
        const colors = {
            'en_attente': 'warning',
            'en_preparation': 'info',
            'expedie': 'primary',
            'livre': 'success',
            'a_verifier': 'danger',
            'termine': 'secondary',
            'en_expedition': 'primary'
        };
        return colors[status] || 'secondary';
    }

    function getStatusLabel(status) {
        const labels = {
            'en_attente': 'En attente',
            'en_preparation': 'En préparation',
            'expedie': 'Expédié',
            'livre': 'Livré',
            'a_verifier': 'À vérifier',
            'termine': 'Terminé',
            'en_expedition': 'En expédition'
        };
        return labels[status] || status;
    }

    // Amélioration des fonctions JavaScript existantes
    document.addEventListener('DOMContentLoaded', function() {
        // Initialisation des tooltips et popovers
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                animation: true,
                delay: { show: 100, hide: 100 }
            });
        });

        // Animation des compteurs statistiques
        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Appliquer l'animation aux compteurs
        document.querySelectorAll('.stats-counter').forEach(counter => {
            const value = parseInt(counter.getAttribute('data-value'));
            animateValue(counter, 0, value, 1500);
        });

        // Fonction de recherche améliorée avec debounce
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            const performSearch = debounce((searchTerm) => {
                const rows = document.querySelectorAll('.futuristic-table tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const match = text.includes(searchTerm.toLowerCase());
                    row.style.display = match ? '' : 'none';
                });

                // Afficher/masquer le message "Aucun résultat"
                const noResults = document.querySelector('.empty-state');
                if (noResults) {
                    const hasVisibleRows = Array.from(rows).some(row => row.style.display !== 'none');
                    noResults.style.display = hasVisibleRows ? 'none' : 'block';
                }
            }, 300);

            searchInput.addEventListener('input', (e) => performSearch(e.target.value));
        }

        // Gestion améliorée des modals
        const modals = document.querySelectorAll('.futuristic-modal');
        modals.forEach(modal => {
            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (button) {
                    const data = button.dataset;
                    const modalTitle = this.querySelector('.modal-title');
                    const modalBody = this.querySelector('.modal-body');

                    // Animation de chargement
                    modalBody.style.opacity = '0';
                    setTimeout(() => {
                        // Remplir les données du modal ici
                        modalBody.style.opacity = '1';
                    }, 200);
                }
            });
        });

        // Formatage des montants en euros
        document.querySelectorAll('.amount').forEach(element => {
            const amount = parseFloat(element.textContent);
            element.textContent = new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'EUR'
            }).format(amount);
        });

        // Attendre que jQuery soit chargé
        window.addEventListener('load', function() {
            if (window.jQuery) {
                // Initialisation de Select2
                $(document).ready(function() {
                    // Détruire toute instance existante
                    if ($('.produit-select').hasClass('select2-hidden-accessible')) {
                        $('.produit-select').select2('destroy');
                    }
                    
                    // Initialiser Select2
                    $('.produit-select').select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        dropdownParent: $('#nouveauRetourModal'),
                        placeholder: 'Rechercher un produit...',
                        allowClear: true,
                        minimumInputLength: 2,
                        language: {
                            inputTooShort: function() {
                                return 'Veuillez saisir au moins 2 caractères';
                            },
                            searching: function() {
                                return 'Recherche en cours...';
                            },
                            noResults: function() {
                                return 'Aucun résultat trouvé';
                            }
                        },
                        ajax: {
                            url: 'ajax/search_products.php',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                console.log('Recherche avec params:', params);
                                return {
                                    q: params.term,
                                    page: params.page || 1
                                };
                            },
                            processResults: function(data) {
                                console.log('Données reçues:', data);
                                return {
                                    results: data.results || [],
                                    pagination: {
                                        more: data.pagination ? data.pagination.more : false
                                    }
                                };
                            },
                            error: function(xhr) {
                                console.error('Erreur AJAX:', xhr.responseText);
                            },
                            cache: true
                        },
                        templateResult: formatProduct,
                        templateSelection: formatProductSelection
                    }).on('select2:open', function() {
                        // Focus sur le champ de recherche quand le dropdown s'ouvre
                        setTimeout(function() {
                            $('.select2-search__field').focus();
                        }, 100);
                    });

                    // Réinitialiser Select2 quand le modal s'ouvre
                    $('#nouveauRetourModal').on('shown.bs.modal', function() {
                        $('.produit-select').val(null).trigger('change');
                    });
                });
            } else {
                console.error('jQuery not loaded');
            }
        });

        function formatProduct(product) {
            if (!product.id) return product.text;
            return $('<div class="select2-result">' +
                '<div class="select2-result__title">' + product.text + '</div>' +
                '<div class="select2-result__meta">' +
                '<span class="badge bg-primary me-2">' + product.code + '</span>' +
                '<span class="text-muted">Prix: ' + product.price + ' €</span>' +
                '<span class="text-muted ms-2">Stock: ' + product.quantity + '</span>' +
                '</div>' +
                '</div>');
        }

        function formatProductSelection(product) {
            return product.text || product.id;
        }
    });
    </script>
</body>
</html> 