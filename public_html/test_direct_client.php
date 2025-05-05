<?php
// Page de test pour la connexion directe à la base de données du magasin
// Cette page permet de tester l'ajout d'un client directement dans la base du magasin

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Inclure la configuration de la base de données
require_once 'config/database.php';

// Traitement du formulaire
$result = null;
$error = null;
$client_id = null;
$db_info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_direct_add'])) {
    try {
        // Récupérer l'ID du magasin
        $shop_id = isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : null;
        
        if (!$shop_id) {
            throw new Exception("Aucun magasin sélectionné dans la session");
        }
        
        // Récupérer les informations de connexion à la base du magasin
        $main_pdo = getMainDBConnection();
        $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
        $stmt->execute([$shop_id]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$shop) {
            throw new Exception("Magasin non trouvé (ID: $shop_id)");
        }
        
        // Vérifier que les informations de connexion sont complètes
        if (empty($shop['db_host']) || empty($shop['db_user']) || empty($shop['db_name'])) {
            throw new Exception("Configuration de la base de données du magasin incomplète");
        }
        
        // Établir une connexion directe à la base de données du magasin
        $dsn = 'mysql:host=' . $shop['db_host'] . ';dbname=' . $shop['db_name'];
        if (!empty($shop['db_port'])) {
            $dsn .= ';port=' . $shop['db_port'];
        }
        
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
        
        // Récupérer les données du formulaire
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $telephone = trim($_POST['telephone']);
        $email = trim($_POST['email'] ?? '');
        
        // Validation des champs obligatoires
        if (empty($nom) || empty($prenom) || empty($telephone)) {
            throw new Exception("Tous les champs obligatoires doivent être remplis");
        }
        
        // Insérer le client dans la base de données du magasin
        $sql = "INSERT INTO clients (nom, prenom, telephone, email, date_creation) 
                VALUES (:nom, :prenom, :telephone, :email, NOW())";
        
        $stmt = $shop_pdo->prepare($sql);
        $stmt->execute([
            ':nom' => $nom,
            ':prenom' => $prenom,
            ':telephone' => $telephone,
            ':email' => $email
        ]);
        
        // Récupérer l'ID du client créé
        $client_id = $shop_pdo->lastInsertId();
        
        // Vérifier la base de données utilisée
        $db_stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
        $db_info = $db_stmt->fetch(PDO::FETCH_ASSOC);
        
        $result = "Client ajouté avec succès dans la base " . $db_info['db_name'] . " (ID: " . $client_id . ")";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Afficher la page HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test d'ajout direct de client</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 2rem; }
        .test-container { max-width: 800px; margin: 0 auto; }
        .test-info { background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .success-message { color: #198754; font-weight: bold; }
        .error-message { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container test-container">
        <h1 class="mb-4">Test d'ajout direct de client</h1>
        
        <div class="test-info">
            <h3>Informations de session</h3>
            <p><strong>Utilisateur ID:</strong> <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Non connecté'; ?></p>
            <p><strong>Magasin ID:</strong> <?php echo isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'Non défini'; ?></p>
            <p><strong>Magasin nom:</strong> <?php echo isset($_SESSION['shop_name']) ? $_SESSION['shop_name'] : 'Non défini'; ?></p>
        </div>
        
        <?php if ($result): ?>
        <div class="alert alert-success">
            <p class="success-message"><?php echo $result; ?></p>
            <?php if ($client_id && $db_info): ?>
            <p><strong>Client ID:</strong> <?php echo $client_id; ?></p>
            <p><strong>Base de données:</strong> <?php echo $db_info['db_name']; ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <p class="error-message">Erreur: <?php echo $error; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title h5 mb-0">Formulaire de test</h2>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="prenom" class="form-label">Prénom *</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" required>
                    </div>
                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone *</label>
                        <input type="text" class="form-control" id="telephone" name="telephone" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <button type="submit" name="test_direct_add" class="btn btn-primary">Tester l'ajout direct</button>
                </form>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="fix_connections.php" class="btn btn-info">Corriger les connexions</a>
            <a href="debug_shop_connection.php" class="btn btn-secondary">Diagnostiquer les connexions</a>
            <a href="index.php" class="btn btn-outline-primary">Retour à l'application</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 