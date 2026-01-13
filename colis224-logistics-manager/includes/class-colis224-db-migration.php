<?php
/**
 * Gestion des migrations de base de données
 * Version: 2.18.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_DB_Migration {

    const DB_VERSION_OPTION = 'colis224_db_version';
    const CURRENT_VERSION = '2.18.0';

    /**
     * Exécuter les migrations nécessaires
     */
    public static function run_migrations() {
        $current_db_version = get_option(self::DB_VERSION_OPTION, '0');

        // Si déjà à jour, ne rien faire
        if (version_compare($current_db_version, self::CURRENT_VERSION, '>=')) {
            return;
        }

        // Exécuter les migrations dans l'ordre
        if (version_compare($current_db_version, '2.18.0', '<')) {
            self::migrate_to_2_18_0();
        }

        // Mettre à jour la version
        update_option(self::DB_VERSION_OPTION, self::CURRENT_VERSION);
    }

    /**
     * Migration vers 2.18.0 : Système d'approbation
     */
    private static function migrate_to_2_18_0() {
        global $wpdb;

        // Ajouter colonne approval_status dans parcels
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_parcels}` LIKE 'approval_status'"
        );

        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_parcels}`
                ADD COLUMN `approval_status` enum('pending','approved','rejected') DEFAULT 'approved' AFTER `status`,
                ADD COLUMN `approved_by` bigint(20) UNSIGNED DEFAULT NULL AFTER `approval_status`,
                ADD COLUMN `approved_at` datetime DEFAULT NULL AFTER `approved_by`,
                ADD COLUMN `rejection_reason` text DEFAULT NULL AFTER `approved_at`,
                ADD KEY `approval_status` (`approval_status`)
            ");
        }

        // Ajouter colonne approval_status dans departures
        $table_departures = $wpdb->prefix . 'colis224_departures';
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_departures}` LIKE 'approval_status'"
        );

        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_departures}`
                ADD COLUMN `approval_status` enum('pending','approved','rejected') DEFAULT 'approved' AFTER `status`,
                ADD COLUMN `approved_by` bigint(20) UNSIGNED DEFAULT NULL AFTER `approval_status`,
                ADD COLUMN `approved_at` datetime DEFAULT NULL AFTER `approved_by`,
                ADD COLUMN `rejection_reason` text DEFAULT NULL AFTER `approved_at`,
                ADD KEY `approval_status` (`approval_status`)
            ");
        }

        // Créer table des logs d'approbation
        $table_approval_logs = $wpdb->prefix . 'colis224_approval_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS `{$table_approval_logs}` (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type enum('parcel','departure','client') NOT NULL,
            entity_id bigint(20) UNSIGNED NOT NULL,
            action enum('submitted','approved','rejected') NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            user_name varchar(255) NOT NULL,
            reason text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY entity_type (entity_type),
            KEY entity_id (entity_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Log une action d'approbation
     */
    public static function log_approval_action($entity_type, $entity_id, $action, $reason = null) {
        global $wpdb;

        $user = wp_get_current_user();
        $user_id = $user->ID;
        $user_name = $user->display_name ?: $user->user_login;

        $wpdb->insert(
            $wpdb->prefix . 'colis224_approval_logs',
            array(
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'action' => $action,
                'user_id' => $user_id,
                'user_name' => $user_name,
                'reason' => $reason
            ),
            array('%s', '%d', '%s', '%d', '%s', '%s')
        );
    }

    /**
     * Récupérer l'historique d'approbation
     */
    public static function get_approval_history($entity_type, $entity_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}colis224_approval_logs
            WHERE entity_type = %s AND entity_id = %d
            ORDER BY created_at DESC
        ", $entity_type, $entity_id));
    }
}
