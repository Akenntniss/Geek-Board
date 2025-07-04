<?php
/**
 * Version simplifiée d'index.php pour tester les redirections
 */

// Inclure les configurations
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';

// Définir la page demandée
$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Cas spéciaux qui ne nécessitent pas d'authentification
    $no_auth_pages = ['imprimer_etiquette', 'diagnostic_session', 'debug_fournisseurs'];
    
    if (in_array($page, $no_auth_pages)) {
        echo "Page autorisée sans authentification: $page";
    } else {
        // Détecter si on est sur un sous-domaine de magasin
        if (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
            $redirect_url = '/pages/login_auto.php';
        } else {
            $redirect_url = '/pages/login.php';
        }
        
        // Ajouter les paramètres de test s'ils existent
        $pwa_params = [];
        
        if (isset($_SESSION['test_pwa']) && $_SESSION['test_pwa'] === true) {
            $pwa_params[] = 'test_pwa=true';
        }
        
        if (isset($_SESSION['test_ios']) && $_SESSION['test_ios'] === true) {
            $pwa_params[] = 'test_ios=true';
        }
        
        // Ajouter le paramètre shop_id si présent
        if (isset($_SESSION['shop_id'])) {
            $pwa_params[] = 'shop_id=' . $_SESSION['shop_id'];
        }
        
        // Mémoriser la page originale pour y revenir après connexion
        if (isset($_GET['page'])) {
            $pwa_params[] = 'redirect=' . urlencode($_GET['page']);
            
            // Ajouter l'ID si présent
            if (isset($_GET['id'])) {
                $pwa_params[] = 'id=' . urlencode($_GET['id']);
            }
        }
        
        // Ajouter les paramètres à l'URL de redirection
        if (!empty($pwa_params)) {
            $redirect_url .= '?' . implode('&', $pwa_params);
        }
        
        // Debug: afficher les informations avant la redirection
        echo "<!-- DEBUG: Redirection vers $redirect_url -->";
        echo "<!-- DEBUG: user_id=" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NON DÉFINI') . " -->";
        echo "<!-- DEBUG: shop_id=" . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'NON DÉFINI') . " -->";
        
        // Effectuer la redirection
        header('Location: ' . $redirect_url);
        exit();
    }
} else {
    echo "Utilisateur connecté: " . $_SESSION['user_id'];
    echo "<br>Magasin: " . ($_SESSION['shop_name'] ?? 'Non défini');
    echo "<br>Page demandée: $page";
    echo "<br><a href='?page=deconnexion'>Se déconnecter</a>";
}
?> 
/**
 * Version simplifiée d'index.php pour tester les redirections
 */

// Inclure les configurations
require_once __DIR__ . '/config/session_config.php';
require_once __DIR__ . '/config/subdomain_config.php';

// Définir la page demandée
$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Cas spéciaux qui ne nécessitent pas d'authentification
    $no_auth_pages = ['imprimer_etiquette', 'diagnostic_session', 'debug_fournisseurs'];
    
    if (in_array($page, $no_auth_pages)) {
        echo "Page autorisée sans authentification: $page";
    } else {
        // Détecter si on est sur un sous-domaine de magasin
        if (isset($_SESSION['shop_id']) && !empty($_SESSION['shop_id'])) {
            $redirect_url = '/pages/login_auto.php';
        } else {
            $redirect_url = '/pages/login.php';
        }
        
        // Ajouter les paramètres de test s'ils existent
        $pwa_params = [];
        
        if (isset($_SESSION['test_pwa']) && $_SESSION['test_pwa'] === true) {
            $pwa_params[] = 'test_pwa=true';
        }
        
        if (isset($_SESSION['test_ios']) && $_SESSION['test_ios'] === true) {
            $pwa_params[] = 'test_ios=true';
        }
        
        // Ajouter le paramètre shop_id si présent
        if (isset($_SESSION['shop_id'])) {
            $pwa_params[] = 'shop_id=' . $_SESSION['shop_id'];
        }
        
        // Mémoriser la page originale pour y revenir après connexion
        if (isset($_GET['page'])) {
            $pwa_params[] = 'redirect=' . urlencode($_GET['page']);
            
            // Ajouter l'ID si présent
            if (isset($_GET['id'])) {
                $pwa_params[] = 'id=' . urlencode($_GET['id']);
            }
        }
        
        // Ajouter les paramètres à l'URL de redirection
        if (!empty($pwa_params)) {
            $redirect_url .= '?' . implode('&', $pwa_params);
        }
        
        // Debug: afficher les informations avant la redirection
        echo "<!-- DEBUG: Redirection vers $redirect_url -->";
        echo "<!-- DEBUG: user_id=" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NON DÉFINI') . " -->";
        echo "<!-- DEBUG: shop_id=" . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'NON DÉFINI') . " -->";
        
        // Effectuer la redirection
        header('Location: ' . $redirect_url);
        exit();
    }
} else {
    echo "Utilisateur connecté: " . $_SESSION['user_id'];
    echo "<br>Magasin: " . ($_SESSION['shop_name'] ?? 'Non défini');
    echo "<br>Page demandée: $page";
    echo "<br><a href='?page=deconnexion'>Se déconnecter</a>";
}
?> 