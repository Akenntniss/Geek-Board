<?php
// Définir la page actuelle pour le menu
$current_page = 'parametre';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Au lieu d'utiliser header() qui cause des problèmes, utiliser des alternatives
    echo '<meta http-equiv="refresh" content="0;url=index.php">';
    exit();
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];

try {
    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_message("Erreur lors de la récupération des informations utilisateur: " . $e->getMessage(), "danger");
}

// Variable pour stocker si un formulaire a été soumis avec succès
$form_submitted = false;

// Traitement du formulaire de mise à jour des paramètres
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Mise à jour du profil
        $nom = cleanInput($_POST['nom']);
        $prenom = cleanInput($_POST['prenom']);
        $email = cleanInput($_POST['email']);
        $telephone = cleanInput($_POST['telephone']);
        
        try {
            $stmt = $shop_pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id = ?");
            $result = $stmt->execute([$nom, $prenom, $email, $telephone, $user_id]);
            
            if ($result) {
                set_message("Votre profil a été mis à jour avec succès.", "success");
                $form_submitted = true;
            } else {
                set_message("Erreur lors de la mise à jour du profil.", "danger");
            }
        } catch (PDOException $e) {
            set_message("Erreur de base de données: " . $e->getMessage(), "danger");
        }
    } elseif (isset($_POST['update_password'])) {
        // Mise à jour du mot de passe
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Vérifier que les nouveaux mots de passe correspondent
        if ($new_password !== $confirm_password) {
            set_message("Les nouveaux mots de passe ne correspondent pas.", "danger");
        } else {
            try {
                // Vérifier le mot de passe actuel
                $stmt = $shop_pdo->prepare("SELECT password FROM utilisateurs WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($current_password, $user_data['password'])) {
                    // Hacher le nouveau mot de passe
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Mettre à jour le mot de passe
                    $stmt = $shop_pdo->prepare("UPDATE utilisateurs SET password = ? WHERE id = ?");
                    $result = $stmt->execute([$hashed_password, $user_id]);
                    
                    if ($result) {
                        set_message("Votre mot de passe a été mis à jour avec succès.", "success");
                        $form_submitted = true;
                    } else {
                        set_message("Erreur lors de la mise à jour du mot de passe.", "danger");
                    }
                } else {
                    set_message("Le mot de passe actuel est incorrect.", "danger");
                }
            } catch (PDOException $e) {
                set_message("Erreur de base de données: " . $e->getMessage(), "danger");
            }
        }
    } elseif (isset($_POST['update_preferences'])) {
        // Mise à jour des préférences
        $theme = cleanInput($_POST['theme']);
        $notifications = isset($_POST['notifications']) ? 1 : 0;
        $elements_per_page = (int)$_POST['elements_per_page'];
        $timezone_offset = (int)$_POST['timezone_offset'];
        
        try {
            // Vérifier si les préférences existent déjà
            $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM preferences WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                // Mettre à jour les préférences existantes
                $stmt = $shop_pdo->prepare("UPDATE preferences SET theme = ?, notifications = ?, elements_per_page = ?, timezone_offset = ? WHERE user_id = ?");
                $result = $stmt->execute([$theme, $notifications, $elements_per_page, $timezone_offset, $user_id]);
            } else {
                // Créer de nouvelles préférences
                $stmt = $shop_pdo->prepare("INSERT INTO preferences (user_id, theme, notifications, elements_per_page, timezone_offset) VALUES (?, ?, ?, ?, ?)");
                $result = $stmt->execute([$user_id, $theme, $notifications, $elements_per_page, $timezone_offset]);
            }
            
            if ($result) {
                // Mettre à jour la session
                $_SESSION['user_preferences'] = [
                    'theme' => $theme,
                    'notifications' => $notifications,
                    'elements_per_page' => $elements_per_page,
                    'timezone_offset' => $timezone_offset
                ];
                
                set_message("Vos préférences ont été mises à jour avec succès.", "success");
                $form_submitted = true;
            } else {
                set_message("Erreur lors de la mise à jour des préférences.", "danger");
            }
        } catch (PDOException $e) {
            set_message("Erreur de base de données: " . $e->getMessage(), "danger");
        }
    }
    
    // Rediriger si un formulaire a été soumis avec succès
    if ($form_submitted) {
        echo '<meta http-equiv="refresh" content="0;url=index.php?page=parametre">';
        exit();
    }
}

// Récupérer les préférences utilisateur
$preferences = [
    'theme' => 'light',
    'notifications' => 1,
    'elements_per_page' => 10,
    'timezone_offset' => 0 // Valeur par défaut pour le décalage GMT
];

