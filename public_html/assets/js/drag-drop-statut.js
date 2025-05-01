/**
 * Script pour gérer le drag & drop des statuts de réparation
 * Avec la fonctionnalité de modal pour choisir un statut spécifique
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le drag & drop pour les badges de statut
    initStatusDragAndDrop();
});

/**
 * Initialise le drag & drop pour les badges de statut
 */
function initStatusDragAndDrop() {
    // Sélectionner tous les badges de statut
    const statusBadges = document.querySelectorAll('.status-badge');
    const dropZones = document.querySelectorAll('.filter-btn.droppable');
    
    // Variables pour le ghost element
    let ghostElement = null;
    let draggedBadge = null;
    
    // Ajouter les écouteurs d'événements pour les badges
    statusBadges.forEach(badge => {
        badge.addEventListener('dragstart', handleDragStart);
        badge.addEventListener('dragend', handleDragEnd);
    });
    
    // Ajouter les écouteurs d'événements pour les zones de dépôt
    dropZones.forEach(zone => {
        zone.addEventListener('dragover', handleDragOver);
        zone.addEventListener('dragenter', handleDragEnter);
        zone.addEventListener('dragleave', handleDragLeave);
        zone.addEventListener('drop', handleDrop);
        
        // Prévenir le comportement de drag par défaut sur les boutons filtres
        zone.addEventListener('dragstart', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
        
        // Empêcher le comportement de drag natif des liens
        zone.addEventListener('mousedown', function(e) {
            zone.setAttribute('draggable', 'false');
        });
    });
    
    /**
     * Gère le début du drag
     */
    function handleDragStart(e) {
        // Marquer le badge comme étant en cours de déplacement
        this.classList.add('dragging');
        draggedBadge = this;
        
        // Récupérer les attributs nécessaires
        const repairId = this.getAttribute('data-repair-id');
        const statusCode = this.getAttribute('data-status-code');
        
        // Log pour le débogage
        console.log('Début du drag avec les données:', {
            repairId: repairId,
            statusCode: statusCode
        });
        
        // Vérifier que les attributs nécessaires sont présents
        if (!repairId) {
            console.error('Attribut data-repair-id manquant sur l\'élément draggable');
            return false;
        }
        
        // Stocker les données de l'élément déplacé
        const dataToTransfer = JSON.stringify({
            repairId: repairId,
            statusCode: statusCode || ''
        });
        
        // Définir les données pour le transfert
        e.dataTransfer.setData('text/plain', dataToTransfer);
        
        // Définir l'effet de déplacement
        e.dataTransfer.effectAllowed = 'move';
        
        // Créer un "ghost element" pour le feedback visuel
        createGhostElement(this, e);
    }
    
    /**
     * Gère la fin du drag
     */
    function handleDragEnd(e) {
        // Empêcher la propagation de l'événement
        e.preventDefault();
        e.stopPropagation();
        
        // Supprimer la classe de dragging
        this.classList.remove('dragging');
        
        // Supprimer le ghost element
        if (ghostElement && ghostElement.parentNode) {
            document.body.removeChild(ghostElement);
            ghostElement = null;
        }
        
        // Réinitialiser les zones de dépôt
        dropZones.forEach(zone => {
            zone.classList.remove('drag-over');
        });
        
        // Supprimer l'écouteur mousemove
        document.removeEventListener('mousemove', updateGhostPosition);
    }
    
    /**
     * Gère le survol d'une zone de dépôt
     */
    function handleDragOver(e) {
        // Empêcher le comportement par défaut pour permettre le drop
        e.preventDefault();
        return false;
    }
    
    /**
     * Gère l'entrée dans une zone de dépôt
     */
    function handleDragEnter(e) {
        this.classList.add('drag-over');
    }
    
    /**
     * Gère la sortie d'une zone de dépôt
     */
    function handleDragLeave() {
        this.classList.remove('drag-over');
    }
    
    /**
     * Gère le dépôt dans une zone
     */
    function handleDrop(e) {
        // Empêcher le comportement par défaut
        e.preventDefault();
        
        // Récupérer les données
        try {
            // Vérifier que le dataTransfer contient des données
            const dataString = e.dataTransfer.getData('text/plain');
            if (!dataString) {
                console.error('Aucune donnée dans le dataTransfer');
                this.classList.remove('drag-over');
                return false;
            }
            
            const data = JSON.parse(dataString);
            const repairId = data.repairId;
            const categoryId = this.getAttribute('data-category-id');
            const dropZoneText = this.innerText.trim();
            
            console.log('Drop détecté :', {
                repairId: repairId,
                categoryId: categoryId,
                dropZone: dropZoneText,
                dropZoneElement: this
            });
            
            // Vérifier que nous avons toutes les données nécessaires
            if (!repairId || !categoryId) {
                console.error('Données incomplètes pour la mise à jour du statut');
                return false;
            }
            
            // Effet visuel de succès
            this.classList.add('drop-success');
            setTimeout(() => {
                this.classList.remove('drop-success');
            }, 1000);
            
            // Si c'est une catégorie "En attente" (ID 3), déterminer le type spécifique d'attente
            if (categoryId === "3") {
                // Définir le cas spécial en fonction du texte du bouton
                let specialCase = '';
                
                // Le texte du bouton contient le titre principal et le compteur
                if (dropZoneText.includes('En attente')) {
                    // Rechercher dans les boutons de statut disponibles pour cette catégorie
                    fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.statuts && data.statuts.length > 0) {
                                // Afficher les options spécifiques "En attente" dans un modal
                                showAttenteCategoryOptions(repairId, data.statuts, draggedBadge);
                            } else {
                                // Utiliser le premier statut par défaut
                                updateDirectStatus(repairId, categoryId, specialCase, draggedBadge);
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la récupération des statuts:', error);
                            updateDirectStatus(repairId, categoryId, specialCase, draggedBadge);
                        });
                    return false;
                }
            }
            
            // Si c'est une catégorie "En cours" (ID 2), déterminer le type spécifique d'intervention
            if (categoryId === "2") {
                // Le texte du bouton contient le titre principal et le compteur
                if (dropZoneText.includes('En cours')) {
                    // Rechercher dans les boutons de statut disponibles pour cette catégorie
                    fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.statuts && data.statuts.length > 0) {
                                // Afficher les options spécifiques "En cours" dans un modal
                                showEnCoursCategoryOptions(repairId, data.statuts, draggedBadge);
                            } else {
                                // Utiliser le premier statut par défaut
                                updateDirectStatus(repairId, categoryId, '', draggedBadge);
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la récupération des statuts:', error);
                            updateDirectStatus(repairId, categoryId, '', draggedBadge);
                        });
                    return false;
                }
            }
            
            // Si c'est une catégorie "Terminé" (ID 4), déterminer le type spécifique
            if (categoryId === "4") {
                // Le texte du bouton contient le titre principal et le compteur
                if (dropZoneText.includes('Terminé')) {
                    // Rechercher dans les boutons de statut disponibles pour cette catégorie
                    fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.statuts && data.statuts.length > 0) {
                                // Afficher les options spécifiques "Terminé" dans un modal
                                showTermineCategoryOptions(repairId, data.statuts, draggedBadge);
                            } else {
                                // Utiliser le premier statut par défaut
                                updateDirectStatus(repairId, categoryId, '', draggedBadge);
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la récupération des statuts:', error);
                            updateDirectStatus(repairId, categoryId, '', draggedBadge);
                        });
                    return false;
                }
            }
            
            // Si c'est une catégorie "Annulé" (ID 5), déterminer le type spécifique
            if (categoryId === "5") {
                // Le texte du bouton contient le titre principal et le compteur
                if (dropZoneText.includes('Annulé')) {
                    // Rechercher dans les boutons de statut disponibles pour cette catégorie
                    fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.statuts && data.statuts.length > 0) {
                                // Afficher les options spécifiques "Annulé" dans un modal
                                showAnnuleCategoryOptions(repairId, data.statuts, draggedBadge);
                            } else {
                                // Utiliser le premier statut par défaut
                                updateDirectStatus(repairId, categoryId, '', draggedBadge);
                            }
                        })
                        .catch(error => {
                            console.error('Erreur lors de la récupération des statuts:', error);
                            updateDirectStatus(repairId, categoryId, '', draggedBadge);
                        });
                    return false;
                }
            }
            
            // Récupérer les statuts disponibles pour cette catégorie
            fetchStatusOptions(repairId, categoryId, draggedBadge);
            
        } catch (error) {
            console.error('Erreur lors du traitement des données:', error);
        }
        
        // Réinitialiser l'état visuel
        this.classList.remove('drag-over');
        return false;
    }
    
    /**
     * Crée un élément fantôme pour le feedback visuel pendant le drag
     */
    function createGhostElement(sourceElement, event) {
        // Supprimer l'ancien ghost s'il existe
        if (ghostElement && ghostElement.parentNode) {
            document.body.removeChild(ghostElement);
        }
        
        // Créer un clone du badge
        ghostElement = sourceElement.cloneNode(true);
        ghostElement.classList.add('ghost-badge');
        
        // Positionner l'élément
        const rect = sourceElement.getBoundingClientRect();
        ghostElement.style.width = rect.width + 'px';
        ghostElement.style.height = rect.height + 'px';
        
        // Calculer l'offset par rapport au point de clic
        const offsetX = event.clientX - rect.left;
        const offsetY = event.clientY - rect.top;
        
        // Sauvegarder l'offset pour les mises à jour de position
        ghostElement.dataset.offsetX = offsetX;
        ghostElement.dataset.offsetY = offsetY;
        
        // Appliquer la position initiale
        ghostElement.style.left = (event.pageX - offsetX) + 'px';
        ghostElement.style.top = (event.pageY - offsetY) + 'px';
        
        // Ajouter au DOM
        document.body.appendChild(ghostElement);
        
        // Ajouter un écouteur pour le mouvement de la souris
        document.addEventListener('mousemove', updateGhostPosition);
    }
    
    /**
     * Met à jour la position de l'élément fantôme pendant le drag
     */
    function updateGhostPosition(e) {
        if (ghostElement) {
            const offsetX = parseInt(ghostElement.dataset.offsetX) || 0;
            const offsetY = parseInt(ghostElement.dataset.offsetY) || 0;
            
            ghostElement.style.left = (e.pageX - offsetX) + 'px';
            ghostElement.style.top = (e.pageY - offsetY) + 'px';
        }
    }
}

