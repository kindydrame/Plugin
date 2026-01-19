# Changelog - Colis224 Logistics Manager

Toutes les modifications importantes de ce projet seront documentées dans ce fichier.

---

## [2.18.7] - 2026-01-19

### 🔧 CORRECTIONS CRITIQUES

#### **Fix: Session Persistence après Connexion Client**
- **Problème résolu**: Session ne persistait pas après connexion AJAX, redirection montrait formulaire au lieu du dashboard
- **Cause**: Cookies sans attribut `SameSite` explicite rejetés par navigateurs modernes
- **Solution implémentée**:
  - ✅ Ajout attribut `SameSite=Lax` à tous les cookies de session
  - ✅ Configuration cookies avec format array PHP 7.3+ (expires, path, domain, secure, httponly, samesite)
  - ✅ Configuration `ini_set()` pour session PHP avant `session_start()`
  - ✅ Application cohérente dans tous les emplacements: `login_client()`, `restore_session_from_cookie()`, `start_session()`, `client_portal_shortcode()`
- **Fichiers modifiés**:
  - `includes/class-colis224-client-auth.php` - Cookie backup avec SameSite
  - `includes/class-colis224-frontend-portal.php` - Session shortcode
  - `colis224-logistics-manager.php` - Session globale

#### **Améliorations Cookie Backup**
- Cookie `colis224_client_session` maintenant correctement défini avec:
  - Path: `COOKIEPATH` ou `/` par défaut
  - Domain: `COOKIE_DOMAIN` ou vide par défaut
  - Secure: Automatique selon HTTPS
  - HttpOnly: `true` pour sécurité
  - SameSite: `Lax` pour compatibilité redirections

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
