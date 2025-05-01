document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des onglets pour les marges
    const marginsTabEl = document.getElementById('marginsTab');
    if (marginsTabEl) {
        const tabs = marginsTabEl.querySelectorAll('.nav-link');
        tabs.forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                // Retirer la classe active de tous les onglets
                tabs.forEach(t => {
                    t.classList.remove('active');
                    const pane = document.querySelector(t.getAttribute('data-bs-target'));
                    if (pane) pane.classList.remove('show', 'active');
                });
                
                // Ajouter la classe active à l'onglet cliqué
                this.classList.add('active');
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                if (target) target.classList.add('show', 'active');
            });
        });
        
        // Activer le premier onglet par défaut
        if (tabs.length > 0) {
            const firstTabTarget = document.querySelector(tabs[0].getAttribute('data-bs-target'));
            if (firstTabTarget) firstTabTarget.classList.add('show', 'active');
        }
    }
    
    // Initialisation du mode tactile
    const touchModeSwitch = document.getElementById('touchModeSwitch');
    let selectedRow = null;
    const quickApplyButton = document.getElementById('quick-apply-button');
    
    if (touchModeSwitch) {
        touchModeSwitch.addEventListener('change', function() {
            const isEnabled = this.checked;
            const marginsModalEl = document.getElementById('marginsModal');
            
            if (isEnabled) {
                marginsModalEl.classList.add('touch-mode');
            } else {
                marginsModalEl.classList.remove('touch-mode');
                // Désélectionner toute ligne sélectionnée
                if (selectedRow) {
                    selectedRow.classList.remove('selected-row');
                    selectedRow = null;
                    quickApplyButton.disabled = true;
                }
            }
        });
        
        // Activer automatiquement le mode tactile sur les appareils tactiles
        if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
            touchModeSwitch.checked = true;
            document.getElementById('marginsModal').classList.add('touch-mode');
        }
    }
    
    // Fonctions pour manipuler les tableaux de marges
    function initMarginsTables() {
        // Ajouter des écouteurs d'événements pour les lignes du tableau
        document.querySelectorAll('#marginsTabContent .tab-pane').forEach(pane => {
            pane.querySelectorAll('.repair-data tr').forEach(row => {
                // Ignorer les en-têtes
                if (row.parentElement.tagName === 'THEAD') return;
                
                // Récupérer les données de la ligne
                const achatHT = row.getAttribute('data-achat');
                const marge = row.getAttribute('data-marge');
                
                // Recalculer les valeurs affichées
                updateRowDisplay(row, parseFloat(achatHT), parseFloat(marge));
                
                // Ajouter l'événement de clic
                row.addEventListener('click', function(e) {
                    // Si l'utilisateur a cliqué sur le bouton Appliquer, traiter différemment
                    if (e.target.tagName === 'BUTTON' || (e.target.closest('button') && e.target.closest('button').classList.contains('apply-margin'))) {
                        applyValueAndReturn(achatHT, marge);
                        return;
                    }
                    
                    // En mode tactile, gérer la sélection
                    if (touchModeSwitch && touchModeSwitch.checked) {
                        // Désélectionner la ligne précédemment sélectionnée
                        if (selectedRow && selectedRow !== this) {
                            selectedRow.classList.remove('selected-row');
                        }
                        
                        // Basculer la sélection de la ligne actuelle
                        this.classList.toggle('selected-row');
                        
                        // Mettre à jour la ligne sélectionnée
                        if (this.classList.contains('selected-row')) {
                            selectedRow = this;
                            quickApplyButton.disabled = false;
                        } else {
                            selectedRow = null;
                            quickApplyButton.disabled = true;
                        }
                    } else {
                        // En mode normal, appliquer directement
                        applyValueAndReturn(achatHT, marge);
                    }
                });
                
                // Configurer le bouton d'application
                const applyButton = row.querySelector('.apply-margin');
                if (applyButton) {
                    applyButton.addEventListener('click', function(e) {
                        e.stopPropagation(); // Empêcher le déclenchement de l'événement de la ligne
                        applyValueAndReturn(achatHT, marge);
                    });
                }
            });
        });
        
        // Configurer le bouton d'application rapide (mode tactile)
        if (quickApplyButton) {
            quickApplyButton.addEventListener('click', function() {
                if (selectedRow) {
                    const achatHT = selectedRow.getAttribute('data-achat');
                    const marge = selectedRow.getAttribute('data-marge');
                    applyValueAndReturn(achatHT, marge);
                }
            });
        }
    }
    
    // Mettre à jour l'affichage d'une ligne
    function updateRowDisplay(row, achatHT, margeHT) {
        const margePct = achatHT > 0 ? (margeHT / achatHT) * 100 : 0;
        const venteHT = achatHT + margeHT;
        const venteTTC = venteHT * 1.2; // TVA 20%
        
        const achatHTCell = row.querySelector('td:nth-child(2)');
        const margeHTCell = row.querySelector('td:nth-child(3)');
        const ttcCell = row.querySelector('td:nth-child(4)');
        
        if (achatHTCell && margeHTCell && ttcCell) {
            achatHTCell.textContent = achatHT.toFixed(2) + ' €';
            
            margeHTCell.innerHTML = margeHT.toFixed(2) + ' € <span class="badge ' + 
                (margePct >= 200 ? 'bg-success' : 
                (margePct >= 100 ? 'bg-info' : 
                (margePct >= 50 ? 'bg-warning' : 'bg-danger'))) + 
                '">' + margePct.toFixed(0) + '%</span>';
            
            ttcCell.textContent = venteTTC.toFixed(2) + ' €';
        }
    }
    
    // Fonction pour appliquer les valeurs et retourner au convertisseur
    function applyValueAndReturn(achatHT, marge) {
        const marginsModal = bootstrap.Modal.getInstance(document.getElementById('marginsModal'));
        if (!marginsModal) return;
        
        marginsModal.hide();
        
        setTimeout(() => {
            const prixAchatHT = document.getElementById('prix-achat-ht');
            const margeHT = document.getElementById('marge-ht');
            
            if (prixAchatHT && margeHT) {
                prixAchatHT.value = achatHT;
                margeHT.value = marge;
                
                // Déclencher l'événement d'entrée pour recalculer les valeurs
                const event = new Event('input', { bubbles: true });
                prixAchatHT.dispatchEvent(event);
                
                // Mettre en surbrillance les champs mis à jour
                highlightField(prixAchatHT);
                highlightField(margeHT);
                
                // Feedback tactile
                if (navigator.vibrate) {
                    navigator.vibrate(100);
                }
            }
            
            // Ré-ouvrir le convertisseur de prix
            const priceConverterModal = new bootstrap.Modal(document.getElementById('priceConverterModal'));
            priceConverterModal.show();
        }, 500);
    }
    
    // Mettre en évidence un champ mis à jour
    function highlightField(field) {
        if (!field) return;
        
        field.classList.add('bg-light');
        setTimeout(() => {
            field.classList.remove('bg-light');
        }, 1500);
    }
    
    // Gestion du mode d'édition des marges
    const editMarginModeBtn = document.getElementById('editMarginModeBtn');
    const saveMarginChangesBtn = document.getElementById('saveMarginChangesBtn');
    const cancelMarginChangesBtn = document.getElementById('cancelMarginChangesBtn');
    const editModeButtons = document.querySelector('.edit-mode-buttons');
    
    let isEditMode = false;
    let originalValues = [];
    
    if (editMarginModeBtn) {
        editMarginModeBtn.addEventListener('click', function() {
            toggleEditMode();
        });
        
        if (saveMarginChangesBtn) {
            saveMarginChangesBtn.addEventListener('click', function() {
                saveEdits();
                toggleEditMode(false);
            });
        }
        
        if (cancelMarginChangesBtn) {
            cancelMarginChangesBtn.addEventListener('click', function() {
                cancelEdits();
                toggleEditMode(false);
            });
        }
    }
    
    // Fonction pour basculer le mode d'édition
    function toggleEditMode(forcedState) {
        if (forcedState !== undefined) {
            isEditMode = forcedState;
        } else {
            isEditMode = !isEditMode;
        }
        
        const marginsModalEl = document.getElementById('marginsModal');
        
        if (isEditMode) {
            // Activer le mode d'édition
            marginsModalEl.classList.add('edit-mode');
            editMarginModeBtn.classList.add('active');
            editMarginModeBtn.classList.replace('btn-outline-primary', 'btn-primary');
            editModeButtons.classList.remove('d-none');
            quickApplyButton.classList.add('d-none');
            
            // Désactiver le mode tactile pendant l'édition
            if (touchModeSwitch && touchModeSwitch.checked) {
                touchModeSwitch.checked = false;
                marginsModalEl.classList.remove('touch-mode');
            }
            touchModeSwitch.disabled = true;
            
            // Sauvegarder les valeurs originales et préparer l'édition
            prepareTableForEditing();
        } else {
            // Désactiver le mode d'édition
            marginsModalEl.classList.remove('edit-mode');
            editMarginModeBtn.classList.remove('active');
            editMarginModeBtn.classList.replace('btn-primary', 'btn-outline-primary');
            editModeButtons.classList.add('d-none');
            quickApplyButton.classList.remove('d-none');
            
            // Réactiver le mode tactile
            touchModeSwitch.disabled = false;
            
            // Restaurer les tableaux à leur état normal
            restoreTablesFromEditing();
        }
    }
    
    // Préparer les tableaux pour l'édition
    function prepareTableForEditing() {
        originalValues = [];
        
        document.querySelectorAll('#marginsModal .repair-data tr').forEach((row, index) => {
            if (row.parentElement.tagName === 'THEAD') return;
            
            // Sauvegarder les valeurs originales
            const achatHT = row.getAttribute('data-achat') || '0';
            const margeHT = row.getAttribute('data-marge') || '0';
            
            originalValues.push({
                index: index,
                achatHT: achatHT,
                margeHT: margeHT
            });
            
            // Transformer les cellules en champs éditables
            const achatHTCell = row.querySelector('td:nth-child(2)');
            const margeHTCell = row.querySelector('td:nth-child(3)');
            
            if (achatHTCell && margeHTCell) {
                const achatHTValue = parseFloat(achatHT);
                const margeHTValue = parseFloat(margeHT);
                
                // Remplacer le contenu de la cellule par un input
                achatHTCell.innerHTML = `
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control edit-achat-ht" value="${achatHTValue.toFixed(2)}" min="0" step="0.01">
                        <span class="input-group-text">€</span>
                    </div>
                `;
                
                margeHTCell.innerHTML = `
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control edit-marge-ht" value="${margeHTValue.toFixed(2)}" step="0.01">
                        <span class="input-group-text">€</span>
                    </div>
                `;
                
                // Ajouter des écouteurs d'événements pour mettre à jour dynamiquement
                const achatHTInput = achatHTCell.querySelector('input');
                const margeHTInput = margeHTCell.querySelector('input');
                
                achatHTInput.addEventListener('input', () => updateRowCalculation(row));
                margeHTInput.addEventListener('input', () => updateRowCalculation(row));
            }
        });
    }
    
    // Mettre à jour les calculs pour une ligne en mode édition
    function updateRowCalculation(row) {
        const achatHTInput = row.querySelector('.edit-achat-ht');
        const margeHTInput = row.querySelector('.edit-marge-ht');
        const ttcCell = row.querySelector('td:nth-child(4)');
        
        if (achatHTInput && margeHTInput && ttcCell) {
            const achatHT = parseFloat(achatHTInput.value) || 0;
            const margeHT = parseFloat(margeHTInput.value) || 0;
            const venteHT = achatHT + margeHT;
            const venteTTC = venteHT * 1.2; // TVA 20%
            
            // Mettre à jour le prix TTC
            ttcCell.textContent = venteTTC.toFixed(2) + ' €';
            
            // Mettre à jour les attributs data-*
            row.setAttribute('data-achat', achatHT.toFixed(2));
            row.setAttribute('data-marge', margeHT.toFixed(2));
        }
    }
    
    // Restaurer les tableaux à leur état normal
    function restoreTablesFromEditing() {
        document.querySelectorAll('#marginsModal .repair-data tr').forEach(row => {
            if (row.parentElement.tagName === 'THEAD') return;
            
            // Récupérer les valeurs actuelles des inputs
            const achatHT = parseFloat(row.getAttribute('data-achat')) || 0;
            const margeHT = parseFloat(row.getAttribute('data-marge')) || 0;
            
            // Mettre à jour l'affichage
            updateRowDisplay(row, achatHT, margeHT);
        });
    }
    
    // Sauvegarder les modifications
    function saveEdits() {
        document.querySelectorAll('#marginsModal .repair-data tr').forEach(row => {
            if (row.parentElement.tagName === 'THEAD') return;
            
            const achatHTInput = row.querySelector('.edit-achat-ht');
            const margeHTInput = row.querySelector('.edit-marge-ht');
            
            if (achatHTInput && margeHTInput) {
                const achatHT = parseFloat(achatHTInput.value) || 0;
                const margeHT = parseFloat(margeHTInput.value) || 0;
                
                // Mettre à jour les attributs data-*
                row.setAttribute('data-achat', achatHT.toFixed(2));
                row.setAttribute('data-marge', margeHT.toFixed(2));
            }
        });
        
        // Sauvegarder dans le localStorage
        saveMarginValuesToStorage();
        
        // Feedback
        const toastContainer = document.getElementById('toastContainer') || document.createElement('div');
        if (!document.getElementById('toastContainer')) {
            toastContainer.id = 'toastContainer';
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        const toastId = 'saveToast' + Date.now();
        toastContainer.innerHTML += `
            <div id="${toastId}" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2"></i> Modifications enregistrées
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 3000 });
        toast.show();
        
        // Vibration de confirmation
        if (navigator.vibrate) {
            navigator.vibrate([100, 50, 100]);
        }
    }
    
    // Annuler les modifications
    function cancelEdits() {
        // Restaurer les valeurs originales
        originalValues.forEach(item => {
            const rows = document.querySelectorAll('#marginsModal .repair-data tr');
            // Trouver la bonne ligne (en ignorant les en-têtes)
            let actualIndex = 0;
            let targetRow = null;
            
            for (let i = 0; i < rows.length; i++) {
                if (rows[i].parentElement.tagName === 'THEAD') continue;
                
                if (actualIndex === item.index) {
                    targetRow = rows[i];
                    break;
                }
                actualIndex++;
            }
            
            if (targetRow) {
                targetRow.setAttribute('data-achat', item.achatHT);
                targetRow.setAttribute('data-marge', item.margeHT);
            }
        });
    }
    
    // Fonction pour sauvegarder les valeurs de marge dans le stockage local
    function saveMarginValuesToStorage() {
        const marginData = {
            phones: [],
            computers: [],
            tablets: []
        };
        
        // Téléphones
        document.querySelectorAll('#phones .repair-data tr').forEach(row => {
            if (row.parentElement.tagName === 'THEAD') return;
            marginData.phones.push({
                name: row.querySelector('td:first-child').textContent.trim(),
                achatHT: row.getAttribute('data-achat'),
                margeHT: row.getAttribute('data-marge')
            });
        });
        
        // Ordinateurs
        document.querySelectorAll('#computers .repair-data tr').forEach(row => {
            if (row.parentElement.tagName === 'THEAD') return;
            marginData.computers.push({
                name: row.querySelector('td:first-child').textContent.trim(),
                achatHT: row.getAttribute('data-achat'),
                margeHT: row.getAttribute('data-marge')
            });
        });
        
        // Tablettes
        document.querySelectorAll('#tablets .repair-data tr').forEach(row => {
            if (row.parentElement.tagName === 'THEAD') return;
            marginData.tablets.push({
                name: row.querySelector('td:first-child').textContent.trim(),
                achatHT: row.getAttribute('data-achat'),
                margeHT: row.getAttribute('data-marge')
            });
        });
        
        // Sauvegarder dans localStorage
        localStorage.setItem('marginEstimatesData', JSON.stringify(marginData));
    }
    
    // Fonction pour charger les valeurs de marge depuis le stockage local
    function loadMarginValuesFromStorage() {
        const storedData = localStorage.getItem('marginEstimatesData');
        if (!storedData) return;
        
        try {
            const marginData = JSON.parse(storedData);
            
            // Téléphones
            marginData.phones.forEach((item, index) => {
                const rows = document.querySelectorAll('#phones .repair-data tr');
                let actualIndex = 0;
                for (let i = 0; i < rows.length; i++) {
                    if (rows[i].parentElement.tagName === 'THEAD') continue;
                    if (actualIndex === index) {
                        rows[i].setAttribute('data-achat', item.achatHT);
                        rows[i].setAttribute('data-marge', item.margeHT);
                        updateRowDisplay(rows[i], parseFloat(item.achatHT), parseFloat(item.margeHT));
                        break;
                    }
                    actualIndex++;
                }
            });
            
            // Ordinateurs
            marginData.computers.forEach((item, index) => {
                const rows = document.querySelectorAll('#computers .repair-data tr');
                let actualIndex = 0;
                for (let i = 0; i < rows.length; i++) {
                    if (rows[i].parentElement.tagName === 'THEAD') continue;
                    if (actualIndex === index) {
                        rows[i].setAttribute('data-achat', item.achatHT);
                        rows[i].setAttribute('data-marge', item.margeHT);
                        updateRowDisplay(rows[i], parseFloat(item.achatHT), parseFloat(item.margeHT));
                        break;
                    }
                    actualIndex++;
                }
            });
            
            // Tablettes
            marginData.tablets.forEach((item, index) => {
                const rows = document.querySelectorAll('#tablets .repair-data tr');
                let actualIndex = 0;
                for (let i = 0; i < rows.length; i++) {
                    if (rows[i].parentElement.tagName === 'THEAD') continue;
                    if (actualIndex === index) {
                        rows[i].setAttribute('data-achat', item.achatHT);
                        rows[i].setAttribute('data-marge', item.margeHT);
                        updateRowDisplay(rows[i], parseFloat(item.achatHT), parseFloat(item.margeHT));
                        break;
                    }
                    actualIndex++;
                }
            });
        } catch (e) {
            console.error('Erreur lors du chargement des données de marge:', e);
        }
    }
    
    // Initialiser la gestion des événements du convertisseur
    const marginsModal = document.getElementById('marginsModal');
    if (marginsModal) {
        // Initialiser les onglets et tables lors de l'ouverture de la modale
        marginsModal.addEventListener('shown.bs.modal', function() {
            initMarginsTables();
            loadMarginValuesFromStorage();
        });
    }
}); 