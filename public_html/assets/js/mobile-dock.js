document.addEventListener('DOMContentLoaded', function() {
    // Éléments du dock
    const mobileDock = document.querySelector('.mobile-dock');
    const addButton = document.querySelector('.add-button');
    const menuButton = document.querySelector('.dock-menu-btn');
    
    // Éléments du menu modal
    const menuModal = document.querySelector('.menu-modal');
    const menuOverlay = document.querySelector('.menu-modal-overlay');
    const menuCloseBtn = document.querySelector('.menu-modal-close');
    
    // Variables pour la gestion du scroll
    let lastScrollTop = 0;
    let isScrolling;
    
    // Fonction pour masquer/afficher le dock en fonction du scroll
    function handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scroll vers le bas & passé 100px - masquer le dock
            mobileDock.classList.add('hidden');
        } else {
            // Scroll vers le haut ou près du haut - afficher le dock
            mobileDock.classList.remove('hidden');
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        
        // Clear le timeout précédent
        clearTimeout(isScrolling);
        
        // Définir un timeout pour détecter quand le scroll s'arrête
        isScrolling = setTimeout(function() {
            // Le scroll s'est arrêté, afficher le dock
            mobileDock.classList.remove('hidden');
        }, 1000);
    }
    
    // Écouter l'événement de scroll
    window.addEventListener('scroll', handleScroll, { passive: true });
    
    // Fonction pour ouvrir le menu modal
    function openMenuModal() {
        if (menuModal && menuOverlay) {
            menuModal.classList.add('active');
            menuOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Ajouter un délai pour les animations d'entrée des éléments
            const menuItems = document.querySelectorAll('.menu-item');
            menuItems.forEach((item, index) => {
                item.style.animationDelay = `${0.05 * index}s`;
            });
        }
    }
    
    // Fonction pour fermer le menu modal
    function closeMenuModal() {
        if (menuModal && menuOverlay) {
            menuModal.classList.remove('active');
            menuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    // Fonction pour gérer la navigation des pages
    function handleNavigation() {
        const currentPath = window.location.pathname;
        const pageParam = new URLSearchParams(window.location.search).get('page');
        
        // Trouver tous les éléments du dock
        const dockItems = document.querySelectorAll('.dock-item');
        
        // Retirer la classe active de tous les éléments
        dockItems.forEach(item => {
            item.classList.remove('active');
        });
        
        // Définir l'élément actif en fonction de la page actuelle
        if (currentPath === '/' || currentPath === '/index.php') {
            if (!pageParam || pageParam === 'dashboard') {
                document.querySelector('.dock-home-btn')?.classList.add('active');
            } else if (pageParam === 'reparations') {
                document.querySelector('.dock-repair-btn')?.classList.add('active');
            } else if (pageParam === 'taches') {
                document.querySelector('.dock-task-btn')?.classList.add('active');
            }
        }
    }
    
    // Initialiser la navigation
    handleNavigation();
    
    // Gestionnaire d'événement pour le bouton du menu
    if (menuButton) {
        menuButton.addEventListener('click', openMenuModal);
    }
    
    // Gestionnaire d'événement pour la fermeture du menu
    if (menuCloseBtn) {
        menuCloseBtn.addEventListener('click', closeMenuModal);
    }
    
    // Fermer le menu en cliquant sur l'overlay
    if (menuOverlay) {
        menuOverlay.addEventListener('click', closeMenuModal);
    }
    
    // Gérer le bouton d'ajout
    if (addButton) {
        addButton.addEventListener('click', function() {
            window.location.href = 'index.php?page=nouvelle_reparation';
        });
    }
}); 