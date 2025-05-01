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
    // Vérification des droits administrateur
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        set_message("Vous n'avez pas les droits nécessaires pour supprimer une réparation.", "danger");
        redirect("reparations");
        exit;
    }

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

// Supprimer une réparation (administrateurs uniquement)
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        set_message("Vous n'avez pas les droits nécessaires pour effectuer cette action.", "danger");
        header('Location: index.php?page=reparations');
        exit();
    }
}
?>

<!-- Styles personnalisés pour les cartes de réparations -->
<style>
    /* Style pour le conteneur principal de la page */
    .page-container {
        display: flex;
        flex-direction: column;
        min-height: 100%;
        padding-top: 15px;
    }
    
    /* Styles pour les boutons de filtres */
    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: center;
        margin-bottom: 1rem;
        width: 100%;
    }
    
    .filter-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 1rem 0.75rem;
        text-decoration: none;
        color: #6c757d;
        background-color: #fff;
        border-radius: 0.75rem;
        transition: all 0.3s ease;
        min-width: 110px;
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
        font-size: 2rem;
    }
    
    .filter-btn span {
        font-size: 0.9rem;
        text-align: center;
        font-weight: 600;
    }
    
    .filter-btn .count {
        position: absolute;
        top: 0.25rem;
        right: 0.25rem;
        background: #e9ecef;
        color: #495057;
        border-radius: 1rem;
        padding: 0.15rem 0.5rem;
        font-size: 0.75rem;
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
        gap: 1rem;
        justify-content: center;
        padding: 0.5rem 0;
    }

    #cards-view .dashboard-card {
        flex: 0 0 calc(33.333% - 1rem);
        min-width: 280px;
        max-width: 380px;
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
        padding: 0.75rem;
        display: flex;
        justify-content: space-between;
    align-items: center;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        background-color: rgba(0,0,0,0.05);
    }

    #cards-view .repair-id {
        font-size: 0.875rem;
        color: #495057;
        font-weight: 600;
    }

    #cards-view .card-content {
        padding: 0.8rem;
        flex: 1;
    }

    #cards-view .card-footer {
        padding: 0.5rem;
        border-top: 1px solid rgba(0,0,0,0.1);
        background-color: rgba(0,0,0,0.05);
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

    /* Styles pour le drag & drop des lignes du tableau */
    #table-view .repair-row {
        cursor: grab;
    }

    #table-view .repair-row:active {
        cursor: grabbing;
    }

    #table-view .repair-row.dragging {
        opacity: 0.7;
        background-color: #f8f9fa;
    }

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

    /* Style pour les boutons d'action dans le tableau */
    #table-view .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem !important;
        justify-content: flex-end;
    }

    #table-view .btn-group .btn {
        margin: 0 !important;
        border-radius: 50rem !important;
        padding: 0.375rem 0.65rem;
        font-size: 0.875rem;
        transition: all 0.3s ease;
        border: none;
    }

    /* Style pour les boutons dans le tableau */
    #table-view .btn-success {
        background-color: rgba(40, 167, 69, 0.15) !important;
        color: #28a745 !important;
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.1);
    }

    #table-view .btn-success:hover {
        background-color: rgba(40, 167, 69, 0.25) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
    }

    #table-view .btn-warning,
    #table-view .start-repair {
        background-color: #4361ee !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);
    }

    #table-view .btn-warning:hover,
    #table-view .start-repair:hover {
        background-color: #3a56de !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
    }

    #table-view .btn-primary {
        background-color: rgba(23, 162, 184, 0.15) !important;
        color: #17a2b8 !important;
        box-shadow: 0 2px 4px rgba(23, 162, 184, 0.1);
    }

    #table-view .btn-primary:hover {
        background-color: rgba(23, 162, 184, 0.25) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(23, 162, 184, 0.2);
    }

    /* Style amélioré pour le bouton démarrer (start-repair) au format tableau */
    #table-view .btn-soft-primary {
        background: linear-gradient(135deg, #0d6efd, #3a86ff) !important;
        color: white !important;
        box-shadow: 0 2px 5px rgba(13, 110, 253, 0.3);
        border: none;
    }

    #table-view .btn-soft-primary:hover {
        background: linear-gradient(135deg, #3a86ff, #0d6efd) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.4);
    }

    /* Style amélioré pour le bouton SMS au format tableau */
    #table-view .btn-soft-info {
        background-color: #6610f2 !important;
        color: white !important;
        box-shadow: 0 2px 4px rgba(102, 16, 242, 0.2);
    }

    #table-view .btn-soft-info:hover {
        background-color: #5a0dce !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(102, 16, 242, 0.4);
        background: linear-gradient(135deg, #6f42c1, #6610f2) !important;
    }

    #cards-view .btn-soft-danger {
        background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
        color: white !important;
        box-shadow: 0 2px 5px rgba(231, 76, 60, 0.3);
        border: none;
        transition: all 0.3s ease;
    }

    #cards-view .btn-soft-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(231, 76, 60, 0.4);
        background: linear-gradient(135deg, #c0392b, #e74c3c) !important;
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

    /* Styles pour aligner le texte à gauche dans le tableau */
    #table-view table th,
    #table-view table td {
        text-align: left !important;
    }

    #table-view .table th:first-child,
    #table-view .table td:first-child {
        padding-left: 1rem !important;
    }

    #table-view .table th:last-child,
    #table-view .table td:last-child {
        text-align: right !important;
    }

    /* Style pour la colonne client */
    #table-view td .d-flex.align-items-center div {
        text-align: left !important;
    }

    /* Nouveaux styles pour la colonne client avec icônes empilées */
    #table-view .d-flex.align-items-start .avatar-circle {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    #table-view .d-flex.flex-column.align-items-center {
        min-width: 36px;
    }

    #table-view .d-flex.align-items-start .text-start {
        padding-top: 3px;
    }

    #table-view .d-flex.align-items-start .text-start h6 {
        font-size: 0.9rem;
        margin-bottom: 2px !important;
    }

    #table-view .d-flex.align-items-start .text-start small {
        font-size: 0.75rem;
        line-height: 1.2;
    }

    /* Style pour la photo de l'appareil dans la galerie */
    .photo-appareil {
        position: relative;
    }

    .badge-appareil {
        position: absolute;
        top: 10px;
        left: 10px;
        background-color: rgba(13, 110, 253, 0.9);
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
        z-index: 2;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Style pour les photos en général */
    .photo-item {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }

    .photo-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.15);
    }

    .photo-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: all 0.3s ease;
    }

    .photo-item:hover img {
        transform: scale(1.05);
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
    
    // Vérifier d'abord s'il y a un paramètre view dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get('view');
    
    if (viewParam) {
        // Si un paramètre view est présent dans l'URL, l'utiliser et le sauvegarder
        console.log('URL view parameter found:', viewParam);
        localStorage.setItem('repairViewMode', viewParam);
    }
    
    // Ensuite seulement charger la préférence utilisateur (soit depuis l'URL, soit depuis localStorage)
    const savedViewMode = localStorage.getItem('repairViewMode') || 'cards';
    console.log('View mode to apply:', savedViewMode);
    
    // Trouver et cliquer sur le bouton correspondant au mode d'affichage
    const btn = document.querySelector(`.toggle-view[data-view="${savedViewMode}"]`);
    if (btn) {
        console.log('Clicking view mode button for:', savedViewMode);
        
        // Utiliser un délai minimal pour s'assurer que le DOM est prêt
        setTimeout(() => {
        btn.click();
        }, 10);
    } else {
        console.error('View mode button not found for:', savedViewMode);
    }
    
    // Fonction pour appliquer un filtre tout en conservant le mode d'affichage
    window.applyFilter = function(statut_ids) {
        // Récupérer le mode d'affichage actuel
        const viewMode = localStorage.getItem('repairViewMode') || 'cards';
        console.log('Applying filter with view mode:', viewMode);
        // Rediriger avec les bons paramètres
        window.location.href = `index.php?page=reparations&statut_ids=${statut_ids}&view=${viewMode}`;
    }
    
    // Modifier les liens de filtres pour utiliser la fonction applyFilter
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Si le bouton a un attribut data-category-id, il s'agit d'un filtre
            const categoryId = this.getAttribute('data-category-id');
            if (categoryId) {
                e.preventDefault();
                let statusIds;
                switch (categoryId) {
                    case '1': statusIds = '1,2,3'; break;
                    case '2': statusIds = '4,5'; break;
                    case '3': statusIds = '6,7,8'; break;
                    case '4': statusIds = '9,10'; break;
                    case '5': statusIds = '11,12,13'; break;
                    default: statusIds = '1,2,3,4,5';
                }
                window.applyFilter(statusIds);
            } else if (this.classList.contains('filter-btn')) {
                // C'est le bouton "Toutes"
                e.preventDefault();
                window.applyFilter('1,2,3,4,5');
            }
        });
    });
    
    // Appliquer des styles améliorés aux boutons au format tableau
    function applyButtonStyles() {
        // Boutons SMS
        document.querySelectorAll('#table-view .btn-soft-info').forEach(btn => {
            btn.style.backgroundColor = '#6610f2';
            btn.style.color = 'white';
            btn.style.boxShadow = '0 2px 4px rgba(102, 16, 242, 0.2)';
            btn.style.border = 'none';
            
            btn.addEventListener('mouseover', function() {
                this.style.backgroundColor = '#5a0dce';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(102, 16, 242, 0.3)';
            });
            
            btn.addEventListener('mouseout', function() {
                this.style.backgroundColor = '#6610f2';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(102, 16, 242, 0.2)';
            });
        });
        
        // Boutons corbeille (delete)
        document.querySelectorAll('#table-view .btn-soft-danger, #table-view .delete-repair').forEach(btn => {
            btn.style.backgroundColor = '#e74c3c';
            btn.style.color = 'white';
            btn.style.boxShadow = '0 2px 4px rgba(231, 76, 60, 0.2)';
            btn.style.border = 'none';
            
            btn.addEventListener('mouseover', function() {
                this.style.backgroundColor = '#c0392b';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(231, 76, 60, 0.3)';
            });
            
            btn.addEventListener('mouseout', function() {
                this.style.backgroundColor = '#e74c3c';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(231, 76, 60, 0.2)';
            });
        });
        
        // Boutons start-repair
        document.querySelectorAll('#table-view .btn-soft-primary, #table-view .start-repair').forEach(btn => {
            btn.style.background = 'linear-gradient(135deg, #0d6efd, #3a86ff)';
            btn.style.color = 'white';
            btn.style.boxShadow = '0 2px 4px rgba(13, 110, 253, 0.2)';
            btn.style.border = 'none';
            
            btn.addEventListener('mouseover', function() {
                this.style.background = 'linear-gradient(135deg, #3a86ff, #0d6efd)';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 8px rgba(13, 110, 253, 0.3)';
            });
            
            btn.addEventListener('mouseout', function() {
                this.style.background = 'linear-gradient(135deg, #0d6efd, #3a86ff)';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(13, 110, 253, 0.2)';
            });
        });
    }
    
    // Appliquer les styles une fois que la vue est chargée
    setTimeout(applyButtonStyles, 100);
    
    // Réappliquer les styles si on change de vue
    document.querySelectorAll('.toggle-view').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimeout(applyButtonStyles, 100);
        });
    });
});
</script>

