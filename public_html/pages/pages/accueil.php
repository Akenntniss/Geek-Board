<?php
// Fonction pour obtenir la couleur en fonction de la priorité
function get_priority_color($priority) {
    switch(strtolower($priority)) {
        case 'haute':
            return 'danger';
        case 'moyenne':
            return 'warning';
        case 'basse':
            return 'info';
        default:
            return 'secondary';
    }
}

// Récupérer les statistiques pour le tableau de bord
$reparations_stats_categorie = get_reparations_count_by_status_categorie();
$reparations_en_attente = $reparations_stats_categorie['en_attente'];
$reparations_en_cours = $reparations_stats_categorie['en_cours'];
$reparations_nouvelles = $reparations_stats_categorie['nouvelles'];
$reparations_actives = count_active_reparations();

$total_clients = get_total_clients();
$taches_recentes_count = get_taches_recentes_count();
$reparations_recentes = get_recent_reparations(5);
$reparations_recentes_count = count_recent_reparations();
$taches = get_taches_en_cours(5);

// Récupérer les commandes récentes
$commandes_recentes = [];
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("
        SELECT c.*, cl.nom as client_nom, cl.prenom as client_prenom, f.nom as fournisseur_nom 
        FROM commandes_pieces c 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        LEFT JOIN fournisseurs f ON c.fournisseur_id = f.id 
        WHERE c.statut IN ('en_attente', 'urgent')
        ORDER BY c.date_creation DESC 
        LIMIT 5
    ");
    $commandes_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    // Gérer l'erreur silencieusement
    error_log("Erreur lors de la récupération des commandes récentes: " . $e->getMessage());
}
?>

<!-- Styles spécifiques pour le tableau de bord -->
<link href="assets/css/dashboard-new.css" rel="stylesheet">

<!-- Correction pour tableaux côte à côte -->
<style>
.dashboard-tables-container {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 1.5rem !important;
    margin-top: 2rem !important;
    margin-bottom: 2rem !important;
    width: 100% !important;
}

.table-section {
    display: flex !important;
    flex-direction: column !important;
    background: #fff !important;
    border-radius: 10px !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
    padding: 1rem !important;
    height: 100% !important;
}

