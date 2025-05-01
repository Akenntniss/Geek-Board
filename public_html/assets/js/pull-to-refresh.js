/**
 * GeekBoard - Pull-to-refresh
 * Module pour ajouter la fonctionnalité de glissement vers le bas pour rafraîchir
 */

class PullToRefresh {
    constructor(options = {}) {
        this.defaults = {
            targetElement: document.body,
            threshold: 60,
            maxPullDownDistance: 80,
            animationDuration: 300,
            distanceToRefresh: 50,
            resistance: 2.5,
            onRefresh: () => window.location.reload(),
            refreshText: 'Relâchez pour rafraîchir',
            pullingText: 'Tirez pour rafraîchir',
            refreshingText: 'Rafraîchissement...',
            backgroundColor: '#0078e8',
            textColor: '#ffffff',
            iconColor: '#ffffff',
            ptrIndicatorContainerId: 'ptr-indicator-container'
        };

        this.settings = { ...this.defaults, ...options };
        this.isPulling = false;
        this.isRefreshing = false;
        this.pullStartY = 0;
        this.currentY = 0;
        this.distance = 0;
        this.ptrIndicatorContainer = null;
        this.ptrIndicator = null;
        this.ptrText = null;
        this.ptrIcon = null;
        this.touchIdentifier = null;

        this.init();
    }

    init() {
        // Créer le conteneur pour l'indicateur de refresh
        this.createPTRElement();
        
        // Ajouter les écouteurs d'événements
        this.addEventListeners();
    }

    createPTRElement() {
        // Vérifier si l'élément existe déjà
        const existingContainer = document.getElementById(this.settings.ptrIndicatorContainerId);
        if (existingContainer) {
            existingContainer.remove();
        }

        // Créer le conteneur principal
        this.ptrIndicatorContainer = document.createElement('div');
        this.ptrIndicatorContainer.id = this.settings.ptrIndicatorContainerId;
        this.ptrIndicatorContainer.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: ${this.settings.backgroundColor};
            color: ${this.settings.textColor};
            transform: translateY(-70px);
            transition: transform ${this.settings.animationDuration}ms;
            z-index: 9999;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0 20px;
        `;

        // Créer l'indicateur
        this.ptrIndicator = document.createElement('div');
        this.ptrIndicator.className = 'ptr-indicator';
        this.ptrIndicator.style.cssText = `
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        `;

        // Créer l'icône
        this.ptrIcon = document.createElement('span');
        this.ptrIcon.className = 'ptr-icon';
        this.ptrIcon.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="${this.settings.iconColor}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12l7-7 7 7"/>
            </svg>
        `;

        // Créer le texte
        this.ptrText = document.createElement('span');
        this.ptrText.className = 'ptr-text';
        this.ptrText.textContent = this.settings.pullingText;

        // Assembler les éléments
        this.ptrIndicator.appendChild(this.ptrIcon);
        this.ptrIndicator.appendChild(this.ptrText);
        this.ptrIndicatorContainer.appendChild(this.ptrIndicator);

        // Ajouter au DOM
        document.body.appendChild(this.ptrIndicatorContainer);
    }

    addEventListeners() {
        // Écouteurs pour desktop/mobile
        document.addEventListener('touchstart', this.onTouchStart.bind(this), { passive: false });
        document.addEventListener('touchmove', this.onTouchMove.bind(this), { passive: false });
        document.addEventListener('touchend', this.onTouchEnd.bind(this), { passive: false });
        
        // Pour le mode iOS PWA
        document.addEventListener('touchcancel', this.onTouchEnd.bind(this), { passive: false });
    }

    onTouchStart(e) {
        // Ne pas déclencher si on est en train de rafraîchir
        if (this.isRefreshing) return;
        
        // Ne déclencher que si on est au sommet de la page
        if (window.scrollY > 5) return;
        
        // Stocker l'identifiant du premier toucher
        if (e.touches.length === 1) {
            this.touchIdentifier = e.touches[0].identifier;
            this.isPulling = true;
            this.pullStartY = e.touches[0].clientY;
        }
    }

