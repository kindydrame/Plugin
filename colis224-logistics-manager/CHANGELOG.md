# Changelog - Colis224 Logistics Manager

Toutes les modifications importantes de ce projet seront documentées dans ce fichier.

---

## [2.10.1] - 2025-11-05

### 🎨 AMÉLIORATIONS MAJEURES DU MODULE DÉPARTS

**Cette version apporte des améliorations significatives au MODULE 33 avec une UX exceptionnelle, des filtres intelligents et de nouvelles fonctionnalités demandées par les utilisateurs.**

### ✨ NOUVELLES FONCTIONNALITÉS

#### 🔍 **Filtres Améliorés et Instructifs**
- **Interface repensée** avec labels clairs et aide contextuelle
- **Messages d'aide** sous chaque filtre ("D'où partez-vous ?", "Où allez-vous ?", etc.)
- **Filtres actifs visibles** avec tags cliquables pour retrait rapide
- **Recherche rapide par ville** en temps réel (recherche instantanée)
- **Auto-complétion** avec debounce de 300ms pour performances optimales

#### 📋 **Modes d'Affichage Multiples**
- **Mode Slider** (défaut) - Défilement automatique avec pause au survol
- **Mode Grille** - Vue en grille responsive pour comparaison rapide
- **Mode Liste** - Vue liste compacte pour aperçu détaillé
- **Toggle facile** avec bouton unique (cycle entre 3 modes)
- **Icônes visuelles** pour chaque mode (🎬 Slider, 📋 Grille, 📝 Liste)

#### 💬 **Message "Aucun Résultat" Amélioré**
- **Design attractif** avec emoji animé (😔 avec animation bounce)
- **Suggestions intelligentes** pour aider l'utilisateur
- **2 boutons contact WhatsApp** avec gradients modernes:
  - **📞 Agence Guinée**: +224 626 526 735 (gradient rose-rouge)
  - **🌍 Service International**: +224 620 178 930 (gradient bleu)
- **Informations complètes**: Email et site web en footer
- **Messages pré-remplis** adaptés au contexte (recherche vs international)

#### 📧 **Système de Notifications Email**
- **Abonnement email** pour recevoir les nouveaux départs
- **Table dédiée** `colis224_departure_notifications` (4 colonnes)
- **Email de confirmation** avec design HTML moderne
- **Email de notification** envoyé à tous les abonnés lors d'un nouveau départ
- **Gestion des abonnés**:
  - Validation email côté client et serveur
  - Protection contre les doublons (UNIQUE KEY sur email)
  - Réactivation automatique si déjà inscrit
  - Système is_active pour désabonnement
- **Template email professionnel** avec:
  - Header avec gradient violet
  - Détails complets du départ (route, date, transport, prix)
  - Bouton WhatsApp pour réservation directe
  - Footer avec coordonnées complètes
  - Lien de désabonnement

#### 📄 **Export PDF**
- **Impression optimisée** via fonction print() du navigateur
- **Styles d'impression** dédiés (@media print)
- **Bouton Export PDF** dans les filtres
- **Feedback visuel** ("⏳ Génération..." pendant le traitement)
- **Masquage automatique** des éléments non imprimables
- **Page-break** intelligent pour éviter coupure des cartes

