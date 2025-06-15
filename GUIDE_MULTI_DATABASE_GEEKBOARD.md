# 🗄️ GUIDE COMPLET - GESTION MULTI-DATABASE GEEKBOARD
## Interface Multi-Magasins avec Bases de Données Séparées

---

## 📋 INTRODUCTION

GeekBoard utilise un système de **bases de données multiples** pour permettre à plusieurs magasins d'utiliser la même interface tout en gardant leurs données **complètement séparées et sécurisées**. Chaque magasin dispose de sa propre base de données.

### 🎯 ARCHITECTURE GÉNÉRALE

```
🏪 MAGASIN A (shop_id: 1)  →  📊 DATABASE_A (u139954273_shop1)
🏪 MAGASIN B (shop_id: 2)  →  📊 DATABASE_B (u139954273_shop2)  
🏪 MAGASIN C (shop_id: 3)  →  📊 DATABASE_C (u139954273_shop3)
                               
📊 BASE PRINCIPALE         →  📊 DATABASE_MAIN (u139954273_Vscodetest)
   (Configuration Magasins)    (Infos de connexion pour chaque magasin)
```

---

## 🔧 CONFIGURATION DES CONNEXIONS

### 1. 📁 **Fichier Principal : `config/database.php`**

Ce fichier gère **toutes les connexions** aux différentes bases de données.

#### **Variables Globales**
```php
$main_pdo = null;   // Connexion à la base principale (configuration)
$shop_pdo = null;   // Connexion à la base du magasin actuel
```

#### **Configuration Base Principale**
```php
define('MAIN_DB_HOST', '191.96.63.103');
define('MAIN_DB_PORT', '3306');
define('MAIN_DB_USER', 'u139954273_Vscodetest');
define('MAIN_DB_PASS', 'Maman01#');
define('MAIN_DB_NAME', 'u139954273_Vscodetest');
```

### 2. 🏢 **Table `shops` dans la Base Principale**

La base principale contient une table `shops` avec les informations de connexion pour chaque magasin :

