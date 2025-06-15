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
                                <tr onclick="window.location.href='index.php?page=reparations&open_modal=<?php echo $reparation['id']; ?>'" style="cursor: pointer;" class="table-row-hover">
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
                                        <span class="status-badge status-badge-<?php echo $status_class; ?>">
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
<script src="assets/js/taches.js"></script>
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