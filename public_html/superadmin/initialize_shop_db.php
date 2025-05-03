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
$shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

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
        code VARCHAR(50) NOT NULL,
        couleur VARCHAR(20) NOT NULL,
        ordre INT NOT NULL DEFAULT 0
    )",

    // Statuts de réparation
    "statuts" => "CREATE TABLE IF NOT EXISTS statuts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        code VARCHAR(50) NOT NULL,
        categorie_id INT NOT NULL,
        est_actif TINYINT(1) NOT NULL DEFAULT 1,
        ordre INT NOT NULL DEFAULT 0,
        FOREIGN KEY (categorie_id) REFERENCES statut_categories(id) ON DELETE CASCADE
    )",

    // Fournisseurs
    "fournisseurs" => "CREATE TABLE IF NOT EXISTS fournisseurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        contact_nom VARCHAR(100) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        url VARCHAR(20) DEFAULT NULL,
        adresse TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Catégories
    "categories" => "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        description TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",

    // Produits
    "produits" => "CREATE TABLE IF NOT EXISTS produits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reference VARCHAR(50) NOT NULL UNIQUE,
        nom VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        categorie_id INT DEFAULT NULL,
        fournisseur_id INT DEFAULT NULL,
        prix_achat DECIMAL(10,2) DEFAULT NULL,
        prix_vente DECIMAL(10,2) DEFAULT NULL,
        quantite INT DEFAULT 0,
        seuil_alerte INT DEFAULT 5,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        status ENUM('normal','temporaire','a_retourner') DEFAULT 'normal',
        date_limite_retour DATE DEFAULT NULL,
        motif_retour TEXT DEFAULT NULL,
        FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE SET NULL
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
        date_fin_prevue DATE DEFAULT NULL,
        statut VARCHAR(50) DEFAULT 'nouvelle_intervention',
        statut_id INT DEFAULT NULL,
        statut_categorie INT DEFAULT NULL,
        signature VARCHAR(255) DEFAULT NULL,
        prix DECIMAL(10,2) DEFAULT NULL,
        notes_techniques TEXT DEFAULT NULL,
        notes_finales TEXT DEFAULT NULL,
        photo_appareil VARCHAR(255) DEFAULT NULL,
        mot_de_passe VARCHAR(100) DEFAULT NULL,
        etat_esthetique VARCHAR(50) DEFAULT NULL,
        prix_reparation DECIMAL(10,2) DEFAULT 0.00,
        photos TEXT DEFAULT NULL,
        urgent TINYINT(1) DEFAULT 0,
        commande_requise TINYINT(1) DEFAULT 0,
        archive ENUM('OUI','NON') DEFAULT 'NON',
        employe_id INT DEFAULT NULL,
        date_gardiennage DATE DEFAULT NULL,
        gardiennage_facture DECIMAL(10,2) DEFAULT NULL,
        parrain_id INT DEFAULT NULL,
        reduction_parrainage DECIMAL(10,2) DEFAULT NULL,
        reduction_parrainage_pourcentage INT DEFAULT NULL,
        signature_client VARCHAR(255) DEFAULT NULL,
        photo_signature VARCHAR(255) DEFAULT NULL,
        photo_client VARCHAR(255) DEFAULT NULL,
        accept_conditions TINYINT(1) DEFAULT 0,
        proprietaire TINYINT(1) DEFAULT 0,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        FOREIGN KEY (statut_id) REFERENCES statuts(id) ON DELETE SET NULL,
        FOREIGN KEY (statut_categorie) REFERENCES statut_categories(id) ON DELETE SET NULL,
        FOREIGN KEY (employe_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (parrain_id) REFERENCES clients(id) ON DELETE SET NULL
    )",

    // Photos des réparations
    "photos_reparation" => "CREATE TABLE IF NOT EXISTS photos_reparation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reparation_id INT NOT NULL,
        url VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        date_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE
    )",

    // Attributions des réparations
    "reparation_attributions" => "CREATE TABLE IF NOT EXISTS reparation_attributions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reparation_id INT NOT NULL,
        employe_id INT NOT NULL,
        date_debut TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_fin TIMESTAMP DEFAULT NULL,
        statut_avant VARCHAR(50) DEFAULT NULL,
        statut_apres VARCHAR(50) DEFAULT NULL,
        est_principal TINYINT(1) DEFAULT 1,
        FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE,
        FOREIGN KEY (employe_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Logs des réparations
    "reparation_logs" => "CREATE TABLE IF NOT EXISTS reparation_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reparation_id INT NOT NULL,
        employe_id INT NOT NULL,
        action_type ENUM('demarrage','terminer','changement_statut','ajout_note','modification','autre') NOT NULL,
        date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        statut_avant VARCHAR(50) DEFAULT NULL,
        statut_apres VARCHAR(50) DEFAULT NULL,
        details TEXT DEFAULT NULL,
        FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE,
        FOREIGN KEY (employe_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // SMS templates
    "sms_templates" => "CREATE TABLE IF NOT EXISTS sms_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        contenu TEXT NOT NULL,
        statut_id INT DEFAULT NULL,
        est_actif TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (statut_id) REFERENCES statuts(id) ON DELETE SET NULL
    )",

    // SMS logs
    "sms_logs" => "CREATE TABLE IF NOT EXISTS sms_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        recipient VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        status INT DEFAULT NULL,
        reparation_id INT DEFAULT NULL,
        response TEXT DEFAULT NULL,
        date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE SET NULL
    )",

    // Table des paramètres
    "parametres" => "CREATE TABLE IF NOT EXISTS parametres (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cle VARCHAR(50) NOT NULL UNIQUE,
        valeur TEXT DEFAULT NULL,
        description TEXT DEFAULT NULL
    )",

    // Journal des actions
    "journal_actions" => "CREATE TABLE IF NOT EXISTS journal_actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action_type VARCHAR(50) NOT NULL,
        target_id INT NOT NULL,
        details TEXT DEFAULT NULL,
        date_action DATETIME NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Notifications
    "notifications" => "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        notification_type VARCHAR(50) NOT NULL DEFAULT 'general',
        message TEXT NOT NULL,
        related_id INT DEFAULT NULL,
        related_type VARCHAR(50) DEFAULT NULL,
        action_url VARCHAR(255) DEFAULT NULL,
        is_important TINYINT(1) NOT NULL DEFAULT 0,
        is_broadcast TINYINT(1) NOT NULL DEFAULT 0,
        created_by INT DEFAULT NULL,
        status ENUM('new','pending','read') DEFAULT 'new',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Types de notifications
    "notification_types" => "CREATE TABLE IF NOT EXISTS notification_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_code VARCHAR(50) NOT NULL,
        description VARCHAR(255) NOT NULL,
        icon VARCHAR(50) NOT NULL,
        color VARCHAR(20) NOT NULL,
        importance ENUM('basse','normale','haute','critique') NOT NULL DEFAULT 'normale'
    )",

    // Préférences de notifications
    "notification_preferences" => "CREATE TABLE IF NOT EXISTS notification_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type_notification VARCHAR(50) NOT NULL,
        active TINYINT(1) NOT NULL DEFAULT 1,
        email_notification TINYINT(1) NOT NULL DEFAULT 0,
        push_notification TINYINT(1) NOT NULL DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY user_notification_type (user_id, type_notification)
    )",

    // Abonnements Push
    "push_subscriptions" => "CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        endpoint VARCHAR(512) NOT NULL,
        auth_key VARCHAR(255) NOT NULL,
        p256dh_key VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Notifications programmées
    "scheduled_notifications" => "CREATE TABLE IF NOT EXISTS scheduled_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        scheduled_datetime DATETIME NOT NULL,
        sent_datetime DATETIME DEFAULT NULL,
        target_user_id INT DEFAULT NULL,
        is_broadcast TINYINT(1) NOT NULL DEFAULT 0,
        notification_type VARCHAR(50) NOT NULL DEFAULT 'general',
        action_url VARCHAR(255) DEFAULT NULL,
        created_by INT DEFAULT NULL,
        status ENUM('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
        options TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT NULL,
        FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )",

    // Sessions utilisateurs
    "user_sessions" => "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expiry DATETIME NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Signalements de bugs
    "bug_reports" => "CREATE TABLE IF NOT EXISTS bug_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        description TEXT NOT NULL,
        page_url VARCHAR(255) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        priorite ENUM('basse','moyenne','haute','critique') NOT NULL DEFAULT 'basse',
        status ENUM('nouveau','en_cours','resolu','ferme') DEFAULT 'nouveau',
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_resolution DATETIME DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",

    // Commandes fournisseurs
    "commandes_fournisseurs" => "CREATE TABLE IF NOT EXISTS commandes_fournisseurs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fournisseur_id INT NOT NULL,
        date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        statut ENUM('en_attente','validee','recue','annulee') DEFAULT 'en_attente',
        montant_total DECIMAL(10,2) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        user_id INT NOT NULL,
        FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",

    // Lignes de commandes fournisseurs
    "lignes_commande_fournisseur" => "CREATE TABLE IF NOT EXISTS lignes_commande_fournisseur (
        id INT AUTO_INCREMENT PRIMARY KEY,
        commande_id INT NOT NULL,
        produit_id INT NOT NULL,
        quantite INT NOT NULL,
        prix_unitaire DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (commande_id) REFERENCES commandes_fournisseurs(id) ON DELETE CASCADE,
        FOREIGN KEY (produit_id) REFERENCES produits(id)
    )",

    // Mouvements de stock
    "mouvements_stock" => "CREATE TABLE IF NOT EXISTS mouvements_stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        produit_id INT NOT NULL,
        fournisseur_id INT DEFAULT NULL,
        type_mouvement ENUM('entree','sortie') NOT NULL,
        quantite INT NOT NULL,
        date_mouvement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        motif VARCHAR(255) DEFAULT NULL,
        user_id INT NOT NULL,
        FOREIGN KEY (produit_id) REFERENCES produits(id),
        FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",

    // Commandes de pièces
    "commandes_pieces" => "CREATE TABLE IF NOT EXISTS commandes_pieces (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reference VARCHAR(50) NOT NULL UNIQUE,
        reparation_id INT DEFAULT NULL,
        client_id INT DEFAULT NULL,
        fournisseur_id INT NOT NULL,
        nom_piece VARCHAR(255) NOT NULL,
        code_barre VARCHAR(50) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        quantite INT NOT NULL DEFAULT 1,
        prix_estime DECIMAL(10,2) DEFAULT NULL,
        commentaire_interne TEXT DEFAULT NULL,
        note_interne TEXT DEFAULT NULL,
        urgence ENUM('normal','urgent','tres_urgent') DEFAULT 'normal',
        statut ENUM('en_attente','commande','recue','annulee','urgent','termine','utilise','a_retourner') NOT NULL DEFAULT 'en_attente',
        date_commande DATETIME DEFAULT NULL,
        date_reception DATETIME DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE SET NULL,
        FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id),
        FOREIGN KEY (client_id) REFERENCES clients(id)
    )",

    // Stock
    "stock" => "CREATE TABLE IF NOT EXISTS stock (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barcode VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(50) DEFAULT NULL,
        quantity INT NOT NULL DEFAULT 0,
        price DECIMAL(10,2) DEFAULT 0.00,
        description TEXT DEFAULT NULL,
        date_created DATETIME NOT NULL,
        date_updated DATETIME DEFAULT NULL,
        status ENUM('normal','temporaire','a_retourner') DEFAULT 'normal',
        date_limite_retour DATE DEFAULT NULL,
        motif_retour TEXT DEFAULT NULL
    )",

    // Historique du stock
    "stock_history" => "CREATE TABLE IF NOT EXISTS stock_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        action VARCHAR(20) NOT NULL,
        quantity INT NOT NULL,
        note TEXT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        date_created DATETIME NOT NULL,
        FOREIGN KEY (product_id) REFERENCES stock(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",

    // Colis retour
    "colis_retour" => "CREATE TABLE IF NOT EXISTS colis_retour (
        id INT AUTO_INCREMENT PRIMARY KEY,
        numero_suivi VARCHAR(100) NOT NULL,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_expedition DATETIME DEFAULT NULL,
        statut ENUM('en_preparation','en_expedition','livre') DEFAULT 'en_preparation',
        notes TEXT DEFAULT NULL
    )",

    // Retours
    "retours" => "CREATE TABLE IF NOT EXISTS retours (
        id INT AUTO_INCREMENT PRIMARY KEY,
        produit_id INT NOT NULL,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_limite DATE NOT NULL,
        statut ENUM('en_attente','en_preparation','expedie','livre','a_verifier','termine') DEFAULT 'en_attente',
        numero_suivi VARCHAR(100) DEFAULT NULL,
        montant_rembourse DECIMAL(10,2) DEFAULT NULL,
        montant_rembourse_client DECIMAL(10,2) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        colis_id INT DEFAULT NULL,
        FOREIGN KEY (produit_id) REFERENCES produits(id),
        FOREIGN KEY (colis_id) REFERENCES colis_retour(id) ON DELETE SET NULL
    )",

    // Système de gardiennage
    "gardiennage" => "CREATE TABLE IF NOT EXISTS gardiennage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reparation_id INT NOT NULL,
        date_debut DATE NOT NULL,
        date_derniere_facturation DATE NOT NULL,
        tarif_journalier DECIMAL(10,2) NOT NULL DEFAULT 5.00,
        jours_factures INT NOT NULL DEFAULT 0,
        montant_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        est_actif TINYINT(1) NOT NULL DEFAULT 1,
        date_fin DATE DEFAULT NULL,
        derniere_notification DATE DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE
    )",

    // Notifications de gardiennage
    "gardiennage_notifications" => "CREATE TABLE IF NOT EXISTS gardiennage_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        gardiennage_id INT NOT NULL,
        date_notification TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        type_notification ENUM('sms','email','appel') NOT NULL,
        statut ENUM('envoyé','échec','annulé') NOT NULL,
        message TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (gardiennage_id) REFERENCES gardiennage(id) ON DELETE CASCADE
    )",

    // Paramètres de gardiennage
    "parametres_gardiennage" => "CREATE TABLE IF NOT EXISTS parametres_gardiennage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tarif_premiere_semaine DECIMAL(10,2) NOT NULL DEFAULT 5.00,
        tarif_intermediaire DECIMAL(10,2) NOT NULL DEFAULT 3.00,
        tarif_longue_duree DECIMAL(10,2) NOT NULL DEFAULT 1.00,
        date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )",

    // Système de parrainage - Configuration
    "parrainage_config" => "CREATE TABLE IF NOT EXISTS parrainage_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_filleuls_requis INT NOT NULL DEFAULT 1,
        seuil_reduction_pourcentage DECIMAL(10,2) NOT NULL DEFAULT 100.00,
        reduction_min_pourcentage INT NOT NULL DEFAULT 10,
        reduction_max_pourcentage INT NOT NULL DEFAULT 30,
        actif TINYINT(1) NOT NULL DEFAULT 1,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // Système de parrainage - Relations
    "parrainage_relations" => "CREATE TABLE IF NOT EXISTS parrainage_relations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parrain_id INT NOT NULL,
        filleul_id INT NOT NULL,
        date_parrainage TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parrain_id) REFERENCES clients(id) ON DELETE CASCADE,
        FOREIGN KEY (filleul_id) REFERENCES clients(id) ON DELETE CASCADE,
        UNIQUE KEY parrain_filleul (parrain_id, filleul_id)
    )",

    // Système de parrainage - Réductions
    "parrainage_reductions" => "CREATE TABLE IF NOT EXISTS parrainage_reductions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parrain_id INT NOT NULL,
        montant_depense_filleul DECIMAL(10,2) NOT NULL,
        pourcentage_reduction INT NOT NULL,
        montant_reduction_max DECIMAL(10,2) NOT NULL,
        utilise TINYINT(1) NOT NULL DEFAULT 0,
        reparation_utilisee_id INT DEFAULT NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_utilisation TIMESTAMP DEFAULT NULL,
        FOREIGN KEY (parrain_id) REFERENCES clients(id) ON DELETE CASCADE,
        FOREIGN KEY (reparation_utilisee_id) REFERENCES reparations(id) ON DELETE SET NULL
    )",

    // Système de partenariat - Partenaires
    "partenaires" => "CREATE TABLE IF NOT EXISTS partenaires (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        telephone VARCHAR(20) DEFAULT NULL,
        adresse TEXT DEFAULT NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actif TINYINT(1) DEFAULT 1
    )",

    // Système de partenariat - Soldes
    "soldes_partenaires" => "CREATE TABLE IF NOT EXISTS soldes_partenaires (
        partenaire_id INT PRIMARY KEY,
        solde_actuel DECIMAL(10,2) DEFAULT 0.00,
        derniere_mise_a_jour DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (partenaire_id) REFERENCES partenaires(id) ON DELETE CASCADE
    )",

    // Système de partenariat - Transactions
    "transactions_partenaires" => "CREATE TABLE IF NOT EXISTS transactions_partenaires (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partenaire_id INT NOT NULL,
        type ENUM('AVANCE','REMBOURSEMENT','SERVICE') NOT NULL,
        montant DECIMAL(10,2) NOT NULL,
        description TEXT DEFAULT NULL,
        date_transaction DATETIME DEFAULT CURRENT_TIMESTAMP,
        reference_document VARCHAR(255) DEFAULT NULL,
        statut ENUM('EN_ATTENTE','VALIDÉ','ANNULÉ') DEFAULT 'EN_ATTENTE',
        FOREIGN KEY (partenaire_id) REFERENCES partenaires(id) ON DELETE CASCADE
    )",

    // Système de partenariat - Historique des soldes
    "historique_soldes" => "CREATE TABLE IF NOT EXISTS historique_soldes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partenaire_id INT NOT NULL,
        ancien_solde DECIMAL(10,2) DEFAULT NULL,
        nouveau_solde DECIMAL(10,2) DEFAULT NULL,
        transaction_id INT DEFAULT NULL,
        date_modification DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (partenaire_id) REFERENCES partenaires(id) ON DELETE CASCADE,
        FOREIGN KEY (transaction_id) REFERENCES transactions_partenaires(id) ON DELETE SET NULL
    )",

    // Système de partenariat - Services
    "services_partenaires" => "CREATE TABLE IF NOT EXISTS services_partenaires (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partenaire_id INT NOT NULL,
        description TEXT NOT NULL,
        montant DECIMAL(10,2) NOT NULL,
        date_service DATETIME DEFAULT CURRENT_TIMESTAMP,
        statut ENUM('EN_ATTENTE','VALIDÉ','ANNULÉ') DEFAULT 'EN_ATTENTE',
        FOREIGN KEY (partenaire_id) REFERENCES partenaires(id) ON DELETE CASCADE
    )",

    // Système de partenariat - Pièces avancées
    "pieces_avancees" => "CREATE TABLE IF NOT EXISTS pieces_avancees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        partenaire_id INT NOT NULL,
        piece_id INT NOT NULL,
        quantite INT NOT NULL,
        prix_unitaire DECIMAL(10,2) NOT NULL,
        date_avance DATETIME DEFAULT CURRENT_TIMESTAMP,
        statut ENUM('EN_ATTENTE','VALIDÉ','REMBOURSÉ','ANNULÉ') DEFAULT 'EN_ATTENTE',
        FOREIGN KEY (partenaire_id) REFERENCES partenaires(id) ON DELETE CASCADE,
        FOREIGN KEY (piece_id) REFERENCES produits(id) ON DELETE CASCADE
    )",

    // Rachat d'appareils
    "rachat_appareils" => "CREATE TABLE IF NOT EXISTS rachat_appareils (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        type_appareil VARCHAR(255) NOT NULL,
        photo_identite VARCHAR(255) NOT NULL,
        photo_appareil VARCHAR(255) NOT NULL,
        signature TEXT NOT NULL,
        client_photo VARCHAR(255) DEFAULT NULL,
        date_rachat DATETIME DEFAULT CURRENT_TIMESTAMP,
        sin VARCHAR(100) DEFAULT NULL,
        fonctionnel TINYINT(1) DEFAULT 0,
        prix DECIMAL(10,2) DEFAULT NULL,
        modele VARCHAR(255) DEFAULT NULL,
        numero_serie VARCHAR(100) DEFAULT NULL,
        created_by INT DEFAULT NULL,
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )",

    // Système de messagerie - Conversations
    "conversations" => "CREATE TABLE IF NOT EXISTS conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(255) NOT NULL,
        type ENUM('direct','groupe','annonce') NOT NULL DEFAULT 'direct',
        created_by INT DEFAULT NULL,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
        derniere_activite DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )",

    // Système de messagerie - Participants
    "conversation_participants" => "CREATE TABLE IF NOT EXISTS conversation_participants (
        conversation_id INT NOT NULL,
        user_id INT NOT NULL,
        role ENUM('admin','membre','lecteur') NOT NULL DEFAULT 'membre',
        date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_derniere_lecture DATETIME DEFAULT NULL,
        est_favoris TINYINT(1) DEFAULT 0,
        est_archive TINYINT(1) DEFAULT 0,
        notification_mute TINYINT(1) DEFAULT 0,
        PRIMARY KEY (conversation_id, user_id),
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Système de messagerie - Messages
    "messages" => "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_id INT DEFAULT NULL,
        contenu TEXT DEFAULT NULL,
        type ENUM('text','file','image','system','info') NOT NULL DEFAULT 'text',
        date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
        est_supprime TINYINT(1) DEFAULT 0,
        est_modifie TINYINT(1) DEFAULT 0,
        date_modification DATETIME DEFAULT NULL,
        est_important TINYINT(1) DEFAULT 0,
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL
    )",

    // Système de messagerie - Pièces jointes
    "message_attachments" => "CREATE TABLE IF NOT EXISTS message_attachments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        file_size INT NOT NULL,
        thumbnail_path VARCHAR(255) DEFAULT NULL,
        est_image TINYINT(1) DEFAULT 0,
        date_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
    )",

    // Système de messagerie - Lectures
    "message_reads" => "CREATE TABLE IF NOT EXISTS message_reads (
        message_id INT NOT NULL,
        user_id INT NOT NULL,
        date_lecture DATETIME DEFAULT CURRENT_TIMESTAMP,
        metadata LONGTEXT DEFAULT NULL,
        PRIMARY KEY (message_id, user_id),
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Système de messagerie - Réactions
    "message_reactions" => "CREATE TABLE IF NOT EXISTS message_reactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        user_id INT NOT NULL,
        reaction VARCHAR(20) NOT NULL,
        date_reaction DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY message_user_reaction (message_id, user_id, reaction)
    )",

    // Système de messagerie - Réponses
    "message_replies" => "CREATE TABLE IF NOT EXISTS message_replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        reply_to_id INT NOT NULL,
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
        FOREIGN KEY (reply_to_id) REFERENCES messages(id) ON DELETE CASCADE
    )",

    // Système de messagerie - Lecture des annonces
    "lecture_annonces" => "CREATE TABLE IF NOT EXISTS lecture_annonces (
        message_id INT NOT NULL,
        user_id INT NOT NULL,
        date_lecture DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (message_id, user_id),
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Système de messagerie - Confirmations de lecture
    "confirmations_lecture" => "CREATE TABLE IF NOT EXISTS confirmations_lecture (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        employe_id INT NOT NULL,
        date_confirmation DATETIME DEFAULT NULL,
        rappel_envoye TINYINT(1) NOT NULL DEFAULT 0,
        date_rappel DATETIME DEFAULT NULL,
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
        FOREIGN KEY (employe_id) REFERENCES employes(id) ON DELETE CASCADE,
        UNIQUE KEY unique_confirmation (message_id, employe_id)
    )",

    // Système de messagerie - Statut de frappe
    "typing_status" => "CREATE TABLE IF NOT EXISTS typing_status (
        user_id INT NOT NULL,
        conversation_id INT NOT NULL,
        timestamp DATETIME NOT NULL,
        PRIMARY KEY (user_id, conversation_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
    )",

    // Base de connaissances - Catégories
    "kb_categories" => "CREATE TABLE IF NOT EXISTS kb_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50) DEFAULT 'fas fa-folder',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )",

    // Base de connaissances - Tags
    "kb_tags" => "CREATE TABLE IF NOT EXISTS kb_tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY name (name)
    )",

    // Base de connaissances - Articles
    "kb_articles" => "CREATE TABLE IF NOT EXISTS kb_articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content LONGTEXT NOT NULL,
        category_id INT NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        views INT NOT NULL DEFAULT 0,
        FOREIGN KEY (category_id) REFERENCES kb_categories(id) ON DELETE CASCADE
    )",

    // Base de connaissances - Tags des articles
    "kb_article_tags" => "CREATE TABLE IF NOT EXISTS kb_article_tags (
        article_id INT NOT NULL,
        tag_id INT NOT NULL,
        PRIMARY KEY (article_id, tag_id),
        FOREIGN KEY (article_id) REFERENCES kb_articles(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES kb_tags(id) ON DELETE CASCADE
    )",

    // Base de connaissances - Évaluations
    "kb_article_ratings" => "CREATE TABLE IF NOT EXISTS kb_article_ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        article_id INT NOT NULL,
        user_id INT NOT NULL,
        is_helpful TINYINT(1) NOT NULL,
        rated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY article_user (article_id, user_id),
        FOREIGN KEY (article_id) REFERENCES kb_articles(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Système de tâches
    "taches" => "CREATE TABLE IF NOT EXISTS taches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titre VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        priorite ENUM('basse','moyenne','haute','urgente') DEFAULT 'moyenne',
        statut ENUM('a_faire','en_cours','termine','annule') DEFAULT 'a_faire',
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        date_limite DATE DEFAULT NULL,
        date_fin TIMESTAMP DEFAULT NULL,
        employe_id INT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        FOREIGN KEY (employe_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )",

    // Commentaires sur les tâches
    "commentaires_tache" => "CREATE TABLE IF NOT EXISTS commentaires_tache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tache_id INT NOT NULL,
        user_id INT NOT NULL,
        commentaire TEXT NOT NULL,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_system TINYINT(1) NOT NULL DEFAULT 0,
        FOREIGN KEY (tache_id) REFERENCES taches(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",

    // Système d'aide
    "tasks" => "CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        status ENUM('en_attente','en_cours','termine','aide_necessaire') DEFAULT 'en_attente',
        priority ENUM('basse','moyenne','haute','urgente') DEFAULT 'moyenne',
        assigned_to INT DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        due_date DATE DEFAULT NULL,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )",

    // Demandes d'aide
    "help_requests" => "CREATE TABLE IF NOT EXISTS help_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        status ENUM('en_attente','resolu','en_cours') DEFAULT 'en_attente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    // Campagnes SMS
    "sms_campaigns" => "CREATE TABLE IF NOT EXISTS sms_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        nb_destinataires INT NOT NULL DEFAULT 0,
        nb_envoyes INT NOT NULL DEFAULT 0,
        nb_echecs INT NOT NULL DEFAULT 0,
        user_id INT DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )",

    // Détails des campagnes SMS
    "sms_campaign_details" => "CREATE TABLE IF NOT EXISTS sms_campaign_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campaign_id INT NOT NULL,
        client_id INT NOT NULL,
        telephone VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        statut ENUM('envoyé','échec') NOT NULL DEFAULT 'envoyé',
        date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (campaign_id) REFERENCES sms_campaigns(id) ON DELETE CASCADE,
        FOREIGN KEY (client_id) REFERENCES clients(id)
    )",

    // Variables de template SMS
    "sms_template_variables" => "CREATE TABLE IF NOT EXISTS sms_template_variables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(50) NOT NULL,
        description VARCHAR(255) NOT NULL,
        exemple VARCHAR(100) NOT NULL,
        UNIQUE KEY nom (nom)
    )",

    // SMS Template principal
    "sms_template" => "CREATE TABLE IF NOT EXISTS sms_template (
        id INT AUTO_INCREMENT PRIMARY KEY,
        statut_id INT DEFAULT NULL,
        message TEXT DEFAULT NULL,
        FOREIGN KEY (statut_id) REFERENCES statuts(id) ON DELETE CASCADE
    )",

    // Réparation SMS
    "reparation_sms" => "CREATE TABLE IF NOT EXISTS reparation_sms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reparation_id INT NOT NULL,
        template_id INT NOT NULL,
        telephone VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        statut_id INT DEFAULT NULL,
        FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE,
        FOREIGN KEY (template_id) REFERENCES sms_templates(id),
        FOREIGN KEY (statut_id) REFERENCES statuts(id) ON DELETE SET NULL
    )",

    // Statuts de réparation
    "statuts_reparation" => "CREATE TABLE IF NOT EXISTS statuts_reparation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        code VARCHAR(50) NOT NULL UNIQUE,
        nom VARCHAR(100) NOT NULL,
        categorie ENUM('nouvelle','en_cours','en_attente','termine','annule') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )"
];

