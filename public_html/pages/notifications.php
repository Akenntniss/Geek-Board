<?php
// Inclure les fonctions de notification
require_once 'includes/notification_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$user_id = $_SESSION['user_id'];

// Traitement des actions
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'mark_read':
            if (isset($_GET['id'])) {
                mark_notification_as_read($_GET['id'], $user_id);
                set_message('success', 'Notification marquée comme lue');
            }
            header('Location: index.php?page=notifications');
            exit;
            break;
            
        case 'mark_all_read':
            $count = mark_all_notifications_as_read($user_id);
            set_message('success', $count . ' notification(s) marquée(s) comme lue(s)');
            header('Location: index.php?page=notifications');
            exit;
            break;
            
        case 'get_unread_count':
            // Pour les requêtes AJAX
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                $count = count_unread_notifications($user_id);
                header('Content-Type: application/json');
                echo json_encode(['count' => $count]);
                exit;
            }
            break;
    }
}

// Pagination
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Filtre de statut
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
if (!in_array($filter, ['all', 'new', 'read'])) {
    $filter = 'all';
}

// Récupérer les notifications
$notifications = get_user_notifications($user_id, $filter, $limit, $offset);

// Récupérer le nombre total pour la pagination
$total_notifications = 0;
switch ($filter) {
    case 'new':
        $total_notifications = count_unread_notifications($user_id);
        break;
    case 'read':
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'read'");
        $stmt->execute([$user_id]);
        $total_notifications = $stmt->fetchColumn();
        break;
    default:
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_notifications = $stmt->fetchColumn();
}

$total_pages = ceil($total_notifications / $limit);

// Récupérer quelques statistiques
$unread_count = count_unread_notifications($user_id);
$stats = get_notification_stats($user_id, 7); // Statistiques sur 7 jours
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Notifications</h1>
                <div>
                    <a href="index.php?page=notification_preferences" class="btn btn-outline-primary me-2">
                        <i class="fas fa-cog"></i> Préférences
                    </a>
                    <?php if ($unread_count > 0): ?>
                    <a href="index.php?page=notifications&action=mark_all_read" class="btn btn-primary">
                        <i class="fas fa-check-double"></i> Tout marquer comme lu
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="mb-4">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="index.php?page=notifications&filter=all">
                            Toutes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter === 'new' ? 'active' : ''; ?>" href="index.php?page=notifications&filter=new">
                            Non lues <span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter === 'read' ? 'active' : ''; ?>" href="index.php?page=notifications&filter=read">
                            Lues
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Liste des notifications -->
            <div class="card">
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-bell-slash fa-3x text-muted"></i>
                            </div>
                            <h5 class="text-muted">Aucune notification</h5>
                            <p class="text-muted">
                                <?php if ($filter === 'new'): ?>
                                    Vous n'avez pas de notifications non lues.
                                <?php elseif ($filter === 'read'): ?>
                                    Vous n'avez pas de notifications lues.
                                <?php else: ?>
                                    Vous n'avez pas de notifications.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                                <?php 
                                $icon = $notification['icon'] ?? 'fas fa-bell';
                                $color = $notification['color'] ?? '#4361ee';
                                $importance = $notification['importance'] ?? 'normale';
                                $is_new = $notification['status'] === 'new';
                                $time_ago = time_elapsed_string($notification['created_at']);
                                ?>
                                <div class="list-group-item notification-item <?php echo $is_new ? 'notification-unread' : ''; ?> p-3">
                                    <div class="d-flex">
                                        <div class="notification-icon me-3">
                                            <span class="notification-icon-circle" style="background-color: <?php echo $color; ?>">
                                                <i class="<?php echo $icon; ?>"></i>
                                            </span>
                                            <?php if ($is_new): ?>
                                                <span class="notification-dot"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="notification-content flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <?php if ($notification['is_important']): ?>
                                                        <span class="badge bg-danger me-2">Important</span>
                                                    <?php endif; ?>
                                                    <?php if ($importance === 'critique'): ?>
                                                        <span class="badge bg-danger me-2">Critique</span>
                                                    <?php elseif ($importance === 'haute'): ?>
                                                        <span class="badge bg-warning text-dark me-2">Haute</span>
                                                    <?php endif; ?>
                                                </div>
                                                <small class="text-muted"><?php echo $time_ago; ?></small>
                                            </div>
                                            <p class="mb-1 notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            <?php if ($notification['created_by']): ?>
                                                <small class="text-muted">Par: <?php echo htmlspecialchars($notification['created_by_name'] ?? 'Utilisateur'); ?></small>
                                            <?php endif; ?>
                                            <div class="mt-2">
                                                <?php if ($notification['action_url']): ?>
                                                    <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" class="btn btn-sm btn-primary me-2">
                                                        <i class="fas fa-eye"></i> Voir
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($is_new): ?>
                                                    <a href="index.php?page=notifications&action=mark_read&id=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-check"></i> Marquer comme lu
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-center py-3">
                                <nav aria-label="Navigation des notifications">
                                    <ul class="pagination">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="index.php?page=notifications&filter=<?php echo $filter; ?>&p=<?php echo $page - 1; ?>" aria-label="Précédent">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="index.php?page=notifications&filter=<?php echo $filter; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="index.php?page=notifications&filter=<?php echo $filter; ?>&p=<?php echo $page + 1; ?>" aria-label="Suivant">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistiques des notifications (7 derniers jours)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($stats)): ?>
                        <p class="text-center text-muted">Aucune donnée statistique disponible</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Total</th>
                                        <th>Non lues</th>
                                        <th>Lues</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats as $stat): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $type_name = '';
                                                switch ($stat['notification_type']) {
                                                    case 'reparation_start': $type_name = 'Démarrage réparation'; break;
                                                    case 'reparation_stop': $type_name = 'Arrêt réparation'; break;
                                                    case 'reparation_update': $type_name = 'Mise à jour réparation'; break;
                                                    case 'reparation_finish': $type_name = 'Réparation terminée'; break;
                                                    case 'new_device': $type_name = 'Nouvel appareil'; break;
                                                    case 'new_order': $type_name = 'Nouvelle commande'; break;
                                                    case 'stock_low': $type_name = 'Stock bas'; break;
                                                    case 'message_received': $type_name = 'Nouveau message'; break;
                                                    case 'task_assigned': $type_name = 'Tâche assignée'; break;
                                                    case 'task_completed': $type_name = 'Tâche terminée'; break;
                                                    case 'system_alert': $type_name = 'Alerte système'; break;
                                                    case 'appointment': $type_name = 'Rendez-vous'; break;
                                                    default: $type_name = $stat['notification_type']; break;
                                                }
                                                echo htmlspecialchars($type_name);
                                                ?>
                                            </td>
                                            <td><?php echo $stat['total']; ?></td>
                                            <td><?php echo $stat['unread']; ?></td>
                                            <td><?php echo $stat['read']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.notification-item {
    transition: background-color 0.3s ease;
}

