/**
 * Script pour gérer le basculement de statut des rapports de bugs
 */

$(document).ready(function() {
    // Initialiser tous les boutons de statut
    initStatusButtons();
});

/**
 * Initialise les boutons de statut pour chaque rapport de bug
 */
function initStatusButtons() {
    $('.bug-status-btn').on('click', function() {
        const bugId = $(this).data('bug-id');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus === 'resolu' ? 'nouveau' : 'resolu';
        
        // Référence au bouton
        const $button = $(this);
        
        // Mettre à jour visuellement le bouton
        updateButtonAppearance($button, newStatus);
        
        // Envoyer la mise à jour au serveur
        updateBugStatus(bugId, newStatus, $button);
    });
}

/**
 * Met à jour l'apparence du bouton en fonction du statut
 */
function updateButtonAppearance($button, status) {
    // Changer l'attribut data-status
    $button.attr('data-status', status);
    
    // Mettre à jour l'icône et la classe
    if (status === 'resolu') {
        $button.html('<i class="fas fa-check-circle"></i>');
        $button.removeClass('btn-outline-success').addClass('btn-success');
        $button.attr('title', 'Marquer comme non résolu');
    } else {
        $button.html('<i class="far fa-check-circle"></i>');
        $button.removeClass('btn-success').addClass('btn-outline-success');
        $button.attr('title', 'Marquer comme résolu');
    }
}

/**
 * Envoie la mise à jour du statut au serveur via une requête AJAX
 */
function updateBugStatus(bugId, status, $button) {
    console.log('Envoi de la mise à jour:', { bugId, status });
    
    // Désactiver le bouton pendant l'envoi
    $button.prop('disabled', true);
    
    // Données à envoyer
    const data = {
        id: bugId,
        statut: status
    };
    
    // Envoyer la requête
    $.ajax({
        url: 'ajax/update_bug_status.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            console.log('Réponse reçue:', response);
            
            // Réactiver le bouton
            $button.prop('disabled', false);
            
            if (response.success) {
                // Afficher un message de succès
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message || `Bug marqué comme ${status === 'resolu' ? 'résolu' : 'non résolu'}`);
                }
            } else {
                // Afficher un message d'erreur
                console.error('Erreur serveur:', response.message);
                
                // Rétablir l'apparence du bouton
                updateButtonAppearance($button, status === 'resolu' ? 'nouveau' : 'resolu');
                
                if (typeof toastr !== 'undefined') {
                    toastr.error(response.message || 'Erreur lors de la mise à jour du statut');
                } else {
                    alert(response.message || 'Erreur lors de la mise à jour du statut');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('Erreur AJAX:', error);
            console.error('Status:', status);
            console.error('Détails:', xhr);
            
            // Réactiver le bouton
            $button.prop('disabled', false);
            
            // Rétablir l'apparence du bouton
            updateButtonAppearance($button, status === 'resolu' ? 'nouveau' : 'resolu');
            
            // Afficher un message d'erreur
            if (typeof toastr !== 'undefined') {
                toastr.error('Erreur lors de la mise à jour du statut');
            } else {
                alert('Erreur lors de la mise à jour du statut');
            }
        }
    });
} 