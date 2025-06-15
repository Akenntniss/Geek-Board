<?php
// Fichier de traitement des rachats d'appareils
session_start();
require_once __DIR__ . '/../config/database.php';

// Obtenir la connexion à la base de données de la boutique
$shop_pdo = getShopDBConnection();

// Vérification de la connexion et des droits
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit();
}

// Vérification du formulaire soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération des données du formulaire
        $client_id = filter_input(INPUT_POST, 'client_id', FILTER_SANITIZE_NUMBER_INT);
        $modele = filter_input(INPUT_POST, 'modele', FILTER_SANITIZE_STRING);
        $numero_serie = filter_input(INPUT_POST, 'numero_serie', FILTER_SANITIZE_STRING);
        $fonctionnel = filter_input(INPUT_POST, 'fonctionnel', FILTER_SANITIZE_NUMBER_INT);
        $prix = filter_input(INPUT_POST, 'prix', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $signature = $_POST['signature']; // Base64 encoded signature
        
        // Validation des données
        if (empty($client_id) || empty($modele) || empty($numero_serie) || empty($signature)) {
            throw new Exception("Tous les champs sont obligatoires");
        }
        
        // Traitement des images
        $photo_identite = '';
        $photo_appareil = '';
        
        if (isset($_FILES['photo_identite']) && $_FILES['photo_identite']['error'] === UPLOAD_ERR_OK) {
            $photo_identite = uploadImage($_FILES['photo_identite'], 'identite');
        } else {
            throw new Exception("La photo de la pièce d'identité est obligatoire");
        }
        
        if (isset($_FILES['photo_appareil']) && $_FILES['photo_appareil']['error'] === UPLOAD_ERR_OK) {
            $photo_appareil = uploadImage($_FILES['photo_appareil'], 'appareil');
        } else {
            throw new Exception("La photo de l'appareil est obligatoire");
        }
        
        // Insertion dans la base de données
        $stmt = $shop_pdo->prepare("INSERT INTO rachat_appareils 
                              (client_id, modele, numero_serie, fonctionnel, photo_identite, photo_appareil, signature, prix, date_rachat) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $result = $stmt->execute([
            $client_id,
            $modele,
            $numero_serie,
            $fonctionnel,
            $photo_identite,
            $photo_appareil,
            $signature,
            $prix
        ]);
        
        if ($result) {
            // Redirection avec message de succès
            $_SESSION['success_message'] = "Le rachat a été enregistré avec succès";
            header('Location: /pages/rachat_appareils.php');
            exit();
        } else {
            throw new Exception("Erreur lors de l'enregistrement dans la base de données");
        }
        
    } catch (Exception $e) {
        // Redirection avec message d'erreur
        $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        header('Location: /pages/rachat_appareils.php');
        exit();
    }
} else {
    // Redirection si accès direct
    header('Location: /pages/rachat_appareils.php');
    exit();
}

/**
 * Fonction pour uploader et sauvegarder une image
 * @param array $file Tableau $_FILES
 * @param string $prefix Préfixe pour le nom du fichier
 * @return string Nom du fichier sauvegardé
 */
function uploadImage($file, $prefix) {
    // Vérification du type MIME
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception("Le type de fichier n'est pas autorisé. Utilisez JPG, PNG ou GIF.");
    }
    
    // Création du répertoire si nécessaire
    $upload_dir = __DIR__ . '/../assets/images/rachats/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Génération d'un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . '_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Déplacement du fichier
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Erreur lors de l'upload du fichier");
    }
    
    return $filename;
} 