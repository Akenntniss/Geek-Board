# Guide d'optimisation PWA pour GeekBoard

Ce document explique comment intégrer les améliorations PWA (Progressive Web App) dans votre application GeekBoard, avec un focus particulier sur la gestion des commandes en mode hors ligne.

## Fichiers créés

1. **`/assets/js/commandes-offline.js`** - Gestion des commandes en mode hors ligne
2. **`/service-worker-optimized.js`** - Service worker optimisé avec stratégies de cache avancées
3. **`/manifest-optimized.json`** - Manifest optimisé pour une meilleure intégration sur iOS et Android
4. **`/assets/js/pwa-integration.js`** - Script d'intégration des fonctionnalités PWA
5. **`/pwa-guide.html`** - Guide d'utilisation de la PWA pour les utilisateurs

## Instructions d'intégration

### 1. Intégrer le service worker optimisé

Remplacez le service worker actuel par la version optimisée :

1. Renommez le fichier `service-worker.js` actuel en `service-worker-backup.js` pour conserver une sauvegarde
2. Renommez `service-worker-optimized.js` en `service-worker.js`

OU

Modifiez le script d'enregistrement du service worker dans vos pages pour pointer vers le nouveau fichier :

```html
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker-optimized.js')
      .then(registration => {
        console.log('Service Worker enregistré avec succès:', registration.scope);
      })
      .catch(error => {
        console.error('Erreur lors de l\'enregistrement du Service Worker:', error);
      });
  }
</script>
```

### 2. Mettre à jour le manifest

Remplacez le manifest actuel par la version optimisée :

1. Renommez le fichier `manifest.json` actuel en `manifest-backup.json` pour conserver une sauvegarde
2. Renommez `manifest-optimized.json` en `manifest.json`

OU

Modifiez la balise link dans vos pages pour pointer vers le nouveau fichier :

```html
<link rel="manifest" href="/manifest-optimized.json">
```

### 3. Intégrer les scripts dans vos pages

Ajoutez les scripts suivants dans l'en-tête ou avant la fermeture du body de vos pages principales :

```html
<!-- Scripts pour le support PWA -->
<script src="/assets/js/offline-sync.js"></script>
<script src="/assets/js/commandes-offline.js"></script>
<script src="/assets/js/pwa-integration.js"></script>
```

### 4. Ajouter les méta-tags pour iOS

Ajoutez ces méta-tags dans l'en-tête de vos pages pour une meilleure intégration sur iOS :

```html
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="GeekBoard">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/images/pwa-icons/apple-touch-icon.png">
```

### 5. Ajouter des attributs aux formulaires de commandes

Modifiez vos formulaires de commandes pour ajouter les attributs nécessaires au traitement hors ligne :

```html
<!-- Pour le formulaire d'ajout de commande -->
<form id="ajouterCommandeForm" data-form="ajouter-commande" data-offline-form="commande">

<!-- Pour le formulaire de modification de commande -->
<form id="editCommandeForm" data-form="modifier-commande" data-offline-form="commande-edit">
```

### 6. Créer le point de terminaison pour la synchronisation

Créez un fichier `ajax/sync_commande.php` pour gérer la synchronisation des commandes hors ligne :

