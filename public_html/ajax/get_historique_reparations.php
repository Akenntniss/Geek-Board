<?php
/**
 * API pour récupérer l'historique des réparations d'un client
 */

// Inclure la configuration et la connexion à la base de données
require_once '../config/database.php';

// Définir l'en-tête pour la réponse JSON
header('Content-Type: application/json');

// Log de l'appel pour le débogage
error_log("Appel à l'API historique réparations - Client ID: " . (isset($_GET['client_id']) ? $_GET['client_id'] : 'non fourni'));

// Vérifier si l'ID du client est fourni
if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID client non fourni'
    ]);
    exit;
}

// Récupérer l'ID du client
$clientId = intval($_GET['client_id']);

try {
    // Requête pour récupérer les réparations du client avec les colonnes correctes
    $sql = "SELECT 
        id, type_appareil, marque, modele, date_reception, statut,
        prix as prix_total, description_probleme as probleme_signe, 
        notes_techniques as diagnostic, signature as solution
        FROM reparations 
        WHERE client_id = :client_id
        ORDER BY date_reception DESC
        LIMIT 20";
    
    error_log("Requête SQL réparations: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':client_id', $clientId, PDO::PARAM_INT);
    
    error_log("Exécution de la requête avec client_id: " . $clientId);
    $stmt->execute();
    
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Nombre de réparations trouvées: " . count($reparations));
    
    // Réponse réussie
    echo json_encode([
        'success' => true,
        'reparations' => $reparations
    ]);
    
} catch (PDOException $e) {
    // Réponse d'erreur
    $error = "Erreur de base de données: " . $e->getMessage();
    error_log($error);
    echo json_encode([
        'success' => false,
        'message' => $error
    ]);
} catch (Exception $e) {
    $error = "Erreur générale: " . $e->getMessage();
    error_log($error);
    echo json_encode([
        'success' => false,
        'message' => $error
    ]);
} 