@media (max-width: 1400px) {
    .dashboard-tables-container {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 992px) {
    .dashboard-tables-container {
        grid-template-columns: 1fr !important;
    }
    
    /* Masquer certaines colonnes sur les écrans moyens et mobiles */
    .hide-md {
        display: none !important;
    }
}

@media (max-width: 768px) {
    /* Masquer les colonnes additionnelles sur mobile */
    .hide-sm {
        display: none !important;
    }
}

.order-date, .order-quantity, .order-price {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
}

.order-price {
    font-weight: 600;
    color: #4361ee;
}

.tabs-header .badge {
    font-size: 0.75rem;
    padding: 3px 6px;
    border-radius: 10px;
}

/* Style pour les boutons d'onglets */
.tab-button {
    padding: 10px 20px;
    border: none;
    background: none;
    cursor: pointer;
    transition: all 0.3s ease;
    border-bottom: 2px solid transparent;
}

.tab-button.active {
    color: #4361ee;
    border-bottom: 2px solid #4361ee;
    background-color: rgba(67, 97, 238, 0.1);
}

.tab-button:hover {
    background-color: rgba(67, 97, 238, 0.05);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Styles pour les badges de statut */
.status-badge {
    display: inline-block;
    padding: 0.25em 0.5em;
    font-size: 0.7rem;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 20px;
    color: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s;
    letter-spacing: 0.01em;
    text-transform: uppercase;
    background-image: linear-gradient(to bottom, rgba(255,255,255,0.15), rgba(0,0,0,0.05));
}

.status-badge-primary {
    background-color: #0d6efd;
}

.status-badge-success {
    background-color: #28a745;
}

.status-badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.status-badge-danger {
    background-color: #dc3545;
}

.status-badge-info {
    background-color: #17a2b8;
}

.status-badge-secondary {
    background-color: #6c757d;
}
</style>

<div class="modern-dashboard">
    <!-- Actions rapides -->
    <?php include 'components/quick-actions.php'; ?>

    <!-- État des réparations -->
    <div class="statistics-container">
        <h3 class="section-title">État des réparations</h3>
        <div class="statistics-grid">
            <a href="index.php?page=reparations&statut_ids=1,2,3" class="stat-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $reparations_actives; ?></div>
                    <div class="stat-label">Réparation</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=taches" class="stat-card progress-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $taches_recentes_count; ?></div>
                    <div class="stat-label">Tâche</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=commandes_pieces" class="stat-card waiting-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $reparations_en_attente; ?></div>
                    <div class="stat-label">Commande</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
            <a href="index.php?page=reparations&urgence=1" class="stat-card clients-card" style="text-decoration: none; color: inherit;">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $reparations_en_cours; ?></div>
                    <div class="stat-label">Urgence</div>
                </div>
                <div class="stat-link">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Tableaux côte à côte -->
    <div class="dashboard-tables-container">
        <!-- Tâches en cours avec onglets -->
        <div class="table-section">
            <div class="table-section-header">
                <h4 class="table-section-title">
                    <i class="fas fa-tasks"></i>
                    <a href="index.php?page=taches" style="text-decoration: none; color: inherit;">
                        Tâches en cours
                        <span class="badge bg-primary ms-2"><?php echo $taches_recentes_count; ?></span>
                    </a>
                </h4>
                <div class="tabs">
                    <button class="tab-button active" data-tab="toutes-taches">Toutes les tâches</button>
                    <button class="tab-button" data-tab="mes-taches">Mes tâches</button>
                </div>
            </div>
            <div class="table-container">
                <div class="tab-content active" id="toutes-taches">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Priorité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $toutes_taches = get_toutes_taches_en_cours(10);
                            if (!empty($toutes_taches)) :
                                foreach ($toutes_taches as $tache) :
                                    $urgence_class = get_urgence_class($tache['urgence']);
                            ?>
                                <tr class="table-row-hover" data-task-id="<?php echo $tache['id']; ?>" style="cursor: pointer;" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                    <td><?php echo htmlspecialchars($tache['titre']); ?></td>
                                    <td><span class="badge <?php echo $urgence_class; ?>"><?php echo htmlspecialchars($tache['urgence']); ?></span></td>
                                </tr>
                            <?php
                                endforeach;
                            else :
                            ?>
                                <tr>
                                    <td colspan="2" class="text-center">Aucune tâche en cours</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="tab-content" id="mes-taches">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Priorité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $mes_taches = get_taches_en_cours(10);
                            if (!empty($mes_taches)) :
                                foreach ($mes_taches as $tache) :
                                    $urgence_class = get_urgence_class($tache['urgence']);
                            ?>
                                <tr class="table-row-hover" data-task-id="<?php echo $tache['id']; ?>" style="cursor: pointer;" onclick="afficherDetailsTache(event, <?php echo $tache['id']; ?>)">
                                    <td><?php echo htmlspecialchars($tache['titre']); ?></td>
                                    <td><span class="badge <?php echo $urgence_class; ?>"><?php echo htmlspecialchars($tache['urgence']); ?></span></td>
                                </tr>
                            <?php
                                endforeach;
                            else :
                            ?>
                                <tr>
                                    <td colspan="2" class="text-center">Aucune tâche en cours</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Réparations récentes -->
        <div class="table-section">
            <div class="table-section-header">
                <h4 class="table-section-title">
                    <i class="fas fa-wrench"></i>
                    <a href="index.php?page=reparations" style="text-decoration: none; color: inherit;">
                        Réparations récentes
                        <span class="badge bg-primary ms-2"><?php echo $reparations_recentes_count; ?></span>
                    </a>
                </h4>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Modèle</th>
                            <th class="hide-md hide-sm">Date de réception</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($reparations_recentes) > 0): ?>
                            <?php foreach ($reparations_recentes as $reparation): ?>
                                <tr onclick="window.location.href='index.php?page=statut_rapide&id=<?php echo $reparation['id']; ?>'" style="cursor: pointer;" class="table-row-hover">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user me-2 text-primary"></i>
                                            <?php echo htmlspecialchars($reparation['client_nom'] ?? 'N/A'); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($reparation['modele'] ?? ''); ?></td>
                                    <td class="hide-md hide-sm"><?php echo format_date($reparation['date_reception'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Aucune réparation récente</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Commandes récentes -->
        <div class="table-section">
            <div class="table-section-header">
                <h4 class="table-section-title">
                    <i class="fas fa-shopping-cart"></i>
                    <a href="index.php?page=commandes_pieces" style="text-decoration: none; color: inherit;">
                        Commandes à traiter
                    </a>
                </h4>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pièce</th>
                            <th>Statut</th>
                            <th class="hide-md hide-sm">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($commandes_recentes) > 0): ?>
                            <?php foreach ($commandes_recentes as $commande): ?>
                                <tr class="table-row-hover">
                                    <td title="<?php echo htmlspecialchars($commande['nom_piece']); ?>">
                                        <?php echo mb_strimwidth(htmlspecialchars($commande['nom_piece']), 0, 30, "..."); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch($commande['statut']) {
                                            case 'en_attente':
                                                $status_class = 'warning';
                                                $status_text = 'En attente';
                                                break;
                                            case 'commande':
                                                $status_class = 'primary';
                                                $status_text = 'Commandé';
                                                break;
                                            case 'recue':
                                                $status_class = 'success';
                                                $status_text = 'Reçu';
                                                break;
                                            case 'urgent':
                                                $status_class = 'danger';
                                                $status_text = 'URGENT';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge status-badge-<?php echo $status_class; ?>" 
                                              data-commande-id="<?php echo $commande['id']; ?>" 
                                              data-status="<?php echo $commande['statut']; ?>"
                                              style="cursor: pointer;">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td class="hide-md hide-sm"><?php echo format_date($commande['date_creation']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center">Aucune commande récente</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Styles spécifiques pour le modal de recherche client -->
<style>
.avatar-lg {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.client-nom {
    font-size: 1.5rem;
    font-weight: 600;
}

.client-telephone {
    font-size: 1rem;
}

#clientHistoryTabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--gray);
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    background: transparent;
}

#clientHistoryTabs .nav-link.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: transparent;
}

#clientHistoryTabs .nav-link:hover:not(.active) {
    border-bottom-color: #e9ecef;
}
</style>

<!-- Modal de recherche client -->
<div class="modal fade" id="searchClientModal" tabindex="-1" aria-labelledby="searchClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchClientModalLabel">Rechercher un client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="search-container">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="clientSearchInput" placeholder="Nom, téléphone ou email">
                        </div>
                    <div id="searchResults" class="search-results">
                        <!-- Résultats de recherche apparaîtront ici -->
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Indicateurs principaux -->
</div>

<!-- Inclure les scripts pour le dashboard -->
<script src="assets/js/dashboard-commands.js"></script>
<script src="assets/js/client-historique.js"></script>
<script src="assets/js/commandes.js"></script>
<script src="assets/js/taches.js"></script>
<script src="assets/js/status-badges.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
});
</script>

