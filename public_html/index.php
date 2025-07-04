<?php
// DÉTECTION PRÉALABLE DE LA PAGE DE LANDING
// On vérifie AVANT tout si on doit afficher la landing page
$host = $_SERVER['HTTP_HOST'] ?? '';

// Nettoyer le host (enlever le port s'il y en a un)
$host = preg_replace('/:\d+$/', '', $host);

// Si on est sur le domaine principal (mdgeek.top ou www.mdgeek.top), vérifier si on doit afficher la landing page
if ($host === 'mdgeek.top' || $host === 'www.mdgeek.top') {
    // Démarrer une session minimale pour vérifier les variables
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // LOGGING pour debug
    error_log("LANDING PAGE DEBUG: Host=$host, shop_id=" . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'non défini') . ", superadmin_id=" . (isset($_SESSION['superadmin_id']) ? $_SESSION['superadmin_id'] : 'non défini'));
    
    // Si aucun shop_id n'est défini ET qu'on n'est pas un super admin
    // OU si on force l'affichage de la landing page
    if ((!isset($_SESSION['shop_id']) && !isset($_SESSION['superadmin_id'])) || isset($_GET['force_landing'])) {
        error_log("LANDING PAGE: Affichage de la landing page pour $host");
        
        // Nettoyer complètement la session pour éviter les interférences
        if (isset($_GET['force_landing'])) {
            unset($_SESSION['shop_id']);
            unset($_SESSION['shop_name']);
        }
        
        // Afficher la page de landing directement sans charger l'application
        include __DIR__ . '/pages/landing.php';
        exit;
    } else {
        error_log("LANDING PAGE: Chargement de l'application normale pour $host (shop_id=" . ($_SESSION['shop_id'] ?? 'non défini') . ")");
    }
}

// Si on arrive ici, on charge l'application normale
// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/config/session_config.php';
// La session est déjà démarrée dans session_config.php, pas besoin de session_start() ici

// Inclure la configuration pour la gestion des sous-domaines
require_once __DIR__ . '/config/subdomain_config.php';
// Le sous-domaine est détecté et la session est configurée avec le magasin correspondant

// Vérifier si on est dans le contexte d'un magasin spécifique
if (isset($_SESSION['shop_id'])) {
    // Nous sommes dans un magasin spécifique, noter cela pour l'interface
    $current_shop_id = $_SESSION['shop_id'];
    $current_shop_name = $_SESSION['shop_name'] ?? 'Magasin';
} else {
    // Pas de magasin sélectionné, vérifier si c'est un super administrateur
    if (isset($_SESSION['superadmin_id'])) {
        // Rediriger vers le tableau de bord des magasins
        header('Location: superadmin/index.php');
        exit;
    }
}

// Vérifier les paramètres de test PWA dans l'URL et les stocker dans la session
if (isset($_GET['test_pwa']) && $_GET['test_pwa'] === 'true') {
    $_SESSION['test_pwa'] = true;
}

if (isset($_GET['test_ios']) && $_GET['test_ios'] === 'true') {
    $_SESSION['test_ios'] = true;
}

if (isset($_GET['test_dynamic_island']) && $_GET['test_dynamic_island'] === 'true') {
    $_SESSION['test_dynamic_island'] = true;
}

// Définir la page demandée (nécessaire pour la vérification d'authentification)
$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Cas spéciaux qui ne nécessitent pas d'authentification
    $no_auth_pages = ['imprimer_etiquette', 'diagnostic_session', 'debug_fournisseurs'];
    
    if (in_array($page, $no_auth_pages)) {
        // Permettre l'accès à ces pages sans authentification
        if ($page == 'imprimer_etiquette' && isset($_GET['id'])) {
        error_log("Accès à imprimer_etiquette avec id=" . $_GET['id'] . " sans session utilisateur active.");
        }
        // Continuer sans redirection
    } else {
    // Transmettre les paramètres de test s'ils existent
    $redirect_url = '/pages/login.php';
    $pwa_params = [];
    
    if (isset($_SESSION['test_pwa']) && $_SESSION['test_pwa'] === true) {
        $pwa_params[] = 'test_pwa=true';
    }
    
    if (isset($_SESSION['test_ios']) && $_SESSION['test_ios'] === true) {
        $pwa_params[] = 'test_ios=true';
    }
    
    if (isset($_SESSION['test_dynamic_island']) && $_SESSION['test_dynamic_island'] === true) {
        $pwa_params[] = 'test_dynamic_island=true';
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
                $pwa_params[] = 'id=' . $_GET['id'];
            }
        }
    
    // Ajouter les paramètres à l'URL de redirection
    if (!empty($pwa_params)) {
        $redirect_url .= '?' . implode('&', $pwa_params);
    }
    
    header('Location: ' . $redirect_url);
    exit();
    }
}

