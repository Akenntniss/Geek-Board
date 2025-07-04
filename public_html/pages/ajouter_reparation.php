<?php
// Inclure la configuration de session avant de démarrer la session
require_once __DIR__ . '/../config/session_config.php';
// La session est déjà démarrée dans session_config.php, pas besoin de session_start() ici

// Inclure la configuration pour la gestion des sous-domaines
require_once __DIR__ . '/../config/subdomain_config.php';
// Le sous-domaine est détecté et la session est configurée avec le magasin correspondant

// Définir le chemin de base seulement s'il n'est pas déjà défini (éviter les conflits avec index.php)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/..');
}

// Inclure les fichiers de configuration et de connexion à la base de données
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/includes/functions.php';

// Code de débogage - Journaliser les variables POST et SESSION
error_log("============= DÉBUT AJOUTER_REPARATION =============");
error_log("SESSION: " . print_r($_SESSION, true));
error_log("POST: " . print_r($_POST, true));
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);

// Déboguer le shop_id en session
if (isset($_SESSION['shop_id'])) {
    error_log("MAGASIN SÉLECTIONNÉ (SESSION): " . $_SESSION['shop_id']);
} else {
    error_log("ALERTE: Aucun magasin sélectionné en session!");
}

// Configuration terminée - la connexion DB correcte devrait maintenant être disponible automatiquement

// Initialiser la connexion à la base de données du magasin
$shop_pdo = getShopDBConnection();

// Vérifier si la connexion a été établie correctement
if ($shop_pdo === null) {
    error_log("ERREUR CRITIQUE: Impossible d'établir une connexion initiale à la base de données du magasin");
    // Si nous sommes dans une requête AJAX, renvoyer une erreur JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    } else {
        // Sinon, définir un message d'erreur et rediriger
        if (function_exists('set_message')) {
            set_message("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.", "danger");
        }
        if (function_exists('redirect')) {
            redirect('accueil');
            exit;
        } else {
            // Fallback si redirect n'est pas disponible
            echo '<div class="alert alert-danger">Erreur de connexion à la base de données. Veuillez réessayer ou contacter l\'administrateur.</div>';
            exit;
        }
    }
}

// Vérifier si la fonction getShopDBConnection est disponible
if (!function_exists('getShopDBConnection')) {
    error_log("ERREUR CRITIQUE: La fonction getShopDBConnection() n'est pas disponible");
}
if (!function_exists('cleanInput')) {
    error_log("ERREUR CRITIQUE: La fonction cleanInput() n'est pas disponible");
}
if (!function_exists('set_message')) {
    error_log("ERREUR CRITIQUE: La fonction set_message() n'est pas disponible");
}
if (!function_exists('redirect')) {
    error_log("ERREUR CRITIQUE: La fonction redirect() n'est pas disponible");
}
if (!function_exists('send_sms')) {
    error_log("AVERTISSEMENT: La fonction send_sms() n'est pas disponible - Les SMS ne seront pas envoyés");
}

// Vérifier si la page est déjà chargée (pour éviter les inclusions multiples)
if (defined('PAGE_AJOUTER_REPARATION_LOADED')) {
    echo '<div class="alert alert-danger">Erreur: La page est déjà chargée une fois. Vérifiez votre système d\'inclusion.</div>';
    return;
}
define('PAGE_AJOUTER_REPARATION_LOADED', true);

// Récupérer la liste des clients pour le formulaire
$shop_pdo = getShopDBConnection();

