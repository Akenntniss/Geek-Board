<?php
// Désactiver l'affichage d'erreurs pour éviter de corrompre le JSON
error_reporting(0);
ini_set('display_errors', 0);

// Définir les en-têtes pour éviter le cache et spécifier le type de contenu
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Fonction pour nettoyer les entrées
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

try {
    // Créer la connexion à la base de données directement
    $db_pdo = new PDO(
            "mysql:host=localhost;port=3306;dbname=geekboard_main;charset=utf8mb4",
    "root",
        "Maman01#",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Récupérer les données JSON envoyées
    $json_data = file_get_contents('php://input');
    if (empty($json_data)) {
        throw new Exception("Aucune donnée reçue");
    }
    
    $data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erreur de décodage JSON: " . json_last_error_msg());
    }
    
    // Vérifier si les données sont valides
    if (!$data || !isset($data['task_id']) || empty($data['task_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de tâche manquant ou invalide'
        ]);
        exit;
    }
    
    // Récupérer et nettoyer les données
    $task_id = (int)$data['task_id'];
    $titre = isset($data['titre']) ? cleanInput($data['titre']) : '';
    $description = isset($data['description']) ? cleanInput($data['description']) : '';
    $priorite = isset($data['priorite']) ? cleanInput($data['priorite']) : '';
    $statut = isset($data['statut']) ? cleanInput($data['statut']) : '';
    $date_limite = isset($data['date_limite']) && !empty($data['date_limite']) ? cleanInput($data['date_limite']) : null;
    $employe_id = isset($data['employe_id']) && !empty($data['employe_id']) ? (int)$data['employe_id'] : null;
    
    // Validation des données
    $errors = [];
    
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire";
    }
    
    if (empty($description)) {
        $errors[] = "La description est obligatoire";
    }
    
    if (empty($priorite)) {
        $errors[] = "La priorité est obligatoire";
    }
    
    if (empty($statut)) {
        $errors[] = "Le statut est obligatoire";
    }
    
    // Si des erreurs sont détectées
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors)
        ]);
        exit;
    }
    
    // Vérifier d'abord si la tâche existe
    $stmt = $db_pdo->prepare("SELECT id FROM taches WHERE id = ?");
    $stmt->execute([$task_id]);
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Tâche non trouvée'
        ]);
        exit;
    }
    
    // Mettre à jour la tâche
    $query = "UPDATE taches SET titre = ?, description = ?, priorite = ?, statut = ?";
    $params = [$titre, $description, $priorite, $statut];
    
    // Ajouter les paramètres optionnels
    if ($date_limite !== null) {
        $query .= ", date_limite = ?";
        $params[] = $date_limite;
    } else {
        $query .= ", date_limite = NULL";
    }
    
    if ($employe_id !== null) {
        $query .= ", employe_id = ?";
        $params[] = $employe_id;
    } else {
        $query .= ", employe_id = NULL";
    }
    
    $query .= " WHERE id = ?";
    $params[] = $task_id;
    
    $stmt = $db_pdo->prepare($query);
    $result = $stmt->execute($params);
    
    if (!$result) {
        throw new Exception("Erreur lors de l'exécution de la requête SQL");
    }
    
    // Vérifier si des modifications ont été effectuées
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Tâche mise à jour avec succès!'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Aucune modification n\'a été apportée à la tâche.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour de la tâche: ' . $e->getMessage()
    ]);
} 