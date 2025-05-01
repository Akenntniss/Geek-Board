/**
 * Module de gestion du modal d'envoi de SMS
 */
const SmsModal = {
    // Éléments DOM
    elements: {
        modal: null,
        container: null,
        repairIdInput: null,
        statusIdInput: null,
        title: null,
        messagePreview: null,
        sendButton: null,
        cancelButton: null
    },

    // Configuration
    config: {
        apiUrl: 'ajax/send_status_sms.php'
    },

    /**
     * Initialise le module
     */
    init() {
        // Créer le modal s'il n'existe pas
        if (!document.getElementById('sendSmsModal')) {
            this.createModal();
        }

        // Récupérer les éléments du DOM
        this.elements.modal = document.getElementById('sendSmsModal');
        this.elements.container = document.getElementById('smsModalContent');
        this.elements.repairIdInput = document.getElementById('sendSmsRepairId');
        this.elements.statusIdInput = document.getElementById('sendSmsStatusId');
        this.elements.title = document.getElementById('sendSmsModalLabel');
        this.elements.messagePreview = document.getElementById('smsMessagePreview');
        this.elements.sendButton = document.getElementById('sendSmsButton');
        this.elements.cancelButton = document.getElementById('cancelSmsButton');

        // Initialiser les événements
        this.initEvents();
    },

    /**
     * Crée le modal dans le DOM
     */
    createModal() {
        const modalHTML = `
            <div class="modal fade" id="sendSmsModal" tabindex="-1" aria-labelledby="sendSmsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title" id="sendSmsModalLabel">
                                <i class="fas fa-sms me-2"></i>
                                Envoyer un SMS
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <div class="avatar-circle bg-light d-inline-flex mb-3">
                                    <i class="fas fa-sms fa-2x text-primary"></i>
                                </div>
                                <h5 class="fw-bold">Message SMS pré-défini</h5>
                                <p class="text-muted">Un message SMS est disponible pour ce statut. Voulez-vous l'envoyer au client ?</p>
                            </div>
                            
                            <div id="smsModalContent">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Chargement du message...
                                </div>
                            </div>
                            
                            <input type="hidden" id="sendSmsRepairId" value="">
                            <input type="hidden" id="sendSmsStatusId" value="">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" id="cancelSmsButton" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary" id="sendSmsButton">
                                <i class="fas fa-paper-plane me-2"></i>Envoyer le SMS
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    },

    /**
     * Initialise les événements du modal
     */
    initEvents() {
        // Événement pour le bouton d'envoi
        this.elements.sendButton.addEventListener('click', () => {
            this.sendSms();
        });

        // Événement pour le bouton d'annulation
        this.elements.cancelButton.addEventListener('click', () => {
            this.closeModal();
        });
    },

    /**
     * Affiche le modal avec le message pré-défini
     * @param {string} repairId - ID de la réparation
     * @param {string} statusId - ID du statut
     */
    show(repairId, statusId) {
        if (!repairId || !statusId) {
            console.error('ID de réparation ou de statut non spécifié');
            return;
        }

        // Stocker les IDs
        this.elements.repairIdInput.value = repairId;
        this.elements.statusIdInput.value = statusId;

        // Afficher un indicateur de chargement
        this.elements.container.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2">Chargement du message...</p>
            </div>
        `;

        // Récupérer le message pré-défini
        fetch(`${this.config.apiUrl}?repair_id=${repairId}&status_id=${statusId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher le message pré-défini
                    this.elements.container.innerHTML = `
                        <div class="form-group">
                            <label class="form-label fw-bold">Message pré-défini :</label>
                            <div class="alert alert-light border">
                                <pre class="mb-0">${data.message}</pre>
                            </div>
                        </div>
                    `;
                } else {
                    // Afficher un message d'erreur
                    this.elements.container.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.message || 'Aucun message pré-défini disponible pour ce statut.'}
                        </div>
                    `;
                    this.elements.sendButton.disabled = true;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                this.elements.container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Une erreur est survenue lors du chargement du message.
                    </div>
                `;
                this.elements.sendButton.disabled = true;
            });

        // Afficher le modal
        const modal = new bootstrap.Modal(this.elements.modal);
        modal.show();
    },

    /**
     * Envoie le SMS
     */
    sendSms() {
        const repairId = this.elements.repairIdInput.value;
        const statusId = this.elements.statusIdInput.value;

        // Désactiver le bouton pendant l'envoi
        this.elements.sendButton.disabled = true;
        this.elements.sendButton.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            Envoi en cours...
        `;

        // Envoyer le SMS
        fetch(this.config.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                repair_id: repairId,
                status_id: statusId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès
                this.elements.container.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        ${data.message || 'Le SMS a été envoyé avec succès.'}
                    </div>
                `;
                
                // Fermer le modal après 2 secondes
                setTimeout(() => {
                    this.closeModal();
                }, 2000);
            } else {
                // Afficher un message d'erreur
                this.elements.container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${data.message || 'Une erreur est survenue lors de l\'envoi du SMS.'}
                    </div>
                `;
                
                // Réactiver le bouton
                this.elements.sendButton.disabled = false;
                this.elements.sendButton.innerHTML = `
                    <i class="fas fa-paper-plane me-2"></i>Envoyer le SMS
                `;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            this.elements.container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Une erreur est survenue lors de l'envoi du SMS.
                </div>
            `;
            
            // Réactiver le bouton
            this.elements.sendButton.disabled = false;
            this.elements.sendButton.innerHTML = `
                <i class="fas fa-paper-plane me-2"></i>Envoyer le SMS
            `;
        });
    },

    /**
     * Ferme le modal
     */
    closeModal() {
        const modal = bootstrap.Modal.getInstance(this.elements.modal);
        if (modal) {
            modal.hide();
        }
    }
};

// Initialiser le module une fois le DOM chargé
document.addEventListener('DOMContentLoaded', () => {
    SmsModal.init();
}); 