.notification-unread {
    background-color: rgba(67, 97, 238, 0.05);
    position: relative;
}

.notification-icon-circle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: white;
    position: relative;
}

.notification-dot {
    position: absolute;
    top: 0;
    right: 0;
    width: 10px;
    height: 10px;
    background-color: #e11d48;
    border: 2px solid white;
    border-radius: 50%;
}

.notification-message {
    line-height: 1.5;
}

@media (max-width: 576px) {
    .notification-icon-circle {
        width: 32px;
        height: 32px;
        font-size: 0.9rem;
    }
}
</style>

<script>
// Mettre à jour le compteur de notifications non lues
function updateNotificationCount() {
    fetch('index.php?page=notifications&action=get_unread_count', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const badge = document.getElementById('notificationCountBadge');
        if (badge) {
            badge.textContent = data.count;
            if (data.count > 0) {
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        }
    })
    .catch(error => console.error('Erreur:', error));
}

// Mettre à jour le compteur toutes les 60 secondes
setInterval(updateNotificationCount, 60000);

// Marquer comme lu lors d'un clic sur une notification non lue
document.addEventListener('DOMContentLoaded', function() {
    const unreadItems = document.querySelectorAll('.notification-unread');
    unreadItems.forEach(item => {
        const markAsReadBtn = item.querySelector('.btn-outline-secondary');
        if (markAsReadBtn) {
            markAsReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('href');
                fetch(url)
                    .then(() => {
                        item.classList.remove('notification-unread');
                        item.querySelector('.notification-dot')?.remove();
                        this.remove();
                        updateNotificationCount();
                    })
                    .catch(error => console.error('Erreur:', error));
            });
        }
    });
});
</script>

