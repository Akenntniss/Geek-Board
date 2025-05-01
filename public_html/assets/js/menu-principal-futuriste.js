/**
 * Script pour les effets futuristes du modal Menu Principal
 */
document.addEventListener('DOMContentLoaded', function() {
    // Référence au modal
    const modal = document.getElementById('menu_navigation_modal');
    
    if (!modal) return;
    
    // Créer les particules au chargement du modal
    modal.addEventListener('shown.bs.modal', function() {
        createParticles();
    });
    
    // Supprimer les particules lorsque le modal est fermé
    modal.addEventListener('hidden.bs.modal', function() {
        const particles = modal.querySelectorAll('.particle');
        particles.forEach(particle => particle.remove());
    });
    
    // Fonction pour créer les particules
    function createParticles() {
        const modalBody = modal.querySelector('.modal-body');
        
        // Nettoyer les anciennes particules
        const oldParticles = modalBody.querySelectorAll('.particle');
        oldParticles.forEach(particle => particle.remove());
        
        // Créer de nouvelles particules
        const particleCount = 25; // Plus de particules pour ce modal qui est plus grand
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');
            
            // Position aléatoire
            const xPos = Math.random() * 100; // Position horizontale en %
            const yPos = Math.random() * 100; // Position verticale en %
            
            // Taille aléatoire
            const size = Math.random() * 4 + 2; // Entre 2px et 6px
            
            // Délai d'animation aléatoire
            const delay = Math.random() * 5;
            
            // Durée d'animation aléatoire
            const duration = Math.random() * 5 + 5; // Entre 5s et 10s
            
            // Appliquer les styles
            particle.style.left = `${xPos}%`;
            particle.style.top = `${yPos}%`;
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.animationDelay = `${delay}s`;
            particle.style.animationDuration = `${duration}s`;
            
            // Ajouter au DOM
            modalBody.appendChild(particle);
        }
    }
    
    // Ajouter des effets de pulsation aléatoires aux icônes
    function addPulseEffects() {
        const icons = modal.querySelectorAll('.launchpad-icon');
        
        // Sélectionner 3 icônes aléatoires pour l'effet de pulsation
        const randomIndexes = new Set();
        while(randomIndexes.size < 3 && randomIndexes.size < icons.length) {
            const randomIndex = Math.floor(Math.random() * icons.length);
            randomIndexes.add(randomIndex);
        }
        
        // Réinitialiser tous les effets d'abord
        icons.forEach(icon => {
            icon.classList.remove('pulse-effect');
        });
        
        // Ajouter l'effet de pulsation aux icônes aléatoires
        Array.from(randomIndexes).forEach(index => {
            icons[index].classList.add('pulse-effect');
        });
    }
    
    // Ajouter la classe "pulse-effect" pour la pulsation
    modal.addEventListener('shown.bs.modal', function() {
        addPulseEffects();
        
        // Changer les icônes qui pulsent toutes les 5 secondes
        setInterval(addPulseEffects, 5000);
    });
    
    // Ajouter une classe au body pour les effets globaux
    modal.addEventListener('shown.bs.modal', function() {
        document.body.classList.add('menu-modal-active');
    });
    
    modal.addEventListener('hidden.bs.modal', function() {
        document.body.classList.remove('menu-modal-active');
    });
}); 