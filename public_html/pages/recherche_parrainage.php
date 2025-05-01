<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    set_message("Vous devez être connecté pour accéder à cette page.", "danger");
    redirect("login");
    exit;
}

// Variables pour la recherche
$search_term = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$parrains = [];

// Si une recherche est lancée
if (!empty($search_term)) {
    try {
        // Rechercher les clients inscrits au parrainage correspondant aux critères
        $stmt = $pdo->prepare("
            SELECT c.* 
            FROM clients c
            WHERE c.inscrit_parrainage = 1 
            AND (
                c.nom LIKE ? OR 
                c.prenom LIKE ? OR 
                c.telephone LIKE ? OR
                c.code_parrainage LIKE ?
            )
            ORDER BY c.nom, c.prenom
            LIMIT 50
        ");
        
        $search_param = '%' . $search_term . '%';
        $stmt->execute([$search_param, $search_param, $search_param, $search_param]);
        $parrains = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer des informations additionnelles pour chaque parrain
        foreach ($parrains as &$parrain) {
            // Nombre de filleuls
            $stmt_filleuls = $pdo->prepare("
                SELECT COUNT(*) as nb_filleuls 
                FROM parrainage_relations 
                WHERE parrain_id = ?
            ");
            $stmt_filleuls->execute([$parrain['id']]);
            $parrain['nb_filleuls'] = $stmt_filleuls->fetch(PDO::FETCH_ASSOC)['nb_filleuls'] ?? 0;
            
            // Nombre de réductions disponibles
            $stmt_reductions = $pdo->prepare("
                SELECT COUNT(*) as nb_reductions 
                FROM parrainage_reductions 
                WHERE parrain_id = ? AND utilise = 0
            ");
            $stmt_reductions->execute([$parrain['id']]);
            $parrain['nb_reductions'] = $stmt_reductions->fetch(PDO::FETCH_ASSOC)['nb_reductions'] ?? 0;
        }
        
    } catch (PDOException $e) {
        set_message("Erreur lors de la recherche: " . $e->getMessage(), "danger");
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Recherche de parrains</h1>
        <a href="index.php?page=gestion_parrainage" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50 me-1"></i> Retour au tableau de bord
        </a>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Rechercher un parrain</h6>
                </div>
                <div class="card-body">
                    <form action="index.php" method="get" class="mb-4">
                        <input type="hidden" name="page" value="recherche_parrainage">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" 
                                           value="<?php echo htmlspecialchars($search_term); ?>" 
                                           placeholder="Nom, prénom, téléphone ou code de parrainage...">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search me-1"></i> Rechercher
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    Recherchez parmi les clients inscrits au programme de parrainage.
                                </small>
                            </div>
                            <div class="col-md-4">
                                <a href="index.php?page=gestion_parrainage" class="btn btn-outline-secondary">
                                    <i class="fas fa-chart-bar me-1"></i> Tableau de bord
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (!empty($search_term)): ?>
                        <?php if (!empty($parrains)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="parrainsTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Téléphone</th>
                                            <th>Code de parrainage</th>
                                            <th>Filleuls</th>
                                            <th>Réductions disponibles</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($parrains as $parrain): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($parrain['prenom'] . ' ' . $parrain['nom']); ?></td>
                                                <td><?php echo htmlspecialchars($parrain['telephone']); ?></td>
                                                <td>
                                                    <?php if (!empty($parrain['code_parrainage'])): ?>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($parrain['code_parrainage']); ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Non défini</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $parrain['nb_filleuls']; ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($parrain['nb_reductions'] > 0): ?>
                                                        <span class="badge bg-success"><?php echo $parrain['nb_reductions']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">0</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="index.php?page=details_parrain&id=<?php echo $parrain['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye me-1"></i> Détails
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Aucun parrain trouvé avec ces critères de recherche.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">Veuillez saisir des critères de recherche pour trouver des parrains.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($parrains)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser DataTables
    if (document.getElementById('parrainsTable')) {
        $('#parrainsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
            },
            order: [[0, 'asc']]
        });
    }
});
</script>
<?php endif; ?> 