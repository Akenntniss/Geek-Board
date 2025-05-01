<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Désactiver l'affichage des erreurs pour les réponses JSON propres
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    if (!$config_path) {
        throw new Exception('Impossible de localiser les fichiers requis');
    }

    // Inclure le fichier de configuration
    require_once $config_path;
    
    // Vérifier que la connexion PDO existe
    if (!isset($pdo) || $pdo === null) {
        throw new Exception('Erreur de connexion à la base de données: connexion PDO non disponible');
    }
    
    // Récupérer l'ID de la réparation
    $repair_id = isset($_GET['repair_id']) ? (int)$_GET['repair_id'] : 0;
    
    if ($repair_id <= 0) {
        throw new Exception('ID de réparation invalide');
    }
    
    // Récupérer les informations du client associé à cette réparation
    $stmt = $pdo->prepare("
        SELECT 
            c.id AS client_id, 
            c.nom AS client_nom, 
            c.prenom AS client_prenom, 
            c.telephone AS client_telephone
        FROM 
            reparations r
        JOIN 
            clients c ON r.client_id = c.id
        WHERE 
            r.id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$repair_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        throw new Exception('Client non trouvé pour cette réparation');
    }
    
    // Retourner les informations du client
    echo json_encode([
        'success' => true,
        'client_id' => $client['client_id'],
        'client_nom' => $client['client_nom'],
        'client_prenom' => $client['client_prenom'],
        'client_telephone' => $client['client_telephone']
    ]);
    
} catch (Exception $e) {
    // Envoyer une réponse d'erreur
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 