<?php
// Section administration pour les administrateurs
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    // Traitement de la génération de notifications de test
    if (isset($_GET['action']) && $_GET['action'] === 'generate_test') {
        $test_type = isset($_GET['type']) ? $_GET['type'] : 'all';
        $success_count = 0;
        
        // Types de notifications à générer
        $types_to_generate = [];
        
        if ($test_type === 'all' || $test_type === 'reparation') {
            $types_to_generate[] = 'reparation_start';
            $types_to_generate[] = 'reparation_update';
            $types_to_generate[] = 'reparation_finish';
        }
        
        if ($test_type === 'all' || $test_type === 'task') {
            $types_to_generate[] = 'task_assigned';
            $types_to_generate[] = 'task_completed';
        }
        
        if ($test_type === 'all' || $test_type === 'order') {
            $types_to_generate[] = 'new_order';
            $types_to_generate[] = 'stock_low';
        }
        
        if ($test_type === 'all' || $test_type === 'system') {
            $types_to_generate[] = 'system_alert';
            $types_to_generate[] = 'appointment';
        }
        
        // Générer les notifications
        foreach ($types_to_generate as $type) {
            $message = '';
            $related_id = rand(1, 100);
            $related_type = '';
            $action_url = '';
            $is_important = in_array($type, ['reparation_finish', 'new_order', 'stock_low', 'system_alert']);
            
            switch ($type) {
                case 'reparation_start':
                    $message = 'Test: Une nouvelle réparation a été démarrée';
                    $related_type = 'reparation';
                    $action_url = 'index.php?page=reparations&id=' . $related_id;
                    break;
                
                case 'reparation_update':
                    $message = 'Test: La réparation #' . $related_id . ' a été mise à jour';
                    $related_type = 'reparation';
                    $action_url = 'index.php?page=reparations&id=' . $related_id;
                    break;
                
                case 'reparation_finish':
                    $message = 'Test: La réparation #' . $related_id . ' est terminée';
                    $related_type = 'reparation';
                    $action_url = 'index.php?page=reparations&id=' . $related_id;
                    break;
                
                case 'task_assigned':
                    $message = 'Test: Une nouvelle tâche vous a été assignée';
                    $related_type = 'tache';
                    $action_url = 'index.php?page=taches&id=' . $related_id;
                    break;
                
                case 'task_completed':
                    $message = 'Test: La tâche #' . $related_id . ' a été terminée';
                    $related_type = 'tache';
                    $action_url = 'index.php?page=taches&id=' . $related_id;
                    break;
                
                case 'new_order':
                    $message = 'Test: Une nouvelle commande #' . $related_id . ' a été créée';
                    $related_type = 'commande';
                    $action_url = 'index.php?page=commande_piece&id=' . $related_id;
                    break;
                
                case 'stock_low':
                    $message = 'Test: Le stock d\'un produit est faible';
                    $related_type = 'produit';
                    $action_url = 'index.php?page=inventaire';
                    break;
                
                case 'system_alert':
                    $message = 'Test: Alerte système importante';
                    $related_type = 'system';
                    break;
                
                case 'appointment':
                    $message = 'Test: Nouveau rendez-vous le ' . date('d/m/Y à H:i', strtotime('+2 days'));
                    $related_type = 'appointment';
                    $action_url = 'index.php?page=agenda';
                    break;
            }
            
            $result = create_notification(
                $_SESSION['user_id'],
                $type,
                $message,
                $related_id,
                $related_type,
                $action_url,
                $is_important,
                false,
                $_SESSION['user_id']
            );
            
            if ($result) {
                $success_count++;
            }
        }
        
        set_message('success', $success_count . ' notification(s) de test générée(s)');
        header('Location: index.php?page=notifications');
        exit;
    }
    
    // Afficher le panneau d'administration des notifications
    echo '<div class="row mt-4">';
    echo '<div class="col-12">';
    echo '<div class="card">';
    echo '<div class="card-header bg-dark text-white">';
    echo '<h5 class="card-title mb-0">Administration des notifications</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    
    // Boutons pour générer des notifications de test
    echo '<div class="mb-3">';
    echo '<h6>Générer des notifications de test</h6>';
    echo '<div class="btn-group">';
    echo '<a href="index.php?page=notifications&action=generate_test&type=all" class="btn btn-outline-primary">Tous les types</a>';
    echo '<a href="index.php?page=notifications&action=generate_test&type=reparation" class="btn btn-outline-primary">Réparations</a>';
    echo '<a href="index.php?page=notifications&action=generate_test&type=task" class="btn btn-outline-primary">Tâches</a>';
    echo '<a href="index.php?page=notifications&action=generate_test&type=order" class="btn btn-outline-primary">Commandes</a>';
    echo '<a href="index.php?page=notifications&action=generate_test&type=system" class="btn btn-outline-primary">Système</a>';
    echo '</div>';
    echo '</div>';
    
    // Statistiques globales
    echo '<h6>Statistiques globales</h6>';
    
    // Récupérer les statistiques globales
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as unread,
            SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read
        FROM notifications
    ");
    $stmt->execute();
    $global_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer le nombre d'utilisateurs avec des notifications
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT user_id) 
        FROM notifications
    ");
    $stmt->execute();
    $users_with_notifications = $stmt->fetchColumn();
    
    echo '<div class="row">';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h3 class="m-0">' . number_format($global_stats['total']) . '</h3>';
    echo '<p class="text-muted mb-0">Notifications totales</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h3 class="m-0">' . number_format($global_stats['unread']) . '</h3>';
    echo '<p class="text-muted mb-0">Non lues</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h3 class="m-0">' . number_format($global_stats['read']) . '</h3>';
    echo '<p class="text-muted mb-0">Lues</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="col-md-3">';
    echo '<div class="card bg-light">';
    echo '<div class="card-body text-center">';
    echo '<h3 class="m-0">' . number_format($users_with_notifications) . '</h3>';
    echo '<p class="text-muted mb-0">Utilisateurs</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // Fin de la row
    
    // Bouton de nettoyage des notifications anciennes
    echo '<div class="mt-3">';
    echo '<a href="index.php?page=notifications&action=clean_old" class="btn btn-danger">';
    echo '<i class="fas fa-trash me-2"></i>Nettoyer les anciennes notifications (+ 30 jours)';
    echo '</a>';
    echo '</div>';
    
    echo '</div>'; // Fin du card-body
    echo '</div>'; // Fin de la card
    echo '</div>'; // Fin de la col
    echo '</div>'; // Fin de la row
}
?> 