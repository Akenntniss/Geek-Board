<?php
// Démarrer la session
session_start();

// Désactiver le mode superadmin
$_SESSION['superadmin_mode'] = false;

// Afficher un message
echo '<!DOCTYPE html>
<html>
<head>
    <title>Mode Superadmin Désactivé</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding-top: 100px; }
        .success { color: green; font-weight: bold; }
        .info { color: blue; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mode Superadmin Désactivé</h1>
        <p class="success">Le mode superadmin a été désactivé avec succès!</p>
        <p class="info">Vous pouvez maintenant utiliser le système normalement.</p>
        
        <div style="margin-top: 30px;">
            <p>Retournez à l\'application pour continuer.</p>
            <p><a href="/index.php" style="display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Retour à l\'application</a></p>
        </div>
        
        <div style="margin-top: 20px; font-size: 12px; color: #666;">
            <p>Session ID actuel: ' . session_id() . '</p>
        </div>
    </div>
</body>
</html>';
?> 