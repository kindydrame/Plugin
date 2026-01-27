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

    /**
     * Durée du cookie "Se souvenir de moi" (30 jours)
     */
    const REMEMBER_ME_DURATION = 30 * 24 * 60 * 60;

    /**
     * Nom du cookie selector
     */
    const COOKIE_SELECTOR = 'colis224_remember_selector';

    /**
     * Nom du cookie token
     */
    const COOKIE_TOKEN = 'colis224_remember_token';

    public function __construct() {
        // Hook pour gérer la déconnexion
        add_action('init', array($this, 'handle_logout'));

        // Hook pour démarrer la session
        add_action('init', array($this, 'start_session'), 1);

        // Hook pour récupérer la session via cookie "Se souvenir de moi"
        add_action('init', array($this, 'try_recover_session'), 2);
    }

    /**
     * Essayer de récupérer la session via le cookie "Se souvenir de moi"
     */
    public function try_recover_session() {
        // Si déjà connecté, ne rien faire
        if (self::is_client_logged_in() && !self::is_session_expired()) {
            return;
        }

        // Vérifier si les cookies "remember me" existent
        if (!isset($_COOKIE[self::COOKIE_SELECTOR]) || !isset($_COOKIE[self::COOKIE_TOKEN])) {
            return;
        }

        $selector = sanitize_text_field($_COOKIE[self::COOKIE_SELECTOR]);
        $token = sanitize_text_field($_COOKIE[self::COOKIE_TOKEN]);

        if (empty($selector) || empty($token)) {
            return;
        }

        // Valider le token et récupérer la session
        self::validate_and_recover_session($selector, $token);
    }

    /**
     * Valider le token et récupérer la session
     *
     * @param string $selector Le sélecteur du token
     * @param string $token Le token
     * @return bool Succès ou échec
     */
    public static function validate_and_recover_session($selector, $token) {
        global $wpdb;
        $table_tokens = $wpdb->prefix . 'colis224_remember_tokens';

        // Nettoyer les tokens expirés
        self::cleanup_expired_tokens();

        // Rechercher le token par selector
        $stored = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_tokens WHERE selector = %s AND expires_at > NOW()",
            $selector
        ));

        if (!$stored) {
            // Token non trouvé ou expiré, supprimer les cookies
            self::delete_remember_cookies();
            return false;
        }

        // Vérifier le token avec hash_equals pour éviter les timing attacks
        if (!hash_equals($stored->token, hash('sha256', $token))) {
            // Token invalide, supprimer les cookies et le token en base
            self::delete_remember_cookies();
            $wpdb->delete($table_tokens, array('id' => $stored->id), array('%d'));
            return false;
        }

        // Token valide, connecter le client
        $login_success = self::login_client($stored->client_id);

        if ($login_success) {
            // Régénérer le token pour plus de sécurité (rotation des tokens)
            $wpdb->delete($table_tokens, array('id' => $stored->id), array('%d'));
            self::create_remember_token($stored->client_id);
            return true;
        }

        return false;
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
        if (!session_id()) {
            session_start();
        }

        // Enregistrer les informations du client en session
        $_SESSION['colis224_client_id'] = $client->id;
        $_SESSION['colis224_client_name'] = $client->name;
        $_SESSION['colis224_client_phone'] = $client->phone;
        $_SESSION['colis224_client_email'] = $client->email;
        $_SESSION['colis224_login_time'] = time();

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
        if (session_id()) {
            // Supprimer les variables de session
            unset($_SESSION['colis224_client_id']);
            unset($_SESSION['colis224_client_name']);
            unset($_SESSION['colis224_client_phone']);
            unset($_SESSION['colis224_client_email']);
            unset($_SESSION['colis224_login_time']);
        }
    }

    /**
     * Détruire complètement la session client
     */
    public function destroy_client_session() {
        // Récupérer l'ID client avant de supprimer la session
        $client_id = isset($_SESSION['colis224_client_id']) ? intval($_SESSION['colis224_client_id']) : 0;

        if (session_id()) {
            // Supprimer toutes les variables de session Colis224
            foreach ($_SESSION as $key => $value) {
                if (strpos($key, 'colis224_') === 0) {
                    unset($_SESSION[$key]);
                }
            }
        }

        // Supprimer les cookies et tokens "Se souvenir de moi"
        if ($client_id) {
            self::delete_all_remember_tokens($client_id);
        } else {
            self::delete_remember_cookies();
        }
    }

    /**
     * Vérifier si un client est connecté
     * 
     * @return bool
     */
    public static function is_client_logged_in() {
        return isset($_SESSION['colis224_client_id']) && !empty($_SESSION['colis224_client_id']);
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
     * Créer un token "Se souvenir de moi" pour un client
     *
     * @param int $client_id ID du client
     * @return bool Succès ou échec
     */
    public static function create_remember_token($client_id) {
        if (!$client_id) {
            return false;
        }

        global $wpdb;
        $table_tokens = $wpdb->prefix . 'colis224_remember_tokens';

        // Générer un selector et un token aléatoires
        $selector = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(32));

        // Hasher le token pour le stockage (le selector reste en clair)
        $token_hash = hash('sha256', $token);

        // Date d'expiration
        $expires_at = date('Y-m-d H:i:s', time() + self::REMEMBER_ME_DURATION);

        // Supprimer les anciens tokens de ce client (garder max 5)
        $old_tokens = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $table_tokens WHERE client_id = %d ORDER BY created_at DESC",
            $client_id
        ));

        if (count($old_tokens) >= 5) {
            $ids_to_delete = array_slice(array_column($old_tokens, 'id'), 4);
            if (!empty($ids_to_delete)) {
                $placeholders = implode(',', array_fill(0, count($ids_to_delete), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM $table_tokens WHERE id IN ($placeholders)",
                    ...$ids_to_delete
                ));
            }
        }

        // Insérer le nouveau token
        $inserted = $wpdb->insert(
            $table_tokens,
            array(
                'client_id' => $client_id,
                'token' => $token_hash,
                'selector' => $selector,
                'expires_at' => $expires_at,
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null,
                'ip_address' => self::get_client_ip()
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );

        if (!$inserted) {
            return false;
        }

        // Définir les cookies
        $cookie_options = array(
            'expires' => time() + self::REMEMBER_ME_DURATION,
            'path' => COOKIEPATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax'
        );

        setcookie(self::COOKIE_SELECTOR, $selector, $cookie_options);
        setcookie(self::COOKIE_TOKEN, $token, $cookie_options);

        return true;
    }

    /**
     * Supprimer les cookies "Se souvenir de moi"
     */
    public static function delete_remember_cookies() {
        $cookie_options = array(
            'expires' => time() - 3600,
            'path' => COOKIEPATH,
            'domain' => COOKIE_DOMAIN,
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax'
        );

        setcookie(self::COOKIE_SELECTOR, '', $cookie_options);
        setcookie(self::COOKIE_TOKEN, '', $cookie_options);

        unset($_COOKIE[self::COOKIE_SELECTOR]);
        unset($_COOKIE[self::COOKIE_TOKEN]);
    }

    /**
     * Supprimer tous les tokens "Se souvenir de moi" d'un client
     *
     * @param int $client_id ID du client
     */
    public static function delete_all_remember_tokens($client_id) {
        if (!$client_id) {
            return;
        }

        global $wpdb;
        $table_tokens = $wpdb->prefix . 'colis224_remember_tokens';

        $wpdb->delete(
            $table_tokens,
            array('client_id' => $client_id),
            array('%d')
        );

        self::delete_remember_cookies();
    }

    /**
     * Nettoyer les tokens expirés
     */
    public static function cleanup_expired_tokens() {
        global $wpdb;
        $table_tokens = $wpdb->prefix . 'colis224_remember_tokens';

        $wpdb->query("DELETE FROM $table_tokens WHERE expires_at < NOW()");
    }

    /**
     * Obtenir l'adresse IP du client
     *
     * @return string
     */
    private static function get_client_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field(trim($ip));
    }

    /**
     * Obtenir les sessions actives d'un client (pour affichage)
     *
     * @param int $client_id ID du client
     * @return array Liste des sessions actives
     */
    public static function get_active_sessions($client_id) {
        if (!$client_id) {
            return array();
        }

        global $wpdb;
        $table_tokens = $wpdb->prefix . 'colis224_remember_tokens';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_agent, ip_address, created_at, expires_at
             FROM $table_tokens
             WHERE client_id = %d AND expires_at > NOW()
             ORDER BY created_at DESC",
            $client_id
        ));
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
