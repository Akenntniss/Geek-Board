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

<!-- Contenu principal de la page avec design amélioré -->
<div class="container-fluid py-4">
    <style>
        /* Variables CSS pour la palette de couleurs */
        :root {
            --primary: #3b82f6;
            --secondary: #64748b;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #ca8a04;
            --info: #4f46e5;
            --bg-light: #f1f5f9;
            --text-dark: #1e293b;
            --white: #ffffff;
            --card-border-radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        /* Styles généraux pour la page */
        body {
            background-color: var(--bg-light);
            color: var(--text-dark);
        }
        
        /* Titre de la page amélioré */
        .h3.mb-4 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem !important;
        }
        
        /* Style moderne pour les cartes */
        .card {
            border-radius: var(--card-border-radius);
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            border: none;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .card-header {
            background: linear-gradient(to right, var(--primary), rgba(59, 130, 246, 0.8));
            color: var(--white);
            font-weight: 600;
            border-bottom: none;
            padding: 1rem 1.25rem;
        }
        
        .card-footer {
            background-color: rgba(241, 245, 249, 0.5);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1.25rem;
        }
        
        /* Style amélioré pour les badges de statut */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            border-radius: 999px;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .status-badge:hover {
            transform: scale(1.05);
        }
        
        .status-badge::before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
            background-color: currentColor;
        }
        
        /* Style amélioré pour les badges de date */
        .date-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        .date-badge:hover {
            transform: scale(1.03);
        }
        
        /* Style pour les boutons de filtre */
        .status-filter {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.4rem 0.75rem;
            transition: var(--transition);
        }
        
        .status-filter:hover, .status-filter.active {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        /* Style pour la barre de recherche */
        #searchCommandes {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        #searchCommandes:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }
        
        /* Animation pour les boutons Google */
        .btn-google:hover {
            animation: pulse 1s;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        /* Style pour les boutons d'action */
        .btn-group .btn {
            border-radius: 6px;
            margin: 0 2px;
            transition: var(--transition);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-danger:hover {
            background-color: var(--danger);
            border-color: var(--danger);
        }
        
        /* Style pour le tableau des commandes */
        .table {
            border-radius: var(--card-border-radius);
            overflow: hidden;
        }
        
        .table th {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
        }
        
        .table td {
            padding: 0.85rem 0.75rem;
            vertical-align: middle;
        }
        
        .table tr {
            transition: var(--transition);
        }
        
        .table tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }
        
        /* Avatar pour le client */
        .avatar-circle {
            width: 32px;
            height: 32px;
            background-color: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.8rem;
        }
        
        /* Lien client plus visible */
        .client-info-link {
            color: var(--primary);
            font-weight: 500;
            transition: var(--transition);
        }
        
        .client-info-link:hover {
            color: var(--info);
            text-decoration: underline !important;
        }
        
        /* Style pour les champs éditables */
        .editable-field {
            position: relative;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: var(--transition);
        }
        
        .editable-field:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
        
        .editable-field::after {
            content: "✏️";
            position: absolute;
            right: -15px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: var(--transition);
            font-size: 0.75rem;
        }
        
        .editable-field:hover::after {
            opacity: 1;
            right: -20px;
        }
        
        /* Modales améliorés */
        .modal-content {
            border-radius: 16px;
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(45deg, var(--primary), var(--info));
            color: var(--white);
            border-bottom: none;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: none;
            padding: 1rem 1.5rem 1.5rem;
        }
        
        /* Animation d'apparition des modales */
        .modal.fade .modal-dialog {
            transition: transform 0.3s ease-out;
            transform: scale(0.95);
        }
        
        .modal.show .modal-dialog {
            transform: scale(1);
        }
        
        /* Effets des filtres */
        #status-filter-group {
            box-shadow: var(--shadow-sm);
            border-radius: 8px;
            overflow: hidden;
        }
        
        /* Style pour les boutons d'exportation et nouvelle commande */
        #export-pdf-btn, .btn-primary[data-bs-toggle="modal"] {
            background: linear-gradient(45deg, var(--success), #22c55e);
            border: none;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        #export-pdf-btn:hover, .btn-primary[data-bs-toggle="modal"]:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-primary[data-bs-toggle="modal"] {
            background: linear-gradient(45deg, var(--primary), var(--info));
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
            
            <!-- Bouton pour activer/désactiver l'envoi de SMS -->
            <div class="p-4 border-top">
                <button id="smsToggleButtonStatus" type="button" class="btn btn-danger w-100 py-3" style="font-weight: bold; font-size: 1rem; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                    <i class="fas fa-ban me-2"></i>
                    NE PAS ENVOYER DE SMS AU CLIENT
                </button>
                <input type="hidden" id="sendSmsSwitchStatus" name="send_sms" value="0">
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
                        
                        <!-- Bouton pour activer/désactiver l'envoi de SMS -->
                        <div class="col-12 mt-4">
                            <button id="smsToggleButton" type="button" class="btn btn-danger w-100 py-3" style="font-weight: bold; font-size: 1rem; transition: all 0.3s ease; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                <i class="fas fa-ban me-2"></i>
                                NE PAS ENVOYER DE SMS AU CLIENT
                            </button>
                            <input type="hidden" id="sendSmsSwitch" name="send_sms" value="0">
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

<!-- Modal pour filtrer par fournisseur -->
<div class="modal fade" id="fournisseursModal" tabindex="-1" aria-labelledby="fournisseursModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="fournisseursModalLabel">
                    <i class="fas fa-filter me-2 text-primary"></i>
                    Filtrer par fournisseur
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0 shadow-sm">
                            <i class="fas fa-search text-primary"></i>
                        </span>
                        <input type="text" id="searchFournisseur" class="form-control bg-light border-0 shadow-sm" placeholder="Rechercher un fournisseur...">
                    </div>
                </div>
                
                <div class="list-group fournisseurs-list" style="max-height: 300px; overflow-y: auto;">
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-fournisseur-id="all">
                        <span>Tous les fournisseurs</span>
                        <span class="badge bg-primary rounded-pill" id="count-all">0</span>
                    </button>
                    
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT f.id, f.nom, COUNT(c.id) as count_commandes 
                                          FROM fournisseurs f 
                                          LEFT JOIN commandes_pieces c ON f.id = c.fournisseur_id 
                                          GROUP BY f.id 
                                          ORDER BY f.nom");
                        $fournisseurs = $stmt->fetchAll();
                        
                        $totalCommandes = 0;
                        foreach ($fournisseurs as $fournisseur) {
                            $totalCommandes += $fournisseur['count_commandes'];
                            echo '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center fournisseur-item" data-fournisseur-id="' . $fournisseur['id'] . '" data-fournisseur-nom="' . htmlspecialchars($fournisseur['nom']) . '">';
                            echo '<span>' . htmlspecialchars($fournisseur['nom']) . '</span>';
                            echo '<span class="badge bg-primary rounded-pill">' . $fournisseur['count_commandes'] . '</span>';
                            echo '</button>';
                        }
                        
                        // Mettre à jour le compteur "Tous les fournisseurs"
                        echo '<script>document.getElementById("count-all").textContent = "' . $totalCommandes . '";</script>';
                    } catch (PDOException $e) {
                        echo '<div class="alert alert-danger">Erreur lors de la récupération des fournisseurs: ' . $e->getMessage() . '</div>';
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour filtrer par période -->
<div class="modal fade" id="periodesModal" tabindex="-1" aria-labelledby="periodesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title" id="periodesModalLabel">
                    <i class="fas fa-calendar-alt me-2 text-primary"></i>
                    Filtrer par période
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center periode-item" data-periode="all">
                        <div>
                            <span class="fw-medium">Toutes les périodes</span>
                            <small class="d-block text-muted">Afficher toutes les commandes</small>
                        </div>
                        <i class="fas fa-check text-primary periode-check d-none"></i>
                    </button>
                    
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center periode-item" data-periode="today">
                        <div>
                            <span class="fw-medium">Aujourd'hui</span>
                            <small class="d-block text-muted"><?= date('d/m/Y') ?></small>
                        </div>
                        <i class="fas fa-check text-primary periode-check d-none"></i>
                    </button>
                    
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center periode-item" data-periode="yesterday">
                        <div>
                            <span class="fw-medium">Hier</span>
                            <small class="d-block text-muted"><?= date('d/m/Y', strtotime('-1 day')) ?></small>
                        </div>
                        <i class="fas fa-check text-primary periode-check d-none"></i>
                    </button>
                    
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center periode-item" data-periode="this_week">
                        <div>
                            <span class="fw-medium">Cette semaine</span>
                            <small class="d-block text-muted">Du <?= date('d/m/Y', strtotime('monday this week')) ?> au <?= date('d/m/Y', strtotime('sunday this week')) ?></small>
                        </div>
                        <i class="fas fa-check text-primary periode-check d-none"></i>
                    </button>
                    
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center periode-item" data-periode="last_week">
                        <div>
                            <span class="fw-medium">Semaine dernière</span>
                            <small class="d-block text-muted">Du <?= date('d/m/Y', strtotime('monday last week')) ?> au <?= date('d/m/Y', strtotime('sunday last week')) ?></small>
                        </div>
                        <i class="fas fa-check text-primary periode-check d-none"></i>
                    </button>
                    
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center periode-item" data-periode="this_month">
                        <div>
                            <span class="fw-medium">Ce mois</span>
                            <small class="d-block text-muted"><?= date('F Y') ?></small>
                        </div>
                        <i class="fas fa-check text-primary periode-check d-none"></i>
                    </button>
                    
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center periode-item" data-periode="last_month">
                        <div>
                            <span class="fw-medium">Mois dernier</span>
                            <small class="d-block text-muted"><?= date('F Y', strtotime('first day of last month')) ?></small>
                        </div>
                        <i class="fas fa-check text-primary periode-check d-none"></i>
                    </button>
                    
                    <div class="list-group-item">
                        <p class="mb-2 fw-medium">Période personnalisée</p>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Date de début</label>
                                <input type="date" id="startDate" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Date de fin</label>
                                <input type="date" id="endDate" class="form-control">
                            </div>
                        </div>
                        <button type="button" id="applyCustomPeriod" class="btn btn-primary w-100 mt-2">
                            <i class="fas fa-filter me-1"></i> Appliquer
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Ajoutons également du code JavaScript pour gérer les filtres -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du filtre par fournisseur
    const fournisseurBouton = document.getElementById('fournisseurBouton');
    const fournisseurItems = document.querySelectorAll('.fournisseur-item');
    let currentFournisseurFilter = 'all';
    
    fournisseurItems.forEach(item => {
        item.addEventListener('click', function() {
            const fournisseurId = this.getAttribute('data-fournisseur-id');
            const fournisseurNom = this.getAttribute('data-fournisseur-nom');
            
            // Mettre à jour le bouton
            if (fournisseurId === 'all') {
                fournisseurBouton.innerHTML = '<i class="fas fa-filter"></i> Tous les fournisseurs';
            } else {
                fournisseurBouton.innerHTML = '<i class="fas fa-filter"></i> ' + fournisseurNom;
            }
            
            currentFournisseurFilter = fournisseurId;
            
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('fournisseursModal'));
            if (modal) {
                modal.hide();
            }
            
            // Appliquer le filtre
            filterCommandes();
        });
    });

    // Gestion du filtre par période
    const periodeButton = document.getElementById('periodeButton');
    const periodeItems = document.querySelectorAll('.periode-item');
    let currentPeriodFilter = 'all';
    
    periodeItems.forEach(item => {
        item.addEventListener('click', function() {
            const periode = this.getAttribute('data-periode');
            
            // Mettre à jour visuellement l'élément sélectionné
            periodeItems.forEach(el => {
                el.querySelector('.periode-check').classList.add('d-none');
            });
            this.querySelector('.periode-check').classList.remove('d-none');
            
            // Mettre à jour le bouton
            switch(periode) {
                case 'all':
                    periodeButton.innerHTML = '<i class="fas fa-calendar-alt"></i> Toutes les périodes';
                    break;
                case 'today':
                    periodeButton.innerHTML = '<i class="fas fa-calendar-alt"></i> Aujourd\'hui';
                    break;
                case 'yesterday':
                    periodeButton.innerHTML = '<i class="fas fa-calendar-alt"></i> Hier';
                    break;
                case 'this_week':
                    periodeButton.innerHTML = '<i class="fas fa-calendar-alt"></i> Cette semaine';
                    break;
                case 'last_week':
                    periodeButton.innerHTML = '<i class="fas fa-calendar-alt"></i> Semaine dernière';
                    break;
                case 'this_month':
                    periodeButton.innerHTML = '<i class="fas fa-calendar-alt"></i> Ce mois';
                    break;
                case 'last_month':
                    periodeButton.innerHTML = '<i class="fas fa-calendar-alt"></i> Mois dernier';
                    break;
                default:
                    periodeButton.innerHTML = '<i class="fas fa-calendar-alt"></i> Période personnalisée';
            }
            
            currentPeriodFilter = periode;
            
            // Fermer le modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('periodesModal'));
            if (modal) {
                modal.hide();
            }
            
            // Appliquer le filtre
            filterCommandes();
        });
    });
    
    // Période personnalisée
    document.getElementById('applyCustomPeriod').addEventListener('click', function() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        
        if (!startDate || !endDate) {
            alert('Veuillez sélectionner une date de début et de fin');
            return;
        }
        
        // Mettre à jour le bouton
        periodeButton.innerHTML = `<i class="fas fa-calendar-alt"></i> Du ${formatDate(startDate)} au ${formatDate(endDate)}`;
        
        currentPeriodFilter = 'custom';
        
        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('periodesModal'));
        if (modal) {
            modal.hide();
        }
        
        // Appliquer le filtre
        filterCommandes(startDate, endDate);
    });
    
    // Fonction pour formater la date affichée
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR');
    }
    
    // Recherche de fournisseur
    const searchFournisseur = document.getElementById('searchFournisseur');
    if (searchFournisseur) {
        searchFournisseur.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.fournisseur-item').forEach(item => {
                const fournisseurNom = item.getAttribute('data-fournisseur-nom').toLowerCase();
                if (fournisseurNom.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Fonction pour filtrer les commandes
    function filterCommandes(customStartDate, customEndDate) {
        const rows = document.querySelectorAll('#commandesTableBody tr');
        let visibleRows = 0;
        
        rows.forEach(row => {
            let showRow = true;
            
            // Filtre par fournisseur
            if (currentFournisseurFilter !== 'all') {
                const rowFournisseurId = row.getAttribute('data-fournisseur-id');
                if (rowFournisseurId !== currentFournisseurFilter) {
                    showRow = false;
                }
            }
            
            // Filtre par période
            if (showRow && currentPeriodFilter !== 'all') {
                const rowDate = new Date(row.getAttribute('data-date'));
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                
                switch(currentPeriodFilter) {
                    case 'today':
                        showRow = rowDate.toDateString() === today.toDateString();
                        break;
                    case 'yesterday':
                        showRow = rowDate.toDateString() === yesterday.toDateString();
                        break;
                    case 'this_week': {
                        const firstDay = new Date(today);
                        firstDay.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1));
                        const lastDay = new Date(firstDay);
                        lastDay.setDate(firstDay.getDate() + 6);
                        showRow = rowDate >= firstDay && rowDate <= lastDay;
                        break;
                    }
                    case 'last_week': {
                        const firstDay = new Date(today);
                        firstDay.setDate(today.getDate() - today.getDay() - 6);
                        const lastDay = new Date(firstDay);
                        lastDay.setDate(firstDay.getDate() + 6);
                        showRow = rowDate >= firstDay && rowDate <= lastDay;
                        break;
                    }
                    case 'this_month': {
                        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        showRow = rowDate >= firstDay && rowDate <= lastDay;
                        break;
                    }
                    case 'last_month': {
                        const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
                        showRow = rowDate >= firstDay && rowDate <= lastDay;
                        break;
                    }
                    case 'custom': {
                        if (customStartDate && customEndDate) {
                            const startDate = new Date(customStartDate);
                            const endDate = new Date(customEndDate);
                            endDate.setHours(23, 59, 59, 999);
                            showRow = rowDate >= startDate && rowDate <= endDate;
                        }
                        break;
                    }
                }
            }
            
            // Appliquer le filtre
            if (showRow) {
                row.style.display = '';
                visibleRows++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Mettre à jour les compteurs
        document.getElementById('visibleRowsCount').textContent = visibleRows;
        document.getElementById('totalRowsCount').textContent = rows.length;
        
        // Afficher/masquer le bouton de réinitialisation
        const resetButton = document.getElementById('resetFilters');
        if (currentFournisseurFilter !== 'all' || currentPeriodFilter !== 'all') {
            resetButton.classList.remove('d-none');
        } else {
            resetButton.classList.add('d-none');
        }
    }
    
    // Réinitialiser les filtres
    document.getElementById('resetFilters').addEventListener('click', function() {
        fournisseurBouton.innerHTML = '<i class="fas fa-filter"></i> Choisir un fournisseur';
        periodeButton.innerHTML = '<i class="fas fa-calendar-alt"></i> Toutes les périodes';
        currentFournisseurFilter = 'all';
        currentPeriodFilter = 'all';
        
        // Réinitialiser la sélection visuelle
        periodeItems.forEach(el => {
            el.querySelector('.periode-check').classList.add('d-none');
        });
        document.querySelector('.periode-item[data-periode="all"]').querySelector('.periode-check').classList.remove('d-none');
        
        filterCommandes();
        this.classList.add('d-none');
    });
    
    // Initialiser les filtres au chargement
    filterCommandes();
});
</script>

<!-- Ajoutons le script JavaScript pour gérer le bouton SMS -->
<script>
// Initialisation du bouton toggle SMS après le chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    initSmsToggleButton();
    
    // Autres initialisations existantes...
});

