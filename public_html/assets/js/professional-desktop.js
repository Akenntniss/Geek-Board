/**
 * Interactions JavaScript pour une interface professionnelle sur desktop
 * Améliore l'expérience utilisateur sur les grands écrans
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si l'appareil n'est pas tactile et a un écran suffisamment grand
    if (!document.body.classList.contains('touch-device') && window.innerWidth >= 992) {
        // Initialiser les effets de survol avancés
        initAdvancedHoverEffects();
        
        // Initialiser les tableaux professionnels
        initProfessionalTables();
        
        // Initialiser les formulaires professionnels
        initProfessionalForms();
        
        // Initialiser les cartes professionnelles
        initProfessionalCards();
        
        // Initialiser les boutons professionnels
        initProfessionalButtons();
        
        // Initialiser la barre latérale professionnelle
        initProfessionalSidebar();
        
        // Initialiser les raccourcis clavier
        initKeyboardShortcuts();
        
        // Initialiser les notifications professionnelles
        initProfessionalNotifications();
        
        // Initialiser les transitions de page
        initPageTransitions();
    }
});

/**
 * Initialise les effets de survol avancés
 */
function initAdvancedHoverEffects() {
    // Ajouter des effets de survol subtils aux éléments interactifs
    const interactiveElements = document.querySelectorAll('.btn:not(.btn-primary), .card, .nav-link:not(.active), .list-group-item, .dropdown-item');
    
    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
            this.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
    
    // Ajouter des effets de survol plus prononcés aux boutons primaires
    const primaryButtons = document.querySelectorAll('.btn-primary');
    primaryButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 6px 12px rgba(67, 97, 238, 0.3)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
}

/**
 * Initialise les tableaux avec des fonctionnalités professionnelles
 */
function initProfessionalTables() {
    const tables = document.querySelectorAll('.table');
    
    tables.forEach(table => {
        // Ajouter une classe pour les styles CSS
        table.classList.add('table-professional');
        
        // Ajouter des effets de survol améliorés
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'rgba(67, 97, 238, 0.05)';
                this.style.transform = 'translateX(5px)';
                this.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
                this.style.transform = '';
            });
        });
        
        // Ajouter la fonctionnalité de tri si la table a un en-tête
        const headers = table.querySelectorAll('thead th');
        if (headers.length > 0) {
            headers.forEach((header, index) => {
                // Ajouter un indicateur de tri
                header.style.position = 'relative';
                header.style.cursor = 'pointer';
                
                // Ajouter un gestionnaire de clic pour le tri
                header.addEventListener('click', function() {
                    sortTable(table, index);
                });
                
                // Ajouter un effet de survol
                header.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'rgba(67, 97, 238, 0.1)';
                });
                
                header.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });
        }
    });
}

/**
 * Trie une table en fonction d'une colonne
 * @param {HTMLElement} table - La table à trier
 * @param {number} column - L'index de la colonne à trier
 */
function sortTable(table, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const headers = table.querySelectorAll('thead th');
    const header = headers[column];
    
    // Déterminer l'ordre de tri
    const currentOrder = header.getAttribute('data-order') || 'asc';
    const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
    
    // Mettre à jour l'attribut d'ordre
    headers.forEach(h => h.removeAttribute('data-order'));
    header.setAttribute('data-order', newOrder);
    
    // Mettre à jour les indicateurs visuels
    headers.forEach(h => h.classList.remove('sorting-asc', 'sorting-desc'));
    header.classList.add(newOrder === 'asc' ? 'sorting-asc' : 'sorting-desc');
    
    // Trier les lignes
    rows.sort((a, b) => {
        const cellA = a.querySelectorAll('td')[column].textContent.trim();
        const cellB = b.querySelectorAll('td')[column].textContent.trim();
        
        // Vérifier si les valeurs sont des nombres
        const numA = parseFloat(cellA);
        const numB = parseFloat(cellB);
        
        if (!isNaN(numA) && !isNaN(numB)) {
            return newOrder === 'asc' ? numA - numB : numB - numA;
        } else {
            return newOrder === 'asc' 
                ? cellA.localeCompare(cellB) 
                : cellB.localeCompare(cellA);
        }
    });
    
    // Réorganiser les lignes
    rows.forEach(row => tbody.appendChild(row));
    
    // Ajouter une animation
    rows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(10px)';
        
        setTimeout(() => {
            row.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 50 * index);
    });
}

