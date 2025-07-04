# Gestionnaire de Base de Données - GeekBoard

## Vue d'ensemble

Le Gestionnaire de Base de Données est une interface web intégrée au superadmin de GeekBoard qui permet d'accéder et de gérer les bases de données des magasins comme avec phpMyAdmin.

## Fonctionnalités principales

### 🏪 Sélection de magasin
- Choisissez parmi tous les magasins configurés
- Connexion automatique à la base de données du magasin sélectionné
- Affichage du statut de connexion en temps réel

### 📊 Navigation des tables
- Liste de toutes les tables disponibles dans la base de données
- Compteur du nombre de tables
- Navigation intuitive par clic

### 🔍 Visualisation des données
- Affichage paginé des données (50 lignes par page)
- Navigation entre les pages
- Compteur du nombre total de lignes
- Affichage responsive des données

### ⚙️ Structure des tables
- Consultation de la structure complète des tables
- Informations sur les colonnes, types, clés, etc.
- Modal dédiée pour la structure

### 💻 Éditeur SQL
- Éditeur de code avec coloration syntaxique
- Autocomplétion et validation
- Raccourcis clavier (Ctrl+Enter pour exécuter)
- Sauvegarde automatique des requêtes

### 📥 Export de données
- Export au format CSV
- Téléchargement direct des données
- Export complet de la table sélectionnée

### 🔒 Sécurité
- Validation des requêtes dangereuses
- Confirmation requise pour les opérations de modification
- Protection contre les injections SQL
- Accès limité aux super administrateurs

## Guide d'utilisation

### 1. Accès à l'interface
1. Connectez-vous au superadmin GeekBoard
2. Cliquez sur "Base de Données" dans le menu de navigation
3. Sélectionnez un magasin dans la liste déroulante

### 2. Navigation des tables
1. Une fois connecté, la liste des tables s'affiche à gauche
2. Cliquez sur une table pour voir son contenu
3. Utilisez les boutons de pagination pour naviguer

### 3. Consultation de la structure
1. Sélectionnez une table
2. Cliquez sur "Structure" dans la barre d'outils
3. Consultez les détails de chaque colonne

### 4. Éditeur SQL
1. Cliquez sur "SQL" dans la liste des tables
2. Saisissez votre requête dans l'éditeur
3. Utilisez Ctrl+Enter ou cliquez sur "Exécuter"
4. Pour les requêtes dangereuses, cochez la case de confirmation

### 5. Export de données
1. Sélectionnez une table
2. Cliquez sur "CSV" pour télécharger les données
3. Le fichier sera automatiquement téléchargé

## Raccourcis clavier

- **Ctrl+Enter** : Exécuter la requête SQL courante
- **Ctrl+S** : Sauvegarder la requête SQL
- **F5** : Exécuter la requête SQL
- **Ctrl+K** : Recherche rapide dans les tables
- **Échap** : Fermer les modals

## Requêtes dangereuses

Les requêtes suivantes sont considérées comme dangereuses et nécessitent une confirmation :
- `DROP` : Suppression de tables/bases
- `DELETE` : Suppression de données
- `TRUNCATE` : Vidage de tables
- `ALTER` : Modification de structure
- `CREATE` : Création d'objets
- `INSERT` : Insertion de données
- `UPDATE` : Modification de données

## Bonnes pratiques

### Sécurité
- ✅ Testez vos requêtes sur un environnement de développement
- ✅ Faites des sauvegardes avant les modifications importantes
- ✅ Utilisez des transactions pour les opérations critiques
- ❌ Ne pas exécuter de requêtes sans comprendre leur impact

### Performance
- ✅ Utilisez LIMIT pour limiter les résultats
- ✅ Évitez SELECT * sur de grandes tables
- ✅ Utilisez des index appropriés
- ❌ Évitez les requêtes lourdes pendant les heures de pointe

### Navigation
- ✅ Utilisez la recherche pour trouver rapidement les tables
- ✅ Sauvegardez vos requêtes fréquemment utilisées
- ✅ Exportez les données avant les modifications

## Dépannage

### Erreur de connexion
- Vérifiez les paramètres de connexion du magasin
- Assurez-vous que la base de données est accessible
- Contactez l'administrateur système si nécessaire

### Requête qui ne s'exécute pas
- Vérifiez la syntaxe SQL
- Assurez-vous d'avoir coché la confirmation pour les requêtes dangereuses
- Vérifiez les permissions sur la base de données

### Données non affichées
- Vérifiez que la table contient des données
- Essayez de rafraîchir la page
- Vérifiez les filtres appliqués

## Limitations

- Affichage limité à 50 lignes par page pour les performances
- Pas d'édition directe des données (sécurité)
- Export limité au format CSV
- Nécessite une connexion super administrateur

## Support technique

Pour toute question ou problème technique :
1. Consultez d'abord cette documentation
2. Vérifiez les logs d'erreur PHP
3. Contactez l'équipe de développement

## Changelog

### Version 1.0
- Interface complète de gestion de base de données
- Éditeur SQL avec coloration syntaxique
- Export CSV
- Sécurité renforcée
- Interface responsive

---

*Gestionnaire de Base de Données GeekBoard - Version 1.0*
