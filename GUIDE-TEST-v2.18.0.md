# 🧪 GUIDE DE TEST - Version 2.18.0

**Plugin:** Colis224 Logistics Manager
**Version:** 2.18.0
**Date:** 13 janvier 2026
**Système:** Séparation Agent/Client + Workflow d'Approbation

---

## 📋 RÉSUMÉ DES CHANGEMENTS v2.18.0

### Nouveau Système d'Architecture

**AVANT (v2.17.x):**
- Un seul shortcode `[colis224_client_portal]` pour tout le monde
- Agents et clients mélangés dans la même interface
- Bugs de permissions et d'authentification

**APRÈS (v2.18.0):**
- ✅ **Shortcode Agent:** `[colis224_agent_portal]` - Espace dédié agents
- ✅ **Shortcode Client:** `[colis224_client_portal]` - Clients uniquement (lecture seule)
- ✅ **Workflow d'approbation:** Agents créent → Admin valide → Exécuté
- ✅ **Sécurité renforcée:** Vérifications serveur (capabilities + nonce + AJAX)

### Nouveaux Éléments

1. **Base de données:**
   - Colonnes `approval_status` (pending/approved/rejected) dans `wp_colis224_parcels`
   - Colonnes `approval_status` dans `wp_colis224_departures`
   - Table `wp_colis224_approval_logs` pour l'audit

2. **Pages/Shortcodes:**
   - `[colis224_agent_portal]` - Espace agent complet
   - Page Admin "Validations" avec badge de compteur

3. **Fichiers:**
   - `includes/class-colis224-db-migration.php` - Gestion migrations DB
   - `includes/class-colis224-agent-portal.php` - Portail agent
   - `admin/class-colis224-approvals.php` - Page validation admin

---

## ⚙️ PRÉREQUIS AVANT LES TESTS

### 1. Installation du Plugin v2.18.0

1. **Désinstaller** l'ancienne version:
   - WordPress Admin → Extensions → Colis224 Logistics Manager
   - Désactiver puis Supprimer

2. **Installer** la nouvelle version:
   - Extensions → Ajouter → Téléverser
   - Sélectionner: `colis224-logistics-manager-v2.18.0.zip`
   - Activer le plugin

3. **Vérifier la migration DB:**
   - Allez dans WordPress Admin → Colis224
   - Si la page se charge sans erreur, la migration a réussi ✅

### 2. Création des Pages avec Shortcodes

**Page Espace Agent** (nouvelle):
```
Titre: Espace Agent
Slug: espace-agent
Contenu: [colis224_agent_portal]
```

**Page Espace Client** (existante - vérifier):
```
Titre: Espace Suivi Colis
Slug: espace-suivi-colis
Contenu: [colis224_client_portal]
```

### 3. Comptes de Test Requis

Vous aurez besoin de **3 comptes** pour tester:

1. **Compte ADMIN:**
   - Rôle: Administrator
   - Username: admin (ou votre compte actuel)
   - Utilisé pour: Validation des actions

2. **Compte AGENT:**
   - Rôle: Administrator (ou rôle avec `colis224_manage_all`)
   - Username: agent_test
   - Utilisé pour: Création de colis/départs

3. **Compte CLIENT:**
   - Pas de compte WordPress
   - Téléphone: +224 612 34 56 78 (exemple)
   - Utilisé pour: Consultation uniquement

---

## 🧪 TEST 1: ESPACE CLIENT (Lecture Seule)

**Objectif:** Vérifier que les clients peuvent **UNIQUEMENT** consulter leurs colis, pas créer.

### Étape 1.1: Créer un Client et un Colis (Côté Admin)

1. **Se connecter** en tant qu'**ADMIN**
2. **Aller dans** Colis224 → Clients → Ajouter un Client
   - Nom: Test Client
   - Téléphone: `+224612345678`
   - Enregistrer
