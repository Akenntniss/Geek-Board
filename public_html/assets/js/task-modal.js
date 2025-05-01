/**
 * Module de gestion du modal des tâches
 */
const TaskModal = {
    // Éléments DOM
    elements: {
        modal: null,
        detailsContainer: null,
        loader: null
    },

    // Configuration
    config: {
        apiUrl: 'ajax/get_task_details.php',
    },
    
    // Flag d'initialisation
    _isInitialized: false,

    /**
     * Initialise le module
     */
    init() {
        // Vérifier si déjà initialisé
        if (this._isInitialized) {
            console.log('TaskModal déjà initialisé');
            return;
        }
        
        // Créer le modal s'il n'existe pas
        this.createModal();
        
        // Récupérer les éléments
        this.elements.modal = document.getElementById('taskDetailsModal');
        this.elements.detailsContainer = document.getElementById('taskDetailsContent');
        this.elements.loader = document.getElementById('taskDetailsLoader');
        
        // Ajouter les écouteurs d'événements pour les clics sur les tâches
        document.addEventListener('click', (e) => {
            const taskRow = e.target.closest('.table-row-hover');
            if (taskRow) {
                const taskId = taskRow.getAttribute('data-task-id');
                if (taskId) {
                    this.loadTaskDetails(taskId);
                }
            }
        });
        
        // Marquer comme initialisé
        this._isInitialized = true;
        
        console.log('TaskModal initialisé avec succès');
    },

    /**
     * Crée le modal s'il n'existe pas
     */
    createModal() {
        const modalHTML = `
            <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="taskDetailsModalLabel">
                                <i class="fas fa-tasks me-2"></i>
                                Détails de la tâche
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div id="taskDetailsLoader" class="text-center py-3" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                            <div id="taskDetailsContent">
                                <!-- Le contenu sera chargé dynamiquement ici -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="button" class="btn btn-primary" id="startTaskBtn">Démarrer</button>
                            <button type="button" class="btn btn-success" id="completeTaskBtn">Terminer</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Ajouter le modal au body s'il n'existe pas déjà
        if (!document.getElementById('taskDetailsModal')) {
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
    },

    /**
     * Charge les détails d'une tâche
     * @param {string} taskId - ID de la tâche
     */
    loadTaskDetails(taskId) {
        // Afficher le loader
        this.showLoader();
        
        // Récupérer les données
        fetch(`${this.config.apiUrl}?id=${taskId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Erreur lors du chargement des détails');
                }
                
                // Mettre à jour le titre du modal
                document.getElementById('taskDetailsModalLabel').innerHTML = `
                    <i class="fas fa-tasks me-2"></i>
                    ${data.task.titre}
                `;
                
                // Afficher les détails
                this.renderTaskDetails(data.task);
                
                // Configurer les boutons d'action
                this.setupActionButtons(taskId, data.task.statut);
            })
            .catch(error => {
                console.error('Erreur:', error);
                this.showError(`Erreur lors du chargement des détails: ${error.message}`);
            })
            .finally(() => {
                this.hideLoader();
            });
        
        // Afficher le modal
        const modal = bootstrap.Modal.getOrCreateInstance(this.elements.modal);
        modal.show();
    },

    /**
     * Affiche les détails de la tâche dans le modal
     * @param {Object} task - Données de la tâche
     */
    renderTaskDetails(task) {
        const content = `
            <div class="task-details">
                <div class="mb-3">
                    <h6 class="text-muted">Description</h6>
                    <p>${task.description || 'Aucune description'}</p>
                </div>
                <div class="mb-3">
                    <h6 class="text-muted">Priorité</h6>
                    <span class="badge bg-${this.getPriorityColor(task.priorite)}">
                        ${task.priorite}
                    </span>
                </div>
                <div class="mb-3">
                    <h6 class="text-muted">Statut</h6>
                    <span class="badge bg-${this.getStatusColor(task.statut)}">
                        ${task.statut}
                    </span>
                </div>
                <div class="mb-3">
                    <h6 class="text-muted">Date de création</h6>
                    <p>${new Date(task.date_creation).toLocaleString()}</p>
                </div>
            </div>
        `;
        
        this.elements.detailsContainer.innerHTML = content;
    },

    /**
     * Configure les boutons d'action en fonction du statut
     * @param {string} taskId - ID de la tâche
     * @param {string} status - Statut actuel de la tâche
     */
    setupActionButtons(taskId, status) {
        const startBtn = document.getElementById('startTaskBtn');
        const completeBtn = document.getElementById('completeTaskBtn');
        
        // Réinitialiser les boutons
        startBtn.style.display = 'none';
        completeBtn.style.display = 'none';
        
        // Configurer les boutons selon le statut
        switch(status.toLowerCase()) {
            case 'à faire':
                startBtn.style.display = 'block';
                startBtn.onclick = () => this.updateTaskStatus(taskId, 'en_cours');
                break;
            case 'en cours':
                completeBtn.style.display = 'block';
                completeBtn.onclick = () => this.updateTaskStatus(taskId, 'terminée');
                break;
        }
    },

    /**
     * Met à jour le statut d'une tâche
     * @param {string} taskId - ID de la tâche
     * @param {string} newStatus - Nouveau statut
     */
    updateTaskStatus(taskId, newStatus) {
        fetch('ajax/update_task_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                task_id: taskId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(this.elements.modal);
                modal.hide();
                
                // Rafraîchir la page pour mettre à jour la liste des tâches
                window.location.reload();
            } else {
                throw new Error(data.error || 'Erreur lors de la mise à jour du statut');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            this.showError(`Erreur lors de la mise à jour du statut: ${error.message}`);
        });
    },

    /**
     * Retourne la couleur Bootstrap en fonction de la priorité
     * @param {string} priority - Priorité de la tâche
     * @returns {string} - Classe de couleur Bootstrap
     */
    getPriorityColor(priority) {
        switch(priority.toLowerCase()) {
            case 'haute':
                return 'danger';
            case 'moyenne':
                return 'warning';
            case 'basse':
                return 'info';
            default:
                return 'secondary';
        }
    },

    /**
     * Retourne la couleur Bootstrap en fonction du statut
     * @param {string} status - Statut de la tâche
     * @returns {string} - Classe de couleur Bootstrap
     */
    getStatusColor(status) {
        switch(status.toLowerCase()) {
            case 'à faire':
                return 'secondary';
            case 'en cours':
                return 'primary';
            case 'terminée':
                return 'success';
            default:
                return 'secondary';
        }
    },

    /**
     * Affiche le loader
     */
    showLoader() {
        if (this.elements.loader) {
            this.elements.loader.style.display = 'block';
        }
    },

    /**
     * Cache le loader
     */
    hideLoader() {
        if (this.elements.loader) {
            this.elements.loader.style.display = 'none';
        }
    },

    /**
     * Affiche une erreur dans le modal
     * @param {string} message - Message d'erreur
     */
    showError(message) {
        this.elements.detailsContainer.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${message}
            </div>
        `;
    }
};

// Initialiser le module quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    TaskModal.init();
}); 