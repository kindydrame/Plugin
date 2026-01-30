# Images pour le Calculateur Frontend Colis224

Ce dossier contient les images d'instructions pour le Shipping Mark et les adresses de livraison.

## Images requises (5 fichiers)

Vous devez placer les 5 images suivantes dans ce dossier:

### 1. `air-plane-address.jpg`
- **Description**: Image montrant l'adresse de livraison AVION pour la Chine (Guangzhou)
- **Contenu**: COLIS224 AIR PLANE - Adresse Guangzhou
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

### 3. `wechat-qr.jpg`
- **Description**: QR Code WeChat pour paiement/contact
- **Contenu**: QR Code WeChat de Kindy Drame (Guangzhou)

### 4. `air-cargo-mark.jpg`
- **Description**: Image montrant le Shipping Mark pour l'envoi AVION
- **Contenu**: Instructions SHIPPING MARK avec exemple de carton
  - COLIS224
  - Nom du client
  - Téléphone +8618719472926
  - Code PA
  - Instructions en français et chinois

### 5. `sea-cargo-mark.jpg`
- **Description**: Image montrant le Shipping Mark pour l'envoi BATEAU
- **Contenu**: Instructions SHIPPING MARK similaire à air-cargo-mark
  - Adresse Foshan au lieu de Guangzhou

## Instructions d'installation

1. Téléchargez les 5 images fournies
2. Renommez-les exactement comme indiqué ci-dessus:
   - `air-plane-address.jpg`
   - `sea-cargo-address.jpg`
   - `wechat-qr.jpg`
   - `air-cargo-mark.jpg`
   - `sea-cargo-mark.jpg`
3. Placez-les dans ce dossier: `colis224-logistics-manager/assets/images/`
4. Les images seront automatiquement détectées et utilisées

## Format recommandé

- **Format**: JPG ou PNG (extension .jpg ou .png)
- **Résolution**: 800x1200px minimum pour une bonne lisibilité
- **Poids**: < 500KB par image pour un chargement rapide

## Utilisation dans le code

Ces images sont référencées dans:
- `includes/class-colis224-frontend-calculator.php` - Fonction `get_calculator_images()`
- `assets/js/frontend-calculator.js` - Via l'objet `colis224Frontend.images`

Les chemins sont générés automatiquement via `COLIS224_PLUGIN_URL . 'assets/images/'`

## Fallback

Si les images ne sont pas présentes dans ce dossier:
1. Le système vérifie d'abord les images locales
2. Sinon, utilise les URLs configurées dans les options WordPress
3. Sinon, affiche un message informatif avec les instructions textuelles

## Configuration alternative (Admin WordPress)

Les URLs des images peuvent aussi être configurées dans:
**Colis224 > Paramètres > Images du calculateur**

Cela permet d'utiliser des images hébergées ailleurs (CDN, médiathèque WordPress, etc.)
