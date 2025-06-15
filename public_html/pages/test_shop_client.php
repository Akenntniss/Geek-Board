<?php
/**
 * Script de test pour la connexion à la base de données du magasin
 * et l'ajout d'un client dans cette base
 */

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Fonction pour afficher un message formaté
function displayMessage($message, $type = 'info') {
    $color = 'black';
    switch ($type) {
        case 'success': $color = 'green'; break;
        case 'error': $color = 'red'; break;
        case 'warning': $color = 'orange'; break;
        default: $color = 'blue';
    }
    
    echo "<div style='padding: 10px; margin: 5px 0; border-left: 4px solid {$color}; background: #f8f9fa;'>{$message}</div>";
    
    // Journaliser également
    error_log("TEST_SHOP_CLIENT: [{$type}] {$message}");
}

// Vérifier si un shop_id est spécifié dans l'URL
if (isset($_GET['shop_id'])) {
    $_SESSION['shop_id'] = (int)$_GET['shop_id'];
    displayMessage("ID du magasin défini à partir de l'URL: " . $_SESSION['shop_id']);
}

// Vérifier si un shop_id est défini en session
if (!isset($_SESSION['shop_id'])) {
    displayMessage("Aucun ID de magasin défini en session. Veuillez spécifier un ID avec ?shop_id=X", "error");
    echo "<p>Exemple: <a href='?shop_id=1'>test_shop_client.php?shop_id=1</a></p>";
    exit;
}

// Inclure la configuration de la base de données
require_once 'config/database.php';

// Vérifier la connexion à la base principale
try {
    $main_pdo = getMainDBConnection();
    $stmt = $main_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    displayMessage("Connexion à la base principale réussie: " . $result['db_name'], "success");
    
    // Récupérer les informations du magasin
    $shop_id = $_SESSION['shop_id'];
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($shop) {
        displayMessage("Information magasin: " . $shop['name'] . " (DB: " . $shop['db_name'] . ")", "success");
        $_SESSION['shop_name'] = $shop['name'];
    } else {
        displayMessage("Aucun magasin trouvé avec l'ID {$shop_id}", "error");
        exit;
    }
} catch (Exception $e) {
    displayMessage("Erreur de connexion à la base principale: " . $e->getMessage(), "error");
    exit;
}

// Vérifier la connexion à la base du magasin
try {
    // Réinitialiser la connexion shop_pdo pour forcer une nouvelle connexion
    global $shop_pdo;
    $shop_pdo = null;
    
    // Obtenir une connexion à la base du magasin
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['db_name'] === $shop['db_name']) {
        displayMessage("Connexion à la base du magasin réussie: " . $result['db_name'], "success");
    } else {
        displayMessage("ALERTE: La base connectée (" . $result['db_name'] . ") ne correspond pas à la base du magasin (" . $shop['db_name'] . ")", "warning");
        
        // Changer explicitement de base
        try {
            $shop_pdo->exec("USE " . $shop['db_name']);
            $check = $shop_pdo->query("SELECT DATABASE() as db_name");
            $after = $check->fetch(PDO::FETCH_ASSOC);
            displayMessage("Après changement explicite de base: " . $after['db_name'], "info");
        } catch (Exception $e) {
            displayMessage("Erreur lors du changement de base: " . $e->getMessage(), "error");
        }
    }
} catch (Exception $e) {
    displayMessage("Erreur de connexion à la base du magasin: " . $e->getMessage(), "error");
    exit;
}

