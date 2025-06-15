# 🔍 Guide de Diagnostic : Modal vs Page d'Accueil

## 🎯 Problème Identifié
La page d'accueil affiche les bonnes données du magasin, mais la modal affiche les données de la base générale au lieu de la base spécifique du magasin.

## 📋 Étapes de Diagnostic

### 1. **Vérifier les Logs en Temps Réel** 
Ouvrez les logs d'erreur et cherchez les messages `DEBUG` :

```bash
# Dans les logs du serveur, cherchez :
GET_CLIENT_DETAILS DEBUG - Base de données connectée: [NOM_DE_LA_BASE]
RECHERCHE DEBUG - Base de données connectée: [NOM_DE_LA_BASE]
```

### 2. **Utiliser les Scripts de Test**

#### **A. Script de Diagnostic Complet**
```
http://votre-domaine.com/debug_modal_vs_homepage.php
```
Ce script compare les connexions entre modal et page d'accueil.

#### **B. Script de Test AJAX**
```
http://votre-domaine.com/test_modal_ajax.php
```
Ce script teste directement les appels AJAX en temps réel.

### 3. **Test Manuel dans le Navigateur**

1. **Ouvrir la Page d'Accueil**
   - Faire une recherche de client
   - Noter quelle base de données s'affiche dans les logs

2. **Ouvrir la Modal**
   - Cliquer sur un client pour ouvrir la modal
   - Vérifier les logs pour voir quelle base est utilisée

3. **Comparer les Résultats**
   - La page d'accueil devrait utiliser : `u139954273_[nom_magasin]`
   - La modal devrait utiliser la **même** base

## 🔧 Solutions Possibles

### **Solution 1 : Problème de Session**
Si la session `shop_id` n'est pas transmise correctement :

```php
// Vérifier dans get_client_details.php que la session est active
session_start();
error_log("Session shop_id: " . ($_SESSION['shop_id'] ?? 'NON DÉFINI'));
```

### **Solution 2 : Cache de Connexion**
Si la connexion est mise en cache incorrectement :

```php
// Forcer une nouvelle connexion dans get_client_details.php
global $shop_pdo;
$shop_pdo = null; // Réinitialiser le cache
$pdo = getShopDBConnection();
```

### **Solution 3 : Problème de Timing**
Si le problème survient après un changement de magasin :

```javascript
// Ajouter un délai avant l'appel AJAX
setTimeout(() => {
    // Appel AJAX vers get_client_details.php
}, 100);
```

## 🚨 Points d'Attention

### **Vérifications Critiques**
1. ✅ **Session Active** : `session_id()` doit être identique entre page et modal
2. ✅ **Shop ID Défini** : `$_SESSION['shop_id']` doit avoir une valeur
3. ✅ **Base Correcte** : `SELECT DATABASE()` doit retourner la base du magasin
4. ✅ **Credentials AJAX** : `credentials: 'same-origin'` doit être présent

### **Logs à Surveiller**
```
[getShopDBConnection] Shop ID utilisé: [ID]
GET_CLIENT_DETAILS DEBUG - Base de données connectée: [BASE]
RECHERCHE DEBUG - Base de données connectée: [BASE]
```

## 🔍 Diagnostic Automatique

Le fichier `get_client_details.php` a été modifié pour inclure des informations de debug :

```json
{
  "success": true,
  "client": {...},
  "debug": {
    "database_used": "u139954273_paris",
    "shop_id": "1"
  }
}
```

## 📊 Résultats Attendus

| Composant | Base Attendue | Status |
|-----------|---------------|--------|
| Page d'accueil | `u139954273_[magasin]` | ✅ |
| Modal client | `u139954273_[magasin]` | ❓ À vérifier |

## 🛠️ Actions de Résolution

### **Si les bases sont différentes :**
1. Vérifier que `$_SESSION['shop_id']` est identique
2. Nettoyer le cache de connexion : `$shop_pdo = null`
3. Forcer une nouvelle connexion dans la modal

### **Si les sessions sont différentes :**
1. Vérifier que `session_start()` est appelé en premier
2. S'assurer que les cookies de session sont transmis
3. Vérifier la configuration du domaine pour les sessions

## 💡 Test Final

Après correction, les deux appels doivent retourner la **même base de données** :

```bash
# Page d'accueil
RECHERCHE DEBUG - Base de données connectée: u139954273_paris

# Modal
GET_CLIENT_DETAILS DEBUG - Base de données connectée: u139954273_paris
```

---

**Note :** Ce guide permet d'identifier précisément où se situe le problème et d'appliquer la solution appropriée selon le système multi-database GeekBoard. 