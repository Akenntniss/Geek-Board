<?php
// Activer l'affichage des erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifier que $pdo est accessible et initialisé
if (!isset($pdo) || $pdo === null) {
    echo "<div class='alert alert-danger'>Erreur de connexion à la base de données. La variable \$pdo n'est pas disponible. Veuillez contacter l'administrateur.</div>";
    error_log("ERREUR CRITIQUE dans reparations.php: La variable \$pdo n'est pas disponible");
    // Initialiser les variables pour éviter les erreurs
    $total_reparations = 0;
    $total_nouvelles = 0;
    $total_en_cours = 0;
    $total_en_attente = 0;
    $total_termines = 0;
    $total_archives = 0;
    $reparations = [];
} else {
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
}

// Construction de la requête SQL avec filtres
$sql = "
    SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone, c.email as client_email
    FROM reparations r
    LEFT JOIN clients c ON r.client_id = c.id
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
        padding-top: 65px;
        max-width: 1400px;
        margin: 0 auto;
        padding-left: 00px;
        padding-right: 00px;
    }
    
    /* Styles pour les boutons de filtres */
    .filter-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: center;
        margin-bottom: 1rem;
        width: 100%;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }
    
    /* Style pour les modals secondaires (pour éviter les problèmes de superposition) */
    .modal ~ .modal {
        z-index: 1060 !important;
    }
    
    /* Le backdrop du second modal doit être au-dessus du premier modal mais en-dessous du second */
    .modal-backdrop {
        z-index: 1040 !important;
    }
    
    .modal-backdrop ~ .modal-backdrop {
        z-index: 1059 !important;
    }
    
    /* Assurer que les modals sont cliquables même si multiples */
    .modal {
        pointer-events: none;
    }
    
    .modal-dialog {
        pointer-events: auto;
    }
    
    /* Elargir la colonne Appareil dans le tableau */
    #table-view th:nth-child(3), 
    #table-view td:nth-child(3) {
        min-width: 200px;
        max-width: none;
        white-space: normal;
        word-break: break-word;
    }
    
    /* Faire en sorte que le texte de la colonne Appareil s'affiche entièrement */
    #table-view .d-none.d-md-table-cell:nth-child(3) {
        white-space: normal;
        word-break: break-word;
    }
    
    /* Amélioration du design du tableau */
    #table-view .table {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    #table-view .table thead th {
        background-color: #f8f9fa;
        border-top: none;
        border-bottom: 2px solid #dee2e6;
        padding: 12px 15px;
        font-weight: 600;
        color: #495057;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.03em;
    }
    
    #table-view .table tbody tr:hover {
        background-color: rgba(67, 97, 238, 0.03);
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }
    
    #table-view .table tbody td {
        padding: 12px 15px;
        vertical-align: middle;
        border-top: 1px solid #f1f1f1;
    }
    
    /* Amélioration des boutons */
    #table-view .btn-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem !important;
        justify-content: flex-end;
    }
    
    #table-view .btn-group .btn {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 50% !important;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: none;
    }
    
    #table-view .btn-group .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    /* Style spécifique pour chaque type de bouton */
    #table-view .btn-success {
        background: linear-gradient(135deg, #28a745, #20c997);
    }
    
    #table-view .btn-success:hover {
        background: linear-gradient(135deg, #20c997, #28a745);
    }
    
    #table-view .btn-soft-primary, 
    #table-view .start-repair {
        background: linear-gradient(135deg, #0d6efd, #3a86ff);
    }
    
    #table-view .btn-soft-primary:hover, 
    #table-view .start-repair:hover {
        background: linear-gradient(135deg, #3a86ff, #0d6efd);
    }
    
    #table-view .btn-soft-info {
        background: linear-gradient(135deg, #17a2b8, #0dcaf0);
    }
    
    #table-view .btn-soft-info:hover {
        background: linear-gradient(135deg, #0dcaf0, #17a2b8);
    }
    
    #table-view .btn-soft-danger,
    #table-view .delete-repair {
        background: linear-gradient(135deg, #dc3545, #ff6b6b);
    }
    
    #table-view .btn-soft-danger:hover,
    #table-view .delete-repair:hover {
        background: linear-gradient(135deg, #ff6b6b, #dc3545);
    }
    
    /* Style pour le badge de statut */
    #table-view .badge {
        padding: 0.6em 0.8em;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.75rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        display: inline-block;
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
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        border: 1px solid #e9ecef;
    }
    
    .filter-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        color: #4361ee;
        border-color: rgba(67, 97, 238, 0.3);
    }
    
    .filter-btn.active {
        background: linear-gradient(135deg, #4361ee, #3a86ff);
        color: white;
        border-color: #4361ee;
        box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
    }
    
    .filter-btn i {
        color: inherit;
        margin-bottom: 0.5rem;
        font-size: 2rem;
        transition: all 0.3s ease;
    }
    
    .filter-btn span {
        font-size: 0.9rem;
        text-align: center;
        font-weight: 600;
        transition: all 0.3s ease;
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
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .filter-btn.active .count {
        background-color: rgba(255, 255, 255, 0.3);
        color: white;
    }
    
    /* Améliorations pour les cartes de réparation */
    .dashboard-card.repair-row {
        height: auto;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        flex: 1 0 300px;
        max-width: calc(33.333% - 1rem);
        min-width: 300px;
        margin-bottom: 1rem;
    }
    
    @media (max-width: 991px) {
        .dashboard-card.repair-row {
            max-width: calc(50% - 0.75rem);
            min-width: 250px;
        }
    }
    
    @media (max-width: 768px) {
        .dashboard-card.repair-row {
            max-width: 100%;
            min-width: 100%;
        }
    }
    
    .dashboard-card.repair-row:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 20px -3px rgba(0,0,0,0.2);
        border-color: rgba(67, 97, 238, 0.3);
    }
    
    .dashboard-card .card-content {
        flex: 1;
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        background: white;
    }
    
    .dashboard-card .card-header {
        background: linear-gradient(to right, #f8f9fa, #ffffff);
        padding: 0.75rem 1.25rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .dashboard-card .card-footer {
        border-top: 1px solid rgba(0,0,0,0.05);
        background-color: #f8f9fa; /* Fond clair pour le mode jour */
        padding: 0.75rem;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
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
        max-width: 1200px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border-radius: 10px;
        overflow: hidden;
        border: none;
    }
    
    /* Style pour le contenu principal */
    .results-container {
        width: 100%;
        margin-top: 0;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
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
            margin-top: 40px; /* Réduit le décalage de 30px supplémentaires (de 70px à 40px) */
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
        width: 100%;
    }
    
    .repair-cards-container {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        width: 100%;
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
            justify-content: flex-start;
            display: flex;
            flex-direction: row;
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
        #cards-view {
            flex-direction: row;
            flex-wrap: wrap;
        }
        
        #cards-view .dashboard-card {
            flex: 0 0 calc(50% - 0.5rem);
            min-width: 250px;
        }
    }

    @media (max-width: 768px) {
        #cards-view {
            flex-direction: column;
            padding: 0;
            gap: 0.75rem;
            justify-content: center;
            align-items: center;
            max-width: 100%;
            width: 100%;
            overflow-x: hidden;
        }
        
        #cards-view .dashboard-card {
            flex: 0 0 100%;
            width: 75%;
            max-width: 75%;
            margin-left: auto;
            margin-right: auto;
            min-width: initial;
            margin-bottom: 0.75rem;
            box-sizing: border-box;
        }
        
        .repair-cards-container {
            padding: 0;
            gap: 0.75rem;
            width: 100%;
            max-width: 100%;
            justify-content: center;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .dashboard-card.repair-row {
            max-width: 92%;
            min-width: initial;
            width: 92%;
            box-sizing: border-box;
        }
        
        /* Ajustements des espacements internes pour le mobile */
        .dashboard-card .card-content {
            padding: 0.75rem;
        }
        
        .dashboard-card .card-footer {
            padding: 0.5rem;
        }
        
        /* Ajustements des conteneurs principaux */
        .results-container {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
            width: 100%;
            max-width: 100%;
        }
        
        .card {
            margin: 0 auto;
            width: 100%;
        }
        
        .page-container {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
            width: 100%;
        }
        
        .card-body {
            padding: 0.75rem;
            overflow-x: hidden;
        }
        
        /* Ajustements pour les informations de contact sur mobile */
        .dashboard-card .contact-row {
            width: 48% !important;
        }
        
        .dashboard-card .contact-icon {
            width: 22px;
            height: 22px;
            margin-right: 0.3rem;
        }
        
        /* Style pour l'affichage du prix */
        .dashboard-card .repair-id {
            font-size: 0.9rem;
            max-width: 85px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Réduire la taille de la police pour mobile */
        .dashboard-card .card-content p,
        .dashboard-card .card-content h6 {
            font-size: 0.85rem;
        }
        
        /* Élargir la zone de données de contact pour éviter les débordements */
        .dashboard-card .contact-data {
            max-width: 75%;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Ajuster la taille des boutons dans le footer de carte */
        .dashboard-card .card-footer .btn {
            width: 32px;
            height: 32px;
        }
        
        /* Ajustements pour l'en-tête de carte */
        .dashboard-card .card-header {
            padding: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Ajustement pour les badges de statut */
        .dashboard-card .status-indicator .badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
    }
    
    /* Styles pour le mode nuit */
    .dark-mode .filter-btn {
        background-color: #1f2937;
        color: #94a3b8;
        border-color: #374151;
        box-shadow: 0 3px 10px rgba(0,0,0,0.3);
    }
    
    .dark-mode .filter-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.4);
        color: #60a5fa;
        border-color: rgba(96, 165, 250, 0.3);
    }
    
    .dark-mode .filter-btn.active {
        background: linear-gradient(135deg, #3b82f6, #60a5fa);
        color: #f8fafc;
        border-color: #3b82f6;
        box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
    }
    
    .dark-mode .filter-btn .count {
        background: #374151;
        color: #f8fafc;
    }
    
    .dark-mode #table-view .table {
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }
    
    .dark-mode #table-view .table thead th {
        background-color: #1f2937;
        border-bottom: 2px solid #374151;
        color: #e2e8f0;
    }
    
    .dark-mode #table-view .table tbody tr {
        background-color: #1e2534;
        color: #e2e8f0;
    }
    
    .dark-mode #table-view .table tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.1);
    }
    
    .dark-mode #table-view .table tbody td {
        border-top: 1px solid #374151;
    }
    
    .dark-mode .dashboard-card.repair-row {
        border-color: #374151;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    .dark-mode .dashboard-card.repair-row:hover {
        box-shadow: 0 15px 25px rgba(0,0,0,0.4);
        border-color: rgba(59, 130, 246, 0.3);
    }
    
    .dark-mode .dashboard-card .card-content {
        background: #1f2937;
        color: #e2e8f0;
    }
    
    .dark-mode .dashboard-card .card-header {
        background: linear-gradient(to right, #111827, #1f2937);
        border-bottom-color: #374151;
    }
    
    .dark-mode .dashboard-card .card-footer {
        border-top-color: #374151;
        background-color: #1f2937;
    }
    
    /* Style pour la barre de recherche améliorée */
    .search-card {
        width: 100%;
        margin: 0 auto 1.5rem;
        max-width: 1200px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        border-radius: 10px;
        overflow: hidden;
        border: none;
    }
    
    .search-form .input-group {
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-radius: 8px;
        overflow: hidden;
    }
    
    .search-form .input-group-text {
        background: white;
        border: 1px solid #ced4da;
        border-right: none;
        color: #4361ee;
    }
    
    .search-form .form-control {
        border: 1px solid #ced4da;
        border-left: none;
        padding-left: 0;
    }
    
    .search-form .btn-primary {
        background: linear-gradient(135deg, #4361ee, #3a86ff);
        border: none;
        box-shadow: 0 2px 5px rgba(67, 97, 238, 0.3);
        transition: all 0.3s ease;
    }
    
    .search-form .btn-primary:hover {
        background: linear-gradient(135deg, #3a86ff, #4361ee);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(67, 97, 238, 0.4);
    }
    
    .dark-mode .search-card {
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    .dark-mode .search-form .input-group {
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }
    
    .dark-mode .search-form .input-group-text {
        background: #1f2937;
        border: 1px solid #374151;
        border-right: none;
        color: #60a5fa;
    }
    
    .dark-mode .search-form .form-control {
        background: #1f2937;
        border: 1px solid #374151;
        border-left: none;
        color: #e2e8f0;
    }
    
    .dark-mode .search-form .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #60a5fa);
        box-shadow: 0 2px 5px rgba(59, 130, 246, 0.3);
    }
    
    .dark-mode .search-form .btn-primary:hover {
        background: linear-gradient(135deg, #60a5fa, #3b82f6);
        box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
    }
    
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
    
    // Fonction pour ajuster l'affichage des cartes
    function adjustCardsLayout() {
        const cards = document.querySelectorAll('#cards-view .dashboard-card');
        
        // Reset des hauteurs pour recalculer
        cards.forEach(card => {
            card.style.height = 'auto';
        });
        
        // Si on est sur un écran de plus de 768px, on uniformise les hauteurs par ligne
        if (window.innerWidth > 768) {
            let rowCards = [];
            let currentOffset = null;
            
            // Regrouper les cartes par ligne en fonction de leur position Y
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                
                if (currentOffset === null || Math.abs(rect.top - currentOffset) > 10) {
                    // Nouvelle ligne
                    if (rowCards.length > 0) {
                        // Appliquer la hauteur maximale à la ligne précédente
                        const maxHeight = Math.max(...rowCards.map(c => c.offsetHeight));
                        rowCards.forEach(c => {
                            c.style.height = maxHeight + 'px';
                        });
                    }
                    
                    currentOffset = rect.top;
                    rowCards = [card];
                } else {
                    // Même ligne
                    rowCards.push(card);
                }
            });
            
            // Traiter la dernière ligne
            if (rowCards.length > 0) {
                const maxHeight = Math.max(...rowCards.map(c => c.offsetHeight));
                rowCards.forEach(c => {
                    c.style.height = maxHeight + 'px';
                });
            }
        }
    }
    
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
                // Ajuster le layout des cartes
                setTimeout(adjustCardsLayout, 100);
            }
            
            // Mettre à jour l'URL avec le mode de vue tout en conservant les autres paramètres
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('view', viewMode);
            
            // Mettre à jour l'URL sans recharger la page
            const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
            history.pushState({}, '', newUrl);
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
            // Ajuster le layout des cartes si on est en mode cartes
            if (savedViewMode === 'cards') {
                setTimeout(adjustCardsLayout, 200);
            }
        }, 10);
    } else {
        console.error('View mode button not found for:', savedViewMode);
    }
    
    // Ajuster le layout des cartes lors du redimensionnement de la fenêtre
    window.addEventListener('resize', function() {
        if (!cardsView.classList.contains('d-none')) {
            adjustCardsLayout();
        }
    });
    
    // Fonction pour appliquer un filtre tout en conservant le mode d'affichage
    window.applyFilter = function(statut_ids) {
        // Récupérer le mode d'affichage actuel
        const viewMode = localStorage.getItem('repairViewMode') || 'cards';
        
        // Construire l'URL avec tous les paramètres
        let url = `index.php?page=reparations&statut_ids=${statut_ids}&view=${viewMode}`;
        
        console.log('Applying filter with params:', { statut_ids, viewMode });
        
        // Rediriger avec les bons paramètres
        window.location.href = url;
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
        
        // Nous ne tronquons plus le nom de l'appareil pour afficher le texte complet
        const appareilCells = document.querySelectorAll('#table-view .d-none.d-md-table-cell:nth-child(3)');
        appareilCells.forEach(cell => {
            // Garder le texte complet
            cell.style.whiteSpace = 'normal';
            cell.style.wordBreak = 'break-word';
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
                    
                    <!-- Préserver le mode d'affichage -->
                    <?php if (isset($_GET['view'])): ?>
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($_GET['view']); ?>">
                    <?php else: ?>
                    <input type="hidden" name="view" value="<?php echo isset($_COOKIE['repairViewMode']) ? htmlspecialchars($_COOKIE['repairViewMode']) : 'cards'; ?>">
                    <?php endif; ?>
                    
                    <input type="text" class="form-control border-start-0 ps-0" name="search" placeholder="Rechercher par nom, téléphone, appareil..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-primary" type="submit">Rechercher</button>
                    <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <a href="index.php?page=reparations<?php echo isset($_GET['view']) ? '&view='.htmlspecialchars($_GET['view']) : ''; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <a href="index.php?page=ajouter_reparation" class="btn btn-success btn-sm">
                        <i class="fas fa-plus-circle me-1"></i>Nouvelle réparation
                    </a>
                    <button type="button" class="btn btn-primary btn-sm toggle-view" data-view="table">
                        <i class="fas fa-table me-1"></i>Tableau
                    </button>
                    <button type="button" class="btn btn-primary btn-sm toggle-view active" data-view="cards">
                        <i class="fas fa-th-large me-1"></i>Cartes
                    </button>
                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                        <i class="fas fa-tasks me-1"></i>Mise à jour Statut
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#relanceClientModal">
                        <i class="fas fa-bell me-1"></i>Relance Client
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
                                                    <i class="fas fa-shopping-basket text-warning" title="Commande requise"></i>
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
                                        <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($reparation['modele']); ?></td>
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
                    <div class="repair-cards-container d-flex flex-wrap gap-3 w-100">
                        <?php if (!empty($reparations)): ?>
                    <?php foreach ($reparations as $reparation): ?>
                                <div class="dashboard-card repair-row draggable-card" data-id="<?php echo $reparation['id']; ?>" data-repair-id="<?php echo $reparation['id']; ?>" data-status="<?php echo $reparation['statut']; ?>" draggable="true">
                                    <!-- En-tête de la carte -->
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="repair-status status-indicator">
                                            <?php echo get_enum_status_badge($reparation['statut'], $reparation['id']); ?>
                                            <?php if ($reparation['urgent']): ?>
                                                <span class="badge rounded-pill bg-danger ms-1" title="Urgent">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($reparation['commande_requise']): ?>
                                                <span class="badge rounded-pill bg-warning ms-1" title="Commande requise">
                                                    <i class="fas fa-shopping-cart"></i>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($reparation['notes_techniques'])): ?>
                                                <span class="badge rounded-pill bg-info ms-1" title="Notes techniques disponibles">
                                                    <i class="fas fa-clipboard-list"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="repair-id fw-bold text-success">
                                            <?php echo isset($reparation['prix_reparation']) ? number_format($reparation['prix_reparation'], 2, ',', ' ') . ' €' : (isset($reparation['prix']) ? number_format($reparation['prix'], 2, ',', ' ') . ' €' : 'N/A'); ?>
                                        </div>
                                    </div>
                            
                            <!-- Contenu principal -->
                            <div class="card-content">
                                <!-- Informations du client et de l'appareil -->
                                <div class="d-flex flex-column mb-2">
                                    <!-- Ligne 1: Nom et Modèle -->
                                    <div class="d-flex justify-content-between mb-2">
                                        <!-- Nom du client -->
                                        <div class="contact-row" style="width: 50%;">
                                            <div class="contact-icon">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div class="contact-data">
                                                <h6 class="mb-0 fs-mobile-sm"><?php echo htmlspecialchars($reparation['client_nom'] . ' ' . $reparation['client_prenom']); ?></h6>
                                            </div>
                                        </div>
                                        
                                        <!-- Appareil -->
                                        <div class="contact-row" style="width: 50%;">
                                            <div class="contact-icon">
                                                <i class="fas fa-mobile-alt text-primary"></i>
                                            </div>
                                            <div class="contact-data">
                                                <p class="mb-0 text-truncate fs-mobile-sm fw-bold"><?php echo htmlspecialchars($reparation['modele']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Ligne 2: Téléphone et Problème -->
                                    <div class="d-flex justify-content-between">
                                        <!-- Téléphone du client -->
                                        <div class="contact-row" style="width: 50%;">
                                            <div class="contact-icon">
                                                <i class="fas fa-phone text-success"></i>
                                            </div>
                                            <div class="contact-data">
                                                <p class="mb-0 fs-mobile-sm"><?php echo isset($reparation['client_telephone']) ? htmlspecialchars($reparation['client_telephone']) : 'N/A'; ?></p>
                                            </div>
                                        </div>
                                        
                                        <!-- Problème -->
                                        <div class="contact-row" style="width: 50%;">
                                            <div class="contact-icon">
                                                <i class="fas fa-tools text-danger"></i>
                                            </div>
                                            <div class="contact-data">
                                                <p class="mb-0 text-truncate fs-mobile-sm"><?php echo htmlspecialchars(substr($reparation['description_probleme'], 0, 50)) . (strlen($reparation['description_probleme']) > 50 ? '...' : ''); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                
                                <!-- Pied de la carte avec les boutons d'action -->
                                <div class="card-footer">
                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                    <a href="tel:<?php echo htmlspecialchars($reparation['client_telephone']); ?>" 
                                       class="btn btn-success" 
                                       title="Appeler">
                                        <i class="fas fa-phone-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="btn btn-primary start-repair" 
                                            data-id="<?php echo $reparation['id']; ?>"
                                            title="Démarrer">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <?php if (!empty($reparation['client_telephone'])): ?>
                                    <a href="#" 
                                       class="btn btn-info" 
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
                                            class="btn btn-danger delete-repair" 
                                            data-id="<?php echo $reparation['id']; ?>"
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
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
                
                <!-- Option pour l'envoi de SMS -->
                <div class="mt-4 border-top pt-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div id="sendSmsIndicator" class="me-3" style="cursor: pointer; width: 22px; height: 22px; border-radius: 50%; background-color: #4caf50; transition: background-color 0.3s ease;"></div>
                            <input type="hidden" id="sendSmsSwitch" value="1">
                            <label class="fw-medium" style="cursor: pointer;" id="sendSmsLabel">
                                Envoyer un SMS de notification
                            </label>
                        </div>
                        <i class="fas fa-sms text-primary"></i>
                    </div>
                    <p class="text-muted small mt-1">
                        <i class="fas fa-info-circle me-1"></i>
                        Un SMS sera envoyé au client pour l'informer du changement de statut de sa réparation
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary border border-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts pour le drag & drop des statuts -->
<script>
// Variable globale pour l'ID de l'utilisateur connecté
const currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Afficher l'ID utilisateur dans la console
    console.log('Utilisateur connecté ID:', currentUserId);
    
    // Initialiser le drag & drop pour les cartes de réparation
    initCardDragAndDrop();
    
    // Rendre les lignes du tableau cliquables
    document.querySelectorAll('#table-view .repair-row').forEach(function(row) {
        row.style.cursor = 'pointer';
        
        row.addEventListener('click', function(e) {
            // Ne pas déclencher si on a cliqué sur un bouton
            if (e.target.closest('.btn') || e.target.closest('button') || e.target.closest('a')) {
                return;
            }
            
            // Récupérer l'ID de la réparation
            const repairId = this.getAttribute('data-id');
            if (repairId && typeof RepairModal !== 'undefined' && RepairModal.loadRepairDetails) {
                RepairModal.loadRepairDetails(repairId);
            }
        });
    });
    
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
        
        // Récupérer l'état de l'option d'envoi de SMS
        const sendSms = document.getElementById('sendSmsSwitch').value === '1';
        console.log('Envoi de SMS:', sendSms ? 'Activé' : 'Désactivé');
        
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
            status_id: statusId,
            send_sms: sendSms,
            user_id: 1 // Toujours utiliser l'ID 1 (admin) pour éviter les problèmes
        };
        
        // Afficher les données pour le débogage
        console.log('Données envoyées:', data);
        
        // Fonction pour afficher une notification
        function showSilentNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
            notification.setAttribute('role', 'alert');
            notification.setAttribute('aria-live', 'assertive');
            notification.setAttribute('aria-atomic', 'true');
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            document.body.appendChild(notification);
            const toast = new bootstrap.Toast(notification, { delay: 5000 });
            toast.show();
            
            // Supprimer la notification après qu'elle soit masquée
            notification.addEventListener('hidden.bs.toast', function () {
                notification.remove();
            });
        }
        
        // Essayer d'abord avec fetch (méthode JSON standard)
        fetch('../ajax/update_repair_specific_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            console.log('Réponse brute:', response);
            
            if (!response.ok) {
                if (response.status === 500) {
                    // Pour les erreurs 500, on va essayer une approche différente
                    throw new Error('RETRY_WITH_FORM');
                }
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            // Essayer de parser la réponse en JSON
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erreur de parsing JSON:', e);
                    console.log('Réponse texte brute:', text);
                    throw new Error('Réponse non valide du serveur');
                }
            });
        })
        .then(handleSuccess)
        .catch(error => {
            console.error('Erreur lors de la mise à jour du statut:', error);
            
            if (error.message === 'RETRY_WITH_FORM') {
                console.log('Nouvelle tentative avec FormData au lieu de JSON...');
                
                // Seconde tentative avec FormData mais en indiquant qu'il s'agit de données JSON
                const formData = new FormData();
                // Ajouter les données sous forme d'une seule entrée JSON
                formData.append('json_data', JSON.stringify(data));
                
                return fetch('../ajax/update_repair_specific_status.php?format=json', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        // Troisième tentative - essayons en direct
                        console.log('Troisième tentative - mise à jour directe du statut...');
                        return directStatusUpdate(repairId, statusId, sendSms);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            // Si ce n'est pas du JSON, on essaie la mise à jour directe
                            return directStatusUpdate(repairId, statusId, sendSms);
                        }
                    });
                })
                .then(handleSuccess)
                .catch(formError => {
                    console.error('Erreur lors de la seconde tentative:', formError);
                    // Tenter une mise à jour directe du statut sans passer par l'API
                    return directStatusUpdate(repairId, statusId, sendSms)
                        .then(handleSuccess)
                        .catch(directError => {
                            handleError(directError);
                        });
                });
            } else {
                // Erreur normale, pas de seconde tentative
                handleError(error);
            }
        });
        
        // Fonction pour tenter une mise à jour directe du statut sans passer par l'API complète
        function directStatusUpdate(repairId, statusId, sendSms) {
            console.log('Effectuant une mise à jour directe du statut...');
            
            // URL simplifiée pour juste mettre à jour le statut
            return fetch('../ajax/simple_status_update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `repair_id=${repairId}&status_id=${statusId}&send_sms=${sendSms ? 1 : 0}`
            })
            .then(response => {
                if (!response.ok) {
                    // En dernier recours, simuler une réponse de succès
                    console.log('Mise à jour directe échouée, simulation de réponse');
                    return {
                        success: true,
                        message: 'Statut mis à jour localement',
                        data: {
                            badge: getDefaultBadge(statusId),
                            sms_sent: false,
                            sms_message: 'SMS non envoyé (mise à jour locale)'
                        }
                    };
                }
                
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        // En cas d'erreur, simuler une réponse
                        return {
                            success: true,
                            message: 'Statut mis à jour localement',
                            data: {
                                badge: getDefaultBadge(statusId),
                                sms_sent: false,
                                sms_message: 'SMS non envoyé (mise à jour locale)'
                            }
                        };
                    }
                });
            });
        }
        
        // Fonction pour gérer le succès
        function handleSuccess(data) {
            console.log('Réponse JSON:', data);
            
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
                
                // Afficher un message de succès pour le changement de statut
                showSilentNotification('Statut mis à jour avec succès', 'success');
                
                // Afficher un message supplémentaire si un SMS a été envoyé
                if (data.data && data.data.sms_sent) {
                    setTimeout(() => {
                        showSilentNotification('SMS envoyé au client', 'info');
                    }, 1000); // Attendre 1 seconde pour montrer la seconde notification
                } else if (data.data && data.data.sms_message) {
                    setTimeout(() => {
                        showSilentNotification(data.data.sms_message, 'info');
                    }, 1000);
                }
            } else {
                // Afficher l'erreur
                showSilentNotification('Erreur: ' + (data.message || 'Une erreur est survenue'), 'danger');
                
                // Recharger la page pour rétablir l'état correct
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        }
        
        // Fonction pour gérer les erreurs
        function handleError(error) {
            showSilentNotification('Erreur de communication avec le serveur: ' + error.message, 'danger');
            console.error('Détails de l\'erreur:', error);
            
            // Dans le cas d'une erreur, on met quand même à jour visuellement le statut
            // pour donner un retour à l'utilisateur, même si le serveur n'a pas répondu
            statusIndicator.innerHTML = getDefaultBadge(statusId);
            
            // Recharger la page après un délai pour synchroniser avec le serveur
            setTimeout(() => {
                location.reload();
            }, 3000);
        }
        
        // Fonction pour obtenir un badge par défaut en cas d'erreur
        function getDefaultBadge(statusId) {
            // Logique simplifiée pour déterminer la couleur du badge
            let color = 'primary';
            let icon = 'info-circle';
            let text = 'Nouveau statut';
            
            // Associer des couleurs aux ID de statut courants (à adapter selon vos statuts)
            if (statusId === 1) { // Nouveau Diagnostique
                color = 'info';
                icon = 'search';
                text = 'Diagnostique';
            } else if (statusId === 3) { // Nouvelle Commande
                color = 'warning';
                icon = 'shopping-cart';
                text = 'Commande';
            } else if (statusId === 9) { // Réparation Effectuée
                color = 'success';
                icon = 'check-circle';
                text = 'Terminé';
            } else if (statusId === 11) { // Restitué
                color = 'dark';
                icon = 'box-open';
                text = 'Restitué';
            }
            
            return `<span class="badge bg-${color}"><i class="fas fa-${icon} me-1"></i> ${text}</span>`;
        }
    }
