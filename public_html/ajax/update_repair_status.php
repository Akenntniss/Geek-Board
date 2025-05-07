<?php
// Activer temporairement l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
$logFile = __DIR__ . '/status_update.log';
file_put_contents($logFile, "--- Nouvelle requête de mise à jour du statut ---\n", FILE_APPEND);
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
        error_log("Erreur: Connexion à la base de données non établie dans update_repair_status.php");
        throw new Exception('Erreur de connexion à la base de données');
    }
    
    // Vérifier quelle base de données nous utilisons réellement
    try {
        $db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
        $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Base de données connectée dans update_repair_status.php: " . ($db_info['current_db'] ?? 'Inconnue'));
        file_put_contents($logFile, "Base de données connectée: " . ($db_info['current_db'] ?? 'Inconnue') . "\n", FILE_APPEND);
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de la base: " . $e->getMessage());
    }

    // Récupérer les données JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    file_put_contents($logFile, "Données JSON décodées: " . print_r($data, true) . "\n", FILE_APPEND);

    // Vérifier que les données requises sont présentes
    if (!isset($data['repair_id']) || !isset($data['status_id'])) {
        throw new Exception('Données manquantes: ID de réparation ou ID de statut non fourni');
    }

    $repair_id = (int)$data['repair_id'];
    $status_id = (int)$data['status_id'];

    // Récupérer le code du statut
    $stmt = $shop_pdo->prepare("SELECT code FROM statuts WHERE id = ?");
    $stmt->execute([$status_id]);
    $statut = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$statut) {
        throw new Exception('Statut introuvable');
    }

    $status_code = $statut['code'];

    // Récupérer le statut précédent
    $stmt = $shop_pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
    $stmt->execute([$repair_id]);
    $old_status = $stmt->fetchColumn();
    
    if ($old_status === false) {
        file_put_contents($logFile, "Réparation non trouvée: ID $repair_id\n", FILE_APPEND);
        throw new Exception("Réparation ID $repair_id non trouvée dans la base " . ($db_info['current_db'] ?? 'Inconnue'));
    }

    // Mettre à jour le statut de la réparation
    $stmt = $shop_pdo->prepare("UPDATE reparations SET statut = ?, statut_id = ?, date_modification = NOW() WHERE id = ?");
    $result = $stmt->execute([$status_code, $status_id, $repair_id]);

    if (!$result) {
        throw new Exception('Erreur lors de la mise à jour du statut');
    }
    
    // Vérifier si la mise à jour a réellement affecté une ligne
    $affected_rows = $stmt->rowCount();
    if ($affected_rows === 0) {
        file_put_contents($logFile, "Mise à jour n'a affecté aucune ligne: $affected_rows\n", FILE_APPEND);
        error_log("Avertissement: Mise à jour du statut pour réparation ID $repair_id a réussi, mais aucune ligne n'a été affectée");
    } else {
        file_put_contents($logFile, "Lignes affectées: $affected_rows\n", FILE_APPEND);
        error_log("Succès: Statut de la réparation ID $repair_id mis à jour à $status_code ($affected_rows lignes affectées)");
    }

    // Récupérer l'ID de l'utilisateur connecté depuis la session
    $employe_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Si pas d'utilisateur connecté, vérifier dans la table users pour un utilisateur par défaut
    if (!$employe_id) {
        $stmt = $shop_pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            $employe_id = $admin['id'];
        } else {
            // Récupérer le premier utilisateur disponible
            $stmt = $shop_pdo->query("SELECT id FROM users LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $employe_id = $user['id'];
            } else {
                throw new Exception("Aucun utilisateur disponible pour le log");
            }
        }
    }

    // Journaliser le changement de statut
    $stmt = $shop_pdo->prepare("
        INSERT INTO reparation_logs 
        (reparation_id, employe_id, action_type, statut_avant, statut_apres, date_action, details) 
        VALUES (?, ?, 'status_change', ?, ?, NOW(), ?)
    ");
    
    $details = "Changement de statut via le modal: de '$old_status' à '$status_code'";
    
    $stmt->execute([
        $repair_id, 
        $employe_id, 
        $old_status, 
        $status_code, 
        $details
    ]);

    // Renvoyer un succès
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès',
        'status_code' => $status_code
    ]);

} catch (Exception $e) {
    file_put_contents($logFile, "Erreur: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 

/**
 * Convertit un nom de catégorie en valeur ENUM valide pour la table reparations
 */
function map_status_to_enum($categorie_nom) {
    $map = [
        'Nouvelle' => 'En attente',
        'En cours' => 'En cours',
        'En attente' => 'En attente',
        'Terminé' => 'Terminé',
        'Annulé' => 'Terminé' // Il n'y a pas d'équivalent direct, nous utilisons 'Terminé'
    ];
    
    return isset($map[$categorie_nom]) ? $map[$categorie_nom] : 'En attente';
} 