$stmt = $shop_pdo->query("SELECT id, nom, prenom, telephone FROM clients ORDER BY nom, prenom");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire d'ajout de réparation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Débogage - Afficher toutes les données POST et SESSION
    error_log("========== TRAITEMENT FORMULAIRE POST ==========");
    error_log("SESSION: " . print_r($_SESSION, true));
    error_log("POST complet: " . print_r($_POST, true));
    
    // Vérifier les informations de la base de données du magasin
    try {
        $main_pdo = null;
        // Vérification de la base de données actuellement utilisée
        try {
            $shop_pdo = getShopDBConnection();
            $db_name_stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
            $db_result = $db_name_stmt->fetch(PDO::FETCH_ASSOC);
            error_log("BD INITIALE dans ajouter_reparation: " . ($db_result['current_db'] ?? 'Inconnue'));
            
            // S'assurer que shop_pdo utilise bien la connexion au magasin
            if (isset($_SESSION['shop_id'])) {
                error_log("SESSION SHOP_ID: " . $_SESSION['shop_id']);
                // On s'assure que shop_pdo est bien la connexion au magasin
                $shop_pdo = getShopDBConnection();
                
                // Vérifier après récupération
                $db_check = $shop_pdo->query("SELECT DATABASE() as current_db");
                $db_info = $db_check->fetch(PDO::FETCH_ASSOC);
                error_log("APRÈS RÉCUPÉRATION avec getShopDBConnection(): " . ($db_info['current_db'] ?? 'Inconnue'));
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la vérification de la base de données: " . $e->getMessage());
        }
        
        if (function_exists('getMainDBConnection')) {
            $main_pdo = getMainDBConnection();
            error_log("Connexion principale (main_pdo) obtenue avec succès");
            
            // Vérifier que $main_pdo n'est pas null avant de l'utiliser
            if ($main_pdo === null) {
                error_log("ERREUR CRITIQUE: $main_pdo est null lors de la récupération des infos du magasin");
                // Nous ne sommes pas dans une boucle, donc ne pas utiliser break
                set_message("Erreur de connexion à la base de données principale. Veuillez contacter l'administrateur.", "danger");
                return; // Sortir du bloc de code courant
            }
            
            // Récupérer les infos du magasin
            if (isset($_SESSION['shop_id'])) {
                $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
                $stmt->execute([$_SESSION['shop_id']]);
                $shop_info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($shop_info) {
                    error_log("INFO MAGASIN SÉLECTIONNÉ: " . json_encode($shop_info));
                } else {
                    error_log("ERREUR: Magasin avec ID=" . $_SESSION['shop_id'] . " non trouvé dans la base principale!");
                }
            }
        } else {
            error_log("ERREUR: Fonction getMainDBConnection() non disponible");
        }
    } catch (Exception $e) {
        error_log("ERREUR lors du débogage des connexions: " . $e->getMessage());
    }
    
    // Vérifier les champs clés
    $champs_requis = ['client_id', 'type_appareil', 'modele', 'description_probleme', 'prix_reparation'];
    $champs_manquants = [];
    foreach ($champs_requis as $champ) {
        if (!isset($_POST[$champ]) || empty($_POST[$champ])) {
            $champs_manquants[] = $champ;
        }
    }
    
    if (!empty($champs_manquants)) {
        error_log("ALERTE: Champs requis manquants: " . implode(', ', $champs_manquants));
    } else {
        error_log("Tous les champs requis sont présents");
    }
    
    // Débogage - Vérifier la connexion à la base de données
    $shop_pdo = getShopDBConnection();
    if (!$shop_pdo) {
        error_log("ERREUR CRITIQUE: \$shop_pdo n'est pas disponible dans ajouter_reparation.php");
        set_message("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.", "danger");
        // Continuer pour voir les autres erreurs potentielles
    } else {
        error_log("Connexion \$shop_pdo disponible dans ajouter_reparation.php");
        try {
            $test_query = $shop_pdo->query("SELECT 1");
            error_log("Test de requête avec \$shop_pdo réussi");
        } catch (PDOException $e) {
            error_log("Erreur lors du test de \$shop_pdo: " . $e->getMessage());
        }
    }
    
    // Récupérer et nettoyer les données du formulaire
    $client_id = (int)$_POST['client_id'];
    $type_appareil = cleanInput($_POST['type_appareil']);
    $modele = cleanInput($_POST['modele']);
    $description_probleme = cleanInput($_POST['description_probleme']);
    $a_mot_de_passe = isset($_POST['a_mot_de_passe']) ? cleanInput($_POST['a_mot_de_passe']) : 'non';
    $mot_de_passe = ($a_mot_de_passe === 'oui') ? cleanInput($_POST['mot_de_passe']) : '';
    $prix_reparation = (float)$_POST['prix_reparation'];
    
    // Récupérer la note interne si elle existe
    $a_note_interne = isset($_POST['a_note_interne']) ? cleanInput($_POST['a_note_interne']) : 'non';
    $notes_techniques = ($a_note_interne === 'oui' && isset($_POST['notes_techniques'])) ? cleanInput($_POST['notes_techniques']) : '';
    
    // Récupérer le statut à partir du bouton cliqué
    if (isset($_POST['statut'])) {
        $statut = cleanInput($_POST['statut']);
        error_log("Statut récupéré de POST: " . $statut);
        
        // Vérifier que $shop_pdo n'est pas null avant de l'utiliser
        if ($shop_pdo === null) {
            error_log("ALERTE: $shop_pdo est null avant la requête de catégorie. Tentative de reconnexion.");
            $shop_pdo = getShopDBConnection();
            
            // Vérifier à nouveau après la tentative de reconnexion
            if ($shop_pdo === null) {
                error_log("ERREUR CRITIQUE: Impossible de rétablir la connexion à la base de données du magasin.");
                set_message("Erreur de connexion à la base de données. Veuillez contacter l'administrateur ou réessayer.", "danger");
                // Rediriger pour éviter l'erreur
                redirect('reparations');
                exit;
            }
        }
        
        // Récupérer la catégorie_id correspondante au statut
        $stmt_categorie = $shop_pdo->prepare("SELECT categorie_id FROM statuts WHERE nom = ?");
        $stmt_categorie->execute([$statut]);
        $categorie_id = $stmt_categorie->fetchColumn();
        
        if (!$categorie_id) {
            // Si pas de catégorie trouvée, utiliser une valeur par défaut
            error_log("Aucune catégorie trouvée pour le statut: " . $statut);
            $categorie_id = 1; // Valeur par défaut
        }
    } else {
        // Valeur par défaut si aucun statut n'est spécifié
        $statut = 'nouvelle_intervention';
        $categorie_id = 1; // Valeur par défaut
        error_log("Statut par défaut utilisé: " . $statut);
    }
    
    // On garde le statut tel quel, sans conversion
    $statutForDB = $statut;
    error_log("Statut utilisé pour la base de données: " . $statutForDB);
    
    // Validation des données
    $errors = [];
    
    if (empty($client_id)) {
        $errors[] = "Veuillez sélectionner un client.";
    }
    
    if (empty($type_appareil)) {
        $errors[] = "Le type d'appareil est obligatoire.";
    }
    
    if (empty($modele)) {
        $errors[] = "Le modèle est obligatoire.";
    }
    
    if (empty($description_probleme)) {
        $errors[] = "La description du problème est obligatoire.";
    }
    
    if ($a_mot_de_passe === 'oui' && empty($mot_de_passe)) {
        $errors[] = "Le mot de passe est obligatoire si l'appareil en possède un.";
    }
    
    // Vérification de la photo - ne pas bloquer si pas de photo
    if (isset($_POST['photo_appareil']) && !empty($_POST['photo_appareil'])) {
        $photo_data = $_POST['photo_appareil'];
        if (strpos($photo_data, ';') === false || strpos($photo_data, ',') === false) {
            error_log("Photo fournie mais format incorrect - on continue sans photo");
            // Ne pas ajouter d'erreur, simplement logger et continuer sans photo
        }
    }
    
    // Si pas d'erreurs, insérer la réparation dans la base de données
    if (empty($errors)) {
        try {
            // Vérification de la structure de la table
            try {
                $shop_pdo = getShopDBConnection();
                $tableCheck = $shop_pdo->query("DESCRIBE reparations");
                $columns = $tableCheck->fetchAll(PDO::FETCH_ASSOC);
                $statutColumn = null;
                
                foreach ($columns as $column) {
                    if ($column['Field'] === 'statut') {
                        $statutColumn = $column;
                        break;
                    }
                }
                
                if ($statutColumn) {
                    error_log("Structure du champ statut: " . print_r($statutColumn, true));
                    
                    // Si c'est un ENUM, extraire les valeurs possibles
                    if (strpos($statutColumn['Type'], 'enum') === 0) {
                        preg_match("/enum\((.*)\)/", $statutColumn['Type'], $matches);
                        if (isset($matches[1])) {
                            $enumValues = str_getcsv($matches[1], ',', "'");
                            error_log("Valeurs autorisées pour statut: " . implode(', ', $enumValues));
                            
                            // Vérifier si notre valeur est dans la liste
                            $foundValue = false;
                            foreach ($enumValues as $value) {
                                if (strtolower(trim($value, "'")) === strtolower($statutForDB)) {
                                    $foundValue = true;
                                    break;
                                }
                            }
                            
                            if (!$foundValue) {
                                error_log("ATTENTION: La valeur '$statutForDB' n'est pas dans les valeurs acceptées pour le champ statut!");
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Erreur lors de la vérification de la structure de la table: " . $e->getMessage());
            }
            
            // Traitement de la photo
            $photo_path = null;
            if (!empty($_POST['photo_appareil'])) {
                $photo_data = $_POST['photo_appareil'];
                error_log("Données photo reçues, longueur: " . strlen($photo_data) . " caractères");
                
                // Vérifier l'existence et les permissions du dossier assets/images
                $base_dir = __DIR__ . '/../assets/';
                $images_dir = $base_dir . 'images/';
                
                if (!file_exists($base_dir)) {
                    error_log("DOSSIER PARENT BASE NON EXISTANT: " . $base_dir . " - Tentative de création");
                    if (!mkdir($base_dir, 0777, true)) {
                        error_log("ÉCHEC création du dossier de base: " . $base_dir);
                    }
                }
                
                if (!file_exists($images_dir)) {
                    error_log("DOSSIER IMAGES NON EXISTANT: " . $images_dir . " - Tentative de création");
                    if (!mkdir($images_dir, 0777, true)) {
                        error_log("ÉCHEC création du dossier images: " . $images_dir);
                    }
                }
                
                // Vérifier que la photo est correctement formatée (doit contenir un point-virgule pour le format base64)
                if (strpos($photo_data, ';') !== false && strpos($photo_data, ',') !== false) {
                    // Extraire les données binaires de l'image
                    list($type, $data_part) = explode(';', $photo_data);
                    error_log("Type de données photo: " . $type);
                    
                    if (!empty($data_part)) {
                        list(, $base64_data) = explode(',', $data_part);
                        
                        if (!empty($base64_data)) {
                            $decoded_data = base64_decode($base64_data);
                            
                            // Vérifier que le décodage a réussi
                            if ($decoded_data !== false) {
                                // Créer le dossier d'upload s'il n'existe pas
                                $upload_dir = __DIR__ . '/../assets/images/reparations/';
                                error_log("Chemin absolu du dossier upload: " . $upload_dir);
                                
                                if (!file_exists($upload_dir)) {
                                    error_log("Le dossier d'upload n'existe pas, tentative de création");
                                    if (mkdir($upload_dir, 0777, true)) {
                                        error_log("Dossier d'upload créé avec succès: " . $upload_dir);
                                    } else {
                                        error_log("ERREUR: Impossible de créer le dossier d'upload: " . $upload_dir);
                                        error_log("Permissions actuelles: " . substr(sprintf('%o', fileperms(dirname($upload_dir))), -4));
                                    }
                                } else {
                                    error_log("Le dossier d'upload existe déjà");
                                    // Vérifier les permissions d'écriture
                                    if (is_writable($upload_dir)) {
                                        error_log("Le dossier d'upload a les permissions d'écriture");
                                    } else {
                                        error_log("ERREUR: Le dossier d'upload n'a pas les permissions d'écriture");
                                        chmod($upload_dir, 0777);
                                        error_log("Tentative de modification des permissions à 777");
                                    }
                                }
                                
                                // Générer un nom unique pour la photo
                                $photo_name = uniqid('repair_') . '.jpg';
                                $photo_path_abs = $upload_dir . $photo_name;
                                $photo_path = 'assets/images/reparations/' . $photo_name; // Chemin relatif pour la BDD
                                
                                error_log("Tentative d'enregistrement de la photo: " . $photo_path_abs);
                                error_log("Taille des données décodées: " . strlen($decoded_data) . " bytes");
                                
                                // Sauvegarder la photo
                                $save_result = file_put_contents($photo_path_abs, $decoded_data);
                                if ($save_result === false) {
                                    error_log("ERREUR lors de l'enregistrement de la photo avec file_put_contents");
                                    error_log("Dernier message d'erreur PHP: " . error_get_last()['message']);
                                    $photo_path = null;
                                } else {
                                    error_log("Photo enregistrée avec succès: " . $photo_path_abs . " (" . $save_result . " bytes écrits)");
                                }
                            } else {
                                error_log("Échec du décodage base64 de la photo");
                            }
                        } else {
                            error_log("Données base64 vides après split sur ','");
                        }
                    } else {
                        error_log("Partie de données vide après split sur ';'");
                    }
                } else {
                    error_log("Format de données photo invalide, manque ';' ou ','");
                }
            } else {
                error_log("Aucune photo fournie dans le formulaire");
            }

            // Vérifier que $shop_pdo n'est pas null avant de l'utiliser pour l'insertion
            if ($shop_pdo === null) {
                error_log("ALERTE: $shop_pdo est null avant l'insertion de la réparation. Tentative de reconnexion.");
                $shop_pdo = getShopDBConnection();
                
                // Vérifier à nouveau après la tentative de reconnexion
                if ($shop_pdo === null) {
                    error_log("ERREUR CRITIQUE: Impossible de rétablir la connexion à la base de données du magasin pour l'insertion.");
                    set_message("Erreur de connexion à la base de données. Veuillez contacter l'administrateur ou réessayer.", "danger");
                    // Rediriger pour éviter l'erreur
                    redirect('reparations');
                    exit;
                }
            }

            $stmt = $shop_pdo->prepare("
                INSERT INTO reparations (client_id, type_appareil, modele, description_probleme, 
                mot_de_passe, prix_reparation, date_reception, statut, photo_appareil, commande_requise, statut_categorie, notes_techniques) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)
            ");
            
            // Débogage - Afficher les valeurs avant exécution
            error_log("Valeurs pour l'insertion: " . 
                      "client_id=" . $client_id . ", " .
                      "type_appareil=" . $type_appareil . ", " .
                      "modele=" . $modele . ", " .
                      "statut=" . $statutForDB . ", " .
                      "statut_categorie=" . $categorie_id . ", " .
                      "notes_techniques=" . $notes_techniques);
            
            try {
                $stmt->execute([
                    $client_id, 
                    $type_appareil, 
                    $modele, 
                    $description_probleme,
                    $mot_de_passe,
                    $prix_reparation,
                    $statutForDB,
                    $photo_path,
                    isset($_POST['commande_requise']) ? 1 : 0,
                    $categorie_id,
                    $notes_techniques
                ]);
                
                error_log("Insertion réussie dans la table reparations");
                
                // Vérifier la base de données après insertion
                try {
                    $db_name_after = $shop_pdo->query("SELECT DATABASE() as current_db");
                    $db_after = $db_name_after->fetch(PDO::FETCH_ASSOC);
                    error_log("APRÈS INSERTION: Base de données utilisée = " . ($db_after['current_db'] ?? 'Inconnue'));
                } catch (Exception $e) {
                    error_log("Erreur après insertion: " . $e->getMessage());
                }
                
                $reparation_id = $shop_pdo->lastInsertId();
                error_log("ID de la réparation insérée: " . $reparation_id);
                
                // Ajoutez ces lignes de debug
                error_log("Insertion dans la table reparations - SQL State: " . $stmt->errorCode());
                error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                error_log("Base de données utilisée pour l'insertion: " . $shop_pdo->query("SELECT DATABASE()")->fetchColumn());
                error_log("Shop ID en session: " . (isset($_SESSION['shop_id']) ? $_SESSION['shop_id'] : 'Non défini'));
                
                // Vérifier directement la présence de la réparation
                $check_stmt = $shop_pdo->prepare("SELECT COUNT(*) FROM reparations WHERE id = ?");
                $check_stmt->execute([$reparation_id]);
                $exists = $check_stmt->fetchColumn();
                error_log("Vérification de l'existence de la réparation ID $reparation_id: " . ($exists ? "EXISTE" : "N'EXISTE PAS"));
                
            } catch (PDOException $e) {
                error_log("Erreur SQL lors de l'insertion: " . $e->getMessage());
                error_log("Code d'erreur SQL: " . $e->getCode());
                // Récupérer plus d'informations sur l'erreur
                $errorInfo = $stmt->errorInfo();
                error_log("SQLSTATE: " . $errorInfo[0]);
                error_log("Code d'erreur du pilote: " . $errorInfo[1]);
                error_log("Message d'erreur du pilote: " . $errorInfo[2]);
                throw $e;
            }

            // Enregistrement du log de création de la réparation
            try {
                // Vérifier que $shop_pdo n'est pas null avant d'insérer le log
                if ($shop_pdo === null) {
                    error_log("ALERTE: $shop_pdo est null avant l'insertion du log. Tentative de reconnexion.");
                    $shop_pdo = getShopDBConnection();
                    
                    // Vérifier à nouveau après la tentative de reconnexion
                    if ($shop_pdo === null) {
                        error_log("ERREUR: Impossible de rétablir la connexion pour l'insertion du log.");
                        // Continuer malgré l'erreur (le log n'est pas critique)
                    }
                }
                
                // Procéder seulement si la connexion est valide
                if ($shop_pdo !== null) {
                    $log_stmt = $shop_pdo->prepare("
                        INSERT INTO reparation_logs 
                        (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) 
                        VALUES (?, ?, ?, NULL, ?, ?)
                    ");
                    
                    $log_stmt->execute([
                        $reparation_id,
                        $_SESSION['user_id'],
                        'demarrage', // Type d'action pour une création
                        $statutForDB, // Statut après (statut initial)
                        'Création d\'une nouvelle réparation' . ($a_note_interne === 'oui' ? ' avec note interne' : '')
                    ]);
                    
                    error_log("Log de création de réparation ajouté avec succès");
                }
            } catch (PDOException $e) {
                error_log("Erreur lors de l'ajout du log de création: " . $e->getMessage());
            }

            // Si une note interne a été ajoutée, enregistrer un log spécifique
            if ($a_note_interne === 'oui' && !empty($notes_techniques)) {
                try {
                    // Vérifier que $shop_pdo n'est pas null avant d'insérer la note
                    if ($shop_pdo === null) {
                        error_log("ALERTE: $shop_pdo est null avant l'insertion de la note. Tentative de reconnexion.");
                        $shop_pdo = getShopDBConnection();
                        
                        // Vérifier à nouveau après la tentative de reconnexion
                        if ($shop_pdo === null) {
                            error_log("ERREUR: Impossible de rétablir la connexion pour l'insertion de la note.");
                            // Continuer malgré l'erreur (la note n'est pas critique)
                            return; // Utiliser return au lieu de continue
                        }
                    }
                    
                    // Procéder seulement si la connexion est valide
                    if ($shop_pdo !== null) {
                        $log_note_stmt = $shop_pdo->prepare("
                            INSERT INTO reparation_logs 
                            (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        
                        $log_note_stmt->execute([
                            $reparation_id,
                            $_SESSION['user_id'],
                            'ajout_note', // Type d'action pour une note
                            $statutForDB,
                            $statutForDB, // Le statut ne change pas
                            'Note interne ajoutée: ' . substr($notes_techniques, 0, 100) . (strlen($notes_techniques) > 100 ? '...' : '')
                        ]);
                        
                        error_log("Log d'ajout de note interne créé avec succès");
                    }
                } catch (PDOException $e) {
                    error_log("Erreur lors de l'ajout du log de note interne: " . $e->getMessage());
                }
            }

            set_message("Réparation ajoutée avec succès!", "success");
            
            // NOUVELLE APPROCHE POUR L'ENVOI DE SMS - Bypass du problème de lastInsertId()
            error_log("===== DÉBUT NOUVELLE APPROCHE SMS =====");
            try {
                // Récupérer l'ID de la dernière réparation insérée pour ce client
                $query_id = $shop_pdo->prepare("
                    SELECT id FROM reparations 
                    WHERE client_id = ? AND type_appareil = ? AND modele = ? 
                    ORDER BY date_reception DESC LIMIT 1
                ");
                $query_id->execute([$client_id, $type_appareil, $modele]);
                $real_repair_id = $query_id->fetchColumn();
                
                error_log("ID réparation récupéré via requête directe: " . ($real_repair_id ?: 'Non trouvé'));
                
                // Si une commande est requise, créer la commande de pièces MAINTENANT qu'on a l'ID
                if ($real_repair_id && isset($_POST['commande_requise'])) {
                    error_log("DEBUG COMMANDE: Création de la commande avec real_repair_id: $real_repair_id");
                    try {
                        // Générer une référence unique
                        $reference = 'CMD-' . date('Ymd') . '-' . uniqid();
                        
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO commandes_pieces (
                                reference,
                                client_id,
                                reparation_id,
                                fournisseur_id,
                                nom_piece,
                                description,
                                quantite,
                                prix_estime
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $reference,
                            $client_id,
                            $real_repair_id, // Utiliser $real_repair_id au lieu de $reparation_id
                            $_POST['fournisseur_id'],
                            $_POST['nom_piece'],
                            $_POST['reference_piece'],
                            $_POST['quantite'],
                            $_POST['prix_piece']
                        ]);
                        
                        $commande_id = $shop_pdo->lastInsertId();
                        error_log("DEBUG COMMANDE: Commande créée avec succès, ID: $commande_id, Réf: $reference");
                        
                        // Ajouter un log pour la création de commande
                        try {
                            $log_commande_stmt = $shop_pdo->prepare("
                                INSERT INTO reparation_logs 
                                (reparation_id, employe_id, action_type, statut_avant, statut_apres, details) 
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            
                            $log_commande_stmt->execute([
                                $real_repair_id,
                                $_SESSION['user_id'],
                                'autre', // Type d'action pour une commande
                                'nouvelle_intervention',
                                'nouvelle_intervention', // Le statut ne change pas
                                'Commande de pièces créée: ' . $_POST['nom_piece'] . ' (Réf: ' . $reference . ')'
                            ]);
                            
                            error_log("DEBUG COMMANDE: Log de création de commande ajouté avec succès");
                        } catch (PDOException $e) {
                            error_log("DEBUG COMMANDE: Erreur lors de l'ajout du log de commande: " . $e->getMessage());
                        }
                    } catch (PDOException $e) {
                        error_log("DEBUG COMMANDE: ERREUR lors de la création de la commande de pièces: " . $e->getMessage());
                        error_log("DEBUG COMMANDE: Trace de l'erreur: " . $e->getTraceAsString());
                    }
                }
                
                if ($real_repair_id) {
                    // Configuration pour les logs
                    $log_dir = __DIR__ . '/../logs';
                    if (!is_dir($log_dir)) {
                        mkdir($log_dir, 0755, true);
                    }
                    $log_file = $log_dir . '/sms_debug_nouveau_' . date('Y-m-d') . '.log';
                    
                    $log_message = function($message) use ($log_file) {
                        file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
                    };
                    
                    $log_message("Début envoi SMS pour réparation ID: $real_repair_id");
                    
                    // Récupérer les infos client et réparation dans une seule requête
                    $query_info = $shop_pdo->prepare("
                        SELECT 
                            c.telephone, c.nom, c.prenom, 
                            r.type_appareil, r.modele, r.prix_reparation, r.date_reception
                        FROM clients c
                        JOIN reparations r ON r.client_id = c.id
                        WHERE r.id = ?
                    ");
                    $query_info->execute([$real_repair_id]);
                    $info = $query_info->fetch(PDO::FETCH_ASSOC);

                    if (!$info || empty($info['telephone'])) {
                        $log_message("ERREUR: Informations client ou téléphone manquantes");
                        throw new Exception("Informations client ou téléphone manquantes");
                    }

                    // Nettoyer et formater le numéro de téléphone
                    $telephone = preg_replace('/[^0-9+]/', '', $info['telephone']);
                    
                    // Si le numéro commence par 0, le remplacer par +33
                    if (substr($telephone, 0, 1) === '0') {
                        $telephone = '+33' . substr($telephone, 1);
                    }
                    // Si le numéro commence par 33, ajouter le +
                    elseif (substr($telephone, 0, 2) === '33') {
                        $telephone = '+' . $telephone;
                    }
                    // Si le numéro ne commence pas par +, l'ajouter
                    elseif (substr($telephone, 0, 1) !== '+') {
                        $telephone = '+' . $telephone;
                    }

                    // S'assurer que nous avons exactement 9 chiffres après le +33
                    if (substr($telephone, 0, 3) === '+33') {
                        $digits = substr($telephone, 3);
                        // Si plus de 9 chiffres, ne garder que les 9 derniers
                        if (strlen($digits) > 9) {
                            $telephone = '+33' . substr($digits, -9);
                        }
                        // Si moins de 9 chiffres, le numéro est invalide
                        elseif (strlen($digits) < 9) {
                            $log_message("ERREUR: Numéro trop court après +33: $telephone");
                            throw new Exception("Format de numéro de téléphone invalide (trop court)");
                        }
                    }

                    // Vérifier que le numéro a le bon format (+33 suivi de 9 chiffres)
                    if (!preg_match('/^\+33[0-9]{9}$/', $telephone)) {
                        $log_message("ERREUR: Format de numéro invalide: $telephone");
                        throw new Exception("Format de numéro de téléphone invalide");
                    }

                    $log_message("Numéro formaté: $telephone");

                    // Préparer le message avec les variables remplacées
                    $message = "Bonjour {$info['prenom']}, votre réparation #$real_repair_id a été enregistrée. ";
                    $message .= "Appareil: {$info['type_appareil']} {$info['modele']}. ";
                    if (!empty($info['prix_reparation'])) {
                        $message .= "Prix estimé: " . number_format($info['prix_reparation'], 2, ',', ' ') . "€. ";
                    }
                    $message .= "Nous vous tiendrons informé de l'avancement.";

                    $log_message("Message préparé: $message");

                    // Envoyer le SMS via votre API personnalisée
                    $ch = curl_init('http://168.231.85.4:3001/api/messages/send');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        'recipient' => $telephone,
                        'message' => $message,
                        'priority' => 'normal'
                    ]));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json'
                    ]);

                    $response = curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curl_error = curl_error($ch);
                    curl_close($ch);

                    $log_message("Réponse API (HTTP $http_code): $response");

                    // Décoder la réponse JSON
                    $response_data = json_decode($response, true);
                    
                    if ($http_code >= 200 && $http_code < 300 && isset($response_data['success']) && $response_data['success']) {
                        // Enregistrer l'envoi dans la base de données
                        $stmt = $shop_pdo->prepare("
                            INSERT INTO reparation_sms (reparation_id, template_id, telephone, message, date_envoi)
                            VALUES (?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $real_repair_id,
                            1, // ID du template par défaut
                            $telephone,
                            $message
                        ]);
                        $log_message("SMS envoyé avec succès ! ID: " . ($response_data['data']['id'] ?? 'N/A'));
                        $log_message("SMS enregistré dans la base de données");
                    } else {
                        $error_message = $response_data['message'] ?? 'Erreur inconnue';
                        $log_message("ERREUR: Échec de l'envoi du SMS - Code HTTP: $http_code, Erreur: $curl_error, Message: $error_message");
                        throw new Exception("Échec de l'envoi du SMS: $error_message");
                    }
                } else {
                    error_log("Impossible de récupérer l'ID de réparation pour l'envoi du SMS");
                }
            } catch (Exception $e) {
                error_log("Exception lors de la nouvelle approche SMS: " . $e->getMessage());
            }
            error_log("===== FIN NOUVELLE APPROCHE SMS =====");
            
            // Rediriger directement vers la page d'impression d'étiquette
            if (isset($real_repair_id) && is_numeric($real_repair_id) && $real_repair_id > 0) {
                // Utiliser le domaine actuel au lieu de mdgeek.top en dur
                $current_domain = $_SERVER['HTTP_HOST'];
                $redirect_url = "https://" . $current_domain . "/index.php?page=imprimer_etiquette&id=" . $real_repair_id;
                error_log("REDIRECTION: Vers $redirect_url");
                // Remplacer la redirection directe par une redirection JavaScript
                echo "<script>window.location.href = '$redirect_url';</script>";
                // Ajouter également une solution de secours au cas où JavaScript est désactivé
                echo '<noscript><meta http-equiv="refresh" content="0;url='.$redirect_url.'"></noscript>';
                exit;
            } else {
                // Utiliser la fonction redirect() qui utilise probablement aussi header()
                // Remplacer par une redirection JavaScript
                echo "<script>window.location.href = 'index.php?page=reparations';</script>";
                echo '<noscript><meta http-equiv="refresh" content="0;url=index.php?page=reparations"></noscript>';
                exit;
            }
        } catch (PDOException $e) {
            error_log("ERREUR PDO PRINCIPALE: " . $e->getMessage());
            set_message("Erreur lors de l'ajout de la réparation: " . $e->getMessage(), "danger");
        }
    } else {
        // Afficher les erreurs
        foreach ($errors as $error) {
            set_message($error, "danger");
        }
    }
}
?>

<!-- Inclusion du CSS spécialisé pour cette page en mode nuit -->
<link rel="stylesheet" href="assets/css/ajouter-reparation-dark.css">

<div class="container-fluid p-0" style="max-width: 100vw; overflow-x: hidden;">
    <div class="row justify-content-center g-0" style="width: 100%; margin: 0 auto;">
        <div class="col-12 col-lg-10 col-xl-8 px-0" style="display: flex; flex-direction: column; align-items: center;">
            <h4 class="page-title text-center my-3">Ajouter une réparation</h4>
            
            <div class="card mb-4" style="width: 92%; max-width: 900px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); border-radius: 15px; margin: 0 auto;">
                <div class="card-body">
                    <div class="progress mb-4" style="height: 10px;">
                        <div class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">Étape 1/4</div>
                    </div>
                    
                    <form id="rep_reparationForm" action="/index.php?page=ajouter_reparation" method="post" enctype="multipart/form-data">
                        <!-- Ajout d'un champ caché pour forcer un identifiant unique au formulaire -->
                        <input type="hidden" name="form_submission_id" value="<?php echo uniqid('rep_'); ?>">
                        <!-- Étape 1: Type d'appareil -->
                        <div id="rep_etape1" class="form-step">
                            <h5 class="mb-3">Type d'appareil</h5>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card text-center mb-3 type-appareil-card" data-type="Informatique">
                                        <div class="card-body py-4">
                                            <i class="fas fa-laptop fa-4x mb-3"></i>
                                            <h5>Appareil informatique</h5>
                                            <p class="mb-0 text-muted">Ordinateur, téléphone, tablette...</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card text-center mb-3 type-appareil-card" data-type="Trottinette">
                                        <div class="card-body py-4">
                                            <i class="fas fa-bolt fa-4x mb-3"></i>
                                            <h5>Trottinette électrique</h5>
                                            <p class="mb-0 text-muted">Tous types de trottinettes...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="type_appareil" id="rep_type_appareil" required>
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-primary next-step" style="min-width: 100px;" disabled>Suivant</button>
                            </div>
                        </div>
                        
                        <!-- Étape 2: Sélection du client -->
                        <div id="rep_etape2" class="form-step d-none">
                            <h5 class="mb-3">Recherche du client</h5>
                            
                            <!-- Zone de recherche optimisée pour mobile -->
                            <div class="mb-3">
                                <label class="form-label">Rechercher un client existant</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-search text-primary"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="rep_recherche_client_reparation" placeholder="Nom, prénom ou téléphone...">
                                    <button class="btn btn-primary rounded-end shadow-sm" type="button" id="rep_btn_recherche_client">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Message "aucun résultat" -->
                            <div id="rep_no_results" class="alert alert-warning d-none my-2">
                                Aucun client trouvé. <button type="button" class="btn btn-sm btn-outline-primary mt-1 d-block" id="rep_btn_nouveau_client">Créer un nouveau client</button>
                            </div>
                            
                            <!-- Client sélectionné -->
                            <div id="rep_client_selectionne" class="alert alert-info d-none mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div><strong>Client sélectionné:</strong> <span id="rep_nom_client_selectionne"></span></div>
                                    <button type="button" class="btn-close" id="rep_reset_client"></button>
                                </div>
                            </div>
                            
                            <!-- Conteneur des résultats de recherche pour mobile -->
                            <div id="rep_resultats_clients" class="d-none mb-3">
                                <div class="client-results-container">
                                    <div class="client-results-list" id="rep_liste_clients_mobile">
                                        <!-- Les résultats seront injectés ici -->
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="client_id" id="rep_client_id" required>
                            
                            <div class="d-flex justify-content-between flex-column flex-md-row">
                                <button type="button" class="btn btn-secondary prev-step mb-2 mb-md-0" style="min-width: 100px;">Précédent</button>
                                <button type="button" class="btn btn-primary next-step" id="rep_btn_etape2_suivant" style="min-width: 100px;" disabled>Suivant</button>
                            </div>
                        </div>
                        
                        <!-- Étape 3: Informations sur l'appareil et description du problème -->
                        <div id="rep_etape3" class="form-step d-none">
                            <h5 class="mb-3">Informations sur l'appareil</h5>
                            
                            <div class="mb-3">
                                <label for="rep_modele" class="form-label">Modèle de l'appareil *</label>
                                <input type="text" class="form-control" id="rep_modele" name="modele" required>
                                <div class="form-text">Indiquez le nom ou référence précise de l'appareil</div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">L'appareil a-t-il un mot de passe ? *</label>
                                <div class="d-flex password-buttons-container">
                                    <div class="flex-grow-1 me-2">
                                        <div class="card text-center h-100 mot-de-passe-card" data-value="oui">
                                            <div class="card-body d-flex flex-column justify-content-center p-3">
                                                <i class="fas fa-lock fa-2x mb-2 text-primary"></i>
                                                <h6 class="mb-1">Oui</h6>
                                                <p class="mb-0 text-muted small">Appareil protégé</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="card text-center h-100 mot-de-passe-card" data-value="non">
                                            <div class="card-body d-flex flex-column justify-content-center p-3">
                                                <i class="fas fa-unlock fa-2x mb-2 text-success"></i>
                                                <h6 class="mb-1">Non</h6>
                                                <p class="mb-0 text-muted small">Pas de mot de passe</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="a_mot_de_passe" id="rep_a_mot_de_passe" required>
                            </div>
                            
                            <div id="rep_champ_mot_de_passe" class="mb-4 d-none">
                                <label for="rep_mot_de_passe" class="form-label">Mot de passe de l'appareil *</label>
                                <input type="text" class="form-control" id="rep_mot_de_passe" name="mot_de_passe">
                                <div class="form-text">Ce mot de passe est nécessaire pour diagnostiquer l'appareil</div>
                            </div>
                            
                            <div id="rep_confirmation_sans_mdp" class="alert alert-warning mb-4 d-none">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attention :</strong> Sans mot de passe, nous pourrions être limités dans notre diagnostic.
                                <div class="mt-2">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="rep_check_responsabilite">
                                        <label class="form-check-label" for="rep_check_responsabilite">
                                            Je confirme avoir demandé le mot de passe au client et qu'il n'en a pas. J'assume la responsabilité de cette information.
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-danger" id="rep_btn_confirmer_sans_mdp">
                                        Je confirme sous ma responsabilité
                                    </button>
                                </div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <h5 class="mb-3">Description du problème</h5>
                            
                            <!-- Boutons de raccourci pour la description -->
                            <div class="mb-3" id="informatique_buttons" style="display: none;">
                                <label class="form-label">Raccourcis pour appareils informatiques :</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="alimentation">Alimentation</button>
                                    <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="ecran">Ecran</button>
                                    <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="autre-info">Autre</button>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="trottinette_buttons" style="display: none;">
                                <label class="form-label">Raccourcis pour trottinettes :</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="alimentation-trot">Alimentation</button>
                                    <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="cycle">Cycle</button>
                                    <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="electronique">Electronique</button>
                                    <button type="button" class="btn btn-outline-primary btn-problem-shortcut" data-problem-type="autre-trot">Autre</button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="rep_description_probleme" class="form-label">Description détaillée du problème *</label>
                                <textarea class="form-control" id="rep_description_probleme" name="description_probleme" rows="4" required></textarea>
                            </div>
                            
                            <hr class="my-3">
                            
                            <h5 class="mb-3">Note interne</h5>
                            <div class="mb-4">
                                <label class="form-label">Souhaitez-vous ajouter une information pour vos collègues ?</label>
                                <div class="d-flex note-interne-buttons-container">
                                    <div class="flex-grow-1 me-2">
                                        <div class="card text-center h-100 note-interne-card" data-value="oui">
                                            <div class="card-body d-flex flex-column justify-content-center p-3">
                                                <i class="fas fa-check fa-2x mb-2 text-success"></i>
                                                <h6 class="mb-1">Oui</h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="card text-center h-100 note-interne-card" data-value="non">
                                            <div class="card-body d-flex flex-column justify-content-center p-3">
                                                <i class="fas fa-times fa-2x mb-2 text-danger"></i>
                                                <h6 class="mb-1">Non</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="a_note_interne" id="rep_a_note_interne" value="non">
                            </div>
                            
                            <div id="rep_champ_note_interne" class="mb-4 d-none">
                                <label for="rep_notes_techniques" class="form-label">Note interne pour l'équipe *</label>
                                <textarea class="form-control" id="rep_notes_techniques" name="notes_techniques" rows="4"></textarea>
                                <div class="form-text">Cette note sera visible uniquement par l'équipe, pas par le client</div>
                            </div>
                            
                            <hr class="my-3">
                            
                            <h5 class="mb-3">Photo de l'appareil</h5>
                            <div class="mb-4">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="rep_photo_file" class="form-label">Ajouter une photo de l'appareil*</label>
                                        <input type="file" class="form-control" id="rep_photo_file" accept="image/*" capture>
                                        <div class="form-text mt-2">Prendre une photo ou choisir depuis la galerie</div>
                                    </div>
                                    
                                    <!-- Bouton Capturer (visible uniquement sur PC) -->
                                    <div class="col-12 desktop-only" id="capture_container">
                                        <div class="d-flex flex-wrap">
                                            <div class="me-3 mb-3">
                                                <button type="button" class="btn btn-secondary" id="rep_capture_photo">
                                                    <i class="fas fa-camera me-2"></i>Capturer avec la caméra PC
                                                </button>
                                            </div>
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-primary d-none" id="take_photo">
                                                    <i class="fas fa-check me-2"></i>Prendre la photo
                                                </button>
                                                <button type="button" class="btn btn-danger d-none" id="cancel_photo">
                                                    <i class="fas fa-times me-2"></i>Annuler
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Zone de caméra (initialement masquée) -->
                                        <div class="camera-container d-none mb-3" id="camera_container" style="max-width: 400px; height: 300px;">
                                            <video id="camera_feed" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover; display: block; background-color: #000; transform: translateZ(0); backface-visibility: hidden; -webkit-backface-visibility: hidden;"></video>
                                            <canvas id="camera_canvas" style="display: none;"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div id="photo_required" class="form-text text-danger mt-2 d-none">Une photo de l'appareil est requise</div>
                            </div>
                            <input type="hidden" name="photo_appareil" id="rep_photo_appareil">
                            
                            <div class="d-flex justify-content-between flex-column flex-md-row">
                                <button type="button" class="btn btn-secondary prev-step mb-2 mb-md-0" style="min-width: 100px;">Précédent</button>
                                <button type="button" class="btn btn-primary next-step" id="rep_btn_etape3_suivant" style="min-width: 100px;">Suivant</button>
                            </div>
                        </div>
                        
                        <!-- Étape 4: Tarification -->
                        <div id="rep_etape4" class="form-step d-none">
                            <h5 class="mb-3">Tarification</h5>
                            <div class="mb-4">
                                <label for="rep_prix_reparation" class="form-label">Prix estimé de la réparation *</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" class="form-control" id="rep_prix_reparation" name="prix_reparation" required>
                                    <span class="input-group-text">€</span>
                                </div>
                                <div class="form-text">Prix indicatif qui pourra être ajusté après diagnostic</div>
                            </div>

                            <!-- Section Commande de pièces -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-shopping-cart me-2"></i>
                                        Commande de pièces
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="rep_commande_requise" name="commande_requise">
                                            <label class="form-check-label" for="rep_commande_requise">Commande de pièces requise</label>
                                        </div>
                                    </div>

                                    <!-- Champs de commande (initialement masqués) -->
                                    <div id="rep_commande_fields" class="d-none">
                                        <div class="mb-3">
                                            <label for="rep_fournisseur" class="form-label">Fournisseur *</label>
                                            <select class="form-select" id="rep_fournisseur" name="fournisseur_id">
                                                <option value="">Sélectionner un fournisseur</option>
                                                <?php
                                                $stmt = $shop_pdo->query("SELECT id, nom FROM fournisseurs ORDER BY nom");
                                                while ($fournisseur = $stmt->fetch()) {
                                                    echo "<option value='{$fournisseur['id']}'>{$fournisseur['nom']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="rep_nom_piece" class="form-label">Nom du produit *</label>
                                            <input type="text" class="form-control" id="rep_nom_piece" name="nom_piece">
                                        </div>

                                        <div class="mb-3">
                                            <label for="rep_reference_piece" class="form-label">Référence du produit</label>
                                            <input type="text" class="form-control" id="rep_reference_piece" name="reference_piece">
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rep_quantite" class="form-label">Quantité *</label>
                                                    <input type="number" class="form-control" id="rep_quantite" name="quantite" min="1" value="1">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="rep_prix_piece" class="form-label">Prix (€) *</label>
                                                    <div class="input-group">
                                                        <input type="number" step="0.01" class="form-control" id="rep_prix_piece" name="prix_piece">
                                                        <span class="input-group-text">€</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            

                            <!-- Boutons de soumission -->
                            <div id="form_buttons" class="d-flex justify-content-between mt-4">
                                <div class="btn-group btn-group-mobile d-flex flex-column d-md-inline-flex flex-md-row" role="group">
                                    <button type="submit" name="statut" value="nouvelle_intervention" class="btn btn-primary mb-2 mb-md-0" id="btn_soumettre_reparation">
                                        <i class="fas fa-save me-2"></i>Enregistrer la réparation
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Message de confirmation caché -->
                            <div class="alert alert-info mt-3 d-none" id="submitting_message">
                                <i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter un nouveau client -->
<div class="modal fade" id="nouveauClientModal_reparation" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Ajouter un nouveau client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNouveauClient_reparation">
                    <?php if (isset($_SESSION['shop_id'])): ?>
                    <input type="hidden" id="nouveau_shop_id_reparation" name="shop_id" value="<?php echo $_SESSION['shop_id']; ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="nouveau_nom_reparation" class="form-label">Nom *</label>
                        <input type="text" class="form-control form-control-lg" id="nouveau_nom_reparation" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_prenom_reparation" class="form-label">Prénom *</label>
                        <input type="text" class="form-control form-control-lg" id="nouveau_prenom_reparation" required>
                    </div>
                    <div class="mb-3">
                        <label for="nouveau_telephone_reparation" class="form-label">Téléphone * <small class="text-muted">Format international : 331234567890</small></label>
                        <input type="tel" inputmode="tel" class="form-control form-control-lg" id="nouveau_telephone_reparation" placeholder="331234567890" pattern="[0-9]{11}" maxlength="11" required>
                        <div class="form-text">Format : 11 chiffres (ex: 331234567890)</div>
                    </div>
                    <!-- Suppression des champs email et adresse selon la demande -->
                </form>
            </div>
            <div class="modal-footer">
                <div class="d-flex w-100">
                    <button type="button" class="btn btn-secondary flex-grow-1 me-2" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary flex-grow-1" id="btn_sauvegarder_client_reparation">Sauvegarder</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.type-appareil-card {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid #dee2e6;
}
.type-appareil-card:hover {
    border-color: #0d6efd;
    transform: translateY(-5px);
}
.type-appareil-card.selected {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}
.mot-de-passe-card {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid #dee2e6;
}
.mot-de-passe-card:hover {
    transform: translateY(-5px);
}
.mot-de-passe-card.selected {
    border-color: #0d6efd;
    background-color: #f8f9fa;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
.note-interne-card {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid #dee2e6;
}
.note-interne-card:hover {
    transform: translateY(-5px);
}
.note-interne-card.selected {
    border-color: #0d6efd;
    background-color: #f8f9fa;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
.form-step {
    transition: all 0.3s;
}
#camera {
    width: 100%;
    max-height: 300px;
    object-fit: cover;
}
#photo_preview {
    width: 100%;
    max-height: 200px;
    object-fit: contain;
    border: 1px solid #dee2e6;
}

/* Styles pour la recherche client mobile */
.client-results-container {
    max-height: 60vh;
    overflow-y: auto;
    border-radius: 8px;
    margin-bottom: 15px;
}

.client-results-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.client-card {
    background-color: #fff;
    border-radius: 8px;
    padding: 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #0d6efd;
    display: flex;
    flex-direction: column;
}

.client-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.client-card-header h6 {
    margin: 0;
    font-weight: 600;
}

.client-card-info {
    display: flex;
    flex-direction: column;
    margin-bottom: 10px;
}

.client-card-info p {
    margin: 0;
    margin-bottom: 4px;
    font-size: 14px;
}

.client-card-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.loading-indicator {
    text-align: center;
    padding: 20px 0;
}

/* Styles pour la caméra et la capture photo */
.camera-container {
    position: relative;
    overflow: hidden;
    margin: 0;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    background-color: #000;
    width: 100%;
    height: auto;
    border-radius: 8px;
    transition: all 0.3s ease;
}

/* Animation pour l'apparition de la caméra */
@keyframes cameraFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.camera-container:not(.d-none) {
    animation: cameraFadeIn 0.3s ease forwards;
}

/* Styles spécifiques pour le flux vidéo */
#camera_feed {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    margin: 0 auto;
    background-color: #000;
    transform: translateZ(0);
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    border-radius: 8px;
}

/* Boutons de contrôle */
#take_photo, #cancel_photo {
    transition: all 0.2s ease;
}

#take_photo:hover, #cancel_photo:hover {
    transform: translateY(-2px);
}

#take_photo:not(.d-none), #cancel_photo:not(.d-none) {
    animation: cameraFadeIn 0.3s ease forwards;
}

