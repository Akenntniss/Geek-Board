<?php
// Vérifier si l'utilisateur est administrateur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    set_message("Vous n'avez pas les droits nécessaires pour accéder à cette page.", "danger");
    redirect("accueil");
    exit;
}

// Récupérer la configuration actuelle du programme
$shop_pdo = getShopDBConnection();
try {
    $stmt = $shop_pdo->query("SELECT * FROM parrainage_config ORDER BY id DESC LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        set_message("La configuration du programme de parrainage n'est pas trouvée. Veuillez exécuter le script d'installation.", "danger");
        redirect("accueil");
        exit;
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération de la configuration: " . $e->getMessage(), "danger");
    redirect("accueil");
    exit;
}

// Traitement du formulaire de mise à jour de la configuration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_config') {
    try {
        $nombre_filleuls_requis = (int)$_POST['nombre_filleuls_requis'];
        $seuil_reduction = (float)$_POST['seuil_reduction'];
        $reduction_min = (int)$_POST['reduction_min'];
        $reduction_max = (int)$_POST['reduction_max'];
        $actif = isset($_POST['actif']) ? 1 : 0;
        
        // Valider les données
        if ($nombre_filleuls_requis < 1) {
            $nombre_filleuls_requis = 1;
        }
        
        if ($seuil_reduction <= 0) {
            $seuil_reduction = 1;
        }
        
        if ($reduction_min < 0 || $reduction_min > 100) {
            $reduction_min = 10;
        }
        
        if ($reduction_max < 0 || $reduction_max > 100 || $reduction_max < $reduction_min) {
            $reduction_max = max($reduction_min, 30);
        }
        
        // Mettre à jour la configuration
        $stmt = $shop_pdo->prepare("
            UPDATE parrainage_config SET 
            nombre_filleuls_requis = ?,
            seuil_reduction_pourcentage = ?,
            reduction_min_pourcentage = ?,
            reduction_max_pourcentage = ?,
            actif = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $nombre_filleuls_requis,
            $seuil_reduction,
            $reduction_min,
            $reduction_max,
            $actif,
            $config['id']
        ]);
        
        set_message("La configuration a été mise à jour avec succès.", "success");
        redirect("gestion_parrainage");
        exit;
    } catch (PDOException $e) {
        set_message("Erreur lors de la mise à jour de la configuration: " . $e->getMessage(), "danger");
    }
}