<script>
// Fonction pour améliorer l'affichage du tableau
document.addEventListener('DOMContentLoaded', function() {
    function improveTableLayout() {
        // Appliquer les styles d'alignement à gauche pour les cellules de tableau
        const tableCells = document.querySelectorAll('#table-view table td, #table-view table th');
        tableCells.forEach(cell => {
            cell.style.textAlign = 'left';
        });
        
        // Seule la dernière colonne (actions) reste alignée à droite
        const lastCells = document.querySelectorAll('#table-view table td:last-child, #table-view table th:last-child');
        lastCells.forEach(cell => {
            cell.style.textAlign = 'right';
        });
        
        // Modifier l'affichage de la colonne appareil pour n'afficher que le modèle
        const appareilCells = document.querySelectorAll('#table-view .d-none.d-md-table-cell:nth-child(3)');
        appareilCells.forEach(cell => {
            // Conserver uniquement le modèle (dernière partie du texte après les espaces)
            const text = cell.innerText;
            const parts = text.split(' ');
            if (parts.length > 1) {
                cell.innerText = parts[parts.length - 1]; // Prendre le dernier élément (modèle)
            }
        });
    }
    
    // Appliquer au chargement
    setTimeout(improveTableLayout, 200);
    
    // Réappliquer lors du changement de vue
    document.querySelectorAll('.toggle-view').forEach(btn => {
        btn.addEventListener('click', function() {
            setTimeout(improveTableLayout, 200);
        });
    });
});
</script>

