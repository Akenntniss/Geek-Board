-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 02 juil. 2025 à 22:05
-- Version du serveur : 10.11.10-MariaDB
-- Version de PHP : 7.2.34




--
-- Base de données : `u139954273_repargsm1`
--

-- --------------------------------------------------------

--
-- Structure de la table `bug_reports`
--

CREATE TABLE `bug_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `page_url` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `priorite` enum('basse','moyenne','haute','critique') NOT NULL DEFAULT 'basse',
  `status` enum('nouveau','en_cours','resolu','ferme') DEFAULT 'nouveau',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_resolution` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `bug_reports`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `inscrit_parrainage` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Client inscrit au programme de parrainage ou non',
  `code_parrainage` varchar(10) DEFAULT NULL COMMENT 'Code unique pour le parrainage (peut être null si pas inscrit)',
  `date_inscription_parrainage` timestamp NULL DEFAULT NULL COMMENT 'Date d''inscription au programme de parrainage'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clients`
--

CREATE TABLE `colis_retour` (
  `id` int(11) NOT NULL,
  `numero_suivi` varchar(100) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_expedition` datetime DEFAULT NULL,
  `statut` enum('en_preparation','en_expedition','livre') DEFAULT 'en_preparation',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commandes_fournisseurs`
--

CREATE TABLE `commandes_fournisseurs` (
  `id` int(11) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `date_commande` timestamp NULL DEFAULT current_timestamp(),
  `statut` enum('en_attente','validee','recue','annulee') DEFAULT 'en_attente',
  `montant_total` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commandes_pieces`
--

