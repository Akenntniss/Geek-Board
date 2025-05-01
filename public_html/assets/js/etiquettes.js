/**
 * Script pour gérer l'affichage et l'impression des étiquettes
 * GestiRep - Module Étiquettes
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments DOM
    const viewModeList = document.getElementById('viewModeList');
    const viewModeLabels = document.getElementById('viewModeLabels');
    const tableView = document.querySelector('.card .table-responsive').closest('.card');
    const etiquettesView = document.getElementById('etiquettesView');
    const printLabelsBtn = document.getElementById('printLabels');
    const selectAllLabelsBtn = document.getElementById('selectAllLabels');
    const filtersSection = document.querySelectorAll('.mb-4')[1]; // Filtres rapides
    const filtersCard = document.querySelector('.card.mb-4'); // Filtres détaillés

    // Basculer entre les modes d'affichage
    if (viewModeList && viewModeLabels) {
        viewModeList.addEventListener('click', function() {
            // Activer le bouton liste
            viewModeList.classList.add('active');
            viewModeLabels.classList.remove('active');
            
            // Afficher la vue tableau, masquer les étiquettes
            tableView.classList.remove('d-none');
            etiquettesView.classList.add('d-none');
            
            // Afficher les filtres
            if (filtersSection) filtersSection.classList.remove('d-none');
            if (filtersCard) filtersCard.classList.remove('d-none');
            
            // Sauvegarder la préférence
            localStorage.setItem('reparationsViewMode', 'list');
        });
        
        viewModeLabels.addEventListener('click', function() {
            // Activer le bouton étiquettes
            viewModeLabels.classList.add('active');
            viewModeList.classList.remove('active');
            
            // Afficher la vue étiquettes, masquer le tableau
            etiquettesView.classList.remove('d-none');
            tableView.classList.add('d-none');
            
            // Masquer les filtres
            if (filtersSection) filtersSection.classList.add('d-none');
            if (filtersCard) filtersCard.classList.add('d-none');
            
            // Sauvegarder la préférence
            localStorage.setItem('reparationsViewMode', 'labels');
        });
        
        // Restaurer le mode d'affichage précédent
        const savedViewMode = localStorage.getItem('reparationsViewMode');
        if (savedViewMode === 'labels') {
            viewModeLabels.click();
        }
    }
    
    // Sélectionner/désélectionner toutes les étiquettes
    if (selectAllLabelsBtn) {
        selectAllLabelsBtn.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.label-selector');
            const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            
            // Mettre à jour le texte du bouton
            this.innerHTML = allChecked ? 
                '<i class="fas fa-check-square me-1"></i>Tout sélectionner' : 
                '<i class="fas fa-square me-1"></i>Tout désélectionner';
        });
    }
    
    // Imprimer les étiquettes sélectionnées
    if (printLabelsBtn) {
        printLabelsBtn.addEventListener('click', function() {
            // Vérifier si des étiquettes sont sélectionnées
            const selectedCheckboxes = document.querySelectorAll('.label-selector:checked');
            
            if (selectedCheckboxes.length === 0) {
                // Afficher une alerte si aucune étiquette n'est sélectionnée
                alert('Veuillez sélectionner au moins une étiquette à imprimer.');
                return;
            }
            
            // Cloner les étiquettes sélectionnées pour l'impression
            const printContainer = document.createElement('div');
            printContainer.className = 'etiquettes-container print-only';
            
            selectedCheckboxes.forEach(checkbox => {
                const etiquetteId = checkbox.value;
                const etiquette = document.querySelector(`.etiquette[data-id="${etiquetteId}"]`);
                
                if (etiquette) {
                    // Cloner l'étiquette et supprimer la case à cocher
                    const etiquetteClone = etiquette.cloneNode(true);
                    const checkbox = etiquetteClone.querySelector('.form-check');
                    if (checkbox) checkbox.remove();
                    
                    printContainer.appendChild(etiquetteClone);
                }
            });
            
            // Ajouter le conteneur temporaire au DOM
            document.body.appendChild(printContainer);
            
            // Déclencher l'impression
            window.print();
            
            // Supprimer le conteneur temporaire après l'impression
            setTimeout(() => {
                document.body.removeChild(printContainer);
            }, 1000);
        });
    }
    
    // Clic sur une étiquette pour sélectionner la case à cocher
    const etiquettes = document.querySelectorAll('.etiquette');
    etiquettes.forEach(etiquette => {
        etiquette.addEventListener('click', function(e) {
            // Ne pas déclencher si on clique sur la case à cocher elle-même
            if (e.target.classList.contains('form-check-input') || 
                e.target.closest('.form-check-input')) {
                return;
            }
            
            const checkbox = this.querySelector('.label-selector');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
            }
        });
    });
}); 