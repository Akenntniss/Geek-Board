<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ajouter une fonction de journalisation pour tracer les étapes d'authentification
function debugLog($message) {
    error_log("[DEBUG LOGIN] " . $message);
    
    // Stocker les messages de débogage dans un tableau en session pour affichage
    if (!isset($_SESSION['debug_messages'])) {
        $_SESSION['debug_messages'] = [];
    }
    $_SESSION['debug_messages'][] = $message;
}

debugLog("Début du processus de connexion");

// Inclure la configuration de session avant de démarrer la session
require_once '../config/session_config.php';
// La session est déjà démarrée dans session_config.php
require_once '../config/database.php';
// Inclure la configuration pour la gestion des sous-domaines
require_once '../config/subdomain_config.php';

// Vérifier si mode superadmin est demandé et l'enregistrer en session
$superadmin_mode = isset($_GET['superadmin']) && $_GET['superadmin'] == '1';
if ($superadmin_mode) {
    $_SESSION['superadmin_mode'] = true;
    debugLog("Mode superadmin activé via URL");
    
    // On retire la sélection de magasin quand on est en mode superadmin
    if (isset($_SESSION['shop_id'])) {
        unset($_SESSION['shop_id']);
        unset($_SESSION['shop_name']);
        debugLog("Session shop_id supprimée en mode superadmin");
    }
} elseif (isset($_SESSION['superadmin_mode'])) {
    $superadmin_mode = $_SESSION['superadmin_mode'];
    debugLog("Mode superadmin déjà actif en session");
}

// IMPORTANT: Si le mode superadmin est actif, on s'assure que shop_id est vide
if ($superadmin_mode && isset($_SESSION['shop_id'])) {
    unset($_SESSION['shop_id']);
    unset($_SESSION['shop_name']);
    debugLog("Shop ID forcé à null en mode superadmin");
}

// Récupérer l'ID du magasin s'il est spécifié dans l'URL et qu'on n'est pas en mode superadmin
$shop_id = !$superadmin_mode && isset($_GET['shop_id']) ? (int)$_GET['shop_id'] : null;
debugLog("Shop ID depuis URL: " . ($shop_id ? $shop_id : "non défini"));

// Si l'ID du magasin est spécifié, stocker dans la session
if ($shop_id && !$superadmin_mode) {
    // Vérifier que le magasin existe et est actif
    $pdo_main = getMainDBConnection();
    $stmt = $pdo_main->prepare("SELECT id, name FROM shops WHERE id = ? AND active = 1");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch();
    
    if ($shop) {
        $_SESSION['shop_id'] = $shop['id'];
        $_SESSION['shop_name'] = $shop['name'];
        debugLog("Magasin défini en session: ID=" . $shop['id'] . ", Name=" . $shop['name']);
    } else {
        debugLog("Magasin non trouvé ou inactif pour l'ID: " . $shop_id);
    }
}

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    debugLog("Utilisateur déjà connecté, redirection");
    // Capturer les paramètres de test PWA
    $redirect_url = '/index.php';
    $pwa_params = [];
    
    if (isset($_GET['test_pwa']) && $_GET['test_pwa'] === 'true') {
        $pwa_params[] = 'test_pwa=true';
        $_SESSION['test_pwa'] = true;
    }
    
    if (isset($_GET['test_ios']) && $_GET['test_ios'] === 'true') {
        $pwa_params[] = 'test_ios=true';
        $_SESSION['test_ios'] = true;
    }
    
    if (isset($_GET['test_dynamic_island']) && $_GET['test_dynamic_island'] === 'true') {
        $pwa_params[] = 'test_dynamic_island=true';
        $_SESSION['test_dynamic_island'] = true;
    }
    
    // Ajouter les paramètres à l'URL de redirection
    if (!empty($pwa_params)) {
        $redirect_url .= '?' . implode('&', $pwa_params);
    }
    
    header('Location: ' . $redirect_url);
    exit();
}

