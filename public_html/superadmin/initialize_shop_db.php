<?php
// Script d'initialisation de la base de données d'un nouveau magasin
session_start();

// Vérifier si l'utilisateur est connecté en tant que super administrateur
if (!isset($_SESSION['superadmin_id'])) {
    header('Location: login.php');
    exit;
}

// Inclure la configuration de la base de données
require_once('../config/database.php');

// Récupérer l'ID du magasin à initialiser
$shop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($shop_id <= 0) {
    die("ID de magasin invalide.");
}

// Récupérer les informations du magasin
$pdo = getMainDBConnection();
$stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ?");
$stmt->execute([$shop_id]);
$shop = $stmt->fetch();

if (!$shop) {
    die("Magasin non trouvé.");
}

// Se connecter à la base de données du magasin
$shop_config = [
    'host' => $shop['db_host'],
    'port' => $shop['db_port'],
    'dbname' => $shop['db_name'],
    'user' => $shop['db_user'],
    'pass' => $shop['db_pass']
];

$shop_db = connectToShopDB($shop_config);

if (!$shop_db) {
    die("Impossible de se connecter à la base de données du magasin.");
}

// Fonction pour exécuter une requête SQL avec gestion des erreurs
function executeSQL($db, $sql, $description) {
    try {
        $db->exec($sql);
        echo "<div class='alert alert-success'>$description</div>";
        return true;
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Erreur lors de $description: " . $e->getMessage() . "</div>";
        return false;
    }
}

// Initialisation des tables
$tables = [
    // Table des utilisateurs
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'technicien') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        techbusy INT DEFAULT 0,
        active_repair_id INT DEFAULT NULL
    )",

    // Table des employés
    "employes" => "CREATE TABLE IF NOT EXISTS employes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        telephone VARCHAR(20),
        date_embauche DATE,
        statut ENUM('actif', 'inactif') DEFAULT 'actif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // Table des clients
    "clients" => "CREATE TABLE IF NOT EXISTS clients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        telephone VARCHAR(20) NOT NULL,
        email VARCHAR(100),
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        inscrit_parrainage TINYINT(1) DEFAULT 0,
        code_parrainage VARCHAR(10),
        date_inscription_parrainage TIMESTAMP NULL
    )",

    // Catégories de statuts
    "statut_categories" => "CREATE TABLE IF NOT EXISTS statut_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(50) NOT NULL,
        couleur VARCHAR(20) NOT NULL,
        ordre INT NOT NULL
    )",

    // Statuts de réparation
    "statuts" => "CREATE TABLE IF NOT EXISTS statuts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(50) NOT NULL,
        description TEXT,
        categorie_id INT,
        couleur VARCHAR(20) NOT NULL,
        ordre INT NOT NULL,
        FOREIGN KEY (categorie_id) REFERENCES statut_categories(id) ON DELETE SET NULL
    )",

    // Table des réparations
    "reparations" => "CREATE TABLE IF NOT EXISTS reparations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        type_appareil VARCHAR(50) NOT NULL,
        marque VARCHAR(50) NOT NULL,
        modele VARCHAR(100) NOT NULL,
        description_probleme TEXT NOT NULL,
        date_reception TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        date_fin_prevue DATE NULL,
        statut VARCHAR(50) DEFAULT 'nouvelle_intervention',
        statut_id INT NULL,
        statut_categorie INT NULL,
        signature VARCHAR(255),
        prix DECIMAL(10,2),
        notes_techniques TEXT,
        notes_finales TEXT,
        photo_appareil VARCHAR(255),
        mot_de_passe VARCHAR(100),
        etat_esthetique VARCHAR(50),
        prix_reparation DECIMAL(10,2) DEFAULT 0.00,
        photos TEXT,
        urgent TINYINT(1) DEFAULT 0,
        commande_requise TINYINT(1) DEFAULT 0,
        archive ENUM('OUI', 'NON') DEFAULT 'NON',
        employe_id INT,
        date_gardiennage DATE NULL,
        gardiennage_facture DECIMAL(10,2),
        parrain_id INT,
        reduction_parrainage DECIMAL(10,2),
        reduction_parrainage_pourcentage INT,
        signature_client VARCHAR(255),
        photo_signature VARCHAR(255),
        photo_client VARCHAR(255),
        accept_conditions TINYINT(1) DEFAULT 0,
        proprietaire TINYINT(1) DEFAULT 0,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        FOREIGN KEY (employe_id) REFERENCES employes(id) ON DELETE SET NULL,
        FOREIGN KEY (statut_id) REFERENCES statuts(id) ON DELETE SET NULL,
        FOREIGN KEY (statut_categorie) REFERENCES statut_categories(id) ON DELETE SET NULL,
        FOREIGN KEY (parrain_id) REFERENCES clients(id) ON DELETE SET NULL
    )",

    // Photos des réparations
    "photos_reparation" => "CREATE TABLE IF NOT EXISTS photos_reparation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reparation_id INT NOT NULL,
        chemin VARCHAR(255) NOT NULL,
        date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE
    )",

    // Attributions des réparations
    "reparation_attributions" => "CREATE TABLE IF NOT EXISTS reparation_attributions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reparation_id INT NOT NULL,
        employe_id INT NOT NULL,
        date_attribution TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE,
        FOREIGN KEY (employe_id) REFERENCES employes(id) ON DELETE CASCADE
    )",

    // Logs des réparations
    "reparation_logs" => "CREATE TABLE IF NOT EXISTS reparation_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reparation_id INT NOT NULL,
        message TEXT NOT NULL,
        date_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_id INT,
        type VARCHAR(50),
        FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",

    // Table des paramètres
    "parametres" => "CREATE TABLE IF NOT EXISTS parametres (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(50) NOT NULL UNIQUE,
        valeur TEXT,
        description TEXT,
        type VARCHAR(20) DEFAULT 'text'
    )",

    // SMS templates
    "sms_templates" => "CREATE TABLE IF NOT EXISTS sms_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        contenu TEXT NOT NULL,
        description TEXT,
        actif TINYINT(1) DEFAULT 1,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // SMS logs
    "sms_logs" => "CREATE TABLE IF NOT EXISTS sms_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        destinataire VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        statut VARCHAR(20) DEFAULT 'envoyé',
        reparation_id INT,
        FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE SET NULL
    )",

    // Notifications
    "notifications" => "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        titre VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        lien VARCHAR(255),
        lue TINYINT(1) DEFAULT 0,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        type VARCHAR(50),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Journal des actions
    "journal_actions" => "CREATE TABLE IF NOT EXISTS journal_actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )"
];

