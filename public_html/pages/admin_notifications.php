<?php
/**
 * Page d'administration des notifications PWA
 * Permet de créer, gérer et programmer des notifications
 */

// Vérifier si l'utilisateur est connecté et a les droits d'administration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';

$shop_pdo = getShopDBConnection();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/PushNotifications.php';

// Initialiser la connexion à la base de données
try {
    $shop_pdo = getShopDBConnection();
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Initialiser la classe de notifications push
$pushNotifications = new PushNotifications($pdo);

// Traitement des actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'send_instant':
                // Envoyer une notification immédiate
                if (isset($_POST['title'], $_POST['message'])) {
                    $options = [
                        'type' => $_POST['notification_type'] ?? 'general',
                        'url' => $_POST['action_url'] ?? '/',
                        'important' => isset($_POST['is_important']) ? true : false
                    ];
                    
                    if ($_POST['recipient_type'] === 'user' && !empty($_POST['user_id'])) {
                        $result = $pushNotifications->sendToUser(
                            $_POST['user_id'],
                            $_POST['title'],
                            $_POST['message'],
                            $options
                        );
                    } else {
                        if (!empty($_POST['role'])) {
                            $options['role'] = $_POST['role'];
                        }
                        $result = $pushNotifications->sendToAll(
                            $_POST['title'],
                            $_POST['message'],
                            $options
                        );
                    }
                    
                    if ($result['success']) {
                        $message = 'Notification envoyée avec succès';
                        $messageType = 'success';
                    } else {
                        $message = 'Erreur lors de l\'envoi de la notification: ' . $result['message'];
                        $messageType = 'danger';
                    }
                }
                break;
                
            case 'schedule':
                // Programmer une notification
                if (isset($_POST['title'], $_POST['message'], $_POST['scheduled_date'], $_POST['scheduled_time'])) {
                    $scheduledDateTime = $_POST['scheduled_date'] . ' ' . $_POST['scheduled_time'] . ':00';
                    
                    $options = [
                        'type' => $_POST['notification_type'] ?? 'general',
                        'url' => $_POST['action_url'] ?? '/',
                        'important' => isset($_POST['is_important']) ? true : false,
                        'created_by' => $_SESSION['user_id']
                    ];
                    
                    if ($_POST['recipient_type'] === 'user' && !empty($_POST['user_id'])) {
                        $options['user_id'] = $_POST['user_id'];
                        $options['is_broadcast'] = false;
                    } else {
                        $options['is_broadcast'] = true;
                        if (!empty($_POST['role'])) {
                            $options['role'] = $_POST['role'];
                        }
                    }
                    
                    $result = $pushNotifications->scheduleNotification(
                        $_POST['title'],
                        $_POST['message'],
                        $scheduledDateTime,
                        $options
                    );
                    
                    if ($result['success']) {
                        $message = 'Notification programmée avec succès pour le ' . date('d/m/Y à H:i', strtotime($scheduledDateTime));
                        $messageType = 'success';
                    } else {
                        $message = 'Erreur lors de la programmation de la notification: ' . $result['message'];
                        $messageType = 'danger';
                    }
                }
                break;
                
            case 'cancel':
                // Annuler une notification programmée
                if (isset($_POST['notification_id'])) {
                    $result = $pushNotifications->cancelScheduledNotification($_POST['notification_id']);
                    
                    if ($result['success']) {
                        $message = 'Notification annulée avec succès';
                        $messageType = 'success';
                    } else {
                        $message = 'Erreur lors de l\'annulation de la notification: ' . $result['message'];
                        $messageType = 'danger';
                    }
                }
                break;
                
            case 'send_now':
                // Envoyer immédiatement une notification programmée
                if (isset($_POST['notification_id'])) {
                    $result = $pushNotifications->sendScheduledNotification($_POST['notification_id']);
                    
                    if ($result['success']) {
                        $message = 'Notification envoyée avec succès';
                        $messageType = 'success';
                    } else {
                        $message = 'Erreur lors de l\'envoi de la notification: ' . $result['message'];
                        $messageType = 'danger';
                    }
                }
                break;
        }
    }
}

// Récupérer les notifications programmées
$scheduledNotifications = $pushNotifications->getScheduledNotifications();

