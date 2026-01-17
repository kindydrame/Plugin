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

        if ($upgrades_made) {
            // Mettre à jour l'option pour indiquer que la migration a été effectuée
            update_option('colis224_db_version', '2.18.3');
        }

        return $upgrades_made;
    }

    /**
     * Afficher un message admin si mise à jour nécessaire
     */
    public static function admin_notice() {
        $current_version = get_option('colis224_db_version', '0');

        if (version_compare($current_version, '2.18.3', '<')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>Colis224 :</strong> Mise à jour de la base de données requise. <a href="<?php echo admin_url('admin.php?page=colis224-dashboard&colis224_upgrade_db=1'); ?>" class="button button-primary">Mettre à jour maintenant</a></p>
            </div>
            <?php
        }
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
add_action('admin_notices', array('Colis224_DB_Upgrade', 'admin_notice'));

// Exécuter la vérification automatiquement au chargement de n'importe quelle page admin du plugin
add_action('load-toplevel_page_colis224-dashboard', array('Colis224_DB_Upgrade', 'check_and_upgrade'));