<div class="page-container">
    <!-- Filtres rapides pour tous les écrans -->
    <div class="mb-3">
        <div class="filter-buttons" role="group" aria-label="Filtres rapides">
            <!-- Bouton Nouvelle -->
            <a href="javascript:void(0);" 
               class="filter-btn droppable <?php echo $statut_ids == '1,2,3' ? 'active' : ''; ?>"
               data-category-id="1">
                <i class="fas fa-plus-circle"></i>
                <span>Nouvelle</span>
                <span class="count"><?php echo $total_nouvelles ?? 0; ?></span>
            </a>
            
            <!-- Bouton En cours -->
            <a href="javascript:void(0);" 
               class="filter-btn droppable <?php echo $statut_ids == '4,5' ? 'active' : ''; ?>"
               data-category-id="2">
                <i class="fas fa-spinner"></i>
                <span>En cours</span>
                <span class="count"><?php echo $total_en_cours ?? 0; ?></span>
            </a>
            
            <!-- Bouton En attente -->
            <a href="javascript:void(0);" 
               class="filter-btn droppable <?php echo $statut_ids == '6,7,8' ? 'active' : ''; ?>"
               data-category-id="3">
                <i class="fas fa-clock"></i>
                <span>En attente</span>
                <span class="count"><?php echo $total_en_attente ?? 0; ?></span>
            </a>
            
            <!-- Bouton Terminé -->
            <a href="javascript:void(0);" 
               class="filter-btn droppable <?php echo $statut_ids == '9,10' ? 'active' : ''; ?>"
               data-category-id="4">
                <i class="fas fa-check-circle"></i>
                <span>Terminé</span>
                <span class="count"><?php echo $total_termines ?? 0; ?></span>
            </a>
            
            <!-- Bouton Toutes -->
            <a href="javascript:void(0);" 
               class="filter-btn <?php echo ($statut_ids == '1,2,3,4,5' || (empty($statut) && empty($_GET['statut_ids']))) ? 'active' : ''; ?>">
                <i class="fas fa-list"></i>
                <span>Récentes</span>
                <span class="count"><?php echo $total_reparations ?? 0; ?></span>
            </a>
            
            <!-- Bouton Archivé -->
            <a href="javascript:void(0);" 
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
                                    <tr class="repair-row draggable-card" data-id="<?php echo $reparation['id']; ?>" data-repair-id="<?php echo $reparation['id']; ?>" data-status="<?php echo $reparation['statut']; ?>" draggable="true">
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
                                            <div class="d-flex align-items-start">
                                                <div class="d-flex flex-column align-items-center me-3">
                                                    <div class="avatar-circle bg-primary bg-opacity-10 text-primary mb-2" style="width: 36px; height: 36px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                                    <div class="avatar-circle bg-success bg-opacity-10 text-success" style="width: 25px; height: 25px; font-size: 12px;">
                                                        <i class="fas fa-phone"></i>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-start">
                                                    <h6 class="mb-0 fw-bold">
                                                        <?php echo htmlspecialchars(($reparation['client_nom'] ?? '') . ' ' . ($reparation['client_prenom'] ?? '')); ?>
                                                    </h6>
                                                    <small class="text-muted d-block">ID: <?php echo $reparation['id']; ?></small>
                                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                                    <small class="text-muted d-block"><?php echo htmlspecialchars($reparation['client_telephone']); ?></small>
                                                    <?php endif; ?>
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
                                                <?php if (!empty($reparation['client_telephone'])): ?>
                                                <a href="tel:<?php echo htmlspecialchars($reparation['client_telephone']); ?>" 
                                                   class="btn btn-sm btn-success" 
                                                   title="Appeler">
                                                    <i class="fas fa-phone-alt"></i>
                                                </a>
                                                <?php endif; ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-soft-primary rounded-pill start-repair" 
                                                        data-id="<?php echo $reparation['id']; ?>"
                                                        title="Démarrer">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <?php if (!empty($reparation['client_telephone'])): ?>
                                                <a href="#" 
                                                   class="btn btn-sm btn-soft-info rounded-pill" 
                                                   title="SMS"
                                                   data-client-id="<?php echo $reparation['client_id']; ?>"
                                                   data-client-nom="<?php echo htmlspecialchars($reparation['client_nom']); ?>"
                                                   data-client-prenom="<?php echo htmlspecialchars($reparation['client_prenom']); ?>"
                                                   data-client-tel="<?php echo htmlspecialchars($reparation['client_telephone']); ?>"
                                                   onclick="openSmsModal(
                                                       '<?php echo $reparation['client_id']; ?>', 
                                                       '<?php echo htmlspecialchars($reparation['client_nom']); ?>', 
                                                       '<?php echo htmlspecialchars($reparation['client_prenom']); ?>', 
                                                       '<?php echo htmlspecialchars($reparation['client_telephone']); ?>'
                                                   ); return false;">
                                                    <i class="fas fa-comment-alt"></i>
                                                </a>
                                                <?php endif; ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-info view-repair-details" 
                                                        data-id="<?php echo $reparation['id']; ?>"
                                                        title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-soft-danger rounded-pill delete-repair" 
                                                        data-id="<?php echo $reparation['id']; ?>"
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
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
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <?php if (!empty($reparation['client_telephone'])): ?>
                                            <a href="tel:<?php echo htmlspecialchars($reparation['client_telephone']); ?>" 
                                               class="btn btn-sm btn-success rounded-pill" 
                                               title="Appeler">
                                                <i class="fas fa-phone-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary rounded-pill start-repair" 
                                                    data-id="<?php echo $reparation['id']; ?>"
                                                    title="Démarrer">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <?php if (!empty($reparation['client_telephone'])): ?>
                                            <a href="#" 
                                               class="btn btn-sm btn-info rounded-pill" 
                                               title="SMS"
                                               data-client-id="<?php echo $reparation['client_id']; ?>"
                                               data-client-nom="<?php echo htmlspecialchars($reparation['client_nom']); ?>"
                                               data-client-prenom="<?php echo htmlspecialchars($reparation['client_prenom']); ?>"
                                               data-client-tel="<?php echo htmlspecialchars($reparation['client_telephone']); ?>"
                                               onclick="openSmsModal(
                                                   '<?php echo $reparation['client_id']; ?>', 
                                                   '<?php echo htmlspecialchars($reparation['client_nom']); ?>', 
                                                   '<?php echo htmlspecialchars($reparation['client_prenom']); ?>', 
                                                   '<?php echo htmlspecialchars($reparation['client_telephone']); ?>'
                                               ); return false;">
                                                <i class="fas fa-comment-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-soft-danger rounded-pill delete-repair" 
                                                    data-id="<?php echo $reparation['id']; ?>"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                    </div>
                    <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                            <div class="no-results-container">
                                <i class="fas fa-clipboard-list text-muted fa-3x mb-3"></i>
                                <p class="text-muted">Aucune réparation trouvée.</p>
                                </div>
                            </div>
                <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

