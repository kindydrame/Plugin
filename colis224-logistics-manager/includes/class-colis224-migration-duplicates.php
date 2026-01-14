<?php
/**
 * Migration pour le système de détection/fusion des doublons
 *
 * @package Colis224_Logistics
 * @version 2.13.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Migration_Duplicates {

    const MIGRATION_VERSION = '2.13.0';

    /**
     * Exécuter la migration
     *
     * @return array Résultat
     */
    public static function run() {
        global $wpdb;

        // Vérifier si déjà migrée
        $current_version = get_option('colis224_duplicates_migration_version', '0');
        if (version_compare($current_version, self::MIGRATION_VERSION, '>=')) {
            return array(
                'success' => true,
                'message' => 'Migration de fusion des doublons déjà effectuée.'
            );
        }

        // Démarrer une transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Créer la table d'historique des fusions
            self::create_merges_table();

            $wpdb->query('COMMIT');

            // Enregistrer la version
            update_option('colis224_duplicates_migration_version', self::MIGRATION_VERSION);

            return array(
                'success' => true,
                'message' => '✅ Migration de fusion des doublons effectuée avec succès !'
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
     * Créer la table d'historique des fusions
     */
    private static function create_merges_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'colis224_client_merges';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            master_client_id bigint(20) NOT NULL,
            merged_client_id bigint(20) NOT NULL,
            merged_client_data longtext NOT NULL,
            merged_by bigint(20) NOT NULL,
            merged_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY master_client_id (master_client_id),
            KEY merged_client_id (merged_client_id),
            KEY merged_at (merged_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Vérifier le statut de la migration
     *
     * @return array
     */
    public static function check_status() {
        global $wpdb;

        $current_version = get_option('colis224_duplicates_migration_version', '0');
        $is_migrated = version_compare($current_version, self::MIGRATION_VERSION, '>=');

        $table_merges = $wpdb->prefix . 'colis224_client_merges';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_merges'") === $table_merges;

        $merge_count = 0;
        if ($table_exists) {
            $merge_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_merges");
        }

        return array(
            'is_migrated' => $is_migrated,
            'version' => $current_version,
            'target_version' => self::MIGRATION_VERSION,
            'table_exists' => $table_exists,
            'merge_count' => intval($merge_count)
        );
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
            // Note: On ne supprime pas la table par sécurité
            // Décommenter si nécessaire
            // $table_merges = $wpdb->prefix . 'colis224_client_merges';
            // $wpdb->query("DROP TABLE IF EXISTS $table_merges");

            $wpdb->query('COMMIT');

            delete_option('colis224_duplicates_migration_version');

            return array(
                'success' => true,
                'message' => '✅ Rollback effectué. Note: La table client_merges n\'a pas été supprimée par sécurité.'
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
