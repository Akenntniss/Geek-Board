<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Charger la configuration de la base de données
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier l'ID de la réparation
if (!isset($_POST['reparation_id']) || empty($_POST['reparation_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de réparation manquant']);
    exit;
}

$reparation_id = (int)$_POST['reparation_id'];
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

try {
    // Vérifier si la réparation existe
    $check_stmt = $pdo->prepare("SELECT id FROM reparations WHERE id = ?");
    $check_stmt->execute([$reparation_id]);
    if (!$check_stmt->fetch()) {
        throw new Exception('Réparation non trouvée');
    }

    // Créer le dossier de destination s'il n'existe pas
    $upload_dir = __DIR__ . '/../assets/images/reparations/' . $reparation_id;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $uploaded_files = [];
    $errors = [];

    // Vérifier si nous avons une image encodée en base64 (depuis la caméra)
    if (isset($_POST['image']) && !empty($_POST['image'])) {
        // Extraire les données d'image de la chaîne base64
        $image_data = $_POST['image'];
        
        // Supprimer le préfixe data:image/jpeg;base64, si présent
        if (strpos($image_data, 'data:image/jpeg;base64,') === 0) {
            $image_data = substr($image_data, 23);
        } else if (strpos($image_data, 'data:image/png;base64,') === 0) {
            $image_data = substr($image_data, 22);
        }
        
        // Décoder les données base64
        $decoded_image = base64_decode($image_data);
        if ($decoded_image !== false) {
            // Générer un nom de fichier unique
            $new_file_name = uniqid() . '.jpg';
            $destination = $upload_dir . '/' . $new_file_name;
            
            // Enregistrer l'image
            if (file_put_contents($destination, $decoded_image)) {
                $file_url = 'assets/images/reparations/' . $reparation_id . '/' . $new_file_name;
                $uploaded_files[] = $file_url;
                
                // Ajouter l'entrée à la table photos_reparation
                $insert_stmt = $pdo->prepare("INSERT INTO photos_reparation (reparation_id, url, description) VALUES (?, ?, ?)");
                $insert_stmt->execute([$reparation_id, $file_url, $description]);
            } else {
                $errors[] = "Erreur lors de l'enregistrement de l'image";
            }
        } else {
            $errors[] = "Données d'image non valides";
        }
    }
    // Sinon vérifier si nous avons des fichiers téléchargés
    else if (isset($_FILES['photos']) && !empty($_FILES['photos']['tmp_name'][0])) {
        // Traiter chaque fichier
        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['photos']['name'][$key];
                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Vérifier l'extension du fichier
                $allowed_extensions = ['jpg', 'jpeg', 'png'];
                if (!in_array($file_extension, $allowed_extensions)) {
                    $errors[] = "Le fichier $file_name n'est pas une image valide";
                    continue;
                }

                // Générer un nom de fichier unique
                $new_file_name = uniqid() . '.' . $file_extension;
                $destination = $upload_dir . '/' . $new_file_name;

                // Déplacer le fichier
                if (move_uploaded_file($tmp_name, $destination)) {
                    $file_url = 'assets/images/reparations/' . $reparation_id . '/' . $new_file_name;
                    $uploaded_files[] = $file_url;
                    
                    // Ajouter l'entrée à la table photos_reparation
                    $insert_stmt = $pdo->prepare("INSERT INTO photos_reparation (reparation_id, url, description) VALUES (?, ?, ?)");
                    $insert_stmt->execute([$reparation_id, $file_url, $description]);
                } else {
                    $errors[] = "Erreur lors du téléchargement de $file_name";
                }
            } else {
                $errors[] = "Erreur lors du téléchargement du fichier #$key";
            }
        }
    } else {
        // Ni fichiers téléchargés ni image en base64
        echo json_encode(['success' => false, 'message' => 'Aucune photo fournie']);
        exit;
    }

    // Préparer la réponse
    echo json_encode([
        'success' => !empty($uploaded_files),
        'message' => !empty($uploaded_files) ? 'Photos téléchargées avec succès' : 'Aucune photo n\'a pu être téléchargée',
        'data' => [
            'uploaded_files' => $uploaded_files,
            'errors' => $errors
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}