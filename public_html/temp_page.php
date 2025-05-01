<?php
// Inclure la configuration de la base de données
require_once('config/database.php');

// Paramètres de filtrage
$statut = isset($_GET['statut']) ? cleanInput($_GET['statut']) : '';
$statut_ids = isset($_GET['statut_ids']) ? cleanInput($_GET['statut_ids']) : '1,2,3,4,5'; // Par défaut, afficher toutes les réparations actives
$type_appareil = isset($_GET['type_appareil']) ? cleanInput($_GET['type_appareil']) : '';
$date_debut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : '';
$date_fin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : '';

// Compter les réparations par catégorie de statut
try {
    // Total des réparations pour le bouton "Récentes" (statuts 1 à 5)
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM reparations r 
        WHERE r.statut IN (SELECT code FROM statuts WHERE id BETWEEN 1 AND 5)
    ");
    $total_reparations = $stmt->fetch()['total'];

    // Réparations nouvelles (statuts 1,2,3)
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM reparations r 
        WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (1,2,3))
    ");
    $total_nouvelles = $stmt->fetch()['total'];

    // Réparations en cours (statuts 4,5)
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM reparations r 
        WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (4,5))
    ");
    $total_en_cours = $stmt->fetch()['total'];

    // Réparations en attente (statuts 6,7,8)
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM reparations r 
        WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (6,7,8))
    ");
    $total_en_attente = $stmt->fetch()['total'];

    // Réparations terminées (statuts 9,10)
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM reparations r 
        WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (9,10))
    ");
    $total_termines = $stmt->fetch()['total'];

    // Réparations archivées (statuts 11,12,13)
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM reparations r 
        WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (11,12,13))
    ");
    $total_archives = $stmt->fetch()['total'];

} catch (PDOException $e) {
    error_log("Erreur lors du comptage des réparations : " . $e->getMessage());
    $total_reparations = 0;
    $total_nouvelles = 0;
    $total_en_cours = 0;
    $total_en_attente = 0;
    $total_termines = 0;
    $total_archives = 0;
}

// Construction de la requête SQL avec filtres
$sql = "
    SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email
    FROM reparations r
    LEFT JOIN clients c ON r.client_id = c.id
    WHERE 1=1
";
$params = [];

// Filtre par ID de statut (prioritaire sur le filtre par code)
if (!empty($statut_ids)) {
    // Vérifier si nous avons plusieurs IDs de statuts (séparés par des virgules)
    if (strpos($statut_ids, ',') !== false) {
        $ids = explode(',', $statut_ids);
        $id_placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql .= " AND r.statut IN (SELECT code FROM statuts WHERE id IN ($id_placeholders))";
        $params = array_merge($params, $ids);
    } else {
        $sql .= " AND r.statut = (SELECT code FROM statuts WHERE id = ?)";
        $params[] = $statut_ids;
    }
}
// Sinon, filtre par code de statut (si statut_ids n'est pas défini)
else if (!empty($statut)) {
    // Vérifier si nous avons plusieurs statuts (séparés par des virgules)
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
    $stmt = $pdo->prepare($sql);
$stmt->execute($params);
    $reparations = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des réparations: " . $e->getMessage() . "</div>";
    error_log("Erreur SQL (reparations.php): " . $e->getMessage());
    $reparations = [];
}

// Traitement de la suppression
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // Vérification de l'authentification - commentez cette section pour l'instant
    /*
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        set_message("Vous n'avez pas les droits nécessaires pour supprimer une réparation.", "danger");
        redirect("reparations");
        exit;
    }
    */

    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM reparations WHERE id = ?");
        $stmt->execute([$id]);
        
        set_message("Réparation supprimée avec succès.", "success");
    } catch (PDOException $e) {
        set_message("Erreur lors de la suppression de la réparation: " . $e->getMessage(), "danger");
    }
    redirect("reparations");
}
?>

<!-- Styles personnalisés pour les cartes de réparations -->
<style>
    /* Style pour le conteneur principal de la page */
    .page-container {
        display: flex;
        flex-direction: column;
        min-height: 100%;
        padding-top: 20px;
    }
    
    /* Styles pour les boutons de filtres */
    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
        margin-bottom: 1.5rem;
        width: 100%;
    }
    
    .filter-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 1.25rem 1rem;
        text-decoration: none;
        color: #6c757d;
        background-color: #fff;
        border-radius: 0.75rem;
        transition: all 0.3s ease;
        min-width: 120px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
    }
    
    .filter-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        color: #4361ee;
        border-color: rgba(67, 97, 238, 0.3);
    }
    
    .filter-btn.active {
        background-color: #4361ee;
        color: white;
        border-color: #4361ee;
    }
    
    .filter-btn i {
        color: inherit;
        margin-bottom: 0.5rem;
        font-size: 2.5rem;
    }
    
    .filter-btn span {
        font-size: 1rem;
        text-align: center;
        font-weight: 600;
    }
    
    .filter-btn .count {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: #e9ecef;
        color: #495057;
        border-radius: 1rem;
        padding: 0.25rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .dashboard-card.repair-row {
        height: auto;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .dashboard-card.repair-row:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.15);
        border-color: rgba(67, 97, 238, 0.3);
    }
    
    .dashboard-card .card-content {
        flex: 1;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        background: white;
    }
    
    .dashboard-card .card-footer {
        border-top: 1px solid rgba(0,0,0,0.05);
    }
    
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    
    .fw-medium {
        font-weight: 500;
    }
    
    /* Style pour la section de recherche */
    .search-card {
        width: 100%;
        margin: 0 auto 1.5rem;
        max-width: 100%;
    }
    
    /* Style pour le contenu principal */
    .results-container {
        width: 100%;
        margin-top: 0;
    }
    
    /* Style pour le message 'Aucune réparation trouvée' */
    .no-results-container {
    display: flex;
        flex-direction: column;
        align-items: center;
    justify-content: center;
        padding: 3rem 1rem;
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    width: 100%;
}

    /* Forcer l'affichage en colonne sur mobile et tablette */
    @media (max-width: 991px) {
        .page-container {
            display: flex;
            flex-direction: column;
            padding-top: 10px;
            margin-top: 100px; /* Ajout du décalage de 100px vers le bas sur mobile */
        }
        
        .filter-buttons {
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .filter-btn {
            min-width: calc(33.333% - 0.5rem);
            padding: 1rem 0.5rem;
}

.filter-btn i {
            font-size: 1.75rem;
        }
        
        .search-card {
            margin-bottom: 1rem;
        }
        
        .results-container {
            margin-top: 0;
        }
        
        /* Centrer la barre de recherche */
        .search-form .input-group {
            max-width: 100%;
            margin: 0 auto;
        }
    }

    /* Style pour la vue en cartes */
    #cards-view {
    display: flex;
    flex-wrap: wrap;
        gap: 1rem;
        justify-content: flex-start;
    }

    #cards-view .dashboard-card {
        flex: 0 0 calc(33.333% - 1rem);
        min-width: 300px;
        max-width: 100%;
        margin-bottom: 1rem;
    }

    /* Style pour le conteneur principal des résultats */
    .results-container {
        width: 100%;
        margin-top: 0;
    }

    .results-container .card {
        border-radius: 0.75rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    /* Style pour la vue en cartes avec peu d'éléments */
    @media (min-width: 992px) {
        #cards-view {
            justify-content: center;
        }
        
        #cards-view .dashboard-card {
            flex: 0 0 calc(33.333% - 1rem);
            min-width: 300px;
            max-width: 450px;
        }
        
        /* Si moins de 4 cartes, centrer le contenu */
        #cards-view.few-items {
            justify-content: center;
        }
    }

    @media (max-width: 991px) {
        #cards-view .dashboard-card {
            flex: 0 0 calc(50% - 0.5rem);
            min-width: 250px;
        }
    }

    @media (max-width: 768px) {
        #cards-view .dashboard-card {
            flex: 0 0 100%;
        }
    }

    /* Style pour la vue en cartes */
    .repair-cards-container {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        justify-content: center;
        padding: 1rem 0;
    }

    #cards-view .dashboard-card {
        flex: 0 0 calc(33.333% - 1rem);
        min-width: 300px;
        max-width: 400px;
        margin-bottom: 0;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
        background-color: white;
    border: 1px solid rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
}

    #cards-view .dashboard-card:hover {
    transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        border-color: #4361ee;
    }

    #cards-view .card-header {
        padding: 1rem;
        display: flex;
        justify-content: space-between;
    align-items: center;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        background-color: rgba(0,0,0,0.02);
    }

    #cards-view .repair-id {
        font-size: 0.875rem;
        color: #6c757d;
    }

    #cards-view .card-content {
        padding: 1.25rem;
        flex: 1;
    }

    #cards-view .card-footer {
    padding: 1rem;
        border-top: 1px solid rgba(0,0,0,0.05);
        background-color: rgba(0,0,0,0.02);
    }

    /* Style pour le conteneur principal des résultats */
    .results-container {
        width: 100%;
        margin-top: 0;
    }

    .results-container .card {
        border-radius: 0.75rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    /* Style pour la vue en cartes avec peu d'éléments */
    @media (min-width: 992px) {
        .repair-cards-container {
            justify-content: center;
        }
        
        #cards-view.few-items .repair-cards-container {
            max-width: 1000px;
            margin: 0 auto;
        }
    }

    @media (max-width: 991px) {
        #cards-view .dashboard-card {
            flex: 0 0 calc(50% - 1rem);
            min-width: 280px;
        }
        
        .repair-cards-container {
            gap: 1rem;
        }
    }

    @media (max-width: 768px) {
        #cards-view .dashboard-card {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .repair-cards-container {
            padding: 0.5rem;
    }
}

    /* Styles pour le drag & drop */
    .draggable-card {
        cursor: grab;
        user-select: none;
    }
    
    .draggable-card:active {
        cursor: grabbing;
    }
    
    .draggable-card.dragging {
        opacity: 0.7;
        transform: scale(0.95);
    }
    
    .filter-btn.droppable {
        position: relative;
        z-index: 10;
    }
    
    .filter-btn.drag-over {
        background-color: rgba(67, 97, 238, 0.2);
        transform: translateY(-5px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.15);
    }
    
    .filter-btn.drop-success {
        background-color: rgba(40, 167, 69, 0.2);
        transition: background-color 0.5s ease;
    }
    
    .draggable-card.updated {
        animation: card-updated 1s ease;
    }
    
    .ghost-card {
        position: absolute;
        width: 300px;
        padding: 1rem;
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        z-index: 9999;
        pointer-events: none;
        opacity: 0.8;
    }
    
    @keyframes card-updated {
        0% { background-color: rgba(40, 167, 69, 0.2); }
        100% { background-color: transparent; }
}

    /* Optimisations supplémentaires pour mobile */
    @media (max-width: 576px) {
        .fs-mobile-sm {
            font-size: 13px !important;
            line-height: 1.2 !important;
        }
        
        .dashboard-card .card-content {
            padding: 8px !important;
        }
        
        .dashboard-card .card-header {
            padding: 8px !important;
        }
        
        .dashboard-card .card-footer {
            padding: 8px !important;
        }
        
        .dashboard-card .card-content .avatar-circle {
            width: 26px !important;
            height: 26px !important;
            font-size: 0.8rem !important;
            min-width: 26px !important;
        }
        
        .dashboard-card .card-content i {
            font-size: 13px !important;
            min-width: 14px !important;
            display: flex !important;
            justify-content: center !important;
        }
        
        .dashboard-card .card-content p {
            font-size: 12px !important;
            margin-bottom: 4px !important;
            max-width: 100% !important;
            overflow: hidden !important;
        }
        
        .dashboard-card .card-content h6 {
            font-size: 13px !important;
            font-weight: 600 !important;
            margin-bottom: 0 !important;
        }
        
        .dashboard-card .card-content .fw-medium {
            font-weight: 500;
        }
        
        .dashboard-card .card-footer .btn-sm {
            padding: 0.2rem 0.4rem !important;
            font-size: 11px !important;
            margin: 0 1px !important;
        }
        
        .dashboard-card .card-content .btn-sm {
            padding: 0.2rem 0.4rem !important;
            font-size: 11px !important;
        }

        .dashboard-card .card-content .mb-2 {
            margin-bottom: 0.5rem !important;
        }
        
        .dashboard-card .card-content .mb-1 {
            margin-bottom: 0.35rem !important;
        }
        
        .dashboard-card .card-content .mt-1 {
            margin-top: 0.25rem !important;
        }
        
        .dashboard-card .card-content .me-2 {
            margin-right: 0.4rem !important;
        }
        
        .text-truncate {
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .repair-cards-container {
            gap: 0.75rem;
        }
        
        .dashboard-card .card-footer .btn-outline-success,
        .dashboard-card .card-footer .btn-outline-primary {
            padding: 0.2rem 0.3rem !important;
        }
        
        .dashboard-card .card-footer .btn i {
            font-size: 11px !important;
            margin-right: 0 !important;
        }
        
        .dashboard-card .card-footer .d-flex.justify-content-between {
            gap: 4px !important;
        }
        
        .dashboard-card .card-footer div > div {
            display: flex !important;
            gap: 2px !important;
        }
        
        /* Amélioration des boutons d'action */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .action-buttons > div {
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        
        .action-btn {
            flex: 1;
            margin: 0 2px;
            padding: 6px 8px !important;
            text-align: center;
            font-size: 11px !important;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px !important;
        }
        
        .action-btn i {
            font-size: 13px !important;
        }
        
        .btn-outline-success {
            color: #28a745;
            border-color: #28a745;
        }
        
        .btn-outline-primary {
            color: #007bff;
            border-color: #007bff;
        }
        
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
    }

    /* Style pour centrer les boutons */
    .card-footer .d-flex {
        justify-content: center !important;
        gap: 10px;
    }

    .card-footer .d-flex .btn {
        min-width: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Style pour le prix en haut à droite des cartes */
    .repair-id.fw-bold.text-success {
        color: #6c757d !important;
        font-size: 0.9rem;
        display: inline-block;
        opacity: 0.8;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing view toggle...');
    
    // Toggle entre vue tableau et cartes
    const toggleButtons = document.querySelectorAll('.toggle-view');
    const tableView = document.getElementById('table-view');
    const cardsView = document.getElementById('cards-view');
    
    console.log('Elements found:', { 
        toggleButtons: toggleButtons.length,
        tableView: tableView,
        cardsView: cardsView
    });
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const viewMode = this.getAttribute('data-view');
            console.log('Switching to view:', viewMode);
            
            // Mettre à jour les boutons
            toggleButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-outline-secondary');
            });
            this.classList.add('active');
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-secondary');
            
            // Mettre à jour l'affichage
            if (viewMode === 'table') {
                tableView.classList.remove('d-none');
                cardsView.classList.add('d-none');
                localStorage.setItem('repairViewMode', 'table');
            } else {
                tableView.classList.add('d-none');
                cardsView.classList.remove('d-none');
                localStorage.setItem('repairViewMode', 'cards');
            }
        });
    });
    
    // Charger la préférence utilisateur
    const savedViewMode = localStorage.getItem('repairViewMode') || 'cards';
    console.log('Saved view mode:', savedViewMode);
    const btn = document.querySelector(`.toggle-view[data-view="${savedViewMode}"]`);
    if (btn) {
        console.log('Clicking saved view mode button');
        btn.click();
    }
});
</script>

