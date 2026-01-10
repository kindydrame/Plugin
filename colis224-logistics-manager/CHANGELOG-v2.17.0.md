# Changelog - Version 2.17.0

Date: 2026-01-10

## ✨ Nouvelles Fonctionnalités

### 1. Gestion Dynamique des Pays (Feature #1)

**Problème résolu:** La liste des pays était codée en dur dans le code, rendant difficile l'ajout ou la modification de pays.

**Solution:**
- ✅ Nouvelle page admin "🌍 Pays" pour gérer dynamiquement les pays
- ✅ Interface CRUD complète : Ajouter, Modifier, Supprimer, Activer/Désactiver
- ✅ Filtrage et recherche des pays
- ✅ Compteur d'utilisation (nombre de colis par pays)
- ✅ Protection : impossible de supprimer un pays utilisé
- ✅ Tous les formulaires chargent maintenant depuis la base de données
- ✅ Support des codes ISO et drapeaux emoji

**Fichiers modifiés:**
- `admin/class-colis224-countries-admin.php` (NOUVEAU)
- `includes/class-colis224-admin.php` (ajout menu)
- `includes/class-colis224-database.php` (ajout colonne `is_active`)
- `includes/class-colis224-departures.php` (fonction `get_countries()` dynamique)
- `admin/class-colis224-colis.php` (filtre pays actifs)
- `admin/class-colis224-batches-admin.php` (filtre pays actifs)
- `admin/class-colis224-entrepots.php` (filtre pays actifs)
- `colis224-logistics-manager.php` (chargement du nouveau fichier)

**Accès:** Menu Admin → Colis224 → 🌍 Pays (nécessite rôle admin/manager)

---

### 2. Téléphone de l'Expéditeur (Feature #2)

**Problème résolu:** Le formulaire de colis ne permettait pas de saisir le téléphone de l'expéditeur.

**Solution:**
- ✅ Nouveau champ "Téléphone Expéditeur" dans le formulaire d'ajout/modification de colis
- ✅ Colonne `sender_phone` ajoutée à la table `wp_colis224_parcels`
- ✅ Validation et sanitization du numéro de téléphone
- ✅ Affichage du téléphone expéditeur dans la vue détails du colis
- ✅ Support format international (+33, +224, etc.)

**Fichiers modifiés:**
- `includes/class-colis224-database.php` (ajout colonne `sender_phone`)
- `admin/class-colis224-colis.php` (formulaire, sauvegarde, affichage)

**Emplacement:**
- Formulaire : Admin → Colis224 → Colis → Nouveau Colis/Modifier
- Affichage : Page détails du colis (section "Informations Principales")

---

### 3. Correction Bug Session Client/Dashboard (Bug #3)

**Problème résolu:** Les clients connectés perdaient leur session et le dashboard ne s'affichait plus.

**Cause identifiée:**
- Utilisation exclusive de sessions PHP (`$_SESSION`)
- Problèmes de compatibilité avec les systèmes de cache
- Sessions perdues après rechargement de page

