/**
 * Script JavaScript pour la barre de navigation mobile
 * Gère l'initialisation et les interactions de la barre de navigation en bas de page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la barre de navigation mobile si nécessaire
    initMobileNavigation();
    
    // Écouter les changements de taille d'écran
    window.addEventListener('resize', function() {
        // Réinitialiser la navigation si nécessaire
        if (window.innerWidth <= 991.98) {
            initMobileNavigation();
        }
    });
});

/**
 * Initialise la barre de navigation mobile en bas de page
 */
function initMobileNavigation() {
    // Vérifier si on est sur un appareil mobile ou tablette
    if (window.innerWidth > 991.98) return;
    
    // Vérifier si la barre de navigation existe déjà
    if (document.querySelector('.mobile-bottom-nav')) return;
    
    // Créer la structure de la barre de navigation
    const mobileNav = document.createElement('nav');
    mobileNav.className = 'mobile-bottom-nav';
    
    const navContainer = document.createElement('div');
    navContainer.className = 'mobile-bottom-nav-container';
    
    // Récupérer les liens de navigation depuis la barre latérale
    const sidebarLinks = document.querySelectorAll('#sidebarMenu .nav-link');
    
    // Si aucun lien n'est trouvé, utiliser des liens par défaut
    if (sidebarLinks.length === 0) {
        createDefaultNavigation(navContainer);
    } else {
        // Créer les éléments de navigation à partir des liens de la barre latérale
        sidebarLinks.forEach(link => {
            // Limiter à 5 éléments maximum pour la navigation mobile
            if (navContainer.children.length >= 5) return;
            
            const navItem = document.createElement('a');
            navItem.className = 'mobile-nav-item';
            navItem.href = link.href;
            
            // Vérifier si le lien est actif
            if (link.classList.contains('active')) {
                navItem.classList.add('active');
            }
            
            // Récupérer l'icône et le texte
            const icon = link.querySelector('i');
            const text = link.textContent.trim();
            
            // Créer l'icône
            const navIcon = document.createElement('i');
            if (icon) {
                // Copier les classes de l'icône existante
                icon.classList.forEach(cls => {
                    navIcon.classList.add(cls);
                });
            } else {
                // Icône par défaut si aucune n'est trouvée
                navIcon.className = 'fas fa-circle';
            }
            
            // Créer le texte
            const navText = document.createElement('span');
            navText.textContent = text;
            
            // Ajouter l'icône et le texte à l'élément de navigation
            navItem.appendChild(navIcon);
            navItem.appendChild(navText);
            
            // Ajouter l'élément à la barre de navigation
            navContainer.appendChild(navItem);
        });
    }
    
    // Ajouter le conteneur à la barre de navigation
    mobileNav.appendChild(navContainer);
    
    // Ajouter la barre de navigation au body
    document.body.appendChild(mobileNav);
    
    // Ajouter des effets de feedback tactile
    addTouchFeedback();
}

/**
 * Crée une navigation par défaut si aucun lien n'est trouvé
 * @param {HTMLElement} container - Le conteneur de navigation
 */
function createDefaultNavigation(container) {
    // Structure de navigation par défaut
    const defaultNavItems = [
        { icon: 'fas fa-tachometer-alt', text: 'Tableau de bord', href: 'index.php' },
        { icon: 'fas fa-users', text: 'Clients', href: 'index.php?page=clients' },
        { icon: 'fas fa-tools', text: 'Réparations', href: 'index.php?page=reparations' },
        { icon: 'fas fa-plus-circle', text: 'Ajouter', href: 'index.php?page=ajouter_reparation' },
        { icon: 'fas fa-cog', text: 'Paramètres', href: '#' }
    ];
    
    // Créer les éléments de navigation
    defaultNavItems.forEach(item => {
        const navItem = document.createElement('a');
        navItem.className = 'mobile-nav-item';
        navItem.href = item.href;
        
        // Vérifier si le lien correspond à la page actuelle
        const currentPage = window.location.href;
        if (currentPage.includes(item.href) && item.href !== '#') {
            navItem.classList.add('active');
        }
        
        // Créer l'icône
        const navIcon = document.createElement('i');
        navIcon.className = item.icon;
        
        // Créer le texte
        const navText = document.createElement('span');
        navText.textContent = item.text;
        
        // Ajouter l'icône et le texte à l'élément de navigation
        navItem.appendChild(navIcon);
        navItem.appendChild(navText);
        
        // Ajouter l'élément à la barre de navigation
        container.appendChild(navItem);
    });
}

/**
 * Ajoute des effets de feedback tactile aux éléments de navigation
 */
function addTouchFeedback() {
    const navItems = document.querySelectorAll('.mobile-nav-item');
    
    navItems.forEach(item => {
        item.addEventListener('touchstart', function() {
            this.style.transform = 'scale(0.95)';
            this.style.opacity = '0.9';
        });
        
        item.addEventListener('touchend', function() {
            this.style.transform = '';
            this.style.opacity = '';
        });
    });
}