<div class="page-container">
    <!-- Filtres rapides pour tous les écrans -->
    <div class="mb-3">
        <div class="filter-buttons" role="group" aria-label="Filtres rapides">
            <!-- Bouton Nouvelle -->
            <a href="index.php?page=reparations&statut_ids=1,2,3" 
               class="filter-btn droppable <?php echo $statut_ids == '1,2,3' ? 'active' : ''; ?>"
               data-category-id="1">
                <i class="fas fa-plus-circle"></i>
                <span>Nouvelle</span>
                <span class="count"><?php echo $total_nouvelles ?? 0; ?></span>
            </a>
            
            <!-- Bouton En cours -->
            <a href="index.php?page=reparations&statut_ids=4,5" 
               class="filter-btn droppable <?php echo $statut_ids == '4,5' ? 'active' : ''; ?>"
               data-category-id="2">
                <i class="fas fa-spinner"></i>
                <span>En cours</span>
                <span class="count"><?php echo $total_en_cours ?? 0; ?></span>
            </a>
            
            <!-- Bouton En attente -->
            <a href="index.php?page=reparations&statut_ids=6,7,8" 
               class="filter-btn droppable <?php echo $statut_ids == '6,7,8' ? 'active' : ''; ?>"
               data-category-id="3">
                <i class="fas fa-clock"></i>
                <span>En attente</span>
                <span class="count"><?php echo $total_en_attente ?? 0; ?></span>
            </a>
            
            <!-- Bouton Terminé -->
            <a href="index.php?page=reparations&statut_ids=9,10" 
               class="filter-btn droppable <?php echo $statut_ids == '9,10' ? 'active' : ''; ?>"
               data-category-id="4">
                <i class="fas fa-check-circle"></i>
                <span>Terminé</span>
                <span class="count"><?php echo $total_termines ?? 0; ?></span>
            </a>
            
            <!-- Bouton Toutes -->
            <a href="index.php?page=reparations&statut_ids=1,2,3,4,5" class="filter-btn <?php echo ($statut_ids == '1,2,3,4,5' || (empty($statut) && empty($_GET['statut_ids']))) ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                <span>Récentes</span>
                <span class="count"><?php echo $total_reparations ?? 0; ?></span>
            </a>
            
            <!-- Bouton Archivé -->
            <a href="index.php?page=reparations&statut_ids=11,12,13" 
               class="filter-btn droppable <?php echo $statut_ids == '11,12,13' ? 'active' : ''; ?>"
               data-category-id="5">
                <i class="fas fa-archive"></i>
                <span>Archivé</span>
                <span class="count"><?php echo $total_archives ?? 0; ?></span>
            </a>
        </div>
    </div>

    <!-- Barre de recherche optimisée -->
    <div class="card search-card">
        <div class="card-body">
            <form method="GET" action="index.php" class="search-form">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0">
                        <i class="fas fa-search text-primary"></i>
                    </span>
                    <input type="hidden" name="page" value="reparations">
                    <input type="text" class="form-control border-start-0 ps-0" name="search" placeholder="Rechercher par nom, téléphone, appareil..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-primary" type="submit">Rechercher</button>
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <a href="index.php?page=reparations" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <a href="index.php?page=ajouter_reparation" class="btn btn-success btn-sm">
                        <i class="fas fa-plus-circle me-1"></i>Nouvelle réparation
                    </a>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                        <i class="fas fa-filter me-1"></i>Filtres avancés
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm toggle-view" data-view="table">
                        <i class="fas fa-table me-1"></i>Tableau
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm toggle-view active" data-view="cards">
                        <i class="fas fa-th-large me-1"></i>Cartes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Conteneur pour les résultats -->
    <div class="results-container">
        <div class="card">
            <div class="card-body">
                <!-- Vue tableau -->
                <div id="table-view" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="d-none d-md-table-cell">New</th>
                                    <th>Client</th>
                                    <th class="d-none d-md-table-cell">Appareil</th>
                                    <th class="d-none d-lg-table-cell">Problème</th>
                                    <th class="d-none d-md-table-cell">Date</th>
                                    <th>Statut</th>
                                    <th class="d-none d-lg-table-cell">Prix</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($reparations)): ?>
                                    <?php foreach ($reparations as $reparation): ?>
                                    <tr class="repair-row" data-id="<?php echo $reparation['id']; ?>">
                                        <td class="d-none d-md-table-cell">
                                            <div class="d-flex gap-2">
                                                <?php if ($reparation['commande_requise']): ?>
                                                    <i class="fas fa-shopping-cart text-warning" title="Commande requise"></i>
                                                <?php endif; ?>
                                                <?php if ($reparation['urgent']): ?>
                                                    <i class="fas fa-exclamation-triangle text-danger" title="Urgent"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary bg-opacity-10 text-primary me-3">
                                                    <i class="fas fa-tools"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">
                                                        <?php echo htmlspecialchars(($reparation['client_nom'] ?? '') . ' ' . ($reparation['client_prenom'] ?? '')); ?>
                                                    </h6>
                                                    <small class="text-muted">ID: <?php echo $reparation['id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . ($reparation['marque'] ?? '') . ' ' . $reparation['modele']); ?></td>
                                        <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 50)) . (strlen($reparation['description_probleme']) > 50 ? '...' : ''); ?></td>
                                        <td class="d-none d-md-table-cell"><?php echo isset($reparation['date_reception']) ? format_date($reparation['date_reception']) : (isset($reparation['date_creation']) ? format_date($reparation['date_creation']) : 'N/A'); ?></td>
                                        <td>
                                            <?php echo get_enum_status_badge($reparation['statut'], $reparation['id']); ?>
                                            <div class="d-block d-md-none mt-1">
                                                <small><?php echo format_date($reparation['date_reception']); ?></small>
                                            </div>
                                        </td>
                                        <td class="d-none d-lg-table-cell"><?php echo isset($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : (isset($reparation['prix']) ? number_format($reparation['prix'], 2, ',', ' ') . ' €' : 'N/A'); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <?php if ($reparation['urgent']): ?>
                                                <button class="btn btn-sm btn-danger toggle-urgent" data-id="<?php echo $reparation['id']; ?>" data-urgent="true" title="Urgent">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-info view-details" 
                                                        data-id="<?php echo $reparation['id']; ?>"
                                                        title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger delete-repair" 
                                                        data-id="<?php echo $reparation['id']; ?>"
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                                    <div class="no-results-container">
                                    <i class="fas fa-clipboard-list text-muted fa-3x mb-3"></i>
                                    <p class="text-muted">Aucune réparation trouvée.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Vue cartes -->
                <div id="cards-view">
                    <div class="repair-cards-container">
                        <?php if (!empty($reparations)): ?>
                    <?php foreach ($reparations as $reparation): ?>
                                <div class="dashboard-card repair-row draggable-card" data-id="<?php echo $reparation['id']; ?>" data-repair-id="<?php echo $reparation['id']; ?>" data-status="<?php echo $reparation['statut']; ?>" draggable="true">
                                    <!-- En-tête de la carte -->
                                    <div class="card-header">
                                        <span class="repair-status status-indicator">
                                    <?php echo get_enum_status_badge($reparation['statut'], $reparation['id']); ?>
                                </span>
                                        <span class="repair-id fw-bold text-success">
                                            <?php echo isset($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : (isset($reparation['prix']) ? number_format($reparation['prix'], 2, ',', ' ') . ' €' : 'N/A'); ?>
                                        </span>
                            </div>
                            
                            <!-- Contenu principal -->
                            <div class="card-content">
                                <!-- Ligne 1: Nom (gauche) et Appareil (droite) -->
                                <div class="d-flex justify-content-between mb-2">
                                    <!-- Colonne gauche: Nom du client et téléphone -->
                                    <div style="width: 50%;">
                                        <!-- Nom du client -->
                                        <div class="d-flex align-items-center mb-1">
                                            <div class="avatar-circle bg-primary bg-opacity-10 text-primary me-2" style="width: 28px; height: 28px; font-size: 0.8rem;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fs-mobile-sm"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></h6>
                                            </div>
                                        </div>
                                        
                                        <!-- Téléphone du client -->
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-phone text-success me-2" style="margin-left: 8px;"></i>
                                            <div>
                                                <p class="mb-0 fs-mobile-sm"><?php echo isset($reparation['client_telephone']) ? htmlspecialchars($reparation['client_telephone']) : 'N/A'; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Colonne droite: Appareil et problème -->
                                    <div style="width: 50%;">
                                        <!-- Appareil -->
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-mobile-alt text-primary me-2"></i>
                                            <div>
                                                <p class="mb-0 text-truncate fs-mobile-sm"><?php echo htmlspecialchars($reparation['type_appareil'] . ' ' . ($reparation['marque'] ?? '') . ' ' . $reparation['modele']); ?></p>
                                            </div>
                                        </div>
                                        
                                        <!-- Problème -->
                                        <div class="d-flex align-items-start mb-1">
                                            <i class="fas fa-tools text-danger me-2 mt-1"></i>
                                            <div>
                                                <p class="mb-0 text-truncate fs-mobile-sm"><?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 50)) . (strlen($reparation['description_probleme']) > 50 ? '...' : ''); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                
                                    <!-- Pied de la carte -->
                                    <div class="card-footer bg-light py-2 px-3">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <?php if (!empty($reparation['client_telephone'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($reparation['client_telephone']); ?>" 
                                               class="btn btn-sm btn-success" 
                                               title="Appeler">
                                                <i class="fas fa-phone-alt"></i>
                                            </a>
                                            <a href="sms:<?php echo htmlspecialchars($reparation['client_telephone']); ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="SMS">
                                                <i class="fas fa-comment-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-warning start-repair" 
                                                    data-id="<?php echo $reparation['id']; ?>"
                                                    title="Démarrer">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info view-details" 
                                                    data-id="<?php echo $reparation['id']; ?>"
                                                    title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger delete-repair" 
                                                    data-id="<?php echo $reparation['id']; ?>"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                    </div>
                    <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-results-container">
                                <i class="fas fa-clipboard-list text-muted fa-3x mb-3"></i>
                                <p class="text-muted">Aucune réparation trouvée.</p>
                            </div>
                <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                    
<!-- Modal de détails et modification -->
<div class="modal fade" id="detailsReparationModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-tools me-2 text-primary"></i>
                    Détails de la réparation
                </h5>
                <div class="ms-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="window.open('ajax/generate_pdf.php?id=' + document.getElementById('detailsReparationModal').getAttribute('data-reparation-id'), '_blank')">
                        <i class="fas fa-print me-1"></i> Imprimer
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm me-2" id="newOrderBtn">
                        <i class="fas fa-shopping-cart me-1"></i> Nouvelle commande
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm me-2" id="useStockPartBtn">
                        <i class="fas fa-box-open me-1"></i> Utiliser pièce stock
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm me-2" id="toggleUrgentBtn">
                        <i class="fas fa-exclamation-triangle me-1"></i> Urgent
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm me-2" id="showNumpad">
                        <i class="fas fa-calculator me-1"></i> Prix
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm me-2" id="addNote">
                        <i class="fas fa-sticky-note me-1"></i> Note
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm me-2" id="toggleEditMode">
                        <i class="fas fa-edit me-1"></i> Modifier
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-4">
                <!-- Mode visualisation -->
                <div id="viewMode" class="fade-in">
                    <!-- Le contenu sera chargé dynamiquement -->
                </div>

                <!-- Mode édition -->
                <div id="editMode" class="d-none fade-in">
                    <form id="editReparationForm" method="POST">
                        <input type="hidden" name="reparation_id" id="reparation_id">
                        
                        <!-- Informations appareil -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light py-2">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-mobile-alt me-2"></i>
                                    Informations appareil
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Type d'appareil</label>
                                        <select class="form-select" name="type_appareil" id="edit_type_appareil">
                                            <option value="Téléphone">Téléphone</option>
                                            <option value="Tablette">Tablette</option>
                                            <option value="Ordinateur">Ordinateur</option>
                                            <option value="Console">Console</option>
                                            <option value="Trottinette">Trottinette</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Marque</label>
                                        <input type="text" class="form-control" name="marque" id="marque">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Modèle</label>
                                        <input type="text" class="form-control" name="modele" id="edit_modele">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Numéro de série</label>
                                        <input type="text" class="form-control" name="numero_serie" id="numero_serie">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informations réparation -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-light py-2">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-wrench me-2"></i>
                                    Informations réparation
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Statut</label>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-outline-warning flex-grow-1 status-btn" data-status="en_attente">
                                                <i class="fas fa-clock me-1"></i> En attente
                                            </button>
                                            <button type="button" class="btn btn-outline-primary flex-grow-1 status-btn" data-status="en_cours">
                                                <i class="fas fa-spinner me-1"></i> En cours
                                            </button>
                                            <button type="button" class="btn btn-outline-success flex-grow-1 status-btn" data-status="livree">
                                                <i class="fas fa-check me-1"></i> Livrée
                                            </button>
                                            <button type="button" class="btn btn-outline-danger flex-grow-1 status-btn" data-status="archive">
                                                <i class="fas fa-archive me-1"></i> Archivé
                                            </button>
                                        </div>
                                        <input type="hidden" name="status" id="edit_selectedStatus" value="en_attente" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Prix (€)</label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" class="form-control" name="prix_reparation" id="edit_prix_reparation">
                                            <span class="input-group-text">€</span>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Date de fin prévue</label>
                                        <input type="date" class="form-control" name="date_fin_prevue" id="date_fin_prevue">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description et notes -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-light py-2">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-clipboard-list me-2"></i>
                                    Description et notes
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Description du problème</label>
                                    <textarea class="form-control" name="description_probleme" id="edit_description_probleme" rows="3"></textarea>
                                </div>
                                <div>
                                    <label class="form-label">Notes techniques</label>
                                    <textarea class="form-control" name="notes_techniques" id="notes_techniques" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fermer
                </button>
                <button type="button" class="btn btn-primary d-none" id="saveChanges">
                    <i class="fas fa-save me-1"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de suppression (unique) -->
<div class="modal" id="supprimerReparationModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                <!-- Le contenu sera chargé dynamiquement -->
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a href="#" class="btn btn-danger" id="confirmDelete">Supprimer</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

<!-- Modal pour les notes -->
<div class="modal fade" id="noteModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-sticky-note me-2 text-info"></i>
                    Notes techniques
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="noteText" class="form-label">Notes techniques</label>
                    <textarea class="form-control" id="noteText" rows="5" placeholder="Entrez vos notes techniques ici..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="button" class="btn btn-primary" id="saveNote">
                    <i class="fas fa-save me-1"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Remplacer le modal de la caméra -->
<div class="modal fade" id="cameraModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-camera me-2 text-primary"></i>
                    Prendre une photo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="cameraForm">
                    <input type="hidden" name="reparation_id" id="photoReparationId">
                    
                    <!-- Zone de la caméra -->
                    <div id="cameraZone" class="text-center mb-3">
                        <video id="camera" autoplay playsinline class="img-fluid rounded" style="max-height: 400px;"></video>
                        <canvas id="cameraCanvas" class="d-none"></canvas>
                    </div>

                    <!-- Zone de prévisualisation -->
                    <div id="photoPreview" class="text-center mb-3 d-none">
                        <div class="position-relative d-inline-block">
                            <img id="previewImage" src="" alt="Aperçu" class="img-fluid rounded" style="max-height: 400px;">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" id="modal_retakePhoto">
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
<div class="modal fade" id="orderPartsModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-shopping-cart me-2 text-warning"></i>
                    Commander une pièce détachée
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="orderPartsForm">
                    <input type="hidden" name="reparation_id" id="orderReparationId">
                    
                    <div class="mb-3">
                        <label for="partName" class="form-label">Nom de la pièce</label>
                        <input type="text" class="form-control" id="partName" name="part_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="partDescription" class="form-label">Description (Facultatif)</label>
                        <textarea class="form-control" id="partDescription" name="description" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="supplier" class="form-label">Fournisseur</label>
                        <select class="form-select" id="supplier" name="supplier_id" required>
                            <option value="">Sélectionner un fournisseur</option>
                            <!-- Les fournisseurs seront chargés dynamiquement -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="estimatedPrice" class="form-label">Prix (€)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control" id="estimatedPrice" name="estimated_price" required>
                            <span class="input-group-text">€</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantité</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Statut</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-warning flex-grow-1 status-btn" data-status="en_attente">
                                <i class="fas fa-clock me-1"></i> En attente
                            </button>
                            <button type="button" class="btn btn-outline-primary flex-grow-1 status-btn" data-status="en_cours">
                                <i class="fas fa-spinner me-1"></i> En cours
                            </button>
                            <button type="button" class="btn btn-outline-success flex-grow-1 status-btn" data-status="livree">
                                <i class="fas fa-check me-1"></i> Livrée
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-grow-1 status-btn" data-status="annulee">
                                <i class="fas fa-times me-1"></i> Annulée
                            </button>
                        </div>
                        <input type="hidden" name="status" id="order_selectedStatus" value="en_attente" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="button" class="btn btn-primary" id="saveOrderBtn">
                    <i class="fas fa-save me-1"></i> Commander
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'utilisation de pièce du stock -->
<div class="modal fade" id="useStockPartModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-box-open me-2"></i>
                    Utiliser une pièce du stock
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="useStockPartForm">
                    <input type="hidden" name="reparation_id" id="stockReparationId">
                    <div class="mb-3">
                        <label for="stockPartSelect" class="form-label">Sélectionner une pièce</label>
                        <select class="form-select" id="stockPartSelect" name="piece_id" required>
                            <option value="">Choisir une pièce...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantityUsed" class="form-label">Quantité utilisée</label>
                        <input type="number" class="form-control" id="quantityUsed" name="quantity" min="1" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="stockNote" class="form-label">Note (optionnel)</label>
                        <textarea class="form-control" id="stockNote" name="note" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="saveStockPartUse">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour démarrer une réparation -->
<div class="modal fade" id="startRepairModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-play-circle me-2 text-warning"></i>
                    Démarrer la réparation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="startRepairContent">
                    <!-- Le contenu sera chargé dynamiquement -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-3">Vérification des informations...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="button" class="btn btn-warning d-none" id="confirmStartRepair">
                    <i class="fas fa-play me-1"></i> Démarrer la réparation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour terminer une réparation -->
<div class="modal fade" id="finishRepairModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title d-flex align-items-center">
                    <i class="fas fa-check-circle me-2 text-success"></i>
                    Terminer la réparation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="finishRepairContent">
                    <p>Vous travaillez actuellement sur cette réparation. Souhaitez-vous la terminer ?</p>
                    
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">Nouveau statut</label>
                        <select class="form-select" id="newStatus">
                            <!-- Options chargées dynamiquement -->
                        </select>
                    </div>
                </div>
                
                <div id="repairInfo" class="alert alert-info mt-3">
                    <!-- Informations sur la réparation -->
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Annuler
                </button>
                <button type="button" class="btn btn-success" id="confirmFinishRepair">
                    <i class="fas fa-check me-1"></i> Terminer la réparation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Script pour la gestion des réparations -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modals
    const startRepairModal = new bootstrap.Modal(document.getElementById('startRepairModal'));
    const finishRepairModal = new bootstrap.Modal(document.getElementById('finishRepairModal'));
    
    // Vérifier les réparations qui ont déjà un employé assigné
    checkAllRepairsAssignment();
    
    // Boutons pour démarrer les réparations
    document.querySelectorAll('.start-repair').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            checkRepairStatus(id);
        });
    });
    
    // Fonction pour vérifier toutes les réparations et mettre à jour les boutons
    function checkAllRepairsAssignment() {
        document.querySelectorAll('.dashboard-card').forEach(card => {
            const repairId = card.getAttribute('data-id');
            if (repairId) {
                // Vérifier si cette réparation a déjà un employé principal
                fetch('ajax/manage_repair_attribution.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'demarrer',
                        reparation_id: repairId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.has_main_employee) {
                        // Cette réparation a déjà un employé principal, 
                        // on change le bouton Démarrer en Assister
                        const startButton = card.querySelector('.start-repair');
                        if (startButton) {
                            startButton.classList.remove('btn-warning');
                            startButton.classList.add('btn-info');
                            startButton.innerHTML = '<i class="fas fa-hands-helping"></i>';
                            startButton.setAttribute('data-has-main', 'true');
                            startButton.setAttribute('title', `Assigné à: ${data.main_employee.employe_nom}`);
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la vérification de l\'assignation:', error);
                });
            }
        });
    }
    
    // Fonction pour vérifier si l'utilisateur peut démarrer cette réparation
    function checkRepairStatus(repairId) {
        fetch('ajax/manage_repair_attribution.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'demarrer',
                reparation_id: repairId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher le modal avec les informations
                displayStartRepairModal(data, repairId);
                startRepairModal.show();
            } else {
                showNotification(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de la vérification de la réparation', 'danger');
        });
    }
    
    // Fonction pour afficher le modal de démarrage avec les infos pertinentes
    function displayStartRepairModal(data, repairId) {
        const startRepairContent = document.getElementById('startRepairContent');
        const confirmStartRepairBtn = document.getElementById('confirmStartRepair');
        
        // Réinitialiser le contenu
        startRepairContent.innerHTML = '';
        
        // Vérifier s'il y a d'autres personnes qui travaillent déjà sur cette réparation
        if (data.other_workers && data.other_workers.length > 0) {
            startRepairContent.innerHTML += `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention :</strong> D'autres employés travaillent déjà sur cette réparation.
                </div>
                <div class="mb-4">
                    <strong>Employés déjà sur cette réparation :</strong>
                    <ul class="mt-2">
                        ${data.other_workers.map(worker => `<li>${worker.nom}</li>`).join('')}
                    </ul>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="repairRole" id="assistantRole" value="0" checked>
                        <label class="form-check-label" for="assistantRole">
                            Je souhaite aider en tant qu'assistant
                        </label>
                    </div>
                </div>
            `;
        } else {
            startRepairContent.innerHTML += `
                <p>Vous êtes sur le point de démarrer cette réparation. Confirmez-vous ?</p>
                <input type="hidden" id="repairRole" value="1">
            `;
        }
        
        // Vérifier si l'employé a déjà des réparations actives
        if (data.active_repairs && data.active_repairs.length > 0) {
            startRepairContent.innerHTML += `
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Information :</strong> Vous travaillez déjà sur ${data.active_repairs.length} réparation(s).
                </div>
                <div class="mb-3">
                    <strong>Réparations actives :</strong>
                    <ul class="mt-2">
                        ${data.active_repairs.map(repair => `
                            <li>
                                #${repair.id} - ${repair.client_nom}
                                <small>(${repair.type_appareil} ${repair.modele})</small>
                                <button type="button" class="btn btn-sm btn-outline-success ms-2 finish-active-repair" data-id="${repair.id}">
                                    <i class="fas fa-check"></i> Terminer
                                </button>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }
        
        // Stocker l'ID de la réparation
        confirmStartRepairBtn.setAttribute('data-id', repairId);
        confirmStartRepairBtn.classList.remove('d-none');
        
        // Ajouter les écouteurs d'événements pour les boutons de fin de réparation active
        setTimeout(() => {
            document.querySelectorAll('.finish-active-repair').forEach(button => {
                button.addEventListener('click', function() {
                    const activeRepairId = this.getAttribute('data-id');
                    startRepairModal.hide();
                    fetchStatusOptions(activeRepairId);
                });
            });
        }, 100);
    }
    
    // Écouteur d'événement pour le bouton de démarrage
    document.getElementById('confirmStartRepair').addEventListener('click', function() {
        const repairId = this.getAttribute('data-id');
        let estPrincipal = 1;
        
        // Vérifier le rôle sélectionné
        const assistantRoleRadio = document.getElementById('assistantRole');
        if (assistantRoleRadio && assistantRoleRadio.checked) {
            estPrincipal = 0;
        } else {
            const repairRoleInput = document.getElementById('repairRole');
            if (repairRoleInput) {
                estPrincipal = parseInt(repairRoleInput.value);
            }
        }
        
        // Envoyer la requête pour démarrer la réparation
        fetch('ajax/manage_repair_attribution.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'confirmer_demarrage',
                reparation_id: repairId,
                est_principal: estPrincipal
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                startRepairModal.hide();
                
                // Mettre à jour le statut de la réparation sur la carte
                const card = document.querySelector(`.draggable-card[data-id="${repairId}"]`);
                if (card) {
                    const statusIndicator = card.querySelector('.status-indicator');
                    if (statusIndicator) {
                        statusIndicator.innerHTML = data.new_status_badge;
                    }
                    
                    // Mettre à jour l'attribut data-status
                    card.setAttribute('data-status', data.new_status);
                    
                    // Transformer le bouton démarrer en bouton terminer
                    const startButton = card.querySelector('.start-repair');
                    if (startButton) {
                        startButton.classList.remove('btn-warning', 'start-repair');
                        startButton.classList.add('btn-success', 'finish-repair');
                        startButton.innerHTML = '<i class="fas fa-check"></i>';
                        startButton.setAttribute('title', 'Terminer');
                        
                        // Mettre à jour l'écouteur d'événements
                        startButton.removeEventListener('click', null);
                        startButton.addEventListener('click', function() {
                            fetchStatusOptions(repairId);
                        });
                    }
                }
                
                // Recharger la page après un délai pour voir les changements
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showNotification(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors du démarrage de la réparation', 'danger');
        });
    });
    
    // Fonction pour récupérer les options de statut pour la fin d'une réparation
    function fetchStatusOptions(repairId) {
        // Afficher le modal de fin
        document.getElementById('repairInfo').innerHTML = `
            <div class="text-center">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mb-0">Chargement des informations...</p>
            </div>
        `;
        
        // Récupérer les statuts possibles
        fetch('ajax/manage_repair_attribution.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_statuts',
                reparation_id: repairId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remplir le select des statuts
                const statusSelect = document.getElementById('newStatus');
                statusSelect.innerHTML = '';
                
                if (data.statuts && data.statuts.length > 0) {
                    // Ajouter les options de statut
                    data.statuts.forEach(statut => {
                        const option = document.createElement('option');
                        option.value = statut.code;
                        option.textContent = statut.nom;
                        
                        // Ajouter un attribut de couleur pour le stylage optionnel
                        if (statut.couleur) {
                            option.setAttribute('data-color', statut.couleur);
                        }
                        
                        statusSelect.appendChild(option);
                    });
                } else {
                    // Ajouter des options par défaut si aucun statut n'est retourné
                    const defaultStatuses = [
                        {code: 'reparation_effectue', nom: 'Réparation effectuée'},
                        {code: 'en_attente_accord_client', nom: "En attente de l'accord client"},
                        {code: 'en_attente_livraison', nom: 'En attente de livraison'}
                    ];
                    
                    defaultStatuses.forEach(statut => {
                        const option = document.createElement('option');
                        option.value = statut.code;
                        option.textContent = statut.nom;
                        statusSelect.appendChild(option);
                    });
                }
                
                // Stocker l'ID de la réparation
                document.getElementById('confirmFinishRepair').setAttribute('data-id', repairId);
                
                // Charger les informations de la réparation
                loadRepairInfo(repairId);
                
                finishRepairModal.show();
                
                // Afficher un message d'avertissement si des statuts par défaut sont utilisés
                if (data.message) {
                    showNotification(data.message, 'warning');
                }
            } else {
                showNotification(data.message || 'Erreur lors de la récupération des statuts', 'danger');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur lors de la récupération des statuts. Veuillez réessayer.', 'danger');
        });
    }
    
    // Fonction pour charger les informations d'une réparation
    function loadRepairInfo(repairId) {
        fetch(`ajax/get_reparation.php?id=${repairId}`)
            .then(response => response.json())
            .then(repairData => {
                if (repairData.success) {
                    const reparation = repairData.reparation;
                    document.getElementById('repairInfo').innerHTML = `
                        <div class="d-flex align-items-start mb-2">
                            <div><i class="fas fa-info-circle me-2 mt-1"></i></div>
                            <div>
                                <div><strong>Client :</strong> ${reparation.client_nom || 'Non spécifié'}</div>
                                <div><strong>Appareil :</strong> ${reparation.type_appareil || ''} ${reparation.marque || ''} ${reparation.modele || ''}</div>
                                <div><strong>Problème :</strong> ${reparation.description_probleme ? (reparation.description_probleme.substring(0, 100) + (reparation.description_probleme.length > 100 ? '...' : '')) : 'Non spécifié'}</div>
                            </div>
                        </div>
                    `;
                } else {
                    document.getElementById('repairInfo').innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Informations détaillées non disponibles
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('repairInfo').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Erreur lors de la récupération des informations de la réparation
                    </div>
                `;
            });
    }
    
    // Écouteur d'événement pour le bouton de fin de réparation
    document.getElementById('confirmFinishRepair').addEventListener('click', function() {
        const repairId = this.getAttribute('data-id');
        const statusSelect = document.getElementById('newStatus');
        const nouveauStatut = statusSelect.value;
        
        if (!nouveauStatut) {
            showNotification('Veuillez sélectionner un statut', 'warning');
            return;
        }
        
        // Désactiver le bouton pendant le traitement pour éviter les clics multiples
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Traitement...';
        
        // Envoyer la requête pour terminer la réparation
        fetch('ajax/manage_repair_attribution.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'terminer',
                reparation_id: repairId,
                nouveau_statut: nouveauStatut
            })
        })
        .then(response => response.json())
        .then(data => {
            // Réactiver le bouton
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check me-1"></i> Terminer la réparation';
            
            if (data.success) {
                showNotification(data.message, 'success');
                finishRepairModal.hide();
                
                // Mettre à jour le statut de la réparation sur la carte
                const card = document.querySelector(`.draggable-card[data-id="${repairId}"]`);
                if (card) {
                    const statusIndicator = card.querySelector('.status-indicator');
                    if (statusIndicator) {
                        statusIndicator.innerHTML = data.new_status_badge;
                    }
                    
                    // Mettre à jour l'attribut data-status
                    card.setAttribute('data-status', data.new_status);
                    
                    // Transformer le bouton terminer en bouton démarrer
                    const finishButton = card.querySelector('.finish-repair');
                    if (finishButton) {
                        finishButton.classList.remove('btn-success', 'finish-repair');
                        finishButton.classList.add('btn-warning', 'start-repair');
                        finishButton.innerHTML = '<i class="fas fa-play"></i><span class="d-none d-md-inline ms-1">Démarrer</span>';
                        
                        // Mettre à jour l'écouteur d'événements
                        finishButton.removeEventListener('click', null);
                        finishButton.addEventListener('click', function() {
                            checkRepairStatus(repairId);
                        });
                    }
                }
                
                // Recharger la page après un délai pour voir les changements
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showNotification(data.message || 'Une erreur est survenue lors de la fin de la réparation', 'danger');
            }
        })
        .catch(error => {
            // Réactiver le bouton en cas d'erreur
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check me-1"></i> Terminer la réparation';
            
            console.error('Erreur:', error);
            showNotification('Erreur lors de la fin de la réparation. Veuillez réessayer.', 'danger');
        });
    });
    
    // Remplacer les boutons démarrer par des boutons terminer pour les réparations en cours
    document.querySelectorAll('.dashboard-card').forEach(card => {
        const repairId = card.getAttribute('data-id');
        const status = card.getAttribute('data-status');
        
        // Vérifier si l'employé est déjà en train de travailler sur cette réparation
        fetch('ajax/manage_repair_attribution.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'actives',
                reparation_id: repairId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.active_repairs) {
                // Vérifier si cette réparation est dans les actives
                const isActive = data.active_repairs.some(repair => repair.id == repairId);
                
                if (isActive) {
                    // Transformer le bouton démarrer en bouton terminer
                    const startButton = card.querySelector('.start-repair');
                    if (startButton) {
                        startButton.classList.remove('btn-warning', 'start-repair');
                        startButton.classList.add('btn-success', 'finish-repair');
                        startButton.innerHTML = '<i class="fas fa-check"></i>';
                        startButton.setAttribute('title', 'Terminer');
                        
                        // Mettre à jour l'écouteur d'événements
                        startButton.removeEventListener('click', null);
                        startButton.addEventListener('click', function() {
                            fetchStatusOptions(repairId);
                        });
                    }
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
    });
    
    // Fonction pour afficher une notification
    function showNotification(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed top-0 end-0 m-3`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 5000 });
        bsToast.show();
        
        // Supprimer la notification après qu'elle ait été fermée
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }
});
</script>