// Configuration de l'affichage des erreurs (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le chemin de base
define('BASE_PATH', __DIR__);
define('BASE_URL', '/');

// Vérification et redirection de domaine
$current_domain = $_SERVER['HTTP_HOST'];
if (strpos($current_domain, 'mdgeek.fr') !== false) {
    // Récupérer l'URL complète actuelle
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    // Remplacer mdgeek.fr par mdgeek.top
    $new_url = str_replace('mdgeek.fr', 'mdgeek.top', $current_url);
    
    // Effectuer la redirection permanente
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $new_url);
    exit;
}

// Script d'initialisation pour la barre de navigation sur Safari
echo '<script>
// Exécution immédiate pour garantir l\'affichage de la barre de navigation
(function() {
    // Détecter Safari
    const isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    const isIPad = /iPad/i.test(navigator.userAgent) || 
                  (/Macintosh/i.test(navigator.userAgent) && "ontouchend" in document);
    const isMobile = /iPhone|iPod|Android|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    // Si c\'est Safari sur desktop (pas iPad, pas mobile)
    if (isSafari) {
        // Créer un style CSS inline pour forcer l\'affichage de la barre de navigation
        const styleElement = document.createElement("style");
        styleElement.textContent = `
            @media screen and (min-width: 992px) {
                #desktop-navbar {
                    display: block !important;
                    visibility: visible !important;
                    opacity: 1 !important;
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    z-index: 1030 !important;
                }
                
                #mobile-dock {
                    display: none !important;
                    visibility: hidden !important;
                    opacity: 0 !important;
                }
                
                body {
                    padding-top: 55px !important;
                }
            }
        `;
        
        // Ajouter le style à la tête du document
        document.head.appendChild(styleElement);
        
        // Forcer une vérification après le chargement du DOM
        document.addEventListener("DOMContentLoaded", function() {
            console.log("Safari détecté - Forçage de la barre de navigation depuis index.php");
            
            // S\'assurer que la barre est visible
            const desktopNavbar = document.getElementById("desktop-navbar");
            if (desktopNavbar) {
                desktopNavbar.style.display = "block";
                desktopNavbar.style.visibility = "visible";
                desktopNavbar.style.opacity = "1";
            } else {
                console.log("Barre de navigation non trouvée, création manuelle");
                
                // Créer une barre de navigation de secours
                const navbar = document.createElement("nav");
                navbar.id = "desktop-navbar";
                navbar.className = "navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2";
                navbar.style.cssText = "display: block !important; visibility: visible !important; opacity: 1 !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; z-index: 1030 !important;";
                navbar.innerHTML = `
                    <div class="container-fluid px-3">
                        <a class="navbar-brand" href="index.php">
                            <img src="assets/images/logo/AppIcons_lightMode/appstore.png" alt="GeekBoard" height="40">
                            TechBoard
                        </a>
                    </div>
                `;
                
                // Ajouter au début du body
                document.body.insertBefore(navbar, document.body.firstChild);
            }
            
            // Masquer le dock mobile
            const mobileDock = document.getElementById("mobile-dock");
            if (mobileDock) {
                mobileDock.style.display = "none";
                mobileDock.style.visibility = "hidden";
            }
        });
    }
})();
</script>';

// Inclure les fichiers de configuration et de connexion à la base de données
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/actions/inventaire_actions.php';

// Nettoyer la page maintenant que functions.php est inclus
$page = cleanInput($page);

