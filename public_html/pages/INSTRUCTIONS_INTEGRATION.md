# Instructions d'intégration du nouveau modal de commande

Ce guide vous aidera à intégrer le nouveau design modernisé du modal de commande de pièces avec support des modes jour et nuit.

## Fichiers créés

1. `assets/css/modern-theme.css` - Framework CSS moderne avec variables
2. `assets/css/order-form.css` - Styles spécifiques pour le formulaire de commande
3. `assets/js/theme-switcher.js` - Gestionnaire de thèmes jour/nuit
4. `assets/css/theme-integration.css` - Fichier d'intégration des styles
5. `includes/custom_order_modal.php` - Nouveau modal de commande modernisé

## Étapes d'intégration

### 1. Ajout des fichiers CSS et JS

Ajoutez les liens vers les fichiers CSS et JS dans le header de votre site :

```php
<!-- Dans le head de votre HTML -->
<link href="assets/css/theme-integration.css" rel="stylesheet">
<script src="assets/js/theme-switcher.js" defer></script>
```

### 2. Remplacement du modal de commande

#### Option 1 : Remplacement complet

Remplacez l'ancien modal par le nouveau en modifiant `includes/modals.php` :

1. Trouvez la section du modal de commande (recherchez `id="ajouterCommandeModal"`)
2. Remplacez cette section par un include vers le nouveau modal :

```php
<?php include 'includes/custom_order_modal.php'; ?>
```

#### Option 2 : Intégration progressive

Pour une approche plus progressive, vous pouvez ajouter cette ligne en tête du fichier `includes/modals.php` :

```php
<?php
// Vérifier si on souhaite utiliser le nouveau modal
$use_modern_order_modal = true;

// Au début du fichier, avant tous les modals
if ($use_modern_order_modal) {
    include 'includes/custom_order_modal.php';
}
?>

<!-- Plus loin dans le fichier, où se trouve l'ancien modal -->
<?php if (!$use_modern_order_modal): ?>
<!-- Ancien modal ici -->
<div class="modal fade" id="ajouterCommandeModal" tabindex="-1" aria-labelledby="ajouterCommandeModalLabel" aria-hidden="true">
    <!-- Contenu de l'ancien modal -->
</div>
<?php endif; ?>
```

### 3. Ajout du support pour le thème sombre

Pour ajouter le support du mode sombre dans votre application, modifiez votre fichier principal (index.php ou layout.php) :

```php
<?php
// Vérifier et définir le mode sombre
$dark_mode = false;
if (isset($_GET['theme'])) {
    $dark_mode = $_GET['theme'] === 'dark';
    $_SESSION['dark_mode'] = $dark_mode;
} elseif (isset($_SESSION['dark_mode'])) {
    $dark_mode = $_SESSION['dark_mode'];
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $dark_mode ? 'dark' : 'light'; ?>">
<head>
    <!-- ... -->
</head>
<body class="<?php echo $dark_mode ? 'dark-mode' : ''; ?>">
    <!-- ... -->
    
    <!-- Bouton pour basculer le thème (à ajouter dans la navbar ou le footer) -->
    <button id="themeToggle" class="theme-toggle" aria-label="Changer le thème">
        <i class="fas <?php echo $dark_mode ? 'fa-sun' : 'fa-moon'; ?>"></i>
    </button>
    
    <!-- ... -->
</body>
</html>
```

### 4. Adaptation pour les fonctionnalités existantes

Si vous avez des événements JavaScript spécifiques pour le modal de commande, vous devrez les adapter au nouveau format. Consultez le fichier `includes/custom_order_modal.php` pour comprendre la nouvelle structure.

Les principaux éléments qui ont changé sont :
- Les IDs et classes CSS
- La structure HTML du formulaire
- La gestion du statut (boutons radio au lieu de boutons)

### 5. Tests

Après l'intégration, testez les fonctionnalités suivantes :
- Ouverture du modal depuis les différents points d'entrée
- Fonctionnement de la recherche de clients
- Sélection des fournisseurs et réparations liées
- Ajout de pièces supplémentaires
- Soumission du formulaire
- Basculement entre les modes jour et nuit

## Adaptation des fonctionnalités JavaScript

Voici quelques exemples d'adaptations nécessaires pour les fonctions JavaScript existantes :

#### Ancien code :
```javascript
// Sélection du statut
document.querySelectorAll('#ajouterCommandeModal .status-btn').forEach(button => {
    button.addEventListener('click', function() {
        document.querySelectorAll('#ajouterCommandeModal .status-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        this.classList.add('active');
        document.querySelector('#statut_input').value = this.getAttribute('data-status');
    });
});
```

#### Nouveau code :
```javascript
// Sélection du statut (déjà implémenté dans le nouveau modal)
document.querySelectorAll('input[name="statut"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.checked) {
            // Le statut est directement accessible via la valeur du bouton radio sélectionné
            console.log("Statut sélectionné :", this.value);
        }
    });
});
```

## Support et assistance

Si vous rencontrez des problèmes lors de l'intégration, n'hésitez pas à consulter le code source des nouveaux fichiers ou à contacter l'équipe de développement.

---

## Annexe : Aperçu du design

### Mode jour
Le design en mode jour utilise une palette de couleurs claire et moderne avec des ombres subtiles et des bordures arrondies.

### Mode nuit
Le mode nuit inverse les couleurs et réduit la luminosité pour une expérience confortable dans des environnements peu éclairés. 