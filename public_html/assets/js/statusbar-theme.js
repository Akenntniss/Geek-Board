/**
 * Script pour gérer la couleur de la barre de statut en fonction du thème
 * sur iPad en mode PWA
 */
document.addEventListener('DOMContentLoaded', function() {
    // Détecter si c'est un iPad
    const isIPad = /iPad/.test(navigator.userAgent) || 
                  (/Macintosh/.test(navigator.userAgent) && 'ontouchend' in document);
    
    // Fonction pour définir la couleur de la barre de statut
    function setStatusBarColor(isDarkMode) {
        // Uniquement pour iPad en mode PWA
        if (!isIPad || !document.body.classList.contains('pwa-mode')) {
            return;
        }
        
        // Créer ou sélectionner l'élément de la barre de statut
        let statusBar = document.getElementById('ipad-status-bar');
        if (!statusBar) {
            statusBar = document.createElement('div');
            statusBar.id = 'ipad-status-bar';
            statusBar.style.position = 'fixed';
            statusBar.style.top = '0';
            statusBar.style.left = '0';
            statusBar.style.right = '0';
            statusBar.style.height = 'env(safe-area-inset-top, 20px)';
            statusBar.style.zIndex = '1100';
            document.body.appendChild(statusBar);
        }
        
        // Définir la couleur en fonction du mode
        if (isDarkMode) {
            statusBar.style.backgroundColor = '#121212';
        } else {
            statusBar.style.backgroundColor = '#f8f9fa';
        }
    }
    
    // Vérifier le thème initial
    const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
    const hasDarkModeClass = document.body.classList.contains('dark-mode') || 
                             document.body.classList.contains('darkmode');
    
    // Appliquer la couleur initiale
    setStatusBarColor(prefersDarkScheme.matches || hasDarkModeClass);
    
    // Écouter les changements de thème système
    prefersDarkScheme.addEventListener('change', function(e) {
        setStatusBarColor(e.matches);
    });
    
    // Observer les changements de classe sur le body (pour les changements de thème manuels)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                const hasDarkModeClass = document.body.classList.contains('dark-mode') || 
                                         document.body.classList.contains('darkmode');
                setStatusBarColor(hasDarkModeClass);
            }
        });
    });
    
    // Configurer l'observateur
    observer.observe(document.body, { attributes: true });
    
    // Vérifier les changements de page pour ajuster la couleur en fonction du contexte
    const pageCheckInterval = setInterval(function() {
        // Détecter la page actuelle
        const currentPage = document.body.getAttribute('data-page') || '';
        const pageClasses = document.body.className;
        
        // Enregistrer la page actuelle
        if (!document.body.hasAttribute('data-current-page')) {
            document.body.setAttribute('data-current-page', currentPage);
        }
        
        // Vérifier si la page a changé
        if (document.body.getAttribute('data-current-page') !== currentPage) {
            document.body.setAttribute('data-current-page', currentPage);
            
            // Recalculer le thème
            const hasDarkModeClass = document.body.classList.contains('dark-mode') || 
                                     document.body.classList.contains('darkmode');
            setStatusBarColor(prefersDarkScheme.matches || hasDarkModeClass);
        }
    }, 1000);
}); 