<script>
// Variables globales pour la caméra
let stream = null;
let camera = null;
let cameraModal = null;

document.addEventListener('DOMContentLoaded', function() {
    // Ouvrir automatiquement les filtres au chargement de la page
    let filterCollapse = document.getElementById('filterCollapse');
    if (filterCollapse) {
        filterCollapse.classList.add('show');
    }

    // Initialiser les modals
    const detailsModal = new bootstrap.Modal(document.getElementById('detailsReparationModal'));
    const deleteModal = new bootstrap.Modal(document.getElementById('supprimerReparationModal'));
    const noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
    cameraModal = new bootstrap.Modal(document.getElementById('cameraModal'));

    // Initialiser le modal de commande de pièces
    const orderPartsModal = new bootstrap.Modal(document.getElementById('orderPartsModal'));

    // Initialisation du modal d'utilisation de pièce du stock
    const useStockPartModal = new bootstrap.Modal(document.getElementById('useStockPartModal'));

    // Fonction pour charger les détails
    window.loadReparationDetails = function(id) {
        console.log('Chargement des détails pour ID:', id);
        
        // Définir l'ID de la réparation sur le modal
        document.getElementById('detailsReparationModal').setAttribute('data-reparation-id', id);
        
        // Afficher un indicateur de chargement
        document.querySelector('#viewMode').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2 text-muted">Chargement des détails...</p>
            </div>
        `;
        
        // Afficher le modal immédiatement
        detailsModal.show();

        fetch(`../ajax/get_reparation.php?id=${id}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(response => {
                if (!response.success) {
                    throw new Error(response.message || 'Erreur lors du chargement des détails');
                }
                
                const data = response.reparation;
                console.log('Données reçues:', data);
                
                // Remplir le mode visualisation avec une mise en page améliorée
                document.querySelector('#viewMode').innerHTML = `
                    <div class="row g-4">
                        <!-- Informations client et réparation -->
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6>
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    Informations client
                                </h6>
                                <div class="mb-3">
                                    <div class="info-label">Client</div>
                                    <div class="info-value">${data.client_nom || ''} ${data.client_prenom || ''}</div>
                                </div>
                                <div class="mb-4">
                                    <div class="info-label">Téléphone</div>
                                    <div class="info-value">
                                        <a href="tel:${data.client_telephone}" class="text-decoration-none">
                                            <i class="fas fa-phone-alt me-2"></i>
                                            ${data.client_telephone}
                                        </a>
                                    </div>
                                </div>

                                <div class="section-divider"></div>

                                <h6>
                                    <i class="fas fa-wrench me-2 text-primary"></i>
                                    Informations réparation
                                </h6>
                                <div class="mb-3">
                                    <div class="info-label">Statut</div>
                                    <div class="info-value">${data.urgent ? '<span class="badge bg-danger me-2">URGENT</span>' : ''}${data.statut_badge}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="info-label">Prix</div>
                                    <div class="info-value">
                                        <span class="fw-bold text-primary">
                                            ${data.prix_reparation ? data.prix_reparation + ' €' : '<span class="text-muted">Non défini</span>'}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <div class="info-label">Dates</div>
                                    <div class="info-value">
                                        <div class="mb-1"><i class="fas fa-calendar-check me-2"></i> Réception: ${data.date_reception}</div>
                                        ${data.date_fin_prevue ? `<div><i class="fas fa-calendar-alt me-2"></i> Fin prévue: ${data.date_fin_prevue}</div>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informations appareil -->
                        <div class="col-md-6">
                            <div class="info-section">
                                <h6 class="mb-3">
                                    <i class="fas fa-mobile-alt me-2 text-primary"></i>
                                    Informations appareil
                                </h6>
                                ${data.photo_appareil ? `
                                    <div class="mb-3">
                                        <div class="photo-container">
                                            <img src="${data.photo_appareil}" alt="Photo de l'appareil" class="img-fluid rounded">
                                        </div>
                                    </div>
                                ` : ''}
                                <div class="mb-2">
                                    <div class="info-label">Type</div>
                                    <div class="info-value">${data.type_appareil}</div>
                                </div>
                                <div class="mb-2">
                                    <div class="info-label">Marque & Modèle</div>
                                    <div class="info-value">${data.marque} ${data.modele}</div>
                                </div>
                                <div class="mb-2">
                                    <div class="info-label">Mot de passe</div>
                                    <div class="info-value">${data.mot_de_passe || '<span class="text-muted">Non renseigné</span>'}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Description du problème -->
                        <div class="col-12">
                            <div class="info-section">
                                <h6 class="mb-3">
                                    <i class="fas fa-exclamation-circle me-2 text-warning"></i>
                                    Description du problème
                                </h6>
                                <p class="mb-0">${data.description_probleme || '<span class="text-muted">Aucune description</span>'}</p>
                            </div>
                        </div>

                        <!-- Notes techniques -->
                        <div class="col-12">
                            <div class="info-section">
                                <h6 class="mb-3">
                                    <i class="fas fa-clipboard-list me-2 text-primary"></i>
                                    Notes techniques
                                </h6>
                                <p class="mb-0">${data.notes_techniques || '<span class="text-muted">Aucune note technique</span>'}</p>
                            </div>
                        </div>

                        <!-- Photos de la réparation -->
                        <div class="col-12">
                            <div class="info-section">
                                <h6 class="mb-3 d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-camera me-2 text-primary"></i>
                                        Photos de la réparation
                                    </span>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addPhotoBtn">
                                        <i class="fas fa-plus me-1"></i> Ajouter une photo
                                    </button>
                                </h6>
                                <div class="photo-gallery" id="photoGallery">
                                    <!-- Les photos seront chargées dynamiquement ici -->
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Charger les photos dans la galerie
                const photoGallery = document.getElementById('photoGallery');
                photoGallery.innerHTML = '';
                
                if (data.photos && data.photos.length > 0) {
                    data.photos.forEach(photo => {
                        const photoItem = document.createElement('div');
                        photoItem.className = 'photo-item';
                        photoItem.innerHTML = `
                            <div class="photo-container">
                                <img src="${photo.url}" alt="${photo.description || 'Photo de la réparation'}" class="img-fluid">
                                <div class="photo-overlay">
                                    <div class="photo-description">${photo.description || 'Aucune description'}</div>
                                    <div class="photo-actions">
                                        <button class="btn btn-sm btn-light delete-photo" data-photo-id="${photo.id}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        photoGallery.appendChild(photoItem);
                    });
                } else {
                    photoGallery.innerHTML = '<p class="text-muted text-center mb-0">Aucune photo disponible</p>';
                }

                // Remplir le formulaire d'édition
                document.getElementById('reparation_id').value = id;
                document.getElementById('edit_type_appareil').value = data.type_appareil;
                document.getElementById('marque').value = data.marque;
                document.getElementById('edit_modele').value = data.modele;
                document.getElementById('numero_serie').value = data.numero_serie;
                document.getElementById('edit_selectedStatus').value = data.statut;
                document.getElementById('edit_prix_reparation').value = data.prix_reparation;
                document.getElementById('date_fin_prevue').value = data.date_fin_prevue;
                document.getElementById('edit_description_probleme').value = data.description_probleme;
                document.getElementById('notes_techniques').value = data.notes_techniques;
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.querySelector('#viewMode').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erreur lors du chargement des détails: ${error.message}
                    </div>
                `;
            });
    };

    // Gestionnaire pour le bouton de détails
    document.querySelectorAll('.view-details').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            console.log('Clic sur le bouton détails, ID:', id); // Debug
            loadReparationDetails(id);
        });
    });

    // Gestionnaire pour le bouton d'ajout de photo
    document.addEventListener('click', function(e) {
        const addPhotoBtn = e.target.closest('#addPhotoBtn');
        if (addPhotoBtn) {
            console.log('Clic sur le bouton Ajouter une photo'); // Debug
            const reparationId = document.querySelector('#detailsReparationModal').getAttribute('data-reparation-id');
            console.log('ID de la réparation:', reparationId); // Debug
            
            if (!reparationId) {
                console.error('ID de réparation non trouvé');
                return;
            }
            
            const photoReparationIdInput = document.getElementById('photoReparationId');
            if (!photoReparationIdInput) {
                console.error('Champ photoReparationId non trouvé');
                return;
            }
            
            photoReparationIdInput.value = reparationId;
            
            // Réinitialiser le modal
            const photoPreview = document.getElementById('photoPreview');
            const cameraZone = document.getElementById('cameraZone');
            const captureBtn = document.getElementById('captureBtn');
            const savePhotoBtn = document.getElementById('savePhotoBtn');
            
            if (photoPreview && cameraZone && captureBtn && savePhotoBtn) {
                photoPreview.classList.add('d-none');
                cameraZone.classList.remove('d-none');
                captureBtn.classList.remove('d-none');
                savePhotoBtn.classList.add('d-none');
                
                // Démarrer la caméra
                startCamera();
                
                // Afficher le modal
                cameraModal.show();
            } else {
                console.error('Éléments du modal de caméra non trouvés');
            }
        }
    });

    // Fonction pour démarrer la caméra
    async function startCamera() {
        try {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                } 
            });
            
            camera = document.getElementById('camera');
            if (camera) {
                camera.srcObject = stream;
            } else {
                console.error('Élément vidéo non trouvé');
            }
        } catch (error) {
            console.error('Erreur d\'accès à la caméra:', error);
            alert('Impossible d\'accéder à la caméra. Veuillez vérifier les permissions.');
        }
    }

    // Gestionnaire pour la capture de photo
    const captureBtn = document.getElementById('captureBtn');
    if (captureBtn) {
        captureBtn.addEventListener('click', function() {
            const canvas = document.getElementById('cameraCanvas');
            const video = document.getElementById('camera');
            const preview = document.getElementById('previewImage');
            const photoPreview = document.getElementById('photoPreview');
            const cameraZone = document.getElementById('cameraZone');
            const savePhotoBtn = document.getElementById('savePhotoBtn');
            
            if (canvas && video && preview && photoPreview && cameraZone && savePhotoBtn) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);
                
                preview.src = canvas.toDataURL('image/jpeg');
                photoPreview.classList.remove('d-none');
                cameraZone.classList.add('d-none');
                this.classList.add('d-none');
                savePhotoBtn.classList.remove('d-none');
                
                // Arrêter le flux vidéo
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
            }
        });
    }

    // Gestionnaire pour reprendre une photo
    const retakePhotoBtn = document.getElementById('modal_retakePhoto');
    if (retakePhotoBtn) {
        retakePhotoBtn.addEventListener('click', function() {
            const photoPreview = document.getElementById('photoPreview');
            const cameraZone = document.getElementById('cameraZone');
            const captureBtn = document.getElementById('captureBtn');
            const savePhotoBtn = document.getElementById('savePhotoBtn');
            
            if (photoPreview && cameraZone && captureBtn && savePhotoBtn) {
                photoPreview.classList.add('d-none');
                cameraZone.classList.remove('d-none');
                captureBtn.classList.remove('d-none');
                savePhotoBtn.classList.add('d-none');
                startCamera();
            }
        });
    }

    // Gestionnaire pour la sauvegarde de la photo
    const savePhotoBtn = document.getElementById('savePhotoBtn');
    if (savePhotoBtn) {
        savePhotoBtn.addEventListener('click', async function() {
            const form = document.getElementById('cameraForm');
            if (!form) {
                console.error('Formulaire de caméra non trouvé');
                return;
            }
            
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
                
                const response = await fetch('../ajax/upload_photo.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Ajouter la nouvelle photo à la galerie
                    const photoGallery = document.getElementById('photoGallery');
                    if (photoGallery) {
                        const newPhoto = document.createElement('div');
                        newPhoto.className = 'photo-item';
                        newPhoto.innerHTML = `
                            <div class="photo-container">
                                <img src="${data.photo_url}" alt="${data.description || 'Photo de la réparation'}" class="img-fluid">
                                <div class="photo-overlay">
                                    <div class="photo-description">${data.description || 'Aucune description'}</div>
                                    <div class="photo-actions">
                                        <button class="btn btn-sm btn-light delete-photo" data-photo-id="${data.photo_id}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        photoGallery.appendChild(newPhoto);
                    }
                    
                    // Afficher une notification de succès
                    const toast = document.createElement('div');
                    toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed bottom-0 end-0 m-3';
                    toast.setAttribute('role', 'alert');
                    toast.style.zIndex = '1070';
                    toast.innerHTML = `
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-check-circle me-2"></i>
                                Photo ajoutée avec succès
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    const bsToast = new bootstrap.Toast(toast);
                    bsToast.show();
                    
                    // Fermer le modal et réinitialiser le formulaire
                    cameraModal.hide();
                    form.reset();
                    
                    // Supprimer le backdrop du modal
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    
                    // Réactiver le scroll de la page
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
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
    }

    // Arrêter la caméra lors de la fermeture du modal
    const cameraModalElement = document.getElementById('cameraModal');
    if (cameraModalElement) {
        cameraModalElement.addEventListener('hidden.bs.modal', function() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        });
    }

    // Gestionnaire pour le bouton urgent dans le modal
    const toggleUrgentBtn = document.getElementById('toggleUrgentBtn');
    if (toggleUrgentBtn) {
        toggleUrgentBtn.addEventListener('click', function() {
            const reparationId = document.querySelector('#detailsReparationModal').getAttribute('data-reparation-id');
            if (!reparationId) return;

            const currentUrgent = this.classList.contains('btn-danger');
            
            fetch('../ajax/toggle_urgent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: reparationId,
                    urgent: !currentUrgent
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'apparence du bouton
                    this.classList.toggle('btn-outline-danger');
                    this.classList.toggle('btn-danger');
                    
                    // Mettre à jour le badge dans le modal
                    const badgeCell = document.querySelector('#viewMode .info-value');
                    if (badgeCell) {
                        badgeCell.innerHTML = `${!currentUrgent ? '<span class="badge bg-danger me-2">URGENT</span>' : ''}${data.statut_badge}`;
                    }
                    
                    // Afficher une notification
                    const toast = document.createElement('div');
                    toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed bottom-0 end-0 m-3';
                    toast.setAttribute('role', 'alert');
                    toast.style.zIndex = '1070';
                    toast.innerHTML = `
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-check-circle me-2"></i>
                                ${!currentUrgent ? 'Réparation marquée comme urgente' : 'Réparation retirée des urgences'}
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    `;
                    document.body.appendChild(toast);
                    const bsToast = new bootstrap.Toast(toast);
                    bsToast.show();
                } else {
                    throw new Error(data.error || 'Erreur lors de la mise à jour');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la mise à jour du statut urgent');
            });
        });
    }

    // Gestionnaire pour le bouton nouvelle commande
    document.getElementById('newOrderBtn').addEventListener('click', function() {
        const reparationId = document.querySelector('#detailsReparationModal').getAttribute('data-reparation-id');
        document.getElementById('orderReparationId').value = reparationId;
        
        // Charger la liste des fournisseurs
        fetch('ajax/get_suppliers.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des fournisseurs');
                }
                return response.json();
            })
            .then(data => {
                const supplierSelect = document.getElementById('supplier');
                supplierSelect.innerHTML = '<option value="">Sélectionner un fournisseur</option>';
                
                if (data && data.length > 0) {
                    data.forEach(supplier => {
                        supplierSelect.innerHTML += `
                            <option value="${supplier.id}">${supplier.nom}</option>
                        `;
                    });
                } else {
                    supplierSelect.innerHTML += '<option value="" disabled>Aucun fournisseur disponible</option>';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                const supplierSelect = document.getElementById('supplier');
                supplierSelect.innerHTML = '<option value="" disabled>Erreur de chargement des fournisseurs</option>';
            });
        
        // Afficher le modal
        const orderPartsModal = new bootstrap.Modal(document.getElementById('orderPartsModal'));
        orderPartsModal.show();
    });

    // Gestionnaire pour la sauvegarde de la commande
    document.getElementById('saveOrderBtn').addEventListener('click', async function() {
        const form = document.getElementById('orderPartsForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        
        try {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Commande en cours...';
            
            const response = await fetch('../ajax/create_part_order.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Afficher une notification de succès
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed bottom-0 end-0 m-3';
                toast.setAttribute('role', 'alert');
                toast.style.zIndex = '1070';
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check-circle me-2"></i>
                            Commande de pièce créée avec succès
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
                
                // Fermer le modal et réinitialiser le formulaire
                const orderPartsModal = bootstrap.Modal.getInstance(document.getElementById('orderPartsModal'));
                orderPartsModal.hide();
                form.reset();

                // Supprimer le backdrop du modal
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }

                // Réactiver le scroll de la page
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            } else {
                throw new Error(data.error || 'Erreur lors de la création de la commande');
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert(`Erreur lors de la création de la commande: ${error.message}`);
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save me-1"></i> Commander';
        }
    });

    // Gestionnaire pour le bouton d'utilisation de pièce du stock
    document.getElementById('useStockPartBtn').addEventListener('click', function() {
        console.log('Clic sur le bouton Utiliser pièce stock'); // Debug
        const reparationId = document.getElementById('reparation_id').value;
        console.log('ID de la réparation:', reparationId); // Debug
        
        if (!reparationId) {
            console.error('ID de réparation non trouvé');
            return;
        }
        
        document.getElementById('stockReparationId').value = reparationId;
        
        // Charger les pièces disponibles dans le stock
        fetch('ajax/get_stock_parts.php')
            .then(response => response.json())
            .then(data => {
                console.log('Pièces disponibles:', data); // Debug
                const select = document.getElementById('stockPartSelect');
                select.innerHTML = '<option value="">Choisir une pièce...</option>';
                
                data.forEach(part => {
                    const option = document.createElement('option');
                    option.value = part.id;
                    option.textContent = `${part.nom} (Stock: ${part.quantite})`;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du chargement des pièces disponibles');
            });
        
        useStockPartModal.show();
    });

    // Gestionnaire pour l'enregistrement de l'utilisation de pièce
    document.getElementById('saveStockPartUse').addEventListener('click', function() {
        const form = document.getElementById('useStockPartForm');
        const formData = new FormData(form);
        
        fetch('ajax/use_stock_part.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                useStockPartModal.hide();
                alert('Pièce utilisée avec succès');
                // Recharger les détails de la réparation
                loadReparationDetails(document.getElementById('reparation_id').value);
            } else {
                alert(data.message || 'Erreur lors de l\'utilisation de la pièce');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'utilisation de la pièce');
        });
    });

    // Gestionnaire pour les boutons de statut
    document.querySelectorAll('.status-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            document.querySelectorAll('.status-btn').forEach(btn => {
                btn.classList.remove('active');
                // Remplacer btn-outline-* par btn-*
                const color = btn.className.match(/btn-outline-(\w+)/)[1];
                btn.className = btn.className.replace(`btn-outline-${color}`, `btn-outline-${color}`);
            });
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            // Remplacer btn-outline-* par btn-*
            const color = this.className.match(/btn-outline-(\w+)/)[1];
            this.className = this.className.replace(`btn-outline-${color}`, `btn-${color}`);
            
            // Mettre à jour la valeur du champ caché
            document.getElementById('selectedStatus').value = this.getAttribute('data-status');
        });
    });

    // Gestionnaire pour le bouton de fermeture du modal de commande
    document.querySelector('#orderPartsModal .btn-close').addEventListener('click', function() {
        // Supprimer le backdrop du modal
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }

        // Réactiver le scroll de la page
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });

    // Gestionnaire pour les boutons de suppression
    document.querySelectorAll('.delete-repair').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const modal = new bootstrap.Modal(document.getElementById('supprimerReparationModal'));
            
            // Mettre à jour le contenu du modal
            document.querySelector('#supprimerReparationModal .modal-body').innerHTML = 
                'Êtes-vous sûr de vouloir supprimer cette réparation ? Cette action est irréversible.';
            
            // Mettre à jour le lien de confirmation
            document.getElementById('confirmDelete').href = `index.php?page=reparations&action=delete&id=${id}`;
            
            modal.show();
        });
    });
});
</script>

