/**
 * Script de correction pour la page Clients
 * Empêche le décalage du contenu après le chargement initial
 */

document.addEventListener('DOMContentLoaded', function() {
    // Détecter si nous sommes sur la page Clients
    const isClientsPage = window.location.href.includes('page=clients') || 
                         document.title.includes('Clients') ||
                         document.querySelector('h1, h2, h3, .page-title')?.textContent.includes('Clients');
    
    if (isClientsPage) {
        // Ajouter un attribut data-page pour cibler avec CSS
        document.body.setAttribute('data-page', 'clients');
        
        // Forcer la position correcte du contenu immédiatement
        const mainContent = document.querySelector('main');
        const sidebar = document.querySelector('.sidebar');
        
        if (mainContent && sidebar) {
            // Vérifier si on est sur tablette
            const isTablet = window.innerWidth >= 768 && window.innerWidth <= 991.98;
            
            if (isTablet) {
                // Forcer le positionnement correct
                mainContent.style.marginLeft = '220px';
                mainContent.style.width = 'calc(100% - 220px)';
                mainContent.style.transition = 'none';
                
                // Désactiver temporairement les transitions
                const style = document.createElement('style');
                style.innerHTML = `
                    @media (min-width: 768px) and (max-width: 991.98px) {
                        .sidebar, main, .container-fluid {
                            transition: none !important;
                        }
                        main {
                            margin-left: 220px !important;
                            width: calc(100% - 220px) !important;
                        }
                        .table-responsive {
                            max-width: calc(100% - 10px) !important;
                            overflow-x: auto !important;
                        }
                    }
                `;
                document.head.appendChild(style);
                
                // Empêcher les scripts de modifier la disposition pendant 2 secondes
                const originalAddEventListener = EventTarget.prototype.addEventListener;
                
                EventTarget.prototype.addEventListener = function(type, listener, options) {
                    // Bloquer les écouteurs d'événements qui pourraient altérer la mise en page
                    if (type === 'resize' || type === 'orientationchange') {
                        return originalAddEventListener.call(this, type, function(e) {
                            // Ne faire rien pendant 2 secondes
                        }, options);
                    }
                    return originalAddEventListener.call(this, type, listener, options);
                };
                
                // Restaurer après 2 secondes
                setTimeout(() => {
                    // Restaurer le comportement normal
                    EventTarget.prototype.addEventListener = originalAddEventListener;
                    
                    // Marquer comme chargé
                    document.body.classList.add('page-loaded');
                }, 2000);
                
                // Forcer le tableau à s'afficher correctement
                const tableResponsive = document.querySelector('.table-responsive');
                if (tableResponsive) {
                    tableResponsive.style.maxWidth = 'calc(100% - 10px)';
                    tableResponsive.style.overflowX = 'auto';
                    
                    const table = tableResponsive.querySelector('table');
                    if (table) {
                        table.style.width = '100%';
                        table.style.minWidth = 'auto';
                    }
                }
            }
        }
    }
    
    // Correction pour le toggle de la barre latérale
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        // S'assurer que le bouton fonctionne correctement
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
            
            // Forcer l'application des classes
            if (isCollapsed) {
                sidebar.classList.remove('sidebar-collapsed');
                document.body.classList.remove('sidebar-collapsed-mode');
                
                // Mettre à jour les styles
                sidebar.style.width = '220px';
                document.querySelector('main').style.marginLeft = '220px';
                document.querySelector('main').style.width = 'calc(100% - 220px)';
            } else {
                sidebar.classList.add('sidebar-collapsed');
                document.body.classList.add('sidebar-collapsed-mode');
                
                // Mettre à jour les styles
                sidebar.style.width = '60px';
                document.querySelector('main').style.marginLeft = '60px';
                document.querySelector('main').style.width = 'calc(100% - 60px)';
            }
            
            // Sauvegarder l'état
            localStorage.setItem('sidebar_collapsed', !isCollapsed);
        });
    }
}); 