/**
 * Récupère les options de statut pour une catégorie donnée
 */
function fetchStatusOptions(repairId, categoryId, badgeElement) {
    // Afficher un indicateur de chargement dans le badge
    badgeElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    console.log('Récupération des statuts pour la catégorie :', categoryId);
    
    // Récupérer les statuts disponibles pour cette catégorie
    fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Statuts récupérés :', data.statuts);
                
                // Stocker les IDs pour une utilisation ultérieure
                document.getElementById('chooseStatusRepairId').value = repairId;
                document.getElementById('chooseStatusCategoryId').value = categoryId;
                
                // Générer les boutons de statut
                const container = document.getElementById('statusButtonsContainer');
                container.innerHTML = ''; // Effacer le contenu précédent
                
                // Déterminer la couleur de la catégorie
                const categoryColor = getCategoryColor(data.category.couleur);
                
                // Créer un bouton pour chaque statut
                data.statuts.forEach(statut => {
                    console.log('Créé bouton pour statut :', statut);
                    
                    const button = document.createElement('button');
                    button.className = `btn btn-${categoryColor} btn-lg w-100 mb-2`;
                    button.setAttribute('data-status-id', statut.id);
                    button.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>
                        ${statut.nom}
                    `;
                    button.addEventListener('click', () => updateSpecificStatus(statut.id, badgeElement));
                    container.appendChild(button);
                });
                
                // Afficher le modal
                const modal = new bootstrap.Modal(document.getElementById('chooseStatusModal'));
                modal.show();
            } else {
                // Afficher l'erreur
                console.error('Erreur:', data.error);
                showNotification('Erreur: ' + data.error, 'danger');
                
                // Réinitialiser le badge
                const statut = badgeElement.getAttribute('data-status-code');
                badgeElement.innerHTML = statut;
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des statuts:', error);
            showNotification('Erreur de communication avec le serveur', 'danger');
            
            // Réinitialiser le badge
            const statut = badgeElement.getAttribute('data-status-code');
            badgeElement.innerHTML = statut;
        });
}

/**
 * Met à jour le statut spécifique d'une réparation
 */
function updateSpecificStatus(statusId, badgeElement) {
    // Récupérer les ID stockés
    const repairId = document.getElementById('chooseStatusRepairId').value;
    
    console.log('Mise à jour avec le statut ID :', statusId, 'pour la réparation ID :', repairId);
    
    // Fermer le modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('chooseStatusModal'));
    modal.hide();
    
    // Afficher un indicateur de chargement
    badgeElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Préparer les données
    const data = {
        repair_id: repairId,
        status_id: statusId
    };
    
    // Envoyer la requête AJAX
    fetch('../ajax/update_repair_specific_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour le badge avec le nouveau statut
            const badgeParent = badgeElement.parentNode;
            badgeParent.innerHTML = data.data.badge;
            
            // Réinitialiser le drag & drop pour le nouveau badge
            const newBadge = badgeParent.querySelector('.status-badge');
            if (newBadge) {
                newBadge.addEventListener('dragstart', handleDragStart);
                newBadge.addEventListener('dragend', handleDragEnd);
            }
            
            // Afficher un message de succès
            showNotification('Statut mis à jour avec succès', 'success');
        } else {
            // Afficher l'erreur
            showNotification('Erreur: ' + data.error, 'danger');
            
            // Recharger la page pour rétablir l'état correct
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Erreur lors de la mise à jour:', error);
        showNotification('Erreur de communication avec le serveur', 'danger');
        
        // Recharger la page pour rétablir l'état correct
        setTimeout(() => {
            location.reload();
        }, 2000);
    });
}

/**
 * Convertit un code couleur de catégorie en classe Bootstrap
 */
function getCategoryColor(color) {
    const colorMap = {
        'info': 'info',
        'primary': 'primary',
        'warning': 'warning',
        'success': 'success',
        'danger': 'danger'
    };
    
    return colorMap[color] || 'secondary';
}

/**
 * Affiche une notification temporaire
 */
function showNotification(message, type = 'info') {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    notification.style.zIndex = '9999';
    notification.style.maxWidth = '300px';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Ajouter au DOM
    document.body.appendChild(notification);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 3000);
}

/**
 * Affiche un modal avec les options spécifiques pour la catégorie "En attente"
 */
function showAttenteCategoryOptions(repairId, statuts, badgeElement) {
    // Afficher un indicateur de chargement dans le badge
    badgeElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    console.log('Affichage des options spécifiques pour En attente, statuts disponibles:', statuts);
    
    // Générer les boutons de statut
    const container = document.getElementById('statusButtonsContainer');
    container.innerHTML = ''; // Effacer le contenu précédent
    
    // Créer un titre
    const title = document.createElement('h5');
    title.className = 'text-center mb-3';
    title.innerText = 'Choisir un type d\'attente';
    container.appendChild(title);
    
    // Créer un bouton pour chaque statut d'attente
    statuts.forEach(statut => {
        console.log('Créé bouton pour statut d\'attente:', statut);
        
        const button = document.createElement('button');
        button.className = 'btn btn-warning btn-lg w-100 mb-2';
        button.setAttribute('data-status-id', statut.id);
        button.innerHTML = `
            <i class="fas fa-clock me-2"></i>
            ${statut.nom}
        `;
        button.addEventListener('click', () => updateSpecificStatus(statut.id, badgeElement));
        container.appendChild(button);
    });
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('chooseStatusModal'));
    modal.show();
}

/**
 * Affiche un modal avec les options spécifiques pour la catégorie "Terminé"
 */
function showTermineCategoryOptions(repairId, statuts, badgeElement) {
    // Afficher un indicateur de chargement dans le badge
    badgeElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    console.log('Affichage des options spécifiques pour Terminé, statuts disponibles:', statuts);
    
    // Générer les boutons de statut
    const container = document.getElementById('statusButtonsContainer');
    container.innerHTML = ''; // Effacer le contenu précédent
    
    // Créer un titre
    const title = document.createElement('h5');
    title.className = 'text-center mb-3';
    title.innerText = 'Choisir un type de clôture';
    container.appendChild(title);
    
    // Créer un bouton pour chaque statut de terminaison
    statuts.forEach(statut => {
        console.log('Créé bouton pour statut terminé:', statut);
        
        const button = document.createElement('button');
        button.className = 'btn btn-success btn-lg w-100 mb-2';
        button.setAttribute('data-status-id', statut.id);
        button.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ${statut.nom}
        `;
        button.addEventListener('click', () => updateSpecificStatus(statut.id, badgeElement));
        container.appendChild(button);
    });
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('chooseStatusModal'));
    modal.show();
}

