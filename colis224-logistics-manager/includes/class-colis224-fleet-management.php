<?php
/**
 * Classe de gestion de la flotte de véhicules
 * MODULE 28: Gestion complète des véhicules, maintenance, carburant, et alertes
 *
 * @package Colis224
 * @subpackage Fleet_Management
 * @since 2.9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Fleet_Management {

    /**
     * Créer les tables pour la gestion de flotte
     */
    public static function create_fleet_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table des véhicules
        $sql_vehicles = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_vehicles (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vehicle_number varchar(50) NOT NULL,
            brand varchar(100) NOT NULL,
            model varchar(100) NOT NULL,
            year int(4) DEFAULT NULL,
            license_plate varchar(50) NOT NULL UNIQUE,
            vehicle_type enum('car','van','truck','motorcycle','bicycle') DEFAULT 'van',
            fuel_type enum('gasoline','diesel','electric','hybrid') DEFAULT 'gasoline',
            capacity_kg decimal(10,2) DEFAULT 0,
            capacity_volume decimal(10,2) DEFAULT 0,
            purchase_date date DEFAULT NULL,
            purchase_price decimal(15,2) DEFAULT 0,
            current_driver_id bigint(20) DEFAULT NULL,
            status enum('active','maintenance','inactive','sold') DEFAULT 'active',
            mileage_km decimal(10,2) DEFAULT 0,
            insurance_expiry date DEFAULT NULL,
            technical_inspection_date date DEFAULT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_license_plate (license_plate),
            KEY idx_status (status),
            KEY idx_driver (current_driver_id)
        ) $charset_collate;";

        // Table de maintenance
        $sql_maintenance = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_vehicle_maintenance (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vehicle_id bigint(20) NOT NULL,
            maintenance_type enum('oil_change','tire_change','brake_service','technical_inspection','repair','cleaning','other') DEFAULT 'other',
            description text NOT NULL,
            maintenance_date date NOT NULL,
            next_maintenance_date date DEFAULT NULL,
            cost decimal(10,2) DEFAULT 0,
            mileage_at_service decimal(10,2) DEFAULT 0,
            service_provider varchar(200) DEFAULT NULL,
            performed_by varchar(100) DEFAULT NULL,
            status enum('scheduled','completed','cancelled') DEFAULT 'completed',
            invoice_number varchar(100) DEFAULT NULL,
            notes text,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_vehicle (vehicle_id),
            KEY idx_maintenance_date (maintenance_date),
            KEY idx_status (status)
        ) $charset_collate;";

        // Table de gestion carburant
        $sql_fuel = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_vehicle_fuel (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vehicle_id bigint(20) NOT NULL,
            refuel_date datetime NOT NULL,
            fuel_type enum('gasoline','diesel','electric','hybrid') DEFAULT 'gasoline',
            quantity_liters decimal(10,2) NOT NULL,
            cost_per_liter decimal(10,2) DEFAULT 0,
            total_cost decimal(10,2) NOT NULL,
            mileage_km decimal(10,2) NOT NULL,
            station_name varchar(200) DEFAULT NULL,
            driver_id bigint(20) DEFAULT NULL,
            is_full_tank tinyint(1) DEFAULT 0,
            receipt_number varchar(100) DEFAULT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_vehicle (vehicle_id),
            KEY idx_date (refuel_date),
            KEY idx_driver (driver_id)
        ) $charset_collate;";

        // Table d'affectation véhicule-livreur
        $sql_assignments = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_vehicle_assignments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vehicle_id bigint(20) NOT NULL,
            driver_id bigint(20) NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime DEFAULT NULL,
            start_mileage decimal(10,2) DEFAULT 0,
            end_mileage decimal(10,2) DEFAULT NULL,
            status enum('active','completed','cancelled') DEFAULT 'active',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_vehicle (vehicle_id),
            KEY idx_driver (driver_id),
            KEY idx_status (status)
        ) $charset_collate;";

        // Table d'alertes maintenance
        $sql_alerts = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_vehicle_alerts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            vehicle_id bigint(20) NOT NULL,
            alert_type enum('maintenance_due','insurance_expiry','inspection_due','high_mileage','fuel_efficiency','other') DEFAULT 'other',
            severity enum('low','medium','high','critical') DEFAULT 'medium',
            message text NOT NULL,
            due_date date DEFAULT NULL,
            is_resolved tinyint(1) DEFAULT 0,
            resolved_at datetime DEFAULT NULL,
            resolved_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_vehicle (vehicle_id),
            KEY idx_severity (severity),
            KEY idx_resolved (is_resolved)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_vehicles);
        dbDelta($sql_maintenance);
        dbDelta($sql_fuel);
        dbDelta($sql_assignments);
        dbDelta($sql_alerts);
    }

    /**
     * Ajouter un véhicule
     */
    public static function add_vehicle($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_vehicles';

        $defaults = array(
            'vehicle_number' => self::generate_vehicle_number(),
            'brand' => '',
            'model' => '',
            'year' => date('Y'),
            'license_plate' => '',
            'vehicle_type' => 'van',
            'fuel_type' => 'gasoline',
            'capacity_kg' => 0,
            'capacity_volume' => 0,
            'purchase_date' => date('Y-m-d'),
            'purchase_price' => 0,
            'current_driver_id' => null,
            'status' => 'active',
            'mileage_km' => 0,
            'insurance_expiry' => null,
            'technical_inspection_date' => null,
            'notes' => ''
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert($table, $data);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Générer un numéro de véhicule unique
     */
    private static function generate_vehicle_number() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_vehicles';

        do {
            $number = 'VH-' . strtoupper(wp_generate_password(8, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE vehicle_number = %s",
                $number
            ));
        } while ($exists > 0);

        return $number;
    }

    /**
     * Enregistrer une maintenance
     */
    public static function add_maintenance($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_vehicle_maintenance';

        $defaults = array(
            'vehicle_id' => 0,
            'maintenance_type' => 'other',
            'description' => '',
            'maintenance_date' => date('Y-m-d'),
            'next_maintenance_date' => null,
            'cost' => 0,
            'mileage_at_service' => 0,
            'service_provider' => '',
            'performed_by' => '',
            'status' => 'completed',
            'invoice_number' => '',
            'notes' => '',
            'created_by' => get_current_user_id()
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert($table, $data);

        if ($result) {
            // Créer une alerte si prochaine maintenance programmée
            if (!empty($data['next_maintenance_date'])) {
                self::create_maintenance_alert($data['vehicle_id'], $data['next_maintenance_date'], $data['maintenance_type']);
            }
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Enregistrer un ravitaillement
     */
    public static function add_fuel_entry($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_vehicle_fuel';

        $defaults = array(
            'vehicle_id' => 0,
            'refuel_date' => current_time('mysql'),
            'fuel_type' => 'gasoline',
            'quantity_liters' => 0,
            'cost_per_liter' => 0,
            'total_cost' => 0,
            'mileage_km' => 0,
            'station_name' => '',
            'driver_id' => null,
            'is_full_tank' => 0,
            'receipt_number' => '',
            'notes' => ''
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert($table, $data);

        if ($result) {
            // Mettre à jour le kilométrage du véhicule
            $wpdb->update(
                $wpdb->prefix . 'colis224_vehicles',
                array('mileage_km' => $data['mileage_km']),
                array('id' => $data['vehicle_id'])
            );

            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Affecter un véhicule à un livreur
     */
    public static function assign_vehicle($vehicle_id, $driver_id, $start_mileage, $notes = '') {
        global $wpdb;
        $table_assignments = $wpdb->prefix . 'colis224_vehicle_assignments';
        $table_vehicles = $wpdb->prefix . 'colis224_vehicles';

        // Terminer les affectations actives de ce véhicule
        $wpdb->update(
            $table_assignments,
            array(
                'status' => 'completed',
                'end_date' => current_time('mysql')
            ),
            array(
                'vehicle_id' => $vehicle_id,
                'status' => 'active'
            )
        );

        // Créer nouvelle affectation
        $result = $wpdb->insert(
            $table_assignments,
            array(
                'vehicle_id' => $vehicle_id,
                'driver_id' => $driver_id,
                'start_date' => current_time('mysql'),
                'start_mileage' => $start_mileage,
                'status' => 'active',
                'notes' => $notes
            )
        );

        if ($result) {
            // Mettre à jour le véhicule
            $wpdb->update(
                $table_vehicles,
                array('current_driver_id' => $driver_id),
                array('id' => $vehicle_id)
            );

            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Créer une alerte de maintenance
     */
    private static function create_maintenance_alert($vehicle_id, $due_date, $maintenance_type) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_vehicle_alerts';

        $vehicle = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_vehicles WHERE id = %d",
            $vehicle_id
        ));

        if (!$vehicle) {
            return false;
        }

        $days_until = floor((strtotime($due_date) - time()) / 86400);

        $severity = 'low';
        if ($days_until <= 3) {
            $severity = 'critical';
        } elseif ($days_until <= 7) {
            $severity = 'high';
        } elseif ($days_until <= 14) {
            $severity = 'medium';
        }

        $message = sprintf(
            'Maintenance %s prévue pour le véhicule %s (%s) le %s',
            $maintenance_type,
            $vehicle->vehicle_number,
            $vehicle->license_plate,
            date('d/m/Y', strtotime($due_date))
        );

        return $wpdb->insert(
            $table,
            array(
                'vehicle_id' => $vehicle_id,
                'alert_type' => 'maintenance_due',
                'severity' => $severity,
                'message' => $message,
                'due_date' => $due_date,
                'is_resolved' => 0
            )
        );
    }

    /**
     * Vérifier les alertes quotidiennes (cron)
     */
    public static function check_daily_alerts() {
        global $wpdb;
        $table_vehicles = $wpdb->prefix . 'colis224_vehicles';
        $table_alerts = $wpdb->prefix . 'colis224_vehicle_alerts';

        $vehicles = $wpdb->get_results("SELECT * FROM $table_vehicles WHERE status = 'active'");

        foreach ($vehicles as $vehicle) {
            // Vérifier expiration assurance
            if ($vehicle->insurance_expiry) {
                $days_until = floor((strtotime($vehicle->insurance_expiry) - time()) / 86400);

                if ($days_until <= 30 && $days_until > 0) {
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_alerts WHERE vehicle_id = %d AND alert_type = 'insurance_expiry' AND is_resolved = 0",
                        $vehicle->id
                    ));

                    if ($existing == 0) {
                        $severity = $days_until <= 7 ? 'critical' : ($days_until <= 14 ? 'high' : 'medium');

                        $wpdb->insert(
                            $table_alerts,
                            array(
                                'vehicle_id' => $vehicle->id,
                                'alert_type' => 'insurance_expiry',
                                'severity' => $severity,
                                'message' => sprintf(
                                    'Assurance du véhicule %s (%s) expire dans %d jours',
                                    $vehicle->vehicle_number,
                                    $vehicle->license_plate,
                                    $days_until
                                ),
                                'due_date' => $vehicle->insurance_expiry,
                                'is_resolved' => 0
                            )
                        );

                        // Notifier les admins
                        self::notify_admins_alert($vehicle, 'Expiration assurance', $days_until);
                    }
                }
            }

            // Vérifier contrôle technique
            if ($vehicle->technical_inspection_date) {
                $days_until = floor((strtotime($vehicle->technical_inspection_date) - time()) / 86400);

                if ($days_until <= 30 && $days_until > 0) {
                    $existing = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_alerts WHERE vehicle_id = %d AND alert_type = 'inspection_due' AND is_resolved = 0",
                        $vehicle->id
                    ));

                    if ($existing == 0) {
                        $severity = $days_until <= 7 ? 'critical' : ($days_until <= 14 ? 'high' : 'medium');

                        $wpdb->insert(
                            $table_alerts,
                            array(
                                'vehicle_id' => $vehicle->id,
                                'alert_type' => 'inspection_due',
                                'severity' => $severity,
                                'message' => sprintf(
                                    'Contrôle technique du véhicule %s (%s) dû dans %d jours',
                                    $vehicle->vehicle_number,
                                    $vehicle->license_plate,
                                    $days_until
                                ),
                                'due_date' => $vehicle->technical_inspection_date,
                                'is_resolved' => 0
                            )
                        );

                        // Notifier les admins
                        self::notify_admins_alert($vehicle, 'Contrôle technique', $days_until);
                    }
                }
            }
        }
    }

    /**
     * Notifier les admins d'une alerte
     */
    private static function notify_admins_alert($vehicle, $alert_type, $days_until) {
        $admin_email = get_option('admin_email');

        $subject = sprintf('[Colis224] Alerte Véhicule: %s', $alert_type);

        $message = sprintf(
            "Une alerte a été créée pour le véhicule:\n\n" .
            "Véhicule: %s\n" .
            "Immatriculation: %s\n" .
            "Type d'alerte: %s\n" .
            "Jours restants: %d\n\n" .
            "Veuillez prendre les mesures nécessaires.",
            $vehicle->vehicle_number,
            $vehicle->license_plate,
            $alert_type,
            $days_until
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Calculer la consommation moyenne
     */
    public static function get_average_fuel_consumption($vehicle_id, $days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_vehicle_fuel';

        $start_date = date('Y-m-d', strtotime("-$days days"));

        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(quantity_liters) as total_liters,
                SUM(total_cost) as total_cost,
                COUNT(*) as refuel_count,
                MAX(mileage_km) - MIN(mileage_km) as distance_km
            FROM $table
            WHERE vehicle_id = %d AND refuel_date >= %s",
            $vehicle_id,
            $start_date
        ));

        if ($stats && $stats->distance_km > 0) {
            return array(
                'liters_per_100km' => ($stats->total_liters / $stats->distance_km) * 100,
                'total_cost' => $stats->total_cost,
                'distance_km' => $stats->distance_km,
                'refuel_count' => $stats->refuel_count
            );
        }

        return null;
    }

    /**
     * Obtenir les véhicules avec alertes
     */
    public static function get_vehicles_with_alerts() {
        global $wpdb;

        return $wpdb->get_results("
            SELECT v.*,
                COUNT(a.id) as alert_count,
                SUM(CASE WHEN a.severity = 'critical' THEN 1 ELSE 0 END) as critical_alerts
            FROM {$wpdb->prefix}colis224_vehicles v
            LEFT JOIN {$wpdb->prefix}colis224_vehicle_alerts a ON v.id = a.vehicle_id AND a.is_resolved = 0
            WHERE v.status = 'active'
            GROUP BY v.id
            HAVING alert_count > 0
            ORDER BY critical_alerts DESC, alert_count DESC
        ");
    }
}
