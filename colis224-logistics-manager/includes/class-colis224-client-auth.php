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
            // Configurer les paramètres de session AVANT session_start()
            // Important pour la compatibilité avec les navigateurs modernes
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', is_ssl() ? '1' : '0');
            ini_set('session.cookie_path', COOKIEPATH ? COOKIEPATH : '/');

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
        error_log('COLIS224 LOGIN_CLIENT: Début avec client_id=' . $client_id);

        if (!$client_id) {
            error_log('COLIS224 LOGIN_CLIENT: ÉCHEC - client_id vide');
            return false;
        }

        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $client_id
        ));

        if (!$client) {
            error_log('COLIS224 LOGIN_CLIENT: ÉCHEC - Client ID=' . $client_id . ' non trouvé en BDD');
            return false;
        }

        error_log('COLIS224 LOGIN_CLIENT: Client trouvé - ID=' . $client->id . ', Nom=' . $client->name . ', Tel=' . $client->phone);

        // Démarrer la session si nécessaire avec configuration appropriée
        $session_before = session_id();
        if (!session_id() && !headers_sent()) {
            error_log('COLIS224 LOGIN_CLIENT: Démarrage session...');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', is_ssl() ? '1' : '0');
            ini_set('session.cookie_path', COOKIEPATH ? COOKIEPATH : '/');
            session_start();
            error_log('COLIS224 LOGIN_CLIENT: Session démarrée - ID=' . session_id());
        } else {
            error_log('COLIS224 LOGIN_CLIENT: Session déjà active - ID=' . session_id() . ', Headers sent=' . (headers_sent() ? 'OUI' : 'NON'));
        }

        // Enregistrer les informations du client en session
        $_SESSION['colis224_client_id'] = $client->id;
        $_SESSION['colis224_client_name'] = $client->name;
        $_SESSION['colis224_client_phone'] = $client->phone;
        $_SESSION['colis224_client_email'] = $client->email;
        $_SESSION['colis224_login_time'] = time();

        error_log('COLIS224 LOGIN_CLIENT: Données session enregistrées - $_SESSION=' . print_r($_SESSION, true));

        // SOLUTION ROBUSTE: Utiliser aussi un cookie sécurisé comme backup
        // Si la session PHP échoue, le cookie prendra le relais
        $cookie_value = base64_encode(json_encode(array(
            'client_id' => $client->id,
            'login_time' => time(),
            'hash' => md5($client->id . $client->phone . AUTH_KEY) // Sécurité
        )));

        error_log('COLIS224 LOGIN_CLIENT: Cookie value créé - length=' . strlen($cookie_value));

        // Utiliser le format array pour PHP 7.3+ avec SameSite explicite
        $cookie_params = array(
            'expires' => time() + (2 * 60 * 60), // 2 heures
            'path' => COOKIEPATH ? COOKIEPATH : '/',
            'domain' => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax' // Important pour les redirections
        );

        error_log('COLIS224 LOGIN_CLIENT: Paramètres cookie - ' . print_r($cookie_params, true));

        $cookie_result = setcookie(
            'colis224_client_session',
            $cookie_value,
            $cookie_params
        );

        error_log('COLIS224 LOGIN_CLIENT: setcookie() retourné=' . ($cookie_result ? 'TRUE' : 'FALSE'));

        // Mettre à jour la dernière connexion du client
        $update_result = $wpdb->update(
            $table_clients,
            array('last_login' => current_time('mysql')),
            array('id' => $client->id),
            array('%s'),
            array('%d')
        );

        error_log('COLIS224 LOGIN_CLIENT: BDD update last_login - Résultat=' . $update_result);
        error_log('COLIS224 LOGIN_CLIENT: SUCCESS - Retour TRUE');

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

        // Supprimer aussi le cookie
        if (isset($_COOKIE['colis224_client_session'])) {
            setcookie(
                'colis224_client_session',
                '',
                array(
                    'expires' => time() - 3600,
                    'path' => COOKIEPATH ? COOKIEPATH : '/',
                    'domain' => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
                    'secure' => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax'
                )
            );
            unset($_COOKIE['colis224_client_session']);
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

        // Supprimer aussi le cookie
        if (isset($_COOKIE['colis224_client_session'])) {
            setcookie(
                'colis224_client_session',
                '',
                array(
                    'expires' => time() - 3600,
                    'path' => COOKIEPATH ? COOKIEPATH : '/',
                    'domain' => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
                    'secure' => is_ssl(),
                    'httponly' => true,
                    'samesite' => 'Lax'
                )
            );
            unset($_COOKIE['colis224_client_session']);
        }
    }

    /**
     * Vérifier si un client est connecté
     *
     * @return bool
     */
    public static function is_client_logged_in() {
        // PRIORITÉ AU COOKIE: Plus fiable après redirections AJAX
        if (isset($_COOKIE['colis224_client_session'])) {
            // Restaurer la session depuis le cookie si nécessaire
            if (!isset($_SESSION['colis224_client_id']) || empty($_SESSION['colis224_client_id'])) {
                $restored = self::restore_session_from_cookie();
                if ($restored) {
                    return true;
                }
            } else {
                // Cookie et session existent tous les deux
                return true;
            }
        }

        // Vérifier la session en dernier recours
        if (isset($_SESSION['colis224_client_id']) && !empty($_SESSION['colis224_client_id'])) {
            return true;
        }

        return false;
    }

    /**
     * Restaurer la session depuis le cookie
     *
     * @return bool
     */
    private static function restore_session_from_cookie() {
        if (!isset($_COOKIE['colis224_client_session'])) {
            error_log('COLIS224 RESTORE: Cookie absent');
            return false;
        }

        global $wpdb;

        $cookie_data = json_decode(base64_decode($_COOKIE['colis224_client_session']), true);

        if (!$cookie_data || !isset($cookie_data['client_id'])) {
            error_log('COLIS224 RESTORE: Cookie invalide - ' . print_r($cookie_data, true));
            return false;
        }

        $client_id = intval($cookie_data['client_id']);
        $login_time = isset($cookie_data['login_time']) ? intval($cookie_data['login_time']) : 0;
        $hash = isset($cookie_data['hash']) ? $cookie_data['hash'] : '';

        // Vérifier que le cookie n'est pas expiré (2 heures)
        if (time() - $login_time > (2 * 60 * 60)) {
            error_log('COLIS224 RESTORE: Cookie expiré');
            return false;
        }

        // Récupérer le client de la base de données
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $client_id
        ));

        if (!$client) {
            error_log('COLIS224 RESTORE: Client ID=' . $client_id . ' introuvable en BDD');
            return false;
        }

        // Vérifier le hash pour la sécurité
        $expected_hash = md5($client->id . $client->phone . AUTH_KEY);
        if ($hash !== $expected_hash) {
            error_log('COLIS224 RESTORE: Hash invalide - Expected=' . $expected_hash . ', Got=' . $hash);
            return false;
        }

        // Restaurer la session avec configuration appropriée
        if (!session_id() && !headers_sent()) {
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', is_ssl() ? '1' : '0');
            ini_set('session.cookie_path', COOKIEPATH ? COOKIEPATH : '/');
            session_start();
        }

        $_SESSION['colis224_client_id'] = $client->id;
        $_SESSION['colis224_client_name'] = $client->name;
        $_SESSION['colis224_client_phone'] = $client->phone;
        $_SESSION['colis224_client_email'] = $client->email;
        $_SESSION['colis224_login_time'] = $login_time;

        error_log('COLIS224 RESTORE: SUCCESS - Client ID=' . $client->id . ', Session ID=' . session_id());

        return true;
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
     * Connecter un client par téléphone
     * 
     * @param string $phone Numéro de téléphone
     * @param string $password Code client (optionnel)
     * @return array Résultat avec succès et message
     */
    public static function authenticate_by_phone($phone, $password = '') {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        error_log('COLIS224 AUTH: Début authentification pour téléphone=' . $phone);

        // Nettoyer le téléphone
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (empty($phone)) {
            error_log('COLIS224 AUTH: Téléphone invalide après nettoyage');
            return array(
                'success' => false,
                'message' => 'Numéro de téléphone invalide.'
            );
        }

        error_log('COLIS224 AUTH: Téléphone nettoyé=' . $phone);

        // Rechercher le client
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE phone = %s",
            $phone
        ));

        if (!$client) {
            error_log('COLIS224 AUTH: Client NON TROUVÉ dans BDD pour phone=' . $phone);
            return array(
                'success' => false,
                'message' => 'Numéro de téléphone non trouvé. Veuillez contacter Colis224.'
            );
        }

        error_log('COLIS224 AUTH: Client TROUVÉ - ID=' . $client->id . ', Nom=' . $client->name);

        // Si un mot de passe est fourni et que le client a un mot de passe, vérifier
        if (!empty($password) && !empty($client->password)) {
            error_log('COLIS224 AUTH: Vérification mot de passe...');
            // Vérifier le mot de passe (hash)
            if (!password_verify($password, $client->password)) {
                error_log('COLIS224 AUTH: Mot de passe INCORRECT');
                return array(
                    'success' => false,
                    'message' => 'Code client incorrect.'
                );
            }
            error_log('COLIS224 AUTH: Mot de passe CORRECT');
        } else {
            error_log('COLIS224 AUTH: Pas de mot de passe à vérifier (password vide ou client sans password)');
        }

        // Connecter le client
        error_log('COLIS224 AUTH: Appel login_client() avec ID=' . $client->id);
        $login_success = self::login_client($client->id);

        if ($login_success) {
            error_log('COLIS224 AUTH: login_client() retourné TRUE - SUCCESS');
            return array(
                'success' => true,
                'message' => 'Connexion réussie ! Bienvenue ' . esc_html($client->name) . '.',
                'client_id' => $client->id
            );
        } else {
            error_log('COLIS224 AUTH: login_client() retourné FALSE - ÉCHEC');
            return array(
                'success' => false,
                'message' => 'Erreur lors de la connexion. Veuillez réessayer.'
            );
        }
    }
}
