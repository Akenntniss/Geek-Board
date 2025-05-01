<?php
// Inclure les fonctions de notification
require_once 'includes/notification_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$user_id = $_SESSION['user_id'];

// Traitement des soumissions de formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_preferences') {
    
    // Récupérer les types de notification
    $stmt = $db->prepare("SELECT type_code FROM notification_types");
    $stmt->execute();
    $notification_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Pour chaque type de notification
    foreach ($notification_types as $type) {
        // Vérifier si le paramètre est défini
        $active = isset($_POST['active_' . $type]) ? 1 : 0;
        $email = isset($_POST['email_' . $type]) ? 1 : 0;
        $push = isset($_POST['push_' . $type]) ? 1 : 0;
        
        // Mettre à jour les préférences
        update_notification_preference($user_id, $type, $active, $email, $push);
    }
    
    set_message('success', 'Vos préférences de notification ont été mises à jour');
    header('Location: index.php?page=notification_preferences');
    exit;
}

// S'assurer que les préférences par défaut sont définies
set_default_notification_preferences($user_id);

// Récupérer les préférences actuelles
$preferences = get_notification_preferences($user_id);

// Récupérer les types de notification
$stmt = $db->prepare("SELECT * FROM notification_types ORDER BY importance DESC, description ASC");
$stmt->execute();
$notification_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les préférences par type pour un accès facile
$preferences_by_type = [];
foreach ($preferences as $pref) {
    $preferences_by_type[$pref['type_notification']] = $pref;
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Préférences de notification</h1>
                <a href="index.php?page=notifications" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Retour aux notifications
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Configurer vos notifications</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_preferences">
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Type de notification</th>
                                        <th class="text-center">Activer</th>
                                        <th class="text-center">Email</th>
                                        <th class="text-center">Notification Push</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notification_types as $type): ?>
                                        <?php 
                                        $pref = $preferences_by_type[$type['type_code']] ?? [
                                            'active' => 1, 
                                            'email_notification' => 0, 
                                            'push_notification' => 1
                                        ];
                                        
                                        $bg_class = '';
                                        if ($type['importance'] === 'critique') {
                                            $bg_class = 'table-danger';
                                        } elseif ($type['importance'] === 'haute') {
                                            $bg_class = 'table-warning';
                                        }
                                        ?>
                                        <tr class="<?php echo $bg_class; ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="notification-icon-sm me-2" style="background-color: <?php echo $type['color']; ?>">
                                                        <i class="<?php echo $type['icon']; ?>"></i>
                                                    </div>
                                                    <div>
                                                        <?php echo htmlspecialchars($type['description']); ?>
                                                        <?php if ($type['importance'] === 'critique'): ?>
                                                            <span class="badge bg-danger ms-2">Critique</span>
                                                        <?php elseif ($type['importance'] === 'haute'): ?>
                                                            <span class="badge bg-warning text-dark ms-2">Haute</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input 
                                                        class="form-check-input notification-toggle" 
                                                        type="checkbox" 
                                                        name="active_<?php echo $type['type_code']; ?>" 
                                                        id="active_<?php echo $type['type_code']; ?>"
                                                        data-type="<?php echo $type['type_code']; ?>"
                                                        <?php echo ($pref['active'] ? 'checked' : ''); ?>
                                                    >
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input 
                                                        class="form-check-input notification-method" 
                                                        type="checkbox" 
                                                        name="email_<?php echo $type['type_code']; ?>" 
                                                        id="email_<?php echo $type['type_code']; ?>"
                                                        data-type="<?php echo $type['type_code']; ?>"
                                                        <?php echo ($pref['email_notification'] ? 'checked' : ''); ?>
                                                        <?php echo (!$pref['active'] ? 'disabled' : ''); ?>
                                                    >
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input 
                                                        class="form-check-input notification-method" 
                                                        type="checkbox" 
                                                        name="push_<?php echo $type['type_code']; ?>" 
                                                        id="push_<?php echo $type['type_code']; ?>"
                                                        data-type="<?php echo $type['type_code']; ?>"
                                                        <?php echo ($pref['push_notification'] ? 'checked' : ''); ?>
                                                        <?php echo (!$pref['active'] ? 'disabled' : ''); ?>
                                                    >
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer les préférences
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Comment fonctionnent les notifications</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex">
                            <i class="fas fa-bell me-3 text-primary mt-1"></i>
                            <div>
                                <strong>Notifications dans l'application</strong>
                                <p class="mb-0 text-muted">Les notifications sont affichées dans le centre de notifications accessible depuis la barre supérieure.</p>
                            </div>
                        </li>
                        <li class="list-group-item d-flex">
                            <i class="fas fa-envelope me-3 text-success mt-1"></i>
                            <div>
                                <strong>Notifications par email</strong>
                                <p class="mb-0 text-muted">Recevez un email pour les notifications importantes selon vos préférences.</p>
                            </div>
                        </li>
                        <li class="list-group-item d-flex">
                            <i class="fas fa-mobile-alt me-3 text-warning mt-1"></i>
                            <div>
                                <strong>Notifications push</strong>
                                <p class="mb-0 text-muted">Recevez des alertes sur votre navigateur, même lorsque l'application n'est pas ouverte.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Niveaux d'importance</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex">
                            <span class="badge bg-danger me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 28px;">
                                <i class="fas fa-exclamation-triangle"></i>
                            </span>
                            <div>
                                <strong>Critique</strong>
                                <p class="mb-0 text-muted">Notifications urgentes nécessitant une attention immédiate.</p>
                            </div>
                        </li>
                        <li class="list-group-item d-flex">
                            <span class="badge bg-warning text-dark me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 28px;">
                                <i class="fas fa-exclamation"></i>
                            </span>
                            <div>
                                <strong>Haute</strong>
                                <p class="mb-0 text-muted">Notifications importantes qui méritent votre attention rapidement.</p>
                            </div>
                        </li>
                        <li class="list-group-item d-flex">
                            <span class="badge bg-primary me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 28px;">
                                <i class="fas fa-info"></i>
                            </span>
                            <div>
                                <strong>Normale</strong>
                                <p class="mb-0 text-muted">Notifications informatives sur les activités quotidiennes.</p>
                            </div>
                        </li>
                        <li class="list-group-item d-flex">
                            <span class="badge bg-secondary me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 28px;">
                                <i class="fas fa-comment"></i>
                            </span>
                            <div>
                                <strong>Basse</strong>
                                <p class="mb-0 text-muted">Informations optionnelles et mises à jour mineures.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.notification-icon-sm {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    color: white;
    font-size: 0.8rem;
}

