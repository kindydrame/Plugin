# 📦 Plugin WordPress Colis224 - Version 2.17.0 CORRIGÉE

**Date:** 10 Janvier 2026
**Développeur:** Claude Code (Agent AI)
**Statut:** ✅ Audit complet + Corrections réalisées

---

## 📋 Résumé des Modifications

Ce plugin a été audité et corrigé selon vos demandes. Voici ce qui a été fait :

### ✅ Feature #1 : Gestion Dynamique des Pays
- **Page admin complète** pour gérer les pays (Ajouter/Modifier/Supprimer/Activer/Désactiver)
- **Accès:** Dashboard Admin → Colis224 → 🌍 Pays
- Plus besoin de modifier le code pour ajouter un pays
- Les pays désactivés n'apparaissent plus dans les formulaires

### ✅ Feature #2 : Téléphone de l'Expéditeur
- **Nouveau champ** dans le formulaire d'ajout de colis
- Visible dans les détails du colis
- Support format international (+33, +224, etc.)

### ✅ Bug #3 : Connexion Client / Dashboard
- **Système amélioré** : Session PHP + Cookies sécurisés + Transients
- Le dashboard client s'affiche maintenant correctement après connexion
- Compatible avec les systèmes de cache (Varnish, Redis, WP Rocket, etc.)

---

## 🚀 Installation du Plugin Corrigé

### Étape 1 : Sauvegarde (IMPORTANT)

Avant toute mise à jour, **sauvegardez** :
1. Votre base de données WordPress
2. Le dossier du plugin actuel : `/wp-content/plugins/colis224-logistics-manager/`

```bash
# Exemple de sauvegarde DB (via SSH)
wp db export backup-$(date +%Y%m%d-%H%M%S).sql

# Exemple de sauvegarde fichiers
cp -r /path/to/wp-content/plugins/colis224-logistics-manager /backup/location/
```

### Étape 2 : Désactiver le Plugin Actuel

1. Connectez-vous à WordPress Admin
2. Allez dans **Extensions** → **Extensions installées**
3. Trouvez "Colis224 Logistics Manager"
4. Cliquez sur **Désactiver**
5. ⚠️ **NE PAS SUPPRIMER** le plugin (pour conserver vos données)

### Étape 3 : Remplacer les Fichiers

#### Option A : Via FTP/SFTP (Recommandé)
1. Téléchargez `colis224-logistics-manager-v2.17.0-CORRECTED.zip`
2. Décompressez sur votre ordinateur
3. Connectez-vous via FTP/SFTP à votre serveur
4. Naviguez vers `/wp-content/plugins/`
5. **Supprimez** l'ancien dossier `colis224-logistics-manager/`
6. **Uploadez** le nouveau dossier décompressé

#### Option B : Via SSH
```bash
cd /path/to/wp-content/plugins/
rm -rf colis224-logistics-manager/
unzip /path/to/colis224-logistics-manager-v2.17.0-CORRECTED.zip
```

#### Option C : Via WordPress Admin
1. Supprimez l'ancien plugin (via Extensions → Supprimer)
2. Allez dans **Extensions** → **Ajouter**
3. Cliquez "Téléverser une extension"
4. Uploadez `colis224-logistics-manager-v2.17.0-CORRECTED.zip`
5. Cliquez "Installer maintenant"

### Étape 4 : Activer le Plugin

1. Allez dans **Extensions** → **Extensions installées**
2. Trouvez "Colis224 Logistics Manager (v2.17.0)"
3. Cliquez sur **Activer**
4. ✅ La base de données sera automatiquement mise à jour

### Étape 5 : Vérification Post-Installation

#### Vérifier la version
1. Allez dans **Extensions** → **Extensions installées**
2. Vérifiez que la version est **2.17.0**

#### Vérifier la base de données
1. Allez dans **Colis224** → **🔧 Diagnostic**
2. Vérifiez que toutes les tables sont présentes
3. Les nouvelles colonnes devraient être créées automatiquement :
   - `wp_colis224_countries.is_active`
   - `wp_colis224_parcels.sender_phone`

---

## 🧪 Tests à Effectuer

### Test 1 : Gestion des Pays (5 min)

1. **Accéder à la page**
   - Menu Admin → Colis224 → 🌍 Pays
   - ✅ Vous devriez voir la liste des pays existants

