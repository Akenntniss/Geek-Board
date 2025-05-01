<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Log des données POST reçues
error_log("POST reçu: " . print_r($_POST, true));

// Vérifier que le terme de recherche est fourni
if (!isset($_POST['terme']) || empty($_POST['terme'])) {
    error_log("Terme de recherche manquant");
    echo json_encode(['success' => false, 'message' => 'Terme de recherche manquant']);
    exit;
}

$terme = trim($_POST['terme']);
error_log("Terme de recherche: " . $terme);

try {
    // Vérifier la connexion à la base de données
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        error_log("Connexion à la base de données non disponible");
        throw new Exception('Connexion à la base de données non disponible');
    }
    
    // Préparer la requête SQL avec trois paramètres distincts
    $sql = "
        SELECT id, nom, prenom, telephone 
        FROM clients 
        WHERE nom LIKE :terme_nom 
        OR prenom LIKE :terme_prenom 
        OR telephone LIKE :terme_tel 
        ORDER BY nom, prenom 
        LIMIT 10
    ";
    error_log("Requête SQL: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        error_log("Erreur de préparation de la requête: " . implode(' ', $pdo->errorInfo()));
        throw new Exception('Erreur de préparation de la requête: ' . implode(' ', $pdo->errorInfo()));
    }
    
    // Exécuter la requête avec les trois paramètres
    $terme = "%$terme%";
    error_log("Terme de recherche avec wildcards: " . $terme);
    
    $stmt->bindParam(':terme_nom', $terme);
    $stmt->bindParam(':terme_prenom', $terme);
    $stmt->bindParam(':terme_tel', $terme);
    
    error_log("Exécution de la requête...");
    $stmt->execute();
    error_log("Requête exécutée");
    
    // Récupérer les résultats
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Résultats trouvés: " . count($clients));
    error_log("Données des clients: " . print_r($clients, true));
    
    echo json_encode([
        'success' => true,
        'clients' => $clients
    ]);
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la recherche des clients: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la recherche des clients: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception lors de la recherche des clients: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}
?> 