// Si un formulaire a été soumis, ajouter un client de test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérifier le type de requête
        if (isset($_POST['test_type'])) {
            switch ($_POST['test_type']) {
                case 'direct':
                    // Test avec direct_add_client.php via HTTP
                    displayMessage("Test avec direct_add_client.php via HTTP", "info");
                    
                    // Préparer les données du client
                    $data = [
                        'nom' => 'Test_' . date('His'),
                        'prenom' => 'Direct_' . rand(1000, 9999),
                        'telephone' => '07' . rand(10000000, 99999999),
                        'shop_id' => $_SESSION['shop_id']
                    ];
                    
                    // Créer le contexte de la requête
                    $options = [
                        'http' => [
                            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                            'method'  => 'POST',
                            'content' => http_build_query($data)
                        ]
                    ];
                    $context  = stream_context_create($options);
                    
                    // Effectuer la requête
                    $result = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . '/ajax/direct_add_client.php', false, $context);
                    
                    if ($result === FALSE) {
                        displayMessage("Erreur lors de la requête HTTP", "error");
                    } else {
                        $response = json_decode($result, true);
                        if ($response && isset($response['success'])) {
                            if ($response['success']) {
                                displayMessage("Client ajouté avec succès via HTTP. ID: " . $response['client_id'], "success");
                                displayMessage("Base utilisée: " . $response['database_info']['database'], "info");
                            } else {
                                displayMessage("Erreur lors de l'ajout du client via HTTP: " . $response['message'], "error");
                            }
                        } else {
                            displayMessage("Réponse invalide: " . $result, "error");
                        }
                    }
                    break;
                    
                case 'manual':
                    // Test avec insertion manuelle directe
                    displayMessage("Test avec insertion manuelle directe", "info");
                    
                    // Vérifier la base active avant insertion
                    $check = $shop_pdo->query("SELECT DATABASE() as db_name");
                    $before = $check->fetch(PDO::FETCH_ASSOC);
                    displayMessage("Base active avant insertion: " . $before['db_name'], "info");
                    
                    // Si la base n'est pas celle du magasin, changer explicitement
                    if ($before['db_name'] !== $shop['db_name']) {
                        $shop_pdo->exec("USE " . $shop['db_name']);
                        $check = $shop_pdo->query("SELECT DATABASE() as db_name");
                        $after = $check->fetch(PDO::FETCH_ASSOC);
                        displayMessage("Après changement explicite: " . $after['db_name'], "info");
                    }
                    
                    // Générer des données de test
                    $nom = 'Test_' . date('His');
                    $prenom = 'Manual_' . rand(1000, 9999);
                    $telephone = '06' . rand(10000000, 99999999);
                    
                    // Insérer le client
                    $stmt = $shop_pdo->prepare("INSERT INTO clients (nom, prenom, telephone, date_creation) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$nom, $prenom, $telephone]);
                    
                    $client_id = $shop_pdo->lastInsertId();
                    
                    if ($client_id) {
                        displayMessage("Client ajouté avec succès en direct. ID: " . $client_id, "success");
                        
                        // Vérifier que le client est bien dans la bonne base
                        $verify = $shop_pdo->prepare("SELECT id, nom, prenom FROM clients WHERE id = ?");
                        $verify->execute([$client_id]);
                        $client = $verify->fetch(PDO::FETCH_ASSOC);
                        
                        if ($client) {
                            displayMessage("Client vérifié dans la base: " . $client['nom'] . ' ' . $client['prenom'], "success");
                        } else {
                            displayMessage("Client non trouvé après insertion!", "error");
                        }
                    } else {
                        displayMessage("Erreur lors de l'ajout du client en direct", "error");
                    }
                    break;
                    
                default:
                    displayMessage("Type de test non reconnu", "error");
            }
        }
    } catch (Exception $e) {
        displayMessage("Erreur lors du test: " . $e->getMessage(), "error");
    }
}

// Afficher un formulaire pour tester l'ajout de client
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test d'Ajout de Client dans la Base du Magasin</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .form-section { background: #f9f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px; }
        button:hover { background: #45a049; }
        .info { background-color: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>Test d'Ajout de Client dans la Base du Magasin</h1>
    
    <div class="info">
        <p><strong>Magasin actuel:</strong> <?php echo htmlspecialchars($_SESSION['shop_name'] ?? 'Non défini'); ?> (ID: <?php echo htmlspecialchars($_SESSION['shop_id'] ?? 'Non défini'); ?>)</p>
    </div>
    
    <div class="form-section">
        <h2>Test 1: Ajout d'un client via direct_add_client.php</h2>
        <p>Ce test appelle le script AJAX comme le ferait le formulaire d'ajout client.</p>
        <form method="post">
            <input type="hidden" name="test_type" value="direct">
            <button type="submit">Tester Ajout via direct_add_client.php</button>
        </form>
    </div>
    
    <div class="form-section">
        <h2>Test 2: Ajout manuel direct</h2>
        <p>Ce test insère directement un client dans la base du magasin.</p>
        <form method="post">
            <input type="hidden" name="test_type" value="manual">
            <button type="submit">Tester Ajout Manuel</button>
        </form>
    </div>
    
    <div class="info">
        <p><a href="/debug_shop_connection.php">Afficher le diagnostic de connexion complet</a></p>
        <p><a href="/fix_connections.php">Utiliser l'outil de correction des connexions</a></p>
    </div>
</body>
</html> 