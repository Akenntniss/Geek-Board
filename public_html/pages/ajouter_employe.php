<?php
// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupération et nettoyage des données
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    $full_name = clean_input($_POST['full_name']);
    $role = clean_input($_POST['role']);
    
    // Validation des données
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est obligatoire.";
    }
    
    if (empty($password)) {
        $errors[] = "Le mot de passe est obligatoire.";
    }
    
    if (empty($full_name)) {
        $errors[] = "Le nom complet est obligatoire.";
    }
    
    if (!in_array($role, ['admin', 'technicien'])) {
        $errors[] = "Le rôle n'est pas valide.";
    }
    
    // Si pas d'erreurs, insertion de l'utilisateur
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, full_name, role) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$username, $hashed_password, $full_name, $role]);
            
            set_message("Utilisateur ajouté avec succès!", "success");
            redirect("employes");
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Code d'erreur pour doublon d'username
                $errors[] = "Ce nom d'utilisateur est déjà utilisé.";
            } else {
                $errors[] = "Erreur lors de l'ajout de l'utilisateur: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-3 mb-md-0">Ajouter un Utilisateur</h1>
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

        <form method="POST" action="index.php?page=ajouter_employe">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur *</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Mot de passe *</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="full_name" class="form-label">Nom complet *</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="role" class="form-label">Rôle *</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="technicien">Technicien</option>
                        <option value="admin">Administrateur</option>
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