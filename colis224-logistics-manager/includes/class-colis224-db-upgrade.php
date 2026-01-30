<?php
/**
 * Gestion des mises à jour de base de données
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_DB_Upgrade {

    /**
     * Vérifier et ajouter les colonnes manquantes
     */
    public static function check_and_upgrade() {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Vérifier si les nouvelles colonnes existent
        $columns = $wpdb->get_col("DESC {$table_parcels}", 0);

        $upgrades_made = false;

        // Ajouter client_email si manquant
        if (!in_array('client_email', $columns)) {
            $wpdb->query("ALTER TABLE {$table_parcels} ADD COLUMN client_email VARCHAR(100) DEFAULT NULL AFTER client_id");
            $upgrades_made = true;
        }

        // Ajouter sender_phone si manquant
        if (!in_array('sender_phone', $columns)) {
            $wpdb->query("ALTER TABLE {$table_parcels} ADD COLUMN sender_phone VARCHAR(50) DEFAULT NULL AFTER sender_name");
            $upgrades_made = true;
        }

        // Ajouter sender_id_card si manquant
        if (!in_array('sender_id_card', $columns)) {
            $wpdb->query("ALTER TABLE {$table_parcels} ADD COLUMN sender_id_card VARCHAR(100) DEFAULT NULL AFTER sender_phone");
            $upgrades_made = true;
        }

        // Ajouter recorded_by_agent_id si manquant
        if (!in_array('recorded_by_agent_id', $columns)) {
            $wpdb->query("ALTER TABLE {$table_parcels} ADD COLUMN recorded_by_agent_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER driver_id");
            $wpdb->query("ALTER TABLE {$table_parcels} ADD INDEX idx_recorded_by_agent (recorded_by_agent_id)");
            $upgrades_made = true;
        }

        // Ajouter receipt_photo si manquant
        if (!in_array('receipt_photo', $columns)) {
            $wpdb->query("ALTER TABLE {$table_parcels} ADD COLUMN receipt_photo VARCHAR(255) DEFAULT NULL AFTER photos");
            $upgrades_made = true;
        }

        // v2.18.23: Ajouter index sur table clients pour recherche ultra-rapide
        self::add_client_search_indexes();

        if ($upgrades_made) {
            // Mettre à jour l'option pour indiquer que la migration a été effectuée
            update_option('colis224_db_version', '2.18.24');
        }

        return $upgrades_made;
    }

    /**
     * Ajouter les index de recherche sur la table clients (v2.18.23)
     * Pour optimiser les performances de recherche de clients
     */
    public static function add_client_search_indexes() {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        // Vérifier si la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_clients}'");
        if (!$table_exists) {
            return false;
        }

        // Récupérer les index existants
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table_clients}");
        $existing_indexes = array();
        foreach ($indexes as $index) {
            $existing_indexes[] = $index->Key_name;
        }

        $indexes_added = false;

        // Ajouter index sur name si manquant
        if (!in_array('name', $existing_indexes)) {
            $wpdb->query("ALTER TABLE {$table_clients} ADD INDEX name (name)");
            $indexes_added = true;
        }

        // Ajouter index sur company_name si manquant
        if (!in_array('company_name', $existing_indexes)) {
            $wpdb->query("ALTER TABLE {$table_clients} ADD INDEX company_name (company_name)");
            $indexes_added = true;
        }

        if ($indexes_added) {
            update_option('colis224_client_indexes_added', '1');
        }

        return $indexes_added;
    }

    /**
     * Afficher un message admin si mise à jour nécessaire
     * v2.18.24: Discret - s'affiche seulement sur le dashboard et peut être masqué
     */
    public static function admin_notice() {
        // Ne pas afficher si déjà masqué par l'utilisateur
        if (get_option('colis224_db_notice_dismissed', 0)) {
            return;
        }

        $current_version = get_option('colis224_db_version', '0');

        // Afficher seulement si mise à jour nécessaire
        if (version_compare($current_version, '2.18.24', '<')) {
            // Vérifier qu'on est sur une page Colis224 pour éviter de polluer toutes les pages
            $screen = get_current_screen();
            if ($screen && strpos($screen->id, 'colis224') === false && $screen->id !== 'dashboard') {
                return; // Ne pas afficher sur les pages non-Colis224
            }

            ?>
            <div class="notice notice-info is-dismissible" id="colis224-db-upgrade-notice" style="border-left-color: #0073aa;">
                <p>
                    <span class="dashicons dashicons-database" style="vertical-align: middle; color: #0073aa;"></span>
                    <strong>Colis224 :</strong> Optimisations de performance disponibles.
                    <a href="<?php echo admin_url('admin.php?page=colis224-dashboard&colis224_upgrade_db=1'); ?>" class="button button-small button-primary" style="margin-left: 10px; vertical-align: middle;">
                        ⚡ Activer maintenant (5 sec)
                    </a>
                    <a href="#" id="colis224-dismiss-db-notice" class="button button-small" style="vertical-align: middle;">Plus tard</a>
                </p>
            </div>
            <script>
            jQuery(document).ready(function($) {
                $('#colis224-dismiss-db-notice').on('click', function(e) {
                    e.preventDefault();
                    $.post(ajaxurl, {
                        action: 'colis224_dismiss_db_notice',
                        nonce: '<?php echo wp_create_nonce('colis224_dismiss_notice'); ?>'
                    });
                    $('#colis224-db-upgrade-notice').fadeOut();
                });

                // Auto-masquer après 30 secondes si pas cliqué
                setTimeout(function() {
                    if ($('#colis224-db-upgrade-notice').is(':visible')) {
                        $('#colis224-db-upgrade-notice').fadeOut();
                    }
                }, 30000);
            });
            </script>
            <?php
        }
    }

    /**
     * AJAX: Masquer le message de mise à jour
     */
    public static function ajax_dismiss_notice() {
        check_ajax_referer('colis224_dismiss_notice', 'nonce');
        update_option('colis224_db_notice_dismissed', 1);
        wp_send_json_success();
    }

    /**
     * Gérer la requête de mise à jour manuelle
     */
    public static function handle_manual_upgrade() {
        if (isset($_GET['colis224_upgrade_db']) && current_user_can('manage_options')) {
            $upgraded = self::check_and_upgrade();

            if ($upgraded) {
                add_action('admin_notices', function() {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><strong>✅ Base de données mise à jour avec succès !</strong></p>
                        <p>Les nouveaux champs ont été ajoutés à la table des colis.</p>
                    </div>
                    <?php
                });
            }
        }
    }
}

// Hooks
add_action('admin_init', array('Colis224_DB_Upgrade', 'handle_manual_upgrade'));
add_action('wp_ajax_colis224_dismiss_db_notice', array('Colis224_DB_Upgrade', 'ajax_dismiss_notice'));
add_action('admin_notices', array('Colis224_DB_Upgrade', 'admin_notice'));

// Exécuter la vérification automatiquement au chargement de n'importe quelle page admin du plugin
add_action('load-toplevel_page_colis224-dashboard', array('Colis224_DB_Upgrade', 'check_and_upgrade'));
