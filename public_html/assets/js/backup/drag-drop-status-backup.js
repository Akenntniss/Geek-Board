/**
 * SAUVEGARDE DE LA FONCTIONNALITÉ DRAG AND DROP DES STATUTS
 * ========================================================
 * Ce fichier contient une sauvegarde complète du code JavaScript nécessaire pour 
 * la fonctionnalité de glisser-déposer des réparations entre différents statuts.
 * 
 * Date de sauvegarde: <?php echo date('Y-m-d H:i:s'); ?>
 * 
 * Comment utiliser cette sauvegarde:
 * 1. Ajouter le modal HTML nécessaire (voir ci-dessous dans les commentaires)
 * 2. Ajouter l'attribut 'data-category-id' aux boutons de filtres avec classe 'filter-btn droppable'
 * 3. Ajouter les classes 'draggable-card' aux cartes/lignes avec attributs data-id/data-repair-id et data-status
 * 4. Ajouter des éléments avec classe 'status-indicator' dans chaque carte pour afficher le statut
 * 5. S'assurer que les APIs AJAX existent et fonctionnent correctement
 */

/**
 * PARTIE 1: HTML nécessaire pour le modal de sélection de statut
 * Copiez ce code dans votre fichier principal:

<!-- Modal pour choisir le statut spécifique après le drag & drop -->
<div class="modal fade" id="chooseStatusModal" tabindex="-1" aria-labelledby="chooseStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chooseStatusModalLabel">
                    <i class="fas fa-tasks me-2"></i>
                    Choisir un statut
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Annuler"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="avatar-circle bg-light d-inline-flex mb-3">
                        <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                    </div>
                    <h5 class="fw-bold">Sélectionner un statut</h5>
                    <p class="text-muted">Choisissez le statut que vous souhaitez attribuer à cette réparation</p>
                </div>
                
                <div id="statusButtonsContainer" class="d-flex flex-column gap-2">
                    <!-- Les boutons de statut seront générés dynamiquement ici -->
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement des statuts disponibles...</p>
                    </div>
                </div>
                
                <input type="hidden" id="chooseStatusRepairId" value="">
                <input type="hidden" id="chooseStatusCategoryId" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            </div>
        </div>
    </div>
</div>

 */

/**
 * PARTIE 2: CSS nécessaire
 * Copiez ces styles dans votre fichier CSS ou dans une balise <style>

/* Style pour l'avatar dans le modal */
.avatar-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* Style pour les boutons de statut */
#statusButtonsContainer .btn {
    text-align: left;
    padding: 12px 20px;
    border-radius: 8px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    font-weight: 500;
}

#statusButtonsContainer .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

#statusButtonsContainer .btn i {
    width: 24px;
    text-align: center;
}

/* Styles spécifiques pour les cartes draggable */
.draggable-card {
    cursor: grab;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.draggable-card:active {
    cursor: grabbing;
}

.draggable-card.dragging {
    opacity: 0.8;
    transform: scale(1.02);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    z-index: 1000;
}

.ghost-card {
    position: absolute;
    pointer-events: none;
    opacity: 0.7;
    z-index: 1000;
    transform: rotate(3deg);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    width: 300px;
}

.filter-btn.drag-over {
    transform: scale(1.05);
    box-shadow: 0 0 10px rgba(0,123,255,0.5);
    border: 2px dashed #0d6efd;
}

.filter-btn.drop-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
    transition: all 0.5s ease;
}

.draggable-card.updated {
    animation: card-update-success 1s ease;
}

@keyframes card-update-success {
    0% { 
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.5);
        transform: scale(1.03);
    }
    50% { 
        box-shadow: 0 0 0 6px rgba(40, 167, 69, 0.3);
    }
    100% { 
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
        transform: scale(1);
    }
}

 */

