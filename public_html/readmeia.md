# Système de Gestion de Réparations d'Appareils - Prompt de Développement

Ce document contient un prompt détaillé pour recréer entièrement le système de gestion des réparations avec toutes ses fonctionnalités.

## Présentation générale du système

Développe un système complet de gestion des réparations pour une entreprise de services techniques. Ce système doit permettre de suivre l'ensemble du processus de réparation d'appareils électroniques (téléphones, ordinateurs, tablettes, etc.) depuis la réception jusqu'à la livraison au client.

## Structure de la base de données

Utilise MariaDB avec la structure suivante :

### Tables principales

#### clients
```sql
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### reparations
```sql
CREATE TABLE reparations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    type_appareil VARCHAR(50) NOT NULL,
    marque VARCHAR(50) NOT NULL,
    modele VARCHAR(100) NOT NULL,
    description_probleme TEXT NOT NULL,
    date_reception TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_fin_prevue DATE,
    date_fin DATETIME NULL,
    statut VARCHAR(50) NOT NULL DEFAULT 'nouvelle_intervention',
    statut_categorie INT NOT NULL DEFAULT 1,
    prix_reparation DECIMAL(10,2) NULL,
    notes_techniques TEXT,
    notes_finales TEXT,
    date_modification DATETIME NULL,
    archive ENUM('OUI', 'NON') DEFAULT 'NON',
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    FOREIGN KEY (statut_categorie) REFERENCES statut_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### users
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'technicien') NOT NULL,
    techbusy TINYINT DEFAULT 0,
    active_repair_id INT NULL,
    remember_token VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### statut_categories
```sql
CREATE TABLE statut_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    couleur VARCHAR(20) NOT NULL,
    ordre INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### statuts
```sql
CREATE TABLE statuts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    categorie_id INT NOT NULL,
    est_actif BOOLEAN NOT NULL DEFAULT TRUE,
    ordre INT NOT NULL DEFAULT 0,
    FOREIGN KEY (categorie_id) REFERENCES statut_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### reparation_attributions
