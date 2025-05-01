/**
 * Script de correction pour les problèmes d'affichage des onglets dans le modal de recherche
 * Solution radicale qui intervient directement sur le DOM
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Script de correction des onglets chargé');
    
    // Fonction pour corriger l'affichage des onglets
    function fixModalTabs() {
        // Cibler le modal de recherche
        const modal = document.getElementById('rechercheAvanceeModal');
        if (!modal) return;
        
        // Forcer l'affichage des résultats
        const resultatsContainer = document.getElementById('resultats_recherche');
        if (resultatsContainer) {
            resultatsContainer.classList.remove('d-none');
            resultatsContainer.style.display = 'block';
            resultatsContainer.style.visibility = 'visible';
            resultatsContainer.style.opacity = '1';
        }
        
        // Fonction pour activer un onglet manuellement
        function forceActivateTab(tabId, paneId) {
            const tab = document.getElementById(tabId);
            const pane = document.getElementById(paneId);
            
            if (tab && pane) {
                // Désactiver tous les onglets
                document.querySelectorAll('.nav-link').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => {
                    p.classList.remove('show', 'active');
                    p.style.visibility = 'hidden';
                    p.style.opacity = '0';
                });
                
                // Activer l'onglet spécifié
                tab.classList.add('active');
                pane.classList.add('show', 'active');
                pane.style.display = 'block';
                pane.style.visibility = 'visible';
                pane.style.opacity = '1';
                
                console.log(`Onglet ${tabId} activé manuellement`);
            }
        }
        
        // Corriger les gestionnaires d'événements des onglets
        const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabButtons.forEach(button => {
            // Supprimer les gestionnaires existants pour éviter les conflits
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Ajouter un nouveau gestionnaire d'événement propre
            newButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const targetId = this.getAttribute('data-bs-target').substring(1); // Enlever le #
                forceActivateTab(this.id, targetId);
            });
        });
        
        // Lorsque le modal est affiché, activer automatiquement l'onglet approprié
        modal.addEventListener('shown.bs.modal', function() {
            console.log('Modal affiché, restauration des onglets');
            
            // Vérifier les compteurs pour déterminer quel onglet activer
            const counts = {
                clients: parseInt(document.getElementById('count-clients')?.textContent || '0'),
                reparations: parseInt(document.getElementById('count-reparations')?.textContent || '0'),
                commandes: parseInt(document.getElementById('count-commandes')?.textContent || '0')
            };
            
            // Activer l'onglet qui a des résultats
            if (counts.reparations > 0) {
                forceActivateTab('reparations-tab', 'reparationsTab');
            } else if (counts.clients > 0) {
                forceActivateTab('clients-tab', 'clients');
            } else if (counts.commandes > 0) {
                forceActivateTab('commandes-tab', 'commandesTab');
            }
            
            // Forcer la visibilité des tableaux
            document.querySelectorAll('.table-responsive').forEach(container => {
                container.style.display = 'block';
                container.style.visibility = 'visible';
                container.style.opacity = '1';
            });
            
            // Déclencher un redimensionnement pour forcer le rendu
            window.dispatchEvent(new Event('resize'));
        });
    }
    
    // Fonction pour forcer l'affichage des tableaux dans un onglet spécifique
    function forceShowTabContent(tabId) {
        console.log(`Forçage de l'affichage du contenu pour l'onglet ${tabId}`);
        
        // Trouver le panneau d'onglet
        const tabPane = document.getElementById(tabId);
        if (!tabPane) {
            console.error(`Onglet ${tabId} introuvable`);
            return;
        }
        
        // Force le style d'affichage
        Object.assign(tabPane.style, {
            display: 'block !important',
            visibility: 'visible !important',
            opacity: '1 !important',
            position: 'relative !important',
            height: 'auto !important',
            overflow: 'visible !important'
        });
        
        // Forcer l'affichage du tableau à l'intérieur
        const tableContainer = tabPane.querySelector('.table-responsive');
        if (tableContainer) {
            Object.assign(tableContainer.style, {
                display: 'block',
                visibility: 'visible',
                opacity: '1',
                height: 'auto',
                minHeight: '200px',
                overflow: 'auto'
            });
            
            // Forcer le tableau lui-même
            const table = tableContainer.querySelector('table');
            if (table) {
                Object.assign(table.style, {
                    display: 'table',
                    visibility: 'visible',
                    opacity: '1',
                    width: '100%'
                });
            }
        }
        
        // Déclencher un événement de redimensionnement pour forcer le rendu
        window.dispatchEvent(new Event('resize'));
    }
    
    // Ajouter un bouton de correction d'urgence
    function addEmergencyFixButton() {
        // Vérifier si le bouton existe déjà
        if (document.getElementById('emergency-fix-btn')) return;
        
        // Créer le bouton
        const fixButton = document.createElement('button');
        fixButton.id = 'emergency-fix-btn';
        fixButton.className = 'btn btn-warning position-absolute';
        fixButton.style.top = '0';
        fixButton.style.right = '0';
        fixButton.style.zIndex = '9999';
        fixButton.style.margin = '5px';
        fixButton.style.padding = '5px 10px';
        fixButton.style.fontSize = '12px';
        fixButton.textContent = 'Afficher';
        fixButton.title = 'Cliquez pour forcer l\'affichage des tableaux';
        
        // Ajouter le gestionnaire d'événement
        fixButton.addEventListener('click', function() {
            // Forcer l'affichage de tous les onglets
            forceShowTabContent('clients');
            forceShowTabContent('reparationsTab');
            forceShowTabContent('commandesTab');
            
            // Forcer l'activation de l'onglet actif
            const activeTab = document.querySelector('.nav-link.active');
            if (activeTab) {
                const targetId = activeTab.getAttribute('data-bs-target').substring(1);
                const targetPane = document.getElementById(targetId);
                if (targetPane) {
                    document.querySelectorAll('.tab-pane').forEach(pane => {
                        pane.style.zIndex = '1';
                    });
                    targetPane.style.zIndex = '2';
                }
            }
        });
        
        // Ajouter au modal
        const modalBody = document.querySelector('#rechercheAvanceeModal .modal-body');
        if (modalBody) {
            modalBody.style.position = 'relative';
            modalBody.appendChild(fixButton);
        }
    }
    
    // Exécuter la correction lorsque le DOM est prêt
    fixModalTabs();
    addEmergencyFixButton();

    // Forcer l'affichage des tableaux après un court délai pour s'assurer que tout est chargé
    setTimeout(function() {
        forceShowTabContent('clients');
        forceShowTabContent('reparationsTab');
        forceShowTabContent('commandesTab');
    }, 1000);
    
    // Réexécuter en cas de chargement AJAX
    document.addEventListener('ajaxComplete', fixModalTabs);
    
    // Solution ultime: vérifier périodiquement l'état des onglets
    setInterval(function() {
        const modal = document.getElementById('rechercheAvanceeModal');
        if (modal && window.getComputedStyle(modal).display !== 'none') {
            const activeTab = document.querySelector('.tab-pane.active');
            if (activeTab && window.getComputedStyle(activeTab).display !== 'block') {
                console.log('Correction d\'urgence des onglets');
                
                // Forcer l'affichage de l'onglet actif
                activeTab.style.display = 'block';
                activeTab.style.visibility = 'visible';
                activeTab.style.opacity = '1';
                
                // Déclencher un redimensionnement
                window.dispatchEvent(new Event('resize'));
            }
        }
    }, 500);
}); 