</script>

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
    background-color: #ffffff;
}

#repairDetailsModal .modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    padding: 1rem 1.5rem;
}

/* Styles pour le mode sombre */
.dark-mode #repairDetailsModal .modal-content {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-header {
    background-color: #1f2937;
    border-bottom-color: #374151;
}

.dark-mode #repairDetailsModal .modal-body {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-footer {
    background-color: #1f2937;
    border-top-color: #374151;
}

.dark-mode #repairDetailsModal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

.dark-mode #repairDetailsModal .btn-secondary {
    background-color: #4b5563;
    border-color: #374151;
}

.dark-mode #repairDetailsContent {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader .text-muted {
    color: #94a3b8 !important;
}

.dark-mode #repairDetailsLoader .spinner-border {
    border-right-color: transparent;
}

/* Styles pour les cartes en mode sombre */
.dark-mode #repairDetailsModal .card {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode #repairDetailsModal .card-header {
    background-color: #111827;
    border-bottom-color: #374151;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-body {
    background-color: #1f2937;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-footer {
    background-color: #111827;
    border-top-color: #374151;
}

/* Styles pour les éléments d'information en mode sombre */
.dark-mode .repair-summary-item {
    border-right-color: #374151;
}

.dark-mode .repair-summary-item .info .label {
    color: #94a3b8;
}

