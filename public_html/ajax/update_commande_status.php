<?php
// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Activer la journalisation des erreurs
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php-errors.log');

// Vérifier si le dossier logs existe, sinon le créer
if (!file_exists('../logs')) {
    mkdir('../logs', 0755, true);
}

// Inclusion des fichiers nécessaires
require_once '../config/database.php';
require_once '../includes/functions.php';

// Journaliser les informations de la requête
error_log('Requête reçue dans update_commande_status.php');

// Log des informations de session pour debuggage
error_log("SESSION: " . print_r($_SESSION, true));

// Vérifier et définir shop_id si nécessaire
if (!isset($_SESSION['shop_id'])) {
    // Essayer de récupérer shop_id depuis l'URL
    if (isset($_GET['shop_id'])) {
        $_SESSION['shop_id'] = $_GET['shop_id'];
        error_log("shop_id récupéré depuis URL: " . $_SESSION['shop_id']);
    } 
    // Essayer de récupérer shop_id depuis les données POST
    else if (isset($_POST['shop_id'])) {
        $_SESSION['shop_id'] = $_POST['shop_id'];
        error_log("shop_id récupéré depuis POST: " . $_SESSION['shop_id']);
    }
    // Utiliser une valeur par défaut (magasin principal)
    else {
        $_SESSION['shop_id'] = 1; // ID du magasin principal
        error_log("shop_id défini par défaut: " . $_SESSION['shop_id']);
    }
}

// Obtenir la connexion à la base de données du magasin
try {
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        throw new Exception('Connexion à la base de données échouée');
    }
    error_log('Connexion à la base de données établie avec succès pour shop_id: ' . $_SESSION['shop_id']);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage()]);
    error_log('Erreur: connexion à la base de données non établie dans update_commande_status.php - ' . $e->getMessage());
    exit;
}

// Vérifier si la requête est en POST et au format JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    error_log('Erreur: méthode non autorisée dans update_commande_status.php');
    exit;
}

// Récupérer les données JSON
$json_data = file_get_contents('php://input');
error_log('Données JSON reçues: ' . $json_data);

$data = json_decode($json_data, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de décodage JSON: ' . json_last_error_msg()]);
    error_log('Erreur de décodage JSON: ' . json_last_error_msg());
    exit;
}

// FORCER l'utilisation du shop_id depuis les données JSON
if (isset($data['shop_id'])) {
    $_SESSION['shop_id'] = intval($data['shop_id']);
    error_log("shop_id FORCÉ depuis les données JSON: " . $_SESSION['shop_id']);
} elseif (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 1; // Fallback
    error_log("shop_id défini par défaut: " . $_SESSION['shop_id']);
}

// Vérifier les données
if (!isset($data['commande_id']) || !isset($data['new_status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    error_log('Erreur: données manquantes dans update_commande_status.php');
    exit;
}

$commande_id = intval($data['commande_id']);
$new_status = $data['new_status'];
error_log("Mise à jour du statut: commande_id=$commande_id, new_status=$new_status");

// DEBUG: Vérifier si la commande existe avant la mise à jour
$check_stmt = $shop_pdo->prepare("SELECT id, statut FROM commandes_pieces WHERE id = :id");
$check_stmt->execute([':id' => $commande_id]);
$existing_commande = $check_stmt->fetch(PDO::FETCH_ASSOC);
error_log("Commande existante: " . print_r($existing_commande, true));

// Vérifier que le statut est valide
$valid_statuses = ['en_attente', 'commande', 'recue', 'annulee', 'urgent', 'utilise', 'a_retourner'];
error_log("Vérification du statut: $new_status, valides: " . implode(', ', $valid_statuses));
if (!in_array($new_status, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Statut invalide: ' . $new_status]);
    error_log("Erreur: statut invalide: $new_status");
    exit;
}

try {
    // Mettre à jour le statut de la commande
    error_log("Préparation de la requête UPDATE pour commande_id=$commande_id et statut=$new_status");
    $stmt = $shop_pdo->prepare("UPDATE commandes_pieces SET statut = :statut, date_modification = NOW() WHERE id = :id");
    $result = $stmt->execute([
        ':statut' => $new_status,
        ':id' => $commande_id
    ]);
    
    error_log("Résultat de l'exécution de la requête: " . ($result ? "succès" : "échec") . ", lignes affectées: " . $stmt->rowCount());
    
    if ($result && $stmt->rowCount() > 0) {
        // Vérifier si la table historique_statuts existe
        try {
            $stmt_check = $shop_pdo->prepare("SHOW TABLES LIKE 'historique_statuts'");
            $stmt_check->execute();
            $table_exists = $stmt_check->rowCount() > 0;
            
            // Si la table existe, enregistrer l'historique
            if ($table_exists) {
                $stmt_historique = $shop_pdo->prepare("
                    INSERT INTO historique_statuts 
                    (commande_id, statut_ancien, statut_nouveau, user_id, date_creation) 
                    VALUES 
                    (:commande_id, (SELECT statut FROM commandes_pieces WHERE id = :id_for_old), :statut_nouveau, :user_id, NOW())
                ");
                
                // Utiliser l'ID de l'utilisateur de la session s'il existe, sinon utiliser 1 comme valeur par défaut
                $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
                
                $stmt_historique->execute([
                    ':commande_id' => $commande_id,
                    ':id_for_old' => $commande_id,
                    ':statut_nouveau' => $new_status,
                    ':user_id' => $user_id
                ]);
            }
        } catch (PDOException $e) {
            // Ignorer l'erreur si la table n'existe pas et continuer
            error_log("Erreur lors de l'enregistrement de l'historique: " . $e->getMessage());
        }
        
        // Retourner un succès
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
    } else {
        header('Content-Type: application/json');
        error_log("Aucune ligne affectée pour commande_id=$commande_id et statut=$new_status");
        echo json_encode(['success' => false, 'message' => 'Commande non trouvée ou aucune modification nécessaire']);
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    $error_message = 'Erreur lors de la mise à jour: ' . $e->getMessage();
    error_log($error_message);
    error_log("Trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
} 