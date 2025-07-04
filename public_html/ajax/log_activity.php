<?php
session_start();
header('Content-Type: application/json');

// Configuration de la base de données (même config que recherche_universelle.php)
$host = '191.96.63.103';
$dbname = 'geekboard_pscannes';
$username = 'root';
$password = 'Merguez01#';

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['type']) || !isset($input['data'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$type = $input['type'];
$data = $input['data'];
$timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');

$user_id = $_SESSION['user_id'] ?? 0;
$boutique_id = $_SESSION['boutique_id'] ?? 1;

try {
    // Connexion directe à la base de données (comme recherche_universelle.php)
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]
    );
    
    // Vérifier si la table activities existe, sinon la créer
    $createTable = "
        CREATE TABLE IF NOT EXISTS user_activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            boutique_id INT NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            activity_data JSON NOT NULL,
            timestamp DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_boutique (user_id, boutique_id),
            INDEX idx_type (activity_type),
            INDEX idx_timestamp (timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($createTable);
    
    // Insérer l'activité
    $stmt = $pdo->prepare("
        INSERT INTO user_activities (user_id, boutique_id, activity_type, activity_data, timestamp)
        VALUES (:user_id, :boutique_id, :activity_type, :activity_data, :timestamp)
    ");
    
    $stmt->execute([
        ':user_id' => $user_id,
        ':boutique_id' => $boutique_id,
        ':activity_type' => $type,
        ':activity_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ':timestamp' => $timestamp
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Activité enregistrée']);
    
} catch (PDOException $e) {
    error_log("Erreur base de données dans log_activity.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
} catch (Exception $e) {
    error_log("Erreur dans log_activity.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?> 