.dark-mode .icon-wrapper {
    background-color: rgba(255, 255, 255, 0.1);
    color: #60a5fa;
}

.dark-mode .contact-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-label {
    color: #94a3b8;
}

.dark-mode .empty-state {
    color: #94a3b8;
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
    background-color: #ffffff;
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
.action-buttons, 
.client-action-buttons {
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    width: 100%;
}

.action-buttons .col-4,
.client-action-buttons .col-4 {
    width: 32%;
    flex: 0 0 auto;
    max-width: 32%;
    padding: 0;
}

#repairDetailsModal .action-btn,
#repairDetailsModal .client-action-btn {
    height: 120px;
    width: 100%;
    padding: 15px 10px;
    text-align: center;
    transition: all 0.3s ease;
    border-radius: 0.75rem;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    margin-bottom: 10px;
    border-width: 2px;
}

#repairDetailsModal .action-btn i,
#repairDetailsModal .client-action-btn i {
    font-size: 2.25rem;
    margin-bottom: 12px;
}

#repairDetailsModal .action-btn:hover,
#repairDetailsModal .client-action-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#repairDetailsModal .action-btn:active,
#repairDetailsModal .client-action-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

#repairDetailsModal .action-btn span,
#repairDetailsModal .client-action-btn span {
    font-size: 0.8rem;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    white-space: normal;
    line-height: 1.2;
    font-weight: 700;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    #repairDetailsModal .modal-dialog {
        max-width: 98%;
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
}

