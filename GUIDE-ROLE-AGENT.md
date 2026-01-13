# 🔑 Guide: Créer un Rôle "Agent" WordPress

**Version:** 2.18.1
**Plugin:** Colis224 Logistics Manager

---

## 🎯 Objectif

Créer un rôle WordPress "Agent" pour que vos employés puissent accéder à l'espace agent `[colis224_agent_portal]` sans être administrateurs.

---

## ✅ Solution Automatique (Recommandée)

Avec la **version 2.18.1**, le plugin détecte automatiquement les agents de 3 façons:

### 1. Par Rôle WordPress
Si le nom du rôle contient "**agent**" (insensible à la casse), l'accès est autorisé.

**Exemples de rôles qui fonctionnent:**
- ✅ `agent`
- ✅ `Agent`
- ✅ `agent_colis224`
- ✅ `Agent Commercial`
- ✅ `agent_logistique`

### 2. Par Capability Colis224
Si l'utilisateur possède une de ces capabilities:
- ✅ `colis224_manage_all`
- ✅ `colis224_create_parcel`
- ✅ `colis224_view_parcels`

### 3. Par Rôle Admin
Si l'utilisateur a le rôle:
- ✅ `Administrator`
- ✅ Ou la capability `manage_options`

---

## 📝 Méthode 1: Créer un Rôle "Agent" Simple (Sans Plugin)

### Étape 1: Ajouter ce code dans `functions.php` de votre thème

```php
/**
 * Créer le rôle Agent Colis224
 */
function colis224_create_agent_role() {
    // Vérifier si le rôle existe déjà
    if (!get_role('agent_colis224')) {
        add_role(
            'agent_colis224',           // Nom technique du rôle
            'Agent Colis224',           // Nom affiché
            array(
                'read' => true,         // Accès au back-office WordPress
                'colis224_view_parcels' => true,   // Voir les colis
                'colis224_create_parcel' => true,  // Créer des colis
            )
        );
    }
}
add_action('init', 'colis224_create_agent_role');
```

### Étape 2: Activer le Rôle
1. Rafraîchir n'importe quelle page WordPress Admin
2. Le rôle sera créé automatiquement

### Étape 3: Assigner le Rôle à un Utilisateur
1. WordPress Admin → **Utilisateurs** → Sélectionner l'utilisateur
2. **Rôle:** Changer vers "Agent Colis224"
3. Enregistrer

---

## 📝 Méthode 2: Utiliser un Plugin (Plus Simple)

### Option A: User Role Editor (Recommandé)

**1. Installer le plugin:**
```
Extensions → Ajouter → Rechercher "User Role Editor"
Installer et Activer
```

**2. Créer le rôle:**
1. Aller dans **Utilisateurs → User Role Editor**
2. Cliquer sur **"Add Role"**
3. **Display Name:** `Agent Colis224`
4. **Role ID:** `agent_colis224`
5. Cliquer **"Add Role"**

**3. Assigner les capabilities:**
1. Sélectionner le rôle "Agent Colis224" dans le dropdown
2. Cocher ces capabilities:
   - ✅ `read` (obligatoire pour accès admin)
   - ✅ `colis224_view_parcels`
   - ✅ `colis224_create_parcel`
3. Cliquer **"Update"**

**4. Assigner à un utilisateur:**
1. **Utilisateurs** → Sélectionner l'utilisateur
2. **Rôle:** `Agent Colis224`
3. Enregistrer

### Option B: Members Plugin

**1. Installer:**
```
Extensions → Ajouter → Rechercher "Members"
Installer et Activer
```

**2. Créer le rôle:**
1. **Utilisateurs → Rôles → Ajouter**
2. **Nom:** `Agent Colis224`
3. Cocher:
   - ✅ `read`
   - ✅ `colis224_view_parcels`
   - ✅ `colis224_create_parcel`
4. Enregistrer

---

## 🧪 Tester le Rôle

### Test 1: Connexion
1. **Se déconnecter** de WordPress
2. **Se connecter** avec le compte agent créé
3. **Visiter:** `https://votresite.com/espace-agent/`

### ✅ Résultat Attendu:
- ✅ Dashboard agent s'affiche
- ✅ Pas de message d'erreur "rôle requis"
- ✅ Formulaires de création visibles

### ❌ Si Erreur:
- ❌ "Accès refusé" → Le rôle ne contient pas "agent" OU n'a pas les capabilities
- ❌ "Authentification requise" → L'utilisateur n'est pas connecté à WordPress