```sql
CREATE TABLE shops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(255) NOT NULL,
    subdomain VARCHAR(100) UNIQUE,
    db_host VARCHAR(255),
    db_port INT DEFAULT 3306,
    db_user VARCHAR(255),
    db_pass VARCHAR(255),
    db_name VARCHAR(255),
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### **Exemple de Données**
```sql
INSERT INTO shops VALUES 
(1, 'Magasin Paris', 'paris', '191.96.63.103', 3306, 'u139954273_paris', 'MotDePasse1', 'u139954273_paris', 1),
(2, 'Magasin Lyon', 'lyon', '191.96.63.103', 3306, 'u139954273_lyon', 'MotDePasse2', 'u139954273_lyon', 1),
(3, 'Magasin Nice', 'nice', '191.96.63.103', 3306, 'u139954273_nice', 'MotDePasse3', 'u139954273_nice', 1);
```

---

## 🔌 FONCTIONS DE CONNEXION

### 1. 📊 **getMainDBConnection()** - Base Principale

```php
function getMainDBConnection() {
    global $main_pdo;
    
    // Connexion à la base principale (configuration des magasins)
    if ($main_pdo === null) {
        $dsn = "mysql:host=" . MAIN_DB_HOST . ";port=" . MAIN_DB_PORT . 
               ";dbname=" . MAIN_DB_NAME . ";charset=utf8mb4";
        
        $main_pdo = new PDO($dsn, MAIN_DB_USER, MAIN_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    
    return $main_pdo;
}
```

### 2. 🏪 **getShopDBConnection()** - Base du Magasin Actuel

```php
function getShopDBConnection() {
    global $shop_pdo;
    
    // Récupération du shop_id depuis la session ou l'URL
    $shop_id = $_SESSION['shop_id'] ?? $_GET['shop_id'] ?? null;
    
    if (!$shop_id) {
        error_log("ERREUR: Aucun shop_id défini");
        return getMainDBConnection(); // Fallback vers base principale
    }
    
    // Cache la connexion pour éviter de se reconnecter
    if ($shop_pdo !== null) {
        return $shop_pdo;
    }
    
    // Récupération des infos de connexion depuis la base principale
    $main_pdo = getMainDBConnection();
    $stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch();
    
    if ($shop) {
        // Connexion à la base du magasin spécifique
        $shop_config = [
            'host' => $shop['db_host'],
            'port' => $shop['db_port'] ?? 3306,
            'user' => $shop['db_user'],
            'pass' => $shop['db_pass'],
            'dbname' => $shop['db_name']
        ];
        
        $shop_pdo = connectToShopDB($shop_config);
    }
    
    return $shop_pdo ?? getMainDBConnection(); // Fallback si échec
}
```

### 3. 🔗 **connectToShopDB()** - Connexion Dynamique

```php
function connectToShopDB($shop_config) {
    $dsn = "mysql:host=" . $shop_config['host'] . 
           ";port=" . $shop_config['port'] . 
           ";dbname=" . $shop_config['dbname'] . 
           ";charset=utf8mb4";
    
    try {
        $pdo = new PDO($dsn, $shop_config['user'], $shop_config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erreur connexion magasin: " . $e->getMessage());
        return null;
    }
}
```

---

## 🌐 GESTION DES SOUS-DOMAINES

### 📁 **Fichier : `subdomain_handler.php`**

Ce fichier gère l'accès aux magasins via des sous-domaines.

```php
// Exemple d'URLs :
// https://paris.mondomaine.com    → Magasin Paris (shop_id = 1)
// https://lyon.mondomaine.com     → Magasin Lyon (shop_id = 2)
// https://nice.mondomaine.com     → Magasin Nice (shop_id = 3)

// Récupération du sous-domaine
$subdomain = $_GET['subdomain'] ?? '';

// Recherche du magasin correspondant
$stmt = $main_pdo->prepare("SELECT * FROM shops WHERE subdomain = ? AND active = 1");
$stmt->execute([$subdomain]);
$shop = $stmt->fetch();

if ($shop) {
    // Stockage de l'ID du magasin en session
    $_SESSION['shop_id'] = $shop['id'];
    $_SESSION['shop_name'] = $shop['nom'];
    
    // Redirection vers l'interface du magasin
    header('Location: /index.php');
} else {
    // Magasin non trouvé
    include 'templates/shop_not_found.php';
}
```

---

## 💻 UTILISATION DANS LE CODE

### ✅ **BONNE PRATIQUE - Utilisation Correcte**

```php
<?php
// 1. Inclusion de la configuration
require_once 'config/database.php';

// 2. Récupération de la connexion du magasin actuel
$shop_pdo = getShopDBConnection();

// 3. Utilisation pour les requêtes
$stmt = $shop_pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

// 4. Exemple pour les réparations
$stmt = $shop_pdo->prepare("SELECT r.*, c.nom FROM reparations r 
                           JOIN clients c ON r.client_id = c.id 
                           WHERE r.status = ?");
$stmt->execute(['en_cours']);
$reparations = $stmt->fetchAll();
?>
```

### ❌ **MAUVAISE PRATIQUE - À Éviter**

```php
<?php
// ❌ NE PAS FAIRE - Utilise la connexion principale au lieu du magasin
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");

// ❌ NE PAS FAIRE - Connexion directe sans passer par les fonctions
$pdo = new PDO("mysql:host=...", $user, $pass);
?>
```

---

## 🔐 SÉCURITÉ ET ISOLATION

### 🛡️ **Avantages du Système Multi-Database**

1. **🔒 ISOLATION COMPLÈTE**
   - Chaque magasin ne peut accéder qu'à ses propres données
   - Impossible d'accéder aux données d'un autre magasin par erreur

2. **🔐 SÉCURITÉ RENFORCÉE**
   - Chaque magasin a ses propres identifiants de base de données
   - En cas de compromission, seul un magasin est affecté

3. **📊 PERFORMANCE**
   - Les requêtes sont plus rapides (moins de données par base)
   - Possibilité de distribuer les bases sur différents serveurs

4. **🔧 MAINTENANCE FACILITÉE**
   - Sauvegarde/restauration par magasin
   - Mise à jour individuelle possible

---

## 🚨 DÉPANNAGE COURANT

### 1. 📋 **Vérifier la Configuration d'un Magasin**

```php
// Script de diagnostic : debug_shop_db.php
$shop_id = $_GET['shop_id'] ?? 1;
$main_pdo = getMainDBConnection();

$stmt = $main_pdo->prepare("SELECT * FROM shops WHERE id = ?");
$stmt->execute([$shop_id]);
$shop = $stmt->fetch();

echo "<h2>Configuration Magasin #$shop_id</h2>";
print_r($shop);

// Test de connexion
$shop_pdo = getShopDBConnection();
if ($shop_pdo) {
    echo "<p>✅ Connexion réussie</p>";
    
    // Vérifier quelle base est utilisée
    $stmt = $shop_pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    echo "<p>Base connectée: " . $result['db_name'] . "</p>";
} else {
    echo "<p>❌ Échec de connexion</p>";
}
```

### 2. 🔍 **Vérifier la Session Actuelle**

```php
// Affichage des informations de session
echo "<h3>Session Actuelle</h3>";
echo "Shop ID: " . ($_SESSION['shop_id'] ?? 'Non défini') . "<br>";
echo "Shop Name: " . ($_SESSION['shop_name'] ?? 'Non défini') . "<br>";
echo "URL Shop ID: " . ($_GET['shop_id'] ?? 'Non défini') . "<br>";
```

### 3. 🔧 **Problèmes Fréquents**

#### **Problème 1: Shop_id non défini**
```php
// Solution : Forcer un shop_id par défaut
if (!isset($_SESSION['shop_id'])) {
    $_SESSION['shop_id'] = 1; // Magasin par défaut
    error_log("Shop_id manquant, utilisation du magasin par défaut");
}
```

#### **Problème 2: Connexion à la mauvaise base**
```php
// Vérification et correction
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query("SELECT DATABASE() as current_db");
$result = $stmt->fetch();

if ($result['current_db'] != $expected_db_name) {
    error_log("ERREUR: Connexion à " . $result['current_db'] . 
              " au lieu de " . $expected_db_name);
    // Forcer la reconnexion
    $shop_pdo = null;
    $shop_pdo = getShopDBConnection();
}
```

---

## 📚 EXEMPLES PRATIQUES

### 1. 🏪 **Page de Gestion des Clients**

```php
<?php
// pages/clients.php
require_once '../config/database.php';

// Connexion au magasin actuel
$shop_pdo = getShopDBConnection();

// Récupération des clients du magasin actuel UNIQUEMENT
$stmt = $shop_pdo->prepare("
    SELECT c.*, COUNT(r.id) as nb_reparations 
    FROM clients c 
    LEFT JOIN reparations r ON c.id = r.client_id 
    GROUP BY c.id 
    ORDER BY c.nom
");
$stmt->execute();
$clients = $stmt->fetchAll();

// Affichage
foreach ($clients as $client) {
    echo "<div class='client'>";
    echo "<h3>" . htmlspecialchars($client['nom']) . "</h3>";
    echo "<p>Réparations: " . $client['nb_reparations'] . "</p>";
    echo "</div>";
}
?>
```

### 2. 📊 **Dashboard avec Statistiques**

```php
<?php
// pages/dashboard.php
require_once '../config/database.php';

$shop_pdo = getShopDBConnection();

// Statistiques du magasin actuel uniquement
$stats = [];

// Nombre total de réparations
$stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM reparations");
$stats['total_reparations'] = $stmt->fetch()['total'];

// Réparations en cours
$stmt = $shop_pdo->query("SELECT COUNT(*) as total FROM reparations WHERE status = 'en_cours'");
$stats['reparations_en_cours'] = $stmt->fetch()['total'];

// Chiffre d'affaires du mois
$stmt = $shop_pdo->query("SELECT SUM(prix) as total FROM reparations 
                         WHERE DATE_FORMAT(date_creation, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
                         AND status = 'terminee'");
$stats['ca_mois'] = $stmt->fetch()['total'] ?? 0;

echo "<h2>Statistiques - " . ($_SESSION['shop_name'] ?? 'Magasin') . "</h2>";
echo "<div class='stats'>";
echo "<div>Total réparations: " . $stats['total_reparations'] . "</div>";
echo "<div>En cours: " . $stats['reparations_en_cours'] . "</div>";
echo "<div>CA ce mois: " . number_format($stats['ca_mois'], 2) . " €</div>";
echo "</div>";
?>
```

### 3. 🔄 **Migration de Code Existant**

```php
// AVANT (code obsolète utilisant la base principale)
<?php
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();
?>

// APRÈS (code correct utilisant la base du magasin)
<?php
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();
?>
```

---

## 🎯 CONFIGURATION APACHE/NGINX

### 🌐 **Configuration des Sous-domaines**

#### **Apache (.htaccess)**
```apache
RewriteEngine On

# Redirection des sous-domaines vers subdomain_handler.php
RewriteCond %{HTTP_HOST} ^([^.]+)\.mondomaine\.com$ [NC]
RewriteRule ^(.*)$ /subdomain_handler.php?subdomain=%1&path=$1 [QSA,L]

# Gestion du domaine principal
RewriteCond %{HTTP_HOST} ^mondomaine\.com$ [NC]
RewriteRule ^(.*)$ /index.php [QSA,L]
```

#### **Nginx**
```nginx
server {
    listen 80;
    server_name *.mondomaine.com;
    
    location / {
        if ($host ~* ^([^.]+)\.mondomaine\.com$) {
            set $subdomain $1;
            rewrite ^/(.*)$ /subdomain_handler.php?subdomain=$subdomain&path=$1 last;
        }
    }
}
```

---

## 📋 CHECKLIST DE MIGRATION

### ✅ **Avant de Migrer un Fichier**

1. **🔍 Identifier** toutes les utilisations de `$pdo` ou `global $pdo`
2. **🔄 Remplacer** par `$shop_pdo = getShopDBConnection();`
3. **🧪 Tester** avec différents shop_id
4. **📝 Vérifier** que les données affichées correspondent au bon magasin
5. **🚨 Valider** que les erreurs sont gérées correctement

### 🔧 **Script de Recherche des Fichiers à Migrer**

```bash
# Rechercher tous les fichiers utilisant encore $pdo
grep -r "global \$pdo" public_html/pages/
grep -r "\$pdo->" public_html/pages/
grep -r "\$pdo=" public_html/pages/

# Rechercher les inclusions de l'ancien db.php
grep -r "includes/db.php" public_html/
```

---

## 🚀 BONNES PRATIQUES

### 1. **🎯 Toujours Utiliser les Fonctions**
- ✅ `getShopDBConnection()` pour les données du magasin
- ✅ `getMainDBConnection()` pour la configuration globale

### 2. **🔐 Vérifier le Shop_ID**
```php
if (!isset($_SESSION['shop_id'])) {
    header('Location: /login.php');
    exit;
}
```

### 3. **📝 Logger les Opérations**
```php
function dbDebugLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $shop_id = $_SESSION['shop_id'] ?? 'unknown';
    error_log("[{$timestamp}] [Shop:{$shop_id}] {$message}");
}
```

### 4. **🔄 Gestion des Erreurs**
```php
$shop_pdo = getShopDBConnection();
if (!$shop_pdo) {
    error_log("Erreur: Impossible de se connecter à la base du magasin");
    include 'templates/error_db.php';
    exit;
}
```

---

## 🎉 CONCLUSION

Ce système multi-database permet à GeekBoard de gérer plusieurs magasins de manière **sécurisée**, **performante** et **isolée**. Chaque magasin dispose de sa propre base de données tout en partageant la même interface.

### 📞 **Support et Aide**

Pour toute question ou problème :
1. Vérifiez les logs dans `/logs/`
2. Utilisez les scripts de diagnostic fournis
3. Consultez ce guide pour les bonnes pratiques

**L'architecture multi-database de GeekBoard garantit une séparation complète des données entre magasins tout en maintenant une interface unifiée et intuitive.** 