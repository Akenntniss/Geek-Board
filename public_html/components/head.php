<!-- Meta tags requis -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<!-- Style critique pour forcer l'affichage de la barre de navigation -->
<style id="critical-nav-styles">
    /* Styles pour Safari desktop uniquement */
    @media screen and (min-width: 992px) {
        #desktop-navbar {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            height: 55px !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1030 !important;
            background-color: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
        }
        
        body:not(.ipad-device):not(.mobile-device):not(.tablet-device) #mobile-dock {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        body:not(.ipad-device):not(.mobile-device):not(.tablet-device) {
            padding-top: 55px !important;
            padding-bottom: 0 !important;
        }
    }
    
    /* Styles pour mobile et iPad */
    @media screen and (max-width: 991px) {
        #mobile-dock {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1030 !important;
            background-color: white !important;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1) !important;
        }
        
        #desktop-navbar {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        body {
            padding-top: 0 !important;
            padding-bottom: 55px !important;
        }
    }
</style>

<!-- Bootstrap CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">

<!-- Styles personnalisés -->
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/modal-search.css">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">

<!-- Script d'initialisation précoce pour Safari -->
<script>
// Exécution immédiate (priorité maximale)
(function() {
    // Détecter Safari
    var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
    var isIPad = /iPad/i.test(navigator.userAgent) || 
                (/Macintosh/i.test(navigator.userAgent) && 'ontouchend' in document);
    var isMobile = /iPhone|iPod|Android|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    var isDesktop = window.innerWidth >= 992 && !isIPad && !isMobile;
    
    if (isSafari) {
        // Forcer les classes Safari sur le document
        document.documentElement.classList.add('safari-browser');
        
        if (isDesktop) {
            // Safari sur desktop
            document.documentElement.classList.add('safari-desktop');
            
            // Créer et injecter immédiatement la barre de navigation au tout début
            setTimeout(function() {
                var existingNavbar = document.getElementById('desktop-navbar');
                if (!existingNavbar) {
                    var navbar = document.createElement('nav');
                    navbar.id = 'desktop-navbar';
                    navbar.className = 'navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm py-2';
                    navbar.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; height: 55px !important; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; z-index: 1030 !important; background-color: white !important;';
                    navbar.innerHTML = '<div class="container-fluid px-3"><a class="navbar-brand" href="index.php"><img src="assets/images/logo/AppIcons_lightMode/appstore.png" alt="GeekBoard" height="40"> TechBoard</a></div>';
                    
                    if (document.body) {
                        document.body.insertBefore(navbar, document.body.firstChild);
                        document.body.style.paddingTop = '55px';
                    }
                }
                
                // Cacher le dock mobile s'il existe
                var mobileDock = document.getElementById('mobile-dock');
                if (mobileDock) {
                    mobileDock.style.display = 'none';
                    mobileDock.style.visibility = 'hidden';
                    mobileDock.style.opacity = '0';
                }
            }, 10);
        } else {
            // Safari sur iPad ou mobile
            document.documentElement.classList.add('mobile-safari');
            if (isIPad) document.documentElement.classList.add('ipad-device');
            if (isMobile) document.documentElement.classList.add('mobile-device');
            
            // S'assurer que le dock est affiché en mode mobile/iPad
            setTimeout(function() {
                var existingDock = document.getElementById('mobile-dock');
                if (!existingDock) {
                    var dock = document.createElement('div');
                    dock.id = 'mobile-dock';
                    dock.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important; position: fixed !important; bottom: 0 !important; left: 0 !important; right: 0 !important; z-index: 1030 !important; background-color: white !important; box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1) !important;';
                    dock.innerHTML = '<div class="mobile-dock-container"><a href="index.php" class="dock-item active"><i class="fas fa-home"></i><span>Accueil</span></a></div>';
                    
                    if (document.body) {
                        document.body.appendChild(dock);
                        document.body.style.paddingBottom = '55px';
                    }
                }
                
                // Cacher la barre desktop si elle existe
                var desktopNavbar = document.getElementById('desktop-navbar');
                if (desktopNavbar) {
                    desktopNavbar.style.display = 'none';
                    desktopNavbar.style.visibility = 'hidden';
                    desktopNavbar.style.opacity = '0';
                }
            }, 10);
        }
    }
})();
</script>

<title>GestiRep - <?php echo $page_title ?? 'Gestion de réparations'; ?></title> 
<link rel="icon" href="assets/images/logo/AppIcons_lightMode/logo.png">
<link rel="apple-touch-icon" href="assets/images/logo/AppIcons_lightMode/appstore.png">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0078e8">

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">

<!-- Animate.css -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">

<!-- Navigation futuriste et styles modernes -->
<link href="assets/css/neo-dock.css" rel="stylesheet">

<!-- Styles personnalisés -->
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/modal-search.css"> 