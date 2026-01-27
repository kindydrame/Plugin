<?php
/**
 * MODULE 23: Gestion de Stock Avancée
 * Alertes stock minimum, prévisions, emplacements, audit
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Inventory_Advanced {

    public function __construct() {
        // Hooks pour les alertes automatiques
        add_action('colis224_daily_inventory_check', array($this, 'check_low_stock'));
        add_action('init', array($this, 'schedule_inventory_checks'));
    }

    /**
     * Créer les tables pour la gestion de stock avancée
     */
    public static function create_inventory_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table des emplacements en entrepôt
        $table_locations = $wpdb->prefix . 'colis224_inventory_locations';
        $sql_locations = "CREATE TABLE $table_locations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            warehouse_id bigint(20) UNSIGNED NOT NULL,
            location_code varchar(50) NOT NULL,
            location_name varchar(255) NOT NULL,
            zone varchar(100) DEFAULT NULL,
            aisle varchar(50) DEFAULT NULL,
            shelf varchar(50) DEFAULT NULL,
            bin varchar(50) DEFAULT NULL,
            capacity int(11) DEFAULT 0,
            current_occupancy int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY location_code (location_code),
            KEY warehouse_id (warehouse_id),
            KEY is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql_locations);

        // Table des mouvements de stock
        $table_movements = $wpdb->prefix . 'colis224_inventory_movements';
        $sql_movements = "CREATE TABLE $table_movements (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parcel_id bigint(20) UNSIGNED NOT NULL,
            warehouse_id bigint(20) UNSIGNED NOT NULL,
            location_id bigint(20) UNSIGNED DEFAULT NULL,
            movement_type enum('in','out','transfer','adjustment') DEFAULT 'in',
            from_location_id bigint(20) UNSIGNED DEFAULT NULL,
            to_location_id bigint(20) UNSIGNED DEFAULT NULL,
            quantity int(11) DEFAULT 1,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            notes text DEFAULT NULL,
            movement_date datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY parcel_id (parcel_id),
            KEY warehouse_id (warehouse_id),
            KEY location_id (location_id),
            KEY movement_type (movement_type),
            KEY movement_date (movement_date)
        ) $charset_collate;";
        dbDelta($sql_movements);

        // Table des alertes stock
        $table_alerts = $wpdb->prefix . 'colis224_inventory_alerts';
        $sql_alerts = "CREATE TABLE $table_alerts (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            alert_type enum('low_stock','overstock','expiry','location_full') DEFAULT 'low_stock',
            warehouse_id bigint(20) UNSIGNED DEFAULT NULL,
            location_id bigint(20) UNSIGNED DEFAULT NULL,
            parcel_id bigint(20) UNSIGNED DEFAULT NULL,
            severity enum('low','medium','high','critical') DEFAULT 'medium',
            message text NOT NULL,
            is_resolved tinyint(1) DEFAULT 0,
            resolved_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY alert_type (alert_type),
            KEY severity (severity),
            KEY is_resolved (is_resolved)
        ) $charset_collate;";
        dbDelta($sql_alerts);

        // Table des audits de stock
        $table_audits = $wpdb->prefix . 'colis224_inventory_audits';
        $sql_audits = "CREATE TABLE $table_audits (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            warehouse_id bigint(20) UNSIGNED NOT NULL,
            audit_date date NOT NULL,
            audited_by bigint(20) UNSIGNED DEFAULT NULL,
            expected_count int(11) DEFAULT 0,
            actual_count int(11) DEFAULT 0,
            discrepancy int(11) DEFAULT 0,
            status enum('planned','in_progress','completed') DEFAULT 'planned',
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY warehouse_id (warehouse_id),
            KEY audit_date (audit_date),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_audits);
    }

    /**
     * Planifier les vérifications automatiques
     */
    public function schedule_inventory_checks() {
        if (!wp_next_scheduled('colis224_daily_inventory_check')) {
            wp_schedule_event(time(), 'daily', 'colis224_daily_inventory_check');
        }
    }

    /**
     * Vérifier les niveaux de stock
     */
    public function check_low_stock() {
        global $wpdb;
        $table_warehouses = $wpdb->prefix . 'colis224_warehouses';
        $table_inventory = $wpdb->prefix . 'colis224_warehouse_inventory';

        // Récupérer les entrepôts
        $warehouses = $wpdb->get_results("SELECT * FROM $table_warehouses WHERE is_active = 1");

        foreach ($warehouses as $warehouse) {
            // Compter les colis en stock
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_inventory WHERE warehouse_id = %d AND status = 'in_stock'",
                $warehouse->id
            ));

            // Si capacité dépassée ou proche
            if ($warehouse->capacity > 0) {
                $usage_percent = ($count / $warehouse->capacity) * 100;

                if ($usage_percent >= 90) {
                    $this->create_alert('overstock', $warehouse->id, null, null, 'critical',
                        "Entrepôt {$warehouse->name} saturé à {$usage_percent}% ({$count}/{$warehouse->capacity})");
                } elseif ($usage_percent >= 75) {
                    $this->create_alert('overstock', $warehouse->id, null, null, 'high',
                        "Entrepôt {$warehouse->name} à {$usage_percent}% ({$count}/{$warehouse->capacity})");
                }
            }
        }
    }

    /**
     * Créer une alerte
     */
    private function create_alert($type, $warehouse_id, $location_id, $parcel_id, $severity, $message) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_inventory_alerts';

        // Vérifier si alerte similaire existe déjà
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table
             WHERE alert_type = %s
             AND warehouse_id = %d
             AND is_resolved = 0
             AND DATE(created_at) = CURRENT_DATE",
            $type,
            $warehouse_id
        ));

        if ($existing > 0) {
            return; // Alerte déjà créée aujourd'hui
        }

        $wpdb->insert($table, array(
            'alert_type' => $type,
            'warehouse_id' => $warehouse_id,
            'location_id' => $location_id,
            'parcel_id' => $parcel_id,
            'severity' => $severity,
            'message' => $message
        ));

        // Envoyer notification si critique
        if ($severity === 'critical') {
            $admin_email = get_option('admin_email');
            wp_mail($admin_email, 'Alerte Stock Critique - Colis224', $message);
        }
    }

    /**
     * Enregistrer un mouvement de stock
     */
    public static function record_movement($parcel_id, $warehouse_id, $location_id, $type, $notes = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_inventory_movements';

        $user_id = get_current_user_id();

        return $wpdb->insert($table, array(
            'parcel_id' => $parcel_id,
            'warehouse_id' => $warehouse_id,
            'location_id' => $location_id,
            'movement_type' => $type,
            'quantity' => 1,
            'user_id' => $user_id,
            'notes' => $notes
        ));
    }

    /**
     * Obtenir les statistiques de stock
     */
    public static function get_inventory_stats($warehouse_id = null) {
        global $wpdb;
        $table_inventory = $wpdb->prefix . 'colis224_warehouse_inventory';

        $where = $warehouse_id ? $wpdb->prepare(' WHERE warehouse_id = %d', $warehouse_id) : '';

        $stats = array();

        // En stock
        $stats['in_stock'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_inventory $where AND status = 'in_stock'"
        );

        // En transit
        $stats['in_transit'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_inventory $where AND status = 'in_transit'"
        );

        // Livrés
        $stats['delivered'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_inventory $where AND status = 'delivered'"
        );

        // Alertes actives
        $table_alerts = $wpdb->prefix . 'colis224_inventory_alerts';
        $stats['active_alerts'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_alerts WHERE is_resolved = 0"
        );

        return $stats;
    }
}

// Initialiser
new Colis224_Inventory_Advanced();
