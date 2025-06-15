// Gestion de la sélection de fournisseur dans le modal
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation du sélecteur de fournisseur...');
    
    // Vérifier que les éléments existent
    const selectBtn = document.getElementById('select-fournisseur-btn');
    const modal = document.getElementById('fournisseurSelectionModal');
    const cards = document.querySelectorAll('#fournisseurSelectionModal .fournisseur-card');
    
    console.log('Éléments trouvés:', {
        selectBtn: !!selectBtn,
        modal: !!modal,
        cardsCount: cards.length
    });
    
    // Mettre à jour la recherche de fournisseurs
    document.getElementById('searchFournisseurSelection')?.addEventListener('input', function(e) {
        const searchText = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('#fournisseurSelectionModal .fournisseur-card');
        
        cards.forEach(card => {
            const name = card.querySelector('.fournisseur-name')?.textContent.toLowerCase();
            if (name && name.includes(searchText)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
    
    // Gestion du clic sur une carte de fournisseur
    document.querySelectorAll('#fournisseurSelectionModal .fournisseur-card').forEach(card => {
        card.addEventListener('click', function() {
            const fournisseurId = this.getAttribute('data-fournisseur-id');
            const fournisseurName = this.getAttribute('data-fournisseur-name');
            
            console.log('Fournisseur cliqué:', { fournisseurId, fournisseurName });
            
            if (fournisseurId && fournisseurName) {
                // Mettre à jour l'affichage du fournisseur sélectionné
                updateSelectedFournisseur(fournisseurId, fournisseurName);
                
                // Fermer le modal de sélection de fournisseur
                const fournisseurModal = bootstrap.Modal.getInstance(document.getElementById('fournisseurSelectionModal'));
                if (fournisseurModal) {
                    fournisseurModal.hide();
                    
                    // Attendre que le modal soit complètement fermé avant de rouvrir le modal principal
                    document.getElementById('fournisseurSelectionModal').addEventListener('hidden.bs.modal', function reopenMainModal() {
                        // Rouvrir le modal principal "Nouvelle commande de pièces"
                        const mainModal = document.getElementById('ajouterCommandeModal');
                        if (mainModal) {
                            const mainModalInstance = new bootstrap.Modal(mainModal);
                            mainModalInstance.show();
                        }
                        
                        // Supprimer l'écouteur pour éviter les répétitions
                        document.getElementById('fournisseurSelectionModal').removeEventListener('hidden.bs.modal', reopenMainModal);
                    }, { once: true });
                }
            }
        });
    });
    
    // Gérer le clic sur le bouton de sélection de fournisseur
    document.getElementById('select-fournisseur-btn')?.addEventListener('click', function() {
        console.log('Bouton de sélection de fournisseur cliqué');
        
        // Fermer d'abord le modal principal
        const mainModal = bootstrap.Modal.getInstance(document.getElementById('ajouterCommandeModal'));
        if (mainModal) {
            mainModal.hide();
        }
        
        // Ouvrir le modal de sélection de fournisseur après fermeture du modal principal
        setTimeout(() => {
        const fournisseurModal = new bootstrap.Modal(document.getElementById('fournisseurSelectionModal'));
        fournisseurModal.show();
        }, 300); // Délai pour permettre la fermeture complète du modal principal
    });
});

// Fonction pour mettre à jour l'affichage du fournisseur sélectionné
function updateSelectedFournisseur(id, name) {
    // Mettre à jour le champ select du fournisseur
    const fournisseurSelect = document.getElementById('fournisseur_id_ajout');
    if (fournisseurSelect) {
        fournisseurSelect.value = id;
        
        // Déclencher l'événement change pour notifier les autres scripts
        const changeEvent = new Event('change', { bubbles: true });
        fournisseurSelect.dispatchEvent(changeEvent);
    }
    
    // Mettre à jour l'affichage personnalisé si présent
    const selectedFournisseurDisplay = document.getElementById('selected-fournisseur-name');
    if (selectedFournisseurDisplay) {
        selectedFournisseurDisplay.textContent = name;
        
        // Afficher l'élément parent (qui contient le nom du fournisseur sélectionné)
        const selectedDisplay = document.getElementById('selected-fournisseur-display');
        if (selectedDisplay) {
            selectedDisplay.classList.remove('d-none');
        }
        
        // Masquer le bouton de sélection si nécessaire
        const selectBtn = document.getElementById('select-fournisseur-btn');
        if (selectBtn) {
            selectBtn.classList.add('d-none');
        }
    }
    
    // Activer le bouton de réinitialisation
    const resetBtn = document.getElementById('reset-fournisseur-btn');
    if (resetBtn) {
        resetBtn.classList.remove('d-none');
        resetBtn.addEventListener('click', function() {
            resetSelectedFournisseur();
        });
    }
    
    console.log('Fournisseur sélectionné:', { id, name });
}

// Fonction pour réinitialiser le fournisseur sélectionné
function resetSelectedFournisseur() {
    // Réinitialiser le champ select
    const fournisseurSelect = document.getElementById('fournisseur_id_ajout');
    if (fournisseurSelect) {
        fournisseurSelect.value = '';
        
        // Déclencher l'événement change
        const changeEvent = new Event('change', { bubbles: true });
        fournisseurSelect.dispatchEvent(changeEvent);
    }
    
    // Masquer l'affichage du fournisseur sélectionné
    const selectedDisplay = document.getElementById('selected-fournisseur-display');
    if (selectedDisplay) {
        selectedDisplay.classList.add('d-none');
    }
    
    // Afficher le bouton de sélection
    const selectBtn = document.getElementById('select-fournisseur-btn');
    if (selectBtn) {
        selectBtn.classList.remove('d-none');
    }
    
    // Masquer le bouton de réinitialisation
    const resetBtn = document.getElementById('reset-fournisseur-btn');
    if (resetBtn) {
        resetBtn.classList.add('d-none');
    }
    
    console.log('Fournisseur réinitialisé');
} 