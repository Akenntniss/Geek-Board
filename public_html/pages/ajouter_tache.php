<?php
// Récupération des utilisateurs
try {
    $stmt = $pdo->query("SELECT id, full_name, role FROM users ORDER BY role DESC, full_name ASC");
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des utilisateurs: " . $e->getMessage(), "error");
    $utilisateurs = [];
}

// Traitement du formulaire d'ajout de tâche
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération et nettoyage des données
    $titre = cleanInput($_POST['titre']);
    $description = cleanInput($_POST['description']);
    $priorite = cleanInput($_POST['priorite']);
    $statut = cleanInput($_POST['statut']);
    $date_limite = cleanInput($_POST['date_limite']);
    $employe_id = isset($_POST['employe_id']) ? (int)$_POST['employe_id'] : null;
    
    // Validation des données
    $errors = [];
    
    if (empty($titre)) {
        $errors[] = "Le titre est obligatoire.";
    }
    
    if (empty($description)) {
        $errors[] = "La description est obligatoire.";
    }
    
    if (empty($priorite)) {
        $errors[] = "La priorité est obligatoire.";
    }
    
    if (empty($statut)) {
        $errors[] = "Le statut est obligatoire.";
    }
    
    // Si pas d'erreurs, insertion de la tâche
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO taches (titre, description, priorite, statut, date_limite, employe_id, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $titre, 
                $description, 
                $priorite, 
                $statut, 
                $date_limite ?: null, 
                $employe_id ?: null,
                $_SESSION['user_id']
            ]);
            
            set_message("Tâche ajoutée avec succès!", "success");
            redirect("accueil");
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'ajout de la tâche: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid p-0">
    <div class="row justify-content-center g-0">
        <div class="col-12 col-lg-10 col-xl-8 px-0" style="display: flex; flex-direction: column; align-items: center;">
            <div class="d-flex justify-content-between align-items-center mb-3 w-100 px-3">
                <h1 class="page-title">Nouvelle Tâche</h1>
                <a href="index.php?page=taches" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
            
            <div class="card mb-4" style="width: 96%; max-width: 900px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border-radius: 15px; margin: 0 auto;">
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?page=ajouter_tache" id="taskForm">
                        <input type="hidden" name="priorite" id="priorite" value="<?php echo isset($_POST['priorite']) ? htmlspecialchars($_POST['priorite']) : ''; ?>">
                        <input type="hidden" name="statut" id="statut" value="<?php echo isset($_POST['statut']) ? htmlspecialchars($_POST['statut']) : ''; ?>">
                        <input type="hidden" name="employe_id" id="employe_id" value="<?php echo isset($_POST['employe_id']) ? htmlspecialchars($_POST['employe_id']) : ''; ?>">
                        
                        <!-- Titre de la tâche -->
                        <div class="mb-4">
                            <label for="titre" class="form-label fw-bold">Titre de la tâche *</label>
                            <input type="text" class="form-control form-control-lg" id="titre" name="titre" required
                                value="<?php echo isset($_POST['titre']) ? htmlspecialchars($_POST['titre']) : ''; ?>"
                                placeholder="Saisissez un titre clair et concis">
                        </div>
                        
                        <!-- Description de la tâche -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-bold">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required
                                placeholder="Détaillez la tâche à accomplir..."><?php 
                                echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                            ?></textarea>
                        </div>
                        
                        <!-- Priorité avec boutons -->
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-bold d-block">Priorité *</label>
                                    <div class="priority-buttons d-flex flex-nowrap">
                                        <button type="button" class="btn btn-priority btn-outline-success flex-grow-1" data-value="basse">
                                            <i class="fas fa-angle-down me-1"></i><span class="d-none d-md-inline">Basse</span>
                                        </button>
                                        <button type="button" class="btn btn-priority btn-outline-primary flex-grow-1" data-value="moyenne">
                                            <i class="fas fa-equals me-1"></i><span class="d-none d-md-inline">Moyenne</span>
                                        </button>
                                        <button type="button" class="btn btn-priority btn-outline-warning flex-grow-1" data-value="haute">
                                            <i class="fas fa-angle-up me-1"></i><span class="d-none d-md-inline">Haute</span>
                                        </button>
                                        <button type="button" class="btn btn-priority btn-outline-danger flex-grow-1" data-value="urgente">
                                            <i class="fas fa-exclamation-triangle me-1"></i><span class="d-none d-md-inline">Urgente</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6 mt-3 mt-md-0">
                                    <!-- Statut avec boutons -->
                                    <label class="form-label fw-bold d-block">Statut *</label>
                                    <div class="status-buttons d-flex flex-nowrap">
                                        <button type="button" class="btn btn-status btn-outline-secondary flex-grow-1" data-value="a_faire">
                                            <i class="far fa-circle me-1"></i><span class="d-none d-md-inline">À faire</span>
                                        </button>
                                        <button type="button" class="btn btn-status btn-outline-info flex-grow-1" data-value="en_cours">
                                            <i class="fas fa-spinner me-1"></i><span class="d-none d-md-inline">En cours</span>
                                        </button>
                                        <button type="button" class="btn btn-status btn-outline-success flex-grow-1" data-value="termine">
                                            <i class="fas fa-check me-1"></i><span class="d-none d-md-inline">Terminé</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Date limite -->
                        <div class="mb-4">
                            <label for="date_limite" class="form-label fw-bold">Date limite</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="date" class="form-control form-control-lg" id="date_limite" name="date_limite"
                                    value="<?php echo isset($_POST['date_limite']) ? htmlspecialchars($_POST['date_limite']) : ''; ?>">
                            </div>
                        </div>
                        
                        <!-- Assigner la tâche -->
                        <div class="mb-4">
                            <label class="form-label fw-bold d-block">Assigner à</label>
                            <div class="user-selection">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <button type="button" class="btn btn-outline-secondary btn-lg user-btn" data-value="">
                                        <i class="fas fa-user-slash me-2"></i>Non assigné
                                    </button>
                                    
                                    <?php foreach ($utilisateurs as $index => $utilisateur): ?>
                                        <?php if ($index < 3): ?>
                                            <button type="button" class="btn btn-outline-primary btn-lg user-btn" 
                                                    data-value="<?php echo $utilisateur['id']; ?>">
                                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($utilisateur['full_name']); ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($utilisateurs) > 3): ?>
                                        <button type="button" class="btn btn-outline-secondary btn-lg" id="showAllUsersBtn">
                                            <i class="fas fa-users me-2"></i>Voir tous
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <div id="allUsersList" class="mt-3" style="display: none;">
                                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                                        <?php foreach ($utilisateurs as $utilisateur): ?>
                                            <div class="col">
                                                <button type="button" class="btn btn-outline-primary w-100 text-start user-btn py-2" 
                                                        data-value="<?php echo $utilisateur['id']; ?>">
                                                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($utilisateur['full_name']); ?>
                                                    <small class="d-block text-muted ms-4"><?php echo ucfirst($utilisateur['role']); ?></small>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-5">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Enregistrer la tâche
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles généraux */
body {
    background-color: #f8f9fa;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.form-control, .input-group-text {
    border-radius: 10px;
}

