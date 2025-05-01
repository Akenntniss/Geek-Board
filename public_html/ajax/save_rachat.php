<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// S'assurer que la session est démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers requis
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Définir le type de contenu
header('Content-Type: application/json');

// Vérifier le mode debug
$debug_mode = isset($_POST['debug_mode']) && $_POST['debug_mode'] == '1';

// Variables pour journaliser les étapes
$debug_info = [];
$debug_info[] = "Démarrage du traitement - " . date('Y-m-d H:i:s');
$debug_info[] = "Session utilisateur: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Non définie');
$debug_info[] = "Rôle utilisateur: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'Non défini');

// Fonction pour journaliser les informations de débogage
function debug_log($message, $data = null) {
    global $debug_mode;
    if ($debug_mode) {
        error_log($message . ($data !== null ? ': ' . json_encode($data) : ''));
    }
}

try {
    // Log des données reçues pour le debug
    $debug_info[] = "POST reçu: " . json_encode($_POST);
    $debug_info[] = "FILES reçu: " . json_encode($_FILES);
    
    // Valider les données requises avec une vérification assouplie
    $required_fields = [];
    
    if (!isset($_POST['client_id']) || empty($_POST['client_id'])) {
        $required_fields[] = 'client_id';
    }
    
    if (!isset($_POST['type_appareil']) || empty($_POST['type_appareil'])) {
        $required_fields[] = 'type_appareil';
    }
    
    if (!isset($_POST['signature']) || empty($_POST['signature'])) {
        $required_fields[] = 'signature';
    }
    
    if (count($required_fields) > 0) {
        $debug_info[] = "Champs requis manquants: " . implode(', ', $required_fields);
        throw new Exception('Les champs suivants sont obligatoires: ' . implode(', ', $required_fields));
    }
    
    // Récupérer et nettoyer les données
    $client_id = (int)$_POST['client_id'];
    $type_appareil = cleanInput($_POST['type_appareil']);
    $modele = isset($_POST['modele']) ? cleanInput($_POST['modele']) : '';
    $sin = isset($_POST['sin']) ? cleanInput($_POST['sin']) : '';
    $prix = isset($_POST['prix']) && is_numeric($_POST['prix']) ? (float)$_POST['prix'] : 0;
    $fonctionnel = isset($_POST['fonctionnel']) ? (int)$_POST['fonctionnel'] : 1;
    
    // Vérifier la signature
    $signature_data = $_POST['signature'];
    if (empty($signature_data) || strpos($signature_data, 'data:image') !== 0) {
        $debug_info[] = "Signature invalide: " . substr($signature_data, 0, 30) . "...";
        throw new Exception('La signature est invalide.');
    }
    
    $debug_info[] = "Données validées avec succès";
    
    // Créer le dossier de destination si nécessaire
    $upload_dir = __DIR__ . '/../assets/images/rachat/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $debug_info[] = "Échec de création du dossier: " . $upload_dir;
            throw new Exception('Impossible de créer le dossier de destination.');
        }
    }
    
    $debug_info[] = "Dossier de destination vérifié";
    
    // Générer des noms de fichiers uniques
    $photo_identite_name = null;
    $photo_appareil_name = null;
    $signature_name = 'signature_' . time() . '_' . uniqid() . '.png';
    
    // Traiter la signature (convertir de base64 à fichier)
    $signature_data = str_replace('data:image/png;base64,', '', $signature_data);
    $signature_data = str_replace(' ', '+', $signature_data);
    $signature_binary = base64_decode($signature_data);
    
    if ($signature_binary === false) {
        $debug_info[] = "Décodage base64 de la signature échoué";
        throw new Exception('Impossible de décoder la signature.');
    }
    
    $signature_path = $upload_dir . $signature_name;
    if (file_put_contents($signature_path, $signature_binary) === false) {
        $debug_info[] = "Échec d'écriture du fichier signature: " . $signature_path;
        throw new Exception('Impossible d\'enregistrer la signature.');
    }
    
    $debug_info[] = "Signature enregistrée: " . $signature_name;
    
    // Traiter la photo d'identité
    if (isset($_FILES['photo_identite']) && $_FILES['photo_identite']['error'] === UPLOAD_ERR_OK) {
        $photo_identite_name = 'identite_' . time() . '_' . uniqid() . '.jpg';
        $photo_identite_path = $upload_dir . $photo_identite_name;
        
        if (!move_uploaded_file($_FILES['photo_identite']['tmp_name'], $photo_identite_path)) {
            $debug_info[] = "Échec de déplacement du fichier photo identité";
            throw new Exception('Impossible d\'enregistrer la photo d\'identité.');
        }
        
        $debug_info[] = "Photo d'identité enregistrée: " . $photo_identite_name;
    } elseif (isset($_POST['photo_identite_data']) && !empty($_POST['photo_identite_data'])) {
        // Alternative: traiter l'image base64 de la webcam
        $photo_data = $_POST['photo_identite_data'];
        $photo_data = str_replace('data:image/jpeg;base64,', '', $photo_data);
        $photo_data = str_replace('data:image/png;base64,', '', $photo_data);
        $photo_data = str_replace(' ', '+', $photo_data);
        $photo_binary = base64_decode($photo_data);
        
        if ($photo_binary === false) {
            $debug_info[] = "Décodage base64 de la photo identité échoué";
            throw new Exception('Impossible de décoder la photo d\'identité.');
        }
        
        $photo_identite_name = 'identite_' . time() . '_' . uniqid() . '.jpg';
        $photo_identite_path = $upload_dir . $photo_identite_name;
        
        if (file_put_contents($photo_identite_path, $photo_binary) === false) {
            $debug_info[] = "Échec d'écriture du fichier photo identité";
            throw new Exception('Impossible d\'enregistrer la photo d\'identité.');
        }
        
        $debug_info[] = "Photo d'identité webcam enregistrée: " . $photo_identite_name;
    }
    
    // Traiter la photo de l'appareil
    if (isset($_FILES['photo_appareil']) && $_FILES['photo_appareil']['error'] === UPLOAD_ERR_OK) {
        $photo_appareil_name = 'appareil_' . time() . '_' . uniqid() . '.jpg';
        $photo_appareil_path = $upload_dir . $photo_appareil_name;
        
        if (!move_uploaded_file($_FILES['photo_appareil']['tmp_name'], $photo_appareil_path)) {
            $debug_info[] = "Échec de déplacement du fichier photo appareil";
            throw new Exception('Impossible d\'enregistrer la photo de l\'appareil.');
        }
        
        $debug_info[] = "Photo d'appareil enregistrée: " . $photo_appareil_name;
    } elseif (isset($_POST['photo_appareil_data']) && !empty($_POST['photo_appareil_data'])) {
        // Alternative: traiter l'image base64
        $photo_data = $_POST['photo_appareil_data'];
        $photo_data = str_replace('data:image/jpeg;base64,', '', $photo_data);
        $photo_data = str_replace('data:image/png;base64,', '', $photo_data);
        $photo_data = str_replace(' ', '+', $photo_data);
        $photo_binary = base64_decode($photo_data);
        
        if ($photo_binary === false) {
            $debug_info[] = "Décodage base64 de la photo appareil échoué";
            throw new Exception('Impossible de décoder la photo de l\'appareil.');
        }
        
        $photo_appareil_name = 'appareil_' . time() . '_' . uniqid() . '.jpg';
        $photo_appareil_path = $upload_dir . $photo_appareil_name;
        
        if (file_put_contents($photo_appareil_path, $photo_binary) === false) {
            $debug_info[] = "Échec d'écriture du fichier photo appareil";
            throw new Exception('Impossible d\'enregistrer la photo de l\'appareil.');
        }
        
        $debug_info[] = "Photo d'appareil webcam enregistrée: " . $photo_appareil_name;
    }
    
    // Traiter la photo du client capturée par la webcam
    $client_photo_name = null;
    if (isset($_POST['client_photo_data']) && !empty($_POST['client_photo_data'])) {
        $photo_data = $_POST['client_photo_data'];
        $photo_data = str_replace('data:image/jpeg;base64,', '', $photo_data);
        $photo_data = str_replace('data:image/png;base64,', '', $photo_data);
        $photo_data = str_replace(' ', '+', $photo_data);
        $photo_binary = base64_decode($photo_data);
        
        if ($photo_binary === false) {
            $debug_info[] = "Décodage base64 de la photo client échoué";
            throw new Exception('Impossible de décoder la photo du client.');
        }
        
        $client_photo_name = 'client_' . time() . '_' . uniqid() . '.jpg';
        $client_photo_path = $upload_dir . $client_photo_name;
        
        if (file_put_contents($client_photo_path, $photo_binary) === false) {
            $debug_info[] = "Échec d'écriture du fichier photo client";
            throw new Exception('Impossible d\'enregistrer la photo du client.');
        }
        
        $debug_info[] = "Photo du client enregistrée: " . $client_photo_name;
    }
    
    // Insérer l'enregistrement dans la base de données avec colonne pour la photo client
    // Vérifier d'abord si la colonne client_photo existe dans la table
    try {
        $check_column = $pdo->query("SHOW COLUMNS FROM rachat_appareils LIKE 'client_photo'");
        $column_exists = ($check_column && $check_column->rowCount() > 0);
    } catch (Exception $e) {
        $column_exists = false;
        $debug_info[] = "Erreur lors de la vérification de la colonne client_photo: " . $e->getMessage();
    }
    
    // Préparer la requête SQL en fonction de l'existence de la colonne
    if ($column_exists) {
        $stmt = $pdo->prepare("
            INSERT INTO rachat_appareils (
                client_id, type_appareil, modele, sin, fonctionnel, prix,
                photo_identite, photo_appareil, signature, date_rachat, client_photo
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?
            )
        ");
        
        $debug_info[] = "Requête SQL préparée avec colonne client_photo";
        
        $result = $stmt->execute([
            $client_id, 
            $type_appareil, 
            $modele, 
            $sin, 
            $fonctionnel, 
            $prix,
            $photo_identite_name, 
            $photo_appareil_name, 
            $signature_name,
            $client_photo_name
        ]);
    } else {
        // Si la colonne n'existe pas, on utilise la requête sans cette colonne
        $stmt = $pdo->prepare("
            INSERT INTO rachat_appareils (
                client_id, type_appareil, modele, sin, fonctionnel, prix,
                photo_identite, photo_appareil, signature, date_rachat
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");
        
        $debug_info[] = "Requête SQL préparée sans colonne client_photo";
        
        $result = $stmt->execute([
            $client_id, 
            $type_appareil, 
            $modele, 
            $sin, 
            $fonctionnel, 
            $prix,
            $photo_identite_name, 
            $photo_appareil_name, 
            $signature_name
        ]);
        
        // Sauvegarder quand même la photo client même si elle n'est pas liée en DB
        if ($client_photo_name) {
            $debug_info[] = "Photo client sauvegardée mais non liée en base de données (colonne manquante)";
        }
    }
    
    if (!$result) {
        $debug_info[] = "Échec d'exécution de la requête SQL: " . implode(', ', $stmt->errorInfo());
        throw new Exception('Erreur lors de l\'enregistrement du rachat dans la base de données.');
    }
    
    $rachat_id = $pdo->lastInsertId();
    $debug_info[] = "Rachat enregistré avec succès avec l'ID: " . $rachat_id;
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'rachat_id' => $rachat_id,
        'message' => 'Rachat enregistré avec succès.',
        'debug' => $debug_mode ? $debug_info : null
    ]);
    
} catch (Exception $e) {
    // Log l'erreur complète
    error_log("Erreur de rachat: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
    
    // Envoyer une réponse d'erreur
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => $debug_mode ? $debug_info : null
    ]);
}
?> 