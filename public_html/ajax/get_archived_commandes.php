<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Définir le header Content-Type pour renvoyer du JSON
header('Content-Type: application/json');

// Log de débogage
error_log('Démarrage de get_archived_commandes.php');

// Inclure les fichiers de configuration et de fonctions
require_once('../config/database.php');
require_once('../includes/functions.php');

// Récupérer les paramètres de la requête
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Log des paramètres reçus
error_log("Paramètres reçus: page=$page, filter=$filter, searchTerm=$searchTerm");

// Valider les paramètres
if ($page < 1) $page = 1;

// Nombre de commandes par page
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Vérifier d'abord si des commandes avec les statuts "termine" ou "annulee" existent
    $check_sql = "SELECT COUNT(*) AS total FROM commandes_pieces WHERE statut IN ('termine', 'annulee')";
    $check_stmt = $pdo->query($check_sql);
    $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Vérification des commandes archivées: " . $check_result['total'] . " trouvées");
    
    // Si aucune commande n'est trouvée, renvoyer une réponse vide immédiatement
    if ($check_result['total'] == 0) {
        error_log("Aucune commande archivée trouvée");
        echo json_encode([
            'success' => true,
            'commandes' => [],
            'pagination' => [
                'current_page' => 1,
                'per_page' => $per_page,
                'total_items' => 0,
                'total_pages' => 0
            ],
            'filter' => $filter,
            'search' => $searchTerm,
            'message' => 'Aucune commande archivée trouvée'
        ]);
        exit;
    }
    
    // Construire la requête SQL avec les filtres
    $sql_where = [];
    $params = [];
    
    // Filtrer par statut ou type de réparation
    if ($filter === 'termine') {
        $sql_where[] = "cp.statut = 'termine'";
    } elseif ($filter === 'annulee') {
        $sql_where[] = "cp.statut = 'annulee'";
    } elseif ($filter === 'with') {
        $sql_where[] = "cp.reparation_id IS NOT NULL";
        $sql_where[] = "cp.statut IN ('termine', 'annulee')";
    } elseif ($filter === 'without') {
        $sql_where[] = "cp.reparation_id IS NULL";
        $sql_where[] = "cp.statut IN ('termine', 'annulee')";
    } else {
        // Par défaut, montrer uniquement les commandes terminées et annulées
        $sql_where[] = "cp.statut IN ('termine', 'annulee')";
    }
    
    // Recherche textuelle
    if (!empty($searchTerm)) {
        $searchParam = '%' . $searchTerm . '%';
        $sql_where[] = "(cp.nom_piece LIKE ? OR cp.code_barre LIKE ? OR c.nom LIKE ? OR c.prenom LIKE ? OR f.nom LIKE ?)";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    }
    
    // Construire la clause WHERE complète
    $where_clause = !empty($sql_where) ? 'WHERE ' . implode(' AND ', $sql_where) : '';
    
    // Requête pour compter le nombre total de commandes
    $count_sql = "SELECT COUNT(*) AS total 
                  FROM commandes_pieces cp 
                  LEFT JOIN clients c ON cp.client_id = c.id 
                  LEFT JOIN fournisseurs f ON cp.fournisseur_id = f.id 
                  $where_clause";
    
    error_log("Requête de comptage: $count_sql");
    error_log("Paramètres: " . json_encode($params));
    
    $stmt = $pdo->prepare($count_sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $total_row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_commandes = $total_row['total'];
    $total_pages = ceil($total_commandes / $per_page);
    
    error_log("Nombre total de commandes: $total_commandes, pages: $total_pages");
    
    // Si la page demandée dépasse le nombre total de pages, revenir à la dernière page
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
        $offset = ($page - 1) * $per_page;
    }
    
    // Requête pour récupérer les commandes avec pagination
    $sql = "SELECT cp.*, c.nom as client_nom, c.prenom as client_prenom, c.telephone, 
                 f.nom as fournisseur_nom, r.id as reparation_id, r.type_appareil, r.marque, r.modele 
            FROM commandes_pieces cp 
            LEFT JOIN clients c ON cp.client_id = c.id 
            LEFT JOIN fournisseurs f ON cp.fournisseur_id = f.id 
            LEFT JOIN reparations r ON cp.reparation_id = r.id 
            $where_clause 
            ORDER BY cp.date_creation DESC 
            LIMIT $offset, $per_page";
    
    error_log("Requête principale: $sql");
    
    $stmt = $pdo->prepare($sql);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Nombre de commandes récupérées: " . count($commandes));
    
    // Préparer les données de pagination
    $pagination = [
        'current_page' => $page,
        'per_page' => $per_page,
        'total_items' => $total_commandes,
        'total_pages' => $total_pages
    ];
    
    // Envoyer la réponse
    $response = [
        'success' => true,
        'commandes' => $commandes,
        'pagination' => $pagination,
        'filter' => $filter,
        'search' => $searchTerm
    ];
    
    error_log("Réponse envoyée: success=true, nombre commandes=" . count($commandes));
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Logger l'erreur
    error_log('Erreur dans get_archived_commandes.php: ' . $e->getMessage());
    
    // Renvoyer une réponse d'erreur
    $error_response = [
        'success' => false,
        'message' => 'Erreur lors de la récupération des commandes: ' . $e->getMessage()
    ];
    echo json_encode($error_response);
} 