CREATE TABLE `commandes_pieces` (
  `id` int(11) NOT NULL,
  `reference` varchar(50) NOT NULL,
  `reparation_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `nom_piece` varchar(255) NOT NULL,
  `code_barre` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `prix_estime` decimal(10,2) DEFAULT NULL,
  `commentaire_interne` text DEFAULT NULL,
  `note_interne` text DEFAULT NULL,
  `urgence` enum('normal','urgent','tres_urgent') DEFAULT 'normal',
  `statut` enum('en_attente','commande','recue','annulee','urgent','termine','utilise','a_retourner') NOT NULL DEFAULT 'en_attente',
  `date_commande` datetime DEFAULT NULL,
  `date_reception` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes_pieces`
--

CREATE TABLE `commentaires_tache` (
  `id` int(11) NOT NULL,
  `tache_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `commentaire` text NOT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `is_system` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commentaires_tache`
--

CREATE TABLE `confirmations_lecture` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `date_confirmation` datetime DEFAULT NULL COMMENT 'NULL = non confirmé, datetime = confirmé à cette date',
  `rappel_envoye` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indique si un rappel a été envoyé',
  `date_rappel` datetime DEFAULT NULL COMMENT 'Date et heure d''envoi du rappel'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conges_demandes`
--

CREATE TABLE `conges_demandes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nb_jours` decimal(5,2) NOT NULL,
  `statut` enum('en_attente','approuve','refuse') DEFAULT 'en_attente',
  `type` enum('normal','impose') DEFAULT 'normal',
  `commentaire` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conges_jours_disponibles`
--

CREATE TABLE `conges_jours_disponibles` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conges_solde`
--

CREATE TABLE `conges_solde` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `solde_actuel` decimal(5,2) NOT NULL DEFAULT 0.00,
  `date_derniere_maj` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `type` enum('direct','groupe','annonce') NOT NULL DEFAULT 'direct',
  `created_by` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `derniere_activite` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conversation_participants`
--

CREATE TABLE `conversation_participants` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin','membre','lecteur') NOT NULL DEFAULT 'membre',
  `date_ajout` datetime DEFAULT current_timestamp(),
  `date_derniere_lecture` datetime DEFAULT NULL,
  `est_favoris` tinyint(1) DEFAULT 0,
  `est_archive` tinyint(1) DEFAULT 0,
  `notification_mute` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `employes`
--

CREATE TABLE `employes` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `contact_nom` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `url` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

CREATE TABLE `gardiennage` (
  `id` int(11) NOT NULL,
  `reparation_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_derniere_facturation` date NOT NULL,
  `tarif_journalier` decimal(10,2) NOT NULL DEFAULT 5.00,
  `jours_factures` int(11) NOT NULL DEFAULT 0,
  `montant_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_fin` date DEFAULT NULL,
  `derniere_notification` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gardiennage`
--

CREATE TABLE `gardiennage_notifications` (
  `id` int(11) NOT NULL,
  `gardiennage_id` int(11) NOT NULL,
  `date_notification` timestamp NULL DEFAULT current_timestamp(),
  `type_notification` enum('sms','email','appel') NOT NULL,
  `statut` enum('envoyé','échec','annulé') NOT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `help_requests`
--

CREATE TABLE `help_requests` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('en_attente','resolu','en_cours') DEFAULT 'en_attente',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `historique_soldes`
--

CREATE TABLE `historique_soldes` (
  `id` int(11) NOT NULL,
  `partenaire_id` int(11) NOT NULL,
  `ancien_solde` decimal(10,2) DEFAULT NULL,
  `nouveau_solde` decimal(10,2) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `date_modification` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `journal_actions`
--

CREATE TABLE `journal_actions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `target_id` int(11) NOT NULL,
  `details` text DEFAULT NULL,
  `date_action` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `journal_actions`
--

CREATE TABLE `kb_articles` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `views` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `kb_articles`
--

CREATE TABLE `kb_article_ratings` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL,
  `rated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `kb_article_ratings`
--

CREATE TABLE `kb_article_tags` (
  `article_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `kb_article_tags`
--

CREATE TABLE `kb_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-folder',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `kb_categories`
--

CREATE TABLE `kb_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `kb_tags`
--

CREATE TABLE `lecture_annonces` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_lecture` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `lignes_commande_fournisseur`
--

CREATE TABLE `lignes_commande_fournisseur` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `marges_estimees`
--

CREATE TABLE `marges_estimees` (
  `id` int(11) NOT NULL,
  `categorie` enum('telephone','pc','tablette') NOT NULL,
  `description` varchar(255) NOT NULL,
  `prix_estime` decimal(10,2) NOT NULL,
  `marge_recommandee` decimal(10,2) NOT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `marges_reference`
--

CREATE TABLE `marges_reference` (
  `id` int(11) NOT NULL,
  `type_reparation` varchar(255) NOT NULL,
  `categorie` enum('smartphone','tablet','computer') NOT NULL,
  `prix_achat` decimal(10,2) NOT NULL,
  `marge_pourcentage` int(11) NOT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `marges_reference`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `contenu` text DEFAULT NULL,
  `type` enum('text','file','image','system','info') NOT NULL DEFAULT 'text',
  `date_envoi` datetime DEFAULT current_timestamp(),
  `est_supprime` tinyint(1) DEFAULT 0,
  `est_modifie` tinyint(1) DEFAULT 0,
  `date_modification` datetime DEFAULT NULL,
  `est_important` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `message_attachments`
--

CREATE TABLE `message_attachments` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `est_image` tinyint(1) DEFAULT 0,
  `date_upload` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `message_reactions`
--

CREATE TABLE `message_reactions` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `date_reaction` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `message_reads`
--

CREATE TABLE `message_reads` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_lecture` datetime DEFAULT current_timestamp(),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `message_replies`
--

CREATE TABLE `message_replies` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `reply_to_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_stock`
--

CREATE TABLE `mouvements_stock` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `fournisseur_id` int(11) DEFAULT NULL,
  `type_mouvement` enum('entree','sortie') NOT NULL,
  `quantite` int(11) NOT NULL,
  `date_mouvement` timestamp NULL DEFAULT current_timestamp(),
  `motif` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `mouvements_stock`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL DEFAULT 'general',
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `action_url` varchar(255) DEFAULT NULL,
  `is_important` tinyint(1) NOT NULL DEFAULT 0,
  `is_broadcast` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('new','pending','read') DEFAULT 'new',
  `created_at` datetime DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification_preferences`
--

CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type_notification` varchar(50) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `email_notification` tinyint(1) NOT NULL DEFAULT 0,
  `push_notification` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification_types`
--

CREATE TABLE `notification_types` (
  `id` int(11) NOT NULL,
  `type_code` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `color` varchar(20) NOT NULL,
  `importance` enum('basse','normale','haute','critique') NOT NULL DEFAULT 'normale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notification_types`
--

CREATE TABLE `parametres` (
  `id` int(11) NOT NULL,
  `cle` varchar(50) NOT NULL,
  `valeur` text DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres`
--

CREATE TABLE `parametres_gardiennage` (
  `id` int(11) NOT NULL,
  `tarif_premiere_semaine` decimal(10,2) NOT NULL DEFAULT 5.00 COMMENT 'Tarif journalier pour les 7 premiers jours',
  `tarif_intermediaire` decimal(10,2) NOT NULL DEFAULT 3.00 COMMENT 'Tarif journalier de 8 à 30 jours',
  `tarif_longue_duree` decimal(10,2) NOT NULL DEFAULT 1.00 COMMENT 'Tarif journalier au-delà de 30 jours',
  `date_modification` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres_gardiennage`
--

CREATE TABLE `parrainage_config` (
  `id` int(11) NOT NULL,
  `nombre_filleuls_requis` int(11) NOT NULL DEFAULT 1 COMMENT 'Nombre de filleuls requis pour activer les récompenses',
  `seuil_reduction_pourcentage` decimal(10,2) NOT NULL DEFAULT 100.00 COMMENT 'Seuil de dépense en euros pour déclencher la réduction maximale',
  `reduction_min_pourcentage` int(11) NOT NULL DEFAULT 10 COMMENT 'Pourcentage de réduction minimum (pour dépenses < seuil)',
  `reduction_max_pourcentage` int(11) NOT NULL DEFAULT 30 COMMENT 'Pourcentage de réduction maximum (pour dépenses >= seuil)',
  `actif` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Programme actif ou non',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parrainage_config`
--

CREATE TABLE `parrainage_reductions` (
  `id` int(11) NOT NULL,
  `parrain_id` int(11) NOT NULL COMMENT 'ID du client parrain',
  `montant_depense_filleul` decimal(10,2) NOT NULL COMMENT 'Montant dépensé par le filleul qui a généré la réduction',
  `pourcentage_reduction` int(11) NOT NULL COMMENT 'Pourcentage de réduction accordé',
  `montant_reduction_max` decimal(10,2) NOT NULL COMMENT 'Montant maximum de la réduction',
  `utilise` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si la réduction a été utilisée',
  `reparation_utilisee_id` int(11) DEFAULT NULL COMMENT 'ID de la réparation où la réduction a été utilisée',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_utilisation` timestamp NULL DEFAULT NULL COMMENT 'Date d''utilisation de la réduction'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parrainage_relations`
--

CREATE TABLE `parrainage_relations` (
  `id` int(11) NOT NULL,
  `parrain_id` int(11) NOT NULL COMMENT 'ID du client parrain',
  `filleul_id` int(11) NOT NULL COMMENT 'ID du client filleul',
  `date_parrainage` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `partenaires`
--

CREATE TABLE `partenaires` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `partenaires`
--

CREATE TABLE `photos_reparation` (
  `id` int(11) NOT NULL,
  `reparation_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_upload` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `photos_reparation`
--

CREATE TABLE `pieces_avancees` (
  `id` int(11) NOT NULL,
  `partenaire_id` int(11) NOT NULL,
  `piece_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `date_avance` datetime DEFAULT current_timestamp(),
  `statut` enum('EN_ATTENTE','VALIDÉ','REMBOURSÉ','ANNULÉ') DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id` int(11) NOT NULL,
  `reference` varchar(50) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `fournisseur_id` int(11) DEFAULT NULL,
  `prix_achat` decimal(10,2) DEFAULT NULL,
  `prix_vente` decimal(10,2) DEFAULT NULL,
  `quantite` int(11) DEFAULT 0,
  `seuil_alerte` int(11) DEFAULT 5,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('normal','temporaire','a_retourner') DEFAULT 'normal',
  `date_limite_retour` date DEFAULT NULL,
  `motif_retour` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produits`
--

CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(512) NOT NULL,
  `auth_key` varchar(255) NOT NULL,
  `p256dh_key` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rachat_appareils`
--

CREATE TABLE `rachat_appareils` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `type_appareil` varchar(255) NOT NULL,
  `photo_identite` varchar(255) NOT NULL,
  `photo_appareil` varchar(255) NOT NULL,
  `signature` text NOT NULL,
  `client_photo` varchar(255) DEFAULT NULL,
  `date_rachat` datetime DEFAULT current_timestamp(),
  `sin` varchar(100) DEFAULT NULL,
  `fonctionnel` tinyint(1) DEFAULT 0,
  `prix` decimal(10,2) DEFAULT NULL,
  `modele` varchar(255) DEFAULT NULL,
  `numero_serie` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `rachat_appareils`
--

CREATE TABLE `reparations` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `type_appareil` varchar(50) NOT NULL,
  `marque` varchar(50) NOT NULL,
  `modele` varchar(100) NOT NULL,
  `description_probleme` text NOT NULL,
  `date_reception` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date_fin_prevue` date DEFAULT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'nouvelle_intervention',
  `statut_id` int(11) DEFAULT NULL,
  `statut_categorie` int(11) DEFAULT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `notes_techniques` text DEFAULT NULL,
  `notes_finales` text DEFAULT NULL,
  `photo_appareil` varchar(255) DEFAULT NULL,
  `mot_de_passe` varchar(100) DEFAULT NULL,
  `etat_esthetique` varchar(50) DEFAULT NULL,
  `prix_reparation` decimal(10,2) DEFAULT 0.00,
  `devis_envoye` enum('OUI','NON') DEFAULT 'NON',
  `devis_accepte` enum('en_attente','oui','non') DEFAULT 'en_attente',
  `date_envoi_devis` timestamp NULL DEFAULT NULL,
  `date_reponse_devis` timestamp NULL DEFAULT NULL,
  `photos` text DEFAULT NULL,
  `urgent` tinyint(1) DEFAULT 0,
  `commande_requise` tinyint(1) DEFAULT 0,
  `archive` enum('OUI','NON') DEFAULT 'NON',
  `employe_id` int(11) DEFAULT NULL,
  `date_gardiennage` date DEFAULT NULL COMMENT 'Date de début du gardiennage',
  `gardiennage_facture` decimal(10,2) DEFAULT NULL COMMENT 'Montant facturé pour le gardiennage',
  `parrain_id` int(11) DEFAULT NULL COMMENT 'ID du client parrain si le client est un filleul',
  `reduction_parrainage` decimal(10,2) DEFAULT NULL COMMENT 'Montant de la réduction appliquée via parrainage',
  `reduction_parrainage_pourcentage` int(11) DEFAULT NULL COMMENT 'Pourcentage de la réduction parrainage appliquée',
  `signature_client` varchar(255) DEFAULT NULL,
  `photo_signature` varchar(255) DEFAULT NULL,
  `photo_client` varchar(255) DEFAULT NULL,
  `accept_conditions` tinyint(1) DEFAULT 0,
  `proprietaire` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reparations`
--

CREATE TABLE `reparation_attributions` (
  `id` int(11) NOT NULL,
  `reparation_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `date_debut` timestamp NULL DEFAULT current_timestamp(),
  `date_fin` timestamp NULL DEFAULT NULL,
  `statut_avant` varchar(50) DEFAULT NULL,
  `statut_apres` varchar(50) DEFAULT NULL,
  `est_principal` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reparation_attributions`
--

CREATE TABLE `reparation_logs` (
  `id` int(11) NOT NULL,
  `reparation_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `action_type` enum('demarrage','terminer','changement_statut','ajout_note','modification','autre') NOT NULL,
  `date_action` timestamp NULL DEFAULT current_timestamp(),
  `statut_avant` varchar(50) DEFAULT NULL,
  `statut_apres` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reparation_logs`
--

CREATE TABLE `reparation_sms` (
  `id` int(11) NOT NULL,
  `reparation_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` timestamp NULL DEFAULT current_timestamp(),
  `statut_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reparation_sms`
--

CREATE TABLE `retours` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_limite` date NOT NULL,
  `statut` enum('en_attente','en_preparation','expedie','livre','a_verifier','termine') DEFAULT 'en_attente',
  `numero_suivi` varchar(100) DEFAULT NULL,
  `montant_rembourse` decimal(10,2) DEFAULT NULL,
  `montant_rembourse_client` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `colis_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `scheduled_notifications`
--

CREATE TABLE `scheduled_notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `scheduled_datetime` datetime NOT NULL,
  `sent_datetime` datetime DEFAULT NULL,
  `target_user_id` int(11) DEFAULT NULL,
  `is_broadcast` tinyint(1) NOT NULL DEFAULT 0,
  `notification_type` varchar(50) NOT NULL DEFAULT 'general',
  `action_url` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `options` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `services_partenaires`
--

CREATE TABLE `services_partenaires` (
  `id` int(11) NOT NULL,
  `partenaire_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_service` datetime DEFAULT current_timestamp(),
  `statut` enum('EN_ATTENTE','VALIDÉ','ANNULÉ') DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sms_campaigns`
--

CREATE TABLE `sms_campaigns` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` timestamp NULL DEFAULT current_timestamp(),
  `nb_destinataires` int(11) NOT NULL DEFAULT 0,
  `nb_envoyes` int(11) NOT NULL DEFAULT 0,
  `nb_echecs` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sms_campaign_details`
--

CREATE TABLE `sms_campaign_details` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `statut` enum('envoyé','échec') NOT NULL DEFAULT 'envoyé',
  `date_envoi` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `recipient` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` int(11) DEFAULT NULL,
  `reparation_id` int(11) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `date_envoi` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sms_logs`
--

CREATE TABLE `sms_template` (
  `id` int(11) NOT NULL,
  `statut_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `contenu` text NOT NULL,
  `statut_id` int(11) DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sms_templates`
--

CREATE TABLE `sms_template_variables` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `exemple` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sms_template_variables`
--

CREATE TABLE `soldes_partenaires` (
  `partenaire_id` int(11) NOT NULL,
  `solde_actuel` decimal(10,2) DEFAULT 0.00,
  `derniere_mise_a_jour` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `soldes_partenaires`
--

CREATE TABLE `statuts` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `ordre` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `statuts`
--

CREATE TABLE `statuts_reparation` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `categorie` enum('nouvelle','en_cours','en_attente','termine','annule') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `statuts_reparation`
--

CREATE TABLE `statut_categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `couleur` varchar(20) NOT NULL,
  `ordre` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `statut_categories`
--

CREATE TABLE `stock` (
  `id` int(11) NOT NULL,
  `barcode` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime DEFAULT NULL,
  `status` enum('normal','temporaire','a_retourner') DEFAULT 'normal',
  `date_limite_retour` date DEFAULT NULL,
  `motif_retour` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date_created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `taches`
--

CREATE TABLE `taches` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priorite` enum('basse','moyenne','haute','urgente') DEFAULT 'moyenne',
  `statut` enum('a_faire','en_cours','termine','annule') DEFAULT 'a_faire',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_limite` date DEFAULT NULL,
  `date_fin` timestamp NULL DEFAULT NULL,
  `employe_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `taches`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('en_attente','en_cours','termine','aide_necessaire') DEFAULT 'en_attente',
  `priority` enum('basse','moyenne','haute','urgente') DEFAULT 'moyenne',
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `transactions_partenaires`
--

CREATE TABLE `transactions_partenaires` (
  `id` int(11) NOT NULL,
  `partenaire_id` int(11) NOT NULL,
  `type` enum('AVANCE','REMBOURSEMENT','SERVICE') NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `date_transaction` datetime DEFAULT current_timestamp(),
  `reference_document` varchar(255) DEFAULT NULL,
  `statut` enum('EN_ATTENTE','VALIDÉ','ANNULÉ') DEFAULT 'EN_ATTENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `transactions_partenaires`
--

CREATE TABLE `typing_status` (
  `user_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','technicien') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `techbusy` int(11) DEFAULT 0,
  `active_repair_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_sessions`
--

ALTER TABLE `bug_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `colis_retour`
--
ALTER TABLE `colis_retour`
  ADD PRIMARY KEY (`id`),
  ADD KEY `statut` (`statut`);

--
-- Index pour la table `commandes_fournisseurs`
--
ALTER TABLE `commandes_fournisseurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fournisseur_id` (`fournisseur_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `commandes_pieces`
--
ALTER TABLE `commandes_pieces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `reparation_id` (`reparation_id`),
  ADD KEY `fournisseur_id` (`fournisseur_id`),
  ADD KEY `fk_commandes_pieces_client` (`client_id`);

--
-- Index pour la table `commentaires_tache`
--
ALTER TABLE `commentaires_tache`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tache_id` (`tache_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `confirmations_lecture`
--
ALTER TABLE `confirmations_lecture`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_confirmation` (`message_id`,`employe_id`),
  ADD KEY `employe_id` (`employe_id`);

--
-- Index pour la table `conges_demandes`
--
ALTER TABLE `conges_demandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `conges_jours_disponibles`
--
ALTER TABLE `conges_jours_disponibles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_date` (`date`);

--
-- Index pour la table `conges_solde`
--
ALTER TABLE `conges_solde`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD PRIMARY KEY (`conversation_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `employes`
--
ALTER TABLE `employes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `gardiennage`
--
ALTER TABLE `gardiennage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reparation_id` (`reparation_id`);

--
-- Index pour la table `gardiennage_notifications`
--
ALTER TABLE `gardiennage_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `gardiennage_id` (`gardiennage_id`);

--
-- Index pour la table `help_requests`
--
ALTER TABLE `help_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partenaire_id` (`partenaire_id`),
  ADD KEY `transaction_id` (`transaction_id`);

--
-- Index pour la table `journal_actions`
--
ALTER TABLE `journal_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action_type` (`action_type`),
  ADD KEY `target_id` (`target_id`);

--
-- Index pour la table `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Index pour la table `kb_article_ratings`
--
ALTER TABLE `kb_article_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `article_user` (`article_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `kb_article_tags`
--
ALTER TABLE `kb_article_tags`
  ADD PRIMARY KEY (`article_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Index pour la table `kb_categories`
--
ALTER TABLE `kb_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `kb_tags`
--
ALTER TABLE `kb_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `lecture_annonces`
--
ALTER TABLE `lecture_annonces`
  ADD PRIMARY KEY (`message_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`),
  ADD KEY `produit_id` (`produit_id`);

--
-- Index pour la table `marges_estimees`
--
ALTER TABLE `marges_estimees`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `marges_reference`
--
ALTER TABLE `marges_reference`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Index pour la table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`);

--
-- Index pour la table `message_reactions`
--
ALTER TABLE `message_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`message_id`,`user_id`,`reaction`),
  ADD KEY `idx_message_reactions_message_id` (`message_id`),
  ADD KEY `idx_message_reactions_user_id` (`user_id`);

--
-- Index pour la table `message_reads`
--
ALTER TABLE `message_reads`
  ADD PRIMARY KEY (`message_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `message_replies`
--
ALTER TABLE `message_replies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reply` (`message_id`,`reply_to_id`),
  ADD KEY `reply_to_id` (`reply_to_id`);

--
-- Index pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `mouvements_stock_fournisseur_fk` (`fournisseur_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `notification_type` (`notification_type`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Index pour la table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_type_unique` (`user_id`,`type_notification`);

--
-- Index pour la table `notification_types`
--
ALTER TABLE `notification_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_code` (`type_code`);

--
-- Index pour la table `parametres`
--
ALTER TABLE `parametres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`);

--
-- Index pour la table `parametres_gardiennage`
--
ALTER TABLE `parametres_gardiennage`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `parrainage_config`
--
ALTER TABLE `parrainage_config`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `parrainage_reductions`
--
ALTER TABLE `parrainage_reductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parrain_id` (`parrain_id`),
  ADD KEY `reparation_utilisee_id` (`reparation_utilisee_id`);

--
-- Index pour la table `parrainage_relations`
--
ALTER TABLE `parrainage_relations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_parrainage` (`filleul_id`) COMMENT 'Un filleul ne peut avoir qu''un seul parrain',
  ADD KEY `parrain_id` (`parrain_id`);

--
-- Index pour la table `partenaires`
--
ALTER TABLE `partenaires`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `photos_reparation`
--
ALTER TABLE `photos_reparation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reparation_id` (`reparation_id`);

--
-- Index pour la table `pieces_avancees`
--
ALTER TABLE `pieces_avancees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partenaire_id` (`partenaire_id`),
  ADD KEY `piece_id` (`piece_id`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `produits_fournisseur_fk` (`fournisseur_id`);

--
-- Index pour la table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_endpoint` (`user_id`,`endpoint`(255)),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `rachat_appareils`
--
ALTER TABLE `rachat_appareils`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `reparations`
--
ALTER TABLE `reparations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `fk_reparation_employe` (`employe_id`),
  ADD KEY `parrain_id` (`parrain_id`);

--
-- Index pour la table `reparation_attributions`
--
ALTER TABLE `reparation_attributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reparation` (`reparation_id`),
  ADD KEY `idx_employe` (`employe_id`);

--
-- Index pour la table `reparation_logs`
--
ALTER TABLE `reparation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reparation` (`reparation_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_date_action` (`date_action`),
  ADD KEY `idx_employe` (`employe_id`);

--
-- Index pour la table `reparation_sms`
--
ALTER TABLE `reparation_sms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reparation_id` (`reparation_id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `statut_id` (`statut_id`);

--
-- Index pour la table `retours`
--
ALTER TABLE `retours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `statut` (`statut`),
  ADD KEY `date_limite` (`date_limite`),
  ADD KEY `colis_id` (`colis_id`);

--
-- Index pour la table `scheduled_notifications`
--
ALTER TABLE `scheduled_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scheduled_datetime` (`scheduled_datetime`),
  ADD KEY `status` (`status`),
  ADD KEY `target_user_id` (`target_user_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `services_partenaires`
--
ALTER TABLE `services_partenaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partenaire_id` (`partenaire_id`);

--
-- Index pour la table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `sms_campaign_details`
--
ALTER TABLE `sms_campaign_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Index pour la table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`date_envoi`);

--
-- Index pour la table `sms_template`
--
ALTER TABLE `sms_template`
  ADD PRIMARY KEY (`id`),
  ADD KEY `statut_id` (`statut_id`);

--
-- Index pour la table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_statut` (`statut_id`);

--
-- Index pour la table `sms_template_variables`
--
ALTER TABLE `sms_template_variables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `soldes_partenaires`
--
ALTER TABLE `soldes_partenaires`
  ADD PRIMARY KEY (`partenaire_id`);

--
-- Index pour la table `statuts`
--
ALTER TABLE `statuts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `categorie_id` (`categorie_id`);

--
-- Index pour la table `statuts_reparation`
--
ALTER TABLE `statuts_reparation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `statut_categories`
--
ALTER TABLE `statut_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`);

--
-- Index pour la table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `taches`
--
ALTER TABLE `taches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `taches_ibfk_1` (`employe_id`);

--
-- Index pour la table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `transactions_partenaires`
--
ALTER TABLE `transactions_partenaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transactions_partenaires` (`partenaire_id`);

--
-- Index pour la table `typing_status`
--
ALTER TABLE `typing_status`
  ADD PRIMARY KEY (`user_id`,`conversation_id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Index pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `expiry` (`expiry`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `bug_reports`
--
ALTER TABLE `bug_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=732;

--
-- AUTO_INCREMENT pour la table `colis_retour`
--
ALTER TABLE `colis_retour`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commandes_fournisseurs`
--
ALTER TABLE `commandes_fournisseurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commandes_pieces`
--
ALTER TABLE `commandes_pieces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=219;

--
-- AUTO_INCREMENT pour la table `commentaires_tache`
--
ALTER TABLE `commentaires_tache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `confirmations_lecture`
--
ALTER TABLE `confirmations_lecture`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `conges_demandes`
--
ALTER TABLE `conges_demandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `conges_jours_disponibles`
--
ALTER TABLE `conges_jours_disponibles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `conges_solde`
--
ALTER TABLE `conges_solde`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `employes`
--
ALTER TABLE `employes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `gardiennage`
--
ALTER TABLE `gardiennage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `gardiennage_notifications`
--
ALTER TABLE `gardiennage_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `help_requests`
--
ALTER TABLE `help_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `journal_actions`
--
ALTER TABLE `journal_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `kb_articles`
--
ALTER TABLE `kb_articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `kb_article_ratings`
--
ALTER TABLE `kb_article_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `kb_categories`
--
ALTER TABLE `kb_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `kb_tags`
--
ALTER TABLE `kb_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `marges_estimees`
--
ALTER TABLE `marges_estimees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `marges_reference`
--
ALTER TABLE `marges_reference`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `message_attachments`
--
ALTER TABLE `message_attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `message_reactions`
--
ALTER TABLE `message_reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `message_replies`
--
ALTER TABLE `message_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification_preferences`
--
ALTER TABLE `notification_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification_types`
--
ALTER TABLE `notification_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `parametres`
--
ALTER TABLE `parametres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `parametres_gardiennage`
--
ALTER TABLE `parametres_gardiennage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `parrainage_config`
--
ALTER TABLE `parrainage_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `parrainage_reductions`
--
ALTER TABLE `parrainage_reductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parrainage_relations`
--
ALTER TABLE `parrainage_relations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `partenaires`
--
ALTER TABLE `partenaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `photos_reparation`
--
ALTER TABLE `photos_reparation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT pour la table `pieces_avancees`
--
ALTER TABLE `pieces_avancees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rachat_appareils`
--
ALTER TABLE `rachat_appareils`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT pour la table `reparations`
--
ALTER TABLE `reparations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=980;

--
-- AUTO_INCREMENT pour la table `reparation_attributions`
--
ALTER TABLE `reparation_attributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=195;

--
-- AUTO_INCREMENT pour la table `reparation_logs`
--
ALTER TABLE `reparation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2074;

--
-- AUTO_INCREMENT pour la table `reparation_sms`
--
ALTER TABLE `reparation_sms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=759;

--
-- AUTO_INCREMENT pour la table `retours`
--
ALTER TABLE `retours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `scheduled_notifications`
--
ALTER TABLE `scheduled_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `services_partenaires`
--
ALTER TABLE `services_partenaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_campaign_details`
--
ALTER TABLE `sms_campaign_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=984;

--
-- AUTO_INCREMENT pour la table `sms_template`
--
ALTER TABLE `sms_template`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_templates`
--
ALTER TABLE `sms_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `sms_template_variables`
--
ALTER TABLE `sms_template_variables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `statuts`
--
ALTER TABLE `statuts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `statuts_reparation`
--
ALTER TABLE `statuts_reparation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `statut_categories`
--
ALTER TABLE `statut_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `stock`
--
ALTER TABLE `stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `taches`
--
ALTER TABLE `taches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `transactions_partenaires`
--
ALTER TABLE `transactions_partenaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=600;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `bug_reports`
--
ALTER TABLE `bug_reports`
  ADD CONSTRAINT `bug_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `commandes_fournisseurs`
--
ALTER TABLE `commandes_fournisseurs`
  ADD CONSTRAINT `commandes_fournisseurs_ibfk_1` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `commandes_fournisseurs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `commandes_pieces`
--
ALTER TABLE `commandes_pieces`
  ADD CONSTRAINT `commandes_pieces_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `commandes_pieces_ibfk_2` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `fk_commandes_pieces_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Contraintes pour la table `commentaires_tache`
--
ALTER TABLE `commentaires_tache`
  ADD CONSTRAINT `commentaires_tache_ibfk_1` FOREIGN KEY (`tache_id`) REFERENCES `taches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaires_tache_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `confirmations_lecture`
--
ALTER TABLE `confirmations_lecture`
  ADD CONSTRAINT `confirmations_lecture_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `confirmations_lecture_ibfk_2` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `conges_demandes`
--
ALTER TABLE `conges_demandes`
  ADD CONSTRAINT `conges_demandes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `conges_demandes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `conges_jours_disponibles`
--
ALTER TABLE `conges_jours_disponibles`
  ADD CONSTRAINT `conges_jours_disponibles_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `conges_solde`
--
ALTER TABLE `conges_solde`
  ADD CONSTRAINT `conges_solde_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `conversation_participants`
--
ALTER TABLE `conversation_participants`
  ADD CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `gardiennage`
--
ALTER TABLE `gardiennage`
  ADD CONSTRAINT `gardiennage_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `gardiennage_notifications`
--
ALTER TABLE `gardiennage_notifications`
  ADD CONSTRAINT `gardiennage_notifications_ibfk_1` FOREIGN KEY (`gardiennage_id`) REFERENCES `gardiennage` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `help_requests`
--
ALTER TABLE `help_requests`
  ADD CONSTRAINT `help_requests_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `help_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  ADD CONSTRAINT `historique_soldes_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `historique_soldes_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `transactions_partenaires` (`id`);

--
-- Contraintes pour la table `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD CONSTRAINT `kb_articles_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `kb_categories` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `kb_article_ratings`
--
ALTER TABLE `kb_article_ratings`
  ADD CONSTRAINT `kb_article_ratings_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `kb_articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kb_article_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `kb_article_tags`
--
ALTER TABLE `kb_article_tags`
  ADD CONSTRAINT `kb_article_tags_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `kb_articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kb_article_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `kb_tags` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lecture_annonces`
--
ALTER TABLE `lecture_annonces`
  ADD CONSTRAINT `lecture_annonces_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lecture_annonces_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lignes_commande_fournisseur`
--
ALTER TABLE `lignes_commande_fournisseur`
  ADD CONSTRAINT `lignes_commande_fournisseur_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes_fournisseurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lignes_commande_fournisseur_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`);

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `message_attachments`
--
ALTER TABLE `message_attachments`
  ADD CONSTRAINT `message_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `message_reactions`
--
ALTER TABLE `message_reactions`
  ADD CONSTRAINT `message_reactions_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_reactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `message_reads`
--
ALTER TABLE `message_reads`
  ADD CONSTRAINT `message_reads_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_reads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `message_replies`
--
ALTER TABLE `message_replies`
  ADD CONSTRAINT `message_replies_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_replies_ibfk_2` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mouvements_stock`
--
ALTER TABLE `mouvements_stock`
  ADD CONSTRAINT `mouvements_stock_fournisseur_fk` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `mouvements_stock_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mouvements_stock_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `parrainage_reductions`
--
ALTER TABLE `parrainage_reductions`
  ADD CONSTRAINT `parrainage_reductions_ibfk_1` FOREIGN KEY (`parrain_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parrainage_reductions_ibfk_2` FOREIGN KEY (`reparation_utilisee_id`) REFERENCES `reparations` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `parrainage_relations`
--
ALTER TABLE `parrainage_relations`
  ADD CONSTRAINT `parrainage_relations_ibfk_1` FOREIGN KEY (`parrain_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parrainage_relations_ibfk_2` FOREIGN KEY (`filleul_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `photos_reparation`
--
ALTER TABLE `photos_reparation`
  ADD CONSTRAINT `photos_reparation_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `pieces_avancees`
--
ALTER TABLE `pieces_avancees`
  ADD CONSTRAINT `pieces_avancees_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `pieces_avancees_ibfk_2` FOREIGN KEY (`piece_id`) REFERENCES `produits` (`id`);

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `produits_fournisseur_fk` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `produits_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD CONSTRAINT `push_subscriptions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `rachat_appareils`
--
ALTER TABLE `rachat_appareils`
  ADD CONSTRAINT `rachat_appareils_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

--
-- Contraintes pour la table `reparations`
--
ALTER TABLE `reparations`
  ADD CONSTRAINT `fk_reparation_employe` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reparations_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `reparations_ibfk_2` FOREIGN KEY (`parrain_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `reparation_attributions`
--
ALTER TABLE `reparation_attributions`
  ADD CONSTRAINT `fk_attribution_reparation` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attribution_user` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reparation_logs`
--
ALTER TABLE `reparation_logs`
  ADD CONSTRAINT `fk_log_reparation` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reparation_sms`
--
ALTER TABLE `reparation_sms`
  ADD CONSTRAINT `reparation_sms_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reparation_sms_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `sms_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reparation_sms_ibfk_3` FOREIGN KEY (`statut_id`) REFERENCES `statuts` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `retours`
--
ALTER TABLE `retours`
  ADD CONSTRAINT `retours_ibfk_1` FOREIGN KEY (`produit_id`) REFERENCES `stock` (`id`),
  ADD CONSTRAINT `retours_ibfk_2` FOREIGN KEY (`colis_id`) REFERENCES `colis_retour` (`id`);

--
-- Contraintes pour la table `scheduled_notifications`
--
ALTER TABLE `scheduled_notifications`
  ADD CONSTRAINT `scheduled_notifications_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `scheduled_notifications_target_user_fk` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `services_partenaires`
--
ALTER TABLE `services_partenaires`
  ADD CONSTRAINT `services_partenaires_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `fournisseurs` (`id`);

--
-- Contraintes pour la table `sms_campaigns`
--
ALTER TABLE `sms_campaigns`
  ADD CONSTRAINT `sms_campaigns_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `sms_campaign_details`
--
ALTER TABLE `sms_campaign_details`
  ADD CONSTRAINT `sms_campaign_details_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `sms_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sms_campaign_details_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sms_template`
--
ALTER TABLE `sms_template`
  ADD CONSTRAINT `sms_template_ibfk_1` FOREIGN KEY (`statut_id`) REFERENCES `statuts` (`id`);

--
-- Contraintes pour la table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD CONSTRAINT `sms_templates_ibfk_1` FOREIGN KEY (`statut_id`) REFERENCES `statuts` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `soldes_partenaires`
--
ALTER TABLE `soldes_partenaires`
  ADD CONSTRAINT `fk_soldes_partenaires` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `soldes_partenaires_ibfk_1` FOREIGN KEY (`partenaire_id`) REFERENCES `fournisseurs` (`id`);

--
-- Contraintes pour la table `statuts`
--
ALTER TABLE `statuts`
  ADD CONSTRAINT `statuts_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `statut_categories` (`id`);

--
-- Contraintes pour la table `statuts_reparation`
--
ALTER TABLE `statuts_reparation`
  ADD CONSTRAINT `statuts_reparation_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `stock` (`id`);

--
-- Contraintes pour la table `taches`
--
ALTER TABLE `taches`
  ADD CONSTRAINT `taches_ibfk_1` FOREIGN KEY (`employe_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `taches_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `transactions_partenaires`
--
ALTER TABLE `transactions_partenaires`
  ADD CONSTRAINT `fk_transactions_partenaires` FOREIGN KEY (`partenaire_id`) REFERENCES `partenaires` (`id`);

--
-- Contraintes pour la table `typing_status`
--
ALTER TABLE `typing_status`
  ADD CONSTRAINT `typing_status_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `typing_status_ibfk_2` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

