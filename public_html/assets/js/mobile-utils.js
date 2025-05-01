/**
 * GeekBoard - Utilitaires pour optimiser l'expérience mobile
 * Ce fichier contient des fonctionnalités spécifiques pour améliorer l'expérience sur appareils mobiles
 */

document.addEventListener('DOMContentLoaded', function() {
    // Détection des appareils mobiles
    const isMobile = window.innerWidth < 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    
    // Appliquer les optimisations mobiles si nécessaire
    if (isMobile) {
        applyMobileOptimizations();
        setupPullToRefresh();
        initTouchGestures();
        optimizeFormsForMobile();
    }
    
    // Écouter les changements de taille d'écran pour adapter l'interface
    window.addEventListener('resize', debounce(function() {
        const newIsMobile = window.innerWidth < 768;
        if (newIsMobile !== isMobile) {
            location.reload(); // Recharger pour appliquer les optimisations appropriées
        }
    }, 250));
});

/**
 * Applique diverses optimisations pour l'expérience mobile
 */
function applyMobileOptimizations() {
    // Ajouter la classe mobile au body
    document.body.classList.add('mobile-view');
    
    // Créer la barre de navigation mobile si elle n'existe pas déjà
    if (!document.querySelector('.navbar-mobile-bottom')) {
        createMobileNavBar();
    }
    
    // Simplifier les tables pour mobile
    const tables = document.querySelectorAll('.table:not(.simple-table)');
    tables.forEach(simplifyTableForMobile);
    
    // Remplacer certains éléments pour version mobile
    const elementsToReplace = document.querySelectorAll('[data-mobile-alternative]');
    elementsToReplace.forEach(element => {
        const mobileTemplate = document.getElementById(element.dataset.mobileAlternative);
        if (mobileTemplate) {
            element.innerHTML = mobileTemplate.innerHTML;
        }
    });
    
    // Optimiser le chargement des images
    lazyLoadImages();
    
    // Activer le mode plein écran pour PWA
    enableFullscreenMode();
}

/**
 * Crée une barre de navigation mobile adaptée aux interactions tactiles
 */
function createMobileNavBar() {
    // Créer la barre de navigation
    const mobileNav = document.createElement('nav');
    mobileNav.className = 'navbar-mobile-bottom';
    
    // Déterminer la page actuelle
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'accueil';
    
    // Définir les éléments de navigation
    const navItems = [
        { icon: 'bi-house-door', text: 'Accueil', url: '/index.php?page=accueil' },
        { icon: 'bi-people', text: 'Clients', url: '/index.php?page=clients' },
        { icon: 'bi-tools', text: 'Réparations', url: '/index.php?page=reparations' },
        { icon: 'bi-list-check', text: 'Tâches', url: '/index.php?page=taches' },
        { icon: 'bi-person-circle', text: 'Profil', url: '/index.php?page=parametre' }
    ];
    
    // Créer les liens
    navItems.forEach(item => {
        const link = document.createElement('a');
        link.className = 'nav-link';
        link.href = item.url;
        
        // Marquer le lien actif
        if (item.url.includes(currentPage)) {
            link.classList.add('active');
        }
        
        // Ajouter l'icône et le texte
        link.innerHTML = `<i class="bi ${item.icon}"></i><span>${item.text}</span>`;
        
        // Ajouter un effet tactile
        link.addEventListener('touchstart', function() {
            this.classList.add('touch-active');
        }, { passive: true });
        
        link.addEventListener('touchend', function() {
            this.classList.remove('touch-active');
        }, { passive: true });
        
        mobileNav.appendChild(link);
    });
    
    // Ajouter la barre de navigation au document
    document.body.appendChild(mobileNav);
    
    // Ajuster le padding du corps pour éviter le chevauchement avec la barre de navigation
    document.body.style.paddingBottom = '70px';
}

/**
 * Simplifie une table pour l'affichage mobile avec une présentation plus adaptée
 * @param {HTMLElement} table - L'élément de table à simplifier
 */
