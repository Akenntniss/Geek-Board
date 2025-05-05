<?php
/**
 * Script d'ajout rapide de client avec connexion directe à la base de données du magasin
 * Version simplifiée qui contourne les problèmes de getShopDBConnection()
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le type de contenu comme JSON
header('Content-Type: application/json');

// Démarrer la session
session_start();

// Log de débogage
error_log("=== DÉBUT DIRECT_ADD_CLIENT_RAPIDE.PHP ===");
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

// Récupérer l'ID du magasin
$shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : (isset($_SESSION['shop_id']) ? (int)$_SESSION['shop_id'] : null);

if (!$shop_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Aucun magasin sélectionné'
    ]);
    error_log('ERREUR: Aucun shop_id trouvé ni en POST ni en SESSION');
    exit;
}

try {
    // Se connecter à la base principale pour récupérer les infos du magasin
    require_once '../config/database.php'; // Pour utiliser getMainDBConnection() uniquement
    
    $main_pdo = getMainDBConnection();
    error_log('Connexion à la base principale réussie');
    
    // Récupérer les informations de connexion pour le magasin
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        echo json_encode([
            'success' => false,
            'message' => 'Magasin non trouvé (ID: ' . $shop_id . ')'
        ]);
        error_log('ERREUR: Magasin non trouvé avec ID ' . $shop_id);
        exit;
    }
    
    error_log('Informations du magasin récupérées: ' . $shop['name'] . ' (DB: ' . $shop['db_name'] . ')');
    
    // Vérifier que toutes les informations de connexion sont présentes
    if (empty($shop['db_host']) || empty($shop['db_user']) || empty($shop['db_name'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Configuration de la base de données du magasin incomplète'
        ]);
        error_log('ERREUR: Configuration DB incomplète pour le magasin ' . $shop_id);
        exit;
    }
    
    // Établir une connexion directe à la base de données du magasin
    $dsn = 'mysql:host=' . $shop['db_host'] . ';dbname=' . $shop['db_name'];
    if (!empty($shop['db_port'])) {
        $dsn .= ';port=' . $shop['db_port'];
    }
    
    error_log('Tentative de connexion directe à la base du magasin: ' . $dsn);
    
    $shop_pdo = new PDO(
        $dsn,
        $shop['db_user'],
        $shop['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
    
    error_log('Connexion directe à la base du magasin réussie');
    
    // Vérifier si un client avec le même nom, prénom et téléphone existe déjà
    $check_sql = "SELECT id FROM clients WHERE nom = :nom AND prenom = :prenom";
    
    if (!empty($telephone)) {
        $check_sql .= " AND telephone = :telephone";
    }
    
    $check_stmt = $shop_pdo->prepare($check_sql);
    $check_params = [
        ':nom' => $nom,
        ':prenom' => $prenom
    ];
    
    if (!empty($telephone)) {
        $check_params[':telephone'] = $telephone;
    }
    
    $check_stmt->execute($check_params);
    
    if ($check_stmt->rowCount() > 0) {
        // Client existe déjà, renvoyer son ID
        $client = $check_stmt->fetch();
        error_log("CLIENT EXISTANT TROUVÉ: " . $client['id']);
        
        echo json_encode([
            'success' => true,
            'client_id' => $client['id'],
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,
            'message' => 'Client existant sélectionné',
            'database' => $shop['db_name'],
            'connection_method' => 'direct'
        ]);
        exit;
    }
    
    // Insérer le nouveau client
    $insert_sql = "INSERT INTO clients (nom, prenom, telephone, email, date_creation) VALUES (:nom, :prenom, :telephone, :email, NOW())";
    $insert_stmt = $shop_pdo->prepare($insert_sql);
    
    $insert_stmt->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':telephone' => $telephone,
        ':email' => $email
    ]);
    
    // Récupérer l'ID du client nouvellement créé
    $client_id = $shop_pdo->lastInsertId();
    error_log("NOUVEAU CLIENT CRÉÉ: " . $client_id);
    
    // Log de l'action
    try {
        $log_sql = "INSERT INTO logs (user_id, action, target_type, target_id, details, date_creation) 
                   VALUES (:user_id, 'create', 'client', :client_id, :details, NOW())";
        
        $details = json_encode([
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,
            'email' => $email,
            'method' => 'direct_rapide'
        ]);
        
        $log_stmt = $shop_pdo->prepare($log_sql);
        $log_stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':client_id' => $client_id,
            ':details' => $details
        ]);
    } catch (Exception $e) {
        // Ne pas échouer si la journalisation échoue
        error_log('Avertissement: Impossible de journaliser l\'action: ' . $e->getMessage());
    }
    
    // Réponse de succès
    echo json_encode([
        'success' => true,
        'client_id' => $client_id,
        'nom' => $nom,
        'prenom' => $prenom,
        'telephone' => $telephone,
        'message' => 'Client créé avec succès',
        'database' => $shop['db_name'],
        'connection_method' => 'direct'
    ]);
    
} catch (PDOException $e) {
    // Log de l'erreur
    error_log("Erreur lors de la création du client: " . $e->getMessage());
    
    // Réponse d'erreur
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la création du client: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}

error_log("=== FIN DIRECT_ADD_CLIENT_RAPIDE.PHP ===");
?> 