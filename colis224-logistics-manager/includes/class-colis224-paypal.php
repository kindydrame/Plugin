<?php
/**
 * MODULE 19: Intégration PayPal
 * Gestion des paiements via PayPal
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_PayPal {

    private $client_id;
    private $client_secret;
    private $mode; // 'sandbox' ou 'live'
    private $api_base;

    public function __construct() {
        $this->client_id = get_option('colis224_paypal_client_id', '');
        $this->client_secret = get_option('colis224_paypal_client_secret', '');
        $this->mode = get_option('colis224_paypal_mode', 'sandbox');

        $this->api_base = $this->mode === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';

        // Hooks AJAX
        add_action('wp_ajax_colis224_create_paypal_order', array($this, 'ajax_create_order'));
        add_action('wp_ajax_nopriv_colis224_create_paypal_order', array($this, 'ajax_create_order'));
        add_action('wp_ajax_colis224_capture_paypal_order', array($this, 'ajax_capture_order'));
        add_action('wp_ajax_nopriv_colis224_capture_paypal_order', array($this, 'ajax_capture_order'));

        // Hook pour le webhook PayPal
        add_action('rest_api_init', array($this, 'register_webhook_endpoint'));
    }

    /**
     * Créer les tables pour les transactions PayPal
     */
    public static function create_paypal_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table des transactions PayPal
        $table_paypal = $wpdb->prefix . 'colis224_paypal_transactions';
        $sql_paypal = "CREATE TABLE $table_paypal (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id varchar(100) NOT NULL,
            parcel_id bigint(20) UNSIGNED DEFAULT NULL,
            payer_email varchar(255) DEFAULT NULL,
            payer_name varchar(255) DEFAULT NULL,
            amount decimal(15,2) NOT NULL,
            currency varchar(10) DEFAULT 'EUR',
            status enum('created','approved','captured','completed','cancelled','failed') DEFAULT 'created',
            paypal_order_id varchar(100) DEFAULT NULL,
            paypal_capture_id varchar(100) DEFAULT NULL,
            api_response text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY order_id (order_id),
            KEY parcel_id (parcel_id),
            KEY status (status),
            KEY paypal_order_id (paypal_order_id)
        ) $charset_collate;";
        dbDelta($sql_paypal);
    }

    /**
     * Obtenir le token d'accès PayPal
     */
    private function get_access_token() {
        if (empty($this->client_id) || empty($this->client_secret)) {
            return false;
        }

        $response = wp_remote_post($this->api_base . '/v1/oauth2/token', array(
            'headers' => array(
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
            ),
            'body' => array(
                'grant_type' => 'client_credentials'
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('PayPal Token Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {
            return $body['access_token'];
        }

        return false;
    }

    /**
     * Créer une commande PayPal
     */
    public function create_order($amount, $currency = 'EUR', $description = '', $parcel_id = null) {
        $access_token = $this->get_access_token();

        if (!$access_token) {
            return array(
                'success' => false,
                'message' => 'Impossible de se connecter à PayPal. Vérifiez vos identifiants.'
            );
        }

        // Générer un ID de commande unique
        $order_id = 'COLIS224-' . strtoupper(wp_generate_password(12, false));

        $order_data = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'reference_id' => $order_id,
                    'description' => !empty($description) ? $description : 'Paiement Colis224',
                    'amount' => array(
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', '')
                    )
                )
            ),
            'application_context' => array(
                'brand_name' => get_option('colis224_company_name', 'Colis224'),
                'locale' => 'fr-FR',
                'landing_page' => 'BILLING',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
                'return_url' => add_query_arg('paypal_success', '1', home_url()),
                'cancel_url' => add_query_arg('paypal_cancel', '1', home_url())
            )
        );

        $response = wp_remote_post($this->api_base . '/v2/checkout/orders', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ),
            'body' => json_encode($order_data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('PayPal Create Order Error: ' . $response->get_error_message());
            return array(
                'success' => false,
                'message' => 'Erreur lors de la création de la commande PayPal.'
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['id'])) {
            // Enregistrer la transaction
            global $wpdb;
            $table = $wpdb->prefix . 'colis224_paypal_transactions';

            $wpdb->insert($table, array(
                'order_id' => $order_id,
                'parcel_id' => $parcel_id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'created',
                'paypal_order_id' => $body['id'],
                'api_response' => json_encode($body)
            ));

            // Trouver le lien d'approbation
            $approve_link = '';
            if (isset($body['links'])) {
                foreach ($body['links'] as $link) {
                    if ($link['rel'] === 'approve') {
                        $approve_link = $link['href'];
                        break;
                    }
                }
            }

            return array(
                'success' => true,
                'order_id' => $body['id'],
                'approve_link' => $approve_link,
                'internal_order_id' => $order_id
            );
        }

        return array(
            'success' => false,
            'message' => 'Erreur PayPal: ' . (isset($body['message']) ? $body['message'] : 'Erreur inconnue')
        );
    }

    /**
     * Capturer une commande PayPal (après approbation)
     */
    public function capture_order($paypal_order_id) {
        $access_token = $this->get_access_token();

        if (!$access_token) {
            return array(
                'success' => false,
                'message' => 'Impossible de se connecter à PayPal.'
            );
        }

        $response = wp_remote_post($this->api_base . '/v2/checkout/orders/' . $paypal_order_id . '/capture', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('PayPal Capture Error: ' . $response->get_error_message());
            return array(
                'success' => false,
                'message' => 'Erreur lors de la capture du paiement.'
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['status']) && $body['status'] === 'COMPLETED') {
            // Mettre à jour la transaction
            global $wpdb;
            $table = $wpdb->prefix . 'colis224_paypal_transactions';

            $capture_id = isset($body['purchase_units'][0]['payments']['captures'][0]['id'])
                ? $body['purchase_units'][0]['payments']['captures'][0]['id']
                : '';

            $payer_email = isset($body['payer']['email_address']) ? $body['payer']['email_address'] : '';
            $payer_name = isset($body['payer']['name']['given_name'])
                ? $body['payer']['name']['given_name'] . ' ' . $body['payer']['name']['surname']
                : '';

            $wpdb->update($table,
                array(
                    'status' => 'completed',
                    'paypal_capture_id' => $capture_id,
                    'payer_email' => $payer_email,
                    'payer_name' => $payer_name,
                    'api_response' => json_encode($body)
                ),
                array('paypal_order_id' => $paypal_order_id)
            );

            // Récupérer la transaction pour obtenir le parcel_id
            $transaction = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE paypal_order_id = %s",
                $paypal_order_id
            ));

            // Mettre à jour le statut de paiement du colis si applicable
            if ($transaction && $transaction->parcel_id) {
                $this->update_parcel_payment($transaction->parcel_id, $transaction->amount);
            }

            return array(
                'success' => true,
                'message' => 'Paiement capturé avec succès',
                'capture_id' => $capture_id,
                'transaction' => $transaction
            );
        }

        return array(
            'success' => false,
            'message' => 'Erreur lors de la capture: ' . (isset($body['message']) ? $body['message'] : 'Erreur inconnue')
        );
    }

    /**
     * Mettre à jour le paiement d'un colis après succès PayPal
     */
    private function update_parcel_payment($parcel_id, $amount) {
        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_payments = $wpdb->prefix . 'colis224_payments';

        // Récupérer le colis
        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_parcels WHERE id = %d",
            $parcel_id
        ));

        if (!$parcel) {
            return;
        }

        // Enregistrer le paiement
        $wpdb->insert($table_payments, array(
            'reference_type' => 'parcel',
            'reference_id' => $parcel_id,
            'amount' => $amount,
            'currency' => 'EUR',
            'payment_method' => 'PayPal',
            'payment_date' => current_time('mysql', false),
            'notes' => 'Paiement PayPal'
        ));

        // Calculer le nouveau montant payé
        $new_paid_amount = $parcel->paid_amount + $amount;
        $new_remaining = $parcel->total_amount - $new_paid_amount;

        // Déterminer le statut de paiement
        $payment_status = 'Non payé';
        if ($new_remaining <= 0) {
            $payment_status = 'Payé';
        } elseif ($new_paid_amount > 0) {
            $payment_status = 'Partiel';
        }

        // Mettre à jour le colis
        $wpdb->update($table_parcels,
            array(
                'paid_amount' => $new_paid_amount,
                'remaining_amount' => max(0, $new_remaining),
                'payment_status' => $payment_status
            ),
            array('id' => $parcel_id)
        );

        // Envoyer notification
        do_action('colis224_payment_received', $parcel_id, $amount, 'PayPal');
    }

    /**
     * Obtenir toutes les transactions PayPal
     */
    public static function get_all_transactions($status = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_paypal_transactions';

        $where = $status ? $wpdb->prepare('WHERE status = %s', $status) : '';

        return $wpdb->get_results(
            "SELECT * FROM $table $where ORDER BY created_at DESC"
        );
    }

    /**
     * Obtenir une transaction par ID de commande PayPal
     */
    public static function get_transaction($paypal_order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_paypal_transactions';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE paypal_order_id = %s",
            $paypal_order_id
        ));
    }

    /**
     * AJAX: Créer une commande PayPal
     */
    public function ajax_create_order() {
        check_ajax_referer('colis224_frontend_nonce', 'nonce');

        $amount = floatval($_POST['amount']);
        $parcel_id = isset($_POST['parcel_id']) ? intval($_POST['parcel_id']) : null;
        $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';

        $result = $this->create_order($amount, 'EUR', $description, $parcel_id);

        wp_send_json($result);
    }

    /**
     * AJAX: Capturer une commande PayPal
     */
    public function ajax_capture_order() {
        check_ajax_referer('colis224_frontend_nonce', 'nonce');

        $paypal_order_id = sanitize_text_field($_POST['order_id']);

        $result = $this->capture_order($paypal_order_id);

        wp_send_json($result);
    }

    /**
     * Enregistrer l'endpoint webhook pour PayPal
     */
    public function register_webhook_endpoint() {
        register_rest_route('colis224/v1', '/paypal-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Gérer le webhook PayPal
     */
    public function handle_webhook($request) {
        $body = $request->get_body();
        $data = json_decode($body, true);

        // Log du webhook
        error_log('PayPal Webhook: ' . print_r($data, true));

        if (!isset($data['event_type'])) {
            return new WP_REST_Response(array('error' => 'Invalid webhook'), 400);
        }

        // Traiter selon le type d'événement
        switch ($data['event_type']) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                if (isset($data['resource']['id'])) {
                    $this->handle_capture_completed($data['resource']);
                }
                break;

            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.REFUNDED':
                if (isset($data['resource']['id'])) {
                    $this->handle_capture_failed($data['resource']);
                }
                break;
        }

        return new WP_REST_Response(array('success' => true), 200);
    }

    /**
     * Gérer la capture complétée via webhook
     */
    private function handle_capture_completed($resource) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_paypal_transactions';

        $capture_id = $resource['id'];

        $wpdb->update($table,
            array('status' => 'completed'),
            array('paypal_capture_id' => $capture_id)
        );
    }

    /**
     * Gérer l'échec de capture via webhook
     */
    private function handle_capture_failed($resource) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_paypal_transactions';

        $capture_id = $resource['id'];

        $wpdb->update($table,
            array('status' => 'failed'),
            array('paypal_capture_id' => $capture_id)
        );
    }
}

// Initialiser
new Colis224_PayPal();