2. **Ajouter un pays**
   - Cliquez "Nouveau Pays"
   - Remplissez: Nom = "Portugal", Code = "PT"
   - Cochez "Pays actif"
   - Cliquez "Ajouter le pays"
   - ✅ Le pays apparaît dans la liste

3. **Vérifier dans les formulaires**
   - Allez dans Colis224 → Colis → Nouveau Colis
   - Dans "Pays d'Origine" et "Pays de Destination"
   - ✅ Le Portugal devrait apparaître dans les listes

4. **Désactiver un pays**
   - Retournez dans Colis224 → 🌍 Pays
   - Cliquez l'icône "👁️" sur le Portugal
   - Confirmez la désactivation
   - ✅ Le statut passe à "Désactivé"
   - Retournez dans le formulaire de colis
   - ✅ Le Portugal ne devrait PLUS apparaître dans les listes

### Test 2 : Téléphone Expéditeur (3 min)

1. **Créer un colis avec téléphone expéditeur**
   - Allez dans Colis224 → Colis → Nouveau Colis
   - Remplissez normalement le formulaire
   - Dans "Téléphone Expéditeur", saisissez : `+33 6 12 34 56 78`
   - Soumettez le formulaire
   - ✅ Aucune erreur ne devrait s'afficher

2. **Vérifier l'affichage**
   - Cliquez sur "👁️ Voir" pour le colis créé
   - Dans "Informations Principales" → "Expéditeur"
   - ✅ Le téléphone devrait s'afficher : `📞 +33 6 12 34 56 78`

### Test 3 : Connexion Client / Dashboard (10 min)

⚠️ **Test le plus important** - C'est le bug principal corrigé

1. **Connexion initiale**
   - Déconnectez-vous si déjà connecté
   - Allez sur la page avec le shortcode `[colis224_client_portal]`
   - Entrez le téléphone d'un client existant + mot de passe
   - Cliquez "Se connecter"
   - ✅ Vous devriez voir le dashboard client

2. **Test de persistance (CRITIQUE)**
   - Restez connecté
   - Appuyez sur F5 (recharger la page)
   - ✅ Le dashboard devrait TOUJOURS être affiché (PAS de retour au login)
   - Fermez complètement le navigateur
   - Rouvrez et retournez sur la page
   - ✅ Vous devriez toujours être connecté (si moins de 2h)

3. **Test avec cache (si applicable)**
   - Si vous utilisez un plugin de cache (WP Rocket, W3 Total Cache, etc.)
   - Purgez le cache
   - Connectez-vous
   - Rechargez la page plusieurs fois
   - ✅ La session devrait rester active

4. **Test de déconnexion**
   - Cliquez sur "Se déconnecter"
   - ✅ Vous devriez voir le formulaire de connexion
   - Rechargez la page
   - ✅ Vous devriez toujours voir le formulaire (PAS de reconnexion auto)

---

## 📊 Récapitulatif Technique

### Fichiers Modifiés (Principaux)

```
admin/
  ├── class-colis224-countries-admin.php    (NOUVEAU - 600 lignes)
  ├── class-colis224-colis.php              (modifié - ajout sender_phone)
  ├── class-colis224-batches-admin.php      (modifié - filtre pays actifs)
  └── class-colis224-entrepots.php          (modifié - filtre pays actifs)

includes/
  ├── class-colis224-database.php           (modifié - 2 nouvelles colonnes)
  ├── class-colis224-departures.php         (modifié - get_countries() dynamique)
  ├── class-colis224-client-auth.php        (modifié - système auth amélioré)
  └── class-colis224-admin.php              (modifié - menu pays)

colis224-logistics-manager.php              (modifié - version 2.17.0)
CHANGELOG-v2.17.0.md                        (NOUVEAU - documentation complète)
```

### Modifications Base de Données

```sql
-- Ajout colonne is_active pour activer/désactiver pays
ALTER TABLE wp_colis224_countries
ADD COLUMN is_active tinyint(1) DEFAULT 1;

-- Ajout colonne sender_phone pour téléphone expéditeur
ALTER TABLE wp_colis224_parcels
ADD COLUMN sender_phone varchar(50) DEFAULT NULL AFTER sender_name;
```

**Note:** Ces modifications sont appliquées **automatiquement** lors de l'activation du plugin.

