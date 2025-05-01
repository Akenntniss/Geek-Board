<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once 'config/database.php';
require_once 'includes/functions.php';

// Démarrer ou récupérer la session existante
session_start();

// Vérifier si l'utilisateur est administrateur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<div class='alert alert-danger'>Vous n'avez pas les droits nécessaires pour accéder à cette page.</div>";
    exit;
}

try {
    // Réactiver les contraintes de clé étrangère
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Vérifier le statut
    $stmt = $pdo->query("SELECT @@FOREIGN_KEY_CHECKS as status");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $status = $result['status'] == 1 ? 'activées' : 'désactivées';
    $message = "Les contraintes de clé étrangère sont maintenant " . $status;
    $alert_type = $result['status'] == 1 ? 'success' : 'danger';
    
} catch (PDOException $e) {
    $message = "Erreur lors de la réactivation des contraintes : " . $e->getMessage();
    $alert_type = 'danger';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réactiver les contraintes de clé étrangère</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 2rem;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Réactiver les contraintes de clé étrangère</h1>
        
        <div class="alert alert-<?php echo $alert_type; ?>">
            <?php echo $message; ?>
        </div>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html> 