<!-- Modal pour changer le statut des commandes -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 futuristic-modal">
            <!-- En-tête du modal avec effet de dégradé -->
            <div class="modal-header position-relative py-3 border-bottom modal-header-futuristic">
                <div class="position-absolute start-0 top-0 w-100 d-flex justify-content-center">
                    <div class="status-progress-bar"></div>
                </div>
                <h5 class="modal-title d-flex align-items-center" id="statusModalLabel">
                    <i class="fas fa-exchange-alt me-2 text-primary"></i>
                    Changement de statut
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <!-- En-tête de contexte -->
                <div class="p-3 border-bottom border-secondary modal-context-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Commande <span id="commandeIdText" class="text-warning fw-bold"></span></h6>
                        </div>
                        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3">
                            <i class="fas fa-times me-1"></i> Annulé
                        </button>
                    </div>
                </div>

                <!-- Grille des options de statut -->
                <div class="p-4 futuristic-modal-body">
                    <p class="mb-4 modal-text">Sélectionnez le nouveau statut pour cette commande:</p>
                    
                    <div class="row g-3">
                        <!-- Option En attente -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 p-0 bg-transparent" data-status="en_attente">
                                <div class="card border-0 status-card h-100">
                                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-4">
                                        <div class="status-icon-wrapper bg-warning rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-clock fa-lg text-dark"></i>
                                        </div>
                                        <h5 class="card-title mb-1">En attente</h5>
                                        <p class="card-text status-description small">Pas encore commandé</p>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- Option Commandé -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 p-0 bg-transparent" data-status="commande">
                                <div class="card border-0 status-card h-100">
                                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-4">
                                        <div class="status-icon-wrapper bg-info rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-shopping-cart fa-lg text-white"></i>
                                        </div>
                                        <h5 class="card-title mb-1">Commandé</h5>
                                        <p class="card-text status-description small">Commande en cours</p>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- Option Reçu -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 p-0 bg-transparent" data-status="recue">
                                <div class="card border-0 status-card h-100">
                                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-4">
                                        <div class="status-icon-wrapper bg-success rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-box fa-lg text-white"></i>
                                        </div>
                                        <h5 class="card-title mb-1">Reçu</h5>
                                        <p class="card-text status-description small">Pièce réceptionnée</p>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- Option Utilisé -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 p-0 bg-transparent" data-status="utilise">
                                <div class="card border-0 status-card h-100">
                                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-4">
                                        <div class="status-icon-wrapper bg-primary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-check-double fa-lg text-white"></i>
                                        </div>
                                        <h5 class="card-title mb-1">Utilisé</h5>
                                        <p class="card-text status-description small">Pièce installée</p>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- Option À retourner -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 p-0 bg-transparent" data-status="a_retourner">
                                <div class="card border-0 status-card h-100">
                                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-4">
                                        <div class="status-icon-wrapper bg-secondary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-undo fa-lg text-white"></i>
                                        </div>
                                        <h5 class="card-title mb-1">À retourner</h5>
                                        <p class="card-text status-description small">Retour fournisseur</p>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- Option Annulé -->
                        <div class="col-md-6">
                            <button type="button" class="status-option-card w-100 h-100 border-0 rounded-3 p-0 bg-transparent" data-status="annulee">
                                <div class="card border-0 status-card h-100">
                                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-4">
                                        <div class="status-icon-wrapper bg-danger rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-times fa-lg text-white"></i>
                                        </div>
                                        <h5 class="card-title mb-1">Annulé</h5>
                                        <p class="card-text status-description small">Commande annulée</p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Inputs cachés pour stocker les valeurs -->
                <input type="hidden" id="commandeIdInput" value="">
                <input type="hidden" id="currentStatusInput" value="">
            </div>
            <div class="modal-footer border-top-0 d-flex justify-content-end py-3 futuristic-modal-footer">
                <button type="button" class="btn btn-modal-close btn-lg px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Style pour le modal de statut avec thème futuriste */
