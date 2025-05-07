<?php
// Script pour vérifier l'existence du template SMS ID 5
session_start();

// Afficher les erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de la base de données
require_once __DIR__ . '/config/database.php';

// Créer une session de test si nécessaire
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['shop_id'] = 1;
}

// Obtenir la connexion à la base de données principale
try {
    $pdo = getMainDBConnection();
    echo "<h2>Connexion à la base de données réussie</h2>";

    // Vérifier si le template SMS ID 5 existe
    $stmt = $pdo->prepare("SELECT * FROM sms_templates WHERE id = ? AND est_actif = 1");
    $stmt->execute([5]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($template) {
        echo "<div style='color:green'><strong>Le template SMS ID 5 existe et est actif !</strong></div>";
        echo "<pre>";
        print_r($template);
        echo "</pre>";
    } else {
        echo "<div style='color:red'><strong>Le template SMS ID 5 n'existe pas ou n'est pas actif !</strong></div>";
        
        // Compter tous les templates
        $stmt = $pdo->query("SELECT COUNT(*) FROM sms_templates");
        $count = $stmt->fetchColumn();
        echo "<p>Nombre total de templates SMS: $count</p>";
        
        // Lister tous les templates actifs
        $stmt = $pdo->query("SELECT id, nom FROM sms_templates WHERE est_actif = 1");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($templates) > 0) {
            echo "<h3>Templates SMS actifs disponibles:</h3>";
            echo "<ul>";
            foreach ($templates as $t) {
                echo "<li>ID: {$t['id']} - Nom: {$t['nom']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>Aucun template SMS actif trouvé.</p>";
        }
    }
} catch (PDOException $e) {
    echo "<div style='color:red'><strong>Erreur de connexion à la base de données :</strong> " . $e->getMessage() . "</div>";
}
?> 