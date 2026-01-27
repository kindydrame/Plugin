<?php
/**
 * MODULE 26: Automatisation des Workflows
 * Règles automatiques si...alors... pour automatiser les actions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Workflows {

    public function __construct() {
        // Hooks pour déclencher les workflows
        add_action('colis224_parcel_created', array($this, 'trigger_workflows'), 10, 1);
        add_action('colis224_parcel_status_changed', array($this, 'trigger_workflows'), 10, 1);
        add_action('colis224_payment_received', array($this, 'trigger_workflows'), 10, 1);
    }

    /**
     * Créer les tables pour les workflows
     */
    public static function create_workflow_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table des règles de workflow
        $table_rules = $wpdb->prefix . 'colis224_workflow_rules';
        $sql_rules = "CREATE TABLE $table_rules (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            trigger_event enum('parcel_created','status_changed','payment_received','delivery','waiting_3days','waiting_7days','vip_client') DEFAULT 'parcel_created',
            conditions text DEFAULT NULL,
            actions text NOT NULL,
            priority int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            execution_count int(11) DEFAULT 0,
            last_executed datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY trigger_event (trigger_event),
            KEY is_active (is_active),
            KEY priority (priority)
        ) $charset_collate;";
        dbDelta($sql_rules);

        // Table des exécutions de workflow
        $table_executions = $wpdb->prefix . 'colis224_workflow_executions';
        $sql_executions = "CREATE TABLE $table_executions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            rule_id bigint(20) UNSIGNED NOT NULL,
            parcel_id bigint(20) UNSIGNED DEFAULT NULL,
            trigger_data text DEFAULT NULL,
            actions_performed text DEFAULT NULL,
            status enum('success','failed','partial') DEFAULT 'success',
            error_message text DEFAULT NULL,
            executed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY rule_id (rule_id),
            KEY parcel_id (parcel_id),
            KEY status (status),
            KEY executed_at (executed_at)
        ) $charset_collate;";
        dbDelta($sql_executions);

        // Créer des règles par défaut
        self::create_default_rules();
    }

    /**
     * Créer des règles par défaut
     */
    private static function create_default_rules() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_workflow_rules';

        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }

        $rules = array(
            array(
                'name' => 'Notification arrivée colis',
                'description' => 'Envoyer notification quand un colis arrive',
                'trigger_event' => 'status_changed',
                'conditions' => json_encode(array('new_status' => 'Livré')),
                'actions' => json_encode(array(
                    array('type' => 'send_email', 'template' => 'arrival'),
                    array('type' => 'send_sms', 'template' => 'arrival')
                )),
                'priority' => 10,
                'is_active' => 1
            ),
            array(
                'name' => 'Priorité VIP',
                'description' => 'Assigner priorité haute aux clients VIP',
                'trigger_event' => 'parcel_created',
                'conditions' => json_encode(array('client_type' => 'VIP')),
                'actions' => json_encode(array(
                    array('type' => 'set_priority', 'value' => 'high'),
                    array('type' => 'notify_manager')
                )),
                'priority' => 20,
                'is_active' => 1
            ),
            array(
                'name' => 'Alerte paiement reçu',
                'description' => 'Notifier quand paiement complet',
                'trigger_event' => 'payment_received',
                'conditions' => json_encode(array('payment_status' => 'Payé')),
                'actions' => json_encode(array(
                    array('type' => 'send_email', 'template' => 'payment_confirmed'),
                    array('type' => 'update_status', 'value' => 'Prêt pour livraison')
                )),
                'priority' => 15,
                'is_active' => 1
            ),
            array(
                'name' => 'Relance automatique J+3',
                'description' => 'Relancer client si colis non retiré après 3 jours',
                'trigger_event' => 'waiting_3days',
                'conditions' => json_encode(array('status' => 'Livré', 'days_waiting' => 3)),
                'actions' => json_encode(array(
                    array('type' => 'send_reminder', 'recipient' => 'client'),
                    array('type' => 'notify_agent')
                )),
                'priority' => 5,
                'is_active' => 1
            )
        );

        foreach ($rules as $rule) {
            $wpdb->insert($table, $rule);
        }
    }

    /**
     * Déclencher les workflows
     */
    public function trigger_workflows($parcel_id, $event_type = 'parcel_created', $data = array()) {
        global $wpdb;
        $table_rules = $wpdb->prefix . 'colis224_workflow_rules';

        // Récupérer les règles actives pour cet événement
        $rules = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_rules WHERE trigger_event = %s AND is_active = 1 ORDER BY priority DESC",
            $event_type
        ));

        foreach ($rules as $rule) {
            $this->execute_rule($rule, $parcel_id, $data);
        }
    }

    /**
     * Exécuter une règle de workflow
     */
    private function execute_rule($rule, $parcel_id, $trigger_data) {
        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_executions = $wpdb->prefix . 'colis224_workflow_executions';

        // Récupérer le colis
        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_parcels WHERE id = %d",
            $parcel_id
        ));

        if (!$parcel) {
            return false;
        }

        // Vérifier les conditions
        $conditions = json_decode($rule->conditions, true);
        if (!$this->check_conditions($parcel, $conditions, $trigger_data)) {
            return false;
        }

        // Exécuter les actions
        $actions = json_decode($rule->actions, true);
        $actions_performed = array();
        $success = true;
        $error_message = '';

        foreach ($actions as $action) {
            try {
                $result = $this->execute_action($action, $parcel);
                $actions_performed[] = array(
                    'action' => $action,
                    'result' => $result
                );
            } catch (Exception $e) {
                $success = false;
                $error_message .= $e->getMessage() . '; ';
            }
        }

        // Enregistrer l'exécution
        $wpdb->insert($table_executions, array(
            'rule_id' => $rule->id,
            'parcel_id' => $parcel_id,
            'trigger_data' => json_encode($trigger_data),
            'actions_performed' => json_encode($actions_performed),
            'status' => $success ? 'success' : 'failed',
            'error_message' => $error_message
        ));

        // Mettre à jour le compteur d'exécution
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}colis224_workflow_rules
             SET execution_count = execution_count + 1, last_executed = %s
             WHERE id = %d",
            current_time('mysql'),
            $rule->id
        ));

        return $success;
    }

    /**
     * Vérifier les conditions
     */
    private function check_conditions($parcel, $conditions, $trigger_data) {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $key => $value) {
            if ($key === 'new_status' && isset($trigger_data['new_status'])) {
                if ($trigger_data['new_status'] !== $value) {
                    return false;
                }
            } elseif ($key === 'payment_status') {
                if ($parcel->payment_status !== $value) {
                    return false;
                }
            } elseif ($key === 'status') {
                if ($parcel->status !== $value) {
                    return false;
                }
            } elseif ($key === 'client_type') {
                // Vérifier si client VIP
                global $wpdb;
                $parcel_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_parcels WHERE client_id = %d",
                    $parcel->client_id
                ));

                if ($value === 'VIP' && $parcel_count < 10) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Exécuter une action
     */
    private function execute_action($action, $parcel) {
        global $wpdb;

        switch ($action['type']) {
            case 'send_email':
                return $this->send_email_action($parcel, $action);

            case 'send_sms':
                return $this->send_sms_action($parcel, $action);

            case 'set_priority':
                // Mettre à jour la priorité du colis
                return true;

            case 'update_status':
                // Mettre à jour le statut
                $wpdb->update(
                    $wpdb->prefix . 'colis224_parcels',
                    array('status' => $action['value']),
                    array('id' => $parcel->id)
                );
                return true;

            case 'notify_manager':
                // Envoyer notification au manager
                $admin_email = get_option('admin_email');
                wp_mail($admin_email, 'Notification Workflow', "Action requise pour colis {$parcel->tracking_number}");
                return true;

            case 'send_reminder':
                // Utiliser le système de relances
                do_action('colis224_send_reminder', $parcel->id);
                return true;

            case 'notify_agent':
                // Notifier un agent
                return true;

            default:
                return false;
        }
    }

    /**
     * Envoyer un email
     */
    private function send_email_action($parcel, $action) {
        $table_clients = $GLOBALS['wpdb']->prefix . 'colis224_clients';
        $client = $GLOBALS['wpdb']->get_row($GLOBALS['wpdb']->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $parcel->client_id
        ));

        if (!$client || !$client->email) {
            return false;
        }

        $subject = "Mise à jour de votre colis {$parcel->tracking_number}";
        $message = "Bonjour {$client->name},\n\n";
        $message .= "Votre colis {$parcel->tracking_number} a été mis à jour.\n\n";
        $message .= "Cordialement,\nColis224";

        return wp_mail($client->email, $subject, $message);
    }

    /**
     * Envoyer un SMS
     */
    private function send_sms_action($parcel, $action) {
        $table_clients = $GLOBALS['wpdb']->prefix . 'colis224_clients';
        $client = $GLOBALS['wpdb']->get_row($GLOBALS['wpdb']->prepare(
            "SELECT * FROM $table_clients WHERE id = %d",
            $parcel->client_id
        ));

        if (!$client || !$client->phone) {
            return false;
        }

        $message = "Colis {$parcel->tracking_number}: Mise à jour disponible. Consultez votre espace client.";
        do_action('colis224_send_sms', $client->phone, $message, $parcel->id);

        return true;
    }

    /**
     * Obtenir les statistiques des workflows
     */
    public static function get_workflow_stats() {
        global $wpdb;
        $table_rules = $wpdb->prefix . 'colis224_workflow_rules';
        $table_executions = $wpdb->prefix . 'colis224_workflow_executions';

        $stats = array();

        // Règles actives
        $stats['active_rules'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_rules WHERE is_active = 1"
        );

        // Total exécutions
        $stats['total_executions'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_executions"
        );

        // Exécutions aujourd'hui
        $stats['executions_today'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_executions WHERE DATE(executed_at) = CURRENT_DATE"
        );

        // Taux de succès
        $success_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_executions WHERE status = 'success'"
        );
        $stats['success_rate'] = $stats['total_executions'] > 0
            ? round(($success_count / $stats['total_executions']) * 100)
            : 0;

        return $stats;
    }
}

// Initialiser
new Colis224_Workflows();