<style>
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

#detailsReparationModal .modal-content {
    border-radius: 0.5rem;
}

#detailsReparationModal .card {
    border: none;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

#detailsReparationModal .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important;
}

#detailsReparationModal .form-control,
#detailsReparationModal .form-select {
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
    padding: 0.5rem 0.75rem;
    transition: all 0.2s ease;
}

#detailsReparationModal .form-control:focus,
#detailsReparationModal .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
}

#detailsReparationModal .btn {
    border-radius: 0.375rem;
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
}

#detailsReparationModal .btn:hover {
    transform: translateY(-1px);
}

#detailsReparationModal .modal-header {
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
}

#detailsReparationModal .badge {
    font-size: 0.875rem;
    padding: 0.5em 0.75em;
}

#viewMode {
    font-size: 0.95rem;
}

#viewMode .info-section {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1.25rem;
    margin-bottom: 1rem;
    height: 100%;
}

#viewMode .info-section h6 {
    position: relative;
    padding-bottom: 0.75rem;
    margin-bottom: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
}

#viewMode .info-section h6:after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 50px;
    height: 3px;
    background: #3498db;
    border-radius: 2px;
}

#viewMode .info-section .section-divider {
    height: 1px;
    background: linear-gradient(to right, #e9ecef 0%, #dee2e6 50%, #e9ecef 100%);
    margin: 1.5rem 0;
}

