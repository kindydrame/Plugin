<?php
/**
 * Plugin Name: Colis224 Logistics Manager
 * Plugin URI: https://colis224.com
 * Description: Système complet de gestion logistique pour entreprise de livraison internationale (Chine, France, Maroc, Sénégal, Côte d'Ivoire, Guinée)
 * Version: 2.18.1
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
define('COLIS224_VERSION', '2.18.1');
define('COLIS224_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COLIS224_PLUGIN_URL', plugin_dir_url(__FILE__));
define('COLIS224_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Démarrer la session pour le portail client (seulement si pas en cours d'activation)
if (!defined('WP_CLI') && !session_id() && !headers_sent()) {
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
        $this->define_admin_hooks();
    }
    
    /**
     * Initialiser l'authentification client (frontend + admin)
     */
    private function init_auth() {
        if (class_exists('Colis224_Client_Auth')) {
            new Colis224_Client_Auth();
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

        // 🆕 Portail agent (v2.18.0) - Espace séparé pour agents
        if (class_exists('Colis224_Agent_Portal')) {
            new Colis224_Agent_Portal();
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

        // Système d'avis
        if (class_exists('Colis224_Reviews')) {
            new Colis224_Reviews();
        }

        // UI Enhancements
        if (class_exists('Colis224_UI_Enhancements')) {
            new Colis224_UI_Enhancements();
        }
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

            // Exécuter les migrations DB (v2.18.0+)
            require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-db-migration.php';
            Colis224_DB_Migration::run_migrations();

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

        // Migration système de validation hiérarchique (v2.11.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-migration-validation.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-parcel-history.php';

        // Système d'archivage automatique (v2.12.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-migration-archiving.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-archiving.php';

        // Système de détection et fusion des doublons (v2.13.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-duplicate-detector.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-migration-duplicates.php';

        // Système de gestion des lots internationaux (v2.14.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-migration-batches.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-batches.php';

        // Système d'import CSV (v2.15.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-csv-importer.php';

        // Charger l'authentification client après que WordPress soit prêt
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-client-auth.php';

        // Chargement des modules admin
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-diagnostic.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-migration-admin.php';  // v2.11.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-validation.php';       // v2.11.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-migration-archiving-admin.php';  // v2.12.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-archives.php';         // v2.12.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-duplicates.php';       // v2.13.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-migration-duplicates-admin.php';  // v2.13.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-batches-admin.php';   // v2.14.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-migration-batches-admin.php';  // v2.14.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-csv-import-admin.php';  // v2.15.0
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-colis.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-clients.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-countries-admin.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-partenaires.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-equipe.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-comptabilite.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-rapports.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-parametres.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-achat.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-dashboard.php';

        // Nouvelles fonctionnalités v2.0
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-frontend-portal.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-notifications.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-qrcode.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-invoice.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-autocomplete.php';

        // Système d'approbation et espace agent (v2.18.0)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-db-migration.php';
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-agent-portal.php';
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-approvals.php';

        // Nouvelles fonctionnalités v2.2+ (5 Modules Majeurs)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-mobile-money.php';      // Paiements Mobile Money
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-rest-api.php';          // API REST complète
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-permissions.php';       // Système de permissions
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-export.php';            // Export avancé
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-sms-api.php';           // SMS API

        // Nouvelles fonctionnalités v2.3+ (Programme Fidélité + Multi-Entrepôts)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-loyalty.php';           // Programme de fidélité
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-warehouses.php';        // Multi-entrepôts
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-fidelite.php';            // Admin fidélité
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-entrepots.php';           // Admin entrepôts

        // Nouvelles fonctionnalités v2.6+ (Support SAV + PayPal)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-support-tickets.php';   // Système de tickets support
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-support-admin.php';       // Admin support
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-paypal.php';           // Intégration PayPal

        // Nouvelles fonctionnalités v2.7+ (Surveillance & Avis)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-auto-reminders.php';   // Relances automatiques
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-surveillance-admin.php';  // Admin surveillance
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-reviews.php';          // Système d'avis

        // Nouvelles fonctionnalités v2.8+ (Live Chat, Inventory, Marketing, Workflows, MultiLang)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-live-chat.php';         // Live Chat Support
        require_once COLIS224_PLUGIN_DIR . 'admin/class-colis224-live-chat-admin.php';     // Admin Live Chat
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-inventory-advanced.php'; // Gestion stock avancée
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-marketing.php';         // Marketing Automation
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-partner-portal.php';    // Portail Partenaire
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-workflows.php';         // Automatisation Workflows
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-multilang.php';         // Multi-langues

        // Nouvelles fonctionnalités v2.9+ (Fleet, Insurance, Scheduling, Analytics)
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-fleet-management.php';  // Gestion de flotte
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-insurance.php';         // Assurance et déclarations
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

        // Page de validation des actions agents (v2.18.0)
        if (class_exists('Colis224_Approvals_Admin')) {
            new Colis224_Approvals_Admin();
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
