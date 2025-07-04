# Page de Landing GeekBoard

## Vue d'ensemble

Une page de landing moderne a été créée pour le domaine principal `mdgeek.top` afin de présenter l'application GeekBoard et permettre aux prospects de nous contacter.

## Architecture

### Fichiers créés/modifiés

1. **`/pages/landing.php`** - Page de landing principale
2. **`/contact_handler.php`** - Traitement du formulaire de contact
3. **`/index.php`** - Modifié pour détecter et afficher la landing page
4. **`/docs/LANDING_PAGE.md`** - Cette documentation

### Logique de détection

La page de landing s'affiche automatiquement quand :
- Le domaine est exactement `mdgeek.top` (domaine principal)
- Aucun `shop_id` n'est défini en session (pas de magasin spécifique)
- L'utilisateur n'est pas un super administrateur

```php
if ($host === 'mdgeek.top' && !isset($_SESSION['shop_id']) && !isset($_SESSION['superadmin_id'])) {
    include __DIR__ . '/pages/landing.php';
    exit;
}
```

## Fonctionnalités de la Landing Page

### Design moderne
- Gradient de couleurs attractif
- Animations CSS au scroll
- Design responsive (mobile/tablet/desktop)
- Effets de hover sur les cartes
- Modal de contact avec animations

### Sections principales
1. **Hero Section** - Message principal avec call-to-action
2. **Fonctionnalités** - 6 cartes présentant les fonctionnalités clés
3. **Call-to-Action** - Section d'incitation au contact
4. **Footer** - Informations de contact et navigation

### Formulaire de contact
- Modal Bootstrap moderne
- Validation côté client et serveur
- Envoi d'email automatique
- Sauvegarde en base de données
- Messages de succès/erreur en temps réel

## Formulaire de contact

### Champs
- **Prénom** (requis)
- **Nom** (requis)
- **Email** (requis, validé)
- **Téléphone** (optionnel)
- **Entreprise** (optionnel)
- **Sujet** (requis, liste déroulante)
- **Message** (requis)

### Traitement
1. Validation des données
2. Envoi d'email à `contact@mdgeek.top`
3. Sauvegarde dans la table `contact_requests`
4. Réponse JSON pour feedback utilisateur

### Sujets disponibles
- Demande de démonstration
- Information tarifs
- Migration depuis autre solution
- Support technique
- Autre

## Base de données

La table `contact_requests` est créée automatiquement :

```sql
CREATE TABLE IF NOT EXISTS contact_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    company VARCHAR(255),
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Configuration

### Email
Modifier dans `/contact_handler.php` :
```php
$to_email = "contact@mdgeek.top"; // Votre email de destination
```

### Personnalisation
- Modifier les couleurs dans les variables CSS `:root`
- Ajuster le contenu dans `/pages/landing.php`
- Personnaliser les sujets du formulaire

## Différenciation avec les sous-domaines

- **`mdgeek.top`** → Page de landing (public, aucune authentification)
- **`magasin.mdgeek.top`** → Application GeekBoard du magasin (authentification requise)
- **Autres sous-domaines** → Applications GeekBoard spécifiques aux magasins

## Avantages

1. **SEO-friendly** - Page optimisée pour les moteurs de recherche
2. **Conversion** - Design orienté conversion avec CTA clairs
3. **Lead generation** - Capture automatique des prospects
4. **Professionnalisme** - Image de marque moderne et professionnelle
5. **Analytics ready** - Prêt pour l'intégration Google Analytics

## Maintenance

- Les demandes de contact sont stockées dans la base de données
- Les logs d'erreur sont enregistrés via `error_log()`
- Le design est entièrement responsive
- Compatible avec tous les navigateurs modernes

## Prochaines étapes recommandées

1. Configurer Google Analytics/GTM
2. Ajouter des témoignages clients
3. Intégrer un système de chat en direct
4. Optimiser le SEO (meta descriptions, structured data)
5. Ajouter des captures d'écran de l'application
6. Créer une page de tarification dédiée 