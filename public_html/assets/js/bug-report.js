document.addEventListener('DOMContentLoaded', function() {
    const bugReportForm = document.getElementById('bug_report_form');
    const bugPageUrlInput = document.getElementById('bug_page_url');
    
    // Mettre à jour l'URL de la page courante dans le formulaire
    function updateCurrentPageUrl() {
        bugPageUrlInput.value = window.location.href;
    }
    
    // Mettre à jour l'URL à chaque ouverture du modal
    $('#declarer_bug_modal').on('show.bs.modal', function () {
        updateCurrentPageUrl();
    });
    
    // Gérer la soumission du formulaire
    if (bugReportForm) {
        bugReportForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Envoyer les données via AJAX
            fetch(bugReportForm.action, {
                method: 'POST',
                body: new FormData(bugReportForm),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer le modal
                    $('#declarer_bug_modal').modal('hide');
                    
                    // Afficher un message de succès
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Réinitialiser le formulaire
                    bugReportForm.reset();
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: error.message || 'Une erreur est survenue lors de l\'enregistrement du bug'
                });
            });
        });
    }
}); 