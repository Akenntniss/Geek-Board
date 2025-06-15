# 📋 MIGRATION MULTI-BOUTIQUE - PLAN DE TRAVAIL COMPLET
## Projet GeekBoard - Finalisation Migration Mono → Multi Boutique

---

## 🚨 PHASE 1 : CORRECTIONS CRITIQUES - CONNEXIONS DATABASE (PRIORITÉ IMMÉDIATE)

### ❌ PROBLÈME PRINCIPAL
Plusieurs pages utilisent encore `$pdo` (connexion principale) au lieu de `getShopDBConnection()` (connexion magasin spécifique).

### 📝 PAGES À CORRIGER IMMÉDIATEMENT

#### 1. **pages/clients.php** (Ligne 3-11)
```php
// ❌ ACTUEL
$stmt = $pdo->query("SELECT c.*, COUNT(r.id)...");

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query("SELECT c.*, COUNT(r.id)...");
```

#### 2. **pages/base_connaissances.php** (Ligne 12+)
```php
// ❌ ACTUEL  
global $pdo;
$stmt = $pdo->query($query);

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query($query);
```

#### 3. **pages/knowledge_base.php** (Ligne 8+)
```php
// ❌ ACTUEL
require_once 'includes/db.php';
$stmt = $db->prepare("SELECT * FROM kb_categories...");

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT * FROM kb_categories...");
```

#### 4. **pages/campagne_details.php** (Ligne 70+)
```php
// ❌ ACTUEL
$stmt_count = $pdo->prepare($sql_count);

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt_count = $shop_pdo->prepare($sql_count);
```

#### 5. **pages/sms_historique.php** (Ligne 80+)
```php
// ❌ ACTUEL
$stmt_count = $pdo->prepare($sql_count);

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt_count = $shop_pdo->prepare($sql_count);
```

#### 6. **pages/suivi_reparation.php** (Ligne 30+)
```php
// ❌ ACTUEL
$stmt = $pdo->prepare("SELECT r.*, c.nom...");

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT r.*, c.nom...");
```

#### 7. **pages/gestion_kb.php** (Ligne 16+)
```php
// ❌ ACTUEL
global $pdo;
$stmt = $pdo->query($query);

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query($query);
```

#### 8. **pages/portail_client.php** (Ligne 19+)
```php
// ❌ ACTUEL
$stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?...");

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT * FROM clients WHERE email = ?...");
```

#### 9. **pages/inventaire.php** (Ligne 9+)
```php
// ❌ ACTUEL
$stmt = $pdo->prepare("SELECT p.* FROM produits p...");

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT p.* FROM produits p...");
```

#### 10. **pages/retours.php** (Ligne 50+)
```php
// ❌ ACTUEL
$stmt = $pdo->prepare("SELECT r.*, s.name...");

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->prepare("SELECT r.*, s.name...");
```

#### 11. **pages/gestion_parrainage.php** (Ligne 80+)
```php
// ❌ ACTUEL
$stmt_inscrits = $pdo->query("SELECT COUNT(*) as total...");

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt_inscrits = $shop_pdo->query("SELECT COUNT(*) as total...");
```

#### 12. **pages/dashboard.php** (Ligne 3+)
```php
// ❌ ACTUEL
$stmt = $pdo->query("SELECT COUNT(*) FROM reparations");

// ✅ À REMPLACER PAR
$shop_pdo = getShopDBConnection();
$stmt = $shop_pdo->query("SELECT COUNT(*) FROM reparations");
```

---

## ✅ PHASE 2 : MIGRATION API SMS TERMINÉE ✅

### 🎯 **MIGRATION RÉALISÉE**
Migration complète de l'ancienne API `sms-gate.app` vers la nouvelle API `http://168.231.85.4:3001/api`

### 📦 **NOUVEAUX FICHIERS CRÉÉS**

#### 1. **classes/NewSmsService.php**
- ✅ Nouvelle classe SMS utilisant la nouvelle API
- ✅ Gestion des erreurs et retry automatique
- ✅ Logs détaillés pour le debugging
- ✅ Pas d'authentification (selon documentation)
- ✅ Exclusion des paramètres `sim_id` et `priority` (comme demandé)

