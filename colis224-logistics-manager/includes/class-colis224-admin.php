<?php
/**
 * Classe d'administration principale
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Admin {

    public function __construct() {
        // Constructor
    }

    /**
     * Obtenir la capability requise pour une page
     */
    private static function get_page_capability($page) {
        // Mapping des pages vers les capabilities
        $page_capabilities = array(
            'colis224-dashboard' => 'colis224_view_parcels',
            'colis224-parcels' => 'colis224_view_parcels',
            'colis224-validation' => 'colis224_manage_all', // Seulement admin/manager
            'colis224-clients' => 'colis224_manage_clients',
            'colis224-countries' => 'colis224_manage_all',
            'colis224-partners' => 'colis224_manage_all',
            'colis224-team' => 'colis224_manage_all',
            'colis224-accounting' => 'colis224_manage_accounting',
            'colis224-reports' => 'colis224_view_reports',
            'colis224-purchases' => 'colis224_manage_all',
            'colis224-loyalty' => 'colis224_manage_all',
            'colis224-warehouses' => 'colis224_manage_all',
            'colis224-support' => 'colis224_manage_all',
            'colis224-surveillance' => 'colis224_manage_all',
            'colis224-live-chat' => 'colis224_manage_all',
            'colis224-departures' => 'colis224_manage_all',
            'colis224-departures-automation' => 'colis224_manage_all',
            'colis224-settings' => 'colis224_manage_all',
            'colis224-diagnostic' => 'colis224_manage_all',
            'colis224-migration' => 'colis224_manage_all',
            'colis224-archives' => 'colis224_manage_all',
            'colis224-migration-archiving' => 'colis224_manage_all',
            'colis224-duplicates' => 'colis224_manage_all',
            'colis224-migration-duplicates' => 'colis224_manage_all',
            'colis224-batches' => 'colis224_manage_all',
            'colis224-migration-batches' => 'colis224_manage_all',
            'colis224-csv-import' => 'colis224_manage_all',
        );

        // Si la page nécessite manage_options, on la garde
        // Sinon on utilise la capability spécifique ou on permet l'accès si l'utilisateur a au moins une capability Colis224
        if (isset($page_capabilities[$page])) {
            $cap = $page_capabilities[$page];
            // Permettre l'accès si l'utilisateur a la capability OU s'il est admin
            return $cap;
        }

        // Par défaut, permettre l'accès aux utilisateurs avec au moins une capability Colis224
        return 'read';
    }

    /**
     * Vérifier si l'utilisateur peut accéder à une page
     */
    private static function user_can_access_page($page) {
        // Les administrateurs ont toujours accès
        if (current_user_can('manage_options')) {
            return true;
        }

        $cap = self::get_page_capability($page);
        
        // Si la capability est 'read', vérifier si l'utilisateur a au moins une capability Colis224
        if ($cap === 'read') {
            $colis224_caps = array(
                'colis224_manage_all',
                'colis224_view_parcels',
                'colis224_create_parcel',
                'colis224_manage_clients',
                'colis224_view_reports',
                'colis224_manage_accounting',
                'colis224_view_assigned_parcels',
                'colis224_update_delivery_status'
            );
            
            foreach ($colis224_caps as $colis_cap) {
                if (current_user_can($colis_cap)) {
                    return true;
                }
            }
            return false;
        }

        return current_user_can($cap);
    }

    /**
     * Vérifier si l'utilisateur a au moins une capability Colis224
     */
    private static function user_has_colis224_access() {
        // Les administrateurs ont toujours accès
        if (current_user_can('manage_options')) {
            return true;
        }

        // Vérifier si l'utilisateur a au moins une capability Colis224
        $colis224_caps = array(
            'colis224_manage_all',
            'colis224_view_parcels',
            'colis224_create_parcel',
            'colis224_manage_clients',
            'colis224_view_reports',
            'colis224_manage_accounting',
            'colis224_view_assigned_parcels',
            'colis224_update_delivery_status'
        );
        
        foreach ($colis224_caps as $cap) {
            if (current_user_can($cap)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ajouter les menus admin
     */
    public function add_plugin_admin_menu() {
        // Vérifier si l'utilisateur a accès avant d'ajouter le menu
        if (!self::user_has_colis224_access()) {
            return;
        }

        // Menu principal - accessible à tous les utilisateurs avec une capability Colis224
        add_menu_page(
            'Colis224 Logistics',
            'Colis224',
            'colis224_view_parcels', // Utiliser une capability Colis224 spécifique
            'colis224-dashboard',
            array('Colis224_Dashboard', 'display_dashboard'),
            'dashicons-airplane',
            26
        );

        // Tableau de bord - accessible aux agents, livreurs, comptables, managers
        add_submenu_page(
            'colis224-dashboard',
            'Tableau de bord',
            'Tableau de bord',
            'colis224_view_parcels',
            'colis224-dashboard',
            array('Colis224_Dashboard', 'display_dashboard')
        );

        // Gestion des colis - accessible aux agents et plus
        add_submenu_page(
            'colis224-dashboard',
            'Gestion des Colis',
            'Colis',
            'colis224_view_parcels',
            'colis224-parcels',
            array('Colis224_Parcels', 'display_page')
        );

        // Validation des colis (v2.11.0) - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Validation des Colis',
            '✅ Validation',
            'colis224_manage_all',
            'colis224-validation',
            array('Colis224_Validation', 'display_page')
        );

        // Gestion des clients - accessible aux agents et plus
        add_submenu_page(
            'colis224-dashboard',
            'Gestion des Clients',
            'Clients',
            'colis224_manage_clients',
            'colis224-clients',
            array('Colis224_Clients', 'display_page')
        );

        // Gestion des pays - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Gestion des Pays',
            '🌍 Pays',
            'colis224_manage_all',
            'colis224-countries',
            array('Colis224_Countries_Admin', 'display_page')
        );

        // Gestion des partenaires - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Gestion des Partenaires',
            'Partenaires',
            'colis224_manage_all',
            'colis224-partners',
            array('Colis224_Partners', 'display_page')
        );

        // Gestion de l'équipe - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Gestion de l\'Équipe',
            'Équipe',
            'colis224_manage_all',
            'colis224-team',
            array('Colis224_Team', 'display_page')
        );

        // Comptabilité - accessible aux comptables et plus
        add_submenu_page(
            'colis224-dashboard',
            'Comptabilité',
            'Comptabilité',
            'colis224_manage_accounting',
            'colis224-accounting',
            array('Colis224_Accounting', 'display_page')
        );

        // Rapports - accessible aux comptables, managers et plus
        add_submenu_page(
            'colis224-dashboard',
            'Rapports et Statistiques',
            'Rapports',
            'colis224_view_reports',
            'colis224-reports',
            array('Colis224_Reports', 'display_page')
        );

        // Service d'achat - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Service d\'Achat',
            'Service d\'Achat',
            'colis224_manage_all',
            'colis224-purchases',
            array('Colis224_Purchases', 'display_page')
        );

        // Programme de Fidélité - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Programme de Fidélité',
            'Fidélité',
            'colis224_manage_all',
            'colis224-loyalty',
            array('Colis224_Loyalty_Admin', 'display_page')
        );

        // Multi-Entrepôts - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Gestion des Entrepôts',
            'Entrepôts',
            'colis224_manage_all',
            'colis224-warehouses',
            array('Colis224_Warehouses_Admin', 'display_page')
        );

        // Support / SAV - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Support / SAV',
            'Support',
            'colis224_manage_all',
            'colis224-support',
            array('Colis224_Support_Admin', 'display_page')
        );

        // Surveillance & Relances - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Surveillance Anti-Vol',
            '🛡️ Surveillance',
            'colis224_manage_all',
            'colis224-surveillance',
            array('Colis224_Surveillance_Admin', 'display_page')
        );

        // Live Chat - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Live Chat Support',
            '💬 Live Chat',
            'colis224_manage_all',
            'colis224-live-chat',
            array('Colis224_Live_Chat_Admin', 'display_page')
        );

        // Départs et Réservations - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Départs et Réservations',
            '🚀 Départs',
            'colis224_manage_all',
            'colis224-departures',
            array('Colis224_Departures_Admin', 'display_page')
        );

        // Automatisation des Départs - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Automatisation des Départs',
            '🤖 Automatisation',
            'colis224_manage_all',
            'colis224-departures-automation',
            array('Colis224_Departures_Automation_Admin', 'display_page')
        );

        // Paramètres - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Paramètres',
            'Paramètres',
            'colis224_manage_all',
            'colis224-settings',
            array('Colis224_Settings', 'display_page')
        );
        
        // Menu Diagnostic - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Diagnostic Système',
            '🔧 Diagnostic',
            'colis224_manage_all',
            'colis224-diagnostic',
            array('Colis224_Diagnostic', 'display_page')
        );

        // Menu Migration (v2.11.0) - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Migration Système',
            '🔄 Migration',
            'colis224_manage_all',
            'colis224-migration',
            array('Colis224_Migration_Admin', 'display_page')
        );

        // Menu Archivage (v2.12.0) - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Gestion des Archives',
            '📦 Archives',
            'colis224_manage_all',
            'colis224-archives',
            array('Colis224_Archives', 'display_page')
        );

        // Menu Migration Archivage (v2.12.0) - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Migration Archivage',
            '🗄️ Migration Archivage',
            'colis224_manage_all',
            'colis224-migration-archiving',
            array('Colis224_Migration_Archiving_Admin', 'display_page')
        );

        // Menu Détection Doublons (v2.13.0) - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Détection des Doublons',
            '👥 Doublons',
            'colis224_manage_all',
            'colis224-duplicates',
            array('Colis224_Duplicates', 'display_page')
        );

        // Menu Migration Doublons (v2.13.0) - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Migration Doublons',
            '🔄 Migration Doublons',
            'colis224_manage_all',
            'colis224-migration-duplicates',
            array('Colis224_Migration_Duplicates_Admin', 'display_page')
        );

        // Menu Lots Internationaux (v2.14.0) - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Lots Internationaux',
            '🚢 Lots Internationaux',
            'colis224_manage_all',
            'colis224-batches',
            array('Colis224_Batches_Admin', 'display_page')
        );

        // Menu Migration Lots Internationaux (v2.14.0) - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Migration Lots Internationaux',
            '🔄 Migration Lots',
            'colis224_manage_all',
            'colis224-migration-batches',
            array('Colis224_Migration_Batches_Admin', 'display_page')
        );

        // Menu Import CSV (v2.15.0) - seulement admin/manager
        add_submenu_page(
            'colis224-dashboard',
            'Import CSV',
            '📊 Import CSV',
            'colis224_manage_all',
            'colis224-csv-import',
            array('Colis224_CSV_Import_Admin', 'display_page')
        );

        // Filtrer les menus selon les permissions
        add_filter('submenu_file', array($this, 'filter_submenu_items'), 10, 2);
    }

    /**
     * Filtrer les items de menu selon les permissions
     */
    public function filter_submenu_items($submenu_file, $parent_file) {
        if ($parent_file !== 'colis224-dashboard') {
            return $submenu_file;
        }

        global $submenu;
        
        if (!isset($submenu['colis224-dashboard'])) {
            return $submenu_file;
        }

        // Les administrateurs voient tout
        if (current_user_can('manage_options')) {
            return $submenu_file;
        }

        // Filtrer les items selon les capabilities
        $allowed_pages = array('colis224-dashboard');
        
        // Vérifier chaque capability
        if (current_user_can('colis224_view_parcels')) {
            $allowed_pages[] = 'colis224-parcels';
        }
        if (current_user_can('colis224_manage_clients')) {
            $allowed_pages[] = 'colis224-clients';
        }
        if (current_user_can('colis224_manage_accounting')) {
            $allowed_pages[] = 'colis224-accounting';
        }
        if (current_user_can('colis224_view_reports')) {
            $allowed_pages[] = 'colis224-reports';
        }
        if (current_user_can('colis224_manage_all')) {
            // Ajouter tous les menus admin
            $allowed_pages = array_merge($allowed_pages, array(
                'colis224-validation',
                'colis224-partners',
                'colis224-team',
                'colis224-purchases',
                'colis224-loyalty',
                'colis224-warehouses',
                'colis224-support',
                'colis224-surveillance',
                'colis224-live-chat',
                'colis224-departures',
                'colis224-departures-automation',
                'colis224-settings',
                'colis224-diagnostic',
                'colis224-migration',
                'colis224-archives',
                'colis224-migration-archiving',
                'colis224-duplicates',
                'colis224-migration-duplicates',
                'colis224-batches',
                'colis224-migration-batches',
                'colis224-csv-import'
            ));
        }

        // Supprimer les items non autorisés
        foreach ($submenu['colis224-dashboard'] as $key => $item) {
            if (isset($item[2]) && !in_array($item[2], $allowed_pages)) {
                unset($submenu['colis224-dashboard'][$key]);
            }
        }

        return $submenu_file;
    }

    /**
     * Charger les styles CSS
     */
    public function enqueue_styles() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'colis224') !== false) {
            wp_enqueue_style(
                'colis224-admin',
                COLIS224_PLUGIN_URL . 'assets/css/admin-style.css',
                array(),
                COLIS224_VERSION,
                'all'
            );

            // Sprint 6 : UX/UI Enhancements (v2.16.0)
            wp_enqueue_style(
                'colis224-toast-notifications',
                COLIS224_PLUGIN_URL . 'assets/css/toast-notifications.css',
                array(),
                COLIS224_VERSION,
                'all'
            );

            wp_enqueue_style(
                'colis224-breadcrumbs',
                COLIS224_PLUGIN_URL . 'assets/css/breadcrumbs.css',
                array(),
                COLIS224_VERSION,
                'all'
            );

            wp_enqueue_style(
                'colis224-confirm-modal',
                COLIS224_PLUGIN_URL . 'assets/css/confirm-modal.css',
                array(),
                COLIS224_VERSION,
                'all'
            );

            wp_enqueue_style(
                'colis224-tooltips',
                COLIS224_PLUGIN_URL . 'assets/css/tooltips.css',
                array(),
                COLIS224_VERSION,
                'all'
            );

            // Charger les styles du module Départs
            if (isset($_GET['page']) && $_GET['page'] === 'colis224-departures') {
                wp_enqueue_style(
                    'colis224-departures-slider',
                    COLIS224_PLUGIN_URL . 'assets/css/departures-slider.css',
                    array(),
                    COLIS224_VERSION,
                    'all'
                );
            }
        }

        // Charger les styles du widget départs sur le frontend
        wp_enqueue_style(
            'colis224-departures-widget',
            COLIS224_PLUGIN_URL . 'assets/css/departures-widget.css',
            array(),
            COLIS224_VERSION,
            'all'
        );
    }

    /**
     * Charger les scripts JavaScript
     */
    public function enqueue_scripts() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'colis224') !== false) {
            // Charger jQuery UI Autocomplete
            Colis224_Autocomplete::enqueue_scripts();

            wp_enqueue_script(
                'colis224-admin',
                COLIS224_PLUGIN_URL . 'assets/js/admin-script.js',
                array('jquery', 'jquery-ui-autocomplete'),
                COLIS224_VERSION,
                true
            );

            // Sprint 6 : UX/UI Enhancements (v2.16.0)
            wp_enqueue_script(
                'colis224-toast-notifications',
                COLIS224_PLUGIN_URL . 'assets/js/toast-notifications.js',
                array('jquery'),
                COLIS224_VERSION,
                true
            );

            wp_enqueue_script(
                'colis224-confirm-modal',
                COLIS224_PLUGIN_URL . 'assets/js/confirm-modal.js',
                array('jquery'),
                COLIS224_VERSION,
                true
            );

            wp_enqueue_script(
                'colis224-tooltips',
                COLIS224_PLUGIN_URL . 'assets/js/tooltips.js',
                array('jquery'),
                COLIS224_VERSION,
                true
            );

            // Passer des variables PHP à JavaScript
            wp_localize_script('colis224-admin', 'colis224Ajax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('colis224_nonce'),
                'autocomplete_nonce' => wp_create_nonce('colis224_autocomplete'),
                'currency' => get_option('colis224_default_currency', 'GNF')
            ));
        }

        // Charger le script du slider de départs sur toutes les pages (shortcode/widget)
        wp_enqueue_script(
            'colis224-departures-slider',
            COLIS224_PLUGIN_URL . 'assets/js/departures-slider.js',
            array('jquery'),
            COLIS224_VERSION,
            true
        );

        // Passer les variables AJAX pour le module Départs
        wp_localize_script('colis224-departures-slider', 'colis224_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colis224_departures_nonce')
        ));
    }
}
