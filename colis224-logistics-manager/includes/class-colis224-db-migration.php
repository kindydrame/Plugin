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
    const CURRENT_VERSION = '2.18.2';

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

        if (version_compare($current_db_version, '2.18.2', '<')) {
            self::migrate_to_2_18_2();
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
     * Migration vers 2.18.2 : Champs formulaire amélioré
     */
    private static function migrate_to_2_18_2() {
        global $wpdb;

        // Ajouter nouveaux champs dans la table parcels
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Vérifier et ajouter sender_id_card (Carte d'identité expéditeur)
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_parcels}` LIKE 'sender_id_card'"
        );
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_parcels}`
                ADD COLUMN `sender_id_card` varchar(100) DEFAULT NULL AFTER `sender_phone`
            ");
        }

        // Vérifier et ajouter sender_email
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_parcels}` LIKE 'sender_email'"
        );
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_parcels}`
                ADD COLUMN `sender_email` varchar(255) DEFAULT NULL AFTER `sender_id_card`
            ");
        }

        // Vérifier et ajouter recipient_email
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_parcels}` LIKE 'recipient_email'"
        );
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_parcels}`
                ADD COLUMN `recipient_email` varchar(255) DEFAULT NULL AFTER `recipient_phone`
            ");
        }

        // Vérifier et ajouter invoice_number (Numéro de facture)
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_parcels}` LIKE 'invoice_number'"
        );
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_parcels}`
                ADD COLUMN `invoice_number` varchar(50) DEFAULT NULL AFTER `tracking_number`,
                ADD UNIQUE KEY `invoice_number` (`invoice_number`)
            ");
        }

        // Vérifier et ajouter client_code (Code client PA)
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_parcels}` LIKE 'client_code'"
        );
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_parcels}`
                ADD COLUMN `client_code` varchar(50) DEFAULT NULL AFTER `client_id`
            ");
        }

        // Vérifier et ajouter parcel_nature (Nature du colis - texte libre)
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_parcels}` LIKE 'parcel_nature'"
        );
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_parcels}`
                ADD COLUMN `parcel_nature` varchar(255) DEFAULT NULL AFTER `category_id`
            ");
        }

        // Vérifier et ajouter receipt_photos (Photos du reçu)
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_parcels}` LIKE 'receipt_photos'"
        );
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_parcels}`
                ADD COLUMN `receipt_photos` text DEFAULT NULL AFTER `photos`
            ");
        }

        // Vérifier et ajouter created_by (WordPress user qui a créé)
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_parcels}` LIKE 'created_by'"
        );
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_parcels}`
                ADD COLUMN `created_by` bigint(20) UNSIGNED DEFAULT NULL AFTER `notes`,
                ADD KEY `created_by` (`created_by`)
            ");
        }

        // Ajouter les mêmes champs dans la table clients si nécessaire
        $table_clients = $wpdb->prefix . 'colis224_clients';

        // Vérifier et ajouter client_code dans clients
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_clients}` LIKE 'client_code'"
        );
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_clients}`
                ADD COLUMN `client_code` varchar(50) DEFAULT NULL AFTER `id`,
                ADD UNIQUE KEY `client_code` (`client_code`)
            ");
        }

        // Vérifier et ajouter id_card dans clients
        $column_exists = $wpdb->get_results(
            "SHOW COLUMNS FROM `{$table_clients}` LIKE 'id_card'"
        );
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE `{$table_clients}`
                ADD COLUMN `id_card` varchar(100) DEFAULT NULL AFTER `phone`
            ");
        }
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
