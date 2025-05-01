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
    // Vérifier la connexion à la base de données
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Connexion à la base de données non disponible');
    }
    
    // Récupérer les commandes du client
    $sql = "
        SELECT cp.*
        FROM commandes_pieces cp
        WHERE cp.client_id = :client_id
        ORDER BY cp.date_creation DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatage des données pour JSON
    $commandes = array_map(function($cmd) {
        // Éviter les problèmes d'encodage
        foreach ($cmd as $key => $value) {
            $cmd[$key] = is_string($value) ? $value : $value;
        }
        return $cmd;
    }, $commandes);
    
    // Renvoyer les résultats
    echo json_encode([
        'success' => true,
        'count' => count($commandes),
        'commandes' => $commandes
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération des commandes du client: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des commandes: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception lors de la récupération des commandes du client: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?> 