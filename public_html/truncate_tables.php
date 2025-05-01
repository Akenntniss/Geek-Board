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

// Fonction pour exécuter les requêtes SQL à partir d'un fichier
function executeSQLFromFile($pdo, $file_path) {
    if (!file_exists($file_path)) {
        throw new Exception("Le fichier SQL n'existe pas: $file_path");
    }
    
    $sql = file_get_contents($file_path);
    // Diviser le fichier en requêtes individuelles (séparées par ;)
    $queries = explode(';', $sql);
    
    $results = [
        'success' => true,
        'executed' => 0,
        'errors' => []
    ];
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $results['executed']++;
        } catch (PDOException $e) {
            $results['success'] = false;
            $results['errors'][] = [
                'query' => $query,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return $results;
}

// Traiter la soumission du formulaire
$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['truncate'])) {
    try {
        // Exécuter le script SQL de vidage des tables
        $sql_file = 'sql/truncate_tables.sql';
        $results = executeSQLFromFile($pdo, $sql_file);
        
        if ($results['success']) {
            $message = "Opération réussie! {$results['executed']} requêtes ont été exécutées.";
            $status = 'success';
        } else {
            $message = "Opération partiellement réussie. {$results['executed']} requêtes ont été exécutées, mais il y a eu des erreurs.";
            $status = 'warning';
            foreach ($results['errors'] as $error) {
                $message .= "<br><strong>Erreur:</strong> " . htmlspecialchars($error['error']);
            }
        }
    } catch (Exception $e) {
        $message = "Erreur lors de l'opération: " . $e->getMessage();
        $status = 'danger';
    }
}

// Afficher la page HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vider les tables</title>
    <!-- Bootstrap CSS -->
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
        .header {
            margin-bottom: 2rem;
            text-align: center;
        }
        .warning-text {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Vider les tables de la base de données</h1>
            <p class="lead">Cette opération va supprimer toutes les données des tables suivantes:</p>
            <ul class="list-group mb-3">
                <li class="list-group-item">Réparations</li>
                <li class="list-group-item">Logs de réparations</li>
                <li class="list-group-item">Logs de SMS</li>
                <li class="list-group-item">Clients</li>
            </ul>
            <p class="warning-text">ATTENTION: Cette action est irréversible!</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $status; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <form method="post" action="" onsubmit="return confirm('Êtes-vous vraiment sûr de vouloir vider toutes ces tables? Cette action est IRRÉVERSIBLE!');">
                <div class="d-grid gap-2">
                    <button type="submit" name="truncate" class="btn btn-danger btn-lg">
                        Vider les tables
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        Retour à l'accueil
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 