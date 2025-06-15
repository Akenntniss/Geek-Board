<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Inclure la configuration de la base de données
require_once 'config/database.php';

// Définir la fonction de formatage pour le HTML
function formatSection($title, $content) {
    return '<div class="test-section">
        <h3>' . $title . '</h3>
        <div class="content">' . $content . '</div>
    </div>';
}

// Commence à collecter le contenu HTML
$html = '<!DOCTYPE html>
<html>
<head>
    <title>Test d\'ajout de client</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        h2 { color: #0066cc; margin-top: 30px; }
        .test-section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .test-section h3 { margin-top: 0; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px; }
        .content { font-family: monospace; white-space: pre-wrap; background: #f5f5f5; padding: 10px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f0f0f0; }
        .actions { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .btn { display: inline-block; padding: 10px 15px; background: #0066cc; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .btn:hover { background: #0052a3; }
        .test-form { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-submit { padding: 10px 15px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn-submit:hover { background: #218838; }
    </style>
</head>
<body>
    <h1>Test d\'ajout de client</h1>';

// Section 1: Informations de session
$html .= '<h2>1. Informations de session</h2>';
$html .= formatSection('Valeurs en session', print_r($_SESSION, true));

// Section 2: Connexions aux bases de données
$html .= '<h2>2. Test des connexions aux bases de données</h2>';

// Test 1: Connexion principale
$html .= '<h3>2.1 Connexion principale</h3>';
try {
    $main_pdo = getMainDBConnection();
    $stmt = $main_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $html .= '<p class="success">Connexion à la base principale réussie: ' . $result['db_name'] . '</p>';
    
    // Test de la table clients dans la base principale
    try {
        $stmt = $main_pdo->query("SHOW TABLES LIKE 'clients'");
        if ($stmt->rowCount() > 0) {
            $html .= '<p>La table "clients" existe dans la base principale.</p>';
            
            // Compter les clients dans la base principale
            $stmt = $main_pdo->query("SELECT COUNT(*) as count FROM clients");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            $html .= '<p>Nombre de clients dans la base principale: <strong>' . $count['count'] . '</strong></p>';
        } else {
            $html .= '<p class="warning">La table "clients" n\'existe pas dans la base principale.</p>';
        }
    } catch (Exception $e) {
        $html .= '<p class="warning">Erreur lors de la vérification de la table clients dans la base principale: ' . $e->getMessage() . '</p>';
    }
} catch (Exception $e) {
    $html .= '<p class="error">Erreur de connexion à la base principale: ' . $e->getMessage() . '</p>';
}

// Test 2: Connexion au magasin
$html .= '<h3>2.2 Connexion au magasin avec getShopDBConnection()</h3>';
try {
    $shop_pdo = getShopDBConnection();
    $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $html .= '<p class="success">Connexion à la base du magasin réussie: ' . $result['db_name'] . '</p>';
    $shop_db_name = $result['db_name'];
    
    // Test de la table clients dans la base du magasin
    try {
        $stmt = $shop_pdo->query("SHOW TABLES LIKE 'clients'");
        if ($stmt->rowCount() > 0) {
            $html .= '<p>La table "clients" existe dans la base du magasin.</p>';
            
            // Compter les clients dans la base du magasin
            $stmt = $shop_pdo->query("SELECT COUNT(*) as count FROM clients");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            $html .= '<p>Nombre de clients dans la base du magasin: <strong>' . $count['count'] . '</strong></p>';
            
            // Lister les 5 derniers clients ajoutés
            $stmt = $shop_pdo->query("SELECT id, nom, prenom, telephone, date_creation FROM clients ORDER BY id DESC LIMIT 5");
            $last_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($last_clients) > 0) {
                $html .= '<p>5 derniers clients ajoutés dans la base du magasin:</p>';
                $html .= '<table>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Téléphone</th>
                        <th>Date de création</th>
                    </tr>';
                
                foreach ($last_clients as $client) {
                    $html .= '<tr>
                        <td>' . $client['id'] . '</td>
                        <td>' . $client['nom'] . '</td>
                        <td>' . $client['prenom'] . '</td>
                        <td>' . $client['telephone'] . '</td>
                        <td>' . $client['date_creation'] . '</td>
                    </tr>';
                }
                
                $html .= '</table>';
            } else {
                $html .= '<p class="warning">Aucun client trouvé dans la base du magasin.</p>';
            }
        } else {
            $html .= '<p class="error">La table "clients" n\'existe pas dans la base du magasin!</p>';
        }
    } catch (Exception $e) {
        $html .= '<p class="warning">Erreur lors de la vérification de la table clients dans la base du magasin: ' . $e->getMessage() . '</p>';
    }
} catch (Exception $e) {
    $html .= '<p class="error">Erreur de connexion à la base du magasin: ' . $e->getMessage() . '</p>';
}

// Section 3: Formulaire de test pour ajouter un client
$html .= '<h2>3. Test d\'ajout de client</h2>';
$html .= '<div class="test-form">
    <form method="post" action="" id="testClientForm">
        <div class="form-group">
            <label for="nom">Nom *</label>
            <input type="text" id="nom" name="nom" required>
        </div>
        <div class="form-group">
            <label for="prenom">Prénom *</label>
            <input type="text" id="prenom" name="prenom" required>
        </div>
        <div class="form-group">
            <label for="telephone">Téléphone *</label>
            <input type="text" id="telephone" name="telephone" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email">
        </div>
        <button type="submit" name="test_add_client" class="btn-submit">Tester l\'ajout de client</button>
    </form>
</div>';

// Section 4: Résultat du test (si le formulaire a été soumis)
if (isset($_POST['test_add_client'])) {
    $html .= '<h2>4. Résultat du test</h2>';
    
    // Récupérer les données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($nom) || empty($prenom) || empty($telephone)) {
        $html .= '<p class="error">Tous les champs marqués d\'un * sont obligatoires.</p>';
    } else {
        $html .= formatSection('Données soumises', print_r($_POST, true));
        
        try {
            // Utiliser getShopDBConnection() pour s'assurer d'utiliser la base du magasin
            $test_pdo = getShopDBConnection();
            
            // Vérifier la connexion à la base de données du magasin
            $db_stmt = $test_pdo->query("SELECT DATABASE() as db_name");
            $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
            $html .= '<p class="success">Base utilisée pour le test: ' . $db_info['db_name'] . '</p>';
            
            // Insérer le client
            $sql = "INSERT INTO clients (nom, prenom, telephone, email, date_creation) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $test_pdo->prepare($sql);
            $result = $stmt->execute([$nom, $prenom, $telephone, $email]);
            
            if ($result) {
                $client_id = $test_pdo->lastInsertId();
                $html .= '<p class="success">Client ajouté avec succès! ID: ' . $client_id . '</p>';
                
                // Vérifier dans quelle base le client a été ajouté
                $check_stmt = $test_pdo->prepare("SELECT * FROM clients WHERE id = ?");
                $check_stmt->execute([$client_id]);
                $new_client = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($new_client) {
                    $html .= formatSection('Données du client dans la base ' . $db_info['db_name'], print_r($new_client, true));
                } else {
                    $html .= '<p class="warning">Client créé mais introuvable dans la base ' . $db_info['db_name'] . '</p>';
                }
                
                // Vérifier si le client existe aussi dans la base principale (s'il s'agit d'une autre base)
                if ($shop_db_name !== $db_info['db_name']) {
                    $html .= '<p class="error">ERREUR: La base utilisée pour le test est différente de celle récupérée précédemment!</p>';
                }
                
                // Si on a une base séparée, vérifier dans la principale
                if ($db_info['db_name'] !== $main_pdo->query("SELECT DATABASE() as db_name")->fetch(PDO::FETCH_ASSOC)['db_name']) {
                    $main_check_stmt = $main_pdo->prepare("SELECT * FROM clients WHERE telephone = ? AND nom = ? AND prenom = ? LIMIT 1");
                    $main_check_stmt->execute([$telephone, $nom, $prenom]);
                    $main_client = $main_check_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($main_client) {
                        $html .= '<p class="warning">ATTENTION: Le client existe aussi dans la base principale! ID: ' . $main_client['id'] . '</p>';
                        $html .= formatSection('Données du client dans la base principale', print_r($main_client, true));
                    } else {
                        $html .= '<p class="success">Le client n\'existe pas dans la base principale (c\'est normal).</p>';
                    }
                }
            } else {
                $html .= '<p class="error">Erreur lors de l\'ajout du client</p>';
            }
        } catch (Exception $e) {
            $html .= '<p class="error">Exception: ' . $e->getMessage() . '</p>';
        }
    }
}

// Liens et actions
$html .= '<div class="actions">
    <a href="/fix_connections.php" class="btn">Corriger les connexions</a>
    <a href="/debug_shop_connection.php" class="btn">Vérifier les connexions</a>
    <a href="/index.php?page=ajouter_reparation" class="btn">Ajouter une réparation</a>
    <a href="/index.php" class="btn" style="background: #6c757d;">Retour à l\'application</a>
</div>';

// Fermer le HTML
$html .= '</body>
</html>';

// Afficher le résultat
echo $html;
?> 