<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Récupérer le terme de recherche
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'Terme de recherche requis']);
    exit;
}

try {
    // Utiliser la connexion à la base de données du magasin actuel
    $shop_pdo = getShopDBConnection();
    
    // Vérifier la connexion à la base de données
    if (!isset($shop_pdo) || !($shop_pdo instanceof PDO)) {
        error_log("Connexion à la base de données du magasin non disponible");
        throw new Exception('Connexion à la base de données du magasin non disponible');
    }
    
    // Journaliser l'information sur la base de données utilisée
    try {
        $stmt_db = $shop_pdo->query("SELECT DATABASE() as db_name");
        $db_info = $stmt_db->fetch(PDO::FETCH_ASSOC);
        error_log("Recherche clients - BASE DE DONNÉES UTILISÉE: " . ($db_info['db_name'] ?? 'Inconnue'));
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de la base de données: " . $e->getMessage());
    }
    
    // Préparer la requête SQL
    $sql = "SELECT id, nom, prenom, telephone, email 
            FROM clients 
            WHERE (nom LIKE :query 
               OR prenom LIKE :query 
               OR telephone LIKE :query 
               OR email LIKE :query)
            AND archive = 'NON'
            ORDER BY nom, prenom 
            LIMIT 10";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute(['query' => "%$query%"]);
    
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'clients' => $clients
    ]);
} catch (PDOException $e) {
    error_log("Erreur lors de la recherche de clients : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la recherche des clients'
    ]);
} catch (Exception $e) {
    error_log("Exception lors de la recherche des clients: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
} 