/**
 * PARTIE 3: Code JavaScript pour le drag and drop
 * Ce code initialise et gère toute la fonctionnalité de drag and drop
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le drag & drop pour les cartes de réparation
    initCardDragAndDrop();
    
    // Fonctions pour le drag & drop des cartes
    function initCardDragAndDrop() {
        // Sélectionner toutes les cartes de réparation et les lignes du tableau
        const draggableCards = document.querySelectorAll('.draggable-card');
        const dropZones = document.querySelectorAll('.filter-btn.droppable');
        
        // Variables pour le ghost element
        let ghostElement = null;
        let draggedCard = null;
        
        console.log('Initializing drag & drop with', draggableCards.length, 'cards and', dropZones.length, 'drop zones');
        
        // Ajouter les écouteurs d'événements pour les cartes et les lignes
        draggableCards.forEach(card => {
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
            
            // Empêcher la propagation du clic pour les boutons à l'intérieur des cartes
            const buttons = card.querySelectorAll('button, a');
            buttons.forEach(button => {
                button.addEventListener('mousedown', e => {
                    e.stopPropagation();
                });
                
                button.addEventListener('click', e => {
                    e.stopPropagation();
                });
            });
        });
        
        // Ajouter les écouteurs d'événements pour les zones de dépôt
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', handleDragOver);
            zone.addEventListener('dragenter', handleDragEnter);
            zone.addEventListener('dragleave', handleDragLeave);
            zone.addEventListener('drop', handleDrop);
        });
        
        /**
         * Gère le début du drag
         */
        function handleDragStart(e) {
            console.log('Début du drag sur une carte', this);
            
            // Marquer la carte comme étant en cours de déplacement
            this.classList.add('dragging');
            draggedCard = this;
            
            // Récupérer les données de réparation et de statut
            const repairId = this.getAttribute('data-repair-id') || this.getAttribute('data-id');
            const status = this.getAttribute('data-status');
            
            console.log('Données de drag:', { repairId, status });
            
            // Stocker les données de l'élément déplacé
            e.dataTransfer.setData('text/plain', JSON.stringify({
                repairId: repairId,
                status: status
            }));
            
            // Créer un "ghost element" pour le feedback visuel
            createGhostElement(this, e);
            
            // Définir l'effet de déplacement
            e.dataTransfer.effectAllowed = 'move';
        }
        
        /**
         * Gère la fin du drag
         */
        function handleDragEnd(e) {
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
            
            console.log('Drop détecté sur une zone de dépôt', this);
            
            // Récupérer les données
            try {
                const dataText = e.dataTransfer.getData('text/plain');
                console.log('Données de transfert brutes:', dataText);
                
                const data = JSON.parse(dataText);
                console.log('Données de transfert parsées:', data);
                
                const repairId = data.repairId;
                const categoryId = this.getAttribute('data-category-id');
                
                console.log('ID réparation:', repairId);
                console.log('ID catégorie:', categoryId);
                console.log('Element de statut:', draggedCard ? draggedCard.querySelector('.status-indicator') : 'Non trouvé');
                
                // Vérifier que nous avons toutes les données nécessaires
                if (!repairId || !categoryId) {
                    console.error('Données incomplètes pour la mise à jour du statut');
                    return false;
                }
                
                // Effet visuel de succès sur la zone de dépôt
                this.classList.add('drop-success');
                setTimeout(() => {
                    this.classList.remove('drop-success');
                }, 1000);
                
                // Mettre à jour le statut de la réparation via la fonction fetchStatusOptions
                if (draggedCard && draggedCard.querySelector('.status-indicator')) {
                fetchStatusOptions(repairId, categoryId, draggedCard.querySelector('.status-indicator'));
                } else {
                    console.error('Impossible de trouver l\'indicateur de statut sur la carte glissée');
                    // Essayer de créer une référence alternative
                    const allCards = document.querySelectorAll('.dashboard-card, .draggable-card');
                    let targetCard = null;
                    allCards.forEach(card => {
                        const cardId = card.getAttribute('data-repair-id') || card.getAttribute('data-id');
                        if (cardId == repairId) {
                            targetCard = card;
                        }
                    });
                    
                    if (targetCard && targetCard.querySelector('.status-indicator')) {
                        console.log('Carte cible alternative trouvée:', targetCard);
                        fetchStatusOptions(repairId, categoryId, targetCard.querySelector('.status-indicator'));
                    } else {
                        console.error('Aucune carte cible alternative trouvée, rechargement de la page');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                }
                
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
            
            // Créer un clone simplifié de la carte pour le ghost
            ghostElement = document.createElement('div');
            ghostElement.className = 'dashboard-card ghost-card';
            
            // Vérifier si c'est une ligne de tableau ou une carte
            if (sourceElement.tagName === 'TR') {
                // C'est une ligne de tableau
                const statusCell = sourceElement.querySelector('td:nth-child(6)');
                if (statusCell) {
                    const badge = statusCell.querySelector('.status-indicator');
                    if (badge) ghostElement.appendChild(badge.cloneNode(true));
                }
                
                const clientInfo = sourceElement.querySelector('td:nth-child(2) h6');
                if (clientInfo) ghostElement.appendChild(clientInfo.cloneNode(true));
            } else {
                // C'est une carte
                const statusBadge = sourceElement.querySelector('.status-indicator');
                if (statusBadge) ghostElement.appendChild(statusBadge.cloneNode(true));
                
                const deviceInfo = sourceElement.querySelector('.mb-0');
                if (deviceInfo) ghostElement.appendChild(deviceInfo.cloneNode(true));
            }
            
            // Positionner l'élément
            const rect = sourceElement.getBoundingClientRect();
            
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
});

/**
 * PARTIE 4: Fonctions utilitaires
 * Ces fonctions sont utilisées pour interagir avec le serveur et gérer l'UI
 */

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
 * Détermine la couleur Bootstrap à utiliser en fonction de la couleur de la catégorie
 */
function getCategoryColor(color) {
    // Convertir la couleur en classe Bootstrap
    const colorMap = {
        'info': 'info',
        'primary': 'primary',
        'warning': 'warning',
        'success': 'success',
        'danger': 'danger',
        'secondary': 'secondary'
    };
    return colorMap[color] || 'primary';
}

/**
 * Récupère les options de statut pour une catégorie donnée
 * Cette fonction appelle l'API ../ajax/get_statuts_by_category.php
 */
function fetchStatusOptions(repairId, categoryId, statusIndicator) {
    // Afficher un indicateur de chargement dans le badge
    statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Chargement...</span>';

    console.log('Récupération des statuts pour la catégorie:', categoryId);
    
    // Récupérer les statuts disponibles pour cette catégorie
    fetch(`../ajax/get_statuts_by_category.php?category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Réponse de get_statuts_by_category:', data);
            
            if (data.success) {
                // Stocker les IDs pour une utilisation ultérieure
                document.getElementById('chooseStatusRepairId').value = repairId;
                document.getElementById('chooseStatusCategoryId').value = categoryId;
                
                // Générer les boutons de statut
                const container = document.getElementById('statusButtonsContainer');
                container.innerHTML = ''; // Effacer le contenu précédent, y compris l'indicateur de chargement
                
                // Déterminer la couleur de la catégorie
                const categoryColor = getCategoryColor(data.category.couleur);
                
                // Ajouter un titre pour la catégorie dans le modal
                const categoryTitle = document.getElementById('chooseStatusModalLabel');
                if (categoryTitle) {
                    categoryTitle.innerHTML = `<i class="fas fa-tasks me-2"></i> Statuts "${data.category.nom}"`;
                }
                
                // Créer un bouton pour chaque statut
                data.statuts.forEach(statut => {
                    const button = document.createElement('button');
                    button.className = `btn btn-${categoryColor} btn-lg w-100 mb-2`;
                    button.setAttribute('data-status-id', statut.id);
                    button.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>
                        ${statut.nom}
                    `;
                    button.addEventListener('click', () => updateSpecificStatus(statut.id, statusIndicator));
                    container.appendChild(button);
                });
                
                // Afficher le modal
                const modal = new bootstrap.Modal(document.getElementById('chooseStatusModal'));
                modal.show();
                
                // Rétablir le badge de statut quand l'utilisateur annule
                const closeBtn = document.querySelector('#chooseStatusModal .btn-close');
                const cancelBtn = document.querySelector('#chooseStatusModal .btn-outline-secondary');
                
                const handleCancel = function() {
                    console.log('Annulation de la sélection de statut');
                    // Nettoyer le backdrop et réactiver le scroll
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove();
                    }
                    
                    // Simplement recharger la page pour éviter l'update automatique
                    location.reload();
                };
                
                if (closeBtn) {
                    // Enlever les anciens écouteurs d'événements
                    closeBtn.removeEventListener('click', handleCancel);
                    // Ajouter le nouvel écouteur
                    closeBtn.addEventListener('click', handleCancel);
                }
                
                if (cancelBtn) {
                    // Enlever les anciens écouteurs d'événements
                    cancelBtn.removeEventListener('click', handleCancel);
                    // Ajouter le nouvel écouteur
                    cancelBtn.addEventListener('click', handleCancel);
                }
                
            } else {
                // Afficher l'erreur
                showNotification('Erreur: ' + data.error, 'danger');
                location.reload(); // Recharger la page en cas d'erreur
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des statuts:', error);
            showNotification('Erreur de communication avec le serveur', 'danger');
            location.reload(); // Recharger la page en cas d'erreur
        });
}

