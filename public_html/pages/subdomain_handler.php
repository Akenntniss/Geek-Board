<?php
// Gestionnaire de sous-domaines pour GeekBoard
// Ce fichier est le point d'entrée pour toutes les requêtes venant d'un sous-domaine

// On active l'affichage des erreurs en développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir les en-têtes pour éviter les erreurs CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization');

// Si c'est une requête OPTIONS, terminer ici (pour les requêtes préflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Démarrer la session
session_start();

// Récupérer le sous-domaine depuis l'URL
$subdomain = isset($_GET['subdomain']) ? trim($_GET['subdomain']) : '';
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';

// Si aucun sous-domaine n'est spécifié, rediriger vers le domaine principal
if (empty($subdomain)) {
    header('Location: /');
    exit;
}

// Journaliser les informations pour le débogage
error_log("Sous-domaine: " . $subdomain);
error_log("Chemin: " . $path);

// Inclure la configuration de la base de données
require_once __DIR__ . '/config/database.php';

// Se connecter à la base de données principale
$shop_pdo = getMainDBConnection();

try {
    // Rechercher le magasin correspondant au sous-domaine
    $stmt = $shop_pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1");
    $stmt->execute([$subdomain]);
    $shop = $stmt->fetch();
    
    if ($shop) {
        // Magasin trouvé, on stocke son ID en session
        $_SESSION['shop_id'] = $shop['id'];
        $_SESSION['shop_name'] = $shop['name'];
        
        // Définir un cookie pour le sous-domaine
        setcookie('current_shop', $shop['id'], time() + 86400 * 30, '/', '.' . $_SERVER['HTTP_HOST']);
        
        // Rediriger vers la page demandée
        $redirect_path = empty($path) ? '/index.php' : '/' . $path;
        
        // Passer les paramètres de requête
        if (!empty($_SERVER['QUERY_STRING'])) {
            $queryParams = $_GET;
            // Supprimer les paramètres ajoutés par le gestionnaire de sous-domaines
            unset($queryParams['subdomain']);
            unset($queryParams['path']);
            
            if (!empty($queryParams)) {
                $redirect_path .= '?' . http_build_query($queryParams);
            }
        }
        
        // Journaliser la redirection
        error_log("Redirection vers: " . $redirect_path);
        
        // Effectuer la redirection
        header('Location: ' . $redirect_path);
        exit;
    } else {
        // Magasin non trouvé ou inactif
        http_response_code(404);
        include __DIR__ . '/templates/shop_not_found.php';
        exit;
    }
} catch (Exception $e) {
    // En cas d'erreur, journaliser et afficher un message d'erreur
    error_log("Erreur: " . $e->getMessage());
    http_response_code(500);
    include __DIR__ . '/templates/error.php';
    exit;
}
?> 