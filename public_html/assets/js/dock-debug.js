/**
 * Script de débogage pour la barre de navigation
 * Permet de voir l'état actuel de la barre et de la tester manuellement
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log("[dock-debug] Initialisation du débogage...");
    
    // Attendre que tous les scripts soient chargés
    setTimeout(function() {
        // Récupérer le dock
        const dock = document.getElementById('mobile-dock');
        if (!dock) {
            console.error("[dock-debug] Dock non trouvé dans le DOM");
            return;
        }
        
        // Afficher l'état actuel du dock
        console.log("[dock-debug] État actuel du dock:");
        console.log("- Classes:", dock.className);
        console.log("- Style display:", window.getComputedStyle(dock).display);
        console.log("- Style visibility:", window.getComputedStyle(dock).visibility);
        console.log("- Style opacity:", window.getComputedStyle(dock).opacity);
        console.log("- Style transform:", window.getComputedStyle(dock).transform);
        
        // Vérifier s'il y a un conflit dans les classes
        if (dock.classList.contains('dock-hidden') && dock.classList.contains('dock-visible')) {
            console.error("[dock-debug] CONFLIT: dock-hidden et dock-visible sont tous deux présents");
        }
        
        if (dock.classList.contains('hidden') && dock.classList.contains('show')) {
            console.error("[dock-debug] CONFLIT: hidden et show sont tous deux présents");
        }
        
        // Créer des boutons de test flottants
        const testPanel = document.createElement('div');
        testPanel.style.position = 'fixed';
        testPanel.style.top = '20px';
        testPanel.style.right = '20px';
        testPanel.style.background = 'rgba(0,0,0,0.7)';
        testPanel.style.padding = '10px';
        testPanel.style.borderRadius = '8px';
        testPanel.style.zIndex = '9999';
        testPanel.style.color = 'white';
        testPanel.style.fontSize = '12px';
        
        testPanel.innerHTML = `
            <div style="margin-bottom:10px;font-weight:bold;">Test Dock</div>
            <button id="test-hide-dock" style="margin:5px;padding:5px 10px;background:#f44336;color:white;border:none;border-radius:4px;">Masquer</button>
            <button id="test-show-dock" style="margin:5px;padding:5px 10px;background:#4CAF50;color:white;border:none;border-radius:4px;">Afficher</button>
            <div id="dock-status" style="margin-top:10px;font-size:11px;">État: Chargement...</div>
        `;
        
        document.body.appendChild(testPanel);
        
        // Fonction pour mettre à jour le statut
        function updateStatus() {
            const statusEl = document.getElementById('dock-status');
            if (!statusEl) return;
            
            let status = "Visible";
            if (dock.classList.contains('hidden') || dock.classList.contains('dock-hidden')) {
                status = "Masqué";
            }
            
            let classes = Array.from(dock.classList).join(', ');
            
            statusEl.innerHTML = `État: ${status}<br>Classes: ${classes}`;
        }
        
        // Ajouter les événements aux boutons
        document.getElementById('test-hide-dock').addEventListener('click', function() {
            dock.classList.remove('show', 'dock-visible');
            dock.classList.add('hidden', 'dock-hidden');
            updateStatus();
        });
        
        document.getElementById('test-show-dock').addEventListener('click', function() {
            dock.classList.remove('hidden', 'dock-hidden');
            dock.classList.add('show', 'dock-visible');
            updateStatus();
        });
        
        // Mettre à jour le statut initial
        updateStatus();
        
        // Surveiller les changements de classe sur le dock
        const observer = new MutationObserver(function(mutations) {
            updateStatus();
        });
        
        observer.observe(dock, { attributes: true, attributeFilter: ['class'] });
        
        console.log("[dock-debug] Débogage initialisé");
    }, 1000);
}); 