#viewMode .info-label {
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

#viewMode .info-value {
    color: #2c3e50;
    font-size: 1.1rem;
    margin-bottom: 1rem;
    padding-left: 0.5rem;
}

#viewMode .info-value a {
    color: #3498db;
    text-decoration: none;
    transition: color 0.2s ease;
}

#viewMode .info-value a:hover {
    color: #2980b9;
}

#viewMode .badge {
    font-size: 0.85rem;
    padding: 0.5em 0.85em;
    font-weight: 500;
}

.photo-container {
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.photo-container img {
    transition: transform 0.3s ease;
}

.photo-container:hover img {
    transform: scale(1.05);
}

/* Styles pour le clavier numérique */
.numpad-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1060;
}

.numpad-container {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.2);
    width: 300px;
}

.numpad-display {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: right;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.numpad-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}

.numpad-btn {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    font-size: 1.25rem;
    cursor: pointer;
    transition: all 0.2s;
}

.numpad-btn:hover {
    background: #e9ecef;
}

.numpad-btn.wide {
    grid-column: span 2;
}

.numpad-btn.confirm {
    background: #198754;
    color: white;
}

.numpad-btn.confirm:hover {
    background: #157347;
}

.numpad-btn.clear {
    background: #dc3545;
    color: white;
}

