<?php
/**
 * Système d'automatisation des départs
 * Crée automatiquement des départs selon des règles prédéfinies
 *
 * @package Colis224
 * @subpackage Automation
 * @since 2.10.6
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Departures_Automation {

    /**
     * Initialiser l'automatisation
     */
    public static function init() {
        // Créer la table des règles d'automatisation
        self::create_automation_table();
        
        // Initialiser les règles par défaut si nécessaire
        self::initialize_default_rules();
        
        // Enregistrer le cron job
        add_action('colis224_daily_departure_automation', array(__CLASS__, 'run_automation'));
        
        // Activer le cron si pas déjà fait
        if (!wp_next_scheduled('colis224_daily_departure_automation')) {
            wp_schedule_event(time(), 'daily', 'colis224_daily_departure_automation');
        }
    }

    /**
     * Créer la table des règles d'automatisation
     */
    public static function create_automation_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_departure_automation_rules (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            rule_name varchar(200) NOT NULL,
            departure_city varchar(200) NOT NULL,
            departure_country varchar(100) NOT NULL,
            departure_country_code varchar(2) NOT NULL,
            arrival_city varchar(200) NOT NULL,
            arrival_country varchar(100) NOT NULL,
            arrival_country_code varchar(2) NOT NULL,
            transport_type enum('plane','boat') DEFAULT 'plane',
            frequency enum('daily','weekly','days_interval','custom') NOT NULL,
            days_of_week varchar(50) DEFAULT NULL COMMENT 'Comma-separated: 1=Monday,7=Sunday',
            days_interval int(11) DEFAULT NULL COMMENT 'Pour frequency=days_interval',
            random_day_in_week tinyint(1) DEFAULT 0,
            departure_time time DEFAULT '08:00:00',
            estimated_duration varchar(50) DEFAULT NULL,
            default_capacity int(11) DEFAULT 100,
            default_price decimal(15,2) DEFAULT NULL,
            currency varchar(3) DEFAULT 'GNF',
            whatsapp_number varchar(20) DEFAULT '+224620178930',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_execution datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_active (is_active),
            KEY idx_frequency (frequency)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Initialiser les règles par défaut
     */
    public static function initialize_default_rules() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departure_automation_rules';

        // Vérifier si des règles existent déjà
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return; // Les règles existent déjà
        }

        // Règle 1: Conakry → USA (Tous les vendredis)
        $wpdb->insert($table, array(
            'rule_name' => 'Conakry → USA (Vendredis)',
            'departure_city' => 'Conakry',
            'departure_country' => 'Guinée',
            'departure_country_code' => 'GN',
            'arrival_city' => 'New York',
            'arrival_country' => 'États-Unis',
            'arrival_country_code' => 'US',
            'transport_type' => 'plane',
            'frequency' => 'weekly',
            'days_of_week' => '5', // Vendredi = 5
            'departure_time' => '14:00:00',
            'estimated_duration' => '12h',
            'default_capacity' => 150,
            'default_price' => 2500000,
            'currency' => 'GNF',
            'is_active' => 1
        ));

        // Règle 2: Conakry → Dakar (Mardi, Jeudi, Samedi)
        $wpdb->insert($table, array(
            'rule_name' => 'Conakry → Dakar (Mar/Jeu/Sam)',
            'departure_city' => 'Conakry',
            'departure_country' => 'Guinée',
            'departure_country_code' => 'GN',
            'arrival_city' => 'Dakar',
            'arrival_country' => 'Sénégal',
            'arrival_country_code' => 'SN',
            'transport_type' => 'plane',
            'frequency' => 'weekly',
            'days_of_week' => '2,4,6', // Mardi, Jeudi, Samedi
            'departure_time' => '10:00:00',
            'estimated_duration' => '2h30',
            'default_capacity' => 100,
            'default_price' => 800000,
            'currency' => 'GNF',
            'is_active' => 1
        ));

        // Règle 3: Conakry → Maroc (1 départ/semaine aléatoire)
        $wpdb->insert($table, array(
            'rule_name' => 'Conakry → Maroc (1x/semaine)',
            'departure_city' => 'Conakry',
            'departure_country' => 'Guinée',
            'departure_country_code' => 'GN',
            'arrival_city' => 'Casablanca',
            'arrival_country' => 'Maroc',
            'arrival_country_code' => 'MA',
            'transport_type' => 'plane',
            'frequency' => 'weekly',
            'days_of_week' => '1,2,3,4,5,6', // Lundi à Samedi
            'random_day_in_week' => 1,
            'departure_time' => '09:00:00',
            'estimated_duration' => '5h',
            'default_capacity' => 120,
            'default_price' => 1500000,
            'currency' => 'GNF',
            'is_active' => 1
        ));

        // Règle 4: Conakry → Paris (Tous les 3 jours)
        $wpdb->insert($table, array(
            'rule_name' => 'Conakry → Paris (Tous les 3 jours)',
            'departure_city' => 'Conakry',
            'departure_country' => 'Guinée',
            'departure_country_code' => 'GN',
            'arrival_city' => 'Paris',
            'arrival_country' => 'France',
            'arrival_country_code' => 'FR',
            'transport_type' => 'plane',
            'frequency' => 'days_interval',
            'days_of_week' => '1,2,3,4,5,6', // Lundi à Samedi uniquement
            'days_interval' => 3,
            'departure_time' => '22:00:00',
            'estimated_duration' => '6h',
            'default_capacity' => 180,
            'default_price' => 3000000,
            'currency' => 'GNF',
            'is_active' => 1
        ));
    }

    /**
     * Exécuter l'automatisation quotidienne
     */
    public static function run_automation() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departure_automation_rules';

        // Récupérer toutes les règles actives
        $rules = $wpdb->get_results("SELECT * FROM $table WHERE is_active = 1");

        $today = date('N'); // 1 = Lundi, 7 = Dimanche
        $created_count = 0;

        foreach ($rules as $rule) {
            $should_create = false;

            switch ($rule->frequency) {
                case 'daily':
                    $should_create = true;
                    break;

                case 'weekly':
                    if ($rule->random_day_in_week == 1) {
                        // Créer un départ aléatoire dans la semaine (1 seul par semaine)
                        $should_create = self::should_create_random_weekly($rule);
                    } else {
                        // Vérifier si aujourd'hui est dans les jours de la semaine
                        $days = explode(',', $rule->days_of_week);
                        $should_create = in_array($today, $days);
                    }
                    break;

                case 'days_interval':
                    // Tous les X jours (en respectant les jours de la semaine autorisés)
                    $should_create = self::should_create_days_interval($rule, $today);
                    break;
            }

            if ($should_create) {
                // Créer le départ
                $departure_date = self::calculate_next_departure_date($rule);
                
                $departure_data = array(
                    'departure_city' => $rule->departure_city,
                    'departure_country' => $rule->departure_country,
                    'departure_country_code' => $rule->departure_country_code,
                    'arrival_city' => $rule->arrival_city,
                    'arrival_country' => $rule->arrival_country,
                    'arrival_country_code' => $rule->arrival_country_code,
                    'departure_date' => $departure_date,
                    'departure_time' => $rule->departure_time,
                    'transport_type' => $rule->transport_type,
                    'estimated_duration' => $rule->estimated_duration,
                    'available_seats' => $rule->default_capacity,
                    'price_estimate' => $rule->default_price,
                    'currency' => $rule->currency,
                    'status' => 'scheduled',
                    'notes' => 'Départ créé automatiquement par règle: ' . $rule->rule_name,
                    'whatsapp_number' => $rule->whatsapp_number,
                    'is_active' => 1,
                    'created_by' => 0 // Système
                );

                $result = Colis224_Departures::add_departure($departure_data);
                
                if ($result) {
                    // Mettre à jour la date de dernière exécution
                    $wpdb->update(
                        $table,
                        array('last_execution' => current_time('mysql')),
                        array('id' => $rule->id)
                    );
                    $created_count++;
                }
            }
        }

        // Log de l'exécution
        if ($created_count > 0) {
            error_log("Colis224 Automation: {$created_count} départ(s) créé(s) automatiquement.");
        }

        return $created_count;
    }

    /**
     * Vérifier si on doit créer un départ aléatoire hebdomadaire
     */
    private static function should_create_random_weekly($rule) {
        global $wpdb;

        // Vérifier si un départ a déjà été créé cette semaine
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $week_end = date('Y-m-d', strtotime('sunday this week'));

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_departures 
            WHERE departure_city = %s 
            AND arrival_city = %s 
            AND departure_date BETWEEN %s AND %s
            AND notes LIKE %s",
            $rule->departure_city,
            $rule->arrival_city,
            $week_start,
            $week_end,
            '%règle: ' . $rule->rule_name . '%'
        ));

        if ($count > 0) {
            return false; // Déjà créé cette semaine
        }

        // Créer seulement un jour de la semaine (par exemple lundi)
        $today = date('N');
        $allowed_days = explode(',', $rule->days_of_week);
        
        // Choisir aléatoirement un jour parmi les jours autorisés
        // Pour éviter de créer plusieurs fois, on le fait seulement le lundi
        if ($today == 1) {
            return true;
        }

        return false;
    }

    /**
     * Vérifier si on doit créer un départ selon un intervalle de jours
     */
    private static function should_create_days_interval($rule, $today) {
        global $wpdb;

        // Vérifier si aujourd'hui fait partie des jours autorisés
        $allowed_days = explode(',', $rule->days_of_week);
        if (!in_array($today, $allowed_days)) {
            return false;
        }

        // Vérifier la dernière exécution
        if (!$rule->last_execution) {
            return true; // Première fois
        }

        $last_execution = strtotime($rule->last_execution);
        $days_since = floor((time() - $last_execution) / 86400);

        return ($days_since >= $rule->days_interval);
    }

    /**
     * Calculer la prochaine date de départ
     */
    private static function calculate_next_departure_date($rule) {
        // Par défaut, le départ est prévu dans 7 jours
        // Cela donne le temps aux clients de réserver
        return date('Y-m-d', strtotime('+7 days'));
    }

    /**
     * Obtenir toutes les règles
     */
    public static function get_all_rules() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departure_automation_rules';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY is_active DESC, rule_name ASC");
    }

    /**
     * Activer/Désactiver une règle
     */
    public static function toggle_rule($rule_id, $is_active) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departure_automation_rules';
        
        return $wpdb->update(
            $table,
            array('is_active' => $is_active ? 1 : 0),
            array('id' => intval($rule_id))
        );
    }

    /**
     * Mettre à jour une règle
     */
    public static function update_rule($rule_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departure_automation_rules';
        
        return $wpdb->update(
            $table,
            $data,
            array('id' => intval($rule_id))
        );
    }

    /**
     * Supprimer une règle
     */
    public static function delete_rule($rule_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departure_automation_rules';
        
        return $wpdb->delete(
            $table,
            array('id' => intval($rule_id))
        );
    }

    /**
     * Réinitialiser la date de dernière exécution d'une règle
     */
    public static function reset_rule_execution($rule_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departure_automation_rules';
        
        return $wpdb->update(
            $table,
            array('last_execution' => null),
            array('id' => intval($rule_id))
        );
    }

    /**
     * Ajouter une nouvelle règle
     */
    public static function add_rule($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departure_automation_rules';
        
        $defaults = array(
            'transport_type' => 'plane',
            'frequency' => 'weekly',
            'departure_time' => '08:00:00',
            'default_capacity' => 100,
            'currency' => 'GNF',
            'whatsapp_number' => '+224620178930',
            'is_active' => 1,
            'random_day_in_week' => 0
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert($table, $data);
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Exécuter manuellement l'automatisation (pour tests)
     */
    public static function run_manual_automation() {
        return self::run_automation();
    }
}

// Initialiser l'automatisation au chargement de WordPress
add_action('init', array('Colis224_Departures_Automation', 'init'));