// Liste des pages autorisées
$allowed_pages = ['accueil', 'clients', 'ajouter_client', 'modifier_client', 'reparations', 'ajouter_reparation', 'modifier_reparation', 'taches', 'ajouter_tache', 'modifier_tache', 'supprimer_tache', 'commentaires_tache', 'employes', 'ajouter_employe', 'modifier_employe', 'conges', 'conges_employe', 'conges_calendrier', 'conges_imposer', 'conges_disponibles', 'inventaire', 'categories', 'fournisseurs', 'commandes', 'commandes_pieces', 'nouvelle_commande', 'ajax/recherche_clients', 'ajax/ajouter_client', 'inventaire_actions', 'historique_client', 'deconnexion', 'rachat_appareils', 'parametre', 'scanner', 'ajouter_scan', 'nouveau_rachat', 'imprimer_etiquette', 'details_reparation', 'statut_rapide', 'comptes_partenaires', 'reparation_logs', 'reparation_log', 'messagerie', 'base_connaissances', 'article_kb', 'ajouter_article_kb', 'modifier_article_kb', 'gestion_kb', 'sms_templates', 'sms_historique', 'gardiennage', 'campagne_sms', 'campagne_details', 'bug-reports', 'suivi_reparation', 'admin_notifications', 'retours', 'retours_actions', 'switch_shop', 'diagnostic_session', 'debug_fournisseurs'];

// Vérifier si la page demandée est autorisée
if (!in_array($page, $allowed_pages)) {
    $page = '404';
}

// Gérer le changement de magasin si demandé
if ($page === 'switch_shop') {
    // Vérifie si l'utilisateur est un super administrateur
    if (isset($_SESSION['superadmin_id'])) {
        $shop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($shop_id > 0) {
            // Stocker l'ID du magasin en session
            $_SESSION['shop_id'] = $shop_id;
            
            // Récupérer le nom du magasin
            $shop_pdo = getMainDBConnection();
            $stmt = $shop_pdo->prepare("SELECT name FROM shops WHERE id = ?");
            $stmt->execute([$shop_id]);
            $shop = $stmt->fetch();
            
            if ($shop) {
                $_SESSION['shop_name'] = $shop['name'];
            }
        }
    }
    
    // Rediriger vers la page d'accueil
    header('Location: index.php');
    exit;
}

