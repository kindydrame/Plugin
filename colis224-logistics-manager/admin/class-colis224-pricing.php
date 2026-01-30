<?php
/**
 * Colis224 Pricing Admin Page
 *
 * Page admin pour les grilles tarifaires et calculateurs
 *
 * @package Colis224_Logistics_Manager
 * @since 2.19.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Pricing {

    /**
     * Afficher la page principale
     */
    public static function render_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'china_guinea';

        ?>
        <div class="wrap colis224-pricing-page">
            <h1 class="colis224-page-title">
                <span class="dashicons dashicons-money-alt"></span>
                📋 Grilles Tarifaires & Calculateurs
            </h1>

            <p class="colis224-page-description">
                Consultez les tarifs et calculez rapidement le prix d'un envoi pour vos clients
            </p>

            <!-- Onglets de navigation -->
            <nav class="nav-tab-wrapper colis224-tab-wrapper">
                <a href="?page=colis224-pricing&tab=china_guinea"
                   class="nav-tab <?php echo $active_tab === 'china_guinea' ? 'nav-tab-active' : ''; ?>">
                    ✈️ Chine → Guinée
                </a>
                <a href="?page=colis224-pricing&tab=cbm"
                   class="nav-tab <?php echo $active_tab === 'cbm' ? 'nav-tab-active' : ''; ?>">
                    🚢 Maritime CBM
                </a>
                <a href="?page=colis224-pricing&tab=container"
                   class="nav-tab <?php echo $active_tab === 'container' ? 'nav-tab-active' : ''; ?>">
                    🚢 Bateau France → Conakry
                </a>
                <a href="?page=colis224-pricing&tab=guinea_world"
                   class="nav-tab <?php echo $active_tab === 'guinea_world' ? 'nav-tab-active' : ''; ?>">
                    🌍 Guinée ↔ Monde
                </a>
                <?php if (current_user_can('manage_options')): ?>
                <a href="?page=colis224-pricing&tab=manage"
                   class="nav-tab <?php echo $active_tab === 'manage' ? 'nav-tab-active' : ''; ?>">
                    ⚙️ Gestion
                </a>
                <?php endif; ?>
            </nav>

            <!-- Contenu des onglets -->
            <div class="colis224-tab-content">
                <?php
                switch ($active_tab) {
                    case 'china_guinea':
                        self::render_china_guinea_tab();
                        break;
                    case 'cbm':
                        self::render_cbm_tab();
                        break;
                    case 'container':
                        self::render_container_tab();
                        break;
                    case 'guinea_world':
                        self::render_guinea_world_tab();
                        break;
                    case 'manage':
                        if (current_user_can('manage_options')) {
                            self::render_manage_tab();
                        }
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Onglet Chine → Guinée
     */
    private static function render_china_guinea_tab() {
        $rates = Colis224_Pricing_Data::get_china_guinea_rates();
        ?>
        <div class="colis224-pricing-container">
            <div class="colis224-pricing-grid">
                <!-- Calculateur -->
                <div class="colis224-calculator-card">
                    <div class="calculator-header">
                        <h2>🧮 Calculateur de Prix</h2>
                        <p>Estimez rapidement le coût d'un envoi de Chine vers la Guinée</p>
                    </div>

                    <div class="calculator-body">
                        <div class="form-group">
                            <label for="china_category">
                                <span class="dashicons dashicons-category"></span>
                                Catégorie d'article
                            </label>
                            <select id="china_category" class="form-control">
                                <option value="">-- Sélectionnez une catégorie --</option>
                                <?php foreach ($rates as $key => $rate): ?>
                                    <option value="<?php echo esc_attr($key); ?>"
                                            data-type="<?php echo esc_attr($rate['type']); ?>"
                                            data-price="<?php echo esc_attr($rate['price']); ?>"
                                            data-currency="<?php echo esc_attr($rate['currency']); ?>"
                                            data-unit="<?php echo esc_attr($rate['unit']); ?>">
                                        <?php echo esc_html($rate['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="china_quantity">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <span id="china_quantity_label">Quantité</span>
                            </label>
                            <input type="number" id="china_quantity" class="form-control"
                                   placeholder="Ex: 2.5" min="0.1" step="0.1">
                            <small class="form-hint" id="china_quantity_hint">Entrez le poids en kg ou le nombre de pièces</small>
                        </div>

                        <button type="button" class="btn btn-primary btn-calculate" onclick="calculateChinaGuinea()">
                            <span class="dashicons dashicons-calculator"></span>
                            Calculer le prix
                        </button>

                        <!-- Résultat -->
                        <div id="china_result" class="calculator-result" style="display:none;"></div>
                    </div>
                </div>

                <!-- Grille tarifaire -->
                <div class="colis224-rates-card">
                    <div class="rates-header">
                        <h2>📊 Grille Tarifaire de Référence</h2>
                        <p>Tarifs applicables pour les envois aériens Chine → Guinée</p>
                    </div>

                    <div class="rates-body">
                        <table class="colis224-table">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th style="text-align:center;">Facturation</th>
                                    <th style="text-align:right;">Prix Unitaire</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rates as $key => $rate): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($rate['label']); ?></strong>
                                        </td>
                                        <td style="text-align:center;">
                                            <span class="badge badge-info">
                                                <?php echo $rate['type'] === 'per_kg' ? '📦 Par kg' : '📱 Par pièce'; ?>
                                            </span>
                                        </td>
                                        <td style="text-align:right;">
                                            <strong class="price-value">
                                                <?php echo number_format($rate['price'], 0, ',', ' '); ?> FG
                                            </strong>
                                            <small class="text-muted">/<?php echo esc_html($rate['unit']); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Onglet CBM Maritime
     */
    private static function render_cbm_tab() {
        $cbm_rate = Colis224_Pricing_Data::get_cbm_rate();
        ?>
        <div class="colis224-pricing-container">
            <div class="colis224-pricing-grid">
                <!-- Calculateur CBM -->
                <div class="colis224-calculator-card">
                    <div class="calculator-header calculator-header-maritime">
                        <h2>🧮 Calculateur CBM</h2>
                        <p>Calculez le volume (CBM) et le prix de votre expédition maritime</p>
                    </div>

                    <div class="calculator-body">
                        <div class="form-group">
                            <label for="cbm_unit">
                                <span class="dashicons dashicons-admin-settings"></span>
                                Unité de mesure
                            </label>
                            <select id="cbm_unit" class="form-control">
                                <option value="cm" selected>Centimètres (cm)</option>
                                <option value="m">Mètres (m)</option>
                            </select>
                        </div>

                        <div class="dimensions-grid">
                            <div class="form-group">
                                <label for="cbm_length">
                                    <span class="dashicons dashicons-leftright"></span>
                                    Longueur
                                </label>
                                <input type="number" id="cbm_length" class="form-control"
                                       placeholder="Ex: 50" min="0.1" step="0.1">
                                <small class="dimension-unit">cm</small>
                            </div>

                            <div class="form-group">
                                <label for="cbm_width">
                                    <span class="dashicons dashicons-leftright"></span>
                                    Largeur
                                </label>
                                <input type="number" id="cbm_width" class="form-control"
                                       placeholder="Ex: 40" min="0.1" step="0.1">
                                <small class="dimension-unit">cm</small>
                            </div>

                            <div class="form-group">
                                <label for="cbm_height">
                                    <span class="dashicons dashicons-sort"></span>
                                    Hauteur
                                </label>
                                <input type="number" id="cbm_height" class="form-control"
                                       placeholder="Ex: 60" min="0.1" step="0.1">
                                <small class="dimension-unit">cm</small>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary btn-calculate" onclick="calculateCBM()">
                            <span class="dashicons dashicons-calculator"></span>
                            Calculer le CBM et le prix
                        </button>

                        <!-- Résultat -->
                        <div id="cbm_result" class="calculator-result" style="display:none;"></div>
                    </div>
                </div>

                <!-- Info CBM -->
                <div class="colis224-rates-card">
                    <div class="rates-header rates-header-maritime">
                        <h2>📦 Informations CBM</h2>
                        <p>Calcul du volume pour expéditions maritimes</p>
                    </div>

                    <div class="rates-body">
                        <div class="info-box info-box-primary">
                            <h3>💰 Tarif Maritime</h3>
                            <div class="price-display">
                                <span class="price-amount"><?php echo number_format($cbm_rate['price_per_cbm'], 0, ',', ' '); ?></span>
                                <span class="price-currency">FG</span>
                                <span class="price-unit">par CBM</span>
                            </div>
                        </div>

                        <div class="info-box info-box-info">
                            <h3>📐 Comment calculer le CBM ?</h3>
                            <p><strong>Formule:</strong></p>
                            <code>CBM = Longueur × Largeur × Hauteur (en mètres)</code>

                            <p style="margin-top: 15px;"><strong>Exemple:</strong></p>
                            <ul style="list-style: none; padding-left: 0;">
                                <li>📏 Longueur: 50 cm = 0.50 m</li>
                                <li>📏 Largeur: 40 cm = 0.40 m</li>
                                <li>📏 Hauteur: 60 cm = 0.60 m</li>
                            </ul>
                            <p><strong>CBM = 0.50 × 0.40 × 0.60 = 0.120 m³</strong></p>
                            <p><strong>Prix = 0.120 × 4 700 000 = 564 000 FG</strong></p>
                        </div>

                        <div class="info-box info-box-warning">
                            <h3>⚠️ Note importante</h3>
                            <p>Les prix affichés sont <strong>hors frais de douane</strong>. Les frais de dédouanement seront ajoutés selon la nature et la valeur de la marchandise.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Onglet Guinée ↔ Monde
     */
    private static function render_guinea_world_tab() {
        $rates = Colis224_Pricing_Data::get_guinea_world_rates();
        $parcel_types = Colis224_Pricing_Data::get_parcel_type_labels();
        $delivery_modes = Colis224_Pricing_Data::get_delivery_mode_labels();
        ?>
        <div class="colis224-pricing-container">
            <div class="colis224-pricing-grid">
                <!-- Calculateur -->
                <div class="colis224-calculator-card">
                    <div class="calculator-header calculator-header-world">
                        <h2>🧮 Calculateur International</h2>
                        <p>Estimez le coût d'un envoi de la Guinée vers le monde (et vice-versa)</p>
                    </div>

                    <div class="calculator-body">
                        <div class="form-group">
                            <label for="world_country">
                                <span class="dashicons dashicons-admin-site"></span>
                                Pays de destination
                            </label>
                            <select id="world_country" class="form-control" onchange="updateWorldModes()">
                                <option value="">-- Sélectionnez un pays --</option>
                                <?php foreach ($rates as $country_code => $country_data): ?>
                                    <option value="<?php echo esc_attr($country_code); ?>"
                                            data-modes='<?php echo json_encode($country_data['modes']); ?>'
                                            data-currency="<?php echo esc_attr($country_data['currency']); ?>"
                                            data-has-cities="<?php echo isset($country_data['cities']) ? 'true' : 'false'; ?>">
                                        <?php echo $country_data['flag']; ?> <?php echo ucfirst($country_code); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Ville (pour Maroc uniquement) -->
                        <div class="form-group" id="world_city_group" style="display:none;">
                            <label for="world_city">
                                <span class="dashicons dashicons-location"></span>
                                Ville
                            </label>
                            <select id="world_city" class="form-control">
                                <option value="">-- Sélectionnez une ville --</option>
                                <option value="casablanca">Casablanca</option>
                                <option value="rabat">Rabat</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="world_mode">
                                <span class="dashicons dashicons-location-alt"></span>
                                Mode de livraison
                            </label>
                            <select id="world_mode" class="form-control" disabled>
                                <option value="">-- Choisissez d'abord un pays --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="world_type">
                                <span class="dashicons dashicons-archive"></span>
                                Type de colis/document
                            </label>
                            <select id="world_type" class="form-control">
                                <option value="">-- Sélectionnez le type --</option>
                                <?php foreach ($parcel_types as $type_code => $type_label): ?>
                                    <option value="<?php echo esc_attr($type_code); ?>">
                                        <?php echo esc_html($type_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="world_quantity">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                Quantité (nombre de pièces)
                            </label>
                            <input type="number" id="world_quantity" class="form-control"
                                   placeholder="Ex: 2" min="1" step="1" value="1">
                            <small class="form-hint">Nombre de colis/documents à envoyer</small>
                        </div>

                        <button type="button" class="btn btn-primary btn-calculate" onclick="calculateGuineaWorld()">
                            <span class="dashicons dashicons-calculator"></span>
                            Calculer le prix
                        </button>

                        <!-- Résultat -->
                        <div id="world_result" class="calculator-result" style="display:none;"></div>
                    </div>
                </div>

                <!-- Liste des pays -->
                <div class="colis224-rates-card">
                    <div class="rates-header rates-header-world">
                        <h2>🌍 Pays Desservis</h2>
                        <p>Sélectionnez un pays pour voir les tarifs disponibles</p>
                    </div>

                    <div class="rates-body">
                        <div class="countries-grid">
                            <?php foreach ($rates as $country_code => $country_data): ?>
                                <div class="country-card" onclick="selectCountry('<?php echo esc_js($country_code); ?>')">
                                    <div class="country-flag"><?php echo $country_data['flag']; ?></div>
                                    <div class="country-name"><?php echo ucfirst($country_code); ?></div>
                                    <div class="country-currency"><?php echo $country_data['currency']; ?></div>
                                    <div class="country-modes">
                                        <?php echo count($country_data['modes']); ?> mode(s)
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="info-box info-box-success" style="margin-top: 20px;">
                            <h3>✅ Tarifs Bidirectionnels</h3>
                            <p>Les tarifs affichés sont <strong>identiques</strong> dans les deux sens:</p>
                            <ul style="margin-top: 10px;">
                                <li>🇬🇳 Guinée → France = 🇫🇷 France → Guinée</li>
                                <li>🇬🇳 Guinée → USA = 🇺🇸 USA → Guinée</li>
                                <li>etc.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Onglet Conteneur France → Conakry (v2.19.1)
     */
    private static function render_container_tab() {
        $rates = Colis224_Pricing_Data::get_container_france_conakry_rates();
        $categories = Colis224_Pricing_Data::get_container_categories();
        ?>
        <div class="colis224-pricing-container">
            <div class="colis224-pricing-grid">
                <!-- Calculateur Conteneur -->
                <div class="colis224-calculator-card">
                    <div class="calculator-header calculator-header-maritime">
                        <h2>🧮 Calculateur Bateau France → Conakry</h2>
                        <p>Transport maritime par conteneur depuis entrepôt Paris</p>
                    </div>

                    <div class="calculator-body">
                        <div class="form-group">
                            <label for="container_item">
                                <span class="dashicons dashicons-archive"></span>
                                Type d'article
                            </label>
                            <select id="container_item" class="form-control" onchange="updateContainerPrice()">
                                <option value="">-- Sélectionnez un article --</option>
                                <?php foreach ($categories as $category): ?>
                                    <optgroup label="<?php echo esc_attr($category); ?>">
                                        <?php foreach ($rates as $key => $rate):
                                            if ($rate['category'] === $category): ?>
                                                <option value="<?php echo esc_attr($key); ?>"
                                                        data-price="<?php echo esc_attr($rate['price']); ?>"
                                                        data-unit="<?php echo esc_attr($rate['unit']); ?>"
                                                        data-note="<?php echo isset($rate['note']) ? esc_attr($rate['note']) : ''; ?>">
                                                    <?php echo esc_html($rate['label']); ?>
                                                </option>
                                            <?php endif;
                                        endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="container_quantity">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <span id="container_quantity_label">Quantité</span>
                            </label>
                            <input type="number" id="container_quantity" class="form-control"
                                   placeholder="Ex: 2" min="0.1" step="0.1">
                            <small class="form-hint" id="container_quantity_hint">Entrez la quantité</small>
                        </div>

                        <button type="button" class="btn btn-primary btn-calculate" onclick="calculateContainer()">
                            <span class="dashicons dashicons-calculator"></span>
                            Calculer le prix
                        </button>

                        <!-- Résultat -->
                        <div id="container_result" class="calculator-result" style="display:none;"></div>
                    </div>
                </div>

                <!-- Grille tarifaire Conteneur -->
                <div class="colis224-rates-card">
                    <div class="rates-header rates-header-maritime">
                        <h2>📋 Tarifs Bateau France → Conakry</h2>
                        <p>Transport maritime par conteneur (Départ: Entrepôt Paris)</p>
                    </div>

                    <div class="rates-body" style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($categories as $category): ?>
                            <h3 style="margin: 20px 0 10px; color: #0891b2; font-size: 16px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                                <?php echo esc_html($category); ?>
                            </h3>
                            <table class="colis224-table" style="margin-bottom: 20px;">
                                <thead>
                                    <tr>
                                        <th>Article</th>
                                        <th style="text-align:center;">Unité</th>
                                        <th style="text-align:right;">Prix</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rates as $key => $rate):
                                        if ($rate['category'] === $category): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo esc_html($rate['label']); ?></strong>
                                                    <?php if (isset($rate['note'])): ?>
                                                        <br><small style="color: #f59e0b;">⚠️ <?php echo esc_html($rate['note']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="text-align:center;">
                                                    <span class="badge badge-info">
                                                        <?php echo esc_html($rate['unit']); ?>
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <strong class="price-value">
                                                        <?php
                                                        if ($rate['price'] > 0) {
                                                            echo number_format($rate['price'], 2, ',', ' ') . ' €';
                                                        } else {
                                                            echo '<span style="color: #f59e0b;">À définir</span>';
                                                        }
                                                        ?>
                                                    </strong>
                                                </td>
                                            </tr>
                                        <?php endif;
                                    endforeach; ?>
                                </tbody>
                            </table>
                        <?php endforeach; ?>
                    </div>

                    <div class="info-box info-box-warning" style="margin-top: 20px;">
                        <h3>📍 Point de Départ</h3>
                        <p><strong>Entrepôt de Paris</strong></p>
                        <p>Tous les tarifs sont calculés depuis notre entrepôt parisien.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Onglet Gestion des Tarifs (Admin uniquement - v2.19.1)
     */
    private static function render_manage_tab() {
        ?>
        <div class="colis224-pricing-container">
            <div style="max-width: 900px; margin: 0 auto;">
                <div class="colis224-card">
                    <h2 style="color: #1e293b; margin-top: 0;">
                        <span class="dashicons dashicons-admin-settings" style="color: #667eea;"></span>
                        Gestion des Tarifs
                    </h2>

                    <div class="info-box info-box-info">
                        <h3>📍 Emplacement des Tarifs</h3>
                        <p>Les tarifs sont actuellement stockés dans le fichier:</p>
                        <code>includes/class-colis224-pricing-data.php</code>
                    </div>

                    <div class="info-box info-box-warning" style="margin-top: 20px;">
                        <h3>⚙️ Comment Modifier les Tarifs</h3>
                        <p><strong>Méthode actuelle (v2.19.1):</strong></p>
                        <ol style="margin-left: 20px; line-height: 1.8;">
                            <li>Ouvrir le fichier <code>includes/class-colis224-pricing-data.php</code></li>
                            <li>Modifier les prix dans les méthodes:
                                <ul style="margin-top: 10px; margin-left: 20px;">
                                    <li><code>get_china_guinea_rates()</code> - Tarifs Chine → Guinée</li>
                                    <li><code>get_cbm_rate()</code> - Tarif CBM</li>
                                    <li><code>get_container_france_conakry_rates()</code> - Tarifs Conteneur</li>
                                    <li><code>get_guinea_world_rates()</code> - Tarifs Guinée ↔ Monde</li>
                                </ul>
                            </li>
                            <li>Sauvegarder le fichier</li>
                            <li>Les nouveaux tarifs seront appliqués immédiatement</li>
                        </ol>
                    </div>

                    <div class="info-box info-box-primary" style="margin-top: 20px;">
                        <h3>🚀 Interface de Gestion Future</h3>
                        <p>Une interface graphique complète pour modifier les tarifs directement depuis cette page est prévue pour une prochaine version.</p>
                        <p><strong>Fonctionnalités à venir:</strong></p>
                        <ul style="margin-left: 20px; line-height: 1.8;">
                            <li>✅ Édition en ligne des tarifs</li>
                            <li>✅ Historique des modifications</li>
                            <li>✅ Import/Export CSV</li>
                            <li>✅ Gestion des promotions temporaires</li>
                            <li>✅ Calcul automatique de marges</li>
                        </ul>
                    </div>

                    <div class="info-box info-box-success" style="margin-top: 20px;">
                        <h3>📊 Résumé des Tarifs Actifs</h3>
                        <table class="colis224-table" style="margin-top: 15px;">
                            <thead>
                                <tr>
                                    <th>Zone Tarifaire</th>
                                    <th style="text-align:center;">Nombre d'Articles</th>
                                    <th style="text-align:right;">Devise</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>✈️ Chine → Guinée</strong></td>
                                    <td style="text-align:center;">
                                        <?php echo count(Colis224_Pricing_Data::get_china_guinea_rates()); ?> articles
                                    </td>
                                    <td style="text-align:right;"><span class="badge badge-primary">GNF</span></td>
                                </tr>
                                <tr>
                                    <td><strong>🚢 Maritime CBM</strong></td>
                                    <td style="text-align:center;">1 tarif</td>
                                    <td style="text-align:right;"><span class="badge badge-primary">GNF</span></td>
                                </tr>
                                <tr>
                                    <td><strong>📦 Conteneur FR → GN</strong></td>
                                    <td style="text-align:center;">
                                        <?php echo count(Colis224_Pricing_Data::get_container_france_conakry_rates()); ?> articles
                                    </td>
                                    <td style="text-align:right;"><span class="badge badge-info">EUR</span></td>
                                </tr>
                                <tr>
                                    <td><strong>🌍 Guinée ↔ Monde</strong></td>
                                    <td style="text-align:center;">
                                        <?php echo count(Colis224_Pricing_Data::get_guinea_world_rates()); ?> pays
                                    </td>
                                    <td style="text-align:right;">
                                        <span class="badge badge-info">EUR</span>
                                        <span class="badge badge-warning">USD</span>
                                        <span class="badge badge-primary">GNF</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white; text-align: center;">
                        <h3 style="color: white; margin-top: 0;">💡 Besoin d'Aide?</h3>
                        <p style="margin-bottom: 15px;">Pour toute assistance concernant la modification des tarifs ou l'ajout de nouvelles zones tarifaires, contactez le support technique.</p>
                        <a href="mailto:support@colis224.com" style="display: inline-block; background: white; color: #667eea; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600;">
                            📧 Contacter le Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