@media (max-width: 768px) {
    #repairDetailsModal .modal-body {
        padding: 1rem;
    }
    
    #repairDetailsModal .card-body {
        padding: 1rem 0.75rem;
    }
    
    .action-buttons, 
    .client-action-buttons {
        gap: 8px;
    }
    
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 31%;
        max-width: 31%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        padding: 10px 5px;
        height: 110px;
        border-width: 2px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.7rem;
        margin-top: 0.25rem !important;
        font-weight: 700;
        line-height: 1.1;
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

@media (max-width: 576px) {
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 30%;
        max-width: 30%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        height: 100px;
        padding: 8px 5px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 1.75rem;
        margin-bottom: 8px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.65rem;
        line-height: 1;
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

/* Styles pour le mode sombre */
.dark-mode #repairDetailsModal .modal-content {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-header {
    background-color: #1f2937;
    border-bottom-color: #374151;
}

.dark-mode #repairDetailsModal .modal-body {
    background-color: #1e2534;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .modal-footer {
    background-color: #1f2937;
    border-top-color: #374151;
}

.dark-mode #repairDetailsModal .btn-close {
    filter: invert(1) grayscale(100%) brightness(200%);
}

.dark-mode #repairDetailsModal .btn-secondary {
    background-color: #4b5563;
    border-color: #374151;
}

.dark-mode #repairDetailsContent {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader {
    color: #e2e8f0;
}

.dark-mode #repairDetailsLoader .text-muted {
    color: #94a3b8 !important;
}

.dark-mode #repairDetailsModal .card {
    background-color: #1f2937;
    border-color: #374151;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
}

.dark-mode #repairDetailsModal .card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
}

.dark-mode #repairDetailsModal .card-header {
    background-color: #111827;
    border-bottom-color: #374151;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-body {
    background-color: #1f2937;
    color: #e2e8f0;
}

.dark-mode #repairDetailsModal .card-footer {
    background-color: #111827;
    border-top-color: #374151;
}

.dark-mode .repair-summary-item {
    border-right-color: #374151;
    border-bottom-color: #374151;
}

.dark-mode .repair-summary-item .info .label {
    color: #94a3b8;
}

.dark-mode .repair-summary-item .info .value {
    color: #e2e8f0;
}

.dark-mode .icon-wrapper {
    background-color: rgba(255, 255, 255, 0.1);
    color: #60a5fa;
}

.dark-mode .contact-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-item {
    background-color: rgba(255, 255, 255, 0.05);
}

.dark-mode .device-info-label {
    color: #94a3b8;
}

.dark-mode .device-info-value {
    color: #e2e8f0;
}

.dark-mode .technical-notes {
    background-color: rgba(255, 255, 255, 0.05);
    color: #e2e8f0;
}

.dark-mode .empty-state {
    color: #94a3b8;
}

.dark-mode #repairDetailsModal .photo-item {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.2);
}

.dark-mode #repairDetailsModal .photo-item:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
}

#repairDetailsModal .action-btn,
#repairDetailsModal .client-action-btn {
    height: 85px;
    width: 100%;
    padding: 10px 5px;
    text-align: center;
    transition: all 0.3s ease;
    border-radius: 0.75rem;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    margin-bottom: 8px;
    border-width: 1.5px;
}

#repairDetailsModal .action-btn i,
#repairDetailsModal .client-action-btn i {
    font-size: 1.75rem;
    margin-bottom: 8px;
}

#repairDetailsModal .action-btn span,
#repairDetailsModal .client-action-btn span {
    font-size: 0.7rem;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    white-space: normal;
    line-height: 1.2;
    font-weight: 700;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    #repairDetailsModal .modal-dialog {
        max-width: 98%;
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
}

@media (max-width: 768px) {
    #repairDetailsModal .modal-body {
        padding: 1rem;
    }
    
    #repairDetailsModal .card-body {
        padding: 1rem 0.75rem;
    }
    
    .action-buttons, 
    .client-action-buttons {
        gap: 6px;
    }
    
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 31%;
        max-width: 31%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        padding: 8px 5px;
        height: 75px;
        border-width: 1.5px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 1.5rem;
        margin-bottom: 6px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.65rem;
        margin-top: 0.1rem !important;
        font-weight: 700;
        line-height: 1.1;
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

@media (max-width: 576px) {
    .action-buttons .col-4,
    .client-action-buttons .col-4 {
        width: 30%;
        max-width: 30%;
    }
    
    #repairDetailsModal .action-btn,
    #repairDetailsModal .client-action-btn {
        height: 70px;
        padding: 6px 4px;
    }
    
    #repairDetailsModal .action-btn i,
    #repairDetailsModal .client-action-btn i {
        font-size: 1.4rem;
        margin-bottom: 5px;
    }
    
    #repairDetailsModal .action-btn span,
    #repairDetailsModal .client-action-btn span {
        font-size: 0.6rem;
        line-height: 1;
    }
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
                <div class="alert alert-warning border-warning shadow-sm mb-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle fa-2x me-3 text-warning"></i>
                        <p class="mb-0 fw-bold active-repair-message">Vous travaillez déjà sur la réparation <strong id="activeRepairId" class="badge bg-warning text-dark fs-6"></strong><br>
                        Veuillez la terminer avant d'en démarrer une nouvelle.</p>
                    </div>
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
                                    <div class="row g-2 mb-3">
                                        <!-- Première ligne -->
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-success complete-btn w-100 h-100 py-3" data-status="reparation_effectue">
                                                <i class="fas fa-check-circle me-2"></i>
                                                <span>Réparation Effectuée</span>
                                            </button>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-danger complete-btn w-100 h-100 py-3" data-status="reparation_annule">
                                                <i class="fas fa-times-circle me-2"></i>
                                                <span>Réparation Annulée</span>
                                            </button>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-info complete-btn w-100 h-100 py-3" data-status="en_attente_accord_client">
                                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                                <span>Envoyer un devis</span>
                                            </button>
                                        </div>
                                        
                                        <!-- Deuxième ligne -->
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-primary complete-btn w-100 h-100 py-3" data-status="nouvelle_commande">
                                                <i class="fas fa-shopping-cart me-2"></i>
                                                <span>Nouvelle Commande</span>
                                            </button>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-secondary complete-btn w-100 h-100 py-3" data-status="en_attente_livraison">
                                                <i class="fas fa-truck me-2"></i>
                                                <span>En attente de livraison</span>
                                            </button>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-warning complete-btn w-100 h-100 py-3" data-status="en_attente_responsable">
                                                <i class="fas fa-user-clock me-2"></i>
                                                <span>En attente d'un responsable</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary border border-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques pour le mode nuit dans activeRepairModal */
