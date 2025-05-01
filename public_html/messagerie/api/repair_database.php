<?php
// Script de réparation et initialisation de la base de données pour la messagerie
header('Content-Type: application/json');

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérification des droits d'accès (seulement en mode débogage)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès restreint aux administrateurs']);
    exit;
}

// Journal de débogage
$debug_log = [];
function add_log($message, $data = null, $type = 'info') {
    global $debug_log;
    $debug_log[] = [
        'time' => date('H:i:s'),
        'type' => $type,
        'message' => $message,
        'data' => $data
    ];
}

add_log("Démarrage du script de réparation de la base de données");

// Connexion à la base de données
try {
    require_once('../../database.php');
    add_log("Connexion à la base de données établie");
    $pdo = $GLOBALS['pdo'];
} catch (Exception $e) {
    add_log("Erreur de connexion à la base de données", $e->getMessage(), 'error');
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données: ' . $e->getMessage(), 'debug' => $debug_log]);
    exit;
}

// Vérifier si les tables nécessaires existent
$required_tables = ['conversations', 'conversation_participants', 'messages'];
$missing_tables = [];

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missing_tables[] = $table;
        }
    } catch (PDOException $e) {
        add_log("Erreur lors de la vérification de la table $table", $e->getMessage(), 'error');
    }
}

add_log("Vérification des tables", ['missing' => $missing_tables]);

// Créer les tables manquantes si nécessaire
$table_creation_scripts = [
    'conversations' => "
        CREATE TABLE IF NOT EXISTS conversations (
            id INT(11) NOT NULL AUTO_INCREMENT,
            titre VARCHAR(255) DEFAULT NULL,
            type ENUM('direct', 'groupe', 'annonce') NOT NULL,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_by INT(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY fk_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ",
    'conversation_participants' => "
        CREATE TABLE IF NOT EXISTS conversation_participants (
            conversation_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            role ENUM('admin', 'membre', 'lecteur') DEFAULT 'membre',
            date_derniere_lecture DATETIME DEFAULT NULL,
            PRIMARY KEY (conversation_id, user_id),
            KEY fk_participant_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ",
    'messages' => "
        CREATE TABLE IF NOT EXISTS messages (
            id INT(11) NOT NULL AUTO_INCREMENT,
            conversation_id INT(11) DEFAULT NULL,
            sender_id INT(11) DEFAULT NULL,
            contenu TEXT DEFAULT NULL,
            type ENUM('texte', 'fichier', 'annonce') DEFAULT 'texte',
            fichier_url VARCHAR(255) DEFAULT NULL,
            fichier_nom VARCHAR(255) DEFAULT NULL,
            fichier_type VARCHAR(50) DEFAULT NULL,
            fichier_taille INT(11) DEFAULT NULL,
            date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
            est_annonce TINYINT(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY fk_message_conversation (conversation_id),
            KEY fk_message_sender (sender_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    "
];

$tables_created = [];
$tables_verified = [];

// Créer ou vérifier chaque table
foreach ($required_tables as $table) {
    try {
        // Vérifier d'abord si la table existe
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            // Créer la table si elle n'existe pas
            add_log("Création de la table $table", null, 'warning');
            $pdo->exec($table_creation_scripts[$table]);
            $tables_created[] = $table;
        } else {
            add_log("Table $table existe déjà");
            
            // Vérifier la structure de la table
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tables_verified[] = [
                'table' => $table,
                'columns' => count($columns),
                'structure' => $columns
            ];
        }
    } catch (PDOException $e) {
        add_log("Erreur lors de la création/vérification de la table $table", $e->getMessage(), 'error');
    }
}

// Vérifier les données existantes
$data_status = [];

try {
    // Compter les enregistrements dans chaque table
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $data_status[$table] = ['count' => $count];
        
        // Échantillon de données pour vérification
        if ($count > 0) {
            $stmt = $pdo->query("SELECT * FROM $table LIMIT 1");
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);
            $data_status[$table]['sample'] = $sample;
        }
    }
    
    add_log("Vérification des données existantes", $data_status);
} catch (PDOException $e) {
    add_log("Erreur lors de la vérification des données", $e->getMessage(), 'error');
}

// Test de création d'une conversation de test si aucune n'existe
$test_created = false;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM conversations");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        add_log("Aucune conversation trouvée, création d'une conversation de test", null, 'warning');
        
        // Créer une transaction pour assurer la cohérence
        $pdo->beginTransaction();
        
        // Créer la conversation
        $stmt = $pdo->prepare("
            INSERT INTO conversations (titre, type, created_by, date_creation) 
            VALUES ('Conversation de test', 'direct', :user_id, NOW())
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $conversation_id = $pdo->lastInsertId();
        
        // Ajouter l'utilisateur actuel comme participant
        $stmt = $pdo->prepare("
            INSERT INTO conversation_participants (conversation_id, user_id, role) 
            VALUES (:conversation_id, :user_id, 'admin')
        ");
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        // Ajouter un message de test
        $stmt = $pdo->prepare("
            INSERT INTO messages (conversation_id, sender_id, contenu, date_envoi) 
            VALUES (:conversation_id, :user_id, 'Message de test automatique', NOW())
        ");
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        $pdo->commit();
        
        add_log("Conversation de test créée avec succès", ['conversation_id' => $conversation_id]);
        $test_created = true;
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    add_log("Erreur lors de la création de la conversation de test", $e->getMessage(), 'error');
}

// Préparer la réponse finale
$response = [
    'success' => true,
    'database_check' => [
        'tables_checked' => $required_tables,
        'tables_created' => $tables_created,
        'tables_verified' => $tables_verified,
        'data_status' => $data_status,
        'test_created' => $test_created
    ],
    'debug' => $debug_log
];

echo json_encode($response, JSON_PRETTY_PRINT);
?> 