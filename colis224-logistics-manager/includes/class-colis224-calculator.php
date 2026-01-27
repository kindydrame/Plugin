<?php
/**
 * Calculateur de Tarif Frontend - Colis224
 *
 * Permet aux clients de calculer le tarif de leurs colis
 * et d'enregistrer leurs informations pour un devis
 *
 * @package Colis224
 * @subpackage Calculator
 * @since 2.17.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Calculator {

    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Obtenir l'instance unique
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    public function __construct() {
        // Shortcode pour le formulaire de calcul
        add_shortcode('colis224_calculator', array($this, 'render_calculator_form'));

        // Actions AJAX
        add_action('wp_ajax_colis224_calculate_rate', array($this, 'ajax_calculate_rate'));
        add_action('wp_ajax_nopriv_colis224_calculate_rate', array($this, 'ajax_calculate_rate'));

        add_action('wp_ajax_colis224_save_quote', array($this, 'ajax_save_quote'));
        add_action('wp_ajax_nopriv_colis224_save_quote', array($this, 'ajax_save_quote'));

        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_calculator_assets'));
    }

    /**
     * Charger les assets du calculateur
     */
    public function enqueue_calculator_assets() {
        // Vérifier si on est sur une page avec le shortcode
        global $post;
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'colis224_calculator') || is_page())) {

            wp_enqueue_style(
                'colis224-calculator',
                COLIS224_PLUGIN_URL . 'assets/css/calculator-style.css',
                array(),
                COLIS224_VERSION
            );

            wp_enqueue_script(
                'colis224-calculator',
                COLIS224_PLUGIN_URL . 'assets/js/frontend-calculator.js',
                array('jquery'),
                COLIS224_VERSION,
                true
            );

            wp_localize_script('colis224-calculator', 'colis224Calculator', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('colis224_calculator_nonce'),
                'currency' => 'GNF',
                'messages' => array(
                    'calculating' => 'Calcul en cours...',
                    'saving' => 'Enregistrement...',
                    'success' => 'Devis enregistré avec succès !',
                    'error' => 'Une erreur est survenue. Veuillez réessayer.',
                    'required' => 'Veuillez remplir tous les champs obligatoires.',
                    'invalid_weight' => 'Le poids doit être supérieur à 0.',
                    'invalid_phone' => 'Veuillez entrer un numéro de téléphone valide.'
                )
            ));
        }
    }

    /**
     * Afficher le formulaire de calcul de tarif
     */
    public function render_calculator_form($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Calculez le tarif de votre colis',
            'show_wechat' => 'true',
            'show_instructions' => 'true'
        ), $atts);

        // Récupérer les pays et modes de transport
        global $wpdb;
        $countries = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}colis224_countries ORDER BY name ASC");
        $transport_modes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}colis224_transport_modes ORDER BY name ASC");
        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}colis224_parcel_categories ORDER BY name ASC");

        ob_start();
        ?>
        <div class="colis224-calculator-container">
            <div class="colis224-calculator-header">
                <h2><?php echo esc_html($atts['title']); ?></h2>
                <p>Remplissez le formulaire ci-dessous pour obtenir une estimation du tarif de votre envoi</p>
            </div>

            <form id="colis224-calculator-form" class="colis224-calculator-form">
                <?php wp_nonce_field('colis224_calculator_nonce', 'calculator_nonce'); ?>

                <!-- Section Expéditeur -->
                <div class="colis224-form-section">
                    <h3>Informations de l'expéditeur</h3>

                    <div class="colis224-form-row">
                        <div class="colis224-form-group">
                            <label for="sender_name">Nom complet <span class="required">*</span></label>
                            <input type="text" id="sender_name" name="sender_name" required
                                   placeholder="Votre nom complet">
                        </div>

                        <div class="colis224-form-group">
                            <label for="sender_phone">Téléphone <span class="required">*</span></label>
                            <input type="tel" id="sender_phone" name="sender_phone" required
                                   placeholder="+224 XXX XXX XXX">
                        </div>
                    </div>

                    <div class="colis224-form-row">
                        <div class="colis224-form-group">
                            <label for="sender_email">Email</label>
                            <input type="email" id="sender_email" name="sender_email"
                                   placeholder="votre@email.com">
                        </div>

                        <div class="colis224-form-group">
                            <label for="sender_address">Adresse</label>
                            <input type="text" id="sender_address" name="sender_address"
                                   placeholder="Votre adresse">
                        </div>
                    </div>
                </div>

                <!-- Section Destinataire -->
                <div class="colis224-form-section">
                    <h3>Informations du destinataire</h3>

                    <div class="colis224-form-row">
                        <div class="colis224-form-group">
                            <label for="recipient_name">Nom complet <span class="required">*</span></label>
                            <input type="text" id="recipient_name" name="recipient_name" required
                                   placeholder="Nom du destinataire">
                        </div>

                        <div class="colis224-form-group">
                            <label for="recipient_phone">Téléphone <span class="required">*</span></label>
                            <input type="tel" id="recipient_phone" name="recipient_phone" required
                                   placeholder="+224 XXX XXX XXX">
                        </div>
                    </div>

                    <div class="colis224-form-row">
                        <div class="colis224-form-group">
                            <label for="recipient_address">Adresse de livraison</label>
                            <input type="text" id="recipient_address" name="recipient_address"
                                   placeholder="Adresse de livraison">
                        </div>

                        <div class="colis224-form-group">
                            <label for="destination_country">Pays de destination <span class="required">*</span></label>
                            <select id="destination_country" name="destination_country" required>
                                <option value="">Sélectionnez un pays</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo esc_attr($country->id); ?>">
                                        <?php echo esc_html($country->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section Colis -->
                <div class="colis224-form-section">
                    <h3>Détails du colis</h3>

                    <div class="colis224-form-row">
                        <div class="colis224-form-group">
                            <label for="origin_country">Pays d'origine <span class="required">*</span></label>
                            <select id="origin_country" name="origin_country" required>
                                <option value="">Sélectionnez un pays</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo esc_attr($country->id); ?>"
                                        <?php echo ($country->code === 'GN') ? 'selected' : ''; ?>>
                                        <?php echo esc_html($country->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="colis224-form-group">
                            <label for="transport_mode">Mode de transport <span class="required">*</span></label>
                            <select id="transport_mode" name="transport_mode" required>
                                <option value="">Sélectionnez un mode</option>
                                <?php foreach ($transport_modes as $mode): ?>
                                    <option value="<?php echo esc_attr($mode->id); ?>"
                                            data-days="<?php echo esc_attr($mode->estimated_days); ?>">
                                        <?php echo esc_html($mode->name); ?>
                                        (<?php echo esc_html($mode->estimated_days); ?> jours)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="colis224-form-row">
                        <div class="colis224-form-group">
                            <label for="category">Catégorie <span class="required">*</span></label>
                            <select id="category" name="category" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->id); ?>">
                                        <?php echo esc_html($cat->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="colis224-form-group">
                            <label for="weight">Poids (kg) <span class="required">*</span></label>
                            <input type="number" id="weight" name="weight" required
                                   min="0.1" step="0.1" placeholder="Ex: 5.5">
                        </div>
                    </div>

                    <div class="colis224-form-row">
                        <div class="colis224-form-group">
                            <label for="description">Description du contenu</label>
                            <textarea id="description" name="description" rows="3"
                                      placeholder="Décrivez le contenu de votre colis..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="colis224-form-actions">
                    <button type="button" id="btn-calculate" class="colis224-btn colis224-btn-primary">
                        Calculer le tarif
                    </button>
                    <button type="submit" id="btn-save-quote" class="colis224-btn colis224-btn-success" disabled>
                        Enregistrer le devis
                    </button>
                </div>

                <!-- Résultat du calcul -->
                <div id="colis224-calculator-result" class="colis224-calculator-result" style="display: none;">
                    <div class="result-header">
                        <h3>Estimation du tarif</h3>
                    </div>
                    <div class="result-body">
                        <div class="result-item">
                            <span class="label">Tarif de base:</span>
                            <span class="value" id="result-base-rate">-</span>
                        </div>
                        <div class="result-item">
                            <span class="label">Frais supplémentaires:</span>
                            <span class="value" id="result-extra-fees">-</span>
                        </div>
                        <div class="result-item result-total">
                            <span class="label">Total estimé:</span>
                            <span class="value" id="result-total">-</span>
                        </div>
                        <div class="result-item">
                            <span class="label">Délai estimé:</span>
                            <span class="value" id="result-delivery-time">-</span>
                        </div>
                    </div>
                    <p class="result-note">
                        * Ce tarif est une estimation. Le prix final peut varier selon les dimensions et la nature du colis.
                    </p>
                </div>

                <!-- Message de statut -->
                <div id="colis224-calculator-message" class="colis224-calculator-message"></div>
            </form>

            <?php if ($atts['show_wechat'] === 'true'): ?>
            <!-- Section Paiement WeChat -->
            <div class="colis224-wechat-section">
                <h3>Paiement WeChat disponible</h3>
                <p>Pour payer vos frais de transport en Chine, scannez notre QR Code WeChat</p>
                <div class="wechat-qr-container">
                    <?php
                    $wechat_qr = get_option('colis224_wechat_qr_url', '');
                    if ($wechat_qr): ?>
                        <img src="<?php echo esc_url($wechat_qr); ?>" alt="QR Code WeChat" class="wechat-qr-image">
                    <?php else: ?>
                        <div class="wechat-qr-placeholder">
                            <p>QR Code WeChat non configuré</p>
                        </div>
                    <?php endif; ?>
                </div>
                <p class="wechat-note">Idéal pour les paiements en Chine</p>
            </div>
            <?php endif; ?>

            <?php if ($atts['show_instructions'] === 'true'): ?>
            <!-- Section Instructions -->
            <div class="colis224-instructions-section">
                <h3>Instructions visuelles pour le fournisseur</h3>
                <div class="instructions-container">
                    <?php
                    $instruction_images = get_option('colis224_instruction_images', array());
                    if (!empty($instruction_images)): ?>
                        <div class="instructions-gallery">
                            <?php foreach ($instruction_images as $image): ?>
                                <div class="instruction-image">
                                    <img src="<?php echo esc_url($image['url']); ?>"
                                         alt="<?php echo esc_attr($image['title'] ?? 'Instruction'); ?>">
                                    <?php if (!empty($image['caption'])): ?>
                                        <p class="caption"><?php echo esc_html($image['caption']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="instructions-placeholder">
                            <p>Images des instructions à venir</p>
                            <p>Les images d'exemple pour l'adresse et le shipping mark seront bientôt disponibles.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Calculer le tarif
     */
    public function ajax_calculate_rate() {
        // Vérifier le nonce
        if (!check_ajax_referer('colis224_calculator_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Erreur de sécurité. Veuillez rafraîchir la page.'));
        }

        // Récupérer les données
        $weight = floatval($_POST['weight'] ?? 0);
        $origin_country = intval($_POST['origin_country'] ?? 0);
        $destination_country = intval($_POST['destination_country'] ?? 0);
        $transport_mode = intval($_POST['transport_mode'] ?? 0);
        $category = intval($_POST['category'] ?? 0);

        // Validation
        if ($weight <= 0) {
            wp_send_json_error(array('message' => 'Le poids doit être supérieur à 0.'));
        }

        if (!$origin_country || !$destination_country || !$transport_mode) {
            wp_send_json_error(array('message' => 'Veuillez remplir tous les champs obligatoires.'));
        }

        // Calculer le tarif
        $rate = $this->calculate_shipping_rate($weight, $origin_country, $destination_country, $transport_mode, $category);

        if (is_wp_error($rate)) {
            wp_send_json_error(array('message' => $rate->get_error_message()));
        }

        wp_send_json_success($rate);
    }

    /**
     * Calculer le tarif d'expédition
     */
    private function calculate_shipping_rate($weight, $origin_id, $destination_id, $transport_id, $category_id) {
        global $wpdb;

        // Récupérer les informations du mode de transport
        $transport = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_transport_modes WHERE id = %d",
            $transport_id
        ));

        if (!$transport) {
            return new WP_Error('invalid_transport', 'Mode de transport invalide.');
        }

        // Récupérer les pays
        $origin = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_countries WHERE id = %d",
            $origin_id
        ));

        $destination = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_countries WHERE id = %d",
            $destination_id
        ));

        // Tarifs de base par kg (configurable via options)
        $rates = get_option('colis224_shipping_rates', array(
            'avion' => 15000,    // GNF par kg
            'bateau' => 8000,   // GNF par kg
            'express' => 25000  // GNF par kg
        ));

        // Déterminer le tarif selon le mode de transport
        $rate_key = strtolower($transport->name);
        $base_rate_per_kg = $rates[$rate_key] ?? 10000;

        // Calculer le tarif de base
        $base_rate = $weight * $base_rate_per_kg;

        // Frais supplémentaires selon la destination
        $extra_fees = 0;

        // Surcharge pour destinations internationales
        if ($destination && $destination->code !== 'GN') {
            $international_surcharge = get_option('colis224_international_surcharge', 0.2); // 20%
            $extra_fees = $base_rate * $international_surcharge;
        }

        // Surcharge pour catégories spéciales (électronique, fragile, etc.)
        $special_categories = array(2, 7); // IDs des catégories avec surcharge
        if (in_array($category_id, $special_categories)) {
            $category_surcharge = get_option('colis224_category_surcharge', 0.1); // 10%
            $extra_fees += $base_rate * $category_surcharge;
        }

        $total = $base_rate + $extra_fees;

        return array(
            'base_rate' => $base_rate,
            'extra_fees' => $extra_fees,
            'total' => $total,
            'currency' => 'GNF',
            'delivery_days' => $transport->estimated_days,
            'transport_mode' => $transport->name,
            'weight' => $weight,
            'formatted' => array(
                'base_rate' => number_format($base_rate, 0, ',', ' ') . ' GNF',
                'extra_fees' => number_format($extra_fees, 0, ',', ' ') . ' GNF',
                'total' => number_format($total, 0, ',', ' ') . ' GNF',
                'delivery_time' => $transport->estimated_days . ' jours'
            )
        );
    }

    /**
     * AJAX: Enregistrer le devis
     */
    public function ajax_save_quote() {
        // Vérifier le nonce
        if (!check_ajax_referer('colis224_calculator_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Erreur de sécurité. Veuillez rafraîchir la page.'));
        }

        global $wpdb;

        // Récupérer et valider les données
        $sender_name = sanitize_text_field($_POST['sender_name'] ?? '');
        $sender_phone = sanitize_text_field($_POST['sender_phone'] ?? '');
        $sender_email = sanitize_email($_POST['sender_email'] ?? '');
        $sender_address = sanitize_text_field($_POST['sender_address'] ?? '');

        $recipient_name = sanitize_text_field($_POST['recipient_name'] ?? '');
        $recipient_phone = sanitize_text_field($_POST['recipient_phone'] ?? '');
        $recipient_address = sanitize_text_field($_POST['recipient_address'] ?? '');

        $origin_country = intval($_POST['origin_country'] ?? 0);
        $destination_country = intval($_POST['destination_country'] ?? 0);
        $transport_mode = intval($_POST['transport_mode'] ?? 0);
        $category = intval($_POST['category'] ?? 0);
        $weight = floatval($_POST['weight'] ?? 0);
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        $estimated_total = floatval($_POST['estimated_total'] ?? 0);

        // Validation
        if (empty($sender_name) || empty($sender_phone)) {
            wp_send_json_error(array('message' => 'Les informations de l\'expéditeur sont obligatoires.'));
        }

        if (empty($recipient_name) || empty($recipient_phone)) {
            wp_send_json_error(array('message' => 'Les informations du destinataire sont obligatoires.'));
        }

        if ($weight <= 0) {
            wp_send_json_error(array('message' => 'Le poids doit être supérieur à 0.'));
        }

        // Vérifier/Créer le client expéditeur
        $client_id = $this->get_or_create_client($sender_name, $sender_phone, $sender_email, $sender_address);

        if (!$client_id) {
            wp_send_json_error(array('message' => 'Erreur lors de l\'enregistrement du client.'));
        }

        // Générer un numéro de suivi temporaire pour le devis
        $quote_number = 'DV' . date('Ymd') . strtoupper(substr(uniqid(), -4));

        // Enregistrer le devis dans la table des colis avec statut "En attente"
        // Note: Le numéro commence par "DV" pour identifier les devis
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'colis224_parcels',
            array(
                'tracking_number' => $quote_number,
                'client_id' => $client_id,
                'recipient_name' => $recipient_name,
                'recipient_phone' => $recipient_phone,
                'recipient_address' => $recipient_address,
                'origin_country_id' => $origin_country,
                'destination_country_id' => $destination_country,
                'transport_mode_id' => $transport_mode,
                'category_id' => $category,
                'weight' => $weight,
                'notes' => '[DEVIS] ' . $description,
                'total_amount' => $estimated_total,
                'status' => 'En attente',
                'payment_status' => 'Non payé',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%f', '%s', '%f', '%s', '%s', '%s', '%s')
        );

        if (!$inserted) {
            wp_send_json_error(array('message' => 'Erreur lors de l\'enregistrement du devis.'));
        }

        $quote_id = $wpdb->insert_id;

        // Envoyer une notification (optionnel)
        $this->send_quote_notification($quote_id, $quote_number, $sender_name, $sender_phone, $sender_email);

        wp_send_json_success(array(
            'message' => 'Votre devis a été enregistré avec succès !',
            'quote_number' => $quote_number,
            'quote_id' => $quote_id
        ));
    }

    /**
     * Obtenir ou créer un client
     */
    private function get_or_create_client($name, $phone, $email = '', $address = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_clients';

        // Chercher un client existant par téléphone
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE phone = %s",
            $phone
        ));

        if ($existing) {
            // Mettre à jour les informations si nécessaire
            $wpdb->update(
                $table,
                array(
                    'name' => $name,
                    'email' => $email,
                    'address' => $address,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing),
                array('%s', '%s', '%s', '%s'),
                array('%d')
            );
            return $existing;
        }

        // Créer un nouveau client
        $inserted = $wpdb->insert(
            $table,
            array(
                'type' => 'particulier',
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
                'balance' => 0,
                'discount_rate' => 0,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s')
        );

        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Envoyer une notification de devis
     */
    private function send_quote_notification($quote_id, $quote_number, $sender_name, $sender_phone, $sender_email) {
        // Notification admin par email
        $admin_email = get_option('admin_email');
        $subject = sprintf('[Colis224] Nouveau devis #%s', $quote_number);
        $message = sprintf(
            "Un nouveau devis a été demandé:\n\n" .
            "Numéro: %s\n" .
            "Client: %s\n" .
            "Téléphone: %s\n" .
            "Email: %s\n\n" .
            "Connectez-vous à l'administration pour voir les détails.",
            $quote_number,
            $sender_name,
            $sender_phone,
            $sender_email ?: 'Non fourni'
        );

        wp_mail($admin_email, $subject, $message);

        // Notification client si email fourni
        if ($sender_email) {
            $client_subject = sprintf('Votre devis Colis224 #%s', $quote_number);
            $client_message = sprintf(
                "Bonjour %s,\n\n" .
                "Merci pour votre demande de devis.\n\n" .
                "Votre numéro de référence: %s\n\n" .
                "Notre équipe va traiter votre demande et vous contacter très prochainement.\n\n" .
                "Cordialement,\n" .
                "L'équipe Colis224",
                $sender_name,
                $quote_number
            );

            wp_mail($sender_email, $client_subject, $client_message);
        }
    }
}

// Initialiser
Colis224_Calculator::get_instance();
