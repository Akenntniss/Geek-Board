<?php
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtenir la connexion à la base de données du magasin de l'utilisateur
$shop_pdo = getShopDBConnection();

// Récupérer et stocker l'ID du magasin actuel
$current_shop_id = $_SESSION['shop_id'] ?? null;
if (!$current_shop_id) {
    // Essayer de récupérer depuis l'URL
    $current_shop_id = $_GET['shop_id'] ?? null;
    if ($current_shop_id) {
        $_SESSION['shop_id'] = $current_shop_id;
    } else {
        error_log("ALERTE: ID du magasin non trouvé dans la session ou l'URL pour reparations.php");
    }
}

// Vérifier que $shop_pdo est accessible et initialisé
if (!isset($shop_pdo) || $shop_pdo === null) {
    echo "<div class='alert alert-danger'>Erreur de connexion à la base de données. La variable \$shop_pdo n'est pas disponible. Veuillez contacter l'administrateur.</div>";
    error_log("ERREUR CRITIQUE dans reparations.php: La variable \$shop_pdo n'est pas disponible");
    // Initialiser les variables pour éviter les erreurs
    $counters = [
        'total_reparations' => 0,
        'total_nouvelles' => 0,
        'total_en_cours' => 0,
        'total_en_attente' => 0,
        'total_termines' => 0,
        'total_archives' => 0
    ];
    $reparations = [];
} else {
    // Paramètres de filtrage
    $statut = isset($_GET['statut']) ? cleanInput($_GET['statut']) : '';
    $statut_ids = isset($_GET['statut_ids']) ? cleanInput($_GET['statut_ids']) : '1,2,3,4,5'; // Par défaut, afficher toutes les réparations actives
    $type_appareil = isset($_GET['type_appareil']) ? cleanInput($_GET['type_appareil']) : '';
    $date_debut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : '';
    $date_fin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : '';
    
    // OPTIMISATION MAJEURE : Combiner toutes les requêtes de comptage en une seule
    try {
        $count_sql = "
            SELECT 
                COUNT(*) as total_reparations,
                SUM(CASE WHEN s.id BETWEEN 1 AND 5 THEN 1 ELSE 0 END) as total_actives,
                SUM(CASE WHEN s.id IN (1,2,3) THEN 1 ELSE 0 END) as total_nouvelles,
                SUM(CASE WHEN s.id IN (4,5) THEN 1 ELSE 0 END) as total_en_cours,
                SUM(CASE WHEN s.id IN (6,7,8) THEN 1 ELSE 0 END) as total_en_attente,
                SUM(CASE WHEN s.id IN (9,10) THEN 1 ELSE 0 END) as total_termines,
                SUM(CASE WHEN s.id IN (11,12,13) THEN 1 ELSE 0 END) as total_archives
            FROM reparations r 
            LEFT JOIN statuts s ON r.statut = s.code
        ";
        
        $stmt = $shop_pdo->query($count_sql);
        $counters = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Extraire les valeurs pour compatibilité avec l'ancien code
        $total_reparations = $counters['total_actives'];
        $total_nouvelles = $counters['total_nouvelles'];
        $total_en_cours = $counters['total_en_cours'];
        $total_en_attente = $counters['total_en_attente'];
        $total_termines = $counters['total_termines'];
        $total_archives = $counters['total_archives'];

    } catch (PDOException $e) {
        error_log("Erreur lors du comptage des réparations : " . $e->getMessage());
        $total_reparations = 0;
        $total_nouvelles = 0;
        $total_en_cours = 0;
        $total_en_attente = 0;
        $total_termines = 0;
        $total_archives = 0;
    }
}

// Construction de la requête SQL optimisée avec filtres
$sql = "
    SELECT r.*, 
           c.nom as client_nom, 
           c.prenom as client_prenom, 
           c.telephone as client_telephone, 
           c.email as client_email,
           s.nom as statut_nom,
           s.couleur as statut_couleur
    FROM reparations r
    LEFT JOIN clients c ON r.client_id = c.id
    LEFT JOIN statuts s ON r.statut = s.code
    WHERE 1=1
";
$params = [];

// Ajouter la condition de recherche si présente
$is_searching = isset($_GET['search']) && !empty($_GET['search']);
if ($is_searching) {
    $search = cleanInput($_GET['search']);
    $sql .= " AND (
        c.nom LIKE ? OR 
        c.prenom LIKE ? OR 
        c.telephone LIKE ? OR 
        r.type_appareil LIKE ? OR 
        r.marque LIKE ? OR 
        r.modele LIKE ? OR 
        r.id LIKE ? OR
        r.description_probleme LIKE ?
    )";
    $search_param = "%$search%";
    $params = array_merge($params, [
        $search_param, $search_param, $search_param, $search_param,
        $search_param, $search_param, $search_param, $search_param
    ]);
}