---

## 🔧 Dépannage

### Problème 1: "Accès refusé" malgré le rôle

**Vérifier que le rôle contient "agent":**
```php
// Ajouter temporairement dans functions.php pour debug
add_action('init', function() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        echo '<pre>Rôles: ' . print_r($user->roles, true) . '</pre>';
    }
});
```

**Si le nom du rôle ne contient pas "agent":**
- Option 1: Renommer le rôle pour inclure "agent"
- Option 2: Ajouter une capability Colis224 (voir ci-dessous)

### Problème 2: Ajouter des Capabilities Manuellement

**Via code (functions.php):**
```php
function colis224_add_agent_capabilities() {
    $role = get_role('votre_role_custom'); // Remplacer par votre nom de rôle

    if ($role) {
        $role->add_cap('colis224_view_parcels');
        $role->add_cap('colis224_create_parcel');
    }
}
add_action('admin_init', 'colis224_add_agent_capabilities');
```

**Exécuter une fois puis SUPPRIMER ce code !**

### Problème 3: Plusieurs Rôles en Conflit

WordPress permet UN SEUL rôle par utilisateur. Si vous avez plusieurs rôles, seul le premier sera pris en compte.

**Solution:** Fusionner les capabilities dans un seul rôle.

---

## 📊 Tableau Récapitulatif des Méthodes

| Méthode | Difficulté | Avantages | Inconvénients |
|---------|-----------|-----------|---------------|
| **Nom contient "agent"** | ⭐ Facile | Détection automatique | Nécessite renommer le rôle |
| **Capability Colis224** | ⭐⭐ Moyen | Flexible, précis | Nécessite plugin ou code |
| **Rôle Administrator** | ⭐ Facile | Fonctionne immédiatement | Trop de permissions |

---

## 🎓 Capabilities Colis224 Disponibles

Voici toutes les capabilities du plugin Colis224:

| Capability | Description | Recommandé Agent? |
|-----------|-------------|-------------------|
| `colis224_manage_all` | Accès total (comme admin) | ✅ Oui (Manager) |
| `colis224_view_parcels` | Voir les colis | ✅ Oui |
| `colis224_create_parcel` | Créer des colis | ✅ Oui |
| `colis224_manage_clients` | Gérer les clients | ⚠️ Optionnel |
| `colis224_view_reports` | Voir les rapports | ⚠️ Optionnel |
| `colis224_manage_accounting` | Gérer la compta | ❌ Non (Comptable) |
| `colis224_view_assigned_parcels` | Voir colis assignés | ⚠️ Optionnel |
| `colis224_update_delivery_status` | Modifier statut livraison | ⚠️ Optionnel (Livreur) |

---

## ✅ Configuration Recommandée pour un Agent Standard

**Rôle:** `agent_colis224` ou `Agent`

**Capabilities minimales:**
```php
array(
    'read' => true,                        // Obligatoire
    'colis224_view_parcels' => true,       // Voir les colis
    'colis224_create_parcel' => true,      // Créer des colis
)
```

**Capabilities étendues (Agent Senior):**
```php
array(
    'read' => true,
    'colis224_view_parcels' => true,
    'colis224_create_parcel' => true,
    'colis224_manage_clients' => true,     // Gérer clients
    'colis224_view_reports' => true,       // Voir rapports
)
```

---

## 🔒 Sécurité

**Important:**
- ❌ Ne donnez JAMAIS `manage_options` à un agent → Accès total WordPress
- ❌ Ne donnez JAMAIS `administrator` à un agent → Risque de sécurité
- ✅ Utilisez les capabilities Colis224 spécifiques
- ✅ Suivez le principe du moindre privilège

---

## 📞 Support

Si le problème persiste après avoir suivi ce guide:

1. Vérifier les rôles de l'utilisateur:
   ```php
   // Ajouter dans functions.php temporairement
   add_action('wp_footer', function() {
       if (is_user_logged_in()) {
           $user = wp_get_current_user();
           echo '<div style="background: black; color: lime; padding: 10px;">';
           echo 'User: ' . $user->user_login . '<br>';
           echo 'Roles: ' . implode(', ', $user->roles) . '<br>';
           echo 'Can view parcels: ' . (current_user_can('colis224_view_parcels') ? 'YES' : 'NO');
           echo '</div>';
       }
   });
   ```

2. Envoyer le résultat affiché en bas de page

---

**Guide mis à jour:** 13 janvier 2026
**Version plugin:** 2.18.1