/* Styles spécifiques pour le modal de caméra */
.camera-modal .modal-content {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    border: none;
}

.camera-modal .modal-body {
    padding: 0;
    background-color: #000;
}

.camera-modal .camera-container {
    box-shadow: none;
    background-color: #000;
    margin: 0;
    width: 100%;
    height: auto;
}

.camera-modal #camera_feed {
    display: block;
    width: 100%;
    height: auto;
    max-height: 480px;
    object-fit: cover;
    margin: 0 auto;
    background-color: #000;
    transform: translateZ(0); /* Empêche le clignotement sur certains navigateurs */
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.camera-modal .modal-footer {
    border-top: none;
    padding: 15px;
    justify-content: space-between;
    background-color: #f8f9fa;
}

.camera-modal .modal-header {
    background-color: #f8f9fa;
    border-bottom: none;
    padding: 15px;
}

.camera-modal #take_photo {
    min-width: 120px;
}

#rep_camera {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px;
    /* Styles spécifiques pour iOS */
    transform: translateZ(0);
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

#rep_photo_preview {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 8px;
}

#rep_takePhoto, #rep_retakePhoto {
    margin-top: 10px;
    padding: 8px 16px;
    font-weight: 500;
}

/* Améliorations mobiles et tablettes */
@media (max-width: 991px) {
    /* Centrer le formulaire */
    .card.mx-auto {
        width: 95% !important;
        max-width: 95% !important;
        margin: 0 auto !important;
    }
    
    #rep_reparationForm {
        max-width: 100%;
        margin: 0 auto;
    }
    
    /* Styles pour la caméra */
    .camera-container {
        width: 100%;
        max-width: 100%;
        border: 1px solid #dee2e6;
        background: #f8f9fa;
        margin-bottom: 10px;
        overflow: hidden;
        border-radius: 8px;
    }
    
    #rep_camera, #rep_photo_preview {
        max-width: 100%;
        max-height: 250px !important;
        border-radius: 8px;
        object-fit: cover;
        display: block;
        margin: 0 auto;
    }
    
    /* Améliorer les tailles des boutons de caméra pour le tactile */
    #rep_startCamera, #rep_takePhoto, #rep_retakePhoto {
        padding: 10px 16px;
        font-size: 15px;
        margin-bottom: 10px;
    }
}

