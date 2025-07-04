# 🔍 Guide de Diagnostic - Recherche Universelle GeekBoard

## 🚨 Symptômes d'Erreur Communs

### 1. Erreur 500 + "// Configu..." JSON Parse Error

**Symptômes:**
- Console JavaScript: `POST https://domain.com/ajax/recherche_universelle.php 500 (Internal Server Error)`
- Erreur: `SyntaxError: Unexpected token '/', "// Configu"... is not valid JSON`

**Causes probables:**
- ❌ Fichier `includes/config.php` sans balise `<?php` d'ouverture
- ❌ Utilisation de `global $db` au lieu de `getShopDBConnection()`
- ❌ Session non démarrée avant utilisation de `$_SESSION['shop_id']`
- ❌ Include incorrect des fichiers de configuration

## 🔧 Solutions par Étapes

### Étape 1: Vérifier les Fichiers de Configuration

```php
// ✅ CORRECT - includes/config.php
<?php
// Configuration de la base de données
define('DB_HOST', 'srv931.hstgr.io');
// ... autres configurations

// ❌ INCORRECT - includes/config.php
// Configuration de la base de données (sans <?php)
define('DB_HOST', 'srv931.hstgr.io');
```

### Étape 2: Corriger les Includes dans recherche_universelle.php

```php
// ✅ CORRECT
require_once '../config/database.php';
require_once '../includes/functions.php';

// ❌ INCORRECT
require_once '../includes/config.php'; // Ancien systeme
```

### Étape 3: Utiliser le Système Multi-Database

```php
// ✅ CORRECT - Fonction de recherche avec système multi-boutique
function searchClients($terme, $shop_pdo) {
    try {
        $sql = "SELECT id, nom, prenom, telephone, email 
                FROM clients 
                WHERE nom LIKE :terme 
                ORDER BY nom, prenom 
                LIMIT 10";
        $stmt = $shop_pdo->prepare($sql);
        $terme_wildcard = "%{$terme}%";
        $stmt->bindParam(':terme', $terme_wildcard);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur searchClients: " . $e->getMessage());
        return [];
    }
}

// ❌ INCORRECT - Ancienne méthode
function searchClients($terme) {
    global $db; // Variable globale inexistante
    $sql = "SELECT ...";
    $stmt = $db->prepare($sql);
    // ...
}
```

### Étape 4: Gestion de Session Sécurisée

```php
// ✅ CORRECT - En début de fichier AJAX
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier la connexion
$shop_pdo = getShopDBConnection();
if (!$shop_pdo) {
    throw new Exception('Connexion à la base de données du magasin impossible');
}
```

## 🧪 Tests de Diagnostic

### Test 1: Vérifier la Balise PHP

```bash
# Commande pour vérifier le début du fichier config.php
head -1 public_html/includes/config.php
# Doit afficher: <?php
```

### Test 2: Tester la Recherche Manuellement

```php
// Créer test_recherche_fix.php et l'exécuter
// Vérifier:
// - Connexion à la base de données
// - Session shop_id
// - Fonctions de recherche
```

### Test 3: Test AJAX en Console JavaScript

```javascript
// Dans la console du navigateur
fetch('ajax/recherche_universelle.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'terme=test'
})
.then(response => {
    console.log('Status:', response.status);
    return response.text(); // Utiliser .text() pour voir le contenu brut
})
.then(data => {
    console.log('Réponse brute:', data);
    try {
        const json = JSON.parse(data);
        console.log('JSON valide:', json);
    } catch (e) {
        console.error('JSON invalide:', e);
    }
})
.catch(error => console.error('Erreur:', error));
```

## 📋 Checklist de Vérification

### Fichiers à Vérifier:
- [ ] `includes/config.php` - Balise `<?php` présente
- [ ] `ajax/recherche_universelle.php` - Utilise `getShopDBConnection()`
- [ ] `config/database.php` - Fonction `getShopDBConnection()` existe
- [ ] Session `$_SESSION['shop_id']` définie

### Structure Database:
- [ ] Table `shops` existe dans la base principale
- [ ] Configuration shop complète (host, user, pass, db_name)
- [ ] Connexion à la base du magasin fonctionnelle
- [ ] Tables `clients`, `reparations` existent dans la base du magasin

### Logs à Consulter:
- [ ] Logs PHP d'erreur (`error_log`)
- [ ] Console JavaScript du navigateur
- [ ] Logs de connexion database

## 🔍 Codes d'Erreur Fréquents

### Erreur 500 - Erreur Serveur
```
Cause: Parse error, fatal error PHP
Solution: Vérifier la syntaxe PHP, balises d'ouverture/fermeture
```

### JSON Parse Error
```
Cause: Réponse contient du HTML/PHP au lieu de JSON
Solution: Vérifier que le script retourne uniquement du JSON
```

### Connection refused
```
Cause: Impossible de se connecter à la base de données
Solution: Vérifier les paramètres de connexion dans la table shops
```

### Shop ID non défini
```
Cause: $_SESSION['shop_id'] non défini
Solution: Vérifier la gestion des sous-domaines et sessions
```

## 🛠️ Scripts de Réparation Automatique

### Script 1: Vérification et Correction des Balises PHP

```bash
#!/bin/bash
# check_php_tags.sh
find . -name "*.php" -exec grep -L "<?php" {} \; | while read file; do
    echo "Fichier sans balise PHP: $file"
    # Ajouter <?php au début si nécessaire
    if ! head -1 "$file" | grep -q "<?php"; then
        echo "<?php" | cat - "$file" > temp && mv temp "$file"
        echo "Balise PHP ajoutée à $file"
    fi
done
```

### Script 2: Remplacement des Variables Globales

```bash
#!/bin/bash
# fix_global_db.sh
find ./ajax -name "*.php" -exec sed -i 's/global $db;/\/\/ global $db; \/\/ REMPLACÉ PAR getShopDBConnection()/g' {} \;
find ./ajax -name "*.php" -exec sed -i 's/$db->prepare/$shop_pdo->prepare/g' {} \;
echo "Variables globales remplacées"
```

## 📞 Support et Dépannage

### Logs de Debug Personnalisés

```php
// Ajouter dans les fonctions de recherche
function dbDebugLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $shop_id = $_SESSION['shop_id'] ?? 'unknown';
    error_log("[{$timestamp}] [Shop:{$shop_id}] {$message}");
}

// Utilisation
dbDebugLog("Recherche universelle - terme: $terme");
dbDebugLog("Connexion database établie pour shop: " . $_SESSION['shop_id']);
```

### Mode Debug Avancé

```php
// En tête du fichier ajax/recherche_universelle.php pour debug
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
```

---

## 🎯 Résumé des Corrections Standards

1. **Ajout balise PHP** dans `includes/config.php`
2. **Remplacement include** vers `config/database.php`
3. **Utilisation getShopDBConnection()** au lieu de `global $db`
4. **Gestion session sécurisée** avec vérification
5. **Amélioration gestion d'erreurs** avec logging détaillé
6. **Vérification existence tables** avant requêtes
7. **Respect isolation multi-boutique** dans toutes les requêtes

Ces corrections garantissent la compatibilité avec le système multi-database GeekBoard tout en maintenant la sécurité et les performances. 