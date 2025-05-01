document.addEventListener('DOMContentLoaded', function() {

    // --- Gestion de l'état actif des liens de navigation ---

    const currentPath = window.location.pathname.split('/').pop(); // Prend le nom du fichier actuel

    // Barre de navigation Desktop
    const desktopNavLinks = document.querySelectorAll('.navbar-desktop .nav-link:not(.btn-nouvelle)');
    desktopNavLinks.forEach(link => {
        const linkPath = link.getAttribute('href').split('/').pop();
        if (linkPath === currentPath) {
            link.classList.add('active');
        }
    });

    // Barre de navigation Mobile
    const mobileNavLinks = document.querySelectorAll('.navbar-mobile .nav-link:not(.fab-button)');
    mobileNavLinks.forEach(link => {
        const linkPath = link.getAttribute('href').split('/').pop();
         // Cas spécial pour l'accueil (peut être index.php ou accueil.php)
        if ( (currentPath === 'index.php' || currentPath === 'accueil.php' || currentPath === '') && (linkPath === 'accueil.php' || linkPath === 'index.php') ) {
             link.classList.add('active');
        } else if (linkPath === currentPath && linkPath !== 'accueil.php' && linkPath !== 'index.php') {
            link.classList.add('active');
        }
    });

    // --- Gestion Modals ---

    // Peut nécessiter Bootstrap JS d'être chargé. Vérifier que les modals sont bien initialisés.
    // Si les modals Bootstrap ne s'ouvrent pas, il faudra peut-être instancier les objets Modal ici:
    // exemple:
    // const menuModalElement = document.getElementById('menuPrincipalModal');
    // if (menuModalElement) {
    //     const menuModal = new bootstrap.Modal(menuModalElement);
    //     // ensuite utiliser menuModal.show() ou menuModal.hide() si besoin
    // }
    // Idem pour les autres modals (#nouvelleModal, #nouvelleActionModal)

    // Note: Les déclencheurs de modal Bootstrap standards (data-bs-toggle="modal" et data-bs-target="#...")
    // devraient fonctionner sans JS supplémentaire si Bootstrap JS est correctement inclus.
    // Ce fichier JS est surtout pour la gestion de l'état 'active'.

}); 