// Default insert for statut_categories after table creation
$default_categories = [
    ["Nouvelles interventions", "nouvelle", "#3498db", 1],
    ["En cours", "en_cours", "#f39c12", 2],
    ["En attente", "en_attente", "#e74c3c", 3],
    ["Terminées", "termine", "#2ecc71", 4],
    ["Annulées", "annule", "#95a5a6", 5]
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
                        ["Nouvelles interventions", "nouvelle", "#3498db", 1],
                        ["En cours", "en_cours", "#f39c12", 2],
                        ["En attente", "en_attente", "#e74c3c", 3],
                        ["Terminées", "termine", "#2ecc71", 4],
                        ["Annulées", "annule", "#95a5a6", 5]
                    ];

                    try {
                        $insert_stmt = $shop_db->prepare("INSERT INTO statut_categories (nom, code, couleur, ordre) VALUES (?, ?, ?, ?)");
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
                        ["nom_magasin", $shop['name'], "Nom du magasin"],
                        ["adresse_magasin", $shop['address'] ?? '', "Adresse du magasin"],
                        ["telephone_magasin", $shop['phone'] ?? '', "Téléphone du magasin"],
                        ["email_magasin", $shop['email'] ?? '', "Email du magasin"],
                        ["devise", "€", "Symbole de la devise"],
                        ["tva", "20", "Taux de TVA en pourcentage"]
                    ];

                    try {
                        $insert_stmt = $shop_db->prepare("INSERT INTO parametres (cle, valeur, description) VALUES (?, ?, ?)");
                        foreach ($default_params as $param) {
                            $insert_stmt->execute($param);
                        }
                        echo "<div class='alert alert-info'><i class='fas fa-cogs me-2'></i> Paramètres par défaut créés.</div>";
                    } catch (PDOException $e) {
                        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i> 
                                Impossible de créer les paramètres par défaut: " . $e->getMessage() . "</div>";
                    }

                    // Création des catégories de base de connaissances par défaut
                    $kb_categories = [
                        ["Procédures", "fas fa-clipboard-list"],
                        ["Problèmes fréquents", "fas fa-exclamation-triangle"],
                        ["Tutoriels", "fas fa-book"],
                        ["Réparations", "fas fa-tools"]
                    ];

                    try {
                        $insert_stmt = $shop_db->prepare("INSERT INTO kb_categories (name, icon) VALUES (?, ?)");
                        foreach ($kb_categories as $category) {
                            $insert_stmt->execute($category);
                        }
                        echo "<div class='alert alert-info'><i class='fas fa-folder me-2'></i> Catégories de base de connaissances créées.</div>";
                    } catch (PDOException $e) {
                        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i> 
                                Impossible de créer les catégories de base de connaissances: " . $e->getMessage() . "</div>";
                    }

                    // Création des variables de template SMS
                    $sms_variables = [
                        ["CLIENT_NOM", "Nom du client", "Dupont"],
                        ["CLIENT_PRENOM", "Prénom du client", "Jean"],
                        ["APPAREIL", "Type d'appareil", "iPhone X"],
                        ["STATUS", "Statut de la réparation", "En cours"],
                        ["DATE", "Date du jour", date('d/m/Y')],
                        ["PRIX", "Prix de la réparation", "89.00€"]
                    ];

                    try {
                        $insert_stmt = $shop_db->prepare("INSERT INTO sms_template_variables (nom, description, exemple) VALUES (?, ?, ?)");
                        foreach ($sms_variables as $variable) {
                            $insert_stmt->execute($variable);
                        }
                        echo "<div class='alert alert-info'><i class='fas fa-sms me-2'></i> Variables SMS créées.</div>";
                    } catch (PDOException $e) {
                        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i> 
                                Impossible de créer les variables SMS: " . $e->getMessage() . "</div>";
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