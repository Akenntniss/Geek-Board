/**
 * Exemple d'utilisation du module SmsManager
 */
document.addEventListener('DOMContentLoaded', function() {
    // Assurez-vous que le module SmsManager est chargé
    if (!window.SmsManager) {
        console.error('Module SmsManager non disponible. Veuillez inclure le fichier sms_manager.js');
        return;
    }

    // Exemple 1: Envoyer un SMS personnalisé directement
    // --------------------------------------------------
    const btnEnvoyerSMS = document.getElementById('btnEnvoyerSMS');
    if (btnEnvoyerSMS) {
        btnEnvoyerSMS.addEventListener('click', function() {
            const texteSMS = document.getElementById('texteSMS').value;
            const clientData = {
                client_id: document.querySelector('input[name="client_id"]').value,
                telephone: document.querySelector('input[name="client_telephone"]').value,
                reparation_id: document.querySelector('input[name="reparation_id"]').value
            };
            
            // Envoyer le SMS directement
            SmsManager.sendCustomSms(texteSMS, clientData, 
                // Callback de succès
                function(data) {
                    console.log('SMS envoyé avec succès:', data);
                    // Réinitialiser le formulaire ou fermer le modal si nécessaire
                    const modalSMS = bootstrap.Modal.getInstance(document.getElementById('modalSMS'));
                    if (modalSMS) modalSMS.hide();
                },
                // Callback d'erreur
                function(error) {
                    console.error('Erreur lors de l\'envoi du SMS:', error);
                }
            );
        });
    }

    // Exemple 2: Prévisualisation avant envoi
    // --------------------------------------
    const btnPreviewSMS = document.getElementById('btnPreviewSMS');
    if (btnPreviewSMS) {
        btnPreviewSMS.addEventListener('click', function() {
            const texteSMS = document.getElementById('texteSMS').value;
            const clientData = {
                client_id: document.querySelector('input[name="client_id"]').value,
                telephone: document.querySelector('input[name="client_telephone"]').value,
                reparation_id: document.querySelector('input[name="reparation_id"]').value
            };
            
            // Afficher la prévisualisation
            SmsManager.previewSms(texteSMS, 'custom', clientData, function() {
                // Cette fonction sera appelée lors du clic sur le bouton de confirmation
                SmsManager.sendCustomSms(texteSMS, clientData, 
                    function(data) {
                        // Fermer tous les modaux ouverts
                        document.querySelectorAll('.modal').forEach(modalEl => {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        });
                        
                        console.log('SMS envoyé avec succès après prévisualisation:', data);
                    }
                );
            });
        });
    }

    // Exemple 3: Envoi d'un SMS à partir d'un modèle prédéfini
    // -------------------------------------------------------
    const templateButtons = document.querySelectorAll('.template-select-btn');
    if (templateButtons.length > 0) {
        templateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const templateId = this.getAttribute('data-template-id');
                const clientData = {
                    client_id: document.querySelector('input[name="client_id"]').value,
                    telephone: document.querySelector('input[name="client_telephone"]').value,
                    reparation_id: document.querySelector('input[name="reparation_id"]').value
                };
                
                // Envoyer le SMS avec le modèle prédéfini
                SmsManager.sendPredefinedSms(templateId, clientData);
            });
        });
    }

    // Exemple 4: Afficher une notification toast manuellement
    // -----------------------------------------------------
    const btnShowToast = document.getElementById('btnShowToast');
    if (btnShowToast) {
        btnShowToast.addEventListener('click', function() {
            const toastType = this.getAttribute('data-toast-type') || 'success';
            const message = 'Ceci est un exemple de notification ' + toastType;
            
            SmsManager.showToast(message, toastType);
        });
    }
}); 