// Filtre par ID de statut seulement si on n'est pas en mode recherche
if (!$is_searching) {
    if (!empty($statut_ids)) {
        if (strpos($statut_ids, ',') !== false) {
            $ids = explode(',', $statut_ids);
            $id_placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql .= " AND s.id IN ($id_placeholders)";
            $params = array_merge($params, $ids);
        } else {
            $sql .= " AND s.id = ?";
            $params[] = $statut_ids;
        }
    }
    else if (!empty($statut)) {
        if (strpos($statut, ',') !== false) {
            $statuts = explode(',', $statut);
            $statut_placeholders = implode(',', array_fill(0, count($statuts), '?'));
            $sql .= " AND r.statut IN ($statut_placeholders)";
            $params = array_merge($params, $statuts);
        } else {
            $sql .= " AND r.statut = ?";
            $params[] = $statut;
        }
    }
}

if (!empty($type_appareil)) {
    $sql .= " AND r.type_appareil = ?";
    $params[] = $type_appareil;
}

if (!empty($date_debut)) {
    $sql .= " AND r.date_reception >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND r.date_reception <= ?";
    $params[] = $date_fin . ' 23:59:59';
}

$sql .= " ORDER BY r.date_reception DESC";

// Récupérer les réparations
try {
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params);
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des réparations: " . $e->getMessage() . "</div>";
    error_log("Erreur SQL (reparations.php): " . $e->getMessage());
    $reparations = [];
}

// Traitement de la suppression (code original conservé)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // Vérification des droits administrateur
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        set_message("Vous n'avez pas les droits nécessaires pour supprimer une réparation.", "danger");
        redirect("reparations");
        exit;
    }

    $id = (int)$_GET['id'];
    try {
        $stmt = $shop_pdo->prepare("DELETE FROM reparations WHERE id = ?");
        $stmt->execute([$id]);
        
        set_message("Réparation supprimée avec succès.", "success");
    } catch (PDOException $e) {
        set_message("Erreur lors de la suppression de la réparation: " . $e->getMessage(), "danger");
    }
    redirect("reparations");
}

// Supprimer une réparation (administrateurs uniquement)
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        set_message("Vous n'avez pas les droits nécessaires pour effectuer cette action.", "danger");
        header('Location: index.php?page=reparations');
        exit();
    }
}
?>

