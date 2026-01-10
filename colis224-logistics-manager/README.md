# Colis224 Logistics Manager

Plugin WordPress professionnel pour la gestion complète d'une entreprise de livraison internationale.

## 📋 Description

**Colis224 Logistics Manager** est un système complet de gestion logistique conçu spécifiquement pour les entreprises de livraison internationale opérant entre plusieurs pays (Chine, France, Maroc, Sénégal, Côte d'Ivoire, Guinée).

Le plugin offre une solution tout-en-un pour gérer :
- ✅ Les colis et leur suivi
- ✅ Les clients (particuliers et entreprises)
- ✅ Les partenaires commerciaux
- ✅ L'équipe (agents et livreurs)
- ✅ La comptabilité complète (revenus et dépenses)
- ✅ Les rapports et statistiques
- ✅ Le service d'achat pour clients
- ✅ Les notifications email et SMS

## 🎯 Fonctionnalités Principales

### 1. Gestion des Colis
- Création et suivi des colis avec numéros de tracking personnalisés
- Gestion multi-pays et multi-modes de transport (Avion Express/Standard, Bateau)
- Calcul automatique des montants avec réductions
- Gestion des paiements partiels et multiples modes de paiement
- Attribution des colis aux livreurs
- Upload de photos de colis
- Statuts de livraison en temps réel

### 2. Gestion des Clients
- Fiches clients complètes (particuliers et entreprises)
- Historique des colis par client
- Gestion des remises personnalisées
- Suivi des soldes et paiements partiels
- Notes et commentaires internes

### 3. Gestion des Partenaires
- Gestion des agences partenaires
- Transactions bidirectionnelles (colis confiés/reçus)
- Suivi des soldes partenaires (dû/à recevoir)
- Historique complet des transactions

### 4. Gestion de l'Équipe
- **Agents de bureau** : Gestion des employés avec rôles et salaires
- **Livreurs** : Commissions automatiques, zones d'intervention, historique des livraisons

### 5. Comptabilité Complète
- Suivi automatique des revenus (colis + autres sources)
- Gestion des dépenses par catégories
- Tableaux de bord financiers
- Calcul automatique du bénéfice net
- Support multi-devises (GNF, EUR, USD)

### 6. Rapports et Statistiques
- Rapports personnalisables par période
- Statistiques détaillées par pays, mode de transport, client
- Analyse des dépenses par catégorie
- Top 10 clients
- Export en CSV et PDF

### 7. Service d'Achat
- Gestion des demandes d'achat clients
- Workflow complet : Demande → Devis → Validation → Achat → Livraison
- Calcul automatique des frais de service
- Liaison avec les colis

### 8. Paramètres et Configuration
- Informations de l'entreprise
- Gestion des taux de change
- Catégories de colis personnalisables
- Configuration des notifications (Email/SMS)
- Préfixes de numérotation personnalisables

## 🚀 Installation

1. **Télécharger le plugin**
   ```
   Téléchargez le fichier ZIP du plugin
   ```

2. **Installation via WordPress**
   - Connectez-vous à votre admin WordPress
   - Allez dans `Extensions > Ajouter`
   - Cliquez sur `Téléverser une extension`
   - Sélectionnez le fichier ZIP
   - Cliquez sur `Installer maintenant`
   - Activez le plugin

3. **Installation manuelle**
   ```bash
   cd wp-content/plugins/
   unzip colis224-logistics-manager.zip
   ```
   Puis activez le plugin dans WordPress

## 📖 Guide d'Utilisation

### Premier Démarrage

1. **Accédez au plugin** : Dans le menu admin WordPress, cliquez sur "Colis224"

2. **Configurez les paramètres** :
   - Allez dans `Colis224 > Paramètres`
   - Remplissez les informations de votre entreprise
   - Configurez les taux de change si nécessaire
   - Ajoutez vos catégories de colis

3. **Ajoutez vos données de base** :
   - Créez vos clients
   - Enregistrez vos partenaires (si applicable)
   - Ajoutez vos livreurs et agents

4. **Commencez à gérer vos colis** :
   - Créez votre premier colis
   - Le système générera automatiquement le numéro de suivi

### Flux de Travail Typique

1. **Réception d'un colis** :
   - `Colis > Nouveau Colis`
   - Remplir les informations (client, destinataire, poids, tarif)
   - Le total est calculé automatiquement avec réductions
   - Statut : "En attente"

2. **Expédition** :
   - Modifier le colis
   - Changer le statut en "Expédié"
   - Ajouter la date d'expédition
   - Assigner un livreur si livraison locale

3. **Livraison** :
   - Mettre le statut "Livré"
   - Ajouter la date de livraison
   - Marquer le paiement comme "Payé"

4. **Suivi Financier** :
   - Les revenus sont enregistrés automatiquement
   - Ajoutez vos dépenses dans `Comptabilité > Dépenses`
   - Consultez vos rapports dans `Rapports`

## 🛠️ Prérequis Techniques

- **WordPress** : 6.0 ou supérieur
- **PHP** : 8.0 ou supérieur
- **MySQL** : 5.7 ou supérieur
- **Navigateurs supportés** : Chrome, Firefox, Safari, Edge (versions récentes)

## 💾 Base de Données

Le plugin crée automatiquement les tables suivantes lors de l'activation :

- `wp_colis224_clients` - Clients
- `wp_colis224_parcels` - Colis
- `wp_colis224_partners` - Partenaires
- `wp_colis224_partner_transactions` - Transactions partenaires
- `wp_colis224_team_members` - Agents
- `wp_colis224_drivers` - Livreurs
- `wp_colis224_expenses` - Dépenses
- `wp_colis224_expense_categories` - Catégories de dépenses
- `wp_colis224_revenues` - Revenus
- `wp_colis224_payments` - Paiements
- `wp_colis224_purchase_requests` - Demandes d'achat
- `wp_colis224_countries` - Pays
- `wp_colis224_transport_modes` - Modes de transport
- `wp_colis224_parcel_categories` - Catégories de colis
- `wp_colis224_notifications` - Notifications

## 🔐 Sécurité

- ✅ Validation et sanitization de toutes les données
- ✅ Nonces WordPress pour toutes les actions
- ✅ Vérification des permissions utilisateur
- ✅ Protection contre les injections SQL
- ✅ Protection contre les attaques XSS et CSRF

## 🌍 Multi-Devises

Le plugin supporte 3 devises principales :
- **GNF** (Franc Guinéen) - Devise par défaut
- **EUR** (Euro)
- **USD** (Dollar Américain)

Les taux de change sont configurables dans les paramètres.

## 📊 Exports

- **Format CSV** : Export des rapports en CSV
- **Format PDF** : Export des rapports en PDF (nécessite TCPDF)

## 🔔 Notifications

### Email
Notifications automatiques par email pour :
- Nouveaux colis
- Changements de statut
- Confirmations de livraison

### SMS
Support des notifications SMS via API (configuration requise dans les paramètres)

## 🎨 Personnalisation

Le plugin utilise des templates HTML et CSS modernes :
- Interface responsive (mobile-friendly)
- Couleurs personnalisables via CSS
- Thème cohérent avec WordPress

## 🐛 Dépannage

### Le plugin ne s'active pas
- Vérifiez la version de PHP (minimum 8.0)
- Vérifiez la version de WordPress (minimum 6.0)
- Consultez les logs d'erreur WordPress

### Les tables ne sont pas créées
- Désactivez et réactivez le plugin
- Vérifiez les permissions de la base de données

### Les calculs automatiques ne fonctionnent pas
- Vérifiez que JavaScript est activé dans votre navigateur
- Videz le cache de votre navigateur

## 📝 Changelog

### Version 1.0.0 (2025-01-02)
- 🎉 Version initiale
- ✅ Gestion complète des colis
- ✅ Gestion des clients et partenaires
- ✅ Module comptabilité
- ✅ Rapports et statistiques
- ✅ Service d'achat
- ✅ Support multi-devises
- ✅ Interface moderne et responsive

## 👨‍💻 Développement

### Structure du Plugin

```
colis224-logistics-manager/
├── colis224-logistics-manager.php  (Fichier principal)
├── includes/                        (Classes principales)
│   ├── class-colis224-activator.php
│   ├── class-colis224-deactivator.php
│   ├── class-colis224-database.php
│   └── class-colis224-admin.php
├── admin/                           (Modules admin)
│   ├── class-colis224-dashboard.php
│   ├── class-colis224-colis.php
│   ├── class-colis224-clients.php
│   ├── class-colis224-partenaires.php
│   ├── class-colis224-equipe.php
│   ├── class-colis224-comptabilite.php
│   ├── class-colis224-rapports.php
│   ├── class-colis224-parametres.php
│   └── class-colis224-achat.php
├── assets/
│   ├── css/
│   │   └── admin-style.css
│   └── js/
│       └── admin-script.js
└── languages/                       (Traductions)
```

## 🤝 Support

Pour toute question ou problème :
- Email : support@colis224.com
- Site web : https://colis224.com

## 📄 Licence

GPL-2.0+

## 🙏 Crédits

Développé pour Colis224 - Livraison internationale intelligente

---

**© 2025 Colis224. Tous droits réservés.**
