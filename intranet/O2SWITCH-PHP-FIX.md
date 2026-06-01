# Corriger « PHP not executed » (fichier .php affiché en texte)

## Symptôme

`curl ping.php` renvoie `<?php` au lieu de `{"ok":true}`.

## Cause sur o2switch

Le compte utilise **PHP Selector** (alt-php 8.5) mais un vieux bloc **ea-php72** dans `.htaccess` force Apache à servir les `.php` comme fichiers statiques.

## Correction manuelle (5 min)

### 1. PHP Selector (cPanel)

- Version : **8.5** (ou 8.2 minimum)
- Extensions : `pdo`, `pdo_pgsql`, `mbstring`, `intl`, `json`, `openssl`, `curl`, `xml`, `zip`, `ctype`, `fileinfo`, `dom`
- **Enregistrer**

### 2. Fichier `.htaccess` à la racine du site

Chemin : `academy.clouddevfusion.com/race8462/.htaccess`

**Supprimer** toute ligne contenant : `ea-php72`, `ea-php84`, `php84`, `suPHP_ConfigPath`.

**Coller en haut du fichier** (ou remplacer le bloc `# php -- BEGIN cPanel` existant) :

```apache
# php -- BEGIN cPanel-generated handler, do not edit
<IfModule mime_module>
  AddHandler application/x-httpd-alt-php85___lsphp .php .php5 .phtml
</IfModule>
<FilesMatch "\.(php|phtml)$">
  SetHandler application/x-httpd-alt-php85___lsphp
</FilesMatch>
# php -- END cPanel-generated handler, do not edit
```

Si PHP 8.5 n’est pas disponible, remplace `php85` par `php84` ou `php82`.

### 3. Tester

https://academy.clouddevfusion.com/intranet/backend/public/ping.php → `{"ok":true}`

### 4. Relancer GitHub Actions

Job **Deploy Intranet Backend** → l’étape `Verify intranet backend endpoint` doit passer.