<!-- Scripts pour le drag & drop des statuts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le drag & drop pour les cartes de réparation
    initCardDragAndDrop();
    
    // Fonctions pour le drag & drop des cartes
    function initCardDragAndDrop() {
        // Sélectionner toutes les cartes de réparation et les lignes du tableau
        const draggableCards = document.querySelectorAll('.draggable-card');
        const dropZones = document.querySelectorAll('.filter-btn.droppable');
        
        // Variables pour le ghost element
        let ghostElement = null;
        let draggedCard = null;
        
        console.log('Initializing drag & drop with', draggableCards.length, 'cards and', dropZones.length, 'drop zones');
        
        // Ajouter les écouteurs d'événements pour les cartes et les lignes
        draggableCards.forEach(card => {
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
            
            // Empêcher la propagation du clic pour les boutons à l'intérieur des cartes
            const buttons = card.querySelectorAll('button, a');
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
                    const allCards = document.querySelectorAll('.dashboard-card, .draggable-card');
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
            
            // Vérifier si c'est une ligne de tableau ou une carte
            if (sourceElement.tagName === 'TR') {
                // C'est une ligne de tableau
                const statusCell = sourceElement.querySelector('td:nth-child(6)');
                if (statusCell) {
                    const badge = statusCell.querySelector('.status-indicator');
                    if (badge) ghostElement.appendChild(badge.cloneNode(true));
                }
                
                const clientInfo = sourceElement.querySelector('td:nth-child(2) h6');
                if (clientInfo) ghostElement.appendChild(clientInfo.cloneNode(true));
            } else {
                // C'est une carte
                const statusBadge = sourceElement.querySelector('.status-indicator');
                if (statusBadge) ghostElement.appendChild(statusBadge.cloneNode(true));
                
                const deviceInfo = sourceElement.querySelector('.mb-0');
                if (deviceInfo) ghostElement.appendChild(deviceInfo.cloneNode(true));
            }
            
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
        });
        
        /**
 * Affiche une notification temporaire
 */
function showNotification(message, type = 'info') {
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
    
    console.log('Récupération des statuts pour la catégorie:', categoryId);
        
        // Récupérer les statuts disponibles pour cette catégorie
        fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
            console.log('Réponse de get_statuts_by_category:', data);
            
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
                    console.log('Annulation de la sélection de statut');
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
    
    console.log('Mise à jour du statut:', statusId, 'pour la réparation:', repairId);
        
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
        console.log('Réponse de update_repair_specific_status:', data);
        
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

<!-- Modal de détails de réparation amélioré -->
<div class="modal fade" id="repairDetailsModal" tabindex="-1" aria-labelledby="repairDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title d-flex align-items-center" id="repairDetailsModalLabel">
                    <i class="fas fa-tools me-2 text-primary"></i>
                    Détails de la réparation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Loader -->
                <div id="repairDetailsLoader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-3 text-muted">Chargement des détails de la réparation...</p>
                </div>
                
                <!-- Contenu des détails -->
                <div id="repairDetailsContent" style="display: none;"></div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Script pour le modal de réparation -->
<script src="assets/js/repair-modal.js"></script>
<script src="assets/js/price-numpad.js"></script>
<script src="assets/js/status-modal.js"></script>

<style>
/* Styles pour le modal de réparation */
#repairDetailsModal .modal-dialog {
    max-width: 70%;
}

@media (max-width: 1200px) {
    #repairDetailsModal .modal-dialog {
        max-width: 80%;
    }
}

