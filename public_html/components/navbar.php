<?php
/**
 * Nouvelle barre de navigation pour GeekBoard
 * Trois formats:
 * 1. PC: Barre en haut avec logo, bouton Nouvelle et menus
 * 2. Mobile: Dock en bas de page (pleine largeur)
 * 3. PWA: Dock en bas de page (adaptatif selon taille d'écran)
 */

// Détecter le mode PWA
$isPWA = false;
if (isset($_SESSION['pwa_mode']) && $_SESSION['pwa_mode'] === true) {
    $isPWA = true;
} elseif (isset($_COOKIE['pwa_mode']) && $_COOKIE['pwa_mode'] === 'true') {
    $isPWA = true;
}

// Détecter si on est sur un appareil mobile ou iPad
$isMobile = false;
$isIPad = false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $isMobile = preg_match('/(android|iphone|mobile)/i', $_SERVER['HTTP_USER_AGENT']);
    $isIPad = preg_match('/(ipad)/i', $_SERVER['HTTP_USER_AGENT']) || 
              (preg_match('/(macintosh)/i', $_SERVER['HTTP_USER_AGENT']) && 
               strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false && 
               strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false);
}

// Obtenir le nom de la base de données actuelle
$db_name = '';
$shop_pdo = null;

try {
    if (isset($_SESSION['shop_id'])) {
        $shop_pdo = getShopDBConnection();
        if ($shop_pdo !== null) {
            $query = $shop_pdo->query("SELECT DATABASE() as db_name");
            $result = $query->fetch(PDO::FETCH_ASSOC);
            if ($result && isset($result['db_name'])) {
                $db_name = $result['db_name'];
            }
        }
    }
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du nom de la base de données: " . $e->getMessage());
}

// Ajouter une classe CSS au body pour les iPad
if ($isIPad) {
    echo '<script>document.body.classList.add("ipad-device");</script>';
}

// Script amélioré pour la détection de tablette et l'application des styles appropriés
echo '<script>
// Fonction pour détecter si c\'est un appareil tablette
function isTabletDevice() {
    return (window.innerWidth <= 1366 && window.innerWidth >= 600) || 
           /ipad|tablet|playbook|silk|android(?!.*mobile)/i.test(navigator.userAgent.toLowerCase());
}

// Fonction pour gérer l\'affichage selon la taille d\'écran
function handleNavbarDisplay() {
    // Pour toute taille d\'écran inférieure à 1366px, on considère comme tablette ou mobile
    if (window.innerWidth < 1366) {
        document.body.classList.add("tablet-device");
        
        // Forcer l\'affichage du dock mobile et cacher la barre de navigation desktop
        const desktopNavbar = document.getElementById("desktop-navbar");
        const mobileDock = document.getElementById("mobile-dock");
        
        if (desktopNavbar) desktopNavbar.style.display = "none";
        if (mobileDock) mobileDock.style.display = "block";
    } else {
        // Pour les grands écrans, même sur Safari
        document.body.classList.remove("tablet-device");
        
        const desktopNavbar = document.getElementById("desktop-navbar");
        const mobileDock = document.getElementById("mobile-dock");
        
        // Ne pas cacher la barre desktop sur les grands écrans, sauf si c\'est un iPad ou en mode PWA
        if (desktopNavbar && !document.body.classList.contains("ipad-device") && !document.body.classList.contains("pwa-mode")) {
            desktopNavbar.style.display = "block";
        }
        
        // Cacher le dock mobile sur desktop, sauf pour iPad ou PWA
        if (mobileDock && !document.body.classList.contains("ipad-device") && !document.body.classList.contains("pwa-mode")) {
            mobileDock.style.display = "none";
        }
    }
}

// Exécuter au chargement
document.addEventListener("DOMContentLoaded", function() {
    if (isTabletDevice()) {
        document.body.classList.add("tablet-device");
    }
    
    handleNavbarDisplay();
    
    // Vérifier à chaque redimensionnement
    window.addEventListener("resize", handleNavbarDisplay);
});
</script>';

