<?php
/**
 * Script de réinitialisation du mot de passe Superadmin
 * 
 * Ce script réinitialise le mot de passe du superadministrateur à "Admin123!"
 * dans la base de données principale
 */

// Afficher les erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Inclure la configuration de la base de données
require_once 'config/database.php';

// Fonction pour journaliser les messages
function logMessage($message) {
    echo "<div class='log-message'>" . htmlspecialchars($message) . "</div>";
    error_log($message);
}

// Définir le nouveau mot de passe
$new_password = 'Admin123!';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Variable pour stocker le résultat
$result = false;
$username = 'Admin';

// Si un formulaire a été soumis pour confirmer la réinitialisation
if (isset($_POST['confirm_reset']) && $_POST['confirm_reset'] === 'yes') {
    try {
        // Définir le nom d'utilisateur depuis le formulaire s'il est fourni
        if (isset($_POST['username']) && !empty($_POST['username'])) {
            $username = $_POST['username'];
        }
        
        // Établir une connexion à la base de données principale
        $pdo = getMainDBConnection();
        
        // Vérifier la connexion
        if ($pdo === null) {
            throw new Exception("Erreur: Impossible de se connecter à la base de données principale.");
        }
        
        // Vérifier quelle base de données est actuellement utilisée
        $stmt = $pdo->query("SELECT DATABASE() as current_db");
        $db_info = $stmt->fetch(PDO::FETCH_ASSOC);
        logMessage("Base de données connectée: " . $db_info['current_db']);
        
        // Mettre à jour le mot de passe dans la table superadmins
        $stmt = $pdo->prepare("UPDATE superadmins SET password = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $username]);
        $affected_rows = $stmt->rowCount();
        
        if ($affected_rows > 0) {
            logMessage("Succès: Le mot de passe du superadmin '$username' a été réinitialisé à '$new_password'");
            $result = true;
        } else {
            logMessage("Attention: Aucun superadmin '$username' trouvé dans la base de données.");
            
            // Tenter de créer le superadmin s'il n'existe pas
            logMessage("Tentative de création du superadmin '$username'...");
            
            $stmt = $pdo->prepare("INSERT INTO superadmins (username, password, full_name, email, active) 
                                  VALUES (?, ?, 'Super Administrateur', 'superadmin@geekboard.com', 1)");
            $stmt->execute([$username, $hashed_password]);
            
            if ($stmt->rowCount() > 0) {
                logMessage("Succès: Le superadmin '$username' a été créé avec le mot de passe '$new_password'");
                $result = true;
            } else {
                logMessage("Erreur: Impossible de créer le superadmin. Vérifiez la structure de la table superadmins.");
            }
        }
    } catch (Exception $e) {
        logMessage("Erreur: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe Superadmin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 50px;
            background-color: #f8f9fa;
        }
        .reset-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .log-message {
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .success-banner {
            background-color: #d4edda;
            color: #155724;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container reset-container">
        <h1 class="text-center mb-4">Réinitialisation du mot de passe Superadmin</h1>
        
        <?php if ($result): ?>
        <div class="success-banner">
            <h4>Réinitialisation réussie !</h4>
            <p>Le mot de passe du superadmin <strong><?php echo htmlspecialchars($username); ?></strong> a été réinitialisé à :</p>
            <div class="p-3 mb-2 bg-light rounded text-center">
                <code class="fs-4"><?php echo htmlspecialchars($new_password); ?></code>
            </div>
            <p class="mb-0">Vous pouvez maintenant vous connecter avec ce mot de passe.</p>
        </div>
        
        <div class="d-flex justify-content-between mt-4">
            <a href="index.php" class="btn btn-primary">Retour à l'accueil</a>
            <a href="pages/login.php?superadmin=1" class="btn btn-success">Se connecter</a>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <p><strong>Attention :</strong> Ce script va réinitialiser le mot de passe d'un superadmin dans la base de données principale.</p>
            <p>Cette action est irréversible et doit être utilisée uniquement en cas de perte de mot de passe.</p>
        </div>
        
        <form method="post" action="">
            <input type="hidden" name="confirm_reset" value="yes">
            
            <div class="mb-3">
                <label for="username" class="form-label">Nom d'utilisateur du superadmin</label>
                <input type="text" class="form-control" id="username" name="username" value="Admin" required>
                <div class="form-text">Laissez "Admin" si vous ne savez pas quel nom d'utilisateur utiliser.</div>
            </div>
            
            <div class="alert alert-info">
                <p>Le nouveau mot de passe sera : <code><?php echo htmlspecialchars($new_password); ?></code></p>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger btn-lg">Confirmer la réinitialisation</button>
                <a href="index.php" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="reset_admin_password.php" class="btn btn-outline-primary w-100">Réinitialiser le mot de passe admin d'un magasin</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 