/* Optimisations pour les mobiles moyens (494px) */
@media (max-width: 494px) {
    /* Styles généraux pour tout le formulaire */
    .card-body {
        padding: 12px !important;
    }
    
    .form-step h5 {
        font-size: 16px !important;
        margin-bottom: 10px !important;
    }
    
    /* Styles spécifiques pour l'étape 3 (informations appareil) */
    #rep_etape3 .form-control {
        font-size: 14px;
        padding: 10px;
        border-radius: 6px;
    }
    
    #rep_etape3 .form-text {
        font-size: 12px;
        margin-top: 4px;
    }
    
    #rep_etape3 textarea {
        min-height: 100px;
    }
    
    /* Boutons de mot de passe optimisés en côte à côte */
    #rep_etape3 .password-buttons-container {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }
    
    #rep_etape3 .mot-de-passe-card {
        height: 100%;
        transition: all 0.2s;
        margin-bottom: 0 !important;
    }
    
    #rep_etape3 .mot-de-passe-card .card-body {
        padding: 12px 8px !important;
        min-height: 110px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    #rep_etape3 .mot-de-passe-card i {
        font-size: 22px !important;
        margin-bottom: 6px !important;
    }
    
    #rep_etape3 .mot-de-passe-card h6 {
        font-size: 15px !important;
        margin-bottom: 4px !important;
    }
    
    #rep_etape3 .mot-de-passe-card p {
        font-size: 11px !important;
        line-height: 1.2 !important;
    }
    
    /* Alerte de confirmation sans mot de passe */
    #rep_etape3 #rep_confirmation_sans_mdp {
        padding: 12px !important;
    }
    
    #rep_etape3 #rep_btn_confirmer_sans_mdp {
        width: 100%;
        margin-top: 5px;
    }
    
    /* Zone de caméra optimisée */
    #rep_etape3 .camera-container {
        height: 220px !important;
        max-width: 100% !important;
        margin-bottom: 10px;
    }
    
    /* Boutons de caméra optimisés */
    #rep_etape3 .camera-controls {
        display: flex;
        justify-content: center;
        gap: 10px;
    }
    
    #rep_etape3 #rep_takePhoto, 
    #rep_etape3 #rep_retakePhoto {
        min-width: 120px;
    }
    
    /* Séparateurs */
    #rep_etape3 hr {
        margin: 15px 0 !important;
        opacity: 0.15;
    }
    
    /* Navigation */
    #rep_etape3 .d-flex.justify-content-between {
        margin-top: 20px;
    }
}

