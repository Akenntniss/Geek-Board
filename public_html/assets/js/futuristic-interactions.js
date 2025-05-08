/**
 * Futuristic Interactions JS
 * Améliore l'interface utilisateur avec des effets modernes et des animations
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser tous les composants interactifs
    initializeModernEffects();
    initializeTouchEffects();
    initializeCardEffects();
    initializeFilterButtons();
    initializeAdvancedSearch();
    initializeStatusUpdates();
    initializeNotifications();
    
    // Appliquer une transition lors du chargement initial
    document.body.classList.add('loaded');
});

/**
 * Initialise les effets modernes sur l'interface
 */
function initializeModernEffects() {
    // Animation d'entrée pour la page
    const pageContent = document.querySelector('.page-container');
    if (pageContent) {
        pageContent.style.opacity = '0';
        pageContent.style.transform = 'translateY(10px)';
        
        setTimeout(() => {
            pageContent.style.transition = 'all 0.5s ease-out';
            pageContent.style.opacity = '1';
            pageContent.style.transform = 'translateY(0)';
        }, 100);
    }
    
    // Effet de survol avancé pour les boutons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mousemove', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            this.style.setProperty('--x-pos', `${x}px`);
            this.style.setProperty('--y-pos', `${y}px`);
        });
    });
    
    // Effet parallaxe pour les cartes
    document.addEventListener('mousemove', function(e) {
        const cards = document.querySelectorAll('.dashboard-card');
        const mouseX = e.clientX / window.innerWidth - 0.5;
        const mouseY = e.clientY / window.innerHeight - 0.5;
        
        cards.forEach(card => {
            const rect = card.getBoundingClientRect();
            // Seulement appliquer l'effet si la souris est proche de la carte
            if (e.clientX > rect.left - 100 && e.clientX < rect.right + 100 && 
                e.clientY > rect.top - 100 && e.clientY < rect.bottom + 100) {
                card.style.transform = `rotateX(${mouseY * -3}deg) rotateY(${mouseX * 3}deg)`;
            } else {
                card.style.transform = '';
            }
        });
    });
}

/**
 * Initialise les effets tactiles pour mobile
 */
function initializeTouchEffects() {
    // Effet de feedback tactile
    const interactiveElements = document.querySelectorAll('.btn, .filter-btn, .dashboard-card, a');
    
    interactiveElements.forEach(element => {
        element.addEventListener('touchstart', function() {
            this.classList.add('touch-active');
        });
        
        ['touchend', 'touchcancel'].forEach(event => {
            element.addEventListener(event, function() {
                this.classList.remove('touch-active');
            });
        });
    });
    
    // Détecter la plateforme pour les optimisations iOS
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    if (isIOS) {
        document.documentElement.classList.add('ios-device');
    }
}

/**
 * Initialise les effets pour les cartes de réparation
 */
function initializeCardEffects() {
    // Effet 3D pour les cartes
    const cards = document.querySelectorAll('.dashboard-card');
    
    cards.forEach(card => {
        // Détection de la plateforme
        const isMobile = window.matchMedia('(max-width: 767px)').matches;
        
        if (!isMobile) {
            card.addEventListener('mouseenter', handleCardMouseEnter);
            card.addEventListener('mousemove', handleCardMouseMove);
            card.addEventListener('mouseleave', handleCardMouseLeave);
        }
        
        // Ajouter une animation d'entrée progressive
        card.style.opacity = '0';
        card.style.transform = 'translateY(10px)';
    });
    
    // Animation d'entrée séquentielle pour les cartes
    if (cards.length > 0) {
        let delay = 100;
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, delay + (index * 50));
        });
    }
}

/**
 * Gère l'entrée de la souris sur une carte
 */
function handleCardMouseEnter(e) {
    this.classList.add('card-hover');
    updateCardTransform.call(this, e);
}

/**
 * Gère le mouvement de la souris sur une carte
 */
function handleCardMouseMove(e) {
    updateCardTransform.call(this, e);
}

/**
 * Gère la sortie de la souris d'une carte
 */
function handleCardMouseLeave() {
    this.classList.remove('card-hover');
    this.style.transform = '';
}

/**
 * Met à jour la transformation 3D d'une carte
 */
function updateCardTransform(e) {
    const card = this;
    const cardRect = card.getBoundingClientRect();
    const cardCenterX = cardRect.left + cardRect.width / 2;
    const cardCenterY = cardRect.top + cardRect.height / 2;
    const mouseX = e.clientX - cardCenterX;
    const mouseY = e.clientY - cardCenterY;
    
    // Calcul de l'angle de rotation basé sur la position de la souris
    const rotateY = (mouseX / (cardRect.width / 2)) * 5;
    const rotateX = -(mouseY / (cardRect.height / 2)) * 5;
    
    // Appliquer la transformation 3D
    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
}

/**
 * Initialise les effets pour les boutons de filtre
 */
function initializeFilterButtons() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Animation lors du clic
            this.classList.add('filter-active');
            setTimeout(() => {
                this.classList.remove('filter-active');
            }, 300);
            
            // Animation pour les nombres
            const countElement = this.querySelector('.count');
            if (countElement) {
                countElement.classList.add('count-bump');
                setTimeout(() => {
                    countElement.classList.remove('count-bump');
                }, 300);
            }
        });
    });
}

/**
 * Initialise la recherche avancée
 */