// Récupérer la page courante
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Définir une fonction de secours pour count_active_tasks si elle n'existe pas
if (!function_exists('count_active_tasks')) {
    function count_active_tasks($user_id) {
        // Fonction temporaire pour éviter les erreurs
        return 0;
    }
}

// Récupérer le nombre de tâches en cours (si la fonction existe)
$tasks_count = 0;
if (isset($_SESSION['user_id'])) {
    $tasks_count = count_active_tasks($_SESSION['user_id']);
}
?>

<!-- NAVBAR DESKTOP (PC) -->
<?php 
// Vérifier si c'est Safari sur desktop
$isSafariDesktop = false;
if (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false && 
    strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') === false) {
    $isSafariDesktop = true;
}

// Afficher la navbar desktop SI:
// - c'est Safari, OU
// - ce n'est pas un mobile ET ce n'est pas un iPad
?>
<nav id="desktop-navbar" class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: var(--navbar-height) !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; z-index: 1030 !important;">
    <div class="container-fluid px-3">
        <!-- Logo à gauche -->
        <a class="navbar-brand me-0 me-lg-4 d-flex align-items-center" href="index.php" style="transform: translateY(-5px);">
            <img src="assets/images/logo/AppIcons_lightMode/appstore.png" alt="GeekBoard" height="40" class="light-mode-logo">
            <img src="assets/images/logo/AppIcons-darkmode/playstore.png" alt="GeekBoard" height="40" class="dark-mode-logo" style="display: none;">
            <div class="ms-2 d-flex flex-column justify-content-center" style="height: 40px;">
                <div class="fw-bold brand-text-primary" style="font-size: 1.25rem; line-height: 1.2;">TechBoard</div>
                <div class="fw-bold brand-text-secondary" style="font-size: 1rem; line-height: 1;">Assistant</div>
            </div>
        </a>
        
        <!-- Message de bienvenue avec le nom de l'utilisateur -->
        <?php if (isset($_SESSION['full_name'])): ?>
        <div class="d-none d-md-flex align-items-center ms-3 me-2" style="transform: translateY(-5px);">
            <span class="fw-medium text-primary">
                Bonjour, <?php echo htmlspecialchars($_SESSION['full_name']); ?> 
                <?php if (isset($_SESSION['shop_name'])): ?>
                <span class="badge bg-info ms-1"><?php echo htmlspecialchars($_SESSION['shop_name']); ?> 
                    <?php if (!empty($db_name)): ?>
                    <small class="ms-1">(DB: <?php echo htmlspecialchars($db_name); ?>)</small>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
        
        <!-- Bouton Nouvelle avec dropdown -->
        <div class="dropdown d-none d-lg-block me-auto" style="transform: translateY(-5px);">
            <button class="btn btn-primary" type="button" id="btnNouvelle" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal">
                <i class="fas fa-plus-circle me-1"></i> Nouvelle
            </button>
        </div>
        
        <!-- Boutons de navigation à droite -->
        <div class="d-none d-lg-flex align-items-center ms-auto" style="transform: translateY(-5px);">
            <!-- Bouton hamburger pour menu principal -->
            <button class="btn btn-outline-secondary ms-2 main-menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenuOffcanvas" aria-controls="mainMenuOffcanvas">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <!-- Version mobile du bouton hamburger -->
        <button class="navbar-toggler d-lg-none ms-auto main-menu-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMenuOffcanvas" aria-controls="mainMenuOffcanvas">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</nav>

