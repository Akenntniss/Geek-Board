<?php
// Titre de la page
$page_title = "Gestion du gardiennage";

// Inclusion des fichiers nécessaires
require_once BASE_PATH . '/includes/functions.php';

// Vérification des droits d'accès (optionnel, car accessible par tous les utilisateurs)
// if (!hasPermission('view_gardiennage')) {
//     set_message("Vous n'avez pas les droits pour accéder à cette page.", "danger");
//     header('Location: index.php');
//     exit();
// }

// Connexion à la base de données
$shop_pdo = getShopDBConnection();

// Traitement des actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'envoyer_rappel':
            if (isset($_POST['gardiennage_id'])) {
                $gardiennage_id = intval($_POST['gardiennage_id']);
                $resultat = envoyer_rappel_gardiennage($gardiennage_id);
                if ($resultat['success']) {
                    set_message("Rappel envoyé avec succès !", "success");
                } else {
                    set_message("Erreur lors de l'envoi du rappel : " . $resultat['message'], "danger");
                }
            }
            break;
            
        case 'sauvegarder_template':
            // Récupérer les données du template
            $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
            $template_nom = isset($_POST['template_nom']) ? trim($_POST['template_nom']) : '';
            $template_contenu = isset($_POST['template_contenu']) ? trim($_POST['template_contenu']) : '';
            
            if (empty($template_nom) || empty($template_contenu)) {
                set_message("Le nom et le contenu du template sont obligatoires", "danger");
                break;
            }
            
            try {
                if ($template_id > 0) {
                    // Mise à jour d'un template existant
                    $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("
                        UPDATE sms_templates 
                        SET nom = ?, contenu = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$template_nom, $template_contenu, $template_id]);
                    
                    set_message("Le template a été mis à jour avec succès", "success");
                } else {
                    // Création d'un nouveau template
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO sms_templates (nom, contenu, est_actif, created_at)
                        VALUES (?, ?, 1, NOW())
                    ");
                    $stmt->execute([$template_nom, $template_contenu]);
                    
                    set_message("Le nouveau template a été créé avec succès", "success");
                }
            } catch (PDOException $e) {
                set_message("Erreur lors de la sauvegarde du template : " . $e->getMessage(), "danger");
            }
            break;
            
        case 'envoyer_sms_groupe':
            // Récupérer le message SMS
            $message = isset($_POST['message_groupe']) ? trim($_POST['message_groupe']) : '';
            
            if (empty($message)) {
                set_message("Le message ne peut pas être vide", "danger");
                break;
            }
            
            // Récupérer tous les gardiennages actifs
            $stmt = $shop_pdo->prepare("
                SELECT g.id, c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone,
                       r.marque, r.modele, r.type_appareil,
                       DATEDIFF(CURRENT_DATE, g.date_debut) as jours_gardiennage
                FROM gardiennage g
                JOIN reparations r ON g.reparation_id = r.id
                JOIN clients c ON r.client_id = c.id
                WHERE g.est_actif = TRUE
            ");
            $stmt->execute();
            $gardiennages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($gardiennages)) {
                set_message("Aucun gardiennage actif trouvé", "warning");
                break;
            }
            
            // Compter les SMS réussis et échoués
            $succes = 0;
            $echecs = 0;
            
            foreach ($gardiennages as $gardiennage) {
                // Variables de remplacement pour personnaliser le message
                $variables = [
                    '[CLIENT_NOM]' => $gardiennage['client_nom'],
                    '[CLIENT_PRENOM]' => $gardiennage['client_prenom'],
                    '[APPAREIL_MARQUE]' => $gardiennage['marque'],
                    '[APPAREIL_MODELE]' => $gardiennage['modele'],
                    '[JOURS_GARDIENNAGE]' => $gardiennage['jours_gardiennage'],
                    '[DATE_ACTUELLE]' => date('d/m/Y')
                ];
                
                // Remplacer les variables dans le message
                $message_personnalise = $message;
                foreach ($variables as $var => $valeur) {
                    $message_personnalise = str_replace($var, $valeur, $message_personnalise);
                }
                
                // Formater le numéro de téléphone
                $telephone = $gardiennage['client_telephone'];
                if (!preg_match('/^\+[0-9]{10,15}$/', $telephone)) {
                    if (preg_match('/^0[6-7][0-9]{8}$/', $telephone)) {
                        $telephone = '+33' . substr($telephone, 1);
                    }
                }
                
                if (empty($telephone)) {
                    $echecs++;
                    continue;
                }
                
                // Envoyer le SMS
                if (function_exists('send_sms')) {
                    $sms_result = send_sms($telephone, $message_personnalise);
                    
                    if ($sms_result['success']) {
                        // Enregistrer l'envoi du SMS dans la base de données
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO gardiennage_notifications (
                                gardiennage_id, type_notification, statut, message
                            ) VALUES (?, 'sms_groupe', 'envoyé', ?)
                        ");
                        $stmt->execute([$gardiennage['id'], $message_personnalise]);
                        
                        // Mettre à jour la date de dernière notification
                        $stmt = $shop_pdo->prepare("UPDATE gardiennage SET derniere_notification = CURRENT_DATE WHERE id = ?");
                        $stmt->execute([$gardiennage['id']]);
                        
                        $succes++;
                    } else {
                        // Enregistrer l'échec dans la base de données
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO gardiennage_notifications (
                                gardiennage_id, type_notification, statut, message
                            ) VALUES (?, 'sms_groupe', 'échec', ?)
                        ");
                        $stmt->execute([$gardiennage['id'], $message_personnalise]);
                        
                        $echecs++;
                    }
                } else {
                    $echecs++;
                }
            }
            
            if ($succes > 0) {
                $message_final = "SMS envoyé à $succes client(s)";
                if ($echecs > 0) {
                    $message_final .= " ($echecs échec(s))";
                }
                set_message($message_final, "success");
            } else {
                set_message("Aucun SMS n'a pu être envoyé.", "danger");
            }
            break;
            
        case 'terminer_gardiennage':
            if (isset($_POST['gardiennage_id'])) {
                $gardiennage_id = intval($_POST['gardiennage_id']);
                $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
                $resultat = terminer_gardiennage($gardiennage_id, $notes);
                if ($resultat['success']) {
                    set_message("Gardiennage terminé avec succès !", "success");
                } else {
                    set_message("Erreur lors de la clôture du gardiennage : " . $resultat['message'], "danger");
                }
            }
            break;
            
        case 'modifier_parametres':
            // Récupérer les tarifs depuis le formulaire
            $tarif_premiere_semaine = isset($_POST['tarif_premiere_semaine']) ? floatval($_POST['tarif_premiere_semaine']) : 5;
            $tarif_intermediaire = isset($_POST['tarif_intermediaire']) ? floatval($_POST['tarif_intermediaire']) : 3;
            $tarif_longue_duree = isset($_POST['tarif_longue_duree']) ? floatval($_POST['tarif_longue_duree']) : 1;
            
            // Mettre à jour les paramètres dans la base de données
            try {
                // Vérifier si les paramètres existent déjà
                $stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM parametres_gardiennage WHERE id = 1");
                $stmt->execute();
                $paramExistent = ($stmt->fetchColumn() > 0);
                
                if ($paramExistent) {
                    // Mise à jour
                    $stmt = $shop_pdo->prepare("
                        UPDATE parametres_gardiennage 
                        SET tarif_premiere_semaine = ?, tarif_intermediaire = ?, tarif_longue_duree = ?, date_modification = NOW()
                        WHERE id = 1
                    ");
                } else {
                    // Création
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO parametres_gardiennage (tarif_premiere_semaine, tarif_intermediaire, tarif_longue_duree, date_modification)
                        VALUES (?, ?, ?, NOW())
                    ");
                }
                
                $stmt->execute([$tarif_premiere_semaine, $tarif_intermediaire, $tarif_longue_duree]);
                
                // Gérer le template SMS s'il a été modifié
                if (isset($_POST['sms_template_contenu'])) {
                    $sms_contenu = $_POST['sms_template_contenu'];
                    $template_id = isset($_POST['sms_template_id']) ? intval($_POST['sms_template_id']) : 0;
                    
                    if ($template_id > 0) {
                        // Mise à jour d'un template existant
                        $stmt = $shop_pdo->prepare("
                            UPDATE sms_templates 
                            SET contenu = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$sms_contenu, $template_id]);
                    } else {
                        // Création d'un nouveau template
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO sms_templates (nom, contenu, est_actif, created_at)
                            VALUES ('Rappel gardiennage', ?, 1, NOW())
                        ");
                        $stmt->execute([$sms_contenu]);
                    }
                }
                
                set_message("Les paramètres de gardiennage ont été mis à jour avec succès !", "success");
            } catch (PDOException $e) {
                set_message("Erreur lors de la mise à jour des paramètres : " . $e->getMessage(), "danger");
            }
            break;
    }
}

