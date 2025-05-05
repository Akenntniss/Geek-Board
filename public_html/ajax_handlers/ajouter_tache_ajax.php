<?php
/**
 * Gestionnaire AJAX pour l'ajout d'une tâche depuis le modal
 */

// Initialisation de la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour effectuer cette action.'
    ]);
    exit;
}

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification que la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée.'
    ]);
    exit;
}

// Récupération et nettoyage des données
$titre = cleanInput($_POST['titre'] ?? '');
$description = cleanInput($_POST['description'] ?? '');
$priorite = cleanInput($_POST['priorite'] ?? '');
$statut = cleanInput($_POST['statut'] ?? '');
$date_limite = cleanInput($_POST['date_limite'] ?? '');

// Vérification et conversion de employe_id
$employe_id_raw = $_POST['employe_id'] ?? '';
$employe_id = !empty($employe_id_raw) ? (int)$employe_id_raw : null;

// Validation des données
$errors = [];

if (empty($titre)) {
    $errors[] = "Le titre est obligatoire.";
}

if (empty($description)) {
    $errors[] = "La description est obligatoire.";
}

if (empty($priorite)) {
    $errors[] = "La priorité est obligatoire.";
}

if (empty($statut)) {
    $errors[] = "Le statut est obligatoire.";
}

// Si des erreurs sont présentes, renvoyer une réponse avec les erreurs
if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Des erreurs ont été détectées.',
        'errors' => $errors
    ]);
    exit;
}

// Si pas d'erreurs, insertion de la tâche
try {
    // Vérification de l'ID du magasin en session
    if (!isset($_SESSION['shop_id']) || empty($_SESSION['shop_id'])) {
        // Journaliser l'erreur
        error_log("Erreur: Aucun magasin associé à l'utilisateur " . $_SESSION['user_id']);
        throw new Exception("Aucun magasin associé à votre compte. Veuillez contacter l'administrateur.");
    }
    
    // Récupérer l'ID du magasin
    $shop_id = $_SESSION['shop_id'];
    error_log("Utilisateur ID: " . $_SESSION['user_id'] . ", Magasin ID: " . $shop_id);
    
    // Obtenir les informations du magasin depuis la base principale
    $main_pdo = getMainDBConnection();
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch();
    
    if (!$shop) {
        error_log("ERREUR: Impossible de trouver les informations du magasin ID: " . $shop_id);
        throw new Exception("Impossible de trouver les informations du magasin. Veuillez contacter l'administrateur.");
    }
    
    // Vérifier que les informations de connexion sont complètes
    if (empty($shop['db_host']) || empty($shop['db_name']) || empty($shop['db_user'])) {
        error_log("ERREUR: Configuration de base de données incomplète pour le magasin: " . $shop['name']);
        throw new Exception("Configuration de base de données incomplète pour le magasin. Veuillez contacter l'administrateur.");
    }
    
    error_log("Connexion directe à la base: " . $shop['db_name'] . " sur " . $shop['db_host']);
    
    // Créer directement une nouvelle connexion à la base de données du magasin
    $dsn = "mysql:host=" . $shop['db_host'] . ";port=" . 
           ($shop['db_port'] ?? '3306') . ";dbname=" . 
           $shop['db_name'] . ";charset=utf8mb4";
    
    $shop_pdo = new PDO(
        $dsn,
        $shop['db_user'],
        $shop['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false
        ]
    );
    
    // Vérifier que la connexion utilise bien la bonne base de données
    $stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_db = $result['current_db'];
    
    error_log("Base de données utilisée pour l'insertion de la tâche: " . $current_db);
    
    if ($current_db !== $shop['db_name']) {
        error_log("ERREUR: Mauvaise base de données utilisée. Attendue: " . $shop['db_name'] . ", Utilisée: " . $current_db);
        throw new Exception("Erreur lors de la connexion à la base de données du magasin. Veuillez contacter l'administrateur.");
    }
    
    // Préparation des paramètres pour la requête
    $params = [
        $titre, 
        $description, 
        $priorite, 
        $statut, 
        !empty($date_limite) ? $date_limite : null, 
        $employe_id, 
        $_SESSION['user_id']
    ];
    
    // Vérifier si la table taches existe dans la base de données du magasin
    $tableExists = false;
    $stmt = $shop_pdo->query("SHOW TABLES LIKE 'taches'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        error_log("ERREUR: La table 'taches' n'existe pas dans la base de données du magasin: " . $shop['db_name']);
        
        // Tenter de créer la table si elle n'existe pas
        $shop_pdo->exec("
            CREATE TABLE IF NOT EXISTS `taches` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `titre` varchar(255) NOT NULL,
              `description` text NOT NULL,
              `priorite` varchar(50) NOT NULL,
              `statut` varchar(50) NOT NULL,
              `date_limite` date DEFAULT NULL,
              `employe_id` int(11) DEFAULT NULL,
              `created_by` int(11) NOT NULL,
              `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        
        error_log("Table 'taches' créée dans la base de données du magasin");
    }
    
    $stmt = $shop_pdo->prepare("
        INSERT INTO taches (titre, description, priorite, statut, date_limite, employe_id, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute($params);
    
    // Récupérer l'ID de la tâche créée
    $task_id = $shop_pdo->lastInsertId();
    
    // Journaliser le succès
    error_log("Tâche ID: " . $task_id . " créée avec succès dans la base: " . $current_db);
    
    // Renvoyer une réponse de succès
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Tâche ajoutée avec succès dans la base de données ' . $shop['db_name'] . '!',
        'task_id' => $task_id,
        'debug_info' => [
            'user_id' => $_SESSION['user_id'],
            'shop_id' => $shop_id,
            'database' => $current_db
        ]
    ]);
    
} catch (Exception $e) {
    // Journaliser l'erreur
    error_log("Erreur lors de l'ajout de la tâche: " . $e->getMessage());
    
    // Renvoyer une réponse d'erreur
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout de la tâche: ' . $e->getMessage()
    ]);
} 