// Fonction pour initialiser le bouton toggle pour l'envoi de SMS
function initSmsToggleButton() {
    const toggleButton = document.getElementById('smsToggleButton');
    const smsSwitch = document.getElementById('sendSmsSwitch');
    
    if (!toggleButton || !smsSwitch) {
        console.error('Éléments du bouton SMS toggle non trouvés');
        return;
    }
    
    // Définir l'état initial (0 = SMS désactivé)
    smsSwitch.value = '0';
    
    // Définir l'apparence initiale du bouton
    updateSmsButtonAppearance(toggleButton, false);
    
    // Ajouter l'écouteur d'événement click
    toggleButton.addEventListener('click', function() {
        // Inverser l'état actuel
        const currentState = smsSwitch.value === '1';
        const newState = !currentState;
        
        // Mettre à jour la valeur dans l'input hidden
        smsSwitch.value = newState ? '1' : '0';
        
        // Mettre à jour l'apparence du bouton
        updateSmsButtonAppearance(toggleButton, newState);
        
        // Vibration pour feedback tactile sur mobile
        if ('vibrate' in navigator) {
            navigator.vibrate(50);
        }
        
        // Jouer un son de notification pour confirmer le changement
        playNotificationSound();
        
        console.log('État du SMS mis à jour:', newState ? 'Activé' : 'Désactivé');
    });
}

