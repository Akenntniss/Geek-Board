/**
 * Script pour les effets futuristes du modal Nouvelles Actions
 */
document.addEventListener('DOMContentLoaded', function() {
    // Référence au modal
    const modal = document.getElementById('nouvelles_actions_modal');
    
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
        const particleCount = 15; // Nombre de particules
        
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
}); 