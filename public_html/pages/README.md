# Application de Gestion des Réparations

Cette application PHP permet de gérer les réparations de téléphones, ordinateurs, tablettes et trottinettes électriques pour votre magasin.

## Fonctionnalités

- Gestion des clients
- Suivi des réparations
- Tableau de bord avec statistiques
- Gestion des statuts de réparation

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache, Nginx, etc.)

## Installation

1. Clonez ce dépôt dans votre répertoire web
2. Créez une base de données MySQL
3. Importez le fichier `database.sql` dans votre base de données
4. Configurez les paramètres de connexion à la base de données dans le fichier `config/database.php`
5. Accédez à l'application via votre navigateur

## Configuration

Modifiez le fichier `config/database.php` avec vos informations de connexion à la base de données :

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
define('DB_NAME', 'reparation_shop');
```

## Utilisation

1. Commencez par ajouter des clients
2. Enregistrez les réparations à effectuer
3. Suivez l'état des réparations et mettez à jour leur statut
4. Consultez le tableau de bord pour avoir une vue d'ensemble

## Structure du projet

- `index.php` : Point d'entrée de l'application
- `config/` : Fichiers de configuration
- `includes/` : Fichiers inclus (fonctions, en-tête, pied de page)
- `pages/` : Pages de l'application
- `assets/` : Ressources (CSS, JavaScript, images)
- `database.sql` : Script SQL pour initialiser la base de données

# Mise à jour du système de statuts

## Installation

Pour mettre à jour le système de statuts et utiliser la nouvelle structure en base de données, suivez ces étapes :

1. Exécutez le script SQL pour créer les tables et insérer les données initiales :

```sql
mysql -u USERNAME -p DATABASE_NAME < sql/add_statut_table.sql
```

Remplacez `USERNAME` et `DATABASE_NAME` par vos identifiants de connexion.

## Fonctionnalités ajoutées

Le nouveau système de statuts offre plusieurs avantages :

1. **Gestion centralisée** : Les statuts sont désormais stockés en base de données plutôt que codés en dur dans l'application
2. **Flexibilité accrue** : Vous pouvez facilement ajouter, modifier ou désactiver des statuts sans modifier le code
3. **Interface utilisateur améliorée** : Les statuts sont organisés par catégories pour une meilleure lisibilité
4. **Personnalisation** : Vous pouvez modifier l'ordre d'affichage des statuts et des catégories

## Nouvelles fonctions PHP

De nouvelles fonctions ont été ajoutées pour interagir avec ce système :

- `get_all_statuts()` : Récupère tous les statuts actifs, organisés par catégorie
- `get_statut_by_code($code)` : Récupère les informations détaillées d'un statut spécifique
- `get_status_badge($status_code)` : Génère un badge HTML pour afficher le statut (compatible avec l'ancien système)

## Gestion des statuts

Pour gérer les statuts, vous pouvez :

1. Ajouter un nouveau statut :
```sql
INSERT INTO statuts (nom, code, categorie_id, ordre) 
VALUES ('Nom du statut', 'code_du_statut', ID_CATEGORIE, ORDRE);
```

2. Désactiver un statut sans le supprimer :
```sql
UPDATE statuts SET est_actif = FALSE WHERE code = 'code_du_statut';
```

3. Modifier l'ordre d'affichage :
```sql
UPDATE statuts SET ordre = NOUVEAU_ORDRE WHERE code = 'code_du_statut';
```

4. Ajouter une nouvelle catégorie :
```sql
INSERT INTO statut_categories (nom, code, couleur, ordre)
VALUES ('Nom catégorie', 'code_categorie', 'couleur_bootstrap', ORDRE);
```