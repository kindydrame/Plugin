<?php
/**
 * Portail Agent Frontend - Espace Agent
 * Version: 2.18.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Agent_Portal {

    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('login_redirect', array($this, 'custom_login_redirect'), 10, 3);
    }

    /**
     * Redirection personnalisée après connexion
     */
    public function custom_login_redirect($redirect_to, $request, $user) {
        // Vérifier si l'utilisateur a un rôle autorisé
        if (isset($user->roles) && is_array($user->roles)) {
            $allowed_roles = array('colis224_agent', 'colis224_manager', 'administrator', 'editor', 'author');

            foreach ($allowed_roles as $role) {
                if (in_array($role, $user->roles)) {
                    // Si la demande vient de la page portail agent, y rediriger
                    if (strpos($request, 'espace-agent') !== false) {
                        return $request;
                    }
                    // Sinon, chercher la page espace-agent
                    $agent_page = get_page_by_path('espace-agent');
                    if ($agent_page) {
                        return get_permalink($agent_page->ID);
                    }
                    break;
                }
            }
        }

        return $redirect_to;
    }

    /**
     * Enregistrer les shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('colis224_agent_portal', array($this, 'agent_portal_shortcode'));
    }

    /**
     * Charger les assets
     */
    public function enqueue_assets() {
        if (is_page()) {
            wp_enqueue_style('dashicons');
            wp_enqueue_style(
                'colis224-agent-portal',
                COLIS224_PLUGIN_URL . 'assets/css/frontend-style.css',
                array(),
                COLIS224_VERSION
            );

            wp_enqueue_script(
                'colis224-agent-portal',
                COLIS224_PLUGIN_URL . 'assets/js/frontend-script.js',
                array('jquery'),
                COLIS224_VERSION,
                true
            );
        }
    }

    /**
     * Shortcode du portail agent [colis224_agent_portal]
     */
    public function agent_portal_shortcode($atts) {
        // Vérifier si l'utilisateur est connecté
        if (!is_user_logged_in()) {
            return $this->display_login_form();
        }

        // Vérifier si l'utilisateur est un agent
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;

        $is_agent = in_array('colis224_agent', $user_roles) ||
                    in_array('colis224_manager', $user_roles) ||
                    in_array('administrator', $user_roles) ||
                    in_array('editor', $user_roles) ||
                    in_array('author', $user_roles);

        if (!$is_agent) {
            return $this->display_access_denied();
        }

        // Afficher le tableau de bord agent
        return $this->display_agent_dashboard();
    }

    /**
     * Afficher le formulaire de connexion
     */
    private function display_login_form() {
        ob_start();
        ?>
        <div class="colis224-agent-portal">
            <div class="colis224-login-container" style="max-width: 500px; margin: 50px auto; padding: 40px; background: #fff; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="color: #667eea; font-size: 32px; margin: 0 0 10px 0;">
                        <span class="dashicons dashicons-admin-users" style="font-size: 40px; vertical-align: middle;"></span>
                        Espace Agent
                    </h2>
                    <p style="color: #666; font-size: 16px; margin: 0;">Connectez-vous pour accéder à votre espace</p>
                </div>

                <?php
                // Afficher le formulaire de connexion WordPress
                $args = array(
                    'echo'           => true,
                    'redirect'       => get_permalink(),
                    'form_id'        => 'colis224-agent-loginform',
                    'label_username' => 'Nom d\'utilisateur ou Email',
                    'label_password' => 'Mot de passe',
                    'label_remember' => 'Se souvenir de moi',
                    'label_log_in'   => 'Se connecter',
                    'remember'       => true,
                    'value_remember' => true,
                );
                wp_login_form($args);
                ?>

                <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">
                    <p style="color: #999; font-size: 14px; margin: 0;">
                        <span class="dashicons dashicons-lock" style="vertical-align: middle;"></span>
                        Connexion sécurisée réservée aux agents, éditeurs et administrateurs
                    </p>
                </div>

                <style>
                    #colis224-agent-loginform {
                        text-align: left;
                    }
                    #colis224-agent-loginform p {
                        margin-bottom: 15px;
                    }
                    #colis224-agent-loginform label {
                        display: block;
                        margin-bottom: 5px;
                        color: #333;
                        font-weight: 600;
                        font-size: 14px;
                    }
                    #colis224-agent-loginform input[type="text"],
                    #colis224-agent-loginform input[type="password"] {
                        width: 100%;
                        padding: 12px;
                        border: 2px solid #e0e0e0;
                        border-radius: 6px;
                        font-size: 16px;
                        transition: border-color 0.3s;
                        box-sizing: border-box;
                    }
                    #colis224-agent-loginform input[type="text"]:focus,
                    #colis224-agent-loginform input[type="password"]:focus {
                        border-color: #667eea;
                        outline: none;
                    }
                    #colis224-agent-loginform input[type="submit"] {
                        width: 100%;
                        padding: 14px;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: #fff;
                        border: none;
                        border-radius: 6px;
                        font-size: 16px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: transform 0.2s;
                    }
                    #colis224-agent-loginform input[type="submit"]:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
                    }
                    #colis224-agent-loginform .login-remember {
                        display: flex;
                        align-items: center;
                    }
                    #colis224-agent-loginform .login-remember label {
                        margin: 0 0 0 5px;
                        font-weight: normal;
                    }
                </style>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Afficher le message d'accès refusé
     */
    private function display_access_denied() {
        ob_start();
        ?>
        <div class="colis224-agent-portal">
            <div class="colis224-access-denied" style="max-width: 600px; margin: 50px auto; padding: 40px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 12px; text-align: center;">
                <span class="dashicons dashicons-warning" style="font-size: 60px; color: #ffc107;"></span>
                <h2 style="color: #856404; margin: 20px 0 10px 0;">Accès Refusé</h2>
                <p style="color: #856404; font-size: 16px; margin: 0 0 20px 0;">
                    Vous n'avez pas les permissions nécessaires pour accéder à cet espace.
                </p>
                <p style="color: #856404; font-size: 14px; margin: 0;">
                    Cet espace est réservé aux agents, gestionnaires, éditeurs et administrateurs Colis224.
                </p>
                <div style="margin-top: 30px;">
                    <a href="<?php echo wp_logout_url(home_url()); ?>" style="display: inline-block; padding: 12px 30px; background: #ffc107; color: #856404; text-decoration: none; border-radius: 6px; font-weight: 600;">
                        Se déconnecter
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Afficher le tableau de bord agent
     */
    private function display_agent_dashboard() {
        $current_user = wp_get_current_user();
        $admin_url = admin_url('admin.php?page=colis224-dashboard');

        ob_start();
        ?>
        <div class="colis224-agent-portal">
            <div class="colis224-agent-dashboard" style="max-width: 1200px; margin: 0 auto; padding: 20px;">

                <!-- En-tête -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 40px; margin-bottom: 30px; color: #fff; text-align: center;">
                    <h1 style="margin: 0 0 10px 0; font-size: 36px;">
                        <span class="dashicons dashicons-admin-users" style="font-size: 40px; vertical-align: middle;"></span>
                        Bienvenue, <?php echo esc_html($current_user->display_name); ?> !
                    </h1>
                    <p style="margin: 0; font-size: 18px; opacity: 0.9;">
                        Espace Agent Colis224 - Gestion de vos opérations
                    </p>
                </div>

                <!-- Actions rapides -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px;">

                    <a href="<?php echo admin_url('admin.php?page=colis224-dashboard'); ?>" style="display: block; background: #fff; border-radius: 12px; padding: 30px; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s;">
                        <div style="text-align: center;">
                            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                <span class="dashicons dashicons-dashboard" style="font-size: 36px; color: #fff;"></span>
                            </div>
                            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 20px;">Tableau de Bord</h3>
                            <p style="margin: 0; color: #666; font-size: 14px;">Vue d'ensemble de vos activités</p>
                        </div>
                    </a>

                    <a href="<?php echo admin_url('admin.php?page=colis224-parcels&action=add'); ?>" style="display: block; background: #fff; border-radius: 12px; padding: 30px; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s;">
                        <div style="text-align: center;">
                            <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                <span class="dashicons dashicons-plus-alt" style="font-size: 36px; color: #fff;"></span>
                            </div>
                            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 20px;">Nouveau Colis</h3>
                            <p style="margin: 0; color: #666; font-size: 14px;">Créer un nouveau colis</p>
                        </div>
                    </a>

                    <a href="<?php echo admin_url('admin.php?page=colis224-parcels'); ?>" style="display: block; background: #fff; border-radius: 12px; padding: 30px; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s;">
                        <div style="text-align: center;">
                            <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                <span class="dashicons dashicons-archive" style="font-size: 36px; color: #fff;"></span>
                            </div>
                            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 20px;">Mes Colis</h3>
                            <p style="margin: 0; color: #666; font-size: 14px;">Gérer tous les colis</p>
                        </div>
                    </a>

                    <a href="<?php echo admin_url('admin.php?page=colis224-clients'); ?>" style="display: block; background: #fff; border-radius: 12px; padding: 30px; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s;">
                        <div style="text-align: center;">
                            <div style="background: linear-gradient(135deg, #FA8BFF 0%, #2BD2FF 90%); width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                <span class="dashicons dashicons-groups" style="font-size: 36px; color: #fff;"></span>
                            </div>
                            <h3 style="margin: 0 0 10px 0; color: #333; font-size: 20px;">Clients</h3>
                            <p style="margin: 0; color: #666; font-size: 14px;">Gérer les clients</p>
                        </div>
                    </a>

                </div>

                <!-- Informations -->
                <div style="background: #f8f9fa; border-radius: 12px; padding: 30px; border-left: 4px solid #667eea;">
                    <h3 style="margin: 0 0 15px 0; color: #333;">
                        <span class="dashicons dashicons-info" style="color: #667eea;"></span>
                        Informations
                    </h3>
                    <p style="margin: 0 0 10px 0; color: #666; line-height: 1.6;">
                        <strong>Rôle:</strong> <?php echo esc_html(implode(', ', $current_user->roles)); ?>
                    </p>
                    <p style="margin: 0 0 10px 0; color: #666; line-height: 1.6;">
                        <strong>Email:</strong> <?php echo esc_html($current_user->user_email); ?>
                    </p>
                    <p style="margin: 0; color: #666; line-height: 1.6;">
                        Pour accéder à toutes les fonctionnalités, utilisez le
                        <a href="<?php echo $admin_url; ?>" style="color: #667eea; text-decoration: none; font-weight: 600;">panneau d'administration →</a>
                    </p>
                </div>

                <!-- Déconnexion -->
                <div style="text-align: center; margin-top: 30px; padding-top: 30px; border-top: 2px solid #eee;">
                    <a href="<?php echo wp_logout_url(get_permalink()); ?>" style="display: inline-block; padding: 12px 30px; background: #dc3545; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; transition: background 0.3s;">
                        <span class="dashicons dashicons-exit" style="vertical-align: middle;"></span>
                        Se déconnecter
                    </a>
                </div>

            </div>
        </div>

        <style>
            .colis224-agent-dashboard a:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            }
        </style>
        <?php
        return ob_get_clean();
    }
}
