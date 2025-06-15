<?php
// Récupérer les statistiques
$shop_pdo = getShopDBConnection();
try {
    // Nombre total de réparations
    $stmt = $shop_pdo->query("SELECT COUNT(*) FROM reparations");
    $total_reparations = $stmt->fetchColumn();

    // Réparations en cours
    $stmt = $shop_pdo->query("SELECT COUNT(*) FROM reparations WHERE statut IN ('en_cours_diagnostique', 'en_cours_intervention')");
    $reparations_en_cours = $stmt->fetchColumn();

    // Réparations en attente
    $stmt = $shop_pdo->query("SELECT COUNT(*) FROM reparations WHERE statut IN ('en_attente_accord_client', 'en_attente_livraison', 'en_attente_responsable')");
    $reparations_en_attente = $stmt->fetchColumn();

    // Réparations terminées ce mois
    $stmt = $shop_pdo->query("SELECT COUNT(*) FROM reparations WHERE statut IN ('termine', 'livre') AND MONTH(date_reception) = MONTH(CURRENT_DATE())");
    $reparations_terminees_mois = $stmt->fetchColumn();

    // Chiffre d'affaires du mois
    $stmt = $shop_pdo->query("SELECT COALESCE(SUM(prix_reparation), 0) FROM reparations WHERE statut IN ('termine', 'livre') AND MONTH(date_reception) = MONTH(CURRENT_DATE())");
    $chiffre_affaires_mois = $stmt->fetchColumn();

    // Réparations par type d'appareil
    $stmt = $shop_pdo->query("
        SELECT type_appareil, COUNT(*) as count 
        FROM reparations 
        GROUP BY type_appareil 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $reparations_par_type = $stmt->fetchAll();

    // Réparations par statut
    $stmt = $shop_pdo->query("
        SELECT 
            CASE 
                WHEN statut IN ('termine', 'livre') THEN 'Terminé'
                WHEN statut IN ('annule', 'refuse') THEN 'Annulé'
                WHEN statut IN ('en_cours_diagnostique', 'en_cours_intervention') THEN 'En cours'
                WHEN statut IN ('en_attente_accord_client', 'en_attente_livraison', 'en_attente_responsable') THEN 'En attente'
                ELSE 'Nouvelle'
            END as statut_affichage,
            COUNT(*) as count
        FROM reparations 
        GROUP BY statut_affichage
    ");
    $reparations_par_statut = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des statistiques : " . $e->getMessage());
    $total_reparations = 0;
    $reparations_en_cours = 0;
    $reparations_en_attente = 0;
    $reparations_terminees_mois = 0;
    $chiffre_affaires_mois = 0;
    $reparations_par_type = [];
    $reparations_par_statut = [];
}
?>

<!-- En-tête du tableau de bord -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-0">Tableau de bord</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Accueil</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-light" id="toggleDarkMode">
            <i class="fas fa-moon"></i>
        </button>
        <a href="index.php?page=ajouter_reparation" class="btn btn-primary d-none d-md-flex align-items-center">
            <i class="fas fa-plus-circle me-2"></i>Nouvelle réparation
        </a>
    </div>
</div>

<!-- Cartes de statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-initial rounded bg-primary bg-opacity-10 text-primary me-3">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">Total réparations</h6>
                        <h3 class="card-title mb-0"><?php echo $total_reparations; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-initial rounded bg-warning bg-opacity-10 text-warning me-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">En attente</h6>
                        <h3 class="card-title mb-0"><?php echo $reparations_en_attente; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-initial rounded bg-info bg-opacity-10 text-info me-3">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">En cours</h6>
                        <h3 class="card-title mb-0"><?php echo $reparations_en_cours; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-initial rounded bg-success bg-opacity-10 text-success me-3">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">Terminées ce mois</h6>
                        <h3 class="card-title mb-0"><?php echo $reparations_terminees_mois; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques et tableaux -->
<div class="row g-4">
    <!-- Graphique des réparations par type -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Réparations par type d'appareil</h5>
            </div>
            <div class="card-body">
                <canvas id="reparationsParTypeChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Graphique des réparations par statut -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Réparations par statut</h5>
            </div>
            <div class="card-body">
                <canvas id="reparationsParStatutChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Dernières réparations -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Dernières réparations</h5>
                <a href="index.php?page=reparations" class="btn btn-sm btn-primary">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="d-none d-md-table-cell ps-3">#</th>
                                <th>Client</th>
                                <th class="d-none d-md-table-cell">Appareil</th>
                                <th class="d-none d-lg-table-cell">Problème</th>
                                <th class="d-none d-md-table-cell">Date</th>
                                <th>Statut</th>
                                <th class="d-none d-lg-table-cell">Prix</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $shop_pdo->query("
                                    SELECT r.*, c.nom as client_nom, c.prenom as client_prenom
                                    FROM reparations r
                                    JOIN clients c ON r.client_id = c.id
                                    ORDER BY r.date_reception DESC
                                    LIMIT 5
                                ");
                                $dernieres_reparations = $stmt->fetchAll();
                                
                                if (!empty($dernieres_reparations)): 
                                    foreach ($dernieres_reparations as $reparation): ?>
                                        <tr>
                                            <td class="d-none d-md-table-cell ps-3"><?php echo $reparation['id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-initial rounded bg-light text-primary me-2">
                                                        <?php echo strtoupper(substr($reparation['client_nom'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></div>
                                                        <div class="text-muted small d-md-none">
                                                            <?php echo htmlspecialchars($reparation['type_appareil']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    $icon = match($reparation['type_appareil']) {
                                                        'Téléphone' => 'fa-mobile-alt',
                                                        'Ordinateur' => 'fa-laptop',
                                                        'Tablette' => 'fa-tablet-alt',
                                                        'Trottinette' => 'fa-bolt',
                                                        default => 'fa-question'
                                                    };
                                                    ?>
                                                    <i class="fas <?php echo $icon; ?> me-2"></i>
                                                    <?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['modele']); ?>
                                                </div>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <div class="text-truncate" style="max-width: 200px;" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($reparation['description_probleme']); ?>">
                                                    <?php echo htmlspecialchars($reparation['description_probleme']); ?>
                                                </div>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <div class="d-flex align-items-center">
                                                    <i class="far fa-calendar me-2"></i>
                                                    <?php echo format_date($reparation['date_reception']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo get_status_badge($reparation['statut']); ?>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                <?php if (!empty($reparation['prix_reparation'])): ?>
                                                    <div class="fw-medium"><?php echo number_format($reparation['prix_reparation'], 2, ',', ' '); ?> €</div>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-3">
                                                <div class="btn-group">
                                                    <button type="button" 
                                                            class="btn btn-sm btn-light" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modifierReparationModal<?php echo $reparation['id']; ?>"
                                                            data-bs-tooltip="tooltip"
                                                            title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                                <p>Aucune réparation trouvée</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif;
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="8" class="text-center text-danger">Erreur lors de la récupération des réparations</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script pour les graphiques -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données pour le graphique des réparations par type
    const reparationsParTypeData = {
        labels: <?php echo json_encode(array_column($reparations_par_type, 'type_appareil')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($reparations_par_type, 'count')); ?>,
            backgroundColor: [
                'rgba(79, 70, 229, 0.2)',
                'rgba(59, 130, 246, 0.2)',
                'rgba(16, 185, 129, 0.2)',
                'rgba(245, 158, 11, 0.2)',
                'rgba(239, 68, 68, 0.2)'
            ],
            borderColor: [
                'rgb(79, 70, 229)',
                'rgb(59, 130, 246)',
                'rgb(16, 185, 129)',
                'rgb(245, 158, 11)',
                'rgb(239, 68, 68)'
            ],
            borderWidth: 1
        }]
    };

    // Données pour le graphique des réparations par statut
    const reparationsParStatutData = {
        labels: <?php echo json_encode(array_column($reparations_par_statut, 'statut_affichage')); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column($reparations_par_statut, 'count')); ?>,
            backgroundColor: [
                'rgba(16, 185, 129, 0.2)',
                'rgba(239, 68, 68, 0.2)',
                'rgba(59, 130, 246, 0.2)',
                'rgba(245, 158, 11, 0.2)',
                'rgba(79, 70, 229, 0.2)'
            ],
            borderColor: [
                'rgb(16, 185, 129)',
                'rgb(239, 68, 68)',
                'rgb(59, 130, 246)',
                'rgb(245, 158, 11)',
                'rgb(79, 70, 229)'
            ],
            borderWidth: 1
        }]
    };

    // Options communes pour les graphiques
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    };

    // Créer le graphique des réparations par type
    const reparationsParTypeCtx = document.getElementById('reparationsParTypeChart').getContext('2d');
    new Chart(reparationsParTypeCtx, {
        type: 'doughnut',
        data: reparationsParTypeData,
        options: chartOptions
    });

    // Créer le graphique des réparations par statut
    const reparationsParStatutCtx = document.getElementById('reparationsParStatutChart').getContext('2d');
    new Chart(reparationsParStatutCtx, {
        type: 'doughnut',
        data: reparationsParStatutData,
        options: chartOptions
    });
});
</script> 