/**
 * Affiche un modal avec les options spécifiques pour la catégorie "Annulé"
 */
function showAnnuleCategoryOptions(repairId, statuts, badgeElement) {
    // Afficher un indicateur de chargement dans le badge
    badgeElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    console.log('Affichage des options spécifiques pour Annulé, statuts disponibles:', statuts);
    
    // Générer les boutons de statut
    const container = document.getElementById('statusButtonsContainer');
    container.innerHTML = ''; // Effacer le contenu précédent
    
    // Créer un titre
    const title = document.createElement('h5');
    title.className = 'text-center mb-3';
    title.innerText = 'Choisir un type d\'annulation';
    container.appendChild(title);
    
    // Créer un bouton pour chaque statut d'annulation
    statuts.forEach(statut => {
        console.log('Créé bouton pour statut annulé:', statut);
        
        const button = document.createElement('button');
        button.className = 'btn btn-danger btn-lg w-100 mb-2';
        button.setAttribute('data-status-id', statut.id);
        button.innerHTML = `
            <i class="fas fa-times-circle me-2"></i>
            ${statut.nom}
        `;
        button.addEventListener('click', () => {
            // Pour "Restitué" spécifiquement, on utilise le cas spécial
            if (statut.code === 'restitue') {
                updateDirectStatus(repairId, 5, 'restitue', badgeElement);
                const modal = bootstrap.Modal.getInstance(document.getElementById('chooseStatusModal'));
                modal.hide();
            } else if (statut.code === 'gardiennage') {
                // Pour "Gardiennage" spécifiquement, on utilise le cas spécial
                updateDirectStatus(repairId, 5, 'gardiennage', badgeElement);
                const modal = bootstrap.Modal.getInstance(document.getElementById('chooseStatusModal'));
                modal.hide();
            } else if (statut.code === 'annule') {
                // Pour "Annulé" spécifiquement, on utilise le cas spécial
                updateDirectStatus(repairId, 5, 'annule', badgeElement);
                const modal = bootstrap.Modal.getInstance(document.getElementById('chooseStatusModal'));
                modal.hide();
            } else {
                updateSpecificStatus(statut.id, badgeElement);
            }
        });
        container.appendChild(button);
    });
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('chooseStatusModal'));
    modal.show();
}

