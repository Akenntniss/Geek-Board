<?php
require_once __DIR__ . "/../config/session_config.php";
require_once __DIR__ . "/../config/subdomain_database_detector.php";

$error = '';
$success = '';
$current_shop = null;

try {
    // Utiliser notre nouveau système de détection
    $detector = new SubdomainDatabaseDetector();
    $subdomain = $detector->detectSubdomain();
    $shop_info = $detector->getCurrentShopInfo();
    
    if ($shop_info) {
        $current_shop = [
            'id' => $shop_info['id'],
            'name' => $shop_info['name'],
            'subdomain' => $shop_info['subdomain'],
            'db' => $shop_info['db_name']
        ];
    }
    
} catch (Exception $e) {
    $error = "Erreur de détection du magasin: " . $e->getMessage();
}

$host = $_SERVER["HTTP_HOST"] ?? "";

// Traitement du formulaire
if ($_POST && $current_shop) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username && $password) {
        try {
            // Connexion à la base du magasin avec les bons identifiants
            $pdo = new PDO(
                "mysql:host=localhost;dbname=" . $current_shop['db'], 
                'root', 
                'Mamanmaman01#'
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Vérifier les identifiants
            $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE username = ? AND password = MD5(?) AND role IN ('admin', 'technicien') LIMIT 1");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['shop_id'] = $current_shop['id'];
                $_SESSION['shop_name'] = $current_shop['name'];
                $_SESSION['shop_subdomain'] = $current_shop['subdomain'];
                $_SESSION['current_database'] = $current_shop['db'];
                
                $success = "Connexion réussie ! Redirection...";
                // Redirection JavaScript pour éviter les problèmes d'en-têtes
                echo "<script>setTimeout(function(){ window.location.href = '/'; }, 1500);</script>";
            } else {
                $error = "Identifiants incorrects pour le magasin " . $current_shop['name'];
            }
        } catch (Exception $e) {
            $error = "Erreur de connexion à la base " . $current_shop['db'] . ": " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Connexion - <?php echo $current_shop ? $current_shop['name'] : 'GeekBoard'; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0;">
    <div style="max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #333; margin: 0;">🏪 GeekBoard</h1>
            <h2 style="color: #666; font-size: 18px; margin: 10px 0;">Connexion</h2>
        </div>
        
        <?php if ($current_shop): ?>
            <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;">
                <p style="margin: 5px 0;"><strong>✅ Magasin:</strong> <?php echo htmlspecialchars($current_shop['name']); ?></p>
                <p style="margin: 5px 0;"><strong>🌐 Domaine:</strong> <?php echo htmlspecialchars($host); ?></p>
                <p style="margin: 5px 0;"><strong>💾 Base:</strong> <?php echo htmlspecialchars($current_shop['db']); ?></p>
                <p style="margin: 5px 0;"><strong>🎯 ID:</strong> <?php echo htmlspecialchars($current_shop['id']); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #dc3545;">
                    <strong>❌ Erreur:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745;">
                    <strong>✅ Succès:</strong> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" style="margin-top: 20px;">
                <div style="margin: 15px 0;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">Nom d'utilisateur:</label>
                    <input type="text" name="username" placeholder="Votre nom d'utilisateur" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box;" required>
                </div>
                
                <div style="margin: 15px 0;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">Mot de passe:</label>
                    <input type="password" name="password" placeholder="••••••••" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box;" required>
                </div>
                
                <button type="submit" style="width: 100%; padding: 15px; background: #007bff; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#0056b3'" onmouseout="this.style.background='#007bff'">
                    🔐 Se connecter à <?php echo htmlspecialchars($current_shop['name']); ?>
                </button>
            </form>
            
        <?php else: ?>
            <div style="text-align: center; color: #dc3545;">
                <h3>❌ Magasin non reconnu</h3>
                <p><strong>Sous-domaine détecté:</strong> <code><?php echo htmlspecialchars($subdomain ?? 'inconnu'); ?></code></p>
                <p><strong>Domaine complet:</strong> <code><?php echo htmlspecialchars($host); ?></code></p>
                <p style="color: #666;">Veuillez contacter l'administrateur.</p>
                
                <?php if ($error): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: left;">
                        <strong>Détails de l'erreur:</strong><br>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
            <small style="color: #888;">
                🚀 <strong>Détection automatique dynamique</strong><br>
                Support multi-magasin complet !
            </small>
        </div>
    </div>
</body>
</html> 
require_once __DIR__ . "/../config/session_config.php";
require_once __DIR__ . "/../config/subdomain_database_detector.php";

$error = '';
$success = '';
$current_shop = null;

try {
    // Utiliser notre nouveau système de détection
    $detector = new SubdomainDatabaseDetector();
    $subdomain = $detector->detectSubdomain();
    $shop_info = $detector->getCurrentShopInfo();
    
    if ($shop_info) {
        $current_shop = [
            'id' => $shop_info['id'],
            'name' => $shop_info['name'],
            'subdomain' => $shop_info['subdomain'],
            'db' => $shop_info['db_name']
        ];
    }
    
} catch (Exception $e) {
    $error = "Erreur de détection du magasin: " . $e->getMessage();
}

$host = $_SERVER["HTTP_HOST"] ?? "";

// Traitement du formulaire
if ($_POST && $current_shop) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username && $password) {
        try {
            // Connexion à la base du magasin avec les bons identifiants
            $pdo = new PDO(
                "mysql:host=localhost;dbname=" . $current_shop['db'], 
                'root', 
                'Mamanmaman01#'
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Vérifier les identifiants
            $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE username = ? AND password = MD5(?) AND role IN ('admin', 'technicien') LIMIT 1");
            $stmt->execute([$username, $password]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Connexion réussie
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['shop_id'] = $current_shop['id'];
                $_SESSION['shop_name'] = $current_shop['name'];
                $_SESSION['shop_subdomain'] = $current_shop['subdomain'];
                $_SESSION['current_database'] = $current_shop['db'];
                
                $success = "Connexion réussie ! Redirection...";
                // Redirection JavaScript pour éviter les problèmes d'en-têtes
                echo "<script>setTimeout(function(){ window.location.href = '/'; }, 1500);</script>";
            } else {
                $error = "Identifiants incorrects pour le magasin " . $current_shop['name'];
            }
        } catch (Exception $e) {
            $error = "Erreur de connexion à la base " . $current_shop['db'] . ": " . $e->getMessage();
        }
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Connexion - <?php echo $current_shop ? $current_shop['name'] : 'GeekBoard'; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0;">
    <div style="max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #333; margin: 0;">🏪 GeekBoard</h1>
            <h2 style="color: #666; font-size: 18px; margin: 10px 0;">Connexion</h2>
        </div>
        
        <?php if ($current_shop): ?>
            <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;">
                <p style="margin: 5px 0;"><strong>✅ Magasin:</strong> <?php echo htmlspecialchars($current_shop['name']); ?></p>
                <p style="margin: 5px 0;"><strong>🌐 Domaine:</strong> <?php echo htmlspecialchars($host); ?></p>
                <p style="margin: 5px 0;"><strong>💾 Base:</strong> <?php echo htmlspecialchars($current_shop['db']); ?></p>
                <p style="margin: 5px 0;"><strong>🎯 ID:</strong> <?php echo htmlspecialchars($current_shop['id']); ?></p>
            </div>
            
            <?php if ($error): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #dc3545;">
                    <strong>❌ Erreur:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #28a745;">
                    <strong>✅ Succès:</strong> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" style="margin-top: 20px;">
                <div style="margin: 15px 0;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">Nom d'utilisateur:</label>
                    <input type="text" name="username" placeholder="Votre nom d'utilisateur" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box;" required>
                </div>
                
                <div style="margin: 15px 0;">
                    <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #333;">Mot de passe:</label>
                    <input type="password" name="password" placeholder="••••••••" style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box;" required>
                </div>
                
                <button type="submit" style="width: 100%; padding: 15px; background: #007bff; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.3s;" onmouseover="this.style.background='#0056b3'" onmouseout="this.style.background='#007bff'">
                    🔐 Se connecter à <?php echo htmlspecialchars($current_shop['name']); ?>
                </button>
            </form>
            
        <?php else: ?>
            <div style="text-align: center; color: #dc3545;">
                <h3>❌ Magasin non reconnu</h3>
                <p><strong>Sous-domaine détecté:</strong> <code><?php echo htmlspecialchars($subdomain ?? 'inconnu'); ?></code></p>
                <p><strong>Domaine complet:</strong> <code><?php echo htmlspecialchars($host); ?></code></p>
                <p style="color: #666;">Veuillez contacter l'administrateur.</p>
                
                <?php if ($error): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: left;">
                        <strong>Détails de l'erreur:</strong><br>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
            <small style="color: #888;">
                🚀 <strong>Détection automatique dynamique</strong><br>
                Support multi-magasin complet !
            </small>
        </div>
    </div>
</body>
</html> 