function simplifyTableForMobile(table) {
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
    const rows = table.querySelectorAll('tbody tr');
    
    // Créer un conteneur pour les cartes
    const cardsContainer = document.createElement('div');
    cardsContainer.className = 'mobile-cards';
    
    // Convertir chaque ligne en carte
    rows.forEach(row => {
        const card = document.createElement('div');
        card.className = 'card mobile-card mb-3';
        
        // Préserver les attributs data et classes importantes
        if (row.dataset.href) {
            card.dataset.href = row.dataset.href;
            card.classList.add('clickable');
            
            card.addEventListener('click', function() {
                window.location.href = this.dataset.href;
            });
        }
        
        const cells = row.querySelectorAll('td');
        
        // Créer le corps de la carte
        const cardBody = document.createElement('div');
        cardBody.className = 'card-body';
        
        // Ajouter un titre si la première cellule contient du texte
        if (cells[0] && cells[0].textContent.trim()) {
            const cardTitle = document.createElement('h5');
            cardTitle.className = 'card-title';
            cardTitle.textContent = cells[0].textContent.trim();
            cardBody.appendChild(cardTitle);
        }
        
        // Créer une liste de contenu pour le reste des cellules
        const contentList = document.createElement('ul');
        contentList.className = 'list-group list-group-flush mobile-card-list';
        
        // Parcourir les cellules à partir de la deuxième (indice 1)
        for (let i = 1; i < cells.length; i++) {
            // Ignorer les cellules vides ou celles qui ne contiennent que des espaces
            if (!cells[i] || !cells[i].textContent.trim()) continue;
            
            const listItem = document.createElement('li');
            listItem.className = 'list-group-item d-flex justify-content-between';
            
            // Préserver le contenu HTML pour les badges et icônes
            const label = document.createElement('span');
            label.className = 'text-muted';
            label.textContent = headers[i] || `Colonne ${i+1}`;
            
            const value = document.createElement('span');
            value.className = 'fw-bold';
            value.innerHTML = cells[i].innerHTML;
            
            listItem.appendChild(label);
            listItem.appendChild(value);
            contentList.appendChild(listItem);
        }
        
        // Ajouter les boutons d'action s'ils existent
        const actions = row.querySelector('.actions-cell, .btn-group, .action-buttons');
        if (actions) {
            const cardFooter = document.createElement('div');
            cardFooter.className = 'card-footer text-end bg-transparent';
            cardFooter.innerHTML = actions.innerHTML;
            card.appendChild(cardFooter);
        }
        
        cardBody.appendChild(contentList);
        card.appendChild(cardBody);
        cardsContainer.appendChild(card);
    });
    
    // Remplacer la table par les cartes tout en gardant le parent
    const tableParent = table.parentNode;
    tableParent.insertBefore(cardsContainer, table);
    
    // Cacher la table originale sur mobile
    table.classList.add('d-none', 'd-md-table');
    cardsContainer.classList.add('d-md-none');
}

/**
 * Configure le "pull-to-refresh" pour les utilisateurs mobiles
 */
function setupPullToRefresh() {
    let touchstartY = 0;
    let touchendY = 0;
    const threshold = 150; // Distance minimale pour déclencher le rechargement
    
    // Créer l'indicateur de rafraîchissement
    const refreshIndicator = document.createElement('div');
    refreshIndicator.className = 'pull-refresh-indicator';
    refreshIndicator.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
    refreshIndicator.style.cssText = 'position: fixed; top: -60px; left: 0; right: 0; height: 60px; display: flex; justify-content: center; align-items: center; background-color: rgba(255,255,255,0.9); transition: top 0.3s ease; z-index: 1000;';
    document.body.appendChild(refreshIndicator);
    
    // Gestionnaire pour le début du toucher
    document.addEventListener('touchstart', function(e) {
        // Vérifier si on est en haut de la page
        if (window.scrollY <= 0) {
            touchstartY = e.touches[0].clientY;
        }
    }, { passive: true });
    
    // Gestionnaire pour le déplacement du toucher
    document.addEventListener('touchmove', function(e) {
        if (touchstartY > 0 && window.scrollY <= 0) {
            const currentY = e.touches[0].clientY;
            const diff = currentY - touchstartY;
            
            if (diff > 0 && diff < threshold) {
                refreshIndicator.style.top = `${diff - 60}px`;
            }
        }
    }, { passive: true });
    
    // Gestionnaire pour la fin du toucher
    document.addEventListener('touchend', function(e) {
        if (touchstartY > 0) {
            touchendY = e.changedTouches[0].clientY;
            const diff = touchendY - touchstartY;
            
            if (diff > threshold && window.scrollY <= 0) {
                // Montrer complètement l'indicateur
                refreshIndicator.style.top = '0px';
                
                // Recharger la page après un délai
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                // Masquer l'indicateur
                refreshIndicator.style.top = '-60px';
            }
            
            // Réinitialiser
            touchstartY = 0;
            touchendY = 0;
        }
    }, { passive: true });
}