/**
 * Met à jour directement le statut sans passer par le modal
 */
function updateDirectStatus(repairId, categoryId, specialCase, badgeElement) {
    // Afficher un indicateur de chargement
    badgeElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Préparer les données
    const formData = new FormData();
    formData.append('repair_id', repairId);
    formData.append('category_id', categoryId);
    if (specialCase) {
        formData.append('special_case', specialCase);
    }
    
    // Envoyer la requête AJAX
    fetch('../ajax/update_repair_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mettre à jour le badge avec le nouveau statut
            const badgeParent = badgeElement.parentNode;
            badgeParent.innerHTML = data.data.badge;
            
            // Mettre à jour le data-status-code
            const newBadge = badgeParent.querySelector('.status-badge');
            if (newBadge) {
                newBadge.setAttribute('data-status-code', data.data.statut);
                newBadge.setAttribute('data-repair-id', repairId);
                
                // Réinitialiser le drag & drop pour le nouveau badge
                newBadge.setAttribute('draggable', 'true');
                newBadge.addEventListener('dragstart', handleDragStart);
                newBadge.addEventListener('dragend', handleDragEnd);
            }
            
            // Afficher une notification de succès
            showNotification('Statut mis à jour avec succès', 'success');
        } else {
            // Afficher l'erreur
            console.error('Erreur:', data.error);
            showNotification('Erreur: ' + data.error, 'danger');
            
            // Réinitialiser le badge
            const statut = badgeElement.getAttribute('data-status-code');
            badgeElement.innerHTML = statut;
        }
    })
    .catch(error => {
        console.error('Erreur lors de la mise à jour du statut:', error);
        showNotification('Erreur de communication avec le serveur', 'danger');
        
        // Réinitialiser le badge
        const statut = badgeElement.getAttribute('data-status-code');
        badgeElement.innerHTML = statut;
    });
}

