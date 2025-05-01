// Particules et animations pour le modal de réparation active
document.addEventListener('DOMContentLoaded', function() {
    // Création des particules pour les modals futuristes
    const containers = document.querySelectorAll('.particles-container');
    
    containers.forEach(container => {
        for (let i = 0; i < 15; i++) {
            createParticle(container);
        }
    });
    
    function createParticle(container) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        // Styles de base pour les particules
        particle.style.position = 'absolute';
        particle.style.width = `${Math.random() * 5 + 2}px`;
        particle.style.height = particle.style.width;
        particle.style.backgroundColor = 'rgba(30, 144, 255, 0.3)';
        particle.style.borderRadius = '50%';
        particle.style.top = `${Math.random() * 100}%`;
        particle.style.left = `${Math.random() * 100}%`;
        particle.style.boxShadow = '0 0 10px rgba(30, 144, 255, 0.5)';
        
        // Animation
        particle.style.animation = `floatParticle ${Math.random() * 10 + 10}s linear infinite`;
        particle.style.opacity = Math.random() * 0.5 + 0.2;
        
        container.appendChild(particle);
    }
    
    // Ajout du keyframe pour l'animation
    if (!document.getElementById('particle-keyframes')) {
        const style = document.createElement('style');
        style.id = 'particle-keyframes';
        style.textContent = `
            @keyframes floatParticle {
                0% {
                    transform: translate(0, 0) rotate(0deg);
                }
                33% {
                    transform: translate(${Math.random() * 30 - 15}px, ${Math.random() * 30 - 15}px) rotate(120deg);
                }
                66% {
                    transform: translate(${Math.random() * 30 - 15}px, ${Math.random() * 30 - 15}px) rotate(240deg);
                }
                100% {
                    transform: translate(0, 0) rotate(360deg);
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Lorsque le modal est affiché, démarrer l'effet de particules et les animations séquentielles
    const activeRepairModal = document.getElementById('activeRepairModal');
    if (activeRepairModal) {
        activeRepairModal.addEventListener('shown.bs.modal', function() {
            // Ajouter les classes d'animation aux éléments avec reveal-item
            setTimeout(() => {
                const revealItems = activeRepairModal.querySelectorAll('.reveal-item');
                revealItems.forEach((item, index) => {
                    item.style.animationDelay = `${index * 0.1}s`;
                    item.style.animationPlayState = 'running';
                });
            }, 100);
            
            // Effet de brève pulsation lors de l'ouverture
            activeRepairModal.classList.add('pulse-effect');
            setTimeout(() => {
                activeRepairModal.classList.remove('pulse-effect');
            }, 700);
        });
        
        // Réinitialiser les animations lorsque le modal est fermé
        activeRepairModal.addEventListener('hidden.bs.modal', function() {
            const revealItems = activeRepairModal.querySelectorAll('.reveal-item');
            revealItems.forEach(item => {
                item.style.opacity = '0';
                item.style.animationPlayState = 'paused';
            });
        });
    }
    
    // Ajouter des effets holographiques sur les boutons lors du traitement
    const completeButtons = document.querySelectorAll('.complete-btn');
    completeButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.classList.add('holographic-effect');
            
            // Simuler un effet de chargement pendant 800ms
            setTimeout(() => {
                this.classList.remove('holographic-effect');
            }, 800);
        });
    });
}); 