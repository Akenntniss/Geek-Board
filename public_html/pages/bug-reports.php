<?php
/**
 * Page d'administration des rapports de bugs
 * Permet de visualiser, trier et gérer les signalements des utilisateurs
 */

// Démarrer la mise en mémoire tampon
ob_start();

// Vérifier si l'utilisateur est connecté et a les droits d'admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Connexion à la base de données
try {
    $shop_pdo = getShopDBConnection();
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Traitement des actions
if (isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    // Vérification de l'ID
    if ($id <= 0) {
        $error = "ID de rapport invalide";
    } else {
        switch ($_POST['action']) {
            case 'update_status':
                // Mise à jour du statut
                $status = isset($_POST['statut']) ? $_POST['statut'] : '';
                $valid_statuses = ['nouveau', 'en_cours', 'resolu', 'invalide'];
                
                if (in_array($status, $valid_statuses)) {
                    $query = "UPDATE bug_reports SET status = :status WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':status' => $status, ':id' => $id]);
                    $success = "Statut mis à jour avec succès";
                } else {
                    $error = "Statut invalide";
                }
                break;
                
            case 'update_priority':
                // Mise à jour de la priorité
                $priority = isset($_POST['priorite']) ? $_POST['priorite'] : '';
                $valid_priorities = ['basse', 'moyenne', 'haute', 'critique'];
                
                if (in_array($priority, $valid_priorities)) {
                    $query = "UPDATE bug_reports SET priorite = :priorite WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':priorite' => $priority, ':id' => $id]);
                    $success = "Priorité mise à jour avec succès";
                } else {
                    $error = "Priorité invalide";
                }
                break;
                
            case 'add_note':
                // Ajout d'une note
                $note = isset($_POST['note']) ? trim($_POST['note']) : '';
                
                if (!empty($note)) {
                    $query = "UPDATE bug_reports SET notes_admin = :note WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->execute([':note' => $note, ':id' => $id]);
                    $success = "Note ajoutée avec succès";
                } else {
                    $error = "Note vide";
                }
                break;
                
            case 'delete':
                // Suppression d'un rapport
                $query = "DELETE FROM bug_reports WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([':id' => $id]);
                $success = "Rapport supprimé avec succès";
                break;
                
            default:
                $error = "Action non reconnue";
        }
    }
}

// Filtrage des rapports
$statut_filter = isset($_GET['statut']) ? $_GET['statut'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Construction de la requête avec filtres
$query = "SELECT * FROM bug_reports WHERE 1=1";
$params = [];

if (!empty($statut_filter)) {
    $query .= " AND status = :statut";
    $params[':statut'] = $statut_filter;
}

if (!empty($date_filter)) {
    $query .= " AND DATE(date_creation) = :date";
    $params[':date'] = $date_filter;
}

// Tri par défaut : les plus récents en premier
$query .= " ORDER BY date_creation DESC";

// Exécution de la requête
$stmt = $db->prepare($query);
$stmt->execute($params);
$bug_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inclusion du header
$page_title = "Gestion des rapports de bugs";
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1>Gestion des rapports de bugs</h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- Filtres -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtres</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="statut" class="form-label">Statut</label>
                    <select name="statut" id="statut" class="form-select">
                        <option value="">Tous</option>
                        <option value="nouveau" <?php echo $statut_filter === 'nouveau' ? 'selected' : ''; ?>>Nouveau</option>
                        <option value="en_cours" <?php echo $statut_filter === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        <option value="resolu" <?php echo $statut_filter === 'resolu' ? 'selected' : ''; ?>>Résolu</option>
                        <option value="invalide" <?php echo $statut_filter === 'invalide' ? 'selected' : ''; ?>>Invalide</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" name="date" id="date" class="form-control" value="<?php echo $date_filter; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrer</button>
                    <a href="bug-reports.php" class="btn btn-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Liste des rapports de bugs -->
    <div class="card">
        <div class="card-header">
            <h5>Liste des rapports (<?php echo count($bug_reports); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($bug_reports)): ?>
                <div class="alert alert-info">Aucun rapport de bug trouvé.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" style="table-layout: auto;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th class="full-width-column">Page</th>
                                <th class="text-center">Validé</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bug_reports as $report): ?>
                                <tr>
                                    <td><?php echo $report['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($report['date_creation'])); ?></td>
                                    <td>
                                        <div style="max-width: 300px; max-height: 100px; overflow: auto;">
                                            <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                                        </div>
                                    </td>
                                    <td style="max-width: none; word-wrap: break-word; word-break: break-all;" class="full-width-column">
                                        <div>
                                            <a href="<?php echo $report['page_url']; ?>" target="_blank" style="word-break: break-all;">
                                                <?php echo $report['page_url']; ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" 
                                            class="btn btn-outline-success btn-validation-bug" 
                                            data-bug-id="<?php echo $report['id']; ?>" 
                                            data-status="<?php echo $report['status']; ?>">
                                            <i class="fas fa-check <?php echo ($report['status'] === 'resolu') ? 'validation-active' : ''; ?>"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $report['id']; ?>">
                                            Détails
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Modal de détails -->
                                <div class="modal fade" id="detailModal<?php echo $report['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Rapport #<?php echo $report['id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <h6>Informations</h6>
                                                        <ul class="list-group">
                                                            <li class="list-group-item"><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($report['date_creation'])); ?></li>
                                                            <li class="list-group-item"><strong>Page:</strong> <?php echo $report['page_url']; ?></li>
                                                            <li class="list-group-item"><strong>User Agent:</strong> <?php echo $report['user_agent']; ?></li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>État</h6>
                                                        <form method="POST" class="mb-3">
                                                            <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                                            <input type="hidden" name="action" value="update_status">
                                                            <div class="input-group">
                                                                <select name="statut" class="form-select">
                                                                    <option value="nouveau" <?php echo $report['status'] === 'nouveau' ? 'selected' : ''; ?>>Nouveau</option>
                                                                    <option value="en_cours" <?php echo $report['status'] === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                                                    <option value="resolu" <?php echo $report['status'] === 'resolu' ? 'selected' : ''; ?>>Résolu</option>
                                                                    <option value="invalide" <?php echo $report['status'] === 'invalide' ? 'selected' : ''; ?>>Invalide</option>
                                                                </select>
                                                                <button type="submit" class="btn btn-outline-primary">Mettre à jour</button>
                                                            </div>
                                                        </form>
                                                        
                                                        <form method="POST">
                                                            <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                                            <input type="hidden" name="action" value="update_priority">
                                                            <div class="input-group">
                                                                <select name="priorite" class="form-select">
                                                                    <option value="basse" <?php echo $report['priorite'] === 'basse' ? 'selected' : ''; ?>>Basse</option>
                                                                    <option value="moyenne" <?php echo $report['priorite'] === 'moyenne' ? 'selected' : ''; ?>>Moyenne</option>
                                                                    <option value="haute" <?php echo $report['priorite'] === 'haute' ? 'selected' : ''; ?>>Haute</option>
                                                                    <option value="critique" <?php echo $report['priorite'] === 'critique' ? 'selected' : ''; ?>>Critique</option>
                                                                </select>
                                                                <button type="submit" class="btn btn-outline-primary">Mettre à jour</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <h6>Description du bug</h6>
                                                    <div class="border p-3 bg-light">
                                                        <?php echo nl2br(htmlspecialchars($report['description'])); ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <h6>Notes administratives</h6>
                                                    <form method="POST">
                                                        <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                                        <input type="hidden" name="action" value="add_note">
                                                        <textarea name="note" class="form-control mb-2" rows="3"><?php echo $report['notes_admin']; ?></textarea>
                                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce rapport ?');">
                                                    <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Vider le buffer
ob_end_flush();
include __DIR__ . '/../includes/footer.php'; 
?>

<style>
/* Styles pour le bouton de validation */
.btn-validation-bug {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-validation-bug .fa-check {
    font-size: 1.2rem;
    opacity: 0.4;
    transition: all 0.3s ease;
    z-index: 2;
}

.btn-validation-bug .validation-active {
    opacity: 1;
    color: #28a745 !important;
    font-weight: bold;
    text-shadow: 0 0 3px rgba(40, 167, 69, 0.3);
}

.btn-validation-bug:hover {
    background-color: #d4edda;
}

.btn-validation-bug:focus {
    box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
}

/* Animation de feedback */
@keyframes validationFeedback {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.validation-feedback {
    animation: validationFeedback 0.5s ease;
}

/* Fond pour l'état résolu */
.btn-validation-bug.resolved {
    background-color: #d4edda;
    border-color: #28a745;
}

/* Style pour afficher le lien complet */
.full-width-column {
    min-width: 250px;
    width: auto;
}

table td {
    white-space: normal !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les boutons résolus
    document.querySelectorAll('.btn-validation-bug').forEach(button => {
        if (button.getAttribute('data-status') === 'resolu') {
            button.classList.add('resolved');
        }
    });
    
    // Sélectionner tous les boutons de validation
    const validationButtons = document.querySelectorAll('.btn-validation-bug');
    
    // Ajouter un gestionnaire d'événements pour chaque bouton
    validationButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const bugId = this.getAttribute('data-bug-id');
            const currentStatus = this.getAttribute('data-status');
            const iconElement = this.querySelector('i.fas');
            
            // Déterminer le nouveau statut (alterne entre 'resolu' et 'nouveau')
            const newStatus = currentStatus === 'resolu' ? 'nouveau' : 'resolu';
            
            // Ajouter l'animation de feedback
            iconElement.classList.add('validation-feedback');
            
            // Utiliser un chemin relatif au document actuel
            const ajaxUrl = window.location.pathname.includes('/pages/') 
                ? '../ajax/update_bug_status.php' 
                : 'ajax/update_bug_status.php';
            
            // Envoyer la requête AJAX pour mettre à jour le statut
            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${bugId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mise à jour de l'interface utilisateur
                    if (newStatus === 'resolu') {
                        iconElement.classList.add('validation-active');
                        this.classList.add('resolved');
                        console.log("Ajout de la classe validation-active");
                    } else {
                        iconElement.classList.remove('validation-active');
                        this.classList.remove('resolved');
                        console.log("Suppression de la classe validation-active");
                    }
                    
                    // Mettre à jour l'attribut de statut du bouton
                    this.setAttribute('data-status', newStatus);
                    
                    // Afficher un message de confirmation
                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message);
                    }
                } else {
                    // Afficher un message d'erreur
                    if (typeof toastr !== 'undefined') {
                        toastr.error(data.message);
                    } else {
                        alert(data.message || 'Une erreur est survenue');
                    }
                }
                
                // Retirer l'animation après un délai
                setTimeout(() => {
                    iconElement.classList.remove('validation-feedback');
                }, 500);
            })
            .catch(error => {
                console.error('Erreur:', error);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Une erreur est survenue lors de la mise à jour du statut');
                } else {
                    alert('Une erreur est survenue lors de la mise à jour du statut');
                }
                
                // Retirer l'animation après un délai
                setTimeout(() => {
                    iconElement.classList.remove('validation-feedback');
                }, 500);
            });
        });
    });
});
</script> 