.form-switch .form-check-input {
    width: 2.5em;
    height: 1.25em;
    cursor: pointer;
}

.form-switch .form-check-input:checked {
    background-color: #4361ee;
    border-color: #4361ee;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gérer l'activation/désactivation des notifications
    const toggles = document.querySelectorAll('.notification-toggle');
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const type = this.dataset.type;
            const isActive = this.checked;
            
            // Activer/désactiver les méthodes de notification
            const emailToggle = document.getElementById(`email_${type}`);
            const pushToggle = document.getElementById(`push_${type}`);
            
            if (emailToggle) {
                emailToggle.disabled = !isActive;
                if (!isActive) {
                    emailToggle.checked = false;
                }
            }
            
            if (pushToggle) {
                pushToggle.disabled = !isActive;
                if (!isActive) {
                    pushToggle.checked = false;
                }
            }
        });
    });
    
    // S'assurer qu'au moins une méthode est active si la notification est activée
    const methodToggles = document.querySelectorAll('.notification-method');
    methodToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const type = this.dataset.type;
            const activeToggle = document.getElementById(`active_${type}`);
            
            if (activeToggle && activeToggle.checked) {
                const emailToggle = document.getElementById(`email_${type}`);
                const pushToggle = document.getElementById(`push_${type}`);
                
                // Si les deux sont décochés, activer Push par défaut
                if (emailToggle && pushToggle && !emailToggle.checked && !pushToggle.checked) {
                    pushToggle.checked = true;
                }
            }
        });
    });
});
</script> 