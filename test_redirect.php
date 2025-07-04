<?php
/**
 * Script pour tester les redirections et diagnostiquer le problème
 */

// Capturer toute sortie
ob_start();

echo "<h1>Test des Redirections</h1>";

$host = $_SERVER['HTTP_HOST'] ?? 'non défini';
echo "<p><strong>HOST:</strong> $host</p>";

// Simuler les conditions d'index.php
echo "<h2>1. Simulation des conditions d'index.php</h2>";

// Inclure les configs comme index.php
try {
    require_once '/var/www/mdgeek.top/config/session_config.php';
    echo "✅ session_config.php inclus<br>";
} catch (Exception $e) {
    echo "❌ Erreur session_config.php: " . $e->getMessage() . "<br>";
}

try {
    require_once '/var/www/mdgeek.top/config/subdomain_config.php';
    echo "✅ subdomain_config.php inclus<br>";
} catch (Exception $e) {
    echo "❌ Erreur subdomain_config.php: " . $e->getMessage() . "<br>";
}

// Vérifier les variables de session
echo "<h3>Variables de session:</h3>";
echo "<strong>user_id:</strong> " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NON DÉFINI') . "<br>";
echo "<strong>shop_id:</strong> " . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'NON DÉFINI') . "<br>";
echo "<strong>shop_name:</strong> " . (isset($_SESSION['shop_name']) ? $_SESSION['shop_name'] : 'NON DÉFINI') . "<br>";

$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';
echo "<strong>Page demandée:</strong> $page<br>";

// Simuler la logique de redirection d'index.php
echo "<h2>2. Test de la logique de redirection</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "❌ Utilisateur NON connecté - Redirection nécessaire<br>";
    
    $no_auth_pages = ['imprimer_etiquette', 'diagnostic_session', 'debug_fournisseurs'];
    
    if (in_array($page, $no_auth_pages)) {
        echo "✅ Page autorisée sans authentification: $page<br>";
    } else {
        echo "🔄 Redirection requise...<br>";
        
        // Détecter si on est sur un sous-domaine de magasin
        if (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
            $redirect_url = '/pages/login_auto.php';
            echo "<strong>URL de redirection (sous-domaine):</strong> $redirect_url<br>";
        } else {
            $redirect_url = '/pages/login.php';
            echo "<strong>URL de redirection (principal):</strong> $redirect_url<br>";
        }
        
        // Tester l'accessibilité de la page de redirection
        echo "<h3>Test d'accessibilité de la page de redirection:</h3>";
        $full_url = "https://$host$redirect_url";
        echo "<strong>URL complète:</strong> $full_url<br>";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<strong>Code HTTP:</strong> $http_code<br>";
        if ($http_code == 200) {
            echo "✅ Page de redirection accessible<br>";
        } else {
            echo "❌ Page de redirection NON accessible<br>";
        }
        
        // Tester la redirection réelle
        echo "<h3>Test de redirection réelle:</h3>";
        
        // Vérifier s'il y a déjà du contenu envoyé
        if (headers_sent($file, $line)) {
            echo "❌ PROBLÈME: Les headers ont déjà été envoyés depuis $file ligne $line<br>";
            echo "C'est pourquoi la redirection ne fonctionne pas !<br>";
        } else {
            echo "✅ Headers pas encore envoyés - Redirection possible<br>";
            
            // Ici on pourrait faire la vraie redirection, mais on va juste l'afficher
            echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Redirection qui serait exécutée:</strong><br>";
            echo "header('Location: $redirect_url');<br>";
            echo "exit();<br>";
            echo "</div>";
        }
    }
} else {
    echo "✅ Utilisateur connecté - Pas de redirection nécessaire<br>";
}

echo "<h2>3. Test manuel de redirection</h2>";
echo "<p>Cliquez sur les liens pour tester :</p>";
echo "<a href='/pages/login.php' target='_blank'>Test login.php</a><br>";
echo "<a href='/pages/login_auto.php' target='_blank'>Test login_auto.php</a><br>";
echo "<a href='/?page=accueil' target='_blank'>Test index.php?page=accueil</a><br>";

// Récupérer et afficher tout le contenu capturé
$content = ob_get_clean();
echo $content;

