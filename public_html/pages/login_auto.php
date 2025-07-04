<?php
require_once __DIR__ . "/../config/session_config.php";

// Détecter le magasin
$shop_subdomain = $_SERVER["SHOP_SUBDOMAIN"] ?? "unknown";
$host = $_SERVER["HTTP_HOST"] ?? "";

// Mapping des sous-domaines vers les infos de magasin
$shop_mapping = [
    'pscannes' => ['id' => 2, 'name' => 'PScannes', 'db' => 'geekboard_pscannes'],
    'psphonac' => ['id' => 6, 'name' => 'PSPHONAC', 'db' => 'geekboard_psphonac'], 
    'cannesphones' => ['id' => 4, 'name' => 'CannesPhones', 'db' => 'geekboard_cannesphones'],
    'testgeek' => ['id' => 30, 'name' => 'test_geek', 'db' => 'geekboard_testgeek'],
    'testdb' => ['id' => 31, 'name' => 'test_db', 'db' => 'geekboard_testdb'],
    'testdb2' => ['id' => 32, 'name' => 'testdb2', 'db' => 'geekboard_testdb2'],
    'testdb3' => ['id' => 33, 'name' => 'testdb3', 'db' => 'geekboard_testdb3'],
    'testdb4' => ['id' => 34, 'name' => 'testdb4', 'db' => 'geekboard_testdb4'],
    'ips1' => ['id' => 35, 'name' => 'ips1', 'db' => 'geekboard_ips1'],
    'ips5' => ['id' => 36, 'name' => 'ips5', 'db' => 'geekboard_ips5'],
    'ips6' => ['id' => 37, 'name' => 'ips6', 'db' => 'geekboard_ips6'],
    'ips7' => ['id' => 38, 'name' => 'ips7', 'db' => 'geekboard_ips7'],
    'ips8' => ['id' => 39, 'name' => 'ips8', 'db' => 'geekboard_ips8'],
    'ls9' => ['id' => 40, 'name' => 'ls9', 'db' => 'geekboard_ls9'],
    'sk' => ['id' => 41, 'name' => 'sk', 'db' => 'geekboard_sk'],
    'sk9' => ['id' => 42, 'name' => 'sk9', 'db' => 'geekboard_sk9'],
    'krol' => ['id' => 43, 'name' => 'krol', 'db' => 'geekboard_krol'],
    'klopl' => ['id' => 44, 'name' => 'klopl', 'db' => 'geekboard_klopl'],
    'klopll' => ['id' => 45, 'name' => 'klopll', 'db' => 'geekboard_klopll'],
    'klopll2' => ['id' => 46, 'name' => 'klopll2', 'db' => 'geekboard_klopll2'],
    'klplp' => ['id' => 47, 'name' => 'klplp', 'db' => 'geekboard_klplp'],
    'klplpasd' => ['id' => 48, 'name' => 'klplpasd', 'db' => 'geekboard_klplpasd'],
    'klplpasdasd' => ['id' => 49, 'name' => 'klplpasdasd', 'db' => 'geekboard_klplpasdasd'],
    'asdadsdas' => ['id' => 50, 'name' => 'asdadsdas', 'db' => 'geekboard_asdadsdas'],
    'asdadsdass' => ['id' => 51, 'name' => 'asdadsdass', 'db' => 'geekboard_asdadsdass'],
    'wsa' => ['id' => 52, 'name' => 'wsa', 'db' => 'geekboard_wsa'],
    'asdqe' => ['id' => 53, 'name' => 'asdqe', 'db' => 'geekboard_asdqe'],
    'kliop' => ['id' => 54, 'name' => 'kliop', 'db' => 'geekboard_kliop']
];

$current_shop = $shop_mapping[$shop_subdomain] ?? null;
$error = '';
$success = '';

// Traitement du formulaire
if ($_POST && $current_shop) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username && $password) {
        try {
            // Connexion à la base du magasin
            $pdo = new PDO("mysql:host=localhost;dbname=" . $current_shop['db'], 'geekboard_user', 'GeekBoard2024#');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Vérifier les identifiants avec la bonne structure de table users
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
                $_SESSION['shop_subdomain'] = $shop_subdomain;
                
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
            <div style="text-center; color: #dc3545;">
                <h3>❌ Magasin non reconnu</h3>
                <p><strong>Sous-domaine détecté:</strong> <code><?php echo htmlspecialchars($shop_subdomain); ?></code></p>
                <p><strong>Domaine complet:</strong> <code><?php echo htmlspecialchars($host); ?></code></p>
                <p style="color: #666;">Veuillez contacter l'administrateur.</p>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
            <small style="color: #888;">
                🚀 <strong>Détection automatique active</strong><br>
                Sans menu déroulant - Connexion directe !
            </small>
        </div>
    </div>
</body>
</html>
