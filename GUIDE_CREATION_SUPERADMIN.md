# Guide de Création du Superadmin - GeekBoard

## 📋 Informations de Base de Données

- **Serveur :** 191.96.63.103
- **Utilisateur :** u139954273_Vscodetest  
- **Base de données :** u139954273_Vscodetest
- **Mot de passe :** Maman01#

## 🛠️ Méthodes de Création du Superadmin

### Méthode 1 : Script PHP Automatique (Recommandée)

1. **Téléchargez le fichier** `public_html/superadmin/create_superadmin.php` sur votre serveur
2. **Accédez au script** via votre navigateur :
   ```
   https://votre-domaine.com/superadmin/create_superadmin.php
   ```
3. **Le script va automatiquement :**
   - Se connecter à la base de données
   - Créer les tables `superadmins` et `shops` si elles n'existent pas
   - Créer le superadmin avec les identifiants par défaut
   - Afficher les informations de connexion

4. **Supprimez le script** après utilisation pour la sécurité

### Méthode 2 : Script SQL Direct

1. **Connectez-vous** à votre interface de gestion MySQL (phpMyAdmin, etc.)
2. **Exécutez le script** `create_superadmin.sql` 
3. **Vérifiez** que les tables ont été créées et le superadmin inséré

### Méthode 3 : Génération de Hash Personnalisé

1. **Utilisez le générateur** `generate_password_hash.php` pour créer un hash personnalisé
2. **Copiez la requête SQL** générée
3. **Exécutez-la** dans votre interface MySQL

## 🔑 Informations de Connexion Par Défaut

```
URL de connexion : https://votre-domaine.com/superadmin/login.php
Nom d'utilisateur : superadmin
Mot de passe : Admin123!
Email : admin@geekboard.fr
```

## 🛡️ Sécurité - Actions Obligatoires

### Immédiatement après la création :

1. **Changez le mot de passe** dès la première connexion
2. **Supprimez tous les scripts** de création du serveur :
   - `create_superadmin.php`
   - `generate_password_hash.php` 
   - `create_superadmin.sql`
   - Ce guide `GUIDE_CREATION_SUPERADMIN.md`

3. **Vérifiez les permissions** du dossier `/superadmin/`
4. **Configurez un accès HTTPS** pour l'administration

## 📁 Structure des Tables Créées

### Table `superadmins`
```sql
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Table `shops`
```sql
CREATE TABLE `shops` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `subdomain` varchar(50) NOT NULL,
    `address` text,
    `city` varchar(100),
    `postal_code` varchar(20),
    `country` varchar(100) DEFAULT 'France',
    `phone` varchar(20),
    `email` varchar(100),
    `website` varchar(255),
    `logo` varchar(255),
    `active` tinyint(1) DEFAULT 1,
    `db_host` varchar(255) NOT NULL,
    `db_port` varchar(10) DEFAULT '3306',
    `db_name` varchar(100) NOT NULL,
    `db_user` varchar(100) NOT NULL,
    `db_pass` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    UNIQUE KEY `subdomain` (`subdomain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 🚀 Première Utilisation

### 1. Connexion
1. Accédez à `https://votre-domaine.com/superadmin/login.php`
2. Connectez-vous avec les identifiants par défaut
3. Changez immédiatement le mot de passe

### 2. Création de votre première boutique
1. Cliquez sur "Nouveau magasin"
2. Remplissez les informations de la boutique
3. Configurez la base de données dédiée
4. Définissez le sous-domaine

### 3. Configuration des sous-domaines
1. Utilisez "Configurer les sous-domaines"
2. Suivez les instructions pour la configuration DNS/Apache

## ❗ Dépannage

### Erreur de connexion à la base de données
- Vérifiez les informations de connexion
- Assurez-vous que les connexions externes sont autorisées
- Contactez votre hébergeur si nécessaire

### Page de login inaccessible
- Vérifiez que le dossier `/superadmin/` existe
- Vérifiez les permissions de fichier
- Assurez-vous que PHP fonctionne correctement

### Table déjà existante
- Le script gère automatiquement les tables existantes
- Utilisez la méthode de génération de hash pour un nouveau mot de passe

## 📞 Support

Pour toute assistance supplémentaire, consultez les logs d'erreur de votre serveur ou contactez votre administrateur système.

---

**⚠️ N'oubliez pas de supprimer ce guide et tous les scripts de création après utilisation !** 