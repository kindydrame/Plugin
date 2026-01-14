<?php
/**
 * Système de Paiement Mobile Money
 * Intégrations: Orange Money, MTN Money, Moov Money
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Mobile_Money {

    private $api_settings;

    public function __construct() {
        // Hooks pour les paiements
        add_action('wp_ajax_colis224_initiate_mobile_payment', array($this, 'ajax_initiate_payment'));
        add_action('wp_ajax_colis224_check_payment_status', array($this, 'ajax_check_payment_status'));

        // Webhook pour les confirmations de paiement
        add_action('init', array($this, 'register_webhook_endpoint'));
        add_action('template_redirect', array($this, 'handle_webhook'));

        // Charger les paramètres API
        $this->load_api_settings();
    }

    /**
     * Charger les paramètres API depuis les options
     */
    private function load_api_settings() {
        $this->api_settings = array(
            'orange_money' => array(
                'enabled' => get_option('colis224_orange_money_enabled', '0'),
                'merchant_id' => get_option('colis224_orange_money_merchant_id', ''),
                'api_key' => get_option('colis224_orange_money_api_key', ''),
                'api_secret' => get_option('colis224_orange_money_api_secret', ''),
                'environment' => get_option('colis224_orange_money_environment', 'sandbox'), // sandbox ou production
            ),
            'mtn_money' => array(
                'enabled' => get_option('colis224_mtn_money_enabled', '0'),
                'api_user' => get_option('colis224_mtn_money_api_user', ''),
                'api_key' => get_option('colis224_mtn_money_api_key', ''),
                'subscription_key' => get_option('colis224_mtn_money_subscription_key', ''),
                'environment' => get_option('colis224_mtn_money_environment', 'sandbox'),
            ),
            'moov_money' => array(
                'enabled' => get_option('colis224_moov_money_enabled', '0'),
                'merchant_id' => get_option('colis224_moov_money_merchant_id', ''),
                'api_key' => get_option('colis224_moov_money_api_key', ''),
                'environment' => get_option('colis224_moov_money_environment', 'sandbox'),
            )
        );
    }

    /**
     * Initier un paiement Mobile Money
     */
    public function initiate_payment($provider, $amount, $phone, $reference, $description = '') {
        // Valider les données
        if (empty($provider) || empty($amount) || empty($phone) || empty($reference)) {
            return array('success' => false, 'message' => 'Données invalides');
        }

        // Vérifier si le provider est activé
        if (!isset($this->api_settings[$provider]) || $this->api_settings[$provider]['enabled'] != '1') {
            return array('success' => false, 'message' => 'Ce moyen de paiement n\'est pas disponible');
        }

        // Appeler la méthode spécifique au provider
        switch ($provider) {
            case 'orange_money':
                return $this->initiate_orange_money_payment($amount, $phone, $reference, $description);

            case 'mtn_money':
                return $this->initiate_mtn_money_payment($amount, $phone, $reference, $description);

            case 'moov_money':
                return $this->initiate_moov_money_payment($amount, $phone, $reference, $description);

            default:
                return array('success' => false, 'message' => 'Provider inconnu');
        }
    }

    /**
     * Orange Money - Initier un paiement
     */
    private function initiate_orange_money_payment($amount, $phone, $reference, $description) {
        $settings = $this->api_settings['orange_money'];

        // URL de l'API selon l'environnement
        $base_url = ($settings['environment'] === 'production')
            ? 'https://api.orange.com/orange-money-webpay/gn/v1'
            : 'https://api.orange.com/orange-money-webpay/dev/v1';

        // Préparer les données de paiement
        $payment_data = array(
            'merchant_key' => $settings['merchant_id'],
            'currency' => 'GNF',
            'order_id' => $reference,
            'amount' => intval($amount),
            'return_url' => home_url('?colis224_payment_return=1'),
            'cancel_url' => home_url('?colis224_payment_cancel=1'),
            'notif_url' => home_url('?colis224_webhook=orange_money'),
            'lang' => 'fr',
            'reference' => $reference,
            'customer_phone' => $this->format_phone_number($phone, 'GN')
        );

        // Appel API
        $response = wp_remote_post($base_url . '/webpayment', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_orange_money_token(),
            ),
            'body' => json_encode($payment_data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => 'Erreur de connexion : ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['payment_url'])) {
            // Enregistrer la transaction
            $this->save_transaction($reference, 'orange_money', $amount, $phone, 'pending', $body);

            return array(
                'success' => true,
                'payment_url' => $body['payment_url'],
                'transaction_id' => $body['pay_token'] ?? $reference,
                'message' => 'Paiement initié avec succès'
            );
        }

        return array('success' => false, 'message' => 'Erreur lors de l\'initialisation du paiement');
    }

    /**
     * MTN Money - Initier un paiement
     */
    private function initiate_mtn_money_payment($amount, $phone, $reference, $description) {
        $settings = $this->api_settings['mtn_money'];

        // URL de l'API
        $base_url = ($settings['environment'] === 'production')
            ? 'https://proxy.momoapi.mtn.com'
            : 'https://sandbox.momodeveloper.mtn.com';

        // UUID pour la transaction
        $transaction_uuid = $this->generate_uuid();

        // Préparer les données
        $payment_data = array(
            'amount' => strval($amount),
            'currency' => 'GNF',
            'externalId' => $reference,
            'payer' => array(
                'partyIdType' => 'MSISDN',
                'partyId' => $this->format_phone_number($phone, 'GN', true)
            ),
            'payerMessage' => $description ?: 'Paiement Colis224',
            'payeeNote' => 'Paiement pour référence ' . $reference
        );

        // Appel API
        $response = wp_remote_post($base_url . '/collection/v1_0/requesttopay', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->get_mtn_money_token(),
                'X-Reference-Id' => $transaction_uuid,
                'X-Target-Environment' => $settings['environment'],
                'Ocp-Apim-Subscription-Key' => $settings['subscription_key']
            ),
            'body' => json_encode($payment_data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => 'Erreur de connexion : ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code == 202) {
            // Paiement accepté et en cours
            $this->save_transaction($reference, 'mtn_money', $amount, $phone, 'pending', array('transaction_id' => $transaction_uuid));

            return array(
                'success' => true,
                'transaction_id' => $transaction_uuid,
                'message' => 'Demande de paiement envoyée. Veuillez confirmer sur votre téléphone.',
                'requires_confirmation' => true
            );
        }

        return array('success' => false, 'message' => 'Erreur lors de l\'initialisation du paiement');
    }

    /**
     * Moov Money - Initier un paiement
     */
    private function initiate_moov_money_payment($amount, $phone, $reference, $description) {
        $settings = $this->api_settings['moov_money'];

        // URL de l'API
        $base_url = ($settings['environment'] === 'production')
            ? 'https://api.moov-africa.ci/v1'
            : 'https://sandbox.moov-africa.ci/v1';

        $payment_data = array(
            'amount' => $amount,
            'currency' => 'GNF',
            'reference' => $reference,
            'description' => $description ?: 'Paiement Colis224',
            'customer' => array(
                'phone' => $this->format_phone_number($phone, 'GN')
            ),
            'merchant_id' => $settings['merchant_id'],
            'callback_url' => home_url('?colis224_webhook=moov_money')
        );

        $response = wp_remote_post($base_url . '/payments/request', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $settings['api_key'],
            ),
            'body' => json_encode($payment_data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => 'Erreur de connexion : ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['status']) && $body['status'] === 'pending') {
            $this->save_transaction($reference, 'moov_money', $amount, $phone, 'pending', $body);

            return array(
                'success' => true,
                'transaction_id' => $body['transaction_id'] ?? $reference,
                'message' => 'Demande de paiement envoyée',
                'requires_confirmation' => true
            );
        }

        return array('success' => false, 'message' => 'Erreur lors de l\'initialisation du paiement');
    }

    /**
     * Enregistrer une transaction de paiement
     */
    private function save_transaction($reference, $provider, $amount, $phone, $status, $api_response) {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_mobile_money_transactions';

        $wpdb->insert($table, array(
            'reference' => $reference,
            'provider' => $provider,
            'amount' => $amount,
            'phone_number' => $phone,
            'status' => $status,
            'api_response' => json_encode($api_response),
            'created_at' => current_time('mysql')
        ));

        return $wpdb->insert_id;
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function check_payment_status($transaction_id, $provider) {
        switch ($provider) {
            case 'orange_money':
                return $this->check_orange_money_status($transaction_id);

            case 'mtn_money':
                return $this->check_mtn_money_status($transaction_id);

            case 'moov_money':
                return $this->check_moov_money_status($transaction_id);

            default:
                return array('success' => false, 'status' => 'unknown');
        }
    }

    /**
     * Formater un numéro de téléphone selon le pays
     */
    private function format_phone_number($phone, $country = 'GN', $international = false) {
        // Nettoyer le numéro
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Selon le pays
        if ($country === 'GN') {
            // Guinée : +224XXXXXXXXX
            if (strlen($phone) === 9) {
                return $international ? '224' . $phone : '+224' . $phone;
            } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === '224') {
                return $international ? $phone : '+' . $phone;
            }
        }

        return $phone;
    }

    /**
     * Générer un UUID v4
     */
    private function generate_uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Obtenir le token Orange Money (à implémenter selon la doc officielle)
     */
    private function get_orange_money_token() {
        // Cache le token pendant 1 heure
        $cached_token = get_transient('colis224_orange_money_token');
        if ($cached_token) {
            return $cached_token;
        }

        $settings = $this->api_settings['orange_money'];

        // TODO: Implémenter l'authentification OAuth selon la documentation Orange
        // Pour le moment, retourner l'API key
        return $settings['api_key'];
    }

    /**
     * Obtenir le token MTN Money
     */
    private function get_mtn_money_token() {
        $cached_token = get_transient('colis224_mtn_money_token');
        if ($cached_token) {
            return $cached_token;
        }

        // TODO: Implémenter l'authentification selon la documentation MTN
        $settings = $this->api_settings['mtn_money'];
        return $settings['api_key'];
    }

    /**
     * Enregistrer le endpoint webhook
     */
    public function register_webhook_endpoint() {
        add_rewrite_rule('^colis224-webhook/([^/]*)/?', 'index.php?colis224_webhook=$matches[1]', 'top');
        add_rewrite_tag('%colis224_webhook%', '([^&]+)');
    }

    /**
     * Gérer les webhooks de confirmation de paiement
     */
    public function handle_webhook() {
        $provider = get_query_var('colis224_webhook');

        if (!$provider) {
            return;
        }

        // Lire le contenu du webhook
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        // Logger le webhook
        error_log('Colis224 Webhook ' . $provider . ': ' . $payload);

        // Traiter selon le provider
        switch ($provider) {
            case 'orange_money':
                $this->process_orange_money_webhook($data);
                break;

            case 'mtn_money':
                $this->process_mtn_money_webhook($data);
                break;

            case 'moov_money':
                $this->process_moov_money_webhook($data);
                break;
        }

        // Répondre OK
        status_header(200);
        echo 'OK';
        exit;
    }

    /**
     * Traiter le webhook Orange Money
     */
    private function process_orange_money_webhook($data) {
        if (!isset($data['order_id']) || !isset($data['status'])) {
            return;
        }

        $reference = $data['order_id'];
        $status = strtolower($data['status']);

        if ($status === 'success' || $status === 'succeeded') {
            $this->update_payment_status($reference, 'orange_money', 'completed');
        } elseif ($status === 'failed' || $status === 'cancelled') {
            $this->update_payment_status($reference, 'orange_money', 'failed');
        }
    }

    /**
     * Mettre à jour le statut d'un paiement
     */
    private function update_payment_status($reference, $provider, $status) {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_mobile_money_transactions';

        $wpdb->update(
            $table,
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('reference' => $reference, 'provider' => $provider)
        );

        // Si le paiement est complété, mettre à jour le colis
        if ($status === 'completed') {
            $this->mark_parcel_as_paid($reference);
        }
    }

    /**
     * Marquer un colis comme payé
     */
    private function mark_parcel_as_paid($tracking_number) {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_parcels';

        $wpdb->update(
            $table,
            array(
                'payment_status' => 'Payé',
                'paid_amount' => $wpdb->get_var($wpdb->prepare("SELECT total_amount FROM $table WHERE tracking_number = %s", $tracking_number)),
                'remaining_amount' => 0
            ),
            array('tracking_number' => $tracking_number)
        );
    }

    /**
     * AJAX: Initier un paiement
     */
    public function ajax_initiate_payment() {
        check_ajax_referer('colis224_nonce', 'nonce');

        $provider = sanitize_text_field($_POST['provider']);
        $amount = floatval($_POST['amount']);
        $phone = sanitize_text_field($_POST['phone']);
        $reference = sanitize_text_field($_POST['reference']);
        $description = sanitize_text_field($_POST['description'] ?? '');

        $result = $this->initiate_payment($provider, $amount, $phone, $reference, $description);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Vérifier le statut
     */
    public function ajax_check_payment_status() {
        check_ajax_referer('colis224_nonce', 'nonce');

        $transaction_id = sanitize_text_field($_POST['transaction_id']);
        $provider = sanitize_text_field($_POST['provider']);

        $result = $this->check_payment_status($transaction_id, $provider);
        wp_send_json_success($result);
    }

    // Méthodes de vérification de statut à implémenter
    private function check_orange_money_status($transaction_id) {
        return array('success' => true, 'status' => 'pending');
    }

    private function check_mtn_money_status($transaction_id) {
        return array('success' => true, 'status' => 'pending');
    }

    private function check_moov_money_status($transaction_id) {
        return array('success' => true, 'status' => 'pending');
    }

    private function process_mtn_money_webhook($data) {
        // À implémenter
    }

    private function process_moov_money_webhook($data) {
        // À implémenter
    }
}

// Initialiser
new Colis224_Mobile_Money();
