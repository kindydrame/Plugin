<?php
/**
 * Plugin Name: Colis224 Logistics Manager
 * Plugin URI: https://colis224.com
 * Description: Système complet de gestion logistique pour entreprise de livraison internationale (Chine, France, Maroc, Sénégal, Côte d'Ivoire, Guinée)
 * Version: 2.20.10
 * Author: Colis224
 * Author URI: https://colis224.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: colis224-logistics
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Si accédé directement, bloquer
if (!defined('ABSPATH')) {
    exit;
}

// Constantes du plugin
define('COLIS224_VERSION', '2.20.10');
define('COLIS224_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COLIS224_PLUGIN_URL', plugin_dir_url(__FILE__));
define('COLIS224_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Démarrer la session pour le portail client (seulement si pas en cours d'activation)
// Configuration des cookies de session pour compatibilité navigateurs modernes
if (!defined('WP_CLI') && !session_id() && !headers_sent()) {
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', is_ssl() ? '1' : '0');
    ini_set('session.cookie_path', '/');
    @session_start();
}

/**
 * Code d'activation du plugin
 */
function activate_colis224_logistics() {
    require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-activator.php';
    Colis224_Activator::activate();
}

/**
 * Code de désactivation du plugin
 */
function deactivate_colis224_logistics() {
    require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-deactivator.php';
    Colis224_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_colis224_logistics');
register_deactivation_hook(__FILE__, 'deactivate_colis224_logistics');

/**
 * Classe principale du plugin
 */
class Colis224_Logistics_Manager {

    protected static $instance = null;

    private function __construct() {
        $this->load_dependencies();
        $this->check_database_version();
        $this->set_locale();
        $this->init_auth(); // Initialiser l'authentification client
        $this->init_frontend(); // Initialiser les classes frontend
        $this->init_dashboard_filters(); // Initialiser les filtres avancés (v2.18.22)
        $this->define_admin_hooks();
    }
    
    /**
     * Initialiser l'authentification client (frontend + admin)
     * NOUVELLE VERSION: Utilise le système d'utilisateurs WordPress natif
     */
    private function init_auth() {
        // NOUVEAU: Synchronisation avec utilisateurs WordPress
        if (class_exists('Colis224_WP_User_Sync')) {
            new Colis224_WP_User_Sync();
        }

        // ANCIEN: Session PHP personnalisée (conservé pour compatibilité)
        // TODO: Sera supprimé dans une future version
        if (class_exists('Colis224_Client_Auth')) {
            // new Colis224_Client_Auth(); // Désactivé - on utilise WP maintenant
        }
    }

    /**
     * Initialiser les classes frontend (portail client, etc.)
     */
    private function init_frontend() {
        // Portail client frontend (AJAX, shortcodes, tracking)
        if (class_exists('Colis224_Frontend_Portal')) {
            new Colis224_Frontend_Portal();
        }

        // Portail client amélioré
        if (class_exists('Colis224_Client_Portal_Enhanced')) {
            new Colis224_Client_Portal_Enhanced();
        }

        // Widget départs
        if (class_exists('Colis224_Departures_Widget')) {
            new Colis224_Departures_Widget();
        }

        // Notifications
        if (class_exists('Colis224_Notifications')) {
            new Colis224_Notifications();
        }

        // QR Code
        if (class_exists('Colis224_QRCode')) {
            new Colis224_QRCode();
        }

        // Autocomplete
        if (class_exists('Colis224_Autocomplete')) {
            new Colis224_Autocomplete();
        }

        // Live Chat
        if (class_exists('Colis224_Live_Chat')) {
            new Colis224_Live_Chat();
        }

        // Portail Partenaire
        if (class_exists('Colis224_Partner_Portal')) {
            new Colis224_Partner_Portal();
        }

        // Portail Agent (v2.18.2)
        if (class_exists('Colis224_Agent_Portal')) {
            new Colis224_Agent_Portal();
        }

        // Système d'avis
        if (class_exists('Colis224_Reviews')) {
            new Colis224_Reviews();
        }

        // UI Enhancements
        if (class_exists('Colis224_UI_Enhancements')) {
            new Colis224_UI_Enhancements();
        }

        // Calculateur frontend (v2.20.0) - Shortcode [colis224_calculator]
        if (class_exists('Colis224_Frontend_Calculator')) {
            Colis224_Frontend_Calculator::init();
        }
    }

    /**
     * Initialiser le système de filtres avancés du dashboard (v2.18.22)
     */
    private function init_dashboard_filters() {
        // Enregistrer les hooks pour afficher les filtres dans le dashboard
        add_action('colis224_dashboard_before_stats', function() {
            if (current_user_can('manage_options') || current_user_can('colis224_view_parcels')) {
                echo Colis224_Dashboard_Filters::render_filters_panel();
            }
        });

        // Enqueue des assets JS et CSS pour les filtres
        add_action('admin_enqueue_scripts', function($hook) {
            // v2.18.28: Charger sur le dashboard et toutes les pages colis224
            if ($hook !== 'toplevel_page_colis224-dashboard' && strpos($hook, 'colis224') === false) {
                return;
            }

            // CSS des filtres avancés
            wp_enqueue_style(
                'colis224-dashboard-advanced',
                COLIS224_PLUGIN_URL . 'assets/css/dashboard-advanced.css',
                array(),
                COLIS224_VERSION
            );

            // JavaScript des filtres
            wp_enqueue_script(
                'colis224-dashboard-filters',
                COLIS224_PLUGIN_URL . 'assets/js/dashboard-filters.js',
                array('jquery'),
                COLIS224_VERSION,
                true
            );

            // v2.18.28: Variables AJAX pour JavaScript (TOUJOURS charger sur pages colis224)
            wp_localize_script('colis224-dashboard-filters', 'colis224_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'dashboard_nonce' => wp_create_nonce('colis224_dashboard_nonce'),
                'debug' => WP_DEBUG // Pour faciliter le debugging
            ));
        }, 100); // Priorité plus haute pour s'assurer que c'est chargé
    }

    /**
     * Vérifier et mettre à jour la base de données si nécessaire
     */
    private function check_database_version() {
        $installed_version = get_option('colis224_version', '0.0.0');

        // Si la version installée est différente de la version actuelle, mettre à jour
        if (version_compare($installed_version, COLIS224_VERSION, '<')) {
            require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-database.php';

            // Créer/mettre à jour toutes les tables
            Colis224_Database::create_tables();

            // Créer les données par défaut si nécessaire
            if (version_compare($installed_version, '2.3.0', '<')) {
                // Nouvelles fonctionnalités v2.3.0
                require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-loyalty.php';
                require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-warehouses.php';

                Colis224_Loyalty::create_default_program();
                Colis224_Warehouses::create_default_warehouse();
            }

            // Mettre à jour la version
            update_option('colis224_version', COLIS224_VERSION);
        }
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_dependencies() {
        // Chargement des classes principales (OBLIGATOIRES)
        $required_files = array(
            COLIS224_PLUGIN_DIR . 'includes/class-colis224-database.php',
            COLIS224_PLUGIN_DIR . 'includes/class-colis224-db-upgrade.php', // Migration BDD v2.18.3
            COLIS224_PLUGIN_DIR . 'includes/class-colis224-admin-alerts.php', // Alertes admin v2.18.4
            COLIS224_PLUGIN_DIR . 'includes/class-colis224-admin.php',
            COLIS224_PLUGIN_DIR . 'includes/class-colis224-sanitizer.php'
        );
        
        foreach ($required_files as $file) {
            if (file_exists($file)) {
                require_once $file;
            } else {
                add_action('admin_notices', function() use ($file) {
                    echo '<div class="error"><p>Erreur Colis224: Fichier manquant: ' . esc_html(basename($file)) . '</p></div>';
                });
                return;
            }
        }

        // Système d'historique des colis (v2.11.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-parcel-history.php';

        // Système d'archivage automatique (v2.12.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-archiving.php';

        // Système de détection et fusion des doublons (v2.13.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-duplicate-detector.php';

        // Système de gestion des lots internationaux (v2.14.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-batches.php';

        // Système d'import CSV (v2.15.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-csv-importer.php';

        // Charger l'authentification client après que WordPress soit prêt
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-client-auth.php';

        // NOUVEAU v2.18.9: Synchronisation avec utilisateurs WordPress
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-wp-user-sync.php';

        // Chargement des modules admin
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-diagnostic.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-validation.php';       // v2.11.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-archives.php';         // v2.12.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-duplicates.php';       // v2.13.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-batches-admin.php';   // v2.14.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-csv-import-admin.php';  // v2.15.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-colis.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-clients.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-message-templates.php';  // v2.18.4
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-partenaires.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-equipe.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-comptabilite.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-rapports.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-parametres.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-achat.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-dashboard.php';

        // Système de filtres avancés dashboard (v2.18.22)
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-dashboard-filters.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-dashboard-advanced-stats.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-stats-ajax.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-emojis.php';        // v2.18.26: Helper pour emojis

        // Système de grilles tarifaires et calculateurs (v2.19.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-pricing-data.php';   // Données tarifaires
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-pricing-engine.php'; // Moteur de calcul
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-pricing.php';           // Page admin grilles

        // Calculateur frontend pour les clients (v2.20.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-frontend-calculator.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-frontend-requests.php';    // v2.20.6: Page admin devis frontend

        // Nouvelles fonctionnalités v2.0
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-frontend-portal.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-notifications.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-qrcode.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-invoice.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-autocomplete.php';

        // Nouvelles fonctionnalités v2.2+ (5 Modules Majeurs)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-mobile-money.php';      // Paiements Mobile Money
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-rest-api.php';          // API REST complète
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-permissions.php';       // Système de permissions
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-export.php';            // Export avancé
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-sms-api.php';           // SMS API
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-nimbasms.php';          // NimbaSMS Integration (v2.18.6)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-activator-nimbasms.php'; // NimbaSMS Auto-Activation (v2.18.6)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-diagnostic-client.php'; // Diagnostic Espace Client (TEMPORAIRE - v2.18.6)

        // Nouvelles fonctionnalités v2.3+ (Programme Fidélité + Multi-Entrepôts)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-loyalty.php';           // Programme de fidélité
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-warehouses.php';        // Multi-entrepôts
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-fidelite.php';            // Admin fidélité
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-entrepots.php';           // Admin entrepôts

        // Nouvelles fonctionnalités v2.6+ (Support SAV + PayPal)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-support-tickets.php';   // Système de tickets support
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-support-admin.php';       // Admin support
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-paypal.php';           // Intégration PayPal

        // Nouvelles fonctionnalités v2.7+ (Surveillance)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-auto-reminders.php';   // Relances automatiques
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-surveillance-admin.php';  // Admin surveillance

        // Nouvelles fonctionnalités v2.8+ (Live Chat, Inventory, MultiLang)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-live-chat.php';         // Live Chat Support
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-live-chat-admin.php';     // Admin Live Chat
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-inventory-advanced.php'; // Gestion stock avancée
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-partner-portal.php';    // Portail Partenaire
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-agent-portal.php';      // Portail Agent (v2.18.2)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-multilang.php';         // Multi-langues

        // Nouvelles fonctionnalités v2.9+ (Scheduling, Analytics)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-scheduling.php';        // Réservation et planification
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-analytics.php';         // Tableau de bord analytique

        // Nouvelles fonctionnalités v2.10+ (Departures & Reservations)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-departures.php';        // Système de départs
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-departures-admin.php';     // Admin départs
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-departures-widget.php'; // Widget départs
        
        // Nouvelles fonctionnalités v2.10.6+ (Departures Automation)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-departures-automation.php';        // Automatisation départs
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-departures-automation-admin.php';     // Admin automatisation
        
        // Nouvelles fonctionnalités v2.10.7+ (Enhanced Client Portal)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-client-portal-enhanced.php';       // Espace client amélioré
        
        // Améliorations UI/UX v2.10.12+
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-ui-enhancements.php';              // Améliorations interface
    }

    private function set_locale() {
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'colis224-logistics',
            false,
            dirname(COLIS224_PLUGIN_BASENAME) . '/languages/'
        );
    }

    private function define_admin_hooks() {
        $admin = new Colis224_Admin();

        // Hooks admin
        add_action('admin_menu', array($admin, 'add_plugin_admin_menu'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));

        // Enregistrer les scripts/styles des calculateurs de prix (v2.19.0)
        add_action('admin_enqueue_scripts', array($this, 'enqueue_pricing_assets'));

        // AJAX handler pour les calculateurs (v2.19.0)
        add_action('wp_ajax_colis224_calculate_pricing', array($this, 'ajax_calculate_pricing'));
    }

    /**
     * Charger les assets CSS/JS des calculateurs de prix
     * v2.19.0
     */
    public function enqueue_pricing_assets($hook) {
        // Charger uniquement sur la page grilles tarifaires
        if ($hook !== 'toplevel_page_colis224-pricing' && strpos($hook, 'colis224-pricing') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'colis224-pricing-calculator',
            COLIS224_PLUGIN_URL . 'assets/css/pricing-calculator.css',
            array(),
            COLIS224_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'colis224-pricing-calculator',
            COLIS224_PLUGIN_URL . 'assets/js/pricing-calculator.js',
            array('jquery'),
            COLIS224_VERSION,
            true
        );

        // Passer des variables à JavaScript
        wp_localize_script('colis224-pricing-calculator', 'colis224Ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colis224_pricing_nonce')
        ));
    }

    /**
     * AJAX Handler pour les calculs de prix
     * v2.19.0
     */
    public function ajax_calculate_pricing() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_pricing_nonce')) {
            wp_send_json_error(array('message' => 'Erreur de sécurité'));
            return;
        }

        $calculator = sanitize_text_field($_POST['calculator']);

        switch ($calculator) {
            case 'guinea_world':
                $country = sanitize_text_field($_POST['country']);
                $city = sanitize_text_field($_POST['city']);
                $delivery_mode = sanitize_text_field($_POST['delivery_mode']);
                $parcel_type = sanitize_text_field($_POST['parcel_type']);
                $quantity = intval($_POST['quantity']);

                $result = Colis224_Pricing_Engine::calculate_guinea_world(
                    $country,
                    $delivery_mode,
                    $parcel_type,
                    $quantity,
                    $city
                );

                if ($result['success']) {
                    wp_send_json_success($result);
                } else {
                    wp_send_json_error($result);
                }
                break;

            default:
                wp_send_json_error(array('message' => 'Calculateur invalide'));
        }
    }
}

/**
 * Démarrage du plugin
 */
function run_colis224_logistics() {
    return Colis224_Logistics_Manager::get_instance();
}

run_colis224_logistics();
