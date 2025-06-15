<?php
// Vérification de l'ID de l'utilisateur
if (!isset($_GET['id'])) {
    set_message("ID d'utilisateur manquant.", "error");
    redirect("employes");
}

$user_id = (int)$_GET['id'];

// Récupération des données de l'utilisateur
try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        set_message("Utilisateur non trouvé.", "error");
        redirect("employes");
    }
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage(), "error");
    redirect("employes");
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération et nettoyage des données
    $username = clean_input($_POST['username']);
    $full_name = clean_input($_POST['full_name']);
    $role = clean_input($_POST['role']);
    
    // Validation des données
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est obligatoire.";
    }
    
    if (empty($full_name)) {
        $errors[] = "Le nom complet est obligatoire.";
    }
    
    if (!in_array($role, ['admin', 'technicien'])) {
        $errors[] = "Le rôle n'est pas valide.";
    }
    
    // Si pas d'erreurs, mise à jour de l'utilisateur
    if (empty($errors)) {
        try {
            // Si un nouveau mot de passe est fourni, l'inclure dans la mise à jour
            if (!empty($_POST['password'])) {
                $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $shop_pdo->prepare("
                    UPDATE users 
                    SET username = ?, password = ?, full_name = ?, role = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $hashed_password, $full_name, $role, $user_id]);
            } else {
                $stmt = $shop_pdo->prepare("
                    UPDATE users 
                    SET username = ?, full_name = ?, role = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $full_name, $role, $user_id]);
            }
            
            set_message("Utilisateur modifié avec succès!", "success");
            redirect("employes");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Code d'erreur pour doublon d'username
                $errors[] = "Ce nom d'utilisateur est déjà utilisé.";
            } else {
                $errors[] = "Erreur lors de la modification de l'utilisateur: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-3 mb-md-0">Modifier l'Utilisateur</h1>
    <a href="index.php?page=employes" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Retour
    </a>
</div>

<div class="card">
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

        <form method="POST" action="index.php?page=modifier_employe&id=<?php echo $user_id; ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur *</label>
                    <input type="text" class="form-control" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($user['username']); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="full_name" class="form-label">Nom complet *</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required
                           value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="role" class="form-label">Rôle *</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="technicien" <?php echo $user['role'] == 'technicien' ? 'selected' : ''; ?>>Technicien</option>
                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                    </select>
                </div>
            </div>
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
            </div>
        </form>
    </div>
</div> 