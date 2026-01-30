<?php
/**
 * MODULE 18: Système de Tickets Support/SAV
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Support_Tickets {

    public function __construct() {
        // Hook pour créer automatiquement un ticket depuis le portail client
        add_action('wp_ajax_nopriv_colis224_create_ticket', array($this, 'ajax_create_ticket'));
        add_action('wp_ajax_colis224_create_ticket', array($this, 'ajax_create_ticket'));

        // Hook pour répondre à un ticket
        add_action('wp_ajax_colis224_reply_ticket', array($this, 'ajax_reply_ticket'));
    }

    /**
     * Créer les tables de tickets
     */
    public static function create_ticket_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table des tickets
        $table_tickets = $wpdb->prefix . 'colis224_support_tickets';
        $sql_tickets = "CREATE TABLE $table_tickets (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_number varchar(50) NOT NULL,
            client_id bigint(20) UNSIGNED DEFAULT NULL,
            parcel_id bigint(20) UNSIGNED DEFAULT NULL,
            subject varchar(255) NOT NULL,
            category enum('colis','paiement','reclamation','information','technique','autre') DEFAULT 'autre',
            priority enum('basse','normale','haute','urgente') DEFAULT 'normale',
            status enum('ouvert','en_cours','resolu','ferme') DEFAULT 'ouvert',
            assigned_to bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            resolved_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY ticket_number (ticket_number),
            KEY client_id (client_id),
            KEY parcel_id (parcel_id),
            KEY status (status),
            KEY priority (priority)
        ) $charset_collate;";
        dbDelta($sql_tickets);

        // Table des messages de tickets
        $table_messages = $wpdb->prefix . 'colis224_support_messages';
        $sql_messages = "CREATE TABLE $table_messages (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) UNSIGNED NOT NULL,
            user_type enum('client','admin','system') DEFAULT 'client',
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            message text NOT NULL,
            attachments text DEFAULT NULL,
            is_internal tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_messages);
    }

    /**
     * Créer un nouveau ticket
     */
    public static function create_ticket($data) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'colis224_support_tickets';
        $table_messages = $wpdb->prefix . 'colis224_support_messages';

        // Générer numéro de ticket unique
        $ticket_number = 'TKT-' . strtoupper(wp_generate_password(8, false));

        // Créer le ticket
        $result = $wpdb->insert($table_tickets, array(
            'ticket_number' => $ticket_number,
            'client_id' => !empty($data['client_id']) ? intval($data['client_id']) : null,
            'parcel_id' => !empty($data['parcel_id']) ? intval($data['parcel_id']) : null,
            'subject' => sanitize_text_field($data['subject']),
            'category' => sanitize_text_field($data['category']),
            'priority' => sanitize_text_field($data['priority'] ?? 'normale'),
            'status' => 'ouvert',
        ));

        if (!$result) {
            return array('success' => false, 'message' => 'Erreur lors de la création du ticket');
        }

        $ticket_id = $wpdb->insert_id;

        // Ajouter le premier message
        $wpdb->insert($table_messages, array(
            'ticket_id' => $ticket_id,
            'user_type' => !empty($data['user_id']) ? 'admin' : 'client',
            'user_id' => !empty($data['user_id']) ? intval($data['user_id']) : null,
            'message' => sanitize_textarea_field($data['message']),
            'is_internal' => 0,
        ));

        // Envoyer notification par email
        self::send_ticket_notification($ticket_id, 'created');

        return array(
            'success' => true,
            'message' => 'Ticket créé avec succès',
            'ticket_id' => $ticket_id,
            'ticket_number' => $ticket_number
        );
    }

    /**
     * Ajouter une réponse à un ticket
     */
    public static function add_reply($ticket_id, $message, $user_type = 'admin', $user_id = null, $is_internal = false) {
        global $wpdb;
        $table_messages = $wpdb->prefix . 'colis224_support_messages';
        $table_tickets = $wpdb->prefix . 'colis224_support_tickets';

        $result = $wpdb->insert($table_messages, array(
            'ticket_id' => $ticket_id,
            'user_type' => $user_type,
            'user_id' => $user_id,
            'message' => sanitize_textarea_field($message),
            'is_internal' => $is_internal ? 1 : 0,
        ));

        if ($result) {
            // Mettre à jour la date du ticket
            $wpdb->update($table_tickets,
                array('updated_at' => current_time('mysql')),
                array('id' => $ticket_id)
            );

            // Envoyer notification
            if (!$is_internal) {
                self::send_ticket_notification($ticket_id, 'reply', $user_type);
            }

            return array('success' => true, 'message' => 'Réponse ajoutée');
        }

        return array('success' => false, 'message' => 'Erreur lors de l\'ajout de la réponse');
    }

    /**
     * Mettre à jour le statut d'un ticket
     */
    public static function update_status($ticket_id, $status) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'colis224_support_tickets';

        $update_data = array('status' => $status);

        if ($status === 'resolu') {
            $update_data['resolved_at'] = current_time('mysql');
        }

        $result = $wpdb->update($table_tickets, $update_data, array('id' => $ticket_id));

        if ($result !== false) {
            self::send_ticket_notification($ticket_id, 'status_changed');
            return array('success' => true, 'message' => 'Statut mis à jour');
        }

        return array('success' => false, 'message' => 'Erreur lors de la mise à jour');
    }

    /**
     * Assigner un ticket à un membre de l'équipe
     */
    public static function assign_ticket($ticket_id, $user_id) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'colis224_support_tickets';

        $result = $wpdb->update($table_tickets,
            array('assigned_to' => $user_id),
            array('id' => $ticket_id)
        );

        return $result !== false;
    }

    /**
     * Obtenir un ticket par ID
     */
    public static function get_ticket($ticket_id) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'colis224_support_tickets';
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, c.name as client_name, c.phone as client_phone, c.email as client_email,
                    p.tracking_number
             FROM $table_tickets t
             LEFT JOIN $table_clients c ON t.client_id = c.id
             LEFT JOIN $table_parcels p ON t.parcel_id = p.id
             WHERE t.id = %d",
            $ticket_id
        ));
    }

    /**
     * Obtenir tous les messages d'un ticket
     */
    public static function get_ticket_messages($ticket_id, $include_internal = false) {
        global $wpdb;
        $table_messages = $wpdb->prefix . 'colis224_support_messages';

        $where = $include_internal ? '' : 'AND is_internal = 0';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_messages
             WHERE ticket_id = %d $where
             ORDER BY created_at ASC",
            $ticket_id
        ));
    }

    /**
     * Obtenir tous les tickets avec filtres
     */
    public static function get_all_tickets($filters = array()) {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'colis224_support_tickets';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $where = array('1=1');
        $params = array();

        if (!empty($filters['status'])) {
            $where[] = 't.status = %s';
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $where[] = 't.priority = %s';
            $params[] = $filters['priority'];
        }

        if (!empty($filters['category'])) {
            $where[] = 't.category = %s';
            $params[] = $filters['category'];
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = 't.assigned_to = %d';
            $params[] = intval($filters['assigned_to']);
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT t.*, c.name as client_name, c.phone as client_phone
                  FROM $table_tickets t
                  LEFT JOIN $table_clients c ON t.client_id = c.id
                  WHERE $where_clause
                  ORDER BY t.created_at DESC";

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        return $wpdb->get_results($query);
    }

    /**
     * Obtenir les statistiques des tickets
     */
    public static function get_stats() {
        global $wpdb;
        $table_tickets = $wpdb->prefix . 'colis224_support_tickets';

        return array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_tickets"),
            'ouvert' => $wpdb->get_var("SELECT COUNT(*) FROM $table_tickets WHERE status = 'ouvert'"),
            'en_cours' => $wpdb->get_var("SELECT COUNT(*) FROM $table_tickets WHERE status = 'en_cours'"),
            'resolu' => $wpdb->get_var("SELECT COUNT(*) FROM $table_tickets WHERE status = 'resolu'"),
            'ferme' => $wpdb->get_var("SELECT COUNT(*) FROM $table_tickets WHERE status = 'ferme'"),
            'urgente' => $wpdb->get_var("SELECT COUNT(*) FROM $table_tickets WHERE priority = 'urgente' AND status NOT IN ('resolu', 'ferme')"),
        );
    }

    /**
     * Envoyer notification pour un ticket
     */
    private static function send_ticket_notification($ticket_id, $type, $sender_type = null) {
        $ticket = self::get_ticket($ticket_id);
        if (!$ticket) return;

        $subject = '';
        $message = '';

        switch ($type) {
            case 'created':
                $subject = "Nouveau ticket créé - {$ticket->ticket_number}";
                $message = "Un nouveau ticket de support a été créé.\n\n";
                $message .= "N° Ticket: {$ticket->ticket_number}\n";
                $message .= "Sujet: {$ticket->subject}\n";
                $message .= "Catégorie: {$ticket->category}\n";
                $message .= "Priorité: {$ticket->priority}\n\n";
                $message .= "Notre équipe va traiter votre demande dans les plus brefs délais.";
                break;

            case 'reply':
                if ($sender_type === 'admin') {
                    $subject = "Nouvelle réponse à votre ticket - {$ticket->ticket_number}";
                    $message = "Vous avez reçu une nouvelle réponse pour votre ticket.\n\n";
                } else {
                    $subject = "Nouvelle réponse client - {$ticket->ticket_number}";
                    $message = "Le client a ajouté une réponse au ticket {$ticket->ticket_number}.\n\n";
                }
                $message .= "Consultez votre espace pour voir la réponse complète.";
                break;

            case 'status_changed':
                $subject = "Mise à jour de votre ticket - {$ticket->ticket_number}";
                $message = "Le statut de votre ticket a été mis à jour.\n\n";
                $message .= "Nouveau statut: {$ticket->status}\n\n";
                break;
        }

        // Envoyer email au client
        if ($ticket->client_email && $type !== 'reply' || ($type === 'reply' && $sender_type === 'admin')) {
            wp_mail($ticket->client_email, $subject, $message);
        }

        // Envoyer SMS si configuré
        if ($ticket->client_phone) {
            do_action('colis224_send_sms', $ticket->client_phone, $message);
        }

        // Envoyer notification admin
        $admin_email = get_option('admin_email');
        if ($type === 'created' || ($type === 'reply' && $sender_type === 'client')) {
            wp_mail($admin_email, $subject, $message);
        }
    }

    /**
     * AJAX: Créer un ticket depuis le frontend
     */
    public function ajax_create_ticket() {
        check_ajax_referer('colis224_frontend_nonce', 'nonce');

        $result = self::create_ticket(array(
            'client_id' => isset($_SESSION['colis224_client_id']) ? $_SESSION['colis224_client_id'] : null,
            'parcel_id' => !empty($_POST['parcel_id']) ? intval($_POST['parcel_id']) : null,
            'subject' => $_POST['subject'],
            'category' => $_POST['category'],
            'priority' => $_POST['priority'] ?? 'normale',
            'message' => $_POST['message'],
        ));

        wp_send_json($result);
    }

    /**
     * AJAX: Répondre à un ticket
     */
    public function ajax_reply_ticket() {
        check_ajax_referer('colis224_nonce', 'nonce');

        $ticket_id = intval($_POST['ticket_id']);
        $message = $_POST['message'];
        $is_internal = !empty($_POST['is_internal']);

        $result = self::add_reply(
            $ticket_id,
            $message,
            'admin',
            get_current_user_id(),
            $is_internal
        );

        wp_send_json($result);
    }
}

// Initialiser
new Colis224_Support_Tickets();