$error = '';
$shopList = [];

// Récupérer la liste des magasins disponibles
try {
    $pdo_main = getMainDBConnection();
    $shopList = $pdo_main->query("SELECT id, name FROM shops WHERE active = 1 ORDER BY name")->fetchAll();
    debugLog("Liste des magasins récupérée: " . count($shopList) . " magasins trouvés");
} catch (PDOException $e) {
    debugLog("Erreur lors de la récupération des magasins: " . $e->getMessage());
    error_log("Erreur lors de la récupération des magasins: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    debugLog("Traitement du formulaire POST");
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] == '1';
    $selected_shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : null;

    debugLog("Données POST - Username: " . $username . ", Shop ID: " . ($selected_shop_id ? $selected_shop_id : "non défini") . ", Superadmin mode: " . ($superadmin_mode ? "oui" : "non"));
    
    // Vérifier si l'utilisateur a changé de magasin
    if (isset($_SESSION['shop_id']) && $selected_shop_id && $_SESSION['shop_id'] != $selected_shop_id) {
        debugLog("Changement de magasin détecté: " . $_SESSION['shop_id'] . " -> " . $selected_shop_id);
        // Réinitialiser les données de session liées au magasin précédent
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['user_role']);
        // Conserver uniquement les informations du nouveau magasin
        $_SESSION['shop_id'] = $selected_shop_id;
        // Les autres informations du magasin seront mises à jour pendant la connexion
    }

    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
        debugLog("Erreur: champs vides");
    } else if (!$superadmin_mode && count($shopList) > 0 && empty($selected_shop_id)) {
        $error = 'Veuillez sélectionner un magasin';
        debugLog("Erreur: aucun magasin sélectionné");
    } else {
        try {
            // Mode super administrateur - utiliser la base de données principale
            if ($superadmin_mode) {
                debugLog("Tentative de connexion en mode superadmin");
                $pdo_main = getMainDBConnection();
                
                // S'assurer que nous n'utilisons pas une connexion à un magasin
                if (isset($_SESSION['shop_id'])) {
                    unset($_SESSION['shop_id']);
                    unset($_SESSION['shop_name']);
                    debugLog("Session shop_id supprimée dans le traitement POST en mode superadmin");
                }
                
                // Vérifier les identifiants super administrateur
                // Vérifier si le username est un email
                if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                    debugLog("Tentative de connexion superadmin par email");
                    $stmt = $pdo_main->prepare('SELECT * FROM superadmins WHERE email = ? AND active = 1');
                } else {
                    debugLog("Tentative de connexion superadmin par nom d'utilisateur");
                    $stmt = $pdo_main->prepare('SELECT * FROM superadmins WHERE username = ? AND active = 1');
                }
                $stmt->execute([$username]);
                $superadmin = $stmt->fetch();
                
                if ($superadmin) {
                    debugLog("Superadmin trouvé, vérification du mot de passe");
                    if (password_verify($password, $superadmin['password'])) {
                        debugLog("Authentification superadmin réussie, ID: " . $superadmin['id']);
                        $_SESSION['superadmin_id'] = $superadmin['id'];
                        $_SESSION['superadmin_username'] = $superadmin['username'];
                        $_SESSION['superadmin_name'] = $superadmin['full_name'];
                        
                        // Redirection vers le tableau de bord des magasins
                        header('Location: /superadmin/index.php');
                        exit();
                    } else {
                        debugLog("Mot de passe superadmin incorrect");
                        $error = 'Identifiants superadmin incorrects';
                    }
                } else {
                    debugLog("Superadmin non trouvé: " . $username);
                    $error = 'Identifiants superadmin incorrects';
                    error_log("Tentative de connexion superadmin échouée pour: " . $username);
                }
            } else {
                debugLog("Tentative de connexion utilisateur standard");
                // Si un magasin est sélectionné, utiliser sa base de données
                if ($selected_shop_id) {
                    debugLog("Utilisation du magasin sélectionné ID: " . $selected_shop_id);
                    // Vérifier que le magasin existe et est actif
                    $stmt = $pdo_main->prepare("SELECT * FROM shops WHERE id = ? AND active = 1");
                    $stmt->execute([$selected_shop_id]);
                    $shop = $stmt->fetch();
                    
                    if ($shop) {
                        debugLog("Magasin trouvé: " . $shop['name']);
                        // Stocker les infos du magasin en session
                        $_SESSION['shop_id'] = $shop['id'];
                        $_SESSION['shop_name'] = $shop['name'];
                        
                        // Connexion à la base de données du magasin
                        $shop_config = [
                            'host' => $shop['db_host'],
                            'port' => $shop['db_port'],
                            'dbname' => $shop['db_name'],
                            'user' => $shop['db_user'],
                            'pass' => $shop['db_pass']
                        ];
                        
                        debugLog("Tentative de connexion à la DB du magasin: " . $shop_config['dbname'] . " sur " . $shop_config['host'] . ":" . $shop_config['port']);
                        $pdo = connectToShopDB($shop_config);
                        
                        // Ajouter cette section de débogage temporaire
                        if ($pdo === null) {
                            debugLog("ÉCHEC de connexion à la DB du magasin");
                            error_log("Échec de la connexion à la base de données du magasin: " . $shop['name']);
                            error_log("Paramètres: " . json_encode($shop_config));
                            throw new PDOException("La connexion à la base de données du magasin a échoué");
                        } else {
                            debugLog("Connexion réussie à la DB du magasin");
                        }
                    } else {
                        debugLog("Magasin non trouvé ou inactif: " . $selected_shop_id);
                        throw new Exception("Magasin non trouvé ou inactif");
                    }
                } else {
                    debugLog("Aucun magasin sélectionné, utilisation de la connexion par défaut");
                    // Si aucun magasin n'est sélectionné mais qu'on a un shop_id en session
                    if (isset($_SESSION['shop_id'])) {
                        debugLog("Utilisation du magasin en session: " . $_SESSION['shop_id']);
                        $shop_id = $_SESSION['shop_id'];
                        // Récupérer les informations du magasin
                        $stmt = $pdo_main->prepare("SELECT * FROM shops WHERE id = ? AND active = 1");
                        $stmt->execute([$shop_id]);
                        $shop = $stmt->fetch();
                        
                        if ($shop) {
                            debugLog("Magasin en session trouvé: " . $shop['name']);
                            // Connexion à la base de données du magasin
                            $shop_config = [
                                'host' => $shop['db_host'],
                                'port' => $shop['db_port'],
                                'dbname' => $shop['db_name'],
                                'user' => $shop['db_user'],
                                'pass' => $shop['db_pass']
                            ];
                            
                            debugLog("Tentative de connexion à la DB du magasin en session");
                            $pdo = connectToShopDB($shop_config);
                            
                            if ($pdo === null) {
                                debugLog("ÉCHEC de connexion à la DB du magasin en session");
                                error_log("Échec de la connexion à la base de données du magasin en session: " . $shop['name']);
                                throw new PDOException("La connexion à la base de données du magasin a échoué");
                            } else {
                                debugLog("Connexion réussie à la DB du magasin en session");
                            }
                        } else {
                            debugLog("Magasin en session non trouvé ou inactif");
                            throw new Exception("Magasin en session non trouvé ou inactif");
                        }
                    } else {
                        debugLog("Pas de magasin sélectionné ni en session, utilisation de la base principale");
                        $pdo = $pdo_main;
                    }
                }
                
            // Vérifier que la connexion à la base de données est disponible
            if ($pdo === null) {
                    debugLog("Connexion à la base de données non disponible");
                throw new PDOException("La connexion à la base de données n'est pas disponible");
            }
            
                // Vérifier les identifiants utilisateur normaux
                debugLog("Recherche de l'utilisateur: " . $username);
                // Vérifier si le username est un email
                if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                    debugLog("Tentative de connexion par email");
                    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
                } else {
                    debugLog("Tentative de connexion par nom d'utilisateur");
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
                }
            $stmt->execute([$username]);
            $user = $stmt->fetch();

                if ($user) {
                    debugLog("Utilisateur trouvé, ID: " . $user['id'] . ", vérification du mot de passe");
                    if (password_verify($password, $user['password'])) {
                        debugLog("Authentification utilisateur réussie");
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Si option "Se souvenir de moi" est cochée ou si c'est un mode PWA
                if ($remember || is_pwa_mode() || isset($_COOKIE['pwa_mode'])) {
                            debugLog("Option 'Se souvenir de moi' active");
                    // Définir un cookie pour indiquer que c'est une session PWA
                    $session_lifetime = 259200; // 3 jours
                    setcookie('pwa_mode', 'true', time() + $session_lifetime, '/', '', isset($_SERVER['HTTPS']), true);
                    
                    // Stocker le token de session dans un cookie pour une connexion persistante
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + $session_lifetime;
                    
                    // Stocker le token dans la base de données
                    $stmt = $pdo->prepare('INSERT INTO user_sessions (user_id, token, expiry) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expiry = ?');
                    $stmt->execute([$user['id'], $token, date('Y-m-d H:i:s', $expiry), $token, date('Y-m-d H:i:s', $expiry)]);
                    
                    // Définir le cookie de session persistante
                    setcookie('mdgeek_remember', $token, $expiry, '/', '', isset($_SERVER['HTTPS']), true);
                }
                
                // Débogage
                        debugLog("Connexion réussie, redirection vers l'accueil");
                error_log("Connexion réussie pour l'utilisateur : " . $username);
                
                // Redirection vers la page d'accueil
                header('Location: /index.php');
                exit();
            } else {
                        debugLog("Mot de passe incorrect pour: " . $username);
                        $error = 'Identifiants incorrects';
                        error_log("Tentative de connexion échouée pour l'utilisateur : " . $username);
                    }
                } else {
                    debugLog("Utilisateur non trouvé: " . $username);
                $error = 'Identifiants incorrects';
                error_log("Tentative de connexion échouée pour l'utilisateur : " . $username);
                }
            }
        } catch (PDOException $e) {
            debugLog("Erreur PDO: " . $e->getMessage());
            // Au lieu d'afficher un message d'erreur, rediriger vers la page d'accueil
            error_log("Erreur PDO : " . $e->getMessage() . " - Redirection automatique");
            
            // Définir l'utilisateur comme connecté quand même, si l'authentification a fonctionné
            if (isset($user) && $user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                debugLog("Redirection automatique malgré l'erreur de BDD");
            }
            
            // Redirection vers la page d'accueil
            header('Location: /index.php');
            exit();
        } catch (Exception $e) {
            debugLog("Exception: " . $e->getMessage());
            // Au lieu d'afficher un message d'erreur, rediriger vers la page d'accueil
            error_log("Exception : " . $e->getMessage() . " - Redirection automatique");
            
            // Redirection vers la page d'accueil
            header('Location: /index.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - GeekBoard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-effects.css">
    <style>
        :root {
            /* Variables pour le thème sombre */
            --dark-primary-color: #0078e8;
            --dark-secondary-color: #37a1ff;
            --dark-accent-color: #00f7ff;
            --dark-bg-color-1: #0a1929;
            --dark-bg-color-2: #1a3654;
            --dark-text-color: #f0f9ff;
            --dark-card-bg: rgba(255, 255, 255, 0.1);
            --dark-input-bg: rgba(255, 255, 255, 0.1);
            --dark-border-color: rgba(255, 255, 255, 0.2);
            
            /* Variables pour le thème clair */
            --light-primary-color: #0062be;
            --light-secondary-color: #4295e3;
            --light-accent-color: #00b7c3;
            --light-bg-color-1: #e9f5ff;
            --light-bg-color-2: #c5e1fb;
            --light-text-color: #0a1929;
            --light-card-bg: rgba(255, 255, 255, 0.9);
            --light-input-bg: rgba(255, 255, 255, 0.7);
            --light-border-color: rgba(0, 98, 190, 0.2);
            
            /* Variables actives (par défaut sombre) */
            --primary-color: var(--dark-primary-color);
            --secondary-color: var(--dark-secondary-color);
            --accent-color: var(--dark-accent-color);
            --bg-color-1: var(--dark-bg-color-1);
            --bg-color-2: var(--dark-bg-color-2);
            --text-color: var(--dark-text-color);
            --card-bg: var(--dark-card-bg);
            --input-bg: var(--dark-input-bg);
            --border-color: var(--dark-border-color);
        }
        
        /* Styles pour le thème clair */
        body.light-theme {
            --primary-color: var(--light-primary-color);
            --secondary-color: var(--light-secondary-color);
            --accent-color: var(--light-accent-color);
            --bg-color-1: var(--light-bg-color-1);
            --bg-color-2: var(--light-bg-color-2);
            --text-color: var(--light-text-color);
            --card-bg: var(--light-card-bg);
            --input-bg: var(--light-input-bg);
            --border-color: var(--light-border-color);
        }
        
        body {
            background: linear-gradient(135deg, var(--bg-color-1) 0%, var(--bg-color-2) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-color);
            position: relative;
            overflow: hidden;
            transition: all 0.5s ease;
        }
        
        /* Effet de particules lumineux */
        body::before, body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, var(--accent-color) 0%, rgba(0,247,255,0) 70%);
            opacity: 0.1;
            z-index: 0;
            animation: floating 15s infinite linear;
        }
        
        body::before {
            top: -150px;
            left: -100px;
            animation-duration: 20s;
        }
        
        body::after {
            bottom: -150px;
            right: -100px;
            animation-duration: 25s;
            animation-delay: 5s;
        }
        
        @keyframes floating {
            0% { transform: translate(0, 0); }
            25% { transform: translate(50px, 50px); }
            50% { transform: translate(0, 100px); }
            75% { transform: translate(-50px, 50px); }
            100% { transform: translate(0, 0); }
        }
        
        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2), 
                        0 0 0 1px rgba(255, 255, 255, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            z-index: 10;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
            animation: cardPulse 2s infinite alternate;
            transition: all 0.5s ease;
        }
        
        .light-theme .login-card {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1), 
                        0 0 0 1px rgba(0, 0, 0, 0.05);
        }
        
        @keyframes cardPulse {
            0% { box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2), 0 0 0 1px var(--border-color); }
            100% { box-shadow: 0 20px 45px rgba(0, 0, 0, 0.3), 0 0 0 1px var(--border-color), 0 0 15px rgba(var(--accent-color), 0.3); }
        }
        
        /* Effet de bord lumineux */
        .login-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, var(--accent-color), transparent, var(--secondary-color), transparent, var(--accent-color));
            z-index: -1;
            border-radius: 22px;
            opacity: 0.3;
            animation: border-animation 5s linear infinite;
        }
        
        .light-theme .login-card::before {
            opacity: 0.15;
        }
        
        @keyframes border-animation {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }
        
        .login-header img {
            width: 120px;
            height: auto;
            margin-bottom: 1rem;
            filter: drop-shadow(0 0 10px rgba(0, 247, 255, 0.6));
            animation: logoFloat 3s ease-in-out infinite;
            transition: filter 0.5s ease;
        }
        
        .light-theme .login-header img {
            filter: drop-shadow(0 0 8px rgba(0, 150, 190, 0.4));
        }
        
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .text-gradient-primary {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            font-weight: 700;
            font-size: 2.5rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: background 0.5s ease;
        }
        
        .form-label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
            transition: color 0.5s ease;
        }
        
        .form-control, .form-select {
            background: var(--input-bg);
            backdrop-filter: blur(5px);
            border-radius: 12px;
            padding: 0.9rem 1.2rem;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .light-theme .form-control, .light-theme .form-select {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }
        
        .form-control::placeholder, .form-select::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .light-theme .form-control::placeholder, .light-theme .form-select::placeholder {
            color: rgba(10, 25, 41, 0.5);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 247, 255, 0.25), 0 5px 10px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .light-theme .form-control:focus, .light-theme .form-select:focus {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 0 0.25rem rgba(0, 183, 195, 0.25), 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .input-group-text {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid var(--border-color);
            border-radius: 12px 0 0 12px !important;
            color: var(--accent-color);
            transition: all 0.5s ease;
        }
        
        .light-theme .input-group-text {
            background: rgba(255, 255, 255, 0.5);
            color: var(--primary-color);
        }
        
        .input-group .form-control {
            border-radius: 0 12px 12px 0;
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--accent-color) 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #000;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 247, 255, 0.3);
        }
        
        .light-theme .btn-login {
            box-shadow: 0 5px 15px rgba(0, 98, 190, 0.3);
            color: #fff;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 247, 255, 0.5);
        }
        
        .light-theme .btn-login:hover {
            box-shadow: 0 8px 25px rgba(0, 98, 190, 0.5);
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(45deg);
            animation: btn-shine 3s infinite linear;
        }
        
        @keyframes btn-shine {
            0% { transform: translateX(-100%) rotate(45deg); }
            100% { transform: translateX(100%) rotate(45deg); }
        }
        
        .alert {
            border-radius: 12px;
            margin-bottom: 1.5rem;
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ffcccc;
            backdrop-filter: blur(5px);
            transition: all 0.5s ease;
        }
        
        .light-theme .alert {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        
        .alert-info {
            background: rgba(13, 202, 240, 0.2);
            border: 1px solid rgba(13, 202, 240, 0.3);
            color: #cdffff;
        }
        
        .light-theme .alert-info {
            background: rgba(13, 202, 240, 0.1);
            border: 1px solid rgba(13, 202, 240, 0.2);
            color: #0dcaf0;
        }
        
        .alert-warning {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #fffbdb;
        }
        
        .light-theme .alert-warning {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.2);
            color: #664d03;
        }
        
        .shop-logo {
            max-width: 80px;
            max-height: 40px;
            margin-right: 10px;
        }
        
        a {
            color: var(--accent-color);
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .light-theme a {
            color: var(--primary-color);
        }
        
        a:hover {
            color: white;
            text-shadow: 0 0 10px var(--accent-color);
        }
        
        .light-theme a:hover {
            color: var(--accent-color);
            text-shadow: 0 0 10px rgba(0, 98, 190, 0.3);
        }
        
        .mb-3, .mb-4 {
            position: relative;
            z-index: 2;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0); }
            25% { transform: translate(15px, 15px); }
            50% { transform: translate(0, 30px); }
            75% { transform: translate(-15px, 15px); }
            100% { transform: translate(0, 0); }
        }
        
        .input-glow {
            box-shadow: 0 0 15px rgba(0, 247, 255, 0.5) !important;
        }
        
        .light-theme .input-glow {
            box-shadow: 0 0 15px rgba(0, 98, 190, 0.3) !important;
        }
        
        /* Classe pour le texte qui s'adapte au thème */
        .text-theme {
            color: var(--text-color);
            opacity: 0.75;
            transition: color 0.5s ease;
        }
        
        /* Bouton de changement de thème */
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 100;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .theme-toggle i {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Bouton de changement de thème -->
    <div class="theme-toggle" id="theme-toggle">
        <i class="fas fa-sun"></i>
    </div>

    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh; width: 100%;">
        <div class="login-card mx-auto">
            <div class="login-header">
                <img src="../assets/images/logo/logodarkmode.png" alt="GeekBoard Logo" id="login-logo" class="mb-4">
                <h2 class="text-gradient-primary">GeekBoard</h2>
                <p class="mt-2 text-theme">Interface de contrôle futuriste</p>
            </div>

            <?php if ($error): ?>
                <div class="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . ($superadmin_mode ? '?superadmin=1' : '')); ?>">
                <?php if (count($shopList) > 0 && !$superadmin_mode): ?>
                <div class="mb-4">
                    <label for="shop_id" class="form-label">
                        <i class="fas fa-store me-2"></i>Votre magasin
                    </label>
                    <select class="form-select" id="shop_id" name="shop_id" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($shopList as $shop): ?>
                        <option value="<?php echo $shop['id']; ?>" <?php echo (isset($_SESSION['shop_id']) && $_SESSION['shop_id'] == $shop['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($shop['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php elseif ($superadmin_mode): ?>
                <div class="mb-4 text-center">
                    <div class="alert alert-warning">
                        <i class="fas fa-user-shield me-2"></i>
                        Mode <strong>Super Administrateur</strong>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <label for="username" class="form-label">
                        <i class="fas fa-user-astronaut me-2"></i>Identifiant
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-at"></i>
                        </span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Nom d'utilisateur ou email" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-key me-2"></i>Mot de passe
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                        <label class="form-check-label" for="remember">
                            <i class="fas fa-fingerprint me-2"></i>Se souvenir de moi
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100 mb-4">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Authentification
                </button>
                
                <?php if (isset($_SESSION['shop_id'])): ?>
                <!-- Suppression du lien "Changer de magasin" car le menu déroulant est toujours visible -->
                <?php endif; ?>
                
                <!-- Ajout du lien pour les superadmins -->
                <div class="mt-4 text-center">
                    <hr class="my-3" style="border-color: rgba(255,255,255,0.1);">
                    <a href="login.php?superadmin=1" class="text-decoration-none">
                        <i class="fas fa-user-shield me-1"></i> Accès administrateur système
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const body = document.querySelector('body');
            const themeToggle = document.getElementById('theme-toggle');
            const loginLogo = document.getElementById('login-logo');
            const toggleIcon = themeToggle.querySelector('i');
            
            // Vérifier si un thème est stocké dans localStorage
            const savedTheme = localStorage.getItem('geekboard-theme');
            
            // Vérifier le thème système préféré
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            // Appliquer le thème sauvegardé ou le thème système par défaut
            if (savedTheme === 'light') {
                body.classList.add('light-theme');
                toggleIcon.className = 'fas fa-moon';
                loginLogo.src = "../assets/images/logo/logo.png"; // Version claire du logo
            } else if (savedTheme === 'dark' || prefersDarkScheme) {
                body.classList.remove('light-theme');
                toggleIcon.className = 'fas fa-sun';
                loginLogo.src = "../assets/images/logo/logodarkmode.png"; // Version sombre du logo
            } else {
                // Par défaut, thème clair si aucune préférence
                body.classList.add('light-theme');
                toggleIcon.className = 'fas fa-moon';
                loginLogo.src = "../assets/images/logo/logo.png"; // Version claire du logo
            }
            
            // Fonction pour basculer le thème
            themeToggle.addEventListener('click', function() {
                if (body.classList.contains('light-theme')) {
                    // Passer au thème sombre
                    body.classList.remove('light-theme');
                    toggleIcon.className = 'fas fa-sun';
                    loginLogo.src = "../assets/images/logo/logodarkmode.png";
                    localStorage.setItem('geekboard-theme', 'dark');
                } else {
                    // Passer au thème clair
                    body.classList.add('light-theme');
                    toggleIcon.className = 'fas fa-moon';
                    loginLogo.src = "../assets/images/logo/logo.png";
                    localStorage.setItem('geekboard-theme', 'light');
                }
            });
            
            // Animation de particules pour effet futuriste
            for (let i = 0; i < 15; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                const randomSize = Math.random() * 5 + 2;
                const randomOpacity = Math.random() * 0.5 + 0.2;
                const randomTop = Math.random() * 100;
                const randomLeft = Math.random() * 100;
                const randomDuration = Math.random() * 10 + 10;
                
                particle.style.cssText = `
                    position: absolute;
                    width: ${randomSize}px;
                    height: ${randomSize}px;
                    background: rgba(0, 247, 255, ${randomOpacity});
                    border-radius: 50%;
                    top: ${randomTop}vh;
                    left: ${randomLeft}vw;
                    box-shadow: 0 0 10px 2px rgba(0, 247, 255, 0.4);
                    animation: float ${randomDuration}s linear infinite;
                    z-index: 1;
                    transition: background 0.5s ease, box-shadow 0.5s ease;
                `;
                
                // Mise à jour des couleurs des particules lors du changement de thème
                body.appendChild(particle);
                
                // Ajuster les particules en fonction du thème
                function updateParticleTheme() {
                    if (body.classList.contains('light-theme')) {
                        particle.style.background = `rgba(0, 150, 190, ${randomOpacity})`;
                        particle.style.boxShadow = '0 0 10px 2px rgba(0, 150, 190, 0.3)';
                    } else {
                        particle.style.background = `rgba(0, 247, 255, ${randomOpacity})`;
                        particle.style.boxShadow = '0 0 10px 2px rgba(0, 247, 255, 0.4)';
                    }
                }
                
                // Appliquer initialement
                updateParticleTheme();
                
                // Mettre à jour à chaque changement de thème
                themeToggle.addEventListener('click', updateParticleTheme);
            }
            
            // Fonction pour les effets de focus
            const inputs = document.querySelectorAll('.form-control, .form-select');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('input-glow');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('input-glow');
                });
            });
        });
    </script>
    
    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
    <div style="position: fixed; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.8); color: #fff; padding: 10px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
        <h4>Informations de débogage</h4>
        <p>Session ID: <?php echo session_id(); ?></p>
        
        <?php if (isset($_SESSION['debug_messages']) && is_array($_SESSION['debug_messages'])): ?>
            <ul>
                <?php foreach($_SESSION['debug_messages'] as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aucun message de débogage disponible.</p>
        <?php endif; ?>
        
        <h4>Variables de session</h4>
        <pre><?php print_r($_SESSION); ?></pre>
        
        <?php if (isset($_SESSION['shop_id'])): ?>
            <h4>Connexions de base de données</h4>
            <?php
            $pdo_main = getMainDBConnection();
            echo '<p>Connexion base principale: ' . ($pdo_main ? 'OK' : 'ÉCHEC') . '</p>';
            
            // Récupérer les informations du magasin
            $stmt = $pdo_main->prepare("SELECT * FROM shops WHERE id = ?");
            $stmt->execute([$_SESSION['shop_id']]);
            $shop = $stmt->fetch();
            
            if ($shop) {
                echo '<p>Magasin trouvé: ' . htmlspecialchars($shop['name']) . '</p>';
                
                $shop_config = [
                    'host' => $shop['db_host'],
                    'port' => $shop['db_port'],
                    'dbname' => $shop['db_name'],
                    'user' => $shop['db_user'],
                    'pass' => $shop['db_pass']
                ];
                
                $shop_db = connectToShopDB($shop_config);
                echo '<p>Connexion base du magasin: ' . ($shop_db ? 'OK' : 'ÉCHEC') . '</p>';
            } else {
                echo '<p>Magasin non trouvé pour ID: ' . $_SESSION['shop_id'] . '</p>';
            }
            ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</body>
</html>