/**
 * Met à jour le statut spécifique d'une réparation
 * Cette fonction appelle l'API ../ajax/update_repair_specific_status.php
 */
function updateSpecificStatus(statusId, statusIndicator) {
    // Récupérer les ID stockés
    const repairId = document.getElementById('chooseStatusRepairId').value;

    console.log('Mise à jour du statut:', statusId, 'pour la réparation:', repairId);
    
    // Fermer le modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('chooseStatusModal'));
    modal.hide();
    
    // Nettoyer le backdrop et réactiver le scroll
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
    
    // Afficher un indicateur de chargement
    statusIndicator.innerHTML = '<span class="badge bg-secondary"><i class="fas fa-spinner fa-spin"></i> Mise à jour...</span>';
    
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
        console.log('Réponse de update_repair_specific_status:', data);
        
        if (data.success) {
            // Mettre à jour le badge avec le nouveau statut
            statusIndicator.innerHTML = data.data.badge;
            
            // Mettre à jour l'attribut data-status de la carte
            const card = statusIndicator.closest('.draggable-card');
            if (card) {
                card.setAttribute('data-status', data.data.statut);
                
                // Animation de succès
                card.classList.add('updated');
                setTimeout(() => {
                    card.classList.remove('updated');
                }, 1000);
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
        console.error('Erreur lors de la mise à jour du statut:', error);
        showNotification('Erreur de communication avec le serveur', 'danger');
        
        // Recharger la page pour rétablir l'état correct
        setTimeout(() => {
            location.reload();
        }, 2000);
    });
}