// Récupération des paramètres de gardiennage
try {
    $stmt = $shop_pdo->prepare("SELECT * FROM parametres_gardiennage WHERE id = 1");
    $stmt->execute();
    $parametres_gardiennage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Valeurs par défaut si les paramètres n'existent pas encore
    if (!$parametres_gardiennage) {
        $parametres_gardiennage = [
            'tarif_premiere_semaine' => 5,
            'tarif_intermediaire' => 3,
            'tarif_longue_duree' => 1
        ];
    }
} catch (PDOException $e) {
    // Si la table n'existe pas encore, on utilise les valeurs par défaut
    $parametres_gardiennage = [
        'tarif_premiere_semaine' => 5,
        'tarif_intermediaire' => 3,
        'tarif_longue_duree' => 1
    ];
}

// Récupération des gardiennages en cours
$stmt = $shop_pdo->prepare("
    SELECT g.*, r.statut as reparation_statut, r.type_appareil, r.marque, r.modele, 
           r.prix_reparation, r.prix as prix_appareil,
           c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone,
           DATEDIFF(CURRENT_DATE, g.date_debut) as jours_totaux,
           DATEDIFF(CURRENT_DATE, g.date_derniere_facturation) as jours_non_factures
    FROM gardiennage g
    JOIN reparations r ON g.reparation_id = r.id
    JOIN clients c ON r.client_id = c.id
    WHERE g.est_actif = TRUE
    ORDER BY g.date_debut ASC
");
$stmt->execute();
$gardiennages_actifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des gardiennages terminés
$stmt = $shop_pdo->prepare("
    SELECT g.*, r.statut as reparation_statut, r.type_appareil, r.marque, r.modele, 
           r.prix_reparation, r.prix as prix_appareil,
           c.nom as client_nom, c.prenom as client_prenom, c.telephone as client_telephone,
           DATEDIFF(g.date_fin, g.date_debut) as jours_totaux
    FROM gardiennage g
    JOIN reparations r ON g.reparation_id = r.id
    JOIN clients c ON r.client_id = c.id
    WHERE g.est_actif = FALSE
    ORDER BY g.date_fin DESC
    LIMIT 20
");
$stmt->execute();
$gardiennages_termines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul des montants totaux
$total_actif = 0;
foreach ($gardiennages_actifs as $gardiennage) {
    $total_actif += $gardiennage['montant_total'];
    // Ajouter les jours non facturés
    $montant_non_facture = $gardiennage['jours_non_factures'] * $gardiennage['tarif_journalier'];
    $total_actif += $montant_non_facture;
}

?>

<div class="container gardiennage-container">
    <!-- En-tête avec titre principal -->
    <div class="page-header mb-4 d-flex justify-content-between align-items-center">
        <h1 class="display-5 fw-bold text-primary">
            <i class="fas fa-warehouse me-2"></i> Gestion du gardiennage
        </h1>
        <div>
            <button class="btn btn-outline-primary me-2" title="Envoyer un SMS à tous les clients" onclick="ouvrirModalSmsGroupe()">
                <i class="fas fa-sms me-2"></i> SMS Groupé
            </button>
            <button class="btn btn-light rounded-circle shadow-sm p-2" title="Paramètres de gardiennage" onclick="ouvrirModalParametres()">
                <i class="fas fa-cog fs-5"></i>
            </button>
        </div>
        <div class="border-bottom mt-2"></div>
    </div>

    <!-- Cartes statistiques en rangée (format portrait) -->
    <div class="row mb-4 stat-cards">
        <div class="col-lg-5 col-md-6 mb-3">
            <div class="card stat-card bg-gradient-primary text-white h-100">
                <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
                    <h5 class="fs-5 fw-normal mb-3">Gardiennages actifs</h5>
                    <div class="display-1 fw-bold"><?= count($gardiennages_actifs) ?></div>
                    <div class="stat-icon-bg">
                        <i class="fas fa-warehouse"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7 col-md-6 mb-3">
            <div class="card stat-card bg-gradient-success text-white h-100">
                <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
                    <h5 class="fs-5 fw-normal mb-3">Montant total à facturer</h5>
                    <div class="display-3 fw-bold"><?= number_format($total_actif, 2, ',', ' ') ?> €</div>
                    <div class="stat-icon-bg">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation par onglets (pleine largeur) -->
    <div class="mb-4">
        <ul class="nav nav-tabs custom-tabs" id="gardiennageTab" role="tablist">
            <li class="nav-item flex-fill text-center" role="presentation">
                <button class="nav-link active w-100" id="actifs-tab" data-bs-toggle="tab" data-bs-target="#actifs" type="button" role="tab" aria-controls="actifs" aria-selected="true">
                    <i class="fas fa-clipboard-list me-2"></i>Gardiennages actifs
                </button>
            </li>
            <li class="nav-item flex-fill text-center" role="presentation">
                <button class="nav-link w-100" id="termines-tab" data-bs-toggle="tab" data-bs-target="#termines" type="button" role="tab" aria-controls="termines" aria-selected="false">
                    <i class="fas fa-check-circle me-2"></i>Gardiennages terminés
                </button>
            </li>
        </ul>
    </div>
    
    <!-- Contenu des onglets -->
    <div class="tab-content" id="gardiennageTabContent">
        <!-- Barre de recherche commune aux deux onglets -->
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Rechercher un client, un appareil, une marque..." aria-label="Rechercher">
                            <button class="btn btn-outline-primary" type="button" onclick="clearSearch()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="text-muted small">
                            <span id="resultCount">0</span> résultat(s) trouvé(s)
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gardiennages actifs -->
        <div class="tab-pane fade show active" id="actifs" role="tabpanel" aria-labelledby="actifs-tab">
            <?php if (empty($gardiennages_actifs)): ?>
                <div class="alert alert-info d-flex align-items-center">
                    <i class="fas fa-info-circle fs-4 me-3"></i>
                    <div>Aucun gardiennage actif pour le moment.</div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover custom-table mb-0" id="tableActifs">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Appareil</th>
                                        <th class="text-center">Début</th>
                                        <th class="text-center">Jours</th>
                                        <th class="text-center">Total Gardiennage</th>
                                        <th class="text-center">Total Réparation</th>
                                        <th class="text-center">Total à Payer</th>
                                        <th class="text-center">Dernier Rappel</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gardiennages_actifs as $gardiennage): ?>
                                        <?php 
                                            // Calcul du montant de gardiennage avec les tarifs progressifs
                                            $montant_total_gardiennage = calculer_montant_gardiennage($gardiennage['jours_totaux'], $parametres_gardiennage);
                                            $prix_reparation = !empty($gardiennage['prix_reparation']) ? floatval($gardiennage['prix_reparation']) : 0;
                                            $total_a_payer = $montant_total_gardiennage + $prix_reparation;
                                            
                                            $classe_alerte = '';
                                            // Si plus de 14 jours sans rappel, mettre en rouge
                                            if ($gardiennage['derniere_notification'] === null || 
                                                dateDiffInDays(new DateTime($gardiennage['derniere_notification']), new DateTime()) > 14) {
                                                $classe_alerte = 'table-danger';
                                            }
                                            // Si plus de 7 jours sans rappel, mettre en orange
                                            else if (dateDiffInDays(new DateTime($gardiennage['derniere_notification']), new DateTime()) > 7) {
                                                $classe_alerte = 'table-warning';
                                            }
                                        ?>
                                        <tr class="<?= $classe_alerte ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle">
                                                        <?= strtoupper(substr($gardiennage['client_prenom'], 0, 1) . substr($gardiennage['client_nom'], 0, 1)) ?>
                                                    </div>
                                                    <div class="ms-2">
                                                        <div class="fw-semibold"><?= htmlspecialchars($gardiennage['client_prenom'] . ' ' . $gardiennage['client_nom']) ?></div>
                                                        <small class="text-muted"><?= formatPhoneNumber($gardiennage['client_telephone']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="device-icon">
                                                        <i class="fas fa-<?= $gardiennage['type_appareil'] === 'Trottinette' ? 'bolt' : 'mobile-alt' ?>"></i>
                                                    </div>
                                                    <div class="ms-2">
                                                        <div class="fw-semibold"><?= htmlspecialchars($gardiennage['type_appareil']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($gardiennage['marque'] . ' ' . $gardiennage['modele']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center"><?= formatDateFr($gardiennage['date_debut']) ?></td>
                                            <td class="text-center">
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="badge rounded-pill bg-primary px-3 py-2 mb-1"><?= $gardiennage['jours_totaux'] ?> jours</span>
                                                    <?php if ($gardiennage['jours_non_factures'] > 0): ?>
                                                        <span class="badge rounded-pill bg-warning px-3 py-2"><?= $gardiennage['jours_non_factures'] ?> non facturés</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-center fw-bold"><?= number_format($montant_total_gardiennage, 2, ',', ' ') ?> €</td>
                                            <td class="text-center"><?= number_format($prix_reparation, 2, ',', ' ') ?> €</td>
                                            <td class="text-center fw-bold">
                                                <span class="total-a-payer"><?= number_format($total_a_payer, 2, ',', ' ') ?> €</span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($gardiennage['derniere_notification']): ?>
                                                    <?= formatDateFr($gardiennage['derniere_notification']) ?>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill bg-danger px-3 py-2">Jamais</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <a href="index.php?page=reparations&open_modal=<?= $gardiennage['reparation_id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir la réparation">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-warning" title="Envoyer un rappel" 
                                                            onclick="confirmerRappel(<?= $gardiennage['id'] ?>, '<?= addslashes($gardiennage['client_prenom'] . ' ' . $gardiennage['client_nom']) ?>')">
                                                        <i class="fas fa-bell"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-success" title="Terminer le gardiennage" 
                                                            onclick="terminerGardiennage(<?= $gardiennage['id'] ?>, '<?= addslashes($gardiennage['client_prenom'] . ' ' . $gardiennage['client_nom']) ?>', <?= $total_a_payer ?>, <?= $montant_total_gardiennage ?>, <?= $prix_reparation ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Gardiennages terminés -->
        <div class="tab-pane fade" id="termines" role="tabpanel" aria-labelledby="termines-tab">
            <?php if (empty($gardiennages_termines)): ?>
                <div class="alert alert-info d-flex align-items-center">
                    <i class="fas fa-info-circle fs-4 me-3"></i>
                    <div>Aucun gardiennage terminé.</div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover custom-table mb-0" id="tableTermines">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Appareil</th>
                                        <th>Période</th>
                                        <th class="text-center">Durée</th>
                                        <th class="text-center">Total Gardiennage</th>
                                        <th class="text-center">Total Réparation</th>
                                        <th class="text-center">Total Payé</th>
                                        <th class="text-center">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($gardiennages_termines as $gardiennage): ?>
                                        <?php
                                            $prix_reparation = !empty($gardiennage['prix_reparation']) ? floatval($gardiennage['prix_reparation']) : 0;
                                            $total_paye = $gardiennage['montant_total'] + $prix_reparation;
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle">
                                                        <?= strtoupper(substr($gardiennage['client_prenom'], 0, 1) . substr($gardiennage['client_nom'], 0, 1)) ?>
                                                    </div>
                                                    <div class="ms-2">
                                                        <div class="fw-semibold"><?= htmlspecialchars($gardiennage['client_prenom'] . ' ' . $gardiennage['client_nom']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="device-icon">
                                                        <i class="fas fa-<?= $gardiennage['type_appareil'] === 'Trottinette' ? 'bolt' : 'mobile-alt' ?>"></i>
                                                    </div>
                                                    <div class="ms-2">
                                                        <div class="fw-semibold"><?= htmlspecialchars($gardiennage['type_appareil']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($gardiennage['marque'] . ' ' . $gardiennage['modele']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="date-range">
                                                    <div class="date-start">
                                                        <i class="fas fa-calendar-day me-1"></i> <?= formatDateFr($gardiennage['date_debut']) ?>
                                                    </div>
                                                    <div class="date-arrow">
                                                        <i class="fas fa-arrow-down text-muted"></i>
                                                    </div>
                                                    <div class="date-end">
                                                        <i class="fas fa-calendar-check me-1"></i> <?= formatDateFr($gardiennage['date_fin']) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill bg-secondary px-3 py-2"><?= $gardiennage['jours_totaux'] ?> jours</span>
                                            </td>
                                            <td class="text-center fw-bold"><?= number_format($gardiennage['montant_total'], 2, ',', ' ') ?> €</td>
                                            <td class="text-center"><?= number_format($prix_reparation, 2, ',', ' ') ?> €</td>
                                            <td class="text-center">
                                                <span class="total-paye"><?= number_format($total_paye, 2, ',', ' ') ?> €</span>
                                            </td>
                                            <td class="text-center">
                                                <?php if (!empty($gardiennage['notes'])): ?>
                                                    <button type="button" class="btn btn-sm btn-soft-info rounded-pill px-3" title="Voir les notes" 
                                                            onclick="afficherNotes('<?= addslashes(htmlspecialchars($gardiennage['notes'])) ?>')">
                                                        <i class="fas fa-sticky-note me-1"></i> Notes
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted">Aucune note</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal pour envoyer un rappel -->
<div class="modal fade" id="rappelModal" tabindex="-1" aria-labelledby="rappelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rappelModalLabel">Envoyer un rappel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=gardiennage" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="envoyer_rappel">
                    <input type="hidden" name="gardiennage_id" id="rappel_gardiennage_id">
                    
                    <p>Vous êtes sur le point d'envoyer un rappel SMS à <strong id="rappel_client_nom"></strong> concernant le gardiennage de sa trottinette.</p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Un SMS sera envoyé au client pour lui rappeler de venir récupérer sa trottinette et l'informer du montant du gardiennage.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Envoyer le rappel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour terminer un gardiennage -->
<div class="modal fade" id="terminerModal" tabindex="-1" aria-labelledby="terminerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="terminerModalLabel">Terminer le gardiennage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=gardiennage" method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="terminer_gardiennage">
                    <input type="hidden" name="gardiennage_id" id="terminer_gardiennage_id">
                    
                    <p>Vous êtes sur le point de terminer le gardiennage pour <strong id="terminer_client_nom"></strong>.</p>
                    
                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading mb-2"><i class="fas fa-info-circle me-2"></i> Détails du paiement</h6>
                        <div class="row mt-3">
                            <div class="col-7">Frais de gardiennage:</div>
                            <div class="col-5 text-end" id="gardiennage_montant">0,00 €</div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-7">Frais de réparation:</div>
                            <div class="col-5 text-end" id="reparation_montant">0,00 €</div>
                        </div>
                        <div class="row mt-2 border-top pt-2">
                            <div class="col-7 fw-bold">Total à payer:</div>
                            <div class="col-5 text-end fw-bold" id="terminer_montant">0,00 €</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (optionnel)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Ajouter des notes sur le gardiennage (paiement, etc.)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Terminer et facturer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour afficher les notes -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-labelledby="notesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notesModalLabel">Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="notes_content" class="p-3 border rounded"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour paramètres de gardiennage -->
<div class="modal fade" id="parametresModal" tabindex="-1" aria-labelledby="parametresModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="parametresModalLabel"><i class="fas fa-cog me-2"></i>Paramètres de gardiennage</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=gardiennage" method="post" id="formParametres">
                <input type="hidden" name="action" value="modifier_parametres">
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="parametresTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tarifs-tab" data-bs-toggle="tab" data-bs-target="#tarifs" type="button" role="tab" aria-controls="tarifs" aria-selected="true">
                                <i class="fas fa-euro-sign me-2"></i>Tarifs
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms" type="button" role="tab" aria-controls="sms" aria-selected="false">
                                <i class="fas fa-sms me-2"></i>Template SMS
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="parametresTabsContent">
                        <!-- Onglet Tarifs -->
                        <div class="tab-pane fade show active" id="tarifs" role="tabpanel" aria-labelledby="tarifs-tab">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Configurez les tarifs journaliers selon la durée du gardiennage.
                            </div>
                            
                            <div class="card mb-3 border-primary shadow-sm">
                                <div class="card-header bg-light">
                                    <div class="d-flex align-items-center">
                                        <div class="tarif-icon bg-primary text-white me-3">
                                            <i class="fas fa-calendar-week"></i>
                                        </div>
                                        <h6 class="mb-0">Première semaine (1 à 7 jours)</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="tarif_premiere_semaine" id="tarif_premiere_semaine" 
                                               step="0.5" min="0" value="<?= $parametres_gardiennage['tarif_premiere_semaine'] ?>" required>
                                        <span class="input-group-text">€ / jour</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-3 border-primary shadow-sm">
                                <div class="card-header bg-light">
                                    <div class="d-flex align-items-center">
                                        <div class="tarif-icon bg-warning text-white me-3">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                        <h6 class="mb-0">Période intermédiaire (8 à 30 jours)</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="tarif_intermediaire" id="tarif_intermediaire" 
                                               step="0.5" min="0" value="<?= $parametres_gardiennage['tarif_intermediaire'] ?>" required>
                                        <span class="input-group-text">€ / jour</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-3 border-primary shadow-sm">
                                <div class="card-header bg-light">
                                    <div class="d-flex align-items-center">
                                        <div class="tarif-icon bg-danger text-white me-3">
                                            <i class="fas fa-calendar-plus"></i>
                                        </div>
                                        <h6 class="mb-0">Longue durée (plus de 30 jours)</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="tarif_longue_duree" id="tarif_longue_duree" 
                                               step="0.5" min="0" value="<?= $parametres_gardiennage['tarif_longue_duree'] ?>" required>
                                        <span class="input-group-text">€ / jour</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="exemple-calcul p-3 border rounded mt-4">
                                <h6 class="fw-bold mb-3"><i class="fas fa-calculator me-2"></i>Exemple de calcul</h6>
                                <p class="mb-2">Pour un appareil gardé pendant <span id="jours_total">38</span> jours :</p>
                                <div class="ms-3">
                                    <div class="row">
                                        <div class="col-8">7 jours × <span id="exemple_tarif1">5</span> €/jour</div>
                                        <div class="col-4 text-end fw-bold"><span id="exemple_montant1">35,00</span> €</div>
                                    </div>
                                    <div class="row mt-1">
                                        <div class="col-8">23 jours × <span id="exemple_tarif2">3</span> €/jour</div>
                                        <div class="col-4 text-end fw-bold"><span id="exemple_montant2">69,00</span> €</div>
                                    </div>
                                    <div class="row mt-1">
                                        <div class="col-8">8 jours × <span id="exemple_tarif3">1</span> €/jour</div>
                                        <div class="col-4 text-end fw-bold"><span id="exemple_montant3">8,00</span> €</div>
                                    </div>
                                    <div class="row mt-2 pt-2 border-top">
                                        <div class="col-8 fw-bold">Total</div>
                                        <div class="col-4 text-end fw-bold"><span id="exemple_total">112,00</span> €</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Onglet Template SMS -->
                        <div class="tab-pane fade" id="sms" role="tabpanel" aria-labelledby="sms-tab">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Personnalisez le message SMS envoyé lors d'un rappel de gardiennage.
                            </div>
                            
                            <?php
                            // Récupérer le template SMS actuel pour le gardiennage
                            try {
                                $stmt = $shop_pdo->prepare("
                                    SELECT id, contenu 
                                    FROM sms_templates 
                                    WHERE nom = 'Rappel gardiennage' AND est_actif = 1
                                    LIMIT 1
                                ");
                                $stmt->execute();
                                $sms_template = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                // Template par défaut si aucun n'existe
                                if (!$sms_template) {
                                    $sms_template = [
                                        'id' => 0,
                                        'contenu' => "Bonjour [CLIENT_PRENOM] [CLIENT_NOM],\n\nVotre [APPAREIL_MARQUE] [APPAREIL_MODELE] est en gardiennage chez nous depuis [JOURS_GARDIENNAGE] jours.\nLe montant actuel du gardiennage s'élève à [MONTANT_GARDIENNAGE] €.\n\nMerci de venir récupérer votre appareil dès que possible.\n\nL'équipe de réparation"
                                    ];
                                }
                            } catch (PDOException $e) {
                                $sms_template = [
                                    'id' => 0,
                                    'contenu' => "Bonjour [CLIENT_PRENOM] [CLIENT_NOM],\n\nVotre [APPAREIL_MARQUE] [APPAREIL_MODELE] est en gardiennage chez nous depuis [JOURS_GARDIENNAGE] jours.\nLe montant actuel du gardiennage s'élève à [MONTANT_GARDIENNAGE] €.\n\nMerci de venir récupérer votre appareil dès que possible.\n\nL'équipe de réparation"
                                ];
                            }
                            ?>
                            
                            <input type="hidden" name="sms_template_id" value="<?= $sms_template['id'] ?>">
                            
                            <div class="form-group mb-3">
                                <label for="sms_template_contenu" class="form-label fw-bold">Message de rappel</label>
                                <textarea class="form-control" id="sms_template_contenu" name="sms_template_contenu" rows="8"><?= htmlspecialchars($sms_template['contenu']) ?></textarea>
                                <div class="form-text">Utilisez les variables entre crochets pour personnaliser le message.</div>
                            </div>
                            
                            <div class="card mb-3 border-info">
                                <div class="card-header bg-info bg-opacity-10">
                                    <h6 class="mb-0"><i class="fas fa-tags me-2"></i>Variables disponibles</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><code>[CLIENT_NOM]</code></span>
                                                    <span class="text-muted">Nom du client</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><code>[CLIENT_PRENOM]</code></span>
                                                    <span class="text-muted">Prénom du client</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><code>[APPAREIL_MARQUE]</code></span>
                                                    <span class="text-muted">Marque de l'appareil</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><code>[APPAREIL_MODELE]</code></span>
                                                    <span class="text-muted">Modèle de l'appareil</span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><code>[JOURS_GARDIENNAGE]</code></span>
                                                    <span class="text-muted">Nombre de jours</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><code>[MONTANT_GARDIENNAGE]</code></span>
                                                    <span class="text-muted">Montant total</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><code>[DATE_DEBUT]</code></span>
                                                    <span class="text-muted">Date de début</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <span><code>[DATE_ACTUELLE]</code></span>
                                                    <span class="text-muted">Date du jour</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3 border-success">
                                <div class="card-header bg-success bg-opacity-10">
                                    <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Aperçu du message</h6>
                                </div>
                                <div class="card-body">
                                    <div class="sms-preview p-3 border rounded" id="sms-preview">
                                        <div class="message-bubble">
                                            <!-- Le contenu sera mis à jour dynamiquement par JavaScript -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer les paramètres</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour SMS groupé -->
<div class="modal fade" id="smsGroupeModal" tabindex="-1" aria-labelledby="smsGroupeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="smsGroupeModalLabel"><i class="fas fa-sms me-2"></i>SMS groupé aux clients</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="index.php?page=gardiennage" method="post" id="formSmsGroupe">
                <input type="hidden" name="action" value="envoyer_sms_groupe">
                <div class="modal-body">
                    <div class="alert alert-info d-flex">
                        <div class="me-3">
                            <i class="fas fa-info-circle fs-4"></i>
                        </div>
                        <div>
                            <p class="mb-1">Vous êtes sur le point d'envoyer un SMS à <strong class="text-primary"><?= count($gardiennages_actifs) ?> client(s)</strong> dont les appareils sont actuellement en gardiennage.</p>
                        </div>
                    </div>
                    
                    <!-- Onglets -->
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="envoi-tab" data-bs-toggle="tab" data-bs-target="#envoi-sms" type="button" role="tab" aria-controls="envoi-sms" aria-selected="true">
                                <i class="fas fa-paper-plane me-2"></i>Envoyer
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates-sms" type="button" role="tab" aria-controls="templates-sms" aria-selected="false">
                                <i class="fas fa-save me-2"></i>Templates
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Contenu des onglets -->
                    <div class="tab-content">
                        <!-- Onglet Envoi -->
                        <div class="tab-pane fade show active" id="envoi-sms" role="tabpanel" aria-labelledby="envoi-tab">
                            <div class="row">
                                <div class="col-md-7">
                                    <div class="form-group mb-3">
                                        <label for="message_groupe" class="form-label fw-bold">Message</label>
                                        <textarea class="form-control" id="message_groupe" name="message_groupe" rows="8" required>Bonjour [CLIENT_PRENOM] [CLIENT_NOM], votre [APPAREIL_MARQUE] [APPAREIL_MODELE] est en gardiennage chez nous depuis [JOURS_GARDIENNAGE] jours. Merci de venir le récupérer dès que possible. L'équipe de réparation.</textarea>
                                        <div class="form-text">Utilisez les variables pour personnaliser le message pour chaque client.</div>
                                    </div>
                                    
                                    <div class="form-group mb-3">
                                        <label class="form-label fw-bold">Sélectionner un template</label>
                                        <select class="form-select" id="select_template" onchange="chargerTemplate()">
                                            <option value="">-- Sélectionner un template --</option>
                                            <?php
                                            // Récupérer les templates SMS
                                            try {
                                                $stmt = $shop_pdo->prepare("
                                                    SELECT id, nom, contenu
                                                    FROM sms_templates
                                                    WHERE (nom LIKE '%gardiennage%' OR nom LIKE '%rappel%') AND est_actif = 1
                                                    ORDER BY nom
                                                ");
                                                $stmt->execute();
                                                $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                
                                                foreach ($templates as $template) {
                                                    echo '<option value="' . htmlspecialchars($template['contenu']) . '">' . htmlspecialchars($template['nom']) . '</option>';
                                                }
                                            } catch (PDOException $e) {
                                                // Aucun template à afficher
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="card mb-3 border-info">
                                        <div class="card-header bg-info bg-opacity-10">
                                            <h6 class="mb-0"><i class="fas fa-tags me-2"></i>Variables disponibles</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <div class="badge bg-light text-dark p-2 d-block text-start mb-2">
                                                        <code>[CLIENT_NOM]</code>
                                                    </div>
                                                    <div class="badge bg-light text-dark p-2 d-block text-start mb-2">
                                                        <code>[CLIENT_PRENOM]</code>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="badge bg-light text-dark p-2 d-block text-start mb-2">
                                                        <code>[APPAREIL_MARQUE]</code>
                                                    </div>
                                                    <div class="badge bg-light text-dark p-2 d-block text-start mb-2">
                                                        <code>[APPAREIL_MODELE]</code>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="badge bg-light text-dark p-2 d-block text-start">
                                                        <code>[JOURS_GARDIENNAGE]</code>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="badge bg-light text-dark p-2 d-block text-start">
                                                        <code>[DATE_ACTUELLE]</code>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-5">
                                    <div class="card h-100 border-success">
                                        <div class="card-header bg-success bg-opacity-10">
                                            <h6 class="mb-0"><i class="fas fa-eye me-2"></i>Aperçu du message</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label class="form-label small text-muted">Exemple pour:</label>
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="avatar-circle me-2">
                                                        <span>JD</span>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">Jean Dupont</div>
                                                        <div class="small text-muted">Samsung Galaxy S21</div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="sms-preview p-3 border rounded" id="sms-preview-groupe">
                                                <div class="message-bubble">
                                                    <!-- Le contenu sera mis à jour dynamiquement par JavaScript -->
                                                </div>
                                            </div>
                                            
                                            <div class="text-center mt-3">
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-mobile-alt me-1"></i> 
                                                    <span id="caracteres-count">0</span> caractères
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning mt-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                                    <div>Ce message sera envoyé à tous les clients ayant des appareils en gardiennage.</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Onglet Templates -->
                        <div class="tab-pane fade" id="templates-sms" role="tabpanel" aria-labelledby="templates-tab">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Gérer les templates de SMS</h6>
                                <button type="button" class="btn btn-sm btn-success" onclick="nouveauTemplate()">
                                    <i class="fas fa-plus me-1"></i> Nouveau template
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Contenu</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($templates ?? [] as $template): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($template['nom']) ?></td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 300px;">
                                                        <?= htmlspecialchars($template['contenu']) ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="utiliserTemplate('<?= addslashes(htmlspecialchars($template['contenu'])) ?>')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-warning me-1" onclick="editerTemplate('<?= $template['id'] ?>', '<?= addslashes(htmlspecialchars($template['nom'])) ?>', '<?= addslashes(htmlspecialchars($template['contenu'])) ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($templates)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-3">
                                                    <div class="text-muted">Aucun template disponible</div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Formulaire pour modifier un template existant -->
                            <div id="edit-template-form" class="mt-4 border rounded p-3 d-none">
                                <h6 class="mb-3"><i class="fas fa-edit me-2"></i><span id="edit-template-title">Modifier le template</span></h6>
                                
                                <input type="hidden" id="edit-template-id">
                                
                                <div class="mb-3">
                                    <label for="edit-template-nom" class="form-label">Nom du template</label>
                                    <input type="text" class="form-control" id="edit-template-nom" placeholder="Nom du template">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit-template-contenu" class="form-label">Contenu</label>
                                    <textarea class="form-control" id="edit-template-contenu" rows="5" placeholder="Contenu du template"></textarea>
                                </div>
                                
                                <div class="text-end">
                                    <button type="button" class="btn btn-outline-secondary me-2" onclick="annulerEditionTemplate()">
                                        Annuler
                                    </button>
                                    <button type="button" class="btn btn-primary" onclick="sauvegarderTemplate()">
                                        Sauvegarder
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary sms-send-btn">
                        <i class="fas fa-paper-plane me-2"></i>Envoyer à tous les clients
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmerRappel(gardiennageId, clientNom) {
    document.getElementById('rappel_gardiennage_id').value = gardiennageId;
    document.getElementById('rappel_client_nom').textContent = clientNom;
    
    // Afficher la modal
    var myModal = new bootstrap.Modal(document.getElementById('rappelModal'));
    myModal.show();
}

function terminerGardiennage(gardiennageId, clientNom, montantTotal, montantGardiennage, montantReparation) {
    document.getElementById('terminer_gardiennage_id').value = gardiennageId;
    document.getElementById('terminer_client_nom').textContent = clientNom;
    
    // Mise à jour des montants détaillés
    document.getElementById('gardiennage_montant').textContent = montantGardiennage.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
    document.getElementById('reparation_montant').textContent = montantReparation.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
    document.getElementById('terminer_montant').textContent = montantTotal.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' €';
    
    // Afficher la modal
    var myModal = new bootstrap.Modal(document.getElementById('terminerModal'));
    myModal.show();
}

function afficherNotes(notes) {
    document.getElementById('notes_content').innerHTML = notes.replace(/\n/g, '<br>');
    
    // Afficher la modal
    var myModal = new bootstrap.Modal(document.getElementById('notesModal'));
    myModal.show();
}

function ouvrirModalParametres() {
    // Récupérer les valeurs déjà définies dans le HTML
    let tarif1 = parseFloat(document.getElementById('tarif_premiere_semaine').value) || 5;
    let tarif2 = parseFloat(document.getElementById('tarif_intermediaire').value) || 3;
    let tarif3 = parseFloat(document.getElementById('tarif_longue_duree').value) || 1;
    
    // Mettre à jour les champs (par précaution)
    document.getElementById('tarif_premiere_semaine').value = tarif1;
    document.getElementById('tarif_intermediaire').value = tarif2;
    document.getElementById('tarif_longue_duree').value = tarif3;
    
    // Mettre à jour l'exemple de calcul
    mettreAJourExempleCalcul();
    
    // Mettre à jour l'aperçu du SMS
    mettreAJourAperçuSMS();
    
    // Ouvrir le modal
    var parametresModal = new bootstrap.Modal(document.getElementById('parametresModal'));
    parametresModal.show();
}

function ouvrirModalSmsGroupe() {
    // Ouvrir le modal
    var smsGroupeModal = new bootstrap.Modal(document.getElementById('smsGroupeModal'));
    smsGroupeModal.show();
}

function mettreAJourExempleCalcul() {
    // Récupérer les valeurs des tarifs
    let tarif1 = parseFloat(document.getElementById('tarif_premiere_semaine').value) || 0;
    let tarif2 = parseFloat(document.getElementById('tarif_intermediaire').value) || 0;
    let tarif3 = parseFloat(document.getElementById('tarif_longue_duree').value) || 0;
    
    // Nombre de jours dans l'exemple
    let joursTotal = 38;
    let jours1 = Math.min(7, joursTotal); // Première semaine (max 7 jours)
    let jours2 = Math.min(23, Math.max(0, joursTotal - 7)); // Période intermédiaire (max 23 jours)
    let jours3 = Math.max(0, joursTotal - 30); // Longue durée (reste au-delà de 30 jours)
    
    // Calculer les montants
    let montant1 = jours1 * tarif1;
    let montant2 = jours2 * tarif2;
    let montant3 = jours3 * tarif3;
    let total = montant1 + montant2 + montant3;
    
    // Mettre à jour les valeurs dans l'exemple
    document.getElementById('jours_total').textContent = joursTotal;
    document.getElementById('exemple_tarif1').textContent = tarif1;
    document.getElementById('exemple_montant1').textContent = montant1.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('exemple_tarif2').textContent = tarif2;
    document.getElementById('exemple_montant2').textContent = montant2.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('exemple_tarif3').textContent = tarif3;
    document.getElementById('exemple_montant3').textContent = montant3.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('exemple_total').textContent = total.toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function mettreAJourAperçuSMS() {
    // Récupérer le contenu du template
    const template = document.getElementById('sms_template_contenu').value;
    
    // Données d'exemple
    const exemples = {
        'CLIENT_NOM': 'Dupont',
        'CLIENT_PRENOM': 'Jean',
        'APPAREIL_MARQUE': 'Samsung',
        'APPAREIL_MODELE': 'Galaxy S21',
        'JOURS_GARDIENNAGE': '15',
        'MONTANT_GARDIENNAGE': '75,00',
        'DATE_DEBUT': '01/04/2023',
        'DATE_ACTUELLE': '15/04/2023'
    };
    
    // Remplacer les variables par les exemples
    let texte = template;
    for (const [variable, valeur] of Object.entries(exemples)) {
        texte = texte.replace(new RegExp('\\[' + variable + '\\]', 'g'), valeur);
    }
    
    // Mettre à jour l'aperçu
    const messageBubble = document.querySelector('#sms-preview .message-bubble');
    messageBubble.innerHTML = texte.replace(/\n/g, '<br>');
}

// Ajouter des écouteurs d'événements pour mettre à jour l'exemple en temps réel
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour l'exemple lorsque les valeurs des tarifs changent
    document.getElementById('tarif_premiere_semaine').addEventListener('input', mettreAJourExempleCalcul);
    document.getElementById('tarif_intermediaire').addEventListener('input', mettreAJourExempleCalcul);
    document.getElementById('tarif_longue_duree').addEventListener('input', mettreAJourExempleCalcul);
    
    // Mettre à jour l'aperçu SMS lorsque le contenu change
    const templateTextarea = document.getElementById('sms_template_contenu');
    if (templateTextarea) {
        templateTextarea.addEventListener('input', mettreAJourAperçuSMS);
        templateTextarea.addEventListener('change', mettreAJourAperçuSMS);
    }
    
    // Mettre à jour l'aperçu SMS du message groupe
    const messageGroupe = document.getElementById('message_groupe');
    if (messageGroupe) {
        messageGroupe.addEventListener('input', mettreAJourAperçuSMSGroupe);
        messageGroupe.addEventListener('change', mettreAJourAperçuSMSGroupe);
        // Initialiser l'aperçu
        mettreAJourAperçuSMSGroupe();
    }
    
    // Initialiser la fonction de recherche
    initSearchFunctionality();
});

/**
 * Initialise la fonctionnalité de recherche pour les tableaux
 */
function initSearchFunctionality() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    // Ajouter l'écouteur d'événement pour la recherche en temps réel
    searchInput.addEventListener('input', function() {
        performSearch(this.value.toLowerCase());
    });
    
    // Assurer que le compteur est initialisé
    updateResultCount();
}

/**
 * Effectue une recherche dans les tableaux de gardiennage
 * @param {string} query - Terme de recherche
 */
function performSearch(query) {
    // Tableaux à rechercher
    const tables = [
        document.getElementById('tableActifs'),
        document.getElementById('tableTermines')
    ];
    
    let totalVisibleRows = 0;
    
    tables.forEach(table => {
        if (!table) return;
        
        const rows = table.getElementsByTagName('tr');
        
        // Parcourir toutes les lignes (sauf l'en-tête)
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const textContent = row.textContent.toLowerCase();
            
            // Vérifier si le contenu de la ligne correspond à la recherche
            if (query === '' || textContent.includes(query)) {
                row.style.display = '';
                totalVisibleRows++;
            } else {
                row.style.display = 'none';
            }
        }
    });
    
    // Mettre à jour le compteur de résultats
    updateResultCount(totalVisibleRows);
}

/**
 * Met à jour le compteur de résultats de recherche
 * @param {number} count - Nombre de résultats (optionnel)
 */
function updateResultCount(count = null) {
    const countElement = document.getElementById('resultCount');
    if (!countElement) return;
    
    if (count === null) {
        // Compter toutes les lignes visibles si count n'est pas fourni
        count = 0;
        const tables = [
            document.getElementById('tableActifs'),
            document.getElementById('tableTermines')
        ];
        
        tables.forEach(table => {
            if (!table) return;
            const rows = table.getElementsByTagName('tr');
            for (let i = 1; i < rows.length; i++) {
                if (rows[i].style.display !== 'none') {
                    count++;
                }
            }
        });
    }
    
    countElement.textContent = count;
}

/**
 * Efface le champ de recherche et réinitialise l'affichage
 */
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    searchInput.value = '';
    performSearch('');
}

/**
 * Met à jour l'aperçu du SMS pour le SMS groupé
 */
function mettreAJourAperçuSMSGroupe() {
    // Récupérer le contenu du template
    const template = document.getElementById('message_groupe').value;
    
    // Données d'exemple
    const exemples = {
        'CLIENT_NOM': 'Dupont',
        'CLIENT_PRENOM': 'Jean',
        'APPAREIL_MARQUE': 'Samsung',
        'APPAREIL_MODELE': 'Galaxy S21',
        'JOURS_GARDIENNAGE': '15',
        'DATE_ACTUELLE': '<?= date("d/m/Y") ?>'
    };
    
    // Remplacer les variables par les exemples
    let texte = template;
    for (const [variable, valeur] of Object.entries(exemples)) {
        texte = texte.replace(new RegExp('\\[' + variable + '\\]', 'g'), valeur);
    }
    
    // Mettre à jour l'aperçu
    const messageBubble = document.querySelector('#sms-preview-groupe .message-bubble');
    if (messageBubble) {
        messageBubble.innerHTML = texte.replace(/\n/g, '<br>');
    }
    
    // Compter les caractères
    const caracteresCount = document.getElementById('caracteres-count');
    if (caracteresCount) {
        caracteresCount.textContent = texte.length;
        
        // Avertissement si le message est trop long (plus de 160 caractères)
        if (texte.length > 160) {
            caracteresCount.classList.add('text-danger');
            caracteresCount.classList.add('fw-bold');
        } else {
            caracteresCount.classList.remove('text-danger');
            caracteresCount.classList.remove('fw-bold');
        }
    }
}

/**
 * Charge le template sélectionné dans le textarea
 */
function chargerTemplate() {
    const select = document.getElementById('select_template');
    const messageTextarea = document.getElementById('message_groupe');
    
    if (select && messageTextarea && select.value) {
        messageTextarea.value = select.value;
        mettreAJourAperçuSMSGroupe();
    }
}

/**
 * Prépare le formulaire pour créer un nouveau template
 */
function nouveauTemplate() {
    document.getElementById('edit-template-id').value = '';
    document.getElementById('edit-template-nom').value = '';
    document.getElementById('edit-template-contenu').value = document.getElementById('message_groupe').value || '';
    document.getElementById('edit-template-title').textContent = 'Créer un nouveau template';
    document.getElementById('edit-template-form').classList.remove('d-none');
}

/**
 * Prépare le formulaire pour éditer un template existant
 */
function editerTemplate(id, nom, contenu) {
    document.getElementById('edit-template-id').value = id;
    document.getElementById('edit-template-nom').value = nom;
    document.getElementById('edit-template-contenu').value = contenu;
    document.getElementById('edit-template-title').textContent = 'Modifier le template';
    document.getElementById('edit-template-form').classList.remove('d-none');
}

/**
 * Utilise le template sélectionné dans le message
 */
function utiliserTemplate(contenu) {
    document.getElementById('message_groupe').value = contenu;
    document.getElementById('envoi-tab').click();
    mettreAJourAperçuSMSGroupe();
}

/**
 * Annule l'édition du template
 */
function annulerEditionTemplate() {
    document.getElementById('edit-template-form').classList.add('d-none');
}

/**
 * Sauvegarde le template (création ou modification)
 */
function sauvegarderTemplate() {
    const id = document.getElementById('edit-template-id').value;
    const nom = document.getElementById('edit-template-nom').value;
    const contenu = document.getElementById('edit-template-contenu').value;
    
    if (!nom || !contenu) {
        alert('Veuillez remplir tous les champs');
        return;
    }
    
    // Créer un formulaire pour soumettre les données
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php?page=gardiennage';
    form.style.display = 'none';
    
    // Ajouter les champs
    const addField = (name, value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    };
    
    addField('action', 'sauvegarder_template');
    addField('template_id', id);
    addField('template_nom', nom);
    addField('template_contenu', contenu);
    
    // Ajouter et soumettre le formulaire
    document.body.appendChild(form);
    form.submit();
}
</script>

<style>
/* Styles pour la page gardiennage - Design moderne et épuré */
.gardiennage-container {
    max-width: 1200px;
    padding: 20px;
}

.page-header {
    position: relative;
}

.page-header .border-bottom {
    position: absolute;
    bottom: -10px;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--bs-primary), transparent);
}

/* Cartes de statistiques */
.stat-card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
    position: relative;
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #4a6bfd, #3355ee);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #28c76f, #1f9d57);
}

.stat-icon-bg {
    position: absolute;
    bottom: -15px;
    right: -15px;
    font-size: 5rem;
    opacity: 0.1;
}

/* Onglets personnalisés */
.custom-tabs {
    border-bottom: 2px solid rgba(0, 0, 0, 0.1);
}

.custom-tabs .nav-link {
    position: relative;
    border: none;
    color: #495057;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    background-color: transparent;
    border-radius: 0;
    transition: color 0.3s;
}

.custom-tabs .nav-link.active {
    color: var(--bs-primary);
    background-color: transparent;
}

.custom-tabs .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 3px;
    background-color: var(--bs-primary);
    border-radius: 3px 3px 0 0;
    animation: slideIn 0.3s ease-in-out;
}

/* Animation pour l'onglet actif */
@keyframes slideIn {
    from { transform: scaleX(0); }
    to { transform: scaleX(1); }
}

/* Tableau personnalisé */
.custom-table {
    margin-bottom: 0;
}

.custom-table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-top: none;
    padding: 0.75rem;
    vertical-align: middle;
}

.custom-table tbody td {
    vertical-align: middle;
    padding: 0.75rem;
}

/* Styles pour la barre de recherche */
.input-group .form-control:focus {
    box-shadow: none;
    border-color: var(--bs-primary);
}

.input-group .input-group-text {
    border-right: 0;
}

.input-group .form-control {
    border-left: 0;
}

.input-group .btn-outline-primary {
    border-color: #ced4da;
    color: #6c757d;
    background-color: transparent;
}

.input-group .btn-outline-primary:hover {
    background-color: #f8f9fa;
    color: #495057;
    border-color: #ced4da;
}

/* Effet de surbrillance pour les résultats de recherche */
.search-highlight {
    background-color: rgba(255, 243, 205, 0.5);
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { background-color: rgba(255, 243, 205, 0.3); }
    50% { background-color: rgba(255, 243, 205, 0.7); }
    100% { background-color: rgba(255, 243, 205, 0.3); }
}

/* Compteur de résultats */
#resultCount {
    font-weight: 600;
    color: var(--bs-primary);
}

/* Badge et éléments visuels */
.avatar-circle {
    width: 36px;
    height: 36px;
    background-color: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
}

.device-icon {
    width: 36px;
    height: 36px;
    background-color: rgba(13, 110, 253, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bs-primary);
}

/* Date range display */
.date-range {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.date-arrow {
    margin-left: 0.75rem;
    color: #6c757d;
}

/* Style pour le total à payer */
.total-a-payer {
    background-color: rgba(13, 110, 253, 0.1);
    color: var(--bs-primary);
    font-weight: bold;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.total-paye {
    background-color: rgba(40, 167, 69, 0.1);
    color: var(--bs-success);
    font-weight: bold;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

/* Styles pour les tarifs dans le modal */
.tarif-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Styles pour les boutons soft */
.btn-soft-info {
    background-color: rgba(13, 202, 240, 0.1);
    color: var(--bs-info);
    border: none;
}

.btn-soft-info:hover {
    background-color: rgba(13, 202, 240, 0.2);
    color: var(--bs-info);
}

/* Message bubble style pour prévisualisation SMS */
.message-bubble {
    background-color: #e5f7ff;
    border-radius: 0.75rem;
    padding: 0.75rem 1rem;
    position: relative;
    max-width: 100%;
    margin-left: 1rem;
    border-top-left-radius: 0;
    font-family: sans-serif;
    font-size: 0.9rem;
    line-height: 1.4;
    word-break: break-word;
}

.message-bubble:before {
    content: "";
    position: absolute;
    top: 0;
    left: -0.75rem;
    width: 0.75rem;
    height: 0.75rem;
    background-color: #e5f7ff;
    clip-path: polygon(100% 0, 0 0, 100% 100%);
}

.sms-preview {
    max-width: 300px;
    margin: 0 auto;
    background-color: #f8f9fa;
}

/* Styles pour le modal SMS Groupe */
.sms-send-btn {
    background: linear-gradient(90deg, #4a6bfd, #3355ee);
    border: none;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s;
}

.sms-send-btn:hover {
    background: linear-gradient(90deg, #3355ee, #2244dd);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Animation pour montrer que le compteur change */
@keyframes pulse-count {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

#caracteres-count.text-danger {
    animation: pulse-count 0.5s;
}

/* Styles pour le formulaire d'édition de template */
#edit-template-form {
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

#edit-template-form:not(.d-none) {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<?php
// Les fonctions utilisateurs restent inchangées
function dateDiffInDays($date1, $date2) {
    $diff = $date2->diff($date1);
    return $diff->days;
}

function formatDateFr($date) {
    if (empty($date)) return '';
    return date('d/m/Y', strtotime($date));
}

function formatPhoneNumber($phone) {
    if (empty($phone)) return '';
    
    // Si le numéro commence par +33, le formater pour l'affichage
    if (substr($phone, 0, 3) === '+33') {
        $phone = '0' . substr($phone, 3);
    }
    
    // Ajouter des espaces pour l'affichage (format français)
    if (strlen($phone) === 10) {
        return wordwrap($phone, 2, ' ', true);
    }
    
    return $phone;
}
?> 