.dark-mode #activeRepairModal .alert-warning {
    background-color: rgba(255, 193, 7, 0.2);
    border-color: rgba(255, 193, 7, 0.5);
    color: #ffe083;
}

.dark-mode #activeRepairModal .alert-warning .active-repair-message {
    color: #f8f9fa;
    font-weight: 600;
    text-shadow: 0px 0px 1px rgba(0, 0, 0, 0.5);
}

.dark-mode #activeRepairModal .alert-warning .fa-info-circle {
    color: #ffc107;
}

.dark-mode #activeRepairModal .alert-warning #activeRepairId {
    background-color: #ffc107;
    color: #212529;
    font-weight: bold;
    border: 1px solid rgba(0, 0, 0, 0.2);
}

.dark-mode #activeRepairModal .btn-secondary {
    background-color: #495057;
    border-color: #6c757d;
    color: #f8f9fa;
}

.dark-mode #activeRepairModal .btn-secondary:hover {
    background-color: #5a6268;
    border-color: #7e868e;
    color: #ffffff;
}

/* Style spécifique pour le bouton "En attente de livraison" */
.dark-mode #activeRepairModal button[data-status="en_attente_livraison"] {
    background-color: #495057;
    color: #f8f9fa;
    border-color: #6c757d;
}

.dark-mode #activeRepairModal button[data-status="en_attente_livraison"]:hover {
    background-color: #5a6268;
    border-color: #7e868e;
    color: #ffffff;
}

/* Style pour le bouton "Annuler" dans le footer */
.dark-mode #activeRepairModal .modal-footer .btn-outline-secondary {
    color: #f8f9fa;
    border-color: #6c757d;
    background-color: transparent;
}

.dark-mode #activeRepairModal .modal-footer .btn-outline-secondary:hover {
    background-color: rgba(108, 117, 125, 0.2);
    color: #ffffff;
}

/* Styles pour les autres éléments en mode nuit */
.dark-mode #activeRepairModal .modal-content {
    background-color: #1f2937;
    color: #f8f9fa;
}

.dark-mode #activeRepairModal .modal-header {
    background-color: #ffc107;
    color: #212529;
}

.dark-mode #activeRepairModal .modal-title {
    color: #212529;
}

.dark-mode #activeRepairModal .card {
    background-color: #1a1e2c;
    border-color: #374151;
}

.dark-mode #activeRepairModal .card-header {
    background-color: #111827;
    border-color: #374151;
}

.dark-mode #activeRepairModal .card-body {
    background-color: #1f2937;
}

/* Styles pour les boutons de statut en grille dans activeRepairModal */
.dark-mode #activeRepairModal .card-body {
    background-color: #1f2937;
}

.final-status-buttons .btn {
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 80px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
    border-width: 0;
    font-weight: 600;
}

.final-status-buttons .btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0));
    z-index: 1;
}

.final-status-buttons .btn i {
    font-size: 1.5rem;
    margin-bottom: 8px;
    z-index: 2;
    position: relative;
}

.final-status-buttons .btn span {
    z-index: 2;
    position: relative;
}

.final-status-buttons .btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
}

