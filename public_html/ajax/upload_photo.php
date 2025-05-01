<?php
// Définir l'en-tête Content-Type
header('Content-Type: application/json');

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Définir le chemin absolu vers le fichier de configuration
$config_path = dirname(__DIR__) . '/config/database.php';

// Vérifier si le fichier de configuration existe
if (!file_exists($config_path)) {
    echo json_encode([
        'success' => false,
        'error' => 'Fichier de configuration manquant à : ' . $config_path
    ]);
    exit;
}

require_once $config_path;
require_once dirname(__DIR__) . '/includes/functions.php';

// Vérifier si la connexion à la base de données est établie
if (!isset($pdo)) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

// Vérifier si une photo a été envoyée
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'error' => 'Aucune photo n\'a été envoyée ou erreur lors de l\'upload'
    ]);
    exit;
}

// Vérifier si l'ID de réparation est fourni
if (!isset($_POST['reparation_id']) || empty($_POST['reparation_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'ID de réparation manquant'
    ]);
    exit;
}

$reparation_id = (int)$_POST['reparation_id'];
$description = isset($_POST['description']) ? cleanInput($_POST['description']) : '';

// Vérifier le type de fichier
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$file_type = $_FILES['photo']['type'];

if (!in_array($file_type, $allowed_types)) {
    echo json_encode([
        'success' => false,
        'error' => 'Type de fichier non autorisé. Types acceptés : JPG, PNG, GIF'
    ]);
    exit;
}

// Vérifier la taille du fichier (max 5MB)
$max_size = 5 * 1024 * 1024; // 5MB en bytes
if ($_FILES['photo']['size'] > $max_size) {
    echo json_encode([
        'success' => false,
        'error' => 'Le fichier est trop volumineux. Taille maximale : 5MB'
    ]);
    exit;
}

try {
    // Créer le dossier de stockage s'il n'existe pas
    $upload_dir = '../uploads/photos/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Impossible de créer le dossier de stockage');
        }
    }

    // Vérifier les permissions du dossier
    if (!is_writable($upload_dir)) {
        throw new Exception('Le dossier de stockage n\'est pas accessible en écriture');
    }

    // Générer un nom de fichier unique
    $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid('photo_') . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;

    // Déplacer le fichier
    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $file_path)) {
        throw new Exception('Erreur lors du déplacement du fichier');
    }

    // Insérer les informations dans la base de données
    $stmt = $pdo->prepare("
        INSERT INTO photos_reparation (reparation_id, url, description, date_upload)
        VALUES (?, ?, ?, NOW())
    ");

    $photo_url = 'uploads/photos/' . $file_name;
    $stmt->execute([$reparation_id, $photo_url, $description]);
    $photo_id = $pdo->lastInsertId();

    // Renvoyer la réponse
    echo json_encode([
        'success' => true,
        'photo_id' => $photo_id,
        'photo_url' => $photo_url,
        'description' => $description
    ]);

} catch (Exception $e) {
    // En cas d'erreur, supprimer le fichier s'il a été uploadé
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }

    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de l\'upload de la photo : ' . $e->getMessage()
    ]);
} 