// Récupérer les statistiques du programme
try {
    // Nombre de clients inscrits au programme
    $stmt_inscrits = $shop_pdo->query("SELECT COUNT(*) as total FROM clients WHERE inscrit_parrainage = 1");
    $inscrits = $stmt_inscrits->fetch(PDO::FETCH_ASSOC);
    $total_inscrits = $inscrits['total'] ?? 0;
    
    // Nombre de relations de parrainage
    $stmt_relations = $shop_pdo->query("SELECT COUNT(*) as total FROM parrainage_relations");
    $relations = $stmt_relations->fetch(PDO::FETCH_ASSOC);
    $total_relations = $relations['total'] ?? 0;
    
    // Nombre de réductions générées
    $stmt_reductions = $shop_pdo->query("SELECT COUNT(*) as total FROM parrainage_reductions");
    $reductions = $stmt_reductions->fetch(PDO::FETCH_ASSOC);
    $total_reductions = $reductions['total'] ?? 0;
    
    // Nombre de réductions utilisées
    $stmt_utilisees = $shop_pdo->query("SELECT COUNT(*) as total FROM parrainage_reductions WHERE utilise = 1");
    $utilisees = $stmt_utilisees->fetch(PDO::FETCH_ASSOC);
    $total_utilisees = $utilisees['total'] ?? 0;
    
    // Montant total des réductions générées
    $stmt_montant = $shop_pdo->query("SELECT SUM(montant_reduction_max) as total FROM parrainage_reductions WHERE utilise = 1");
    $montant = $stmt_montant->fetch(PDO::FETCH_ASSOC);
    $total_montant = $montant['total'] ?? 0;
    
    // Top 5 des parrains avec le plus de filleuls
    $stmt_top_parrains = $shop_pdo->query("
        SELECT c.id, c.nom, c.prenom, COUNT(pr.id) as nb_filleuls
        FROM clients c
        JOIN parrainage_relations pr ON c.id = pr.parrain_id
        GROUP BY c.id
        ORDER BY nb_filleuls DESC
        LIMIT 5
    ");
    $top_parrains = $stmt_top_parrains->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gestion du Programme de Parrainage</h1>
    </div>

    <div class="row">
        <!-- Configuration actuelle -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Configuration actuelle</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Actions :</div>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#configModal">
                                <i class="fas fa-edit fa-sm fa-fw me-2 text-gray-400"></i>
                                Modifier la configuration
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group row">
                        <label class="col-sm-6 col-form-label">Statut du programme :</label>
                        <div class="col-sm-6">
                            <?php if ($config['actif']): ?>
                                <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactif</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-6 col-form-label">Nombre de filleuls requis :</label>
                        <div class="col-sm-6">
                            <p class="form-control-static"><?php echo $config['nombre_filleuls_requis']; ?></p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-6 col-form-label">Seuil pour réduction maximale :</label>
                        <div class="col-sm-6">
                            <p class="form-control-static"><?php echo number_format($config['seuil_reduction_pourcentage'], 2, ',', ' '); ?> €</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-6 col-form-label">Réduction minimum :</label>
                        <div class="col-sm-6">
                            <p class="form-control-static"><?php echo $config['reduction_min_pourcentage']; ?> %</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-6 col-form-label">Réduction maximum :</label>
                        <div class="col-sm-6">
                            <p class="form-control-static"><?php echo $config['reduction_max_pourcentage']; ?> %</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques du programme -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Statistiques du programme</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Clients Inscrits</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_inscrits; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Relations de Parrainage</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_relations; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-link fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Réductions Utilisées</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_utilisees; ?> / <?php echo $total_reductions; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-percent fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Montant Total Réduit</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_montant, 2, ',', ' '); ?> €</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top parrains -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top 5 des parrains</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($top_parrains)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Nombre de filleuls</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_parrains as $parrain): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($parrain['prenom'] . ' ' . $parrain['nom']); ?></td>
                                            <td><?php echo $parrain['nb_filleuls']; ?></td>
                                            <td>
                                                <a href="index.php?page=details_parrain&id=<?php echo $parrain['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">Aucun parrain n'a encore recruté de filleuls.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Dernières réductions -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Dernières réductions utilisées</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Récupérer les dernières réductions utilisées
                    try {
                        $stmt_dernières_reductions = $shop_pdo->query("
                            SELECT pr.*, c.nom, c.prenom, r.id as reparation_id
                            FROM parrainage_reductions pr
                            JOIN clients c ON pr.parrain_id = c.id
                            LEFT JOIN reparations r ON pr.reparation_utilisee_id = r.id
                            WHERE pr.utilise = 1
                            ORDER BY pr.date_utilisation DESC
                            LIMIT 5
                        ");
                        $dernières_reductions = $stmt_dernières_reductions->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        error_log("Erreur lors de la récupération des dernières réductions: " . $e->getMessage());
                        $dernières_reductions = [];
                    }
                    ?>

                    <?php if (!empty($dernières_reductions)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Parrain</th>
                                        <th>Réduction</th>
                                        <th>Montant</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dernières_reductions as $reduction): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reduction['prenom'] . ' ' . $reduction['nom']); ?></td>
                                            <td><?php echo $reduction['pourcentage_reduction']; ?> %</td>
                                            <td><?php echo number_format($reduction['montant_reduction_max'], 2, ',', ' '); ?> €</td>
                                            <td><?php echo format_date($reduction['date_utilisation']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-percentage fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">Aucune réduction n'a encore été utilisée.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de modification de la configuration -->
<div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="configModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="configModalLabel">Modifier la configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=gestion_parrainage" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_config">
                    
                    <div class="form-group mb-3">
                        <label for="nombre_filleuls_requis">Nombre de filleuls requis</label>
                        <input type="number" class="form-control" id="nombre_filleuls_requis" name="nombre_filleuls_requis" min="1" value="<?php echo $config['nombre_filleuls_requis']; ?>" required>
                        <small class="form-text text-muted">Nombre minimum de filleuls nécessaires pour bénéficier des réductions.</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="seuil_reduction">Seuil pour réduction maximale (€)</label>
                        <input type="number" step="0.01" class="form-control" id="seuil_reduction" name="seuil_reduction" min="0.01" value="<?php echo $config['seuil_reduction_pourcentage']; ?>" required>
                        <small class="form-text text-muted">Montant à partir duquel la réduction maximale est appliquée.</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="reduction_min">Réduction minimum (%)</label>
                        <input type="number" class="form-control" id="reduction_min" name="reduction_min" min="0" max="100" value="<?php echo $config['reduction_min_pourcentage']; ?>" required>
                        <small class="form-text text-muted">Pourcentage de réduction pour les montants inférieurs au seuil.</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="reduction_max">Réduction maximum (%)</label>
                        <input type="number" class="form-control" id="reduction_max" name="reduction_max" min="0" max="100" value="<?php echo $config['reduction_max_pourcentage']; ?>" required>
                        <small class="form-text text-muted">Pourcentage de réduction pour les montants supérieurs ou égaux au seuil.</small>
                    </div>
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="actif" name="actif" <?php echo $config['actif'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="actif">Activer le programme</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div> 