3. **Aller dans** Colis224 → Colis → Ajouter un Colis
   - Client: Test Client
   - N° Suivi: `TEST001`
   - Destinataire: John Doe
   - Poids: 5 kg
   - Status: `en_transit`
   - **IMPORTANT:** Laisser `approval_status` à `approved` (défaut)
   - Enregistrer

### Étape 1.2: Tester la Consultation Client

1. **Se déconnecter** de WordPress
2. **Visiter:** `https://votresite.com/espace-suivi-colis/`
3. **Se connecter** avec le téléphone client:
   - Téléphone: `+224612345678`
   - Cliquer "Se connecter"

### ✅ Résultats Attendus:

- ✅ Le formulaire de login s'affiche (pas connecté à WordPress)
- ✅ Après connexion: Dashboard client s'affiche
- ✅ **ONGLET VISIBLE:** "Mes Colis", "Suivi Colis", "Tickets", "Chat"
- ✅ **ONGLET INVISIBLE:** "Ajouter" (pas d'onglet de création)
- ✅ Le colis `TEST001` s'affiche dans "Mes Colis"
- ✅ Statut: "En Transit" visible
- ✅ Boutons: "Voir Détails", "Signaler un Problème" (pas de "Modifier")

### ❌ Erreurs à Surveiller:

- ❌ Si l'onglet "Ajouter" s'affiche → BUG (client ne doit PAS créer)
- ❌ Si le client peut modifier un colis → BUG
- ❌ Si aucune data ne s'affiche → Problème AJAX ou session

---

## 🧪 TEST 2: ESPACE AGENT (Création Pending)

**Objectif:** Vérifier que les agents peuvent créer des colis/départs en statut `pending`.

### Étape 2.1: Préparer un Compte Agent

1. **Se connecter** en tant qu'**ADMIN**
2. **Aller dans** Utilisateurs → Ajouter
   - Username: `agent_test`
   - Email: agent@test.com
   - Rôle: **Administrator** (ou ajoutez `colis224_manage_all` à un rôle custom)
   - Mot de passe: définir un mot de passe
   - Enregistrer

### Étape 2.2: Tester la Connexion Agent WordPress

1. **Se déconnecter** du compte admin
2. **Se connecter** avec `agent_test` via **WordPress login standard**
3. **Visiter:** `https://votresite.com/espace-agent/`

### ✅ Résultats Attendus (Vue Initiale):

- ✅ **Pas de formulaire client** - Dashboard agent s'affiche directement
- ✅ **Titre:** "Espace Agent - Colis224"
- ✅ **Stats affichées:**
  - "📦 Colis en Attente: 0"
  - "✈️ Départs en Attente: 0"
- ✅ **Onglets visibles:**
  - "📦 Créer un Colis"
  - "✈️ Créer un Départ"
  - "⏳ En Attente (0)"

### Étape 2.3: Créer un Colis en Tant qu'Agent

1. **Cliquer** sur l'onglet "📦 Créer un Colis"
2. **Remplir le formulaire:**
   - Client: Sélectionner "Test Client" (créé précédemment)
   - N° Suivi: `AGENT001`
   - Destinataire: Jane Smith
   - Téléphone destinataire: +33 6 12 34 56 78
   - Poids: 10
   - Description: Test création agent
   - Prix: 5000
3. **Cliquer** "Créer le Colis"

### ✅ Résultats Attendus (Après Création):

- ✅ **Message succès:** "✅ Colis créé avec succès ! En attente de validation admin."
- ✅ **Onglet "En Attente"** affiche maintenant: "(1)"
- ✅ **Stats mises à jour:** "📦 Colis en Attente: 1"
- ✅ **Dans l'onglet "En Attente":**
  - Le colis `AGENT001` s'affiche
  - Badge: "🟡 En Attente"
  - Client: Test Client
  - Destinataire: Jane Smith
  - Poids: 10 kg

### Étape 2.4: Créer un Départ en Tant qu'Agent

1. **Cliquer** sur l'onglet "✈️ Créer un Départ"
2. **Remplir le formulaire:**
   - N° Départ: `DEP001`
   - Ville départ: Conakry
   - Ville arrivée: Paris
   - Date départ: Choisir une date future
   - Type transport: Avion
   - Transporteur: Air France
3. **Cliquer** "Créer le Départ"

### ✅ Résultats Attendus (Après Création Départ):

- ✅ **Message succès:** "✅ Départ créé avec succès ! En attente de validation admin."
- ✅ **Stats:** "✈️ Départs en Attente: 1"
- ✅ **Onglet "En Attente":** Affiche le départ `DEP001`
- ✅ **Badge:** "🟡 En Attente"

### Étape 2.5: Vérifier que les Items ne sont PAS Visibles Publiquement

1. **Aller dans** l'espace client (`/espace-suivi-colis/`)
2. **Se connecter** avec le client `+224612345678`
3. **Vérifier** "Mes Colis"

### ✅ Résultats Attendus:

- ✅ **Le colis `AGENT001` ne s'affiche PAS** (status pending)
- ✅ **Seul le colis `TEST001` s'affiche** (status approved)

### ❌ Erreurs à Surveiller:

- ❌ Si le formulaire client s'affiche au lieu du dashboard agent → BUG WordPress auth
- ❌ Si les colis créés sont `approved` au lieu de `pending` → BUG insertion DB
- ❌ Si les items pending s'affichent aux clients → BUG filtrage AJAX

---

## 🧪 TEST 3: VALIDATION ADMIN

**Objectif:** Vérifier que l'admin peut valider/rejeter les actions agents.

### Étape 3.1: Accéder à la Page de Validation

1. **Se connecter** en tant qu'**ADMIN**
2. **Aller dans** WordPress Admin → **Colis224** → **Validations**
3. **Vérifier le badge** dans le menu

### ✅ Résultats Attendus (Vue Initiale):

- ✅ **Titre page:** "Validation des Actions Agents"
- ✅ **Menu badge:** "Validations **2**" (1 colis + 1 départ)
- ✅ **Section 1:** "📦 Colis en Attente (1)"
- ✅ **Section 2:** "✈️ Départs en Attente (1)"

### Étape 3.2: Détails des Items Affichés

**Section Colis:**

| Colonne | Valeur Attendue |
|---------|----------------|
| N° Suivi | AGENT001 |
| Client | Test Client (+224612345678) |
| Destinataire | Jane Smith (+33 6 12 34 56 78) |
| Poids | 10 kg |
| Agent | agent_test (ou nom affiché) |
| Date Création | Date/heure actuelle |
| Actions | Boutons "Approuver" + "Rejeter" |

**Section Départs:**

| Colonne | Valeur Attendue |
|---------|----------------|
| N° Départ | DEP001 |
| Route | Conakry → Paris |
| Date Départ | Date sélectionnée |
| Transport | ✈️ Avion |
| Agent | agent_test |
| Date Création | Date/heure actuelle |
| Actions | Boutons "Approuver" + "Rejeter" |

### Étape 3.3: Approuver un Colis

1. **Cliquer** sur le bouton "✅ Approuver" du colis `AGENT001`
2. **Observer** la redirection

### ✅ Résultats Attendus:

- ✅ **Redirection** vers la même page
- ✅ **Message succès:** "✅ Action approuvée avec succès !"
- ✅ **Le colis `AGENT001` disparaît** de la liste
- ✅ **Compteur:** "📦 Colis en Attente (0)"
- ✅ **Badge menu:** "Validations **1**" (il reste le départ)

### Étape 3.4: Vérifier que le Colis est Maintenant Visible aux Clients

1. **Aller dans** l'espace client (`/espace-suivi-colis/`)
2. **Se connecter** avec `+224612345678`
3. **Vérifier** "Mes Colis"

### ✅ Résultats Attendus:

- ✅ **Le colis `AGENT001` s'affiche maintenant** (approved)
- ✅ **Statut:** "En Attente" (status parcel = `pending` par défaut)
- ✅ **Client peut:** Voir détails, créer ticket

### Étape 3.5: Rejeter un Départ

1. **Retourner** sur la page Admin → Colis224 → Validations
2. **Cliquer** sur le bouton "⛔ Rejeter" du départ `DEP001`
3. **Modal s'ouvre** avec un champ "Raison du rejet"

### ✅ Résultats Attendus (Modal):

- ✅ **Titre:** "Rejeter l'Action"
- ✅ **Champ visible:** Textarea "Raison du rejet"
- ✅ **Placeholder:** "Expliquez pourquoi cette action est rejetée..."
- ✅ **Boutons:** "Confirmer le Rejet" + "Annuler"

4. **Saisir** une raison:
   ```
   Date de départ incorrecte, veuillez vérifier avec le transporteur
   ```
5. **Cliquer** "Confirmer le Rejet"

### ✅ Résultats Attendus (Après Rejet):

- ✅ **Redirection** vers la même page
- ✅ **Message warning:** "⛔ Action rejetée."
- ✅ **Le départ `DEP001` disparaît** de la liste
- ✅ **Message:** "✅ Aucune action en attente de validation."
- ✅ **Badge menu:** "Validations" (sans compteur)

### Étape 3.6: Vérifier la Visibilité du Rejet pour l'Agent

1. **Se connecter** avec le compte `agent_test`
2. **Visiter** `/espace-agent/`
3. **Aller** dans l'onglet "⏳ En Attente"

### ✅ Résultats Attendus:

- ✅ **Stats:** "📦 Colis en Attente: 0" (approuvé)
- ✅ **Stats:** "✈️ Départs en Attente: 0" (rejeté = supprimé de pending)
- ✅ **Liste vide** dans "En Attente"

**Note:** Pour la v2.18.0, les items rejetés ne s'affichent pas dans l'interface agent. Dans une future version, vous pourriez ajouter un onglet "Rejetés" pour que l'agent voie la raison du rejet.

### ❌ Erreurs à Surveiller:

- ❌ Si le badge ne met pas à jour le compteur → BUG SQL ou cache
- ❌ Si l'item reste visible après approbation/rejet → BUG update DB
- ❌ Si le modal ne s'ouvre pas → Erreur JavaScript
- ❌ Si le nonce échoue → Erreur de sécurité (vérifier nonce generation)

---

## 🧪 TEST 4: VÉRIFICATIONS DE SÉCURITÉ

**Objectif:** S'assurer que les permissions sont respectées côté serveur.

### Test 4.1: Client Ne Peut PAS Créer de Colis via AJAX

1. **Se connecter** en tant que **client** (`/espace-suivi-colis/`)
2. **Ouvrir la console du navigateur** (F12)
3. **Coller et exécuter** ce code JavaScript:

```javascript
jQuery.ajax({
    url: colis224_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'colis224_agent_create_parcel',
        nonce: 'fake_nonce',
        client_id: 1,
        tracking_number: 'HACK001',
        recipient_name: 'Hacker',
        recipient_phone: '+1234567890',
        weight: 1,
        description: 'Test',
        price: 1000
    },
    success: function(response) {
        console.log('Response:', response);
    }
});
```

### ✅ Résultats Attendus:

- ✅ **Erreur retournée:** `"⛔ Accès refusé : Réservé aux agents WordPress."`
- ✅ **Statut:** `success: false`
- ✅ **Pas de colis créé** dans la DB

### Test 4.2: Agent Non-Connecté WordPress Ne Peut PAS Accéder à l'Espace Agent

1. **Se déconnecter** complètement de WordPress
2. **Visiter** `/espace-agent/`

### ✅ Résultats Attendus:

- ✅ **Message d'erreur affiché:**
  ```
  🔒 Connexion Requise

  Vous devez être connecté à WordPress en tant qu'agent pour accéder à cet espace.

  [Se connecter à WordPress]
  ```
- ✅ **Lien:** Redirige vers `wp-login.php`

### Test 4.3: Vérifier les Nonces AJAX

1. **Se connecter** en tant qu'**agent_test**
2. **Aller dans** `/espace-agent/`
3. **Ouvrir la console** (F12)
4. **Créer un colis** normalement via le formulaire
5. **Observer la requête AJAX** dans l'onglet "Network"

### ✅ Résultats Attendus:

- ✅ **Paramètre `nonce`** présent dans la requête POST
- ✅ **Nonce valide:** 10 caractères alphanumériques
- ✅ **Si nonce invalide/expiré:** Erreur `"Vérification de sécurité échouée"`

---

## 🧪 TEST 5: MIGRATION BASE DE DONNÉES

**Objectif:** Vérifier que la migration DB s'est bien exécutée.

### Étape 5.1: Vérifier les Colonnes via phpMyAdmin

1. **Se connecter** à phpMyAdmin
2. **Sélectionner** votre base de données WordPress
3. **Ouvrir** la table `wp_colis224_parcels`
4. **Aller** dans l'onglet "Structure"

### ✅ Colonnes Attendues (Nouvelles):

| Colonne | Type | Défaut |
|---------|------|--------|
| `approval_status` | ENUM('pending','approved','rejected') | 'approved' |
| `approved_by` | BIGINT(20) UNSIGNED | NULL |
| `approved_at` | DATETIME | NULL |
| `rejection_reason` | TEXT | NULL |

5. **Répéter** pour la table `wp_colis224_departures`

### Étape 5.2: Vérifier la Table de Logs

1. **Vérifier** que la table `wp_colis224_approval_logs` existe
2. **Structure attendue:**

| Colonne | Type |
|---------|------|
| `id` | BIGINT(20) UNSIGNED AUTO_INCREMENT |
| `entity_type` | ENUM('parcel','departure','client') |
| `entity_id` | BIGINT(20) UNSIGNED |
| `action` | ENUM('submitted','approved','rejected') |
| `user_id` | BIGINT(20) UNSIGNED |
| `user_name` | VARCHAR(255) |
| `reason` | TEXT |
| `created_at` | DATETIME DEFAULT CURRENT_TIMESTAMP |

### Étape 5.3: Vérifier les Logs d'Approbation

1. **Exécuter** cette requête SQL:

```sql
SELECT * FROM wp_colis224_approval_logs ORDER BY created_at DESC;
```

### ✅ Résultats Attendus:

Après avoir fait les tests ci-dessus, vous devriez voir:

| id | entity_type | entity_id | action | user_name | reason | created_at |
|----|-------------|-----------|--------|-----------|--------|------------|
| 2 | departure | 1 | rejected | admin | Date de départ incorrecte... | [timestamp] |
| 1 | parcel | 1 | approved | admin | NULL | [timestamp] |

---

## 🧪 TEST 6: DONNÉES EXISTANTES (Non-Régression)

**Objectif:** S'assurer que les colis/départs existants fonctionnent toujours.

### Étape 6.1: Vérifier les Colis Existants

1. **Se connecter** en tant qu'**ADMIN**
2. **Aller dans** Colis224 → Colis
3. **Vérifier** que tous les colis existants s'affichent

### ✅ Résultats Attendus:

- ✅ **Tous les colis** créés avant v2.18.0 s'affichent
- ✅ **Leur `approval_status`** est `approved` (valeur par défaut de la migration)
- ✅ **Aucune erreur** SQL ou PHP

### Étape 6.2: Vérifier l'Affichage Client des Colis Existants

1. **Se connecter** en tant que client
2. **Vérifier** "Mes Colis"

### ✅ Résultats Attendus:

- ✅ **Les colis existants** du client s'affichent normalement
- ✅ **Pas de changement** dans l'affichage (tracking, détails, tickets)

---

## 📊 RÉCAPITULATIF DES TESTS

| # | Test | Statut | Notes |
|---|------|--------|-------|
| 1 | Espace Client (Lecture seule) | ⬜ À tester | Pas d'onglet "Ajouter" |
| 2 | Espace Agent (Création pending) | ⬜ À tester | Colis/départs en attente |
| 3 | Validation Admin | ⬜ À tester | Approuver/Rejeter avec raison |
| 4 | Sécurité AJAX | ⬜ À tester | Nonces + capabilities |
| 5 | Migration DB | ⬜ À tester | Colonnes + table logs |
| 6 | Non-régression | ⬜ À tester | Données existantes OK |

---

## 🐛 ERREURS COURANTES ET SOLUTIONS

### Erreur 1: "Call to undefined function Colis224_DB_Migration::log_approval_action()"

**Cause:** Le fichier `class-colis224-db-migration.php` n'est pas chargé.

**Solution:**
```php
// Vérifier dans colis224-logistics-manager.php ligne ~247
require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-db-migration.php';
```

### Erreur 2: Le Badge "Validations" ne s'affiche pas

**Cause:** Requête SQL échoue ou classe non chargée.

**Solution:**
1. Vérifier dans phpMyAdmin que les colonnes `approval_status` existent
2. Vérifier que `class-colis224-approvals.php` est chargé (ligne ~249)
3. Vérifier les logs PHP: `wp-content/debug.log`

### Erreur 3: "Nonce verification failed"

**Cause:** Les nonces WordPress ont expiré (24h par défaut).

**Solution:**
1. Rafraîchir la page (F5)
2. Se reconnecter à WordPress
3. Vider le cache du navigateur

### Erreur 4: Les Items Pending s'affichent aux Clients

**Cause:** Filtrage AJAX manquant ou mal implémenté.

**Solution:**
1. Vérifier dans `class-colis224-client-portal-enhanced.php` les requêtes AJAX
2. S'assurer que les requêtes incluent: `WHERE approval_status = 'approved'`

### Erreur 5: Modal de Rejet ne s'ouvre pas

**Cause:** Erreur JavaScript ou conflit avec un autre plugin.

**Solution:**
1. Ouvrir la console (F12) et chercher des erreurs JS
2. Désactiver temporairement les autres plugins
3. Tester avec un thème par défaut (Twenty Twenty-Four)

---

## ✅ CRITÈRES DE VALIDATION FINALE

Pour considérer la v2.18.0 comme **STABLE et PRÊTE POUR PRODUCTION**, tous ces points doivent être ✅:

- [ ] ✅ Les clients ne peuvent PAS créer de colis (lecture seule)
- [ ] ✅ Les agents peuvent créer des colis en statut `pending`
- [ ] ✅ Les agents peuvent créer des départs en statut `pending`
- [ ] ✅ Les items `pending` ne s'affichent PAS aux clients
- [ ] ✅ L'admin voit les items pending dans "Validations"
- [ ] ✅ Le badge affiche le bon compteur
- [ ] ✅ L'approbation met à jour le statut en `approved`
- [ ] ✅ Le rejet met à jour le statut en `rejected`
- [ ] ✅ La raison du rejet est enregistrée
- [ ] ✅ Les logs d'approbation sont enregistrés dans la DB
- [ ] ✅ Les colis existants fonctionnent normalement (non-régression)
- [ ] ✅ Aucune erreur PHP dans `debug.log`
- [ ] ✅ Les nonces AJAX sont vérifiés
- [ ] ✅ Les capabilities WordPress sont respectées
- [ ] ✅ La migration DB s'exécute sans erreur

---

## 📞 SUPPORT

Si vous rencontrez des problèmes pendant les tests, fournissez:

1. ✅ **Capture d'écran** de l'erreur
2. ✅ **Console du navigateur** (F12 → onglet Console)
3. ✅ **Logs PHP** (wp-content/debug.log si WP_DEBUG activé)
4. ✅ **Requête SQL** qui échoue (si erreur DB)
5. ✅ **Version PHP** et **Version WordPress**

---

**Bonne chance pour les tests ! 🚀**

**Fait avec ❤️ par Claude Code**
**Session ID: rs4q8**
