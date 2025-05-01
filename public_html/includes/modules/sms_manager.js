/**
 * Module de gestion des SMS
 * Ce module fournit des fonctions pour envoyer des SMS depuis différentes parties de l'application
 */

const SmsManager = {
    /**
     * Initialise les notifications toast
     */
    initToastContainer: function() {
        if (!document.getElementById('toast-container')) {
            const toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
    },

    /**
     * Affiche une notification toast
     * @param {string} message - Le message à afficher
     * @param {string} type - Le type de notification (success, error, warning, info)
     */
    showToast: function(message, type = 'success') {
        this.initToastContainer();
        const toastContainer = document.getElementById('toast-container');
        
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            </div>
            <div class="toast-message">${message}</div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Supprimer le toast après 5 secondes
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => {
                if (toastContainer.contains(toast)) {
                    toastContainer.removeChild(toast);
                }
            }, 500);
        }, 5000);
    },

    /**
     * Affiche une animation de chargement overlay
     * @returns {HTMLElement} L'élément de chargement créé
     */
    showLoadingOverlay: function() {
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = '<div class="spinner-border text-light" role="status"><span class="visually-hidden">Envoi en cours...</span></div>';
        document.body.appendChild(loadingOverlay);
        return loadingOverlay;
    },

    /**
     * Supprime l'animation de chargement
     * @param {HTMLElement} loadingOverlay - L'élément de chargement à supprimer
     */
    hideLoadingOverlay: function(loadingOverlay) {
        if (document.body.contains(loadingOverlay)) {
            document.body.removeChild(loadingOverlay);
        }
    },

    /**
     * Envoie un SMS personnalisé
     * @param {string} message - Le contenu du SMS
     * @param {Object} data - Les données du SMS (client_id, telephone, reparation_id)
     * @param {function} onSuccess - Fonction à exécuter en cas de succès
     * @param {function} onError - Fonction à exécuter en cas d'erreur
     */
    sendCustomSms: function(message, data, onSuccess = null, onError = null) {
        const loadingOverlay = this.showLoadingOverlay();
        
        const formData = new FormData();
        formData.append('client_id', data.client_id);
        formData.append('client_telephone', data.telephone);
        formData.append('reparation_id', data.reparation_id);
        formData.append('message', message);
        formData.append('type', 'custom');
        
        fetch('ajax/send_sms.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoadingOverlay(loadingOverlay);
            
            if (data.success) {
                this.showToast(`SMS envoyé avec succès à ${formData.get('client_telephone')}`, 'success');
                if (onSuccess) onSuccess(data);
            } else {
                this.showToast(`Erreur: ${data.message || 'Impossible d\'envoyer le SMS'}`, 'error');
                if (onError) onError(data);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            this.hideLoadingOverlay(loadingOverlay);
            this.showToast('Erreur technique: Impossible d\'envoyer le SMS', 'error');
            if (onError) onError({success: false, message: 'Erreur technique'});
        });
    },

    /**
     * Envoie un SMS prédéfini à partir d'un modèle
     * @param {number} templateId - ID du modèle de SMS
     * @param {Object} data - Les données du SMS (client_id, telephone, reparation_id)
     * @param {function} onSuccess - Fonction à exécuter en cas de succès
     * @param {function} onError - Fonction à exécuter en cas d'erreur
     */
    sendPredefinedSms: function(templateId, data, onSuccess = null, onError = null) {
        const loadingOverlay = this.showLoadingOverlay();
        
        const formData = new FormData();
        formData.append('client_id', data.client_id);
        formData.append('client_telephone', data.telephone);
        formData.append('reparation_id', data.reparation_id);
        formData.append('template_id', templateId);
        formData.append('type', 'predefined');
        
        fetch('ajax/send_sms.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.hideLoadingOverlay(loadingOverlay);
            
            if (data.success) {
                this.showToast(`SMS envoyé avec succès à ${formData.get('client_telephone')}`, 'success');
                if (onSuccess) onSuccess(data);
            } else {
                this.showToast(`Erreur: ${data.message || 'Impossible d\'envoyer le SMS'}`, 'error');
                if (onError) onError(data);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            this.hideLoadingOverlay(loadingOverlay);
            this.showToast('Erreur technique: Impossible d\'envoyer le SMS', 'error');
            if (onError) onError({success: false, message: 'Erreur technique'});
        });
    },

    /**
     * Prévisualiser un SMS avant envoi
     * @param {string} message - Contenu du SMS
     * @param {string} source - Source du message (custom ou predefined)
     * @param {Object} data - Données additionnelles
     * @param {function} onConfirm - Fonction à exécuter lors de la confirmation
     */
    previewSms: function(message, source, data, onConfirm) {
        // Vérifier si le modal existe déjà
        let smsPreviewModal = document.getElementById('smsPreviewModal');
        if (!smsPreviewModal) {
            // Créer le modal de prévisualisation
            const modalHtml = `
                <div class="modal fade" id="smsPreviewModal" tabindex="-1" aria-labelledby="smsPreviewModalLabel" aria-hidden="true" data-source="">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <div class="modal-header bg-primary text-white border-bottom-0 rounded-top-4">
                                <h5 class="modal-title" id="smsPreviewModalLabel">
                                    <i class="fas fa-sms me-2"></i>Prévisualisation du SMS
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="preview-container bg-light p-3 rounded-3 mb-3">
                                    <div class="preview-header mb-2">
                                        <small class="text-muted">Le message suivant sera envoyé à <strong id="previewRecipient"></strong> :</small>
                                    </div>
                                    <div class="preview-body border-start border-4 border-primary ps-3 py-2">
                                        <p id="previewText" class="mb-0"></p>
                                    </div>
                                </div>
                                <div class="preview-info">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-info me-2">Info</span>
                                        <small class="text-muted">Longueur du message: <span id="messageLength">0</span> caractères</small>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="button" class="btn btn-primary" id="confirmSendSmsBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Envoyer le SMS
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Ajouter le modal au body
            const div = document.createElement('div');
            div.innerHTML = modalHtml;
            document.body.appendChild(div.firstChild);
            
            // Initialiser le modal
            smsPreviewModal = document.getElementById('smsPreviewModal');
        }
        
        // Mettre à jour le contenu du modal
        document.getElementById('previewText').innerHTML = message.replace(/\n/g, '<br>');
        document.getElementById('previewRecipient').textContent = data.telephone;
        document.getElementById('messageLength').textContent = message.length;
        smsPreviewModal.setAttribute('data-source', source);
        
        // Initialiser le bouton de confirmation
        const confirmBtn = document.getElementById('confirmSendSmsBtn');
        confirmBtn.addEventListener('click', function() {
            if (onConfirm) onConfirm();
        }, { once: true }); // Utiliser once: true pour éviter les listeners multiples
        
        // Afficher le modal
        const bsModal = new bootstrap.Modal(smsPreviewModal);
        bsModal.show();
        
        return bsModal;
    }
};

// Ajouter des styles pour les toasts
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        .toast-container {
            z-index: 1060;
        }
        
        .toast-notification {
            display: flex;
            align-items: center;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            margin-bottom: 10px;
            overflow: hidden;
            padding: 12px 15px;
            transition: all 0.3s ease;
            max-width: 350px;
        }
        
        .toast-notification.success {
            border-left: 4px solid #2ecc71;
        }
        
        .toast-notification.error {
            border-left: 4px solid #e74c3c;
        }
        
        .toast-notification.warning {
            border-left: 4px solid #f39c12;
        }
        
        .toast-notification.info {
            border-left: 4px solid #3498db;
        }
        
        .toast-icon {
            margin-right: 12px;
            font-size: 20px;
        }
        
        .toast-notification.success .toast-icon {
            color: #2ecc71;
        }
        
        .toast-notification.error .toast-icon {
            color: #e74c3c;
        }
        
        .toast-notification.warning .toast-icon {
            color: #f39c12;
        }
        
        .toast-notification.info .toast-icon {
            color: #3498db;
        }
        
        .toast-message {
            flex-grow: 1;
            font-size: 14px;
        }
        
        .toast-notification.fade-out {
            opacity: 0;
            transform: translateX(10px);
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
    `;
    document.head.appendChild(style);
});

// Exporter le module
window.SmsManager = SmsManager; 