<?php
/**
 * API REST pour intégrations externes et application mobile
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_REST_API {

    private $namespace = 'colis224/v1';

    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Enregistrer tous les endpoints REST
     */
    public function register_routes() {
        // === PARCELS (Colis) ===
        register_rest_route($this->namespace, '/parcels', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_parcels'),
                'permission_callback' => array($this, 'check_api_permission'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_parcel'),
                'permission_callback' => array($this, 'check_api_permission'),
            )
        ));

        register_rest_route($this->namespace, '/parcels/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_parcel'),
                'permission_callback' => array($this, 'check_api_permission'),
            ),
            array(
                'methods' => 'PUT',
                'callback' => array($this, 'update_parcel'),
                'permission_callback' => array($this, 'check_api_permission'),
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'delete_parcel'),
                'permission_callback' => array($this, 'check_api_permission'),
            )
        ));

        // Endpoint de tracking public
        register_rest_route($this->namespace, '/track/(?P<tracking_number>[a-zA-Z0-9]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'track_parcel'),
            'permission_callback' => '__return_true', // Public
        ));

        // === CLIENTS ===
        register_rest_route($this->namespace, '/clients', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_clients'),
                'permission_callback' => array($this, 'check_api_permission'),
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'create_client'),
                'permission_callback' => array($this, 'check_api_permission'),
            )
        ));

        // === STATISTICS ===
        register_rest_route($this->namespace, '/stats/dashboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_dashboard_stats'),
            'permission_callback' => array($this, 'check_api_permission'),
        ));

        // === AUTHENTICATION ===
        register_rest_route($this->namespace, '/auth/login', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_login'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Vérifier les permissions API
     */
    public function check_api_permission($request) {
        // Vérifier le token JWT dans le header
        $auth_header = $request->get_header('Authorization');

        if (!$auth_header) {
            return new WP_Error('no_auth', 'Token d\'authentification manquant', array('status' => 401));
        }

        // Extraire le token
        $token = str_replace('Bearer ', '', $auth_header);

        // Vérifier le token
        $user_id = $this->verify_jwt_token($token);

        if (!$user_id) {
            return new WP_Error('invalid_token', 'Token invalide ou expiré', array('status' => 401));
        }

        return true;
    }

    /**
     * GET /parcels - Liste des colis
     */
    public function get_parcels($request) {
        global $wpdb;

        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;
        $status = $request->get_param('status');
        $offset = ($page - 1) * $per_page;

        $table = $wpdb->prefix . 'colis224_parcels';

        $where = '1=1';
        if ($status) {
            $where .= $wpdb->prepare(' AND status = %s', $status);
        }

        $parcels = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where");

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $parcels,
            'pagination' => array(
                'total' => intval($total),
                'page' => intval($page),
                'per_page' => intval($per_page),
                'total_pages' => ceil($total / $per_page)
            )
        ), 200);
    }

    /**
     * GET /parcels/:id - Détails d'un colis
     */
    public function get_parcel($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $table = $wpdb->prefix . 'colis224_parcels';

        $parcel = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

        if (!$parcel) {
            return new WP_Error('not_found', 'Colis introuvable', array('status' => 404));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $parcel
        ), 200);
    }

    /**
     * GET /track/:tracking_number - Suivi public
     */
    public function track_parcel($request) {
        global $wpdb;

        $tracking_number = $request->get_param('tracking_number');
        $table = $wpdb->prefix . 'colis224_parcels';

        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT id, tracking_number, status, recipient_name, recipient_phone,
                    origin_country_id, destination_country_id, created_at,
                    estimated_delivery_date, shipping_date, delivery_date
             FROM $table
             WHERE tracking_number = %s",
            $tracking_number
        ));

        if (!$parcel) {
            return new WP_Error('not_found', 'Colis introuvable', array('status' => 404));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $parcel
        ), 200);
    }

    /**
     * POST /parcels - Créer un colis
     */
    public function create_parcel($request) {
        global $wpdb;

        $params = $request->get_json_params();

        // Validation des données requises
        $required = array('tracking_number', 'recipient_name', 'recipient_phone', 'recipient_address');
        foreach ($required as $field) {
            if (empty($params[$field])) {
                return new WP_Error('missing_field', "Le champ $field est requis", array('status' => 400));
            }
        }

        $table = $wpdb->prefix . 'colis224_parcels';

        // Vérifier si le numéro de suivi existe déjà
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE tracking_number = %s",
            $params['tracking_number']
        ));

        if ($exists) {
            return new WP_Error('duplicate', 'Ce numéro de suivi existe déjà', array('status' => 409));
        }

        // Insérer
        $result = $wpdb->insert($table, array(
            'tracking_number' => sanitize_text_field($params['tracking_number']),
            'client_id' => isset($params['client_id']) ? intval($params['client_id']) : null,
            'sender_name' => sanitize_text_field($params['sender_name'] ?? ''),
            'recipient_name' => sanitize_text_field($params['recipient_name']),
            'recipient_phone' => sanitize_text_field($params['recipient_phone']),
            'recipient_address' => sanitize_textarea_field($params['recipient_address']),
            'weight' => floatval($params['weight'] ?? 0),
            'unit_price' => floatval($params['unit_price'] ?? 0),
            'total_amount' => floatval($params['total_amount'] ?? 0),
            'status' => sanitize_text_field($params['status'] ?? 'En attente'),
            'payment_status' => sanitize_text_field($params['payment_status'] ?? 'Non payé'),
        ));

        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de la création', array('status' => 500));
        }

        $parcel_id = $wpdb->insert_id;

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Colis créé avec succès',
            'data' => array('id' => $parcel_id)
        ), 201);
    }

    /**
     * PUT /parcels/:id - Mettre à jour un colis
     */
    public function update_parcel($request) {
        global $wpdb;

        $id = $request->get_param('id');
        $params = $request->get_json_params();
        $table = $wpdb->prefix . 'colis224_parcels';

        // Vérifier que le colis existe
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE id = %d", $id));
        if (!$exists) {
            return new WP_Error('not_found', 'Colis introuvable', array('status' => 404));
        }

        // Préparer les données à mettre à jour
        $update_data = array();
        $allowed_fields = array('status', 'payment_status', 'recipient_name', 'recipient_phone', 'recipient_address', 'notes');

        foreach ($allowed_fields as $field) {
            if (isset($params[$field])) {
                $update_data[$field] = sanitize_text_field($params[$field]);
            }
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', 'Aucune donnée à mettre à jour', array('status' => 400));
        }

        $result = $wpdb->update($table, $update_data, array('id' => $id));

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Colis mis à jour avec succès'
        ), 200);
    }

    /**
     * GET /clients - Liste des clients
     */
    public function get_clients($request) {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_clients';
        $clients = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $clients
        ), 200);
    }

    /**
     * POST /clients - Créer un client
     */
    public function create_client($request) {
        global $wpdb;

        $params = $request->get_json_params();
        $table = $wpdb->prefix . 'colis224_clients';

        $result = $wpdb->insert($table, array(
            'name' => sanitize_text_field($params['name']),
            'phone' => sanitize_text_field($params['phone']),
            'email' => sanitize_email($params['email'] ?? ''),
            'address' => sanitize_textarea_field($params['address'] ?? ''),
            'type' => sanitize_text_field($params['type'] ?? 'Particulier'),
        ));

        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de la création', array('status' => 500));
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Client créé avec succès',
            'data' => array('id' => $wpdb->insert_id)
        ), 201);
    }

    /**
     * GET /stats/dashboard - Statistiques du dashboard
     */
    public function get_dashboard_stats($request) {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $stats = array(
            'total_parcels' => $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels"),
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels WHERE status = 'En attente'"),
            'in_transit' => $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels WHERE status = 'En transit'"),
            'delivered' => $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels WHERE status = 'Livré'"),
        );

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $stats
        ), 200);
    }

    /**
     * POST /auth/login - Authentification API
     */
    public function api_login($request) {
        $params = $request->get_json_params();

        $username = $params['username'] ?? '';
        $password = $params['password'] ?? '';

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return new WP_Error('auth_failed', 'Identifiants invalides', array('status' => 401));
        }

        // Générer un token JWT
        $token = $this->generate_jwt_token($user->ID);

        return new WP_REST_Response(array(
            'success' => true,
            'token' => $token,
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'name' => $user->display_name
            )
        ), 200);
    }

    /**
     * Générer un token JWT
     */
    private function generate_jwt_token($user_id) {
        $secret_key = get_option('colis224_jwt_secret', wp_generate_password(64, true, true));

        // Sauvegarder la clé si elle n'existe pas
        if (!get_option('colis224_jwt_secret')) {
            update_option('colis224_jwt_secret', $secret_key);
        }

        $issued_at = time();
        $expiration = $issued_at + (7 * 24 * 60 * 60); // 7 jours

        $token_data = array(
            'iat' => $issued_at,
            'exp' => $expiration,
            'user_id' => $user_id
        );

        // Simple base64 encoding pour la démo (utiliser une vraie lib JWT en production)
        return base64_encode(json_encode($token_data) . '.' . hash_hmac('sha256', json_encode($token_data), $secret_key));
    }

    /**
     * Vérifier un token JWT
     */
    private function verify_jwt_token($token) {
        $secret_key = get_option('colis224_jwt_secret', '');

        if (empty($secret_key)) {
            return false;
        }

        try {
            $decoded = base64_decode($token);
            $parts = explode('.', $decoded);

            if (count($parts) !== 2) {
                return false;
            }

            $data = json_decode($parts[0], true);
            $signature = $parts[1];

            // Vérifier la signature
            $valid_signature = hash_hmac('sha256', $parts[0], $secret_key);

            if ($signature !== $valid_signature) {
                return false;
            }

            // Vérifier l'expiration
            if ($data['exp'] < time()) {
                return false;
            }

            return $data['user_id'];

        } catch (Exception $e) {
            return false;
        }
    }
}

// Initialiser
new Colis224_REST_API();