@media (max-width: 992px) {
    #repairDetailsModal .modal-dialog {
        max-width: 85%;
    }
}

@media (max-width: 768px) {
    #repairDetailsModal .modal-dialog {
        max-width: 95%;
    }
}

#repairDetailsModal .modal-content {
    border: none;
    border-radius: 0.75rem;
    overflow: hidden;
}

#repairDetailsModal .modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    padding: 1rem 1.5rem;
}

#repairDetailsModal .modal-body {
    padding: 1.5rem;
}

#repairDetailsModal .modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    padding: 1rem 1.5rem;
}

/* Styles pour les cartes */
#repairDetailsModal .card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.2s ease;
    border-radius: 0.5rem;
    overflow: hidden;
    height: 100%;
}

#repairDetailsModal .card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#repairDetailsModal .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1rem 1.25rem;
}

#repairDetailsModal .card-body {
    padding: 1.25rem;
}

/* Styles pour le résumé de réparation */
.repair-summary {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    width: 100%;
}

.repair-summary-item {
    flex: 1;
    min-width: 180px;
    display: flex;
    align-items: center;
    padding: 1rem;
    border-right: 1px solid rgba(0, 0, 0, 0.05);
}

.repair-summary-item:last-child {
    border-right: none;
}

.icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 1.2rem;
}