// Entête HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialisation de la base de données - <?php echo htmlspecialchars($shop['name']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">
                    <i class="fas fa-database me-2"></i>
                    Initialisation de la base de données - <?php echo htmlspecialchars($shop['name']); ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Ce processus va initialiser la structure de base de données pour le magasin <strong><?php echo htmlspecialchars($shop['name']); ?></strong>.
                </div>

                <div class="progress mb-4">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%" id="progress-bar"></div>
                </div>

                <div id="status-container">
                    <?php
                    // Créer les tables
                    $total_tables = count($tables);
                    $progress = 0;
                    $success_count = 0;
                    
                    foreach ($tables as $table_name => $sql) {
                        $progress++;
                        $percent = round(($progress / $total_tables) * 100);
                        
                        echo "<script>document.getElementById('progress-bar').style.width = '$percent%';</script>";
                        echo "<div id='status-$table_name'><i class='fas fa-cog fa-spin me-2'></i>Création de la table $table_name...</div>";
                        ob_flush();
                        flush();
                        
                        if (executeSQL($shop_db, $sql, "création de la table $table_name")) {
                            echo "<script>document.getElementById('status-$table_name').innerHTML = '<i class=\"fas fa-check text-success me-2\"></i>Table $table_name créée avec succès';</script>";
                            $success_count++;
                        } else {
                            echo "<script>document.getElementById('status-$table_name').innerHTML = '<i class=\"fas fa-times text-danger me-2\"></i>Échec de création de la table $table_name';</script>";
                        }
                        
                        ob_flush();
                        flush();
                        sleep(1); // Petite pause pour l'effet visuel
                    }
                    ?>
                </div>

                <div class="alert alert-<?php echo ($success_count == $total_tables) ? 'success' : 'warning'; ?> mt-4">
                    <i class="fas fa-<?php echo ($success_count == $total_tables) ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <?php
                    if ($success_count == $total_tables) {
                        echo "Initialisation terminée avec succès! Toutes les tables ont été créées.";
                    } else {
                        echo "Initialisation terminée avec des avertissements. $success_count sur $total_tables tables ont été créées.";
                    }
                    ?>
                </div>

                <?php
                // Créer un administrateur par défaut si l'initialisation a réussi
                if ($success_count == $total_tables) {
                    $default_password = password_hash('Admin123!', PASSWORD_DEFAULT);
                    try {
                        $shop_db->exec("INSERT INTO users (username, password, full_name, role) 
                                        VALUES ('admin', '$default_password', 'Administrateur', 'admin')");
                        echo "<div class='alert alert-info'><i class='fas fa-user me-2'></i> 
                                Compte administrateur créé avec succès. Identifiants:<br>
                                <strong>Nom d'utilisateur:</strong> admin<br>
                                <strong>Mot de passe:</strong> Admin123!<br>
                                <span class='text-danger'>IMPORTANT: Changez ce mot de passe dès votre première connexion!</span>
                              </div>";
                    } catch (PDOException $e) {
                        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i> 
                                Impossible de créer le compte administrateur: " . $e->getMessage() . "</div>";
                    }

                    // Insertion des catégories de statut par défaut
                    $default_categories = [
                        ["En attente", "#ffc107", 1],
                        ["En cours", "#17a2b8", 2],
                        ["Terminé", "#28a745", 3],
                        ["Problème", "#dc3545", 4]
                    ];

                    try {
                        $insert_stmt = $shop_db->prepare("INSERT INTO statut_categories (nom, couleur, ordre) VALUES (?, ?, ?)");
                        foreach ($default_categories as $category) {
                            $insert_stmt->execute($category);
                        }
                        echo "<div class='alert alert-info'><i class='fas fa-tags me-2'></i> Catégories de statut par défaut créées.</div>";
                    } catch (PDOException $e) {
                        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i> 
                                Impossible de créer les catégories de statut par défaut: " . $e->getMessage() . "</div>";
                    }

                    // Insertion des paramètres par défaut
                    $default_params = [
                        ["nom_magasin", $shop['name'], "Nom du magasin", "text"],
                        ["adresse_magasin", $shop['address'], "Adresse du magasin", "text"],
                        ["telephone_magasin", $shop['phone'], "Téléphone du magasin", "text"],
                        ["email_magasin", $shop['email'], "Email du magasin", "text"],
                        ["devise", "€", "Symbole de la devise", "text"],
                        ["tva", "20", "Taux de TVA en pourcentage", "number"],
                        ["logo", "", "Chemin vers le logo du magasin", "text"],
                        ["sms_actif", "1", "Activer l'envoi de SMS", "boolean"],
                        ["sms_api_key", "", "Clé API pour le service SMS", "text"],
                        ["sms_sender", $shop['name'], "Nom de l'expéditeur des SMS", "text"]
                    ];

                    try {
                        $insert_stmt = $shop_db->prepare("INSERT INTO parametres (nom, valeur, description, type) VALUES (?, ?, ?, ?)");
                        foreach ($default_params as $param) {
                            $insert_stmt->execute($param);
                        }
                        echo "<div class='alert alert-info'><i class='fas fa-cogs me-2'></i> Paramètres par défaut créés.</div>";
                    } catch (PDOException $e) {
                        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i> 
                                Impossible de créer les paramètres par défaut: " . $e->getMessage() . "</div>";
                    }
                }
                ?>

                <div class="mt-4 d-flex gap-2">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Retour à l'accueil
                    </a>
                    <a href="view_shop.php?id=<?php echo $shop_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-eye me-2"></i>Détails du magasin
                    </a>
                    <a href="shop_access.php?id=<?php echo $shop_id; ?>" class="btn btn-success">
                        <i class="fas fa-sign-in-alt me-2"></i>Accéder au magasin
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS & Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 