/**
 * Initialise les formulaires avec des fonctionnalités professionnelles
 */
function initProfessionalForms() {
    // Améliorer l'aspect des champs de formulaire
    const formFields = document.querySelectorAll('.form-control, .form-select');
    
    formFields.forEach(field => {
        // Ajouter un effet de focus amélioré
        field.addEventListener('focus', function() {
            this.parentElement.classList.add('field-focus');
            this.style.borderColor = '#4361ee';
            this.style.boxShadow = '0 0 0 3px rgba(67, 97, 238, 0.15)';
        });
        
        field.addEventListener('blur', function() {
            this.parentElement.classList.remove('field-focus');
            this.style.borderColor = '';
            this.style.boxShadow = '';
        });
        
        // Ajouter une validation en temps réel si le champ a un attribut pattern
        if (field.hasAttribute('pattern')) {
            field.addEventListener('input', function() {
                validateField(this);
            });
        }
    });
    
    // Améliorer les formulaires de recherche
    const searchForms = document.querySelectorAll('form[role="search"]');
    searchForms.forEach(form => {
        const input = form.querySelector('input[type="search"], input[type="text"]');
        if (input) {
            // Ajouter une icône de recherche
            const searchIcon = document.createElement('i');
            searchIcon.className = 'fas fa-search search-icon';
            input.parentElement.style.position = 'relative';
            searchIcon.style.position = 'absolute';
            searchIcon.style.right = '10px';
            searchIcon.style.top = '50%';
            searchIcon.style.transform = 'translateY(-50%)';
            searchIcon.style.color = '#6b7280';
            input.parentElement.appendChild(searchIcon);
            
            // Ajouter un effet de focus
            input.addEventListener('focus', function() {
                searchIcon.style.color = '#4361ee';
            });
            
            input.addEventListener('blur', function() {
                searchIcon.style.color = '#6b7280';
            });
        }
    });
}

/**
 * Valide un champ de formulaire en fonction de son pattern
 * @param {HTMLElement} field - Le champ à valider
 */
function validateField(field) {
    const pattern = new RegExp(field.getAttribute('pattern'));
    const isValid = pattern.test(field.value);
    
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
    }
}

/**
 * Initialise les cartes avec des fonctionnalités professionnelles
 */
function initProfessionalCards() {
    const cards = document.querySelectorAll('.card');
    
    cards.forEach(card => {
        // Ajouter des transitions fluides
        card.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
        
        // Ajouter des effets de survol professionnels
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 12px 32px rgba(0, 0, 0, 0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
        
        // Ajouter un effet de clic
        card.addEventListener('mousedown', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseup', function() {
            this.style.transform = 'translateY(-5px)';
        });
    });
}

/**
 * Initialise les boutons avec des fonctionnalités professionnelles
 */
function initProfessionalButtons() {
    const buttons = document.querySelectorAll('.btn');
    
    buttons.forEach(button => {
        // Ajouter des transitions fluides
        button.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
        
        // Ajouter un effet de clic
        button.addEventListener('mousedown', function() {
            this.style.transform = 'scale(0.95)';
        });
        
        button.addEventListener('mouseup', function() {
            this.style.transform = '';
        });
        
        // Ajouter un effet de survol pour les boutons primaires
        if (button.classList.contains('btn-primary')) {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 10px rgba(67, 97, 238, 0.3)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
        }
    });
}

/**
 * Initialise la barre latérale avec des fonctionnalités professionnelles
 */
function initProfessionalSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    // Ajouter des styles professionnels
    sidebar.style.boxShadow = '0 0 20px rgba(0, 0, 0, 0.05)';

    // Améliorer les liens de navigation
    const navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateX(5px)';
                this.style.backgroundColor = 'var(--gray-100, #f3f4f6)';
            }
        });

        link.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = '';
                this.style.backgroundColor = '';
            }
        });
    });

    // Vérifier si un bouton de toggle existe déjà
    if (sidebar.querySelector('.sidebar-toggle')) {
        return; // Ne pas créer de bouton si un existe déjà
    }

    // Ajouter un bouton pour réduire/agrandir la barre latérale
    const toggleButton = document.createElement('button');
    toggleButton.className = 'sidebar-toggle';
    toggleButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
    toggleButton.setAttribute('title', 'Réduire le menu');
    toggleButton.setAttribute('aria-label', 'Toggle Sidebar');

    sidebar.style.position = 'relative';
    sidebar.appendChild(toggleButton);
    
    // Ajouter la fonctionnalité de réduction/agrandissement
    let isCollapsed = false;
    toggleButton.addEventListener('click', function() {
        isCollapsed = !isCollapsed;

        // Trouver le contenu principal
        const mainContent = document.querySelector('main');

        if (isCollapsed) {
            // Réduire la barre latérale
            sidebar.classList.add('sidebar-collapsed');
            document.body.classList.add('sidebar-collapsed-mode');
            this.innerHTML = '<i class="fas fa-chevron-right"></i>';

            // Le contenu principal s'ajustera automatiquement grâce aux classes CSS

            // Masquer le texte des liens avec animation
            navLinks.forEach(link => {
                const icon = link.querySelector('i');
                const text = link.querySelector('span');

                if (icon && text) {
                    // Animation de disparition du texte
                    text.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                    text.style.opacity = '0';
                    text.style.transform = 'translateX(-10px)';

                    // Après l'animation, masquer complètement
                    setTimeout(() => {
                        text.style.display = 'none';
                    }, 200);

                    // Centrer et agrandir l'icône
                    icon.style.margin = '0';
                    icon.style.fontSize = '1.25rem';
                    icon.style.transition = 'all 0.3s ease';
                    icon.style.transform = 'scale(1.1)';

                    // Ajuster le lien
                    link.style.justifyContent = 'center';
                    link.style.padding = '10px';
                }
            });

            // Masquer le logo et le texte
            const brand = sidebar.querySelector('.navbar-brand, a:first-child');
            if (brand) {
                const brandText = brand.querySelector('span');
                if (brandText) brandText.style.display = 'none';
            }

            // Masquer le dropdown utilisateur
            const dropdown = sidebar.querySelector('.dropdown');
            if (dropdown) dropdown.style.display = 'none';

            // Ajouter une classe au body pour les styles CSS
            document.body.classList.add('sidebar-collapsed-mode');
        } else {
            // Restaurer la barre latérale
            sidebar.classList.remove('sidebar-collapsed');
            document.body.classList.remove('sidebar-collapsed-mode');
            this.innerHTML = '<i class="fas fa-chevron-left"></i>';

            // Le contenu principal s'ajustera automatiquement grâce aux classes CSS

            // Afficher le texte des liens avec animation
            navLinks.forEach(link => {
                const icon = link.querySelector('i');
                const text = link.querySelector('span');

                if (icon && text) {
                    // Réinitialiser l'affichage du texte
                    text.style.display = '';

                    // Animation de réapparition
                    setTimeout(() => {
                        text.style.opacity = '1';
                        text.style.transform = 'translateX(0)';
                    }, 50);

                    // Réinitialiser l'icône
                    icon.style.margin = '';
                    icon.style.fontSize = '';
                    icon.style.transform = '';

                    // Réinitialiser le lien
                    link.style.justifyContent = '';
                    link.style.padding = '';
                }
            });

            // Afficher le logo et le texte
            const brand = sidebar.querySelector('.navbar-brand, a:first-child');
            if (brand) {
                const brandText = brand.querySelector('span');
                if (brandText) brandText.style.display = '';
            }

            // Afficher le dropdown utilisateur
            const dropdown = sidebar.querySelector('.dropdown');
            if (dropdown) dropdown.style.display = '';

            // Supprimer la classe du body
            document.body.classList.remove('sidebar-collapsed-mode');
        }
    });
}

/**
 * Initialise les raccourcis clavier
 */
