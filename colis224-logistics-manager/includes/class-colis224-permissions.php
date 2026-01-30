<?php
/**
 * Système de Permissions et Rôles
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Permissions {

    public function __construct() {
        add_action('init', array($this, 'register_roles_and_capabilities'));
        add_action('admin_init', array($this, 'check_user_permissions'));
    }

    /**
     * Enregistrer les rôles personnalisés
     */
    public function register_roles_and_capabilities() {
        // Rôle: Gestionnaire Colis224
        add_role('colis224_manager', 'Gestionnaire Colis224', array(
            'read' => true,
            'colis224_manage_all' => true,
            'colis224_view_reports' => true,
            'colis224_manage_accounting' => true,
        ));

        // Rôle: Agent Colis224
        add_role('colis224_agent', 'Agent Colis224', array(
            'read' => true,
            'colis224_create_parcel' => true,
            'colis224_view_parcels' => true,
            'colis224_manage_clients' => true,
        ));
        
        // v2.20.12: Forcer la mise à jour des capabilities des agents
        // Supprimer et recréer le rôle si la version a changé
        $current_role_version = get_option('colis224_role_version', '0');
        $target_role_version = '2.20.12';

        if (version_compare($current_role_version, $target_role_version, '<')) {
            // Supprimer le rôle agent existant pour le recréer avec les bonnes capabilities
            remove_role('colis224_agent');

            // Recréer le rôle avec toutes les capabilities
            add_role('colis224_agent', 'Agent Colis224', array(
                'read' => true,
                'colis224_create_parcel' => true,
                'colis224_view_parcels' => true,
                'colis224_manage_clients' => true,
            ));

            update_option('colis224_role_version', $target_role_version);
        }

        // S'assurer que le rôle agent existe et a les bonnes capabilities
        $agent_role = get_role('colis224_agent');
        if ($agent_role) {
            // Vérifier et ajouter les capabilities si nécessaire
            if (!$agent_role->has_cap('colis224_view_parcels')) {
                $agent_role->add_cap('colis224_view_parcels');
            }
            if (!$agent_role->has_cap('colis224_create_parcel')) {
                $agent_role->add_cap('colis224_create_parcel');
            }
            if (!$agent_role->has_cap('colis224_manage_clients')) {
                $agent_role->add_cap('colis224_manage_clients');
            }
        }

        // Rôle: Livreur Colis224
        add_role('colis224_driver', 'Livreur Colis224', array(
            'read' => true,
            'colis224_view_assigned_parcels' => true,
            'colis224_update_delivery_status' => true,
        ));

        // Rôle: Comptable Colis224
        add_role('colis224_accountant', 'Comptable Colis224', array(
            'read' => true,
            'colis224_view_reports' => true,
            'colis224_manage_accounting' => true,
            'colis224_view_all_transactions' => true,
        ));

        // Ajouter les capabilities aux administrateurs
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('colis224_manage_all');
            $admin->add_cap('colis224_view_reports');
            $admin->add_cap('colis224_manage_accounting');
            $admin->add_cap('colis224_create_parcel');
            $admin->add_cap('colis224_view_parcels');
            $admin->add_cap('colis224_manage_clients');
        }

        // Ajouter les capabilities aux éditeurs (v2.18.2.2)
        $editor = get_role('editor');
        if ($editor) {
            $editor->add_cap('colis224_create_parcel');
            $editor->add_cap('colis224_view_parcels');
            $editor->add_cap('colis224_manage_clients');
            $editor->add_cap('colis224_view_reports');
        }

        // Ajouter les capabilities aux auteurs (v2.18.2.2)
        $author = get_role('author');
        if ($author) {
            $author->add_cap('colis224_create_parcel');
            $author->add_cap('colis224_view_parcels');
            $author->add_cap('colis224_manage_clients');
        }
    }

    /**
     * Vérifier les permissions de l'utilisateur
     */
    public function check_user_permissions() {
        if (!is_admin()) {
            return;
        }

        // Vérifier l'accès aux pages Colis224
        if (isset($_GET['page']) && strpos($_GET['page'], 'colis224') !== false) {
            $page = $_GET['page'];

            // Mapping des pages vers les capabilities requises
            $page_permissions = array(
                'colis224-dashboard' => 'colis224_view_parcels',
                'colis224-parcels' => 'colis224_view_parcels',
                'colis224-clients' => 'colis224_manage_clients',
                'colis224-partners' => 'colis224_manage_all',
                'colis224-team' => 'colis224_manage_all',
                'colis224-accounting' => 'colis224_manage_accounting',
                'colis224-reports' => 'colis224_view_reports',
                'colis224-settings' => 'colis224_manage_all',
                'colis224-pricing' => 'colis224_view_parcels', // v2.20.12: Accessible aux agents
                'colis224-frontend-requests' => 'colis224_view_parcels', // v2.20.12: Accessible aux agents
            );

            if (isset($page_permissions[$page])) {
                if (!current_user_can($page_permissions[$page]) && !current_user_can('manage_options')) {
                    wp_die('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.');
                }
            }
        }
    }

    /**
     * Vérifier si l'utilisateur peut créer des colis
     */
    public static function can_create_parcel() {
        return current_user_can('colis224_create_parcel') || current_user_can('manage_options');
    }

    /**
     * Vérifier si l'utilisateur peut voir tous les colis
     */
    public static function can_view_all_parcels() {
        return current_user_can('colis224_view_parcels') || current_user_can('manage_options');
    }

    /**
     * Vérifier si l'utilisateur peut gérer la comptabilité
     */
    public static function can_manage_accounting() {
        return current_user_can('colis224_manage_accounting') || current_user_can('manage_options');
    }

    /**
     * Obtenir le rôle Colis224 de l'utilisateur
     */
    public static function get_user_colis224_role($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $colis224_roles = array('colis224_manager', 'colis224_agent', 'colis224_driver', 'colis224_accountant');

        foreach ($user->roles as $role) {
            if (in_array($role, $colis224_roles)) {
                return $role;
            }
        }

        if (in_array('administrator', $user->roles)) {
            return 'administrator';
        }

        return false;
    }
}

// Initialiser
new Colis224_Permissions();