/**
 * PARTIE 5: Structure des données échangées avec le serveur
 * 
 * 1. API get_statuts_by_category.php:
 * -------------------------------------
 * Requête: GET ../ajax/get_statuts_by_category.php?category_id=1
 * 
 * Réponse attendue:
 * {
 *    "success": true,
 *    "category": {
 *       "id": 1,
 *       "nom": "Nouvelle",
 *       "couleur": "primary"
 *    },
 *    "statuts": [
 *       { "id": 1, "nom": "À diagnostiquer", "code": "DIAG" },
 *       { "id": 2, "nom": "En attente de confirmation", "code": "CONF" },
 *       { "id": 3, "nom": "Devis à réaliser", "code": "DEVIS" }
 *    ]
 * }
 * 
 * 2. API update_repair_specific_status.php:
 * -----------------------------------------
 * Requête: POST ../ajax/update_repair_specific_status.php
 * Body: { "repair_id": 123, "status_id": 2 }
 * 
 * Réponse attendue:
 * {
 *    "success": true,
 *    "data": {
 *       "badge": "<span class=\"badge bg-primary\">En attente de confirmation</span>",
 *       "statut": "CONF"
 *    }
 * }
 */

/**
 * PARTIE 6: Comment ajouter les classes et attributs HTML nécessaires
 * 
 * 1. Pour les cartes/lignes de réparation:
 * <div class="dashboard-card repair-row draggable-card" 
 *      data-id="123" 
 *      data-repair-id="123" 
 *      data-status="DIAG" 
 *      draggable="true">
 *   <!-- Contenu de la carte -->
 *   <span class="status-indicator">
 *     <!-- Badge de statut ici -->
 *   </span>
 * </div>
 * 
 * 2. Pour les boutons de filtres:
 * <a href="javascript:void(0);" 
 *    class="filter-btn droppable" 
 *    data-category-id="1">
 *   <i class="fas fa-plus-circle"></i>
 *   <span>Nouvelle</span>
 * </a>
 */

/**
 * PARTIE 7: Dépannage
 * 
 * Si le drag and drop ne fonctionne pas :
 * 
 * 1. Vérifiez que la console ne contient pas d'erreurs JavaScript
 * 2. Assurez-vous que les éléments ont bien l'attribut draggable="true"
 * 3. Vérifiez que les classes CSS sont correctement appliquées
 * 4. Assurez-vous que les boutons ont bien l'attribut data-category-id
 * 5. Vérifiez que les API AJAX répondent correctement
 * 6. Inspectez les éléments pour vérifier qu'ils ont bien les classes .draggable-card et .status-indicator
 * 7. Vérifiez que les attributs data-id, data-repair-id et data-status sont présents
 * 8. Testez d'abord sur un navigateur comme Chrome qui a de bons outils de développement
 */ 