<!-- NAVBAR MOBILE ET PWA (Dock en bas) -->
<div id="mobile-dock" class="<?php echo ($isMobile || $isIPad) ? 'd-block' : 'd-lg-none'; ?>" <?php if (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false && !strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false && !$isIPad && !$isMobile): ?>style="display: none !important; visibility: hidden !important;"<?php endif; ?>>
    <!-- Message de bienvenue pour mobile en haut du dock -->
    <?php if (isset($_SESSION['full_name'])): ?>
    <div class="mobile-welcome-banner">
        <div class="container-fluid py-1 text-center">
            <span class="fw-medium">
                Bonjour, <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                <?php if (isset($_SESSION['shop_name'])): ?>
                <span class="badge bg-info ms-1">
                    <?php echo htmlspecialchars($_SESSION['shop_name']); ?>
                    <?php if (!empty($db_name)): ?>
                    <small class="ms-1">(DB: <?php echo htmlspecialchars($db_name); ?>)</small>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="mobile-dock-container">
        <a href="index.php" class="dock-item <?php echo $currentPage == 'accueil' ? 'active' : ''; ?>">
            <div class="dock-icon-wrapper">
                <i class="fas fa-home"></i>
            </div>
            <span>Accueil</span>
        </a>
        
        <a href="index.php?page=reparations" class="dock-item <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>">
            <div class="dock-icon-wrapper">
                <i class="fas fa-tools"></i>
            </div>
            <span>Réparations</span>
        </a>
        
        <!-- Bouton Nouvelle au centre (stylisé différemment) -->
        <div class="dock-item-center" style="overflow: visible !important; position: relative !important;">
            <button class="btn-nouvelle-action" type="button" data-bs-toggle="modal" data-bs-target="#nouvelles_actions_modal" style="transform: translateY(0) !important;">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        
        <a href="index.php?page=taches" class="dock-item <?php echo $currentPage == 'taches' ? 'active' : ''; ?>">
            <div class="dock-icon-wrapper">
                <i class="fas fa-tasks"></i>
                <?php if ($tasks_count > 0): ?>
                <span class="badge"><?php echo $tasks_count; ?></span>
                <?php endif; ?>
            </div>
            <span>Tâches</span>
        </a>
        
        <a href="#" class="dock-item" data-bs-toggle="modal" data-bs-target="#menu_navigation_modal">
            <div class="dock-icon-wrapper">
                <i class="fas fa-ellipsis-h"></i>
            </div>
            <span>Menu</span>
        </a>
    </div>
</div>

<style>
/* Style pour gérer l'affichage des logos en fonction du mode */
body:not(.dark-mode) .dark-mode-logo {
    display: none !important;
}

body:not(.dark-mode) .light-mode-logo {
    display: inline-block !important;
}

body.dark-mode .dark-mode-logo {
    display: inline-block !important;
}

body.dark-mode .light-mode-logo {
    display: none !important;
}

/* Styles pour le texte de la marque */
.brand-text-primary {
    color: var(--primary) !important;
}

.brand-text-secondary {
    color: var(--info) !important;
}

body:not(.dark-mode) .brand-text-primary {
    color: #2563eb !important;
}

body:not(.dark-mode) .brand-text-secondary {
    color: #3b82f6 !important;
}

body.dark-mode .brand-text-primary {
    color: #60a5fa !important;
}

body.dark-mode .brand-text-secondary {
    color: #93c5fd !important;
}

/* Style pour la bannière de bienvenue mobile */
.mobile-welcome-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background-color: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    z-index: 1020;
    padding: 8px 0;
    font-size: 0.9rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.dark-mode .mobile-welcome-banner {
    background-color: rgba(25, 25, 25, 0.95);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

/* Ajustement du contenu principal pour éviter qu'il soit caché par la bannière */
body:has(.mobile-welcome-banner) #main-content {
    padding-top: 60px !important;
}

/* Styles pour le nouveau menu Offcanvas */
.offcanvas-launchpad {
    padding: 1rem;
}

.offcanvas-launchpad .launchpad-section {
    margin-bottom: 1.5rem;
}

.offcanvas-launchpad .launchpad-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.75rem;
    padding-left: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05rem;
}