// Récupérer les types de notifications
$stmt = $shop_pdo->query("SELECT * FROM notification_types ORDER BY description");
$notificationTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la liste des utilisateurs
$stmt = $shop_pdo->query("SELECT id, full_name, email, role FROM users ORDER BY full_name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les rôles disponibles
$stmt = $shop_pdo->query("SELECT DISTINCT role FROM users WHERE role IS NOT NULL ORDER BY role");
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-bell me-2"></i>Gestion des Notifications PWA</h1>
            </div>
            <p class="text-muted">Gérez les notifications pour l'application PWA, envoyez des messages instantanés ou programmez des rappels.</p>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card border-primary mb-4">
                <div class="card-header bg-primary text-white">
                    <ul class="nav nav-tabs card-header-tabs" id="notificationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active text-dark" id="instant-tab" data-bs-toggle="tab" data-bs-target="#instant" type="button" role="tab" aria-controls="instant" aria-selected="true">
                                <i class="fas fa-paper-plane me-2"></i>Notification Immédiate
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-dark" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab" aria-controls="schedule" aria-selected="false">
                                <i class="fas fa-clock me-2"></i>Programmer une Notification
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-dark" id="scheduled-tab" data-bs-toggle="tab" data-bs-target="#scheduled" type="button" role="tab" aria-controls="scheduled" aria-selected="false">
                                <i class="fas fa-calendar-alt me-2"></i>Notifications Programmées
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="notificationTabsContent">
                        <!-- Onglet Notification Immédiate -->
                        <div class="tab-pane fade show active" id="instant" role="tabpanel" aria-labelledby="instant-tab">
                            <form method="post" action="">
                                <input type="hidden" name="action" value="send_instant">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="title" class="form-label">Titre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="notification_type" class="form-label">Type de notification</label>
                                        <select class="form-select" id="notification_type" name="notification_type">
                                            <?php foreach ($notificationTypes as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type['type_code']); ?>"><?php echo htmlspecialchars($type['description']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="action_url" class="form-label">URL d'action (facultatif)</label>
                                        <input type="text" class="form-control" id="action_url" name="action_url" placeholder="ex: /index.php?page=detail&id=123">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="is_important" name="is_important">
                                            <label class="form-check-label" for="is_important">
                                                Notification importante
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Destinataires</label>
                                    <div class="form-check">
                                        <input class="form-check-input recipient-type" type="radio" name="recipient_type" id="all_users" value="all" checked>
                                        <label class="form-check-label" for="all_users">
                                            Tous les utilisateurs
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input recipient-type" type="radio" name="recipient_type" id="by_role" value="role">
                                        <label class="form-check-label" for="by_role">
                                            Par rôle
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input recipient-type" type="radio" name="recipient_type" id="specific_user" value="user">
                                        <label class="form-check-label" for="specific_user">
                                            Utilisateur spécifique
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="role_selector" class="mb-3" style="display: none;">
                                    <label for="role" class="form-label">Sélectionner un rôle</label>
                                    <select class="form-select" id="role" name="role">
                                        <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars(ucfirst($role)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div id="user_selector" class="mb-3" style="display: none;">
                                    <label for="user_id" class="form-label">Sélectionner un utilisateur</label>
                                    <select class="form-select" id="user_id" name="user_id">
                                        <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name'] . ' (' . $user['email'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Envoyer la notification
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Onglet Programmer une Notification -->
                        <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                            <form method="post" action="">
                                <input type="hidden" name="action" value="schedule">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="schedule_title" class="form-label">Titre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="schedule_title" name="title" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="schedule_notification_type" class="form-label">Type de notification</label>
                                        <select class="form-select" id="schedule_notification_type" name="notification_type">
                                            <?php foreach ($notificationTypes as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type['type_code']); ?>"><?php echo htmlspecialchars($type['description']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="schedule_message" class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="schedule_message" name="message" rows="3" required></textarea>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="schedule_action_url" class="form-label">URL d'action (facultatif)</label>
                                        <input type="text" class="form-control" id="schedule_action_url" name="action_url" placeholder="ex: /index.php?page=detail&id=123">
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="schedule_is_important" name="is_important">
                                            <label class="form-check-label" for="schedule_is_important">
                                                Notification importante
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="scheduled_date" class="form-label">Date programmée <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="scheduled_date" name="scheduled_date" required min="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="scheduled_time" class="form-label">Heure programmée <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="scheduled_time" name="scheduled_time" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Destinataires</label>
                                    <div class="form-check">
                                        <input class="form-check-input schedule-recipient-type" type="radio" name="recipient_type" id="schedule_all_users" value="all" checked>
                                        <label class="form-check-label" for="schedule_all_users">
                                            Tous les utilisateurs
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input schedule-recipient-type" type="radio" name="recipient_type" id="schedule_by_role" value="role">
                                        <label class="form-check-label" for="schedule_by_role">
                                            Par rôle
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input schedule-recipient-type" type="radio" name="recipient_type" id="schedule_specific_user" value="user">
                                        <label class="form-check-label" for="schedule_specific_user">
                                            Utilisateur spécifique
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="schedule_role_selector" class="mb-3" style="display: none;">
                                    <label for="schedule_role" class="form-label">Sélectionner un rôle</label>
                                    <select class="form-select" id="schedule_role" name="role">
                                        <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars(ucfirst($role)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div id="schedule_user_selector" class="mb-3" style="display: none;">
                                    <label for="schedule_user_id" class="form-label">Sélectionner un utilisateur</label>
                                    <select class="form-select" id="schedule_user_id" name="user_id">
                                        <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name'] . ' (' . $user['email'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-clock me-2"></i>Programmer la notification
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Onglet Notifications Programmées -->
                        <div class="tab-pane fade" id="scheduled" role="tabpanel" aria-labelledby="scheduled-tab">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Titre</th>
                                            <th>Message</th>
                                            <th>Date programmée</th>
                                            <th>Statut</th>
                                            <th>Destinataire</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($scheduledNotifications['success']) && $scheduledNotifications['success'] && !empty($scheduledNotifications['notifications'])): ?>
                                            <?php foreach ($scheduledNotifications['notifications'] as $notification): ?>
                                            <tr class="<?php echo ($notification['status'] === 'pending') ? 'table-info' : (($notification['status'] === 'sent') ? 'table-success' : (($notification['status'] === 'cancelled') ? 'table-warning' : 'table-danger')); ?>">
                                                <td><?php echo $notification['id']; ?></td>
                                                <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($notification['message'], 0, 50)) . (strlen($notification['message']) > 50 ? '...' : ''); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($notification['scheduled_datetime'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $statusLabels = [
                                                        'pending' => '<span class="badge bg-info">En attente</span>',
                                                        'sent' => '<span class="badge bg-success">Envoyée</span>',
                                                        'failed' => '<span class="badge bg-danger">Échec</span>',
                                                        'cancelled' => '<span class="badge bg-warning text-dark">Annulée</span>'
                                                    ];
                                                    echo $statusLabels[$notification['status']] ?? $notification['status'];
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($notification['is_broadcast']): ?>
                                                        <span class="badge bg-primary">Tous les utilisateurs</span>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($notification['target_user_name'] ?? 'Utilisateur inconnu'); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($notification['status'] === 'pending'): ?>
                                                    <form method="post" action="" class="d-inline me-1">
                                                        <input type="hidden" name="action" value="send_now">
                                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Envoyer maintenant">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </button>
                                                    </form>
                                                    <form method="post" action="" class="d-inline">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Annuler" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette notification ?');">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Aucune notification programmée trouvée</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des destinataires pour l'onglet notification immédiate
    const recipientTypeRadios = document.querySelectorAll('.recipient-type');
    const roleSelector = document.getElementById('role_selector');
    const userSelector = document.getElementById('user_selector');
    
    recipientTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'role') {
                roleSelector.style.display = 'block';
                userSelector.style.display = 'none';
            } else if (this.value === 'user') {
                roleSelector.style.display = 'none';
                userSelector.style.display = 'block';
            } else {
                roleSelector.style.display = 'none';
                userSelector.style.display = 'none';
            }
        });
    });
    
    // Gestion des destinataires pour l'onglet notification programmée
    const scheduleRecipientTypeRadios = document.querySelectorAll('.schedule-recipient-type');
    const scheduleRoleSelector = document.getElementById('schedule_role_selector');
    const scheduleUserSelector = document.getElementById('schedule_user_selector');
    
    scheduleRecipientTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'role') {
                scheduleRoleSelector.style.display = 'block';
                scheduleUserSelector.style.display = 'none';
            } else if (this.value === 'user') {
                scheduleRoleSelector.style.display = 'none';
                scheduleUserSelector.style.display = 'block';
            } else {
                scheduleRoleSelector.style.display = 'none';
                scheduleUserSelector.style.display = 'none';
            }
        });
    });
    
    // Définir la date minimale pour la date programmée
    const scheduledDate = document.getElementById('scheduled_date');
    const today = new Date().toISOString().split('T')[0];
    scheduledDate.min = today;
    
    // Par défaut, définir l'heure à l'heure actuelle + 1 heure
    const scheduledTime = document.getElementById('scheduled_time');
    const now = new Date();
    now.setHours(now.getHours() + 1);
    scheduledTime.value = now.toTimeString().slice(0, 5);
});
</script>