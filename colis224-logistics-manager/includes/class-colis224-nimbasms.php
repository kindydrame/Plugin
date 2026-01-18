<?php
/**
 * Intégration NimbaSMS API
 * Documentation: https://developers.nimbasms.com/
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_NimbaSMS {

    private $sid;
    private $auth_token;
    private $api_url = 'https://api.nimbasms.com/v1/messages';

    public function __construct() {
        $this->sid = get_option('colis224_nimbasms_sid', '48782ece605fcd66fc15da242cc0142c');
        $this->auth_token = get_option('colis224_nimbasms_token', 'Basic NDg3ODJlY2U2MDVmY2Q2NmZjMTVkYTI0MmNjMDE0MmM6elhMZGIySU4xVVh2eE5KZWdyakJkX0VGMlE2XzRtemFGM2FFTlE1NW94ZmYyS0lWX1lMNi1KdnE1TUNaRGV0Vm9wc1JVaXYyNkdIUkhpYWVPbmdMU2xnQnNPOS1YNXE0dWkxS1BEUFFOMjQ=');
    }

    /**
     * Envoyer un SMS
     *
     * @param string $to Numéro de téléphone (format international, ex: +224622000000)
     * @param string $message Contenu du message (max 160 caractères recommandé)
     * @param string $from Nom de l'expéditeur (optionnel, max 11 caractères)
     * @return array Résultat de l'envoi
     */
    public function send_sms($to, $message, $from = 'Colis224') {
        // Nettoyer et formater le numéro
        $to = $this->format_phone_number($to);

        if (!$to) {
            return array(
                'success' => false,
                'error' => 'Numéro de téléphone invalide'
            );
        }

        // Préparer les données
        $data = array(
            'to' => $to,
            'message' => $message,
            'from' => substr($from, 0, 11) // Limite à 11 caractères
        );

        // Envoyer la requête
        $response = $this->make_request($data);

        // Logger l'envoi
        $this->log_sms($to, $message, $response);

        return $response;
    }

    /**
     * Envoyer un SMS à plusieurs destinataires
     *
     * @param array $recipients Liste de numéros
     * @param string $message Contenu du message
     * @param string $from Nom de l'expéditeur
     * @return array Résultats des envois
     */
    public function send_bulk_sms($recipients, $message, $from = 'Colis224') {
        $results = array();

        foreach ($recipients as $phone) {
            $results[] = $this->send_sms($phone, $message, $from);
        }

        return $results;
    }

    /**
     * Faire une requête à l'API NimbaSMS
     *
     * @param array $data Données à envoyer
     * @return array Réponse
     */
    private function make_request($data) {
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => $this->auth_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30,
            'sslverify' => true
        );

        $response = wp_remote_post($this->api_url, $args);

        // Vérifier les erreurs de connexion
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => 'Erreur de connexion: ' . $response->get_error_message(),
                'raw_response' => null
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($body, true);

        // Vérifier le code de statut
        if ($status_code >= 200 && $status_code < 300) {
            return array(
                'success' => true,
                'message_id' => isset($decoded_body['id']) ? $decoded_body['id'] : null,
                'status' => isset($decoded_body['status']) ? $decoded_body['status'] : 'sent',
                'raw_response' => $decoded_body
            );
        } else {
            return array(
                'success' => false,
                'error' => isset($decoded_body['message']) ? $decoded_body['message'] : 'Erreur inconnue',
                'status_code' => $status_code,
                'raw_response' => $decoded_body
            );
        }
    }

    /**
     * Formater un numéro de téléphone
     *
     * @param string $phone Numéro brut
     * @return string|false Numéro formaté ou false
     */
    private function format_phone_number($phone) {
        // Nettoyer le numéro
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Si commence par 00, remplacer par +
        if (substr($phone, 0, 2) === '00') {
            $phone = '+' . substr($phone, 2);
        }

        // Si ne commence pas par +, ajouter +224 (Guinée par défaut)
        if (substr($phone, 0, 1) !== '+') {
            // Si commence par 6 ou 6XX (numéro local guinéen)
            if (substr($phone, 0, 1) === '6' && strlen($phone) >= 9) {
                $phone = '+224' . $phone;
            } else {
                return false; // Format invalide
            }
        }

        // Vérifier la longueur minimale
        if (strlen($phone) < 10) {
            return false;
        }

        return $phone;
    }

    /**
     * Logger un envoi SMS dans la base de données
     *
     * @param string $to Destinataire
     * @param string $message Message
     * @param array $response Réponse API
     */
    private function log_sms($to, $message, $response) {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_sms_logs';

        // Vérifier si la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return; // Table n'existe pas encore
        }

        $wpdb->insert($table, array(
            'recipient' => $to,
            'message' => $message,
            'status' => $response['success'] ? 'sent' : 'failed',
            'response' => json_encode($response),
            'message_id' => isset($response['message_id']) ? $response['message_id'] : null,
            'sent_at' => current_time('mysql')
        ));
    }

    /**
     * Vérifier le solde du compte NimbaSMS (si disponible via API)
     */
    public function check_balance() {
        // Note: Vérifier la documentation NimbaSMS pour l'endpoint de solde
        // Pour l'instant, retourne un placeholder
        return array(
            'success' => false,
            'message' => 'Endpoint de vérification de solde non implémenté'
        );
    }

    /**
     * Tester la connexion à l'API
     */
    public static function test_connection() {
        $sms = new self();

        // Envoyer un SMS de test à un numéro fictif pour vérifier la connexion
        $test_result = $sms->make_request(array(
            'to' => '+224622000000', // Numéro de test
            'message' => 'Test Colis224',
            'from' => 'Colis224'
        ));

        return $test_result;
    }

    /**
     * Obtenir les statistiques d'envoi
     */
    public static function get_stats($days = 30) {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_sms_logs';

        $stats = array(
            'total_sent' => 0,
            'total_failed' => 0,
            'last_7_days' => 0,
            'last_30_days' => 0
        );

        // Vérifier si la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $stats['total_sent'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table WHERE status = 'sent'"
            );

            $stats['total_failed'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table WHERE status = 'failed'"
            );

            $stats['last_7_days'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table
                WHERE status = 'sent'
                AND sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );

            $stats['last_30_days'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM $table
                WHERE status = 'sent'
                AND sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
        }

        return $stats;
    }

    /**
     * Formater un message avec des variables
     */
    public static function format_message($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }
}
