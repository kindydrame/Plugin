<?php
/**
 * Colis224 Frontend Calculator - Shortcode pour les clients
 *
 * Permet aux clients de calculer les tarifs, générer leurs shipping marks
 * et obtenir les adresses de livraison selon leurs besoins
 *
 * @package Colis224_Logistics_Manager
 * @since 2.20.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Frontend_Calculator {

    /**
     * Table name pour stocker les demandes
     */
    private static $table_name = 'colis224_frontend_requests';

    /**
     * Noms des fichiers images attendus
     */
    private static $image_files = array(
        'wechat_qr' => 'wechat-qr',
        'orange_money_qr' => 'orange-money-qr',
        'sea_cargo' => 'sea-cargo-address',
        'air_plane' => 'air-plane-address',
        'air_cargo_mark' => 'air-cargo-mark',
        'sea_cargo_mark' => 'sea-cargo-mark',
    );

    /**
     * Initialize the class
     */
    public static function init() {
        // Register shortcode
        add_shortcode('colis224_calculator', array(__CLASS__, 'render_calculator'));

        // AJAX handlers
        add_action('wp_ajax_colis224_submit_request', array(__CLASS__, 'ajax_submit_request'));
        add_action('wp_ajax_nopriv_colis224_submit_request', array(__CLASS__, 'ajax_submit_request'));

        // Enqueue scripts
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }

    /**
     * Obtenir l'URL d'une image avec fallback
     *
     * Ordre de priorité:
     * 1. Image locale dans assets/images/ (jpg ou png)
     * 2. Option WordPress si configurée
     * 3. URL par défaut (externe)
     *
     * @param string $image_key Clé de l'image (ex: 'wechat_qr', 'sea_cargo')
     * @return string URL de l'image ou chaîne vide si non trouvée
     */
    public static function get_image_url($image_key) {
        $image_name = isset(self::$image_files[$image_key]) ? self::$image_files[$image_key] : $image_key;
        $images_dir = COLIS224_PLUGIN_DIR . 'assets/images/';
        $images_url = COLIS224_PLUGIN_URL . 'assets/images/';

        // 1. Vérifier si l'image existe localement (jpg ou png)
        $extensions = array('jpg', 'jpeg', 'png', 'webp');
        foreach ($extensions as $ext) {
            $local_path = $images_dir . $image_name . '.' . $ext;
            if (file_exists($local_path)) {
                return $images_url . $image_name . '.' . $ext;
            }
        }

        // 2. Vérifier les options WordPress
        $option_key = 'colis224_image_' . $image_key;
        $option_url = get_option($option_key, '');
        if (!empty($option_url)) {
            return esc_url($option_url);
        }

        // 3. URLs par défaut (fallback externe)
        $default_urls = array(
            'wechat_qr' => 'https://colis224.com/wp-content/uploads/2026/01/Compte-Wechat.jpg',
            'orange_money_qr' => 'https://colis224.com/wp-content/uploads/2026/01/compte-marchand-orange-money-colis224.jpg',
            'sea_cargo' => 'https://colis224.com/wp-content/uploads/2026/01/adresse-bateau.jpeg',
            'air_plane' => 'https://colis224.com/wp-content/uploads/2026/01/adrsse-avion.jpeg',
            'air_cargo_mark' => 'https://colis224.com/wp-content/uploads/2026/01/Process-avion.jpg',
            'sea_cargo_mark' => 'https://colis224.com/wp-content/uploads/2026/01/process-bateau.jpg',
        );

        return isset($default_urls[$image_key]) ? $default_urls[$image_key] : '';
    }

    /**
     * Obtenir toutes les URLs des images pour le calculateur
     *
     * @return array Tableau associatif des URLs d'images
     */
    public static function get_calculator_images() {
        return array(
            'wechat_qr' => self::get_image_url('wechat_qr'),
            'orange_money_qr' => self::get_image_url('orange_money_qr'),
            'images' => array(
                'sea_cargo' => self::get_image_url('sea_cargo'),
                'air_plane' => self::get_image_url('air_plane'),
                'air_cargo_mark' => self::get_image_url('air_cargo_mark'),
                'sea_cargo_mark' => self::get_image_url('sea_cargo_mark'),
            ),
        );
    }

    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            nom varchar(100) NOT NULL,
            prenom varchar(100) NOT NULL,
            telephone varchar(50) NOT NULL,
            pays varchar(10) DEFAULT 'GN',
            indicatif varchar(10) DEFAULT '+224',
            type_service varchar(20) NOT NULL COMMENT 'cas1, cas2, cas3 ou cas4',
            origine varchar(20) DEFAULT NULL COMMENT 'chine, france, guinee, senegal, maroc, abidjan',
            mode_livraison varchar(20) DEFAULT NULL COMMENT 'avion ou bateau',
            destination varchar(50) DEFAULT NULL COMMENT 'paris, marseille, nice, etc',
            shipping_mark text DEFAULT NULL,
            pa_code varchar(20) DEFAULT NULL,
            quantites text DEFAULT NULL COMMENT 'Pour CAS 1',
            modeles text DEFAULT NULL COMMENT 'Pour CAS 1',
            delai text DEFAULT NULL COMMENT 'Pour CAS 1',
            panier_details text DEFAULT NULL COMMENT 'Pour CAS 3',
            site_achat varchar(100) DEFAULT NULL COMMENT 'Pour CAS 3',
            agency varchar(50) DEFAULT NULL COMMENT 'Pour CAS 4: conakry, paris, marseille, nice',
            statut_paiement varchar(20) DEFAULT 'pending' COMMENT 'pending, paid, confirmed',
            ip_address varchar(50) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            date_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY telephone (telephone),
            KEY type_service (type_service),
            KEY statut_paiement (statut_paiement),
            KEY date_created (date_created)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Enqueue scripts and styles
     */
    public static function enqueue_scripts() {
        // Only enqueue on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'colis224_calculator')) {
            // CSS
            wp_enqueue_style(
                'colis224-frontend-calculator',
                COLIS224_PLUGIN_URL . 'assets/css/frontend-calculator.css',
                array(),
                COLIS224_VERSION
            );

            // JavaScript - Force cache refresh with timestamp
            wp_enqueue_script(
                'colis224-frontend-calculator',
                COLIS224_PLUGIN_URL . 'assets/js/frontend-calculator.js',
                array('jquery'),
                COLIS224_VERSION . '.' . time(),
                true
            );

            // Obtenir les URLs des images avec le système de fallback
            $calculator_images = self::get_calculator_images();

            // Localize script
            wp_localize_script('colis224-frontend-calculator', 'colis224Frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('colis224_frontend_nonce'),
                'warehouse_phone_china' => '+8618719472926',
                'warehouse_phone_guinea' => '+224620178930',
                'agencies' => array(
                    array('name' => 'Agence 1', 'phone' => '+224620178930'),
                    array('name' => 'Agence 2 Lambanyi', 'phone' => '+224626526737'),
                    array('name' => 'Agence 3', 'phone' => '+224626526735'),
                    array('name' => 'Bureau France', 'phone' => '+33698485752'),
                    array('name' => 'Agence USA', 'phone' => '+17185822079'),
                ),
                'orange_money_code' => '#144*6*649048*100000*code secret#OK',
                'orange_money_merchant' => 'COLIS224',
                'orange_money_qr' => $calculator_images['orange_money_qr'],
                'wechat_qr' => $calculator_images['wechat_qr'],
                'images' => $calculator_images['images'],
                'plugin_images_url' => COLIS224_PLUGIN_URL . 'assets/images/',
            ));
        }
    }

    /**
     * Render the calculator shortcode
     */
    public static function render_calculator($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Calculateur de Tarifs Colis224',
            'theme' => 'default'
        ), $atts);

        ob_start();
        ?>
        <div class="colis224-frontend-calculator" data-theme="<?php echo esc_attr($atts['theme']); ?>">
            <div class="calculator-container">

                <!-- Header -->
                <div class="calculator-header">
                    <h2><?php echo esc_html($atts['title']); ?></h2>
                    <p class="subtitle">Obtenez vos tarifs et shipping marks en quelques clics</p>
                </div>

                <!-- Progress Bar -->
                <div class="progress-bar">
                    <div class="progress-step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-label">Vos infos</span>
                    </div>
                    <div class="progress-step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-label">Type de service</span>
                    </div>
                    <div class="progress-step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-label">Détails</span>
                    </div>
                    <div class="progress-step" data-step="4">
                        <span class="step-number">4</span>
                        <span class="step-label">Résultat</span>
                    </div>
                </div>

                <!-- Form Steps -->
                <form id="colis224-calculator-form" class="calculator-form" novalidate>

                    <!-- Step 1: Contact Information -->
                    <div class="form-step active" data-step="1">
                        <h3>📝 Vos informations</h3>
                        <p class="step-description">Commençons par faire connaissance</p>

                        <div class="form-group">
                            <label for="client_prenom">Prénom *</label>
                            <input type="text" id="client_prenom" name="prenom" required
                                   placeholder="Ex: Mamadou">
                        </div>

                        <div class="form-group">
                            <label for="client_nom">Nom *</label>
                            <input type="text" id="client_nom" name="nom" required
                                   placeholder="Ex: Diallo">
                        </div>

                        <div class="form-group">
                            <label for="client_telephone">Numéro de téléphone *</label>
                            <input type="tel" id="client_telephone" name="telephone" required
                                   placeholder="Ex: +224 620 17 89 30">
                            <small class="hint">Avec l'indicatif de votre pays (ex: +224 pour la Guinée)</small>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-primary btn-next" data-next="2">
                                Suivant <span class="icon">→</span>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Service Type Selection -->
                    <div class="form-step" data-step="2">
                        <h3>😊 Tu es plutôt CAS N°1, N°2, N°3 ou N°4 ? 🤔</h3>
                        <p class="step-description">Chez Colis224, on s'adapte à ton niveau !</p>

                        <div class="service-cards" style="grid-template-columns: repeat(2, 1fr);">
                            <!-- CAS 1 -->
                            <div class="service-card" data-service="cas1">
                                <div class="service-icon">🛑</div>
                                <h4>CAS N°1 : "Je ne sais pas faire / Je n'ai pas de carte bancaire"</h4>
                                <p class="service-subtitle">Pas de panique, on gère tout de A à Z !</p>

                                <ul class="service-features">
                                    <li>✅ On vérifie la fiabilité du vendeur (anti-arnaque)</li>
                                    <li>✅ On valide et on paie pour toi</li>
                                    <li>✅ On suit le colis jusqu'à Conakry</li>
                                </ul>

                                <div class="service-price">
                                    <span class="price-amount">100 000 GNF</span>
                                    <span class="price-label">Frais fixes pour qu'on s'occupe de ta commande</span>
                                </div>

                                <p class="service-note">
                                    <strong>Note :</strong> Tu paies le produit + les 100 000 GNF + le transport.
                                </p>

                                <button type="button" class="btn btn-secondary btn-select-service" data-service="cas1">
                                    Choisir CAS N°1
                                </button>
                            </div>

                            <!-- CAS 2 -->
                            <div class="service-card" data-service="cas2">
                                <div class="service-icon">🚀</div>
                                <h4>CAS N°2 : "Je suis un pro, je commande moi-même"</h4>
                                <p class="service-subtitle">Tu as ta carte et tes comptes Alibaba/Shein ?</p>

                                <ul class="service-features">
                                    <li>✅ Utilise nos adresses en Chine/Europe pour te faire livrer</li>
                                </ul>

                                <div class="service-price service-price-free">
                                    <span class="price-amount">0 GNF !</span>
                                    <span class="price-label">Service gratuit</span>
                                </div>

                                <p class="service-note">
                                    <strong>Note :</strong> Tu ne paies que le transport (prix au kg) à l'arrivée.
                                </p>

                                <button type="button" class="btn btn-primary btn-select-service" data-service="cas2">
                                    Choisir CAS N°2
                                </button>
                            </div>

                            <!-- CAS 3 -->
                            <div class="service-card" data-service="cas3">
                                <div class="service-icon">✅</div>
                                <h4>CAS N°3 : "Je veux valider ma commande"</h4>
                                <p class="service-subtitle">Ton panier est prêt ? On valide ensemble !</p>

                                <ul class="service-features">
                                    <li>✅ Validation de ton panier d'achat</li>
                                    <li>✅ Vérification des articles sélectionnés</li>
                                    <li>✅ Confirmation avant paiement final</li>
                                </ul>

                                <div class="service-price">
                                    <span class="price-amount">100 000 GNF</span>
                                    <span class="price-label">Frais de validation du panier</span>
                                </div>

                                <p class="service-note">
                                    <strong>Note :</strong> Paiement pour sécuriser ta commande.
                                </p>

                                <button type="button" class="btn btn-warning btn-select-service" data-service="cas3">
                                    Choisir CAS N°3
                                </button>
                            </div>

                            <!-- CAS 4 -->
                            <div class="service-card" data-service="cas4">
                                <div class="service-icon">🏢</div>
                                <h4>CAS N°4 : "Déposer un colis dans votre agence"</h4>
                                <p class="service-subtitle">Tu veux venir directement en agence ?</p>

                                <ul class="service-features">
                                    <li>✅ Dépôt direct en agence</li>
                                    <li>✅ Accueil personnalisé</li>
                                    <li>✅ Traitement immédiat</li>
                                </ul>

                                <div class="service-price service-price-free">
                                    <span class="price-amount">0 GNF</span>
                                    <span class="price-label">Juste les frais de transport</span>
                                </div>

                                <p class="service-note">
                                    <strong>Note :</strong> Choisis ton agence ci-dessous.
                                </p>

                                <button type="button" class="btn btn-success btn-select-service" data-service="cas4">
                                    Choisir CAS N°4
                                </button>
                            </div>
                        </div>

                        <div class="info-box">
                            <h4>💡 Pourquoi payer les 100 000 GNF ?</h4>
                            <p>C'est le prix de la tranquillité. Pour un novice, acheter sur Alibaba comporte des risques (qualité, faux vendeurs). Nous mettons notre expertise à votre service pour éviter de perdre votre argent.</p>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary btn-prev" data-prev="1">
                                <span class="icon">←</span> Retour
                            </button>
                        </div>
                    </div>

                    <!-- Step 3A: CAS 1 - Order Details -->
                    <div class="form-step" data-step="3" data-service="cas1" style="display:none;">
                        <h3>🛒 Détails de votre commande</h3>
                        <p class="step-description">Chez Colis224, on vous facilite l'achat au meilleur prix 😊</p>

                        <div class="info-box info-box-primary">
                            <h4>💰 Tarif du service : 100 000 GNF</h4>
                            <p>Pour lancer immédiatement la recherche auprès de 3 à 5 fournisseurs fiables, notre service est facturé à seulement 100 000 GNF. Ce tarif couvre :</p>
                            <ul>
                                <li>L'analyse de votre demande</li>
                                <li>Le contact direct avec plusieurs fournisseurs sérieux</li>
                                <li>La négociation des meilleurs devis pour vous</li>
                            </ul>
                            <p><strong>Dès validation du paiement, notre équipe se met au travail dans l'heure !</strong></p>
                        </div>

                        <div class="form-group">
                            <label for="cas1_quantites">Quantités souhaitées *</label>
                            <textarea id="cas1_quantites" name="quantites" rows="3"
                                      placeholder="Ex: 100 pièces de chaussures taille 38-42"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="cas1_modeles">Modèles, références ou couleurs spécifiques *</label>
                            <textarea id="cas1_modeles" name="modeles" rows="3"
                                      placeholder="Ex: Baskets Nike Air Max, couleurs : noir, blanc, rouge"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="cas1_delai">Délai de livraison idéal</label>
                            <input type="text" id="cas1_delai" name="delai"
                                   placeholder="Ex: Dans 2 semaines">
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary btn-prev" data-prev="2">
                                <span class="icon">←</span> Retour
                            </button>
                            <button type="button" class="btn btn-primary btn-next" data-next="4">
                                Continuer au paiement <span class="icon">→</span>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3B: CAS 2 - Origin Selection -->
                    <div class="form-step" data-step="3" data-service="cas2" style="display:none;">
                        <h3>🌍 D'où vient votre colis ?</h3>
                        <p class="step-description">Choisissez l'origine de votre commande</p>

                        <div class="origin-cards" style="grid-template-columns: repeat(3, 1fr);">
                            <div class="origin-card" data-origin="chine">
                                <div class="origin-flag">🇨🇳</div>
                                <h4>Chine</h4>
                                <p>Alibaba, 1688, Taobao, etc.</p>
                                <button type="button" class="btn btn-primary btn-select-origin" data-origin="chine">
                                    Choisir la Chine
                                </button>
                            </div>

                            <div class="origin-card" data-origin="france">
                                <div class="origin-flag">🇫🇷</div>
                                <h4>France / Europe</h4>
                                <p>Amazon, Shein, Temu, Zara, etc.</p>
                                <button type="button" class="btn btn-primary btn-select-origin" data-origin="france">
                                    Choisir la France
                                </button>
                            </div>

                            <div class="origin-card" data-origin="guinee">
                                <div class="origin-flag">🇬🇳</div>
                                <h4>Guinée</h4>
                                <p>Conakry, Labé, Kankan, etc.</p>
                                <button type="button" class="btn btn-primary btn-select-origin" data-origin="guinee">
                                    Choisir la Guinée
                                </button>
                            </div>

                            <div class="origin-card" data-origin="senegal">
                                <div class="origin-flag">🇸🇳</div>
                                <h4>Sénégal</h4>
                                <p>Dakar, Thiès, etc.</p>
                                <button type="button" class="btn btn-primary btn-select-origin" data-origin="senegal">
                                    Choisir le Sénégal
                                </button>
                            </div>

                            <div class="origin-card" data-origin="maroc">
                                <div class="origin-flag">🇲🇦</div>
                                <h4>Maroc</h4>
                                <p>Casablanca, Rabat, etc.</p>
                                <button type="button" class="btn btn-primary btn-select-origin" data-origin="maroc">
                                    Choisir le Maroc
                                </button>
                            </div>

                            <div class="origin-card" data-origin="abidjan">
                                <div class="origin-flag">🇨🇮</div>
                                <h4>Côte d'Ivoire</h4>
                                <p>Abidjan, Yamoussoukro, etc.</p>
                                <button type="button" class="btn btn-primary btn-select-origin" data-origin="abidjan">
                                    Choisir Abidjan
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary btn-prev" data-prev="2">
                                <span class="icon">←</span> Retour
                            </button>
                        </div>
                    </div>

                    <!-- Step 3B2: CAS 3 - Validation Panier Payment -->
                    <div class="form-step" data-step="3" data-service="cas3" style="display:none;">
                        <h3>✅ Validation de votre panier</h3>
                        <p class="step-description">Sécurisez votre commande avec notre service de validation</p>

                        <div class="info-box info-box-primary">
                            <h4>💰 Frais de validation : 100 000 GNF</h4>
                            <p>Ce service vous garantit :</p>
                            <ul>
                                <li>Vérification complète de votre panier</li>
                                <li>Validation de tous les articles sélectionnés</li>
                                <li>Confirmation des quantités et prix</li>
                                <li>Sécurisation de la transaction</li>
                            </ul>
                        </div>

                        <div class="form-group">
                            <label for="cas3_panier">Détails de votre panier *</label>
                            <textarea id="cas3_panier" name="panier_details" rows="5"
                                      placeholder="Listez les articles de votre panier avec quantités et prix si possible"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="cas3_site">Site d'achat</label>
                            <input type="text" id="cas3_site" name="site_achat"
                                   placeholder="Ex: Alibaba, Shein, Amazon...">
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary btn-prev" data-prev="2">
                                <span class="icon">←</span> Retour
                            </button>
                            <button type="button" class="btn btn-primary btn-next" data-next="4">
                                Continuer au paiement <span class="icon">→</span>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3B3: CAS 4 - Choix Agence -->
                    <div class="form-step" data-step="3" data-service="cas4" style="display:none;">
                        <h3>🏢 Choisissez votre agence</h3>
                        <p class="step-description">Où souhaitez-vous déposer votre colis ?</p>

                        <div class="origin-cards" style="grid-template-columns: repeat(2, 1fr);">
                            <div class="origin-card agency-card" data-agency="conakry">
                                <div class="origin-flag">🇬🇳</div>
                                <h4>Agence Conakry</h4>
                                <p><strong>📍 Adresse:</strong> Conakry, Guinée</p>
                                <p><strong>📞 Téléphone:</strong></p>
                                <p style="margin: 4px 0;">
                                    <a href="https://wa.me/224620178930" target="_blank" rel="noopener noreferrer" style="color: #25d366; text-decoration: none; font-weight: 600;">
                                        📱 +224 620 17 89 30
                                    </a>
                                </p>
                                <p style="margin: 4px 0;">
                                    <a href="https://wa.me/224626526737" target="_blank" rel="noopener noreferrer" style="color: #25d366; text-decoration: none; font-weight: 600;">
                                        📱 +224 626 526 737
                                    </a>
                                </p>
                                <p style="margin: 4px 0;">
                                    <a href="https://wa.me/224628108661" target="_blank" rel="noopener noreferrer" style="color: #25d366; text-decoration: none; font-weight: 600;">
                                        📱 +224 628 108 661
                                    </a>
                                </p>
                                <button type="button" class="btn btn-primary btn-select-agency" data-agency="conakry">
                                    Choisir Conakry
                                </button>
                            </div>

                            <div class="origin-card agency-card" data-agency="paris">
                                <div class="origin-flag">🇫🇷</div>
                                <h4>Bureau Paris</h4>
                                <p><strong>📍 Adresse:</strong> 37 Rue Stephenson<br>75018 Paris, France</p>
                                <p><strong>📞 Téléphone:</strong> +33 6 98 48 57 52</p>
                                <button type="button" class="btn btn-primary btn-select-agency" data-agency="paris">
                                    Choisir Paris
                                </button>
                            </div>

                            <div class="origin-card agency-card" data-agency="marseille">
                                <div class="origin-flag">🇫🇷</div>
                                <h4>Bureau Marseille</h4>
                                <p><strong>📍 Adresse:</strong> 68 rue Longue des Capucins<br>13001 Marseille, France</p>
                                <p><strong>📞 Téléphone:</strong> +33 6 98 48 57 52</p>
                                <button type="button" class="btn btn-primary btn-select-agency" data-agency="marseille">
                                    Choisir Marseille
                                </button>
                            </div>

                            <div class="origin-card agency-card" data-agency="nice">
                                <div class="origin-flag">🇫🇷</div>
                                <h4>Bureau Nice</h4>
                                <p><strong>📍 Adresse:</strong> 42 Rue Gounod<br>06000 Nice, France</p>
                                <p><strong>📞 Téléphone:</strong> +33 6 98 48 57 52</p>
                                <button type="button" class="btn btn-primary btn-select-agency" data-agency="nice">
                                    Choisir Nice
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary btn-prev" data-prev="2">
                                <span class="icon">←</span> Retour
                            </button>
                        </div>
                    </div>

                    <!-- Step 3C: CAS 2 - Chine - Mode Selection -->
                    <div class="form-step" data-step="3-chine" style="display:none;">
                        <h3>✈️ Mode de livraison depuis la Chine</h3>
                        <p class="step-description">Comment souhaitez-vous recevoir votre colis ?</p>

                        <div class="mode-cards">
                            <div class="mode-card" data-mode="avion">
                                <div class="mode-icon">✈️</div>
                                <h4>Avion</h4>
                                <p>Rapide - 7 à 15 jours</p>
                                <button type="button" class="btn btn-primary btn-select-mode" data-mode="avion" data-origin="chine">
                                    Choisir Avion
                                </button>
                            </div>

                            <div class="mode-card" data-mode="bateau">
                                <div class="mode-icon">🚢</div>
                                <h4>Bateau</h4>
                                <p>Économique - 30 à 45 jours</p>
                                <button type="button" class="btn btn-primary btn-select-mode" data-mode="bateau" data-origin="chine">
                                    Choisir Bateau
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary btn-back-origin">
                                <span class="icon">←</span> Retour au choix d'origine
                            </button>
                        </div>
                    </div>

                    <!-- Step 3D: CAS 2 - France - Mode Selection -->
                    <div class="form-step" data-step="3-france" style="display:none;">
                        <h3>🇫🇷 Mode de livraison depuis la France</h3>
                        <p class="step-description">Comment souhaitez-vous recevoir votre colis ?</p>

                        <div class="mode-cards">
                            <div class="mode-card" data-mode="bateau-paris">
                                <div class="mode-icon">🚢</div>
                                <h4>Bateau Paris → Conakry</h4>
                                <p>Pour gros volumes - Conteneur</p>
                                <button type="button" class="btn btn-primary btn-select-mode" data-mode="bateau" data-origin="france-paris">
                                    Choisir Bateau Paris
                                </button>
                            </div>

                            <div class="mode-card" data-mode="avion-france">
                                <div class="mode-icon">📦</div>
                                <h4>Colis depuis sites français</h4>
                                <p>Amazon, Shein, Temu, Zara, etc.</p>
                                <button type="button" class="btn btn-primary btn-select-france-cities">
                                    Choisir cette option
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary btn-back-origin">
                                <span class="icon">←</span> Retour au choix d'origine
                            </button>
                        </div>
                    </div>

                    <!-- Step 3E: CAS 2 - France Cities Selection -->
                    <div class="form-step" data-step="3-france-cities" style="display:none;">
                        <h3>📍 Quelle ville de réception ?</h3>
                        <p class="step-description">Choisissez notre adresse pour votre livraison</p>

                        <div class="city-cards">
                            <div class="city-card" data-city="paris">
                                <div class="city-icon">🗼</div>
                                <h4>Paris</h4>
                                <p>37 Rue Stephenson, 75018</p>
                                <button type="button" class="btn btn-primary btn-select-mode" data-mode="avion" data-origin="france-paris">
                                    Livrer à Paris
                                </button>
                            </div>

                            <div class="city-card" data-city="marseille">
                                <div class="city-icon">⚓</div>
                                <h4>Marseille</h4>
                                <p>68 rue Longue des Capucins, 13001</p>
                                <button type="button" class="btn btn-primary btn-select-mode" data-mode="avion" data-origin="france-marseille">
                                    Livrer à Marseille
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">
                                <span class="icon">←</span> Retour
                            </button>
                        </div>
                    </div>

                    <!-- Step 4A: CAS 1 - Payment -->
                    <div class="form-step" data-step="4" data-result="cas1" style="display:none;">
                        <h3>💳 Paiement sécurisé</h3>
                        <p class="step-description">Validez votre demande avec Orange Money</p>

                        <div class="payment-box">
                            <h4>💰 Montant à payer : 100 000 GNF</h4>

                            <div class="payment-methods">
                                <div class="payment-method">
                                    <h5>📱 Option 1 : Scanner le QR Code</h5>
                                    <div class="qr-code-container" style="text-align: center; padding: 20px; background: #fff; border-radius: 8px; border: 2px solid #f59e0b;">
                                        <img src="https://colis224.com/wp-content/uploads/2026/01/compte-marchand-orange-money-colis224.jpg"
                                             alt="QR Code Orange Money COLIS224"
                                             style="max-width: 180px; width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                        <p class="merchant-name" style="margin: 12px 0 0; font-weight: 600; color: #f59e0b;">Marchand : COLIS224</p>
                                    </div>
                                </div>

                                <div class="payment-divider">OU</div>

                                <div class="payment-method">
                                    <h5>📞 Option 2 : Code USSD</h5>
                                    <div class="ussd-code">
                                        <code id="ussd-code">#144*6*649048*100000*code secret#OK</code>
                                        <button type="button" class="btn-copy" data-copy="ussd-code">
                                            📋 Copier
                                        </button>
                                    </div>
                                    <p class="merchant-name">Nom du marchand : COLIS224</p>
                                </div>
                            </div>

                            <div class="payment-confirmation">
                                <p><strong>Une fois votre paiement confirmé, on s'occupe de tout pour vous.</strong></p>
                                <p>Vous recevrez rapidement les meilleures propositions.</p>
                            </div>
                        </div>

                        <div class="summary-box">
                            <h4>📋 Récapitulatif de votre demande</h4>
                            <div id="cas1-summary"></div>
                        </div>

                        <!-- WhatsApp Contact Section -->
                        <div class="whatsapp-quote-section" style="margin-top: 24px; padding: 24px; background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); border-radius: 12px; text-align: center; color: white;">
                            <h4 style="margin: 0 0 12px; font-size: 20px; font-weight: 700;">
                                💬 Des questions sur votre devis ?
                            </h4>
                            <p style="margin: 0 0 16px; opacity: 0.9; font-size: 14px;">
                                Notre équipe est disponible sur WhatsApp pour vous aider !
                            </p>
                            <a href="https://wa.me/224620178930"
                               target="_blank"
                               rel="noopener noreferrer"
                               style="display: inline-block; margin: 8px; padding: 14px 32px; background: white; color: #25d366; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: transform 0.2s, box-shadow 0.2s;"
                               onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,0.3)';"
                               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)';">
                                💬 Contacter sur WhatsApp
                            </a>
                            <div id="whatsapp-cas1-buttons" style="margin-top: 12px;"></div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary btn-prev" data-prev="3">
                                <span class="icon">←</span> Retour
                            </button>
                            <button type="submit" class="btn btn-success btn-submit">
                                ✅ Confirmer ma demande
                            </button>
                        </div>
                    </div>

                    <!-- Step 4A2: CAS 3 - Payment -->
                    <div class="form-step" data-step="4" data-result="cas3" style="display:none;">
                        <h3>💳 Paiement sécurisé - Validation Panier</h3>
                        <p class="step-description">Validez votre panier avec Orange Money</p>

                        <div class="payment-box">
                            <h4>💰 Montant à payer : 100 000 GNF</h4>

                            <div class="payment-methods">
                                <div class="payment-method">
                                    <h5>📱 Option 1 : Scanner le QR Code</h5>
                                    <div class="qr-code-container" style="text-align: center; padding: 20px; background: #fff; border-radius: 8px; border: 2px solid #f59e0b;">
                                        <img src="https://colis224.com/wp-content/uploads/2026/01/compte-marchand-orange-money-colis224.jpg"
                                             alt="QR Code Orange Money COLIS224"
                                             style="max-width: 180px; width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                        <p class="merchant-name" style="margin: 12px 0 0; font-weight: 600; color: #f59e0b;">Marchand : COLIS224</p>
                                    </div>
                                </div>

                                <div class="payment-divider">OU</div>

                                <div class="payment-method">
                                    <h5>📞 Option 2 : Code USSD</h5>
                                    <div class="ussd-code">
                                        <code id="ussd-code-cas3">#144*6*649048*100000*code secret#OK</code>
                                        <button type="button" class="btn-copy" data-copy="ussd-code-cas3">
                                            📋 Copier
                                        </button>
                                    </div>
                                    <p class="merchant-name">Nom du marchand : COLIS224</p>
                                </div>
                            </div>

                            <div class="payment-confirmation">
                                <p><strong>Une fois votre paiement confirmé, notre équipe validera votre panier.</strong></p>
                                <p>Vous recevrez la confirmation rapidement.</p>
                            </div>
                        </div>

                        <div class="summary-box">
                            <h4>📋 Récapitulatif</h4>
                            <div id="cas3-summary"></div>
                        </div>

                        <!-- WhatsApp Contact Section -->
                        <div class="whatsapp-quote-section" style="margin-top: 24px; padding: 24px; background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); border-radius: 12px; text-align: center; color: white;">
                            <h4 style="margin: 0 0 12px; font-size: 20px; font-weight: 700;">
                                💬 Des questions sur votre devis ?
                            </h4>
                            <p style="margin: 0 0 16px; opacity: 0.9; font-size: 14px;">
                                Notre équipe est disponible sur WhatsApp pour vous aider !
                            </p>
                            <a href="https://wa.me/224620178930"
                               target="_blank"
                               rel="noopener noreferrer"
                               style="display: inline-block; margin: 8px; padding: 14px 32px; background: white; color: #25d366; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: transform 0.2s, box-shadow 0.2s;"
                               onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,0.3)';"
                               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)';">
                                💬 Contacter sur WhatsApp
                            </a>
                            <div id="whatsapp-cas3-buttons" style="margin-top: 12px;"></div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary btn-prev" data-prev="3">
                                <span class="icon">←</span> Retour
                            </button>
                            <button type="submit" class="btn btn-success btn-submit">
                                ✅ Confirmer le paiement
                            </button>
                        </div>
                    </div>

                    <!-- Step 4B: CAS 2 - Shipping Mark & Address -->
                    <div class="form-step" data-step="4" data-result="cas2" style="display:none;">
                        <h3>📦 Votre Shipping Mark & Adresse</h3>
                        <p class="step-description">Copiez ces informations pour votre fournisseur</p>

                        <div class="result-container">
                            <!-- Pricing Info -->
                            <div id="pricing-display"></div>

                            <!-- Shipping Mark -->
                            <div class="shipping-mark-box">
                                <h4>🏷️ SHIPPING MARK (à écrire sur le carton)</h4>
                                <div class="shipping-mark-content" id="shipping-mark-display"></div>
                                <button type="button" class="btn btn-copy-full" data-copy="shipping-mark-display">
                                    📋 Copier le Shipping Mark
                                </button>
                            </div>

                            <!-- Delivery Address -->
                            <div class="address-box">
                                <h4>📍 ADRESSE DE LIVRAISON</h4>
                                <!-- Image d'adresse correspondante au mode -->
                                <div id="address-image" style="margin-bottom: 16px;"></div>
                                <div class="address-content" id="address-display"></div>
                                <button type="button" class="btn btn-copy-full" data-copy="address-display">
                                    📋 Copier l'adresse
                                </button>
                            </div>

                            <!-- Visual Instructions - Shipping Mark -->
                            <div class="visual-instructions">
                                <h4>📸 Instructions de marquage des cartons</h4>
                                <div id="visual-instructions-images"></div>
                            </div>

                            <!-- WhatsApp Contact Section -->
                            <div class="whatsapp-quote-section" style="margin-top: 24px; padding: 24px; background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); border-radius: 12px; text-align: center; color: white;">
                                <h4 style="margin: 0 0 12px; font-size: 20px; font-weight: 700;">
                                    💬 Des questions sur votre devis ?
                                </h4>
                                <p style="margin: 0 0 16px; opacity: 0.9; font-size: 14px;">
                                    Notre équipe est disponible sur WhatsApp pour vous aider !
                                </p>
                                <a href="https://wa.me/224620178930"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   style="display: inline-block; margin: 8px; padding: 14px 32px; background: white; color: #25d366; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: transform 0.2s, box-shadow 0.2s;"
                                   onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 16px rgba(0,0,0,0.3)';"
                                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.2)';">
                                    💬 Contacter sur WhatsApp
                                </a>
                                <div id="whatsapp-quote-buttons" style="margin-top: 12px;"></div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary btn-restart">
                                <span class="icon">←</span> Retour à l'accueil
                            </button>
                            <button type="submit" class="btn btn-success btn-submit">
                                ✅ Enregistrer mes informations
                            </button>
                        </div>
                    </div>

                </form>

                <!-- Success Message -->
                <div class="success-message" style="display:none;">
                    <div class="success-icon">✅</div>
                    <h3>Demande enregistrée avec succès !</h3>
                    <p id="success-message-text"></p>

                    <div class="whatsapp-section">
                        <h4>💬 Besoin d'aide ? Contactez-nous sur WhatsApp</h4>
                        <div class="whatsapp-contacts" id="whatsapp-contacts"></div>
                    </div>

                    <button type="button" class="btn btn-primary btn-restart">
                        🔄 Nouvelle demande
                    </button>
                </div>

            </div>
        </div>

        <?php
        // v2.20.14: Récupérer les paramètres configurables depuis les options WordPress
        $cbm_price = intval(get_option('colis224_cbm_price_gnf', 4700000));

        // v2.20.14: Récupérer les URLs d'images depuis les options (vides si non configurées)
        $configured_images = array(
            'wechat_qr' => get_option('colis224_image_wechat_qr', ''),
            'orange_money_qr' => get_option('colis224_image_orange_money_qr', ''),
            'air_plane' => get_option('colis224_image_air_plane', ''),
            'sea_cargo' => get_option('colis224_image_sea_cargo', ''),
            'air_cargo_mark' => get_option('colis224_image_air_cargo_mark', ''),
            'sea_cargo_mark' => get_option('colis224_image_sea_cargo_mark', ''),
        );

        $frontend_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colis224_frontend_nonce'),
            'warehouse_phone_china' => '+8618719472926',
            'warehouse_phone_guinea' => '+224620178930',
            'agencies' => array(
                array('name' => 'Agence 1', 'phone' => '+224620178930'),
                array('name' => 'Agence 2 Lambanyi', 'phone' => '+224626526737'),
                array('name' => 'Agence 3', 'phone' => '+224626526735'),
                array('name' => 'Bureau France', 'phone' => '+33698485752'),
                array('name' => 'Agence USA', 'phone' => '+17185822079'),
            ),
            'orange_money_code' => '#144*6*649048*100000*code secret#OK',
            'orange_money_merchant' => 'COLIS224',
            // v2.20.14: Tarif CBM configurable
            'cbm_price' => $cbm_price,
            // v2.20.14: Images configurées (vides si non définies)
            'wechat_qr' => $configured_images['wechat_qr'],
            'orange_money_qr' => $configured_images['orange_money_qr'],
            'images' => array(
                'air_plane' => $configured_images['air_plane'],
                'sea_cargo' => $configured_images['sea_cargo'],
                'air_cargo_mark' => $configured_images['air_cargo_mark'],
                'sea_cargo_mark' => $configured_images['sea_cargo_mark'],
            ),
            'plugin_images_url' => COLIS224_PLUGIN_URL . 'assets/images/',
        );
        ?>
        <script type="text/javascript">
        // v2.20.14: Fusion des données avec colis224Frontend existant (wp_localize_script)
        (function() {
            var newData = <?php echo json_encode($frontend_data); ?>;
            if (typeof colis224Frontend !== 'undefined') {
                // Fusionner avec les données existantes (ajax_url, nonce, etc.)
                for (var key in newData) {
                    if (newData.hasOwnProperty(key)) {
                        colis224Frontend[key] = newData[key];
                    }
                }
            } else {
                // Créer l'objet s'il n'existe pas
                window.colis224Frontend = newData;
            }
            console.log('colis224Frontend initialisé (v2.20.14):', colis224Frontend);
            console.log('Tarif CBM:', colis224Frontend.cbm_price, 'GNF/m³');
            console.log('Ajax URL:', colis224Frontend.ajax_url);
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Submit request
     */
    public static function ajax_submit_request() {
        // Vérification du nonce avec gestion d'erreur
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_frontend_nonce')) {
            wp_send_json_error(array(
                'message' => 'Erreur de sécurité. Veuillez rafraîchir la page et réessayer.'
            ));
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . self::$table_name;

        // Vérifier que la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
        if (!$table_exists) {
            // Créer la table si elle n'existe pas
            self::create_table();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if (!$table_exists) {
                wp_send_json_error(array(
                    'message' => 'Erreur de configuration. Veuillez contacter le support.'
                ));
                return;
            }
        }

        // Vérifier les champs obligatoires
        if (empty($_POST['nom']) || empty($_POST['prenom']) || empty($_POST['telephone'])) {
            wp_send_json_error(array(
                'message' => 'Veuillez remplir tous les champs obligatoires (nom, prénom, téléphone).'
            ));
            return;
        }

        // Get form data with sanitization
        $data = array(
            'nom' => sanitize_text_field($_POST['nom']),
            'prenom' => sanitize_text_field($_POST['prenom']),
            'telephone' => sanitize_text_field($_POST['telephone']),
            'type_service' => isset($_POST['type_service']) ? sanitize_text_field($_POST['type_service']) : '',
            'origine' => isset($_POST['origine']) && $_POST['origine'] ? sanitize_text_field($_POST['origine']) : null,
            'mode_livraison' => isset($_POST['mode_livraison']) && $_POST['mode_livraison'] ? sanitize_text_field($_POST['mode_livraison']) : null,
            'destination' => isset($_POST['destination']) && $_POST['destination'] ? sanitize_text_field($_POST['destination']) : null,
            'shipping_mark' => isset($_POST['shipping_mark']) ? sanitize_textarea_field($_POST['shipping_mark']) : null,
            'pa_code' => isset($_POST['pa_code']) ? sanitize_text_field($_POST['pa_code']) : null,
            'quantites' => isset($_POST['quantites']) ? sanitize_textarea_field($_POST['quantites']) : null,
            'modeles' => isset($_POST['modeles']) ? sanitize_textarea_field($_POST['modeles']) : null,
            'delai' => isset($_POST['delai']) ? sanitize_text_field($_POST['delai']) : null,
            'panier_details' => isset($_POST['panier_details']) ? sanitize_textarea_field($_POST['panier_details']) : null,
            'site_achat' => isset($_POST['site_achat']) ? sanitize_text_field($_POST['site_achat']) : null,
            'agency' => isset($_POST['agency']) ? sanitize_text_field($_POST['agency']) : null,
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
        );

        // Detect country code and indicatif
        $phone_info = self::detect_country_from_phone($data['telephone']);
        $data['pays'] = $phone_info['country_code'];
        $data['indicatif'] = $phone_info['indicatif'];

        // v2.20.14: S'assurer que type_service n'est pas vide
        if (empty($data['type_service'])) {
            $data['type_service'] = 'cas2'; // Default to cas2 (envoi personnel)
        }

        // Nettoyer les valeurs NULL pour éviter les erreurs d'insertion
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        // Debug: Log les données avant insertion
        error_log('Colis224 Frontend Calculator - Data to insert: ' . print_r($data, true));

        // Insert into database
        $inserted = $wpdb->insert($table, $data);

        if ($inserted) {
            wp_send_json_success(array(
                'message' => 'Demande enregistrée avec succès',
                'id' => $wpdb->insert_id
            ));
        } else {
            // Log l'erreur pour debug
            $db_error = $wpdb->last_error;
            error_log('Colis224 Frontend Calculator - Insert error: ' . $db_error);
            error_log('Colis224 Frontend Calculator - Last query: ' . $wpdb->last_query);

            wp_send_json_error(array(
                'message' => 'Erreur lors de l\'enregistrement: ' . ($db_error ? $db_error : 'Erreur inconnue. Veuillez réessayer.'),
                'debug' => WP_DEBUG ? $db_error : null
            ));
        }
    }

    /**
     * Detect country from phone number
     */
    private static function detect_country_from_phone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        $countries = array(
            '+224' => array('code' => 'GN', 'name' => 'Guinée'),
            '+33' => array('code' => 'FR', 'name' => 'France'),
            '+1' => array('code' => 'US', 'name' => 'USA'),
            '+86' => array('code' => 'CN', 'name' => 'Chine'),
            '+221' => array('code' => 'SN', 'name' => 'Sénégal'),
            '+225' => array('code' => 'CI', 'name' => 'Côte d\'Ivoire'),
            '+212' => array('code' => 'MA', 'name' => 'Maroc'),
        );

        foreach ($countries as $indicatif => $info) {
            if (strpos($phone, $indicatif) === 0) {
                return array(
                    'indicatif' => $indicatif,
                    'country_code' => $info['code'],
                    'country_name' => $info['name']
                );
            }
        }

        // Default to Guinea if not detected
        return array(
            'indicatif' => '+224',
            'country_code' => 'GN',
            'country_name' => 'Guinée'
        );
    }

    /**
     * Get client IP address
     */
    private static function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field($ip);
    }

    /**
     * Generate PA code from phone number
     */
    public static function generate_pa_code($phone) {
        // Extract last 4 digits
        $digits = preg_replace('/[^0-9]/', '', $phone);
        $last4 = substr($digits, -4);
        return 'PA' . $last4;
    }
}

// Initialize
Colis224_Frontend_Calculator::init();
