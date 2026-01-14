# 📌 Instructions pour le Shortcode Espace Agent

## Version : 2.18.2

---

## ✅ Nouveau Shortcode Ajouté

Le shortcode **`[colis224_agent_portal]`** a été créé pour afficher l'espace agent sur votre site.

---

## 🚀 Comment l'utiliser

### 1. Sur votre page WordPress "Espace Agent"

1. **Connectez-vous** à votre administration WordPress
2. **Allez** dans Pages → Toutes les pages
3. **Trouvez** la page "Espace Agent" (https://colis224.com/espace-agent/)
4. **Cliquez** sur "Modifier"
5. **Supprimez** tout le contenu actuel de la page
6. **Ajoutez** le shortcode suivant :

```
[colis224_agent_portal]
```

7. **Publiez** ou **Mettez à jour** la page
8. **Testez** en visitant la page

---

## 🎯 Fonctionnalités du Portail Agent

### Pour les utilisateurs NON connectés :
- ✅ Affiche un **formulaire de connexion** élégant
- ✅ Connexion sécurisée avec identifiants WordPress
- ✅ Design moderne et responsive

### Pour les agents connectés :
- ✅ **Tableau de bord personnalisé** avec nom de l'agent
- ✅ **Actions rapides** :
  - Accès au tableau de bord complet
  - Créer un nouveau colis
  - Gérer les colis
  - Gérer les clients
- ✅ **Informations de compte** (rôle, email)
- ✅ **Bouton de déconnexion**

### Pour les utilisateurs sans permission :
- ✅ Affiche un **message d'accès refusé** clair
- ✅ Explique que l'espace est réservé aux agents

---

## 👥 Rôles autorisés

Le portail est accessible aux utilisateurs ayant l'un de ces rôles :
- ✅ **Agent Colis224** (`colis224_agent`)
- ✅ **Gestionnaire Colis224** (`colis224_manager`)
- ✅ **Administrateur** (`administrator`)

---

## 🎨 Design

Le shortcode affiche :
- 🎨 Design moderne avec dégradés et ombres
- 📱 Responsive (mobile, tablette, desktop)
- 🎯 Interface intuitive avec icônes
- 🔒 Formulaire de connexion sécurisé intégré

---

## 📸 Aperçu de la page

### État 1 : Non connecté
```
┌─────────────────────────────────────┐
│  👥 Espace Agent                    │
│  Connectez-vous pour accéder        │
│                                     │
│  ┌───────────────────────────────┐ │
│  │ Nom d'utilisateur ou Email    │ │
│  ├───────────────────────────────┤ │
│  │ Mot de passe                  │ │
│  ├───────────────────────────────┤ │
│  │ ☑ Se souvenir de moi          │ │
│  ├───────────────────────────────┤ │
│  │    [Se connecter]             │ │
│  └───────────────────────────────┘ │
│                                     │
│  🔒 Connexion sécurisée            │
└─────────────────────────────────────┘
```

### État 2 : Connecté (Agent)
```
┌─────────────────────────────────────┐
│  Bienvenue, Jean Dupont ! 👋        │
│  Espace Agent Colis224              │
│                                     │
│  ┌───────┐ ┌───────┐ ┌───────┐    │
│  │  📊   │ │  ➕   │ │  📦   │    │
│  │Tableau│ │Nouveau│ │  Mes  │    │
│  │  de   │ │ Colis │ │ Colis │    │
│  │ Bord  │ │       │ │       │    │
│  └───────┘ └───────┘ └───────┘    │
│                                     │
│  ┌───────┐                         │
│  │  👥   │                         │
│  │Clients│                         │
│  │       │                         │
│  └───────┘                         │
│                                     │
│  ℹ️ Informations                    │
│  Rôle: Agent Colis224               │
│  Email: jean@example.com            │
│                                     │
│      [🚪 Se déconnecter]            │
└─────────────────────────────────────┘
```

---

## 🛠️ Dépannage

### Le shortcode ne s'affiche pas ?
1. ✅ Vérifiez que le plugin est bien activé
2. ✅ Vérifiez que vous avez bien la version 2.18.2
3. ✅ Videz le cache (Ctrl+F5)
4. ✅ Vérifiez que le shortcode est bien écrit : `[colis224_agent_portal]` (sans espaces)

### L'agent ne peut pas se connecter ?
1. ✅ Vérifiez que l'utilisateur existe dans WordPress
2. ✅ Vérifiez que l'utilisateur a le rôle "Agent Colis224"
3. ✅ Vérifiez que le mot de passe est correct

### Message "Accès refusé" pour un agent ?
1. ✅ Allez dans WordPress → Utilisateurs
2. ✅ Éditez l'utilisateur concerné
3. ✅ Vérifiez que son rôle est bien "Agent Colis224" ou "Gestionnaire Colis224"

---

## 📞 Support

Si vous rencontrez des problèmes, contactez le support technique avec :
- 🔹 URL de la page
- 🔹 Capture d'écran de l'erreur
- 🔹 Rôle de l'utilisateur qui tente de se connecter

---

## ✨ Fonctionnalités ajoutées dans v2.18.2

1. ✅ **Nouveau shortcode** `[colis224_agent_portal]`
2. ✅ **Correctif** de l'erreur "Duplicate entry PA3330"
3. ✅ **Portail agent** complet avec authentification
4. ✅ **Design moderne** et responsive

---

Profitez de votre nouvel espace agent ! 🚀
