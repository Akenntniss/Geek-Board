<?php
// Page d'accueil mobile moderne
// Inclure les fichiers nécessaires
require_once 'includes/header.php';

// Récupérer les statistiques
$stats = [
    'reparations' => [
        'count' => 0,
        'change' => 0,
        'icon' => 'tools'
    ],
    'clients' => [
        'count' => 0,
        'change' => 0,
        'icon' => 'users'
    ]
];

// Récupérer le nombre de réparations
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reparations");
    $stmt->execute();
    $stats['reparations']['count'] = $stmt->fetch()['total'];
    
    // Réparations récentes (dernière semaine)
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent FROM reparations WHERE date_reception >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute();
    $recent_repairs = $stmt->fetch()['recent'];
    
    // Calculer le changement en pourcentage
    if ($stats['reparations']['count'] > 0) {
        $stats['reparations']['change'] = round(($recent_repairs / $stats['reparations']['count']) * 100);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des statistiques de réparations: " . $e->getMessage());
}

// Récupérer le nombre de clients
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM clients");
    $stmt->execute();
    $stats['clients']['count'] = $stmt->fetch()['total'];
    
    // Clients récents (dernière semaine)
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent FROM clients WHERE date_creation >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute();
    $recent_clients = $stmt->fetch()['recent'];
    
    // Calculer le changement en pourcentage
    if ($stats['clients']['count'] > 0) {
        $stats['clients']['change'] = round(($recent_clients / $stats['clients']['count']) * 100);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des statistiques de clients: " . $e->getMessage());
}

// Récupérer les réparations récentes
$recent_repairs = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.id, r.type_appareil, r.marque, r.modele, r.statut, 
               c.nom as client_nom, c.prenom as client_prenom,
               r.date_reception
        FROM reparations r 
        LEFT JOIN clients c ON r.client_id = c.id 
        ORDER BY r.date_reception DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $recent_repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des réparations récentes: " . $e->getMessage());
}

// Récupérer les tâches
$tasks = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, titre, description, priorite, date_echeance
        FROM taches
        WHERE statut = 'en_cours'
        ORDER BY 
            CASE priorite
                WHEN 'haute' THEN 1
                WHEN 'moyenne' THEN 2
                WHEN 'basse' THEN 3
                ELSE 4
            END,
            date_echeance ASC
        LIMIT 3
    ");
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des tâches: " . $e->getMessage());
}
?>

