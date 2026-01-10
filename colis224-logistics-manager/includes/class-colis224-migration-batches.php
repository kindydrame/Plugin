<?php
/**
 * Migration pour le système de gestion des lots internationaux
 *
 * @package Colis224_Logistics
 * @version 2.14.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Migration_Batches {

    const MIGRATION_VERSION = '2.14.0';

    /**
     * Exécuter la migration
     *
     * @return array Résultat
     */
    public static function run() {
        global $wpdb;

        $current_version = get_option('colis224_batches_migration_version', '0');
        if (version_compare($current_version, self::MIGRATION_VERSION, '>=')) {
            return array(
                'success' => true,
                'message' => 'Migration des lots internationaux déjà effectuée.'
            );
        }

        $wpdb->query('START TRANSACTION');

        try {
            // Créer la table des lots internationaux
            self::create_batches_table();

            // Créer la table d'assignation colis → lots
            self::create_batch_parcels_table();

            // Ajouter colonne batch_id à la table parcels
            self::add_batch_column();

            $wpdb->query('COMMIT');

            update_option('colis224_batches_migration_version', self::MIGRATION_VERSION);

            return array(
                'success' => true,
                'message' => '✅ Migration des lots internationaux effectuée avec succès !'
            );

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');

            return array(
                'success' => false,
                'message' => '❌ Erreur lors de la migration : ' . $e->getMessage()
            );
        }
    }

    /**
     * Créer la table des lots internationaux
     */
    private static function create_batches_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'colis224_international_batches';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_number varchar(50) NOT NULL,
            batch_type varchar(20) NOT NULL,
            origin_country_id bigint(20) UNSIGNED DEFAULT NULL,
            destination_country_id bigint(20) UNSIGNED DEFAULT NULL,
            transport_company varchar(255) DEFAULT NULL,
            container_number varchar(100) DEFAULT NULL,
            flight_number varchar(100) DEFAULT NULL,
            departure_date date DEFAULT NULL,
            arrival_date date DEFAULT NULL,
            estimated_arrival_date date DEFAULT NULL,
            actual_arrival_date date DEFAULT NULL,
            status varchar(50) DEFAULT 'preparing',
            total_parcels int(11) DEFAULT 0,
            total_weight decimal(10,2) DEFAULT 0.00,
            total_value decimal(10,2) DEFAULT 0.00,
            customs_document_url varchar(500) DEFAULT NULL,
            manifest_url varchar(500) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            closed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY batch_number (batch_number),
            KEY batch_type (batch_type),
            KEY status (status),
            KEY departure_date (departure_date),
            KEY arrival_date (arrival_date)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Créer la table d'assignation colis → lots
     */
    private static function create_batch_parcels_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'colis224_batch_parcels';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id bigint(20) UNSIGNED NOT NULL,
            parcel_id bigint(20) UNSIGNED NOT NULL,
            assigned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            assigned_by bigint(20) UNSIGNED DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY batch_parcel_unique (batch_id, parcel_id),
            KEY batch_id (batch_id),
            KEY parcel_id (parcel_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Ajouter la colonne batch_id à la table parcels
     */
    private static function add_batch_column() {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Vérifier si la table existe d'abord
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_parcels'") === $table_parcels;
        if (!$table_exists) {
            return; // La table n'existe pas encore, elle sera créée avec la colonne
        }

        // Vérifier si la colonne existe déjà
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'batch_id'");

        if (empty($columns)) {
            // Vérifier si la colonne driver_id existe pour placer batch_id après
            $driver_column = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'driver_id'");
            $after_clause = !empty($driver_column) ? 'AFTER driver_id' : '';

            $wpdb->query("
                ALTER TABLE $table_parcels
                ADD COLUMN batch_id bigint(20) UNSIGNED DEFAULT NULL $after_clause
            ");

            // Vérifier si l'index existe déjà avant de l'ajouter
            $indexes = $wpdb->get_results("SHOW INDEX FROM $table_parcels WHERE Key_name = 'idx_batch_id'");
            if (empty($indexes)) {
                $wpdb->query("
                    ALTER TABLE $table_parcels
                    ADD INDEX idx_batch_id (batch_id)
                ");
            }
        }
    }

    /**
     * Vérifier le statut de la migration
     *
     * @return array
     */
    public static function check_status() {
        global $wpdb;

        $current_version = get_option('colis224_batches_migration_version', '0');
        $is_migrated = version_compare($current_version, self::MIGRATION_VERSION, '>=');

        $table_batches = $wpdb->prefix . 'colis224_international_batches';
        $table_batch_parcels = $wpdb->prefix . 'colis224_batch_parcels';

        $batches_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_batches'") === $table_batches;
        $batch_parcels_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_batch_parcels'") === $table_batch_parcels;

        $batch_count = 0;
        $parcel_count = 0;

        if ($batches_exists) {
            $batch_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_batches");
        }

        if ($batch_parcels_exists) {
            $parcel_count = $wpdb->get_var("SELECT COUNT(DISTINCT parcel_id) FROM $table_batch_parcels");
        }

        // Vérifier colonne batch_id
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $has_column = !empty($wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'batch_id'"));

        return array(
            'is_migrated' => $is_migrated,
            'version' => $current_version,
            'target_version' => self::MIGRATION_VERSION,
            'batches_table_exists' => $batches_exists,
            'batch_parcels_table_exists' => $batch_parcels_exists,
            'has_column' => $has_column,
            'batch_count' => intval($batch_count),
            'assigned_parcels' => intval($parcel_count)
        );
    }

    /**
     * Alias pour check_status() - compatibilité avec l'admin
     *
     * @return array
     */
    public static function check_migration_status() {
        return self::check_status();
    }

    /**
     * Alias pour run() - compatibilité avec l'admin
     *
     * @return array
     */
    public static function run_migration() {
        return self::run();
    }

    /**
     * Rollback de la migration
     *
     * @return array
     */
    public static function rollback() {
        global $wpdb;

        $wpdb->query('START TRANSACTION');

        try {
            $table_parcels = $wpdb->prefix . 'colis224_parcels';

            // Supprimer la colonne batch_id
            $wpdb->query("ALTER TABLE $table_parcels DROP COLUMN IF EXISTS batch_id");

            // Note: On ne supprime pas les tables par sécurité

            $wpdb->query('COMMIT');

            delete_option('colis224_batches_migration_version');

            return array(
                'success' => true,
                'message' => '✅ Rollback effectué. Les tables ont été conservées par sécurité.'
            );

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');

            return array(
                'success' => false,
                'message' => '❌ Erreur lors du rollback : ' . $e->getMessage()
            );
        }
    }
}
