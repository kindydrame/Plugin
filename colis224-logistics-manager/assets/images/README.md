# Images pour le Calculateur Frontend Colis224

Ce dossier contient les images d'instructions pour le Shipping Mark et les adresses de livraison.

## Images requises

Vous devez placer les 4 images suivantes dans ce dossier:

### 1. `air-plane-address.jpg`
- **Description**: Image montrant l'adresse de livraison AVION pour la Chine (Guangzhou)
- **Contenu**: COLIS224 AIR PLANECARGO ADDRESS
- **Adresse affichée**:
  ```
  COLIS224 广东省广州市越秀区流花街环市西路202号美博运动城 10楼1020室
  +8618719472926 KINDY
  ```

### 2. `sea-cargo-address.jpg`
- **Description**: Image montrant l'adresse de livraison BATEAU pour la Chine (Foshan)
- **Contenu**: COLIS224 SEA CARGO ADDRESS
- **Adresse affichée**:
  ```
  COLIS224 佛山市南海区里水镇上沙路29号
  +8618719472926 KINDY
  ```

### 3. `air-cargo-mark.jpg`
- **Description**: Image montrant le Shipping Mark pour l'envoi AVION
- **Contenu**: Exemple de carton avec les mentions obligatoires:
  - COLIS224
  - Nom du client (kindy Drame)
  - Téléphone (+8618719472926)
  - Code PA (PA2926)
  - Drapeaux Chine 🇨🇳 et Guinée 🇬🇳

### 4. `sea-cargo-mark.jpg`
- **Description**: Image montrant le Shipping Mark pour l'envoi BATEAU
- **Contenu**: Exemple de carton avec les mentions obligatoires (similaire à air-cargo-mark)
  - COLIS224
  - Nom du client
  - Téléphone
  - Code PA
  - Icône bateau 🚢

## Instructions d'upload

1. Téléchargez les 4 images que vous avez reçues par email/WhatsApp
2. Renommez-les exactement comme indiqué ci-dessus
3. Placez-les dans ce dossier `colis224-logistics-manager/assets/images/`
4. Les images seront automatiquement affichées dans le calculateur

## Format recommandé

- **Format**: JPG ou PNG
- **Résolution**: 1200x800px minimum
- **Poids**: < 500KB par image pour un chargement rapide

## Utilisation dans le code

Ces images sont référencées dans:
- `includes/class-colis224-frontend-calculator.php` (ligne 120-125)
- `assets/js/frontend-calculator.js` (ligne 285-300)

Les chemins sont automatiquement générés via `COLIS224_PLUGIN_URL . 'assets/images/nom-fichier.jpg'`