<div class="mobile-container page-transition">
    <!-- Barre de recherche -->
    <div class="search-bar touchable">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Rechercher..." id="mobileSearch">
    </div>

    <!-- Navigation rapide -->
    <div class="quick-nav-grid">
        <a href="index.php?page=reparations" class="quick-nav-card touchable">
            <div class="quick-nav-icon">
                <i class="fas fa-tools"></i>
            </div>
            <h3 class="quick-nav-title">Réparations</h3>
        </a>
        <a href="index.php?page=clients" class="quick-nav-card touchable">
            <div class="quick-nav-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3 class="quick-nav-title">Clients</h3>
        </a>
        <a href="index.php?page=inventaire" class="quick-nav-card touchable">
            <div class="quick-nav-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <h3 class="quick-nav-title">Inventaire</h3>
        </a>
        <a href="index.php?page=taches" class="quick-nav-card touchable">
            <div class="quick-nav-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <h3 class="quick-nav-title">Tâches</h3>
        </a>
    </div>

    <!-- Statistiques -->
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-chart-pie"></i>Tableau de bord</h2>
        <p class="section-subtitle">Aperçu de l'activité</p>
    </div>

    <div class="stat-grid">
        <div class="stat-card touchable">
            <div class="stat-icon bg-blue">
                <i class="fas fa-tools"></i>
            </div>
            <div class="stat-label">Réparations</div>
            <div class="stat-value"><?php echo number_format($stats['reparations']['count']); ?></div>
            <div class="stat-change <?php echo $stats['reparations']['change'] > 0 ? 'positive' : 'negative'; ?>">
                <i class="fas <?php echo $stats['reparations']['change'] > 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                <?php echo abs($stats['reparations']['change']); ?>% récent
            </div>
        </div>
        
        <div class="stat-card touchable">
            <div class="stat-icon bg-green">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-label">Clients</div>
            <div class="stat-value"><?php echo number_format($stats['clients']['count']); ?></div>
            <div class="stat-change <?php echo $stats['clients']['change'] > 0 ? 'positive' : 'negative'; ?>">
                <i class="fas <?php echo $stats['clients']['change'] > 0 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                <?php echo abs($stats['clients']['change']); ?>% récent
            </div>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-bolt"></i>Actions rapides</h2>
        <p class="section-subtitle">Accédez rapidement aux fonctionnalités clés</p>
    </div>

    <a href="index.php?page=ajouter_reparation" class="action-card touchable">
        <div class="action-icon">
            <i class="fas fa-plus"></i>
        </div>
        <div class="action-content">
            <h3 class="action-title">Nouvelle réparation</h3>
            <p class="action-description">Créer un nouveau dossier de réparation</p>
        </div>
        <div class="action-arrow">
            <i class="fas fa-chevron-right"></i>
        </div>
    </a>

    <a href="index.php?page=ajouter_client" class="action-card touchable">
        <div class="action-icon">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="action-content">
            <h3 class="action-title">Nouveau client</h3>
            <p class="action-description">Ajouter un nouveau client</p>
        </div>
        <div class="action-arrow">
            <i class="fas fa-chevron-right"></i>
        </div>
    </a>

    <a href="index.php?page=scanner" class="action-card touchable">
        <div class="action-icon">
            <i class="fas fa-qrcode"></i>
        </div>
        <div class="action-content">
            <h3 class="action-title">Scanner</h3>
            <p class="action-description">Scanner un code-barres ou QR code</p>
        </div>
        <div class="action-arrow">
            <i class="fas fa-chevron-right"></i>
        </div>
    </a>

    <!-- Réparations récentes -->
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-history"></i>Réparations récentes</h2>
        <p class="section-subtitle">Les dernières réparations enregistrées</p>
    </div>

    <?php if (count($recent_repairs) > 0): ?>
        <?php foreach ($recent_repairs as $repair): ?>
            <div class="list-item touchable" onclick="window.location.href='index.php?page=reparations&id=<?php echo $repair['id']; ?>'">
                <div class="list-item-icon">
                    <?php
                    $icon_class = 'fa-tools';
                    if (strpos(strtolower($repair['type_appareil']), 'iphone') !== false || 
                        strpos(strtolower($repair['type_appareil']), 'smartphone') !== false) {
                        $icon_class = 'fa-mobile-alt';
                    } elseif (strpos(strtolower($repair['type_appareil']), 'ipad') !== false || 
                            strpos(strtolower($repair['type_appareil']), 'tablet') !== false) {
                        $icon_class = 'fa-tablet-alt';
                    } elseif (strpos(strtolower($repair['type_appareil']), 'laptop') !== false || 
                            strpos(strtolower($repair['type_appareil']), 'portable') !== false) {
                        $icon_class = 'fa-laptop';
                    }
                    ?>
                    <i class="fas <?php echo $icon_class; ?>"></i>
                </div>
                <div class="list-item-content">
                    <h3 class="list-item-title"><?php echo htmlspecialchars($repair['marque'] . ' ' . $repair['modele']); ?></h3>
                    <p class="list-item-subtitle"><?php echo htmlspecialchars($repair['client_nom'] . ' ' . $repair['client_prenom']); ?></p>
                    <div class="list-item-meta">
                        <?php
                        $date = new DateTime($repair['date_reception']);
                        echo $date->format('d/m/Y');
                        
                        // Badge de statut
                        $status_class = 'badge-primary';
                        $status_text = 'En cours';
                        
                        switch($repair['statut']) {
                            case 'en_attente':
                                $status_class = 'badge-warning';
                                $status_text = 'En attente';
                                break;
                            case 'terminee':
                                $status_class = 'badge-success';
                                $status_text = 'Terminée';
                                break;
                            case 'abandonnee':
                                $status_class = 'badge-danger';
                                $status_text = 'Abandonnée';
                                break;
                        }
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </div>
                </div>
                <div class="list-item-action">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-tools"></i>
            </div>
            <h3 class="empty-state-title">Aucune réparation récente</h3>
            <p class="empty-state-description">Les réparations que vous créez apparaîtront ici</p>
            <a href="index.php?page=ajouter_reparation" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvelle réparation
            </a>
        </div>
    <?php endif; ?>

    <!-- Tâches à faire -->
    <div class="section-header">
        <h2 class="section-title"><i class="fas fa-tasks"></i>Tâches à faire</h2>
        <p class="section-subtitle">Vos tâches actuelles</p>
    </div>

    <?php if (count($tasks) > 0): ?>
        <?php foreach ($tasks as $task): ?>
            <div class="list-item touchable" onclick="window.location.href='index.php?page=taches&id=<?php echo $task['id']; ?>'">
                <div class="list-item-icon">
                    <?php
                    $priority_class = '';
                    switch($task['priorite']) {
                        case 'haute':
                            $priority_class = 'bg-red';
                            break;
                        case 'moyenne':
                            $priority_class = 'bg-orange';
                            break;
                        case 'basse':
                            $priority_class = 'bg-blue';
                            break;
                    }
                    ?>
                    <i class="fas fa-clipboard-check <?php echo $priority_class; ?>"></i>
                </div>
                <div class="list-item-content">
                    <h3 class="list-item-title"><?php echo htmlspecialchars($task['titre']); ?></h3>
                    <p class="list-item-subtitle"><?php echo htmlspecialchars(substr($task['description'], 0, 60) . (strlen($task['description']) > 60 ? '...' : '')); ?></p>
                    <div class="list-item-meta">
                        <?php
                        $date = new DateTime($task['date_echeance']);
                        echo 'Échéance: ' . $date->format('d/m/Y');
                        ?>
                    </div>
                </div>
                <div class="list-item-action">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-tasks"></i>
            </div>
            <h3 class="empty-state-title">Aucune tâche en cours</h3>
            <p class="empty-state-description">Les tâches que vous créez apparaîtront ici</p>
            <a href="index.php?page=ajouter_tache" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvelle tâche
            </a>
        </div>
    <?php endif; ?>

    <!-- Espace en bas pour éviter que le contenu soit caché par la barre de navigation -->
    <div style="height: 30px;"></div>
</div>

<script>
// Script pour la recherche mobile
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('mobileSearch');
    
    if (searchInput) {
        searchInput.addEventListener('focus', function() {
            window.location.href = 'index.php?page=recherche';
        });
    }
});
</script>

<?php
require_once 'includes/footer.php';
?> 