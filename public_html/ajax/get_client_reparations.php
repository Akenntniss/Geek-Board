<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Vérifier que l'ID du client est fourni
if (!isset($_POST['client_id']) || empty($_POST['client_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de client manquant']);
    exit;
}

$client_id = intval($_POST['client_id']);

try {
    // Utiliser la connexion à la base de données du magasin actuel
    $shop_pdo = getShopDBConnection();
    
    // Vérifier la connexion à la base de données
    if (!isset($shop_pdo) || !($shop_pdo instanceof PDO)) {
        throw new Exception('Connexion à la base de données du magasin non disponible');
    }
    
    // Journaliser l'information sur la base de données utilisée
    try {
        $stmt_db = $shop_pdo->query("SELECT DATABASE() as db_name");
        $db_info = $stmt_db->fetch(PDO::FETCH_ASSOC);
        error_log("Get client reparations - BASE DE DONNÉES UTILISÉE: " . ($db_info['db_name'] ?? 'Inconnue'));
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de la base de données: " . $e->getMessage());
    }
    
    // Récupérer les réparations du client
    $sql = "
        SELECT r.*
        FROM reparations r
        WHERE r.client_id = :client_id
        ORDER BY r.date_reception DESC
    ";
    
    $stmt = $shop_pdo->prepare($sql);
    $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatage des données pour JSON
    $reparations = array_map(function($rep) {
        // Éviter les problèmes d'encodage
        foreach ($rep as $key => $value) {
            $rep[$key] = is_string($value) ? $value : $value;
        }
        return $rep;
    }, $reparations);
    
    // Renvoyer les résultats
    echo json_encode([
        'success' => true,
        'count' => count($reparations),
        'reparations' => $reparations
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération des réparations du client: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des réparations: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception lors de la récupération des réparations du client: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?> 