.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Styles pour les boutons de priorité et statut */
.priority-buttons .btn, .status-buttons .btn {
    border-width: 2px;
    transition: all 0.2s;
    margin: 0;
    border-radius: 0;
    padding: 0.5rem 0.25rem;
    font-size: 0.9rem;
}

.priority-buttons .btn:first-child, .status-buttons .btn:first-child {
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
}

.priority-buttons .btn:last-child, .status-buttons .btn:last-child {
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}

.user-btn {
    border-width: 2px;
    transition: all 0.2s;
}

.btn-priority.active, .btn-status.active, .user-btn.active {
    transform: translateY(-2px);
    font-weight: 500;
}

.btn-priority[data-value="basse"].active {
    background-color: #198754;
    color: white;
    border-color: #198754;
}

.btn-priority[data-value="moyenne"].active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

.btn-priority[data-value="haute"].active {
    background-color: #ffc107;
    color: #212529;
    border-color: #ffc107;
}

.btn-priority[data-value="urgente"].active {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
}

.btn-status[data-value="a_faire"].active {
    background-color: #6c757d;
    color: white;
    border-color: #6c757d;
}

.btn-status[data-value="en_cours"].active {
    background-color: #0dcaf0;
    color: white;
    border-color: #0dcaf0;
}

.btn-status[data-value="termine"].active {
    background-color: #198754;
    color: white;
    border-color: #198754;
}