function initKeyboardShortcuts() {
    // Ajouter un gestionnaire d'événements pour les raccourcis clavier
    document.addEventListener('keydown', function(e) {
        // Ctrl+K pour la recherche
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[type="search"], form[role="search"] input');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Ctrl+N pour ajouter
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            const addButton = document.querySelector('a[href*="ajouter"], a[href*="add"], a[href*="nouveau"], a[href*="new"], button[id*="add"], button[id*="new"]');
            if (addButton) {
                addButton.click();
            }
        }
        
        // Échap pour fermer les modales
        if (e.key === 'Escape') {
            const modal = document.querySelector('.modal.show');
            if (modal) {
                const closeButton = modal.querySelector('.btn-close, .close, [data-bs-dismiss="modal"]');
                if (closeButton) {
                    closeButton.click();
                }
            }
        }
    });
    
    // Le bouton de raccourcis clavier a été supprimé
}

/**
 * Initialise les notifications professionnelles
 */
function initProfessionalNotifications() {
    // Créer un conteneur pour les notifications
    const notificationsContainer = document.createElement('div');
    notificationsContainer.className = 'notifications-container';
    notificationsContainer.style.position = 'fixed';
    notificationsContainer.style.top = '20px';
    notificationsContainer.style.right = '20px';
    notificationsContainer.style.zIndex = '1060';
    notificationsContainer.style.maxWidth = '350px';
    
    document.body.appendChild(notificationsContainer);
    
    // Fonction pour créer une notification
    window.showNotification = function(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
            </div>
            <div class="notification-content">
                <p>${message}</p>
            </div>
            <button class="notification-close"><i class="fas fa-times"></i></button>
        `;
        
        // Styles de la notification
        notification.style.backgroundColor = 'white';
        notification.style.borderRadius = '8px';
        notification.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
        notification.style.marginBottom = '10px';
        notification.style.display = 'flex';
        notification.style.alignItems = 'center';
        notification.style.padding = '12px 15px';
        notification.style.borderLeft = `4px solid ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'}`;
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        notification.style.transition = 'all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1)';
        
        // Styles de l'icône
        const icon = notification.querySelector('.notification-icon');
        icon.style.marginRight = '12px';
        icon.style.fontSize = '1.25rem';
        icon.style.color = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6';
        
        // Styles du contenu
        const content = notification.querySelector('.notification-content');
        content.style.flex = '1';
        
        // Styles du bouton de fermeture
        const closeButton = notification.querySelector('.notification-close');
        closeButton.style.background = 'none';
        closeButton.style.border = 'none';
        closeButton.style.cursor = 'pointer';
        closeButton.style.fontSize = '0.875rem';
        closeButton.style.color = '#6b7280';
        
        // Ajouter la notification au conteneur
        notificationsContainer.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        }, 10);
        
        // Fermer la notification après la durée spécifiée
        const timeout = setTimeout(() => {
            closeNotification(notification);
        }, duration);
        
        // Fermer la notification en cliquant sur le bouton
        closeButton.addEventListener('click', function() {
            clearTimeout(timeout);
            closeNotification(notification);
        });
        
        // Fonction pour fermer la notification
        function closeNotification(notification) {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
        
        return notification;
    };
}

/**
 * Initialise les transitions de page
 */
function initPageTransitions() {
    // Ajouter une classe pour les transitions
    document.body.classList.add('page-transitions');
    
    // Intercepter les clics sur les liens
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        
        if (link && link.href && link.href.startsWith(window.location.origin) && !link.hasAttribute('data-bs-toggle') && !link.target) {
            e.preventDefault();
            
            // Ajouter une transition de sortie
            document.body.classList.add('page-exit');
            
            // Naviguer vers la nouvelle page après la transition
            setTimeout(() => {
                window.location.href = link.href;
            }, 300);
        }
    });
    
    // Ajouter une transition d'entrée
    window.addEventListener('load', function() {
        document.body.classList.add('page-enter');
    });
}

/**
 * Fonction utilitaire pour limiter la fréquence d'exécution d'une fonction
 * @param {Function} func - La fonction à exécuter
 * @param {number} wait - Le délai d'attente en millisecondes
 * @returns {Function} - La fonction avec limite de fréquence
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            func.apply(context, args);
        }, wait);
    };
}