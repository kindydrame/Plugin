<?php
/**
 * MODULE 24: Marketing Automation
 * Campagnes SMS/Email, segments clients, promotions, newsletters
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Marketing {

    public function __construct() {
        // Hooks pour campagnes automatiques
        add_action('colis224_run_scheduled_campaigns', array($this, 'process_scheduled_campaigns'));
        add_action('init', array($this, 'schedule_campaign_processor'));
    }

    /**
     * Créer les tables pour le marketing
     */
    public static function create_marketing_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table des segments clients
        $table_segments = $wpdb->prefix . 'colis224_customer_segments';
        $sql_segments = "CREATE TABLE $table_segments (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            conditions text DEFAULT NULL,
            customer_count int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_segments);

        // Table des campagnes
        $table_campaigns = $wpdb->prefix . 'colis224_campaigns';
        $sql_campaigns = "CREATE TABLE $table_campaigns (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            campaign_type enum('email','sms','both') DEFAULT 'both',
            segment_id bigint(20) UNSIGNED DEFAULT NULL,
            subject varchar(255) DEFAULT NULL,
            message text NOT NULL,
            status enum('draft','scheduled','sending','completed','cancelled') DEFAULT 'draft',
            scheduled_date datetime DEFAULT NULL,
            sent_count int(11) DEFAULT 0,
            delivered_count int(11) DEFAULT 0,
            failed_count int(11) DEFAULT 0,
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            sent_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY segment_id (segment_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_campaigns);

        // Table des envois de campagne
        $table_campaign_sends = $wpdb->prefix . 'colis224_campaign_sends';
        $sql_campaign_sends = "CREATE TABLE $table_campaign_sends (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) UNSIGNED NOT NULL,
            client_id bigint(20) UNSIGNED NOT NULL,
            send_type enum('email','sms') NOT NULL,
            recipient varchar(255) NOT NULL,
            status enum('pending','sent','delivered','failed','bounced') DEFAULT 'pending',
            sent_at datetime DEFAULT NULL,
            delivered_at datetime DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY campaign_id (campaign_id),
            KEY client_id (client_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_campaign_sends);

        // Table des codes promo
        $table_promos = $wpdb->prefix . 'colis224_promo_codes';
        $sql_promos = "CREATE TABLE $table_promos (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            description text DEFAULT NULL,
            discount_type enum('percentage','fixed') DEFAULT 'percentage',
            discount_value decimal(10,2) NOT NULL,
            min_amount decimal(15,2) DEFAULT 0.00,
            max_uses int(11) DEFAULT NULL,
            used_count int(11) DEFAULT 0,
            valid_from date DEFAULT NULL,
            valid_until date DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql_promos);

        // Créer des segments par défaut
        self::create_default_segments();
    }

    /**
     * Créer des segments clients par défaut
     */
    private static function create_default_segments() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_customer_segments';

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }

        $segments = array(
            array(
                'name' => 'Clients VIP',
                'description' => 'Clients avec plus de 10 colis',
                'conditions' => json_encode(array('min_parcels' => 10))
            ),
            array(
                'name' => 'Nouveaux Clients',
                'description' => 'Clients inscrits il y a moins de 30 jours',
                'conditions' => json_encode(array('registered_days' => 30))
            ),
            array(
                'name' => 'Clients Inactifs',
                'description' => 'Aucun colis depuis plus de 90 jours',
                'conditions' => json_encode(array('inactive_days' => 90))
            ),
            array(
                'name' => 'Programme Fidélité',
                'description' => 'Membres du programme de fidélité',
                'conditions' => json_encode(array('loyalty_member' => true))
            )
        );

        foreach ($segments as $segment) {
            $wpdb->insert($table, $segment);
        }
    }

    /**
     * Planifier le processeur de campagnes
     */
    public function schedule_campaign_processor() {
        if (!wp_next_scheduled('colis224_run_scheduled_campaigns')) {
            wp_schedule_event(time(), 'hourly', 'colis224_run_scheduled_campaigns');
        }
    }

    /**
     * Traiter les campagnes planifiées
     */
    public function process_scheduled_campaigns() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_campaigns';

        // Récupérer les campagnes à envoyer
        $campaigns = $wpdb->get_results(
            "SELECT * FROM $table
             WHERE status = 'scheduled'
             AND scheduled_date <= NOW()
             LIMIT 5"
        );

        foreach ($campaigns as $campaign) {
            $this->send_campaign($campaign->id);
        }
    }

    /**
     * Envoyer une campagne
     */
    public function send_campaign($campaign_id) {
        global $wpdb;
        $table_campaigns = $wpdb->prefix . 'colis224_campaigns';
        $table_sends = $wpdb->prefix . 'colis224_campaign_sends';

        $campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_campaigns WHERE id = %d",
            $campaign_id
        ));

        if (!$campaign) {
            return false;
        }

        // Mettre à jour le statut
        $wpdb->update($table_campaigns,
            array('status' => 'sending', 'sent_at' => current_time('mysql')),
            array('id' => $campaign_id)
        );

        // Récupérer les clients du segment
        $clients = $this->get_segment_clients($campaign->segment_id);

        $sent_count = 0;
        $failed_count = 0;

        foreach ($clients as $client) {
            // Email
            if (in_array($campaign->campaign_type, array('email', 'both')) && $client->email) {
                $result = wp_mail($client->email, $campaign->subject, $campaign->message);

                $wpdb->insert($table_sends, array(
                    'campaign_id' => $campaign_id,
                    'client_id' => $client->id,
                    'send_type' => 'email',
                    'recipient' => $client->email,
                    'status' => $result ? 'sent' : 'failed',
                    'sent_at' => current_time('mysql')
                ));

                if ($result) {
                    $sent_count++;
                } else {
                    $failed_count++;
                }
            }

            // SMS
            if (in_array($campaign->campaign_type, array('sms', 'both')) && $client->phone) {
                do_action('colis224_send_sms', $client->phone, $campaign->message, 0);

                $wpdb->insert($table_sends, array(
                    'campaign_id' => $campaign_id,
                    'client_id' => $client->id,
                    'send_type' => 'sms',
                    'recipient' => $client->phone,
                    'status' => 'sent',
                    'sent_at' => current_time('mysql')
                ));

                $sent_count++;
            }
        }

        // Mettre à jour les statistiques
        $wpdb->update($table_campaigns,
            array(
                'status' => 'completed',
                'sent_count' => $sent_count,
                'failed_count' => $failed_count,
                'completed_at' => current_time('mysql')
            ),
            array('id' => $campaign_id)
        );

        return true;
    }

    /**
     * Récupérer les clients d'un segment
     */
    private function get_segment_clients($segment_id) {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $table_segments = $wpdb->prefix . 'colis224_customer_segments';

        $segment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_segments WHERE id = %d",
            $segment_id
        ));

        if (!$segment) {
            return array();
        }

        $conditions = json_decode($segment->conditions, true);

        // Construction de la requête selon les conditions
        $query = "SELECT * FROM $table_clients WHERE 1=1";

        if (isset($conditions['min_parcels'])) {
            $min = intval($conditions['min_parcels']);
            $query .= " AND (SELECT COUNT(*) FROM {$wpdb->prefix}colis224_parcels WHERE client_id = {$table_clients}.id) >= $min";
        }

        if (isset($conditions['registered_days'])) {
            $days = intval($conditions['registered_days']);
            $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)";
        }

        if (isset($conditions['inactive_days'])) {
            $days = intval($conditions['inactive_days']);
            $query .= " AND (SELECT MAX(created_at) FROM {$wpdb->prefix}colis224_parcels WHERE client_id = {$table_clients}.id) <= DATE_SUB(NOW(), INTERVAL $days DAY)";
        }

        return $wpdb->get_results($query);
    }

    /**
     * Obtenir les statistiques marketing
     */
    public static function get_marketing_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_campaigns';

        $stats = array();

        // Total campagnes
        $stats['total_campaigns'] = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        // Campagnes actives
        $stats['active_campaigns'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE status IN ('scheduled', 'sending')"
        );

        // Envois ce mois
        $stats['sends_this_month'] = $wpdb->get_var(
            "SELECT SUM(sent_count) FROM $table WHERE MONTH(sent_at) = MONTH(CURRENT_DATE) AND YEAR(sent_at) = YEAR(CURRENT_DATE)"
        );

        // Taux de succès
        $total_sent = $wpdb->get_var("SELECT SUM(sent_count) FROM $table");
        $total_failed = $wpdb->get_var("SELECT SUM(failed_count) FROM $table");
        $stats['success_rate'] = $total_sent > 0 ? round((($total_sent - $total_failed) / $total_sent) * 100) : 0;

        return $stats;
    }
}

// Initialiser
new Colis224_Marketing();
