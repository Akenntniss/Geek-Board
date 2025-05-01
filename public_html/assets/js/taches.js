document.addEventListener('DOMContentLoaded', function() {
    // Ajouter les gestionnaires d'événements pour les boutons de changement de statut de tâche
    document.getElementById('start-task-btn')?.addEventListener('click', updateTaskStatus);
    document.getElementById('complete-task-btn')?.addEventListener('click', updateTaskStatus);
});

// Fonction pour afficher les détails d'une tâche
function afficherDetailsTache(event, taskId) {
    console.log("Fonction afficherDetailsTache appelée avec taskId:", taskId);
    // Empêcher la propagation de l'événement
    event.stopPropagation();
    
    // Trouver la ligne de la tâche correspondante
    const taskRow = document.querySelector(`[data-task-id="${taskId}"]`).closest('tr');
    console.log("Ligne de tâche trouvée:", taskRow);
    
    if (taskRow) {
        // Récupérer les informations de la tâche
        const title = taskRow.querySelector('td:nth-child(1)').textContent.trim();
        const priority = taskRow.querySelector('td:nth-child(2) .badge').textContent.trim();
        console.log("Informations de la tâche:", { title, priority });
        
        // Remplir le modal avec les informations de la tâche
        document.getElementById('task-title').textContent = title;
        document.getElementById('task-description').textContent = "Chargement...";
        document.getElementById('task-priority').textContent = priority;
        
        // Mettre à jour les attributs data-task-id des boutons
        document.getElementById('start-task-btn').setAttribute('data-task-id', taskId);
        document.getElementById('complete-task-btn').setAttribute('data-task-id', taskId);
        
        // Gérer l'état actif/inactif des boutons
        const startButton = document.getElementById('start-task-btn');
        const completeButton = document.getElementById('complete-task-btn');
        
        // Par défaut, activer les deux boutons
        startButton.disabled = false;
        startButton.classList.remove('btn-secondary');
        startButton.classList.add('btn-primary');
        
        completeButton.disabled = false;
        completeButton.classList.remove('btn-secondary');
        completeButton.classList.add('btn-success');
        
        // Afficher le modal
        const taskModal = document.getElementById('taskDetailsModal');
        console.log("Modal trouvé:", taskModal);
        if (taskModal) {
            const bsModal = new bootstrap.Modal(taskModal);
            console.log("Modal Bootstrap créé:", bsModal);
            bsModal.show();
            console.log("Modal affiché");
            
            // Charger la description de la tâche via AJAX
            fetch(`ajax/get_task_details.php?id=${taskId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('task-description').textContent = data.description || "Aucune description disponible";
                        
                        // Mettre à jour les boutons en fonction du statut
                        if (data.status === 'termine') {
                            startButton.disabled = true;
                            startButton.classList.remove('btn-primary');
                            startButton.classList.add('btn-secondary');
                            
                            completeButton.disabled = true;
                            completeButton.classList.remove('btn-success');
                            completeButton.classList.add('btn-secondary');
                        } else if (data.status === 'en_cours') {
                            startButton.disabled = true;
                            startButton.classList.remove('btn-primary');
                            startButton.classList.add('btn-secondary');
                        }
                    } else {
                        document.getElementById('task-description').textContent = "Erreur lors du chargement de la description";
                        document.getElementById('task-error-container').style.display = 'block';
                        document.getElementById('task-error-container').textContent = data.message || "Une erreur est survenue";
                    }
                })
                .catch(error => {
                    console.error("Erreur lors du chargement des détails de la tâche:", error);
                    document.getElementById('task-description').textContent = "Erreur lors du chargement de la description";
                    document.getElementById('task-error-container').style.display = 'block';
                    document.getElementById('task-error-container').textContent = "Une erreur est survenue lors du chargement des détails de la tâche";
                });
        } else {
            console.error("Le modal n'a pas été trouvé dans le DOM");
        }
    }
}

// Fonction pour mettre à jour le statut d'une tâche
function updateTaskStatus(e) {
    const taskId = this.getAttribute('data-task-id');
    const newStatus = this.getAttribute('data-status');
    
    if (!taskId) {
        console.error("ID de tâche manquant");
        alert("Erreur: Impossible d'identifier la tâche");
        return;
    }
    
    // Vérifier si la fonction startProcessingEffect existe (intégration futuriste)
    const hasFuturisticEffects = typeof startProcessingEffect === 'function';
    
    // Afficher un spinner pendant le traitement
    const originalContent = this.innerHTML;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
    this.disabled = true;
    
    // Appliquer l'effet de traitement futuriste si disponible
    let processingPromise = Promise.resolve();
    if (hasFuturisticEffects) {
        processingPromise = startProcessingEffect('taskDetailsModal');
    }
    
    // Attendre que l'effet soit terminé avant de continuer
    processingPromise.then(() => {
        // Envoyer la requête pour mettre à jour le statut
        return fetch('ajax/update_tache_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${taskId}&statut=${newStatus}`
        });
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Afficher l'effet de succès si disponible
            if (hasFuturisticEffects && typeof showSuccessEffect === 'function') {
                showSuccessEffect(this);
            }
            
            // Afficher une notification de succès
            setTimeout(() => {
                alert(`Statut de la tâche mis à jour avec succès.`);
                
                // Fermer le modal
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('taskDetailsModal'));
                if (modalInstance) modalInstance.hide();
                
                // Recharger la page pour afficher les changements
                window.location.reload();
            }, hasFuturisticEffects ? 1000 : 0);
        } else {
            // Effet de secousse si disponible
            if (hasFuturisticEffects && typeof shakeModal === 'function') {
                shakeModal('taskDetailsModal');
            }
            alert(data.message || "Erreur lors de la mise à jour du statut de la tâche");
            // Rétablir le contenu original du bouton
            this.innerHTML = originalContent;
            this.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        // Effet de secousse si disponible
        if (hasFuturisticEffects && typeof shakeModal === 'function') {
            shakeModal('taskDetailsModal');
        }
        alert("Erreur lors de la communication avec le serveur. Veuillez réessayer.");
        // Rétablir le contenu original du bouton
        this.innerHTML = originalContent;
        this.disabled = false;
    });
} 