.repair-summary-item .info .label {
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.repair-summary-item .info .value {
    font-weight: 600;
    font-size: 1rem;
}

/* Styles pour les informations client */
.contact-info {
    margin-top: 1rem;
}

.contact-info-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: 0.5rem;
}

.contact-info-item:last-child {
    margin-bottom: 0;
}

.contact-info-item i {
    font-size: 1.1rem;
    width: 25px;
    margin-right: 0.75rem;
    text-align: center;
}

/* Styles pour les informations appareil */
.device-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.device-info-item {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: 0.5rem;
}

.device-info-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.device-info-value {
    font-weight: 500;
    color: #343a40;
}

.problem-description {
    white-space: pre-line;
    line-height: 1.5;
}

.device-photo {
    margin-top: 1.5rem;
    text-align: center;
}

.device-photo img {
    max-height: 300px;
    object-fit: contain;
    border-radius: 0.5rem;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

/* Styles pour les notes techniques */
.technical-notes {
    white-space: pre-line;
    line-height: 1.6;
    padding: 0.75rem;
    background-color: rgba(0, 0, 0, 0.02);
    border-radius: 0.5rem;
    font-size: 0.95rem;
}

/* Styles pour les photos */
.photo-gallery {
    margin: 0;
}

#repairDetailsModal .photo-item {
    position: relative;
    overflow: hidden;
    border-radius: 0.375rem;
    cursor: pointer;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
    aspect-ratio: 1/1;
}