```sql
CREATE TABLE reparation_attributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reparation_id INT NOT NULL,
    employe_id INT NOT NULL,
    date_debut TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_fin TIMESTAMP NULL DEFAULT NULL,
    statut_avant VARCHAR(50) NULL,
    statut_apres VARCHAR(50) NULL,
    est_principal TINYINT(1) DEFAULT 1,
    FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE,
    FOREIGN KEY (employe_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### reparation_logs
```sql
CREATE TABLE reparation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reparation_id INT NOT NULL,
    employe_id INT NOT NULL,
    action_type ENUM('demarrage', 'terminer', 'changement_statut', 'ajout_note', 'modification', 'autre') NOT NULL,
    date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut_avant VARCHAR(50) NULL,
    statut_apres VARCHAR(50) NULL,
    details TEXT NULL,
    FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE,
    FOREIGN KEY (employe_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### sms_templates
```sql
CREATE TABLE sms_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    contenu TEXT NOT NULL,
    statut_id INT NULL,
    est_actif BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (statut_id) REFERENCES statuts(id) ON DELETE SET NULL,
    UNIQUE KEY unique_statut (statut_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### reparation_sms
```sql
CREATE TABLE reparation_sms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reparation_id INT NOT NULL,
    template_id INT NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut_id INT NULL,
    FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES sms_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (statut_id) REFERENCES statuts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### photos_reparation
```sql
CREATE TABLE photos_reparation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reparation_id INT NOT NULL,
    url VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    date_upload DATETIME NOT NULL DEFAULT current_timestamp(),
    FOREIGN KEY (reparation_id) REFERENCES reparations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Données initiales importantes

#### Catégories de statuts
```sql
INSERT INTO statut_categories (nom, code, couleur, ordre) VALUES
('Nouvelle', 'nouvelle', 'info', 1),
('En cours', 'en_cours', 'primary', 2),
('En attente', 'en_attente', 'warning', 3),
('Terminé', 'termine', 'success', 4),
('Annulé', 'annule', 'danger', 5);
```

#### Statuts
```sql
INSERT INTO statuts (nom, code, categorie_id, ordre) VALUES
-- Catégorie Nouvelle (id=1)
('Nouveau Diagnostique', 'nouveau_diagnostique', 1, 1),
('Nouvelle Intervention', 'nouvelle_intervention', 1, 2),
('Nouvelle Commande', 'nouvelle_commande', 1, 3),

-- Catégorie En cours (id=2)
('En cours de diagnostique', 'en_cours_diagnostique', 2, 1),
('En cours d\'intervention', 'en_cours_intervention', 2, 2),

-- Catégorie En attente (id=3)
('En attente de l\'accord client', 'en_attente_accord_client', 3, 1),
('En attente de livraison', 'en_attente_livraison', 3, 2),
('En attente d\'un responsable', 'en_attente_responsable', 3, 3),

-- Catégorie Terminé (id=4)
('Réparation Effectuée', 'reparation_effectue', 4, 1),
('Réparation Annulée', 'reparation_annule', 4, 2),

-- Catégorie Annulé (id=5)
('Restitué', 'restitue', 5, 1),
('Gardiennage', 'gardiennage', 5, 2),
('Annulé', 'annule', 5, 3);
```

### Workflow des réparations
Le système doit gérer le cycle complet :
1. Réception de l'appareil
2. Diagnostic initial
3. Génération de devis (si nécessaire)
4. Attente d'accord client
5. Commande de pièces (si nécessaire)
6. Réparation/intervention
7. Finalisation
8. Livraison/restitution au client

## Pages principales et leur fonctionnement

### 1. Dashboard (accueil.php)
- Vue d'ensemble avec compteurs de réparations par statut
- Accès rapide aux fonctionnalités principales
- Liste des dernières réparations et tâches
- Statistiques et graphiques de performance
- Actions rapides (nouvelle réparation, recherche client, etc.)

### 2. Gestion des réparations (reparations.php)
- Tableau de bord filtrable par statut, technicien, date
- Système d'attribution des réparations aux techniciens
- Boutons d'action rapide pour changer les statuts
- Filtres avancés avec recherche dynamique
- Vue en liste ou tableau des réparations

### 3. Détails d'une réparation (details_reparation.php)
- Informations complètes sur la réparation
- Historique des actions (logs)
- Upload de photos
- Notes techniques
- Actions disponibles selon le statut
- Communication avec le client (SMS)

### 4. Ajout/modification de réparation (ajouter_reparation.php, modifier_reparation.php)
- Formulaire complet avec validation
- Sélection du client existant ou création de nouveau client
- Choix du type d'appareil, marque, modèle
- Description du problème
- Date de réception et estimation de fin

### 5. Statut rapide (statut_rapide.php)
- Interface optimisée pour les techniciens
- Changement rapide de statut
- Attribution des réparations
- Vue centrée sur les réparations en cours de l'utilisateur
- Boutons d'action adaptés au contexte

### 6. Gestion des SMS (sms_templates.php)
- Configuration des modèles de SMS par statut
- Prévisualisation
- Variables dynamiques pour personnalisation
- Historique des SMS envoyés

### 7. Gestion des tâches (taches.php)
- Création et attribution des tâches aux techniciens
- Suivi de l'avancement
- Commentaires et échanges sur les tâches
- Priorités et échéances
- Tableau de bord Kanban avec colonnes de statut

### 8. Gardiennage (gardiennage.php)
- Gestion des appareils en attente prolongée
- Facturation automatique
- Envoi de rappels SMS aux clients
- Suivi des montants accumulés
- Génération de factures pour le gardiennage

### 9. Suivi client (suivi_reparation.php)
- Interface publique pour les clients
- Consultation du statut par numéro de réparation
- Historique des réparations
- Acceptation des devis en ligne

### 10. Commandes de pièces (commandes_pieces.php)
- Gestion des commandes fournisseurs
- Suivi des commandes
- Association aux réparations
- Statuts de livraison

### 11. Base de connaissances (base_connaissances.php, gestion_kb.php)
- Articles techniques
- Procédures de réparation
- Recherche par mot-clé
- Catégories et tags
- Gestion des articles techniques (CRUD)

### 12. Paramètres (parametre.php)
- Configuration globale du système
- Gestion des utilisateurs et permissions
- Personnalisation de l'interface
- Réglages des notifications
- Configuration des API externes (SMS, etc.)

### 13. Historique des SMS (sms_historique.php)
- Journal complet des SMS envoyés
- Filtrage par date, client, statut
- Détails du contenu des messages
- Statut d'envoi (succès/échec)

### 14. Scanner (scanner.php)
- Interface pour scanner des codes-barres
- Recherche rapide de réparations par code
- Prise en charge des appareils mobiles
- Historique des scans récents

### 15. Logs de réparation (reparation_logs.php)
- Journal détaillé de toutes les actions
- Historique complet par réparation
- Filtrage par type d'action, utilisateur, date
- Audit des modifications

### 16. Messagerie interne (messagerie.php)
- Système de chat entre techniciens
- Conversations privées et de groupe
- Partage de fichiers et images
- Notifications en temps réel

### 17. Inventaire (inventaire.php)
- Gestion du stock de pièces détachées
- Suivi des entrées/sorties
- Alertes de stock bas
- Associations avec réparations et commandes

### 18. Impression d'étiquettes (imprimer_etiquette.php)
- Génération d'étiquettes pour les réparations
- Codes-barres pour identification rapide
- Formats personnalisables
- Impression directe ou PDF

### 19. Fournisseurs (fournisseurs.php)
- Gestion des fournisseurs et contacts
- Historique des commandes par fournisseur
- Évaluation et notes
- Catalogues de produits

### 20. Gestion des congés (conges.php)
- Planning des absences des techniciens
- Demandes et validation de congés
- Calendrier d'équipe
- Impact sur la planification des réparations

### 21. Campagnes SMS (campagne_sms.php)
- Création de campagnes marketing
- Segmentation de la clientèle
- Envoi programmé de messages groupés
- Statistiques d'envoi et de lecture

### 22. Comptes partenaires (comptes_partenaires.php)
- Gestion des comptes revendeurs
- Tarification spéciale
- Tableau de bord dédié
- Suivi des commissions et partenariats

## Fonctionnalités principales à implémenter

### Gestion des clients
- Ajout et modification des informations clients
- Recherche de clients par nom, téléphone ou email
- Historique des réparations par client

### Gestion des réparations
- Création de nouvelles réparations avec choix du client
- Suivi du statut des réparations en temps réel
- Attribution des réparations aux techniciens
- Transfert de réparation entre techniciens
- Ajout de notes techniques
- Upload de photos de l'appareil
- Impression de fiche de réparation/devis
- Filtrage des réparations par statut, technicien, date

### Communication avec les clients
- Envoi de SMS automatiques lors des changements de statut (devis, réparation terminée, etc.)
- Gestion des modèles de SMS personnalisables par statut
- Historique des communications

### Système d'authentification
- Connexion sécurisée avec rôles (admin, technicien)
- Sessions persistantes
- Protection des routes sensibles

### Interface utilisateur
- Design responsive pour utilisation sur ordinateur et mobile
- Dashboard avec vue d'ensemble des réparations
- PWA (Progressive Web App) pour installation sur mobile

## Technologies à utiliser

- **Backend** : PHP (structure procédurale)
- **Base de données** : MariaDB
- **Frontend** : HTML, CSS (Bootstrap), JavaScript (jQuery)
- **Sécurité** : PDO pour les requêtes préparées, validation des entrées
- **Communication** : API SMS pour l'envoi de messages

## Architecture des fichiers

- **/index.php** : Point d'entrée principal
- **/config/** : Configuration de la base de données et de l'application
- **/includes/** : Fonctions partagées et composants réutilisables
- **/pages/** : Pages principales de l'application
- **/ajax/** : Handlers pour les requêtes AJAX
- **/assets/** : Ressources statiques (CSS, JS, images)
- **/uploads/** : Stockage des photos des réparations

## Processus principaux à implémenter

### Process de devis
1. Le technicien diagnostique le problème
2. Il saisit un montant de devis
3. Le système met la réparation en statut "en_attente_accord_client"
4. Un SMS est automatiquement envoyé au client avec le montant du devis
5. Le client répond par SMS ou appelle pour accepter/refuser
6. Si accepté, la réparation passe en "en_cours_intervention"

### Process d'attribution
1. Un technicien peut s'attribuer une réparation
2. La réparation passe en "en_cours_diagnostique" ou "en_cours_intervention"
3. Le technicien devient "occupé" (techbusy=1)
4. La réparation active est associée au technicien (active_repair_id)
5. Un log d'attribution est créé
6. Un technicien peut terminer sa réparation
7. D'autres techniciens peuvent participer à la réparation (est_principal=0)

### Process de finalisation
1. Le technicien termine la réparation
2. Il choisit un statut final (réparation_effectue, annulé, etc.)
3. Il peut ajouter une note finale
4. Un SMS peut être envoyé automatiquement
5. Le technicien est libéré (techbusy=0)
6. Le système enregistre la date de fin

## Fonctionnalités avancées

- Système de recherche avancée
- Gestion des stocks de pièces détachées
- Statistiques et rapports
- Gestion des tâches pour les techniciens
- System de parrainage de clients
- Système de messagerie interne
- Base de connaissances pour les procédures de réparation

## Intégrations externes et aspects techniques

- API SMS pour les notifications clients
- Impression de PDF (factures, devis)
- PWA pour l'installation sur mobile et les notifications push
- Optimisation pour écrans tactiles et tablettes
- Mode hors ligne pour accéder aux réparations en cours
- Synchronisation côté client avec ServiceWorker

## Sécurité et performances

- Protection contre les injections SQL avec PDO
- Hashage des mots de passe avec bcrypt
- Tokens CSRF pour les formulaires
- Sessions sécurisées HTTP-only
- Validation des entrées côté serveur et client
- Mise en cache pour les éléments statiques
- Compression des ressources
- Optimisation des requêtes SQL avec indexation

Pour une implémentation efficace, commence par créer la structure de la base de données, puis développe l'authentification, le CRUD des clients et des réparations, et enfin les fonctionnalités avancées. Assure-toi d'implémenter des mesures de sécurité robustes et une UI/UX fluide et intuitive. 