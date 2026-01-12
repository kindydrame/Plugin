# 🔧 INSTRUCTIONS DE DÉPANNAGE - Agent WordPress Login

**Version:** 2.17.2-DEBUG
**Date:** 12 janvier 2026

---

## 🚨 PROBLÈME ACTUEL

Vous êtes connecté à WordPress mais le formulaire de connexion client s'affiche au lieu du dashboard agent.

---

## ✅ SOLUTION EN 3 ÉTAPES

### ÉTAPE 1: Installer le Plugin avec Mode Debug

1. **Téléchargez** le nouveau ZIP:
   ```
   /home/user/Plugin/colis224-logistics-manager-v2.17.2-DEBUG.zip
   ```

2. **Désinstallez** l'ancienne version:
   - WordPress Admin → Extensions → Colis224 Logistics Manager
   - Cliquez "Désactiver" puis "Supprimer"

3. **Installez** la nouvelle version:
   - WordPress Admin → Extensions → Ajouter
   - Téléverser: `colis224-logistics-manager-v2.17.2-DEBUG.zip`
   - Activez le plugin

### ÉTAPE 2: Activer le Mode Debug WordPress

Modifiez votre fichier `wp-config.php` et ajoutez/modifiez ces lignes **AVANT** `/* C'est tout, ne touchez pas à ce qui suit ! */`:

```php
// Activer le mode debug
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', true);
define('WP_DEBUG_LOG', false); // On n'a pas besoin des logs
@ini_set('display_errors', 1);
```

**Localisation du fichier:**
- Via FTP: `/public_html/wp-config.php` (ou `/www/`, `/htdocs/`)
- Via cPanel: File Manager → `wp-config.php`

### ÉTAPE 3: Visitez la Page et Lisez le Debug

1. **Connectez-vous** à WordPress si ce n'est pas déjà fait

2. **Visitez** votre page agent:
   ```
   https://colis224.com/espace-suivi-colis/
   ```

3. **Vous verrez** un encadré jaune en haut avec des informations de debug:

```
🔍 DEBUG - État de l'authentification

WordPress login: ✅ OUI
Utilisateur: lambanyi (lambanyi livraison)
Rôles: editor, shop_manager (ou autre)
Est agent/admin (is_wordpress_agent_or_admin): ✅ OUI ou ❌ NON
Can colis224_manage_all: ✅ OUI ou ❌ NON
Can administrator: ✅ OUI ou ❌ NON
Can manage_options: ✅ OUI ou ❌ NON
Session client active: ❌ NON
```

4. **Envoyez-moi une CAPTURE D'ÉCRAN** de cet encadré jaune

---

## 📸 CE QUE JE DOIS VOIR

Faites une capture d'écran montrant:
1. ✅ La barre d'admin WordPress (en haut, avec votre nom)
2. ✅ L'encadré jaune de debug
3. ✅ Ce qui s'affiche en dessous (formulaire login ou dashboard)

---

## 🎯 CE QUE NOUS ALLONS DÉCOUVRIR

Le debug nous dira **EXACTEMENT** pourquoi ça ne fonctionne pas:

### Cas 1: "Est agent/admin" = ❌ NON
**Problème:** Votre utilisateur n'a pas les bonnes permissions

**Solution:** Modifier le rôle de l'utilisateur:
- Option A: Changer le rôle en "Administrator"
- Option B: Ajouter la capability "colis224_manage_all" à votre rôle actuel

### Cas 2: "Est agent/admin" = ✅ OUI mais formulaire s'affiche quand même
**Problème:** Le code n'est pas chargé (cache ou erreur PHP)

**Solution:**
- Vider le cache (plugin de cache, cache serveur, cache CDN)
- Vérifier les erreurs PHP dans `wp-content/debug.log`

### Cas 3: Tous les "Can" = ❌ NON
**Problème:** Le rôle de l'utilisateur est trop restrictif

**Solution:** Créer un nouvel utilisateur avec rôle "Administrator"

---

## 🔧 SOLUTIONS RAPIDES SELON LE RÔLE

### SI VOTRE RÔLE EST "editor" OU "shop_manager":

Ces rôles **NE SONT PAS** reconnus comme agents par défaut. Vous devez:

**Option 1: Changer en Administrator (RECOMMANDÉ)**
```
1. WordPress Admin → Utilisateurs
2. Trouvez "lambanyi"
3. Cliquez "Modifier"
4. Rôle → "Administrator"
5. Enregistrer
```

**Option 2: Ajouter un plugin de gestion de rôles**

Installez "User Role Editor" plugin:
```
1. Extensions → Ajouter
2. Cherchez "User Role Editor"
3. Installez et activez
4. Allez dans Users → User Role Editor
5. Sélectionnez le rôle "Editor" ou votre rôle actuel
6. Cochez "colis224_manage_all" OU "manage_options"
7. Update
```

---

## 🚀 APRÈS LE FIX

Une fois que vous voyez le dashboard agent:

1. **Désactivez** le mode debug dans `wp-config.php`:
   ```php
   define('WP_DEBUG', false);
   ```

2. **Testez** toutes les fonctionnalités:
   - Création de colis
   - Création de clients
   - Création de départs
   - Tickets support

---

## 📞 BESOIN D'AIDE?

Si après avoir suivi ces étapes le problème persiste, envoyez-moi:

1. ✅ Capture d'écran de l'encadré jaune de debug
2. ✅ Le rôle exact de votre utilisateur (visible dans le debug)
3. ✅ Si vous avez des plugins de cache activés
4. ✅ Si vous voyez des erreurs PHP sur la page

---

## 🎓 COMPRENDRE LE PROBLÈME

### Pourquoi ça ne marchait pas avant?

Le plugin avait **DEUX systèmes d'authentification séparés**:
1. **Client login** (téléphone) → Pour les clients finaux
2. **WordPress login** (username/password) → Pour les agents

Mais le shortcode vérifiait **SEULEMENT** le client login et ignorait WordPress login.

### Ce que j'ai changé:

```
AVANT:
1. Vérifier client login → OUI? Dashboard | NON? Formulaire

APRÈS:
1. Vérifier WordPress login ET permissions → OUI? Dashboard agent
2. Sinon, vérifier client login → OUI? Dashboard client
3. Sinon → Formulaire
```

### Permissions nécessaires:

Pour être reconnu comme agent, il faut **AU MOINS UNE** de ces conditions:
- ✅ Rôle "Administrator"
- ✅ Capability "colis224_manage_all"
- ✅ Capability "manage_options"

Les rôles comme "Editor", "Shop Manager", "Author" ne suffisent PAS par défaut.

---

**Fait avec ❤️ par Claude Code**
**Session ID: rs4q8**
