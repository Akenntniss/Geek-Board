/**
 * Gestionnaire de modals modernes
 * Gère les animations et les interactions des modals
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des modals Bootstrap
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        // Ajouter la classe pour les animations
        modal.classList.add('futuristic-modal');
        
        // Gérer l'ouverture du modal
        modal.addEventListener('show.bs.modal', function(event) {
            // Ajouter l'effet de particules si disponible
            if (typeof createParticles === 'function') {
                createParticles(modal);
            }
            
            // Animer les éléments internes
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.classList.add('fade-in');
            }
            
            // Animer les éléments du corps
            const modalBody = modal.querySelector('.modal-body');
            if (modalBody) {
                const elements = modalBody.children;
                Array.from(elements).forEach((element, index) => {
                    element.classList.add('slide-up');
                    element.style.animationDelay = `${index * 0.1}s`;
                });
            }
        });
        
        // Gérer la fermeture du modal
        modal.addEventListener('hide.bs.modal', function(event) {
            // Nettoyer les animations
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.classList.remove('fade-in');
            }
            
            const modalBody = modal.querySelector('.modal-body');
            if (modalBody) {
                const elements = modalBody.children;
                Array.from(elements).forEach(element => {
                    element.classList.remove('slide-up');
                    element.style.animationDelay = '';
                });
            }
        });
        
        // Gérer le focus et l'accessibilité
        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        const firstFocusableElement = focusableElements[0];
        const lastFocusableElement = focusableElements[focusableElements.length - 1];
        
        modal.addEventListener('keydown', function(event) {
            if (event.key === 'Tab') {
                if (event.shiftKey) {
                    if (document.activeElement === firstFocusableElement) {
                        lastFocusableElement.focus();
                        event.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusableElement) {
                        firstFocusableElement.focus();
                        event.preventDefault();
                    }
                }
            }
        });
    });
    
    // Fonction pour créer des particules
    window.createParticles = function(modal) {
        const particlesContainer = document.createElement('div');
        particlesContainer.className = 'particles-container';
        
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = `${Math.random() * 100}%`;
            particle.style.animationDelay = `${Math.random() * 2}s`;
            particlesContainer.appendChild(particle);
        }
        
        modal.appendChild(particlesContainer);
        
        // Nettoyer les particules après l'animation
        setTimeout(() => {
            particlesContainer.remove();
        }, 2000);
    };
    
    // Gestionnaire pour les modals de recherche
    const searchModals = document.querySelectorAll('.modal[data-modal-type="search"]');
    
    searchModals.forEach(modal => {
        const searchInput = modal.querySelector('input[type="search"], input[type="text"]');
        const searchButton = modal.querySelector('.search-button');
        
        if (searchInput) {
            // Ajouter l'effet de focus
            searchInput.addEventListener('focus', function() {
                this.parentElement.classList.add('search-focused');
            });
            
            searchInput.addEventListener('blur', function() {
                this.parentElement.classList.remove('search-focused');
            });
            
            // Gérer la recherche avec debounce
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (typeof performSearch === 'function') {
                        performSearch(this.value);
                    }
                }, 300);
            });
        }
        
        if (searchButton) {
            searchButton.addEventListener('click', function() {
                if (searchInput && typeof performSearch === 'function') {
                    performSearch(searchInput.value);
                }
            });
        }
    });
});

// Styles CSS pour les particules
const style = document.createElement('style');
style.textContent = `
    .particles-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
    }
    
    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: rgba(30, 144, 255, 0.5);
        border-radius: 50%;
        animation: particleFloat 2s ease-out forwards;
    }
    
    @keyframes particleFloat {
        0% {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        100% {
            transform: translateY(-100px) scale(0);
            opacity: 0;
        }
    }
    
    .search-focused {
        box-shadow: 0 0 0 3px rgba(30, 144, 255, 0.25);
    }
`;

document.head.appendChild(style); 