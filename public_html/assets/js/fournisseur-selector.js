// Gestion de la sélection de fournisseur dans le modal
document.addEventListener('DOMContentLoaded', function() {
    
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
            
            if (fournisseurId && fournisseurName) {
                // Mettre à jour l'affichage du fournisseur sélectionné
                updateSelectedFournisseur(fournisseurId, fournisseurName);
                
                // Fermer le modal
                // const modal = bootstrap.Modal.getInstance(document.getElementById('fournisseurSelectionModal'));
                // if (modal) modal.hide();
            }
        });
    });
    
    // Gérer le clic sur le bouton de sélection de fournisseur
    document.getElementById('select-fournisseur-btn')?.addEventListener('click', function() {
        // Ouvrir le modal de sélection de fournisseur
        const fournisseurModal = new bootstrap.Modal(document.getElementById('fournisseurSelectionModal'));
        fournisseurModal.show();
    });
});

// Fonction pour mettre à jour l'affichage du fournisseur sélectionné
function updateSelectedFournisseur(id, name) {
    // Mettre à jour le champ hidden et le bouton
    document.getElementById('fournisseur_id_ajout').value = id;
    
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
    
    // Active le bouton de réinitialisation
    const resetBtn = document.getElementById('reset-fournisseur-btn');
    if (resetBtn) {
        resetBtn.classList.remove('d-none');
        resetBtn.addEventListener('click', function() {
            resetSelectedFournisseur();
        });
    }
}

// Fonction pour réinitialiser le fournisseur sélectionné
function resetSelectedFournisseur() {
    // Réinitialiser le champ hidden
    document.getElementById('fournisseur_id_ajout').value = '';
    
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
} 