#repairDetailsModal .photo-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#repairDetailsModal .photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

#repairDetailsModal .photo-item:hover img {
    transform: scale(1.05);
}

#repairDetailsModal .photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.6));
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    padding: 0.75rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

#repairDetailsModal .photo-item:hover .photo-overlay {
    opacity: 1;
}

#repairDetailsModal .photo-actions {
    display: flex;
    gap: 0.5rem;
}

/* Styles pour l'état vide */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #6c757d;
}

/* Styles pour les boutons d'action */
.action-buttons {
    margin: 0 -0.5rem;
}

#repairDetailsModal .action-btn {
    height: 100%;
    padding: 0.5rem;
    text-align: center;
    transition: all 0.3s ease;
    border-radius: 0.5rem;
    font-weight: 500;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

#repairDetailsModal .action-btn i {
    font-size: 1.2rem;
    margin-bottom: 0.25rem;
}

#repairDetailsModal .action-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Responsive adjustments */
@media (max-width: 992px) {
    #repairDetailsModal .modal-dialog {
        max-width: 95%;
    }
    
    .repair-summary-item {
        min-width: 150px;
        padding: 1rem;
    }
    
    .icon-wrapper {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
    
    #repairDetailsModal .action-btn {
        padding: 0.75rem 0.5rem;
        font-size: 0.875rem;
    }
    
    #repairDetailsModal .action-btn i {
        font-size: 1.25rem;
    }
}

@media (max-width: 768px) {
    #repairDetailsModal .modal-body {
        padding: 1rem;
    }
    
    #repairDetailsModal .card-body {
        padding: 1rem;
    }
    
    .repair-summary-item {
        flex: 0 0 50%;
        min-width: unset;
        border-right: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .repair-summary-item:nth-child(even) {
        border-left: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .repair-summary-item:nth-last-child(-n+2) {
        border-bottom: none;
    }
    
    .repair-summary-item:last-child:nth-child(odd) {
        border-bottom: none;
    }
    
    .device-photo img {
        max-height: 200px;
    }
}

/* Animation de transition */
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

<!-- Modal pour le démarrage d'une réparation déjà active -->
<div class="modal fade" id="activeRepairModal" tabindex="-1" aria-labelledby="activeRepairModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="activeRepairModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Vous avez déjà une réparation active</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <p>Vous avez déjà une réparation active <strong id="activeRepairId"></strong>. Vous devez d'abord la terminer avant de pouvoir en démarrer une nouvelle.</p>
                </div>
                
                <div class="card border mb-3">
                    <div class="card-header">Détails de la réparation active</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Appareil:</strong> <span id="activeRepairDevice"></span></p>
                                <p><strong>Client:</strong> <span id="activeRepairClient"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Statut:</strong> <span id="activeRepairStatus"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5 class="mb-3">Comment souhaitez-vous procéder ?</h5>
                
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="mb-3 text-center">Terminer la réparation active</h6>
                                <div class="final-status-buttons">
                                    <p class="mb-3 text-center">Choisir le statut final :</p>
                                    <div class="d-grid gap-2 mb-3">
                                        <button type="button" class="btn btn-success complete-btn" data-status="reparation_effectue">
                                            <i class="fas fa-check-circle me-2"></i>Réparation Effectuée
                                        </button>
                                        <button type="button" class="btn btn-danger complete-btn" data-status="reparation_annule">
                                            <i class="fas fa-times-circle me-2"></i>Réparation Annulée
                                        </button>
                                        <button type="button" class="btn btn-warning complete-btn" data-status="en_attente_responsable">
                                            <i class="fas fa-user-clock me-2"></i>En attente d'un responsable