/* Optimisations pour les petits mobiles (428px) */
@media (max-width: 428px) {
    .container-fluid {
        padding: 0 !important;
    }
    
    .card {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
    
    .card-body {
        padding: 15px 10px !important;
    }
    
    /* Adapter les cartes de sélection */
    .type-appareil-card, .mot-de-passe-card, .note-interne-card {
        margin-bottom: 10px !important;
    }
    
    .type-appareil-card .card-body, .mot-de-passe-card .card-body, .note-interne-card .card-body {
        padding: 10px !important;
    }
    
    .type-appareil-card i, .mot-de-passe-card i, .note-interne-card i {
        font-size: 2em !important;
        margin-bottom: 5px !important;
    }
    
    .type-appareil-card h5, .mot-de-passe-card h5, .note-interne-card h5 {
        font-size: 16px !important;
        margin-bottom: 5px !important;
    }
    
    .type-appareil-card p, .mot-de-passe-card p, .note-interne-card p {
        font-size: 12px !important;
        line-height: 1.2 !important;
    }
    
    /* Ajuster les boutons */
    .btn-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        margin-bottom: 8px;
        border-radius: 4px !important;
    }
    
    /* Optimisation spécifique pour la recherche client mobile */
    .client-results-container {
        max-height: 50vh;
        margin-bottom: 10px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .client-card {
        margin-bottom: 0;
        border-radius: 0;
        border-bottom: 1px solid #e9ecef;
        border-left: 4px solid #0d6efd;
    }
    
    .client-card:last-child {
        border-bottom: none;
    }
    
    .client-card-header h6 {
        font-size: 15px;
    }
    
    .client-card-info p {
        font-size: 13px;
    }
    
    /* Améliorer le conteneur de la caméra */
    .camera-container {
        height: 180px !important;
    }
    
    /* Ajuster les contrôles de formulaire */
    .form-control, .form-select {
        font-size: 14px;
        padding: 8px;
    }
    
    .form-label {
        font-size: 14px;
        margin-bottom: 4px;
    }
    
    .form-text {
        font-size: 12px;
    }
    
    /* Améliorer la navigation entre étapes */
    .d-flex.justify-content-between {
        gap: 10px;
    }
    
    /* Ajuster la taille des boutons pour un meilleur toucher */
    button {
        min-height: 44px;
    }
    
    /* Améliorer les alertes */
    .alert {
        padding: 10px;
        font-size: 14px;
    }
    
    /* Adapter la barre de progression */
    .progress {
        height: 8px !important;
    }
    
    /* Ajuster la page-title */
    .page-title {
        font-size: 20px !important;
        margin: 10px 0 !important;
    }
    
    /* Ajuster les espaces entre les sections */
    hr {
        margin: 15px 0;
    }
    
    h5 {
        font-size: 16px !important;
    }
}

/* Styles spécifiques pour iOS */
@supports (-webkit-touch-callout: none) {
    .camera-container {
        z-index: 100;
        position: relative;
    }
    
    #rep_camera {
        z-index: 101;
    }
    
    #rep_takePhoto, #rep_retakePhoto {
        z-index: 102;
        position: relative;
        font-size: 16px;
        padding: 12px 20px;
        margin-top: 15px;
    }
    
    /* S'assurer que les boutons sont bien visibles sur iOS */
    .mt-2.text-center {
        position: relative;
        z-index: 103;
        margin-top: 15px !important;
    }
    
    /* Correction pour les problèmes d'image sur iOS PWA */
    #rep_photo_preview {
        width: 100% !important;
        height: auto !important;
        min-height: 200px !important;
        object-fit: contain !important;
        background-color: #f8f9fa;
        -webkit-transform: translateZ(0);
        transform: translateZ(0);
    }
    
    /* Forcer le rafraîchissement du rendu sur iOS */
    .photo-preview-container {
        -webkit-transform: translateZ(0);
        transform: translateZ(0);
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        perspective: 1000;
        -webkit-perspective: 1000;
    }
}

/* Styles pour le mode nuit */
.dark-mode .type-appareil-card,
.dark-mode .mot-de-passe-card,
.dark-mode .note-interne-card {
    background-color: #1f2937;
    border-color: #374151;
    color: #f8fafc;
}

.dark-mode .type-appareil-card:hover,
.dark-mode .mot-de-passe-card:hover,
.dark-mode .note-interne-card:hover {
    border-color: #60a5fa;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}

.dark-mode .type-appareil-card.selected,
.dark-mode .mot-de-passe-card.selected,
.dark-mode .note-interne-card.selected {
    border-color: #3b82f6;
    background-color: #2d3748;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

.dark-mode .card {
    background-color: #1f2937;
    border-color: #374151;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.dark-mode .card-body {
    background-color: #1f2937;
    color: #f8fafc;
}

.dark-mode .card-header {
    background-color: #111827;
    border-bottom-color: #374151;
}

.dark-mode .form-control,
.dark-mode .form-select,
.dark-mode .input-group-text {
    background-color: #111827;
    border-color: #374151;
    color: #f8fafc;
}

.dark-mode .form-control:focus,
.dark-mode .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
}

.dark-mode .input-group-text {
    color: #94a3b8;
}

.dark-mode .form-text {
    color: #94a3b8;
}

.dark-mode .progress {
    background-color: #374151;
}

.dark-mode .progress-bar {
    background-color: #3b82f6;
}

.dark-mode .text-muted {
    color: #94a3b8 !important;
}

.dark-mode .client-card {
    background-color: #1f2937;
    border-left-color: #3b82f6;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
}

.dark-mode .client-results-container {
    background-color: #111827;
}

.dark-mode .alert-info {
    background-color: rgba(59, 130, 246, 0.2);
    border-color: rgba(59, 130, 246, 0.3);
    color: #f8fafc;
}

.dark-mode .alert-warning {
    background-color: rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.3);
    color: #f8fafc;
}

.dark-mode .btn-primary {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.dark-mode .btn-success {
    background-color: #10b981;
    border-color: #10b981;
}

.dark-mode .btn-info {
    background-color: #0ea5e9;
    border-color: #0ea5e9;
}

.dark-mode .btn-secondary {
    background-color: #4b5563;
    border-color: #4b5563;
}

.dark-mode .btn-danger {
    background-color: #ef4444;
    border-color: #ef4444;
}

.dark-mode .btn-warning {
    background-color: #f59e0b;
    border-color: #f59e0b;
}

.dark-mode .btn-outline-primary {
    color: #60a5fa;
    border-color: #60a5fa;
}

.dark-mode .btn-outline-primary:hover {
    background-color: #3b82f6;
    color: #f8fafc;
}

.dark-mode .btn-outline-secondary {
    color: #94a3b8;
    border-color: #4b5563;
}

.dark-mode .btn-outline-secondary:hover {
    background-color: #4b5563;
    color: #f8fafc;
}

.dark-mode .bg-light {
    background-color: #111827 !important;
}

.dark-mode .modal-content {
    background-color: #1f2937;
    border-color: #374151;
}

.dark-mode .modal-header {
    background-color: #111827;
    border-bottom-color: #374151;
}

.dark-mode .modal-footer {
    background-color: #111827;
    border-top-color: #374151;
}

.dark-mode #camera {
    background-color: #111827;
}

.dark-mode .camera-container {
    background-color: #111827;
    border: 1px solid #374151;
}

.dark-mode .page-title {
    color: #f8fafc;
}

.dark-mode #rep_no_results {
    background-color: rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.3);
}

/* Styles spécifiques pour iOS */
@supports (-webkit-touch-callout: none) {
    #rep_photo_preview {
        transform: translateZ(0);
        -webkit-transform: translateZ(0);
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        width: auto !important;
        height: auto !important;
        max-width: 100% !important;
        max-height: 100% !important;
        object-fit: contain !important;
        background-color: #f8f9fa;
    }
    
    .photo-preview-container {
        transform: translateZ(0);
        -webkit-transform: translateZ(0);
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        perspective: 1000;
        -webkit-perspective: 1000;
    }
}

/* Styles pour le mode nuit */
@media (prefers-color-scheme: dark) {
    /* Styles du mode nuit existants */
}

/* Classe pour afficher uniquement sur les PC */
.desktop-only {
    display: none;
}

@media (min-width: 992px) and (hover: hover) {
    .desktop-only {
        display: block;
    }
}

/* Styles pour la signature */
.signature-container {
    position: relative;
    margin-bottom: 15px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    background-color: #fff;
    overflow: hidden;
}

.signature-pad {
    width: 100%;
    height: 200px;
    background-color: #fff;
    border-radius: 6px;
    touch-action: none;
    cursor: crosshair;
}

.signature-controls {
    position: absolute;
    bottom: 10px;
    right: 10px;
}

.dark-mode .signature-container {
    border-color: #374151;
    background-color: #1f2937;
}

