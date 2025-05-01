<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure la configuration de session avant de démarrer la session
require_once '../config/session_config.php';
// La session est déjà démarrée dans session_config.php
require_once '../config/database.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] == '1';

    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        try {
            // Vérifier que la connexion à la base de données est disponible
            if ($pdo === null) {
                throw new PDOException("La connexion à la base de données n'est pas disponible");
            }
            
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                // Si option "Se souvenir de moi" est cochée ou si c'est un mode PWA
                if ($remember || is_pwa_mode() || isset($_COOKIE['pwa_mode'])) {
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
                error_log("Connexion réussie pour l'utilisateur : " . $username);
                
                // Redirection vers la page d'accueil
                header('Location: /index.php');
                exit();
            } else {
                $error = 'Identifiants incorrects';
                error_log("Tentative de connexion échouée pour l'utilisateur : " . $username);
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion à la base de données';
            error_log("Erreur PDO : " . $e->getMessage());
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
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .form-control:focus {
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

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
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
</body>
</html>