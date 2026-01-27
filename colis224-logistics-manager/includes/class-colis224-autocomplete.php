<?php
/**
 * Gestion de l'autocomplétion pour les formulaires
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Autocomplete {

    public function __construct() {
        // Enregistrer les hooks AJAX
        add_action('wp_ajax_colis224_search_clients', array($this, 'search_clients'));
        add_action('wp_ajax_colis224_search_recipients', array($this, 'search_recipients'));
        add_action('wp_ajax_colis224_search_senders', array($this, 'search_senders'));
        add_action('wp_ajax_colis224_get_client_details', array($this, 'get_client_details'));

        // Nouveaux endpoints pour la sélection rapide de clients
        add_action('wp_ajax_colis224_get_recent_clients', array($this, 'get_recent_clients'));
        add_action('wp_ajax_colis224_get_all_clients', array($this, 'get_all_clients'));
    }

    /**
     * Rechercher des clients par nom ou téléphone
     * Amélioré: retourne les clients récents si le terme est vide
     */
    public function search_clients() {
        global $wpdb;

        // Vérification de sécurité
        check_ajax_referer('colis224_autocomplete', 'nonce');

        $search_term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        $table_clients = $wpdb->prefix . 'colis224_clients';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Si le terme est vide, retourner les clients récents (basé sur les derniers colis)
        if (empty($search_term)) {
            $results = $wpdb->get_results("
                SELECT DISTINCT c.id, c.name, c.phone, c.email, c.company_name
                FROM $table_clients c
                INNER JOIN $table_parcels p ON c.id = p.client_id
                ORDER BY p.created_at DESC
                LIMIT 10
            ");

            // Si pas de clients récents, retourner les derniers clients créés
            if (empty($results)) {
                $results = $wpdb->get_results("
                    SELECT id, name, phone, email, company_name
                    FROM $table_clients
                    ORDER BY created_at DESC
                    LIMIT 10
                ");
            }
        } else {
            // Recherche dans nom, email, téléphone et company_name
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT id, name, phone, email, company_name
                FROM $table_clients
                WHERE name LIKE %s
                   OR phone LIKE %s
                   OR email LIKE %s
                   OR company_name LIKE %s
                ORDER BY name ASC
                LIMIT 20
            ",
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%',
                '%' . $wpdb->esc_like($search_term) . '%'
            ));
        }

        $formatted_results = array();
        foreach ($results as $client) {
            $label = $client->name;
            if (!empty($client->phone)) {
                $label .= ' - ' . $client->phone;
            }
            if (!empty($client->company_name)) {
                $label .= ' (' . $client->company_name . ')';
            }

            $formatted_results[] = array(
                'id' => $client->id,
                'label' => $label,
                'value' => $client->name . (!empty($client->phone) ? ' - ' . $client->phone : ''),
                'phone' => $client->phone,
                'email' => $client->email,
                'company_name' => $client->company_name
            );
        }

        wp_send_json_success($formatted_results);
    }

    /**
     * Rechercher des destinataires dans l'historique des colis
     */
    public function search_recipients() {
        global $wpdb;

        check_ajax_referer('colis224_autocomplete', 'nonce');

        $search_term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        if (empty($search_term)) {
            wp_send_json_success(array());
            return;
        }

        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Recherche des destinataires uniques
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT recipient_name, recipient_phone, recipient_address
            FROM $table_parcels
            WHERE recipient_name LIKE %s
               OR recipient_phone LIKE %s
            ORDER BY recipient_name ASC
            LIMIT 20
        ",
            '%' . $wpdb->esc_like($search_term) . '%',
            '%' . $wpdb->esc_like($search_term) . '%'
        ));

        $formatted_results = array();
        foreach ($results as $recipient) {
            $formatted_results[] = array(
                'label' => $recipient->recipient_name . ' - ' . $recipient->recipient_phone,
                'value' => $recipient->recipient_name,
                'name' => $recipient->recipient_name,
                'phone' => $recipient->recipient_phone,
                'address' => $recipient->recipient_address
            );
        }

        wp_send_json_success($formatted_results);
    }

    /**
     * Rechercher des expéditeurs dans l'historique des colis
     */
    public function search_senders() {
        global $wpdb;

        check_ajax_referer('colis224_autocomplete', 'nonce');

        $search_term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        if (empty($search_term)) {
            wp_send_json_success(array());
            return;
        }

        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Recherche des expéditeurs uniques
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT sender_name
            FROM $table_parcels
            WHERE sender_name LIKE %s
               AND sender_name IS NOT NULL
               AND sender_name != ''
            ORDER BY sender_name ASC
            LIMIT 20
        ",
            '%' . $wpdb->esc_like($search_term) . '%'
        ));

        $formatted_results = array();
        foreach ($results as $sender) {
            $formatted_results[] = array(
                'label' => $sender->sender_name,
                'value' => $sender->sender_name
            );
        }

        wp_send_json_success($formatted_results);
    }

    /**
     * Récupérer les détails d'un client par ID
     */
    public function get_client_details() {
        global $wpdb;

        check_ajax_referer('colis224_autocomplete', 'nonce');

        $client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

        if ($client_id <= 0) {
            wp_send_json_error('ID client invalide');
            return;
        }

        $table_clients = $wpdb->prefix . 'colis224_clients';
        $client = $wpdb->get_row($wpdb->prepare("
            SELECT id, name, phone, email, address, company_name
            FROM $table_clients
            WHERE id = %d
        ", $client_id));

        if ($client) {
            wp_send_json_success($client);
        } else {
            wp_send_json_error('Client introuvable');
        }
    }

    /**
     * Récupérer les clients récents (basé sur les derniers colis créés)
     */
    public function get_recent_clients() {
        global $wpdb;

        check_ajax_referer('colis224_autocomplete', 'nonce');

        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        $limit = min($limit, 20); // Maximum 20

        $table_clients = $wpdb->prefix . 'colis224_clients';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Récupérer les clients ayant des colis récents
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT c.id, c.name, c.phone, c.email, c.company_name,
                   MAX(p.created_at) as last_parcel_date
            FROM $table_clients c
            INNER JOIN $table_parcels p ON c.id = p.client_id
            GROUP BY c.id, c.name, c.phone, c.email, c.company_name
            ORDER BY last_parcel_date DESC
            LIMIT %d
        ", $limit));

        // Si pas de résultats avec des colis, retourner les derniers clients créés
        if (empty($results)) {
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT id, name, phone, email, company_name
                FROM $table_clients
                ORDER BY created_at DESC
                LIMIT %d
            ", $limit));
        }

        $formatted_results = array();
        foreach ($results as $client) {
            $formatted_results[] = array(
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
                'company_name' => $client->company_name
            );
        }

        wp_send_json_success($formatted_results);
    }

    /**
     * Récupérer tous les clients avec filtres et recherche
     */
    public function get_all_clients() {
        global $wpdb;

        check_ajax_referer('colis224_autocomplete', 'nonce');

        $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

        $table_clients = $wpdb->prefix . 'colis224_clients';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $where_clauses = array('1=1');
        $prepare_values = array();

        // Ajouter la recherche si présente
        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where_clauses[] = "(c.name LIKE %s OR c.phone LIKE %s OR c.email LIKE %s OR c.company_name LIKE %s)";
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
            $prepare_values[] = $search_like;
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Construire la requête selon le filtre
        switch ($filter) {
            case 'recent':
                // Clients ayant eu des colis dans les 30 derniers jours
                $query = "
                    SELECT DISTINCT c.id, c.name, c.phone, c.email, c.company_name,
                           COUNT(p.id) as parcels_count,
                           MAX(p.created_at) as last_parcel_date
                    FROM $table_clients c
                    LEFT JOIN $table_parcels p ON c.id = p.client_id AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    WHERE $where_sql
                    GROUP BY c.id, c.name, c.phone, c.email, c.company_name
                    HAVING parcels_count > 0
                    ORDER BY last_parcel_date DESC
                    LIMIT 50
                ";
                break;

            case 'frequent':
                // Clients avec le plus de colis
                $query = "
                    SELECT c.id, c.name, c.phone, c.email, c.company_name,
                           COUNT(p.id) as parcels_count
                    FROM $table_clients c
                    LEFT JOIN $table_parcels p ON c.id = p.client_id
                    WHERE $where_sql
                    GROUP BY c.id, c.name, c.phone, c.email, c.company_name
                    ORDER BY parcels_count DESC
                    LIMIT 50
                ";
                break;

            default: // 'all'
                // Tous les clients triés par nom
                $query = "
                    SELECT c.id, c.name, c.phone, c.email, c.company_name,
                           COUNT(p.id) as parcels_count
                    FROM $table_clients c
                    LEFT JOIN $table_parcels p ON c.id = p.client_id
                    WHERE $where_sql
                    GROUP BY c.id, c.name, c.phone, c.email, c.company_name
                    ORDER BY c.name ASC
                    LIMIT 100
                ";
                break;
        }

        // Exécuter la requête
        if (!empty($prepare_values)) {
            $results = $wpdb->get_results($wpdb->prepare($query, $prepare_values));
        } else {
            $results = $wpdb->get_results($query);
        }

        $formatted_results = array();
        foreach ($results as $client) {
            $formatted_results[] = array(
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'email' => $client->email,
                'company_name' => $client->company_name,
                'parcels_count' => isset($client->parcels_count) ? intval($client->parcels_count) : 0
            );
        }

        wp_send_json_success($formatted_results);
    }

    /**
     * Charger les scripts et styles nécessaires pour l'autocomplete
     */
    public static function enqueue_scripts() {
        // jQuery UI Autocomplete est inclus dans WordPress
        wp_enqueue_script('jquery-ui-autocomplete');

        // Style personnalisé pour l'autocomplete
        wp_add_inline_style('colis224-admin-style', '
            .ui-autocomplete {
                max-height: 300px;
                overflow-y: auto;
                overflow-x: hidden;
                z-index: 9999 !important;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .ui-menu-item {
                padding: 0;
                margin: 0;
                list-style: none;
            }
            .ui-menu-item .ui-menu-item-wrapper {
                padding: 8px 12px;
                cursor: pointer;
                border-bottom: 1px solid #f0f0f0;
                font-size: 13px;
            }
            .ui-menu-item .ui-menu-item-wrapper:hover,
            .ui-menu-item .ui-menu-item-wrapper.ui-state-active {
                background: #0073aa;
                color: #fff;
                border-color: #0073aa;
            }
            .colis224-autocomplete-input {
                position: relative;
            }
            .colis224-autocomplete-input .dashicons {
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                color: #7e8993;
                pointer-events: none;
            }
        ');

        // Script d'initialisation
        wp_add_inline_script('jquery-ui-autocomplete', '
            var colis224AutocompleteNonce = "' . wp_create_nonce('colis224_autocomplete') . '";
        ');
    }
}

// Initialiser la classe
new Colis224_Autocomplete();