/* Responsive designs */
@media (max-width: 992px) {
    .card {
        border-radius: 15px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .btn {
        font-size: 1rem;
        padding: 10px 15px;
    }
    
    .form-control, .form-select {
        font-size: 16px; /* Évite le zoom sur mobile */
        height: auto;
        padding: 12px 15px;
    }
    
    .priority-buttons .btn, .status-buttons .btn {
        padding: 0.75rem 0.5rem;
    }
}

@media (max-width: 768px) {
    .priority-buttons, .status-buttons {
        width: 100%;
    }
    
    .priority-buttons .btn, .status-buttons .btn {
        padding: 0.75rem 0.25rem;
    }
}

@media (max-width: 576px) {
    .page-title {
        font-size: 1.3rem;
    }
    
    .card-body {
        padding: 15px;
    }
    
    .user-selection .btn {
        width: 100%;
        margin-bottom: 8px;
        text-align: left;
    }
    
    .btn-lg {
        font-size: 1rem;
        padding: 10px 15px;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

#allUsersList {
    animation: fadeIn 0.3s ease-out;
}

/* Styles pour le mode nuit */
.dark-mode {
    background-color: #111827;
}

.dark-mode .page-title {
    color: #f8fafc;
}

.dark-mode .card {
    background-color: #1f2937;
    border-color: #374151;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.dark-mode .card-body {
    background-color: #1f2937;
    color: #f8fafc;
}

.dark-mode .form-control,
.dark-mode .form-select,
.dark-mode .input-group-text {
    background-color: #111827;
    border-color: #374151;
    color: #f8fafc;
}

.dark-mode .form-control:focus,
.dark-mode .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
}

.dark-mode .form-label {
    color: #f8fafc;
}

.dark-mode .text-muted {
    color: #94a3b8 !important;
}

.dark-mode .alert-warning {
    background-color: rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.3);
    color: #f8fafc;
}

.dark-mode .btn-priority,
.dark-mode .btn-status,
.dark-mode .user-btn {
    color: #f8fafc;
    border-color: #374151;
}

.dark-mode .btn-outline-primary {
    color: #60a5fa;
    border-color: #60a5fa;
}

.dark-mode .btn-outline-secondary {
    color: #94a3b8;
    border-color: #4b5563;
}

.dark-mode .btn-outline-success {
    color: #34d399;
    border-color: #34d399;
}

.dark-mode .btn-outline-warning {
    color: #fbbf24;
    border-color: #fbbf24;
}

.dark-mode .btn-outline-danger {
    color: #f87171;
    border-color: #f87171;
}

.dark-mode .btn-outline-info {
    color: #38bdf8;
    border-color: #38bdf8;
}

.dark-mode .btn-priority[data-value="basse"].active {
    background-color: #10b981;
    color: #f8fafc;
    border-color: #10b981;
}

.dark-mode .btn-priority[data-value="moyenne"].active {
    background-color: #3b82f6;
    color: #f8fafc;
    border-color: #3b82f6;
}

.dark-mode .btn-priority[data-value="haute"].active {
    background-color: #f59e0b;
    color: #111827;
    border-color: #f59e0b;
}

.dark-mode .btn-priority[data-value="urgente"].active {
    background-color: #ef4444;
    color: #f8fafc;
    border-color: #ef4444;
}

.dark-mode .btn-status[data-value="a_faire"].active {
    background-color: #4b5563;
    color: #f8fafc;
    border-color: #4b5563;
}

.dark-mode .btn-status[data-value="en_cours"].active {
    background-color: #0ea5e9;
    color: #f8fafc;
    border-color: #0ea5e9;
}

.dark-mode .btn-status[data-value="termine"].active {
    background-color: #10b981;
    color: #f8fafc;
    border-color: #10b981;
}

