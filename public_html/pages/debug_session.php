<?php
// Démarrer la session
session_start();

// Afficher l'en-tête HTML pour que ce soit bien formaté
echo '<!DOCTYPE html>
<html>
<head>
    <title>Debug Session</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .highlight { background: #fff3cd; padding: 2px 5px; }
    </style>
</head>
<body>';

echo '<h1>Informations de session</h1>';

// Afficher l'ID de session
echo '<p>Session ID: <strong>' . session_id() . '</strong></p>';

// Afficher le contenu complet de la session
echo '<h2>Contenu de $_SESSION</h2>';
echo '<pre>' . print_r($_SESSION, true) . '</pre>';

// Vérifier spécifiquement le mode superadmin
echo '<h2>Mode Superadmin</h2>';
if (isset($_SESSION['superadmin_mode']) && $_SESSION['superadmin_mode'] === true) {
    echo '<p style="color: red; font-weight: bold;">Mode superadmin est ACTIF! Cette configuration force l\'utilisation de la base de données principale.</p>';
    
    // Ajout d'un bouton pour désactiver le mode superadmin
    echo '<form method="post" action="">';
    echo '<input type="hidden" name="disable_superadmin" value="1">';
    echo '<button type="submit" style="background: #dc3545; color: white; padding: 10px; border: none; border-radius: 5px; cursor: pointer;">Désactiver le mode superadmin</button>';
    echo '</form>';
} else {
    echo '<p style="color: green;">Mode superadmin est INACTIF</p>';
}

// Vérifier l'ID du magasin
echo '<h2>Magasin sélectionné</h2>';
if (isset($_SESSION['shop_id'])) {
    echo '<p>ID du magasin: <span class="highlight">' . $_SESSION['shop_id'] . '</span></p>';
    echo '<p>Nom du magasin: <span class="highlight">' . ($_SESSION['shop_name'] ?? 'Non défini') . '</span></p>';
} else {
    echo '<p style="color: red;">Aucun magasin sélectionné!</p>';
}

// Si le formulaire est soumis pour désactiver le mode superadmin
if (isset($_POST['disable_superadmin'])) {
    $_SESSION['superadmin_mode'] = false;
    echo '<p style="color: green; font-weight: bold;">Mode superadmin désactivé! Actualisez la page pour voir les changements.</p>';
    echo '<script>setTimeout(function() { window.location.reload(); }, 1500);</script>';
}

echo '</body></html>';
?> 