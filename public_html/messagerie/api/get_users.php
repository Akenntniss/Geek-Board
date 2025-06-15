<?php
/**
 * API - Récupérer la liste des utilisateurs pour créer une conversation
 */

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Inclure la connexion à la base de données
require_once '../includes/functions.php';

// Récupérer le terme de recherche optionnel
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    global $shop_pdo;
    
    // Modification de la requête pour s'adapter à la structure de la table users
    // Structure de la table: id, username, password, full_name, role, created_at, techbusy, active_repair_id
    $query = "
        SELECT id, username, full_name, role
        FROM users
        WHERE id != :current_user_id
    ";
    
    $params = [':current_user_id' => $_SESSION['user_id']];
    
    // Ajouter la recherche si fournie
    if (!empty($search)) {
        $query .= " AND (full_name LIKE :search OR username LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    // Trier par nom
    $query .= " ORDER BY full_name ASC";
    
    $stmt = $shop_pdo->prepare($query);
    $stmt->execute($params);
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Renvoyer les utilisateurs
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'users' => $users,
        'count' => count($users)
    ]);
    
} catch (PDOException $e) {
    // Journaliser l'erreur spécifique pour le débogage
    log_error('Erreur SQL lors de la récupération des utilisateurs', $e->getMessage() . ' - ' . $e->getTraceAsString());
    
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des utilisateurs: ' . $e->getMessage()
    ]);
    exit;
} 