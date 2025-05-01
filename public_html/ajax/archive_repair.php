<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Créer un fichier de log pour le débogage
$logFile = __DIR__ . '/archive_repair.log';
file_put_contents($logFile, "--- Nouvelle requête d'archivage ---\n", FILE_APPEND);
file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    file_put_contents($logFile, "Config path: " . $config_path . "\n", FILE_APPEND);

    if (!file_exists($config_path)) {
        throw new Exception('Fichiers de configuration introuvables.');
    }

    require_once $config_path;

    // Vérifier si la requête est en POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupérer les données sous forme JSON
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (!$data) {
        // Si les données JSON ne sont pas valides, essayer de récupérer les données POST normales
        $reparation_id = isset($_POST['repair_id']) ? (int)$_POST['repair_id'] : 0;
    } else {
        $reparation_id = isset($data['repair_id']) ? (int)$data['repair_id'] : 0;
    }

    file_put_contents($logFile, "ID réparation: $reparation_id\n", FILE_APPEND);

    // Valider les données
    if ($reparation_id <= 0) {
        throw new Exception('ID de réparation invalide');
    }

    // Mettre à jour le statut d'archive de la réparation
    $stmt = $pdo->prepare("UPDATE reparations SET archive = 'OUI' WHERE id = ?");
    
    if (!$stmt->execute([$reparation_id])) {
        $error = $stmt->errorInfo();
        file_put_contents($logFile, "Erreur SQL: " . print_r($error, true) . "\n", FILE_APPEND);
        throw new Exception('Erreur lors de l\'archivage: ' . $error[2]);
    }

    // Vérifier si la mise à jour a réussi
    if ($stmt->rowCount() > 0) {
        $response = [
            'success' => true,
            'message' => 'Réparation archivée avec succès',
            'data' => [
                'repair_id' => $reparation_id
            ]
        ];
        
        file_put_contents($logFile, "Réponse: Succès - Réparation archivée\n", FILE_APPEND);
        echo json_encode($response);
    } else {
        throw new Exception('Aucune réparation mise à jour');
    }

} catch (Exception $e) {
    // Log l'erreur pour le débogage
    $error_message = "Erreur dans archive_repair.php: " . $e->getMessage();
    error_log($error_message);
    file_put_contents($logFile, "Exception: " . $error_message . "\n", FILE_APPEND);
    
    // Renvoyer une réponse JSON d'erreur
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 