.dark-mode .signature-pad {
    background-color: #1f2937;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des étapes
    let etapeCourante = 1;
    const totalEtapes = 4;
    
    // Fonction pour adapter l'affichage selon la taille de l'écran
    function adjustDisplay() {
        const isMobile = window.innerWidth <= 428;
    }
    
    // Appeler la fonction au chargement
    adjustDisplay();
    
    // Écouter les événements de redimensionnement
    window.addEventListener('resize', adjustDisplay, { passive: true });
    
    // Mettre à jour la barre de progression
    function updateProgressBar() {
        const pourcentage = (etapeCourante / totalEtapes) * 100;
        document.querySelector('.progress-bar').style.width = pourcentage + '%';
        document.querySelector('.progress-bar').textContent = 'Étape ' + etapeCourante + '/' + totalEtapes;
    }
    
    // Navigation entre les étapes avec effet de transition
    document.querySelectorAll('.next-step').forEach(function(button) {
        button.addEventListener('click', function() {
            const currentStep = document.getElementById('rep_etape' + etapeCourante);
            currentStep.style.opacity = 0;
            
            setTimeout(function() {
                currentStep.classList.add('d-none');
                etapeCourante++;
                const nextStep = document.getElementById('rep_etape' + etapeCourante);
                nextStep.classList.remove('d-none');
                
                setTimeout(function() {
                    nextStep.style.opacity = 1;
                }, 50);
                
                updateProgressBar();
                
                // Scroll en haut du formulaire pour les appareils mobiles
                window.scrollTo({top: 0, behavior: 'smooth'});
            }, 300);
        });
    });
    
    document.querySelectorAll('.prev-step').forEach(function(button) {
        button.addEventListener('click', function() {
            const currentStep = document.getElementById('rep_etape' + etapeCourante);
            currentStep.style.opacity = 0;
            
            setTimeout(function() {
                currentStep.classList.add('d-none');
                etapeCourante--;
                const prevStep = document.getElementById('rep_etape' + etapeCourante);
                prevStep.classList.remove('d-none');
                
                setTimeout(function() {
                    prevStep.style.opacity = 1;
                }, 50);
                
                updateProgressBar();
                
                // Scroll en haut du formulaire pour les appareils mobiles
                window.scrollTo({top: 0, behavior: 'smooth'});
            }, 300);
        });
    });
    
    // Améliorer le feedback tactile pour les cartes sélectionnables
    function addTouchFeedback(elements) {
        elements.forEach(function(element) {
            element.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
                this.style.backgroundColor = '#f0f8ff';
            }, { passive: true });
            
            element.addEventListener('touchend', function() {
                this.style.transform = '';
                setTimeout(() => {
                    if (!this.classList.contains('selected')) {
                        this.style.backgroundColor = '';
                    }
                }, 300);
            }, { passive: true });
        });
    }
    
    // Appliquer le feedback tactile
    addTouchFeedback(document.querySelectorAll('.type-appareil-card'));
    addTouchFeedback(document.querySelectorAll('.mot-de-passe-card'));
    addTouchFeedback(document.querySelectorAll('.note-interne-card'));
    
    // Initialiser l'opacité des étapes
    document.getElementById('rep_etape1').style.opacity = 1;
    document.querySelectorAll('.form-step:not(#rep_etape1)').forEach(function(step) {
        step.style.opacity = 0;
    });
    
    // Étape 1: Sélection du type d'appareil
    document.querySelectorAll('.type-appareil-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('.type-appareil-card').forEach(function(c) {
                c.classList.remove('selected');
            });
            this.classList.add('selected');
            document.getElementById('rep_type_appareil').value = this.getAttribute('data-type');
            this.closest('.form-step').querySelector('.next-step').disabled = false;
            
            // Mémoriser le type d'appareil sélectionné pour les étapes suivantes
            window.typeAppareilSelectionne = this.getAttribute('data-type');
        }, { passive: true });
    });
    
    // Gestion des boutons de raccourci pour la description du problème
    document.querySelectorAll('.btn-problem-shortcut').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const problemType = this.getAttribute('data-problem-type');
            const descriptionField = document.getElementById('rep_description_probleme');
            
            // Définir le texte approprié selon le type de problème
            switch(problemType) {
                case 'alimentation':
                    descriptionField.value = 'Alimentation : DECRIVEZ_LE_PROBLEME_DE_FACON_CLAIRE';
                    break;
                case 'ecran':
                    descriptionField.value = 'ECRAN : REMPLACEMENT_DE_LA_VITRE OU REMPLACEMENT_ECRAN_COMPLET';
                    break;
                case 'autre-info':
                    descriptionField.value = 'AUTRE : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL';
                    break;
                case 'alimentation-trot':
                    descriptionField.value = 'Alimentation : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL';
                    break;
                case 'cycle':
                    descriptionField.value = 'Cycle : PRECISEZ_AVEC_OU_SANS_CHAMBRE_ET_PRECISEZ_LE_TYPE_ET_LA_TAILLE_DU_PNEU';
                    break;
                case 'electronique':
                    descriptionField.value = 'Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL';
                    break;
                case 'autre-trot':
                    descriptionField.value = 'Autre : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL';
                    break;
            }
            
            // Mettre le focus sur le champ de description pour permettre à l'utilisateur de modifier le texte
            descriptionField.focus();
        });
    });
    
    // Afficher les boutons de raccourci appropriés selon le type d'appareil sélectionné
    document.querySelectorAll('.next-step').forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Si on passe à l'étape 3 et qu'un type d'appareil est sélectionné
            if (etapeCourante === 2 && window.typeAppareilSelectionne) {
                setTimeout(function() {
                    if (window.typeAppareilSelectionne === 'Informatique') {
                        document.getElementById('informatique_buttons').style.display = 'block';
                        document.getElementById('trottinette_buttons').style.display = 'none';
                    } else if (window.typeAppareilSelectionne === 'Trottinette') {
                        document.getElementById('informatique_buttons').style.display = 'none';
                        document.getElementById('trottinette_buttons').style.display = 'block';
                    }
                }, 400); // Délai pour laisser le temps à l'animation de transition de se terminer
            }
        });
    });
    
    // Étape 2: Recherche de client
    let timeoutId;
    document.getElementById('rep_recherche_client_reparation').addEventListener('input', function() {
        const terme = this.value;
        
        // Effacer le timeout précédent
        clearTimeout(timeoutId);
        
        // Si moins de 2 caractères, cacher les résultats
        if (terme.length < 2) {
            document.getElementById('rep_resultats_clients').classList.add('d-none');
            document.getElementById('rep_no_results').classList.add('d-none');
            return;
        }
        
        // Mettre en place un nouveau timeout pour éviter trop de requêtes
        timeoutId = setTimeout(() => {
            // Afficher un loader ou indicateur de chargement
            const listeClients = document.getElementById('rep_liste_clients_mobile');
            listeClients.innerHTML = '<div class="loading-indicator"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
            document.getElementById('rep_resultats_clients').classList.remove('d-none');
            
            // Construire l'URL complète avec le chemin absolu
            const baseUrl = window.location.protocol + '//' + window.location.host;
            const url = baseUrl + '/ajax/recherche_clients.php';
            
            console.log('Envoi requête à:', url);
            
            // Recherche AJAX avec attribut credentials pour envoyer les cookies
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Cache-Control': 'no-cache, no-store, must-revalidate'
                },
                body: 'terme=' + encodeURIComponent(terme),
                credentials: 'same-origin'
            })
            .then(response => {
                // Vérifier si la réponse est OK avant de parser le JSON
                if (!response.ok) {
                    throw new Error('Erreur réseau: ' + response.status);
                }
                return response.text().then(text => {
                    // Debugger la réponse brute en cas d'erreur
                    try {
                        if (!text || text.trim() === '') {
                            throw new Error('Réponse vide du serveur');
                        }
                        console.log('Réponse reçue:', text);
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Erreur de parsing JSON:', text);
                        throw new Error('Réponse invalide du serveur: ' + e.message);
                    }
                });
            })
            .then(data => {
                const listeClients = document.getElementById('rep_liste_clients_mobile');
                listeClients.innerHTML = '';
                
                if (data.success && data.clients && data.clients.length > 0) {
                    // Créer des cartes clients pour le mobile
                    data.clients.forEach(function(client) {
                        const clientCard = document.createElement('div');
                        clientCard.className = 'client-card';
                        
                        clientCard.innerHTML = `
                            <div class="client-card-header">
                                <h6>${client.nom} ${client.prenom}</h6>
                            </div>
                            <div class="client-card-info">
                                <p><i class="fas fa-phone-alt text-muted me-2"></i>${client.telephone || 'Non renseigné'}</p>
                            </div>
                            <div class="client-card-actions">
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger supprimer-client" 
                                    data-id="${client.id}">
                                    <i class="fas fa-trash me-1"></i>Supprimer
                                </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-primary selectionner-client" 
                                    data-id="${client.id}" 
                                    data-nom="${client.nom}" 
                                    data-prenom="${client.prenom}">
                                    <i class="fas fa-check me-1"></i>Sélectionner
                                </button>
                            </div>
                        `;
                        
                        listeClients.appendChild(clientCard);
                    });
                    
                    document.getElementById('rep_resultats_clients').classList.remove('d-none');
                    document.getElementById('rep_no_results').classList.add('d-none');
                } else {
                    document.getElementById('rep_resultats_clients').classList.add('d-none');
                    document.getElementById('rep_no_results').classList.remove('d-none');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('rep_resultats_clients').classList.add('d-none');
                document.getElementById('rep_no_results').classList.remove('d-none');
            });
        }, 300); // Délai de 300ms avant de lancer la recherche
    }, { passive: true });
    
    // Sélection d'un client
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('selectionner-client')) {
            const id = e.target.getAttribute('data-id');
            const nom = e.target.getAttribute('data-nom');
            const prenom = e.target.getAttribute('data-prenom');
            
            document.getElementById('rep_client_id').value = id;
            document.getElementById('rep_nom_client_selectionne').textContent = nom + ' ' + prenom;
            document.getElementById('rep_client_selectionne').classList.remove('d-none');
            document.getElementById('rep_resultats_clients').classList.add('d-none');
            document.getElementById('rep_btn_etape2_suivant').disabled = false;
        }
        
        if (e.target && e.target.classList.contains('supprimer-client')) {
            const id = e.target.getAttribute('data-id');
            if (confirm('Êtes-vous sûr de vouloir supprimer ce client ?')) {
                const baseUrl = window.location.protocol + '//' + window.location.host;
                const url = baseUrl + '/ajax/supprimer_client.php';
                
                console.log('Envoi requête à:', url);
                
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Cache-Control': 'no-cache, no-store, must-revalidate'
                    },
                    body: 'id=' + encodeURIComponent(id),
                    credentials: 'same-origin'
                })
                .then(response => {
                    // Vérifier si la réponse est OK avant de parser le JSON
                    if (!response.ok) {
                        throw new Error('Erreur réseau: ' + response.status);
                    }
                    return response.text().then(text => {
                        // Debugger la réponse brute en cas d'erreur
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Erreur de parsing JSON:', text);
                            throw new Error('Réponse invalide du serveur');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Recharger la liste des clients
                        document.getElementById('rep_recherche_client_reparation').dispatchEvent(new Event('input'));
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erreur lors de la suppression du client');
                    console.error('Erreur:', error);
                });
            }
        }
    });
    
    // Réinitialiser la sélection du client
    document.getElementById('rep_reset_client').addEventListener('click', function() {
        document.getElementById('rep_client_id').value = '';
        document.getElementById('rep_client_selectionne').classList.add('d-none');
        document.getElementById('rep_btn_etape2_suivant').disabled = true;
    });
    
    // Ouvrir le modal d'ajout de client
    document.getElementById('rep_btn_nouveau_client').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('nouveauClientModal_reparation'));
        modal.show();
    });
    
    // Sauvegarder un nouveau client
    document.getElementById('btn_sauvegarder_client_reparation').addEventListener('click', function() {
        const nom = document.getElementById('nouveau_nom_reparation').value.trim();
        const prenom = document.getElementById('nouveau_prenom_reparation').value.trim();
        const telephone = document.getElementById('nouveau_telephone_reparation').value.trim();
        
        // Validation des champs
        if (!nom || !prenom || !telephone) {
            alert('Veuillez remplir tous les champs obligatoires');
            return;
        }
        
        // Désactiver le bouton pendant l'envoi pour éviter les soumissions multiples
        const btnSave = this;
        btnSave.disabled = true;
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement...';
        
        // Afficher un indicateur de chargement global
        const savingIndicator = document.createElement('div');
        savingIndicator.className = 'position-fixed top-0 start-0 w-100 bg-primary text-white p-2 text-center';
        savingIndicator.id = 'savingIndicator';
        savingIndicator.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enregistrement en cours...';
        savingIndicator.style.zIndex = '9999';
        document.body.appendChild(savingIndicator);
        
        // Construire les données du formulaire
        const formData = new FormData();
        formData.append('nom', nom);
        formData.append('prenom', prenom);
        formData.append('telephone', telephone);
        
        // Récupérer le shop_id depuis PHP pour l'envoyer explicitement
        <?php if (isset($_SESSION['shop_id'])): ?>
        formData.append('shop_id', '<?php echo $_SESSION['shop_id']; ?>');
        <?php else: ?>
        console.error("ERREUR: Aucun shop_id défini en session!");
        <?php endif; ?>
        
        // Ajouter un timestamp pour éviter les problèmes de cache
        formData.append('_timestamp', Date.now());
        
        // Enregistrement AJAX avec le nouveau script qui effectue une connexion directe
        fetch('/ajax/direct_add_client.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => {
            console.log('Statut de la réponse:', response.status, response.statusText);
            return response.text().then(text => {
                console.log('Réponse brute:', text);
                
                if (!response.ok) {
                    throw new Error('Erreur réseau: ' + response.status + ' - ' + text);
                }
                
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Réponse invalide du serveur: ' + e.message);
                }
            });
        })
        .then(data => {
            if (data.success) {
                console.log('Client ajouté avec succès, ID:', data.client_id);
                
                document.getElementById('rep_client_id').value = data.client_id;
                document.getElementById('rep_nom_client_selectionne').textContent = nom + ' ' + prenom;
                document.getElementById('rep_client_selectionne').classList.remove('d-none');
                
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('nouveauClientModal_reparation'));
                modal.hide();
                
                // Activer le bouton suivant
                document.getElementById('rep_btn_etape2_suivant').disabled = false;
                
                // Réinitialiser le formulaire
                document.getElementById('formNouveauClient_reparation').reset();
                
                // Afficher une notification de succès
                const successNotif = document.createElement('div');
                successNotif.className = 'position-fixed top-0 end-0 p-3';
                successNotif.style.zIndex = '1050';
                successNotif.innerHTML = `
                    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-check-circle me-2"></i>
                                Client ajouté avec succès
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                `;
                document.body.appendChild(successNotif);
                const toast = new bootstrap.Toast(successNotif.querySelector('.toast'));
                toast.show();
                
                // Supprimer la notification après l'animation
                setTimeout(() => {
                    successNotif.remove();
                }, 5000);
            } else {
                throw new Error(data.message || 'Erreur lors de l\'ajout du client');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'ajout du client: ' + error.message);
        })
        .finally(() => {
            // Réactiver le bouton
            btnSave.disabled = false;
            btnSave.innerHTML = 'Sauvegarder';
            
            // Supprimer l'indicateur de chargement
            const indicator = document.getElementById('savingIndicator');
            if (indicator) {
                indicator.remove();
            }
        });
    });
    
    // Vérification des champs de l'étape 3
    function checkEtape3Fields() {
        const modele = document.getElementById('rep_modele').value.trim();
        const aMotDePasse = document.getElementById('rep_a_mot_de_passe').value;
        const motDePasse = document.getElementById('rep_mot_de_passe').value.trim();
        const descriptionProbleme = document.getElementById('rep_description_probleme').value.trim();
        const photoAppareil = document.getElementById('rep_photo_appareil').value;
        const photoFile = document.getElementById('rep_photo_file').value;
        const aNoteInterne = document.getElementById('rep_a_note_interne').value;
        const noteInterne = document.getElementById('rep_notes_techniques').value.trim();
        
        const btnEtape3Suivant = document.getElementById('rep_btn_etape3_suivant');
        const photoRequired = document.getElementById('photo_required');
        
        // Considérer que la photo est requise si aucune image n'est sélectionnée et aucune image n'est stockée
        const isPhotoRequired = !photoAppareil && !photoFile;
        
        // Afficher/masquer le message d'erreur pour la photo
        if (photoRequired) {
            photoRequired.classList.toggle('d-none', !isPhotoRequired);
        }
        
        btnEtape3Suivant.disabled = 
            modele === '' || 
            descriptionProbleme === '' || 
            !aMotDePasse ||
            (aMotDePasse === 'oui' && motDePasse === '') ||
            (aNoteInterne === 'oui' && noteInterne === '') ||
            isPhotoRequired;
    }
    
    // Ajouter les écouteurs d'événements pour les champs de l'étape 3
    document.getElementById('rep_modele').addEventListener('input', checkEtape3Fields);
    document.getElementById('rep_description_probleme').addEventListener('input', checkEtape3Fields);
    document.getElementById('rep_mot_de_passe').addEventListener('input', checkEtape3Fields);
    document.getElementById('rep_notes_techniques').addEventListener('input', checkEtape3Fields);
    
    // Mise à jour de la gestion des boutons de mot de passe
    document.querySelectorAll('.mot-de-passe-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('.mot-de-passe-card').forEach(function(c) {
                c.classList.remove('selected');
            });
            this.classList.add('selected');
            
            const value = this.getAttribute('data-value');
            document.getElementById('rep_a_mot_de_passe').value = value;
            
            const champMotDePasse = document.getElementById('rep_champ_mot_de_passe');
            const confirmationSansMdp = document.getElementById('rep_confirmation_sans_mdp');
            
            if (value === 'oui') {
                champMotDePasse.classList.remove('d-none');
                confirmationSansMdp.classList.add('d-none');
                document.getElementById('rep_mot_de_passe').setAttribute('required', 'required');
            } else {
                champMotDePasse.classList.add('d-none');
                confirmationSansMdp.classList.remove('d-none');
                document.getElementById('rep_mot_de_passe').removeAttribute('required');
            }
            
            checkEtape3Fields();
        }, { passive: true });
    });
    
    // Gestion des boutons de note interne
    document.querySelectorAll('.note-interne-card').forEach(function(card) {
        card.addEventListener('click', function() {
            document.querySelectorAll('.note-interne-card').forEach(function(c) {
                c.classList.remove('selected');
            });
            this.classList.add('selected');
            
            const value = this.getAttribute('data-value');
            document.getElementById('rep_a_note_interne').value = value;
            
            const champNoteInterne = document.getElementById('rep_champ_note_interne');
            
            if (value === 'oui') {
                champNoteInterne.classList.remove('d-none');
                document.getElementById('rep_notes_techniques').setAttribute('required', 'required');
            } else {
                champNoteInterne.classList.add('d-none');
                document.getElementById('rep_notes_techniques').removeAttribute('required');
            }
            
            checkEtape3Fields();
        }, { passive: true });
    });
    
    // Mise à jour de la confirmation sans mot de passe
    document.getElementById('rep_btn_confirmer_sans_mdp').addEventListener('click', function() {
        // Mettre à jour le message de confirmation
        document.getElementById('rep_confirmation_sans_mdp').innerHTML = `
            <i class="fas fa-check me-2"></i>
            <strong>Confirmation enregistrée</strong>
        `;
        document.getElementById('rep_confirmation_sans_mdp').classList.remove('alert-warning');
        document.getElementById('rep_confirmation_sans_mdp').classList.add('alert-success');
        
        // Mettre à jour la validation des champs
        checkEtape3Fields();
    }, { passive: true });
    
    // Configuration de la gestion des photos par sélection de fichier
    const photoFileInput = document.getElementById('rep_photo_file');
    const photoAppearField = document.getElementById('rep_photo_appareil');
    
    // Gestion de la sélection de fichier
    photoFileInput.addEventListener('change', function(e) {
        const file = this.files[0];
        if (!file) return;
        
        // Détection de l'environnement iOS et PWA
        const isPWA = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            
        console.log("Sélection de fichier:", {
            fileName: file.name,
            fileType: file.type,
            fileSize: file.size,
            isPWA: isPWA,
            isIOS: isIOS
        });
        
        // Vérifier si c'est une image
        if (!file.type.startsWith('image/')) {
            alert('Veuillez sélectionner une image.');
            this.value = ''; // Réinitialiser l'input
            return;
        }
        
        // Vérifier la taille du fichier (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('La taille de l\'image ne doit pas dépasser 5 MB.');
            this.value = ''; // Réinitialiser l'input
            return;
        }
        
        // Si iOS en mode PWA, utiliser FileReader directement
        if (isPWA && isIOS) {
            console.log("Méthode FileReader directe pour iOS PWA");
            const reader = new FileReader();
            reader.onload = function(event) {
                photoAppearField.value = event.target.result;
                
                // Mettre à jour la validation
                if (typeof checkEtape3Fields === 'function') {
                    checkEtape3Fields();
                }
            };
            reader.readAsDataURL(file);
        } else {
            // Pour les autres navigateurs, utiliser optimizeImage
            processWithFileReader(file);
        }
    }, { passive: true });
    
    // Fonction pour traiter l'image avec FileReader (méthode originale)
    function processWithFileReader(file) {
        const reader = new FileReader();
        
        reader.onload = function(event) {
            // Récupérer les données de l'image
            const imageDataUrl = event.target.result;
            
            // Optimiser l'image avant de la stocker
            optimizeImage(imageDataUrl, function(optimizedImageData) {
                // Stocker l'image optimisée dans le champ caché
                photoAppearField.value = optimizedImageData;
                
                // Mettre à jour la validation des champs
                if (typeof checkEtape3Fields === 'function') {
                    checkEtape3Fields();
                }
            });
        };
        
        reader.onerror = function(event) {
            console.error("Erreur lors de la lecture du fichier:", event);
            alert("Erreur lors du chargement de l'image. Veuillez réessayer.");
        };
        
        // Lire le fichier comme une URL de données
        reader.readAsDataURL(file);
    }
    
    // Réinitialiser le champ photo si l'utilisateur clique sur "Annuler" dans la sélection de fichier
    photoFileInput.addEventListener('click', function() {
        // Ajouter un gestionnaire pour détecter si le dialogue de fichier a été annulé
        const checkForCancellation = setInterval(() => {
            if (document.activeElement !== photoFileInput) {
                clearInterval(checkForCancellation);
                setTimeout(() => {
                    if (!this.value && photoAppearField.value) {
                        // L'utilisateur a annulé, mais il y avait déjà une image
                        // Ne rien faire, garder l'image existante
                    }
                }, 1000);
            }
        }, 500);
    }, { passive: true });
    
    // Fonction pour optimiser l'image (réduire la résolution et la compression)
    function optimizeImage(imageDataUrl, callback) {
        console.log("Optimisation de l'image, format d'entrée:", 
            imageDataUrl.substring(0, 30) + "..." + imageDataUrl.substring(imageDataUrl.length - 10));

        // Vérifier le format de l'image
        if (!imageDataUrl.startsWith('data:image/')) {
            console.error("Format d'image invalide:", imageDataUrl.substring(0, 30));
            // Essayer de corriger le format s'il manque le préfixe
            if (imageDataUrl.includes(',')) {
                imageDataUrl = 'data:image/jpeg;base64,' + imageDataUrl.split(',')[1];
                console.log("Format corrigé:", imageDataUrl.substring(0, 30) + "...");
            } else {
                console.error("Impossible de corriger le format d'image");
                imageDataUrl = 'data:image/jpeg;base64,' + imageDataUrl;
            }
        }

        const img = new Image();
        img.onload = function() {
            // Définir des dimensions maximales
            const maxWidth = 1024;
            const maxHeight = 1024;
            
            // Déterminer les dimensions de sortie
            let width = img.width;
            let height = img.height;
            
            // Redimensionner si nécessaire
            if (width > maxWidth) {
                height = Math.round(height * (maxWidth / width));
                width = maxWidth;
            }
            if (height > maxHeight) {
                width = Math.round(width * (maxHeight / height));
                height = maxHeight;
            }
            
            console.log("Dimensions originales:", img.width, "x", img.height);
            console.log("Dimensions optimisées:", width, "x", height);
            
            // Détection spéciale pour iOS en mode PWA
            const isPWA = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            
            // Si on est sur iOS en mode PWA, utiliser un traitement spécial pour éviter l'écran blanc
            if (isPWA && isIOS) {
                console.log("Mode iOS PWA détecté: utilisation du traitement spécial d'image");
                // Pour iOS en PWA, on évite le redimensionnement qui peut causer l'écran blanc
                // On retourne l'image originale avec une légère compression
                const optimizedDataUrl = imageDataUrl;
                callback(optimizedDataUrl);
                return;
            }
            
            // Créer un canvas pour le redimensionnement (pour les autres plateformes)
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            
            // Dessiner l'image redimensionnée
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);
            
            // Comprimer en fonction du type d'appareil
            const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            
            // Qualité de compression (0-1)
            let quality = 0.85;
            if (isMobile) {
                // Compression plus agressive sur mobile
                quality = 0.75;
            }
            
            // Convertir en URL de données
            try {
                const optimizedDataUrl = canvas.toDataURL('image/jpeg', quality);
                console.log("Image optimisée avec succès:", 
                    optimizedDataUrl.substring(0, 30) + "..." + optimizedDataUrl.substring(optimizedDataUrl.length - 10));
                
                // Vérifier que le format est correct
                if (!optimizedDataUrl.startsWith('data:image/')) {
                    console.error("Format de sortie incorrect, application d'une correction");
                    const correctedUrl = 'data:image/jpeg;base64,' + optimizedDataUrl.split(',')[1];
                    callback(correctedUrl);
                } else {
                    // Appeler le callback avec l'image optimisée
                    callback(optimizedDataUrl);
                }
            } catch (e) {
                console.error("Erreur lors de l'optimisation de l'image:", e);
                // En cas d'erreur, utiliser l'image d'origine
                callback(imageDataUrl);
            }
        };
        
        img.onerror = function(e) {
            console.error("Erreur lors du chargement de l'image pour optimisation:", e);
            callback(imageDataUrl); // Utiliser l'image d'origine en cas d'erreur
        };
        
        img.src = imageDataUrl;
    }

    // Gestion de l'affichage des champs de commande
    const commandeRequise = document.getElementById('rep_commande_requise');
    const commandeFields = document.getElementById('rep_commande_fields');
    const reparationForm = document.getElementById('rep_reparationForm');

    commandeRequise.addEventListener('change', function() {
        commandeFields.classList.toggle('d-none', !this.checked);
        
        // Rendre les champs obligatoires si la commande est requise
        const requiredFields = commandeFields.querySelectorAll('[name="rep_fournisseur"], [name="rep_nom_piece"], [name="rep_quantite"], [name="rep_prix_piece"]');
        requiredFields.forEach(field => {
            field.required = this.checked;
        });
    }, { passive: true });

    // Gestion de la soumission du formulaire
    reparationForm.addEventListener('submit', function(e) {
        // Empêcher la soumission par défaut
        e.preventDefault();
        
        // Si une commande est requise, vérifier que tous les champs obligatoires sont remplis
        const commandeRequise = document.getElementById('rep_commande_requise');
        if (commandeRequise.checked) {
            const fournisseur = document.getElementById('rep_fournisseur').value;
            const nomPiece = document.getElementById('rep_nom_piece').value;
            const quantite = document.getElementById('rep_quantite').value;
            const prixPiece = document.getElementById('rep_prix_piece').value;

            if (!fournisseur || !nomPiece || !quantite || !prixPiece) {
                alert('Veuillez remplir tous les champs obligatoires de la commande de pièces.');
                return;
            }
        }
        
        // Afficher un message pendant le traitement
        document.getElementById('submitting_message').classList.remove('d-none');
        document.getElementById('btn_soumettre_reparation').disabled = true;
        document.getElementById('btn_soumettre_reparation').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Traitement...';
        
        // Collecter les données du formulaire
        const formData = new FormData(this);
        
        // Ajouter un timestamp pour éviter les problèmes de cache
        formData.append('submission_time', Date.now());
        
        // Log pour débogage des photos
        console.log('Soumission du formulaire avec photo:', !!document.getElementById('rep_photo_appareil').value);
        if (document.getElementById('rep_photo_appareil').value) {
            console.log('Longueur des données photo:', document.getElementById('rep_photo_appareil').value.length);
            console.log('Début des données photo:', document.getElementById('rep_photo_appareil').value.substring(0, 50) + '...');
        }
        
        // Effectuer une requête AJAX pour soumettre le formulaire
        fetch('/index.php?page=ajouter_reparation', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (response.ok) {
                // Redirection réussie (peut contenir une URL de redirection)
                return response.text();
            }
            throw new Error('Erreur lors de la soumission');
        })
        .then(html => {
            // Vérifier si l'HTML contient une URL de redirection
            const redirectMatch = html.match(/<meta\s+http-equiv="refresh"\s+content="0;\s*url=([^"]+)"/i);
            if (redirectMatch && redirectMatch[1]) {
                // Rediriger vers l'URL spécifiée
                window.location.href = redirectMatch[1];
            } else if (html.includes('imprimer_etiquette')) {
                // Si nous pouvons identifier que le HTML contient une référence à imprimer_etiquette
                const repairId = html.match(/id=(\d+)/i) ? html.match(/id=(\d+)/i)[1] : '';
                window.location.href = 'https://mdgeek.top/index.php?page=imprimer_etiquette&id=' + repairId;
            } else {
                // Soumettre directement le formulaire de manière traditionnelle
                document.getElementById('rep_reparationForm').removeEventListener('submit', this);
                document.getElementById('rep_reparationForm').submit();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            // En cas d'erreur, soumettre le formulaire de manière traditionnelle
            document.getElementById('rep_reparationForm').removeEventListener('submit', this);
            document.getElementById('rep_reparationForm').submit();
        });
    });

    // Validation des champs requis pour la confirmation sans mot de passe
    function validateNoPasswordConfirmation() {
        // Le bouton de confirmation est toujours actif
        document.getElementById('rep_btn_confirmer_sans_mdp').disabled = false;
    }

    // Ajouter un écouteur d'événement pour la case à cocher
    const checkResponsabilite = document.getElementById('rep_check_responsabilite');
    if (checkResponsabilite) {
        checkResponsabilite.addEventListener('change', validateNoPasswordConfirmation, { passive: true });
    }

    // Fonction pour diagnostiquer l'état de la caméra
    function diagnostiquerCamera() {
        const isPWA = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        
        console.log("[PWA-DEBUG] 🔍 DIAGNOSTIC CAMÉRA - Mode PWA:", isPWA);
        console.log("[PWA-DEBUG] 📐 Résolution vidéo:", camera.videoWidth, "x", camera.videoHeight);
        
        try {
            // Vérifier les capacités de l'appareil
            const capabilities = stream && stream.getVideoTracks().length > 0 ? 
                stream.getVideoTracks()[0].getCapabilities() : null;
            
            if (capabilities) {
                console.log("[PWA-DEBUG] 📊 Capacités de la caméra:", {
                    widthRange: capabilities.width ? [capabilities.width.min, capabilities.width.max] : "Non disponible",
                    heightRange: capabilities.height ? [capabilities.height.min, capabilities.height.max] : "Non disponible",
                    aspectRatioRange: capabilities.aspectRatio ? [capabilities.aspectRatio.min, capabilities.aspectRatio.max] : "Non disponible",
                    frameRateRange: capabilities.frameRate ? [capabilities.frameRate.min, capabilities.frameRate.max] : "Non disponible",
                    facingMode: capabilities.facingMode || "Non disponible"
                });
            } else {
                console.log("[PWA-DEBUG] ⚠️ Capacités de la caméra non disponibles");
            }
            
            // Vérifier les paramètres actuels
            const settings = stream && stream.getVideoTracks().length > 0 ? 
                stream.getVideoTracks()[0].getSettings() : null;
            
            if (settings) {
                console.log("[PWA-DEBUG] ⚙️ Paramètres actuels de la caméra:", {
                    width: settings.width,
                    height: settings.height,
                    aspectRatio: settings.aspectRatio,
                    frameRate: settings.frameRate,
                    facingMode: settings.facingMode
                });
            } else {
                console.log("[PWA-DEBUG] ⚠️ Paramètres de la caméra non disponibles");
            }
        } catch (e) {
            console.error("[PWA-DEBUG] ❌ Erreur lors de la récupération des capacités de la caméra:", e);
        }
        
        console.log("[PWA-DEBUG] 📺 Propriétés de l'élément vidéo:", {
            videoWidth: camera.videoWidth,
            videoHeight: camera.videoHeight,
            clientWidth: camera.clientWidth,
            clientHeight: camera.clientHeight,
            offsetWidth: camera.offsetWidth,
            offsetHeight: camera.offsetHeight,
            readyState: camera.readyState,
            currentTime: camera.currentTime,
            paused: camera.paused,
            ended: camera.ended,
            muted: camera.muted,
            autoplay: camera.autoplay,
            playsinline: camera.playsinline,
            hasAttribute_playsinline: camera.hasAttribute('playsinline'),
            srcObject: !!camera.srcObject
        });
        
        console.log("[PWA-DEBUG] 🌐 Informations navigateur:", {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            vendor: navigator.vendor,
            maxTouchPoints: navigator.maxTouchPoints,
            hardwareConcurrency: navigator.hardwareConcurrency,
            deviceMemory: navigator.deviceMemory || "Non disponible",
            connection: navigator.connection ? {
                type: navigator.connection.type,
                effectiveType: navigator.connection.effectiveType,
                downlink: navigator.connection.downlink,
                rtt: navigator.connection.rtt
            } : "Non disponible"
        });
    }

    // Gestion de la capture de photo via webcam (uniquement sur PC)
    const capturePhotoBtn = document.getElementById('rep_capture_photo');
    const cameraContainer = document.getElementById('camera_container');
    const cameraFeed = document.getElementById('camera_feed');
    const cameraCanvas = document.getElementById('camera_canvas');
    const takePhotoBtn = document.getElementById('take_photo');
    const cancelPhotoBtn = document.getElementById('cancel_photo');
    
    let stream = null;
    
    // Vérifier si on est sur mobile/tablette
    const isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isPWA = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    
    // Masquer le bouton de capture sur mobile/tablette et en mode PWA
    if (isMobileDevice || isPWA) {
        document.getElementById('capture_container').style.display = 'none';
    }
    
    // Démarrer la caméra quand on clique sur le bouton Capturer
    capturePhotoBtn.addEventListener('click', function() {
        // Afficher la zone de caméra
        cameraContainer.classList.remove('d-none');
        
        // Afficher les boutons de contrôle
        takePhotoBtn.classList.remove('d-none');
        cancelPhotoBtn.classList.remove('d-none');
        
        // Masquer le bouton de capture pendant l'utilisation de la caméra
        this.classList.add('d-none');
        
        // Démarrer la caméra avec une courte pause pour éviter les problèmes d'affichage
        setTimeout(() => {
            navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'user',
                    frameRate: { ideal: 30 }
                }, 
                audio: false 
            })
            .then(function(videoStream) {
                stream = videoStream;
                cameraFeed.srcObject = stream;
                cameraFeed.setAttribute('autoplay', '');
                cameraFeed.setAttribute('playsinline', '');
                cameraFeed.setAttribute('muted', '');
                cameraFeed.play()
                .catch(e => console.error("Erreur lors du démarrage de la vidéo:", e));
            })
            .catch(function(error) {
                console.error("Erreur d'accès à la caméra:", error);
                alert("Impossible d'accéder à votre caméra. Veuillez vérifier les permissions.");
                resetCamera();
            });
        }, 200);
    });
    
    // Arrêter la caméra quand on annule
    cancelPhotoBtn.addEventListener('click', function() {
        resetCamera();
    });
    
    // Fonction pour réinitialiser l'interface de la caméra
    function resetCamera() {
        // Arrêter le flux vidéo
        if (stream) {
            stream.getTracks().forEach(track => {
                track.stop();
            });
            cameraFeed.srcObject = null;
            stream = null;
        }
        
        // Masquer la zone de caméra et les boutons
        cameraContainer.classList.add('d-none');
        takePhotoBtn.classList.add('d-none');
        cancelPhotoBtn.classList.add('d-none');
        
        // Réafficher le bouton principal
        capturePhotoBtn.classList.remove('d-none');
    }
    
    // Prendre une photo
    takePhotoBtn.addEventListener('click', function() {
        // Configurer le canvas pour capturer l'image
        cameraCanvas.width = cameraFeed.videoWidth;
        cameraCanvas.height = cameraFeed.videoHeight;
        
        // Dessiner l'image actuelle de la vidéo sur le canvas
        const context = cameraCanvas.getContext('2d');
        context.drawImage(cameraFeed, 0, 0, cameraCanvas.width, cameraCanvas.height);
        
        // Convertir en data URL
        const imageDataUrl = cameraCanvas.toDataURL('image/jpeg', 0.85);
        
        // Stocker l'image dans le champ caché
        document.getElementById('rep_photo_appareil').value = imageDataUrl;
        
        // Réinitialiser l'interface caméra
        resetCamera();
        
        // Mettre à jour la validation des champs
        if (typeof checkEtape3Fields === 'function') {
            checkEtape3Fields();
        }
        
        // Afficher un indicateur de succès
        const successMsg = document.createElement('div');
        successMsg.className = 'alert alert-success mt-2';
        successMsg.innerHTML = '<i class="fas fa-check-circle me-2"></i>Photo capturée avec succès';
        
        // Remplacer le message précédent s'il existe
        const oldMsg = document.querySelector('#capture_container + .alert');
        if (oldMsg) {
            oldMsg.remove();
        }
        
        // Ajouter le message après le conteneur de capture
        document.getElementById('capture_container').insertAdjacentElement('afterend', successMsg);
        
        // Supprimer le message après 3 secondes
        setTimeout(() => {
            successMsg.remove();
        }, 3000);
    });
    
    // Empêcher les retours à la ligne dans les champs de description et notes techniques
    document.getElementById('rep_description_probleme').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });
    
    document.getElementById('rep_notes_techniques').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });
    
    // Nettoyer les retours à la ligne lors de la saisie
    document.getElementById('rep_description_probleme').addEventListener('input', function(e) {
        this.value = this.value.replace(/[\r\n]+/g, ' ');
    });
    
    document.getElementById('rep_notes_techniques').addEventListener('input', function(e) {
        this.value = this.value.replace(/[\r\n]+/g, ' ');
    });

    function rechercheClient() {
        // Récupérer le terme de recherche
        const terme = document.getElementById('rep_recherche_client_reparation').value.trim();
        
        // Vérifier que le terme n'est pas vide
        if (terme.length < 2) {
            document.getElementById('rep_resultats_recherche_client').innerHTML = '<div class="alert alert-info">Entrez au moins 2 caractères</div>';
            return;
        }
        
        // Afficher un indicateur de chargement
        document.getElementById('rep_resultats_recherche_client').innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
        
        // Construire les données à envoyer au format FormData
        const formData = new FormData();
        formData.append('terme', terme);
        
        console.log('Recherche client avec le terme:', terme);
        
        // Construire l'URL complète avec le chemin absolu
        const baseUrl = window.location.protocol + '//' + window.location.host;
        const url = baseUrl + '/ajax/recherche_clients.php';
        
        console.log('Envoi requête à:', url);
        
        // Recherche AJAX
        fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau');
            }
            return response.json();
        })
        .then(data => {
            console.log('Réponse reçue:', data);
            
            // Si la recherche a réussi
            if (data.success) {
                // Log la base de données utilisée pour diagnostic
                console.log('Base de données utilisée:', data.database);
                console.log('Nombre de clients trouvés:', data.count);
                
                // Vérifier s'il y a des résultats
                if (data.clients && data.clients.length > 0) {
                    // Construire le tableau des résultats
                    let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Contact</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    // Ajouter chaque client trouvé
                    data.clients.forEach(client => {
                        html += `
                            <tr>
                                <td>${client.lastname || ''}</td>
                                <td>${client.firstname || ''}</td>
                                <td>${client.phone || ''}<br>${client.email || ''}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                        onclick="selectionnerClient(${client.id}, '${client.lastname || ''}', '${client.firstname || ''}')">
                                        Sélectionner
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    html += `
                            </tbody>
                        </table>
                    </div>
                    `;
                    
                    // Afficher les résultats
                    document.getElementById('rep_resultats_recherche_client').innerHTML = html;
                } else {
                    // Aucun résultat trouvé
                    document.getElementById('rep_resultats_recherche_client').innerHTML = 
                        '<div class="alert alert-warning">Aucun client trouvé. <button type="button" class="btn btn-link p-0" onclick="afficherFormulaireAjoutClient()">Ajouter un nouveau client</button></div>';
                }
            } else {
                // La recherche a échoué
                document.getElementById('rep_resultats_recherche_client').innerHTML = 
                    `<div class="alert alert-danger">Erreur: ${data.message || 'Une erreur est survenue'}</div>`;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            document.getElementById('rep_resultats_recherche_client').innerHTML = 
                `<div class="alert alert-danger">Erreur: ${error.message}</div>`;
        });
    }
});
</script>