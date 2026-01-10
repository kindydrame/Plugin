<?php
/**
 * Portail Client Frontend - Suivi de Colis et Espace Client
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Frontend_Portal {

    public function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_ajax_nopriv_colis224_track_parcel', array($this, 'ajax_track_parcel'));
        add_action('wp_ajax_colis224_track_parcel', array($this, 'ajax_track_parcel'));
        add_action('wp_ajax_nopriv_colis224_client_login', array($this, 'ajax_client_login'));
        add_action('wp_ajax_colis224_client_login', array($this, 'ajax_client_login'));

        // Redirection automatique pour les QR codes
        add_action('template_redirect', array($this, 'auto_display_tracking'));
    }

    /**
     * Affichage automatique de la page de tracking depuis QR code
     */
    public function auto_display_tracking() {
        // Vérifier si le paramètre colis224_track est présent
        if (isset($_GET['colis224_track']) && !empty($_GET['colis224_track'])) {
            // Charger les assets frontend
            wp_enqueue_style('dashicons');
            wp_enqueue_style(
                'colis224-frontend',
                COLIS224_PLUGIN_URL . 'assets/css/frontend-style.css',
                array(),
                COLIS224_VERSION
            );

            wp_enqueue_script(
                'colis224-frontend',
                COLIS224_PLUGIN_URL . 'assets/js/frontend-script.js',
                array('jquery'),
                COLIS224_VERSION,
                true
            );

            wp_localize_script('colis224-frontend', 'colis224Frontend', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('colis224_frontend_nonce')
            ));

            // Afficher la page de tracking
            $this->display_tracking_page();
            exit;
        }
    }

    /**
     * Page de tracking complète (standalone)
     */
    private function display_tracking_page() {
        $tracking_number = sanitize_text_field($_GET['colis224_track']);

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>Suivi Colis <?php echo esc_html($tracking_number); ?> - Colis224</title>
            <?php wp_head(); ?>
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                }
                .tracking-page-wrapper {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 40px 20px;
                }
                .tracking-logo {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .tracking-logo h1 {
                    color: #fff;
                    font-size: 48px;
                    margin: 0;
                    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
                }
                .tracking-logo p {
                    color: rgba(255,255,255,0.9);
                    font-size: 18px;
                    margin: 10px 0 0 0;
                }
                .colis224-tracking-container {
                    background: #fff;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                    padding: 40px;
                }
                .back-link {
                    display: inline-block;
                    color: #fff;
                    text-decoration: none;
                    margin-bottom: 20px;
                    padding: 10px 20px;
                    background: rgba(255,255,255,0.2);
                    border-radius: 6px;
                    transition: background 0.3s;
                }
                .back-link:hover {
                    background: rgba(255,255,255,0.3);
                }
            </style>
        </head>
        <body>
            <div class="tracking-page-wrapper">
                <div class="tracking-logo">
                    <h1>📦 COLIS224</h1>
                    <p>Suivi de Colis en Temps Réel</p>
                </div>

                <?php echo $this->tracking_form_shortcode(array()); ?>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="<?php echo home_url(); ?>" class="back-link">
                        ← Retour à l'accueil
                    </a>
                </div>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Enregistrer les shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('colis224_tracking', array($this, 'tracking_form_shortcode'));
        add_shortcode('colis224_client_portal', array($this, 'client_portal_shortcode'));
    }

    /**
     * Charger les assets frontend
     */
    public function enqueue_frontend_assets() {
        if (is_page() || is_single()) {
            wp_enqueue_style(
                'colis224-frontend',
                COLIS224_PLUGIN_URL . 'assets/css/frontend-style.css',
                array(),
                COLIS224_VERSION . '.' . time() // Force cache refresh
            );

            wp_enqueue_script(
                'colis224-frontend',
                COLIS224_PLUGIN_URL . 'assets/js/frontend-script.js',
                array('jquery'),
                COLIS224_VERSION . '.' . time(), // Force cache refresh
                true
            );

            wp_localize_script('colis224-frontend', 'colis224Frontend', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('colis224_frontend_nonce')
            ));
        }
    }

    /**
     * Shortcode de suivi de colis [colis224_tracking]
     */
    public function tracking_form_shortcode($atts) {
        // Vérifier si un numéro de suivi est passé en paramètre GET (depuis QR code)
        $tracking_from_qr = isset($_GET['colis224_track']) ? sanitize_text_field($_GET['colis224_track']) : '';

        ob_start();
        ?>
        <div class="colis224-tracking-container">
            <div class="colis224-tracking-header">
                <h2><span class="dashicons dashicons-location"></span> Suivre Mon Colis</h2>
                <p>Entrez votre numéro de suivi pour connaître l'état de votre colis en temps réel</p>
            </div>

            <form id="colis224-tracking-form" class="colis224-tracking-form">
                <div class="colis224-tracking-input-group">
                    <input type="text"
                           id="tracking_number"
                           name="tracking_number"
                           value="<?php echo esc_attr($tracking_from_qr); ?>"
                           placeholder="Ex: PA1234"
                           required>
                    <button type="submit" class="colis224-btn colis224-btn-primary">
                        <span class="dashicons dashicons-search"></span> Suivre
                    </button>
                </div>
            </form>

            <div id="colis224-tracking-result" class="colis224-tracking-result"></div>
        </div>

        <?php if ($tracking_from_qr): ?>
        <script>
        jQuery(document).ready(function($) {
            // Déclencher automatiquement la recherche si le numéro vient du QR code
            $('#colis224-tracking-form').trigger('submit');
        });
        </script>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode du portail client [colis224_client_portal]
     */
    public function client_portal_shortcode($atts) {
        ob_start();

        // S'assurer que la session est démarrée
        if (!session_id() && !headers_sent()) {
            session_start();
        }

        // Afficher un message de déconnexion si présent ET si l'utilisateur n'est PAS connecté
        if (isset($_GET['logout']) && $_GET['logout'] === 'success' && !Colis224_Client_Auth::is_client_logged_in()) {
            echo '<div class="colis224-notice colis224-notice-success">
                <p>✅ Vous avez été déconnecté avec succès.</p>
            </div>';
        }

        // Vérifier si le client est connecté
        $is_logged_in = Colis224_Client_Auth::is_client_logged_in();

        if (!$is_logged_in) {
            $this->display_login_form();
        } else {
            // Vérifier si la session n'est pas expirée
            if (Colis224_Client_Auth::is_session_expired()) {
                Colis224_Client_Auth::logout_client();
                echo '<div class="colis224-notice colis224-notice-warning">
                    <p>⚠️ Votre session a expiré. Veuillez vous reconnecter.</p>
                </div>';
                $this->display_login_form();
            } else {
                // Rafraîchir la session
                Colis224_Client_Auth::refresh_session();
                $this->display_client_dashboard();
            }
        }

        return ob_get_clean();
    }

    /**
     * Formulaire de connexion client
     */
    private function display_login_form() {
        ?>
        <div class="colis224-portal-container">
            <div class="colis224-login-box">
                <div class="colis224-login-header">
                    <h2>Espace Client Colis224</h2>
                    <p>Connectez-vous pour accéder à votre espace personnel</p>
                </div>

                <form id="colis224-client-login-form" class="colis224-login-form">
                    <div class="colis224-form-group">
                        <label for="client_phone">Téléphone</label>
                        <input type="text" id="client_phone" name="client_phone"
                               placeholder="Votre numéro de téléphone" required>
                    </div>

                    <div class="colis224-form-group">
                        <label for="client_password">Code Client (optionnel)</label>
                        <input type="password" id="client_password" name="client_password"
                               placeholder="Votre code si vous en avez un">
                        <small>Si vous n'avez pas de code, laissez vide</small>
                    </div>

                    <button type="submit" class="colis224-btn colis224-btn-primary colis224-btn-block">
                        <span class="dashicons dashicons-admin-network"></span> Se Connecter
                    </button>
                </form>

                <div id="colis224-login-message"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Tableau de bord client
     */
    private function display_client_dashboard() {
        $client_id = Colis224_Client_Auth::get_current_client_id();
        
        if (!$client_id) {
            $this->display_login_form();
            return;
        }
        
        // Utiliser le nouvel espace client amélioré
        echo Colis224_Client_Portal_Enhanced::render_enhanced_portal($client_id);
    }

    /**
     * AJAX: Suivre un colis
     */
    public function ajax_track_parcel() {
        check_ajax_referer('colis224_frontend_nonce', 'nonce');

        $tracking_number = sanitize_text_field($_POST['tracking_number']);

        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_countries = $wpdb->prefix . 'colis224_countries';
        $table_transport = $wpdb->prefix . 'colis224_transport_modes';

        $parcel = $wpdb->get_row($wpdb->prepare("
            SELECT p.*, c.name as origin_country, t.name as transport_mode
            FROM $table_parcels p
            LEFT JOIN $table_countries c ON p.origin_country_id = c.id
            LEFT JOIN $table_transport t ON p.transport_mode_id = t.id
            WHERE p.tracking_number = %s
        ", $tracking_number));

        if (!$parcel) {
            wp_send_json_error(array(
                'message' => 'Numéro de suivi introuvable. Veuillez vérifier et réessayer.'
            ));
        }

        // Construire le HTML du résultat
        ob_start();
        ?>
        <div class="colis224-tracking-success">
            <div class="colis224-tracking-header-result">
                <h3><span class="dashicons dashicons-yes-alt"></span> Colis Trouvé !</h3>
                <p>Numéro de suivi: <strong><?php echo esc_html($parcel->tracking_number); ?></strong></p>
            </div>

            <div class="colis224-tracking-status">
                <div class="colis224-status-current">
                    <span class="colis224-badge colis224-badge-large colis224-badge-<?php echo sanitize_title($parcel->status); ?>">
                        <?php echo esc_html($parcel->status); ?>
                    </span>
                </div>

                <!-- Timeline -->
                <div class="colis224-timeline">
                    <div class="colis224-timeline-item <?php echo $parcel->reception_date ? 'completed' : ''; ?>">
                        <div class="colis224-timeline-icon">
                            <span class="dashicons dashicons-yes"></span>
                        </div>
                        <div class="colis224-timeline-content">
                            <h4>Colis Reçu</h4>
                            <p><?php echo $parcel->reception_date ? date('d/m/Y', strtotime($parcel->reception_date)) : 'En attente'; ?></p>
                        </div>
                    </div>

                    <div class="colis224-timeline-item <?php echo $parcel->shipping_date ? 'completed' : ''; ?>">
                        <div class="colis224-timeline-icon">
                            <span class="dashicons dashicons-airplane"></span>
                        </div>
                        <div class="colis224-timeline-content">
                            <h4>Expédié</h4>
                            <p><?php echo $parcel->shipping_date ? date('d/m/Y', strtotime($parcel->shipping_date)) : 'En attente'; ?></p>
                        </div>
                    </div>

                    <div class="colis224-timeline-item <?php echo $parcel->status == 'En transit' || $parcel->status == 'Livré' ? 'completed' : ''; ?>">
                        <div class="colis224-timeline-icon">
                            <span class="dashicons dashicons-location"></span>
                        </div>
                        <div class="colis224-timeline-content">
                            <h4>En Transit</h4>
                            <p><?php echo $parcel->status == 'En transit' || $parcel->status == 'Livré' ? 'En cours' : 'En attente'; ?></p>
                        </div>
                    </div>

                    <div class="colis224-timeline-item <?php echo $parcel->delivery_date ? 'completed' : ''; ?>">
                        <div class="colis224-timeline-icon">
                            <span class="dashicons dashicons-flag"></span>
                        </div>
                        <div class="colis224-timeline-content">
                            <h4>Livré</h4>
                            <p><?php echo $parcel->delivery_date ? date('d/m/Y', strtotime($parcel->delivery_date)) : 'Estimation: ' . ($parcel->estimated_delivery_date ? date('d/m/Y', strtotime($parcel->estimated_delivery_date)) : 'N/A'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="colis224-tracking-details">
                <h4>Informations du Colis</h4>
                <table class="colis224-details-table-frontend">
                    <tr>
                        <th>Destinataire:</th>
                        <td><?php echo esc_html($parcel->recipient_name); ?></td>
                    </tr>
                    <tr>
                        <th>Téléphone:</th>
                        <td><?php echo esc_html($parcel->recipient_phone); ?></td>
                    </tr>
                    <tr>
                        <th>Pays d'origine:</th>
                        <td><?php echo esc_html($parcel->origin_country ?: 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Mode de transport:</th>
                        <td><?php echo esc_html($parcel->transport_mode ?: 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Poids:</th>
                        <td><?php echo esc_html($parcel->weight); ?> kg</td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html
        ));
    }

    /**
     * AJAX: Connexion client
     */
    public function ajax_client_login() {
        // Nettoyer toute sortie parasite
        ob_start();

        // Vérifier le nonce avec gestion d'erreur
        if (!check_ajax_referer('colis224_frontend_nonce', 'nonce', false)) {
            ob_end_clean();
            wp_send_json_error(array(
                'message' => 'Erreur de sécurité. Veuillez rafraîchir la page et réessayer.'
            ));
        }

        $phone = isset($_POST['client_phone']) ? sanitize_text_field($_POST['client_phone']) : '';
        $password = isset($_POST['client_password']) ? $_POST['client_password'] : '';

        // Vérifier que le téléphone n'est pas vide
        if (empty($phone)) {
            ob_end_clean();
            wp_send_json_error(array(
                'message' => 'Veuillez entrer votre numéro de téléphone.'
            ));
        }

        // Vérifier que la classe existe
        if (!class_exists('Colis224_Client_Auth')) {
            ob_end_clean();
            wp_send_json_error(array(
                'message' => 'Erreur système. Veuillez contacter l\'administrateur.'
            ));
        }

        try {
            // Utiliser la nouvelle classe d'authentification
            $result = Colis224_Client_Auth::authenticate_by_phone($phone, $password);

            if ($result['success']) {
                // Préparer l'URL de redirection
                $redirect_url = '';
                if (isset($_POST['redirect_url'])) {
                    $redirect_url = esc_url_raw($_POST['redirect_url']);
                }

                if (empty($redirect_url)) {
                    $redirect_url = wp_get_referer();
                }

                if (empty($redirect_url)) {
                    $redirect_url = home_url();
                }

                ob_end_clean();
                wp_send_json_success(array(
                    'message' => $result['message'],
                    'redirect_url' => $redirect_url
                ));
            } else {
                ob_end_clean();
                wp_send_json_error(array(
                    'message' => $result['message']
                ));
            }
        } catch (Exception $e) {
            ob_end_clean();
            wp_send_json_error(array(
                'message' => 'Une erreur est survenue lors de la connexion. Veuillez réessayer.'
            ));
        }
    }
}
