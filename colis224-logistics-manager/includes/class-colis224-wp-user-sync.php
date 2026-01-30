<?php
/**
 * Synchronisation entre clients Colis224 et utilisateurs WordPress
 *
 * Cette classe gère la création automatique d'utilisateurs WordPress
 * pour chaque client Colis224, permettant l'utilisation du système
 * d'authentification natif de WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_WP_User_Sync {

    /**
     * Nom du rôle personnalisé pour les clients
     */
    const ROLE_NAME = 'colis224_client';

    /**
     * Meta key pour lier l'utilisateur WP au client Colis224
     */
    const META_CLIENT_ID = 'colis224_client_id';

    /**
     * Constructeur
     */
    public function __construct() {
        // Créer le rôle personnalisé lors de l'activation
        add_action('init', array($this, 'register_client_role'));

        // Hook pour la redirection après connexion
        add_filter('login_redirect', array($this, 'client_login_redirect'), 10, 3);

        // Hook pour personnaliser la page de connexion (optionnel)
        add_action('login_enqueue_scripts', array($this, 'customize_login_page'));

        // Hook pour ajouter des instructions sur la page de connexion
        add_action('login_message', array($this, 'add_login_instructions'));

        // Hook pour permettre la connexion par téléphone
        add_filter('authenticate', array($this, 'authenticate_by_phone_on_login'), 30, 3);

        // Hook pour gérer la déconnexion
        add_action('wp_logout', array($this, 'handle_client_logout'));

        // Hook pour créer automatiquement un utilisateur WP lors de la création d'un client
        add_action('colis224_client_created', array($this, 'auto_create_wp_user'), 10, 1);
        add_action('colis224_client_updated', array($this, 'auto_sync_wp_user'), 10, 1);
    }

    /**
     * Créer automatiquement un utilisateur WP après création d'un client
     *
     * @param int $client_id ID du client créé
     */
    public function auto_create_wp_user($client_id) {
        error_log('COLIS224 AUTO_CREATE: Hook déclenché pour client_id=' . $client_id);
        $user_id = self::create_wp_user_for_client($client_id);

        if (!is_wp_error($user_id)) {
            error_log('COLIS224 AUTO_CREATE: Utilisateur WP créé automatiquement - user_id=' . $user_id);
        } else {
            error_log('COLIS224 AUTO_CREATE: Échec création utilisateur - ' . $user_id->get_error_message());
        }
    }

    /**
     * Synchroniser un utilisateur WP après mise à jour d'un client
     *
     * @param int $client_id ID du client mis à jour
     */
    public function auto_sync_wp_user($client_id) {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $client_id
        ));

        if (!$client) {
            return;
        }

        $user_id = self::get_wp_user_id_by_client_id($client_id);

        if ($user_id) {
            // Mettre à jour les métadonnées de l'utilisateur
            update_user_meta($user_id, 'first_name', $client->name);
            update_user_meta($user_id, 'colis224_phone', $client->phone);
            update_user_meta($user_id, 'colis224_address', $client->address);

            // Mettre à jour l'email si fourni
            if (!empty($client->email) && strpos($client->email, '@colis224.local') === false) {
                wp_update_user(array(
                    'ID' => $user_id,
                    'user_email' => $client->email
                ));
            }

            error_log('COLIS224 AUTO_SYNC: Utilisateur WP synchronisé - user_id=' . $user_id);
        } else {
            // Si l'utilisateur n'existe pas, le créer
            $this->auto_create_wp_user($client_id);
        }
    }

    /**
     * Créer le rôle personnalisé pour les clients
     */
    public function register_client_role() {
        // Vérifier si le rôle existe déjà
        if (get_role(self::ROLE_NAME)) {
            return;
        }

        // Créer le rôle avec des capacités minimales
        add_role(
            self::ROLE_NAME,
            'Client Colis224',
            array(
                'read' => true, // Peut se connecter et lire le contenu
                'view_client_dashboard' => true, // Capacité personnalisée
            )
        );

        error_log('COLIS224 SYNC: Rôle "colis224_client" créé avec succès');
    }

    /**
     * Créer un utilisateur WordPress pour un client Colis224
     *
     * @param int $client_id ID du client dans la table colis224_clients
     * @return int|WP_Error ID de l'utilisateur créé ou erreur
     */
    public static function create_wp_user_for_client($client_id) {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        // Récupérer les infos du client
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $client_id
        ));

        if (!$client) {
            return new WP_Error('client_not_found', 'Client introuvable');
        }

        // Vérifier si un utilisateur existe déjà pour ce client
        $existing_user_id = self::get_wp_user_id_by_client_id($client_id);
        if ($existing_user_id) {
            error_log('COLIS224 SYNC: Utilisateur WP existe déjà pour client ID=' . $client_id . ', user_id=' . $existing_user_id);
            return $existing_user_id;
        }

        // Générer un nom d'utilisateur unique basé sur le téléphone
        $username = 'client_' . sanitize_user($client->phone);

        // Si le username existe déjà, ajouter un suffixe
        if (username_exists($username)) {
            $username = $username . '_' . $client_id;
        }

        // Générer un email si le client n'en a pas
        $email = isset($client->email) ? trim($client->email) : '';

        error_log('COLIS224 SYNC: Email original du client: "' . $email . '"');

        // Vérifier si l'email est vide, NULL, ou invalide
        if (empty($email) || !is_email($email)) {
            // Client sans email valide → générer un email fictif
            $email = 'noemail.client' . $client_id . '@colis224.com';
            error_log('COLIS224 SYNC: Client sans email valide - génération email fictif: ' . $email);
        } else {
            error_log('COLIS224 SYNC: Email valide trouvé: ' . $email);
        }

        // Vérifier si l'email existe déjà dans WordPress
        if (email_exists($email)) {
            // Si c'est un email auto-généré, en créer un nouveau avec timestamp
            if (strpos($email, '@colis224.com') !== false && strpos($email, 'noemail.client') !== false) {
                $email = 'noemail.client' . $client_id . '_' . time() . '@colis224.com';
                error_log('COLIS224 SYNC: Email fictif existe déjà - nouveau généré: ' . $email);
            } else {
                error_log('COLIS224 SYNC ERROR: Email réel déjà utilisé: ' . $email);
                return new WP_Error('email_exists', 'Cet email est déjà utilisé par un autre compte');
            }
        }

        // Générer un mot de passe aléatoire (l'utilisateur peut se connecter par téléphone)
        $password = wp_generate_password(20, true, true);

        // Créer l'utilisateur WordPress
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            error_log('COLIS224 SYNC ERROR: Échec création utilisateur - ' . $user_id->get_error_message());
            return $user_id;
        }

        // Attribuer le rôle personnalisé
        $user = new WP_User($user_id);
        $user->set_role(self::ROLE_NAME);

        // Mettre à jour les métadonnées
        update_user_meta($user_id, self::META_CLIENT_ID, $client_id);
        update_user_meta($user_id, 'first_name', $client->name);
        update_user_meta($user_id, 'colis224_phone', $client->phone);
        update_user_meta($user_id, 'colis224_address', $client->address);

        // Sauvegarder l'ID utilisateur WordPress dans la table clients
        $wpdb->update(
            $table_clients,
            array('wp_user_id' => $user_id),
            array('id' => $client_id),
            array('%d'),
            array('%d')
        );

        error_log('COLIS224 SYNC: Utilisateur WP créé avec succès - client_id=' . $client_id . ', user_id=' . $user_id . ', username=' . $username);

        return $user_id;
    }

    /**
     * Obtenir l'ID utilisateur WordPress à partir de l'ID client Colis224
     *
     * @param int $client_id ID du client
     * @return int|false ID de l'utilisateur ou false
     */
    public static function get_wp_user_id_by_client_id($client_id) {
        global $wpdb;

        // Chercher d'abord dans la table clients
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT wp_user_id FROM $table_clients WHERE id = %d AND wp_user_id IS NOT NULL",
            $client_id
        ));

        if ($user_id) {
            return intval($user_id);
        }

        // Sinon chercher par meta
        $users = get_users(array(
            'meta_key' => self::META_CLIENT_ID,
            'meta_value' => $client_id,
            'number' => 1,
            'fields' => 'ID'
        ));

        return !empty($users) ? intval($users[0]) : false;
    }

    /**
     * Obtenir l'ID client Colis224 à partir de l'ID utilisateur WordPress
     *
     * @param int $user_id ID de l'utilisateur WordPress
     * @return int|false ID du client ou false
     */
    public static function get_client_id_by_wp_user_id($user_id) {
        $client_id = get_user_meta($user_id, self::META_CLIENT_ID, true);
        return $client_id ? intval($client_id) : false;
    }

    /**
     * Connexion d'un client par numéro de téléphone
     *
     * @param string $phone Numéro de téléphone
     * @param string $password Code client (OBLIGATOIRE pour sécurité)
     * @param bool $set_cookie Définir automatiquement le cookie d'authentification (défaut: true)
     * @return array Résultat avec succès et message
     */
    public static function authenticate_by_phone($phone, $password = '', $set_cookie = true) {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        // Nettoyer le numéro de téléphone
        $phone = sanitize_text_field($phone);

        // Chercher le client par téléphone
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE phone = %s",
            $phone
        ));

        if (!$client) {
            return array(
                'success' => false,
                'message' => 'Aucun client trouvé avec ce numéro de téléphone.'
            );
        }

        // SÉCURITÉ: Code client OBLIGATOIRE
        if (empty($password)) {
            return array(
                'success' => false,
                'message' => 'Le code client est obligatoire pour se connecter.'
            );
        }

        // Générer le code par défaut : PA + 4 derniers chiffres du téléphone
        // Extraire uniquement les chiffres du numéro
        $digits_only = preg_replace('/[^0-9]/', '', $client->phone);
        $last_four = substr($digits_only, -4);
        $default_code = 'PA' . $last_four; // Ex: PA2244

        error_log('COLIS224 AUTH: phone=' . $client->phone . ', digits=' . $digits_only . ', last4=' . $last_four . ', default_code=' . $default_code);

        // Vérifier le mot de passe
        $password_valid = false;

        // 1. Vérifier si le mot de passe correspond au code configuré (si existe)
        if (!empty($client->code) && $password === $client->code) {
            $password_valid = true;
            error_log('COLIS224 AUTH: Authentification avec code personnalisé');
        }

        // 2. Vérifier si le mot de passe correspond au code par défaut (PA + 4 derniers chiffres)
        // Comparaison insensible à la casse pour la partie "PA"
        if (!$password_valid && strcasecmp($password, $default_code) === 0) {
            $password_valid = true;
            error_log('COLIS224 AUTH: Authentification avec code par défaut PA+4chiffres');
        }

        // Si aucune des deux options ne fonctionne, refuser
        if (!$password_valid) {
            return array(
                'success' => false,
                'message' => 'Code client incorrect. Utilisez votre code personnalisé ou PA + les 4 derniers chiffres de votre numéro.'
            );
        }

        // Créer l'utilisateur WordPress s'il n'existe pas
        $user_id = self::get_wp_user_id_by_client_id($client->id);

        if (!$user_id) {
            $user_id = self::create_wp_user_for_client($client->id);

            if (is_wp_error($user_id)) {
                return array(
                    'success' => false,
                    'message' => 'Erreur lors de la création du compte : ' . $user_id->get_error_message()
                );
            }
        }

        // Récupérer l'utilisateur WordPress
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            return array(
                'success' => false,
                'message' => 'Erreur : utilisateur introuvable.'
            );
        }

        // Connecter l'utilisateur avec WordPress (seulement si demandé)
        if ($set_cookie) {
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true); // true = remember me
        }

        error_log('COLIS224 AUTH: Client connecté avec succès - client_id=' . $client->id . ', user_id=' . $user_id . ', phone=' . $phone . ', set_cookie=' . ($set_cookie ? 'true' : 'false'));

        return array(
            'success' => true,
            'message' => 'Connexion réussie ! Bienvenue ' . $client->name,
            'user_id' => $user_id,
            'client_id' => $client->id
        );
    }

    /**
     * Redirection après connexion
     *
     * @param string $redirect_to URL de redirection
     * @param string $request URL demandée
     * @param WP_User|WP_Error $user Utilisateur connecté
     * @return string URL de redirection
     */
    public function client_login_redirect($redirect_to, $request, $user) {
        // Vérifier si c'est un client Colis224
        if (!is_wp_error($user) && $user && in_array(self::ROLE_NAME, (array) $user->roles)) {
            // Rediriger vers la page du portail client
            $portal_page = get_option('colis224_portal_page_id');

            if ($portal_page) {
                return get_permalink($portal_page);
            }

            // Sinon, chercher une page avec le shortcode
            $pages = get_posts(array(
                'post_type' => 'page',
                's' => '[colis224_client_portal]',
                'posts_per_page' => 1
            ));

            if (!empty($pages)) {
                return get_permalink($pages[0]->ID);
            }

            // Par défaut, rediriger vers l'accueil
            return home_url();
        }

        return $redirect_to;
    }

    /**
     * Ajouter des instructions sur la page de connexion
     */
    public function add_login_instructions($message) {
        // Ajouter un message d'information pour les clients
        $client_message = '<div class="message" style="border-left: 4px solid #667eea; padding: 12px; margin-bottom: 20px; background: #f0f0ff;">';
        $client_message .= '<strong>📱 Clients Colis224 :</strong> Entrez votre <strong>numéro de téléphone</strong> comme identifiant.';
        $client_message .= '<br><strong>🔒 Mot de passe :</strong> ';
        $client_message .= '<ul style="margin: 5px 0 5px 20px; padding: 0;">';
        $client_message .= '<li>Par défaut : <strong style="color: #667eea;">PA</strong> suivi des <strong>4 derniers chiffres</strong> de votre téléphone (ex : PA2244)</li>';
        $client_message .= '<li>Ou votre code personnalisé si vous en avez un</li>';
        $client_message .= '</ul>';
        $client_message .= '<small style="color: #666;">💡 Le PA peut être en majuscule ou minuscule (PA, pa, Pa, pA). Pour un code personnalisé, contactez-nous via <a href="https://wa.me/224620002244" target="_blank" style="color: #25D366; font-weight: bold;">WhatsApp</a>.</small>';
        $client_message .= '</div>';

        return $client_message . $message;
    }

    /**
     * Gérer la déconnexion d'un client
     */
    public function handle_client_logout() {
        error_log('COLIS224 LOGOUT: Client déconnecté avec succès');
    }

    /**
     * Permettre l'authentification par téléphone sur wp-login.php
     *
     * @param WP_User|WP_Error|null $user
     * @param string $username
     * @param string $password
     * @return WP_User|WP_Error
     */
    public function authenticate_by_phone_on_login($user, $username, $password) {
        // Si un utilisateur est déjà authentifié, on le retourne
        if ($user instanceof WP_User) {
            return $user;
        }

        // Vérifier si le username ressemble à un numéro de téléphone
        $is_phone = preg_match('/^[\d\s\+\-\(\)]+$/', $username);

        if (!$is_phone) {
            // Ce n'est pas un numéro de téléphone, laisser WordPress gérer
            return $user;
        }

        // Nettoyer le numéro de téléphone
        $phone = sanitize_text_field($username);

        error_log('COLIS224 WP-LOGIN: Tentative de connexion par téléphone - phone=' . $phone);

        // Utiliser notre méthode d'authentification par téléphone
        // false = ne pas définir le cookie, WordPress le fera après
        $result = self::authenticate_by_phone($phone, $password, false);

        if ($result['success']) {
            // Récupérer l'utilisateur WordPress
            $wp_user = get_user_by('ID', $result['user_id']);

            if ($wp_user) {
                error_log('COLIS224 WP-LOGIN: Authentification réussie - user_id=' . $result['user_id'] . ', client_id=' . $result['client_id']);
                return $wp_user;
            }
        }

        // Échec de l'authentification
        error_log('COLIS224 WP-LOGIN: Échec authentification - ' . $result['message']);
        return new WP_Error('invalid_phone', '<strong>Erreur</strong> : ' . $result['message']);
    }

    /**
     * Personnaliser la page de connexion (optionnel)
     */
    public function customize_login_page() {
        ?>
        <style>
            /* Personnalisation légère de la page de connexion pour les clients */
            body.login {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .login h1 a {
                background-image: url(<?php echo COLIS224_PLUGIN_URL . 'assets/images/logo.png'; ?>) !important;
                background-size: contain;
                width: 100%;
            }
            /* Modifier les labels pour les clients */
            body.login label[for="user_login"]::after {
                content: " (ou numéro de téléphone)";
                font-size: 0.9em;
                color: #667eea;
                font-weight: normal;
            }
        </style>
        <?php
    }

    /**
     * Obtenir l'ID du client actuellement connecté
     *
     * @return int|false ID du client ou false
     */
    public static function get_current_client_id() {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        return self::get_client_id_by_wp_user_id($user_id);
    }

    /**
     * Vérifier si l'utilisateur actuel est un client Colis224
     *
     * @return bool
     */
    public static function is_client_logged_in() {
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        return in_array(self::ROLE_NAME, (array) $user->roles);
    }
}
