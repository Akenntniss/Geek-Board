<?php
/**
 * Script d'ajout de client avec connexion directe à la base de données du magasin
 * Contourne les problèmes de getShopDBConnection() en établissant une connexion explicite
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session (nécessaire pour récupérer l'ID du magasin)
session_start();

// Journaliser les données reçues pour le débogage
error_log('=== DÉBUT DIRECT_ADD_CLIENT.PHP ===');
error_log('POST: ' . print_r($_POST, true));
error_log('SESSION: ' . print_r($_SESSION, true));

// Répondre en JSON
header('Content-Type: application/json');

// 1. Vérifier si les données nécessaires sont présentes
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et nettoyer les données du client
$nom = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validation des données obligatoires
if (empty($nom) || empty($prenom) || empty($telephone)) {
    echo json_encode(['success' => false, 'message' => 'Nom, prénom et téléphone sont requis']);
    exit;
}

// 2. Récupérer l'ID du magasin (soit de POST, soit de SESSION)
$shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : (isset($_SESSION['shop_id']) ? (int)$_SESSION['shop_id'] : null);

if (!$shop_id) {
    echo json_encode(['success' => false, 'message' => 'Aucun magasin sélectionné']);
    error_log('ERREUR: Aucun shop_id trouvé ni en POST ni en SESSION');
    exit;
}

try {
    // 3. Se connecter à la base principale pour récupérer les informations du magasin
    require_once '../config/database.php'; // Pour utiliser getMainDBConnection() uniquement
    
    $main_pdo = getMainDBConnection();
    error_log('Connexion à la base principale réussie');
    
    // Vérifier la connexion actuelle
    try {
        $current_db_stmt = $main_pdo->query("SELECT DATABASE() as current_db");
        $current_db = $current_db_stmt->fetch(PDO::FETCH_ASSOC);
        error_log('Base de données principale: ' . ($current_db['current_db'] ?? 'Inconnue'));
    } catch (Exception $e) {
        error_log('Erreur lors de la vérification de la base de données principale: ' . $e->getMessage());
    }
    
    // 4. Récupérer les informations de connexion pour le magasin
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$shop) {
        echo json_encode(['success' => false, 'message' => 'Magasin non trouvé (ID: ' . $shop_id . ')']);
        error_log('ERREUR: Magasin non trouvé avec ID ' . $shop_id);
        exit;
    }
    
    error_log('Informations du magasin récupérées: ' . $shop['name'] . ' (DB: ' . $shop['db_name'] . ')');
    
    // 5. Vérifier que toutes les informations de connexion sont présentes
    if (empty($shop['db_host']) || empty($shop['db_user']) || empty($shop['db_name'])) {
        echo json_encode(['success' => false, 'message' => 'Configuration de la base de données du magasin incomplète']);
        error_log('ERREUR: Configuration DB incomplète pour le magasin ' . $shop_id);
        exit;
    }
    
    // 6. Établir une connexion directe à la base de données du magasin
    $dsn = 'mysql:host=' . $shop['db_host'] . ';dbname=' . $shop['db_name'];
    if (!empty($shop['db_port'])) {
        $dsn .= ';port=' . $shop['db_port'];
    }
    
    error_log('Tentative de connexion directe à la base du magasin: ' . $dsn);
    
    // Forcer une nouvelle connexion en désactivant les connexions persistantes
    $shop_pdo = new PDO(
        $dsn,
        $shop['db_user'],
        $shop['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_PERSISTENT => false // S'assurer que nous n'utilisons pas une connexion persistante
        ]
    );
    
    // Vérifier que nous sommes bien connectés à la bonne base de données
    try {
        $check_db_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
        $connected_db = $check_db_stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log('Connexion établie à la base: ' . ($connected_db['current_db'] ?? 'Inconnue'));
        
        // Vérifier si nous sommes connectés à la bonne base
        if ($connected_db['current_db'] !== $shop['db_name']) {
            error_log('ALERTE: La base connectée (' . $connected_db['current_db'] . ') ne correspond pas à la base du magasin (' . $shop['db_name'] . ')');
            
            // Ajouter une instruction USE pour changer explicitement de base
            $shop_pdo->exec("USE " . $shop['db_name']);
            error_log('Tentative de changement explicite vers la base: ' . $shop['db_name']);
            
            // Vérifier à nouveau
            $check_again = $shop_pdo->query("SELECT DATABASE() as current_db");
            $after_use = $check_again->fetch(PDO::FETCH_ASSOC);
            error_log('Après USE: Base active = ' . ($after_use['current_db'] ?? 'Inconnue'));
        }
    } catch (Exception $e) {
        error_log('Erreur lors de la vérification de la base connectée: ' . $e->getMessage());
    }
    
    error_log('Connexion directe à la base du magasin réussie');
    
    // 7. Vérifier que la table clients existe dans la base du magasin
    $stmt = $shop_pdo->query("SHOW TABLES LIKE 'clients'");
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'La table clients n\'existe pas dans la base du magasin']);
        error_log('ERREUR: Table clients inexistante dans la base ' . $shop['db_name']);
        exit;
    }
    
    // 8. Insérer le client dans la base du magasin
    $sql = "INSERT INTO clients (nom, prenom, telephone, email, date_creation) 
            VALUES (:nom, :prenom, :telephone, :email, NOW())";
    
    $stmt = $shop_pdo->prepare($sql);
    $success = $stmt->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':telephone' => $telephone,
        ':email' => $email
    ]);
    
    if (!$success) {
        throw new Exception('Échec de l\'insertion du client');
    }
    
    // 9. Récupérer l'ID du client créé
    $client_id = $shop_pdo->lastInsertId();
    error_log('Client inséré avec succès dans la base du magasin. ID: ' . $client_id);
    
    // 10. Vérifier que l'insertion a réussi
    $stmt = $shop_pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$client_id]);
    $inserted_client = $stmt->fetch();
    
    if (!$inserted_client) {
        throw new Exception('Client créé mais introuvable après insertion');
    }
    
    // 11. Journaliser l'action (facultatif)
    if (isset($_SESSION['user_id'])) {
        try {
            $log_sql = "INSERT INTO logs (user_id, action, target_type, target_id, details, date_creation) 
                       VALUES (:user_id, 'create', 'client', :client_id, :details, NOW())";
            
            $details = json_encode([
                'nom' => $nom,
                'prenom' => $prenom,
                'telephone' => $telephone,
                'email' => $email,
                'method' => 'direct_connection'
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
    }
    
    // 12. Répondre avec succès
    echo json_encode([
        'success' => true,
        'message' => 'Client créé avec succès',
        'client_id' => $client_id,
        'client' => [
            'id' => $client_id,
            'nom' => $nom,
            'prenom' => $prenom,
            'telephone' => $telephone,
            'email' => $email
        ],
        'database_info' => [
            'database' => $shop['db_name'],
            'connected_to' => $connected_db['current_db'] ?? 'Inconnue',
            'shop_id' => $shop_id,
            'shop_name' => $shop['name'],
            'connection_method' => 'direct'
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Erreur PDO: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Exception: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage()
    ]);
}

error_log('=== FIN DIRECT_ADD_CLIENT.PHP ===');
?> 