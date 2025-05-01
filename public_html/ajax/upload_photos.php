<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once('../config/database.php');

if (!$pdo) {
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}

if (!isset($_POST['photos']) || !isset($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Paramètres manquants'
    ]);
    exit;
}

$repair_id = (int)$_GET['id'];
$uploaded_photos = [];

try {
    // Créer le dossier pour les photos s'il n'existe pas
    $upload_dir = '../uploads/photos_reparation/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['photos']['name'][$key];
        $file_size = $_FILES['photos']['size'][$key];
        $file_tmp = $_FILES['photos']['tmp_name'][$key];
        $file_type = $_FILES['photos']['type'][$key];

        // Vérifier le type de fichier
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file_type, $allowed_types)) {
            continue;
        }

        // Générer un nom de fichier unique
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $new_file_name = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $new_file_name;

        // Déplacer le fichier
        if (move_uploaded_file($file_tmp, $file_path)) {
            // Insérer la photo dans la base de données
            $sql = "INSERT INTO photos_reparation (reparation_id, url) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$repair_id, 'uploads/photos_reparation/' . $new_file_name]);
            
            $uploaded_photos[] = 'uploads/photos_reparation/' . $new_file_name;
        }
    }

    echo json_encode([
        'success' => true,
        'photos' => $uploaded_photos
    ]);

} catch (Exception $e) {
    error_log("Erreur lors de l'upload des photos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de l\'upload des photos'
    ]);
} 