.offcanvas-launchpad .launchpad-section-content {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

@media (min-width: 400px) {
    .offcanvas-launchpad .launchpad-section-content {
        grid-template-columns: repeat(3, 1fr);
    }
}

.offcanvas-launchpad .launchpad-item {
    animation-delay: 0.05s;
}

#mainMenuOffcanvas {
    max-width: 420px;
}

#mainMenuOffcanvas .launchpad-icon {
    width: 50px;
    height: 50px;
    font-size: 1.2rem;
}

#mainMenuOffcanvas .launchpad-item span {
    font-size: 0.8rem;
}

/* Bouton du menu hamburger amélioré */
.main-menu-btn {
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.main-menu-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Styles pour l'icône scanner */
.launchpad-icon-scanner {
    background-color: rgba(0, 150, 136, 0.15);
    color: #009688;
}

/* Styles pour l'icône ajouter */
.launchpad-icon-add {
    background-color: rgba(76, 175, 80, 0.15);
    color: #4CAF50;
}

/* Styles pour l'icône partenaires */
.launchpad-icon-partner {
    background-color: rgba(33, 150, 243, 0.15);
    color: #2196F3;
}

/* Styles pour l'icône bug */
.launchpad-icon-bug {
    background-color: rgba(244, 67, 54, 0.15);
    color: #F44336;
}

/* Styles pour l'icône de rachat */
.launchpad-icon-trade {
    background-color: rgba(76, 175, 80, 0.15);
    color: #4CAF50;
}

/* Styles pour l'icône retours */
.launchpad-icon-return {
    background-color: rgba(33, 150, 243, 0.15);
    color: #2196F3;
}

/* Styles pour l'icône logs */
.launchpad-icon-logs {
    background-color: rgba(33, 150, 243, 0.15);
    color: #2196F3;
}
</style>

<!-- OFFCANVAS MENU PRINCIPAL (pour desktop) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="mainMenuOffcanvas" aria-labelledby="mainMenuOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mainMenuOffcanvasLabel">
            <img src="assets/images/logo/AppIcons_lightMode/appstore.png" alt="GeekBoard" height="30" class="me-2 light-mode-logo">
            <img src="assets/images/logo/AppIcons-darkmode/playstore.png" alt="GeekBoard" height="30" class="me-2 dark-mode-logo" style="display: none;">
            Menu Principal
        </h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="launchpad-container offcanvas-launchpad">
            <!-- Section Gestion Principale -->
            <div class="launchpad-section">
                <h3 class="launchpad-section-title">Gestion Principale</h3>
                <div class="launchpad-section-content">
                    <a href="index.php" class="launchpad-item <?php echo empty($_GET['page']) ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-home">
                            <i class="fas fa-home"></i>
                        </div>
                        <span>Accueil</span>
                    </a>
                    
                    <a href="index.php?page=reparations" class="launchpad-item <?php echo $currentPage == 'reparations' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-repair">
                            <i class="fas fa-tools"></i>
                        </div>
                        <span>Réparations</span>
                    </a>
                    
                    <a href="index.php?page=ajouter_reparation" class="launchpad-item <?php echo $currentPage == 'ajouter_reparation' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-add">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <span>Nouvelle Réparation</span>
                    </a>
                    
                    <a href="index.php?page=commandes_pieces" class="launchpad-item <?php echo $currentPage == 'commandes_pieces' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-order">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <span>Commandes</span>
                    </a>
                    
                    <a href="index.php?page=taches" class="launchpad-item <?php echo $currentPage == 'taches' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-task">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <span>Tâches</span>
                        <?php if ($tasks_count > 0): ?>
                            <span class="badge rounded-pill bg-danger position-absolute top-0 end-0 translate-middle"><?php echo $tasks_count; ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <a href="index.php?page=rachat_appareils" class="launchpad-item <?php echo $currentPage == 'rachat_appareils' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-trade">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <span>Rachat</span>
                    </a>
                    
                    <a href="index.php?page=base_connaissances" class="launchpad-item <?php echo $currentPage == 'base_connaissances' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-knowledge">
                            <i class="fas fa-book"></i>
                        </div>
                        <span>Base de connaissance</span>
                    </a>
                    
                    <a href="index.php?page=clients" class="launchpad-item <?php echo $currentPage == 'clients' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-client">
                            <i class="fas fa-users"></i>
                        </div>
                        <span>Clients</span>
                    </a>
                </div>
            </div>

            <!-- Section Communication -->
            <div class="launchpad-section">
                <h3 class="launchpad-section-title">Communication</h3>
                <div class="launchpad-section-content">
                    <a href="index.php?page=campagne_sms" class="launchpad-item <?php echo $currentPage == 'campagne_sms' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-sms">
                            <i class="fas fa-sms"></i>
                        </div>
                        <span>Campagne SMS</span>
                    </a>
                    
                    <a href="index.php?page=template_sms" class="launchpad-item <?php echo $currentPage == 'template_sms' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-template">
                            <i class="fas fa-comment-dots"></i>
                        </div>
                        <span>Template SMS</span>
                    </a>
                    
                    <a href="index.php?page=sms_historique" class="launchpad-item <?php echo $currentPage == 'sms_historique' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-history">
                            <i class="fas fa-history"></i>
                        </div>
                        <span>Historique SMS</span>
                    </a>
                </div>
            </div>

            <!-- Section Administration -->
            <div class="launchpad-section">
                <h3 class="launchpad-section-title">Administration</h3>
                <div class="launchpad-section-content">
                    <a href="index.php?page=employes" class="launchpad-item <?php echo $currentPage == 'employes' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-employee">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <span>Employés</span>
                    </a>
                    
                    <a href="index.php?page=reparation_logs" class="launchpad-item <?php echo $currentPage == 'reparation_logs' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-logs">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <span>Journaux de réparation</span>
                    </a>
                    
                    <a href="#" class="launchpad-item" data-bs-toggle="modal" data-bs-target="#ajouterBugModal">
                        <div class="launchpad-icon launchpad-icon-bug">
                            <i class="fas fa-bug"></i>
                        </div>
                        <span>Signaler un bug</span>
                    </a>
                    
                    <a href="index.php?page=parametre" class="launchpad-item <?php echo $currentPage == 'parametre' ? 'active' : ''; ?>">
                        <div class="launchpad-icon launchpad-icon-settings">
                            <i class="fas fa-cog"></i>
                        </div>
                        <span>Parametre</span>
                    </a>
                    
                    <?php if (isset($_SESSION['shop_id'])): ?>
                    <a href="/pages/change_shop.php" class="launchpad-item">
                        <div class="launchpad-icon" style="background-color: rgba(0, 120, 232, 0.15); color: #0078E8;">
                            <i class="fas fa-store"></i>
                        </div>
                        <span>Changer de magasin</span>
                    </a>
                    <?php endif; ?>
                    
                    <a href="index.php?action=logout" class="launchpad-item">
                        <div class="launchpad-icon launchpad-icon-danger">
                            <i class="fas fa-sign-out-alt"></i>
                        </div>
                        <span>Déconnexion</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour déclarer un bug -->
<div class="modal fade" id="ajouterBugModal" tabindex="-1" aria-labelledby="ajouterBugModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ajouterBugModalLabel">
                    <i class="fas fa-bug me-2 text-danger"></i> Signaler un problème
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <form id="ajouterBugForm">
                    <input type="hidden" name="page_url" id="bug_page_url" value="<?php echo (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
                    
                    <div class="mb-3">
                        <label for="bug_description" class="form-label">Problème :</label>
                        <textarea class="form-control" id="bug_description" name="description" rows="4" required placeholder="Décrivez le problème rencontré..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="ajouterBugForm" class="btn btn-primary">Sauvegarder</button>
            </div>
        </div>
    </div>
</div>