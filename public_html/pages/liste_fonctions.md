# Liste Complète des Fonctions Disponibles

## Fonctions de Gestion des Clients

| Fonction | Description | Paramètres | Retour |
|----------|-------------|------------|--------|
| `get_total_clients()` | Récupère le nombre total de clients | Aucun | Nombre entier |
| `get_client_info($client_id)` | Récupère les informations d'un client | `$client_id` (int) | Tableau associatif |
| `search_clients($query)` | Recherche des clients par nom, prénom ou téléphone | `$query` (string) | Tableau de clients |
| `get_client_historique($client_id)` | Récupère l'historique des réparations d'un client | `$client_id` (int) | Tableau de réparations |
| `get_historique_reparations($client_id)` | Récupère l'historique des réparations d'un client | `$client_id` (int) | Tableau de réparations |
| `get_historique_commandes($client_id)` | Récupère l'historique des commandes d'un client | `$client_id` (int) | Tableau de commandes |

## Fonctions de Gestion des Réparations

| Fonction | Description | Paramètres | Retour |
|----------|-------------|------------|--------|
| `get_reparations_count_by_status()` | Compte les réparations par statut | Aucun | Tableau associatif |
| `get_recent_reparations($limit)` | Récupère les réparations récentes | `$limit` (int) | Tableau de réparations |
| `get_all_statuts()` | Récupère tous les statuts de réparation | Aucun | Tableau de statuts |
| `get_statut_by_code($code)` | Récupère un statut par son code | `$code` (string) | Tableau associatif |
| `get_status_badge($status_code, $reparation_id)` | Génère un badge HTML pour un statut | `$status_code` (string), `$reparation_id` (int) | Chaîne HTML |
| `get_enum_status_badge($statut, $reparation_id)` | Génère un badge HTML pour un statut enum | `$statut` (string), `$reparation_id` (int) | Chaîne HTML |
| `determine_color($status_code)` | Détermine la couleur associée à un statut | `$status_code` (string) | Chaîne de couleur |
| `determine_display_text($status_code)` | Détermine le texte d'affichage pour un statut | `$status_code` (string) | Chaîne de texte |
| `get_repair_details($repair_id)` | Récupère les détails d'une réparation | `$repair_id` (int) | Tableau associatif |
| `get_repair_count()` | Compte les réparations | Aucun | Nombre entier |
| `get_repair_count_by_status_categorie()` | Compte les réparations par catégorie de statut | Aucun | Tableau associatif |
| `get_reparations_by_status($status)` | Récupère les réparations par statut | `$status` (string) | Tableau de réparations |
| `get_reparation($repair_id)` | Récupère une réparation par ID | `$repair_id` (int) | Tableau associatif |
| `get_recent_repairs($limit)` | Récupère les réparations récentes | `$limit` (int) | Tableau de réparations |

## Fonctions de Gestion des Tâches

| Fonction | Description | Paramètres | Retour |
|----------|-------------|------------|--------|
| `get_taches_en_cours($limit)` | Récupère les tâches en cours | `$limit` (int) | Tableau de tâches |
| `get_taches_urgentes($limit)` | Récupère les tâches urgentes | `$limit` (int) | Tableau de tâches |
| `get_taches_recentes_count()` | Compte les tâches récentes | Aucun | Nombre entier |
| `get_recent_tasks($limit)` | Récupère les tâches récentes | `$limit` (int) | Tableau de tâches |

## Fonctions de Gestion des Commandes

| Fonction | Description | Paramètres | Retour |
|----------|-------------|------------|--------|
| `get_commandes_en_cours($limit)` | Récupère les commandes en cours | `$limit` (int) | Tableau de commandes |
| `get_commande($commande_id)` | Récupère une commande par ID | `$commande_id` (int) | Tableau associatif |
| `get_archived_commandes()` | Récupère les commandes archivées | Aucun | Tableau de commandes |
| `get_details_commande($commande_id)` | Récupère les détails d'une commande | `$commande_id` (int) | Tableau associatif |
| `get_fournisseurs()` | Récupère tous les fournisseurs | Aucun | Tableau de fournisseurs |
| `get_suppliers()` | Récupère tous les fournisseurs (alias) | Aucun | Tableau de fournisseurs |
| `get_stock_parts()` | Récupère les pièces en stock | Aucun | Tableau de pièces |
| `get_produit($produit_id)` | Récupère un produit par ID | `$produit_id` (int) | Tableau associatif |