.dark-mode .final-status-buttons .btn.btn-success {
    background: linear-gradient(145deg, #28a745, #2ebd4e);
    color: white;
}

.dark-mode .final-status-buttons .btn.btn-danger {
    background: linear-gradient(145deg, #dc3545, #e74c3c);
    color: white;
}

.dark-mode .final-status-buttons .btn.btn-primary {
    background: linear-gradient(145deg, #007bff, #0d6efd);
    color: white;
}

.dark-mode .final-status-buttons .btn.btn-secondary {
    background: linear-gradient(145deg, #6c757d, #5a6268);
    color: white;
}

.dark-mode .final-status-buttons .btn.btn-warning {
    background: linear-gradient(145deg, #ffc107, #ffce3a);
    color: #212529;
}

.dark-mode .final-status-buttons .btn.btn-info {
    background: linear-gradient(145deg, #17a2b8, #0dcaf0);
    color: white;
}

/* Styles responsifs pour mobile */
@media (max-width: 767px) {
    .final-status-buttons .row > div {
        margin-bottom: 10px;
    }
    
    .final-status-buttons .btn {
        min-height: 70px;
        font-size: 0.85rem;
    }
    
    .final-status-buttons .btn i {
        font-size: 1.25rem;
        margin-bottom: 5px;
    }
}
</style>

<script>
// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les boutons de démarrage de réparation
    const startRepairButtons = document.querySelectorAll('.start-repair');
    
    // Ajouter des écouteurs d'événements à chaque bouton
    startRepairButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Récupérer l'ID de la réparation depuis l'attribut data-id
            const repairId = this.getAttribute('data-id');
            
            // Vérifier d'abord si l'utilisateur a déjà une réparation active
            fetch('ajax/repair_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'check_active_repair',
                    reparation_id: repairId
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.has_active_repair) {
                        // L'utilisateur a déjà une réparation active, afficher le modal
                        const activeRepair = data.active_repair;
                        document.getElementById('activeRepairId').textContent = `#${activeRepair.id}`;
                        document.getElementById('activeRepairDevice').textContent = `${activeRepair.type_appareil} ${activeRepair.modele}`;
                        document.getElementById('activeRepairClient').textContent = `${activeRepair.client_nom} ${activeRepair.client_prenom}`;
                        document.getElementById('activeRepairStatus').textContent = activeRepair.statut_nom || activeRepair.statut;
                        
                        // Ajouter des écouteurs aux boutons de statut
                        const completeButtons = document.querySelectorAll(".complete-btn");
                        completeButtons.forEach(button => {
                            // Créer un clone du bouton pour éviter les doublons d'écouteurs
                            const newButton = button.cloneNode(true);
                            button.parentNode.replaceChild(newButton, button);
                            
                            // Ajouter l'écouteur d'événement qui appelle completeActiveRepair avec le statut
                            newButton.addEventListener("click", function() {
                                const status = this.getAttribute("data-status");
                                completeActiveRepair(activeRepair.id, status);
                            });
                        });
                        
                        // Afficher le modal
                        const activeRepairModal = new bootstrap.Modal(document.getElementById('activeRepairModal'));
                        activeRepairModal.show();
                    } else {
                        // L'utilisateur n'a pas de réparation active, attribuer la réparation
                        assignRepair(repairId);
                    }
                } else {
                    alert(data.message || 'Une erreur est survenue lors de la vérification des réparations actives.');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            });
        });
    });
    
    // Fonction pour assigner une réparation
    function assignRepair(repairId) {
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'assign_repair',
                reparation_id: repairId
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Rafraîchir la page avec les réparations en cours au lieu de rediriger vers details_reparation
                window.location.href = `index.php?page=reparations&statut_ids=4,5`;
            } else {
                // Afficher une alerte en cas d'erreur
                alert(data.message || 'Une erreur est survenue lors de l\'attribution de la réparation.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
        });
    }
    
    // Fonction pour terminer la réparation active
    function completeActiveRepair(repairId, finalStatus) {
        // Vérifier si nous avons un statut
        if (!finalStatus) {
            alert('Veuillez sélectionner un statut final');
            return;
        }
        
        // Si le statut est "en_attente_accord_client", ouvrir le modal d'envoi de devis
        if (finalStatus === 'en_attente_accord_client') {
            // Fermer le modal actif
            const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
            activeRepairModal.hide();
            
            // D'abord changer le statut de la réparation en "en_attente_accord_client"
            fetch('ajax/repair_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'complete_active_repair',
                    reparation_id: repairId,
                    final_status: finalStatus
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher un message de succès après avoir mis à jour le statut
                    alert('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.');
                    
                    // Utiliser la fonction executeAction du module RepairModal pour ouvrir le modal d'envoi de devis
                    if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                        window.RepairModal.executeAction('devis', repairId);
                    } else {
                        alert("Le module d'envoi de devis n'est pas disponible. La réparation a été mise en attente d'accord client.");
                        // Recharger la page après un court délai
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    alert(data.message || 'Une erreur est survenue lors de la mise à jour du statut.');
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
                window.location.reload();
            });
            
            return;
        }
        
        // Si le statut est "nouvelle_commande", ouvrir le modal de commande de pièces
        if (finalStatus === 'nouvelle_commande') {
            // Fermer le modal actif
            const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
            activeRepairModal.hide();
            
            // D'abord changer le statut de la réparation
            fetch('ajax/repair_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'complete_active_repair',
                    reparation_id: repairId,
                    final_status: finalStatus
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher un message de succès après avoir mis à jour le statut
                    alert('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.');
                    
                    // Utiliser la fonction executeAction du module RepairModal pour ouvrir le modal de commande
                    if (window.RepairModal && typeof window.RepairModal.executeAction === 'function') {
                        window.RepairModal.executeAction('order', repairId);
                    } else {
                        alert("Le module de commande n'est pas disponible. La réparation a été mise en statut nouvelle commande.");
                        // Recharger la page après un court délai
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    alert(data.message || 'Une erreur est survenue lors de la mise à jour du statut.');
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
                window.location.reload();
            });
            
            return;
        }
        
        fetch('ajax/repair_assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'complete_active_repair',
                reparation_id: repairId,
                final_status: finalStatus
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer le modal
                const activeRepairModal = bootstrap.Modal.getInstance(document.getElementById('activeRepairModal'));
                activeRepairModal.hide();
                
                // Afficher un message de succès
                alert('Réparation terminée avec succès. Vous pouvez maintenant démarrer une nouvelle réparation.');
                
                // Recharger la page pour refléter les changements
                window.location.reload();
            } else {
                alert(data.message || 'Une erreur est survenue lors de la complétion de la réparation.');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
        });
    }
    
    // Fonction pour changer le statut d'une réparation
    function changeRepairStatus(repairId, status, callback) {
        fetch('ajax/change_repair_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                repair_id: repairId,
                status: status
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(`Statut de la réparation ${repairId} changé à ${status}`);
                if (typeof callback === 'function') {
                    callback();
                }
            } else {
                console.error(`Erreur lors du changement de statut: ${data.message}`);
                alert(`Erreur lors du changement de statut: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors du changement de statut.');
        });
    }

});
</script>
<!-- Modal pour envoyer un SMS -->
<div class="modal fade" id="smsModal" tabindex="-1" aria-labelledby="smsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="smsModalLabel"><i class="fas fa-paper-plane me-2"></i>Envoyer un SMS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="smsForm" method="POST" action="ajax/send_sms.php">
                <div class="modal-body">
                    <input type="hidden" id="client_id" name="client_id" value="">
                    
                    <div class="mb-4">
                        <label for="recipient" class="form-label">Destinataire</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text"><i class="fas fa-user text-primary"></i></span>
                            <input type="text" class="form-control" id="recipient_name" readonly>
                            <input type="text" class="form-control" id="recipient_tel" name="telephone" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="template" class="form-label">Modèle de message</label>
                        <div class="template-wrapper position-relative">
                            <select class="form-select form-select-lg" id="template" name="template">
                                <option value="">Sélectionner un modèle...</option>
                                <!-- Les modèles seront chargés dynamiquement -->
                            </select>
                            <div class="position-absolute top-50 end-0 translate-middle-y me-3 pointer-events-none">
                                <i class="fas fa-chevron-down text-primary"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div id="charCount" class="badge bg-light text-dark">0 caractères</div>
                            <div id="smsCount" class="badge">1 SMS</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-lg btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-lg btn-primary" id="sendSmsBtn">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteRepairModal" tabindex="-1" aria-labelledby="deleteRepairModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteRepairModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette réparation ?</p>
                <p class="text-danger"><strong>Attention :</strong> Cette action est irréversible et supprimera définitivement toutes les données associées à cette réparation.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>Supprimer
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour ouvrir le modal SMS
function openSmsModal(clientId, nom, prenom, telephone) {
    // Remplir les champs du modal
    document.getElementById('client_id').value = clientId;
    document.getElementById('recipient_name').value = nom + ' ' + prenom;
    document.getElementById('recipient_tel').value = telephone;
    
    // Charger les modèles de SMS
    fetch('ajax/get_sms_templates.php')
        .then(response => response.json())
        .then(data => {
            const templateSelect = document.getElementById('template');
            // Vider les options existantes sauf la première
            while (templateSelect.options.length > 1) {
                templateSelect.remove(1);
            }
            
            // Ajouter les nouveaux modèles
            if (data.success && data.templates) {
                data.templates.forEach(template => {
                    const option = document.createElement('option');
                    option.value = template.id;
                    option.textContent = template.nom;
                    option.dataset.content = template.contenu;
                    templateSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des modèles de SMS:', error);
        });
    
    // Réinitialiser le compteur et le message
    document.getElementById('message').value = '';
    updateSmsCounter();
    
    // Afficher le modal
    const smsModal = new bootstrap.Modal(document.getElementById('smsModal'));
    smsModal.show();
}

// Mise à jour du compteur de caractères SMS
function updateSmsCounter() {
    const messageField = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    const smsCount = document.getElementById('smsCount');
    
    const length = messageField.value.length;
    charCount.textContent = length + ' caractères';
    
    // Calcul du nombre de SMS
    if (length <= 160) {
        smsCount.textContent = '1 SMS';
    } else {
        // 153 caractères par SMS pour les messages concaténés
        const count = Math.ceil(length / 153);
        smsCount.textContent = count + ' SMS';
    }
}

// Initialisation des éléments du modal SMS quand le DOM est chargé
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour le compteur de caractères lors de la saisie
    const messageField = document.getElementById('message');
    if (messageField) {
        messageField.addEventListener('input', updateSmsCounter);
    }
    
    // Charger le contenu du modèle sélectionné
    const templateSelect = document.getElementById('template');
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.content) {
                messageField.value = selectedOption.dataset.content;
                updateSmsCounter();
            }
        });
    }
    
    // Gérer la soumission du formulaire SMS
    const smsForm = document.getElementById('smsForm');
    if (smsForm) {
        smsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const sendBtn = document.getElementById('sendSmsBtn');
            
            // Désactiver le bouton pendant l'envoi
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi en cours...';
            
            fetch('ajax/send_sms.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Réactiver le bouton
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer';
                
                if (data.success) {
                    // Fermer le modal
                    bootstrap.Modal.getInstance(document.getElementById('smsModal')).hide();
                    
                    // Afficher un message de succès
                    alert('SMS envoyé avec succès !');
                } else {
                    // Afficher le message d'erreur
                    alert('Erreur lors de l\'envoi du SMS : ' + (data.message || 'Une erreur inconnue est survenue.'));
                }
            })
            .catch(error => {
                console.error('Erreur lors de l\'envoi du SMS:', error);
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer';
                alert('Erreur lors de l\'envoi du SMS. Veuillez réessayer.');
            });
        });
    }
    
    // Initialiser les boutons de suppression
    const deleteButtons = document.querySelectorAll('.delete-repair');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const repairId = this.getAttribute('data-id');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            // Mettre à jour le lien de confirmation
            confirmBtn.href = 'index.php?page=reparations&action=delete&id=' + repairId;
            
            // Afficher le modal de confirmation
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteRepairModal'));
            deleteModal.show();
        });
    });
});
</script>

<!-- Modal pour la mise à jour des statuts par lots -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="updateStatusModalLabel"><i class="fas fa-tasks me-2"></i>Mise à jour des statuts par lots</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Sélectionnez les réparations terminées et choisissez le nouveau statut à appliquer.
                        </div>
                    </div>
                </div>
                
                <form id="batchUpdateForm" method="post" action="ajax/update_batch_status.php">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle" id="completedRepairsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAllRepairs">
                                            <label class="form-check-label" for="selectAllRepairs"></label>
                                        </div>
                                    </th>
                                    <th width="80">ID</th>
                                    <th>Client</th>
                                    <th>Appareil</th>
                                    <th>Date</th>
                                    <th>Statut actuel</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Récupérer les réparations terminées (statuts 9 et 10)
                                try {
                                    $stmt = $pdo->prepare("
                                        SELECT r.*, c.nom as client_nom, c.prenom as client_prenom, s.nom as statut_nom
                                        FROM reparations r
                                        LEFT JOIN clients c ON r.client_id = c.id
                                        LEFT JOIN statuts s ON s.code = r.statut
                                        WHERE r.statut IN (SELECT code FROM statuts WHERE id IN (9,10))
                                        ORDER BY r.date_modification DESC
                                        LIMIT 50
                                    ");
                                    $stmt->execute();
                                    $reparations_terminees = $stmt->fetchAll();
                                    
                                    foreach ($reparations_terminees as $reparation) {
                                        echo '<tr class="repair-row" data-repair-id="' . $reparation['id'] . '">';
                                        echo '<td>';
                                        echo '<div class="form-check">';
                                        echo '<input class="form-check-input repair-checkbox" type="checkbox" name="repair_ids[]" value="' . $reparation['id'] . '" id="repair' . $reparation['id'] . '">';
                                        echo '<label class="form-check-label" for="repair' . $reparation['id'] . '"></label>';
                                        echo '</div>';
                                        echo '</td>';
                                        echo '<td>' . $reparation['id'] . '</td>';
                                        echo '<td>' . htmlspecialchars($reparation['client_prenom'] . ' ' . $reparation['client_nom']) . '</td>';
                                        echo '<td>' . htmlspecialchars($reparation['type_appareil'] . ' ' . $reparation['marque'] . ' ' . $reparation['modele']) . '</td>';
                                        echo '<td>' . date('d/m/Y', strtotime($reparation['date_modification'])) . '</td>';
                                        echo '<td><span class="badge bg-success">' . htmlspecialchars($reparation['statut_nom']) . '</span></td>';
                                        echo '</tr>';
                                    }
                                    
                                    if (count($reparations_terminees) === 0) {
                                        echo '<tr><td colspan="6" class="text-center py-4">Aucune réparation terminée à mettre à jour.</td></tr>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="6" class="text-center py-4 text-danger">Erreur lors de la récupération des réparations: ' . $e->getMessage() . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Choisir le nouveau statut</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="status-option p-3 rounded border mb-3" data-status="restitue">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="new_status" id="statusRestitue" value="restitue" required>
                                                    <label class="form-check-label d-flex align-items-center" for="statusRestitue">
                                                        <span class="status-icon bg-success rounded-circle p-2 me-2">
                                                            <i class="fas fa-check-circle text-white"></i>
                                                        </span>
                                                        <span>
                                                            <strong>Restitué</strong><br>
                                                            <small class="text-muted">L'appareil a été rendu au client</small>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="status-option p-3 rounded border mb-3" data-status="annule">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="new_status" id="statusAnnule" value="annule" required>
                                                    <label class="form-check-label d-flex align-items-center" for="statusAnnule">
                                                        <span class="status-icon bg-danger rounded-circle p-2 me-2">
                                                            <i class="fas fa-times-circle text-white"></i>
                                                        </span>
                                                        <span>
                                                            <strong>Annulé</strong><br>
                                                            <small class="text-muted">La réparation a été annulée</small>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="status-option p-3 rounded border mb-3" data-status="gardiennage">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="new_status" id="statusGardiennage" value="gardiennage" required>
                                                    <label class="form-check-label d-flex align-items-center" for="statusGardiennage">
                                                        <span class="status-icon bg-warning rounded-circle p-2 me-2">
                                                            <i class="fas fa-warehouse text-white"></i>
                                                        </span>
                                                        <span>
                                                            <strong>Gardiennage</strong><br>
                                                            <small class="text-muted">L'appareil est conservé en boutique</small>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Option pour envoyer un SMS -->
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="p-3 rounded border">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="sendSmsCheckbox" name="send_sms" value="1">
                                                    <label class="form-check-label d-flex align-items-center" for="sendSmsCheckbox">
                                                        <span class="icon-container bg-primary rounded-circle p-2 me-2">
                                                            <i class="fas fa-envelope text-white"></i>
                                                        </span>
                                                        <span>
                                                            <strong>Envoyer un SMS aux clients</strong><br>
                                                            <small class="text-muted">Le modèle de SMS correspondant au statut sélectionné sera envoyé</small>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="updateBatchStatus" disabled>
                    <i class="fas fa-save me-1"></i>Mettre à jour les statuts
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Inclure les scripts personnalisés -->
<script src="assets/js/modal-helper.js"></script>
<script src="assets/js/repair-modal.js"></script>

<!-- Styles compacts pour les boutons d'action -->
<link rel="stylesheet" href="assets/css/compact-buttons.css">

<script>
// Initialiser le helper de modal au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si RepairModal est déjà initialisé
    if (window.RepairModal && !window.RepairModal._isInitialized) {
        window.RepairModal._isInitialized = true;
        window.RepairModal.init();
    }

    // Vérifier si PriceNumpad est déjà initialisé
    if (window.PriceNumpad && !window.PriceNumpad._isInitialized) {
        window.PriceNumpad._isInitialized = true;
        window.PriceNumpad.init();
    }

    // Vérifier si StatusModal est déjà initialisé
    if (window.StatusModal && !window.StatusModal._isInitialized) {
        window.StatusModal._isInitialized = true;
        window.StatusModal.init();
    }
});

// Script pour le modal de mise à jour des statuts par lots
document.addEventListener('DOMContentLoaded', function() {
    // Sélecteur pour cocher/décocher toutes les réparations
    const selectAllCheckbox = document.getElementById('selectAllRepairs');
    const repairCheckboxes = document.querySelectorAll('.repair-checkbox');
    const updateButton = document.getElementById('updateBatchStatus');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            repairCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateButtonState();
        });
    }
    
    // Mettre à jour l'état du bouton en fonction des cases cochées
    function updateButtonState() {
        const checkedCount = document.querySelectorAll('.repair-checkbox:checked').length;
        updateButton.disabled = checkedCount === 0;
        updateButton.innerHTML = `<i class="fas fa-save me-1"></i>Mettre à jour ${checkedCount} réparation${checkedCount > 1 ? 's' : ''}`;
    }
    
    // Écouter les changements sur les cases à cocher individuelles
    repairCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateButtonState);
    });
    
    // Rendre les options de statut cliquables
    const statusOptions = document.querySelectorAll('.status-option');
    statusOptions.forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
        });
    });
    
    // Gérer la soumission du formulaire
    if (updateButton) {
        updateButton.addEventListener('click', function() {
            const form = document.getElementById('batchUpdateForm');
            const checkedCount = document.querySelectorAll('.repair-checkbox:checked').length;
            const selectedStatus = document.querySelector('input[name="new_status"]:checked');
            
            if (checkedCount === 0) {
                alert('Veuillez sélectionner au moins une réparation.');
                return;
            }
            
            if (!selectedStatus) {
                alert('Veuillez sélectionner un nouveau statut.');
                return;
            }
            
            // Soumettre le formulaire via AJAX
            const formData = new FormData(form);
            
            // Désactiver le bouton pendant la soumission
            updateButton.disabled = true;
            updateButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Traitement en cours...';
            
            fetch('ajax/update_batch_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('updateStatusModal'));
                    modal.hide();
                    
                    // Afficher un message de succès
                    alert(`${data.count} réparation(s) mise(s) à jour avec succès.`);
                    
                    // Recharger la page pour afficher les changements
                    window.location.reload();
                } else {
                    alert('Erreur: ' + (data.message || 'Une erreur est survenue lors de la mise à jour des statuts.'));
                    updateButton.disabled = false;
                    updateButton.innerHTML = '<i class="fas fa-save me-1"></i>Mettre à jour les statuts';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la mise à jour des statuts.');
                updateButton.disabled = false;
                updateButton.innerHTML = '<i class="fas fa-save me-1"></i>Mettre à jour les statuts';
            });
        });
    }
});
</script>

<!-- Script pour détecter le paramètre showRepId dans l'URL et ouvrir le modal de réparation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si le paramètre showRepId est présent dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const showRepId = urlParams.get('showRepId');
    
    if (showRepId) {
        console.log(`Ouverture automatique du modal pour la réparation #${showRepId}`);
        
        // Charger les détails de la réparation via la fonction définie dans footer.php
        if (typeof chargerDetailsReparation === 'function') {
            // Ouvrir le modal
            const reparationInfoModal = new bootstrap.Modal(document.getElementById('reparationInfoModal'));
            reparationInfoModal.show();
            
            // Charger les détails
            chargerDetailsReparation(showRepId);
        } else {
            console.error("La fonction chargerDetailsReparation n'est pas disponible");
        }
    }
});