**Solution (Système Hybride Robuste):**
- ✅ Session PHP maintenue pour compatibilité
- ✅ **Cookies sécurisés** (HttpOnly, Secure si HTTPS)
- ✅ **Tokens** stockés dans des transients WordPress (2h d'expiration)
- ✅ Restauration automatique de la session si cookie valide
- ✅ Nettoyage propre lors de la déconnexion (session + cookie + transient)
- ✅ Meilleure compatibilité avec les systèmes de cache (Varnish, Redis, etc.)

**Fichiers modifiés:**
- `includes/class-colis224-client-auth.php` (amélioration complète du système d'auth)

**Fonctionnement technique:**
1. **Connexion:** Crée session + cookie + transient avec token unique
2. **Vérification:** Vérifie d'abord session, puis cookie si session perdue
3. **Restauration:** Si cookie valide et token correspond, restaure la session automatiquement
4. **Déconnexion:** Nettoie tout (session + cookie + transient)

---

## 📋 Changements de Base de Données

### Nouvelles Colonnes

1. **Table `wp_colis224_countries`**
   ```sql
   ALTER TABLE wp_colis224_countries ADD COLUMN is_active tinyint(1) DEFAULT 1;
   ```

2. **Table `wp_colis224_parcels`**
   ```sql
   ALTER TABLE wp_colis224_parcels ADD COLUMN sender_phone varchar(50) DEFAULT NULL AFTER sender_name;
   ```

**Note:** Ces modifications sont appliquées automatiquement via `dbDelta()` lors de l'activation du plugin ou mise à jour de version.

---

## 🔒 Sécurité

### Améliorations de Sécurité

1. **Gestion des Pays:**
   - Vérification des nonces pour toutes les actions (ajout, modification, suppression)
   - Vérification des capabilities (`colis224_manage_all`)
   - Sanitization des entrées (`sanitize_text_field`)
   - Protection contre la suppression de pays utilisés

2. **Téléphone Expéditeur:**
   - Sanitization avec `sanitize_text_field()`
   - Validation format téléphone (chiffres, +, espaces autorisés)
   - Échappement lors de l'affichage (`esc_html`)

3. **Authentification Client:**
   - Tokens uniques générés avec `wp_hash()` et `wp_salt('auth')`
   - Cookies HttpOnly (protection XSS)
   - Cookies Secure si HTTPS
   - Tokens stockés en transients (expiration automatique)
   - Base64 encoding du cookie (obfuscation simple)

---

## 🧪 Comment Tester

### Test 1: Gestion des Pays

1. **Accès:**
   - Connectez-vous en tant qu'admin
   - Allez dans Colis224 → 🌍 Pays

2. **Ajouter un pays:**
   - Cliquez "Nouveau Pays"
   - Remplissez: Nom = "Allemagne", Code = "DE"
   - Cochez "Pays actif"
   - Cliquez "Ajouter le pays"
   - ✅ Vérifiez que le pays apparaît dans la liste

3. **Modifier un pays:**
   - Cliquez l'icône "✏️ Modifier" sur un pays
   - Changez le nom ou le code
   - Cliquez "Mettre à jour"
   - ✅ Vérifiez que les modifications sont enregistrées

4. **Désactiver un pays:**
   - Cliquez l'icône "👁️ Activer/Désactiver" sur un pays actif
   - Confirmez l'action
   - ✅ Vérifiez que le statut passe à "Désactivé"
   - ✅ Allez dans Colis224 → Colis → Nouveau Colis
   - ✅ Vérifiez que le pays désactivé n'apparaît PAS dans les listes déroulantes

5. **Supprimer un pays:**
   - Trouvez un pays avec "0 colis"
   - Cliquez l'icône "🗑️ Supprimer"
   - Confirmez
   - ✅ Vérifiez que le pays est supprimé
   - Essayez de supprimer un pays utilisé (compteur > 0)
   - ✅ Vérifiez qu'un message d'erreur s'affiche

### Test 2: Téléphone Expéditeur

1. **Ajout d'un colis:**
   - Allez dans Colis224 → Colis → Nouveau Colis
   - Remplissez le formulaire normalement
   - Dans "Téléphone Expéditeur", saisissez: `+33 6 12 34 56 78`
   - Soumettez le formulaire
   - ✅ Vérifiez qu'aucune erreur ne s'affiche

2. **Vérification affichage:**
   - Cliquez sur "👁️ Voir" pour le colis créé
   - Dans la section "Informations Principales" → "Expéditeur"
   - ✅ Vérifiez que le téléphone s'affiche : `📞 +33 6 12 34 56 78`

3. **Modification:**
   - Cliquez "✏️ Modifier"
   - Changez le téléphone expéditeur
   - Sauvegardez
   - ✅ Vérifiez que la modification est enregistrée

### Test 3: Connexion Client / Dashboard

1. **Connexion initiale:**
   - Déconnectez-vous si connecté
   - Allez sur la page avec le shortcode `[colis224_client_portal]`
   - Saisissez le téléphone d'un client existant + mot de passe (si requis)
   - Cliquez "Se connecter"
   - ✅ Vérifiez que vous êtes connecté et voyez le dashboard

2. **Test persistance (IMPORTANT):**
   - Restez connecté
   - Rechargez la page (F5)
   - ✅ Vérifiez que vous êtes toujours connecté
   - Fermez le navigateur
   - Rouvrez et revenez sur la page
   - ✅ Vérifiez que vous êtes toujours connecté (si < 2h)

3. **Test avec cache:**
   - Si vous avez un système de cache (Varnish, Redis, WP Rocket, etc.)
   - Purgez le cache
   - Connectez-vous
   - Rechargez plusieurs fois la page
   - ✅ Vérifiez que la session reste active

4. **Déconnexion:**
   - Cliquez sur "Se déconnecter"
   - ✅ Vérifiez que vous êtes déconnecté
   - ✅ Vérifiez que le formulaire de connexion s'affiche

5. **Test expiration (optionnel):**
   - Connectez-vous
   - Attendez 2 heures + 1 minute
   - Rechargez la page
   - ✅ Vérifiez que la session a expiré et le formulaire de connexion s'affiche

### Test 4: Intégration Complète

1. **Créer un colis avec tout:**
   - Nouveau colis
   - Sélectionnez un pays de provenance (actif)
   - Sélectionnez un pays de destination (actif)
   - Remplissez le téléphone expéditeur
   - Soumettez
   - ✅ Vérifiez que tout fonctionne sans erreur

2. **Vérifier les filtres:**
   - Désactivez le pays de provenance utilisé
   - Créez un nouveau colis
   - ✅ Vérifiez que le pays désactivé n'apparaît PAS dans la liste
   - Réactivez le pays
   - ✅ Vérifiez qu'il réapparaît dans la liste

---

## ⚠️ Notes Importantes

### Compatibilité
- ✅ WordPress 6.0+
- ✅ PHP 8.0+
- ✅ Compatible avec les systèmes de cache (amélioration majeure)
- ✅ Rétrocompatible avec les versions précédentes

### Migration Automatique
- Les colonnes DB sont ajoutées automatiquement (pas d'action manuelle requise)
- Les pays existants en DB sont automatiquement marqués comme actifs (`is_active = 1`)
- Les sessions client existantes continuent de fonctionner

### Performances
- Requêtes optimisées avec filtres `is_active = 1`
- Utilisation de transients WordPress (cache automatique)
- Pas d'impact négatif sur les performances

---

## 🐛 Bugs Corrigés

1. **Dashboard client ne s'affiche pas après connexion**
   - Cause: Sessions PHP perdues avec les systèmes de cache
   - Fix: Système hybride session + cookie + transient

2. **Impossible d'ajouter/modifier des pays**
   - Cause: Liste codée en dur
   - Fix: Interface admin complète de gestion

3. **Pays désactivés visibles dans les formulaires**
   - Cause: Pas de filtre `is_active`
   - Fix: Ajout du filtre dans toutes les requêtes

---

## 📝 Prochaines Étapes

### Améliorations Suggérées (Non implémentées dans cette version)

1. **Téléphone Expéditeur:**
   - Ajouter dans la liste des colis (colonne optionnelle)
   - Ajouter dans les exports CSV/PDF
   - Ajouter dans les factures générées

2. **Gestion des Pays:**
   - Import/Export CSV de pays
   - Gestion des drapeaux personnalisés (upload image)
   - Tri par ordre personnalisé (drag & drop)

3. **Authentification Client:**
   - Authentification à deux facteurs (2FA)
   - Durée de session configurable depuis admin
   - Historique des connexions

---

## 👨‍💻 Développeur

Modifications effectuées par Claude Code (AI Developer)
Date: 10 janvier 2026

**Contact Support:** https://github.com/anthropics/claude-code/issues
