<?php
// Page de test pour forcer l'affichage de la landing page
// Cette page simule mdgeek.top et force l'affichage de la landing page

// Simuler les variables serveur pour mdgeek.top
$_SERVER['HTTP_HOST'] = 'mdgeek.top';

// Démarrer la session et la nettoyer
session_start();
unset($_SESSION['shop_id']);
unset($_SESSION['shop_name']);
unset($_SESSION['superadmin_id']);

// Inclure la page de landing directement
include __DIR__ . '/pages/landing.php';
?> 