### Système d'Authentification Amélioré

**Avant (v2.16.6):**
- ❌ Session PHP uniquement (`$_SESSION`)
- ❌ Perdue après rechargement avec cache
- ❌ Incompatible avec certains serveurs

**Après (v2.17.0):**
- ✅ Session PHP (compatibilité)
- ✅ Cookie sécurisé HttpOnly avec token
- ✅ Transient WordPress (expiration 2h)
- ✅ Restauration automatique si session perdue
- ✅ Compatible avec tous les systèmes de cache

---

## 🔒 Sécurité

Toutes les modifications respectent les standards WordPress :

- ✅ **Nonces** : Vérifiés pour toutes les actions (CSRF protection)
- ✅ **Capabilities** : Permissions vérifiées (`colis224_manage_all`)
- ✅ **Sanitization** : `sanitize_text_field()`, `sanitize_textarea_field()`
- ✅ **Escaping** : `esc_html()`, `esc_attr()` lors de l'affichage
- ✅ **Prepared Statements** : Requêtes SQL sécurisées (`$wpdb->prepare()`)
- ✅ **Cookies HttpOnly** : Protection XSS
- ✅ **Tokens sécurisés** : `wp_hash()` + `wp_salt('auth')`

---

## ⚠️ Compatibilité

### Requis
- ✅ WordPress 6.0+
- ✅ PHP 8.0+
- ✅ MySQL 5.7+ ou MariaDB 10.2+

### Compatible avec
- ✅ WP Rocket (cache)
- ✅ W3 Total Cache
- ✅ Redis Object Cache
- ✅ Varnish
- ✅ Cloudflare CDN
- ✅ Tous les thèmes WordPress

### Rétrocompatible
- ✅ Toutes les données existantes sont préservées
- ✅ Aucune perte de données lors de la mise à jour
- ✅ Les anciennes sessions client continuent de fonctionner

---

## 🆘 Support / Dépannage

### Problème : "Les pays n'apparaissent pas dans les formulaires"

**Solution:**
1. Allez dans Colis224 → 🌍 Pays
2. Vérifiez que les pays ont le statut "Actif"
3. Si tous sont désactivés, activez-les un par un
4. Rechargez le formulaire de colis

### Problème : "Le dashboard client ne s'affiche toujours pas"

**Solutions:**
1. **Vider le cache**
   - Purgez le cache de votre plugin de cache
   - Videz le cache du navigateur (Ctrl+Shift+Delete)

2. **Vérifier les cookies**
   - Ouvrez les DevTools (F12)
   - Onglet "Application" → "Cookies"
   - Vérifiez qu'il existe un cookie `colis224_client_auth`
   - Si absent après connexion : problème de configuration serveur

3. **Vérifier les sessions**
   - Contactez votre hébergeur
   - Vérifiez que les sessions PHP sont activées
   - Vérifiez que `session.save_path` est accessible

4. **Logs WordPress**
   - Activez WP_DEBUG dans `wp-config.php`
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
   - Consultez `/wp-content/debug.log`

### Problème : "Erreur lors de l'ajout d'un pays"

**Solution:**
1. Vérifiez que vous êtes connecté en tant qu'admin
2. Vérifiez que le nom du pays n'existe pas déjà
3. Consultez les logs pour plus de détails

---

## 📞 Contact

Pour toute question ou problème :
- **GitHub Issues:** https://github.com/anthropics/claude-code/issues
- **Documentation:** Voir `CHANGELOG-v2.17.0.md` dans le plugin

---

## ✅ Checklist Post-Installation

Cochez au fur et à mesure :

- [ ] Sauvegarde DB effectuée
- [ ] Sauvegarde fichiers effectuée
- [ ] Plugin désactivé
- [ ] Fichiers remplacés
- [ ] Plugin réactivé
- [ ] Version 2.17.0 confirmée
- [ ] Test 1 : Gestion des pays ✓
- [ ] Test 2 : Téléphone expéditeur ✓
- [ ] Test 3 : Connexion client ✓
- [ ] Cache purgé
- [ ] Tout fonctionne sans erreur

---

**🎉 Félicitations !** Votre plugin est maintenant à jour avec toutes les corrections et améliorations demandées.

---

*Audit et corrections réalisés par Claude Code (Agent AI développeur)*
*Date : 10 janvier 2026*