/**
 * Configure des gestes tactiles avancés pour la navigation
 */
function initTouchGestures() {
    let touchStartX = 0;
    let touchEndX = 0;
    const minSwipeDistance = 100; // Distance minimale pour un swipe
    
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.touches[0].clientX;
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].clientX;
        handleGesture();
    }, { passive: true });
    
    function handleGesture() {
        const swipeDistance = touchEndX - touchStartX;
        
        // Ignorer les petits mouvements
        if (Math.abs(swipeDistance) < minSwipeDistance) return;
        
        // Déterminer la direction du swipe
        if (swipeDistance > 0) {
            // Swipe vers la droite - retour en arrière
            navigateBack();
        } else {
            // Swipe vers la gauche - afficher menu (si implémenté)
            // showSidebar();
        }
    }
    
    function navigateBack() {
        // Ne fonctionner que si l'historique contient au moins 2 entrées
        if (window.history.length > 1) {
            window.history.back();
        }
    }
}

/**
 * Optimise les formulaires pour les appareils mobiles
 */
function optimizeFormsForMobile() {
    // Formulaires à optimiser
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Augmenter la taille des champs pour faciliter la saisie
        const inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
        
        inputs.forEach(input => {
            // Ajouter des classes pour une meilleure expérience tactile
            input.classList.add('mobile-input');
            
            // Gérer le focus et le blur pour l'expérience tactile
            input.addEventListener('focus', function() {
                this.closest('.form-group')?.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.closest('.form-group')?.classList.remove('focused');
            });
            
            // Ajuster le comportement des champs numériques
            if (input.type === 'number') {
                // Permettre l'utilisation du clavier numérique natif
                input.setAttribute('inputmode', 'numeric');
                input.setAttribute('pattern', '[0-9]*');
            }
            
            // Optimiser les champs de recherche
            if (input.type === 'search' || input.name.includes('search')) {
                input.setAttribute('autocomplete', 'off');
                input.setAttribute('autocorrect', 'off');
                input.setAttribute('spellcheck', 'false');
            }
        });
        
        // Ajouter un gestionnaire pour les soumissions de formulaire
        form.addEventListener('submit', function(e) {
            // Vérifier la validation HTML5
            if (!this.checkValidity()) {
                e.preventDefault();
                // Faire défiler jusqu'au premier champ invalide
                const invalidField = this.querySelector(':invalid');
                if (invalidField) {
                    invalidField.focus();
                    invalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
}

/**
 * Active le mode plein écran pour les PWA
 */
function enableFullscreenMode() {
    // Vérifier si c'est une PWA
    const isPWA = window.matchMedia('(display-mode: standalone)').matches || 
                 window.navigator.standalone ||
                 document.referrer.includes('android-app://');
    
    if (isPWA) {
        // Masquer les éléments non nécessaires en mode PWA
        document.querySelectorAll('[data-hide-in-pwa="true"]').forEach(el => {
            el.classList.add('d-none');
        });
        
        // Ajouter la classe PWA au body
        document.body.classList.add('pwa-mode');
        
        // Optimisation spécifique pour iOS
        if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            document.body.classList.add('ios-pwa');
            
            // Gestion de Dynamic Island / notch sur iPhone X et plus récents
            if (window.screen.height >= 812 && window.screen.width >= 375) {
                document.body.classList.add('notch-device');
            }
        }
    }
}

/**
 * Implémente le chargement différé des images pour améliorer les performances
 */
function lazyLoadImages() {
    // Vérifier si IntersectionObserver est disponible
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');
                    
                    if (src) {
                        img.src = src;
                        img.removeAttribute('data-src');
                        img.classList.add('fade-in');
                    }
                    
                    observer.unobserve(img);
                }
            });
        });
        
        // Observer toutes les images avec l'attribut data-src
        const lazyImages = document.querySelectorAll('img[data-src]');
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback pour les navigateurs sans support d'IntersectionObserver
        const lazyImages = document.querySelectorAll('img[data-src]');
        lazyImages.forEach(img => {
            img.src = img.getAttribute('data-src');
            img.removeAttribute('data-src');
        });
    }
}

/**
 * Utilitaire de debounce pour éviter les appels répétés de fonctions
 * @param {Function} func - La fonction à exécuter
 * @param {number} delay - Le délai en millisecondes
 * @returns {Function} - La fonction debounced
 */
function debounce(func, delay) {
    let timer;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timer);
        timer = setTimeout(() => func.apply(context, args), delay);
    };
} 