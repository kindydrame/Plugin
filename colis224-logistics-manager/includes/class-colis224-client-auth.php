<?php
/**
 * Gestion de l'authentification des clients
 * Connexion, déconnexion, vérification de session
 * 
 * @package Colis224
 * @since 2.10.13
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Client_Auth {

    public function __construct() {
        // Hook pour gérer la déconnexion
        add_action('init', array($this, 'handle_logout'));
        
        // Hook pour démarrer la session
        add_action('init', array($this, 'start_session'), 1);
    }

    /**
     * Démarrer la session PHP si pas déjà démarrée
     */
    public function start_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Gérer la déconnexion client
     */
    public function handle_logout() {
        // Vérifier si c'est une demande de déconnexion
        if (isset($_GET['action']) && $_GET['action'] === 'colis224_logout') {
            // Vérifier le nonce pour la sécurité
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'colis224_logout')) {
                // Détruire les données de session client
                $this->destroy_client_session();
                
                // Rediriger vers la page de connexion
                $redirect_url = remove_query_arg(array('action', '_wpnonce'));
                
                // Ajouter un message de succès
                $redirect_url = add_query_arg('logout', 'success', $redirect_url);
                
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
    }

    /**
     * Connecter un client
     *
     * @param int $client_id ID du client
     * @return bool Succès ou échec
     */
    public static function login_client($client_id) {
        if (!$client_id) {
            return false;
        }

        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $client_id
        ));

        if (!$client) {
            return false;
        }

        // Démarrer la session si nécessaire
        if (!session_id() && !headers_sent()) {
            session_start();
        }

        // Enregistrer les informations du client en session
        $_SESSION['colis224_client_id'] = $client->id;
        $_SESSION['colis224_client_name'] = $client->name;
        $_SESSION['colis224_client_phone'] = $client->phone;
        $_SESSION['colis224_client_email'] = $client->email;
        $_SESSION['colis224_login_time'] = time();

        // AMÉLIORATION: Utiliser aussi des cookies sécurisés pour la persistance
        // Générer un token unique pour ce client
        $token = wp_hash($client->id . $client->phone . time() . wp_salt('auth'));

        // Stocker le token dans un transient WordPress (expiration 2 heures)
        set_transient('colis224_client_token_' . $client->id, $token, 2 * HOUR_IN_SECONDS);

        // Définir un cookie sécurisé avec le token (httponly pour sécurité)
        $cookie_name = 'colis224_client_auth';
        $cookie_value = base64_encode(json_encode(array(
            'client_id' => $client->id,
            'token' => $token
        )));

        // Cookie valide 2 heures
        setcookie(
            $cookie_name,
            $cookie_value,
            time() + (2 * HOUR_IN_SECONDS),
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(), // Secure si HTTPS
            true // HttpOnly pour sécurité
        );

        // Mettre à jour la dernière connexion du client
        $wpdb->update(
            $table_clients,
            array('last_login' => current_time('mysql')),
            array('id' => $client->id),
            array('%s'),
            array('%d')
        );

        return true;
    }

    /**
     * Déconnecter un client
     */
    public static function logout_client() {
        $client_id = self::get_current_client_id();

        if (session_id()) {
            // Supprimer les variables de session
            unset($_SESSION['colis224_client_id']);
            unset($_SESSION['colis224_client_name']);
            unset($_SESSION['colis224_client_phone']);
            unset($_SESSION['colis224_client_email']);
            unset($_SESSION['colis224_login_time']);
        }

        // AMÉLIORATION: Supprimer aussi le cookie et le transient
        if ($client_id) {
            delete_transient('colis224_client_token_' . $client_id);
        }

        // Supprimer le cookie
        if (isset($_COOKIE['colis224_client_auth'])) {
            setcookie(
                'colis224_client_auth',
                '',
                time() - 3600,
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(),
                true
            );
            unset($_COOKIE['colis224_client_auth']);
        }
    }

    /**
     * Détruire complètement la session client
     */
    public function destroy_client_session() {
        if (session_id()) {
            // Supprimer toutes les variables de session Colis224
            foreach ($_SESSION as $key => $value) {
                if (strpos($key, 'colis224_') === 0) {
                    unset($_SESSION[$key]);
                }
            }
        }

        // AMÉLIORATION: Supprimer aussi le cookie et le transient
        $client_id = null;
        if (isset($_SESSION['colis224_client_id'])) {
            $client_id = $_SESSION['colis224_client_id'];
        } elseif (isset($_COOKIE['colis224_client_auth'])) {
            $cookie_data = json_decode(base64_decode($_COOKIE['colis224_client_auth']), true);
            if ($cookie_data && isset($cookie_data['client_id'])) {
                $client_id = $cookie_data['client_id'];
            }
        }

        if ($client_id) {
            delete_transient('colis224_client_token_' . $client_id);
        }

        // Supprimer le cookie
        if (isset($_COOKIE['colis224_client_auth'])) {
            setcookie(
                'colis224_client_auth',
                '',
                time() - 3600,
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(),
                true
            );
            unset($_COOKIE['colis224_client_auth']);
        }
    }

    /**
     * Vérifier si un client est connecté
     *
     * @return bool
     */
    public static function is_client_logged_in() {
        // D'abord vérifier la session
        if (isset($_SESSION['colis224_client_id']) && !empty($_SESSION['colis224_client_id'])) {
            return true;
        }

        // AMÉLIORATION: Si pas de session, essayer de restaurer depuis le cookie
        if (isset($_COOKIE['colis224_client_auth'])) {
            $cookie_data = json_decode(base64_decode($_COOKIE['colis224_client_auth']), true);

            if ($cookie_data && isset($cookie_data['client_id']) && isset($cookie_data['token'])) {
                $client_id = intval($cookie_data['client_id']);
                $token = $cookie_data['token'];

                // Vérifier que le token est valide dans le transient
                $stored_token = get_transient('colis224_client_token_' . $client_id);

                if ($stored_token && $stored_token === $token) {
                    // Token valide, restaurer la session
                    self::restore_session_from_cookie($client_id);
                    return true;
                }
            }

            // Cookie invalide ou expiré, le supprimer
            setcookie(
                'colis224_client_auth',
                '',
                time() - 3600,
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(),
                true
            );
        }

        return false;
    }

    /**
     * Obtenir l'ID du client connecté
     *
     * @return int|false
     */
    public static function get_current_client_id() {
        if (self::is_client_logged_in()) {
            return intval($_SESSION['colis224_client_id']);
        }
        return false;
    }

    /**
     * Restaurer la session depuis le cookie
     *
     * @param int $client_id ID du client
     * @return bool Succès ou échec
     */
    private static function restore_session_from_cookie($client_id) {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $client_id
        ));

        if (!$client) {
            return false;
        }

        // Démarrer la session si nécessaire
        if (!session_id() && !headers_sent()) {
            session_start();
        }

        // Restaurer les informations du client en session
        $_SESSION['colis224_client_id'] = $client->id;
        $_SESSION['colis224_client_name'] = $client->name;
        $_SESSION['colis224_client_phone'] = $client->phone;
        $_SESSION['colis224_client_email'] = $client->email;
        $_SESSION['colis224_login_time'] = time();

        return true;
    }

    /**
     * Obtenir les informations du client connecté
     * 
     * @return object|false
     */
    public static function get_current_client() {
        $client_id = self::get_current_client_id();
        
        if (!$client_id) {
            return false;
        }

        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $client_id
        ));
    }

    /**
     * Vérifier si la session est expirée (2 heures)
     * 
     * @return bool
     */
    public static function is_session_expired() {
        if (!isset($_SESSION['colis224_login_time'])) {
            return true;
        }

        $login_time = intval($_SESSION['colis224_login_time']);
        $current_time = time();
        $session_duration = 2 * 60 * 60; // 2 heures

        return ($current_time - $login_time) > $session_duration;
    }

    /**
     * Rafraîchir le temps de session
     */
    public static function refresh_session() {
        if (self::is_client_logged_in()) {
            $_SESSION['colis224_login_time'] = time();
        }
    }

    /**
     * Connecter un client par téléphone
     * 
     * @param string $phone Numéro de téléphone
     * @param string $password Code client (optionnel)
     * @return array Résultat avec succès et message
     */
    public static function authenticate_by_phone($phone, $password = '') {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        // Nettoyer le téléphone
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (empty($phone)) {
            return array(
                'success' => false,
                'message' => 'Numéro de téléphone invalide.'
            );
        }

        // Rechercher le client
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE phone = %s",
            $phone
        ));

        if (!$client) {
            return array(
                'success' => false,
                'message' => 'Numéro de téléphone non trouvé. Veuillez contacter Colis224.'
            );
        }

        // Si un mot de passe est fourni et que le client a un mot de passe, vérifier
        if (!empty($password) && !empty($client->password)) {
            // Vérifier le mot de passe (hash)
            if (!password_verify($password, $client->password)) {
                return array(
                    'success' => false,
                    'message' => 'Code client incorrect.'
                );
            }
        }

        // Connecter le client
        $login_success = self::login_client($client->id);

        if ($login_success) {
            return array(
                'success' => true,
                'message' => 'Connexion réussie ! Bienvenue ' . esc_html($client->name) . '.',
                'client_id' => $client->id
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Erreur lors de la connexion. Veuillez réessayer.'
            );
        }
    }
}