#### 2. **includes/sms_functions.php**
- ✅ Fonction `send_sms()` unifiée compatible avec l'ancien code
- ✅ Logging automatique en base de données
- ✅ Gestion des erreurs et validation
- ✅ Création automatique de la table `sms_logs`

#### 3. **test_new_sms_api.php**
- ✅ Script de test complet pour la migration
- ✅ Interface web pour tester l'envoi de SMS
- ✅ Vérification des fonctions et classes
- ✅ Diagnostics techniques

### 🔧 **FICHIERS MODIFIÉS**

#### 1. **includes/global.php**
- ✅ Inclusion des nouvelles fonctions SMS
- ✅ Remplacement de l'ancien `api/sms/send.php`

#### 2. **classes/SmsService.php**
- ✅ Redirection vers la nouvelle API
- ✅ Compatibilité maintenue avec l'ancien code
- ✅ Messages de logs pour identifier l'utilisation

#### 3. **ajax/send_sms.php**
- ✅ Migration vers la nouvelle fonction `send_sms()`
- ✅ Simplification du code (suppression cURL manuel)
- ✅ Logs et enregistrement BDD automatiques

### 🚀 **STATUT MIGRATION SMS**
- ✅ **Infrastructure** : Nouvelle API opérationnelle
- ✅ **Compatibilité** : Ancien code fonctionne avec nouvelle API
- ✅ **Tests** : Script de test disponible
- ⏳ **Finalisation** : Autres fichiers à migrer progressivement

### 📋 **PROCHAINES ÉTAPES SMS**
1. **Tester** avec `test_new_sms_api.php`
2. **Migrer** les fichiers ajax restants :
   - `ajax/send_status_sms.php`
   - `ajax/send_devis_sms.php`
   - `ajax/direct_send_sms.php`
3. **Surveiller** les logs dans `/logs/`
4. **Supprimer** l'ancienne API une fois tout migré

---

## 🖼️ PHASE 3 : CORRECTIONS INTERFACE UTILISATEUR (PRIORITÉ MOYENNE)

### 📝 PROBLÈMES PHOTOS

#### 1. **pages/ajouter_reparation.php**
- ❌ Problème lors de la prise de photos
- ✅ Vérifier le module de prise de photo
- ✅ Tester l'upload et l'affichage

#### 2. **ajax/upload_repair_photo.php**
- ✅ Vérifier la fonction d'upload
- ✅ S'assurer qu'elle utilise `getShopDBConnection()`

### 📝 AMÉLIORATIONS UI

#### 1. **Agrandir champ de texte du problème**
- Fichier : `pages/ajouter_reparation.php`
- ✅ Transformer input en textarea
- ✅ Augmenter la taille du champ

```html
<!-- ❌ ACTUEL -->
<input type="text" name="description_probleme" class="form-control">

<!-- ✅ À REMPLACER PAR -->
<textarea name="description_probleme" class="form-control" rows="4"></textarea>
```

---

## 🔍 PHASE 4 : MODULES DE RECHERCHE (PRIORITÉ MOYENNE)

### 📝 PAGES AVEC RECHERCHE INCOMPLÈTE

#### 1. **pages/reparations.php**
- ❌ Module de recherche ne couvre pas tous les statuts
- ✅ Vérifier que la recherche fonctionne avec tous les statuts
- ✅ Optimiser la requête de recherche

#### 2. **pages/taches.php**
- ❌ Problème d'espace vide entre colonnes DESCRIPTION et ÉTAT
- ✅ Corriger le CSS/HTML de l'affichage
- ✅ Vérifier que la recherche couvre tous les statuts

#### 3. **pages/commandes_pieces.php**
- ✅ Vérifier modal "Filtrer par fournisseur"
- ✅ Tester le filtre par période
- ✅ Tester export PDF
- ✅ Tester modal "Modifier la commande"

---

## 📊 PHASE 5 : VÉRIFICATIONS PAR PAGE (PRIORITÉ MOYENNE)

### 📝 PAGES À VÉRIFIER UNE PAR UNE

#### 1. **pages/rachat_appareils.php**
- ✅ Vérifier utilisation `getShopDBConnection()`
- ✅ Tester modal "Nouveau rachat d'appareil"

#### 2. **pages/campagne_sms.php**
- ✅ Vérifier utilisation bonne database

