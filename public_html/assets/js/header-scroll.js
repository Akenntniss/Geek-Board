document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('.top-nav');
    let lastScrollTop = 0;
    let headerTimeout;
    const HEADER_TIMEOUT_DURATION = 50000; // 50 secondes

    // Fonction pour masquer le header
    function hideHeader() {
        if (!header.classList.contains('hidden')) {
            header.classList.add('hidden');
            header.classList.remove('elevation');
        }
    }

    // Fonction pour afficher le header
    function showHeader() {
        if (header.classList.contains('hidden')) {
            header.classList.remove('hidden');
            header.classList.add('elevation');
            
            // Retirez la classe d'animation après qu'elle soit terminée
            setTimeout(() => {
                header.classList.remove('elevation');
            }, 500);
        }
    }

    // Fonction pour programmer la réapparition automatique du header
    function scheduleHeaderReappearance() {
        // Annuler le timeout précédent s'il existe
        if (headerTimeout) {
            clearTimeout(headerTimeout);
        }
        
        // Programmer la réapparition dans 50 secondes
        headerTimeout = setTimeout(() => {
            showHeader();
        }, HEADER_TIMEOUT_DURATION);
    }

    // Écouteur d'événement de défilement
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Détermine la direction du défilement
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Défilement vers le bas et pas tout en haut de la page
            hideHeader();
            scheduleHeaderReappearance();
        } else {
            // Défilement vers le haut
            showHeader();
        }
        
        // Mettre à jour la position précédente
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    }, { passive: true });

    // Créer une zone de déclenchement en haut pour faire réapparaître le header
    const triggerZone = document.createElement('div');
    triggerZone.className = 'nav-trigger-zone';
    document.body.appendChild(triggerZone);

    // Faire réapparaître le header lorsque la souris entre dans la zone de déclenchement
    triggerZone.addEventListener('mouseenter', function() {
        showHeader();
    });
    
    // Initialiser le timeout pour la réapparition automatique
    scheduleHeaderReappearance();
}); 