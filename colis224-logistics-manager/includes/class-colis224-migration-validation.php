<?php
/**
 * Migration pour le système de validation hiérarchique
 *
 * @package Colis224_Logistics
 * @subpackage Includes
 * @version 2.11.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Colis224_Migration_Validation {

    /**
     * Version de la migration
     */
    const MIGRATION_VERSION = '2.11.0';

    /**
     * Exécuter la migration
     *
     * @return array {
     *     @type bool   $success  Succès de la migration
     *     @type string $message  Message de résultat
     *     @type array  $details  Détails des opérations
     * }
     */
    public static function run() {
        global $wpdb;

        $results = array(
            'success' => true,
            'message' => '',
            'details' => array()
        );

        // Vérifier si migration déjà effectuée
        $current_version = get_option('colis224_validation_migration_version', '0');

        if (version_compare($current_version, self::MIGRATION_VERSION, '>=')) {
            $results['message'] = 'Migration déjà effectuée.';
            return $results;
        }

        // Sauvegarder la structure actuelle (backup)
        $backup_result = self::backup_table_structure();
        $results['details']['backup'] = $backup_result;

        if (!$backup_result['success']) {
            $results['success'] = false;
            $results['message'] = 'Échec de la sauvegarde de la structure.';
            return $results;
        }

        // Démarrer une transaction
        $wpdb->query('START TRANSACTION');

        try {
            // 1. Ajouter colonnes à la table parcels
            $alter_parcels = self::alter_parcels_table();
            $results['details']['alter_parcels'] = $alter_parcels;

            if (!$alter_parcels['success']) {
                throw new Exception($alter_parcels['message']);
            }

            // 2. Créer table d'historique
            $create_history = self::create_history_table();
            $results['details']['create_history'] = $create_history;

            if (!$create_history['success']) {
                throw new Exception($create_history['message']);
            }

            // 3. Migrer les données existantes (définir created_by pour colis existants)
            $migrate_data = self::migrate_existing_data();
            $results['details']['migrate_data'] = $migrate_data;

            if (!$migrate_data['success']) {
                throw new Exception($migrate_data['message']);
            }

            // Commit transaction
            $wpdb->query('COMMIT');

            // Enregistrer la version de migration
            update_option('colis224_validation_migration_version', self::MIGRATION_VERSION);

            $results['success'] = true;
            $results['message'] = 'Migration effectuée avec succès !';

        } catch (Exception $e) {
            // Rollback en cas d'erreur
            $wpdb->query('ROLLBACK');

            $results['success'] = false;
            $results['message'] = 'Erreur lors de la migration : ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Sauvegarder la structure de la table parcels
     */
    private static function backup_table_structure() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'colis224_parcels';

        // Récupérer la structure CREATE TABLE
        $create_table = $wpdb->get_row("SHOW CREATE TABLE $table_name", ARRAY_A);

        if (!$create_table) {
            return array(
                'success' => false,
                'message' => 'Impossible de récupérer la structure de la table.'
            );
        }

        // Sauvegarder dans les options WordPress
        $backup_key = 'colis224_parcels_backup_' . time();
        update_option($backup_key, $create_table['Create Table']);

        return array(
            'success' => true,
            'message' => 'Structure sauvegardée.',
            'backup_key' => $backup_key
        );
    }

    /**
     * Modifier la table parcels pour ajouter les colonnes de validation
     */
    private static function alter_parcels_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'colis224_parcels';
        $modifications = array();

        // Vérifier si les colonnes existent déjà
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $existing_columns = array();
        foreach ($columns as $column) {
            $existing_columns[] = $column->Field;
        }

        // Ajouter created_by (référence à l'utilisateur WordPress qui a créé le colis)
        if (!in_array('created_by', $existing_columns)) {
            $result = $wpdb->query("
                ALTER TABLE $table_name
                ADD COLUMN created_by bigint(20) UNSIGNED DEFAULT NULL AFTER driver_id,
                ADD INDEX idx_created_by (created_by)
            ");

            $modifications['created_by'] = ($result !== false) ? 'Ajoutée' : 'Échec';
        } else {
            $modifications['created_by'] = 'Existe déjà';
        }

        // Ajouter validated_by (admin qui a validé)
        if (!in_array('validated_by', $existing_columns)) {
            $result = $wpdb->query("
                ALTER TABLE $table_name
                ADD COLUMN validated_by bigint(20) UNSIGNED DEFAULT NULL AFTER created_by,
                ADD INDEX idx_validated_by (validated_by)
            ");

            $modifications['validated_by'] = ($result !== false) ? 'Ajoutée' : 'Échec';
        } else {
            $modifications['validated_by'] = 'Existe déjà';
        }

        // Ajouter validated_at
        if (!in_array('validated_at', $existing_columns)) {
            $result = $wpdb->query("
                ALTER TABLE $table_name
                ADD COLUMN validated_at datetime DEFAULT NULL AFTER validated_by
            ");

            $modifications['validated_at'] = ($result !== false) ? 'Ajoutée' : 'Échec';
        } else {
            $modifications['validated_at'] = 'Existe déjà';
        }

        // Ajouter validation_status
        if (!in_array('validation_status', $existing_columns)) {
            $result = $wpdb->query("
                ALTER TABLE $table_name
                ADD COLUMN validation_status enum('pending','approved','rejected') DEFAULT 'approved' AFTER validated_at,
                ADD INDEX idx_validation_status (validation_status)
            ");

            $modifications['validation_status'] = ($result !== false) ? 'Ajoutée' : 'Échec';
        } else {
            $modifications['validation_status'] = 'Existe déjà';
        }

        // Ajouter validation_notes (commentaire de validation/rejet)
        if (!in_array('validation_notes', $existing_columns)) {
            $result = $wpdb->query("
                ALTER TABLE $table_name
                ADD COLUMN validation_notes text DEFAULT NULL AFTER validation_status
            ");

            $modifications['validation_notes'] = ($result !== false) ? 'Ajoutée' : 'Échec';
        } else {
            $modifications['validation_notes'] = 'Existe déjà';
        }

        // Vérifier si toutes les modifications ont réussi
        $all_success = true;
        foreach ($modifications as $col => $status) {
            if ($status === 'Échec') {
                $all_success = false;
                break;
            }
        }

        return array(
            'success' => $all_success,
            'message' => $all_success ? 'Table parcels modifiée avec succès.' : 'Échec de modification de la table parcels.',
            'modifications' => $modifications
        );
    }

    /**
     * Créer la table d'historique des modifications
     */
    private static function create_history_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'colis224_parcel_history';
        $charset_collate = $wpdb->get_charset_collate();

        // Vérifier si la table existe déjà
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

        if ($table_exists) {
            return array(
                'success' => true,
                'message' => 'Table d\'historique existe déjà.'
            );
        }

        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parcel_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            user_name varchar(255) DEFAULT NULL,
            user_role varchar(50) DEFAULT NULL,
            action varchar(100) NOT NULL,
            field_changed varchar(100) DEFAULT NULL,
            old_value text DEFAULT NULL,
            new_value text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_parcel_id (parcel_id),
            KEY idx_user_id (user_id),
            KEY idx_action (action),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Vérifier que la table a bien été créée
        $table_created = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

        return array(
            'success' => ($table_created !== null),
            'message' => ($table_created !== null) ? 'Table d\'historique créée avec succès.' : 'Échec de création de la table d\'historique.'
        );
    }

    /**
     * Migrer les données existantes
     * Définir validation_status='approved' pour tous les colis existants
     */
    private static function migrate_existing_data() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'colis224_parcels';

        // Compter les colis existants
        $total_parcels = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        // Marquer tous les colis existants comme approuvés (rétrocompatibilité)
        $result = $wpdb->query("
            UPDATE $table_name
            SET validation_status = 'approved',
                validated_at = created_at
            WHERE validation_status IS NULL OR validation_status = ''
        ");

        return array(
            'success' => ($result !== false),
            'message' => ($result !== false)
                ? "Migration des données effectuée : $total_parcels colis marqués comme approuvés."
                : 'Échec de la migration des données.',
            'total_parcels' => $total_parcels,
            'updated_count' => $result
        );
    }

    /**
     * Rollback de la migration
     *
     * @param string $backup_key Clé de sauvegarde
     */
    public static function rollback($backup_key = null) {
        global $wpdb;

        if (!$backup_key) {
            return array(
                'success' => false,
                'message' => 'Clé de sauvegarde manquante.'
            );
        }

        // Récupérer la structure sauvegardée
        $backup_sql = get_option($backup_key);

        if (!$backup_sql) {
            return array(
                'success' => false,
                'message' => 'Sauvegarde introuvable.'
            );
        }

        $table_name = $wpdb->prefix . 'colis224_parcels';

        // Supprimer les colonnes ajoutées
        $wpdb->query("ALTER TABLE $table_name DROP COLUMN IF EXISTS created_by");
        $wpdb->query("ALTER TABLE $table_name DROP COLUMN IF EXISTS validated_by");
        $wpdb->query("ALTER TABLE $table_name DROP COLUMN IF EXISTS validated_at");
        $wpdb->query("ALTER TABLE $table_name DROP COLUMN IF EXISTS validation_status");
        $wpdb->query("ALTER TABLE $table_name DROP COLUMN IF EXISTS validation_notes");

        // Supprimer la table d'historique
        $history_table = $wpdb->prefix . 'colis224_parcel_history';
        $wpdb->query("DROP TABLE IF EXISTS $history_table");

        // Supprimer la version de migration
        delete_option('colis224_validation_migration_version');

        return array(
            'success' => true,
            'message' => 'Rollback effectué avec succès.'
        );
    }

    /**
     * Vérifier l'état de la migration
     */
    public static function check_status() {
        $current_version = get_option('colis224_validation_migration_version', '0');

        return array(
            'is_migrated' => version_compare($current_version, self::MIGRATION_VERSION, '>='),
            'current_version' => $current_version,
            'target_version' => self::MIGRATION_VERSION
        );
    }
}