// Fonction pour mettre à jour l'apparence du bouton selon l'état
function updateSmsButtonAppearance(button, isSmsEnabled) {
    if (isSmsEnabled) {
        // SMS activé
        button.classList.remove('btn-danger');
        button.classList.add('btn-success');
        button.innerHTML = '<i class="fas fa-paper-plane me-2"></i> ENVOYER UN SMS AU CLIENT';
    } else {
        // SMS désactivé
        button.classList.remove('btn-success');
        button.classList.add('btn-danger');
        button.innerHTML = '<i class="fas fa-ban me-2"></i> NE PAS ENVOYER DE SMS AU CLIENT';
    }
}

// Fonction pour jouer un son de notification
function playNotificationSound() {
    try {
        const audio = new Audio('assets/sounds/click.mp3');
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Impossible de jouer le son:', e));
    } catch (e) {
        console.log('Erreur lors de la lecture du son:', e);
    }
}

// Ajouter ces fonctions à l'objet global pour les rendre accessibles
window.smsToggle = {
    init: initSmsToggleButton,
    updateAppearance: updateSmsButtonAppearance,
    getSmsStatus: function() {
        return document.getElementById('sendSmsSwitch')?.value === '1';
    },
    setSmsStatus: function(status) {
        const smsSwitch = document.getElementById('sendSmsSwitch');
        const toggleButton = document.getElementById('smsToggleButton');
        if (smsSwitch && toggleButton) {
            smsSwitch.value = status ? '1' : '0';
            updateSmsButtonAppearance(toggleButton, status);
        }
    }
};