## Fonctions de Messagerie et SMS

| Fonction | Description | Paramètres | Retour |
|----------|-------------|------------|--------|
| `send_sms($recipient, $message, $gateway_url)` | Envoie un SMS | `$recipient` (string), `$message` (string), `$gateway_url` (string) | Booléen |
| `count_unread_messages($user_id)` | Compte les messages non lus | `$user_id` (int) | Nombre entier |
| `check_sms_template($template_id)` | Vérifie un modèle de SMS | `$template_id` (int) | Tableau associatif |

## Fonctions de Gardiennage

| Fonction | Description | Paramètres | Retour |
|----------|-------------|------------|--------|
| `demarrer_gardiennage($reparation_id, $tarif_journalier)` | Démarre un gardiennage | `$reparation_id` (int), `$tarif_journalier` (float) | ID du gardiennage |
| `terminer_gardiennage($gardiennage_id, $notes)` | Termine un gardiennage | `$gardiennage_id` (int), `$notes` (string) | Booléen |
| `mettre_a_jour_facturation_gardiennage($gardiennage_id)` | Met à jour la facturation d'un gardiennage | `$gardiennage_id` (int) | Booléen |
| `envoyer_rappel_gardiennage($gardiennage_id)` | Envoie un rappel pour un gardiennage | `$gardiennage_id` (int) | Booléen |
| `mettre_a_jour_tous_gardiennages()` | Met à jour tous les gardiennages | Aucun | Booléen |
| `calculer_montant_gardiennage($jours_totaux, $parametres)` | Calcule le montant d'un gardiennage | `$jours_totaux` (int), `$parametres` (array) | Montant (float) |

## Fonctions d'Authentification et de Session

| Fonction | Description | Paramètres | Retour |
|----------|-------------|------------|--------|
| `check_remember_token()` | Vérifie le token de "se souvenir de moi" | Aucun | Booléen |
| `cleanup_sessions()` | Nettoie les sessions expirées | Aucun | Booléen |
| `logout_from_all_sessions($user_id)` | Déconnecte un utilisateur de toutes ses sessions | `$user_id` (int) | Booléen |
| `generateCSRFToken()` | Génère un token CSRF | Aucun | Chaîne de caractères |
| `verifyCSRFToken($token)` | Vérifie un token CSRF | `$token` (string) | Booléen |
| `is_pwa_mode_client()` | Vérifie si l'application est en mode PWA | Aucun | Booléen |

## Fonctions de Gestion des Partenaires

| Fonction | Description | Paramètres | Retour |
|----------|-------------|------------|--------|
| `get_partenaires()` | Récupère tous les partenaires | Aucun | Tableau de partenaires |
| `get_transactions_partenaire($partenaire_id)` | Récupère les transactions d'un partenaire | `$partenaire_id` (int) | Tableau de transactions |

## Fonctions Utilitaires

| Fonction | Description | Paramètres | Retour |
|----------|-------------|------------|--------|
| `cleanInput($data)` | Nettoie une entrée utilisateur | `$data` (string) | Chaîne nettoyée |
| `clean_input($data)` | Nettoie une entrée utilisateur (alias) | `$data` (string) | Chaîne nettoyée |
| `format_date($date)` | Formate une date | `$date` (string) | Chaîne formatée |
| `format_mois_annee($timestamp)` | Formate un mois et une année | `$timestamp` (int) | Chaîne formatée |
| `formatPrix($prix)` | Formate un prix | `$prix` (float) | Chaîne formatée |
| `get_device_icon($device_type)` | Récupère l'icône pour un type d'appareil | `$device_type` (string) | Classe d'icône |
| `set_message($message, $type)` | Définit un message à afficher | `$message` (string), `$type` (string) | Void |
| `display_message()` | Affiche les messages | Aucun | Chaîne HTML |
| `redirect($page, $params)` | Redirige vers une page | `$page` (string), `$params` (array) | Void |
| `get_base_url()` | Récupère l'URL de base du site | Aucun | URL (string) |
| `get_status_categories()` | Récupère les catégories de statut | Aucun | Tableau de catégories |
| `get_statuts_by_category($category_id)` | Récupère les statuts par catégorie | `$category_id` (int) | Tableau de statuts |
| `get_status_categories()` | Récupère les catégories de statut | Aucun | Tableau de catégories |
| `get_all_statuts()` | Récupère tous les statuts | Aucun | Tableau de statuts | 