.numpad-btn.clear:hover {
    background: #bb2d3b;
}

/* Ajouter les styles pour la galerie de photos */
.photo-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.photo-item {
    position: relative;
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    transition: transform 0.3s ease;
}

.photo-item:hover {
    transform: translateY(-2px);
}

.photo-container {
    position: relative;
    padding-top: 75%; /* Ratio 4:3 */
    background-color: #f8f9fa;
}

.photo-container img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
    padding: 1rem;
    color: white;
    transform: translateY(100%);
    transition: transform 0.3s ease;
}

.photo-item:hover .photo-overlay {
    transform: translateY(0);
}

.photo-description {
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.photo-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.photo-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Styles pour les boutons de statut */
.status-btn {
    transition: all 0.3s ease;
    border-radius: 0.5rem;
    padding: 0.75rem;
    font-weight: 500;
}

.status-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1);
}

.status-btn.active {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1);
}

.status-btn i {
    font-size: 1.1rem;
}

/* Styles pour les boutons de filtre */
.filter-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    background: transparent;
    padding: 0;
    box-shadow: none;
}

.filter-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    border-radius: 1rem;
    font-weight: 500;
    color: #6c757d;
    background-color: white;
    border: 1px solid #e9ecef;
    text-decoration: none;
    transition: all 0.3s ease;
    min-width: 120px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.filter-btn:hover {
    background-color: #f8f9fa;
    color: #495057;
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.filter-btn.active {
    background-color: #4361ee;
    color: white;
    border-color: #4361ee;
    box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
}

.filter-btn i {
    color: inherit;
    margin-bottom: 0.5rem;
}

.filter-btn span {
    font-size: 0.9rem;
    text-align: center;
}

/* Ajustements pour mobile */
@media (max-width: 768px) {
    .filter-buttons {
        gap: 0.5rem;
    }
    
    .filter-btn {
        padding: 1rem;
        min-width: 100px;
    }
    
    .filter-btn i {
        font-size: 1.5rem;
    }
    
    .filter-btn span {
        font-size: 0.8rem;
    }
}
</style>

<!-- JavaScript pour les cartes cliquables -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Récupérer les éléments DOM
    const tableView = document.getElementById('table-view');
    const cardsView = document.getElementById('cards-view');
    const viewModeButtons = document.querySelectorAll('.view-mode-btn');
    
    // Fonction pour changer de mode d'affichage
    function changeViewMode(mode) {
        // Mettre à jour les boutons
        viewModeButtons.forEach(btn => {
            if (btn.getAttribute('data-mode') === mode) {
                btn.classList.add('active');
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-secondary');
            } else {
                btn.classList.remove('active');
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-outline-secondary');
            }
        });
        
        // Afficher/masquer les vues
        if (mode === 'table') {
            tableView.classList.remove('d-none');
            cardsView.classList.add('d-none');
        } else {
            tableView.classList.add('d-none');
            cardsView.classList.remove('d-none');
        }
        
        // Sauvegarder la préférence dans localStorage
        localStorage.setItem('reparations_view_mode', mode);
    }
    
    // Gestionnaire d'événements pour les boutons de mode
    viewModeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const mode = this.getAttribute('data-mode');
            changeViewMode(mode);
        });
    });
    
    // Restaurer la préférence de l'utilisateur au chargement
    const savedMode = localStorage.getItem('reparations_view_mode');
    if (savedMode) {
        changeViewMode(savedMode);
    } else {
        // Par défaut: mode cartes
        changeViewMode('cards');
    }
    
    // Rendre les cartes cliquables (ouvre le modal de détails)
    document.querySelectorAll('.dashboard-card.repair-row').forEach(function(card) {
        card.addEventListener('click', function(e) {
            // Ne pas déclencher si on a cliqué sur un bouton
            if (e.target.closest('.btn') || e.target.closest('button')) {
                return;
            }
            
            // Récupérer l'ID de la réparation et déclencher le clic sur le bouton view-details
            const repairId = this.getAttribute('data-id');
            const viewButton = this.querySelector('.view-details');
            if (viewButton) {
                viewButton.click();
            }
        });
    });
    
    // Rendre les lignes du tableau cliquables aussi
    document.querySelectorAll('#table-view .repair-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            // Ne pas déclencher si on a cliqué sur un bouton
            if (e.target.closest('.btn') || e.target.closest('button')) {
                return;
            }
            
            // Récupérer l'ID de la réparation et déclencher le clic sur le bouton view-details
            const repairId = this.getAttribute('data-id');
            const viewButton = this.querySelector('.view-details');
            if (viewButton) {
                viewButton.click();
            }
        });
    });
    
    // S'assurer que les filtres fonctionnent sur mobile
    const filterToggleBtn = document.querySelector('[data-bs-target="#filterCollapse"]');
    if (filterToggleBtn) {
        // Vérifier si des filtres sont actifs
        const hasActiveFilters = 
            '<?php echo !empty($statut) || !empty($statut_ids) || !empty($type_appareil) || !empty($date_debut) || !empty($date_fin); ?>' === '1';
        
        // Si des filtres sont actifs, afficher automatiquement le panneau de filtres
        if (hasActiveFilters) {
            const filterCollapse = document.getElementById('filterCollapse');
            if (filterCollapse) {
                filterCollapse.classList.add('show');
            }
        }
    }
});
</script>

<style>
/* Style pour les boutons de basculement de mode */
.view-mode-btn {
    transition: all 0.2s ease;
}

.view-mode-btn.active {
    font-weight: 500;
}

/* Style pour rendre les lignes du tableau cliquables */
#table-view .repair-row {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

#table-view .repair-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Styles existants pour les cartes */
.dashboard-card.repair-row {
    height: auto;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.08);
    overflow: hidden;
}
</style>

<!-- Scripts pour le drag & drop des statuts -->
<script src="assets/js/drag-drop-fix.js"></script>
<script src="assets/js/drag-drop-statut.js"></script>

<!-- Modal pour choisir le statut spécifique après le drag & drop -->
<div class="modal fade" id="chooseStatusModal" tabindex="-1" aria-labelledby="chooseStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chooseStatusModalLabel">
                    <i class="fas fa-tasks me-2"></i>
                    Choisir un statut
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Annuler"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="avatar-circle bg-light d-inline-flex mb-3">
                        <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                    </div>
                    <h5 class="fw-bold">Sélectionner un statut</h5>
                    <p class="text-muted">Choisissez le statut que vous souhaitez attribuer à cette réparation</p>
                </div>
                
                <div id="statusButtonsContainer" class="d-flex flex-column gap-2">
                    <!-- Les boutons de statut seront générés dynamiquement ici -->
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement des statuts disponibles...</p>
                    </div>
                </div>
                
                <input type="hidden" id="chooseStatusRepairId" value="">
                <input type="hidden" id="chooseStatusCategoryId" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Style pour l'avatar dans le modal */