echo "<p><strong>Test terminé</strong> - " . date('Y-m-d H:i:s') . "</p>";
?> 
/**
 * Script pour tester les redirections et diagnostiquer le problème
 */

// Capturer toute sortie
ob_start();

echo "<h1>Test des Redirections</h1>";

$host = $_SERVER['HTTP_HOST'] ?? 'non défini';
echo "<p><strong>HOST:</strong> $host</p>";

// Simuler les conditions d'index.php
echo "<h2>1. Simulation des conditions d'index.php</h2>";

// Inclure les configs comme index.php
try {
    require_once '/var/www/mdgeek.top/config/session_config.php';
    echo "✅ session_config.php inclus<br>";
} catch (Exception $e) {
    echo "❌ Erreur session_config.php: " . $e->getMessage() . "<br>";
}

try {
    require_once '/var/www/mdgeek.top/config/subdomain_config.php';
    echo "✅ subdomain_config.php inclus<br>";
} catch (Exception $e) {
    echo "❌ Erreur subdomain_config.php: " . $e->getMessage() . "<br>";
}

// Vérifier les variables de session
echo "<h3>Variables de session:</h3>";
echo "<strong>user_id:</strong> " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NON DÉFINI') . "<br>";
echo "<strong>shop_id:</strong> " . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'NON DÉFINI') . "<br>";
echo "<strong>shop_name:</strong> " . (isset($_SESSION['shop_name']) ? $_SESSION['shop_name'] : 'NON DÉFINI') . "<br>";

$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';
echo "<strong>Page demandée:</strong> $page<br>";

// Simuler la logique de redirection d'index.php
echo "<h2>2. Test de la logique de redirection</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "❌ Utilisateur NON connecté - Redirection nécessaire<br>";
    
    $no_auth_pages = ['imprimer_etiquette', 'diagnostic_session', 'debug_fournisseurs'];
    
    if (in_array($page, $no_auth_pages)) {
        echo "✅ Page autorisée sans authentification: $page<br>";
    } else {
        echo "🔄 Redirection requise...<br>";
        
        // Détecter si on est sur un sous-domaine de magasin
        if (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
            $redirect_url = '/pages/login_auto.php';
            echo "<strong>URL de redirection (sous-domaine):</strong> $redirect_url<br>";
        } else {
            $redirect_url = '/pages/login.php';
            echo "<strong>URL de redirection (principal):</strong> $redirect_url<br>";
        }
        
        // Tester l'accessibilité de la page de redirection
        echo "<h3>Test d'accessibilité de la page de redirection:</h3>";
        $full_url = "https://$host$redirect_url";
        echo "<strong>URL complète:</strong> $full_url<br>";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<strong>Code HTTP:</strong> $http_code<br>";
        if ($http_code == 200) {
            echo "✅ Page de redirection accessible<br>";
        } else {
            echo "❌ Page de redirection NON accessible<br>";
        }
        
        // Tester la redirection réelle
        echo "<h3>Test de redirection réelle:</h3>";
        
        // Vérifier s'il y a déjà du contenu envoyé
        if (headers_sent($file, $line)) {
            echo "❌ PROBLÈME: Les headers ont déjà été envoyés depuis $file ligne $line<br>";
            echo "C'est pourquoi la redirection ne fonctionne pas !<br>";
        } else {
            echo "✅ Headers pas encore envoyés - Redirection possible<br>";
            
            // Ici on pourrait faire la vraie redirection, mais on va juste l'afficher
            echo "<div style='background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Redirection qui serait exécutée:</strong><br>";
            echo "header('Location: $redirect_url');<br>";
            echo "exit();<br>";
            echo "</div>";
        }
    }
} else {
    echo "✅ Utilisateur connecté - Pas de redirection nécessaire<br>";
}

echo "<h2>3. Test manuel de redirection</h2>";
echo "<p>Cliquez sur les liens pour tester :</p>";
echo "<a href='/pages/login.php' target='_blank'>Test login.php</a><br>";
echo "<a href='/pages/login_auto.php' target='_blank'>Test login_auto.php</a><br>";
echo "<a href='/?page=accueil' target='_blank'>Test index.php?page=accueil</a><br>";

// Récupérer et afficher tout le contenu capturé
$content = ob_get_clean();
echo $content;

echo "<p><strong>Test terminé</strong> - " . date('Y-m-d H:i:s') . "</p>";
?> 