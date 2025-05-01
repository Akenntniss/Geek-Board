<?php
// Activer temporairement l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Démarrer la session pour accéder aux informations de l'utilisateur connecté
session_start();

// Créer un fichier de log pour le débogage
$logFile = __DIR__ . '/status_update.log';
file_put_contents($logFile, "--- Nouvelle requête de mise à jour du statut ---\n", FILE_APPEND);
file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

try {
    // Récupérer les chemins des fichiers includes
    $config_path = realpath(__DIR__ . '/../config/database.php');
    
    if (!file_exists($config_path)) {
        throw new Exception('Fichier de configuration introuvable.');
    }

    // Inclure les fichiers nécessaires
    require_once $config_path;

    // Récupérer les données JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Vérifier que les données requises sont présentes
    if (!isset($data['repair_id']) || !isset($data['status_id'])) {
        throw new Exception('Données manquantes: ID de réparation ou ID de statut non fourni');
    }

    $repair_id = (int)$data['repair_id'];
    $status_id = (int)$data['status_id'];

    // Récupérer le code du statut
    $stmt = $pdo->prepare("SELECT code FROM statuts WHERE id = ?");
    $stmt->execute([$status_id]);
    $statut = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$statut) {
        throw new Exception('Statut introuvable');
    }

    $status_code = $statut['code'];

    // Récupérer le statut précédent
    $stmt = $pdo->prepare("SELECT statut FROM reparations WHERE id = ?");
    $stmt->execute([$repair_id]);
    $old_status = $stmt->fetchColumn();

    // Mettre à jour le statut de la réparation
    $stmt = $pdo->prepare("UPDATE reparations SET statut = ?, statut_id = ?, date_modification = NOW() WHERE id = ?");
    $result = $stmt->execute([$status_code, $status_id, $repair_id]);

    if (!$result) {
        throw new Exception('Erreur lors de la mise à jour du statut');
    }

    // Récupérer l'ID de l'utilisateur connecté depuis la session
    $employe_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Si pas d'utilisateur connecté, vérifier dans la table users pour un utilisateur par défaut
    if (!$employe_id) {
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            $employe_id = $admin['id'];
        } else {
            // Récupérer le premier utilisateur disponible
            $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $employe_id = $user['id'];
            } else {
                throw new Exception("Aucun utilisateur disponible pour le log");
            }
        }
    }

    // Journaliser le changement de statut
    $stmt = $pdo->prepare("
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