.dark-mode .btn-primary {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.dark-mode .btn-success {
    background-color: #10b981;
    border-color: #10b981;
}

.dark-mode .btn-lg {
    color: #f8fafc;
}

.dark-mode small.text-muted {
    color: #94a3b8 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Priorité
    const priorityButtons = document.querySelectorAll('.btn-priority');
    const priorityInput = document.getElementById('priorite');
    
    // Statut
    const statusButtons = document.querySelectorAll('.btn-status');
    const statusInput = document.getElementById('statut');
    
    // Utilisateurs
    const userButtons = document.querySelectorAll('.user-btn');
    const employeInput = document.getElementById('employe_id');
    const showAllUsersBtn = document.getElementById('showAllUsersBtn');
    const allUsersList = document.getElementById('allUsersList');
    
    // Activation des boutons de priorité
    priorityButtons.forEach(button => {
        // Préselectionner une valeur si elle existe déjà
        if (priorityInput.value === button.dataset.value) {
            button.classList.add('active');
        }
        
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            priorityButtons.forEach(btn => btn.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Mettre à jour la valeur du champ caché
            priorityInput.value = this.dataset.value;
            
            // Effet visuel de feedback
            this.classList.add('pulse');
            setTimeout(() => {
                this.classList.remove('pulse');
            }, 500);
        });
    });
    
    // Activation des boutons de statut
    statusButtons.forEach(button => {
        // Préselectionner une valeur si elle existe déjà
        if (statusInput.value === button.dataset.value) {
            button.classList.add('active');
        }
        
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            statusButtons.forEach(btn => btn.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Mettre à jour la valeur du champ caché
            statusInput.value = this.dataset.value;
            
            // Effet visuel de feedback
            this.classList.add('pulse');
            setTimeout(() => {
                this.classList.remove('pulse');
            }, 500);
        });
    });
    
    // Activation des boutons d'utilisateurs
    userButtons.forEach(button => {
        // Préselectionner une valeur si elle existe déjà
        if (employeInput.value === button.dataset.value) {
            button.classList.add('active');
        }
        
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            userButtons.forEach(btn => btn.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Mettre à jour la valeur du champ caché
            employeInput.value = this.dataset.value;
            
            // Effet visuel de feedback
            this.classList.add('pulse');
            setTimeout(() => {
                this.classList.remove('pulse');
            }, 500);
        });
    });
    
    // Afficher/masquer la liste complète des utilisateurs
    if (showAllUsersBtn) {
        showAllUsersBtn.addEventListener('click', function() {
            if (allUsersList.style.display === 'none') {
                allUsersList.style.display = 'block';
                this.innerHTML = '<i class="fas fa-users-slash me-2"></i>Masquer';
            } else {
                allUsersList.style.display = 'none';
                this.innerHTML = '<i class="fas fa-users me-2"></i>Voir tous';
            }
        });
    }
    
    // Validation du formulaire
    document.getElementById('taskForm').addEventListener('submit', function(e) {
        if (!priorityInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner une priorité pour la tâche');
            return;
        }
        
        if (!statusInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un statut pour la tâche');
            return;
        }
    });
    
    // Définir des valeurs par défaut si aucune n'est sélectionnée
    if (!priorityInput.value && priorityButtons.length > 0) {
        // Sélectionner 'moyenne' par défaut
        const defaultPriority = document.querySelector('.btn-priority[data-value="moyenne"]');
        if (defaultPriority) {
            defaultPriority.click();
        } else {
            priorityButtons[0].click();
        }
    }
    
    if (!statusInput.value && statusButtons.length > 0) {
        // Sélectionner 'a_faire' par défaut
        const defaultStatus = document.querySelector('.btn-status[data-value="a_faire"]');
        if (defaultStatus) {
            defaultStatus.click();
        } else {
            statusButtons[0].click();
        }
    }
    
    // Ajouter du feedback tactile pour les appareils mobiles
    const allButtons = document.querySelectorAll('.btn');
    
    function addTouchFeedback(buttons) {
        buttons.forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });
            
            button.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
    }
    
    addTouchFeedback(allButtons);
});
</script> 