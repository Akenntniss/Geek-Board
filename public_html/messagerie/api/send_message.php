<?php
/**
 * API - Envoyer un message dans une conversation
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de base de données
require_once '../../config/database.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Inclure les fonctions
require_once '../includes/functions.php';

// Récupérer les données
$conversation_id = null;
$contenu = '';

try {
    // Si c'est un formulaire normal
    if (isset($_POST['conversation_id'])) {
        $conversation_id = (int)$_POST['conversation_id'];
        $contenu = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';
    } 
    // Si c'est une requête JSON
    else {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input && isset($input['conversation_id'])) {
            $conversation_id = (int)$input['conversation_id'];
            $contenu = isset($input['contenu']) ? trim($input['contenu']) : '';
        }
    }
    
    // Validation des données
    if (!$conversation_id) {
        throw new Exception('ID de conversation manquant ou invalide');
    }
    
    // Vérifier l'accès à la conversation
    $access = user_has_conversation_access($_SESSION['user_id'], $conversation_id);
    if (!$access) {
        throw new Exception('Accès refusé à cette conversation');
    }
    
    // Envoyer le message
    global $shop_pdo;
    
    // Commencer une transaction
    $shop_pdo->beginTransaction();
    
    // Insérer le message (en utilisant directement PDO au lieu de la fonction)
    $stmt = $shop_pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, contenu, type, date_envoi)
        VALUES (:conversation_id, :sender_id, :contenu, 'text', NOW())
    ");
    
    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':sender_id' => $_SESSION['user_id'],
        ':contenu' => $contenu
    ]);
    
    $message_id = $shop_pdo->lastInsertId();
    
    // Mettre à jour la dernière activité de la conversation
    $stmt = $shop_pdo->prepare("
        UPDATE conversations 
        SET derniere_activite = NOW() 
        WHERE id = :conversation_id
    ");
    
    $stmt->execute([':conversation_id' => $conversation_id]);
    
    // Marquer le message comme lu par l'expéditeur
    $stmt = $shop_pdo->prepare("
        INSERT IGNORE INTO message_reads (message_id, user_id, date_lecture)
        VALUES (:message_id, :user_id, NOW())
    ");
    
    $stmt->execute([
        ':message_id' => $message_id,
        ':user_id' => $_SESSION['user_id']
    ]);
    
    // Valider la transaction
    $shop_pdo->commit();
    
    // Réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Message envoyé avec succès',
        'message_id' => $message_id
    ]);
    
} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if (isset($shop_pdo) && $shop_pdo->inTransaction()) {
        $shop_pdo->rollback();
    }
    
    // Journaliser l'erreur
    log_error('Erreur lors de l\'envoi du message', $e->getMessage() . ' - ' . $e->getTraceAsString());
    
    // Réponse d'erreur
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi du message: ' . $e->getMessage()
    ]);
}
exit;

/**
 * Crée une miniature d'une image
 * 
 * @param string $source Chemin de l'image source
 * @param string $destination Chemin de destination de la miniature
 * @param int $width Largeur maximale
 * @param int $height Hauteur maximale
 * @return bool Succès ou échec
 */
function create_thumbnail($source, $destination, $width, $height) {
    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }
    
    // Obtenir les dimensions de l'image
    list($img_width, $img_height, $img_type) = getimagesize($source);
    
    // Calculer les dimensions de la miniature en conservant le ratio
    $ratio = min($width / $img_width, $height / $img_height);
    $new_width = $img_width * $ratio;
    $new_height = $img_height * $ratio;
    
    // Créer une image vide avec les nouvelles dimensions
    $thumb = imagecreatetruecolor($new_width, $new_height);
    
    // Charger l'image source selon son type
    switch ($img_type) {
        case IMAGETYPE_JPEG:
            $src_img = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $src_img = imagecreatefrompng($source);
            // Préserver la transparence
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            break;
        case IMAGETYPE_GIF:
            $src_img = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Redimensionner l'image
    imagecopyresampled($thumb, $src_img, 0, 0, 0, 0, $new_width, $new_height, $img_width, $img_height);
    
    // Sauvegarder la miniature selon le type de l'image source
    switch ($img_type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $destination, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $destination, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $destination);
            break;
        default:
            return false;
    }
    
    // Libérer la mémoire
    imagedestroy($thumb);
    imagedestroy($src_img);
    
    return true;
} 