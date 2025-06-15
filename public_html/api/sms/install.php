<?php
/**
 * Script d'installation du système SMS
 * 
 * Ce script crée les tables nécessaires et initialise les configurations par défaut
 */

// Initialiser la session
session_start();

// Vérifier si l'utilisateur est connecté et admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php?error=unauthorized');
    exit;
}

// Inclure les fonctions
require_once '../../includes/functions.php';

// Récupérer le contenu du script SQL
$sql_file = file_get_contents(__DIR__ . '/create_sms_tables.sql');

// Diviser en requêtes individuelles
$queries = explode(';', $sql_file);

// Variables pour le suivi
$success = true;
$error_message = '';
$success_count = 0;
$error_count = 0;
$total_queries = count($queries) - 1; // La dernière requête après le dernier ';' est vide

// Exécuter chaque requête
$shop_pdo = getShopDBConnection();

try {
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        try {
            $shop_pdo->exec($query);
            $success_count++;
        } catch (PDOException $e) {
            $error_count++;
            $error_message .= "Erreur dans la requête: " . $e->getMessage() . "<br>";
            $success = false;
        }
    }
} catch (Exception $e) {
    $success = false;
    $error_message = "Erreur globale: " . $e->getMessage();
}

// Inclure l'en-tête
$title = "Installation du système SMS";
include '../../includes/header.php';
?>

<div class="container mt-4">
    <h1>Installation du système SMS</h1>
    
    <div class="card">
        <div class="card-header">
            <h5>Résultat de l'installation</h5>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h4 class="alert-heading">Installation réussie !</h4>
                    <p>Toutes les tables et configurations ont été créées avec succès.</p>
                    <hr>
                    <p class="mb-0">
                        <?php echo $success_count; ?> requêtes exécutées sur <?php echo $total_queries; ?>.
                    </p>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <h4 class="alert-heading">Erreur lors de l'installation</h4>
                    <p>Des erreurs sont survenues lors de l'installation :</p>
                    <hr>
                    <p><?php echo $error_message; ?></p>
                    <p class="mb-0">
                        <?php echo $success_count; ?> requêtes exécutées avec succès, <?php echo $error_count; ?> erreurs.
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="/api/sms/admin.php" class="btn btn-primary">Aller à l'administration SMS</a>
                <a href="/index.php" class="btn btn-secondary">Retour à l'accueil</a>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h5>Prochaines étapes</h5>
        </div>
        <div class="card-body">
            <ol>
                <li>Accédez à la <a href="/api/sms/admin.php">page d'administration SMS</a> pour configurer le système</li>
                <li>Installez l'application <a href="https://play.google.com/store/apps/details?id=org.ushahidi.android.app.smssync" target="_blank">SMSSync</a> sur votre smartphone Android</li>
                <li>Suivez les instructions de configuration sur la page d'administration SMS</li>
                <li>Testez l'envoi de SMS à l'aide de la fonction de test</li>
            </ol>
        </div>
    </div>
</div>

<?php
// Inclure le pied de page
include '../../includes/footer.php';
?> 