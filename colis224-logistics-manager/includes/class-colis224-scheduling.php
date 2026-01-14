<?php
/**
 * Classe de gestion de la réservation et planification des livraisons
 * MODULE 31: Réservation créneaux, planification tournées, optimisation
 *
 * @package Colis224
 * @subpackage Scheduling
 * @since 2.9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Scheduling {

    /**
     * Créer les tables pour la planification
     */
    public static function create_scheduling_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table des créneaux horaires
        $sql_slots = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_delivery_slots (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            slot_name varchar(100) NOT NULL,
            day_of_week tinyint(1) NOT NULL COMMENT '0=Dimanche, 1=Lundi, ..., 6=Samedi',
            start_time time NOT NULL,
            end_time time NOT NULL,
            max_deliveries int(11) DEFAULT 10,
            current_bookings int(11) DEFAULT 0,
            zone varchar(100) DEFAULT NULL,
            vehicle_type varchar(50) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_day (day_of_week),
            KEY idx_zone (zone),
            KEY idx_active (is_active)
        ) $charset_collate;";

        // Table des réservations
        $sql_bookings = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_slot_bookings (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_number varchar(50) NOT NULL UNIQUE,
            parcel_id bigint(20) NOT NULL,
            client_id bigint(20) NOT NULL,
            slot_id bigint(20) NOT NULL,
            delivery_date date NOT NULL,
            status enum('pending','confirmed','cancelled','completed','rescheduled') DEFAULT 'pending',
            confirmation_sent tinyint(1) DEFAULT 0,
            confirmation_sent_at datetime DEFAULT NULL,
            client_notes text,
            admin_notes text,
            rescheduled_from bigint(20) DEFAULT NULL,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_parcel (parcel_id),
            KEY idx_slot (slot_id),
            KEY idx_date (delivery_date),
            KEY idx_status (status)
        ) $charset_collate;";

        // Table des tournées
        $sql_routes = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_delivery_routes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            route_number varchar(50) NOT NULL UNIQUE,
            route_name varchar(200) NOT NULL,
            route_date date NOT NULL,
            driver_id bigint(20) DEFAULT NULL,
            vehicle_id bigint(20) DEFAULT NULL,
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            estimated_duration int(11) DEFAULT 0 COMMENT 'En minutes',
            actual_duration int(11) DEFAULT NULL,
            start_location varchar(200) DEFAULT NULL,
            start_latitude decimal(10,8) DEFAULT NULL,
            start_longitude decimal(11,8) DEFAULT NULL,
            total_distance_km decimal(10,2) DEFAULT 0,
            total_parcels int(11) DEFAULT 0,
            completed_parcels int(11) DEFAULT 0,
            status enum('draft','scheduled','in_progress','completed','cancelled') DEFAULT 'draft',
            notes text,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_route_number (route_number),
            KEY idx_date (route_date),
            KEY idx_driver (driver_id),
            KEY idx_status (status)
        ) $charset_collate;";

        // Table des étapes de tournée
        $sql_stops = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_route_stops (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            route_id bigint(20) NOT NULL,
            parcel_id bigint(20) NOT NULL,
            stop_order int(11) NOT NULL,
            address text NOT NULL,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            estimated_arrival time DEFAULT NULL,
            actual_arrival time DEFAULT NULL,
            estimated_duration int(11) DEFAULT 15 COMMENT 'En minutes',
            status enum('pending','skipped','completed','failed') DEFAULT 'pending',
            notes text,
            photo_proof varchar(255) DEFAULT NULL,
            signature varchar(255) DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_route (route_id),
            KEY idx_parcel (parcel_id),
            KEY idx_order (stop_order)
        ) $charset_collate;";

        // Table d'optimisation de charge
        $sql_load_optimization = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_load_optimization (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            route_id bigint(20) NOT NULL,
            vehicle_capacity_kg decimal(10,2) NOT NULL,
            vehicle_capacity_volume decimal(10,2) NOT NULL,
            total_weight_kg decimal(10,2) DEFAULT 0,
            total_volume decimal(10,2) DEFAULT 0,
            utilization_percentage decimal(5,2) DEFAULT 0,
            is_optimized tinyint(1) DEFAULT 0,
            optimization_score decimal(5,2) DEFAULT 0,
            optimization_notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_route (route_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_slots);
        dbDelta($sql_bookings);
        dbDelta($sql_routes);
        dbDelta($sql_stops);
        dbDelta($sql_load_optimization);

        // Créer les créneaux par défaut
        self::create_default_slots();
    }

    /**
     * Créer les créneaux horaires par défaut
     */
    private static function create_default_slots() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_delivery_slots';

        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        if ($existing == 0) {
            $slots = array(
                // Lundi à Vendredi
                array('slot_name' => 'Matin (8h-12h)', 'day_of_week' => 1, 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'max_deliveries' => 15),
                array('slot_name' => 'Après-midi (14h-18h)', 'day_of_week' => 1, 'start_time' => '14:00:00', 'end_time' => '18:00:00', 'max_deliveries' => 15),
                array('slot_name' => 'Matin (8h-12h)', 'day_of_week' => 2, 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'max_deliveries' => 15),
                array('slot_name' => 'Après-midi (14h-18h)', 'day_of_week' => 2, 'start_time' => '14:00:00', 'end_time' => '18:00:00', 'max_deliveries' => 15),
                array('slot_name' => 'Matin (8h-12h)', 'day_of_week' => 3, 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'max_deliveries' => 15),
                array('slot_name' => 'Après-midi (14h-18h)', 'day_of_week' => 3, 'start_time' => '14:00:00', 'end_time' => '18:00:00', 'max_deliveries' => 15),
                array('slot_name' => 'Matin (8h-12h)', 'day_of_week' => 4, 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'max_deliveries' => 15),
                array('slot_name' => 'Après-midi (14h-18h)', 'day_of_week' => 4, 'start_time' => '14:00:00', 'end_time' => '18:00:00', 'max_deliveries' => 15),
                array('slot_name' => 'Matin (8h-12h)', 'day_of_week' => 5, 'start_time' => '08:00:00', 'end_time' => '12:00:00', 'max_deliveries' => 15),
                array('slot_name' => 'Après-midi (14h-18h)', 'day_of_week' => 5, 'start_time' => '14:00:00', 'end_time' => '18:00:00', 'max_deliveries' => 15),
                // Samedi
                array('slot_name' => 'Matin (8h-13h)', 'day_of_week' => 6, 'start_time' => '08:00:00', 'end_time' => '13:00:00', 'max_deliveries' => 10),
            );

            foreach ($slots as $slot) {
                $wpdb->insert($table, $slot);
            }
        }
    }

    /**
     * Créer une réservation de créneau
     */
    public static function create_booking($data) {
        global $wpdb;
        $table_bookings = $wpdb->prefix . 'colis224_slot_bookings';
        $table_slots = $wpdb->prefix . 'colis224_delivery_slots';

        $defaults = array(
            'booking_number' => self::generate_booking_number(),
            'parcel_id' => 0,
            'client_id' => 0,
            'slot_id' => 0,
            'delivery_date' => '',
            'status' => 'pending',
            'client_notes' => '',
            'admin_notes' => '',
            'created_by' => get_current_user_id()
        );

        $data = wp_parse_args($data, $defaults);

        // Vérifier disponibilité du créneau
        $slot = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_slots WHERE id = %d AND is_active = 1",
            $data['slot_id']
        ));

        if (!$slot) {
            return array('success' => false, 'message' => 'Créneau non disponible');
        }

        if ($slot->current_bookings >= $slot->max_deliveries) {
            return array('success' => false, 'message' => 'Créneau complet');
        }

        // Créer la réservation
        $result = $wpdb->insert($table_bookings, $data);

        if ($result) {
            $booking_id = $wpdb->insert_id;

            // Incrémenter le compteur de réservations
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_slots SET current_bookings = current_bookings + 1 WHERE id = %d",
                $data['slot_id']
            ));

            // Envoyer confirmation
            self::send_booking_confirmation($booking_id);

            return array('success' => true, 'booking_id' => $booking_id);
        }

        return array('success' => false, 'message' => 'Erreur lors de la création');
    }

    /**
     * Générer un numéro de réservation unique
     */
    private static function generate_booking_number() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_slot_bookings';

        do {
            $number = 'BK-' . date('Ymd') . '-' . strtoupper(wp_generate_password(6, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE booking_number = %s",
                $number
            ));
        } while ($exists > 0);

        return $number;
    }

    /**
     * Créer une tournée
     */
    public static function create_route($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_delivery_routes';

        $defaults = array(
            'route_number' => self::generate_route_number(),
            'route_name' => '',
            'route_date' => date('Y-m-d'),
            'driver_id' => null,
            'vehicle_id' => null,
            'start_time' => '08:00:00',
            'end_time' => null,
            'estimated_duration' => 0,
            'start_location' => '',
            'status' => 'draft',
            'notes' => '',
            'created_by' => get_current_user_id()
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert($table, $data);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Générer un numéro de tournée unique
     */
    private static function generate_route_number() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_delivery_routes';

        do {
            $number = 'RT-' . date('Ymd') . '-' . strtoupper(wp_generate_password(6, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE route_number = %s",
                $number
            ));
        } while ($exists > 0);

        return $number;
    }

    /**
     * Ajouter un colis à une tournée
     */
    public static function add_parcel_to_route($route_id, $parcel_id, $stop_order = null) {
        global $wpdb;
        $table_stops = $wpdb->prefix . 'colis224_route_stops';
        $table_routes = $wpdb->prefix . 'colis224_delivery_routes';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Si pas d'ordre spécifié, prendre le suivant
        if ($stop_order === null) {
            $max_order = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(stop_order) FROM $table_stops WHERE route_id = %d",
                $route_id
            ));
            $stop_order = $max_order ? $max_order + 1 : 1;
        }

        // Récupérer les infos du colis
        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_parcels WHERE id = %d",
            $parcel_id
        ));

        if (!$parcel) {
            return false;
        }

        $stop_data = array(
            'route_id' => $route_id,
            'parcel_id' => $parcel_id,
            'stop_order' => $stop_order,
            'address' => $parcel->recipient_address,
            'status' => 'pending'
        );

        $result = $wpdb->insert($table_stops, $stop_data);

        if ($result) {
            // Mettre à jour le compteur de la tournée
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_routes SET total_parcels = total_parcels + 1 WHERE id = %d",
                $route_id
            ));

            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Optimiser l'ordre des étapes d'une tournée
     */
    public static function optimize_route($route_id) {
        global $wpdb;
        $table_stops = $wpdb->prefix . 'colis224_route_stops';

        // Récupérer toutes les étapes
        $stops = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_stops WHERE route_id = %d ORDER BY stop_order",
            $route_id
        ));

        if (empty($stops)) {
            return false;
        }

        // Algorithme simple : regrouper par zone géographique
        // Dans une vraie implémentation, utiliser Google Maps API ou algorithme du voyageur de commerce

        // Pour l'instant, on trie alphabétiquement par adresse (simulation)
        usort($stops, function($a, $b) {
            return strcmp($a->address, $b->address);
        });

        // Réordonner
        $order = 1;
        foreach ($stops as $stop) {
            $wpdb->update(
                $table_stops,
                array('stop_order' => $order),
                array('id' => $stop->id)
            );
            $order++;
        }

        return true;
    }

    /**
     * Calculer l'optimisation de charge
     */
    public static function calculate_load_optimization($route_id) {
        global $wpdb;
        $table_routes = $wpdb->prefix . 'colis224_delivery_routes';
        $table_stops = $wpdb->prefix . 'colis224_route_stops';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_vehicles = $wpdb->prefix . 'colis224_vehicles';
        $table_optimization = $wpdb->prefix . 'colis224_load_optimization';

        $route = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_routes WHERE id = %d",
            $route_id
        ));

        if (!$route || !$route->vehicle_id) {
            return false;
        }

        // Récupérer les capacités du véhicule
        $vehicle = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_vehicles WHERE id = %d",
            $route->vehicle_id
        ));

        if (!$vehicle) {
            return false;
        }

        // Calculer le poids et volume total
        $totals = $wpdb->get_row($wpdb->prepare(
            "SELECT
                SUM(p.weight) as total_weight,
                SUM(p.length * p.width * p.height / 1000000) as total_volume
            FROM $table_stops s
            INNER JOIN $table_parcels p ON s.parcel_id = p.id
            WHERE s.route_id = %d",
            $route_id
        ));

        $total_weight = $totals->total_weight ?: 0;
        $total_volume = $totals->total_volume ?: 0;

        // Calculer l'utilisation
        $weight_util = $vehicle->capacity_kg > 0 ? ($total_weight / $vehicle->capacity_kg) * 100 : 0;
        $volume_util = $vehicle->capacity_volume > 0 ? ($total_volume / $vehicle->capacity_volume) * 100 : 0;

        $utilization = max($weight_util, $volume_util);

        // Score d'optimisation (idéal = 80-90% d'utilisation)
        if ($utilization >= 80 && $utilization <= 95) {
            $score = 100;
        } elseif ($utilization > 95) {
            $score = 100 - (($utilization - 95) * 5); // Pénalité pour surcharge
        } else {
            $score = ($utilization / 80) * 100; // Moins bon si sous-utilisé
        }

        $optimization_data = array(
            'route_id' => $route_id,
            'vehicle_capacity_kg' => $vehicle->capacity_kg,
            'vehicle_capacity_volume' => $vehicle->capacity_volume,
            'total_weight_kg' => $total_weight,
            'total_volume' => $total_volume,
            'utilization_percentage' => $utilization,
            'is_optimized' => $score >= 70 ? 1 : 0,
            'optimization_score' => $score,
            'optimization_notes' => self::get_optimization_notes($utilization, $score)
        );

        // Supprimer ancienne optimisation si existe
        $wpdb->delete($table_optimization, array('route_id' => $route_id));

        // Insérer nouvelle optimisation
        $wpdb->insert($table_optimization, $optimization_data);

        return $optimization_data;
    }

    /**
     * Obtenir les notes d'optimisation
     */
    private static function get_optimization_notes($utilization, $score) {
        if ($utilization > 100) {
            return 'ATTENTION: Véhicule en surcharge! Réduire le nombre de colis.';
        } elseif ($utilization > 95) {
            return 'Véhicule presque saturé. Vérifier que la charge est sécurisée.';
        } elseif ($utilization >= 80) {
            return 'Optimisation excellente! Charge équilibrée.';
        } elseif ($utilization >= 60) {
            return 'Bonne utilisation. Possibilité d\'ajouter quelques colis.';
        } else {
            return 'Véhicule sous-utilisé. Ajouter plus de colis pour optimiser.';
        }
    }

    /**
     * Envoyer une confirmation de réservation
     */
    private static function send_booking_confirmation($booking_id) {
        global $wpdb;
        $table_bookings = $wpdb->prefix . 'colis224_slot_bookings';
        $table_slots = $wpdb->prefix . 'colis224_delivery_slots';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT b.*, s.slot_name, s.start_time, s.end_time
            FROM $table_bookings b
            INNER JOIN $table_slots s ON b.slot_id = s.id
            WHERE b.id = %d",
            $booking_id
        ));

        if (!$booking) {
            return false;
        }

        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $booking->client_id
        ));

        if (!$client || empty($client->email)) {
            return false;
        }

        $subject = sprintf('[Colis224] Confirmation de créneau: %s', $booking->booking_number);

        $message = sprintf(
            "Bonjour %s,\n\n" .
            "Votre réservation de créneau de livraison est confirmée:\n\n" .
            "Numéro de réservation: %s\n" .
            "Date: %s\n" .
            "Créneau: %s (%s - %s)\n\n" .
            "Nous vous contacterons avant la livraison.\n\n" .
            "Cordialement,\nL'équipe Colis224",
            $client->name,
            $booking->booking_number,
            date('d/m/Y', strtotime($booking->delivery_date)),
            $booking->slot_name,
            substr($booking->start_time, 0, 5),
            substr($booking->end_time, 0, 5)
        );

        $result = wp_mail($client->email, $subject, $message);

        if ($result) {
            $wpdb->update(
                $table_bookings,
                array(
                    'confirmation_sent' => 1,
                    'confirmation_sent_at' => current_time('mysql')
                ),
                array('id' => $booking_id)
            );
        }

        return $result;
    }

    /**
     * Obtenir les créneaux disponibles pour une date
     */
    public static function get_available_slots($date) {
        global $wpdb;
        $table_slots = $wpdb->prefix . 'colis224_delivery_slots';

        $day_of_week = date('w', strtotime($date));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT *,
                (max_deliveries - current_bookings) as available_spaces
            FROM $table_slots
            WHERE day_of_week = %d
            AND is_active = 1
            AND current_bookings < max_deliveries
            ORDER BY start_time",
            $day_of_week
        ));
    }
}
