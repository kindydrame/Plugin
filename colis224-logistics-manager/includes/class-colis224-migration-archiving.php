<?php
/**
 * Migration pour le système d'archivage automatique
 *
 * @package Colis224_Logistics
 * @version 2.12.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Migration_Archiving {

    const MIGRATION_VERSION = '2.12.0';

    /**
     * Exécuter la migration
     *
     * @return array Résultat de la migration
     */
    public static function run() {
        global $wpdb;

        // Vérifier si déjà migrée
        $current_version = get_option('colis224_archiving_migration_version', '0');
        if (version_compare($current_version, self::MIGRATION_VERSION, '>=')) {
            return array(
                'success' => true,
                'message' => 'Migration d\'archivage déjà effectuée.'
            );
        }

        // Créer un backup de sécurité
        self::create_backup();

        // Démarrer une transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Créer la table d'archivage
            self::create_archived_parcels_table();

            // Ajouter les colonnes d'archivage à la table principale
            self::add_archiving_columns();

            // Valider la transaction
            $wpdb->query('COMMIT');

            // Enregistrer la version de migration
            update_option('colis224_archiving_migration_version', self::MIGRATION_VERSION);

            return array(
                'success' => true,
                'message' => '✅ Migration d\'archivage effectuée avec succès !'
            );

        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $wpdb->query('ROLLBACK');

            return array(
                'success' => false,
                'message' => '❌ Erreur lors de la migration : ' . $e->getMessage()
            );
        }
    }

    /**
     * Créer la table des colis archivés
     */
    private static function create_archived_parcels_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'colis224_archived_parcels';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            original_parcel_id bigint(20) NOT NULL,
            tracking_number varchar(50) NOT NULL,
            client_id bigint(20) DEFAULT NULL,
            sender_name varchar(255) DEFAULT NULL,
            recipient_name varchar(255) NOT NULL,
            recipient_phone varchar(50) DEFAULT NULL,
            recipient_address text DEFAULT NULL,
            origin_country_id int(11) DEFAULT NULL,
            destination_country_id int(11) DEFAULT NULL,
            transport_mode_id int(11) DEFAULT NULL,
            category_id int(11) DEFAULT NULL,
            weight decimal(10,2) DEFAULT 0.00,
            unit_price decimal(10,2) DEFAULT 0.00,
            discount_type varchar(20) DEFAULT 'none',
            discount_value decimal(10,2) DEFAULT 0.00,
            total_amount decimal(10,2) NOT NULL,
            currency varchar(10) DEFAULT 'GNF',
            reception_date date DEFAULT NULL,
            shipping_date date DEFAULT NULL,
            delivery_date date DEFAULT NULL,
            estimated_delivery_date date DEFAULT NULL,
            status varchar(50) DEFAULT 'Livré',
            payment_method varchar(50) DEFAULT NULL,
            payment_status varchar(50) DEFAULT 'Payé',
            paid_amount decimal(10,2) DEFAULT 0.00,
            remaining_amount decimal(10,2) DEFAULT 0.00,
            driver_id bigint(20) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_by bigint(20) DEFAULT NULL,
            validated_by bigint(20) DEFAULT NULL,
            validated_at datetime DEFAULT NULL,
            validation_status varchar(20) DEFAULT 'approved',
            validation_notes text DEFAULT NULL,
            created_at datetime NOT NULL,
            archived_at datetime NOT NULL,
            archived_by bigint(20) NOT NULL,
            archive_reason varchar(255) DEFAULT 'auto_archive_delivered',
            PRIMARY KEY (id),
            KEY original_parcel_id (original_parcel_id),
            KEY tracking_number (tracking_number),
            KEY client_id (client_id),
            KEY archived_at (archived_at),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Ajouter les colonnes d'archivage à la table principale
     */
    private static function add_archiving_columns() {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Vérifier si les colonnes existent déjà
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'is_archived'");

        if (empty($columns)) {
            // Ajouter la colonne is_archived
            $wpdb->query("
                ALTER TABLE $table_parcels
                ADD COLUMN is_archived tinyint(1) DEFAULT 0 AFTER validation_notes
            ");

            // Ajouter la colonne archived_at
            $wpdb->query("
                ALTER TABLE $table_parcels
                ADD COLUMN archived_at datetime DEFAULT NULL AFTER is_archived
            ");

            // Créer un index sur is_archived
            $wpdb->query("
                ALTER TABLE $table_parcels
                ADD INDEX idx_is_archived (is_archived)
            ");
        }
    }

    /**
     * Créer un backup avant migration
     */
    private static function create_backup() {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $backup_table = $wpdb->prefix . 'colis224_parcels_backup_archiving_' . date('YmdHis');

        $wpdb->query("CREATE TABLE $backup_table LIKE $table_parcels");
        $wpdb->query("INSERT INTO $backup_table SELECT * FROM $table_parcels");

        update_option('colis224_last_archiving_backup', $backup_table);
    }

    /**
     * Annuler la migration (rollback)
     */
    public static function rollback() {
        global $wpdb;

        $wpdb->query('START TRANSACTION');

        try {
            $table_parcels = $wpdb->prefix . 'colis224_parcels';
            $table_archived = $wpdb->prefix . 'colis224_archived_parcels';

            // Supprimer les colonnes ajoutées
            $wpdb->query("ALTER TABLE $table_parcels DROP COLUMN IF EXISTS is_archived");
            $wpdb->query("ALTER TABLE $table_parcels DROP COLUMN IF EXISTS archived_at");

            // Supprimer la table d'archivage (ATTENTION : perte des données archivées)
            // Commenté par sécurité - à décommenter si nécessaire
            // $wpdb->query("DROP TABLE IF EXISTS $table_archived");

            $wpdb->query('COMMIT');

            // Réinitialiser la version
            delete_option('colis224_archiving_migration_version');

            return array(
                'success' => true,
                'message' => '✅ Rollback effectué. Note: La table archived_parcels n\'a pas été supprimée par sécurité.'
            );

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');

            return array(
                'success' => false,
                'message' => '❌ Erreur lors du rollback : ' . $e->getMessage()
            );
        }
    }

    /**
     * Vérifier le statut de la migration
     *
     * @return array Statut détaillé
     */
    public static function check_status() {
        global $wpdb;

        $current_version = get_option('colis224_archiving_migration_version', '0');
        $is_migrated = version_compare($current_version, self::MIGRATION_VERSION, '>=');

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_archived = $wpdb->prefix . 'colis224_archived_parcels';

        // Vérifier l'existence des colonnes
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'is_archived'");
        $has_columns = !empty($columns);

        // Vérifier l'existence de la table d'archivage
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_archived'") === $table_archived;

        // Compter les colis archivés
        $archived_count = 0;
        if ($table_exists) {
            $archived_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_archived");
        }

        return array(
            'is_migrated' => $is_migrated,
            'version' => $current_version,
            'target_version' => self::MIGRATION_VERSION,
            'has_columns' => $has_columns,
            'table_exists' => $table_exists,
            'archived_count' => intval($archived_count)
        );
    }
}