```php
<?php
// Vérifier si la requête est en JSON
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
} else {
    // Fallback pour les requêtes non-JSON
    $data = $_POST;
}

// Vérifier les données requises
if (empty($data) || !isset($data['action']) || !isset($data['data'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

// Inclure les fichiers nécessaires
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Vérifier l'authentification
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Traiter selon l'action
$action = $data['action'];
$commandeData = $data['data'];

switch ($action) {
    case 'add':
        // Ajouter une nouvelle commande
        $result = add_commande($commandeData);
        break;
    case 'update':
        // Mettre à jour une commande existante
        $result = update_commande($commandeData);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        exit;
}

// Retourner le résultat
echo json_encode($result);

// Fonction pour ajouter une commande
function add_commande($data) {
    global $db;
    
    // Préparer les données
    $client_id = isset($data['client_id']) ? intval($data['client_id']) : 0;
    $fournisseur_id = isset($data['fournisseur_id']) ? intval($data['fournisseur_id']) : 0;
    $nom_piece = isset($data['nom_piece']) ? $db->real_escape_string($data['nom_piece']) : '';
    $quantite = isset($data['quantite']) ? intval($data['quantite']) : 1;
    $prix_estime = isset($data['prix_estime']) ? floatval($data['prix_estime']) : 0;
    $code_barre = isset($data['code_barre']) ? $db->real_escape_string($data['code_barre']) : '';
    $statut = isset($data['statut']) ? $db->real_escape_string($data['statut']) : 'en_attente';
    $reparation_id = isset($data['reparation_id']) ? intval($data['reparation_id']) : 0;
    
    // Insérer dans la base de données
    $query = "INSERT INTO commandes_pieces "
           . "(client_id, fournisseur_id, nom_piece, quantite, prix_estime, code_barre, statut, date_creation, reparation_id) "
           . "VALUES "
           . "($client_id, $fournisseur_id, '$nom_piece', $quantite, $prix_estime, '$code_barre', '$statut', NOW(), $reparation_id)";
    
    if ($db->query($query)) {
        $id = $db->insert_id;
        return ['success' => true, 'message' => 'Commande ajoutée avec succès', 'id' => $id];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'ajout de la commande: ' . $db->error];
    }
}

// Fonction pour mettre à jour une commande
function update_commande($data) {
    global $db;
    
    // Vérifier l'ID de la commande
    if (!isset($data['id'])) {
        return ['success' => false, 'message' => 'ID de commande manquant'];
    }
    
    $id = intval($data['id']);
    
    // Préparer les données
    $updates = [];
    
    if (isset($data['client_id'])) {
        $updates[] = "client_id = " . intval($data['client_id']);
    }
    
    if (isset($data['fournisseur_id'])) {
        $updates[] = "fournisseur_id = " . intval($data['fournisseur_id']);
    }
    
    if (isset($data['nom_piece'])) {
        $updates[] = "nom_piece = '" . $db->real_escape_string($data['nom_piece']) . "'";
    }
    
    if (isset($data['quantite'])) {
        $updates[] = "quantite = " . intval($data['quantite']);
    }
    
    if (isset($data['prix_estime'])) {
        $updates[] = "prix_estime = " . floatval($data['prix_estime']);
    }
    
    if (isset($data['code_barre'])) {
        $updates[] = "code_barre = '" . $db->real_escape_string($data['code_barre']) . "'";
    }
    
    if (isset($data['statut'])) {
        $updates[] = "statut = '" . $db->real_escape_string($data['statut']) . "'";
    }
    
    if (isset($data['reparation_id'])) {
        $updates[] = "reparation_id = " . intval($data['reparation_id']);
    }
    
    // S'il n'y a rien à mettre à jour
    if (empty($updates)) {
        return ['success' => false, 'message' => 'Aucune donnée à mettre à jour'];
    }
    
    // Construire la requête
    $query = "UPDATE commandes_pieces SET " . implode(", ", $updates) . " WHERE id = $id";
    
    if ($db->query($query)) {
        return ['success' => true, 'message' => 'Commande mise à jour avec succès', 'id' => $id];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour de la commande: ' . $db->error];
    }
}
?>
```

## Fonctionnalités PWA améliorées

### Mode hors ligne pour les commandes

Les utilisateurs peuvent désormais :

- Consulter les commandes existantes sans connexion internet
- Créer de nouvelles commandes en mode hors ligne
- Modifier les commandes existantes hors ligne
- Changer le statut des commandes hors ligne

Toutes ces modifications sont synchronisées automatiquement dès que la connexion internet est rétablie.

### Stratégies de cache optimisées

Le service worker optimisé utilise plusieurs stratégies de cache :

- **Cache First** pour les ressources statiques (CSS, JS, images)
- **Network First** pour les pages de navigation
- **Stale-While-Revalidate** pour les API de données avec durée de validité configurable

### Installation améliorée

Le manifest optimisé améliore l'expérience d'installation sur iOS et Android :

- Icônes optimisées pour tous les appareils
- Écrans de démarrage pour iOS
- Raccourcis pour accéder rapidement aux fonctionnalités principales
- Meilleure intégration avec les systèmes d'exploitation

### Interface utilisateur adaptative

L'interface s'adapte automatiquement lorsque l'application est installée :

- Indicateur de mode hors ligne
- Marquage visuel des éléments modifiés hors ligne
- Optimisations pour les appareils iOS (notch, dynamic island)

## Guide pour les utilisateurs

Un guide complet est disponible pour les utilisateurs à l'adresse `/pwa-guide.html`. Ce guide explique :

- Comment installer l'application sur iOS et Android
- Comment utiliser les fonctionnalités hors ligne
- Les indicateurs visuels du mode hors ligne
- Les avantages de l'utilisation en mode PWA

## Dépannage

### Problèmes de cache

Si les utilisateurs rencontrent des problèmes avec d'anciennes versions en cache :

1. Accédez à l'URL `/index.php?clear_cache=1`
2. Dans le service worker, augmentez la version de cache (`CACHE_VERSION`)

### Problèmes de synchronisation

Si les données ne se synchronisent pas correctement :

1. Vérifiez les erreurs dans la console du navigateur
2. Assurez-vous que le point de terminaison `ajax/sync_commande.php` est accessible
3. Vérifiez que les permissions de base de données sont correctes

### Problèmes d'installation sur iOS

Pour les problèmes d'installation sur iOS :

1. Assurez-vous que l'utilisateur utilise Safari (les autres navigateurs ne supportent pas l'installation PWA sur iOS)
2. Vérifiez que tous les méta-tags Apple sont présents
3. Assurez-vous que les icônes Apple sont disponibles dans les bonnes tailles

## Ressources additionnelles

- [Documentation PWA de Google](https://web.dev/progressive-web-apps/)
- [Guide PWA pour iOS](https://webkit.org/blog/8090/workers-at-your-service/)
- [Outils de test Lighthouse](https://developers.google.com/web/tools/lighthouse)