/**
 * Affiche un modal avec les options spécifiques pour la catégorie "En cours"
 */
function showEnCoursCategoryOptions(repairId, statuts, badgeElement) {
    // Afficher un indicateur de chargement dans le badge
    badgeElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    console.log('Affichage des options spécifiques pour En cours, statuts disponibles:', statuts);
    
    // Générer les boutons de statut
    const container = document.getElementById('statusButtonsContainer');
    container.innerHTML = ''; // Effacer le contenu précédent
    
    // Créer un titre
    const title = document.createElement('h5');
    title.className = 'text-center mb-3';
    title.innerText = 'Choisir un type d\'intervention';
    container.appendChild(title);
    
    // Créer un bouton pour chaque statut d'attente
    statuts.forEach(statut => {
        console.log('Créé bouton pour statut En cours:', statut);
        
        const button = document.createElement('button');
        button.className = 'btn btn-primary btn-lg w-100 mb-2';
        button.setAttribute('data-status-id', statut.id);
        button.innerHTML = `
            <i class="fas fa-tools me-2"></i>
            ${statut.nom}
        `;
        button.addEventListener('click', () => {
            // Pour "En cours de diagnostique" spécifiquement, utiliser le cas spécial
            if (statut.nom === 'En cours de diagnostique' || statut.code === 'en_cours_diagnostique') {
                updateDirectStatus(repairId, 2, 'diagnostique', badgeElement);
                const modal = bootstrap.Modal.getInstance(document.getElementById('chooseStatusModal'));
                modal.hide();
            } 
            // Pour "Nouveau Diagnostique" spécifiquement, utiliser updateSpecificStatus avec ID=1
            else if (statut.nom === 'Nouveau Diagnostique' || statut.code === 'nouveau_diagnostique') {
                updateSpecificStatus(1, badgeElement); // ID 1 = Nouveau Diagnostique
                const modal = bootstrap.Modal.getInstance(document.getElementById('chooseStatusModal'));
                modal.hide();
            }
            // Pour "Nouvelle Commande" spécifiquement, utiliser updateSpecificStatus avec ID=3
            else if (statut.nom === 'Nouvelle Commande' || statut.code === 'nouvelle_commande') {
                updateSpecificStatus(3, badgeElement); // ID 3 = Nouvelle Commande
                const modal = bootstrap.Modal.getInstance(document.getElementById('chooseStatusModal'));
                modal.hide();
            }
            else if (statut.nom === 'Nouvelle Intervention' || statut.code === 'nouvelle_intervention') {
                // Pour "Nouvelle Intervention" spécifiquement, utiliser le code "nouvelle_intervention" avec catégorie 1
                updateSpecificStatus(2, badgeElement); // ID 2 = Nouvelle Intervention
                const modal = bootstrap.Modal.getInstance(document.getElementById('chooseStatusModal'));
                modal.hide();
            } else if (statut.nom === "En cours d'intervention" || statut.code === 'en_cours_intervention') {
                // Pour "En cours d'intervention" spécifiquement, utiliser le code "en_cours_intervention" avec catégorie 2
                updateSpecificStatus(5, badgeElement); // ID 5 = En cours d'intervention
                const modal = bootstrap.Modal.getInstance(document.getElementById('chooseStatusModal'));
                modal.hide();
            } else {
                updateSpecificStatus(statut.id, badgeElement);
            }
        });
        container.appendChild(button);
    });
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('chooseStatusModal'));
    modal.show();
} 