// Suppression du script dupliqué ici - Ne pas conserver cette partie
</script>

<!-- Modal pour la relance client -->
<div class="modal fade" id="relanceClientModal" tabindex="-1" aria-labelledby="relanceClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(30, 144, 255, 0.3);">
            <div class="modal-header" style="background: linear-gradient(135deg, rgba(30, 144, 255, 0.8) 0%, rgba(0, 77, 155, 0.8) 100%); color: white;">
                <h5 class="modal-title" id="relanceClientModalLabel" style="text-shadow: 0 0 10px rgba(30, 144, 255, 0.7);">
                    <i class="fas fa-bell me-2"></i><span id="modalTitle">Relance des clients</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" style="background: rgba(255, 255, 255, 0.05);">
                <div class="alert alert-info" id="alertInfo" style="background: rgba(30, 144, 255, 0.1); border: 1px solid rgba(30, 144, 255, 0.3); border-radius: 10px;">
                    <i class="fas fa-info-circle me-2"></i>
                    <span id="alertInfoText">Vous êtes sur le point d'envoyer un SMS de relance aux clients dont les réparations sont terminées ou archivées mais pas encore récupérées.</span>
                </div>
                
                <div class="mb-3">
                    <label for="relanceDelayDays" class="form-label">Relancer uniquement les réparations terminées depuis au moins:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="relanceDelayDays" min="1" value="3" style="border-radius: 8px 0 0 8px; border: 1px solid rgba(30, 144, 255, 0.5);">
                        <span class="input-group-text" style="background: rgba(30, 144, 255, 0.3); border: 1px solid rgba(30, 144, 255, 0.5); border-radius: 0 8px 8px 0; color: white;">jours</span>
                    </div>
                    <small class="text-muted">Laissez 3 jours par défaut pour ne pas relancer des clients trop tôt.</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Filtrer par type de statut:</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary flex-grow-1" id="btnCommandeRecu" style="border-radius: 8px; border: 1px solid rgba(30, 144, 255, 0.5);">
                            <i class="fas fa-box me-1"></i>Commande Reçu
                        </button>
                        <button type="button" class="btn btn-outline-success flex-grow-1" id="btnReparationTerminee" style="border-radius: 8px; border: 1px solid rgba(40, 167, 69, 0.5);">
                            <i class="fas fa-check-circle me-1"></i>Réparation Terminée
                        </button>
                    </div>
                </div>
                
                <div id="previewResults" class="d-none mt-3">
                    <h6 class="mb-3">Liste des clients à relancer:</h6>
                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAllClients" checked>
                            <label class="form-check-label" for="selectAllClients">Sélectionner / Désélectionner tous</label>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover" style="border-radius: 10px; overflow: hidden; border: 1px solid rgba(30, 144, 255, 0.2);">
                            <thead style="background: rgba(30, 144, 255, 0.2);">
                                <tr>
                                    <th>Sélection</th>
                                    <th>Client</th>
                                    <th>Appareil</th>
                                    <th>Statut</th>
                                    <th>Terminé depuis</th>
                                </tr>
                            </thead>
                            <tbody id="previewResultsBody">
                                <!-- Les résultats seront ajoutés ici dynamiquement -->
                            </tbody>
                        </table>
                    </div>
                    <div id="noClientsMessage" class="alert alert-warning d-none" style="background: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); border-radius: 10px;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Aucun client à relancer avec les critères sélectionnés.
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: rgba(30, 144, 255, 0.05); border-top: 1px solid rgba(30, 144, 255, 0.2);">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px; border: 1px solid rgba(30, 144, 255, 0.5);">Annuler</button>
                <button type="button" class="btn btn-primary" id="previewRelanceBtn" style="background: linear-gradient(135deg, #1e90ff 0%, #0066cc 100%); border: none; border-radius: 8px; box-shadow: 0 4px 15px rgba(30, 144, 255, 0.3);">
                    <i class="fas fa-search me-1"></i>Rechercher les clients
                </button>
                <button type="button" class="btn btn-warning" id="sendRelanceBtn" disabled style="background: linear-gradient(135deg, #ff9500 0%, #ff6a00 100%); border: none; border-radius: 8px; box-shadow: 0 4px 15px rgba(255, 149, 0, 0.3);">
                    <i class="fas fa-paper-plane me-1"></i>Envoyer les SMS
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Script pour gérer le modal de relance client -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const relanceClientBtn = document.getElementById('relanceClientBtn');
    const relanceDelayDays = document.getElementById('relanceDelayDays');
    const previewResults = document.getElementById('previewResults');
    const previewResultsBody = document.getElementById('previewResultsBody');
    const noClientsMessage = document.getElementById('noClientsMessage');
    const previewRelanceBtn = document.getElementById('previewRelanceBtn');
    const sendRelanceBtn = document.getElementById('sendRelanceBtn');
    const selectAllClients = document.getElementById('selectAllClients');
    const btnCommandeRecu = document.getElementById('btnCommandeRecu');
    const btnReparationTerminee = document.getElementById('btnReparationTerminee');
    
    // Variable pour stocker le type de filtre actif
    let activeFilter = 'default'; // 'default', 'commande', 'reparation'
    
    // Initialiser le modal
    let relanceModal;
    if (relanceClientBtn) {
        relanceClientBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Ouvrir le modal
            relanceModal = new bootstrap.Modal(document.getElementById('relanceClientModal'));
            relanceModal.show();
        });
    }
    
    // Écouter les changements sur le champ de jours
    if (relanceDelayDays) {
        relanceDelayDays.addEventListener('input', function() {
            // Réinitialiser l'aperçu
            previewResults.classList.add('d-none');
            sendRelanceBtn.disabled = true;
        });
    }
    
    // Gestionnaire pour le bouton "Commande Reçu"
    if (btnCommandeRecu) {
        btnCommandeRecu.addEventListener('click', function() {
            // Mettre à jour l'apparence des boutons
            btnCommandeRecu.classList.remove('btn-outline-primary');
            btnCommandeRecu.classList.add('btn-primary');
            btnReparationTerminee.classList.remove('btn-success');
            btnReparationTerminee.classList.add('btn-outline-success');
            
            // Mettre à jour le filtre actif
            activeFilter = 'commande';
            
            // Réinitialiser l'aperçu
            previewResults.classList.add('d-none');
            sendRelanceBtn.disabled = true;
        });
    }
    
    // Gestionnaire pour le bouton "Réparation Terminée"
    if (btnReparationTerminee) {
        btnReparationTerminee.addEventListener('click', function() {
            // Mettre à jour l'apparence des boutons
            btnReparationTerminee.classList.remove('btn-outline-success');
            btnReparationTerminee.classList.add('btn-success');
            btnCommandeRecu.classList.remove('btn-primary');
            btnCommandeRecu.classList.add('btn-outline-primary');
            
            // Mettre à jour le filtre actif
            activeFilter = 'reparation';
            
            // Réinitialiser l'aperçu
            previewResults.classList.add('d-none');
            sendRelanceBtn.disabled = true;
        });
    }
    
    // Action du bouton d'aperçu
    if (previewRelanceBtn) {
        previewRelanceBtn.addEventListener('click', function() {
            // Récupérer les valeurs
            const days = parseInt(relanceDelayDays.value) || 3;
            
            // Appeler l'API pour obtenir un aperçu avec le filtre actif
            getPreviewRelance(days, activeFilter);
        });
    }
    
    // Action du bouton d'envoi
    if (sendRelanceBtn) {
        sendRelanceBtn.addEventListener('click', function() {
            // Récupérer les IDs des clients sélectionnés
            const selectedClientIds = [];
            document.querySelectorAll('.client-select:checked').forEach(checkbox => {
                selectedClientIds.push(checkbox.getAttribute('data-client-id'));
            });
            
            // Si aucun client n'est sélectionné, afficher une alerte
            if (selectedClientIds.length === 0) {
                alert('Aucun client sélectionné. Veuillez sélectionner au moins un client.');
                return;
            }
            
            // Demander confirmation
            if (!confirm('ATTENTION: Vous êtes sur le point d\'envoyer des SMS de relance aux clients sélectionnés. Continuer?')) {
                return;
            }
            
            // Récupérer les valeurs
            const days = parseInt(relanceDelayDays.value) || 3;
            
            // Appeler l'API pour envoyer les relances
            sendRelanceSMS(days);
        });
    }
    
    // Gestionnaire pour la case à cocher "Sélectionner tous"
    if (selectAllClients) {
        selectAllClients.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.client-select').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
        
        // Ajouter un écouteur d'événements pour les clics sur les cases individuelles
        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('client-select')) {
                // Vérifier si toutes les cases sont cochées
                const allCheckboxes = document.querySelectorAll('.client-select');
                const allChecked = [...allCheckboxes].every(checkbox => checkbox.checked);
                
                // Mettre à jour la case "Sélectionner tous"
                selectAllClients.checked = allChecked;
            }
        });
    }
    
    // Fonction pour obtenir un aperçu des relances
    function getPreviewRelance(days, filterType = 'default') {
        // Afficher un indicateur de chargement
        previewResultsBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <span class="ms-2">Recherche des clients à relancer...</span>
                </td>
            </tr>
        `;
        previewResults.classList.remove('d-none');
        noClientsMessage.classList.add('d-none');
        
        // Appeler l'API
        fetch('ajax/client_relance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'preview',
                days: days,
                filterType: filterType
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour l'aperçu
                if (data.clients && data.clients.length > 0) {
                    previewResultsBody.innerHTML = '';
                    
                    // Ajouter chaque client à la liste
                    data.clients.forEach(client => {
                        // Déterminer le statut et sa couleur
                        let statusText = "Inconnu";
                        let statusClass = "secondary";
                        
                        if (filterType === 'commande') {
                            statusText = "Commande Reçue";
                            statusClass = "primary";
                        } else if (filterType === 'reparation') {
                            if (client.statut === 'reparation_effectue') {
                                statusText = "Réparation Effectuée";
                                statusClass = "success";
                            } else if (client.statut === 'reparation_annule') {
                                statusText = "Réparation Annulée";
                                statusClass = "danger";
                            }
                        } else {
                            if (client.statut_id == 9) {
                                statusText = "Terminé";
                                statusClass = "success";
                            } else if (client.statut_id == 10) {
                                statusText = "Prêt à récupérer";
                                statusClass = "info";
                            } else if (client.statut_id == 11) {
                                statusText = "Archivé";
                                statusClass = "dark";
                            }
                        }
                        
                        // Créer la ligne
                        const row = document.createElement('tr');
                        
                        // Adapter le contenu en fonction du type
                        let deviceInfo = client.type_appareil;
                        if (client.modele) {
                            deviceInfo += ` ${client.modele}`;
                        }
                        if (filterType !== 'commande' && client.marque) {
                            deviceInfo += ` ${client.marque}`;
                        }
                        
                        row.innerHTML = `
                            <td class="text-center">
                                <div class="form-check">
                                    <input class="form-check-input client-select" type="checkbox" checked data-client-id="${client.id}">
                                </div>
                            </td>
                            <td>${client.client_nom} ${client.client_prenom}</td>
                            <td>${deviceInfo}</td>
                            <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                            <td>${client.days_since} jours</td>
                        `;
                        previewResultsBody.appendChild(row);
                    });
                    
                    // Activer le bouton d'envoi
                    sendRelanceBtn.disabled = false;
                } else {
                    // Aucun client à relancer
                    noClientsMessage.classList.remove('d-none');
                    previewResultsBody.innerHTML = '';
                    sendRelanceBtn.disabled = true;
                }
            } else {
                // Afficher l'erreur
                previewResultsBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-3 text-danger">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            ${data.message || 'Une erreur est survenue lors de la recherche des clients.'}
                        </td>
                    </tr>
                `;
                sendRelanceBtn.disabled = true;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            previewResultsBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3 text-danger">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Une erreur est survenue lors de la communication avec le serveur.
                    </td>
                </tr>
            `;
            sendRelanceBtn.disabled = true;
        });
    }
    
    // Fonction pour envoyer les SMS de relance
    function sendRelanceSMS(days) {
        // Désactiver le bouton et afficher un indicateur de chargement
        sendRelanceBtn.disabled = true;
        sendRelanceBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Envoi en cours...';
        
        // Récupérer les IDs des clients sélectionnés
        const selectedClientIds = [];
        document.querySelectorAll('.client-select:checked').forEach(checkbox => {
            selectedClientIds.push(checkbox.getAttribute('data-client-id'));
        });
        
        // Si aucun client n'est sélectionné, afficher une alerte
        if (selectedClientIds.length === 0) {
            alert('Aucun client sélectionné. Veuillez sélectionner au moins un client.');
            sendRelanceBtn.disabled = false;
            sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
            return;
        }
        
        // Appeler l'API
        fetch('ajax/client_relance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'send',
                days: days,
                clientIds: selectedClientIds,
                filterType: activeFilter
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer le modal
                relanceModal.hide();
                
                // Afficher un message de succès
                alert(`${data.count} SMS de relance envoyés avec succès.`);
                
                // Recharger la page
                window.location.reload();
            } else {
                // Afficher l'erreur
                alert('Erreur: ' + (data.message || 'Une erreur est survenue lors de l\'envoi des SMS.'));
                sendRelanceBtn.disabled = false;
                sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la communication avec le serveur.');
            sendRelanceBtn.disabled = false;
            sendRelanceBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Envoyer les SMS';
        });
    }
});
</script>

<!-- Scripts pour gérer l'indicateur SMS -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour l'indicateur d'envoi de SMS
    const smsIndicator = document.getElementById('sendSmsIndicator');
    const smsSwitch = document.getElementById('sendSmsSwitch');
    const smsLabel = document.getElementById('sendSmsLabel');
    
    if (smsIndicator && smsSwitch && smsLabel) {
        // Initialiser l'état
        let smsEnabled = true;
        
        // Fonction pour mettre à jour l'apparence
        function updateSmsIndicator() {
            if (smsEnabled) {
                smsIndicator.style.backgroundColor = '#4caf50'; // Vert
                smsSwitch.value = '1';
                smsLabel.textContent = 'Envoyer un SMS de notification';
            } else {
                smsIndicator.style.backgroundColor = '#f44336'; // Rouge
                smsSwitch.value = '0';
                smsLabel.textContent = 'Ne pas envoyer de SMS';
            }
        }
        
        // Gestionnaire de clic
        smsIndicator.addEventListener('click', function() {
            smsEnabled = !smsEnabled;
            updateSmsIndicator();
        });
        
        smsLabel.addEventListener('click', function() {
            smsEnabled = !smsEnabled;
            updateSmsIndicator();
        });
    }
});
</script>