#### 🌓 **Mode Sombre / Clair**
- **Toggle flottant** en bas à droite (position fixed)
- **Persistance** via localStorage (préférence sauvegardée)
- **CSS Variables** pour changement de thème fluide
- **Animations** smooth lors du changement
- **Icônes contextuelles**: 🌙 Mode Sombre / ☀️ Mode Clair
- **Design adapté**:
  - Cards sombres (#2d3748) en mode sombre
  - Texte clair (#f7fafc) automatique
  - Gradients adaptés pour chaque mode
  - Bordures et ombres ajustées

### 🎨 **Design Moderne Ultra-Amélioré**

#### **CSS Variables et Thèmes**
```css
--primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%)
--success-gradient: linear-gradient(135deg, #25D366 0%, #128C7E 100%)
--hover-shadow: 0 12px 40px rgba(102, 126, 234, 0.25)
```

#### **Animations Avancées**
- **Pulse** pour badge "Départ imminent" (2s infinite)
- **Bounce** pour emoji "Aucun résultat" (2s infinite)
- **Spin** pour loader (1s linear infinite)
- **Slide** pour défilement automatique (dynamique selon nb cartes)
- **Hover effects** sur tous les boutons et cartes
- **Transform translateY** pour effet de levée au survol

#### **Filtres avec Background Pattern**
- **SVG Pattern** en pseudo-élément ::before
- **Gradient principal** avec overlay subtil
- **Border-radius** augmenté à 20px
- **Box-shadow** plus prononcée (50px blur)
- **Responsive** avec adaptation mobile

#### **Boutons Contact Stylisés**
- **Gradients spécifiques**:
  - Guinée: `#f093fb → #f5576c`
  - International: `#4facfe → #00f2fe`
- **Icônes grandes** (32px) à gauche
- **Structure flex** avec contenu empilé
- **Min-width** 250px pour cohérence
- **Transform** au survol (-4px translateY)

### 🔧 **Améliorations Techniques**

#### **JavaScript Orienté Objet**
- **Classe DeparturesSlider** refactorisée et étendue
- **Méthodes dédiées** pour chaque fonctionnalité:
  - `initCitySearch()` - Recherche par ville
  - `initViewToggle()` - Toggle modes d'affichage
  - `initExportPDF()` - Export PDF
  - `initDarkMode()` - Mode sombre/clair
  - `initEmailNotification()` - Notifications email
  - `updateActiveFilters()` - Gestion tags actifs
  - `checkNoResults()` - Détection résultats vides

#### **AJAX et Performance**
- **Debounce** sur recherche ville (300ms)
- **Prepared statements** SQL partout
- **Nonce** WordPress sur tous les AJAX
- **Sanitization** complète (sanitize_email, sanitize_text_field)
- **Email validation** côté client ET serveur
- **Error handling** robuste avec messages clairs

#### **Base de Données**
- **Nouvelle table**: `colis224_departure_notifications`
  - `id` bigint(20) AUTO_INCREMENT PRIMARY KEY
  - `email` varchar(100) NOT NULL UNIQUE
  - `subscribed_at` datetime DEFAULT CURRENT_TIMESTAMP
  - `is_active` tinyint(1) DEFAULT 1
- **Total système**: 64 tables (63 → 64)

#### **Sécurité Renforcée**
- **wp_send_json_error/success** pour réponses AJAX
- **is_email()** WordPress pour validation
- **BCC** pour emails groupés (confidentialité)
- **Escape output** systématique (esc_attr, esc_html, esc_url)

### 📊 **Fichiers Modifiés (4)**

#### `includes/class-colis224-departures.php` (+238 lignes)
- Ajout `ajax_subscribe_notifications()` pour abonnements
- Ajout `notify_subscribers_new_departure()` pour envoi emails groupés
- Amélioration HTML des filtres (labels, placeholders, aide)
- Message "Aucun résultat" complet avec boutons contact
- Hooks AJAX ajoutés

#### `assets/css/departures-slider.css` (réécriture complète - 716 lignes)
- CSS Variables pour thèmes
- Mode sombre complet (.dark-mode)
- Styles pour 3 modes d'affichage (.departures-slider, .departures-grid, .departures-list)
- Animations avancées (pulse, bounce, spin)
- Message "Aucun résultat" stylisé
- Boutons contact avec gradients
- Filtres actifs avec tags
- Responsive mobile amélioré
- @media print pour export PDF

#### `assets/js/departures-slider.js` (réécriture complète - 548 lignes)
- Refactoring orienté objet
- 8 nouvelles méthodes ajoutées
- Gestion état (`currentView`, `isDarkMode`)
- LocalStorage pour préférences
- Validation email (regex)
- AJAX calls avec error handling
- Event listeners optimisés
- DOM manipulation performante

#### `colis224-logistics-manager.php`
- Version: 2.10.0 → **2.10.1**

### 🎯 **Fonctionnalités Demandées Implémentées**

✅ **Badge "Départ Imminent"** - Déjà présent, amélioré avec animation pulse
✅ **Compteur places disponibles** - Déjà présent, stylisé avec icône 🪑
✅ **Prix indicatif affiché** - Déjà présent, mis en avant avec couleur
✅ **Mode Grille/Liste/Slider** - NOUVEAU - Toggle avec 3 modes
✅ **Recherche rapide par ville** - NOUVEAU - Instant search avec debounce
✅ **Notification email nouveaux départs** - NOUVEAU - Système complet avec table
✅ **Export PDF** - NOUVEAU - Via print() avec styles dédiés
✅ **Mode sombre/clair** - NOUVEAU - Toggle persistant avec localStorage

### 🌟 **Expérience Utilisateur**

**Filtres Plus Instructifs:**
- Labels descriptifs avec questions ("D'où partez-vous ?")
- Aide contextuelle sous chaque champ
- Options par défaut descriptives ("🌍 Tous les pays de départ")
- Feedback visuel immédiat (filtres actifs affichés)

**Contact Client Optimisé:**
- 2 numéros WhatsApp clairement différenciés
- Messages pré-remplis adaptés au contexte
- Design attractif qui incite au clic
- Informations complètes (email, site) pour alternatives

**Navigation Fluide:**
- Toggle modes d'affichage en 1 clic
- Recherche instantanée sans AJAX
- Filtres qui s'ajoutent/retirent facilement
- Pas de rechargement pour changements mineurs

### 📈 **Statistiques Version 2.10.1**

- **Lignes de code ajoutées**: ~1,200 lignes
- **Nouvelles méthodes JS**: 8 méthodes
- **Nouveaux styles CSS**: 400+ lignes
- **Nouvelles fonctionnalités**: 8 majeures
- **Tables ajoutées**: 1 (notifications)
- **Total tables**: 64 tables
- **Total modules**: 33 modules
- **Taille estimée ZIP**: ~235KB

### 🔄 **Migration depuis 2.10.0**

**Automatique:**
- Table `colis224_departure_notifications` créée automatiquement au premier abonnement
- Aucune action requise de l'administrateur
- Compatibilité ascendante garantie

**Nouveautés Visibles:**
- Nouveaux boutons dans filtres (Vue Toggle, Export PDF)
- Bouton Mode Sombre flottant en bas à droite
- Bannière d'abonnement email sous le slider
- Filtres actifs apparaissent automatiquement

### 💡 **Notes pour Développeurs**

**Hooks Disponibles:**
```php
// Appeler manuellement la notification subscribers
Colis224_Departures::notify_subscribers_new_departure($departure_id);

// AJAX endpoints
wp_ajax_colis224_subscribe_departure_notifications
wp_ajax_nopriv_colis224_subscribe_departure_notifications
```

**CSS Classes:**
```css
.departures-slider    /* Mode slider par défaut */
.departures-grid      /* Mode grille */
.departures-list      /* Mode liste */
.dark-mode           /* Mode sombre activé */
.filter-tag          /* Tags de filtres actifs */
.btn-contact         /* Boutons contact WhatsApp */
```

**LocalStorage Keys:**
```javascript
'colis224_dark_mode'  // 'true' ou 'false'
```

---

## [2.10.0] - 2025-11-05

### 🚀 MODULE 33: SYSTÈME DE DÉPARTS ET RÉSERVATIONS

**Cette version majeure apporte un système complet d'affichage et de réservation des départs avec un slider automatique moderne et une intégration WhatsApp pour la réservation.**

### ✨ NOUVEAUTÉS MAJEURES

#### 🛫 MODULE 33: DÉPARTS ET RÉSERVATIONS

**Slider Automatique de Départs:**
- Slider moderne avec défilement automatique fluide
- Animation CSS avec pause automatique au survol (requis par l'utilisateur)
- Vitesse de défilement optimale: 4 secondes par carte
- Effet de boucle infinie pour défilement continu
- Design élégant avec gradients et animations
- Compatible mobile et desktop

**Filtres Avancés:**
- Filtrage par pays de départ (27 pays supportés)
- Filtrage par pays d'arrivée (27 pays supportés)
- Filtrage par type de transport: ✈️ Avion, 🚢 Bateau
- Filtrage par mois avec calendrier
- AJAX en temps réel sans rechargement de page
- Bouton de réinitialisation des filtres

**27 Pays Supportés avec Drapeaux:**
- 🇨🇳 Chine, 🇫🇷 France, 🇬🇳 Guinée, 🇸🇳 Sénégal
- 🇨🇮 Côte d'Ivoire, 🇲🇱 Mali, 🇧🇫 Burkina Faso, 🇳🇪 Niger
- 🇹🇬 Togo, 🇧🇯 Bénin, 🇬🇭 Ghana, 🇳🇬 Nigeria
- 🇨🇲 Cameroun, 🇨🇩 RD Congo, 🇬🇦 Gabon, 🇲🇦 Maroc
- 🇩🇿 Algérie, 🇹🇳 Tunisie, 🇪🇬 Égypte, 🇦🇪 Émirats Arabes Unis
- 🇸🇦 Arabie Saoudite, 🇹🇷 Turquie, 🇺🇸 États-Unis, 🇬🇧 Royaume-Uni
- 🇪🇸 Espagne, 🇮🇹 Italie, 🇧🇪 Belgique

**Réservation WhatsApp:**
- Bouton "Réserver" sur chaque carte de départ
- Redirection automatique vers WhatsApp (+224620178930)
- Message pré-rempli avec détails du départ
- Informations incluses: route, date, prix, numéro de départ
- Compatible desktop et mobile
- Ouverture automatique de l'application WhatsApp

**Interface Admin Complète:**
- Page d'administration "🚀 Départs" dans le menu Colis224
- CRUD complet (Créer, Lire, Modifier, Supprimer)
- Numérotation automatique unique: DEP-YYYYMMDD-XXXX
- Gestion des villes et pays (départ/arrivée)
- Sélection type de transport avec icônes
- Prix et devise configurables
- Places disponibles avec gestion du stock
- Statut actif/inactif pour chaque départ
- Tri et filtrage des départs
- Interface moderne avec badges colorés

**Widget WordPress:**
- Widget sidebar "🚀 Colis224 - Prochains Départs"
- Affichage compact pour zones latérales
- Options de configuration:
  - Nombre de départs à afficher (3-10)
  - Filtrage par type de transport
  - Affichage/masquage des prix
- Design adaptatif pour sidebars
- Animation au survol
- Style harmonisé avec le thème

**Shortcode Flexible:**
- `[colis224_departures]` pour affichage complet
- Paramètres optionnels:
  - `transport_type="plane"` ou `"boat"`
  - `departure_country="GN"`
  - `arrival_country="FR"`
  - `limit="10"` (nombre maximum)
- Intégration facile dans pages et articles
- Compatible page builders (Elementor, Divi, etc.)

**Fonctionnalités Avancées:**
- Badge "Départ imminent" pour départs < 7 jours
- Affichage prix avec devise (GNF, EUR, USD, etc.)
- Indication des places disponibles
- Heure de départ optionnelle
- Notes et informations supplémentaires
- Numéro WhatsApp personnalisable par départ

**Design Moderne:**
- Gradients violets/pourpres élégants
- Cartes avec effets de survol (hover)
- Animations CSS fluides
- Icônes de transport (avion/bateau)
- Drapeaux émojis pour identification rapide
- Bouton WhatsApp avec gradient vert
- Responsive 100% (mobile-first)
- Loading spinner professionnel
- Message "Aucun départ" stylisé

**Tables Créées:**
- `colis224_departures` - Table des départs (19 colonnes)
  - ID, numéro unique, villes départ/arrivée
  - Pays avec codes ISO (departure_country_code, arrival_country_code)
  - Date et heure de départ
  - Type de transport (plane/boat)
  - Prix et devise
  - Places disponibles et réservées
  - Numéro WhatsApp, notes
  - Statut actif/inactif
  - Timestamps (created_at, updated_at)

### 🔧 AMÉLIORATIONS TECHNIQUES

#### Base de Données
- **+1 nouvelle table** créée
- **Total: 63 tables** dans le système complet
- **Migration automatique** via dbDelta
- **Indexation optimale** (numéro unique, dates, statuts)
- **ENUM** pour type transport et statut

#### Architecture
- **5 nouveaux fichiers** créés:
  - `includes/class-colis224-departures.php` (450+ lignes)
  - `admin/class-colis224-departures-admin.php` (350+ lignes)
  - `includes/class-colis224-departures-widget.php` (150+ lignes)
  - `assets/css/departures-slider.css` (400+ lignes)
  - `assets/css/departures-widget.css` (80+ lignes)
  - `assets/js/departures-slider.js` (150+ lignes)
- **Code modulaire** et maintenable
- **POO strict** avec classes dédiées
- **Shortcode** et **Widget** WordPress natifs

#### JavaScript
- **Classe DeparturesSlider** pour gestion du slider
- **Auto-scroll** avec calcul dynamique de durée
- **Pause on hover** (fonctionnalité clé demandée)
- **Infinite scroll** par duplication des cartes
- **AJAX filters** sans rechargement
- **jQuery** pour compatibilité maximale
- **Nonce** de sécurité pour AJAX

#### Sécurité
- **Nonces WordPress** sur tous formulaires
- **Prepared statements** SQL systématiques
- **Sanitization complète** des inputs
- **Validation** des données (dates, prix, places)
- **Permissions** WordPress respectées (manage_options)

#### Performance
- **CSS animations** hardware-accelerated
- **Requêtes optimisées** avec WHERE clauses
- **AJAX** pour filtres (pas de reload complet)
- **Indexation** des champs de recherche
- **Cache potentiel** pour données statiques

### 📊 NOUVEAUX FICHIERS

```
includes/
├── class-colis224-departures.php         (Module principal)
└── class-colis224-departures-widget.php  (Widget WordPress)

admin/
└── class-colis224-departures-admin.php   (Interface admin)

assets/css/
├── departures-slider.css                 (Styles slider)
└── departures-widget.css                 (Styles widget)

assets/js/
└── departures-slider.js                  (Logique slider + AJAX)
```

### 🎯 TOTAL DES MODULES

**33 MODULES COMPLETS** couvrant 100% des besoins d'une grande entreprise logistique:

**Modules 1-32** (v2.9.0 et antérieures): [Liste précédente]

**MODULE 33** (v2.10.0 - NOUVEAU):
33. ✅ **Système de Départs et Réservations** - Slider automatique, filtres, WhatsApp

### 🚀 IMPACT BUSINESS

**Augmentation des Réservations:**
- Affichage attractif des prochains départs
- Réservation simplifiée via WhatsApp
- Visibilité accrue des destinations
- Expérience utilisateur fluide

**Communication Client:**
- Contact direct via WhatsApp
- Réponse rapide aux demandes
- Informations complètes et claires
- Message pré-rempli pour gain de temps

**Gestion Optimisée:**
- Planification des départs centralisée
- Suivi des places disponibles
- Filtres puissants pour clients
- Interface admin intuitive

**Visibilité Marketing:**
- Widget dans sidebar pour visibilité permanente
- Shortcode pour pages de vente
- Design moderne et attractif
- Compatible avec stratégies SEO

### ⚙️ CONFIGURATION REQUISE

- **WordPress**: 6.0+
- **PHP**: 8.0+
- **MySQL**: 5.7+
- **Extensions PHP**: mbstring, json

### 📝 NOTES DE MIGRATION

**Pour les utilisateurs v2.9.1:**
1. Sauvegarder la base de données
2. Mettre à jour le plugin
3. La nouvelle table est créée automatiquement
4. Accéder à "🚀 Départs" dans le menu admin
5. Configurer vos premiers départs

**Utilisation:**
- **Admin**: Colis224 > 🚀 Départs
- **Shortcode**: `[colis224_departures]`
- **Widget**: Apparence > Widgets > "🚀 Colis224 - Prochains Départs"

**Personnalisation:**
- Numéro WhatsApp par défaut: +224620178930 (configurable par départ)
- Couleurs modifiables via CSS (variables CSS recommandées)
- Durée auto-scroll: 4 secondes/carte (modifiable JS ligne 65)

---

## [2.9.0] - 2025-11-05

### 🚀 4 NOUVEAUX MODULES STRATÉGIQUES - GESTION OPTIMALE COMPLÈTE

**Cette version majeure apporte 4 modules professionnels avancés pour optimiser les opérations, réduire les risques, améliorer la planification et fournir une analytique décisionnelle de pointe.**

### ✨ NOUVEAUTÉS MAJEURES

#### 🚗 MODULE 28: GESTION DE FLOTTE DE VÉHICULES

**Gestion Complète des Véhicules:**
- Enregistrement détaillé: marque, modèle, immatriculation, année
- Types supportés: voiture, camionnette, camion, moto, vélo
- Carburants: essence, diesel, électrique, hybride
- Capacité en poids (kg) et volume (m³)
- Informations d'achat: date, prix
- Statut: actif, en maintenance, inactif, vendu

**Suivi de Maintenance:**
- Types: vidange, pneus, freins, contrôle technique, réparations
- Planification des maintenances avec dates de rappel
- Coûts et kilométrage au moment du service
- Fournisseur et factures
- Statut: planifié, complété, annulé

**Gestion du Carburant:**
- Enregistrement des ravitaillements
- Quantité en litres, coût par litre, coût total
- Suivi du kilométrage à chaque plein
- Calcul de consommation moyenne (L/100km)
- Station de service et reçus
- Statistiques sur 30/60/90 jours

**Attribution Véhicule-Livreur:**
- Affectation automatique ou manuelle
- Suivi kilométrage début/fin de mission
- Historique complet des affectations
- Statut: actif, complété, annulé

**Alertes Préventives:**
- Expiration assurance (30/14/7 jours avant)
- Contrôle technique à venir
- Kilométrage élevé
- Efficacité carburant anormale
- Niveaux de sévérité: low, medium, high, critical
- Notifications email automatiques aux admins

**Tables Créées:**
- `colis224_vehicles` - Base véhicules (20 colonnes)
- `colis224_vehicle_maintenance` - Historique maintenance (13 colonnes)
- `colis224_vehicle_fuel` - Ravitaillements (13 colonnes)
- `colis224_vehicle_assignments` - Affectations (9 colonnes)
- `colis224_vehicle_alerts` - Système d'alertes (10 colonnes)

#### 💎 MODULE 30: ASSURANCE ET DÉCLARATION DE VALEUR

**Déclaration de Valeur:**
- Déclaration pour colis précieux ou fragiles
- Montant déclaré avec description détaillée
- Catégories d'articles personnalisables
- 4 types de couverture: basic, standard, premium, custom
- Numérotation unique: DV-YYYYMMDD-XXXXXX
- Dates de début et fin de couverture

**Calcul Automatique des Primes:**
- **Basic** (0-500K GNF): 1.00% - Min 5,000 GNF
- **Standard** (500K-2M GNF): 2.00% - Min 10,000 GNF
- **Premium** (2M-10M GNF): 3.00% - Min 50,000 GNF
- **Custom** (10M+ GNF): 5.00% - Min 100,000 GNF
- Tarifs configurables par l'admin

**Gestion des Sinistres:**
- Types: dommage, perte, vol, perte partielle, retard
- Numérotation unique: CLM-YYYYMMDD-XXXXXX
- Description détaillée avec preuves (documents, photos)
- Support rapport de police
- Priorité: low, medium, high, urgent
- Statuts: submitted, under_review, approved, rejected, paid, closed

**Processus de Réclamation:**
1. Client soumet réclamation avec preuves
2. Admin examine et évalue
3. Approbation avec montant validé
4. Génération remboursement automatique
5. Paiement et clôture

**Remboursements:**
- Types: complet, partiel, avec franchise
- Méthodes: virement, mobile money, espèces, chèque
- Numérotation unique: RFD-YYYYMMDD-XXXXXX
- Statuts: pending, processing, completed, failed, cancelled
- Historique complet traçable

**Statistiques d'Assurance:**
- Total déclarations et primes collectées
- Nombre de réclamations (total, approuvées)
- Montant total remboursé
- Ratio de sinistralité (Loss Ratio)
- Analyses par période personnalisable

**Tables Créées:**
- `colis224_value_declarations` - Déclarations (16 colonnes)
- `colis224_insurance_claims` - Réclamations (19 colonnes)
- `colis224_insurance_refunds` - Remboursements (13 colonnes)
- `colis224_claim_history` - Historique (7 colonnes)
- `colis224_insurance_rates` - Tarifs (8 colonnes)

#### 📅 MODULE 31: RÉSERVATION ET PLANIFICATION

**Système de Créneaux Horaires:**
- Créneaux configurables par jour de semaine
- Horaires personnalisables (matin, après-midi, soir)
- Capacité maximale par créneau
- Filtres par zone géographique
- Créneaux par défaut: Lun-Ven 8h-12h / 14h-18h, Sam 8h-13h

**Réservation Client:**
- Sélection date et créneau disponible
- Numérotation unique: BK-YYYYMMDD-XXXXXX
- Notes client et admin
- Statuts: pending, confirmed, cancelled, completed, rescheduled
- Confirmation automatique par email
- Possibilité de reprogrammer

**Planification de Tournées:**
- Création de tournées quotidiennes
- Numérotation unique: RT-YYYYMMDD-XXXXXX
- Attribution chauffeur et véhicule
- Heure début/fin et durée estimée
- Point de départ avec coordonnées GPS
- Distance totale calculée
- Statuts: draft, scheduled, in_progress, completed, cancelled

**Gestion des Étapes:**
- Ordre des étapes paramétrable
- Adresse avec coordonnées GPS
- Heures d'arrivée estimées et réelles
- Durée estimée par étape (défaut 15 min)
- Photo de livraison et signature
- Statuts: pending, skipped, completed, failed

**Optimisation de Charge:**
- Calcul poids et volume total des colis
- Comparaison avec capacité véhicule
- Taux d'utilisation en pourcentage
- Score d'optimisation (0-100)
  - 80-95% = Excellent (score 100)
  - >95% = Surcharge (pénalité)
  - <80% = Sous-utilisation
- Recommandations automatiques

**Optimisation d'Itinéraire:**
- Algorithme de tri intelligent
- Regroupement par zones géographiques
- Réduction distance totale
- Minimisation du temps de trajet
- (Support futur: Google Maps API, algorithme voyageur de commerce)

**Tables Créées:**
- `colis224_delivery_slots` - Créneaux horaires (11 colonnes)
- `colis224_slot_bookings` - Réservations (14 colonnes)
- `colis224_delivery_routes` - Tournées (17 colonnes)
- `colis224_route_stops` - Étapes (13 colonnes)
- `colis224_load_optimization` - Optimisation charge (9 colonnes)

#### 📊 MODULE 32: TABLEAU DE BORD ANALYTIQUE AVANCÉ

**KPIs en Temps Réel:**
- Total colis avec comparaison période précédente
- Revenus totaux avec tendance
- Nouveaux clients et croissance
- Taux de livraison avec évolution
- Satisfaction client (moyenne avis)
- Valeur moyenne par colis
- Indicateurs de tendance: up, down, stable
- Pourcentages de changement calculés

**Graphiques Interactifs:**
- **Timeline colis**: Évolution quotidienne création colis
- **Timeline revenus**: Évolution quotidienne des revenus
- **Distribution statuts**: Répartition colis par statut (pie chart)
- **Top clients**: Top 10 clients par nombre et valeur
- **Performance livreurs**: Livraisons réussies, temps moyen
- **Comparaison mensuelle**: 12 derniers mois colis/revenus
- Support Chart.js pour visualisations riches

**Prévisions Intelligentes:**
- Algorithme de régression linéaire
- Prévisions à 7, 14, 30 jours
- Types: colis futurs, revenus attendus
- Niveau de confiance (75% par défaut)
- Basé sur historique 90 derniers jours
- Stockage des prévisions vs. réalisations

**Snapshots Quotidiens:**
- Capture automatique données chaque jour (cron)
- Total colis, clients, revenus, dépenses
- Bénéfice net calculé
- Chauffeurs et véhicules actifs
- Réclamations en attente
- Satisfaction client moyenne
- Données JSON supplémentaires (top clients, distribution)
- Historique complet pour analyses

**Comparaison de Périodes:**
- Comparer 2 périodes personnalisées
- Métriques: colis, revenus, valeur moyenne
- Calcul automatique des variations
- Pourcentages de changement
- Support benchmarking annuel

**Rapports Personnalisés:**
- Création de rapports sur mesure
- Paramètres et filtres configurables
- Types de graphiques: bar, line, pie, area
- Planification automatique (daily, weekly, monthly)
- Export formats: CSV, JSON
- Historique des générations

**Export Avancé:**
- Export CSV avec UTF-8 BOM
- Séparateur point-virgule (Excel français)
- Export JSON pour intégrations
- Nom de fichiers personnalisables
- Headers automatiques
- Données formatées

**Tables Créées:**
- `colis224_kpi_history` - Historique KPIs (8 colonnes)
- `colis224_custom_reports` - Rapports personnalisés (10 colonnes)
- `colis224_data_snapshots` - Snapshots quotidiens (12 colonnes)
- `colis224_forecasts` - Prévisions (9 colonnes)

### 🔧 AMÉLIORATIONS TECHNIQUES

#### Base de Données
- **+19 nouvelles tables** créées
- **Total: 62 tables** dans le système complet
- **Migration automatique** via dbDelta
- **Indexation optimale** pour performances
- **Foreign keys** et relations bien définies

#### Architecture
- **4 nouveaux modules** parfaitement intégrés
- **Code modulaire** et maintenable
- **POO strict** avec classes dédiées
- **Méthodes statiques** pour simplicité
- **Calculs automatiques** et intelligents

#### Sécurité
- **Nonces WordPress** sur tous formulaires
- **Prepared statements** SQL systématiques
- **Sanitization complète** des inputs
- **Validation** des données métier
- **Permissions** WordPress respectées

#### Performance
- **Cron jobs** pour tâches quotidiennes
- **Indexation** des champs de recherche
- **Requêtes optimisées** avec JOINs
- **Cache** potentiel pour KPIs
- **Algorithmes** efficaces

### 📊 NOUVEAUX FICHIERS

```
includes/
├── class-colis224-fleet-management.php   (Gestion flotte - 450 lignes)
├── class-colis224-insurance.php          (Assurance - 520 lignes)
├── class-colis224-scheduling.php         (Réservation - 600 lignes)
└── class-colis224-analytics.php          (Analytique - 480 lignes)
```

### 🎯 TOTAL DES MODULES

**31 MODULES COMPLETS** couvrant 100% des besoins d'une grande entreprise logistique:

**Modules 1-27** (v2.8.0 et antérieures):
1-21. Modules de base v1.0 à v2.7.0
22-27. Modules v2.8.0 (Live Chat, Stock Avancé, Marketing, Portail Partenaire, Workflows, Multi-langues)

**Modules 28-31** (v2.9.0 - NOUVEAU):
28. ✅ **Gestion de Flotte de Véhicules** - Maintenance, carburant, alertes
30. ✅ **Assurance et Déclaration de Valeur** - Sinistres, réclamations, remboursements
31. ✅ **Réservation et Planification** - Créneaux, tournées, optimisation
32. ✅ **Tableau de Bord Analytique** - KPIs, graphiques, prévisions

### 🚀 IMPACT BUSINESS

**Optimisation Opérationnelle:**
- Maintenance préventive véhicules
- Réduction coûts carburant
- Alertes proactives
- Meilleure disponibilité flotte

**Réduction des Risques:**
- Protection colis de valeur
- Gestion professionnelle sinistres
- Processus remboursement transparent
- Statistiques de sinistralité

**Satisfaction Client:**
- Réservation créneaux flexibles
- Planification précise livraisons
- Confirmations automatiques
- Optimisation des délais

**Décisions Éclairées:**
- KPIs temps réel
- Prévisions basées sur données
- Comparaisons périodes
- Rapports personnalisables

### ⚙️ CONFIGURATION REQUISE

- **WordPress**: 6.0+
- **PHP**: 8.0+
- **MySQL**: 5.7+
- **Extensions PHP**: mbstring, json, session

### 📝 NOTES DE MIGRATION

**Pour les utilisateurs v2.8.0:**
1. Sauvegarder la base de données
2. Mettre à jour le plugin
3. Les 19 nouvelles tables sont créées automatiquement
4. Vérifier les alertes véhicules dans Paramètres

**Tâches Cron Automatiques:**
- Vérification quotidienne alertes véhicules
- Snapshots quotidiens de données
- Vérification stock minimum
- Génération prévisions hebdomadaires

**Nouveaux Menus Admin (à venir):**
- 🚗 Flotte de Véhicules
- 💎 Assurance & Déclarations
- 📅 Planification & Tournées
- 📊 Analytique Avancée

---

## [2.8.0] - 2025-11-05

### 🚀 6 NOUVEAUX MODULES PROFESSIONNELS - SYSTÈME ULTRA-COMPLET

**Cette version apporte 6 modules stratégiques majeurs pour transformer Colis224 en solution logistique de classe mondiale avec automatisation complète, marketing, support client en temps réel et internationalisation.**

### ✨ NOUVEAUTÉS MAJEURES

#### 💬 MODULE 22: LIVE CHAT SUPPORT

**Chat en Temps Réel pour Clients:**
- Interface de chat moderne intégrée à l'espace client
- Polling automatique toutes les 3 secondes
- Shortcode `[colis224_live_chat]` pour intégration facile
- Historique complet des conversations
- Notifications en temps réel

**Interface Admin Dédiée:**
- Dashboard de conversations avec filtres (actives, en attente, archivées)
- Statut des agents: Online, Away, Busy, Offline
- Réponses rapides pré-configurées
- Vue temps réel des conversations
- Auto-refresh toutes les 5-10 secondes
- Notifications automatiques aux managers

**Tables Créées:**
- `colis224_chat_conversations` - Gestion des conversations
- `colis224_chat_messages` - Historique des messages
- `colis224_chat_agents_status` - Statut des agents
- `colis224_chat_quick_replies` - Réponses rapides

#### 📦 MODULE 23: GESTION DE STOCK AVANCÉE

**Gestion des Emplacements:**
- Organisation complète: zones, allées, étagères, casiers
- Codes d'emplacement uniques (ex: A1-S2-B3)
- Suivi de capacité et occupation
- Emplacement actif/inactif

**Mouvements de Stock:**
- Tracking complet: entrées, sorties, transferts, ajustements
- Historique détaillé avec user_id
- Notes pour chaque mouvement
- Traçabilité complète

**Alertes Automatiques:**
- Stock minimum détecté
- Saturation entrepôt (75%, 90%)
- Localisation pleine
- Colis expirant
- Notifications email pour alertes critiques
- Vérifications quotidiennes via cron

**Audits de Stock:**
- Planification d'audits
- Comparaison attendu vs réel
- Calcul des écarts
- Historique complet

**Tables Créées:**
- `colis224_inventory_locations` - Emplacements détaillés
- `colis224_inventory_movements` - Mouvements de stock
- `colis224_inventory_alerts` - Système d'alertes
- `colis224_inventory_audits` - Audits de stock

#### 📧 MODULE 24: MARKETING AUTOMATION

**Segmentation Client Intelligente:**
- **Clients VIP**: Plus de 10 colis
- **Nouveaux Clients**: Inscrits < 30 jours
- **Clients Inactifs**: Aucun colis depuis 90 jours
- **Programme Fidélité**: Membres actifs
- Conditions JSON configurables
- Compteur automatique de clients par segment

**Campagnes Marketing:**
- Campagnes Email, SMS ou les deux
- Planification avec dates futures
- Envoi automatique via cron horaire
- Suivi complet: envoyés, livrés, échecs
- Statuts: Draft, Scheduled, Sending, Completed, Cancelled
- Templates de messages personnalisables

**Codes Promotionnels:**
- Réduction en pourcentage ou montant fixe
- Montant minimum d'achat
- Nombre d'utilisations max
- Dates de validité
- Statut actif/inactif
- Suivi des utilisations

**Statistiques Marketing:**
- Total campagnes actives
- Envois du mois
- Taux de succès
- Performance en temps réel

**Tables Créées:**
- `colis224_customer_segments` - Segments clients
- `colis224_campaigns` - Campagnes marketing
- `colis224_campaign_sends` - Historique envois
- `colis224_promo_codes` - Codes promotionnels

#### 🤝 MODULE 25: PORTAIL PARTENAIRE COMPLET

**Authentification Partenaire:**
- Système de login dédié
- Username + mot de passe crypté
- Sessions PHP sécurisées
- Shortcode `[colis224_partner_login]`

**Dashboard Partenaire:**
- Statistiques en temps réel:
  - Colis confiés ce mois
  - Colis reçus ce mois
  - Balance des transactions
  - Transaction history
- Shortcode `[colis224_partner_dashboard]`
- Interface moderne et responsive

**Gestion des Commissions:**
- Tracking automatique des commissions
- Types: fixed, percentage
- Statut: pending, paid, cancelled
- Historique complet

**Facturation Partenaires:**
- Génération de factures automatiques
- Suivi des paiements
- Dates d'émission et échéance
- Statuts de paiement

**Tables Créées:**
- `colis224_partner_access` - Accès et authentification
- `colis224_partner_commissions` - Commissions
- `colis224_partner_invoices` - Facturation

#### ⚙️ MODULE 26: AUTOMATISATION DES WORKFLOWS

**Système de Règles If...Then:**
- Conditions multiples combinables
- Actions multiples par règle
- Priorité d'exécution (1-10)
- Activation/désactivation facile

**Déclencheurs Disponibles:**
- `parcel_created` - Création de colis
- `status_changed` - Changement de statut
- `payment_received` - Paiement reçu
- `delivery_completed` - Livraison effectuée
- `payment_delayed` - Retard de paiement

**Conditions Configurables:**
- Statut du colis
- Statut de paiement
- Type de client (VIP, normal)
- Montant minimum/maximum
- Jours depuis création

**Actions Automatiques:**
- `send_email` - Envoi email automatique
- `send_sms` - Envoi SMS automatique
- `set_priority` - Modification de priorité
- `update_status` - Changement de statut
- `notify_manager` - Alerte manager

**Règles Par Défaut:**
1. **Notification Arrivée**: Email + SMS automatique dès réception
2. **Priorité VIP**: Priorisation automatique des clients VIP
3. **Relance Paiement J+3**: Rappel paiement après 3 jours
4. **Alerte Retard**: Notification si livraison > 7 jours

**Historique d'Exécution:**
- Log complet de chaque exécution
- Résultat: success, failed, skipped
- Message d'erreur si échec
- Traçabilité totale

**Tables Créées:**
- `colis224_workflow_rules` - Règles d'automatisation
- `colis224_workflow_executions` - Historique exécutions

#### 🌍 MODULE 27: MULTI-LANGUES COMPLET

**4 Langues Supportées:**
- 🇫🇷 **Français** (défaut)
- 🇬🇧 **English**
- 🇸🇦 **العربية** (avec support RTL)
- 🇨🇳 **中文**

**Détection Automatique:**
- Langue du navigateur détectée
- Stockage en session PHP
- Cookie 30 jours pour persistance
- Changement à la volée

**Sélecteur de Langue:**
- Shortcode `[colis224_language_selector]`
- Dropdown avec drapeaux
- AJAX pour changement sans reload
- Design moderne et responsive

**Traductions Pré-Remplies:**
- **Navigation**: Dashboard, Parcels, Clients, Tracking, Settings
- **États Colis**: Pending, Shipped, Delivered, Transit
- **Paiements**: Paid, Unpaid, Partial
- **Actions**: Search, Save, Cancel, Delete, Edit
- **Messages**: Success, Error, Welcome
- **Formulaires**: Name, Email, Phone, Address, Tracking
- **Dates**: Today, Yesterday, Week, Month

**Fonction Helper:**
```php
colis224_t('status_pending', 'En attente');
// Retourne la traduction dans la langue courante
```

**Méthodes Disponibles:**
- `translate($key, $default)` - Traduction d'une clé
- `get_language()` - Langue courante
- `is_rtl()` - Vérification RTL
- `get_available_languages()` - Liste des langues

**Table Créée:**
- `colis224_translations` - Stockage des traductions

### 🔧 AMÉLIORATIONS TECHNIQUES

#### Base de Données
- **+18 nouvelles tables** créées
- **Total: 43 tables** dans le système complet
- **Migration automatique** via dbDelta
- **Indexation optimale** pour performances
- **Foreign keys** et contraintes

#### Architecture
- **6 nouveaux modules** parfaitement intégrés
- **Code modulaire** et maintenable
- **POO strict** avec classes dédiées
- **Hooks WordPress** pour extensibilité
- **Actions et filters** personnalisés

#### Sécurité
- **Nonces WordPress** partout
- **Sessions PHP** sécurisées
- **Prepared statements** SQL
- **Sanitization complète** des inputs
- **CSRF protection** renforcée

#### Performance
- **Cron jobs** optimisés (hourly/daily)
- **Polling intelligent** (3-10 secondes)
- **Cache** pour traductions
- **Requêtes optimisées** avec index
- **Lazy loading** des modules

### 📊 NOUVEAUX FICHIERS

```
includes/
├── class-colis224-live-chat.php          (Chat client)
├── class-colis224-inventory-advanced.php (Stock avancé)
├── class-colis224-marketing.php          (Marketing automation)
├── class-colis224-partner-portal.php     (Portail partenaire)
├── class-colis224-workflows.php          (Workflows automatisés)
└── class-colis224-multilang.php          (Multi-langues)

admin/
└── class-colis224-live-chat-admin.php    (Admin chat)
```

### 🎯 TOTAL DES MODULES

**27 MODULES COMPLETS** couvrant 100% des besoins logistiques:

**Modules 1-21** (v2.7.0 et antérieures):
1. Gestion des Colis
2. Gestion des Clients
3. Gestion des Partenaires
4. Gestion de l'Équipe
5. Comptabilité Complète
6. Rapports et Statistiques
7. Service d'Achat
8. Portail Client Frontend
9. Programme de Fidélité
10. Multi-Entrepôts
11. SMS API
12. Paiements Mobile Money
13. Système de Permissions
14. API REST Complète
15. Export Avancé
16. QR Codes et Étiquettes
17. Notifications
18. Support SAV
19. Intégration PayPal
20. Relances Automatiques & Anti-Vol
21. Avis Clients

**Modules 22-27** (v2.8.0 - NOUVEAU):
22. ✅ **Live Chat Support** - Support client temps réel
23. ✅ **Gestion de Stock Avancée** - Emplacements, alertes, audits
24. ✅ **Marketing Automation** - Campagnes, segments, promos
25. ✅ **Portail Partenaire Complet** - Dashboard et gestion
26. ✅ **Automatisation des Workflows** - Règles if...then
27. ✅ **Multi-Langues Complet** - 4 langues avec RTL

### 🚀 IMPACT BUSINESS

**Support Client:**
- Réponses instantanées via live chat
- Satisfaction client améliorée
- Réduction charge support

**Optimisation Stock:**
- Alertes proactives stock minimum
- Audits réguliers automatiques
- Réduction pertes et vols

**Marketing Ciblé:**
- Segmentation client précise
- Campagnes automatisées
- ROI mesurable

**Automatisation:**
- Réduction tâches manuelles
- Processus standardisés
- Gain de temps équipe

**Internationalisation:**
- Ouverture marchés internationaux
- Expérience client localisée
- Support RTL pour l'arabe

### ⚙️ CONFIGURATION REQUISE

- **WordPress**: 6.0+
- **PHP**: 8.0+
- **MySQL**: 5.7+
- **Extensions PHP**: mbstring, json, session

### 📝 NOTES DE MIGRATION

**Pour les utilisateurs v2.7.0:**
1. Sauvegarder la base de données
2. Mettre à jour le plugin
3. Les nouvelles tables sont créées automatiquement
4. Configurer les nouveaux modules dans Paramètres

**Nouveaux Menus Admin:**
- 💬 Live Chat (sous Colis224)

**Nouveaux Shortcodes:**
- `[colis224_live_chat]` - Chat client
- `[colis224_partner_login]` - Login partenaire
- `[colis224_partner_dashboard]` - Dashboard partenaire
- `[colis224_language_selector]` - Sélecteur langue

---

## [2.7.0] - 2025-01-06

### 🛡️ SYSTÈME ANTI-VOL & SURVEILLANCE AUTOMATIQUE

**Système de prévention des pertes et relances automatiques pour limiter l'accumulation et les vols.**

### ✨ Nouveautés

#### 🛡️ **MODULE 20: Système de Relances Automatiques et Anti-Vol**

**Relances Automatiques:**
- Relances clients automatiques J+3, J+7, J+14
- Alertes agents et managers pour colis non retirés
- Notifications arrivée de colis
- Rappels paiement en attente
- Configuration personnalisable des déclencheurs
- Templates de messages personnalisables avec variables
- Envoi Email + SMS automatique
- Planification quotidienne, 3 jours, hebdomadaire

**Surveillance Anti-Vol:**
- Calcul automatique du niveau de risque (Bas, Moyen, Élevé, Critique)
- Logs de surveillance complets
- Alertes critiques automatiques pour colis ≥14 jours
- Détection des colis à risque de perte
- Dashboard de surveillance en temps réel

**Rapports Automatiques:**
- Résumé tous les 3 jours des colis non retirés
- Rapport hebdomadaire détaillé
- Liste complète: N° tracking, client, téléphone, jours d'attente, montant
- Total valeur en stock
- Total impayé
- Envoi automatique aux admins et agents

**Statistiques Temps Réel:**
- Colis en attente ≥3 jours
- Colis en attente ≥7 jours
- Colis critiques ≥14 jours
- Valeur totale en stock
- Montant total impayé

**Tables créées:**
- `colis224_reminders` - Historique des relances envoyées
- `colis224_surveillance_logs` - Logs de surveillance et événements
- `colis224_reminder_config` - Configuration des règles de relance

#### ⭐ **MODULE 21: Système d'Évaluation et Avis Clients**

**Après Retrait:**
- Email + SMS automatique invitant à laisser un avis
- Lien personnalisé vers formulaire d'avis
- Demande envoyée automatiquement lors du retrait

**Formulaire d'Avis:**
- Interface élégante et responsive
- 4 catégories de notation (⭐ 1-5):
  - Note globale
  - Qualité de la livraison
  - Qualité du service
  - État de l'emballage
- Commentaire optionnel
- Recommandation (Oui/Non)
- Vérification: un seul avis par colis

**Gestion des Avis:**
- Statistiques moyennes par catégorie
- Pourcentage de recommandation
- Notification admin à chaque nouvel avis
- Avis publics/privés
- Possibilité de réponse admin

**Table créée:**
- `colis224_reviews` - Stockage des avis clients

### 🎯 Objectifs Atteints

**✅ Prévention des Vols et Pertes:**
- Surveillance 24/7 automatique
- Alertes multi-niveaux
- Traçabilité complète
- Réduction accumulation stock

**✅ Amélioration Recouvrement:**
- Relances systématiques
- Rappels paiement automatiques
- Réduction des impayés
- Accélération des retraits

**✅ Qualité de Service:**
- Feedback client structuré
- Amélioration continue
- Satisfaction mesurable
- Engagement client

### 🔧 Améliorations Techniques

- Tâches cron WordPress pour automatisation
- Calcul intelligent du niveau de risque
- Templates de messages avec variables dynamiques
- Interface admin dédiée surveillance
- Dashboard statistiques temps réel
- Filtres avancés (risque, statut, période)
- Historique complet des actions
- Configuration flexible des règles

### 📊 Total des Modules

**21 MODULES COMPLETS** maintenant disponibles:
1-19. (Modules v2.6.0 et antérieurs)
20. ✅ **Relances Automatiques & Surveillance Anti-Vol** - NOUVEAU
21. ✅ **Système d'Avis Clients** - NOUVEAU

### 🚀 Impact Business

- **Réduction des pertes** par surveillance proactive
- **Amélioration trésorerie** par relances automatiques
- **Optimisation stock** par détection accumulation
- **Satisfaction client** mesurée et tracée
- **Gain de temps** équipes par automatisation

---

## [2.6.0] - 2025-01-06

### 🎫 Système de Support SAV et PayPal

**Nouvelles fonctionnalités majeures ajoutées au système complet de gestion logistique.**

### ✨ Nouveautés

#### 🎫 **MODULE 18: Système de Tickets Support/SAV**
- Création et gestion complète des tickets support
- Catégories: Colis, Paiement, Réclamation, Information, Technique, Autre
- Niveaux de priorité: Basse, Normale, Haute, Urgente
- Statuts: Ouvert, En cours, Résolu, Fermé
- Attribution des tickets aux membres de l'équipe
- Système de conversation avec messages multiples
- Notes internes (invisibles pour les clients)
- Notifications automatiques par Email et SMS
- Génération automatique de numéros de ticket (TKT-XXXXXXXX)
- Statistiques en temps réel (ouverts, en cours, résolus, urgents)
- Filtres avancés par statut, priorité et catégorie
- Liaison avec les colis et les clients

**Tables créées:**
- `colis224_support_tickets` - Tickets support
- `colis224_support_messages` - Messages et conversations

#### 💳 **Intégration PayPal**
- Paiement sécurisé via PayPal
- Mode Sandbox pour tests
- Mode Production pour paiements réels
- Configuration simple (Client ID + Secret)
- Webhooks de confirmation automatique
- Intégration dans les paiements de colis
- Historique des transactions PayPal

### 🔧 Améliorations
- Interface admin dédiée pour le support
- Actions rapides (changement de statut, attribution)
- Vue détaillée des tickets avec historique complet
- Réponse aux tickets directement depuis l'admin
- PayPal ajouté aux méthodes de paiement disponibles

### 📊 Total des Modules
**19 MODULES COMPLETS** maintenant disponibles:
1-17. (Modules v2.5.0)
18. ✅ **Support / SAV** - Nouveau
19. ✅ **PayPal** - Nouveau

---

## [2.5.0] - 2025-01-05

### 🎊 VERSION FINALE - SYSTÈME COMPLET DE GESTION LOGISTIQUE

**Cette version marque l'aboutissement complet du plugin Colis224 Logistics Manager avec TOUTES les fonctionnalités opérationnelles.**

###  ✅ 17 MODULES COMPLETS DISPONIBLES

1. ✅ **Gestion des Colis** - CRUD complet avec tracking
2. ✅ **Gestion des Clients** - Profils + Fidélité intégrée
3. ✅ **Gestion des Partenaires** - Transactions et commissions
4. ✅ **Gestion de l'Équipe** - Membres et salaires
5. ✅ **Comptabilité Complète** - Revenus/Dépenses/Bénéfices
6. ✅ **Rapports et Statistiques** - Dashboard + Exports
7. ✅ **Service d'Achat** - Demandes clients
8. ✅ **Portail Client Frontend** - Tracking + Espace client
9. ✅ **Programme de Fidélité** - Points, tiers, récompenses
10. ✅ **Multi-Entrepôts** - Inventaire et transferts
11. ✅ **SMS API** - Orange, Africa's Talking, Twilio
12. ✅ **Paiements Mobile Money** - Orange, MTN, Moov
13. ✅ **Système de Permissions** - Rôles et accès
14. ✅ **API REST Complète** - Intégrations tierces
15. ✅ **Export Avancé** - Excel, PDF, CSV
16. ✅ **QR Codes et Étiquettes** - Génération automatique
17. ✅ **Notifications** - Email + SMS automatiques

### 🌟 Points Forts de cette Version

**SMS API Orange Guinée:**
- Configuration complète dans Paramètres > SMS
- Client ID + Client Secret
- Nom expéditeur personnalisable (max 11 caractères)
- Envoi automatique pour notifications colis et fidélité
- Historique des SMS

**Paiements Mobile Money:**
- **Orange Money** (Guinée, Sénégal, Côte d'Ivoire)
- **MTN Mobile Money** (MoMo)
- **Moov Money**
- Modes Sandbox/Production
- Webhooks de confirmation

**Multi-Entrepôts:**
- Gestion complète des entrepôts (codes, capacités, responsables)
- Inventaire en temps réel par entrepôt
- Transferts inter-entrepôts avec confirmation
- Taux de remplissage visuel
- Statistiques détaillées

**Programme de Fidélité:**
- Widget dans formulaire colis
- Attribution automatique 10 pts/colis
- 4 tiers: Bronze, Silver, Gold, Platinum
- Dashboard Top 10 clients
- Notifications automatiques

**QR Code:**
- Redirection automatique vers page de tracking
- Page standalone professionnelle
- Design moderne avec gradient
- Chargement automatique des informations

### 📋 Configuration Requise

- WordPress: 6.0+
- PHP: 8.0+
- MySQL: 5.7+

### 🎯 Prêt pour Production

✅ Toutes les fonctionnalités implémentées
✅ Interface moderne et responsive
✅ Sécurité WordPress standard
✅ Documentation complète
✅ Support multi-pays
✅ Intégrations API tierces

---

## [2.4.1] - 2025-01-04

### 🐛 CORRECTION CRITIQUE - QR Code Redirection

**Problème Résolu:**
- ✅ QR code redirige maintenant automatiquement vers une page de suivi dédiée
- ✅ Création d'une page standalone pour le tracking (ne nécessite plus de shortcode)
- ✅ Affichage automatique des informations du colis dès le scan du QR code

**Nouvelles Fonctionnalités:**

1. **Page de Tracking Automatique** 📱
   - Détection automatique du paramètre `?colis224_track=NUMERO`
   - Page complète standalone avec design moderne
   - Background gradient violet/rose
   - Logo Colis224 centré
   - Formulaire de tracking pré-rempli
   - Recherche automatique au chargement
   - Bouton retour à l'accueil

2. **Améliorations UX**
   - Template responsive optimisé mobile
   - Chargement immédiat des informations du colis
   - Pas besoin de page WordPress avec shortcode
   - Fonctionne directement après scan du QR code

**Impact:**
- Les QR codes sur les étiquettes fonctionnent parfaitement
- Expérience client fluide et professionnelle
- Tracking instantané en scannant le code

---

## [2.4.0] - 2025-01-04

### 🎉 INTÉGRATION COMPLÈTE DU PROGRAMME DE FIDÉLITÉ

**Nouvelles Fonctionnalités Majeures:**

1. **Widget Fidélité dans Formulaire Colis** 🎁
   - ✅ Affichage automatique des points du client lors de la création d'un colis
   - ✅ Visualisation du tier actuel (🥉 Bronze / 🥈 Silver / 🥇 Gold / 💎 Platinum)
   - ✅ Aperçu des points qui seront gagnés pour ce colis
   - ✅ Interface dynamique avec chargement AJAX
   - ✅ Design moderne avec gradient violet

2. **Dashboard - Top 10 Clients Fidèles** 🏆
   - ✅ Widget dédié affichant les 10 meilleurs clients du programme
   - ✅ Classement par points totaux avec rang (1-10)
   - ✅ Badges visuels personnalisés pour chaque tier
   - ✅ Statistiques complètes du programme :
     - Nombre total de membres inscrits
     - Points attribués (total cumulé)
     - Points échangés (récompenses)
     - Nombre de récompenses échangées
   - ✅ Répartition des membres par tier

3. **Notifications Automatiques** 📧 📱
   - ✅ Email automatique HTML lors du gain de points
   - ✅ SMS automatique via système existant
   - ✅ Notification spéciale lors de promotion de tier
   - ✅ Design email moderne avec gradients et emojis
   - ✅ Informations complètes (solde, tier, progression)

4. **Section Fidélité dans Détails Client** 👤
   - ✅ Vue complète du profil fidélité :
     - Points disponibles (prêts à échanger)
     - Points totaux (cumulés depuis inscription)
     - Points échangés (récompenses obtenues)
     - Badge de tier avec couleurs personnalisées
   - ✅ Barre de progression vers le prochain tier
   - ✅ Message spécial pour membres Platinum (tier max)
   - ✅ Historique des 10 dernières transactions
   - ✅ Informations du programme et dates clés

**Améliorations Techniques:**

- Attribution automatique de 10 points par colis créé
- Attribution automatique de points basés sur les paiements (1 point / 100 GNF)
- Auto-inscription au programme lors du premier colis
- Actions WordPress pour extensibilité :
  - `colis224_parcel_created` - Déclenché à la création d'un colis
  - `colis224_payment_received` - Déclenché lors d'un paiement
- Endpoint AJAX : `colis224_get_client_loyalty` pour récupération des données
- Système de tiers automatique :
  - Bronze : 0-1,999 points
  - Silver : 2,000-4,999 points
  - Gold : 5,000-9,999 points
  - Platinum : 10,000+ points

**Design et UX:**

- Couleurs de tier distinctives avec gradients CSS3
- Icônes émojis pour meilleure lisibilité (🥉 🥈 🥇 💎 🎉 🎊)
- Cartes avec gradients modernes et ombres
- Animations et transitions CSS fluides
- Responsive design pour tous les écrans
- Interface cohérente avec le reste du plugin

**Impact:**
- Engagement client renforcé grâce au programme de fidélité
- Visibilité complète sur les clients les plus fidèles
- Notifications automatiques pour garder les clients informés
- Expérience utilisateur premium pour l'administration

---

## [2.3.3] - 2025-01-04

### 🐛 HOTFIX FINAL - Corrections Espacement + Loader Étiquette

**Corrections Appliquées:**

1. **Suppression Texte "Génération de l'étiquette..."**
   - ✅ Remplacé par un loader animé professionnel
   - ✅ Spinner centré avec animation CSS
   - ✅ Le texte disparaît complètement quand l'étiquette se charge
   - ✅ Utilisation de `document.open()` pour effacer le contenu précédent

2. **Espacement Icônes - Correction Définitive**
   - ✅ Ajout règles CSS ultra-spécifiques avec `!important`
   - ✅ `.wrap a .dashicons` forcé à 6px margin
   - ✅ `.colis224-wrap .dashicons` espacé à 6px
   - ✅ Tous les dashicons-arrow-left-alt2 espacés
   - ✅ Tous les dashicons-plus-alt espacés
   - ✅ Tous les dashicons-edit espacés
   - ✅ Ajout `::after` pseudo-element pour garantir l'espace

3. **QR Code - Redirection Directe**
   - ✅ QR code contient URL tracking: `?colis224_track=NUMERO`
   - ✅ Scan → Redirection automatique vers page de suivi
   - ✅ Affichage instantané des informations du colis

**Impact:**
- Interface 100% propre et professionnelle
- Aucun texte parasite visible
- Tous les espaces icônes corrigés définitivement
- QR codes pleinement fonctionnels

---

## [2.3.2] - 2025-01-04

### 🐛 HOTFIX - Corrections UX/UI et Améliorations

**Problèmes Résolus:**

1. **QR Code - Correction Complète**
   - ✅ Suppression du texte parasite à côté de l'étiquette
   - ✅ QR code maintenant contient l'URL de suivi (au lieu de vCard)
   - ✅ Scan QR code redirige vers page de suivi avec informations
   - ✅ Ajout "www.colis224.com" en haut de l'étiquette d'expédition

2. **Espacement des Icônes - Corrections Finales**
   - ✅ Icône flèche retour correctement espacée du cadre
   - ✅ Icône crayon (modifier) correctement espacée
   - ✅ Icône "+" (nouveau client) espacée du texte "Nouveau client"
   - ✅ Icône "+" (nouvelle demande) espacée dans service d'achat
   - ✅ Ajout règles CSS spécifiques `!important` pour tous les boutons

3. **Page Facturation - Design Moderne et Professionnel**
   - ✅ Design moderne avec dégradés et ombres
   - ✅ **Informations Colis224 ajoutées:**
     - 📧 Email: contact@colis224.com
     - 📞 Téléphone: +224 620 17 89 30
     - 📍 Adresse: Conakry, Guinée
     - 🌐 Site web: www.colis224.com
   - ✅ En-tête avec fond dégradé moderne
   - ✅ Pied de page professionnel avec toutes les coordonnées
   - ✅ Police améliorée (Segoe UI)
   - ✅ Bordures arrondies et ombres portées
   - ✅ Meilleure hiérarchie visuelle

**Améliorations Techniques:**

```css
/* Nouvelles règles CSS ajoutées */
- Correction spécifique pour liens retour et boutons
- a[href*="page=colis224"] .dashicons !important
- .button .dashicons-plus-alt !important
- .button .dashicons-arrow-left-alt2 !important
- a.button .dashicons:first-child !important
```

**Impact Utilisateur:**
- Interface plus propre et professionnelle
- QR codes fonctionnels à 100%
- Factures imprimables de qualité professionnelle
- Meilleure cohérence visuelle dans tout le plugin

---

## [2.3.1] - 2025-01-04

### 🐛 HOTFIX - Migration Automatique Base de Données

**Problème Résolu:**
Les tables de fidélité et multi-entrepôts n'étaient pas créées lors de la mise à jour du plugin depuis une version antérieure, causant des erreurs SQL.

**Solution Implémentée:**
- ✅ Système de migration automatique ajouté au démarrage du plugin
- ✅ Détection de version et création automatique des tables manquantes
- ✅ Vérification à chaque chargement via `check_database_version()`
- ✅ Création automatique du programme de fidélité par défaut
- ✅ Création automatique de l'entrepôt principal par défaut

**Fonctionnement:**
1. Le plugin vérifie `colis224_version` en base de données
2. Compare avec `COLIS224_VERSION` (2.3.1)
3. Si différente, exécute `Colis224_Database::create_tables()`
4. Crée les données par défaut pour v2.3.0+
5. Met à jour la version stockée

**Impact:**
- ✅ Plus besoin de désactiver/réactiver le plugin après mise à jour
- ✅ Migration transparente pour l'utilisateur
- ✅ Création automatique de toutes les tables manquantes
- ✅ Compatible avec toutes les versions futures

**Tables Créées Automatiquement:**
- `wp_colis224_loyalty_programs`
- `wp_colis224_loyalty_members`
- `wp_colis224_loyalty_transactions`
- `wp_colis224_loyalty_rewards`
- `wp_colis224_loyalty_redemptions`
- `wp_colis224_warehouses`
- `wp_colis224_warehouse_inventory`
- `wp_colis224_warehouse_transfers`

---

## [2.3.0] - 2025-01-04

### 🎉 NOUVELLE VERSION MAJEURE - FIDÉLITÉ + MULTI-ENTREPÔTS

Cette version apporte 2 modules stratégiques pour la rétention client et l'optimisation logistique.

#### 💎 MODULE 11: PROGRAMME DE FIDÉLITÉ

**Système de Points Complet**
- Attribution automatique de points par colis envoyé (configurable)
- Points basés sur montant dépensé (ex: 1 point / 100 GNF)
- Historique complet des transactions de points
- Ajout/retrait manuel de points avec description
- Types de transactions: earn, redeem, expire, adjust, bonus

**Système de Tiers (4 niveaux)**
- 🥉 **Bronze** : 0-1,999 points (tier par défaut)
- 🥈 **Silver** : 2,000-4,999 points (priorité traitement)
- 🥇 **Gold** : 5,000-9,999 points (réductions exclusives)
- 💎 **Platinum** : 10,000+ points (service VIP)
- Progression automatique basée sur total accumulé

**Catalogue de Récompenses**
- **5 types disponibles** :
  - Réduction pourcentage (ex: 10% sur prochain envoi)
  - Réduction fixe (ex: 50,000 GNF de remise)
  - Livraison gratuite
  - Remboursement cash
  - Cadeaux physiques
- Gestion du stock pour récompenses limitées
- Période de validité configurable (en jours)
- Statuts d'échange: pending, used, expired, cancelled

**Interface Administration Complète**
- **Onglet Membres** :
  - Liste tous les membres avec points et tiers
  - Ajout/retrait manuel de points
  - Recherche et filtres
  - Vue du total, disponible, échangé
- **Onglet Récompenses** :
  - Création de nouvelles récompenses
  - Gestion stock et validité
  - Activation/désactivation
  - Catalogue complet
- **Onglet Paramètres** :
  - Configuration règles de points (par GNF, par colis)
  - Seuil minimum
  - Description du programme
  - Paliers des tiers
- **Onglet Statistiques** :
  - Total membres inscrits
  - Points distribués vs échangés
  - Répartition par tier (graphiques)
  - Métriques de performance

**Base de Données (5 tables)**
- `colis224_loyalty_programs` - Programmes de fidélité
- `colis224_loyalty_members` - Membres inscrits (9 colonnes)
- `colis224_loyalty_transactions` - Historique points (7 colonnes)
- `colis224_loyalty_rewards` - Catalogue récompenses (10 colonnes)
- `colis224_loyalty_redemptions` - Échanges effectués (10 colonnes)

**Intégration Automatique**
- Hooks `colis224_parcel_created` et `colis224_payment_received`
- Auto-inscription des nouveaux clients
- Calcul automatique des points
- Mise à jour tier en temps réel
- Programme par défaut créé lors de l'activation

#### 🏢 MODULE 12: GESTION MULTI-ENTREPÔTS

**Gestion Complète des Entrepôts**
- Création d'entrepôts multiples avec codes uniques
- Informations détaillées :
  - Nom, code (ex: WH-CONAKRY, WH-KANKAN)
  - Adresse complète, ville, pays
  - Responsable (nom + téléphone)
  - Capacité (nombre de colis max)
  - Statut actif/inactif
- Modification et désactivation
- Statistiques par entrepôt

**Inventaire en Temps Réel**
- Suivi de tous les colis par entrepôt
- **4 statuts d'inventaire** :
  - `in_stock` : Colis présent dans l'entrepôt
  - `in_transit` : En cours de transfert
  - `delivered` : Livré au destinataire final
  - `transferred` : Transféré vers autre entrepôt
- Dates d'entrée et sortie automatiques
- Notes et commentaires par mouvement
- Historique complet des mouvements

**Système de Transferts Inter-Entrepôts**
- Création de transferts entre entrepôts
- Workflow complet :
  1. **Pending** : Transfert créé, colis marqué en transit
  2. **In Transit** : En cours d'acheminement
  3. **Completed** : Arrivé à destination, inventaire mis à jour
  4. **Cancelled** : Annulé, colis retourne en stock
- Initiateur du transfert tracé (user_id)
- Dates de transfert et d'arrivée
- Notes et raisons du transfert
- Confirmation manuelle d'arrivée

**Interface Administration**
- **Onglet Entrepôts** :
  - Liste complète avec statistiques
  - Formulaire création/modification
  - Code unique requis
  - Vue capacité vs stock actuel
- **Onglet Inventaire** :
  - Filtre par entrepôt
  - Liste des colis en stock
  - Ajout manuel de colis
  - Vue détaillée (tracking, destinataire, poids)
- **Onglet Transferts** :
  - Création de transferts
  - Filtre par statut
  - Actions: Confirmer / Annuler
  - Historique complet

**Fonctionnalités Intelligentes**
- Localisation rapide d'un colis dans le réseau
- Ajout automatique à l'entrepôt par défaut lors création
- Vérifications de cohérence (colis déjà dans un entrepôt)
- Mise à jour automatique lors de confirmation transfert
- Statistiques en temps réel par entrepôt

**Base de Données (3 tables)**
- `colis224_warehouses` - Entrepôts/dépôts (11 colonnes)
- `colis224_warehouse_inventory` - Inventaire (9 colonnes)
- `colis224_warehouse_transfers` - Transferts (11 colonnes)

**Intégration Automatique**
- Hook `colis224_parcel_created` pour ajout auto
- Entrepôt principal créé lors de l'activation
- Code: WH-MAIN, Capacité: 1000 colis

### 🐛 Corrections de Bugs
- ✅ Fix: Espacement correct des icônes dashicons dans tous les contextes
  - Ajout règles CSS pour labels, formulaires, badges
  - Margin-right: 6px sur tous les dashicons précédant du texte
  - Vertical-align: middle pour alignement parfait
- ✅ Fix: Design du QR code amélioré
  - Container en min-height au lieu de height fixe
  - Footer en position relative au lieu d'absolute
  - Bordures épaissies (3px au lieu de 2px)
  - Texte footer plus lisible (12px au lieu de 10px)
  - Cadre couvre tout le contenu correctement
- ✅ Fix: Configuration SMS maintenant visible
  - Onglet "SMS" ajouté dans paramètres
  - Interface complète pour Orange, Africa's Talking, Twilio
- ✅ Fix: Configuration paiements accessible
  - Onglet "Paiements" ajouté dans paramètres
  - Configuration Orange Money, MTN Money, Moov Money

### 🔧 Améliorations
- Ajout des onglets "SMS" et "Paiements" dans les paramètres
- Configuration complète des fournisseurs SMS:
  - **Orange SMS** : Client ID, Client Secret, Sender Name
  - **Africa's Talking** : Username, API Key, Sender ID
  - **Twilio** : Account SID, Auth Token, From Number
  - **Custom API** : Webhook personnalisable
- Configuration des moyens de paiement Mobile Money:
  - **Orange Money** : Merchant ID, API Key, Secret, Environment
  - **MTN Money** : API User, API Key, Subscription Key, Environment
  - **Moov Money** : Merchant ID, API Key, Environment
  - Options sandbox/production pour chaque fournisseur
- Mise à jour complète de `save_settings()` pour tous les nouveaux champs
- Gestion correcte des checkboxes (enabled/disabled)
- Auto-création programme fidélité lors activation
- Auto-création entrepôt principal lors activation

### 📊 Base de Données
- **+8 nouvelles tables** (5 fidélité + 3 entrepôts)
- **Total: 25 tables** dans le système complet
- **+2,000 lignes** de code PHP ajoutées
- **4 nouveaux fichiers** de classes:
  - `includes/class-colis224-loyalty.php` (~450 lignes)
  - `includes/class-colis224-warehouses.php` (~500 lignes)
  - `admin/class-colis224-fidelite.php` (~600 lignes)
  - `admin/class-colis224-entrepots.php` (~650 lignes)

### 🎯 Architecture Technique
- **Hooks WordPress** :
  - `colis224_parcel_created` - Attribution points + ajout entrepôt
  - `colis224_payment_received` - Attribution points sur paiement
- **AJAX** : Aucun AJAX requis (interfaces simples et performantes)
- **Sécurité** :
  - Nonces WordPress sur tous les formulaires
  - Sanitization complète des données
  - Prepared statements SQL
  - Vérification des permissions
- **Performance** :
  - Index sur toutes les clés étrangères
  - Requêtes optimisées avec JOINs
  - Cache potentiel pour stats (à implémenter)

### 📈 Impact Business
- **Rétention client** : Programme fidélité augmente fidélisation
- **Optimisation logistique** : Multi-entrepôts réduit délais livraison
- **Scalabilité** : Architecture prête pour croissance réseau
- **Analyse** : Métriques fidélité + inventaire pour décisions stratégiques

---

## [2.2.0] - 2025-01-03

### 🎉 VERSION MAJEURE - 5 NOUVELLES FONCTIONNALITÉS BUSINESS

Cette version apporte 5 modules professionnels majeurs pour transformer Colis224 en solution logistique complète de niveau entreprise.

#### 💰 1. PAIEMENTS MOBILE MONEY
- **Orange Money** : Intégration complète (Guinée, Sénégal, Côte d'Ivoire)
- **MTN Money** : Support MTN MoMo API (Guinée, Côte d'Ivoire)
- **Moov Money** : Intégration Moov Africa (Côte d'Ivoire)
- **Webhooks** : Confirmation automatique des paiements
- **Table dédiée** : `colis224_mobile_money_transactions` pour historique complet
- **Statuts en temps réel** : Pending, Completed, Failed, Cancelled
- **Sandbox & Production** : Support environnements de test et production
- **Formatage intelligent** : Numéros de téléphone formatés automatiquement
- **Sécurité** : Tokens cachés, authentification OAuth, nonces WordPress

**Providers supportés :**
- Orange Money Web Payment API
- MTN MoMo Collections API
- Moov Money Payment API
- Architecture extensible pour futurs providers (Wave, PayPal, Stripe)

#### 🔌 2. API REST COMPLÈTE
- **Endpoints complets** : Parcels, Clients, Statistics, Authentication
- **Authentification JWT** : Tokens sécurisés avec expiration 7 jours
- **Pagination** : Support pagination pour grandes listes
- **Filtres avancés** : Par statut, date, client, etc.
- **Tracking public** : Endpoint `/track/:number` sans authentification
- **Rate limiting ready** : Architecture prête pour limitation de requêtes
- **Documentation Swagger** : Prête pour génération automatique
- **CORS support** : Headers configurables pour apps mobiles

**Endpoints disponibles :**
```
GET    /colis224/v1/parcels              - Liste des colis
POST   /colis224/v1/parcels              - Créer un colis
GET    /colis224/v1/parcels/:id          - Détails d'un colis
PUT    /colis224/v1/parcels/:id          - Mettre à jour
DELETE /colis224/v1/parcels/:id          - Supprimer
GET    /colis224/v1/track/:tracking      - Suivi public
GET    /colis224/v1/clients              - Liste clients
POST   /colis224/v1/clients              - Créer client
GET    /colis224/v1/stats/dashboard      - Statistiques
POST   /colis224/v1/auth/login           - Authentification
```

**Base pour app mobile** : Architecture prête pour React Native, Flutter, etc.

#### 🔐 3. SYSTÈME DE PERMISSIONS AVANCÉ
- **4 Rôles personnalisés** :
  - `colis224_manager` : Accès complet gestion
  - `colis224_agent` : Création colis, gestion clients
  - `colis224_driver` : Vue colis assignés, mise à jour livraison
  - `colis224_accountant` : Rapports financiers, comptabilité
- **Capabilities granulaires** :
  - `colis224_manage_all` - Gestion complète
  - `colis224_view_reports` - Voir rapports
  - `colis224_manage_accounting` - Comptabilité
  - `colis224_create_parcel` - Créer colis
  - `colis224_view_parcels` - Voir colis
  - `colis224_manage_clients` - Gérer clients
  - `colis224_view_assigned_parcels` - Colis assignés (livreurs)
  - `colis224_update_delivery_status` - MAJ statut livraison
- **Protection pages** : Vérification automatique permissions par page
- **Helpers pratiques** : `can_create_parcel()`, `can_view_all_parcels()`, etc.
- **Intégration WordPress** : Utilise le système natif de rôles/capabilities

#### 📊 4. EXPORT AVANCÉ (CSV, EXCEL, PDF)
- **Export CSV** :
  - Encodage UTF-8 avec BOM pour Excel
  - Séparateur point-virgule pour compatibilité française
  - Filtres par statut et dates
  - Toutes les colonnes importantes
- **Export Excel** :
  - Format HTML compatible Microsoft Excel
  - Styles professionnels (couleurs, bordures)
  - Alternance couleurs lignes pour lisibilité
  - Limite 1000 lignes pour performance
- **Export PDF Rapports** :
  - Rapports comptables mensuels
  - Rapports statistiques
  - Design professionnel avec logo
  - Calculs automatiques (revenus, dépenses, bénéfices)
  - Prêt pour intégration TCPDF/Dompdf
- **AJAX Ready** : Tous les exports via AJAX avec nonces

**Formats supportés :**
- CSV (compatible Excel français)
- XLS (format HTML)
- PDF (via impression ou lib externe)

#### 📱 5. SMS API PROFESSIONNELLE
- **4 Providers SMS** :
  - **Twilio** : Solution internationale premium
  - **Africa's Talking** : Spécialisé Afrique (meilleurs tarifs)
  - **Orange SMS API** : Authentification OAuth, intégration directe
  - **API Personnalisée** : Support toute API REST (GET/POST)
- **Table logs** : `colis224_sms_logs` pour historique complet
- **Notifications automatiques** : Envoi auto selon statut colis
- **Formatage numéros** : +224 Guinée par défaut, support international
- **Messages personnalisés** :
  - Expédié : "Votre colis a été expédié..."
  - En transit : "Votre colis est en route..."
  - Livré : "Bonne nouvelle ! Votre colis est livré..."
- **Envoi en masse** : Support bulk SMS
- **SMS de test** : Interface test avant production
- **Cache tokens** : OAuth tokens cachés 1h pour performance

**Providers détails :**
- Twilio : SMS worldwide, fiable, cher
- Africa's Talking : +40 pays africains, tarifs bas
- Orange API : Intégration directe opérateurs
- Custom : Votre propre fournisseur local

### 🔧 AMÉLIORATIONS TECHNIQUES

#### Base de Données
- **2 nouvelles tables** :
  - `colis224_mobile_money_transactions` (10 colonnes)
  - `colis224_sms_logs` (8 colonnes)
- **17 tables au total** maintenant
- **Migration automatique** via dbDelta lors de l'activation

#### Architecture
- **5 nouveaux fichiers** includes :
  - `class-colis224-mobile-money.php` (600+ lignes)
  - `class-colis224-rest-api.php` (500+ lignes)
  - `class-colis224-permissions.php` (150+ lignes)
  - `class-colis224-export.php` (400+ lignes)
  - `class-colis224-sms-api.php` (500+ lignes)
- **Code modulaire** : Chaque fonctionnalité isolée
- **POO strict** : Classes, namespaces, single responsibility

#### Sécurité
- **Nonces WordPress** partout
- **Sanitization** complète des inputs
- **Prepared statements** pour toutes requêtes SQL
- **JWT tokens** pour API
- **OAuth** pour Mobile Money et SMS
- **Permissions checks** sur chaque endpoint

### 📝 DOCUMENTATION

#### Utilisation Mobile Money
```php
$mobile_money = new Colis224_Mobile_Money();
$result = $mobile_money->initiate_payment(
    'orange_money',  // Provider
    50000,           // Montant en GNF
    '+224621234567', // Téléphone
    'PA1234',        // Référence colis
    'Paiement colis' // Description
);
```

#### Utilisation API REST
```bash
# Authentification
curl -X POST https://votresite.com/wp-json/colis224/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"pass"}'

# Créer un colis
curl -X POST https://votresite.com/wp-json/colis224/v1/parcels \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"tracking_number":"PA5678","recipient_name":"Mamadou",...}'

# Tracking public
curl https://votresite.com/wp-json/colis224/v1/track/PA1234
```

#### Utilisation SMS API
```php
$sms = new Colis224_SMS_API();
$result = $sms->send_sms(
    '+224621234567',
    'Votre colis PA1234 est en route !'
);

// Notification automatique
Colis224_SMS_API::send_parcel_status_notification($parcel_id, 'Livré');
```

### 🎯 NOTES DE MIGRATION

**IMPORTANT** : Version 2.2.0 nécessite réactivation du plugin pour créer les nouvelles tables.

**Étapes :**
1. Sauvegarder la base de données
2. Désactiver le plugin
3. Activer le plugin
4. Vérifier que les 2 nouvelles tables existent
5. Configurer vos clés API (Mobile Money, SMS) dans Paramètres

**Compatibilité :**
- WordPress 6.0+
- PHP 8.0+
- MySQL 5.7+

### ⚙️ CONFIGURATION REQUISE

#### Pour Mobile Money :
- Obtenir clés API auprès d'Orange/MTN/Moov
- Configurer webhooks pour confirmations auto
- Tester en sandbox avant production

#### Pour API REST :
- Activer permaliens WordPress
- Configurer CORS si app mobile externe
- Générer tokens JWT pour utilisateurs

#### Pour SMS :
- Choisir un provider (Africa's Talking recommandé pour l'Afrique)
- Obtenir clés API
- Créditer compte SMS
- Tester avec SMS de test

### 🚀 PROCHAINES ÉTAPES

**v2.3.0 Planifiée :**
- Intégration PayPal/Stripe
- Multi-entrepôts
- Programme de fidélité clients
- Système de tickets support

**v3.0.0 Vision :**
- Application mobile native (React Native)
- Intelligence Artificielle (prédictions délais)
- Intégrations DHL/FedEx/UPS
- Multi-langues (FR, EN, AR)

---

## [2.1.0] - 2025-01-03

### ✨ NOUVELLES FONCTIONNALITÉS

#### 🔍 Système d'Autocomplétion Intelligent
- **Autocomplete pour les clients** : Recherche rapide par nom ou numéro de téléphone dans le formulaire de création de colis
- **Autocomplete pour les destinataires** : Suggestions basées sur l'historique des livraisons avec pré-remplissage automatique du téléphone et de l'adresse
- **Autocomplete pour les expéditeurs** : Suggestions basées sur l'historique des expéditions
- **Intégration jQuery UI** : Interface utilisateur fluide et professionnelle
- **Recherche en temps réel** via AJAX avec minimum 2 caractères
- **Classe dédiée** : `class-colis224-autocomplete.php` pour gérer toutes les requêtes

#### ✏️ Édition des Livreurs et Agents
- **Modification complète des livreurs** : Nom, téléphone, email, zone, type de commission, valeur de commission, statut actif/inactif
- **Modification complète des agents** : Nom, téléphone, email, rôle/poste, salaire, statut actif/inactif
- **Interface unifiée** : Le même formulaire sert pour l'ajout et la modification
- **Suppression sécurisée** : Confirmation avant suppression avec bouton dédié
- **Nonces WordPress** : Protection CSRF pour toutes les opérations

#### 📞 Support de Numéros de Téléphone Multiples
- **Champ supplémentaire** pour les numéros additionnels (partenaires, livreurs, agents)
- **Format flexible** : Numéros séparés par des virgules
- **Stockage texte** : Champ `additional_phones` dans la base de données
- **Migration automatique** : Les nouvelles colonnes sont ajoutées via `dbDelta` lors de l'activation
- **Affichage en liste** : Indication claire du téléphone principal et des numéros supplémentaires

#### 📊 Statistiques Avancées du Dashboard
- **Colis par livreur** : Affichage du nombre de colis actuellement assignés à chaque livreur (non livrés)
- **Colis par agent** : Préparation pour le tracking des colis enregistrés par agent (nécessite ajout futur d'un champ `created_by`)
- **Colis par partenaire** : Nombre de colis confiés à chaque partenaire (statut non livré)
- **Interface en 3 colonnes** : Présentation claire et organisée des statistiques
- **Limitation intelligente** : Top 10 pour chaque catégorie
- **Tri par nombre** : Les plus actifs en premier

### 🐛 CORRECTIONS DE BUGS

#### Shortcode de Suivi
- **Fix : Shortcode inactif** : Déplacement de l'enregistrement des shortcodes vers le hook `init` pour garantir leur fonctionnement
- **Message d'erreur corrigé** : Plus de message "[colis224_tracking] (hotfix) — shortcode inactif"

#### Boutons d'Actions
- **Section "Actions Rapides"** ajoutée dans les détails du colis
- **Boutons visibles** : Génération de facture et impression d'étiquette clairement accessibles
- **Refactorisation QR Code** : Séparation en deux fonctions (bouton simple et section complète)

### 🔧 AMÉLIORATIONS TECHNIQUES

#### Base de Données
- **3 nouvelles colonnes** : `additional_phones` pour les tables `colis224_partners`, `colis224_team_members`, `colis224_drivers`
- **Migration automatique** : Utilisation de `dbDelta` pour ajouter les colonnes sans perte de données

#### JavaScript & UX
- **Pré-remplissage automatique** : Lors de la sélection d'un destinataire, tous ses champs sont remplis automatiquement
- **Messages d'aide** : Instructions claires pour chaque champ avec autocomplete
- **Performance optimisée** : Limitation à 20 résultats par recherche

#### Code Quality
- **Nonces uniques** : `colis224_autocomplete` pour toutes les requêtes AJAX d'autocomplete
- **Sanitization complète** : Tous les inputs sont nettoyés avant insertion en base
- **Requêtes préparées** : 100% des requêtes SQL utilisent `$wpdb->prepare()`

### 📝 FICHIERS AJOUTÉS/MODIFIÉS

#### Nouveaux Fichiers
```
includes/
└── class-colis224-autocomplete.php  (Système d'autocomplétion)
```

#### Fichiers Modifiés
```
colis224-logistics-manager.php       (v2.1.0 + chargement autocomplete)
includes/
├── class-colis224-admin.php         (Enqueue scripts autocomplete)
├── class-colis224-database.php      (Colonnes additional_phones)
└── class-colis224-frontend-portal.php (Fix shortcode registration)

admin/
├── class-colis224-colis.php         (Autocomplete + Actions Rapides)
├── class-colis224-equipe.php        (Édition livreurs/agents + multiples phones)
└── class-colis224-dashboard.php     (Statistiques avancées)

assets/css/
└── admin-style.css                  (Classe .colis224-third-width)
```

### 🎯 NOTES DE MIGRATION

#### Activation du Plugin
Les utilisateurs existants doivent **réactiver le plugin** pour que les nouvelles colonnes de base de données soient créées :
1. Aller dans **Extensions**
2. **Désactiver** Colis224 Logistics Manager
3. **Activer** à nouveau

OU exécuter manuellement :
```php
Colis224_Database::create_tables();
```

#### Tracking par Agent
La statistique "Colis par agent" est préparée mais nécessiterait l'ajout d'un champ `created_by` dans la table `colis224_parcels` pour être pleinement fonctionnelle. Cette fonctionnalité sera améliorée dans la v2.2.0.

### ⚙️ CONFIGURATION

#### Shortcodes Corrigés
Les shortcodes fonctionnent maintenant correctement :
- `[colis224_tracking]` - Suivi public de colis ✅
- `[colis224_client_portal]` - Espace client sécurisé ✅

---

## [2.0.0] - 2025-01-02

### 🎉 AJOUTS MAJEURS - Version Ultra-Puissante

#### ✨ Portail Client Frontend
- **Page de suivi public** avec shortcode `[colis224_tracking]`
- **Espace client sécurisé** avec shortcode `[colis224_client_portal]`
- **Tableau de bord client** avec statistiques personnelles
- **Authentification par numéro de téléphone**
- **Timeline visuelle** de suivi de colis en temps réel
- **Interface moderne et responsive** pour mobile et desktop
- **Session sécurisée** pour les clients

#### 📧 Système de Notifications Automatiques
- **Notifications Email** avec templates HTML professionnels
- **Notifications SMS** (intégration API configurable)
- **Notifications WhatsApp Business** (via API)
- **Déclenchement automatique** lors des changements de statut
- **Système de logs** pour toutes les notifications envoyées
- **Messages personnalisés** selon le statut du colis
- **Templates responsive** pour tous les emails

#### 📱 QR Codes et Codes-Barres
- **Génération automatique de QR codes** pour chaque colis
- **Codes-barres Code128** pour lecture rapide
- **Étiquettes d'expédition complètes** imprimables
- **Format 10x15cm** optimisé pour étiquettes
- **Bouton d'impression** directe depuis l'admin
- **URL de suivi** intégrée dans le QR code
- **API externe** pour génération (pas de bibliothèque lourde)

#### 💰 Facturation PDF Automatique
- **Génération de factures professionnelles** en HTML/PDF
- **Design moderne** avec couleurs de marque
- **Numérotation automatique** des factures
- **Informations complètes** : client, expédition, paiement
- **Calcul automatique** des totaux et réductions
- **Statut de paiement** visuel avec codes couleur
- **Impression directe** ou téléchargement PDF
- **Logo et coordonnées** de l'entreprise

### 🔧 AMÉLIORATIONS

#### Interface Utilisateur
- **CSS frontend moderne** avec animations
- **JavaScript optimisé** pour AJAX
- **Messages d'erreur** améliorés
- **Loaders visuels** lors des opérations
- **Smooth scrolling** automatique

#### Sécurité
- **Sessions PHP sécurisées**
- **Nonces WordPress** pour toutes les actions AJAX
- **Sanitization complète** des données
- **Protection CSRF** renforcée

#### Performance
- **Chargement conditionnel** des assets
- **Optimisation des requêtes** SQL
- **Cache des QR codes** via API externe
- **Minification** recommandée en production

### 📝 FICHIERS AJOUTÉS

```
includes/
├── class-colis224-frontend-portal.php  (Portail client)
├── class-colis224-notifications.php    (Notifications)
├── class-colis224-qrcode.php           (QR codes)
└── class-colis224-invoice.php          (Facturation)

assets/css/
└── frontend-style.css                  (Styles frontend)

assets/js/
└── frontend-script.js                  (Scripts frontend)
```

### 🎯 UTILISATION

#### Shortcodes Disponibles

**Suivi de colis** (page publique) :
```
[colis224_tracking]
```

**Espace client** (page privée) :
```
[colis224_client_portal]
```

#### Hooks Disponibles

**Déclencher une notification manuelle** :
```php
do_action('colis224_parcel_status_changed', $parcel_id, $old_status, $new_status);
```

**Créer une notification** :
```php
Colis224_Notifications::send_custom_notification($email, $phone, $subject, $message);
```

**Générer un QR code** :
```php
$qrcode = new Colis224_QRCode();
$url = $qrcode->generate_qr_code($tracking_number, 300);
```

**Générer une facture** :
```php
$invoice = new Colis224_Invoice();
$html = $invoice->generate_invoice_html($parcel_id);
```

### ⚙️ CONFIGURATION

#### Notifications
Allez dans **Colis224 > Paramètres > Notifications** pour configurer :
- Email : Activé par défaut
- SMS : Nécessite URL et clé API
- WhatsApp : Nécessite WhatsApp Business API

#### Pages Recommandées
Créez ces pages WordPress avec les shortcodes :
1. **/suivi-colis** → `[colis224_tracking]`
2. **/mon-espace** → `[colis224_client_portal]`

---

## [1.0.0] - 2025-01-01

### 🎉 VERSION INITIALE

#### Modules de Base
- ✅ Gestion des Colis
- ✅ Gestion des Clients
- ✅ Gestion des Partenaires
- ✅ Gestion de l'Équipe (Agents + Livreurs)
- ✅ Comptabilité (Revenus + Dépenses)
- ✅ Rapports et Statistiques
- ✅ Service d'Achat
- ✅ Paramètres Généraux

#### Fonctionnalités
- Numérotation automatique des colis
- Multi-pays et multi-modes de transport
- Multi-devises (GNF, EUR, USD)
- Paiements partiels
- Calculs automatiques
- Interface admin moderne
- Tableaux de bord statistiques
- Export CSV
- 15 tables de base de données

---

## 🚀 Prochaines Versions Prévues

### v2.1.0 (À venir)
- API REST complète
- Application mobile (base)
- Intégrations paiement (Mobile Money, PayPal, Stripe)
- Système de permissions avancé

### v2.2.0 (À venir)
- Multi-entrepôts
- Programme de fidélité
- Système de tickets/support
- Optimisation des itinéraires avec cartes

### v3.0.0 (À venir)
- Gestion des véhicules
- Assurance colis
- Multi-langues
- Intégration DHL/FedEx/UPS
- Intelligence Artificielle (prédictions)

---

**Développé avec ❤️ pour Colis224**