.futuristic-modal {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(30, 144, 255, 0.3);
    transition: all 0.3s ease;
    backdrop-filter: blur(16px);
    animation: modal-fade-in 0.3s ease-out forwards;
}

/* Mode jour */
:root:not(.dark-mode) .futuristic-modal {
    background-color: rgba(255, 255, 255, 0.95);
    color: #1f2937;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Mode nuit */
.dark-mode .futuristic-modal {
    background-color: #121826;
    color: #ffffff;
    border: 2px solid #3b82f6;
    box-shadow: 0 0 30px rgba(59, 130, 246, 0.5);
}

/* En-tête du modal entièrement revu */
.dark-mode .modal-header-futuristic {
    background-color: #212938; /* Gris-bleu plus clair qui correspond à l'image */
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
}

/* Style du titre dans l'en-tête */
.dark-mode .modal-header-futuristic .modal-title {
    font-size: 1.3rem;
    color: #4285F4; /* Bleu plus vif qui correspond au texte dans l'image */
    font-weight: 500;
    display: flex;
    align-items: center;
    letter-spacing: 0.5px;
}

/* Icône dans le titre */
.dark-mode #statusModal .modal-title i {
    color: #4285F4; /* Assorti au texte */
    margin-right: 0.75rem;
    font-size: 1.2rem;
}

/* Bouton de fermeture dans l'en-tête */
.dark-mode .btn-close-white {
    filter: brightness(1.5);
    opacity: 0.8;
}

/* En-tête de contexte */
.dark-mode .modal-context-header {
    background-color: #1e293b; /* Bleu foncé */
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1rem 1.5rem;
}

.dark-mode .modal-context-header h6 {
    color: #e2e8f0;
    font-size: 1.1rem;
}

.dark-mode #commandeIdText {
    color: #fbbf24;
    font-weight: bold;
    text-shadow: 0 0 5px rgba(251, 191, 36, 0.5);
}

/* Corps du modal */
.dark-mode .futuristic-modal-body {
    background-color: #1e293b;
    padding: 1.5rem !important;
}

.dark-mode .modal-text {
    color: #ffffff;
    font-weight: 500;
    font-size: 1.1rem;
    text-shadow: 0 0 5px rgba(59, 130, 246, 0.3);
}

/* Cartes de statut */
.dark-mode .status-card {
    background-color: #1e293b;
    border: 2px solid #3b82f6;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5);
}

.dark-mode .status-card .card-title {
    color: #ffffff;
    font-weight: 600;
    font-size: 1.2rem;
    text-shadow: 0 0 8px rgba(59, 130, 246, 0.8);
}

.dark-mode .status-description {
    color: #d1d5db;
    font-size: 0.95rem;
}

/* Mode nuit - icônes plus visibles */
.dark-mode .status-icon-wrapper {
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.6);
}

/* Options au survol */
.dark-mode .status-option-card:hover .status-card {
    background-color: #1e3a8a;
    border-color: #60a5fa;
    box-shadow: 0 0 25px rgba(59, 130, 246, 0.8);
}

