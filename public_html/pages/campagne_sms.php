<?php
// Vérification des droits de base
if (!isset($_SESSION['user_id'])) {
    set_message("Vous devez être connecté pour accéder à cette page.", "danger");
    redirect("");
    exit;
}

// Variable pour déterminer le niveau d'accès
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Traitement de l'envoi d'une campagne SMS
$campaign_sent = false;
$campaign_error = null;
$preview_mode = isset($_POST['preview']) && $_POST['preview'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_campaign') {
    // Récupération des données du formulaire
    $template_id = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
    $client_filter = isset($_POST['client_filter']) ? clean_input($_POST['client_filter']) : 'all';
    $date_debut = isset($_POST['date_debut']) ? clean_input($_POST['date_debut']) : '';
    $date_fin = isset($_POST['date_fin']) ? clean_input($_POST['date_fin']) : '';
    $custom_message = isset($_POST['custom_message']) ? $_POST['custom_message'] : '';
    
    // Validation des données
    $errors = [];
    if ($template_id == 0 && empty($custom_message)) {
        $errors[] = "Veuillez sélectionner un modèle ou saisir un message personnalisé.";
    }
    
    if (empty($errors)) {
        try {
            // Construction de la requête pour obtenir les clients selon le filtre
            $sql = "SELECT id, nom, prenom, telephone FROM clients WHERE 1=1";
            $params = [];
            
            if ($client_filter === 'with_repair') {
                $sql = "SELECT DISTINCT c.id, c.nom, c.prenom, c.telephone 
                        FROM clients c 
                        JOIN reparations r ON c.id = r.client_id";
                
                if (!empty($date_debut)) {
                    $sql .= " WHERE r.date_creation >= ?";
                    $params[] = $date_debut;
                    
                    if (!empty($date_fin)) {
                        $sql .= " AND r.date_creation <= ?";
                        $params[] = $date_fin . ' 23:59:59';
                    }
                } elseif (!empty($date_fin)) {
                    $sql .= " WHERE r.date_creation <= ?";
                    $params[] = $date_fin . ' 23:59:59';
                }
            } else {
                // Filtre par date pour tous les clients
                if (!empty($date_debut)) {
                    $sql .= " AND date_creation >= ?";
                    $params[] = $date_debut;
                    
                    if (!empty($date_fin)) {
                        $sql .= " AND date_creation <= ?";
                        $params[] = $date_fin . ' 23:59:59';
                    }
                } elseif (!empty($date_fin)) {
                    $sql .= " AND date_creation <= ?";
                    $params[] = $date_fin . ' 23:59:59';
                }
            }
            
            // Exécution de la requête
            $shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare($sql);
            $stmt->execute($params);
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($clients)) {
                $campaign_error = "Aucun client ne correspond aux critères sélectionnés.";
            } else {
                // Si nous sommes en mode aperçu, afficher seulement les clients qui recevraient le SMS
                if ($preview_mode) {
                    $_SESSION['campaign_preview'] = [
                        'clients' => $clients,
                        'template_id' => $template_id,
                        'custom_message' => $custom_message
                    ];
                    
                    redirect("campagne_sms", ["preview" => 1]);
                    exit;
                }
                
                // Sinon, procéder à l'envoi de la campagne
                $message = '';
                if ($template_id > 0) {
                    // Récupération du modèle SMS
                    $template_stmt = $shop_pdo->prepare("SELECT contenu FROM sms_templates WHERE id = ?");
                    $template_stmt->execute([$template_id]);
                    $template = $template_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($template) {
                        $message = $template['contenu'];
                    } else {
                        $campaign_error = "Le modèle sélectionné n'existe pas.";
                    }
                } else {
                    // Utilisation du message personnalisé
                    $message = $custom_message;
                }
                
                if (!empty($message)) {
                    $success_count = 0;
                    $error_count = 0;
                    
                    // Enregistrement de la campagne
                    $stmt = $shop_pdo->prepare("
                        INSERT INTO sms_campaigns (
                            nom, message, date_envoi, nb_destinataires, user_id
                        ) VALUES (?, ?, NOW(), ?, ?)
                    ");
                    $campaign_name = "Campagne du " . date('d/m/Y H:i');
                    $stmt->execute([$campaign_name, $message, count($clients), $_SESSION['user_id']]);
                    $campaign_id = $shop_pdo->lastInsertId();
                    
                    // Envoi à chaque client
                    foreach ($clients as $client) {
                        // Préparation du message personnalisé pour ce client
                        $personalized_message = str_replace(
                            ['[CLIENT_NOM]', '[CLIENT_PRENOM]'],
                            [$client['nom'], $client['prenom']],
                            $message
                        );
                        
                        // Envoi du SMS
                        $result = send_sms($client['telephone'], $personalized_message);
                        
                        // Enregistrement du résultat
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO sms_campaign_details (
                                campaign_id, client_id, telephone, message, statut, date_envoi
                            ) VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        $status = $result['success'] ? 'envoyé' : 'échec';
                        $stmt->execute([
                            $campaign_id,
                            $client['id'],
                            $client['telephone'],
                            $personalized_message,
                            $status
                        ]);
                        
                        // Comptage des succès/échecs
                        if ($result['success']) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    }
                    
                    // Mise à jour des statistiques de la campagne
                    $stmt = $shop_pdo->prepare("UPDATE sms_campaigns SET nb_envoyes = ?, nb_echecs = ? WHERE id = ?");
                    $stmt->execute([$success_count, $error_count, $campaign_id]);
                    
                    // Message de succès
                    $campaign_sent = true;
                    set_message("Campagne SMS envoyée : $success_count SMS envoyés avec succès, $error_count échecs.", 
                                $error_count > 0 ? "warning" : "success");
                    
                    redirect("campagne_sms");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $campaign_error = "Erreur lors de l'envoi de la campagne : " . $e->getMessage();
        }
    } else {
        $campaign_error = implode("<br>", $errors);
    }
}

// Récupération des modèles de SMS
try {
    $stmt = $shop_pdo->query("
        SELECT id, nom, contenu 
        FROM sms_templates 
        WHERE est_actif = 1
        ORDER BY nom
    ");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $templates = [];
    set_message("Erreur lors de la récupération des modèles : " . $e->getMessage(), "danger");
}

// Récupération des campagnes précédentes (les 10 dernières)
try {
    $stmt = $shop_pdo->query("
        SELECT c.*, u.nom as user_nom, u.prenom as user_prenom
        FROM sms_campaigns c
        LEFT JOIN users u ON c.user_id = u.id
        ORDER BY c.date_envoi DESC
        LIMIT 10
    ");
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $campaigns = [];
    set_message("Erreur lors de la récupération des campagnes : " . $e->getMessage(), "danger");
}

// Mode aperçu
$preview_clients = [];
if (isset($_GET['preview']) && $_GET['preview'] == 1 && isset($_SESSION['campaign_preview'])) {
    $preview_clients = $_SESSION['campaign_preview']['clients'];
    $selected_template_id = $_SESSION['campaign_preview']['template_id'];
    $custom_message = $_SESSION['campaign_preview']['custom_message'];
}
?>

<div style="width: 100%; max-width: 100%; padding: 1.5rem;">
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="text-primary fw-bold"><i class="fas fa-paper-plane me-3 text-gradient-blue"></i>Campagnes SMS</h1>
            <a href="index.php?page=sms_templates" class="btn btn-primary btn-gradient-blue shadow-sm">
                <i class="fas fa-cog me-2"></i>Gérer les modèles
            </a>
        </div>
        <p class="text-muted">Créez et envoyez des campagnes SMS à vos clients</p>
    </div>
    
    <?php if (isset($_GET['preview']) && $_GET['preview'] == 1 && !empty($preview_clients)): ?>
    <!-- Mode aperçu -->
    <div style="width: 100%; margin-bottom: 1.5rem;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="index.php?page=campagne_sms" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Retour
            </a>
            <form method="post">
                <input type="hidden" name="action" value="send_campaign">
                <input type="hidden" name="template_id" value="<?php echo $selected_template_id; ?>">
                <input type="hidden" name="custom_message" value="<?php echo htmlspecialchars($custom_message); ?>">
                <input type="hidden" name="client_filter" value="<?php echo isset($_POST['client_filter']) ? $_POST['client_filter'] : 'all'; ?>">
                <button type="submit" class="btn btn-success btn-gradient-green rounded-pill px-4 shadow-sm">
                    <i class="fas fa-paper-plane me-2"></i>Envoyer la campagne
                </button>
            </form>
        </div>
        
        <div class="card shadow border-0 rounded-4 position-relative overflow-hidden">
            <div class="card-blur-bg"></div>
            <div class="card-header bg-info bg-gradient text-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-eye me-2 pulse-icon"></i>Aperçu de la campagne</h5>
                    <span class="badge bg-white text-info rounded-pill px-3 py-2 shadow-sm"><?php echo count($preview_clients); ?> destinataire(s)</span>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info bg-info bg-opacity-10 border-0 rounded-3 shadow-sm">
                    <p class="fw-bold mb-2"><i class="fas fa-comment-dots me-2"></i>Message qui sera envoyé :</p>
                    <?php
                    $preview_message = '';
                    if ($selected_template_id > 0) {
                        foreach ($templates as $template) {
                            if ($template['id'] == $selected_template_id) {
                                $preview_message = $template['contenu'];
                                break;
                            }
                        }
                    } else {
                        $preview_message = $custom_message;
                    }
                    
                    // Afficher avec le premier client comme exemple
                    if (!empty($preview_clients)) {
                        $example_client = $preview_clients[0];
                        $preview_message = str_replace(
                            ['[CLIENT_NOM]', '[CLIENT_PRENOM]'],
                            [$example_client['nom'], $example_client['prenom']],
                            $preview_message
                        );
                    }
                    ?>
                    <div class="bg-white p-4 rounded-3 shadow-inner mt-2 message-preview">
                        <?php echo nl2br(htmlspecialchars($preview_message)); ?>
                    </div>
                </div>
                
                <h6 class="mt-4 mb-3 fw-bold"><i class="fas fa-users me-2"></i>Liste des destinataires</h6>
                <div class="table-responsive rounded-3 shadow-sm">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold">Nom</th>
                                <th class="fw-bold">Prénom</th>
                                <th class="fw-bold">Téléphone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview_clients as $client): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($client['nom']); ?></td>
                                <td><?php echo htmlspecialchars($client['prenom']); ?></td>
                                <td><span class="badge bg-light text-dark rounded-pill"><?php echo htmlspecialchars($client['telephone']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Formulaire de création de campagne -->
    <div style="width: 100%; margin-bottom: 1.5rem;">
        <div class="card shadow border-0 rounded-4 position-relative overflow-hidden">
            <div class="card-blur-bg"></div>
            <div class="card-header bg-primary bg-gradient text-white border-0">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2 pulse-icon"></i>Nouvelle campagne SMS</h5>
            </div>
            <div class="card-body p-4">
                <?php if ($campaign_error): ?>
                <div class="alert alert-danger bg-danger bg-opacity-10 border-0 rounded-3">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $campaign_error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($campaign_sent): ?>
                <div class="alert alert-success bg-success bg-opacity-10 border-0 rounded-3">
                    <i class="fas fa-check-circle me-2"></i>Campagne SMS envoyée avec succès !
                </div>
                <?php endif; ?>
                
                <form method="post" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="send_campaign">
                    
                    <div class="mb-4">
                        <label for="template_id" class="form-label fw-bold"><i class="fas fa-file-alt me-2 text-primary"></i>Modèle de SMS</label>
                        <select class="form-select form-select-lg border-0 shadow-sm rounded-3" id="template_id" name="template_id">
                            <option value="0">-- Message personnalisé --</option>
                            <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template['id']; ?>">
                                <?php echo htmlspecialchars($template['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="client_filter" class="form-label fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtrer les clients</label>
                        <select class="form-select border-0 shadow-sm rounded-3" id="client_filter" name="client_filter">
                            <option value="all">Tous les clients</option>
                            <option value="with_repair">Clients avec réparations</option>
                        </select>
                    </div>
                    
                    <div class="mb-4" id="custom_message_container">
                        <label for="custom_message" class="form-label fw-bold"><i class="fas fa-pen me-2 text-primary"></i>Message personnalisé</label>
                        <textarea class="form-control border-0 shadow-sm rounded-3" id="custom_message" name="custom_message" rows="4" 
                                 maxlength="320" style="min-height:120px"></textarea>
                        <div class="d-flex justify-content-between mt-2">
                            <div id="charCount" class="form-text badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">0/320 caractères</div>
                            <div id="smsCount" class="form-text badge bg-info bg-opacity-10 text-info rounded-pill px-3">1 SMS</div>
                        </div>
                        <div class="form-text mt-2">
                            <i class="fas fa-info-circle me-1"></i>Variables disponibles : <span class="badge bg-light text-dark">[CLIENT_NOM]</span>, <span class="badge bg-light text-dark">[CLIENT_PRENOM]</span>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="submit" name="preview" value="1" class="btn btn-outline-primary rounded-pill px-4">
                            <i class="fas fa-eye me-2"></i>Aperçu
                        </button>
                        <button type="submit" class="btn btn-primary btn-gradient-blue rounded-pill px-4 shadow-sm">
                            <i class="fas fa-paper-plane me-2"></i>Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Historique des campagnes -->
    <div style="width: 100%;">
        <div class="card shadow border-0 rounded-4 position-relative overflow-hidden">
            <div class="card-blur-bg"></div>
            <div class="card-header bg-light bg-gradient border-0">
                <h5 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Historique des campagnes</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($campaigns)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="mb-0">Aucune campagne SMS n'a été envoyée pour le moment.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Nom</th>
                                <th>Envoyé par</th>
                                <th>Destinataires</th>
                                <th>Taux</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark rounded-pill"><?php echo date('d/m/Y H:i', strtotime($campaign['date_envoi'])); ?></span></td>
                                <td class="fw-medium"><?php echo htmlspecialchars($campaign['nom']); ?></td>
                                <td>
                                    <?php 
                                    if ($campaign['user_nom']) {
                                        echo '<span class="user-badge">'.htmlspecialchars(substr($campaign['user_prenom'],0,1).substr($campaign['user_nom'],0,1)).'</span>';
                                        echo '<span class="d-none d-md-inline ms-2">'.htmlspecialchars($campaign['user_prenom'] . ' ' . $campaign['user_nom']).'</span>';
                                    } else {
                                        echo '<span class="user-badge bg-secondary">SY</span><span class="d-none d-md-inline ms-2">Système</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $campaign['nb_destinataires']; ?></td>
                                <td>
                                    <?php 
                                    $success_rate = $campaign['nb_destinataires'] > 0 
                                        ? round(($campaign['nb_envoyes'] / $campaign['nb_destinataires']) * 100) 
                                        : 0;
                                    
                                    $badge_class = 'bg-success';
                                    if ($success_rate < 50) {
                                        $badge_class = 'bg-danger';
                                    } elseif ($success_rate < 90) {
                                        $badge_class = 'bg-warning';
                                    }
                                    ?>
                                    <div class="progress rounded-pill">
                                        <div class="progress-bar <?php echo $badge_class; ?>" role="progressbar" style="width: <?php echo $success_rate; ?>%" 
                                             aria-valuenow="<?php echo $success_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $success_rate; ?>%
                                        </div>
                                    </div>
                                    <small class="d-block text-center mt-1">
                                        (<?php echo $campaign['nb_envoyes']; ?>/<?php echo $campaign['nb_destinataires']; ?>)
                                    </small>
                                </td>
                                <td>
                                    <a href="index.php?page=campagne_details&id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-light rounded-circle shadow-sm">
                                        <i class="fas fa-search text-primary"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.btn-gradient-blue {
    background: linear-gradient(45deg, #1e90ff, #3a7bd5);
    border: none;
    transition: all 0.3s ease;
}
.btn-gradient-blue:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(30, 144, 255, 0.4);
}
.btn-gradient-green {
    background: linear-gradient(45deg, #2ecc71, #27ae60);
    border: none;
    transition: all 0.3s ease;
}
.btn-gradient-green:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);
}
.shadow-inner {
    box-shadow: inset 0 1px 4px rgba(0,0,0,0.1);
}
.text-gradient-blue {
    background: linear-gradient(45deg, #1e90ff, #3a7bd5);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.card {
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-5px);
}
.card-blur-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    backdrop-filter: blur(16px);
    opacity: 0.05;
    z-index: 0;
}
.card-body {
    position: relative;
    z-index: 1;
}
.pulse-icon {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}
.message-preview {
    transition: all 0.3s ease;
    min-height: 100px;
}
.message-preview:hover {
    box-shadow: inset 0 1px 8px rgba(30, 144, 255, 0.2);
}
.user-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #1e90ff;
    color: white;
    font-weight: bold;
}
.progress {
    height: 10px;
    background-color: rgba(200, 200, 200, 0.2);
}
@media (max-width: 768px) {
    .progress-bar {
        font-size: 9px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animation d'entrée pour les cartes
    document.querySelectorAll('.card').forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });
    
    // Gestion de l'affichage du message personnalisé en fonction du modèle sélectionné
    const templateSelect = document.getElementById('template_id');
    const customMessageContainer = document.getElementById('custom_message_container');
    const customMessageTextarea = document.getElementById('custom_message');
    
    function toggleCustomMessage() {
        if (templateSelect.value === '0') {
            customMessageContainer.style.display = 'block';
            customMessageTextarea.setAttribute('required', 'required');
            // Animation d'entrée
            customMessageContainer.style.opacity = '0';
            customMessageContainer.style.transform = 'translateY(10px)';
            setTimeout(() => {
                customMessageContainer.style.opacity = '1';
                customMessageContainer.style.transform = 'translateY(0)';
            }, 100);
        } else {
            customMessageContainer.style.display = 'none';
            customMessageTextarea.removeAttribute('required');
        }
    }
    
    templateSelect.addEventListener('change', toggleCustomMessage);
    toggleCustomMessage(); // Exécuter au chargement
    
    // Compteur de caractères pour le SMS avec animation
    const charCount = document.getElementById('charCount');
    const smsCount = document.getElementById('smsCount');
    
    function updateCounter() {
        const length = customMessageTextarea.value.length;
        charCount.textContent = length + "/320 caractères";
        
        // Calcul du nombre de SMS
        let count = 1;
        if (length <= 160) {
            smsCount.textContent = "1 SMS";
        } else {
            // 153 caractères par SMS pour les messages concaténés
            count = Math.ceil(length / 153);
            smsCount.textContent = count + " SMS";
        }
        
        // Animation du badge selon le nombre de SMS
        if (count > 2) {
            smsCount.classList.remove('text-info', 'bg-info');
            smsCount.classList.add('text-warning', 'bg-warning');
        } else {
            smsCount.classList.remove('text-warning', 'bg-warning');
            smsCount.classList.add('text-info', 'bg-info');
        }
        
        // Animation du badge quand on approche de la limite
        if (length > 300) {
            charCount.classList.remove('text-primary', 'bg-primary');
            charCount.classList.add('text-danger', 'bg-danger');
            charCount.classList.add('pulse-badge');
        } else {
            charCount.classList.remove('text-danger', 'bg-danger', 'pulse-badge');
            charCount.classList.add('text-primary', 'bg-primary');
        }
    }
    
    customMessageTextarea.addEventListener('input', updateCounter);
    
    // Validation du formulaire
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Animation de secousse en cas d'erreur
                form.classList.add('shake-animation');
                setTimeout(() => {
                    form.classList.remove('shake-animation');
                }, 500);
            }
            
            form.classList.add('was-validated');
        }, false);
    });
});
</script> 