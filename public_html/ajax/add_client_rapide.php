<?php
/**
 * Fichier pour l'ajout rapide d'un client depuis le modal de commande
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Démarrer la session
session_start();

// Log de débogage
error_log("=== DÉBUT ADD_CLIENT_RAPIDE.PHP ===");
error_log("POST: " . print_r($_POST, true));
error_log("SESSION: " . print_r($_SESSION, true));

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action'
    ]);
    exit;
}

// Inclure la configuration de la base de données
require_once '../config/database.php';

// Vérifier que le nom est fourni
if (!isset($_POST['nom']) || empty($_POST['nom'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Le nom du client est obligatoire'
    ]);
    exit;
}

// Récupérer les données du formulaire
$nom = trim($_POST['nom']);
$prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
$telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

try {
    // Utiliser getShopDBConnection() pour s'assurer d'utiliser la base du magasin
    $shop_pdo = getShopDBConnection();
    
    // Vérifier la connexion à la base de données du magasin
    $db_stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("BASE UTILISÉE POUR L'AJOUT RAPIDE DU CLIENT: " . ($db_info['db_name'] ?? 'Inconnue'));

    // Vérifier si un client avec le même nom, prénom et téléphone existe déjà
    $check_sql = "SELECT id FROM clients WHERE nom = :nom AND prenom = :prenom";
    
    // Ajouter la vérification par téléphone uniquement si le téléphone est fourni
    if (!empty($telephone)) {
        $check_sql .= " AND telephone = :telephone";
    }
    
    $check_stmt = $shop_pdo->prepare($check_sql);
    $check_stmt->bindParam(':nom', $nom);
    $check_stmt->bindParam(':prenom', $prenom);
    
    if (!empty($telephone)) {
        $check_stmt->bindParam(':telephone', $telephone);
    }
    
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        // Client existe déjà, renvoyer son ID
        $client = $check_stmt->fetch(PDO::FETCH_ASSOC);
        error_log("CLIENT EXISTANT TROUVÉ: " . $client['id']);
        
        echo json_encode([
            'success' => true,
            'client_id' => $client['id'],
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,
            'message' => 'Client existant sélectionné',
            'database' => $db_info['db_name']
        ]);
        exit;
    }
    
    // Insérer le nouveau client
    $insert_sql = "INSERT INTO clients (nom, prenom, telephone, email, date_creation) VALUES (:nom, :prenom, :telephone, :email, NOW())";
    $insert_stmt = $shop_pdo->prepare($insert_sql);
    
    $insert_stmt->bindParam(':nom', $nom);
    $insert_stmt->bindParam(':prenom', $prenom);
    $insert_stmt->bindParam(':telephone', $telephone);
    $insert_stmt->bindParam(':email', $email);
    
    $insert_stmt->execute();
    
    // Récupérer l'ID du client nouvellement créé
    $client_id = $shop_pdo->lastInsertId();
    error_log("NOUVEAU CLIENT CRÉÉ: " . $client_id);
    
    // Log de l'action
    $user_id = $_SESSION['user_id'];
    $log_sql = "INSERT INTO logs (user_id, action, target_type, target_id, details, date_creation) 
                VALUES (:user_id, 'create', 'client', :client_id, :details, NOW())";
    $log_stmt = $shop_pdo->prepare($log_sql);
    
    $details = json_encode([
        'nom' => $nom,
        'prenom' => $prenom,
        'telephone' => $telephone,
        'email' => $email
    ]);
    
    $log_stmt->bindParam(':user_id', $user_id);
    $log_stmt->bindParam(':client_id', $client_id);
    $log_stmt->bindParam(':details', $details);
    
    $log_stmt->execute();
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'client_id' => $client_id,
        'nom' => $nom,
        'prenom' => $prenom,
        'telephone' => $telephone,
        'message' => 'Client créé avec succès',
        'database' => $db_info['db_name']
    ]);
    
} catch (PDOException $e) {
    // Log de l'erreur
    error_log("Erreur lors de la création du client: " . $e->getMessage());
    
    // Réponse d'erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création du client: ' . $e->getMessage()
    ]);
}

error_log("=== FIN ADD_CLIENT_RAPIDE.PHP ===");
?> 