try {
    $stmt = $shop_pdo->prepare("SELECT * FROM preferences WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_preferences = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_preferences) {
        $preferences = [
            'theme' => $user_preferences['theme'],
            'notifications' => $user_preferences['notifications'],
            'elements_per_page' => $user_preferences['elements_per_page'],
            'timezone_offset' => isset($user_preferences['timezone_offset']) ? $user_preferences['timezone_offset'] : 0
        ];
    }
} catch (PDOException $e) {
    // Utiliser les préférences par défaut si erreur
}
?>

<style>
/* Style pour déplacer le contenu de la page vers le bas */
.container {
    margin-top: 65px;
}
</style>

<div class="container">
    <h1 class="my-4"><i class="fas fa-cogs me-2"></i>Paramètres</h1>
    
    <div class="row">
        <div class="col-md-3 mb-4">
            <!-- Menu de navigation des paramètres -->
            <div class="list-group shadow-sm">
                <a href="#profile" class="list-group-item list-group-item-action active d-flex align-items-center" data-bs-toggle="list">
                    <i class="fas fa-user me-3 text-primary"></i><span>Mon profil</span>
                </a>
                <a href="#security" class="list-group-item list-group-item-action d-flex align-items-center" data-bs-toggle="list">
                    <i class="fas fa-lock me-3 text-primary"></i><span>Sécurité</span>
                </a>
                <a href="#preferences" class="list-group-item list-group-item-action d-flex align-items-center" data-bs-toggle="list">
                    <i class="fas fa-sliders-h me-3 text-primary"></i><span>Préférences</span>
                </a>
                <a href="#notifications" class="list-group-item list-group-item-action d-flex align-items-center" data-bs-toggle="list">
                    <i class="fas fa-bell me-3 text-primary"></i><span>Notifications</span>
                </a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="#system" class="list-group-item list-group-item-action d-flex align-items-center" data-bs-toggle="list">
                    <i class="fas fa-server me-3 text-primary"></i><span>Système</span>
                </a>
                <?php endif; ?>
            </div>
            
            <div class="card mt-4 border-primary">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-info-circle me-2 text-primary"></i>Aide</h5>
                    <p class="card-text small">Configurez votre profil et vos préférences pour personnaliser votre expérience utilisateur.</p>
                    <a href="#" class="btn btn-sm btn-outline-primary">Documentation</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="tab-content">
                <!-- Profil -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Informations personnelles</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nom" class="form-label">Nom</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($user['nom']) ? htmlspecialchars($user['nom']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prenom" class="form-label">Prénom</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo isset($user['prenom']) ? htmlspecialchars($user['prenom']) : ''; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo isset($user['telephone']) ? htmlspecialchars($user['telephone']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Sécurité -->
                <div class="tab-pane fade" id="security">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-key me-2"></i>Changer de mot de passe</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Mot de passe actuel</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Le mot de passe doit contenir au moins 8 caractères.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_password" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Mettre à jour le mot de passe
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Sessions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <span class="badge bg-success p-2"><i class="fas fa-check-circle"></i></span>
                                </div>
                                <div>
                                    <h6 class="mb-0">Session actuelle</h6>
                                    <p class="text-muted mb-0 small">Connecté depuis <?php echo isset($user['last_login']) ? format_date_user(strtotime($user['last_login']), 'd/m/Y H:i') : 'inconnu'; ?></p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <button class="btn btn-danger" onclick="if(confirm('Êtes-vous sûr de vouloir déconnecter toutes les autres sessions ?')) window.location.href='index.php?page=deconnexion&all=1';">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnecter toutes les autres sessions
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Préférences -->
                <div class="tab-pane fade" id="preferences">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Préférences d'affichage</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="theme" class="form-label">Thème</label>
                                    <select class="form-select" id="theme" name="theme">
                                        <option value="light" <?php echo $preferences['theme'] === 'light' ? 'selected' : ''; ?>>Clair</option>
                                        <option value="dark" <?php echo $preferences['theme'] === 'dark' ? 'selected' : ''; ?>>Sombre</option>
                                        <option value="system" <?php echo $preferences['theme'] === 'system' ? 'selected' : ''; ?>>Utiliser les préférences système</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="elements_per_page" class="form-label">Nombre d'éléments par page</label>
                                    <select class="form-select" id="elements_per_page" name="elements_per_page">
                                        <option value="10" <?php echo $preferences['elements_per_page'] == 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="25" <?php echo $preferences['elements_per_page'] == 25 ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo $preferences['elements_per_page'] == 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $preferences['elements_per_page'] == 100 ? 'selected' : ''; ?>>100</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="timezone_offset" class="form-label">Fuseau horaire GMT</label>
                                    <select class="form-select" id="timezone_offset" name="timezone_offset">
                                        <?php for ($i = -12; $i <= 14; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $preferences['timezone_offset'] == $i ? 'selected' : ''; ?>>
                                                GMT<?php echo $i > 0 ? '+' . $i : $i; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="form-text">Définissez votre fuseau horaire par rapport à GMT (Greenwich Mean Time).</div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="notifications" name="notifications" <?php echo $preferences['notifications'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="notifications">Activer les notifications du navigateur</label>
                                </div>
                                
                                <button type="submit" name="update_preferences" class="btn btn-primary">Enregistrer les préférences</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="tab-pane fade" id="notifications">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Paramètres de notification</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="notification_email" checked>
                                    <label class="form-check-label" for="notification_email">Recevoir des notifications par email</label>
                                </div>
                                
                                <div class="mt-4">
                                    <h6 class="mb-3">Types de notifications</h6>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_new_repair" checked>
                                        <label class="form-check-label" for="notify_new_repair">Nouvelles réparations</label>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_repair_status" checked>
                                        <label class="form-check-label" for="notify_repair_status">Changements de statut des réparations</label>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_new_task" checked>
                                        <label class="form-check-label" for="notify_new_task">Nouvelles tâches</label>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_inventory" checked>
                                        <label class="form-check-label" for="notify_inventory">Alertes d'inventaire</label>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_system">
                                        <label class="form-check-label" for="notify_system">Notifications système</label>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-primary mt-3">Enregistrer les paramètres</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Système (Admin uniquement) -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <div class="tab-pane fade" id="system">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Paramètres système</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Nom de l'entreprise</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="MD Geek">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="company_address" class="form-label">Adresse</label>
                                    <textarea class="form-control" id="company_address" name="company_address" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="company_phone" class="form-label">Téléphone</label>
                                    <input type="text" class="form-control" id="company_phone" name="company_phone">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="company_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="company_email" name="company_email">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="company_logo" class="form-label">Logo</label>
                                    <input type="file" class="form-control" id="company_logo" name="company_logo">
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode">
                                    <label class="form-check-label" for="maintenance_mode">Mode maintenance</label>
                                </div>
                                
                                <button type="button" class="btn btn-primary">Enregistrer les paramètres</button>
                                <a href="#" class="btn btn-info ms-2">Sauvegarder la base de données</a>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Activer les onglets Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        // Afficher l'onglet sauvegardé ou le premier par défaut
        const activeTab = localStorage.getItem('active_settings_tab') || 'profile';
        const tabToActivate = document.querySelector('.list-group-item[href="#' + activeTab + '"]');
        if (tabToActivate) {
            const bsTab = new bootstrap.Tab(tabToActivate);
            bsTab.show();
        }
        
        // Sauvegarder l'onglet actif lorsqu'il est changé
        const tabs = document.querySelectorAll('.list-group-item[data-bs-toggle="list"]');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(event) {
                const targetId = event.target.getAttribute('href').substring(1);
                localStorage.setItem('active_settings_tab', targetId);
                
                // Mettre à jour la classe active
                tabs.forEach(t => t.classList.remove('active'));
                event.target.classList.add('active');
            });
        });
        
        // Gestion des boutons de visibilité des mots de passe
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetInput = document.getElementById(targetId);
                
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    this.querySelector('i').classList.remove('fa-eye');
                    this.querySelector('i').classList.add('fa-eye-slash');
                } else {
                    targetInput.type = 'password';
                    this.querySelector('i').classList.remove('fa-eye-slash');
                    this.querySelector('i').classList.add('fa-eye');
                }
            });
        });
    });
    
    // Fonction pour afficher le temps écoulé
    function timeAgo(timestamp) {
        const seconds = Math.floor((new Date() - timestamp * 1000) / 1000);
        
        let interval = Math.floor(seconds / 31536000);
        if (interval > 1) return interval + ' ans';
        
        interval = Math.floor(seconds / 2592000);
        if (interval > 1) return interval + ' mois';
        
        interval = Math.floor(seconds / 86400);
        if (interval > 1) return interval + ' jours';
        if (interval === 1) return 'hier';
        
        interval = Math.floor(seconds / 3600);
        if (interval > 1) return interval + ' heures';
        if (interval === 1) return '1 heure';
        
        interval = Math.floor(seconds / 60);
        if (interval > 1) return interval + ' minutes';
        if (interval === 1) return '1 minute';
        
        return 'quelques secondes';
    }
</script> 