#### 3. **pages/employes.php**
- ✅ Vérifier modal "Ajouter employé"
- ✅ Vérifier modal "Modifier employé"

#### 4. **pages/reparation_logs.php**
- ✅ Vérifier utilisation bonne database
- ✅ Améliorer l'UI (plus ergonomique)

#### 5. **pages/parametre.php**
- ✅ Vérifier le contenu de la page
- ❌ Retirer le bouton "Changer de magasin"

---

## 🎯 PHASE 6 : MODALS ET FONCTIONNALITÉS AVANCÉES (PRIORITÉ BASSE)

### 📝 MODALS À CRÉER/CORRIGER

#### 1. **pages/clients.php**
- ❌ Créer modal "Modifier un client"
- ❌ Ajouter option recherche client
- ❌ Ajouter pagination
- ❌ Dans modal "Historique réparations" : redirection vers page réparation avec modal détails ouvert

#### 2. **Modal "Signaler un problème" (footer)**
- ✅ Vérifier qu'il ajoute dans la database générale (pas magasin)
- ✅ Pour voir remontées de bugs de tous les magasins

#### 3. **pages/reparations.php**
- ✅ Modal "Mise à jour des statuts par lots"
- ✅ Modal "Relance des clients" 
- ✅ Modal "Afficher détails réparation"

---

## 📱 PHASE 7 : TESTS INTERFACE MOBILE/PWA (PRIORITÉ BASSE)

### 📝 TESTS À EFFECTUER

#### 1. **Interface Mobile PWA**
- ✅ Refaire tous les mêmes tests sur mobile
- ✅ Vérifier fonctionnalités photos
- ✅ Tester navigation et modals

#### 2. **Interface iPad PWA**
- ✅ Refaire tous les mêmes tests sur iPad
- ✅ Vérifier responsive design
- ✅ Tester toutes les fonctionnalités

---

## 🔧 TEMPLATE DE CORRECTION STANDARD

### Pour chaque page à corriger :

```php
<?php
// 1. AJOUTER EN DÉBUT DE FICHIER (après les autres require)
$shop_pdo = getShopDBConnection();

// 2. VÉRIFICATION DE CONNEXION (optionnel, pour debug)
if (!$shop_pdo) {
    error_log("ERREUR: Connexion shop_pdo non disponible dans " . __FILE__);
    set_message("Erreur de connexion à la base de données", "danger");
    redirect('accueil');
    exit;
}

// 3. REMPLACER TOUTES LES INSTANCES
// ❌ $pdo->query(...)
// ❌ $pdo->prepare(...)
// ✅ $shop_pdo->query(...)
// ✅ $shop_pdo->prepare(...)
```

---

## 📋 CHECKLIST DE VALIDATION

### Après chaque correction :
- [ ] ✅ La page utilise `getShopDBConnection()`
- [ ] ✅ Aucune référence à `$pdo` restante
- [ ] ✅ Test fonctionnel : les données affichées correspondent au bon magasin
- [ ] ✅ Aucune erreur PHP dans les logs
- [ ] ✅ Interface utilisateur fonctionne correctement

---

## 🚀 ORDRE DE PRIORITÉ RECOMMANDÉ

### SEMAINE 1 (CRITIQUE)
1. `pages/clients.php`
2. `pages/reparations.php` (vérification)
3. `pages/dashboard.php`
4. `pages/inventaire.php`

### SEMAINE 2 (HAUTE)
5. `pages/sms_historique.php`
6. `pages/campagne_details.php`
7. `classes/SmsService.php` (API SMS)

### SEMAINE 3 (MOYENNE)
8. Pages restantes de la Phase 1
9. Corrections UI (photos, champs texte)
10. Modules de recherche

### SEMAINE 4 (FINALISATION)
11. Modals et fonctionnalités avancées
12. Tests mobile/iPad PWA
13. Nettoyage final

---

## 💡 NOTES IMPORTANTES

- **TOUJOURS FAIRE UNE SAUVEGARDE** avant modification
- **TESTER CHAQUE PAGE** après modification
- **VÉRIFIER LES LOGS** pour détecter les erreurs
- **UTILISER UN MAGASIN DE TEST** pour les validations

---

**PRÊT À COMMENCER ? 🚀**
Suivre l'ordre de priorité et cocher chaque tâche accomplie ! 