// Contenu principal
try {
    // Vérifier si c'est une requête AJAX
    $is_ajax = strpos($page, 'ajax/') === 0;
    
    // Ne pas inclure l'en-tête pour les requêtes AJAX
    if (!$is_ajax) {
        include BASE_PATH . '/includes/header.php';
    }
    
    switch ($page) {
        case 'accueil':
            include BASE_PATH . '/pages/accueil.php';
            break;
        case 'clients':
            include BASE_PATH . '/pages/clients.php';
            break;
        case 'ajouter_client':
            include BASE_PATH . '/pages/ajouter_client.php';
            break;
        case 'modifier_client':
            include BASE_PATH . '/pages/modifier_client.php';
            break;
        case 'reparations':
            include BASE_PATH . '/pages/reparations.php';
            break;
        case 'ajouter_reparation':
            include BASE_PATH . '/pages/ajouter_reparation.php';
            break;
        case 'modifier_reparation':
            include BASE_PATH . '/pages/modifier_reparation.php';
            break;
        case 'taches':
            include BASE_PATH . '/pages/taches.php';
            break;
        case 'ajouter_tache':
            include BASE_PATH . '/pages/ajouter_tache.php';
            break;
        case 'modifier_tache':
            include BASE_PATH . '/pages/modifier_tache.php';
            break;
        case 'supprimer_tache':
            include BASE_PATH . '/pages/supprimer_tache.php';
            break;
        case 'commentaires_tache':
            include BASE_PATH . '/pages/commentaires_tache.php';
            break;
        case 'employes':
            include BASE_PATH . '/pages/employes.php';
            break;
        case 'ajouter_employe':
            include BASE_PATH . '/pages/ajouter_employe.php';
            break;
        case 'modifier_employe':
            include BASE_PATH . '/pages/modifier_employe.php';
            break;
        case 'conges':
            include BASE_PATH . '/pages/conges.php';
            break;
        case 'conges_employe':
            include BASE_PATH . '/pages/conges_employe.php';
            break;
        case 'conges_calendrier':
            include BASE_PATH . '/pages/conges_calendrier.php';
            break;
        case 'conges_imposer':
            include BASE_PATH . '/pages/conges_imposer.php';
            break;
        case 'conges_disponibles':
            include BASE_PATH . '/pages/conges_disponibles.php';
            break;
        case 'inventaire':
            include BASE_PATH . '/pages/inventaire.php';
            break;
        case 'categories':
            include BASE_PATH . '/pages/categories.php';
            break;
        case 'fournisseurs':
            include BASE_PATH . '/pages/fournisseurs.php';
            break;
        case 'commandes':
            include BASE_PATH . '/pages/commandes.php';
            break;
        case 'commandes_pieces':
            include BASE_PATH . '/pages/commandes_pieces.php';
            break;
        case 'nouvelle_commande':
            include BASE_PATH . '/pages/nouvelle_commande.php';
            break;
        case 'historique_client':
            include BASE_PATH . '/pages/historique_client.php';
            break;
        case 'ajax/recherche_clients':
            include BASE_PATH . '/ajax/recherche_clients.php';
            break;
        case 'ajax/ajouter_client':
            include BASE_PATH . '/ajax/ajouter_client.php';
            break;
        case 'inventaire_actions':
            include BASE_PATH . '/actions/inventaire_actions.php';
            break;
        case 'deconnexion':
            include BASE_PATH . '/pages/deconnexion.php';
            break;
        case 'rachat_appareils':
            include BASE_PATH . '/pages/rachat_appareils.php';
            break;
        case 'nouveau_rachat':
            include BASE_PATH . '/pages/nouveau_rachat.php';
            break;
        case 'scanner':
            include BASE_PATH . '/pages/scanner.php';
            break;
        case 'ajouter_scan':
            include BASE_PATH . '/pages/ajouter_scan.php';
            break;
        case 'parametre':
            include BASE_PATH . '/pages/parametre.php';
            break;
        case 'imprimer_etiquette':
            include BASE_PATH . '/pages/imprimer_etiquette.php';
            break;
        case 'details_reparation':
            include BASE_PATH . '/pages/details_reparation.php';
            break;
        case 'statut_rapide':
            include BASE_PATH . '/pages/statut_rapide.php';
            break;
        case 'comptes_partenaires':
            include BASE_PATH . '/pages/comptes_partenaires.php';
            break;
        case 'reparation_logs':
            include BASE_PATH . '/pages/reparation_logs.php';
            break;
        case 'reparation_log':
            include BASE_PATH . '/pages/reparation_logs.php';
            break;
        case 'messagerie':
            include BASE_PATH . '/pages/messagerie.php';
            break;
        case 'base_connaissances':
            include BASE_PATH . '/pages/base_connaissances.php';
            break;
        case 'article_kb':
            include BASE_PATH . '/pages/article_kb.php';
            break;
        case 'ajouter_article_kb':
            include BASE_PATH . '/pages/ajouter_article_kb.php';
            break;
        case 'modifier_article_kb':
            include BASE_PATH . '/pages/modifier_article_kb.php';
            break;
        case 'gestion_kb':
            include BASE_PATH . '/pages/gestion_kb.php';
            break;
        case 'sms_templates':
            include BASE_PATH . '/pages/sms_templates.php';
            break;
        case 'sms_historique':
            include BASE_PATH . '/pages/sms_historique.php';
            break;
        case 'gardiennage':
            include BASE_PATH . '/pages/gardiennage.php';
            break;
        case 'campagne_sms':
            include BASE_PATH . '/pages/campagne_sms.php';
            break;
        case 'campagne_details':
            include BASE_PATH . '/pages/campagne_details.php';
            break;
        case 'bug-reports':
            include BASE_PATH . '/pages/bug-reports.php';
            break;
        case 'suivi_reparation':
            include BASE_PATH . '/pages/suivi_reparation.php';
            break;
        case 'admin_notifications':
            include BASE_PATH . '/pages/admin_notifications.php';
            break;
        case 'retours':
            include BASE_PATH . '/pages/retours.php';
            break;
        case 'retours_actions':
            include BASE_PATH . '/pages/retours_actions.php';
            break;
        case 'diagnostic_session':
            include BASE_PATH . '/pages/diagnostic_session.php';
            break;
        case 'debug_fournisseurs':
            include BASE_PATH . '/ajax/debug_fournisseurs.php';
            break;
        case '404':
            include BASE_PATH . '/pages/404.php';
            break;
        default:
            include BASE_PATH . '/pages/404.php';
            break;
    }
    
    // Nous avons supprimé l'inclusion du footer pour les requêtes non-AJAX
    if (!$is_ajax) {
        include BASE_PATH . '/includes/footer.php';
    }
} catch (Exception $e) {
    // Log l'erreur
    error_log("Erreur lors du chargement de la page $page : " . $e->getMessage());
    // Afficher une page d'erreur générique
    include BASE_PATH . '/pages/404.php';
}
?>