<?php
/**
 * MODULE 20: Système de Relances Automatiques et Anti-Vol
 * Prévention des pertes, relances clients, surveillance des colis
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Auto_Reminders {

    public function __construct() {
        // Cron jobs pour les relances automatiques
        add_action('colis224_daily_reminder_check', array($this, 'process_daily_reminders'));
        add_action('colis224_weekly_summary', array($this, 'send_weekly_summary'));
        add_action('colis224_three_day_summary', array($this, 'send_three_day_summary'));

        // Hooks WordPress
        add_action('init', array($this, 'schedule_reminder_tasks'));

        // Hook après livraison pour demander avis
        add_action('colis224_parcel_delivered', array($this, 'request_feedback'), 10, 1);
    }

    /**
     * Créer les tables pour le système de relances et surveillance
     */
    public static function create_reminder_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table des relances envoyées
        $table_reminders = $wpdb->prefix . 'colis224_reminders';
        $sql_reminders = "CREATE TABLE $table_reminders (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parcel_id bigint(20) UNSIGNED NOT NULL,
            reminder_type enum('payment','arrival','pickup','delivery','waiting','urgent') DEFAULT 'pickup',
            recipient_type enum('client','agent','admin','manager') DEFAULT 'client',
            recipient_id bigint(20) UNSIGNED DEFAULT NULL,
            message text NOT NULL,
            sent_via enum('email','sms','both','notification') DEFAULT 'both',
            status enum('pending','sent','failed') DEFAULT 'pending',
            scheduled_date datetime DEFAULT NULL,
            sent_date datetime DEFAULT NULL,
            response_received tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY parcel_id (parcel_id),
            KEY reminder_type (reminder_type),
            KEY status (status),
            KEY scheduled_date (scheduled_date)
        ) $charset_collate;";
        dbDelta($sql_reminders);

        // Table des logs de surveillance
        $table_surveillance = $wpdb->prefix . 'colis224_surveillance_logs';
        $sql_surveillance = "CREATE TABLE $table_surveillance (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parcel_id bigint(20) UNSIGNED NOT NULL,
            event_type enum('arrival','waiting','reminder_sent','payment_pending','high_risk','picked_up','timeout') DEFAULT 'waiting',
            risk_level enum('low','medium','high','critical') DEFAULT 'low',
            days_waiting int(11) DEFAULT 0,
            amount_pending decimal(15,2) DEFAULT 0.00,
            alert_sent tinyint(1) DEFAULT 0,
            alert_recipients text DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY parcel_id (parcel_id),
            KEY event_type (event_type),
            KEY risk_level (risk_level),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_surveillance);

        // Table des paramètres de relance
        $table_reminder_config = $wpdb->prefix . 'colis224_reminder_config';
        $sql_reminder_config = "CREATE TABLE $table_reminder_config (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reminder_name varchar(255) NOT NULL,
            trigger_type enum('days_waiting','payment_pending','arrival','delivery') DEFAULT 'days_waiting',
            trigger_value int(11) NOT NULL,
            recipient_type enum('client','agent','admin','manager') DEFAULT 'client',
            message_template text NOT NULL,
            send_email tinyint(1) DEFAULT 1,
            send_sms tinyint(1) DEFAULT 1,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_reminder_config);

        // Créer les configurations par défaut
        self::create_default_reminder_configs();
    }

    /**
     * Créer les configurations de relance par défaut
     */
    private static function create_default_reminder_configs() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_reminder_config';

        // Vérifier si déjà créées
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }

        $configs = array(
            array(
                'reminder_name' => 'Relance J+3 - Colis en attente',
                'trigger_type' => 'days_waiting',
                'trigger_value' => 3,
                'recipient_type' => 'client',
                'message_template' => 'Bonjour {client_name}, votre colis {tracking_number} est arrivé il y a 3 jours et attend d\'être retiré. Montant: {amount} {currency}. Merci de venir le récupérer rapidement.',
                'send_email' => 1,
                'send_sms' => 1,
                'is_active' => 1
            ),
            array(
                'reminder_name' => 'Relance J+7 - Urgence',
                'trigger_type' => 'days_waiting',
                'trigger_value' => 7,
                'recipient_type' => 'client',
                'message_template' => 'URGENT: Votre colis {tracking_number} attend depuis 7 jours. Montant restant: {remaining_amount} {currency}. Veuillez le retirer sous 48h pour éviter des frais de stockage.',
                'send_email' => 1,
                'send_sms' => 1,
                'is_active' => 1
            ),
            array(
                'reminder_name' => 'Alerte Agent J+3',
                'trigger_type' => 'days_waiting',
                'trigger_value' => 3,
                'recipient_type' => 'agent',
                'message_template' => 'ALERTE: Le colis {tracking_number} du client {client_name} attend depuis 3 jours. Montant: {amount} {currency}. Veuillez contacter le client.',
                'send_email' => 1,
                'send_sms' => 0,
                'is_active' => 1
            ),
            array(
                'reminder_name' => 'Alerte Admin J+7',
                'trigger_type' => 'days_waiting',
                'trigger_value' => 7,
                'recipient_type' => 'admin',
                'message_template' => 'SURVEILLANCE: Colis {tracking_number} non retiré depuis 7 jours. Client: {client_name}. Montant: {amount} {currency}. Risque de perte élevé.',
                'send_email' => 1,
                'send_sms' => 1,
                'is_active' => 1
            ),
            array(
                'reminder_name' => 'Rappel Paiement Partiel',
                'trigger_type' => 'payment_pending',
                'trigger_value' => 1,
                'recipient_type' => 'client',
                'message_template' => 'Bonjour {client_name}, votre colis {tracking_number} a un solde impayé de {remaining_amount} {currency}. Merci de régulariser pour récupérer votre colis.',
                'send_email' => 1,
                'send_sms' => 1,
                'is_active' => 1
            ),
            array(
                'reminder_name' => 'Notification Arrivée Colis',
                'trigger_type' => 'arrival',
                'trigger_value' => 0,
                'recipient_type' => 'client',
                'message_template' => 'Bonne nouvelle! Votre colis {tracking_number} est arrivé à notre agence. Montant à payer: {amount} {currency}. Vous pouvez venir le retirer.',
                'send_email' => 1,
                'send_sms' => 1,
                'is_active' => 1
            )
        );

        foreach ($configs as $config) {
            $wpdb->insert($table, $config);
        }
    }

    /**
     * Planifier les tâches automatiques
     */
    public function schedule_reminder_tasks() {
        // Vérification quotidienne
        if (!wp_next_scheduled('colis224_daily_reminder_check')) {
            wp_schedule_event(time(), 'daily', 'colis224_daily_reminder_check');
        }

        // Résumé tous les 3 jours
        if (!wp_next_scheduled('colis224_three_day_summary')) {
            wp_schedule_event(time(), 'twicedaily', 'colis224_three_day_summary');
        }

        // Résumé hebdomadaire
        if (!wp_next_scheduled('colis224_weekly_summary')) {
            wp_schedule_event(strtotime('next monday 8:00'), 'weekly', 'colis224_weekly_summary');
        }
    }

    /**
     * Traiter les relances quotidiennes
     */
    public function process_daily_reminders() {
        global $wpdb;

        // Récupérer les colis non retirés
        $parcels = $this->get_parcels_needing_reminder();

        foreach ($parcels as $parcel) {
            $this->process_parcel_reminders($parcel);
        }

        // Mettre à jour les logs de surveillance
        $this->update_surveillance_logs();
    }

    /**
     * Récupérer les colis nécessitant une relance
     */
    private function get_parcels_needing_reminder() {
        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Colis livrés mais non retirés, ou en attente de paiement
        $query = "SELECT p.*,
                  DATEDIFF(CURRENT_DATE,
                      CASE
                          WHEN p.status = 'Livré' THEN p.delivery_date
                          WHEN p.status = 'En attente' THEN p.reception_date
                          ELSE p.created_at
                      END
                  ) as days_waiting
                  FROM $table_parcels p
                  WHERE p.status IN ('En attente', 'Livré')
                  AND (p.payment_status != 'Payé' OR p.status = 'En attente')
                  HAVING days_waiting > 0
                  ORDER BY days_waiting DESC";

        return $wpdb->get_results($query);
    }

    /**
     * Traiter les relances pour un colis
     */
    private function process_parcel_reminders($parcel) {
        global $wpdb;
        $table_config = $wpdb->prefix . 'colis224_reminder_config';
        $table_reminders = $wpdb->prefix . 'colis224_reminders';

        $days_waiting = intval($parcel->days_waiting);

        // Récupérer les configurations actives
        $configs = $wpdb->get_results(
            "SELECT * FROM $table_config WHERE is_active = 1"
        );

        foreach ($configs as $config) {
            $should_send = false;

            // Vérifier si cette relance doit être envoyée
            if ($config->trigger_type === 'days_waiting' && $days_waiting >= $config->trigger_value) {
                // Vérifier si pas déjà envoyée pour ce jour
                $already_sent = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_reminders
                     WHERE parcel_id = %d
                     AND reminder_type = 'waiting'
                     AND recipient_type = %s
                     AND DATE(created_at) = CURRENT_DATE",
                    $parcel->id,
                    $config->recipient_type
                ));

                if (!$already_sent) {
                    $should_send = true;
                }
            } elseif ($config->trigger_type === 'payment_pending' && $parcel->payment_status !== 'Payé') {
                $should_send = true;
            } elseif ($config->trigger_type === 'arrival' && $parcel->status === 'Livré') {
                // Vérifier si notification d'arrivée pas encore envoyée
                $already_sent = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_reminders
                     WHERE parcel_id = %d AND reminder_type = 'arrival'",
                    $parcel->id
                ));

                if (!$already_sent) {
                    $should_send = true;
                }
            }

            if ($should_send) {
                $this->send_reminder($parcel, $config);
            }
        }

        // Calculer et enregistrer le niveau de risque
        $this->calculate_risk_level($parcel);
    }

    /**
     * Envoyer une relance
     */
    private function send_reminder($parcel, $config) {
        global $wpdb;
        $table_reminders = $wpdb->prefix . 'colis224_reminders';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        // Récupérer les infos client
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $parcel->client_id
        ));

        // Préparer le message avec les variables
        $message = $config->message_template;
        $replacements = array(
            '{client_name}' => $client ? $client->name : 'Client',
            '{tracking_number}' => $parcel->tracking_number,
            '{amount}' => number_format($parcel->total_amount, 0, ',', ' '),
            '{remaining_amount}' => number_format($parcel->remaining_amount, 0, ',', ' '),
            '{currency}' => $parcel->currency,
            '{days_waiting}' => $parcel->days_waiting
        );

        $message = str_replace(array_keys($replacements), array_values($replacements), $message);

        // Déterminer le destinataire
        $recipient_email = '';
        $recipient_phone = '';
        $recipient_id = null;

        if ($config->recipient_type === 'client' && $client) {
            $recipient_email = $client->email;
            $recipient_phone = $client->phone;
            $recipient_id = $client->id;
        } elseif ($config->recipient_type === 'admin') {
            $recipient_email = get_option('admin_email');
            $recipient_phone = get_option('colis224_company_phone');
        } elseif ($config->recipient_type === 'agent' || $config->recipient_type === 'manager') {
            // Récupérer un agent actif
            $table_team = $wpdb->prefix . 'colis224_team_members';
            $agent = $wpdb->get_row(
                "SELECT * FROM $table_team WHERE is_active = 1 ORDER BY id ASC LIMIT 1"
            );

            if ($agent) {
                $recipient_email = $agent->email;
                $recipient_phone = $agent->phone;
                $recipient_id = $agent->id;
            }
        }

        // Enregistrer la relance
        $wpdb->insert($table_reminders, array(
            'parcel_id' => $parcel->id,
            'reminder_type' => $config->trigger_type === 'arrival' ? 'arrival' : 'waiting',
            'recipient_type' => $config->recipient_type,
            'recipient_id' => $recipient_id,
            'message' => $message,
            'sent_via' => $config->send_email && $config->send_sms ? 'both' : ($config->send_email ? 'email' : 'sms'),
            'status' => 'pending',
            'scheduled_date' => current_time('mysql')
        ));

        $reminder_id = $wpdb->insert_id;

        // Envoyer effectivement
        $sent = false;

        if ($config->send_email && !empty($recipient_email)) {
            $subject = "Colis224 - " . $config->reminder_name;
            $sent = wp_mail($recipient_email, $subject, $message);
        }

        if ($config->send_sms && !empty($recipient_phone)) {
            do_action('colis224_send_sms', $recipient_phone, $message, $parcel->id);
            $sent = true;
        }

        // Mettre à jour le statut
        $wpdb->update($table_reminders,
            array(
                'status' => $sent ? 'sent' : 'failed',
                'sent_date' => current_time('mysql')
            ),
            array('id' => $reminder_id)
        );
    }

    /**
     * Calculer le niveau de risque d'un colis
     */
    private function calculate_risk_level($parcel) {
        global $wpdb;
        $table_surveillance = $wpdb->prefix . 'colis224_surveillance_logs';

        $days_waiting = intval($parcel->days_waiting);
        $risk_level = 'low';
        $event_type = 'waiting';

        // Calcul du risque
        if ($days_waiting >= 14) {
            $risk_level = 'critical';
            $event_type = 'timeout';
        } elseif ($days_waiting >= 10) {
            $risk_level = 'high';
            $event_type = 'high_risk';
        } elseif ($days_waiting >= 7) {
            $risk_level = 'high';
        } elseif ($days_waiting >= 3) {
            $risk_level = 'medium';
        }

        // Si paiement non effectué, augmenter le risque
        if ($parcel->payment_status !== 'Payé') {
            $event_type = 'payment_pending';
            if ($risk_level === 'low') {
                $risk_level = 'medium';
            }
        }

        // Enregistrer dans les logs
        $wpdb->insert($table_surveillance, array(
            'parcel_id' => $parcel->id,
            'event_type' => $event_type,
            'risk_level' => $risk_level,
            'days_waiting' => $days_waiting,
            'amount_pending' => $parcel->remaining_amount,
            'alert_sent' => 0,
            'notes' => "Surveillance automatique - Risque: $risk_level"
        ));

        // Si risque critique, alerter immédiatement l'admin
        if ($risk_level === 'critical') {
            $this->send_critical_alert($parcel);
        }
    }

    /**
     * Envoyer une alerte critique
     */
    private function send_critical_alert($parcel) {
        $admin_email = get_option('admin_email');
        $subject = "⚠️ ALERTE CRITIQUE - Colis " . $parcel->tracking_number;
        $message = "ATTENTION: Le colis {$parcel->tracking_number} attend depuis {$parcel->days_waiting} jours.\n\n";
        $message .= "Montant: " . number_format($parcel->total_amount, 0, ',', ' ') . " {$parcel->currency}\n";
        $message .= "Statut paiement: {$parcel->payment_status}\n";
        $message .= "Risque de perte ou vol élevé.\n\n";
        $message .= "Action requise immédiatement.";

        wp_mail($admin_email, $subject, $message);

        // Envoyer SMS aussi
        $admin_phone = get_option('colis224_company_phone');
        if ($admin_phone) {
            do_action('colis224_send_sms', $admin_phone, $subject, $parcel->id);
        }
    }

    /**
     * Mettre à jour les logs de surveillance
     */
    private function update_surveillance_logs() {
        global $wpdb;
        $table_surveillance = $wpdb->prefix . 'colis224_surveillance_logs';

        // Nettoyer les anciens logs (> 90 jours)
        $wpdb->query(
            "DELETE FROM $table_surveillance WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
    }

    /**
     * Envoyer le résumé des 3 jours
     */
    public function send_three_day_summary() {
        $this->send_summary_report(3);
    }

    /**
     * Envoyer le résumé hebdomadaire
     */
    public function send_weekly_summary() {
        $this->send_summary_report(7);
    }

    /**
     * Envoyer un rapport de résumé
     */
    private function send_summary_report($days) {
        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Récupérer les colis non retirés
        $parcels = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*,
             DATEDIFF(CURRENT_DATE,
                 CASE
                     WHEN p.status = 'Livré' THEN p.delivery_date
                     WHEN p.status = 'En attente' THEN p.reception_date
                     ELSE p.created_at
                 END
             ) as days_waiting,
             c.name as client_name,
             c.phone as client_phone
             FROM $table_parcels p
             LEFT JOIN {$wpdb->prefix}colis224_clients c ON p.client_id = c.id
             WHERE p.status IN ('En attente', 'Livré')
             AND DATEDIFF(CURRENT_DATE,
                 CASE
                     WHEN p.status = 'Livré' THEN p.delivery_date
                     WHEN p.status = 'En attente' THEN p.reception_date
                     ELSE p.created_at
                 END
             ) >= %d
             ORDER BY days_waiting DESC",
            $days
        ));

        if (empty($parcels)) {
            return; // Pas de colis à signaler
        }

        // Préparer le rapport
        $report = "📊 RAPPORT DE SURVEILLANCE - Colis Non Retirés (≥ $days jours)\n\n";
        $report .= "Date: " . date('d/m/Y H:i') . "\n";
        $report .= "Nombre total: " . count($parcels) . " colis\n\n";
        $report .= str_repeat("=", 60) . "\n\n";

        $total_amount = 0;
        $total_unpaid = 0;

        foreach ($parcels as $parcel) {
            $report .= "🔸 Colis: {$parcel->tracking_number}\n";
            $report .= "   Client: {$parcel->client_name}\n";
            $report .= "   Téléphone: {$parcel->client_phone}\n";
            $report .= "   Jours d'attente: {$parcel->days_waiting} jours\n";
            $report .= "   Montant total: " . number_format($parcel->total_amount, 0, ',', ' ') . " {$parcel->currency}\n";
            $report .= "   Impayé: " . number_format($parcel->remaining_amount, 0, ',', ' ') . " {$parcel->currency}\n";
            $report .= "   Statut: {$parcel->status} / Paiement: {$parcel->payment_status}\n";

            if ($parcel->days_waiting >= 14) {
                $report .= "   ⚠️ RISQUE CRITIQUE\n";
            } elseif ($parcel->days_waiting >= 7) {
                $report .= "   ⚡ RISQUE ÉLEVÉ\n";
            }

            $report .= "\n";

            $total_amount += $parcel->total_amount;
            $total_unpaid += $parcel->remaining_amount;
        }

        $report .= str_repeat("=", 60) . "\n";
        $report .= "💰 TOTAL EN STOCK: " . number_format($total_amount, 0, ',', ' ') . " GNF\n";
        $report .= "💳 TOTAL IMPAYÉ: " . number_format($total_unpaid, 0, ',', ' ') . " GNF\n\n";
        $report .= "⚠️ ACTION REQUISE: Relancer tous les clients listés ci-dessus.\n";

        // Envoyer à l'admin et aux agents
        $admin_email = get_option('admin_email');
        $subject = "📊 Rapport Surveillance Colis - $days jours";

        wp_mail($admin_email, $subject, $report);

        // Envoyer aussi aux agents
        $table_team = $wpdb->prefix . 'colis224_team_members';
        $agents = $wpdb->get_results(
            "SELECT * FROM $table_team WHERE is_active = 1 AND email IS NOT NULL"
        );

        foreach ($agents as $agent) {
            wp_mail($agent->email, $subject, $report);
        }

        // Envoyer SMS à l'admin
        $admin_phone = get_option('colis224_company_phone');
        if ($admin_phone) {
            $sms = "📊 RAPPORT: " . count($parcels) . " colis non retirés (≥$days jours). Total: " . number_format($total_amount, 0, ',', ' ') . " GNF. Impayé: " . number_format($total_unpaid, 0, ',', ' ') . " GNF. Consultez votre email.";
            do_action('colis224_send_sms', $admin_phone, $sms, 0);
        }
    }

    /**
     * Demander un avis après retrait
     */
    public function request_feedback($parcel_id) {
        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_parcels WHERE id = %d",
            $parcel_id
        ));

        if (!$parcel || !$parcel->client_id) {
            return;
        }

        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $parcel->client_id
        ));

        if (!$client) {
            return;
        }

        // Créer un lien pour laisser un avis
        $feedback_url = add_query_arg(
            array(
                'colis224_feedback' => 1,
                'parcel' => $parcel->id
            ),
            home_url()
        );

        $message = "Merci d'avoir récupéré votre colis {$parcel->tracking_number}! Nous serions ravis de connaître votre avis sur notre service. Notez-nous: $feedback_url";

        // Envoyer par email
        if ($client->email) {
            wp_mail($client->email, 'Votre avis compte - Colis224', $message);
        }

        // Envoyer par SMS
        if ($client->phone) {
            do_action('colis224_send_sms', $client->phone, $message, $parcel->id);
        }
    }

    /**
     * Obtenir les statistiques de surveillance
     */
    public static function get_surveillance_stats() {
        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $stats = array();

        // Colis non retirés depuis plus de 3 jours
        $stats['waiting_3_days'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_parcels
             WHERE status IN ('En attente', 'Livré')
             AND DATEDIFF(CURRENT_DATE, reception_date) >= 3"
        );

        // Colis non retirés depuis plus de 7 jours
        $stats['waiting_7_days'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_parcels
             WHERE status IN ('En attente', 'Livré')
             AND DATEDIFF(CURRENT_DATE, reception_date) >= 7"
        );

        // Colis critiques (14+ jours)
        $stats['critical'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_parcels
             WHERE status IN ('En attente', 'Livré')
             AND DATEDIFF(CURRENT_DATE, reception_date) >= 14"
        );

        // Total impayé
        $stats['total_unpaid'] = $wpdb->get_var(
            "SELECT SUM(remaining_amount) FROM $table_parcels
             WHERE payment_status != 'Payé'
             AND status IN ('En attente', 'Livré')"
        );

        // Valeur totale des colis en stock
        $stats['total_value'] = $wpdb->get_var(
            "SELECT SUM(total_amount) FROM $table_parcels
             WHERE status IN ('En attente', 'Livré')"
        );

        return $stats;
    }
}

// Initialiser
new Colis224_Auto_Reminders();
