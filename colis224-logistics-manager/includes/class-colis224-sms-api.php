<?php
/**
 * Système d'Envoi SMS via API
 * Supports: Twilio, Nexmo/Vonage, Africa's Talking, Orange SMS API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_SMS_API {

    private $provider;
    private $settings;

    public function __construct() {
        $this->load_settings();

        // Hooks AJAX pour tests et envois
        add_action('wp_ajax_colis224_send_test_sms', array($this, 'ajax_send_test_sms'));
        add_action('wp_ajax_colis224_send_bulk_sms', array($this, 'ajax_send_bulk_sms'));
    }

    /**
     * Charger les paramètres SMS
     */
    private function load_settings() {
        $this->provider = get_option('colis224_sms_provider', 'none');

        $this->settings = array(
            'twilio' => array(
                'account_sid' => get_option('colis224_twilio_account_sid', ''),
                'auth_token' => get_option('colis224_twilio_auth_token', ''),
                'from_number' => get_option('colis224_twilio_from_number', ''),
            ),
            'africas_talking' => array(
                'api_key' => get_option('colis224_africastalking_api_key', ''),
                'username' => get_option('colis224_africastalking_username', ''),
                'from' => get_option('colis224_africastalking_from', 'Colis224'),
            ),
            'orange' => array(
                'client_id' => get_option('colis224_orange_sms_client_id', ''),
                'client_secret' => get_option('colis224_orange_sms_client_secret', ''),
                'sender_name' => get_option('colis224_orange_sms_sender', 'Colis224'),
            ),
            'nimbasms' => array(
                'sid' => get_option('colis224_nimbasms_sid', '48782ece605fcd66fc15da242cc0142c'),
                'auth_token' => get_option('colis224_nimbasms_token', 'Basic NDg3ODJlY2U2MDVmY2Q2NmZjMTVkYTI0MmNjMDE0MmM6elhMZGIySU4xVVh2eE5KZWdyakJkX0VGMlE2XzRtemFGM2FFTlE1NW94ZmYyS0lWX1lMNi1KdnE1TUNaRGV0Vm9wc1JVaXYyNkdIUkhpYWVPbmdMU2xnQnNPOS1YNXE0dWkxS1BEUFFOMjQ='),
                'from' => get_option('colis224_nimbasms_from', 'Colis224'),
            ),
            'custom' => array(
                'api_url' => get_option('colis224_custom_sms_api_url', ''),
                'api_key' => get_option('colis224_custom_sms_api_key', ''),
                'method' => get_option('colis224_custom_sms_method', 'POST'),
            )
        );
    }

    /**
     * Envoyer un SMS
     */
    public function send_sms($to, $message, $parcel_id = null) {
        // Vérifier que le provider est configuré
        if ($this->provider === 'none' || empty($this->settings[$this->provider])) {
            return array('success' => false, 'message' => 'Aucun provider SMS configuré');
        }

        // Nettoyer le numéro
        $to = $this->format_phone_number($to);

        if (empty($to)) {
            return array('success' => false, 'message' => 'Numéro de téléphone invalide');
        }

        // Envoyer selon le provider
        switch ($this->provider) {
            case 'twilio':
                $result = $this->send_via_twilio($to, $message);
                break;

            case 'africas_talking':
                $result = $this->send_via_africas_talking($to, $message);
                break;

            case 'orange':
                $result = $this->send_via_orange($to, $message);
                break;

            case 'nimbasms':
                $result = $this->send_via_nimbasms($to, $message);
                break;

            case 'custom':
                $result = $this->send_via_custom_api($to, $message);
                break;

            default:
                $result = array('success' => false, 'message' => 'Provider inconnu');
        }

        // Logger le SMS
        $this->log_sms($to, $message, $result['success'], $result['message'] ?? '', $parcel_id);

        return $result;
    }

    /**
     * Envoyer via Twilio
     */
    private function send_via_twilio($to, $message) {
        $settings = $this->settings['twilio'];

        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $settings['account_sid'] . '/Messages.json';

        $data = array(
            'From' => $settings['from_number'],
            'To' => $to,
            'Body' => $message
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($settings['account_sid'] . ':' . $settings['auth_token'])
            ),
            'body' => $data,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['sid'])) {
            return array('success' => true, 'message' => 'SMS envoyé via Twilio', 'sid' => $body['sid']);
        }

        return array('success' => false, 'message' => $body['message'] ?? 'Erreur Twilio');
    }

    /**
     * Envoyer via Africa's Talking
     */
    private function send_via_africas_talking($to, $message) {
        $settings = $this->settings['africas_talking'];

        $url = 'https://api.africastalking.com/version1/messaging';

        $data = array(
            'username' => $settings['username'],
            'to' => $to,
            'message' => $message,
            'from' => $settings['from']
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'apiKey' => $settings['api_key'],
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ),
            'body' => $data,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['SMSMessageData']['Recipients']) && count($body['SMSMessageData']['Recipients']) > 0) {
            return array('success' => true, 'message' => 'SMS envoyé via Africa\'s Talking');
        }

        return array('success' => false, 'message' => 'Erreur Africa\'s Talking');
    }

    /**
     * Envoyer via Orange SMS API
     */
    private function send_via_orange($to, $message) {
        $settings = $this->settings['orange'];

        // 1. Obtenir le token OAuth
        $token = $this->get_orange_token();

        if (!$token) {
            return array('success' => false, 'message' => 'Impossible d\'obtenir le token Orange');
        }

        // 2. Envoyer le SMS
        $url = 'https://api.orange.com/smsmessaging/v1/outbound/' . urlencode($settings['sender_name']) . '/requests';

        $data = array(
            'outboundSMSMessageRequest' => array(
                'address' => 'tel:' . $to,
                'senderAddress' => 'tel:' . $settings['sender_name'],
                'outboundSMSTextMessage' => array(
                    'message' => $message
                )
            )
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code == 201) {
            return array('success' => true, 'message' => 'SMS envoyé via Orange');
        }

        return array('success' => false, 'message' => 'Erreur Orange SMS');
    }

    /**
     * Envoyer via NimbaSMS
     */
    private function send_via_nimbasms($to, $message) {
        $settings = $this->settings['nimbasms'];

        if (empty($settings['sid']) || empty($settings['auth_token'])) {
            return array('success' => false, 'message' => 'Identifiants NimbaSMS manquants');
        }

        // Formater le numéro pour Guinée (+224)
        $to = $this->format_phone_number($to);
        if (!$to) {
            return array('success' => false, 'message' => 'Numéro invalide');
        }

        $api_url = 'https://api.nimbasms.com/v1/messages';

        $data = array(
            'to' => $to,
            'message' => $message,
            'from' => !empty($settings['from']) ? $settings['from'] : 'Colis224'
        );

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => $settings['auth_token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30,
            'sslverify' => true
        );

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Erreur de connexion: ' . $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($body, true);

        // Vérifier le code de statut (NimbaSMS utilise 200, 201 pour succès)
        if ($status_code >= 200 && $status_code < 300) {
            return array(
                'success' => true,
                'message' => 'SMS envoyé via NimbaSMS',
                'message_id' => isset($decoded_body['id']) ? $decoded_body['id'] : null
            );
        } else {
            $error_message = isset($decoded_body['message']) ? $decoded_body['message'] : 'Erreur NimbaSMS';
            return array(
                'success' => false,
                'message' => $error_message,
                'status_code' => $status_code
            );
        }
    }

    /**
     * Envoyer via API personnalisée
     */
    private function send_via_custom_api($to, $message) {
        $settings = $this->settings['custom'];

        $url = str_replace(
            array('{phone}', '{message}'),
            array(urlencode($to), urlencode($message)),
            $settings['api_url']
        );

        $args = array(
            'timeout' => 30,
            'headers' => array()
        );

        if (!empty($settings['api_key'])) {
            $args['headers']['Authorization'] = 'Bearer ' . $settings['api_key'];
        }

        if ($settings['method'] === 'POST') {
            $args['body'] = array('phone' => $to, 'message' => $message);
            $response = wp_remote_post($url, $args);
        } else {
            $response = wp_remote_get($url, $args);
        }

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 200 && $status_code < 300) {
            return array('success' => true, 'message' => 'SMS envoyé via API personnalisée');
        }

        return array('success' => false, 'message' => 'Erreur API personnalisée');
    }

    /**
     * Obtenir le token OAuth Orange
     */
    private function get_orange_token() {
        $cached_token = get_transient('colis224_orange_sms_token');
        if ($cached_token) {
            return $cached_token;
        }

        $settings = $this->settings['orange'];

        $url = 'https://api.orange.com/oauth/v2/token';

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($settings['client_id'] . ':' . $settings['client_secret']),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => array('grant_type' => 'client_credentials'),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {
            // Cacher le token pour 1 heure
            set_transient('colis224_orange_sms_token', $body['access_token'], 3600);
            return $body['access_token'];
        }

        return false;
    }

    /**
     * Formater un numéro de téléphone
     */
    private function format_phone_number($phone) {
        // Nettoyer
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Si commence par 00, remplacer par +
        if (substr($phone, 0, 2) === '00') {
            $phone = '+' . substr($phone, 2);
        }

        // Si ne commence pas par +, ajouter +224 (Guinée par défaut)
        if (substr($phone, 0, 1) !== '+') {
            if (strlen($phone) === 9) {
                $phone = '+224' . $phone;
            }
        }

        return $phone;
    }

    /**
     * Logger les SMS envoyés
     */
    private function log_sms($to, $message, $success, $response, $parcel_id = null) {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_sms_logs';

        $wpdb->insert($table, array(
            'phone_number' => $to,
            'message' => $message,
            'provider' => $this->provider,
            'status' => $success ? 'sent' : 'failed',
            'response' => $response,
            'parcel_id' => $parcel_id,
            'created_at' => current_time('mysql')
        ));
    }

    /**
     * Obtenir le crédit SMS restant (si supporté par le provider)
     */
    public function get_sms_credit() {
        // À implémenter selon les APIs des providers
        return array('credit' => 'N/A', 'provider' => $this->provider);
    }

    /**
     * AJAX: Envoyer un SMS de test
     */
    public function ajax_send_test_sms() {
        check_ajax_referer('colis224_nonce', 'nonce');

        $phone = sanitize_text_field($_POST['phone']);
        $message = sanitize_text_field($_POST['message']);

        $result = $this->send_sms($phone, $message);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Envoyer des SMS en masse
     */
    public function ajax_send_bulk_sms() {
        check_ajax_referer('colis224_nonce', 'nonce');

        $phones = $_POST['phones']; // Array
        $message = sanitize_text_field($_POST['message']);

        $results = array('sent' => 0, 'failed' => 0);

        foreach ($phones as $phone) {
            $result = $this->send_sms($phone, $message);
            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        wp_send_json_success($results);
    }

    /**
     * Envoyer une notification SMS pour un changement de statut de colis
     */
    public static function send_parcel_status_notification($parcel_id, $new_status) {
        global $wpdb;

        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_parcels WHERE id = %d",
            $parcel_id
        ));

        if (!$parcel) {
            return false;
        }

        // Message selon le statut
        $messages = array(
            'Expédié' => "Bonjour, votre colis {$parcel->tracking_number} a été expédié. Suivez-le sur " . home_url(),
            'En transit' => "Votre colis {$parcel->tracking_number} est en transit vers sa destination.",
            'Livré' => "Bonne nouvelle ! Votre colis {$parcel->tracking_number} a été livré. Merci de votre confiance.",
        );

        $message = $messages[$new_status] ?? "Mise à jour: votre colis {$parcel->tracking_number} - Statut: {$new_status}";

        $sms = new self();
        return $sms->send_sms($parcel->recipient_phone, $message, $parcel_id);
    }
}

// Initialiser
new Colis224_SMS_API();
