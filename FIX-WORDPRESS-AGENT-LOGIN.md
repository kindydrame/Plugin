# 🔧 FIX: Connexion Agent WordPress - v2.17.1

**Date:** 11 janvier 2026
**Commit:** 62cf563
**Priorité:** 🔥 CRITIQUE

---

## 🐛 PROBLÈME RÉSOLU

### Symptôme
Les agents WordPress connectés via `wp-login.php` voyaient **le formulaire de connexion client** au lieu de leur **dashboard agent**, malgré qu'ils soient **connectés à WordPress**.

### Exemple de Comportement Problématique

**Avant le fix:**
```
1. Agent crée compte WordPress (rôle: administrator ou colis224_agent)
2. Agent se connecte via wp-login.php ✅ Connexion réussie
3. Agent visite page avec [colis224_client_portal] ❌ Voit formulaire de connexion client
4. Agent est confus car déjà connecté à WordPress
```

**HTML observé (fourni par l'utilisateur):**
```html
<body class="logged-in">  <!-- ✅ Connecté à WordPress -->
<div class="user-roles">colis224_agent</div>  <!-- ✅ Bon rôle -->
<!-- Mais le shortcode affiche quand même le formulaire de connexion ❌ -->
```

---

## 🔍 CAUSE RACINE

### Architecture Duale (Problématique)

Le plugin utilisait **DEUX systèmes d'authentification séparés SANS pont** entre eux :

#### 1. Authentification Client (téléphone)
- **Fichier:** `includes/class-colis224-client-auth.php`
- **Méthode:** Connexion par téléphone + session PHP
- **Stockage:** `$_SESSION['colis224_client_id']` + cookies
- **Table DB:** `wp_colis224_clients`
- **Public:** Clients finaux (particuliers)

#### 2. Authentification WordPress (standard)
- **Fichier:** WordPress core (`wp-login.php`)
- **Méthode:** Connexion username/password standard
- **Stockage:** Cookies WordPress (`wordpress_logged_in_*`)
- **Table DB:** `wp_users`
- **Public:** Agents, admins

### Le Problème dans le Code

**Fichier:** `includes/class-colis224-frontend-portal.php`
**Fonction:** `client_portal_shortcode()` (ligne 224)

**Code AVANT le fix:**
```php
public function client_portal_shortcode($atts) {
    // ...

    // ❌ VÉRIFIE SEULEMENT LES SESSIONS CLIENT
    $is_logged_in = Colis224_Client_Auth::is_client_logged_in();

    if (!$is_logged_in) {
        $this->display_login_form(); // ❌ Affiche formulaire même si WordPress connecté
    } else {
        $this->display_client_dashboard();
    }
}
```

**Fonction `is_client_logged_in()` :**
```php
public static function is_client_logged_in() {
    // ❌ VÉRIFIE UNIQUEMENT $_SESSION ET COOKIES CLIENT
    if (isset($_SESSION['colis224_client_id'])) {
        return true;
    }

    if (isset($_COOKIE['colis224_client_auth'])) {
        // Restaurer session depuis cookie
        return true;
    }

    return false;

    // ❌ AUCUNE VÉRIFICATION is_user_logged_in() POUR WORDPRESS
}
```

**Résultat:** Même si l'agent est connecté à WordPress, le shortcode l'ignore complètement et cherche uniquement une session client.

---

## ✅ SOLUTION IMPLÉMENTÉE

### Approche: Système de Priorité à Deux Niveaux

```
PRIORITÉ 1: Authentification WordPress (agents/admins)
    ↓ Si non trouvé
PRIORITÉ 2: Authentification Client (téléphone)
    ↓ Si non trouvé
AFFICHER: Formulaire de connexion client
```

### Modifications Apportées

#### 1️⃣ **Modification du Shortcode** (`class-colis224-frontend-portal.php`)

**Code APRÈS le fix:**
```php
public function client_portal_shortcode($atts) {
    ob_start();

    // S'assurer que la session est démarrée
    if (!session_id() && !headers_sent()) {
        session_start();
    }

    // 🔥 PRIORITÉ 1: Vérifier WordPress AVANT client
    if (is_user_logged_in() && $this->is_wordpress_agent_or_admin()) {
        // ✅ Afficher dashboard agent WordPress
        $this->display_wordpress_agent_dashboard();
    }
    // PRIORITÉ 2: Vérifier session client
    else {
        $is_logged_in = Colis224_Client_Auth::is_client_logged_in();

        if (!$is_logged_in) {
            $this->display_login_form();
        } else {
            // ... dashboard client normal ...
        }
    }

    return ob_get_clean();
}
```

#### 2️⃣ **Nouvelle Fonction de Vérification Permissions**

```php
private function is_wordpress_agent_or_admin() {
    if (!is_user_logged_in()) {
        return false;
    }

    $user = wp_get_current_user();

    // Vérifier les capabilities
    if (current_user_can('colis224_manage_all') ||
        current_user_can('administrator') ||
        current_user_can('manage_options')) {
        return true;
    }

    return false;
}
```

**Capabilities reconnues:**
- ✅ `colis224_manage_all` (capability custom du plugin)
- ✅ `administrator` (rôle admin WordPress standard)
- ✅ `manage_options` (capability admin WordPress)

#### 3️⃣ **Dashboard Agent WordPress**

```php
private function display_wordpress_agent_dashboard() {
    // Passer client_id = 0 pour indiquer "agent WordPress"
    echo Colis224_Client_Portal_Enhanced::render_enhanced_portal(0);
}
```

**Signification de `client_id = 0` :**
→ Convention spéciale indiquant "utilisateur WordPress, pas un client de la DB"

#### 4️⃣ **Gestion Client Virtuel** (`class-colis224-client-portal-enhanced.php`)

**Modification de `render_enhanced_portal()` :**

```php
public static function render_enhanced_portal($client_id) {
    global $wpdb;

    // 🔥 DÉTECTION CAS SPÉCIAL: agent WordPress
    $is_wordpress_agent = ($client_id === 0);

    if ($is_wordpress_agent) {
        // ✅ Créer un objet client virtuel avec les infos WordPress
        $user = wp_get_current_user();
        $client = new stdClass();
        $client->id = 0;
        $client->name = $user->display_name ?: $user->user_login;
        $client->phone = $user->user_email ?: 'Agent WordPress';
        $client->email = $user->user_email;

        // ✅ Récupérer TOUS les colis (agents voient tout)
        $parcels = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}colis224_parcels
            ORDER BY created_at DESC
            LIMIT 50"
        );

        // ✅ Pas de fidélité pour agents
        $loyalty_info = array(
            'total_parcels' => 0,
            'total_weight' => 0,
            'level' => 'Agent',
            'points' => 0
        );

        // ✅ Récupérer TOUS les tickets (agents voient tout)
        $tickets = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}colis224_tickets
            ORDER BY created_at DESC
            LIMIT 50"
        );

        $unread_count = 0;

        // ✅ Toujours agent
        $is_agent = true;

    } else {
        // CAS NORMAL: client de la base de données
        // ... code existant ...
    }
}
```

#### 5️⃣ **Interface Adaptée pour Agents**

**Badge Agent WordPress:**
```php
<p class="client-phone">
    <?php if ($is_wordpress_agent): ?>
        <span class="agent-badge" style="background: #0073aa; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;">
            🔑 AGENT WORDPRESS
        </span>
    <?php else: ?>
        📞 <?php echo esc_html($client->phone); ?>
    <?php endif; ?>
</p>
```

**Bouton Déconnexion Adapté:**
```php
<?php if ($is_wordpress_agent): ?>
    <!-- Déconnexion WordPress -->
    <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="btn-logout">
        <span class="dashicons dashicons-exit"></span> Déconnexion
    </a>
<?php else: ?>
    <!-- Déconnexion Client -->
    <a href="<?php echo wp_nonce_url('?action=colis224_logout', 'colis224_logout'); ?>" class="btn-logout">
        <span class="dashicons dashicons-exit"></span> Déconnexion
    </a>
<?php endif; ?>
```

**Carte Fidélité Masquée:**
```php
<!-- Programme de Fidélité (Clients uniquement, pas pour agents WordPress) -->
<?php if ($loyalty_info && !$is_wordpress_agent): ?>
    <!-- ... carte fidélité ... -->
<?php endif; ?>
```

---

## 📊 TABLEAU COMPARATIF

| Critère | AVANT (❌) | APRÈS (✅) |
|---------|-----------|-----------|
| **Agent WordPress connecté** | Voit formulaire de connexion client | Voit dashboard agent directement |
| **Client avec téléphone** | Connexion normale | Connexion normale (pas d'impact) |
| **Pont WordPress ↔ Client** | Aucun | Système de priorité à 2 niveaux |
| **Données affichées (agent)** | N/A | Tous les colis/tickets (pas filtrés) |
| **Badge agent** | N/A | "🔑 AGENT WORDPRESS" visible |
| **Bouton déconnexion** | N/A | `wp_logout_url()` pour agents |
| **Carte fidélité** | N/A | Masquée pour agents |

---

## 🧪 COMMENT TESTER

### Test 1: Connexion Agent WordPress

**Prérequis:**
- Créer un utilisateur WordPress (Users → Add New)
- Rôle: Administrator OU rôle custom avec capability `colis224_manage_all`
- Créer une page avec le shortcode `[colis224_client_portal]`

**Étapes:**
1. ✅ Se connecter à WordPress via `wp-login.php` avec le compte agent
2. ✅ Visiter la page avec le shortcode `[colis224_client_portal]`
3. ✅ **RÉSULTAT ATTENDU:** Dashboard agent s'affiche directement
4. ✅ **VÉRIFICATIONS:**
   - Message de bienvenue avec nom WordPress
   - Badge "🔑 AGENT WORDPRESS" visible
   - Onglet "Ajouter" visible (création colis/clients)
   - Liste de TOUS les colis (pas seulement ceux du client)
   - Bouton "Déconnexion" pointe vers WordPress logout

### Test 2: Connexion Client Normal (Non-régression)

**Prérequis:**
- Un client existant dans `wp_colis224_clients`
- Le client a un téléphone enregistré

**Étapes:**
1. ✅ Se déconnecter de WordPress si connecté
2. ✅ Visiter la page avec `[colis224_client_portal]`
3. ✅ Saisir le numéro de téléphone client
4. ✅ Cliquer "Se connecter"
5. ✅ **RÉSULTAT ATTENDU:** Dashboard client normal s'affiche
6. ✅ **VÉRIFICATIONS:**
   - Message de bienvenue avec nom du client
   - Téléphone affiché (📞)
   - Carte fidélité visible
   - Liste des colis filtrés par client_id
   - Onglet "Ajouter" MASQUÉ (clients ne peuvent pas créer)

### Test 3: Hiérarchie des Connexions

**Scénario:** Agent connecté à WordPress + Session client active

**Étapes:**
1. ✅ Se connecter à WordPress (agent)
2. ✅ Dans un autre onglet, se connecter comme client (téléphone)
3. ✅ Visiter la page avec `[colis224_client_portal]`
4. ✅ **RÉSULTAT ATTENDU:** Dashboard AGENT s'affiche (priorité WordPress)
5. ✅ La session client est ignorée car WordPress a priorité

### Test 4: Déconnexion

**Agent WordPress:**
1. ✅ Connecté comme agent WordPress
2. ✅ Cliquer "Déconnexion"
3. ✅ **RÉSULTAT:** Déconnecté de WordPress, redirigé vers page courante
4. ✅ Formulaire de connexion CLIENT s'affiche

**Client:**
1. ✅ Connecté comme client (téléphone)
2. ✅ Cliquer "Déconnexion"
3. ✅ **RÉSULTAT:** Session client détruite, formulaire s'affiche

---

## 🔒 SÉCURITÉ

### Vérifications de Sécurité

✅ **Vérification des Capabilities WordPress**
- Utilise `current_user_can()` (fonction WordPress native sécurisée)
- Vérifie plusieurs capabilities (redondance de sécurité)
- Pas de bypass possible sans permissions WordPress

✅ **Isolation des Données**
- Agents WordPress voient TOUS les colis (normal pour un agent)
- Clients voient SEULEMENT leurs colis (filtre `WHERE client_id = %d`)
- Impossible pour un client de voir les données d'un autre client

✅ **Aucune Élévation de Privilèges**
- Un client ne peut PAS devenir agent en manipulant `client_id`
- La vérification `is_wordpress_agent_or_admin()` est côté serveur
- Les AJAX handlers vérifient toujours les permissions via `is_agent_or_admin()`

---

## 📦 FICHIERS MODIFIÉS

### 1. `includes/class-colis224-frontend-portal.php`
**Lignes modifiées:** 224-296
**Changements:**
- `client_portal_shortcode()` : Ajout priorité WordPress
- `is_wordpress_agent_or_admin()` : Nouvelle fonction
- `display_wordpress_agent_dashboard()` : Nouvelle fonction

### 2. `includes/class-colis224-client-portal-enhanced.php`
**Lignes modifiées:** 90-220
**Changements:**
- `render_enhanced_portal()` : Gestion `client_id = 0`
- Client virtuel pour agents WordPress
- Badge agent dans header
- Logout adapté
- Carte fidélité conditionnelle

---

## 🚀 DÉPLOIEMENT

### Installation

**Option 1: Via Git (recommandé)**
```bash
cd /wp-content/plugins/colis224-logistics-manager/
git pull origin claude/audit-wordpress-plugin-rs4q8
```

**Option 2: Via FTP**
1. Télécharger les 2 fichiers modifiés
2. Remplacer via FTP dans `/wp-content/plugins/colis224-logistics-manager/includes/`

### Pas de Migration DB Requise

✅ Aucune modification de base de données
✅ Pas de migration à exécuter
✅ Compatibilité 100% avec versions précédentes

---

## ✅ RÉSULTAT FINAL

### Comportement Après le Fix

```
┌─────────────────────────────────────────────────┐
│  Utilisateur visite [colis224_client_portal]   │
└─────────────────────────────────────────────────┘
                      ↓
        ┌─────────────────────────────┐
        │  Connecté à WordPress ?     │
        │  + Permissions agent/admin ? │
        └─────────────────────────────┘
          ✅ OUI              ❌ NON
            ↓                   ↓
    ┌───────────────┐   ┌──────────────────┐
    │  Dashboard    │   │  Session client  │
    │  Agent        │   │  active ?        │
    │  WordPress    │   └──────────────────┘
    │               │       ✅ OUI    ❌ NON
    │  - Badge      │         ↓         ↓
    │  - Tous colis │   ┌──────────┐ ┌───────┐
    │  - Tab Ajouter│   │Dashboard │ │Formulaire│
    └───────────────┘   │  Client  │ │Connexion│
                        └──────────┘ └───────┘
```

### Message pour l'Utilisateur

> ✅ **PROBLÈME RÉSOLU !**
>
> Vous pouvez maintenant :
> 1. Créer un utilisateur WordPress (rôle administrator)
> 2. Vous connecter via `wp-login.php`
> 3. Visiter votre page `/espace-agent/` avec le shortcode
> 4. **Le dashboard agent s'affichera directement** sans demander de connexion !
>
> Vous verrez un badge bleu "🔑 AGENT WORDPRESS" confirmant que vous êtes connecté en tant qu'agent.
>
> Le shortcode est bien le même : `[colis224_client_portal]`
> - Pour les agents WordPress → Dashboard agent
> - Pour les clients → Formulaire de connexion téléphone

---

## 📞 SUPPORT

En cas de problème :
1. Vérifier les logs : `/wp-content/debug.log`
2. Activer le mode debug WordPress :
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
3. Vérifier que l'utilisateur a bien le rôle administrator OU la capability `colis224_manage_all`

---

**Développé par Claude Code**
**Version plugin:** 2.17.1
**Commit:** 62cf563
