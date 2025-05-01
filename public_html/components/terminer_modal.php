<?php
// Ce fichier contient le modal pour finaliser une réparation avec l'option d'envoi de SMS
?>

<!-- Modal de finalisation de réparation -->
<div class="modal fade" id="terminerModal" tabindex="-1" aria-labelledby="terminerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="terminerModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Finaliser la réparation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form action="index.php?page=statut_rapide&id=<?php echo $reparation_id; ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="finaliser">
                    <input type="hidden" name="reparation_id" value="<?php echo $reparation_id; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Choisir un statut final</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="statut_final" 
                                           id="statut_effectue" value="reparation_effectue" checked>
                                    <label class="form-check-label" for="statut_effectue">
                                        <span class="badge bg-success me-2">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                        Réparation effectuée
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="statut_final" 
                                           id="statut_restitue" value="restitue">
                                    <label class="form-check-label" for="statut_restitue">
                                        <span class="badge bg-info me-2">
                                            <i class="fas fa-handshake"></i>
                                        </span>
                                        Restitué au client
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="statut_final" 
                                           id="statut_annule" value="reparation_annule">
                                    <label class="form-check-label" for="statut_annule">
                                        <span class="badge bg-danger me-2">
                                            <i class="fas fa-times-circle"></i>
                                        </span>
                                        Réparation annulée
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="statut_final" 
                                           id="statut_attente_responsable" value="en_attente_responsable">
                                    <label class="form-check-label" for="statut_attente_responsable">
                                        <span class="badge bg-warning me-2">
                                            <i class="fas fa-user-tie"></i>
                                        </span>
                                        En attente d'un responsable
                                    </label>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="statut_id" value="9">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes_techniques" class="form-label fw-bold">Notes techniques</label>
                        <textarea class="form-control" id="notes_techniques" name="notes_techniques" rows="3" 
                                  placeholder="Ajoutez les informations techniques sur la réparation..."></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="envoi_sms" name="envoi_sms" checked>
                        <label class="form-check-label" for="envoi_sms">
                            <i class="fas fa-sms me-1"></i>
                            Envoyer un SMS de notification au client
                        </label>
                        <div class="form-text">Un SMS sera envoyé au client si un modèle est disponible pour le statut choisi.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i>Finaliser
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Mettre à jour l'ID de réparation lorsque le modal est ouvert
document.addEventListener('DOMContentLoaded', function() {
    const terminerModal = document.getElementById('terminerModal');
    if (terminerModal) {
        terminerModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const reparationId = button.getAttribute('data-reparation-id') || <?php echo $reparation_id ?? 0; ?>;
            document.getElementById('terminerReparationId').value = reparationId;
        });
    }
});
</script> 