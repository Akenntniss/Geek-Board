/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.7.2-MariaDB, for osx10.20 (arm64)
--
-- Host: 191.96.63.103    Database: u139954273_cannesphones
-- ------------------------------------------------------
-- Server version	10.11.10-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `Log_tasks`
--

DROP TABLE IF EXISTS `Log_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `Log_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` enum('demarrer','terminer','pause','reprendre','modifier','creer','supprimer') NOT NULL,
  `old_status` enum('a_faire','en_cours','termine','pause','annule') DEFAULT NULL,
  `new_status` enum('a_faire','en_cours','termine','pause','annule') DEFAULT NULL,
  `action_timestamp` timestamp NULL DEFAULT current_timestamp(),
  `user_name` varchar(255) DEFAULT NULL,
  `task_title` varchar(500) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_timestamp` (`action_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Log_tasks`
--

LOCK TABLES `Log_tasks` WRITE;
/*!40000 ALTER TABLE `Log_tasks` DISABLE KEYS */;
INSERT INTO `Log_tasks` VALUES
(1,29,NULL,'demarrer','a_faire','en_cours','2025-06-15 23:31:15',NULL,'cannesphones_sample','Tâche démarrée depuis le statut: a_faire','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(2,29,NULL,'terminer','en_cours','termine','2025-06-15 23:32:27',NULL,'cannesphones_sample','Tâche terminée depuis le statut: en_cours','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');
/*!40000 ALTER TABLE `Log_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_reports`
--

DROP TABLE IF EXISTS `bug_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bug_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `page_url` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `priorite` enum('basse','moyenne','haute','critique') NOT NULL DEFAULT 'basse',
  `status` enum('nouveau','en_cours','resolu','ferme') DEFAULT 'nouveau',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_resolution` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `bug_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_reports`
--

LOCK TABLES `bug_reports` WRITE;
/*!40000 ALTER TABLE `bug_reports` DISABLE KEYS */;
INSERT INTO `bug_reports` VALUES
(2,NULL,'Quand je clique sur démarrer la réparation il faut que ça me redirige vers la page statut_rapide',NULL,NULL,'basse','resolu','2025-04-12 01:47:36','2025-04-17 21:29:41'),
(3,NULL,'Ça n’envoi pas de sms quand je glisse un dossier dans un nouveau statut',NULL,NULL,'basse','resolu','2025-04-12 01:50:04','2025-04-17 21:29:36'),
(4,NULL,'Il faudrait rajouter un module pour vérifier lorsque le prix est à 01 message de confirmation pour demander si on est sûre que le montant de la réparation est à zéro euro',NULL,NULL,'basse','resolu','2025-04-12 15:51:06','2025-04-21 20:53:38'),
(5,NULL,'J’ai un message me demandant de me connecter lorsque, j’appuie sur le bouton démarrer pour démarrer la tâche',NULL,NULL,'basse','resolu','2025-04-12 15:53:12','2025-04-17 21:29:23'),
(6,NULL,'Quand je clique sur Terminer, ca me redirige vers \nhttps://mdgeek.top/index.php?page=index.php%3Fpage%3Dreparations\net jai une erreur 404\n404\nPage non trouvée\n\nLa page que vous recherchez n&#039;existe pas ou a été déplacée.\n\n\n\n--\nje voudrais que ca me redirige vers \nhttps://mdgeek.top/index.php?page=reparations',NULL,NULL,'basse','resolu','2025-04-12 16:39:02','2025-04-17 21:29:19'),
(8,1,'Au format mobile, quand j’ajoute une réparation, j’ai du mal à imprimer l’étiquette','https://mdgeek.top/index.php','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-15 11:46:37','2025-04-17 21:29:04'),
(9,1,'Au format mobile, quand j’ajoute une réparation, j’ai du mal à imprimer l’étiquette','https://mdgeek.top/index.php','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-15 11:46:37','2025-04-17 21:29:10'),
(14,NULL,'afficher le nom de la personne qui a cree la reparation','https://mdgeek.top/index.php?page=reparations','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','basse','resolu','2025-04-16 23:02:14','2025-04-21 20:53:36'),
(15,NULL,'Je voudrais que dans le champ éteins les réparations, tu affiche aussi les réparations en cours et les réparations en attente\r\n\r\nEt je voudrais que à côté du mot réparations récentes, tu affiche le numéro correspondant au nombre de réparations dans la catégorie nouvelles','https://mdgeek.top/index.php','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','basse','resolu','2025-04-16 23:16:02','2025-04-17 00:10:47'),
(16,NULL,'Quand je veux ajouter un client depuis la page Rachat appareil, j’ai un message qui m’empêche d’enregistrer le client','https://mdgeek.top/index.php?page=rachat_appareils','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3.1 Safari/605.1.15','basse','resolu','2025-04-17 13:14:28','2025-04-21 20:53:32'),
(17,NULL,'Le SMS qui est envoyé lorsque la réparation est effectuée.\r\nIl faudrait remplacer le mot informatique par appareil','https://mdgeek.top/index.php?page=reparations','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-17 14:56:46','2025-04-21 20:53:30'),
(18,NULL,'Quand je glisse une reparation dans un statut, ca n;envoi pas de sms','https://mdgeek.top/index.php?page=reparations','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','basse','resolu','2025-04-18 00:05:28','2025-04-21 20:53:31'),
(19,NULL,'Verifier pourquoi le module d\'accepation de devis ne fonctionne pas','https://mdgeek.top/index.php','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','basse','resolu','2025-04-18 00:09:04','2025-04-21 20:52:56'),
(20,NULL,'Ca nenvoi pas de sms lors du glisser deposer','https://mdgeek.top/index.php?page=reparations','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','basse','nouveau','2025-04-18 00:41:35',NULL),
(21,1,'ajouter des emoticon au sms','https://mdgeek.top/index.php','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','basse','resolu','2025-04-18 01:21:33','2025-04-21 20:53:12'),
(22,NULL,'Si Rayan commence une réparation, je ne peux pas en commencer une autre.','https://mdgeek.top/index.php?page=reparations&view=cards','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-19 09:47:59','2025-04-21 20:53:16'),
(24,NULL,'Lorsque je veux démarrer une nouvelle réparation, si j’avais une réparation en cours avec marqué en attente d’un responsable, je ne peux pas en démarrer une nouvelle cela, crée un bug','https://mdgeek.top/index.php?page=taches','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-19 10:03:12','2025-04-21 20:54:51'),
(26,NULL,'Il faut décaler le contenu du modèle, ajouter une pièce de 60 PX au format Mobile','https://mdgeek.top/index.php?page=inventaire','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-21 02:02:04','2025-04-21 20:56:09'),
(28,NULL,'Ajouter une colonne fournisseur ainsi qu’un bouton Google','https://mdgeek.top/index.php?page=inventaire','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 02:06:48',NULL),
(30,NULL,'Le bouton de filtre dans le tableau ne fonctionne pas, je ne peux pas filtrer par ce statut','https://mdgeek.top/index.php?page=inventaire','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 02:07:27',NULL),
(32,NULL,'Le bouton exporter ne fonctionne pas','https://mdgeek.top/index.php?page=inventaire','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 02:07:47',NULL),
(34,NULL,'Vérifier pourquoi les notifications ne marche pas','https://mdgeek.top/index.php?page=ajouter_reparation','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 17:52:06',NULL),
(36,1,'Ajouter un champs pour assigner à un employée ( technicien )','https://mdgeek.top/index.php?page=ajouter_reparation','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:17:09',NULL),
(38,1,'Le bouton ajouter une photo ne fonctionne pas il me redirigé au lieu d’afficher un modal pour prendre une photo','https://mdgeek.top/index.php?page=statut_rapide&id=635','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-21 18:18:04','2025-04-21 21:07:06'),
(39,1,'Je veut que tu decalle de 60px vers le bas au format mobile uniquement\r\nLe modal nouvelle commande de piece','https://mdgeek.top/index.php?page=statut_rapide&id=631','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:18:52',NULL),
(41,1,'Dans le modal envoyer un devis ajoute l’option pour détailler le sms','https://mdgeek.top/index.php?page=statut_rapide&id=631','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:19:31',NULL),
(43,1,'Le bouton statut n’affiche pas le modal pour changer le statut','https://mdgeek.top/index.php?page=statut_rapide&id=631','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:20:02',NULL),
(45,1,'Quand j’appuie pour envoyer un devis, j’ai un message qui dit erreur de communication avec le serveur alors que le sms d’envoi correctement ( je reçoit bien le sms lors de mon test )','https://mdgeek.top/index.php?page=statut_rapide&id=631','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:21:05',NULL),
(47,1,'Dans le sms du devis il ne prend pas en charge la valeur des variables','https://mdgeek.top/index.php?page=statut_rapide&id=631','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:22:11',NULL),
(48,1,'Le sms du prix ne mentionne pas le prix','https://mdgeek.top/index.php?page=statut_rapide&id=631','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:22:37',NULL),
(49,1,'Le bouton restituée fonctionne mais me redirigé vers une page qui me donne une erreuf','https://mdgeek.top/index.php?page=statut_rapide&id=631','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:23:10',NULL),
(51,1,'Le bouton envoyer un sms ne fonctionne pas','https://mdgeek.top/index.php?page=statut_rapide&id=631','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-21 18:23:30','2025-04-21 21:42:39'),
(52,1,'Problème de couleur modal confirmation du gardiennage en mode nuit','https://mdgeek.top/index.php?page=statut_rapide&id=631','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:24:14',NULL),
(55,1,'Problème de couleur mode nuit modal devis','https://mdgeek.top/index.php?page=statut_rapide&id=631','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:24:39',NULL),
(57,1,'Après avoir terminer la réparation Quand je sélectionne une réparation en statut \r\nEn attente d’un responsable.\r\nÇa ne desassigne pas la réparation au technicien','https://mdgeek.top/index.php?page=statut_rapide&id=632','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-21 18:26:14','2025-04-22 00:45:26'),
(59,1,'Dans le modal prix quand j’appuie sur le clavier ça me compte les chiffre en double, si je veut écrit 321 ca va m’écrit 332211','https://mdgeek.top/index.php?page=statut_rapide&id=632','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:26:59',NULL),
(60,1,'Je veut que tu retire le statut qui est tout en haut à droite ( à côté du bouton retour )','https://mdgeek.top/index.php?page=statut_rapide&id=632','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:27:59',NULL),
(62,1,'Problème de couleur mode nuit - dans le tableau je veut que les prix soit affichée en noir','https://mdgeek.top/index.php?page=commandes_pieces','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-21 18:28:57','2025-04-21 22:43:10'),
(65,1,'Problème de couleur mode nuit - je veut que la date soit affichée en noir en mode nuit','https://mdgeek.top/index.php?page=commandes_pieces','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-21 18:29:23','2025-04-21 22:43:09'),
(72,1,'Je veut que le tableau au format mobile fasse la largeur de l’écran, et je veut pouvoir slider de gauche à droite pour visualiser le tableau sans qu’il ne dépasse de l’écran','https://mdgeek.top/index.php?page=commandes_pieces','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-21 18:30:32','2025-04-21 22:16:39'),
(77,1,'Quand je veut éditer la commande j’ai - modale d’édition non trouvée - bouton editer dans la collone action','https://mdgeek.top/index.php?page=commandes_pieces','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-21 18:31:21','2025-04-21 22:42:23'),
(78,1,'Quand j’appuie dans le tableau sur une tache ça ne m’affiche plus le modal avec les details','https://mdgeek.top/index.php','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:32:07',NULL),
(80,1,'Je veut que tu inverse la position de la colline statut avec la collone fournisseur','https://mdgeek.top/index.php?page=commandes_pieces','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-21 18:33:11','2025-04-21 22:05:26'),
(83,1,'Le bouton exporter ne fonctionne pas','https://mdgeek.top/index.php?page=commandes_pieces','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-21 18:33:31','2025-04-21 22:42:19'),
(86,1,'Problème de couleur mode nuit modal ajustement de stock','https://mdgeek.top/index.php?page=inventaire','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:34:08',NULL),
(89,1,'Ajouter une rubrique pour des produit temporaire\r\nCes produit seront destinée aux articles que nous avons commander pour les client mais que ils ne sont pas venu chercher. \r\nJe veut que quand le produit est scanner pour être enregistrer dans notre stock tu demande si c’est un produit succeptible d’être retournée.\r\nces produit seront enregistrée en produit temporaire.\r\nAu bout de 12 jours,il faudra que je retourne le produit, je veut que le produit apparaisse différemment dans le tableau ( pour qu’on le remarque couleur - badge À retourner)\r\n\r\nJe veut que tu adapte la table dsl pour cette fonction et que tu me fasse une page pour gérer les retour de produit\r\nCette page me permettra de voir quel produit je doit retourné et d’ajouter aussi de nouveau produit a retourné.\r\nJe veut pouvoir indiquée quand les produit ont été expédiée ( ajouter le numéro de suivi ) ( vérifier le statut de la livraison ) ( une fois le colis livrée je veut que le le retour passe en retour à vérifier, c’est à dire que à ce moment je vais devoir comparer le montant rembourser par mon fournisseur et le montant des produit que j’ai rembourser pour vérifier si tout c’est bien passer )\r\n\r\nJe veut que dans la pêche de retour je puisse sélectionner les produit a retourner, pour qu’ils soit enregistrée dans un colis\r\n\r\nJe veut une interface pour gérer les colis ( voir le contenu / suivi / etc )','https://mdgeek.top/index.php?page=inventaire','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:42:00',NULL),
(90,1,'Dans la page réparation quand je suis sur le modal de détail d’une réparation et que je clique sur le bouton pour modifier le statut, le modal s’affiche mais derrière le modal de détails. Je suis obliger de fermer le modal de détails de réparation pour voir le modal de modification de statut','https://mdgeek.top/index.php?page=taches','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-21 18:44:06',NULL),
(91,NULL,'Trouver un moyen pour assigner une réparation à un employée pour que ça apparaisse dans ses tâches à faires.\r\nIl faudra qu’il demmare la réparation depuis les tâches, puis une fois la réparation terminée il faudra vérifier si la réparation est assignée en tâches à l’employée, si c’est le cas il va falloir marquée la tache comme terminée.','https://mdgeek.top/index.php?page=taches','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-22 01:06:15',NULL),
(92,NULL,'Trouver un moyen pour assigner une réparation à un employée pour que ça apparaisse dans ses tâches à faires.\r\nIl faudra qu’il demmare la réparation depuis les tâches, puis une fois la réparation terminée il faudra vérifier si la réparation est assignée en tâches à l’employée, si c’est le cas il va falloir marquée la tache comme terminée.','https://mdgeek.top/index.php?page=taches','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-22 01:06:15',NULL),
(93,1,'asd','https://mdgeek.top/index.php','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','basse','resolu','2025-04-22 01:06:52','2025-04-30 22:36:55'),
(94,1,'Quand je soumet un but ça le soumet 2 fois.\r\nQuand je soumet une commande ça la soumet 2 fois\r\nAu format mobile','https://mdgeek.top/index.php','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-22 08:59:12','2025-04-30 22:36:53'),
(95,NULL,'Dans la page commande, je veut voir le technicien qui a bosser sur la reparation','https://mdgeek.top/index.php','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36','basse','nouveau','2025-04-22 09:46:20',NULL),
(96,NULL,'Quand je scanne le billet, et que j’appuie sur terminer la réparation.\r\nSi je choisis le statut restitué, ça ne change pas le statut de la réparation','https://mdgeek.top/index.php?page=statut_rapide&id=637','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1','basse','nouveau','2025-04-22 10:18:01',NULL),
(97,NULL,'Dans la page statut rapide\r\nQuand j’imprime l’étiquette\r\n\r\nJe veut que tu m’affiche le texte en plus gros de 2px','https://mdgeek.top/index.php','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-22 17:16:38','2025-04-30 22:36:40'),
(98,NULL,'Dans la page statut rapide\r\nQuand j’imprime l’étiquette\r\n\r\nJe veut que tu m’affiche le texte en plus gros de 2px','https://mdgeek.top/index.php','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-22 17:16:38','2025-04-30 22:36:41'),
(99,1,'Désactiver le slide verre en haut pour actualiser la page','https://mdgeek.top/index.php?pwa=1&timestamp=1745512223331','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-24 16:30:47','2025-04-30 22:36:36'),
(100,1,'Désactiver le slide verre en haut pour actualiser la page','https://mdgeek.top/index.php?pwa=1&timestamp=1745512223331','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-24 16:30:47','2025-04-30 22:36:36'),
(101,1,'Je voudrais que le tableau réparation recent Africa que les nouvelles reparation','https://mdgeek.top/index.php','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-26 18:53:48','2025-04-30 22:36:34'),
(102,1,'Je voudrais que le tableau réparation recent Africa que les nouvelles reparation','https://mdgeek.top/index.php','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-26 18:53:48','2025-04-30 22:36:34'),
(103,1,'Je veux désactiver le slide vers le haut pour rafraîchir quand on est sur PWA','https://mdgeek.top/index.php','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-26 18:54:27','2025-04-30 22:36:27'),
(104,1,'Je veux désactiver le slide vers le haut pour rafraîchir quand on est sur PWA','https://mdgeek.top/index.php','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-26 18:54:27','2025-04-30 22:36:28'),
(105,1,'Je veut retirer le bouton urgence','https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-26 18:57:09','2025-04-30 22:36:21'),
(106,1,'Je veut retirer le bouton urgence','https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-26 18:57:09','2025-04-30 22:36:22'),
(107,1,'Quand je clique sur le statut d’une commande, il ne se passe rien.\r\nNormalement ça doit m’ouvrir un modèle','https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-26 18:57:40','2025-04-30 22:36:18'),
(108,1,'Quand je clique sur le statut d’une commande, il ne se passe rien.\r\nNormalement ça doit m’ouvrir un modèle','https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-26 18:57:40','2025-04-30 22:36:20'),
(109,1,'Quand je soumets un bug sur iOS, ça me soumettre deux fois','https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-26 18:57:56','2025-04-30 22:36:11'),
(110,1,'Quand je soumets un bug sur iOS, ça me soumettre deux fois','https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-26 18:57:56','2025-04-30 22:36:12'),
(111,1,'Quand je soumets une commande ça la soumettre deux fois','https://mdgeek.top/index.php?pwa=1&timestamp=1745693801502','Mozilla/5.0 (iPhone; CPU iPhone OS 18_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Mobile/15E148 Safari/604.1','basse','resolu','2025-04-26 18:58:44','2025-04-30 22:36:10'),
(112,NULL,'test','https://mdgeek.top/index.php','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','basse','resolu','2025-04-30 22:35:58','2025-04-30 22:36:09');
/*!40000 ALTER TABLE `bug_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `inscrit_parrainage` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Client inscrit au programme de parrainage ou non',
  `code_parrainage` varchar(10) DEFAULT NULL COMMENT 'Code unique pour le parrainage (peut être null si pas inscrit)',
  `date_inscription_parrainage` timestamp NULL DEFAULT NULL COMMENT 'Date d''inscription au programme de parrainage',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=527 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES
(505,'cannesphones_sample','tet','33782962906','','2025-05-03 22:22:31',0,NULL,NULL),
(506,'cannesphones_sample','asd','0782962906','','2025-05-04 19:42:58',0,NULL,NULL),
(507,'cannesphones_sample','test1','77829546545','','2025-05-04 19:55:07',0,NULL,NULL),
(508,'cannesphones_sample','asd','782962854','','2025-05-04 19:56:59',0,NULL,NULL),
(509,'cannesphones_sample','saa','78296295455','','2025-05-04 20:00:24',0,NULL,NULL),
(510,'cannesphones_sample','test','789456','','2025-05-04 20:08:25',0,NULL,NULL),
(511,'cannesphones_sample','Shiba','77854545','','2025-05-04 21:19:32',0,NULL,NULL),
(512,'cannesphones_sample','sa','337829626906','','2025-05-04 21:26:56',0,NULL,NULL),
(513,'cannesphones_sample','sa','33782962906','','2025-05-05 00:09:31',0,NULL,NULL),
(514,'cannesphones_sample','bel','123455','','2025-05-05 00:10:00',0,NULL,NULL),
(515,'cannesphones_sample','sas','123','','2025-05-05 00:13:24',0,NULL,NULL),
(516,'cannesphones_sample','Debug_6967','0769700029','','2025-05-05 00:15:26',0,NULL,NULL),
(517,'cannesphones_sample','sasa','33782962906','','2025-05-05 00:16:54',0,NULL,NULL),
(518,'cannesphones_sample','test','78295','','2025-05-05 00:22:41',0,NULL,NULL),
(519,'cannesphones_sample','sasaas','33782962906','','2025-05-05 00:32:12',0,NULL,NULL),
(520,'cannesphones_sample','sad','33782962906','','2025-05-05 00:37:52',0,NULL,NULL),
(521,'cannesphones_sample','adsdas','789','','2025-05-05 00:44:15',0,NULL,NULL),
(522,'cannesphones_sample','55','55','','2025-05-05 00:47:28',0,NULL,NULL),
(523,'cannesphones_sample','sfdjdf','0782962906','','2025-05-07 17:48:56',0,NULL,NULL),
(524,'cannesphones_sample','saber','33782962906','','2025-05-07 20:21:33',0,NULL,NULL),
(525,'cannesphones_sample','farida','33782962906','','2025-06-07 22:13:27',0,NULL,NULL),
(526,'cannesphones_sample','albert','33234567891','','2025-06-07 22:54:47',0,NULL,NULL);
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `colis_retour`
--

DROP TABLE IF EXISTS `colis_retour`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `colis_retour` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_suivi` varchar(100) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_expedition` datetime DEFAULT NULL,
  `statut` enum('en_preparation','en_expedition','livre') DEFAULT 'en_preparation',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `colis_retour`
--

LOCK TABLES `colis_retour` WRITE;
/*!40000 ALTER TABLE `colis_retour` DISABLE KEYS */;
/*!40000 ALTER TABLE `colis_retour` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commandes_fournisseurs`
--

DROP TABLE IF EXISTS `commandes_fournisseurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `commandes_fournisseurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fournisseur_id` int(11) NOT NULL,
  `date_commande` timestamp NULL DEFAULT current_timestamp(),
  `statut` enum('en_attente','validee','recue','annulee') DEFAULT 'en_attente',
  `montant_total` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fournisseur_id` (`fournisseur_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `commandes_fournisseurs_ibfk_1` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  CONSTRAINT `commandes_fournisseurs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commandes_fournisseurs`
--

LOCK TABLES `commandes_fournisseurs` WRITE;
/*!40000 ALTER TABLE `commandes_fournisseurs` DISABLE KEYS */;
/*!40000 ALTER TABLE `commandes_fournisseurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commandes_pieces`
--

DROP TABLE IF EXISTS `commandes_pieces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `commandes_pieces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `reparation_id` (`reparation_id`),
  KEY `fournisseur_id` (`fournisseur_id`),
  KEY `fk_commandes_pieces_client` (`client_id`),
  CONSTRAINT `commandes_pieces_ibfk_1` FOREIGN KEY (`reparation_id`) REFERENCES `reparations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `commandes_pieces_ibfk_2` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  CONSTRAINT `fk_commandes_pieces_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=194 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commandes_pieces`
--

LOCK TABLES `commandes_pieces` WRITE;
/*!40000 ALTER TABLE `commandes_pieces` DISABLE KEYS */;
INSERT INTO `commandes_pieces` VALUES
(182,'cannes_phones',NULL,524,9,'test21','2',NULL,1,22.00,NULL,NULL,'normal','commande',NULL,NULL,NULL,'2025-05-07 23:58:10','2025-06-15 13:43:23'),
(186,'CMD-20250611-6849f70a2513b',NULL,506,9,'21','21',NULL,1,21.00,NULL,NULL,'normal','commande',NULL,NULL,NULL,'2025-06-11 21:37:14','2025-06-15 13:43:28'),
(187,'CMD-20250611-6849f78e561a6',NULL,506,4,'sa','sa',NULL,1,0.00,NULL,NULL,'normal','commande',NULL,NULL,NULL,'2025-06-11 21:39:26','2025-06-15 13:43:30'),
(188,'CMD-20250611-684a096cb4651',NULL,506,9,'ds','3',NULL,1,3.00,NULL,NULL,'normal','commande',NULL,NULL,NULL,'2025-06-11 22:55:40','2025-06-15 13:43:31'),
(189,'CMD-20250615-1749946277-3ada',NULL,524,4,'21','21',NULL,1,21.00,NULL,NULL,'normal','commande',NULL,NULL,NULL,'2025-06-15 00:11:17','2025-06-15 13:43:31'),
(190,'CMD-20250615-1749946305-ecd4',NULL,524,4,'21','12',NULL,1,21.00,NULL,NULL,'normal','recue',NULL,NULL,NULL,'2025-06-15 00:11:45','2025-06-15 14:12:16'),
(191,'CMD-20250615-1749946305-ee65',NULL,524,4,'21','21',NULL,1,21.00,NULL,NULL,'normal','en_attente',NULL,NULL,NULL,'2025-06-15 00:11:45','2025-06-15 14:19:44'),
(193,'CMD-20250615-1750018731-28e8',761,524,2,'sa','',NULL,1,21.00,NULL,NULL,'normal','en_attente',NULL,NULL,NULL,'2025-06-15 20:18:51','2025-06-15 20:18:51');
/*!40000 ALTER TABLE `commandes_pieces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commentaires_tache`
--

DROP TABLE IF EXISTS `commentaires_tache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `commentaires_tache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tache_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `commentaire` text NOT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tache_id` (`tache_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `commentaires_tache_ibfk_1` FOREIGN KEY (`tache_id`) REFERENCES `taches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `commentaires_tache_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commentaires_tache`
--

LOCK TABLES `commentaires_tache` WRITE;
/*!40000 ALTER TABLE `commentaires_tache` DISABLE KEYS */;
/*!40000 ALTER TABLE `commentaires_tache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `confirmations_lecture`
--

DROP TABLE IF EXISTS `confirmations_lecture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `confirmations_lecture` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `date_confirmation` datetime DEFAULT NULL COMMENT 'NULL = non confirmé, datetime = confirmé à cette date',
  `rappel_envoye` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indique si un rappel a été envoyé',
  `date_rappel` datetime DEFAULT NULL COMMENT 'Date et heure d''envoi du rappel',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_confirmation` (`message_id`,`employe_id`),
  KEY `employe_id` (`employe_id`),
  CONSTRAINT `confirmations_lecture_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `confirmations_lecture_ibfk_2` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `confirmations_lecture`
--

LOCK TABLES `confirmations_lecture` WRITE;
/*!40000 ALTER TABLE `confirmations_lecture` DISABLE KEYS */;
/*!40000 ALTER TABLE `confirmations_lecture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conges_demandes`
--

DROP TABLE IF EXISTS `conges_demandes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conges_demandes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nb_jours` decimal(5,2) NOT NULL,
  `statut` enum('en_attente','approuve','refuse') DEFAULT 'en_attente',
  `type` enum('normal','impose') DEFAULT 'normal',
  `commentaire` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `conges_demandes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `conges_demandes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conges_demandes`
--

LOCK TABLES `conges_demandes` WRITE;
/*!40000 ALTER TABLE `conges_demandes` DISABLE KEYS */;
/*!40000 ALTER TABLE `conges_demandes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conges_jours_disponibles`
--

DROP TABLE IF EXISTS `conges_jours_disponibles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conges_jours_disponibles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_date` (`date`),
  KEY `created_by` (`created_by`),
  KEY `idx_date` (`date`),
  CONSTRAINT `conges_jours_disponibles_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conges_jours_disponibles`
--

LOCK TABLES `conges_jours_disponibles` WRITE;
/*!40000 ALTER TABLE `conges_jours_disponibles` DISABLE KEYS */;
/*!40000 ALTER TABLE `conges_jours_disponibles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conges_solde`
--

DROP TABLE IF EXISTS `conges_solde`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conges_solde` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `solde_actuel` decimal(5,2) NOT NULL DEFAULT 0.00,
  `date_derniere_maj` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `conges_solde_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conges_solde`
--

LOCK TABLES `conges_solde` WRITE;
/*!40000 ALTER TABLE `conges_solde` DISABLE KEYS */;
/*!40000 ALTER TABLE `conges_solde` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversation_participants`
--

DROP TABLE IF EXISTS `conversation_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversation_participants` (
  `conversation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin','membre','lecteur') NOT NULL DEFAULT 'membre',
  `date_ajout` datetime DEFAULT current_timestamp(),
  `date_derniere_lecture` datetime DEFAULT NULL,
  `est_favoris` tinyint(1) DEFAULT 0,
  `est_archive` tinyint(1) DEFAULT 0,
  `notification_mute` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`conversation_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `conversation_participants_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversation_participants`
--

LOCK TABLES `conversation_participants` WRITE;
/*!40000 ALTER TABLE `conversation_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversation_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `type` enum('direct','groupe','annonce') NOT NULL DEFAULT 'direct',
  `created_by` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `derniere_activite` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employes`
--

DROP TABLE IF EXISTS `employes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employes`
--

LOCK TABLES `employes` WRITE;
/*!40000 ALTER TABLE `employes` DISABLE KEYS */;
INSERT INTO `employes` VALUES
(3,'SYSTÈME','Client','system@mdgeek.fr','0000000000',NULL,'inactif','2025-06-25 20:29:48','2025-06-25 20:29:48');
/*!40000 ALTER TABLE `employes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fournisseurs`
--

DROP TABLE IF EXISTS `fournisseurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fournisseurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `contact_nom` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `url` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fournisseurs`
--

LOCK TABLES `fournisseurs` WRITE;
/*!40000 ALTER TABLE `fournisseurs` DISABLE KEYS */;
INSERT INTO `fournisseurs` VALUES
(2,'cannesphones_sample',NULL,NULL,'https://mdgeek.top/i',NULL,'2025-03-28 18:58:21'),
(4,'cannesphones_sample',NULL,NULL,'https://www.wattiz.f',NULL,'2025-03-29 00:40:20'),
(9,'cannesphones_sample',NULL,NULL,'http://Aliexpress.fr',NULL,'2025-03-29 00:41:01'),
(10,'cannesphones_sample',NULL,NULL,'http://amazon.fr',NULL,'2025-03-29 00:41:15'),
(11,'cannesphones_sample',NULL,NULL,'http://mobilax.fr',NULL,'2025-03-29 00:41:28'),
(12,'cannesphones_sample',NULL,NULL,'http://volt-corp.com',NULL,'2025-03-29 00:41:45'),
(14,'cannesphones_sample',NULL,NULL,'https://autre.com',NULL,'2025-04-02 16:27:46'),
(15,'cannesphones_sample',NULL,NULL,'http://E-Wheel.fr',NULL,'2025-04-02 16:29:49');
/*!40000 ALTER TABLE `fournisseurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gardiennage`
--

DROP TABLE IF EXISTS `gardiennage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gardiennage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reparation_id` (`reparation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gardiennage`
--

LOCK TABLES `gardiennage` WRITE;
/*!40000 ALTER TABLE `gardiennage` DISABLE KEYS */;
INSERT INTO `gardiennage` VALUES
(15,593,'2025-04-23','2025-04-23',5.00,3,15.00,0,'2025-04-26',NULL,'','2025-04-23 12:33:46','2025-04-26 18:56:10'),
(16,593,'2025-04-23','2025-04-23',5.00,3,15.00,0,'2025-04-26',NULL,'','2025-04-23 12:33:46','2025-04-26 18:55:54'),
(17,618,'2025-04-23','2025-04-23',5.00,3,15.00,0,'2025-04-26',NULL,'','2025-04-23 12:35:27','2025-04-26 18:56:03'),
(18,594,'2025-04-26','2025-04-26',5.00,0,0.00,0,'2025-04-26',NULL,'','2025-04-26 10:15:04','2025-04-26 18:55:39'),
(19,594,'2025-04-26','2025-04-26',5.00,0,0.00,0,'2025-04-26',NULL,'','2025-04-26 10:15:05','2025-04-26 18:55:45'),
(20,675,'2025-04-28','2025-04-28',5.00,0,0.00,1,NULL,NULL,'','2025-04-28 22:40:13','2025-04-28 22:40:13');
/*!40000 ALTER TABLE `gardiennage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gardiennage_notifications`
--

DROP TABLE IF EXISTS `gardiennage_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gardiennage_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gardiennage_id` int(11) NOT NULL,
  `date_notification` timestamp NULL DEFAULT current_timestamp(),
  `type_notification` enum('sms','email','appel') NOT NULL,
  `statut` enum('envoyé','échec','annulé') NOT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `gardiennage_id` (`gardiennage_id`),
  CONSTRAINT `gardiennage_notifications_ibfk_1` FOREIGN KEY (`gardiennage_id`) REFERENCES `gardiennage` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gardiennage_notifications`
--

LOCK TABLES `gardiennage_notifications` WRITE;
/*!40000 ALTER TABLE `gardiennage_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `gardiennage_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `global_theme_settings`
--

DROP TABLE IF EXISTS `global_theme_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `global_theme_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('boolean','string','number','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `global_theme_settings`
--

LOCK TABLES `global_theme_settings` WRITE;
/*!40000 ALTER TABLE `global_theme_settings` DISABLE KEYS */;
INSERT INTO `global_theme_settings` VALUES
(1,'enable_theme_switching','true','boolean','Permettre aux utilisateurs de changer de thème','2025-06-16 23:34:45'),
(2,'default_theme','modern-theme','string','Thème par défaut pour les nouveaux utilisateurs','2025-06-16 23:34:45'),
(3,'enable_auto_dark_mode','true','boolean','Activer le passage automatique en mode sombre','2025-06-16 23:34:45'),
(4,'theme_cache_duration','3600','number','Durée de cache des thèmes en secondes','2025-06-16 23:34:45');
/*!40000 ALTER TABLE `global_theme_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `help_requests`
--

DROP TABLE IF EXISTS `help_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `help_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `status` enum('en_attente','resolu','en_cours') DEFAULT 'en_attente',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `help_requests`
--

LOCK TABLES `help_requests` WRITE;
/*!40000 ALTER TABLE `help_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `help_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historique_soldes`
--

DROP TABLE IF EXISTS `historique_soldes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `historique_soldes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partenaire_id` int(11) NOT NULL,
  `ancien_solde` decimal(10,2) DEFAULT NULL,
  `nouveau_solde` decimal(10,2) DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `date_modification` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partenaire_id` (`partenaire_id`),
  KEY `transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historique_soldes`
--

LOCK TABLES `historique_soldes` WRITE;
/*!40000 ALTER TABLE `historique_soldes` DISABLE KEYS */;
/*!40000 ALTER TABLE `historique_soldes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_actions`
--

DROP TABLE IF EXISTS `journal_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `target_id` int(11) NOT NULL,
  `details` text DEFAULT NULL,
  `date_action` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action_type` (`action_type`),
  KEY `target_id` (`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_actions`
--

LOCK TABLES `journal_actions` WRITE;
/*!40000 ALTER TABLE `journal_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `journal_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kb_article_ratings`
--

DROP TABLE IF EXISTS `kb_article_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kb_article_ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL,
  `rated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `article_user` (`article_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kb_article_ratings`
--

LOCK TABLES `kb_article_ratings` WRITE;
/*!40000 ALTER TABLE `kb_article_ratings` DISABLE KEYS */;
INSERT INTO `kb_article_ratings` VALUES
(1,1,1,0,'2025-04-07 21:03:09');
/*!40000 ALTER TABLE `kb_article_ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kb_article_tags`
--

DROP TABLE IF EXISTS `kb_article_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kb_article_tags` (
  `article_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`article_id`,`tag_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kb_article_tags`
--

LOCK TABLES `kb_article_tags` WRITE;
/*!40000 ALTER TABLE `kb_article_tags` DISABLE KEYS */;
INSERT INTO `kb_article_tags` VALUES
(1,10);
/*!40000 ALTER TABLE `kb_article_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kb_articles`
--

DROP TABLE IF EXISTS `kb_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kb_articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kb_articles`
--

LOCK TABLES `kb_articles` WRITE;
/*!40000 ALTER TABLE `kb_articles` DISABLE KEYS */;
INSERT INTO `kb_articles` VALUES
(1,'cannesphones_sample','Liste rapide de tous les codes erreurs Xiaomi M365 (Liste détaillée disponible juste en dessous)\r\nSi tu souhaites directement consulter la correspondance de ton code erreur, tu peux regarder immédiatement dans la liste ci-dessous, ils sont tous répertoriés.\r\n\r\nCode erreur 10 : Défaut entre carte Bluetooth et carte mère.\r\nCode erreur 11, 12, 13, 28, 29 : Défaut MosFET carte mère\r\nCode erreur 14 : Défaut du levier de frein ou accélérateur\r\nCode erreur 15 : Défaut poignée accélérateur ou levier de frein\r\nCode erreur 18 : Défaut capteur hall moteur\r\nCode erreur 21 : Défaut communication batterie\r\nCode erreur 22, 23 : Défaut numéro de série BMS\r\nCode erreur 24 : Défaut tension batterie déséquilibré\r\nCode erreur 27, 39 : Défaut numéro série carte mère\r\nCode erreur 35, 36 : Défaut capteur ou surchauffe batterie\r\nCode erreur 40 : Défaut surchauffe carte mère\r\nCode erreur 41 : Défaut version BLE\r\nCode erreur 42 : Défaut carte mère numéro de série',4,'2025-04-07 21:03:03','2025-04-07 21:04:50',18);
/*!40000 ALTER TABLE `kb_articles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kb_categories`
--

DROP TABLE IF EXISTS `kb_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kb_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-folder',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kb_categories`
--

LOCK TABLES `kb_categories` WRITE;
/*!40000 ALTER TABLE `kb_categories` DISABLE KEYS */;
INSERT INTO `kb_categories` VALUES
(1,'Réparations','fas fa-tools','2025-03-30 03:07:40'),
(2,'Inventaire','fas fa-boxes','2025-03-30 03:07:40'),
(3,'Facturation','fas fa-file-invoice-dollar','2025-03-30 03:07:40'),
(4,'Clients','fas fa-users','2025-03-30 03:07:40'),
(5,'Fournisseurs','fas fa-truck','2025-03-30 03:07:40'),
(6,'Système','fas fa-cogs','2025-03-30 03:07:40'),
(7,'Procédures','fas fa-clipboard-list','2025-04-07 17:34:00'),
(8,'Tutoriels','fas fa-book','2025-04-07 17:34:00'),
(9,'FAQ','fas fa-question-circle','2025-04-07 17:34:00'),
(10,'Documentation Technique','fas fa-file-alt','2025-04-07 17:34:00');
/*!40000 ALTER TABLE `kb_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kb_tags`
--

DROP TABLE IF EXISTS `kb_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kb_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kb_tags`
--

LOCK TABLES `kb_tags` WRITE;
/*!40000 ALTER TABLE `kb_tags` DISABLE KEYS */;
INSERT INTO `kb_tags` VALUES
(1,'cannesphones_sample','2025-03-30 03:07:40'),
(2,'Tutoriel','2025-03-30 03:07:40'),
(3,'Débutant','2025-03-30 03:07:40'),
(4,'Avancé','2025-03-30 03:07:40'),
(5,'Configuration','2025-03-30 03:07:40'),
(6,'Dépannage','2025-03-30 03:07:40'),
(7,'Astuces','2025-03-30 03:07:40'),
(8,'FAQ','2025-03-30 03:07:40'),
(9,'iPhone','2025-04-07 17:34:00'),
(10,'Android','2025-04-07 17:34:00'),
(11,'Samsung','2025-04-07 17:34:00'),
(12,'Réparation','2025-04-07 17:34:00'),
(13,'Formation','2025-04-07 17:34:00'),
(14,'Procédure','2025-04-07 17:34:00'),
(15,'Logiciel','2025-04-07 17:34:00'),
(16,'Matériel','2025-04-07 17:34:00');
/*!40000 ALTER TABLE `kb_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lecture_annonces`
--

DROP TABLE IF EXISTS `lecture_annonces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lecture_annonces` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_lecture` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lecture_annonces`
--

LOCK TABLES `lecture_annonces` WRITE;
/*!40000 ALTER TABLE `lecture_annonces` DISABLE KEYS */;
/*!40000 ALTER TABLE `lecture_annonces` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lignes_commande_fournisseur`
--

DROP TABLE IF EXISTS `lignes_commande_fournisseur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lignes_commande_fournisseur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `commande_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `commande_id` (`commande_id`),
  KEY `produit_id` (`produit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lignes_commande_fournisseur`
--

LOCK TABLES `lignes_commande_fournisseur` WRITE;
/*!40000 ALTER TABLE `lignes_commande_fournisseur` DISABLE KEYS */;
/*!40000 ALTER TABLE `lignes_commande_fournisseur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marges_estimees`
--

DROP TABLE IF EXISTS `marges_estimees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marges_estimees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categorie` enum('telephone','pc','tablette') NOT NULL,
  `description` varchar(255) NOT NULL,
  `prix_estime` decimal(10,2) NOT NULL,
  `marge_recommandee` decimal(10,2) NOT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marges_estimees`
--

LOCK TABLES `marges_estimees` WRITE;
/*!40000 ALTER TABLE `marges_estimees` DISABLE KEYS */;
/*!40000 ALTER TABLE `marges_estimees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marges_reference`
--

DROP TABLE IF EXISTS `marges_reference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marges_reference` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_reparation` varchar(255) NOT NULL,
  `categorie` enum('smartphone','tablet','computer') NOT NULL,
  `prix_achat` decimal(10,2) NOT NULL,
  `marge_pourcentage` int(11) NOT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marges_reference`
--

LOCK TABLES `marges_reference` WRITE;
/*!40000 ALTER TABLE `marges_reference` DISABLE KEYS */;
INSERT INTO `marges_reference` VALUES
(1,'database_general_sample_info','smartphone',50.00,100,'2025-04-01 13:28:19'),
(2,'Remplacement écran Samsung','smartphone',50.00,100,'2025-04-01 13:28:19'),
(3,'Remplacement batterie iPhone','smartphone',15.00,200,'2025-04-01 13:28:19'),
(4,'Remplacement batterie Samsung','smartphone',18.00,200,'2025-04-01 13:28:19'),
(5,'Remplacement connecteur de charge iPhone','smartphone',10.00,300,'2025-04-01 13:28:19'),
(6,'Remplacement connecteur de charge Samsung','smartphone',12.00,250,'2025-04-01 13:28:19'),
(7,'Remplacement vitre tactile iPhone','smartphone',35.00,100,'2025-04-01 13:28:19'),
(8,'Remplacement caméra arrière iPhone','smartphone',25.00,150,'2025-04-01 13:28:19'),
(9,'Remplacement haut-parleur iPhone','smartphone',8.00,250,'2025-04-01 13:28:27'),
(10,'Réparation carte mère iPhone','smartphone',60.00,150,'2025-04-01 13:28:27'),
(11,'Remplacement écran iPad','tablet',65.00,100,'2025-04-01 13:28:27'),
(12,'Remplacement batterie iPad','tablet',25.00,150,'2025-04-01 13:28:27'),
(13,'Remplacement connecteur de charge iPad','tablet',15.00,200,'2025-04-01 13:28:27'),
(14,'Remplacement vitre tactile iPad','tablet',40.00,100,'2025-04-01 13:28:27'),
(15,'Remplacement caméra iPad','tablet',30.00,120,'2025-04-01 13:28:27'),
(16,'Réparation carte mère iPad','tablet',70.00,130,'2025-04-01 13:28:27'),
(17,'Remplacement écran PC portable','computer',80.00,80,'2025-04-01 13:28:34'),
(18,'Remplacement clavier PC portable','computer',40.00,100,'2025-04-01 13:28:34'),
(19,'Remplacement disque dur par SSD','computer',45.00,90,'2025-04-01 13:28:34'),
(20,'Remplacement batterie PC portable','computer',30.00,120,'2025-04-01 13:28:34'),
(21,'Augmentation RAM PC portable','computer',35.00,80,'2025-04-01 13:28:34'),
(22,'Nettoyage ventilation PC portable','computer',15.00,250,'2025-04-01 13:28:34'),
(23,'Réparation carte mère PC portable','computer',90.00,120,'2025-04-01 13:28:34'),
(24,'Réinstallation système d\'exploitation','computer',0.00,150,'2025-04-01 13:28:34');
/*!40000 ALTER TABLE `marges_reference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_attachments`
--

DROP TABLE IF EXISTS `message_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `thumbnail_path` varchar(255) DEFAULT NULL,
  `est_image` tinyint(1) DEFAULT 0,
  `date_upload` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_attachments`
--

LOCK TABLES `message_attachments` WRITE;
/*!40000 ALTER TABLE `message_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_reactions`
--

DROP TABLE IF EXISTS `message_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_reactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reaction` varchar(20) NOT NULL,
  `date_reaction` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_reaction` (`message_id`,`user_id`,`reaction`),
  KEY `idx_message_reactions_message_id` (`message_id`),
  KEY `idx_message_reactions_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_reactions`
--

LOCK TABLES `message_reactions` WRITE;
/*!40000 ALTER TABLE `message_reactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_reactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_reads`
--

DROP TABLE IF EXISTS `message_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_reads` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_lecture` datetime DEFAULT current_timestamp(),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  PRIMARY KEY (`message_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_reads`
--

LOCK TABLES `message_reads` WRITE;
/*!40000 ALTER TABLE `message_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_replies`
--

DROP TABLE IF EXISTS `message_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `reply_to_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_reply` (`message_id`,`reply_to_id`),
  KEY `reply_to_id` (`reply_to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_replies`
--

LOCK TABLES `message_replies` WRITE;
/*!40000 ALTER TABLE `message_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `contenu` text DEFAULT NULL,
  `type` enum('text','file','image','system','info') NOT NULL DEFAULT 'text',
  `date_envoi` datetime DEFAULT current_timestamp(),
  `est_supprime` tinyint(1) DEFAULT 0,
  `est_modifie` tinyint(1) DEFAULT 0,
  `date_modification` datetime DEFAULT NULL,
  `est_important` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `sender_id` (`sender_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mouvements_stock`
--

DROP TABLE IF EXISTS `mouvements_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mouvements_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produit_id` int(11) NOT NULL,
  `fournisseur_id` int(11) DEFAULT NULL,
  `type_mouvement` enum('entree','sortie') NOT NULL,
  `quantite` int(11) NOT NULL,
  `date_mouvement` timestamp NULL DEFAULT current_timestamp(),
  `motif` varchar(255) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `produit_id` (`produit_id`),
  KEY `user_id` (`user_id`),
  KEY `mouvements_stock_fournisseur_fk` (`fournisseur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mouvements_stock`
--

LOCK TABLES `mouvements_stock` WRITE;
/*!40000 ALTER TABLE `mouvements_stock` DISABLE KEYS */;
INSERT INTO `mouvements_stock` VALUES
(1,6,NULL,'sortie',1,'2025-04-20 23:48:24','2sad',1),
(2,6,NULL,'entree',3,'2025-04-20 23:49:16','ld',1),
(3,6,NULL,'entree',1,'2025-04-21 00:29:06','Retour de prêt',1),
(4,6,NULL,'sortie',1,'2025-04-21 00:43:37','Prêté à un partenaire',1),
(5,6,NULL,'sortie',1,'2025-04-21 00:44:04','Utilisé pour une réparation',1),
(6,6,NULL,'entree',1,'2025-04-21 00:45:31','Retour de prêt',1),
(10,6,NULL,'entree',1,'2025-04-21 01:18:29','Retour de prêt',1);
/*!40000 ALTER TABLE `mouvements_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_preferences`
--

DROP TABLE IF EXISTS `notification_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type_notification` varchar(50) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `email_notification` tinyint(1) NOT NULL DEFAULT 0,
  `push_notification` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_type_unique` (`user_id`,`type_notification`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_preferences`
--

LOCK TABLES `notification_preferences` WRITE;
/*!40000 ALTER TABLE `notification_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_types`
--

DROP TABLE IF EXISTS `notification_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_code` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `color` varchar(20) NOT NULL,
  `importance` enum('basse','normale','haute','critique') NOT NULL DEFAULT 'normale',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_code` (`type_code`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_types`
--

LOCK TABLES `notification_types` WRITE;
/*!40000 ALTER TABLE `notification_types` DISABLE KEYS */;
INSERT INTO `notification_types` VALUES
(1,'reparation_start','Démarrage de réparation','fas fa-play-circle','#4361ee','normale'),
(2,'reparation_stop','Arrêt de réparation','fas fa-stop-circle','#e11d48','normale'),
(3,'reparation_update','Mise à jour dossier','fas fa-edit','#6366f1','normale'),
(4,'reparation_finish','Réparation terminée','fas fa-check-circle','#10b981','haute'),
(5,'new_device','Prise en charge appareil','fas fa-mobile-alt','#0ea5e9','normale'),
(6,'new_order','Nouvelle commande','fas fa-shopping-cart','#f59e0b','haute'),
(7,'stock_low','Stock bas','fas fa-exclamation-triangle','#dc2626','critique'),
(8,'message_received','Nouveau message','fas fa-envelope','#8b5cf6','normale'),
(9,'task_assigned','Tâche assignée','fas fa-tasks','#ec4899','normale'),
(10,'task_completed','Tâche terminée','fas fa-clipboard-check','#0d9488','normale'),
(11,'system_alert','Alerte système','fas fa-bell','#f43f5e','critique'),
(12,'appointment','Rendez-vous','fas fa-calendar-check','#0891b2','haute'),
(13,'sms_notification','Notification SMS','message','#28a745','normale');
/*!40000 ALTER TABLE `notification_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_user_status` (`user_id`,`status`),
  KEY `notification_type` (`notification_type`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parametres`
--

DROP TABLE IF EXISTS `parametres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parametres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cle` varchar(50) NOT NULL,
  `valeur` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cle` (`cle`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parametres`
--

LOCK TABLES `parametres` WRITE;
/*!40000 ALTER TABLE `parametres` DISABLE KEYS */;
INSERT INTO `parametres` VALUES
(1,'attribution_reparation_active','1','Activer/désactiver la fonctionnalité d\'attribution des réparations aux employés'),
(2,'points_reparation_terminee','50','Points attribués pour une réparation terminée'),
(3,'points_tache_terminee','20','Points attribués pour une tâche terminée'),
(4,'points_connexion_quotidienne','5','Points attribués pour la première connexion du jour'),
(5,'points_objectif_atteint','100','Bonus pour un objectif atteint'),
(6,'points_retard_penalite','-10','Pénalité pour un retard'),
(7,'seuil_inactivite_minutes','120','Seuil d\'inactivité en minutes avant alerte'),
(8,'seuil_productivite_minimum','60','Seuil minimum de productivité en pourcentage'),
(9,'capture_ecran_active','false','Activation de la capture d\'écran automatique'),
(10,'geolocalisation_active','true','Activation du suivi géolocalisation'),
(11,'notifications_intelligentes_active','true','Activation des notifications intelligentes'),
(12,'ia_anomalies_active','false','Activation de la détection d\'anomalies IA'),
(13,'gamification_active','true','Activation du système de gamification'),
(14,'tracking_temps_reel_active','true','Activation du tracking temps réel'),
(15,'zone_travail_latitude','43.5527','Latitude du centre de la zone de travail'),
(16,'zone_travail_longitude','7.0174','Longitude du centre de la zone de travail'),
(17,'zone_travail_rayon_metres','1000','Rayon autorisé autour du lieu de travail en mètres');
/*!40000 ALTER TABLE `parametres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parametres_gardiennage`
--

DROP TABLE IF EXISTS `parametres_gardiennage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parametres_gardiennage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tarif_premiere_semaine` decimal(10,2) NOT NULL DEFAULT 5.00 COMMENT 'Tarif journalier pour les 7 premiers jours',
  `tarif_intermediaire` decimal(10,2) NOT NULL DEFAULT 3.00 COMMENT 'Tarif journalier de 8 à 30 jours',
  `tarif_longue_duree` decimal(10,2) NOT NULL DEFAULT 1.00 COMMENT 'Tarif journalier au-delà de 30 jours',
  `date_modification` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parametres_gardiennage`
--

LOCK TABLES `parametres_gardiennage` WRITE;
/*!40000 ALTER TABLE `parametres_gardiennage` DISABLE KEYS */;
INSERT INTO `parametres_gardiennage` VALUES
(1,5.00,3.00,1.00,'2025-04-10 18:13:37');
/*!40000 ALTER TABLE `parametres_gardiennage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parrainage_config`
--

DROP TABLE IF EXISTS `parrainage_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parrainage_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_filleuls_requis` int(11) NOT NULL DEFAULT 1 COMMENT 'Nombre de filleuls requis pour activer les récompenses',
  `seuil_reduction_pourcentage` decimal(10,2) NOT NULL DEFAULT 100.00 COMMENT 'Seuil de dépense en euros pour déclencher la réduction maximale',
  `reduction_min_pourcentage` int(11) NOT NULL DEFAULT 10 COMMENT 'Pourcentage de réduction minimum (pour dépenses < seuil)',
  `reduction_max_pourcentage` int(11) NOT NULL DEFAULT 30 COMMENT 'Pourcentage de réduction maximum (pour dépenses >= seuil)',
  `actif` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Programme actif ou non',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parrainage_config`
--

LOCK TABLES `parrainage_config` WRITE;
/*!40000 ALTER TABLE `parrainage_config` DISABLE KEYS */;
INSERT INTO `parrainage_config` VALUES
(1,1,100.00,10,30,1,'2025-04-11 02:14:22','2025-04-11 02:14:22');
/*!40000 ALTER TABLE `parrainage_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parrainage_reductions`
--

DROP TABLE IF EXISTS `parrainage_reductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parrainage_reductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parrain_id` int(11) NOT NULL COMMENT 'ID du client parrain',
  `montant_depense_filleul` decimal(10,2) NOT NULL COMMENT 'Montant dépensé par le filleul qui a généré la réduction',
  `pourcentage_reduction` int(11) NOT NULL COMMENT 'Pourcentage de réduction accordé',
  `montant_reduction_max` decimal(10,2) NOT NULL COMMENT 'Montant maximum de la réduction',
  `utilise` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Si la réduction a été utilisée',
  `reparation_utilisee_id` int(11) DEFAULT NULL COMMENT 'ID de la réparation où la réduction a été utilisée',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_utilisation` timestamp NULL DEFAULT NULL COMMENT 'Date d''utilisation de la réduction',
  PRIMARY KEY (`id`),
  KEY `parrain_id` (`parrain_id`),
  KEY `reparation_utilisee_id` (`reparation_utilisee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parrainage_reductions`
--

LOCK TABLES `parrainage_reductions` WRITE;
/*!40000 ALTER TABLE `parrainage_reductions` DISABLE KEYS */;
/*!40000 ALTER TABLE `parrainage_reductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parrainage_relations`
--

DROP TABLE IF EXISTS `parrainage_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `parrainage_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parrain_id` int(11) NOT NULL COMMENT 'ID du client parrain',
  `filleul_id` int(11) NOT NULL COMMENT 'ID du client filleul',
  `date_parrainage` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_parrainage` (`filleul_id`) COMMENT 'Un filleul ne peut avoir qu''un seul parrain',
  KEY `parrain_id` (`parrain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parrainage_relations`
--

LOCK TABLES `parrainage_relations` WRITE;
/*!40000 ALTER TABLE `parrainage_relations` DISABLE KEYS */;
/*!40000 ALTER TABLE `parrainage_relations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partenaires`
--

DROP TABLE IF EXISTS `partenaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `partenaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `actif` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partenaires`
--

LOCK TABLES `partenaires` WRITE;
/*!40000 ALTER TABLE `partenaires` DISABLE KEYS */;
INSERT INTO `partenaires` VALUES
(1,'cannesphones_sample','','','','2025-04-05 15:09:28',1),
(2,'cannesphones_sample','','','','2025-04-05 15:12:46',1),
(3,'cannesphones_sample','','','','2025-04-05 15:12:55',1),
(5,'cannesphones_sample','','','','2025-04-05 15:13:47',1),
(6,'cannesphones_sample','','','','2025-04-05 15:15:21',1),
(7,'cannesphones_sample','','','','2025-04-05 15:15:44',1);
/*!40000 ALTER TABLE `partenaires` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `photos_reparation`
--

DROP TABLE IF EXISTS `photos_reparation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `photos_reparation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reparation_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date_upload` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reparation_id` (`reparation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `photos_reparation`
--

LOCK TABLES `photos_reparation` WRITE;
/*!40000 ALTER TABLE `photos_reparation` DISABLE KEYS */;
INSERT INTO `photos_reparation` VALUES
(32,739,'assets/images/reparations/739/photo_1746478049_681923e1e67e6.jpeg','','2025-05-05 20:47:29'),
(33,755,'assets/images/reparations/755/photo_1749079410_6840d572f2a21.jpeg','sad','2025-06-04 23:23:30'),
(34,761,'assets/images/reparations/761/photo_1750019096_684f2c181e685.jpeg','','2025-06-15 20:24:57');
/*!40000 ALTER TABLE `photos_reparation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pieces_avancees`
--

DROP TABLE IF EXISTS `pieces_avancees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pieces_avancees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partenaire_id` int(11) NOT NULL,
  `piece_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `date_avance` datetime DEFAULT current_timestamp(),
  `statut` enum('EN_ATTENTE','VALIDÉ','REMBOURSÉ','ANNULÉ') DEFAULT 'EN_ATTENTE',
  PRIMARY KEY (`id`),
  KEY `partenaire_id` (`partenaire_id`),
  KEY `piece_id` (`piece_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pieces_avancees`
--

LOCK TABLES `pieces_avancees` WRITE;
/*!40000 ALTER TABLE `pieces_avancees` DISABLE KEYS */;
/*!40000 ALTER TABLE `pieces_avancees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produits`
--

DROP TABLE IF EXISTS `produits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `produits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `motif_retour` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `categorie_id` (`categorie_id`),
  KEY `produits_fournisseur_fk` (`fournisseur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produits`
--

LOCK TABLES `produits` WRITE;
/*!40000 ALTER TABLE `produits` DISABLE KEYS */;
INSERT INTO `produits` VALUES
(6,'87248548','database_general_sample_info','',NULL,NULL,10.00,89.00,8,2,'2025-04-20 23:19:34','2025-06-08 23:52:36','normal',NULL,NULL),
(7,'6971402785014','database_general_sample_info','Hh',NULL,NULL,20.00,40.00,9,5,'2025-04-21 10:57:09','2025-06-08 23:52:36','normal',NULL,NULL),
(8,'935764','database_general_sample_info','',NULL,4,15.60,99.00,0,2,'2025-04-21 21:16:05','2025-06-08 23:52:36','a_retourner',NULL,NULL),
(9,'iphone 8','database_general_sample_info','test',NULL,NULL,12.00,222.00,0,5,'2025-04-21 23:26:29','2025-06-08 23:52:36','temporaire',NULL,NULL);
/*!40000 ALTER TABLE `produits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_subscriptions`
--

DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(512) NOT NULL,
  `auth_key` varchar(255) NOT NULL,
  `p256dh_key` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_endpoint` (`user_id`,`endpoint`(255)),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_subscriptions`
--

LOCK TABLES `push_subscriptions` WRITE;
/*!40000 ALTER TABLE `push_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rachat_appareils`
--

DROP TABLE IF EXISTS `rachat_appareils`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rachat_appareils` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rachat_appareils`
--

LOCK TABLES `rachat_appareils` WRITE;
/*!40000 ALTER TABLE `rachat_appareils` DISABLE KEYS */;
INSERT INTO `rachat_appareils` VALUES
(40,414,'database_general_sample_info','identite_1744750425_67fec75998af5.jpg','appareil_1744750425_67fec7599b837.jpg','signature_1744750425_67fec759983ee.png','client_1744750425_67fec7599e0b9.jpg','2025-04-15 20:53:45','Jhnh',0,80.00,'Hbhj',NULL,NULL),
(41,445,'iPhone 12','identite_1744896129_680100816749c.jpg','appareil_1744896129_6801008168ee0.jpg','signature_1744896129_6801008166fd0.png','client_1744896129_680100816a537.jpg','2025-04-17 13:22:09','G6TDJ84J0F0N',1,50.00,'iPhone 12',NULL,NULL),
(42,522,'21','identite_1750027191_684f4bb74d83f.jpg','appareil_1750027191_684f4bb74da47.jpg','signature_1750027191_684f4bb74d4bd.png','client_1750027191_684f4bb74dbee.jpg','2025-06-15 22:39:51','21',1,22.00,'21',NULL,NULL),
(43,524,'da','identite_1750027925_684f4e95ae789.jpg','appareil_1750027925_684f4e95ae91a.jpg','signature_1750027925_684f4e95add72.png','client_1750027925_684f4e95aea64.jpg','2025-06-15 22:52:06','sad',1,2.00,'da',NULL,NULL);
/*!40000 ALTER TABLE `rachat_appareils` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reparation_attributions`
--

DROP TABLE IF EXISTS `reparation_attributions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reparation_attributions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reparation_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `date_debut` timestamp NULL DEFAULT current_timestamp(),
  `date_fin` timestamp NULL DEFAULT NULL,
  `statut_avant` varchar(50) DEFAULT NULL,
  `statut_apres` varchar(50) DEFAULT NULL,
  `est_principal` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_reparation` (`reparation_id`),
  KEY `idx_employe` (`employe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reparation_attributions`
--

LOCK TABLES `reparation_attributions` WRITE;
/*!40000 ALTER TABLE `reparation_attributions` DISABLE KEYS */;
INSERT INTO `reparation_attributions` VALUES
(77,603,1,'2025-04-17 14:46:39','2025-04-17 16:02:37','nouveau_diagnostique',NULL,1),
(78,606,3,'2025-04-17 14:56:06','2025-04-17 14:56:11','nouvelle_intervention',NULL,1),
(79,603,3,'2025-04-17 16:02:15','2025-04-17 16:02:37','reparation_effectue',NULL,1),
(81,587,2,'2025-04-18 09:44:54','2025-04-18 09:49:32','en_attente_livraison',NULL,1),
(82,617,3,'2025-04-18 12:40:53','2025-04-18 13:13:28','nouvelle_intervention',NULL,1),
(83,620,3,'2025-04-18 13:16:30','2025-04-18 14:40:07','nouveau_diagnostique',NULL,1),
(85,618,3,'2025-04-18 14:41:43','2025-04-29 12:12:54','nouveau_diagnostique',NULL,1),
(86,624,2,'2025-04-18 15:45:10','2025-04-18 15:45:16','nouvelle_intervention',NULL,1),
(87,628,2,'2025-04-19 09:22:40','2025-04-19 09:58:13','nouvelle_intervention',NULL,1),
(88,595,2,'2025-04-19 10:06:18','2025-04-19 10:06:42','restitue',NULL,1),
(89,589,1,'2025-04-19 10:16:53','2025-04-19 15:30:13','en_attente_livraison',NULL,1),
(90,592,2,'2025-04-19 12:28:39','2025-04-22 10:15:52','nouvelle_intervention',NULL,1),
(91,589,1,'2025-04-19 15:32:13','2025-04-19 15:32:44','en_attente_responsable',NULL,1),
(92,589,1,'2025-04-19 15:33:15','2025-04-20 16:34:32','reparation_effectue',NULL,1),
(93,632,1,'2025-04-20 16:34:51','2025-04-21 10:55:39','nouvelle_intervention',NULL,1),
(94,632,1,'2025-04-21 10:55:48','2025-04-21 18:25:32','reparation_effectue',NULL,1),
(97,638,1,'2025-04-22 10:20:10','2025-04-22 10:20:58','nouvelle_intervention',NULL,1),
(98,638,1,'2025-04-22 10:21:02','2025-04-22 10:21:16','reparation_effectue',NULL,1),
(99,638,1,'2025-04-22 10:21:23','2025-04-22 10:21:31','en_attente_responsable',NULL,1),
(100,638,2,'2025-04-22 10:22:18','2025-04-22 10:22:32','nouveau_diagnostique',NULL,1),
(101,640,2,'2025-04-22 16:06:14','2025-04-22 16:06:31','nouvelle_intervention',NULL,1),
(102,640,2,'2025-04-22 16:12:23','2025-04-22 16:12:27','reparation_effectue',NULL,1),
(103,640,2,'2025-04-22 16:15:26','2025-04-22 16:15:29','reparation_effectue',NULL,1),
(104,640,2,'2025-04-22 16:16:20','2025-04-22 16:16:28','reparation_effectue',NULL,1),
(105,640,1,'2025-04-22 16:17:13','2025-04-22 16:17:20','reparation_effectue',NULL,1),
(106,640,1,'2025-04-22 16:17:42','2025-04-22 16:17:54','reparation_effectue',NULL,1),
(107,640,1,'2025-04-22 16:18:00','2025-04-22 16:18:29','reparation_effectue',NULL,1),
(108,640,1,'2025-04-22 16:18:35','2025-04-22 16:18:42','reparation_annule',NULL,1),
(109,640,1,'2025-04-22 16:18:52','2025-04-22 16:18:55','en_attente_responsable',NULL,1),
(110,640,1,'2025-04-22 16:24:19','2025-04-22 16:24:22','reparation_effectue',NULL,1),
(111,644,1,'2025-04-22 16:33:25','2025-04-22 16:33:29','nouvelle_intervention',NULL,1),
(112,651,1,'2025-04-23 01:19:26','2025-04-23 08:52:05','restitue',NULL,1),
(113,652,1,'2025-04-23 08:52:42','2025-04-23 08:52:46','reparation_effectue',NULL,1),
(114,653,1,'2025-04-23 09:30:49','2025-04-23 09:30:57','nouvelle_intervention',NULL,1),
(115,625,1,'2025-04-23 09:49:45','2025-04-23 09:49:50','en_attente_livraison',NULL,1),
(116,655,1,'2025-04-23 15:34:51','2025-04-23 15:34:57','nouvelle_intervention',NULL,1),
(117,646,1,'2025-04-25 08:23:29','2025-04-25 08:23:38','nouveau_diagnostique',NULL,1),
(118,657,1,'2025-04-25 13:04:32','2025-04-25 13:04:45','nouvelle_intervention',NULL,1),
(119,663,1,'2025-04-26 09:24:17','2025-04-26 09:24:33','nouvelle_intervention',NULL,1),
(120,668,1,'2025-04-26 10:01:10','2025-04-26 10:04:27','nouvelle_intervention',NULL,1),
(121,670,1,'2025-04-26 13:38:20','2025-04-26 13:38:27','nouvelle_intervention',NULL,1),
(122,591,1,'2025-04-26 14:18:07','2025-04-26 14:18:12','en_attente_livraison',NULL,1),
(123,671,1,'2025-04-26 15:14:58','2025-04-26 15:15:01','nouvelle_intervention',NULL,1),
(124,675,1,'2025-04-27 16:42:39','2025-04-27 16:42:48','nouvelle_intervention',NULL,1),
(125,675,1,'2025-04-28 22:35:52','2025-04-29 10:32:39','restitue',NULL,1),
(126,688,1,'2025-04-29 10:32:50','2025-04-29 10:33:10','nouvelle_intervention',NULL,1),
(127,690,3,'2025-04-29 12:15:55','2025-04-29 12:57:53','nouvelle_intervention',NULL,1),
(128,660,3,'2025-04-29 13:52:52','2025-04-29 15:22:41','nouvelle_intervention',NULL,1),
(129,693,3,'2025-04-29 15:27:15','2025-04-29 15:27:19','nouvelle_intervention',NULL,1),
(130,660,3,'2025-04-29 15:29:34','2025-04-29 15:29:43','reparation_effectue',NULL,1),
(131,665,1,'2025-04-30 08:47:30','2025-04-30 08:47:34','en_attente_livraison',NULL,1),
(132,697,3,'2025-04-30 08:49:48','2025-04-30 08:54:48','nouvelle_intervention',NULL,1),
(133,592,3,'2025-04-30 08:58:38','2025-04-30 09:55:39','en_attente_livraison',NULL,1),
(134,699,1,'2025-04-30 15:16:18','2025-04-30 15:16:26','en_cours_intervention',NULL,1);
/*!40000 ALTER TABLE `reparation_attributions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reparation_logs`
--

DROP TABLE IF EXISTS `reparation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reparation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reparation_id` int(11) NOT NULL,
  `employe_id` int(11) NOT NULL,
  `action_type` enum('demarrage','terminer','changement_statut','ajout_note','modification','autre') NOT NULL,
  `date_action` timestamp NULL DEFAULT current_timestamp(),
  `statut_avant` varchar(50) DEFAULT NULL,
  `statut_apres` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reparation` (`reparation_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_date_action` (`date_action`),
  KEY `idx_employe` (`employe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1318 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reparation_logs`
--

LOCK TABLES `reparation_logs` WRITE;
/*!40000 ALTER TABLE `reparation_logs` DISABLE KEYS */;
INSERT INTO `reparation_logs` VALUES
(1056,0,6,'demarrage','2025-05-05 00:47:47',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1057,0,6,'demarrage','2025-05-05 00:50:15',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1058,0,6,'demarrage','2025-05-05 00:52:55',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation avec note interne'),
(1059,0,6,'ajout_note','2025-05-05 00:52:55','nouvelle_intervention','nouvelle_intervention','Note interne ajoutée: s'),
(1060,0,6,'demarrage','2025-05-05 01:02:43',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1061,0,6,'demarrage','2025-05-05 01:03:28',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1062,0,6,'demarrage','2025-05-05 01:18:35',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1063,0,6,'demarrage','2025-05-05 01:43:38',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1064,0,6,'demarrage','2025-05-05 01:43:38',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1065,0,6,'demarrage','2025-05-05 01:45:03',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1066,0,6,'demarrage','2025-05-05 01:45:03',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1067,0,6,'demarrage','2025-05-05 01:46:57',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1068,0,6,'demarrage','2025-05-05 01:46:57',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1069,0,6,'demarrage','2025-05-05 01:47:18',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1070,0,6,'demarrage','2025-05-05 01:47:18',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1071,0,6,'demarrage','2025-05-05 09:57:08',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1072,0,6,'demarrage','2025-05-05 09:57:09',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1073,0,6,'demarrage','2025-05-05 10:50:26',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1074,0,6,'demarrage','2025-05-05 10:50:26',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1075,0,6,'demarrage','2025-05-05 10:50:47',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1076,0,6,'demarrage','2025-05-05 10:50:47',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1077,0,6,'demarrage','2025-05-05 10:59:13',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1078,0,6,'demarrage','2025-05-05 10:59:13',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1079,0,6,'demarrage','2025-05-05 11:20:12',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation avec note interne'),
(1080,0,6,'ajout_note','2025-05-05 11:20:12','nouvelle_intervention','nouvelle_intervention','Note interne ajoutée: sad'),
(1081,0,6,'demarrage','2025-05-05 11:20:12',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation avec note interne'),
(1082,0,6,'ajout_note','2025-05-05 11:20:12','nouvelle_intervention','nouvelle_intervention','Note interne ajoutée: sad'),
(1083,0,6,'demarrage','2025-05-05 11:43:55',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1084,0,6,'demarrage','2025-05-05 11:43:56',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1085,0,6,'demarrage','2025-05-05 18:43:30',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1086,0,6,'demarrage','2025-05-05 18:43:30',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1087,0,6,'demarrage','2025-05-05 18:50:38',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1088,0,6,'demarrage','2025-05-05 18:50:38',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1089,732,6,'demarrage','2025-05-05 18:53:51',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1090,733,6,'demarrage','2025-05-05 18:53:52',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1091,0,6,'demarrage','2025-05-05 18:58:07',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1092,0,6,'demarrage','2025-05-05 18:58:07',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1093,0,6,'demarrage','2025-05-05 19:00:46',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1094,0,6,'demarrage','2025-05-05 19:03:50',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1095,0,6,'demarrage','2025-05-05 19:04:52',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1096,0,6,'demarrage','2025-05-05 19:07:40',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1097,0,6,'demarrage','2025-05-05 19:12:36',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1098,738,0,'','2025-05-05 19:49:31',NULL,NULL,'Prix mis à jour: 4 €'),
(1099,740,0,'','2025-05-05 19:49:39',NULL,NULL,'Prix mis à jour: 4 €'),
(1100,740,1,'changement_statut','2025-05-05 19:52:14','nouvelle_intervention','en_attente_accord_client','Statut changé en \'En attente d\'accord client\' lors de l\'envoi d\'un devis'),
(1101,740,1,'','2025-05-05 19:57:03','en_attente_accord_client','nouveau_diagnostique','Changement de statut via le modal: de \'en_attente_accord_client\' à \'nouveau_diagnostique\''),
(1102,740,1,'','2025-05-05 19:57:15','nouveau_diagnostique','nouvelle_commande','Changement de statut via le modal: de \'nouveau_diagnostique\' à \'nouvelle_commande\''),
(1103,740,1,'autre','2025-05-05 20:02:49',NULL,NULL,'Réparation marquée comme URGENTE'),
(1104,740,1,'changement_statut','2025-05-05 20:11:49','nouvelle_commande','en_attente_accord_client','Statut changé en \'En attente d\'accord client\' lors de l\'envoi d\'un devis'),
(1105,0,6,'demarrage','2025-05-05 20:49:32',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1106,0,6,'demarrage','2025-05-05 20:52:03',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1107,0,6,'demarrage','2025-05-05 20:55:29',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1108,743,6,'demarrage','2025-05-05 21:03:30',NULL,NULL,'Réparation assignée à l\'employé'),
(1109,743,6,'changement_statut','2025-05-05 21:03:35','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1110,743,6,'demarrage','2025-05-05 21:03:59',NULL,NULL,'Réparation assignée à l\'employé'),
(1111,743,6,'changement_statut','2025-05-05 21:04:35','en_cours_intervention','en_attente_accord_client','Réparation marquée comme en_attente_accord_client'),
(1112,743,1,'changement_statut','2025-05-05 21:04:53','en_attente_accord_client','en_attente_accord_client','Statut changé en \'En attente d\'accord client\' lors de l\'envoi d\'un devis'),
(1113,742,6,'demarrage','2025-05-05 21:06:30',NULL,NULL,'Réparation assignée à l\'employé'),
(1114,742,6,'changement_statut','2025-05-05 21:12:59','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1115,743,6,'demarrage','2025-05-05 21:13:22',NULL,NULL,'Réparation assignée à l\'employé'),
(1116,743,6,'changement_statut','2025-05-05 21:13:26','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1117,743,6,'demarrage','2025-05-05 21:13:55',NULL,NULL,'Réparation assignée à l\'employé'),
(1118,743,6,'changement_statut','2025-05-05 21:13:59','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1119,743,6,'demarrage','2025-05-05 21:14:20',NULL,NULL,'Réparation assignée à l\'employé'),
(1120,743,6,'changement_statut','2025-05-05 21:14:29','en_cours_intervention','reparation_annule','Réparation marquée comme reparation_annule'),
(1121,743,6,'demarrage','2025-05-05 21:16:08',NULL,NULL,'Réparation assignée à l\'employé'),
(1122,743,6,'changement_statut','2025-05-05 21:16:11','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1123,743,6,'demarrage','2025-05-05 21:16:25',NULL,NULL,'Réparation assignée à l\'employé'),
(1124,743,6,'changement_statut','2025-05-05 21:16:29','en_cours_intervention','reparation_annule','Réparation marquée comme reparation_annule'),
(1125,743,6,'demarrage','2025-05-05 21:16:46',NULL,NULL,'Réparation assignée à l\'employé'),
(1126,743,6,'changement_statut','2025-05-05 21:16:50','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1127,743,6,'demarrage','2025-05-05 21:21:41',NULL,NULL,'Réparation assignée à l\'employé'),
(1128,743,6,'changement_statut','2025-05-05 21:21:58','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1129,743,6,'demarrage','2025-05-05 21:25:53',NULL,NULL,'Réparation assignée à l\'employé'),
(1130,743,6,'changement_statut','2025-05-05 21:25:56','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1131,742,6,'demarrage','2025-05-05 21:30:25',NULL,NULL,'Réparation assignée à l\'employé'),
(1132,742,6,'changement_statut','2025-05-05 21:30:28','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1133,743,1,'changement_statut','2025-05-05 21:36:06','reparation_effectue','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1134,743,1,'changement_statut','2025-05-05 21:36:13','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1135,743,1,'changement_statut','2025-05-05 21:36:25','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1136,743,1,'changement_statut','2025-05-05 21:41:05','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1137,743,1,'changement_statut','2025-05-05 21:41:10','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1138,743,1,'changement_statut','2025-05-05 21:41:23','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1139,743,1,'changement_statut','2025-05-05 21:56:10','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1140,743,1,'changement_statut','2025-05-05 21:57:10','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1141,743,1,'changement_statut','2025-05-05 21:57:17','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1142,743,1,'changement_statut','2025-05-05 21:57:27','nouveau_diagnostique','reparation_effectue','Mise à jour du statut avec SMS activé'),
(1143,743,1,'changement_statut','2025-05-05 21:57:41','reparation_effectue','reparation_annule','Mise à jour du statut avec SMS activé'),
(1144,743,1,'changement_statut','2025-05-05 21:57:49','reparation_annule','reparation_effectue','Mise à jour du statut avec SMS activé'),
(1145,743,1,'changement_statut','2025-05-05 21:57:55','reparation_effectue','reparation_annule','Mise à jour du statut avec SMS activé'),
(1146,743,1,'changement_statut','2025-05-05 22:01:47','reparation_annule','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1147,743,1,'changement_statut','2025-05-05 22:01:54','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1148,743,1,'changement_statut','2025-05-05 22:02:31','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1149,743,1,'changement_statut','2025-05-05 22:03:57','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1150,742,1,'changement_statut','2025-05-05 22:04:06','reparation_effectue','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1151,743,1,'changement_statut','2025-05-05 22:05:03','nouveau_diagnostique','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1152,742,1,'changement_statut','2025-05-05 22:05:14','nouveau_diagnostique','reparation_annule','Mise à jour du statut avec SMS désactivé'),
(1153,743,1,'changement_statut','2025-05-05 22:16:41','reparation_effectue','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1154,743,1,'changement_statut','2025-05-05 22:16:50','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1155,743,1,'changement_statut','2025-05-05 22:24:42','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1156,743,1,'changement_statut','2025-05-05 22:25:10','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1157,743,1,'changement_statut','2025-05-05 22:25:27','nouveau_diagnostique','nouvelle_commande','Mise à jour du statut avec SMS activé'),
(1158,743,6,'demarrage','2025-05-05 22:30:36',NULL,NULL,'Réparation assignée à l\'employé'),
(1159,743,6,'changement_statut','2025-05-05 22:30:38','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1160,743,1,'changement_statut','2025-05-05 22:32:29','reparation_effectue','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1161,742,1,'changement_statut','2025-05-05 22:32:36','reparation_annule','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1162,742,6,'demarrage','2025-05-05 22:32:40',NULL,NULL,'Réparation assignée à l\'employé'),
(1163,742,6,'changement_statut','2025-05-05 22:32:42','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1164,743,6,'demarrage','2025-05-05 22:41:51',NULL,NULL,'Réparation assignée à l\'employé'),
(1165,743,6,'changement_statut','2025-05-05 22:41:53','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1166,743,6,'demarrage','2025-05-05 22:54:42',NULL,NULL,'Réparation assignée à l\'employé'),
(1167,743,6,'changement_statut','2025-05-05 22:54:52','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1168,743,6,'demarrage','2025-05-05 22:55:26',NULL,NULL,'Réparation assignée à l\'employé'),
(1169,743,6,'changement_statut','2025-05-05 22:55:30','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1170,743,6,'demarrage','2025-05-05 22:55:54',NULL,NULL,'Réparation assignée à l\'employé'),
(1171,743,6,'changement_statut','2025-05-05 22:55:58','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1172,743,6,'demarrage','2025-05-05 22:57:05',NULL,NULL,'Réparation assignée à l\'employé'),
(1173,743,6,'changement_statut','2025-05-05 22:57:07','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1174,743,6,'demarrage','2025-05-05 22:58:45',NULL,NULL,'Réparation assignée à l\'employé'),
(1175,743,6,'changement_statut','2025-05-05 22:58:47','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1176,743,6,'demarrage','2025-05-05 22:59:08',NULL,NULL,'Réparation assignée à l\'employé'),
(1177,743,6,'changement_statut','2025-05-05 22:59:10','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1178,0,6,'demarrage','2025-05-05 23:00:13',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1179,744,6,'demarrage','2025-05-05 23:01:58',NULL,NULL,'Réparation assignée à l\'employé'),
(1180,744,6,'changement_statut','2025-05-05 23:02:00','en_cours_intervention','reparation_effectue','Réparation marquée comme reparation_effectue'),
(1181,0,6,'demarrage','2025-05-07 17:31:22',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1182,0,6,'demarrage','2025-05-07 17:32:14',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1183,0,6,'demarrage','2025-05-07 17:41:38',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1184,0,6,'demarrage','2025-05-07 17:46:37',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1185,0,6,'demarrage','2025-05-07 17:49:07',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1186,0,6,'demarrage','2025-05-07 17:51:30',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1187,0,6,'demarrage','2025-05-07 17:53:39',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1188,0,6,'demarrage','2025-05-07 17:54:53',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1189,0,6,'demarrage','2025-05-07 17:56:04',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1190,0,6,'demarrage','2025-05-07 17:57:04',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1191,0,6,'demarrage','2025-05-07 20:21:48',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1192,755,1,'changement_statut','2025-05-07 20:22:53','inconnu','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1193,744,1,'changement_statut','2025-05-07 21:21:35','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1194,743,1,'changement_statut','2025-05-07 21:22:03','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1195,742,1,'changement_statut','2025-05-07 21:22:04','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1196,743,1,'changement_statut','2025-05-07 21:22:33','restitue','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1197,742,1,'changement_statut','2025-05-07 21:22:42','restitue','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1198,754,1,'changement_statut','2025-05-07 21:47:20','inconnu','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1199,753,1,'changement_statut','2025-05-07 21:47:23','inconnu','reparation_annule','Mise à jour du statut avec SMS désactivé'),
(1200,753,1,'changement_statut','2025-05-07 21:47:34','reparation_annule','restitue','Mise à jour du statut de \'reparation_annule\' à \'restitue\' via mise à jour par lots'),
(1201,754,1,'changement_statut','2025-05-07 21:47:34','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1202,752,1,'changement_statut','2025-05-07 21:48:14','inconnu','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1203,751,1,'changement_statut','2025-05-07 21:48:16','inconnu','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1204,755,1,'changement_statut','2025-05-07 21:48:25','nouveau_diagnostique','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1205,750,1,'changement_statut','2025-05-07 21:50:19','inconnu','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1206,746,1,'changement_statut','2025-05-07 21:50:23','inconnu','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1207,746,1,'changement_statut','2025-05-07 21:53:21','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1208,750,1,'changement_statut','2025-05-07 21:53:21','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1209,755,1,'changement_statut','2025-05-07 21:53:21','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1210,751,1,'changement_statut','2025-05-07 21:53:22','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1211,752,1,'changement_statut','2025-05-07 21:53:22','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1212,754,1,'changement_statut','2025-05-07 21:57:42','restitue','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1213,754,1,'changement_statut','2025-05-07 22:15:26','reparation_effectue','gardiennage','Mise à jour du statut de \'reparation_effectue\' à \'gardiennage\' via mise à jour par lots'),
(1214,748,1,'changement_statut','2025-05-07 22:39:41','inconnu','en_cours_diagnostique','Mise à jour du statut avec SMS activé'),
(1215,747,1,'changement_statut','2025-05-07 22:40:18','inconnu','en_cours_diagnostique','Mise à jour du statut avec SMS activé'),
(1216,745,1,'changement_statut','2025-05-07 22:40:24','inconnu','en_cours_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1217,748,1,'changement_statut','2025-05-07 22:44:27','en_cours_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1218,748,1,'changement_statut','2025-05-07 22:44:37','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1219,748,1,'changement_statut','2025-05-07 23:18:35','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1220,748,1,'changement_statut','2025-05-07 23:18:44','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1221,747,1,'changement_statut','2025-05-07 23:18:50','en_cours_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1222,748,1,'changement_statut','2025-05-07 23:19:37','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1223,748,1,'changement_statut','2025-05-07 23:21:47','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1224,748,1,'changement_statut','2025-05-07 23:22:00','nouveau_diagnostique','nouvelle_intervention','Mise à jour du statut avec SMS activé'),
(1225,748,1,'changement_statut','2025-05-07 23:22:10','nouvelle_intervention','nouvelle_intervention','Mise à jour du statut avec SMS désactivé'),
(1226,747,1,'changement_statut','2025-05-07 23:37:56','nouveau_diagnostique','en_cours_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1227,748,1,'changement_statut','2025-05-07 23:38:01','nouvelle_intervention','en_cours_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1228,743,1,'changement_statut','2025-05-07 23:46:06','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1229,743,1,'changement_statut','2025-05-07 23:46:12','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS activé'),
(1230,743,1,'changement_statut','2025-05-07 23:46:20','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1231,742,1,'changement_statut','2025-05-07 23:46:26','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1232,748,1,'changement_statut','2025-05-07 23:46:35','en_cours_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1233,748,1,'changement_statut','2025-05-07 23:48:38','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1234,748,1,'changement_statut','2025-05-07 23:48:47','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1235,748,1,'changement_statut','2025-05-07 23:48:58','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1236,748,1,'changement_statut','2025-05-07 23:49:08','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1237,748,1,'changement_statut','2025-05-07 23:49:23','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1238,748,1,'changement_statut','2025-05-07 23:55:38','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1239,747,1,'changement_statut','2025-05-07 23:55:44','en_cours_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1240,747,1,'changement_statut','2025-05-07 23:56:07','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1241,745,1,'changement_statut','2025-05-07 23:56:13','en_cours_diagnostique','en_attente_accord_client','Mise à jour du statut avec SMS désactivé'),
(1242,742,1,'changement_statut','2025-05-07 23:56:18','nouveau_diagnostique','restitue','Mise à jour du statut avec SMS désactivé'),
(1243,747,1,'changement_statut','2025-05-07 23:56:23','nouveau_diagnostique','en_attente_accord_client','Mise à jour du statut avec SMS désactivé'),
(1244,748,1,'changement_statut','2025-05-07 23:56:30','nouveau_diagnostique','annule','Mise à jour du statut avec SMS désactivé'),
(1245,753,1,'changement_statut','2025-05-07 23:56:37','restitue','gardiennage','Mise à jour du statut avec SMS désactivé'),
(1246,754,1,'changement_statut','2025-05-07 23:58:49','gardiennage','en_cours_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1247,753,1,'changement_statut','2025-05-07 23:58:56','gardiennage','en_cours_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1248,755,1,'changement_statut','2025-05-07 23:59:26','restitue','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1249,753,1,'','2025-05-08 00:10:15','en_cours_diagnostique','nouvelle_commande','Changement de statut via le modal: de \'en_cours_diagnostique\' à \'nouvelle_commande\''),
(1250,755,1,'changement_statut','2025-05-08 00:22:49','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1251,754,1,'changement_statut','2025-05-08 00:23:01','en_cours_diagnostique','nouvelle_intervention','Mise à jour du statut avec SMS désactivé'),
(1252,753,1,'changement_statut','2025-05-08 00:23:26','en_cours_diagnostique','en_cours_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1253,753,1,'changement_statut','2025-05-08 00:24:29','en_cours_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1254,753,1,'changement_statut','2025-05-08 00:24:37','nouveau_diagnostique','nouveau_diagnostique','Mise à jour du statut avec SMS désactivé'),
(1255,755,0,'','2025-06-04 23:22:58',NULL,NULL,'Prix mis à jour: 50 €'),
(1256,0,6,'demarrage','2025-06-04 23:26:15',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1257,0,6,'demarrage','2025-06-07 22:13:43',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1258,0,6,'demarrage','2025-06-07 22:55:10',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1259,758,0,'changement_statut','2025-06-09 21:49:44','nouvelle_intervention','Nouveau Diagnostique','Changement de statut via modal'),
(1260,758,0,'changement_statut','2025-06-09 21:50:21','Nouveau Diagnostique','Nouvelle Intervention','Changement de statut via modal'),
(1261,757,0,'changement_statut','2025-06-09 21:51:09','nouvelle_intervention','Nouvelle Intervention','Changement de statut via modal'),
(1262,0,6,'demarrage','2025-06-09 21:56:31',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1263,757,0,'changement_statut','2025-06-09 21:57:31','Nouvelle Intervention','Nouvelle Commande','Changement de statut via modal'),
(1264,758,0,'changement_statut','2025-06-09 21:57:37','Nouvelle Intervention','Nouvelle Commande','Changement de statut via modal'),
(1265,758,0,'changement_statut','2025-06-09 21:57:37','Nouvelle Commande','Nouvelle Commande','Changement de statut via modal'),
(1266,759,0,'changement_statut','2025-06-09 22:05:16','nouvelle_intervention','Nouvelle Intervention','Changement de statut via modal'),
(1267,759,0,'changement_statut','2025-06-09 22:05:16','Nouvelle Intervention','Nouvelle Intervention','Changement de statut via modal'),
(1268,759,0,'changement_statut','2025-06-09 22:05:35','Nouvelle Intervention','Nouvelle Commande','Changement de statut via modal'),
(1269,753,0,'changement_statut','2025-06-09 22:05:45','nouveau_diagnostique','En cours de diagnostique','Changement de statut via modal'),
(1270,759,0,'changement_statut','2025-06-09 23:43:17','Nouvelle Commande','Restitué','Changement de statut via modal'),
(1271,759,0,'changement_statut','2025-06-09 23:43:58','Restitué','Réparation Effectuée','Changement de statut via modal'),
(1272,758,0,'changement_statut','2025-06-09 23:44:16','Nouvelle Commande','Restitué','Changement de statut via modal'),
(1273,759,0,'changement_statut','2025-06-09 23:45:54','Réparation Effectuée','Restitué','Changement de statut via modal'),
(1274,759,0,'changement_statut','2025-06-09 23:46:22','Restitué','Réparation Effectuée','Changement de statut via modal'),
(1275,759,0,'changement_statut','2025-06-09 23:47:37','Réparation Effectuée','Nouvelle Intervention','Changement de statut via modal'),
(1276,756,0,'changement_statut','2025-06-09 23:48:46','nouvelle_intervention','Nouvelle Intervention','Changement de statut via modal'),
(1277,759,0,'changement_statut','2025-06-09 23:49:38','Nouvelle Intervention','Nouvelle Commande','Changement de statut via modal'),
(1278,759,0,'changement_statut','2025-06-09 23:58:14','Nouvelle Commande','Nouveau Diagnostique','Changement de statut via modal'),
(1279,759,0,'changement_statut','2025-06-09 23:58:22','Nouveau Diagnostique','Nouvelle Intervention','Changement de statut via modal'),
(1280,756,0,'changement_statut','2025-06-10 00:07:03','Nouvelle Intervention','Réparation Effectuée','Changement de statut via modal'),
(1281,0,6,'demarrage','2025-06-11 20:37:07',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1282,0,6,'demarrage','2025-06-15 14:58:07',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation'),
(1283,755,1,'changement_statut','2025-06-15 19:14:36','nouveau_diagnostique','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1284,755,1,'changement_statut','2025-06-15 19:15:15','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1285,755,1,'changement_statut','2025-06-15 19:19:37','restitue','restitue','Mise à jour du statut de \'restitue\' à \'restitue\' via mise à jour par lots'),
(1286,755,1,'changement_statut','2025-06-15 19:23:11','restitue','restitue','Mise à jour du statut de \'restitue\' à \'restitue\' via mise à jour par lots'),
(1287,754,1,'changement_statut','2025-06-15 19:23:38','nouvelle_intervention','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1288,754,1,'changement_statut','2025-06-15 19:24:11','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1289,760,1,'changement_statut','2025-06-15 19:27:03','inconnu','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1290,760,1,'changement_statut','2025-06-15 19:27:18','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1291,761,1,'changement_statut','2025-06-15 19:29:35','inconnu','restitue','Mise à jour du statut avec SMS activé'),
(1292,760,1,'changement_statut','2025-06-15 19:29:58','restitue','reparation_effectue','Mise à jour du statut avec SMS activé'),
(1293,760,1,'changement_statut','2025-06-15 19:30:24','reparation_effectue','annule','Mise à jour du statut de \'reparation_effectue\' à \'annule\' via mise à jour par lots'),
(1294,758,1,'changement_statut','2025-06-15 19:31:31','Restitué','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1295,758,1,'changement_statut','2025-06-15 19:31:38','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1296,758,1,'changement_statut','2025-06-15 19:31:45','restitue','restitue','Mise à jour du statut de \'restitue\' à \'restitue\' via mise à jour par lots'),
(1297,758,1,'changement_statut','2025-06-15 19:32:13','restitue','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1298,755,1,'changement_statut','2025-06-15 19:32:23','restitue','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1299,754,1,'changement_statut','2025-06-15 19:32:28','restitue','reparation_effectue','Mise à jour du statut avec SMS activé'),
(1300,754,1,'changement_statut','2025-06-15 19:34:37','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1301,755,1,'changement_statut','2025-06-15 19:34:37','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1302,758,1,'changement_statut','2025-06-15 19:34:37','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1303,758,1,'changement_statut','2025-06-15 19:37:15','restitue','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1304,758,1,'changement_statut','2025-06-15 19:37:33','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1305,758,1,'changement_statut','2025-06-15 19:37:50','restitue','reparation_effectue','Mise à jour du statut avec SMS désactivé'),
(1306,758,1,'changement_statut','2025-06-15 19:38:01','reparation_effectue','restitue','Mise à jour du statut de \'reparation_effectue\' à \'restitue\' via mise à jour par lots'),
(1307,758,1,'changement_statut','2025-06-15 19:38:19','restitue','reparation_effectue','Mise à jour du statut avec SMS activé'),
(1308,754,1,'changement_statut','2025-06-15 19:38:45','restitue','reparation_effectue','Mise à jour du statut avec SMS activé'),
(1309,755,1,'changement_statut','2025-06-15 19:42:07','restitue','reparation_effectue','Mise à jour du statut avec SMS activé'),
(1310,755,1,'changement_statut','2025-06-15 19:49:54','reparation_effectue','termine','Mise à jour du statut avec SMS activé'),
(1311,761,1,'changement_statut','2025-06-15 20:11:59','restitue','en_attente_accord_client','Statut changé en \'En attente d\'accord client\' lors de l\'envoi d\'un devis'),
(1312,761,1,'changement_statut','2025-06-15 20:12:01','en_attente_accord_client','en_attente_accord_client','Statut changé en \'En attente d\'accord client\' lors de l\'envoi d\'un devis'),
(1313,761,0,'','2025-06-15 20:12:36',NULL,NULL,'Prix mis à jour: 55 €'),
(1314,761,1,'autre','2025-06-15 20:13:54',NULL,NULL,'Réparation marquée comme URGENTE'),
(1315,747,0,'','2025-06-15 20:22:23',NULL,NULL,'Prix mis à jour: 2 €'),
(1316,754,1,'changement_statut','2025-06-15 20:48:37','reparation_effectue','restitue','Mise à jour du statut avec SMS activé'),
(1317,0,6,'demarrage','2025-06-16 23:07:23',NULL,'nouvelle_intervention','Création d\'une nouvelle réparation');
/*!40000 ALTER TABLE `reparation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reparation_sms`
--

DROP TABLE IF EXISTS `reparation_sms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reparation_sms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reparation_id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` timestamp NULL DEFAULT current_timestamp(),
  `statut_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reparation_id` (`reparation_id`),
  KEY `template_id` (`template_id`),
  KEY `statut_id` (`statut_id`)
) ENGINE=InnoDB AUTO_INCREMENT=477 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reparation_sms`
--

LOCK TABLES `reparation_sms` WRITE;
/*!40000 ALTER TABLE `reparation_sms` DISABLE KEYS */;
INSERT INTO `reparation_sms` VALUES
(441,740,4,'0782962906','Bonjour, asd, \r\nle devis de votre 0782962906 est disponible. \r\nMontant : 4.00€\r\nConsultez-le ici :\r\n📄 http://Mdgeek.top/suivi.php?id=740\r\nUne question ? Appelez-nous au 04 93 46 71 63\r\nMAISON DU GEEK','2025-05-05 19:52:14',6),
(442,740,4,'0782962906','Bonjour, asd, \r\nle devis de votre 0782962906 est disponible. \r\nMontant : 4.00€\r\nConsultez-le ici :\r\n📄 http://Mdgeek.top/suivi.php?id=740\r\nUne question ? Appelez-nous au 04 93 46 71 63\r\nMAISON DU GEEK','2025-05-05 20:11:49',6),
(443,743,4,'337829626906','Bonjour, sa, \r\nle devis de votre sad est disponible. \r\nMontant : 21.00€\r\nConsultez-le ici :\r\n📄 http://Mdgeek.top/suivi.php?id=743\r\nUne question ? Appelez-nous au 04 93 46 71 63\r\nMAISON DU GEEK','2025-05-05 21:04:53',6),
(444,742,2,'+33782962906','tet, votre test est prêt ! Il vous attend pour retrouver une vie normale. 🥳\r\n🧾 Suivi : http://Mdgeek.top/suivi.php?id=742\r\nMaison du Geek – 08 95 79 59 33','2025-05-05 21:13:00',9),
(445,742,2,'+33782962906','tet, votre test est prêt ! Il vous attend pour retrouver une vie normale. 🥳\r\n🧾 Suivi : http://Mdgeek.top/suivi.php?id=742\r\nMaison du Geek – 08 95 79 59 33','2025-05-05 21:30:28',9),
(446,742,2,'+33782962906','tet, votre test est prêt ! Il vous attend pour retrouver une vie normale. 🥳\r\n🧾 Suivi : http://Mdgeek.top/suivi.php?id=742\r\nMaison du Geek – 08 95 79 59 33','2025-05-05 22:32:42',9),
(447,747,1,'+33782962906','Bonjour tet, votre réparation #747 a été enregistrée. Appareil: Trottinette asd. Prix estimé: 2,00€. Nous vous tiendrons informé de l\'avancement.','2025-05-07 17:41:38',NULL),
(448,748,1,'+33782962906','Bonjour tet, votre réparation #748 a été enregistrée. Appareil: Informatique ds. Prix estimé: 2,00€. Nous vous tiendrons informé de l\'avancement.','2025-05-07 17:46:37',NULL),
(449,749,1,'+33782962906','Bonjour sfdjdf, votre réparation #749 a été enregistrée. Appareil: Trottinette test. Prix estimé: 2,00€. Nous vous tiendrons informé de l\'avancement.','2025-05-07 17:49:07',NULL),
(450,750,1,'+33782962906','Bonjour asd, votre réparation #750 a été enregistrée. Appareil: Trottinette 0782962906. Prix estimé: 2,00€. Nous vous tiendrons informé de l\'avancement.','2025-05-07 17:51:30',NULL),
(451,751,1,'+33782962906','Bonjour asd, votre réparation #751 a été enregistrée. Appareil: Trottinette 0782962906. Prix estimé: 2,00€. Nous vous tiendrons informé de l\'avancement.','2025-05-07 17:53:40',NULL),
(452,752,1,'+33782962906','Bonjour asd, votre réparation #752 a été enregistrée. Appareil: Trottinette test. Prix estimé: 2,00€. Nous vous tiendrons informé de l\'avancement.','2025-05-07 17:54:53',NULL),
(453,753,1,'+33782962906','Bonjour asd, votre réparation #753 a été enregistrée. Appareil: Informatique cdsjd. Prix estimé: 2,00€. Nous vous tiendrons informé de l\'avancement.','2025-05-07 17:56:05',NULL),
(454,754,1,'+33782962906','Bonjour tet, votre réparation #754 a été enregistrée. Appareil: Trottinette test. Prix estimé: 3,00€. Nous vous tiendrons informé de l\'avancement.','2025-05-07 17:57:05',NULL),
(455,755,1,'+33782962906','Bonjour saber, votre réparation #755 a été enregistrée. Appareil: Trottinette test. Prix estimé: 1,00€. Nous vous tiendrons informé de l\'avancement.','2025-05-07 20:21:49',NULL),
(456,755,0,'+33782962906','Bonjour saber, votre réparation #755 est maintenant en statut: Nouveau Diagnostique.','2025-05-07 20:22:54',1),
(457,742,0,'+33782962906','Bonjour tet, votre réparation #742 est maintenant en statut: Nouveau Diagnostique.','2025-05-07 21:22:42',1),
(458,748,0,'+33782962906','Bonjour tet, votre réparation #748 est maintenant en statut: En cours de diagnostique.','2025-05-07 22:39:42',4),
(459,747,0,'+33782962906','Bonjour tet, votre réparation #747 est maintenant en statut: En cours de diagnostique.','2025-05-07 22:40:19',4),
(460,748,0,'+33782962906','Bonjour tet, votre réparation #748 est maintenant en statut: Nouveau Diagnostique.','2025-05-07 22:44:27',1),
(461,748,0,'+33782962906','Bonjour tet, votre réparation #748 est maintenant en statut: Nouveau Diagnostique.','2025-05-07 23:18:35',1),
(462,748,0,'+33782962906','Bonjour tet, votre réparation #748 est maintenant en statut: Nouvelle Intervention.','2025-05-07 23:22:01',2),
(463,756,1,'+33782962906','Bonjour saber, votre réparation #756 a été enregistrée. Appareil: Trottinette asd. Prix estimé: 21,00€. Nous vous tiendrons informé de l\'avancement.','2025-06-04 23:26:16',NULL),
(464,757,1,'+33782962906','Bonjour farida, votre réparation #757 a été enregistrée. Appareil: Informatique sab. Prix estimé: 20,00€. Nous vous tiendrons informé de l\'avancement.','2025-06-07 22:13:43',NULL),
(465,761,1,'+33782962906','Bonjour saber, votre réparation #761 a été enregistrée. Appareil: Trottinette 213. Prix estimé: 21,00€. Nous vous tiendrons informé de l\'avancement.','2025-06-15 14:58:08',NULL),
(466,761,11,'33782962906','🎉 saber,\r\nTon 213 est de retour à la maison ! On espère qu’il est content 🤓\r\n💬 Laisse-nous un petit avis !\r\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\r\n🏠 Maison du Geek\r\n📞 08 95 79 59 33\r\n','2025-06-15 19:29:36',11),
(467,760,2,'55','55, votre saaa est prêt ! Il vous attend pour retrouver une vie normale. 🥳\r\n🧾 Suivi : http://Mdgeek.top/suivi.php?id=760\r\nMaison du Geek – 08 95 79 59 33','2025-06-15 19:29:59',9),
(468,754,2,'33782962906','tet, votre test est prêt ! Il vous attend pour retrouver une vie normale. 🥳\r\n🧾 Suivi : http://Mdgeek.top/suivi.php?id=754\r\nMaison du Geek – 08 95 79 59 33','2025-06-15 19:32:28',9),
(469,758,2,'33234567891','albert, votre asd est prêt ! Il vous attend pour retrouver une vie normale. 🥳\r\n🧾 Suivi : http://Mdgeek.top/suivi.php?id=758\r\nMaison du Geek – 08 95 79 59 33','2025-06-15 19:38:19',9),
(470,754,2,'33782962906','tet, votre test est prêt ! Il vous attend pour retrouver une vie normale. 🥳\r\n🧾 Suivi : http://Mdgeek.top/suivi.php?id=754\r\nMaison du Geek – 08 95 79 59 33','2025-06-15 19:38:46',9),
(471,755,2,'33782962906','saber, votre test est prêt ! Il vous attend pour retrouver une vie normale. 🥳\r\n🧾 Suivi : http://Mdgeek.top/suivi.php?id=755\r\nMaison du Geek – 08 95 79 59 33','2025-06-15 19:42:07',9),
(472,755,15,'33782962906','saber, on espère que ton test se porte comme un charme ! 😊 Aide nos Geeks avec un petit avis :\r\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\r\n📲 Suivi : http://Mdgeek.top/suivi.php?id=755\r\nMaison du Geek – 08 95 79 59 33','2025-06-15 19:49:54',15),
(473,761,4,'33782962906','Bonjour, saber, \r\nle devis de votre 213 est disponible. \r\nMontant : 21.00€\r\nConsultez-le ici :\r\n📄 http://Mdgeek.top/suivi.php?id=761\r\nUne question ? Appelez-nous au 04 93 46 71 63\r\nMAISON DU GEEK\n\nDétails techniques:\n21','2025-06-15 20:11:59',6),
(474,761,4,'33782962906','Bonjour, saber, \r\nle devis de votre 213 est disponible. \r\nMontant : 21.00€\r\nConsultez-le ici :\r\n📄 http://Mdgeek.top/suivi.php?id=761\r\nUne question ? Appelez-nous au 04 93 46 71 63\r\nMAISON DU GEEK\n\nDétails techniques:\n21','2025-06-15 20:12:01',6),
(475,754,11,'33782962906','🎉 tet,\r\nTon test est de retour à la maison ! On espère qu’il est content 🤓\r\n💬 Laisse-nous un petit avis !\r\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\r\n🏠 Maison du Geek\r\n📞 08 95 79 59 33\r\n','2025-06-15 20:48:37',11),
(476,762,1,'+33782962906','Bonjour tet, votre réparation #762 a été enregistrée. Appareil: Trottinette asd. Prix estimé: 2,00€. Nous vous tiendrons informé de l\'avancement.','2025-06-16 23:07:24',NULL);
/*!40000 ALTER TABLE `reparation_sms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reparations`
--

DROP TABLE IF EXISTS `reparations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reparations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `proprietaire` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `fk_reparation_employe` (`employe_id`),
  KEY `parrain_id` (`parrain_id`)
) ENGINE=InnoDB AUTO_INCREMENT=763 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reparations`
--

LOCK TABLES `reparations` WRITE;
/*!40000 ALTER TABLE `reparations` DISABLE KEYS */;
INSERT INTO `reparations` VALUES
(742,505,'Informatique','','test','cannesphones_sample','2025-05-05 20:52:03','2025-06-09 00:04:20',NULL,'restitue',11,1,NULL,NULL,'',NULL,NULL,'',NULL,213.00,NULL,0,0,'NON',6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(744,512,'Trottinette','','ss','cannesphones_sample','2025-05-05 23:00:13','2025-06-09 00:04:30',NULL,'restitue',NULL,1,NULL,NULL,'',NULL,NULL,'',NULL,2.00,NULL,0,0,'NON',6,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(745,512,'Informatique','','sa','cannesphones_sample','2025-05-07 17:31:22','2025-06-09 00:04:30',NULL,'en_attente_accord_client',6,1,NULL,NULL,'',NULL,NULL,'',NULL,21.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(746,505,'Trottinette','','asd','cannesphones_sample','2025-05-07 17:32:14','2025-06-09 00:04:30',NULL,'restitue',9,1,NULL,NULL,'',NULL,NULL,'',NULL,2.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(747,505,'Trottinette','','asd','cannesphones_sample','2025-05-07 17:41:38','2025-06-15 20:22:23',NULL,'en_attente_accord_client',6,1,NULL,NULL,'',NULL,'assets/images/reparations/repair_681b9b52206d3.jpg','',NULL,2.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(748,505,'Informatique','','ds','cannesphones_sample','2025-05-07 17:46:37','2025-06-09 00:04:30',NULL,'annule',13,1,NULL,NULL,'dtgdgf',NULL,NULL,'',NULL,2.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(750,506,'Trottinette','','0782962906','cannesphones_sample','2025-05-07 17:51:30','2025-06-09 00:04:30',NULL,'restitue',9,1,NULL,NULL,'',NULL,NULL,'',NULL,2.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(751,506,'Trottinette','','0782962906','cannesphones_sample','2025-05-07 17:53:39','2025-06-09 00:04:30',NULL,'restitue',9,1,NULL,NULL,'',NULL,NULL,'',NULL,2.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(752,506,'Trottinette','','test','cannesphones_sample','2025-05-07 17:54:53','2025-06-09 00:04:30',NULL,'restitue',9,1,NULL,NULL,'',NULL,NULL,'',NULL,2.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(753,506,'Informatique','','cdsjd','cannesphones_sample','2025-05-07 17:56:04','2025-06-09 22:05:45',NULL,'En cours de diagnostique',1,1,NULL,NULL,'',NULL,NULL,'',NULL,2.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(754,505,'Trottinette','','test','cannesphones_sample','2025-05-07 17:57:04','2025-06-15 20:48:37',NULL,'restitue',11,1,NULL,NULL,'',NULL,NULL,'',NULL,3.00,NULL,0,0,'NON',NULL,'2025-05-07',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(755,524,'Trottinette','','test','cannesphones_sample','2025-05-07 20:21:48','2025-06-15 19:49:54',NULL,'termine',15,1,NULL,NULL,'and',NULL,NULL,'',NULL,50.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(756,524,'Trottinette','','asd','cannesphones_sample','2025-06-04 23:26:15','2025-06-10 00:07:03',NULL,'Réparation Effectuée',NULL,1,NULL,NULL,'',NULL,'assets/images/reparations/repair_6840d617b4360.jpg','',NULL,21.00,NULL,0,1,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(757,525,'Informatique','','sab','cannesphones_sample','2025-06-07 22:13:43','2025-06-09 21:57:31',NULL,'Nouvelle Commande',NULL,1,NULL,NULL,'',NULL,NULL,'',NULL,20.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(759,522,'Trottinette','','cannestest','cannestest','2025-06-09 21:56:31','2025-06-09 23:58:22',NULL,'Nouvelle Intervention',NULL,1,NULL,NULL,'',NULL,'assets/images/reparations/repair_6847588f1345c.jpg','',NULL,90.00,NULL,0,1,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(760,522,'Trottinette','','saaa','Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREIL','2025-06-11 20:37:07','2025-06-15 19:30:24',NULL,'annule',9,1,NULL,NULL,'',NULL,NULL,'',NULL,21.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(761,524,'Trottinette','','213','Cycle : PRECISEZ_AVEC_OU_SANS_CHAMBRE_ET_PRECISEZ_LE_TYPE_ET_LA_TAILLE_DU_PNEU','2025-06-15 14:58:07','2025-06-25 20:25:41',NULL,'en_attente_accord_client',6,1,NULL,NULL,'',NULL,NULL,'',NULL,25.00,NULL,1,1,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0),
(762,505,'Trottinette','','asd','Electronique : MERCI_D_INDIQUER_DE_FACON_CLAIRE_ET_PRECISE_LE_PROBLEME_DE_L_APPAREILads','2025-06-16 23:07:23','2025-06-16 23:07:23',NULL,'nouvelle_intervention',NULL,1,NULL,NULL,'',NULL,NULL,'',NULL,2.00,NULL,0,0,'NON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0);
/*!40000 ALTER TABLE `reparations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `retours`
--

DROP TABLE IF EXISTS `retours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `retours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produit_id` int(11) NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_limite` date NOT NULL,
  `statut` enum('en_attente','en_preparation','expedie','livre','a_verifier','termine') DEFAULT 'en_attente',
  `numero_suivi` varchar(100) DEFAULT NULL,
  `montant_rembourse` decimal(10,2) DEFAULT NULL,
  `montant_rembourse_client` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `colis_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `produit_id` (`produit_id`),
  KEY `statut` (`statut`),
  KEY `date_limite` (`date_limite`),
  KEY `colis_id` (`colis_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `retours`
--

LOCK TABLES `retours` WRITE;
/*!40000 ALTER TABLE `retours` DISABLE KEYS */;
/*!40000 ALTER TABLE `retours` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scheduled_notifications`
--

DROP TABLE IF EXISTS `scheduled_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `scheduled_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheduled_datetime` (`scheduled_datetime`),
  KEY `status` (`status`),
  KEY `target_user_id` (`target_user_id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scheduled_notifications`
--

LOCK TABLES `scheduled_notifications` WRITE;
/*!40000 ALTER TABLE `scheduled_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `scheduled_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services_partenaires`
--

DROP TABLE IF EXISTS `services_partenaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `services_partenaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partenaire_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_service` datetime DEFAULT current_timestamp(),
  `statut` enum('EN_ATTENTE','VALIDÉ','ANNULÉ') DEFAULT 'EN_ATTENTE',
  PRIMARY KEY (`id`),
  KEY `partenaire_id` (`partenaire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services_partenaires`
--

LOCK TABLES `services_partenaires` WRITE;
/*!40000 ALTER TABLE `services_partenaires` DISABLE KEYS */;
/*!40000 ALTER TABLE `services_partenaires` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_admins`
--

DROP TABLE IF EXISTS `shop_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `shop_id` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_admins`
--

LOCK TABLES `shop_admins` WRITE;
/*!40000 ALTER TABLE `shop_admins` DISABLE KEYS */;
/*!40000 ALTER TABLE `shop_admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shops`
--

DROP TABLE IF EXISTS `shops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shops` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'France',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `subdomain` varchar(50) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `db_host` varchar(255) NOT NULL,
  `db_port` varchar(10) DEFAULT '3306',
  `db_name` varchar(100) NOT NULL,
  `db_user` varchar(100) NOT NULL,
  `db_pass` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subdomain` (`subdomain`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shops`
--

LOCK TABLES `shops` WRITE;
/*!40000 ALTER TABLE `shops` DISABLE KEYS */;
INSERT INTO `shops` VALUES
(1,'Magasin Principal','Magasin existant migré vers le système multi-magasin',NULL,NULL,NULL,'France',NULL,NULL,NULL,NULL,NULL,1,'2025-05-02 20:26:26','2025-05-02 21:34:11','srv931.hstgr.io','3306','u139954273_Vscodetest','u139954273_Vscodetest','Maman01#'),
(2,'PScannes','','','','','France','','','',NULL,'shop_2_1746221300.png',1,'2025-05-02 21:28:20','2025-05-02 21:34:29','srv931.hstgr.io','3306','u139954273_Vscodetest','u139954273_Vscodetest','Maman01#'),
(4,'cannesphones','cannesphones','','','','France','','','',NULL,NULL,1,'2025-05-02 22:41:55','2025-06-15 01:00:29','191.96.63.103','3306','u139954273_cannesphones','u139954273_cannesphones','Merguez01#');
/*!40000 ALTER TABLE `shops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_campaign_details`
--

DROP TABLE IF EXISTS `sms_campaign_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_campaign_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `statut` enum('envoyé','échec') NOT NULL DEFAULT 'envoyé',
  `date_envoi` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_campaign_details`
--

LOCK TABLES `sms_campaign_details` WRITE;
/*!40000 ALTER TABLE `sms_campaign_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_campaign_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_campaigns`
--

DROP TABLE IF EXISTS `sms_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` timestamp NULL DEFAULT current_timestamp(),
  `nb_destinataires` int(11) NOT NULL DEFAULT 0,
  `nb_envoyes` int(11) NOT NULL DEFAULT 0,
  `nb_echecs` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_campaigns`
--

LOCK TABLES `sms_campaigns` WRITE;
/*!40000 ALTER TABLE `sms_campaigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_deduplication`
--

DROP TABLE IF EXISTS `sms_deduplication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_deduplication` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone_hash` varchar(64) NOT NULL,
  `message_hash` varchar(64) NOT NULL,
  `status_id` int(11) DEFAULT NULL,
  `repair_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_phone_message` (`phone_hash`,`message_hash`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_deduplication`
--

LOCK TABLES `sms_deduplication` WRITE;
/*!40000 ALTER TABLE `sms_deduplication` DISABLE KEYS */;
INSERT INTO `sms_deduplication` VALUES
(2,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','1378d7919aac5157bcda25ee46943f9f2920b48aca578171028db9226cb61fd8',NULL,NULL,'2025-06-15 19:15:15'),
(3,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','1378d7919aac5157bcda25ee46943f9f2920b48aca578171028db9226cb61fd8',NULL,NULL,'2025-06-15 19:19:38'),
(4,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','1378d7919aac5157bcda25ee46943f9f2920b48aca578171028db9226cb61fd8',NULL,NULL,'2025-06-15 19:23:11'),
(5,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','101c66bff541a9961326a57129ef2f8894bf036ca3f61721b01229b017941c30',NULL,NULL,'2025-06-15 19:24:12'),
(6,'02d20bbd7e394ad5999a4cebabac9619732c343a4cac99470c03e23ba2bdc2bc','21669c15105d94c85ecd07d678354a09d0532b0843afd008dae5e54810aa20a5',NULL,NULL,'2025-06-15 19:27:18'),
(7,'02d20bbd7e394ad5999a4cebabac9619732c343a4cac99470c03e23ba2bdc2bc','6f1813f4548b230858543037ce2d42f7695f19c91876a284f735be24caab3d4e',NULL,NULL,'2025-06-15 19:30:25'),
(8,'aff2cd35edf99a914acfe4212fc2f704fff8b950466ecb2e1f1fe700ecf77b85','72a9e46ff9c23c77872c1868298a119ad2f09fa38a0b148e44a9b4bff15d6cbe',NULL,NULL,'2025-06-15 19:31:38'),
(9,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','101c66bff541a9961326a57129ef2f8894bf036ca3f61721b01229b017941c30',NULL,NULL,'2025-06-15 19:34:37'),
(10,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','1378d7919aac5157bcda25ee46943f9f2920b48aca578171028db9226cb61fd8',NULL,NULL,'2025-06-15 19:34:37'),
(11,'aff2cd35edf99a914acfe4212fc2f704fff8b950466ecb2e1f1fe700ecf77b85','72a9e46ff9c23c77872c1868298a119ad2f09fa38a0b148e44a9b4bff15d6cbe',NULL,NULL,'2025-06-15 19:34:37'),
(12,'aff2cd35edf99a914acfe4212fc2f704fff8b950466ecb2e1f1fe700ecf77b85','72a9e46ff9c23c77872c1868298a119ad2f09fa38a0b148e44a9b4bff15d6cbe',NULL,NULL,'2025-06-15 19:38:02'),
(13,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','e99b981defc753ac4d834e28bde3ea0828b7e3fbf68a8b4c8dfb916f9505eed0',NULL,NULL,'2025-06-15 19:39:36'),
(14,'aff2cd35edf99a914acfe4212fc2f704fff8b950466ecb2e1f1fe700ecf77b85','f7b2d574eee0a285b36b19206e76a3c728d26da9292bfc66dfea11eef4f91a00',NULL,NULL,'2025-06-15 19:39:37'),
(15,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','cada72596ca6b2bbe72c6af02c5368757001fca6409214aca6e9e37ce636bd00',755,NULL,'2025-06-15 19:42:07'),
(16,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','97f6f438765a224490a757275d7f50f3e4fea3996d620fb2b5870afd7df45bc2',755,NULL,'2025-06-15 19:49:54'),
(17,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','a2e5efaacbf88bd2f32ef56f5831c02ccbc9b96e368ba88cb3daf9aeab4cf6e4',NULL,NULL,'2025-06-15 20:11:59'),
(18,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','fedbd8caf057bd64f1165ad44b437c09bc2672fae593dc908c45239bc831095d',NULL,NULL,'2025-06-15 20:34:48'),
(19,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','21f55fe901a09606ac6a4c45b6a139375699cfcdd76c6cdc097f93522923be9a',NULL,NULL,'2025-06-15 20:47:52'),
(20,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','fedbd8caf057bd64f1165ad44b437c09bc2672fae593dc908c45239bc831095d',NULL,NULL,'2025-06-15 20:48:02'),
(21,'1a616ec1c06322792b6f752c1a384822992d1a283a4ba5ac97d7dc124c0f7e44','101c66bff541a9961326a57129ef2f8894bf036ca3f61721b01229b017941c30',754,NULL,'2025-06-15 20:48:37');
/*!40000 ALTER TABLE `sms_deduplication` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_logs`
--

DROP TABLE IF EXISTS `sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` int(11) DEFAULT NULL,
  `reparation_id` int(11) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `date_envoi` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_date` (`date_envoi`)
) ENGINE=InnoDB AUTO_INCREMENT=452 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_logs`
--

LOCK TABLES `sms_logs` WRITE;
/*!40000 ALTER TABLE `sms_logs` DISABLE KEYS */;
INSERT INTO `sms_logs` VALUES
(449,'+33782962906','Bonjour, asd, \r\nle devis de votre 0782962906 est disponible. \r\nMontant : 4.00€\r\nConsultez-le ici :\r\n📄 http://Mdgeek.top/suivi.php?id=740\r\nUne question ? Appelez-nous au 04 93 46 71 63\r\nMAISON DU GEEK',200,NULL,'{\"id\":\"ZJ5ypCjL7l-N795B8Fv2E\",\"state\":\"Pending\",\"isHashed\":false,\"isEncrypted\":false,\"recipients\":[{\"phoneNumber\":\"+33782962906\",\"state\":\"Pending\"}],\"states\":null}','2025-05-05 19:52:15'),
(450,'+33782962906','Bonjour, asd, \r\nle devis de votre 0782962906 est disponible. \r\nMontant : 4.00€\r\nConsultez-le ici :\r\n📄 http://Mdgeek.top/suivi.php?id=740\r\nUne question ? Appelez-nous au 04 93 46 71 63\r\nMAISON DU GEEK',200,NULL,'{\"id\":\"8uD_c-hMGAOT_pJ-xcmYI\",\"state\":\"Pending\",\"isHashed\":false,\"isEncrypted\":false,\"recipients\":[{\"phoneNumber\":\"+33782962906\",\"state\":\"Pending\"}],\"states\":null}','2025-05-05 20:11:49'),
(451,'+337829626906','Bonjour, sa, \r\nle devis de votre sad est disponible. \r\nMontant : 21.00€\r\nConsultez-le ici :\r\n📄 http://Mdgeek.top/suivi.php?id=743\r\nUne question ? Appelez-nous au 04 93 46 71 63\r\nMAISON DU GEEK',400,NULL,'{\"message\":\"invalid phone number\"}','2025-05-05 21:04:53');
/*!40000 ALTER TABLE `sms_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_template`
--

DROP TABLE IF EXISTS `sms_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `statut_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `statut_id` (`statut_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_template`
--

LOCK TABLES `sms_template` WRITE;
/*!40000 ALTER TABLE `sms_template` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_template` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_template_variables`
--

DROP TABLE IF EXISTS `sms_template_variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_template_variables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `exemple` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_template_variables`
--

LOCK TABLES `sms_template_variables` WRITE;
/*!40000 ALTER TABLE `sms_template_variables` DISABLE KEYS */;
INSERT INTO `sms_template_variables` VALUES
(1,'CLIENT_NOM','Nom du client','Dupont'),
(2,'CLIENT_PRENOM','Prénom du client','Jean'),
(3,'CLIENT_TELEPHONE','Numéro de téléphone du client','+33612345678'),
(4,'REPARATION_ID','Numéro de la réparation','1234'),
(5,'APPAREIL_TYPE','Type d\'appareil','Téléphone'),
(6,'APPAREIL_MARQUE','Marque de l\'appareil','Samsung'),
(7,'APPAREIL_MODELE','Modèle de l\'appareil','Galaxy S21'),
(8,'DATE_RECEPTION','Date de réception de l\'appareil','01/01/2023'),
(9,'DATE_FIN_PREVUE','Date de fin prévue','15/01/2023'),
(10,'PRIX','Prix de la réparation','59.90€');
/*!40000 ALTER TABLE `sms_template_variables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_templates`
--

DROP TABLE IF EXISTS `sms_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `contenu` text NOT NULL,
  `statut_id` int(11) DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_statut` (`statut_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_templates`
--

LOCK TABLES `sms_templates` WRITE;
/*!40000 ALTER TABLE `sms_templates` DISABLE KEYS */;
INSERT INTO `sms_templates` VALUES
(1,'Réparation en cours','Votre [APPAREIL_MODELE] est entre de bonnes mains ! Nos tournevis chauffent, les pixels tremblent. 🧑‍🔧\r\n🔍 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33',5,1,'2025-04-09 23:53:13','2025-04-17 23:46:21'),
(2,'Réparation terminée','[CLIENT_PRENOM], votre [APPAREIL_MODELE] est prêt ! Il vous attend pour retrouver une vie normale. 🥳\r\n🧾 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33',9,1,'2025-04-09 23:53:13','2025-04-17 23:46:07'),
(3,'En attente de pièces','📦 En attente de pièces\r\nVotre [APPAREIL_MODELE] attend sa livraison. Le livreur est en route à dos de modem. ⏳\r\n🚚 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33',7,1,'2025-04-09 23:53:13','2025-04-17 23:35:57'),
(4,'En attente de validation','Bonjour, [CLIENT_PRENOM], \nLe devis de votre [APPAREIL_MODELE] est disponible. \nMontant : [PRIX]\nConsultez votre devis detaillé ici :\n📄 http://Mdgeek.fr/suivi.php?id=[REPARATION_ID]\nUne question ? Appelez-nous au 04 93 46 71 63\nMAISON DU GEEK',6,1,'2025-04-09 23:53:13','2025-06-25 20:15:09'),
(5,'Nouvelle reparation','👋 Bonjour [CLIENT_PRENOM],\r\n🛠️ Nous avons bien reçu votre [APPAREIL_MODELE] et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\r\n🔎 Suivez l\'avancement de la réparation ici :\r\n👉 http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\n📞 Une question ? Contactez nous au 08 95 79 59 33\r\n🏠 Maison du GEEK 🛠️',1,1,'2025-04-09 23:53:27','2025-04-18 01:29:26'),
(6,'Nouvelle Intervention','👋 Bonjour [CLIENT_PRENOM],\r\n🛠️ Nous avons bien reçu votre [APPAREIL_MODELE] et nos experts geeks sont déjà à l\'œuvre pour le remettre en état.\r\n🔎 Suivez l\'avancement de la réparation ici :\r\n👉 http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\n📞 Une question ? Contactez nous au 08 95 79 59 33\r\n🏠 Maison du GEEK 🛠️',2,1,'2025-04-10 02:15:50','2025-04-18 01:29:19'),
(7,'Nouvelle Commande','MD Geek: Commande enregistrée pour votre [APPAREIL_MARQUE] . Ref:[REPARATION_ID]. Suivez l\'état de votre commande: mdgeek.top/suivi.php?id=[REPARATION_ID]',3,1,'2025-04-10 02:15:55','2025-04-18 01:20:57'),
(8,'En cours de diagnostique','👋 Hello [CLIENT_PRENOM],\r\nVotre devis pour le 📱 [APPAREIL_MODELE] est prêt !\r\n🧐 Consultez-le ici :\r\n🔗 http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\n📞 Une question ? Appelez-nous au ☎️ 04 93 46 71 63\r\n🏠 Maison du Geek – 📱 08 95 79 59 33',4,1,'2025-04-10 02:16:00','2025-04-18 01:17:54'),
(9,'En attente d\'un responsable','Bonjour [CLIENT_PRENOM], votre dossier [REPARATION_ID] au sujet de votre [APPAREIL_MODELE] est en attente de validation par un responsable technique. Nous vous tenons informé très bientôt.\r\n📲 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33',8,1,'2025-04-10 02:16:06','2025-04-17 23:36:28'),
(10,'Réparation Annulée','Nous avons tout essayé pour sauver votre [APPAREIL_MODELE] ([APPAREIL_MARQUE]), mais pour des raisons techniques, nous avons dû annuler la réparation.\r\n📄 Détails : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33',10,1,'2025-04-10 02:16:12','2025-04-17 23:46:00'),
(11,'Restitué','🎉 [CLIENT_PRENOM],\r\nTon [APPAREIL_MODELE] est de retour à la maison ! On espère qu’il est content 🤓\r\n💬 Laisse-nous un petit avis !\r\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\r\n🏠 Maison du Geek\r\n📞 08 95 79 59 33\r\n',11,1,'2025-04-10 02:16:18','2025-04-18 01:23:35'),
(12,'Gardiennage','📣 [CLIENT_PRENOM],\r\nTon [APPAREIL_MODELE] est prêt mais t’attend toujours ! Des frais de gardiennage s’appliquent 🪙 et après 90j il sera recyclé gratuitement ♻️\r\n📍 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\n📞 08 95 79 59 33\r\n🏠 Maison du Geek',12,1,'2025-04-10 02:16:24','2025-04-18 01:23:12'),
(13,'Annulé','😔 [CLIENT_PRENOM],\r\nOn a tout tenté pour réparer ton [APPAREIL_MODELE], mais pour raisons techniques, on a dû annuler la réparation.\r\n🔍 Détails : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\n📞 Une Question ?  08 95 79 59 33\r\n🏠 Maison du Geek',13,1,'2025-04-10 02:16:29','2025-04-18 01:22:43'),
(15,'Terminé','[CLIENT_PRENOM], on espère que ton [APPAREIL_MODELE] se porte comme un charme ! 😊 Aide nos Geeks avec un petit avis :\r\n⭐ https://g.page/r/Ce-HHwKZjezIEB0/review\r\n📲 Suivi : http://Mdgeek.top/suivi.php?id=[REPARATION_ID]\r\nMaison du Geek – 08 95 79 59 33',15,1,'2025-04-10 02:16:51','2025-04-17 23:46:25'),
(17,'Relance client','Bonjour [CLIENT_PRENOM],\nVotre [APPAREIL_TYPE] [APPAREIL_MARQUE] [APPAREIL_MODELE] est réparé et attend votre visite à la boutique.\nMaison du Geek - 08 95 79 59 33',NULL,1,'2025-04-23 00:14:29','2025-04-23 00:14:29');
/*!40000 ALTER TABLE `sms_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soldes_partenaires`
--

DROP TABLE IF EXISTS `soldes_partenaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `soldes_partenaires` (
  `partenaire_id` int(11) NOT NULL,
  `solde_actuel` decimal(10,2) DEFAULT 0.00,
  `derniere_mise_a_jour` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`partenaire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soldes_partenaires`
--

LOCK TABLES `soldes_partenaires` WRITE;
/*!40000 ALTER TABLE `soldes_partenaires` DISABLE KEYS */;
INSERT INTO `soldes_partenaires` VALUES
(2,-94.00,'2025-04-21 01:18:29');
/*!40000 ALTER TABLE `soldes_partenaires` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statut_categories`
--

DROP TABLE IF EXISTS `statut_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `statut_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `couleur` varchar(20) NOT NULL,
  `ordre` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statut_categories`
--

LOCK TABLES `statut_categories` WRITE;
/*!40000 ALTER TABLE `statut_categories` DISABLE KEYS */;
INSERT INTO `statut_categories` VALUES
(1,'Nouvelle','nouvelle','info',1),
(2,'En cours','en_cours','primary',2),
(3,'En attente','en_attente','warning',3),
(4,'Terminé','termine','success',4),
(5,'Annulé','annule','danger',5),
(6,'Archivé','archive','secondary',6);
/*!40000 ALTER TABLE `statut_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statuts`
--

DROP TABLE IF EXISTS `statuts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `statuts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `ordre` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `categorie_id` (`categorie_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statuts`
--

LOCK TABLES `statuts` WRITE;
/*!40000 ALTER TABLE `statuts` DISABLE KEYS */;
INSERT INTO `statuts` VALUES
(1,'Nouveau Diagnostique','nouveau_diagnostique',1,1,1),
(2,'Nouvelle Intervention','nouvelle_intervention',1,1,2),
(3,'Nouvelle Commande','nouvelle_commande',1,1,3),
(4,'En cours de diagnostique','en_cours_diagnostique',2,1,1),
(5,'En cours d\'intervention','en_cours_intervention',2,1,2),
(6,'En attente de l\'accord client','en_attente_accord_client',3,1,1),
(7,'En attente de livraison','en_attente_livraison',3,1,2),
(8,'En attente d\'un responsable','en_attente_responsable',3,1,3),
(9,'Réparation Effectuée','reparation_effectue',4,1,1),
(10,'Réparation Annulée','reparation_annule',4,1,2),
(11,'Restitué','restitue',5,1,1),
(12,'Gardiennage','gardiennage',5,1,2),
(13,'Annulé','annule',5,1,3),
(14,'Archivé','archive',6,1,1),
(15,'Terminé','termine',3,1,0);
/*!40000 ALTER TABLE `statuts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statuts_reparation`
--

DROP TABLE IF EXISTS `statuts_reparation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `statuts_reparation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `categorie` enum('nouvelle','en_cours','en_attente','termine','annule') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statuts_reparation`
--

LOCK TABLES `statuts_reparation` WRITE;
/*!40000 ALTER TABLE `statuts_reparation` DISABLE KEYS */;
INSERT INTO `statuts_reparation` VALUES
(1,NULL,'nouveau_diagnostique','Nouveau Diagnostique','nouvelle','2025-03-23 19:09:51'),
(2,NULL,'nouvelle_intervention','Nouvelle Intervention','nouvelle','2025-03-23 19:09:51'),
(3,NULL,'nouvelle_commande','Nouvelle Commande','nouvelle','2025-03-23 19:09:51'),
(4,NULL,'en_cours_diagnostique','En cours de diagnostique','en_cours','2025-03-23 19:09:51'),
(5,NULL,'en_cours_intervention','En cours d\'intervention','en_cours','2025-03-23 19:09:51'),
(6,NULL,'en_attente_accord_client','En attente de l\'accord client','en_attente','2025-03-23 19:09:51'),
(7,NULL,'en_attente_livraison','En attente de livraison','en_attente','2025-03-23 19:09:51'),
(8,NULL,'en_attente_responsable','En attente d\'un responsable','en_attente','2025-03-23 19:09:51'),
(9,NULL,'reparation_effectue','Réparation Effectuée','termine','2025-03-23 19:09:51'),
(10,NULL,'reparation_annule','Réparation Annulée','termine','2025-03-23 19:09:51'),
(11,NULL,'restitue','Restitué','annule','2025-03-23 19:09:51'),
(12,NULL,'gardiennage','Gardiennage','annule','2025-03-23 19:09:51'),
(13,NULL,'annule','Annulé','annule','2025-03-23 19:09:51');
/*!40000 ALTER TABLE `statuts_reparation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock`
--

DROP TABLE IF EXISTS `stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `motif_retour` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock`
--

LOCK TABLES `stock` WRITE;
/*!40000 ALTER TABLE `stock` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_history`
--

DROP TABLE IF EXISTS `stock_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `action` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_history`
--

LOCK TABLES `stock_history` WRITE;
/*!40000 ALTER TABLE `stock_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `superadmins`
--

DROP TABLE IF EXISTS `superadmins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `superadmins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `superadmins`
--

LOCK TABLES `superadmins` WRITE;
/*!40000 ALTER TABLE `superadmins` DISABLE KEYS */;
INSERT INTO `superadmins` VALUES
(1,'superadmin','$2y$10$Qd6qnRp4F54lQapw.keRyOE6BVCzurkc6IbjliVNAaFwsIwDZnWD6','Super Administrateur','admin@geekboard.com',1,'2025-05-02 20:26:26');
/*!40000 ALTER TABLE `superadmins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taches`
--

DROP TABLE IF EXISTS `taches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `taches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priorite` enum('basse','moyenne','haute','urgente') DEFAULT 'moyenne',
  `statut` enum('a_faire','en_cours','termine','annule') DEFAULT 'a_faire',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_limite` date DEFAULT NULL,
  `date_fin` timestamp NULL DEFAULT NULL,
  `employe_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `taches_ibfk_1` (`employe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taches`
--

LOCK TABLES `taches` WRITE;
/*!40000 ALTER TABLE `taches` DISABLE KEYS */;
INSERT INTO `taches` VALUES
(28,'cannesphones_sample','test','moyenne','termine','2025-05-15 18:37:17',NULL,'2025-06-15 23:23:53',6,6),
(29,'cannesphones_sample','ceci est un tetsceci est un tetsceci est un tetsceci est un tetsceci est un tetsceci est un tetsceci est un tetsceci est un tetsceci est un tetsceci est un tetsceci est un tetsceci est un tetsceci est un tetsceci est un tets','basse','termine','2025-06-07 19:08:36',NULL,'2025-06-15 23:32:27',6,6),
(30,'test','test','moyenne','termine','2025-06-15 22:19:09',NULL,'2025-06-15 22:43:33',NULL,6);
/*!40000 ALTER TABLE `taches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('en_attente','en_cours','termine','aide_necessaire') DEFAULT 'en_attente',
  `priority` enum('basse','moyenne','haute','urgente') DEFAULT 'moyenne',
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `due_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `theme_management`
--

DROP TABLE IF EXISTS `theme_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `theme_management` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `css_file` varchar(255) DEFAULT NULL,
  `js_file` varchar(255) DEFAULT NULL,
  `preview_image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `is_default` tinyint(1) DEFAULT 0,
  `supports_dark_mode` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `theme_management`
--

LOCK TABLES `theme_management` WRITE;
/*!40000 ALTER TABLE `theme_management` DISABLE KEYS */;
INSERT INTO `theme_management` VALUES
(1,'ios26-liquid-glass','iOS 26 Liquid Glass','Thème futuriste inspiré d\'iOS 26 avec effets Liquid Glass','assets/css/ios26-liquid-glass.css','assets/js/ios26-theme-manager.js',NULL,0,0,1,'2025-06-16 23:34:45','2025-06-16 23:34:45'),
(2,'modern-theme','Thème Moderne','Design contemporain avec support jour/nuit','assets/css/modern-theme.css',NULL,NULL,0,1,1,'2025-06-16 23:34:45','2025-06-16 23:34:45'),
(3,'dark-theme','Thème Sombre','Interface sombre pour un confort visuel optimal','assets/css/dark-theme.css',NULL,NULL,0,0,1,'2025-06-16 23:34:45','2025-06-16 23:34:45'),
(4,'classic','Thème Classique','Design original de GeekBoard',NULL,NULL,NULL,0,0,1,'2025-06-16 23:34:45','2025-06-16 23:34:45');
/*!40000 ALTER TABLE `theme_management` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transactions_partenaires`
--

DROP TABLE IF EXISTS `transactions_partenaires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions_partenaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partenaire_id` int(11) NOT NULL,
  `type` enum('AVANCE','REMBOURSEMENT','SERVICE') NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `date_transaction` datetime DEFAULT current_timestamp(),
  `reference_document` varchar(255) DEFAULT NULL,
  `statut` enum('EN_ATTENTE','VALIDÉ','ANNULÉ') DEFAULT 'EN_ATTENTE',
  PRIMARY KEY (`id`),
  KEY `fk_transactions_partenaires` (`partenaire_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions_partenaires`
--

LOCK TABLES `transactions_partenaires` WRITE;
/*!40000 ALTER TABLE `transactions_partenaires` DISABLE KEYS */;
INSERT INTO `transactions_partenaires` VALUES
(19,2,'REMBOURSEMENT',89.00,'Retour de prêt d\'un LCD iphone 11','2025-04-21 00:45:31',NULL,'EN_ATTENTE'),
(22,2,'REMBOURSEMENT',10.00,'Retour de prêt d\'un LCD iphone 11','2025-04-21 01:18:29',NULL,'EN_ATTENTE');
/*!40000 ALTER TABLE `transactions_partenaires` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `typing_status`
--

DROP TABLE IF EXISTS `typing_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `typing_status` (
  `user_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`user_id`,`conversation_id`),
  KEY `conversation_id` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `typing_status`
--

LOCK TABLES `typing_status` WRITE;
/*!40000 ALTER TABLE `typing_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `typing_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `token` (`token`),
  KEY `expiry` (`expiry`)
) ENGINE=InnoDB AUTO_INCREMENT=600 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
INSERT INTO `user_sessions` VALUES
(1,1,'11dee6a1ece1222631b78a9bdd5ebf863959bafc431453e7aad5760421139894','2025-05-05 20:28:57','2025-04-11 15:25:02',NULL,NULL),
(353,2,'9271c14ff9cf1babc3babe23f2b240e9091198a0762368cb883380f6431f4d75','2025-04-25 22:29:26','2025-04-22 08:10:09',NULL,NULL),
(359,3,'ef8e240f3484df1142ea80017a057dcc807a11d4f72b7cbe75e91b15995824b5','2025-05-03 08:49:38','2025-04-22 12:33:39',NULL,NULL),
(488,6,'992cded6bc4bc220c4da5c27a8232b729b1c321eaaa491f1ffbc79e186f20d30','2025-06-29 18:18:46','2025-05-04 23:49:40',NULL,NULL);
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_theme_preferences`
--

DROP TABLE IF EXISTS `user_theme_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_theme_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `theme_id` int(11) DEFAULT NULL,
  `dark_mode_enabled` tinyint(1) DEFAULT 0,
  `auto_switch_enabled` tinyint(1) DEFAULT 0,
  `custom_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_settings`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_theme` (`user_id`),
  KEY `theme_id` (`theme_id`),
  CONSTRAINT `user_theme_preferences_ibfk_1` FOREIGN KEY (`theme_id`) REFERENCES `theme_management` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_theme_preferences`
--

LOCK TABLES `user_theme_preferences` WRITE;
/*!40000 ALTER TABLE `user_theme_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_theme_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','technicien') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `techbusy` int(11) DEFAULT 0,
  `active_repair_id` int(11) DEFAULT NULL,
  `shop_id` int(11) DEFAULT NULL,
  `score_total` int(11) DEFAULT 0,
  `niveau` int(11) DEFAULT 1,
  `points_experience` int(11) DEFAULT 0,
  `derniere_activite` datetime DEFAULT NULL,
  `statut_presence` enum('present','absent','pause','mission_externe') DEFAULT 'absent',
  `preference_notifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preference_notifications`)),
  `timezone` varchar(50) DEFAULT 'Europe/Paris',
  `productivity_target` decimal(5,2) DEFAULT 80.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_shop_id` (`shop_id`),
  KEY `idx_score_total` (`score_total`),
  KEY `idx_niveau` (`niveau`),
  KEY `idx_derniere_activite` (`derniere_activite`),
  KEY `idx_statut_presence` (`statut_presence`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'sabera','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Saber','admin','2025-03-20 10:15:29',0,NULL,4,0,1,0,NULL,'absent',NULL,'Europe/Paris',80.00),
(6,'Admin','$2y$10$3JTMop/lAGF.gXQHR2wfBeDF.o84HA3h3W29riqL/lK3d9klKLQq.','Administrateur','admin','2025-05-04 23:49:30',0,NULL,NULL,0,1,0,NULL,'absent',NULL,'Europe/Paris',80.00);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-06-27  0:02:28
