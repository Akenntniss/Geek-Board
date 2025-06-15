# 🔧 Guide de Dépannage - Exportation PDF Page Commandes Pièces

## 🎯 Problème Identifié

La fonction d'exportation PDF ne fonctionnait pas dans la page `commandes_pieces.php` car **l'événement click n'était pas attaché au bouton**.

## ✅ Corrections Apportées

### 1. **Ajout de l'événement click** (`assets/js/commandes.js`)
```javascript
// Dans la section d'initialisation DOM
const exportPdfBtn = document.getElementById('export-pdf-btn');
if (exportPdfBtn) {
    exportPdfBtn.addEventListener('click', function() {
        console.log("Bouton d'exportation PDF cliqué");
        exportPDF();
    });
}
```

### 2. **Correction de l'ordre des colonnes**
L'ordre des colonnes dans la fonction `exportPDF()` a été corrigé pour correspondre au tableau HTML :
- **ID** → **Client** → **Pièce** → **Fournisseur** → **Quantité** → **Prix** → **Date** → **Statut**

## 🧪 Test de la Correction

### Option 1: Test avec la page réelle
1. Accédez à `index.php?page=commandes_pieces`
2. Cliquez sur le bouton **"Exporter PDF"** 
3. Vérifiez que le PDF se télécharge correctement

### Option 2: Test avec la page de diagnostic
1. Accédez à `public_html/test_pdf_export.html`
2. Cliquez sur **"Tester Exportation PDF"**
3. Consultez les diagnostics détaillés

## 🔍 Diagnostic Étape par Étape

### 1. Vérifier la Console JavaScript
Ouvrez les outils de développement (F12) et vérifiez :
```javascript
// Ces messages doivent apparaître :
"Événement click attaché au bouton d'exportation PDF"
"Bouton d'exportation PDF cliqué"
"Début de l'exportation des commandes en PDF..."
```

### 2. Vérifier les Bibliothèques
```javascript
// Dans la console, testez :
console.log(window.jspdf);           // Doit retourner un objet
console.log(typeof exportPDF);       // Doit retourner 'function'
```

### 3. Vérifier le Tableau
```javascript
// Testez l'extraction des données :
const tableRows = document.querySelectorAll('#commandesTableBody tr');
console.log(tableRows.length);       // Doit être > 0
```

## 🔧 Dépannage Avancé

### Si le bouton ne répond toujours pas :

1. **Vérifier l'ID du bouton**
```html
<!-- Dans commandes_pieces.php, ligne ~571 -->
<button type="button" class="btn btn-success d-flex align-items-center" id="export-pdf-btn">
```

2. **Vérifier le chargement des scripts**
```html
<!-- Scripts requis dans la page -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
<script src="assets/js/commandes.js"></script>
```

3. **Test manuel dans la console**
```javascript
// Force l'exécution de la fonction
exportPDF();
```

### Si les bibliothèques ne se chargent pas :

1. **Vérifier la connectivité internet**
2. **Tester avec des CDN alternatifs** :
```html
<!-- Alternative CDN -->
<script src="https://unpkg.com/jspdf@latest/dist/jspdf.umd.min.js"></script>
<script src="https://unpkg.com/jspdf-autotable@latest/dist/jspdf.plugin.autotable.min.js"></script>
```

## 📝 Fichiers Modifiés

### `assets/js/commandes.js`
- ✅ Ajout de l'événement click pour le bouton d'exportation
- ✅ Correction de l'ordre des colonnes dans l'extraction des données  
- ✅ Correction de l'ordre des colonnes dans la génération du PDF

### `test_pdf_export.html` (nouveau)
- ✅ Page de test avec diagnostic détaillé
- ✅ Simulation du tableau des commandes
- ✅ Tests des bibliothèques jsPDF et autoTable

## 🚀 Vérification Finale

### Checklist de Test
- [ ] Le bouton "Exporter PDF" est visible
- [ ] Clic sur le bouton déclenche la fonction
- [ ] Les données du tableau sont extraites correctement
- [ ] Le PDF se génère avec le bon ordre de colonnes
- [ ] Le fichier PDF se télécharge automatiquement
- [ ] Le contenu du PDF correspond aux données affichées

### Messages de Succès Attendus
```
✅ Événement click attaché au bouton d'exportation PDF
✅ Fonction exportPDF() appelée
✅ X lignes trouvées dans le tableau
✅ Bibliothèque jsPDF disponible
✅ Plugin autoTable disponible
🎉 PDF généré avec succès: commandes_pieces_15-06-2025.pdf
```

## 💡 Notes Importantes

- **Architecture Multi-Database** : L'exportation PDF fonctionne côté client (JavaScript) et n'utilise pas les connexions database PHP
- **Données Visibles Uniquement** : Seules les commandes actuellement visibles (non filtrées) sont exportées
- **Filtres Appliqués** : Le PDF inclut les informations sur les filtres actifs
- **Format Français** : Dates et formatage adaptés au format français

## 🆘 Support

Si le problème persiste après ces corrections :

1. **Examiner les logs de la console** pour identifier l'erreur exacte
2. **Tester avec la page de diagnostic** `test_pdf_export.html`
3. **Vérifier que tous les scripts sont bien chargés** dans l'ordre correct
4. **S'assurer que les CDN sont accessibles** depuis votre environnement

---

**🎯 Résumé** : Le problème principal était l'absence de l'événement click sur le bouton d'exportation PDF. Avec les corrections apportées, la fonction devrait maintenant fonctionner correctement. 