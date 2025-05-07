<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Démarrer la session pour accéder aux informations de l'utilisateur connecté
session_start();

// Récupérer l'ID du magasin depuis les paramètres POST ou GET
$shop_id_from_request = $_POST['shop_id'] ?? $_GET['shop_id'] ?? null;
if ($shop_id_from_request) {
    $_SESSION['shop_id'] = $shop_id_from_request;
    error_log("ID du magasin récupéré depuis la requête: $shop_id_from_request");
}

// Créer un fichier de log pour le débogage
$logFile = __DIR__ . '/urgent_toggle.log';
file_put_contents($logFile, "--- Nouvelle requête de changement d'état urgent ---\n", FILE_APPEND);
file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($logFile, "Session: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable.');
    }

    // Inclure les fichiers nécessaires
    require_once $config_path;
    
    // Utiliser la connexion à la base de données du magasin
    $shop_pdo = getShopDBConnection();
    
    // Vérifier que la connexion à la base de données est établie
    if (!isset($shop_pdo) || $shop_pdo === null) {
        error_log("Erreur: Connexion à la base de données non établie dans toggle_repair_urgent.php");
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Vérifier quelle base de données nous utilisons réellement
    try {
        $db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
        $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Base de données connectée dans toggle_repair_urgent.php: " . ($db_info['current_db'] ?? 'Inconnue'));
        file_put_contents($logFile, "Base de données connectée: " . ($db_info['current_db'] ?? 'Inconnue') . "\n", FILE_APPEND);
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de la base: " . $e->getMessage());
    }

    // Vérifier que les paramètres nécessaires sont présents
    if (!isset($_POST['repair_id']) || !isset($_POST['urgent'])) {
        throw new Exception('Paramètres manquants.');
    }

    // Récupérer les valeurs
    $repair_id = intval($_POST['repair_id']);
    $urgent = intval($_POST['urgent']) === 1 ? 1 : 0;

    // Vérifier que la réparation existe
    $checkStmt = $shop_pdo->prepare("SELECT id FROM reparations WHERE id = ?");
    $checkStmt->execute([$repair_id]);
    if (!$checkStmt->fetch()) {
        file_put_contents($logFile, "Réparation non trouvée: ID $repair_id\n", FILE_APPEND);
        throw new Exception("Réparation ID $repair_id non trouvée dans la base " . ($db_info['current_db'] ?? 'Inconnue'));
    }

    // Mettre à jour la base de données
    $stmt = $shop_pdo->prepare("UPDATE reparations SET urgent = ? WHERE id = ?");
    $stmt->execute([$urgent, $repair_id]);
    
    // Vérifier si la mise à jour a réellement affecté une ligne
    $affected_rows = $stmt->rowCount();
    if ($affected_rows === 0) {
        file_put_contents($logFile, "Mise à jour n'a affecté aucune ligne: $affected_rows\n", FILE_APPEND);
        error_log("Avertissement: Mise à jour de l'état urgent pour réparation ID $repair_id a réussi, mais aucune ligne n'a été affectée");
    } else {
        file_put_contents($logFile, "Lignes affectées: $affected_rows\n", FILE_APPEND);
        error_log("Succès: État urgent de la réparation ID $repair_id mis à jour à $urgent ($affected_rows lignes affectées)");
    }

    // Récupérer l'ID de l'utilisateur connecté pour le log
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Si aucun utilisateur n'est connecté, essayer de trouver un administrateur
    if (!$user_id) {
        $adminStmt = $shop_pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $adminStmt->execute();
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            $user_id = $admin['id'];
        } else {
            // En dernier recours, prendre le premier utilisateur disponible
            $userStmt = $shop_pdo->prepare("SELECT id FROM users LIMIT 1");
            $userStmt->execute();
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $user_id = $user['id'];
            }
        }
    }

    // Enregistrer dans les logs de réparation
    if ($user_id) {
        $logDetails = $urgent 
            ? "Réparation marquée comme URGENTE" 
            : "État urgent supprimé de la réparation";
        
        $stmt = $shop_pdo->prepare("
            INSERT INTO reparation_logs 
            (reparation_id, employe_id, action_type, details, date_action) 
            VALUES (?, ?, 'autre', ?, NOW())
        ");
        $stmt->execute([$repair_id, $user_id, $logDetails]);
        
        file_put_contents($logFile, "Log ajouté avec succès: $logDetails\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, "Aucun utilisateur trouvé pour le log\n", FILE_APPEND);
    }

    // Retourner une réponse de succès
    echo json_encode([
        'success' => true,
        'urgent' => $urgent,
        'db_used' => $db_info['current_db'] ?? 'Inconnue'
    ]);

} catch (Exception $e) {
    // Gérer les erreurs
    file_put_contents($logFile, "Erreur: " . $e->getMessage() . "\n", FILE_APPEND);
    error_log("Erreur dans toggle_repair_urgent.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 