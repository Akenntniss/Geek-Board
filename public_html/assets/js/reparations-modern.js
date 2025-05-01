/**
 * reparations-modern.js
 * Script pour améliorer l'expérience utilisateur sur la page des réparations
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation des améliorations modernes pour la page des réparations');
    
    // Référence aux éléments DOM
    const cardsContainer = document.querySelector('.repair-cards-container');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const searchField = document.querySelector('.search-form .form-control');
    const repairCards = document.querySelectorAll('.dashboard-card.repair-row');
    
    // Appliquer des effets d'entrée aux cartes
    applyEntryEffects();
    
    // Améliorer les interactions avec les boutons de filtre
    enhanceFilterButtons();
    
    // Améliorer l'interaction avec le champ de recherche
    enhanceSearchField();
    
    // Améliorer les interactions avec les cartes de réparation
    enhanceRepairCards();
    
    /**
     * Appliquer des effets d'entrée aux cartes avec un délai séquentiel
     */
    function applyEntryEffects() {
        // S'assurer que les cartes sont bien chargées avant d'appliquer les animations
        if (repairCards && repairCards.length > 0) {
            repairCards.forEach((card, index) => {
                // Stocker l'index comme variable CSS pour l'animation
                card.style.setProperty('--animation-delay', index + 1);
                
                // Ajouter une classe pour déclencher l'animation au hover
                card.addEventListener('mouseenter', function() {
                    this.classList.add('card-hovered');
                });
                
                card.addEventListener('mouseleave', function() {
                    this.classList.remove('card-hovered');
                });
            });
            
            console.log(`Effets d'entrée appliqués à ${repairCards.length} cartes`);
        } else {
            console.log('Aucune carte trouvée pour appliquer les effets d\'entrée');
        }
    }
    
    /**
     * Améliorer les interactions avec les boutons de filtre
     */
    function enhanceFilterButtons() {
        if (filterButtons && filterButtons.length > 0) {
            filterButtons.forEach(btn => {
                // Effet d'onde au clic
                btn.addEventListener('click', function(e) {
                    if (!this.classList.contains('no-ripple')) {
                        const rect = this.getBoundingClientRect();
                        const x = e.clientX - rect.left;
                        const y = e.clientY - rect.top;
                        
                        const ripple = document.createElement('span');
                        ripple.classList.add('ripple-effect');
                        ripple.style.left = `${x}px`;
                        ripple.style.top = `${y}px`;
                        
                        this.appendChild(ripple);
                        
                        setTimeout(() => {
                            ripple.remove();
                        }, 600);
                    }
                });
                
                // Mettre à jour dynamiquement le nombre de réparations dans les compteurs
                const countElement = btn.querySelector('.count');
                if (countElement) {
                    const countValue = parseInt(countElement.textContent);
                    if (countValue > 0) {
                        countElement.classList.add('has-items');
                    }
                }
            });
            
            console.log(`Interactions améliorées pour ${filterButtons.length} boutons de filtre`);
        }
    }
    
    /**
     * Améliorer l'interaction avec le champ de recherche
     */
    function enhanceSearchField() {
        if (searchField) {
            // Focus automatique sur le champ de recherche si l'URL contient un paramètre search
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('search')) {
                setTimeout(() => {
                    searchField.focus();
                    // Positionner le curseur à la fin du texte
                    const val = searchField.value;
                    searchField.value = '';
                    searchField.value = val;
                }, 500);
            }
            
            // Améliorer l'apparence du champ de recherche
            const searchCard = document.querySelector('.search-card');
            if (searchCard) {
                searchField.addEventListener('focus', function() {
                    searchCard.classList.add('search-focused');
                });
                
                searchField.addEventListener('blur', function() {
                    searchCard.classList.remove('search-focused');
                });
            }
            
            console.log('Interactions de recherche améliorées');
        }
    }
    
    /**
     * Améliorer les interactions avec les cartes de réparation
     */
    function enhanceRepairCards() {
        if (repairCards && repairCards.length > 0) {
            repairCards.forEach(card => {
                // Trouver les boutons d'action dans le footer
                const actionButtons = card.querySelectorAll('.card-footer .btn');
                
                actionButtons.forEach(btn => {
                    // Ajouter un effet de survol amélioré
                    btn.addEventListener('mouseenter', function() {
                        this.classList.add('btn-hover-effect');
                        
                        // Trouver l'icône et ajouter une classe d'animation
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.classList.add('icon-hover-effect');
                        }
                    });
                    
                    btn.addEventListener('mouseleave', function() {
                        this.classList.remove('btn-hover-effect');
                        
                        // Supprimer la classe d'animation de l'icône
                        const icon = this.querySelector('i');
                        if (icon) {
                            icon.classList.remove('icon-hover-effect');
                        }
                    });
                });
                
                // Améliorer l'accessibilité
                card.setAttribute('tabindex', '0');
                card.setAttribute('role', 'button');
                card.setAttribute('aria-label', 'Détails de la réparation');
                
                // Ajouter un effet de focus pour l'accessibilité au clavier
                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        // Simuler un clic sur le bouton de détails
                        const detailsButton = this.querySelector('.btn-info.view-repair-details');
                        if (detailsButton) {
                            detailsButton.click();
                        }
                    }
                });
                
                // Améliorer l'effet de glisser-déposer
                if (card.classList.contains('draggable-card')) {
                    card.addEventListener('dragstart', function() {
                        // Ajouter un effet visuel lors du début du glissement
                        setTimeout(() => {
                            this.classList.add('drag-active');
                        }, 0);
                    });
                    
                    card.addEventListener('dragend', function() {
                        this.classList.remove('drag-active');
                    });
                }
            });
            
            console.log(`Interactions améliorées pour ${repairCards.length} cartes de réparation`);
        }
    }
    
    /**
     * Détecte le mode sombre/clair et applique des ajustements visuels
     */
    function detectColorScheme() {
        const isDarkMode = document.body.classList.contains('dark-mode');
        
        // Appliquer des ajustements spécifiques au mode
        if (isDarkMode) {
            document.documentElement.setAttribute('data-theme', 'dark');
            console.log('Mode sombre détecté, ajustements appliqués');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            console.log('Mode clair détecté, ajustements appliqués');
        }
    }
    
    // Appliquer les ajustements initiaux pour le mode clair/sombre
    detectColorScheme();
    
    // Surveiller les changements de schéma de couleurs
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                detectColorScheme();
            }
        });
    });
    
    observer.observe(document.body, { attributes: true });
    
    // Ajouter un gestionnaire pour les redimensionnements de fenêtre
    window.addEventListener('resize', function() {
        if (cardsContainer) {
            // Recalculer la disposition des cartes lors du redimensionnement
            adjustCardLayout();
        }
    });
    
    /**
     * Recalcule et ajuste la disposition des cartes
     */
    function adjustCardLayout() {
        if (!cardsContainer || !repairCards.length) return;
        
        // Vérifier si le viewport est assez large pour afficher plusieurs cartes par ligne
        if (window.innerWidth >= 768) {
            let rowCards = [];
            let currentRowTop = null;
            
            // Regrouper les cartes par rangée
            repairCards.forEach(card => {
                // Réinitialiser la hauteur pour obtenir la hauteur naturelle
                card.style.height = 'auto';
                
                const rect = card.getBoundingClientRect();
                
                // Si c'est une nouvelle rangée ou la première carte
                if (currentRowTop === null || Math.abs(rect.top - currentRowTop) > 5) {
                    // Si nous avions des cartes dans la rangée précédente, appliquer la hauteur maximale
                    if (rowCards.length > 0) {
                        const maxHeight = Math.max(...rowCards.map(c => c.offsetHeight));
                        rowCards.forEach(c => {
                            c.style.height = maxHeight + 'px';
                        });
                    }
                    
                    // Commencer une nouvelle rangée
                    currentRowTop = rect.top;
                    rowCards = [card];
                } else {
                    // Ajouter à la rangée actuelle
                    rowCards.push(card);
                }
            });
            
            // Traiter la dernière rangée
            if (rowCards.length > 0) {
                const maxHeight = Math.max(...rowCards.map(c => c.offsetHeight));
                rowCards.forEach(c => {
                    c.style.height = maxHeight + 'px';
                });
            }
            
            console.log('Disposition des cartes ajustée pour les écrans larges');
        } else {
            // Sur mobile, réinitialiser les hauteurs
            repairCards.forEach(card => {
                card.style.height = 'auto';
            });
            
            console.log('Disposition des cartes ajustée pour mobile');
        }
    }
    
    // Exécuter l'ajustement initial de la disposition
    setTimeout(adjustCardLayout, 300);
});

/**
 * Fonction pour améliorer l'expérience utilisateur des modals
 */
function enhanceModals() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        // Améliorer l'animation d'entrée/sortie
        modal.addEventListener('show.bs.modal', function() {
            this.classList.add('modal-enhanced');
        });
        
        // Ajouter des transitions améliorées pour le contenu du modal
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.classList.add('modal-content-enhanced');
        }
    });
}

// Appeler la fonction d'amélioration des modals une fois que les modals sont chargés
document.addEventListener('DOMContentLoaded', enhanceModals); 