    onTouchMove(e) {
        if (!this.isPulling) return;
        
        // Trouver le bon touch si multi-touch
        let touch = null;
        for (let i = 0; i < e.changedTouches.length; i++) {
            if (e.changedTouches[i].identifier === this.touchIdentifier) {
                touch = e.changedTouches[i];
                break;
            }
        }
        
        if (!touch) return;
        
        // Calculer la distance
        this.currentY = touch.clientY;
        this.distance = (this.currentY - this.pullStartY) / this.settings.resistance;
        
        // Empêcher le comportement par défaut uniquement si on tire vers le bas
        if (this.distance > 0) {
            e.preventDefault();
            
            // Limiter la distance
            if (this.distance > this.settings.maxPullDownDistance) {
                this.distance = this.settings.maxPullDownDistance;
            }
            
            // Mettre à jour l'interface
            this.updateUI();
        } else {
            // Réinitialiser si on tire vers le haut
            this.reset();
        }
    }

    onTouchEnd(e) {
        if (!this.isPulling) return;
        
        // Vérifier que c'est le bon toucher
        let isCorrectTouch = false;
        for (let i = 0; i < e.changedTouches.length; i++) {
            if (e.changedTouches[i].identifier === this.touchIdentifier) {
                isCorrectTouch = true;
                break;
            }
        }
        
        if (!isCorrectTouch) return;
        
        // Si la distance est suffisante, rafraîchir
        if (this.distance >= this.settings.distanceToRefresh) {
            this.refresh();
        } else {
            this.reset();
        }
        
        this.isPulling = false;
        this.touchIdentifier = null;
    }

    updateUI() {
        // Mettre à jour la position de l'indicateur
        this.ptrIndicatorContainer.style.transform = `translateY(${this.distance - 60}px)`;
        
        // Mettre à jour le texte selon la distance
        if (this.distance >= this.settings.distanceToRefresh) {
            this.ptrText.textContent = this.settings.refreshText;
            this.ptrIcon.style.transform = 'rotate(180deg)';
        } else {
            this.ptrText.textContent = this.settings.pullingText;
            this.ptrIcon.style.transform = 'rotate(0deg)';
        }
    }

    refresh() {
        this.isRefreshing = true;
        
        // Mettre à jour l'interface
        this.ptrIndicatorContainer.style.transform = 'translateY(0)';
        this.ptrText.textContent = this.settings.refreshingText;
        
        // Ajouter une animation de rotation à l'icône
        this.ptrIcon.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="${this.settings.iconColor}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ptr-spinner">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 6v6l4 2"></path>
            </svg>
        `;
        
        const spinner = this.ptrIcon.querySelector('.ptr-spinner');
        spinner.style.animation = 'ptr-rotate 2s linear infinite';
        
        // Ajouter le style d'animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ptr-rotate {
                100% {
                    transform: rotate(360deg);
                }
            }
        `;
        document.head.appendChild(style);
        
        // Exécuter la fonction de rafraîchissement
        setTimeout(() => {
            this.settings.onRefresh();
        }, 500);
    }

    reset() {
        if (this.isRefreshing) return;
        
        // Replacer l'indicateur
        this.ptrIndicatorContainer.style.transform = 'translateY(-70px)';
        
        // Réinitialiser le texte
        this.ptrText.textContent = this.settings.pullingText;
        
        // Réinitialiser l'icône
        this.ptrIcon.style.transform = 'rotate(0deg)';
    }
}

// Initialiser le pull-to-refresh
document.addEventListener('DOMContentLoaded', () => {
    let ptrSettings = {
        onRefresh: () => {
            // Si nous sommes en mode PWA, ajouter un paramètre pour éviter le cache
            if (localStorage.getItem('isPwaMode') === 'true') {
                window.location.href = window.location.pathname + '?pwa=1&timestamp=' + Date.now();
            } else {
                window.location.reload();
            }
        }
    };
    
    // Support iOS spécifique
    if (/iPhone|iPad|iPod/.test(navigator.userAgent) || document.body.classList.contains('ios-pwa')) {
        ptrSettings.resistance = 3; // Plus de résistance pour iOS
    }
    
    // Initialiser
    const ptr = new PullToRefresh(ptrSettings);
}); 