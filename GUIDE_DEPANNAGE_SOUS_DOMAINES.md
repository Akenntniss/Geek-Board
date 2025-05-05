# Guide de dépannage pour les sous-domaines sur GeekBoard

Ce guide vous aidera à résoudre les problèmes courants liés à la configuration et à l'utilisation des sous-domaines dans l'application GeekBoard.

## Prérequis

Avant de commencer, assurez-vous que :

1. Vous avez accès à la configuration de votre serveur Apache
2. Vous avez accès à la configuration DNS de votre domaine
3. Vous avez accès à la base de données principale de GeekBoard

## 1. Vérification de la configuration DNS

La première étape consiste à vérifier que la configuration DNS est correcte :

1. Connectez-vous à votre panneau de gestion DNS (souvent chez votre registrar)
2. Vérifiez que vous avez un enregistrement wildcard pour votre domaine :
   ```
   *.mdgeek.top.  IN  A  <adresse_IP_de_votre_serveur>
   ```
3. Si vous n'avez pas cet enregistrement, ajoutez-le
4. Attendez la propagation DNS (peut prendre jusqu'à 24-48h, mais souvent quelques minutes)

Pour tester votre configuration DNS :
```
nslookup magasin1.mdgeek.top
nslookup autremagasin.mdgeek.top
```

Toutes ces requêtes devraient renvoyer l'adresse IP de votre serveur.

## 2. Vérification de la configuration Apache

Vérifiez que votre configuration Apache est correcte :

1. Vérifiez que le module `mod_rewrite` est activé :
   ```
   a2enmod rewrite
   ```

2. Vérifiez que le fichier de configuration du site est correctement installé :
   ```
   cd /etc/apache2/sites-available/
   ```
   
   Créez ou modifiez un fichier nommé `mdgeek.top.conf` (ou un nom similaire) avec le contenu du fichier `apache_config_subdomain.conf` de votre projet.
   
3. Activez le site :
   ```
   a2ensite mdgeek.top.conf
   ```
   
4. Redémarrez Apache :
   ```
   systemctl restart apache2
   ```

## 3. Tester la détection des sous-domaines

Utilisez les scripts de diagnostic pour tester la détection des sous-domaines :

1. Accédez à `http://magasin1.mdgeek.top/test-subdomain.php`
2. Vérifiez que le sous-domaine est correctement détecté

Si vous obtenez une erreur 403 ou 404, vérifiez :
- Les permissions des fichiers
- Les règles dans le fichier `.htaccess`
- La configuration Apache

## 4. Diagnostiquer les problèmes courants

### Erreur 403 Forbidden

Cette erreur signifie que le serveur comprend la requête mais refuse de l'autoriser.

Solutions possibles :
1. Vérifiez les permissions des fichiers dans le répertoire `public_html`
   ```
   chmod -R 755 public_html/
   chown -R www-data:www-data public_html/
   ```

2. Vérifiez que dans votre fichier de configuration Apache, vous avez :
   ```
   <Directory /chemin/vers/public_html>
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```

### Erreur 404 Not Found

Si vous obtenez une erreur 404 lorsque vous accédez à un sous-domaine :

1. Vérifiez que le sous-domaine est correctement configuré dans la base de données :
   ```sql
   SELECT * FROM shops WHERE subdomain = 'magasin1';
   ```

2. Vérifiez que les fichiers de script existent et sont accessibles :
   ```
   ls -la public_html/subdomain_handler.php
   ```

3. Utilisez le script de diagnostic pour voir si la récriture d'URL fonctionne :
   ```
   http://magasin1.mdgeek.top/subdomain_diagnostic.php
   ```

### Le sous-domaine est détecté mais le magasin n'est pas trouvé

1. Vérifiez que le magasin existe dans la base de données :
   ```sql
   SELECT id, name, subdomain FROM shops WHERE subdomain = 'magasin1';
   ```

2. Vérifiez que le magasin est actif :
   ```sql
   SELECT id, name, subdomain, active FROM shops WHERE subdomain = 'magasin1';
   ```

3. Exécutez le script de test :
   ```
   http://magasin1.mdgeek.top/test_shop_subdomain.php
   ```

## 5. Solution complète étape par étape

Si vous rencontrez toujours des problèmes, suivez ces étapes pour une réinstallation complète :

1. **Configuration DNS** :
   - Ajoutez l'enregistrement `*.mdgeek.top` pointant vers votre serveur
   - Testez avec `nslookup magasin1.mdgeek.top`

2. **Configuration Apache** :
   ```bash
   # Créer le fichier de configuration
   sudo nano /etc/apache2/sites-available/mdgeek.top.conf
   
   # Copiez le contenu du fichier apache_config_subdomain.conf
   # Remplacez /chemin/vers/public_html par le chemin réel
   
   # Activez le site
   sudo a2ensite mdgeek.top.conf
   
   # Redémarrez Apache
   sudo systemctl restart apache2
   ```

3. **Configuration .htaccess** :
   ```bash
   # Remplacez le fichier .htaccess actuel
   cp public_html/enhanced.htaccess public_html/.htaccess
   
   # Assurez-vous qu'il a les bonnes permissions
   chmod 644 public_html/.htaccess
   ```

4. **Base de données** :
   ```sql
   -- Vérifiez que la colonne subdomain existe
   SHOW COLUMNS FROM shops LIKE 'subdomain';
   
   -- Si non, ajoutez-la
   ALTER TABLE shops ADD COLUMN subdomain VARCHAR(50) DEFAULT NULL UNIQUE;
   
   -- Configurez un magasin de test
   UPDATE shops SET subdomain = 'magasin1' WHERE id = 1;
   ```

5. **Tests de validation** :
   - Accédez à `http://magasin1.mdgeek.top/subdomain_diagnostic.php`
   - Accédez à `http://magasin1.mdgeek.top/test_shop_subdomain.php`
   - Accédez à `http://magasin1.mdgeek.top` (devrait charger le magasin)

## 6. Journalisation et débogage

Pour un débogage avancé :

1. Activez la journalisation des erreurs Apache :
   ```
   sudo nano /etc/apache2/apache2.conf
   
   # Ajoutez ou modifiez
   LogLevel debug
   ```

2. Vérifiez les journaux Apache :
   ```
   tail -f /var/log/apache2/error.log
   tail -f /var/log/apache2/access.log
   ```

3. Activez la journalisation des erreurs PHP dans le fichier `.htaccess` :
   ```
   php_flag display_errors On
   php_flag log_errors On
   php_value error_log /chemin/vers/php-errors.log
   ```

Si vous avez d'autres questions ou problèmes, n'hésitez pas à demander de l'aide supplémentaire. 