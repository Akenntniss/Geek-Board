<?php
// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Démarrer la session pour accéder aux informations de l'utilisateur connecté
session_start();

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable.');
    }

    // Inclure les fichiers nécessaires
    require_once $config_path;

    // Vérifier que les paramètres nécessaires sont présents
    if (!isset($_POST['repair_id']) || !isset($_POST['urgent'])) {
        throw new Exception('Paramètres manquants.');
    }

    // Récupérer les valeurs
    $repair_id = intval($_POST['repair_id']);
    $urgent = intval($_POST['urgent']) === 1 ? 1 : 0;

    // Mettre à jour la base de données
    $stmt = $pdo->prepare("UPDATE reparations SET urgent = ? WHERE id = ?");
    $stmt->execute([$urgent, $repair_id]);

    // Récupérer l'ID de l'utilisateur connecté pour le log
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Si aucun utilisateur n'est connecté, essayer de trouver un administrateur
    if (!$user_id) {
        $adminStmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $adminStmt->execute();
        $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            $user_id = $admin['id'];
        } else {
            // En dernier recours, prendre le premier utilisateur disponible
            $userStmt = $pdo->prepare("SELECT id FROM users LIMIT 1");
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
        
        $stmt = $pdo->prepare("
            INSERT INTO reparation_logs 
            (reparation_id, employe_id, action_type, details, date_action) 
            VALUES (?, ?, 'autre', ?, NOW())
        ");
        $stmt->execute([$repair_id, $user_id, $logDetails]);
    }

    // Retourner une réponse de succès
    echo json_encode([
        'success' => true,
        'urgent' => $urgent
    ]);

} catch (Exception $e) {
    // Gérer les erreurs
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 