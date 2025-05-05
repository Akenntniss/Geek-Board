<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Activer le débogage
error_log("=== DÉBUT ADD_CLIENT.PHP ===");
error_log("POST: " . print_r($_POST, true));
error_log("SESSION: " . print_r($_SESSION, true));

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et nettoyer les données
$nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$email = trim($_POST['email'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');

// Validation des données
if (empty($nom) || empty($prenom)) {
    echo json_encode(['success' => false, 'message' => 'Nom et prénom requis']);
    exit;
}

if (empty($telephone)) {
    echo json_encode(['success' => false, 'message' => 'Numéro de téléphone requis']);
    exit;
}

try {
    // Utiliser getShopDBConnection() pour s'assurer d'utiliser la base du magasin
    $shop_pdo = getShopDBConnection();
    
    // Vérifier la connexion à la base de données du magasin
    $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("BASE UTILISÉE POUR L'AJOUT DU CLIENT: " . ($db_info['db_name'] ?? 'Inconnue'));
    
    // Préparer la requête d'insertion
    $sql = "INSERT INTO clients (nom, prenom, telephone, email, adresse, date_creation) 
            VALUES (:nom, :prenom, :telephone, :email, :adresse, NOW())";
    
    $stmt = $shop_pdo->prepare($sql);
    
    // Exécuter la requête
    $success = $stmt->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':telephone' => $telephone,
        ':email' => $email,
        ':adresse' => $adresse
    ]);
    
    if ($success) {
        // Récupérer l'ID du client créé
        $client_id = $shop_pdo->lastInsertId();
        error_log("CLIENT CRÉÉ AVEC SUCCÈS, ID: " . $client_id);
        
        // Retourner les données du client créé et des informations sur la base de données utilisée
        echo json_encode([
            'success' => true,
            'message' => 'Client créé avec succès',
            'client' => [
                'id' => $client_id,
                'nom' => $nom,
                'prenom' => $prenom,
                'telephone' => $telephone,
                'email' => $email,
                'adresse' => $adresse
            ],
            'database_info' => [
                'database' => $db_info['db_name'],
                'shop_id' => $_SESSION['shop_id'] ?? 'Non disponible'
            ]
        ]);
    } else {
        throw new Exception('Erreur lors de la création du client');
    }
} catch (PDOException $e) {
    error_log('Erreur SQL: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la création du client: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Erreur: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
error_log("=== FIN ADD_CLIENT.PHP ===");
?> 