function initializeAdvancedSearch() {
    const searchForm = document.querySelector('.search-form');
    if (!searchForm) return;
    
    const searchInput = searchForm.querySelector('input[name="search"]');
    const searchButton = searchForm.querySelector('button[type="submit"]');
    
    if (searchInput) {
        // Animation lors de la saisie
        searchInput.addEventListener('focus', function() {
            searchForm.querySelector('.input-group').classList.add('input-focus');
        });
        
        searchInput.addEventListener('blur', function() {
            searchForm.querySelector('.input-group').classList.remove('input-focus');
        });
        
        // Auto-complétion simplifiée (à connecter à une API de suggestions si disponible)
        searchInput.addEventListener('input', function() {
            if (this.value.length > 2) {
                // Simuler une recherche en temps réel
                if (typeof showSearchSuggestions === 'function') {
                    showSearchSuggestions(this.value);
                }
            }
        });
    }
    
    if (searchButton) {
        searchButton.addEventListener('click', function(e) {
            if (searchInput.value.trim() === '') {
                e.preventDefault();
                // Animation d'erreur
                searchInput.classList.add('search-error');
                setTimeout(() => {
                    searchInput.classList.remove('search-error');
                }, 500);
            } else {
                // Animation de recherche
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                // La soumission du formulaire se poursuit normalement
            }
        });
    }
}

/**
 * Gère les mises à jour de statut avec effets visuels
 */
function initializeStatusUpdates() {
    // Détecter les changements de statut
    const statusIndicators = document.querySelectorAll('.status-indicator .badge');
    
    statusIndicators.forEach(badge => {
        // Stocker le statut initial
        badge.dataset.initialStatus = badge.textContent.trim();
        
        // Observer les changements de contenu
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.type === 'childList' && 
                    badge.textContent.trim() !== badge.dataset.initialStatus) {
                    // Statut changé - appliquer l'effet
                    badge.classList.add('status-update');
                    setTimeout(() => {
                        badge.classList.remove('status-update');
                    }, 500);
                    
                    // Mettre à jour le statut initial
                    badge.dataset.initialStatus = badge.textContent.trim();
                }
            });
        });
        
        // Configurer l'observateur
        observer.observe(badge, { childList: true, subtree: true });
    });
}

/**
 * Système de notifications amélioré
 */
function initializeNotifications() {
    // Créer le conteneur de notifications s'il n'existe pas
    let notificationsContainer = document.getElementById('notificationsContainer');
    
    if (!notificationsContainer) {
        notificationsContainer = document.createElement('div');
        notificationsContainer.id = 'notificationsContainer';
        notificationsContainer.className = 'notifications-container';
        document.body.appendChild(notificationsContainer);
    }
    
    // Fonction globale pour afficher des notifications
    window.showNotification = function(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        // Icône selon le type
        let icon = 'info-circle';
        if (type === 'success') icon = 'check-circle';
        if (type === 'warning') icon = 'exclamation-triangle';
        if (type === 'error') icon = 'times-circle';
        
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-${icon}"></i>
            </div>
            <div class="notification-content">${message}</div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Ajouter au DOM
        notificationsContainer.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Bouton de fermeture
        notification.querySelector('.notification-close').addEventListener('click', () => {
            closeNotification(notification);
        });
        
        // Fermeture automatique
        if (duration > 0) {
            setTimeout(() => {
                closeNotification(notification);
            }, duration);
        }
        
        // Fermeture au survol
        notification.addEventListener('click', function(e) {
            if (!e.target.closest('.notification-close')) {
                closeNotification(notification);
            }
        });
        
        function closeNotification(notification) {
            notification.classList.remove('show');
            notification.classList.add('hide');
            
            // Supprimer après l'animation
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    };
}

/**
 * Ajoute des effets lorsque le contenu est visible dans la fenêtre
 */
function initializeScrollEffects() {
    // Détecter les éléments à animer
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    
    // Créer l'observateur d'intersection
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view');
                // Option: désinscrire après animation
                // observer.unobserve(entry.target);
            } else {
                entry.target.classList.remove('in-view');
            }
        });
    }, {
        threshold: 0.1 // Déclencher lorsque 10% de l'élément est visible
    });
    
    // Observer chaque élément
    animatedElements.forEach(element => {
        observer.observe(element);
    });
}

/**
 * Améliore les modals avec des effets avancés
 */
function enhanceModals() {
    // Ajouter des transitions personnalisées aux modals Bootstrap
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            // Ajouter des classes pour l'animation
            this.classList.add('modal-fade-in');
        });
        
        modal.addEventListener('shown.bs.modal', function() {
            // Ajouter un effet de mise au point sur le premier champ
            const firstInput = this.querySelector('input, textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        });
        
        modal.addEventListener('hide.bs.modal', function() {
            this.classList.add('modal-fade-out');
        });
        
        modal.addEventListener('hidden.bs.modal', function() {
            this.classList.remove('modal-fade-in', 'modal-fade-out');
        });
    });
}

// Exécuter enhanceModals une fois que les modals sont initialisés
if (typeof bootstrap !== 'undefined') {
    enhanceModals();
} else {
    window.addEventListener('load', function() {
        if (typeof bootstrap !== 'undefined') {
            enhanceModals();
        }
    });
}

// Exposer les fonctions au contexte global pour une utilisation éventuelle par d'autres scripts
window.FuturisticUI = {
    initializeModernEffects,
    initializeTouchEffects,
    initializeCardEffects,
    initializeFilterButtons,
    initializeAdvancedSearch,
    initializeStatusUpdates,
    initializeNotifications,
    initializeScrollEffects,
    enhanceModals
}; 