.dark-mode .status-option-card:hover .status-card .card-title {
    color: #ffffff;
    text-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

.dark-mode .status-option-card:hover .status-description {
    color: #e5e7eb;
}

/* Bouton de fermeture */
.dark-mode .btn-modal-close {
    background-color: #374151; /* Gris foncé */
    color: white;
    font-weight: 500;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: none;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.dark-mode .btn-modal-close:hover {
    background-color: #4b5563; /* Gris légèrement plus clair */
    border-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.dark-mode .btn-modal-close i {
    margin-right: 0.5rem;
    font-size: 0.9rem;
}

/* Pied de page du modal */
.dark-mode .futuristic-modal-footer {
    background-color: #111827; /* Même couleur que l'en-tête */
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1rem 1.5rem;
}

/* Animation de chargement dans le bouton */
.status-option-card.loading .status-icon-wrapper {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
}

/* Mode nuit - amélioration des icônes */
.dark-mode .status-icon-wrapper {
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.dark-mode .status-icon-wrapper::after {
    background: linear-gradient(45deg, rgba(59, 130, 246, 0) 0%, rgba(59, 130, 246, 0.2) 50%, rgba(59, 130, 246, 0) 100%);
}

/* Ajout d'effets de particules pour le mode nuit */
.dark-mode .futuristic-modal::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.05) 0%, transparent 15%),
        radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.05) 0%, transparent 15%),
        radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.02) 0%, transparent 25%);
    pointer-events: none;
    z-index: -1;
}

/* Amélioration de l'animation pulse pour le mode nuit */
.dark-mode .status-option-card.loading .status-icon-wrapper {
    animation: pulse-blue 1.5s infinite;
}

@keyframes pulse-blue {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
        box-shadow: 0 4px 20px rgba(59, 130, 246, 0.5);
    }
}

/* Mode nuit - bouton Annulé au bas du modal */
.dark-mode #statusModal .status-option-card[data-status="annulee"] .status-icon-wrapper {
    background-color: #dc2626 !important;
    box-shadow: 0 0 20px rgba(220, 38, 38, 0.5);
}

.dark-mode #statusModal .status-option-card[data-status="annulee"]:hover .status-card {
    border-color: #ef4444;
    box-shadow: 0 0 25px rgba(220, 38, 38, 0.4);
}

/* Barre de progression en haut */
.dark-mode .status-progress-bar {
    display: none; /* Suppression de la barre de progression qui peut nuire à l'apparence */
}

/* Style pour le bouton "voir-toutes-taches" en mode nuit */
.dark-mode #voir-toutes-taches.btn-secondary {
    background-color: #3b82f6; /* Bleu plus vif */
    border-color: #2563eb;
    color: #ffffff !important;
    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
}

.dark-mode #voir-toutes-taches.btn-secondary:hover {
    background-color: #2563eb;
    border-color: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 6px 8px rgba(59, 130, 246, 0.4);
}

.dark-mode #voir-toutes-taches.btn-secondary:active {
    transform: translateY(0);
}
</style>

<!-- Modal pour afficher les détails d'une tâche -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="taskDetailsModalLabel">Détails de la tâche</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="task-detail-container">
                    <div class="mb-3">
                        <h5 id="task-title" class="fw-bold"></h5>
                        <div class="mt-2">
                            <span class="me-2">Priorité:</span>
                            <span id="task-priority" class="fw-medium"></span>
                        </div>
                        <div class="mt-3">
                            <span class="me-2 fw-medium">Description:</span>
                            <p id="task-description" class="mt-2 p-2 rounded">Chargement...</p>
                        </div>
                        <!-- Ajout d'une section pour afficher les erreurs de chargement -->
                        <div id="task-error-container" class="alert alert-danger mt-2" style="display:none;"></div>
                    </div>
                    
                    <div class="task-actions d-flex justify-content-between gap-2 mt-4">
                        <div class="d-flex gap-2">
                            <button id="start-task-btn" class="btn btn-primary" data-task-id="" data-status="en_cours">
                                <i class="fas fa-play me-2"></i>Démarrer
                            </button>
                            <button id="complete-task-btn" class="btn btn-success" data-task-id="" data-status="termine">
                                <i class="fas fa-check me-2"></i>Terminer
                            </button>
                        </div>
                        <a href="index.php?page=taches" id="voir-toutes-taches" class="btn btn-secondary">
                            <i class="fas fa-list-ul me-2"></i>Voir les détails
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
</div>
</div>