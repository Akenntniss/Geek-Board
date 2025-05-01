<?php
session_start();
require_once '../includes/config.php';

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérification des données POST
if (!isset($_POST['conversation_id']) || !isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$conversation_id = intval($_POST['conversation_id']);
$user_id = $_SESSION['user_id'];
$file = $_FILES['file'];

// Vérification du fichier
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_message = match($file['error']) {
        UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée',
        UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée',
        UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
        UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier',
        UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement',
        default => 'Erreur inconnue lors du téléchargement'
    };
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

// Vérification de la taille du fichier (max 10MB)
$max_size = 10 * 1024 * 1024; // 10MB en octets
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'Le fichier ne doit pas dépasser 10MB']);
    exit;
}

// Vérification du type de fichier
$allowed_types = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain'
];

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé']);
    exit;
}

try {
    // Démarrer la transaction
    $pdo->beginTransaction();

    // Vérifier si l'utilisateur a accès à cette conversation
    $stmt = $pdo->prepare("
        SELECT role 
        FROM conversation_participants 
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$conversation_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
        exit;
    }

    // Créer le dossier de stockage s'il n'existe pas
    $upload_dir = '../uploads/messages/' . $conversation_id;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . '/' . $filename;

    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Échec du déplacement du fichier');
    }

    // Insérer le message avec le fichier
    $stmt = $pdo->prepare("
        INSERT INTO messages (
            conversation_id, 
            sender_id, 
            contenu, 
            type, 
            fichier_nom, 
            fichier_type, 
            fichier_taille, 
            date_envoi
        ) VALUES (?, ?, ?, 'file', ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $conversation_id,
        $user_id,
        $file['name'],
        $filename,
        $file['type'],
        $file['size']
    ]);
    $message_id = $pdo->lastInsertId();

    // Créer des notifications pour tous les participants
    $stmt = $pdo->prepare("
        INSERT INTO notifications_messages (user_id, conversation_id, message_id, est_lu, date_creation)
        SELECT user_id, ?, ?, 0, NOW()
        FROM conversation_participants
        WHERE conversation_id = ? AND user_id != ?
    ");
    $stmt->execute([$conversation_id, $message_id, $conversation_id, $user_id]);

    // Récupérer les détails du message
    $stmt = $pdo->prepare("
        SELECT m.*, 
               u.nom as sender_nom,
               u.prenom as sender_prenom
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.id = ?
    ");
    $stmt->execute([$message_id]);
    $message_details = $stmt->fetch(PDO::FETCH_ASSOC);

    // Valider la transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $message_details
    ]);

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    $pdo->rollBack();
    
    // Supprimer le fichier s'il a été uploadé
    if (isset($filepath) && file_exists($filepath)) {
        unlink($filepath);
    }
    
    error_log("Erreur (upload_file.php): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
} 