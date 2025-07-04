<?php
// Démarrage des erreurs pour débug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtenir la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Gestion de l'ID du magasin
$current_shop_id = $_SESSION['shop_id'] ?? $_GET['shop_id'] ?? null;
if ($current_shop_id && !isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = $current_shop_id;
}

// Vérification de la connexion
if (!$shop_pdo) {
    echo "<div class='alert alert-danger'>Erreur de connexion à la base de données.</div>";
    exit;
}

// ========== PARAMÈTRES DE FILTRAGE ==========
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : '';
$statut_ids = isset($_GET['statut_ids']) ? cleanInput($_GET['statut_ids']) : '1,2,3,4,5';
$type_appareil = isset($_GET['type_appareil']) ? cleanInput($_GET['type_appareil']) : '';
$date_debut = isset($_GET['date_debut']) ? cleanInput($_GET['date_debut']) : '';
$date_fin = isset($_GET['date_fin']) ? cleanInput($_GET['date_fin']) : '';
$view_mode = isset($_GET['view']) ? cleanInput($_GET['view']) : 'cards';

// ========== COMPTAGE DES RÉPARATIONS ==========
function getRepairCount($shop_pdo, $status_ids) {
    try {
        $placeholders = implode(',', array_fill(0, count($status_ids), '?'));
        $stmt = $shop_pdo->prepare("
            SELECT COUNT(*) as total 
            FROM reparations r 
            JOIN statuts s ON r.statut = s.code 
            WHERE s.id IN ($placeholders)
        ");
        $stmt->execute($status_ids);
        return $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Erreur comptage: " . $e->getMessage());
        return 0;
    }
}

$counters = [
    'total_reparations' => getRepairCount($shop_pdo, [1,2,3,4,5]),
    'total_nouvelles' => getRepairCount($shop_pdo, [1,2,3]),
    'total_en_cours' => getRepairCount($shop_pdo, [4,5]),
    'total_en_attente' => getRepairCount($shop_pdo, [6,7,8]),
    'total_termines' => getRepairCount($shop_pdo, [9,10]),
    'total_archives' => getRepairCount($shop_pdo, [11,12,13])
];

// ========== CONSTRUCTION DE LA REQUÊTE ==========
$sql = "
    SELECT r.*, 
           c.nom as client_nom, 
           c.prenom as client_prenom, 
           c.telephone as client_telephone, 
           c.email as client_email,
           s.nom as statut_nom,
           s.couleur as statut_couleur,
           s.id as statut_id
    FROM reparations r
    LEFT JOIN clients c ON r.client_id = c.id
    LEFT JOIN statuts s ON r.statut = s.code
    WHERE 1=1
";

$params = [];

// Recherche textuelle
if (!empty($search)) {
    $sql .= " AND (
        c.nom LIKE ? OR 
        c.prenom LIKE ? OR 
        c.telephone LIKE ? OR 
        r.type_appareil LIKE ? OR 
        r.modele LIKE ? OR 
        r.id LIKE ? OR
        r.description_probleme LIKE ?
    )";
    $search_param = "%$search%";
    for ($i = 0; $i < 7; $i++) {
        $params[] = $search_param;
    }
} else {
    // Filtre par statut seulement si pas de recherche
    if (!empty($statut_ids)) {
        $ids = explode(',', $statut_ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql .= " AND s.id IN ($placeholders)";
        $params = array_merge($params, $ids);
    }
}

// Autres filtres
if (!empty($type_appareil)) {
    $sql .= " AND r.type_appareil = ?";
    $params[] = $type_appareil;
}

if (!empty($date_debut)) {
    $sql .= " AND r.date_reception >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $sql .= " AND r.date_reception <= ?";
    $params[] = $date_fin . ' 23:59:59';
}

$sql .= " ORDER BY r.date_reception DESC";

// ========== EXÉCUTION DE LA REQUÊTE ==========
try {
    $stmt = $shop_pdo->prepare($sql);
    $stmt->execute($params);
    $reparations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur lors de la récupération des réparations: " . $e->getMessage() . "</div>";
    error_log("Erreur SQL reparations.php: " . $e->getMessage());
    $reparations = [];
}

// ========== FONCTIONS D'AFFICHAGE ==========
function getStatusBadge($statut_nom, $statut_couleur) {
    $couleur = $statut_couleur ?: 'secondary';
    return "<span class='badge bg-$couleur'>" . htmlspecialchars($statut_nom) . "</span>";
}

function formatDate($date) {
    if (!$date) return '-';
    return date('d/m', strtotime($date));
}

function formatClient($nom, $prenom) {
    return trim(htmlspecialchars($nom . ' ' . $prenom)) ?: 'Client inconnu';
}

function formatPhone($telephone) {
    if (!$telephone) return '-';
    return htmlspecialchars($telephone);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réparations - GeekBoard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .page-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .search-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filters-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            border-radius: 25px;
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            background: white;
            color: #495057;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .filter-btn:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .filter-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .repair-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .repair-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .repair-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
        }
        
        .view-toggle {
            margin-left: auto;
            display: flex;
            gap: 5px;
        }
        
        .view-toggle .btn {
            border-radius: 8px;
        }
        
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-action {
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .filters-container {
                flex-direction: column;
            }
            
            .page-container {
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="page-container">
        
        <!-- Barre de recherche -->
        <div class="search-container">
            <form method="GET" action="index.php" class="row g-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="hidden" name="page" value="reparations">
                        <input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>">
                        <input 
                            type="text" 
                            class="form-control" 
                            name="search" 
                            placeholder="Rechercher par nom, téléphone, modèle, problème, ID..." 
                            value="<?= htmlspecialchars($search) ?>"
                        >
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                        <?php if ($search): ?>
                        <a href="index.php?page=reparations&view=<?= htmlspecialchars($view_mode) ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Effacer
                        </a>
                        <?php endif; ?>
                        <div class="view-toggle">
                            <a href="index.php?page=reparations&view=cards<?= $search ? '&search='.urlencode($search) : '' ?>" 
                               class="btn <?= $view_mode === 'cards' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                <i class="fas fa-th"></i>
                            </a>
                            <a href="index.php?page=reparations&view=table<?= $search ? '&search='.urlencode($search) : '' ?>" 
                               class="btn <?= $view_mode === 'table' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                <i class="fas fa-list"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Filtres par statut (seulement si pas de recherche) -->
        <?php if (!$search): ?>
        <div class="filters-container">
            <a href="index.php?page=reparations&statut_ids=1,2,3,4,5&view=<?= $view_mode ?>" 
               class="filter-btn <?= $statut_ids === '1,2,3,4,5' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i> Récentes 
                <span class="badge bg-secondary"><?= $counters['total_reparations'] ?></span>
            </a>
            <a href="index.php?page=reparations&statut_ids=1,2,3&view=<?= $view_mode ?>" 
               class="filter-btn <?= $statut_ids === '1,2,3' ? 'active' : '' ?>">
                <i class="fas fa-plus"></i> Nouvelles 
                <span class="badge bg-info"><?= $counters['total_nouvelles'] ?></span>
            </a>
            <a href="index.php?page=reparations&statut_ids=4,5&view=<?= $view_mode ?>" 
               class="filter-btn <?= $statut_ids === '4,5' ? 'active' : '' ?>">
                <i class="fas fa-tools"></i> En cours 
                <span class="badge bg-warning"><?= $counters['total_en_cours'] ?></span>
            </a>
            <a href="index.php?page=reparations&statut_ids=6,7,8&view=<?= $view_mode ?>" 
               class="filter-btn <?= $statut_ids === '6,7,8' ? 'active' : '' ?>">
                <i class="fas fa-pause"></i> En attente 
                <span class="badge bg-secondary"><?= $counters['total_en_attente'] ?></span>
            </a>
            <a href="index.php?page=reparations&statut_ids=9,10&view=<?= $view_mode ?>" 
               class="filter-btn <?= $statut_ids === '9,10' ? 'active' : '' ?>">
                <i class="fas fa-check"></i> Terminées 
                <span class="badge bg-success"><?= $counters['total_termines'] ?></span>
            </a>
        </div>
        <?php endif; ?>

        <!-- Résultats -->
        <?php if (empty($reparations)): ?>
            <div class="no-results">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h4>Aucune réparation trouvée</h4>
                <p>
                    <?= $search ? "Aucun résultat pour \"" . htmlspecialchars($search) . "\"" : "Aucune réparation dans cette catégorie" ?>
                </p>
            </div>
        <?php else: ?>
            
            <?php if ($view_mode === 'cards'): ?>
                <!-- Vue en cartes -->
                <div class="row">
                    <?php foreach ($reparations as $reparation): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="repair-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title mb-0">
                                        <strong>#<?= $reparation['id'] ?></strong>
                                    </h6>
                                    <?= getStatusBadge($reparation['statut_nom'], $reparation['statut_couleur']) ?>
                                </div>
                                
                                <div class="mb-2">
                                    <small class="text-muted">Client:</small><br>
                                    <strong><?= formatClient($reparation['client_nom'], $reparation['client_prenom']) ?></strong>
                                </div>
                                
                                <div class="mb-2">
                                    <small class="text-muted">Appareil:</small><br>
                                    <?= htmlspecialchars($reparation['type_appareil']) ?>
                                    <?= $reparation['modele'] ? ' - ' . htmlspecialchars($reparation['modele']) : '' ?>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted">Reçu le:</small><br>
                                    <?= formatDate($reparation['date_reception']) ?>
                                </div>
                                
                                <div class="action-buttons">
                                    <a href="index.php?page=voir_reparation&id=<?= $reparation['id'] ?>" 
                                       class="btn btn-primary btn-action">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                    <a href="index.php?page=modifier_reparation&id=<?= $reparation['id'] ?>" 
                                       class="btn btn-warning btn-action">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <button type="button" 
                                            class="btn btn-info btn-action"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#statusModal"
                                            data-repair-id="<?= $reparation['id'] ?>"
                                            data-current-status="<?= $reparation['statut_id'] ?>">
                                        <i class="fas fa-exchange-alt"></i> Statut
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
            <?php else: ?>
                <!-- Vue en tableau -->
                <div class="repair-table">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Téléphone</th>
                                <th>Appareil</th>
                                <th>Modèle</th>
                                <th>Statut</th>
                                <th>Reçu le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reparations as $reparation): ?>
                            <tr>
                                <td><strong>#<?= $reparation['id'] ?></strong></td>
                                <td><?= formatClient($reparation['client_nom'], $reparation['client_prenom']) ?></td>
                                <td><?= formatPhone($reparation['client_telephone']) ?></td>
                                <td><?= htmlspecialchars($reparation['type_appareil']) ?></td>
                                <td><?= htmlspecialchars($reparation['modele'] ?: '-') ?></td>
                                <td><?= getStatusBadge($reparation['statut_nom'], $reparation['statut_couleur']) ?></td>
                                <td><?= formatDate($reparation['date_reception']) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="index.php?page=voir_reparation&id=<?= $reparation['id'] ?>" 
                                           class="btn btn-primary btn-action">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="index.php?page=modifier_reparation&id=<?= $reparation['id'] ?>" 
                                           class="btn btn-warning btn-action">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-info btn-action"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#statusModal"
                                                data-repair-id="<?= $reparation['id'] ?>"
                                                data-current-status="<?= $reparation['statut_id'] ?>">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Informations de résultats -->
            <div class="mt-3 text-muted text-center">
                <small>
                    <?= count($reparations) ?> réparation(s) trouvée(s)
                    <?= $search ? ' pour "' . htmlspecialchars($search) . '"' : '' ?>
                </small>
            </div>
            
        <?php endif; ?>
    </div>

    <!-- Modal de changement de statut -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Changer le statut</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="statusForm">
                        <input type="hidden" id="modalRepairId" name="repair_id">
                        <div class="mb-3">
                            <label for="modalStatusSelect" class="form-label">Nouveau statut:</label>
                            <select class="form-select" id="modalStatusSelect" name="status_id" required>
                                <!-- Options chargées par JavaScript -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="modalSendSMS" name="send_sms">
                                <label class="form-check-label" for="modalSendSMS">
                                    Envoyer un SMS au client
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="saveStatusBtn">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Gestion du modal de statut
        document.addEventListener('DOMContentLoaded', function() {
            const statusModal = document.getElementById('statusModal');
            const modalRepairId = document.getElementById('modalRepairId');
            const modalStatusSelect = document.getElementById('modalStatusSelect');
            const saveStatusBtn = document.getElementById('saveStatusBtn');
            
            // Charger les statuts disponibles
            fetch('ajax/get_statuts.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modalStatusSelect.innerHTML = '';
                        data.statuts.forEach(statut => {
                            const option = document.createElement('option');
                            option.value = statut.id;
                            option.textContent = statut.nom;
                            modalStatusSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Erreur lors du chargement des statuts:', error));
            
            // Ouvrir le modal
            statusModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const repairId = button.getAttribute('data-repair-id');
                const currentStatus = button.getAttribute('data-current-status');
                
                modalRepairId.value = repairId;
                modalStatusSelect.value = currentStatus;
            });
            
            // Sauvegarder le statut
            saveStatusBtn.addEventListener('click', function() {
                const formData = new FormData();
                formData.append('repair_id', modalRepairId.value);
                formData.append('status_id', modalStatusSelect.value);
                formData.append('send_sms', document.getElementById('modalSendSMS').checked);
                
                fetch('ajax/update_repair_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Recharger la page pour voir les changements
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la mise à jour du statut');
                });
            });
        });
    </script>
</body>
</html> 