.avatar-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* Style pour les boutons de statut */
#statusButtonsContainer .btn {
    text-align: left;
    padding: 12px 20px;
    border-radius: 8px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    font-weight: 500;
}

#statusButtonsContainer .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

#statusButtonsContainer .btn i {
    width: 24px;
    text-align: center;
}

/* Styles spécifiques pour les cartes draggable */
.draggable-card {
    cursor: grab;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.draggable-card:active {
    cursor: grabbing;
}

.draggable-card.dragging {
    opacity: 0.8;
    transform: scale(1.02);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    z-index: 1000;
}

.ghost-card {
    position: absolute;
    pointer-events: none;
    opacity: 0.7;
    z-index: 1000;
    transform: rotate(3deg);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    width: 300px;
}

.filter-btn.drag-over {
    transform: scale(1.05);
    box-shadow: 0 0 10px rgba(0,123,255,0.5);
    border: 2px dashed #0d6efd;
}

.filter-btn.drop-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
    transition: all 0.5s ease;
}

.draggable-card.updated {
    animation: card-update-success 1s ease;
}

@keyframes card-update-success {
    0% { 
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.5);
        transform: scale(1.03);
    }
    50% { 
        box-shadow: 0 0 0 6px rgba(40, 167, 69, 0.3);
    }
    100% { 
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
        transform: scale(1);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le drag & drop pour les cartes de réparation
    initCardDragAndDrop();
    
    // Reste du code JavaScript...
    
    // Fonctions pour le drag & drop des cartes
    function initCardDragAndDrop() {
        // Sélectionner toutes les cartes de réparation
        const draggableCards = document.querySelectorAll('.draggable-card');
        const dropZones = document.querySelectorAll('.filter-btn.droppable');
        
        // Variables pour le ghost element
        let ghostElement = null;
        let draggedCard = null;
        
        // Ajouter les écouteurs d'événements pour les cartes
        draggableCards.forEach(card => {
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
            
            // Empêcher la propagation du clic pour les boutons à l'intérieur des cartes
            const buttons = card.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('mousedown', e => {
                    e.stopPropagation();
                });
                
                button.addEventListener('click', e => {
                    e.stopPropagation();
                });
            });
        });
        
        // Ajouter les écouteurs d'événements pour les zones de dépôt
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', handleDragOver);
            zone.addEventListener('dragenter', handleDragEnter);
            zone.addEventListener('dragleave', handleDragLeave);
            zone.addEventListener('drop', handleDrop);
        });
        
        /**
         * Gère le début du drag
         */
        function handleDragStart(e) {
            console.log('Début du drag sur une carte', this);
            
            // Marquer la carte comme étant en cours de déplacement
            this.classList.add('dragging');
            draggedCard = this;
            
            // Récupérer les données de réparation et de statut
            const repairId = this.getAttribute('data-repair-id') || this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            
            console.log('Données de drag:', { repairId, status });
            
            // Stocker les données de l'élément déplacé
            e.dataTransfer.setData('text/plain', JSON.stringify({
                repairId: repairId,
                status: status
            }));
            
            // Créer un "ghost element" pour le feedback visuel
            createGhostElement(this, e);
            
            // Définir l'effet de déplacement
            e.dataTransfer.effectAllowed = 'move';
        }
        
        /**
         * Gère la fin du drag
         */
        function handleDragEnd(e) {
            // Supprimer la classe de dragging
            this.classList.remove('dragging');
            
            // Supprimer le ghost element
            if (ghostElement && ghostElement.parentNode) {
                document.body.removeChild(ghostElement);
                ghostElement = null;
            }
            
            // Réinitialiser les zones de dépôt
            dropZones.forEach(zone => {
                zone.classList.remove('drag-over');
            });
            
            // Supprimer l'écouteur mousemove
            document.removeEventListener('mousemove', updateGhostPosition);
        }
        
        /**
         * Gère le survol d'une zone de dépôt
         */
        function handleDragOver(e) {
            // Empêcher le comportement par défaut pour permettre le drop
            e.preventDefault();
            return false;
        }
        
        /**
         * Gère l'entrée dans une zone de dépôt
         */
        function handleDragEnter(e) {
            this.classList.add('drag-over');
        }
        
        /**
         * Gère la sortie d'une zone de dépôt
         */
        function handleDragLeave() {
            this.classList.remove('drag-over');
        }
        
        /**
         * Gère le dépôt dans une zone
         */
        function handleDrop(e) {
            // Empêcher le comportement par défaut
            e.preventDefault();
            
            console.log('Drop détecté sur une zone de dépôt', this);
            
            // Récupérer les données
            try {
                const dataText = e.dataTransfer.getData('text/plain');
                console.log('Données de transfert brutes:', dataText);
                
                const data = JSON.parse(dataText);
                console.log('Données de transfert parsées:', data);
                
                const repairId = data.repairId;
                const categoryId = this.getAttribute('data-category-id');
                
                console.log('ID réparation:', repairId);
                console.log('ID catégorie:', categoryId);
                console.log('Element de statut:', draggedCard ? draggedCard.querySelector('.status-indicator') : 'Non trouvé');
                
                // Vérifier que nous avons toutes les données nécessaires
                if (!repairId || !categoryId) {
                    console.error('Données incomplètes pour la mise à jour du statut');
                    return false;
                }
                
                // Effet visuel de succès sur la zone de dépôt
                this.classList.add('drop-success');
                setTimeout(() => {
                    this.classList.remove('drop-success');
                }, 1000);
                
                // Mettre à jour le statut de la réparation via la fonction fetchStatusOptions
                if (draggedCard && draggedCard.querySelector('.status-indicator')) {
                fetchStatusOptions(repairId, categoryId, draggedCard.querySelector('.status-indicator'));
                } else {
                    console.error('Impossible de trouver l\'indicateur de statut sur la carte glissée');
                    // Essayer de créer une référence alternative
                    const allCards = document.querySelectorAll('.dashboard-card');
                    let targetCard = null;
                    allCards.forEach(card => {
                        const cardId = card.getAttribute('data-repair-id') || card.getAttribute('data-id');
                        if (cardId == repairId) {
                            targetCard = card;
                        }
                    });
                    
                    if (targetCard && targetCard.querySelector('.status-indicator')) {
                        console.log('Carte cible alternative trouvée:', targetCard);
                        fetchStatusOptions(repairId, categoryId, targetCard.querySelector('.status-indicator'));
                    } else {
                        console.error('Aucune carte cible alternative trouvée, rechargement de la page');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                }
                
            } catch (error) {
                console.error('Erreur lors du traitement des données:', error);
            }
            
            // Réinitialiser l'état visuel
            this.classList.remove('drag-over');
            return false;
        }
        
        /**
         * Crée un élément fantôme pour le feedback visuel pendant le drag
         */
        function createGhostElement(sourceElement, event) {
            // Supprimer l'ancien ghost s'il existe
            if (ghostElement && ghostElement.parentNode) {
                document.body.removeChild(ghostElement);
            }
            
            // Créer un clone simplifié de la carte pour le ghost
            ghostElement = document.createElement('div');
            ghostElement.className = 'dashboard-card ghost-card';
            
            // Ajouter les informations importantes de la carte
            const statusBadge = sourceElement.querySelector('.status-indicator').cloneNode(true);
            const deviceInfo = sourceElement.querySelector('.mb-0').cloneNode(true);
            
            ghostElement.appendChild(statusBadge);
            ghostElement.appendChild(deviceInfo);
            
            // Positionner l'élément
            const rect = sourceElement.getBoundingClientRect();
            
            // Calculer l'offset par rapport au point de clic
            const offsetX = event.clientX - rect.left;
            const offsetY = event.clientY - rect.top;
            
            // Sauvegarder l'offset pour les mises à jour de position
            ghostElement.dataset.offsetX = offsetX;
            ghostElement.dataset.offsetY = offsetY;
            
            // Appliquer la position initiale
            ghostElement.style.left = (event.pageX - offsetX) + 'px';
            ghostElement.style.top = (event.pageY - offsetY) + 'px';
            
            // Ajouter au DOM
            document.body.appendChild(ghostElement);
            
            // Ajouter un écouteur pour le mouvement de la souris
            document.addEventListener('mousemove', updateGhostPosition);
        }
        
        /**
         * Met à jour la position de l'élément fantôme pendant le drag
         */
        function updateGhostPosition(e) {
            if (ghostElement) {
                const offsetX = parseInt(ghostElement.dataset.offsetX) || 0;
                const offsetY = parseInt(ghostElement.dataset.offsetY) || 0;
                
                ghostElement.style.left = (e.pageX - offsetX) + 'px';
                ghostElement.style.top = (e.pageY - offsetY) + 'px';
            }
        }
    }
    
    /**
     * Met à jour le statut d'une réparation via AJAX
     */
    function updateRepairStatus(repairId, categoryId, cardElement) {
        // Afficher un indicateur de chargement
        const statusIndicator = cardElement.querySelector('.status-indicator');
        statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Mise à jour...</span>';
        
        // Préparer les données
        const data = {
            repair_id: repairId,
            category_id: categoryId
        };
        
        // Envoyer la requête AJAX
        fetch('../ajax/update_repair_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le badge avec le nouveau statut
                statusIndicator.innerHTML = data.data.badge;
                
                // Mettre à jour l'attribut data-status de la carte
                cardElement.setAttribute('data-status', data.data.statut);
                
                // Afficher un message de succès
                showNotification('Statut mis à jour avec succès', 'success');
                
                // Animation de succès
                cardElement.classList.add('updated');
                setTimeout(() => {
                    cardElement.classList.remove('updated');
                }, 1000);
            } else {
                // Afficher l'erreur
                showNotification('Erreur: ' + data.error, 'danger');
                
                // Recharger la page pour rétablir l'état correct
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour du statut:', error);
            showNotification('Erreur de communication avec le serveur', 'danger');
            
            // Recharger la page pour rétablir l'état correct
            setTimeout(() => {
                location.reload();
            }, 2000);
        });
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le drag & drop pour les cartes de réparation
    initCardDragAndDrop();
    
    // Reste du code JavaScript...
    
    // Fonctions pour le drag & drop des cartes
    function initCardDragAndDrop() {
        // Sélectionner toutes les cartes de réparation
        const draggableCards = document.querySelectorAll('.draggable-card');
        const dropZones = document.querySelectorAll('.filter-btn.droppable');
        
        // Variables pour le ghost element
        let ghostElement = null;
        let draggedCard = null;
        
        // Ajouter les écouteurs d'événements pour les cartes
        draggableCards.forEach(card => {
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
            
            // Empêcher la propagation du clic pour les boutons à l'intérieur des cartes
            const buttons = card.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('mousedown', e => {
                    e.stopPropagation();
                });
                
                button.addEventListener('click', e => {
                    e.stopPropagation();
                });
            });
        });
        
        // Ajouter les écouteurs d'événements pour les zones de dépôt
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', handleDragOver);
            zone.addEventListener('dragenter', handleDragEnter);
            zone.addEventListener('dragleave', handleDragLeave);
            zone.addEventListener('drop', handleDrop);
        });
        
        /**
         * Gère le début du drag
         */
        function handleDragStart(e) {
            // Marquer la carte comme étant en cours de déplacement
            this.classList.add('dragging');
            draggedCard = this;
            
            // Stocker les données de l'élément déplacé
            e.dataTransfer.setData('text/plain', JSON.stringify({
                repairId: this.getAttribute('data-repair-id'),
                status: this.getAttribute('data-status')
            }));
            
            // Créer un "ghost element" pour le feedback visuel
            createGhostElement(this, e);
            
            // Définir l'effet de déplacement
            e.dataTransfer.effectAllowed = 'move';
        }
        
        /**
         * Gère la fin du drag
         */
        function handleDragEnd(e) {
            // Supprimer la classe de dragging
            this.classList.remove('dragging');
            
            // Supprimer le ghost element
            if (ghostElement && ghostElement.parentNode) {
                document.body.removeChild(ghostElement);
                ghostElement = null;
            }
            
            // Réinitialiser les zones de dépôt
            dropZones.forEach(zone => {
                zone.classList.remove('drag-over');
            });
            
            // Supprimer l'écouteur mousemove
            document.removeEventListener('mousemove', updateGhostPosition);
        }
        
        /**
         * Gère le survol d'une zone de dépôt
         */
        function handleDragOver(e) {
            // Empêcher le comportement par défaut pour permettre le drop
            e.preventDefault();
            return false;
        }
        
        /**
         * Gère l'entrée dans une zone de dépôt
         */
        function handleDragEnter(e) {
            this.classList.add('drag-over');
        }
        
        /**
         * Gère la sortie d'une zone de dépôt
         */
        function handleDragLeave() {
            this.classList.remove('drag-over');
        }
        
        /**
         * Gère le dépôt dans une zone
         */
        function handleDrop(e) {
            // Empêcher le comportement par défaut
            e.preventDefault();
            
            console.log('Drop détecté sur une zone de dépôt', this);
            
            // Récupérer les données
            try {
                const dataText = e.dataTransfer.getData('text/plain');
                console.log('Données de transfert brutes:', dataText);
                
                const data = JSON.parse(dataText);
                console.log('Données de transfert parsées:', data);
                
                const repairId = data.repairId;
                const categoryId = this.getAttribute('data-category-id');
                
                console.log('ID réparation:', repairId);
                console.log('ID catégorie:', categoryId);
                console.log('Element de statut:', draggedCard ? draggedCard.querySelector('.status-indicator') : 'Non trouvé');
                
                // Vérifier que nous avons toutes les données nécessaires
                if (!repairId || !categoryId) {
                    console.error('Données incomplètes pour la mise à jour du statut');
                    return false;
                }
                
                // Effet visuel de succès sur la zone de dépôt
                this.classList.add('drop-success');
                setTimeout(() => {
                    this.classList.remove('drop-success');
                }, 1000);
                
                // Mettre à jour le statut de la réparation via la fonction fetchStatusOptions
                if (draggedCard && draggedCard.querySelector('.status-indicator')) {
                fetchStatusOptions(repairId, categoryId, draggedCard.querySelector('.status-indicator'));
                } else {
                    console.error('Impossible de trouver l\'indicateur de statut sur la carte glissée');
                    // Essayer de créer une référence alternative
                    const allCards = document.querySelectorAll('.dashboard-card');
                    let targetCard = null;
                    allCards.forEach(card => {
                        const cardId = card.getAttribute('data-repair-id') || card.getAttribute('data-id');
                        if (cardId == repairId) {
                            targetCard = card;
                        }
                    });
                    
                    if (targetCard && targetCard.querySelector('.status-indicator')) {
                        console.log('Carte cible alternative trouvée:', targetCard);
                        fetchStatusOptions(repairId, categoryId, targetCard.querySelector('.status-indicator'));
                    } else {
                        console.error('Aucune carte cible alternative trouvée, rechargement de la page');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                }
                
            } catch (error) {
                console.error('Erreur lors du traitement des données:', error);
            }
            
            // Réinitialiser l'état visuel
            this.classList.remove('drag-over');
            return false;
        }
        
        /**
         * Crée un élément fantôme pour le feedback visuel pendant le drag
         */
        function createGhostElement(sourceElement, event) {
            // Supprimer l'ancien ghost s'il existe
            if (ghostElement && ghostElement.parentNode) {
                document.body.removeChild(ghostElement);
            }
            
            // Créer un clone simplifié de la carte pour le ghost
            ghostElement = document.createElement('div');
            ghostElement.className = 'dashboard-card ghost-card';
            
            // Ajouter les informations importantes de la carte
            const statusBadge = sourceElement.querySelector('.status-indicator').cloneNode(true);
            const deviceInfo = sourceElement.querySelector('.mb-0').cloneNode(true);
            
            ghostElement.appendChild(statusBadge);
            ghostElement.appendChild(deviceInfo);
            
            // Positionner l'élément
            const rect = sourceElement.getBoundingClientRect();
            
            // Calculer l'offset par rapport au point de clic
            const offsetX = event.clientX - rect.left;
            const offsetY = event.clientY - rect.top;
            
            // Sauvegarder l'offset pour les mises à jour de position
            ghostElement.dataset.offsetX = offsetX;
            ghostElement.dataset.offsetY = offsetY;
            
            // Appliquer la position initiale
            ghostElement.style.left = (event.pageX - offsetX) + 'px';
            ghostElement.style.top = (event.pageY - offsetY) + 'px';
            
            // Ajouter au DOM
            document.body.appendChild(ghostElement);
            
            // Ajouter un écouteur pour le mouvement de la souris
            document.addEventListener('mousemove', updateGhostPosition);
        }
        
        /**
         * Met à jour la position de l'élément fantôme pendant le drag
         */
        function updateGhostPosition(e) {
            if (ghostElement) {
                const offsetX = parseInt(ghostElement.dataset.offsetX) || 0;
                const offsetY = parseInt(ghostElement.dataset.offsetY) || 0;
                
                ghostElement.style.left = (e.pageX - offsetX) + 'px';
                ghostElement.style.top = (e.pageY - offsetY) + 'px';
            }
        }
    }
    
    /**
     * Met à jour le statut d'une réparation via AJAX
     */
    function updateRepairStatus(repairId, categoryId, cardElement) {
        // Afficher un indicateur de chargement
        const statusIndicator = cardElement.querySelector('.status-indicator');
        statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Mise à jour...</span>';
        
        // Préparer les données
        const data = {
            repair_id: repairId,
            category_id: categoryId
        };
        
        // Envoyer la requête AJAX
        fetch('../ajax/update_repair_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le badge avec le nouveau statut
                statusIndicator.innerHTML = data.data.badge;
                
                // Mettre à jour l'attribut data-status de la carte
                cardElement.setAttribute('data-status', data.data.statut);
                
                // Afficher un message de succès
                showNotification('Statut mis à jour avec succès', 'success');
                
                // Animation de succès
                cardElement.classList.add('updated');
                setTimeout(() => {
                    cardElement.classList.remove('updated');
                }, 1000);
            } else {
                // Afficher l'erreur
                showNotification('Erreur: ' + data.error, 'danger');
                
                // Recharger la page pour rétablir l'état correct
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour du statut:', error);
            showNotification('Erreur de communication avec le serveur', 'danger');
            
            // Recharger la page pour rétablir l'état correct
            setTimeout(() => {
                location.reload();
            }, 2000);
        });
    }
});