<!-- Styles optimisés -->
<style>
    :root {
        --primary-color: #667eea;
        --secondary-color: #764ba2;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --info-color: #17a2b8;
        --light-color: #f8f9fa;
        --dark-color: #343a40;
        --border-radius: 0.5rem;
        --transition: all 0.3s ease;
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Optimisation des performances : Réduire les reflows et repaints */
    * {
        box-sizing: border-box;
    }

    body {
        background: #f1f5f9;
        background-image: linear-gradient(135deg, #e2e8f0, #cbd5e1, #e2e8f0);
        background-attachment: fixed;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        overflow-x: hidden;
    }

    .dark-mode body {
        background: #0a0f19;
        background-image: linear-gradient(135deg, #0a0f19, #111827, #0f172a);
        color: #e2e8f0;
    }

    .page-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 1rem;
    }

    .card {
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        transition: var(--transition);
        will-change: transform;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .table-container {
        background: white;
        border-radius: var(--border-radius);
        padding: 1rem;
        box-shadow: var(--shadow);
        max-height: 80vh;
        overflow-y: auto;
    }

    .table {
        margin-bottom: 0;
    }

    .table th {
        background: var(--light-color);
        border-top: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.05em;
        padding: 1rem 0.75rem;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table td {
        padding: 0.75rem;
        vertical-align: middle;
        border-top: 1px solid #f1f1f1;
    }

    .table tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.05);
        transform: translateX(2px);
        transition: var(--transition);
    }

    .btn-group {
        display: flex;
        gap: 0.25rem;
        flex-wrap: wrap;
    }

    .btn {
        border-radius: var(--border-radius);
        transition: var(--transition);
        font-weight: 500;
    }

    .btn:hover {
        transform: translateY(-1px);
    }

    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .filter-btn {
        padding: 0.5rem 1rem;
        border: 2px solid transparent;
        border-radius: var(--border-radius);
        background: white;
        color: var(--dark-color);
        text-decoration: none;
        transition: var(--transition);
        font-weight: 500;
    }

    .filter-btn:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-1px);
    }

    .filter-btn.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--secondary-color);
    }

    .badge {
        padding: 0.375rem 0.75rem;
        border-radius: var(--border-radius);
        font-size: 0.875rem;
        font-weight: 500;
    }

    .search-form {
        background: white;
        border-radius: var(--border-radius);
        padding: 1rem;
        box-shadow: var(--shadow);
        margin-bottom: 1rem;
    }

    .form-control {
        border-radius: var(--border-radius);
        border: 2px solid #e2e8f0;
        transition: var(--transition);
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    /* Optimisation pour les performances */
    .results-container {
        contain: layout style;
    }

    .table-responsive {
        contain: layout;
    }

    /* Loader optimisé */
    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(102, 126, 234, 0.3);
        border-radius: 50%;
        border-top-color: var(--primary-color);
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Responsive optimisé */
    @media (max-width: 768px) {
        .page-container {
            padding: 0.5rem;
        }
        
        .table-container {
            padding: 0.5rem;
        }
        
        .btn-group {
            flex-direction: column;
            gap: 0.125rem;
        }
    }
</style>

<!-- Interface utilisateur optimisée -->
<div class="page-container">
    <!-- Barre de recherche -->
    <div class="search-form">
        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="reparations">
            <div class="input-group">
                <input type="text" 
                       class="form-control" 
                       name="search" 
                       placeholder="Rechercher par nom, téléphone, appareil..." 
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Filtres rapides -->
    <div class="filter-buttons">
        <a href="index.php?page=reparations&statut_ids=1,2,3,4,5" class="filter-btn <?php echo ($statut_ids === '1,2,3,4,5') ? 'active' : ''; ?>">
            Récentes <span class="badge"><?php echo $total_reparations; ?></span>
        </a>
        <a href="index.php?page=reparations&statut_ids=1,2,3" class="filter-btn <?php echo ($statut_ids === '1,2,3') ? 'active' : ''; ?>">
            Nouvelles <span class="badge"><?php echo $total_nouvelles; ?></span>
        </a>
        <a href="index.php?page=reparations&statut_ids=4,5" class="filter-btn <?php echo ($statut_ids === '4,5') ? 'active' : ''; ?>">
            En cours <span class="badge"><?php echo $total_en_cours; ?></span>
        </a>
        <a href="index.php?page=reparations&statut_ids=6,7,8" class="filter-btn <?php echo ($statut_ids === '6,7,8') ? 'active' : ''; ?>">
            En attente <span class="badge"><?php echo $total_en_attente; ?></span>
        </a>
        <a href="index.php?page=reparations&statut_ids=9,10" class="filter-btn <?php echo ($statut_ids === '9,10') ? 'active' : ''; ?>">
            Terminées <span class="badge"><?php echo $total_termines; ?></span>
        </a>
    </div>

    <!-- Tableau des réparations -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Appareil</th>
                        <th>Problème</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Prix</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reparations)): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="p-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Aucune réparation trouvée</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reparations as $reparation): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($reparation['id']); ?></strong></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></strong>
                                        <?php if ($reparation['client_telephone']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($reparation['client_telephone']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($reparation['type_appareil']); ?></strong>
                                        <?php if ($reparation['marque']): ?>
                                            <br><small><?php echo htmlspecialchars($reparation['marque'] . ' ' . $reparation['modele']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 100)); ?>
                                        <?php if (strlen($reparation['description_probleme']) > 100): ?>...<?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <small><?php echo date('d/m', strtotime($reparation['date_reception'])); ?></small>
                                </td>
                                <td>
                                    <span class="badge" style="background-color: <?php echo $reparation['statut_couleur'] ?? '#6c757d'; ?>">
                                        <?php echo htmlspecialchars($reparation['statut_nom'] ?? $reparation['statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($reparation['prix_final']): ?>
                                        <strong><?php echo number_format($reparation['prix_final'], 2); ?>€</strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="index.php?page=detail_reparation&id=<?php echo $reparation['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?page=modifier_reparation&id=<?php echo $reparation['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary" 
                                           title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="pdf/etiquette_reparation.php?id=<?php echo $reparation['id']; ?>" 
                                           class="btn btn-sm btn-outline-info" 
                                           title="Imprimer l'étiquette">
                                            <i class="fas fa-print"></i>
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

<!-- JavaScript optimisé -->
<script>
// Optimisation des performances : Éviter les manipulations DOM coûteuses
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation du thème
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (prefersDark) {
        document.body.classList.add('dark-mode');
    }
    
    // Optimisation des interactions
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
    
    // Recherche en temps réel optimisée avec debounce
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length > 2 || this.value.length === 0) {
                    // Logique de recherche ici si nécessaire
                }
            }, 300);
        });
    }
});

// Fonction pour filtrer les réparations
function applyFilter(statut_ids) {
    window.location.href = `index.php?page=reparations&statut_ids=${statut_ids}`;
}

// Performance monitoring (optionnel)
if (typeof performance !== 'undefined') {
    window.addEventListener('load', function() {
        const perfData = performance.getEntriesByType('navigation')[0];
        if (perfData.loadEventEnd - perfData.loadEventStart > 3000) {
            console.warn('Page lente détectée:', perfData.loadEventEnd - perfData.loadEventStart + 'ms');
        }
    });
}
</script> 