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
require_once '../config/domain_config.php';

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
                $stmt = $pdo_main->prepare('SELECT * FROM superadmins WHERE username = ? AND active = 1');
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
                $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
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
        body {
            background: linear-gradient(135deg, #0078e8 0%, #37a1ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header img {
            width: 100px;
            height: auto;
            margin-bottom: 1rem;
        }
        .text-gradient-primary {
            background: linear-gradient(135deg, #0078e8, #37a1ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            transition: all 0.3s ease;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0078e8;
            box-shadow: 0 0 0 0.2rem rgba(0, 120, 232, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #0078e8 0%, #37a1ff 100%);
            border: none;
            border-radius: 8px;
            padding: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 120, 232, 0.3);
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .shop-logo {
            max-width: 80px;
            max-height: 40px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh; width: 100%;">
        <div class="login-card mx-auto">
            <div class="login-header">
                <img src="../assets/images/logo/logodarkmode.png" alt="GeekBoard Logo" id="login-logo">
                <h2 class="text-gradient-primary">GeekBoard</h2>
                <p class="text-muted">Connectez-vous à votre compte</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . ($superadmin_mode ? '?superadmin=1' : '')); ?>">
                <?php if (count($shopList) > 0 && !isset($_SESSION['shop_id']) && !$superadmin_mode): ?>
                <div class="mb-3">
                    <label for="shop_id" class="form-label">Sélectionnez votre magasin</label>
                    <select class="form-select" id="shop_id" name="shop_id" required>
                        <option value="">-- Choisir un magasin --</option>
                        <?php foreach ($shopList as $shop): ?>
                        <option value="<?php echo $shop['id']; ?>" <?php echo (isset($_SESSION['shop_id']) && $_SESSION['shop_id'] == $shop['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($shop['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php elseif (isset($_SESSION['shop_id']) && isset($_SESSION['shop_name']) && !$superadmin_mode): ?>
                <div class="mb-3 text-center">
                    <div class="alert alert-info">
                        <i class="fas fa-store me-2"></i>
                        Connexion au magasin: <strong><?php echo htmlspecialchars($_SESSION['shop_name']); ?></strong>
                        <?php
                        // Vérifier si on est sur un sous-domaine
                        $subdomain = getCurrentSubdomain();
                        if ($subdomain && !isSystemSubdomain($subdomain)):
                        ?>
                        <br><small>(Détecté par votre sous-domaine: <?php echo htmlspecialchars($subdomain); ?>.<?php echo MAIN_DOMAIN; ?>)</small>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="shop_id" value="<?php echo $_SESSION['shop_id']; ?>">
                </div>
                <?php elseif ($superadmin_mode): ?>
                <div class="mb-3 text-center">
                    <div class="alert alert-warning">
                        <i class="fas fa-user-shield me-2"></i>
                        Mode connexion <strong>Super Administrateur</strong>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="remember" class="form-label">
                        <input type="checkbox" id="remember" name="remember">
                        Se souvenir de moi
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Se connecter
                </button>
                
                <?php if (isset($_SESSION['shop_id'])): ?>
                <div class="mt-3 text-center">
                    <a href="login.php" class="text-decoration-none">
                        <i class="fas fa-exchange-alt me-1"></i> Changer de magasin
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Ajout du lien pour les superadmins -->
                <div class="mt-3 text-center">
                    <hr class="my-2">
                    <a href="login.php?superadmin=1" class="text-decoration-none">
                        <i class="fas fa-user-shield me-1"></i> Connexion superadministrateur
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // On utilise toujours la version darkmode du logo, indépendamment du thème
        document.addEventListener('DOMContentLoaded', function() {
            const loginLogo = document.getElementById('login-logo');
            
            // Toujours afficher le logo darkmode
            if (loginLogo) {
                loginLogo.src = "../assets/images/logo/logodarkmode.png";
            }
            
            // Ne pas changer le logo même en cas de changement de thème
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
                if (loginLogo) {
                    loginLogo.src = "../assets/images/logo/logodarkmode.png";
                }
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