// Mise à jour de la fonction updateCommande pour inclure l'état du SMS
function updateCommande() {
    // Récupérer l'ID de la commande
    const id = document.getElementById('edit_id').value;
    if (!id) {
        alert('Erreur: ID de commande manquant');
        return;
    }
    
    // Récupérer toutes les valeurs du formulaire
    const formData = new FormData(document.getElementById('editCommandeForm'));
    
    // Si disponible, récupérer également l'état du SMS
    const smsSwitch = document.getElementById('sendSmsSwitch');
    if (smsSwitch) {
        formData.append('send_sms', smsSwitch.value);
        console.log('Mise à jour de la commande avec statut SMS:', smsSwitch.value === '1' ? 'Envoyer' : 'Ne pas envoyer');
    }
    
    // Afficher un indicateur de chargement
    const saveButton = document.querySelector('.modal-footer .btn-primary');
    const originalContent = saveButton.innerHTML;
    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enregistrement...';
    saveButton.disabled = true;
    
    // Envoyer les données au serveur
    fetch('ajax/update_commande.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Restaurer le bouton
        saveButton.innerHTML = originalContent;
        saveButton.disabled = false;
        
        if (data.success) {
            // Fermer le modal
            bootstrap.Modal.getInstance(document.getElementById('editCommandeModal')).hide();
            
            // Afficher un message de succès
            showNotification('Commande mise à jour avec succès', 'success');
            
            // Rafraîchir la page pour afficher les modifications
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            // Afficher un message d'erreur
            showNotification('Erreur lors de la mise à jour de la commande: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        // Restaurer le bouton
        saveButton.innerHTML = originalContent;
        saveButton.disabled = false;
        
        // Afficher un message d'erreur
        console.error('Erreur:', error);
        showNotification('Erreur de communication avec le serveur', 'danger');
    });
}

// Mise à jour de la fonction pour initialiser tous les boutons toggle SMS après le chargement du DOM
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser tous les boutons SMS
    initAllSmsToggleButtons();
    
    // Autres initialisations existantes...
});

// Fonction pour initialiser tous les boutons toggle SMS sur la page
function initAllSmsToggleButtons() {
    // Liste des paires bouton/switch à initialiser
    const smsButtons = [
        { button: 'smsToggleButton', switch: 'sendSmsSwitch' },
        { button: 'smsToggleButtonStatus', switch: 'sendSmsSwitchStatus' },
        { button: 'smsToggleButtonAjout', switch: 'sendSmsSwitchAjout' }
    ];
    
    // Initialiser chaque bouton s'il existe
    smsButtons.forEach(pair => {
        const toggleButton = document.getElementById(pair.button);
        const smsSwitch = document.getElementById(pair.switch);
        
        if (toggleButton && smsSwitch) {
            console.log(`Initialisation du bouton SMS: ${pair.button}`);
</script>