/**
 * Affiche une notification temporaire si la fonction n'existe pas déjà
 */
if (typeof window.showNotification !== 'function') {
    window.showNotification = function(message, type = 'info') {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.style.maxWidth = '300px';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Ajouter au DOM
        document.body.appendChild(notification);
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    };
}

    /**
     * Détermine la couleur Bootstrap à utiliser en fonction de la couleur de la catégorie
     */
    function getCategoryColor(color) {
        // Convertir la couleur en classe Bootstrap
        const colorMap = {
            'info': 'info',
            'primary': 'primary',
            'warning': 'warning',
            'success': 'success',
            'danger': 'danger',
            'secondary': 'secondary'
        };
        return colorMap[color] || 'primary';
    }

    /**
     * Récupère les options de statut pour une catégorie donnée
     */
    function fetchStatusOptions(repairId, categoryId, statusIndicator) {
        // Afficher un indicateur de chargement dans le badge
        statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Chargement...</span>';
        
        // Récupérer les statuts disponibles pour cette catégorie
        fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Stocker les IDs pour une utilisation ultérieure
                    document.getElementById('chooseStatusRepairId').value = repairId;
                    document.getElementById('chooseStatusCategoryId').value = categoryId;
                    
                    // Générer les boutons de statut
                    const container = document.getElementById('statusButtonsContainer');
                    container.innerHTML = ''; // Effacer le contenu précédent, y compris l'indicateur de chargement
                    
                    // Déterminer la couleur de la catégorie
                    const categoryColor = getCategoryColor(data.category.couleur);
                    
                    // Ajouter un titre pour la catégorie dans le modal
                    const categoryTitle = document.getElementById('chooseStatusModalLabel');
                    if (categoryTitle) {
                        categoryTitle.innerHTML = `<i class="fas fa-tasks me-2"></i> Statuts "${data.category.nom}"`;
                    }
                    
                    // Créer un bouton pour chaque statut
                    data.statuts.forEach(statut => {
                        const button = document.createElement('button');
                        button.className = `btn btn-${categoryColor} btn-lg w-100 mb-2`;
                        button.setAttribute('data-status-id', statut.id);
                        button.innerHTML = `
                            <i class="fas fa-check-circle me-2"></i>
                            ${statut.nom}
                        `;
                        button.addEventListener('click', () => updateSpecificStatus(statut.id, statusIndicator));
                        container.appendChild(button);
                    });
                    
                    // Afficher le modal
                    const modal = new bootstrap.Modal(document.getElementById('chooseStatusModal'));
                    modal.show();
                    
                    // Rétablir le badge de statut quand l'utilisateur annule
                    const closeBtn = document.querySelector('#chooseStatusModal .btn-close');
                    const cancelBtn = document.querySelector('#chooseStatusModal .btn-outline-secondary');
                    
                    const handleCancel = function() {
                        // Nettoyer le backdrop et réactiver le scroll
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                        
                        // Simplement recharger la page pour éviter l'update automatique
                        location.reload();
                    };
                    
                    if (closeBtn) {
                        // Enlever les anciens écouteurs d'événements
                        closeBtn.removeEventListener('click', handleCancel);
                        // Ajouter le nouvel écouteur
                        closeBtn.addEventListener('click', handleCancel);
                    }
                    
                    if (cancelBtn) {
                        // Enlever les anciens écouteurs d'événements
                        cancelBtn.removeEventListener('click', handleCancel);
                        // Ajouter le nouvel écouteur
                        cancelBtn.addEventListener('click', handleCancel);
                    }
                    
                } else {
                    // Afficher l'erreur
                    showNotification('Erreur: ' + data.error, 'danger');
                    location.reload(); // Recharger la page en cas d'erreur
                }
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des statuts:', error);
                showNotification('Erreur de communication avec le serveur', 'danger');
                location.reload(); // Recharger la page en cas d'erreur
            });
    }
    
    /**
     * Met à jour le statut spécifique d'une réparation
     */
    function updateSpecificStatus(statusId, statusIndicator) {
        // Récupérer les ID stockés
        const repairId = document.getElementById('chooseStatusRepairId').value;
        
        // Fermer le modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('chooseStatusModal'));
        modal.hide();
        
        // Nettoyer le backdrop et réactiver le scroll
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        
        // Afficher un indicateur de chargement
        // Afficher un indicateur de chargement
        statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Mise à jour...</span>';
        
        // Préparer les données
        const data = {
            repair_id: repairId,
            status_id: statusId
        };
        
        // Envoyer la requête AJAX
        fetch('../ajax/update_repair_specific_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le badge avec le nouveau statut
                statusIndicator.innerHTML = data.data.badge;
                
                // Mettre à jour l'attribut data-status de la carte
                const card = statusIndicator.closest('.draggable-card');
                if (card) {
                    card.setAttribute('data-status', data.data.statut);
                    
                    // Animation de succès
                    card.classList.add('updated');
                    setTimeout(() => {
                        card.classList.remove('updated');
                    }, 1000);
                }
                
                // Afficher un message de succès
                showNotification('Statut mis à jour avec succès', 'success');
            } else {
                // Afficher l'erreur
                showNotification('Erreur: ' + data.error, 'danger');
                
                // Recharger la page pour rétablir l'état correct
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la mise à jour du statut:', error);
            showNotification('Erreur de communication avec le serveur', 'danger');
            
            // Recharger la page pour rétablir l'état correct
            setTimeout(() => {
                location.reload();
            }, 2000);
        });
    }
</script>

<style>
/* Style pour les boutons d'action */
.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 12px;
}

.action-buttons > div {
    display: flex;
    justify-content: space-between;
    width: 100%;
    gap: 4px;
}

.action-buttons > div > div {
    display: flex;
    gap: 5px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    margin: 0;
    border-radius: 5px;
    padding: 6px 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    font-weight: 500;
    min-width: 36px;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 5px rgba(0,0,0,0.15);
}

.action-btn i {
    font-size: 14px;
}

.action-btn span {
    margin-left: 5px;
}

.btn-outline-success {
    color: #28a745;
    border-color: #28a745;
}

.btn-outline-success:hover {
    color: white;
    background-color: #28a745;
}

.btn-outline-primary {
    color: #007bff;
    border-color: #007bff;
}

.btn-outline-primary:hover {
    color: white;
    background-color: #007bff;
}

.btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
    color: white;
}

.btn-info:hover {
    background-color: #138496;
    border-color: #138496;
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #c82333;
}

@media (max-width: 576px) {
    /* Amélioration des boutons d'action pour mobile */
    .action-buttons {
        flex-direction: column;
        gap: 6px;
        margin-top: 8px;
    }
    
    .action-buttons > div {
        width: 100%;
        gap: 4px;
    }
    
    .action-buttons > div > div {
        gap: 3px;
    }
    
    .action-btn {
        padding: 4px 8px !important;
        font-size: 11px !important;
        min-width: auto !important;
        flex: 1;
        text-align: center;
    }
    
    .action-btn i {
        font-size: 12px !important;
        margin-right: 0;
    }
}
</style>
}