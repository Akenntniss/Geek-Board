/**
 * Script pour adapter l'affichage des tableaux en mode mobile
 * Ajuste la position et l'affichage du contenu
 */
document.addEventListener('DOMContentLoaded', function() {
    // S'assurer que le conteneur a la bonne hauteur sur mobile
    if (window.innerWidth <= 768) {
        const contentContainer = document.querySelector('.commandes-content-container');
        if (contentContainer) {
            const contentHeight = contentContainer.scrollHeight;
            document.body.style.minHeight = (contentHeight + 90) + 'px';
        }
        
        // S'assurer que le tableau ne dépasse pas la largeur de l'écran
        const tableContainer = document.querySelector('.mobile-table-container');
        if (tableContainer) {
            tableContainer.style.maxWidth = (window.innerWidth - 30) + 'px'; // 15px de padding de chaque côté
            
            // Vérifier si le contenu est plus large que le conteneur
            if (tableContainer.scrollWidth > tableContainer.clientWidth) {
                // Ajouter un indicateur de défilement
                const scrollIndicator = document.createElement('div');
                scrollIndicator.className = 'scroll-indicator';
                scrollIndicator.innerHTML = '<i class="fas fa-arrows-alt-h"></i> Faire défiler horizontalement pour voir tout le tableau';
                tableContainer.parentNode.insertBefore(scrollIndicator, tableContainer);
                
                // Cacher l'indicateur après le premier défilement
                tableContainer.addEventListener('scroll', function() {
                    scrollIndicator.style.opacity = '0';
                    setTimeout(() => {
                        scrollIndicator.remove();
                    }, 300);
                }, { once: true });
            }
        }
    }
}); 