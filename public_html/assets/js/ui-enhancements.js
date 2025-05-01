/**
 * Améliorations de l'interface utilisateur pour GestiRep
 */

document.addEventListener('DOMContentLoaded', function() {
    // La référence au bouton flottant a été supprimée
    
    // Initialiser l'horloge en temps réel
    updateClock();
    
    // Ajouter des animations aux cartes
    addCardAnimations();
});

/**
 * Fonction addFloatingActionButton supprimée
 */

/**
 * Met à jour l'horloge en temps réel
 */
function updateClock() {
    const clockElement = document.getElementById('current-time');
    if (clockElement) {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        clockElement.textContent = `${hours}:${minutes}:${seconds}`;
        setTimeout(updateClock, 1000);
    }
}

/**
 * Ajoute des animations aux cartes, mais de manière sécurisée
 * pour éviter que le contenu principal ne disparaisse
 */
function addCardAnimations() {
    // Sélectionner uniquement les cartes qui ne sont pas des conteneurs principaux
    const cards = document.querySelectorAll('.card:not(.container-fluid):not(.row)');

    // Ajouter immédiatement la classe 'visible' pour éviter la disparition
    cards.forEach(card => {
        card.classList.add('visible');
        card.style.opacity = '1';
    });

    // Ensuite ajouter les animations avec un délai
    cards.forEach((card, index) => {
        // Ajouter un délai progressif pour l'animation
        card.style.animationDelay = `${index * 0.1}s`;

        // Ajouter la classe d'animation si elle n'existe pas déjà
        // mais s'assurer que l'élément reste visible
        if (!card.classList.contains('fade-in-up')) {
            // Stocker l'opacité actuelle
            const currentOpacity = card.style.opacity;

            // Ajouter la classe d'animation
            card.classList.add('fade-in-up');

            // S'assurer que l'élément reste visible
            card.style.opacity = currentOpacity;
        }
    });
}

/**
 * Améliore les tableaux avec des effets de survol
 */
function enhanceTables() {
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        if (!table.classList.contains('table-hover')) {
            table.classList.add('table-hover');
        }
    });
}

/**
 * Gestion responsive
 */
window.addEventListener('resize', function() {
    // Code pour le bouton flottant supprimé
});