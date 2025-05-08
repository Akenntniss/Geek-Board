<?php
// Assurons-nous d'utiliser la connexion à la base de données du magasin
$pdo = getShopDBConnection();

// Vérification de la base de données actuelle
try {
    $db_check = $pdo->query("SELECT DATABASE() as current_db");
    $current_db = $db_check->fetch(PDO::FETCH_ASSOC);
    error_log("Page employes.php - Base de données utilisée: " . $current_db['current_db']);
} catch (PDOException $e) {
    error_log("Erreur lors de la vérification de la base: " . $e->getMessage());
}

// Récupération de la liste des utilisateurs
try {
    $stmt = $pdo->query("
        SELECT u.*, 
               COUNT(t.id) as nombre_taches,
               COUNT(CASE WHEN t.statut = 'a_faire' THEN 1 END) as taches_en_attente
        FROM users u 
        LEFT JOIN taches t ON u.id = t.employe_id 
        GROUP BY u.id 
        ORDER BY u.full_name ASC
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des utilisateurs : " . $e->getMessage(), "error");
    $users = [];
}

// Traitement de la suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        // Vérifier si l'utilisateur a des tâches associées
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM taches WHERE employe_id = ?");
        $stmt->execute([$id]);
        $has_tasks = $stmt->fetchColumn() > 0;
        
        if ($has_tasks) {
            set_message("Impossible de supprimer l'utilisateur car il a des tâches associées.", "error");
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            set_message("Utilisateur supprimé avec succès!", "success");
            redirect("employes");
        }
    } catch (PDOException $e) {
        set_message("Erreur lors de la suppression de l'utilisateur : " . $e->getMessage(), "error");
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-3 mb-md-0">Gestion des Utilisateurs</h1>
    <a href="index.php?page=ajouter_employe" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Nouvel Utilisateur
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nom complet</th>
                        <th>Nom d'utilisateur</th>
                        <th>Rôle</th>
                        <th>Tâches</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Aucun utilisateur trouvé.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'info'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-primary"><?php echo $user['nombre_taches']; ?> tâches</span>
                                    <?php if ($user['taches_en_attente'] > 0): ?>
                                        <span class="badge bg-warning"><?php echo $user['taches_en_attente']; ?> en attente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="index.php?page=modifier_employe&id=<?php echo $user['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['username'] !== 'admin'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmDelete(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        window.location.href = 'index.php?page=employes&delete=' + id;
    }
}
</script> 