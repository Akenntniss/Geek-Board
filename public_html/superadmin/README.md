# GeekBoard - Système Multi-Magasin

## Présentation

Le système multi-magasin de GeekBoard permet de gérer plusieurs points de vente avec une seule installation. Chaque magasin dispose de sa propre base de données séparée, garantissant ainsi une isolation complète des données entre les différents magasins.

## Architecture

### Structure principale

- **Base de données principale**: Contient les informations sur les magasins et les super administrateurs
- **Bases de données des magasins**: Chaque magasin a sa propre base de données avec ses propres clients, réparations, utilisateurs, etc.

### Types d'utilisateurs

1. **Super Administrateur**: Gère la création et la configuration des magasins
2. **Administrateur de magasin**: Gère un magasin spécifique
3. **Technicien**: Travaille dans un magasin spécifique

## Configuration initiale

1. Accédez à `/superadmin/create_shops_table.php` pour créer les tables nécessaires dans la base de données principale
2. Connectez-vous avec les identifiants super administrateur par défaut:
   - Nom d'utilisateur: `superadmin`
   - Mot de passe: `SuperAdmin2024!`
3. Changez immédiatement ce mot de passe par défaut pour des raisons de sécurité!

## Création d'un nouveau magasin

1. Connectez-vous en tant que super administrateur
2. Accédez au tableau de bord d'administration
3. Cliquez sur "Nouveau magasin"
4. Remplissez les informations du magasin:
   - Nom, adresse, contact
   - Informations de connexion à la base de données
5. Après la création, initialisez la base de données du magasin
6. Créez le premier administrateur pour ce magasin

## Bases de données

Chaque magasin nécessite sa propre base de données MySQL/MariaDB. Assurez-vous que:

1. La base de données existe avant de créer le magasin
2. L'utilisateur spécifié a tous les droits sur cette base de données
3. Les informations de connexion sont correctes

## Fonctionnement du système

### Connexion des utilisateurs

- Si aucun magasin n'est sélectionné, l'utilisateur doit choisir un magasin dans la liste
- Les super administrateurs sont toujours redirigés vers le tableau de bord d'administration
- Les utilisateurs normaux (admin/technicien) sont connectés au magasin sélectionné

### Isolation des données

- Les données d'un magasin ne sont jamais accessibles depuis un autre magasin
- Chaque magasin a ses propres paramètres, utilisateurs, clients, etc.
- Seuls les super administrateurs peuvent accéder à tous les magasins

## Maintenance

Pour maintenir le système multi-magasin:

1. Effectuez des sauvegardes régulières de toutes les bases de données
2. Mettez à jour les informations des magasins au besoin
3. Gérez les utilisateurs et leurs droits de manière appropriée

## Sécurité

Pour assurer la sécurité:

1. Utilisez des mots de passe forts pour tous les comptes
2. Limitez l'accès au panneau super administrateur
3. Effectuez des audits réguliers des comptes et des accès
4. Gardez toutes les informations de connexion à la base de données confidentielles

## Support technique

En cas de problème, contactez l'équipe de support technique de GeekBoard. 