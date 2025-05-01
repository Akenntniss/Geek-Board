<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit();
}

// Configuration de l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le chemin de base
define('BASE_PATH', __DIR__);

// Inclure les fichiers nécessaires
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Inclure les fichiers d'interface
include BASE_PATH . '/includes/header.php';

// Inclure la page gardiennage
include BASE_PATH . '/pages/gardiennage.php';

// Inclure le pied de page
include BASE_PATH . '/includes/footer.php';
?> 