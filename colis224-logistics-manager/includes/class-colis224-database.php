<?php
/**
 * Gestion de la base de données
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Database {

    public static function create_tables() {
        global $wpdb;

        // Capturer TOUTE sortie pour éviter les warnings WordPress
        $ob_started = false;
        if (!ob_get_level()) {
            ob_start();
            $ob_started = true;
        }
        
        // Supprimer les erreurs d'affichage pendant la création des tables
        $wpdb->suppress_errors = true;
        $error_reporting = error_reporting(0);
        $display_errors = ini_get('display_errors');
        ini_set('display_errors', 0);
        
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table des clients
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $sql_clients = "CREATE TABLE $table_clients (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            type enum('Particulier','Entreprise') DEFAULT 'Particulier',
            name varchar(255) NOT NULL,
            company_name varchar(255) DEFAULT NULL,
            phone varchar(50) NOT NULL,
            email varchar(100) DEFAULT NULL,
            address text DEFAULT NULL,
            balance decimal(15,2) DEFAULT 0.00,
            discount_rate decimal(5,2) DEFAULT 0.00,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY phone (phone),
            KEY email (email)
        ) $charset_collate;";
        dbDelta($sql_clients);

        // Table des pays
        $table_countries = $wpdb->prefix . 'colis224_countries';
        $sql_countries = "CREATE TABLE $table_countries (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            code varchar(10) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";
        dbDelta($sql_countries);

        // Table des modes de transport
        $table_transport = $wpdb->prefix . 'colis224_transport_modes';
        $sql_transport = "CREATE TABLE $table_transport (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            estimated_days int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";
        dbDelta($sql_transport);

        // Table des catégories de colis
        $table_categories = $wpdb->prefix . 'colis224_parcel_categories';
        $sql_categories = "CREATE TABLE $table_categories (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";
        dbDelta($sql_categories);

        // Table des colis
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $sql_parcels = "CREATE TABLE $table_parcels (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tracking_number varchar(50) NOT NULL,
            client_id bigint(20) UNSIGNED DEFAULT NULL,
            sender_name varchar(255) DEFAULT NULL,
            recipient_name varchar(255) NOT NULL,
            recipient_phone varchar(50) NOT NULL,
            recipient_address text NOT NULL,
            origin_country_id bigint(20) UNSIGNED DEFAULT NULL,
            destination_country_id bigint(20) UNSIGNED DEFAULT NULL,
            transport_mode_id bigint(20) UNSIGNED DEFAULT NULL,
            category_id bigint(20) UNSIGNED DEFAULT NULL,
            weight decimal(10,2) DEFAULT 0.00,
            unit_price decimal(15,2) DEFAULT 0.00,
            discount_type enum('percentage','fixed') DEFAULT 'percentage',
            discount_value decimal(10,2) DEFAULT 0.00,
            total_amount decimal(15,2) DEFAULT 0.00,
            currency varchar(10) DEFAULT 'GNF',
            reception_date date DEFAULT NULL,
            shipping_date date DEFAULT NULL,
            delivery_date date DEFAULT NULL,
            estimated_delivery_date date DEFAULT NULL,
            status enum('En attente','Expédié','En transit','Livré','Retour') DEFAULT 'En attente',
            payment_method varchar(50) DEFAULT NULL,
            payment_status enum('Payé','Partiel','Non payé') DEFAULT 'Non payé',
            paid_amount decimal(15,2) DEFAULT 0.00,
            remaining_amount decimal(15,2) DEFAULT 0.00,
            driver_id bigint(20) UNSIGNED DEFAULT NULL,
            photos text DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY tracking_number (tracking_number),
            KEY client_id (client_id),
            KEY status (status),
            KEY payment_status (payment_status)
        ) $charset_collate;";
        dbDelta($sql_parcels);

        // Table des partenaires
        $table_partners = $wpdb->prefix . 'colis224_partners';
        $sql_partners = "CREATE TABLE $table_partners (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            country varchar(100) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            additional_phones text DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            address text DEFAULT NULL,
            collaboration_type enum('Nous leur confions','Ils nous confient','Bidirectionnel') DEFAULT 'Bidirectionnel',
            balance decimal(15,2) DEFAULT 0.00,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY name (name)
        ) $charset_collate;";
        dbDelta($sql_partners);

        // Table des transactions partenaires
        $table_partner_trans = $wpdb->prefix . 'colis224_partner_transactions';
        $sql_partner_trans = "CREATE TABLE $table_partner_trans (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            partner_id bigint(20) UNSIGNED NOT NULL,
            transaction_type enum('confié','reçu') NOT NULL,
            partner_tracking_number varchar(50) DEFAULT NULL,
            internal_tracking_number varchar(50) DEFAULT NULL,
            weight decimal(10,2) DEFAULT 0.00,
            nature varchar(255) DEFAULT NULL,
            invoice_number varchar(50) DEFAULT NULL,
            amount decimal(15,2) DEFAULT 0.00,
            payment_status enum('Payé','À payer','Partiel') DEFAULT 'À payer',
            paid_amount decimal(15,2) DEFAULT 0.00,
            parcel_status enum('Déposé','Reçu par partenaire','En transit','Livré','En attente') DEFAULT 'Déposé',
            transaction_date date DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY partner_id (partner_id),
            KEY payment_status (payment_status)
        ) $charset_collate;";
        dbDelta($sql_partner_trans);

        // Table des agents
        $table_team = $wpdb->prefix . 'colis224_team_members';
        $sql_team = "CREATE TABLE $table_team (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            phone varchar(50) DEFAULT NULL,
            additional_phones text DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            role varchar(100) DEFAULT 'Agent',
            salary decimal(15,2) DEFAULT 0.00,
            notes text DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_team);

        // Table des livreurs
        $table_drivers = $wpdb->prefix . 'colis224_drivers';
        $sql_drivers = "CREATE TABLE $table_drivers (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            phone varchar(50) NOT NULL,
            additional_phones text DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            zone varchar(255) DEFAULT NULL,
            commission_type enum('percentage','fixed') DEFAULT 'percentage',
            commission_value decimal(10,2) DEFAULT 0.00,
            total_earned decimal(15,2) DEFAULT 0.00,
            total_paid decimal(15,2) DEFAULT 0.00,
            balance decimal(15,2) DEFAULT 0.00,
            is_active tinyint(1) DEFAULT 1,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY phone (phone)
        ) $charset_collate;";
        dbDelta($sql_drivers);

        // Table des catégories de dépenses
        $table_expense_cat = $wpdb->prefix . 'colis224_expense_categories';
        $sql_expense_cat = "CREATE TABLE $table_expense_cat (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";
        dbDelta($sql_expense_cat);

        // Table des dépenses
        $table_expenses = $wpdb->prefix . 'colis224_expenses';
        $sql_expenses = "CREATE TABLE $table_expenses (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            category_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(15,2) NOT NULL,
            currency varchar(10) DEFAULT 'GNF',
            payment_method varchar(50) DEFAULT NULL,
            beneficiary varchar(255) DEFAULT NULL,
            description text DEFAULT NULL,
            invoice_file varchar(255) DEFAULT NULL,
            expense_date date NOT NULL,
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY category_id (category_id),
            KEY expense_date (expense_date)
        ) $charset_collate;";
        dbDelta($sql_expenses);

        // Table des revenus
        $table_revenues = $wpdb->prefix . 'colis224_revenues';
        $sql_revenues = "CREATE TABLE $table_revenues (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_type enum('parcel','other') DEFAULT 'parcel',
            parcel_id bigint(20) UNSIGNED DEFAULT NULL,
            amount decimal(15,2) NOT NULL,
            currency varchar(10) DEFAULT 'GNF',
            payment_method varchar(50) DEFAULT NULL,
            description text DEFAULT NULL,
            revenue_date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY parcel_id (parcel_id),
            KEY revenue_date (revenue_date)
        ) $charset_collate;";
        dbDelta($sql_revenues);

        // Table des paiements
        $table_payments = $wpdb->prefix . 'colis224_payments';
        $sql_payments = "CREATE TABLE $table_payments (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reference_type enum('parcel','partner','driver','other') NOT NULL,
            reference_id bigint(20) UNSIGNED NOT NULL,
            amount decimal(15,2) NOT NULL,
            currency varchar(10) DEFAULT 'GNF',
            payment_method varchar(50) NOT NULL,
            payment_date date NOT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY reference_type (reference_type),
            KEY reference_id (reference_id)
        ) $charset_collate;";
        dbDelta($sql_payments);

        // Table des demandes d'achat
        $table_purchases = $wpdb->prefix . 'colis224_purchase_requests';
        $sql_purchases = "CREATE TABLE $table_purchases (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id bigint(20) UNSIGNED NOT NULL,
            item_description text NOT NULL,
            item_url text DEFAULT NULL,
            estimated_amount decimal(15,2) DEFAULT 0.00,
            service_fee decimal(15,2) DEFAULT 0.00,
            total_amount decimal(15,2) DEFAULT 0.00,
            currency varchar(10) DEFAULT 'GNF',
            status enum('Demande reçue','Devis envoyé','Validé','Acheté','Expédié','Livré','Annulé') DEFAULT 'Demande reçue',
            parcel_id bigint(20) UNSIGNED DEFAULT NULL,
            notes text DEFAULT NULL,
            request_date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY client_id (client_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_purchases);

        // Table des notifications
        $table_notifications = $wpdb->prefix . 'colis224_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recipient_type enum('client','partner','driver','team') NOT NULL,
            recipient_id bigint(20) UNSIGNED NOT NULL,
            notification_type enum('email','sms','both') NOT NULL,
            subject varchar(255) DEFAULT NULL,
            message text NOT NULL,
            status enum('pending','sent','failed') DEFAULT 'pending',
            sent_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY recipient_type (recipient_type),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_notifications);

        // Table des transactions Mobile Money
        $table_mobile_money = $wpdb->prefix . 'colis224_mobile_money_transactions';
        $sql_mobile_money = "CREATE TABLE $table_mobile_money (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            reference varchar(50) NOT NULL,
            provider enum('orange_money','mtn_money','moov_money') NOT NULL,
            amount decimal(15,2) NOT NULL,
            phone_number varchar(50) NOT NULL,
            status enum('pending','completed','failed','cancelled') DEFAULT 'pending',
            transaction_id varchar(100) DEFAULT NULL,
            api_response text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY reference (reference),
            KEY provider (provider),
            KEY status (status),
            KEY phone_number (phone_number)
        ) $charset_collate;";
        dbDelta($sql_mobile_money);

        // Table des logs SMS
        $table_sms_logs = $wpdb->prefix . 'colis224_sms_logs';
        $sql_sms_logs = "CREATE TABLE $table_sms_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            phone_number varchar(50) NOT NULL,
            message text NOT NULL,
            provider varchar(50) DEFAULT NULL,
            status enum('sent','failed','pending') DEFAULT 'pending',
            response text DEFAULT NULL,
            parcel_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY phone_number (phone_number),
            KEY status (status),
            KEY parcel_id (parcel_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_sms_logs);

        // ===== PROGRAMME DE FIDÉLITÉ =====

        // Table des programmes de fidélité
        $table_loyalty_programs = $wpdb->prefix . 'colis224_loyalty_programs';
        $sql_loyalty_programs = "CREATE TABLE $table_loyalty_programs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            points_per_gnf decimal(10,2) DEFAULT 0.00,
            points_per_parcel int(11) DEFAULT 0,
            min_points_threshold int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_loyalty_programs);

        // Table des membres du programme de fidélité
        $table_loyalty_members = $wpdb->prefix . 'colis224_loyalty_members';
        $sql_loyalty_members = "CREATE TABLE $table_loyalty_members (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id bigint(20) UNSIGNED NOT NULL,
            program_id bigint(20) UNSIGNED NOT NULL,
            total_points int(11) DEFAULT 0,
            available_points int(11) DEFAULT 0,
            redeemed_points int(11) DEFAULT 0,
            tier enum('Bronze','Silver','Gold','Platinum') DEFAULT 'Bronze',
            join_date date NOT NULL,
            last_activity_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY client_id (client_id),
            KEY program_id (program_id),
            KEY tier (tier)
        ) $charset_collate;";
        dbDelta($sql_loyalty_members);

        // Table des transactions de points
        $table_loyalty_trans = $wpdb->prefix . 'colis224_loyalty_transactions';
        $sql_loyalty_trans = "CREATE TABLE $table_loyalty_trans (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id bigint(20) UNSIGNED NOT NULL,
            transaction_type enum('earn','redeem','expire','adjust','bonus') NOT NULL,
            points int(11) NOT NULL,
            description text DEFAULT NULL,
            reference_type enum('parcel','payment','manual','system') DEFAULT NULL,
            reference_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY transaction_type (transaction_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_loyalty_trans);

        // Table des récompenses
        $table_loyalty_rewards = $wpdb->prefix . 'colis224_loyalty_rewards';
        $sql_loyalty_rewards = "CREATE TABLE $table_loyalty_rewards (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            points_required int(11) NOT NULL,
            reward_type enum('discount_percentage','discount_fixed','free_shipping','cash_back','gift') NOT NULL,
            reward_value decimal(10,2) DEFAULT 0.00,
            is_active tinyint(1) DEFAULT 1,
            validity_days int(11) DEFAULT 30,
            stock_quantity int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql_loyalty_rewards);

        // Table des échanges de récompenses
        $table_loyalty_redemptions = $wpdb->prefix . 'colis224_loyalty_redemptions';
        $sql_loyalty_redemptions = "CREATE TABLE $table_loyalty_redemptions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            member_id bigint(20) UNSIGNED NOT NULL,
            reward_id bigint(20) UNSIGNED NOT NULL,
            points_spent int(11) NOT NULL,
            parcel_id bigint(20) UNSIGNED DEFAULT NULL,
            status enum('pending','used','expired','cancelled') DEFAULT 'pending',
            redeemed_date datetime NOT NULL,
            expiry_date datetime DEFAULT NULL,
            used_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY reward_id (reward_id),
            KEY status (status),
            KEY parcel_id (parcel_id)
        ) $charset_collate;";
        dbDelta($sql_loyalty_redemptions);

        // ===== MULTI-ENTREPÔTS =====

        // Table des entrepôts
        $table_warehouses = $wpdb->prefix . 'colis224_warehouses';
        $sql_warehouses = "CREATE TABLE $table_warehouses (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            code varchar(50) NOT NULL,
            address text DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            country_id bigint(20) UNSIGNED DEFAULT NULL,
            manager_name varchar(255) DEFAULT NULL,
            manager_phone varchar(50) DEFAULT NULL,
            capacity int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql_warehouses);

        // Table de l'inventaire des entrepôts
        $table_warehouse_inventory = $wpdb->prefix . 'colis224_warehouse_inventory';
        $sql_warehouse_inventory = "CREATE TABLE $table_warehouse_inventory (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            warehouse_id bigint(20) UNSIGNED NOT NULL,
            parcel_id bigint(20) UNSIGNED NOT NULL,
            entry_date datetime NOT NULL,
            exit_date datetime DEFAULT NULL,
            status enum('in_stock','in_transit','delivered','transferred') DEFAULT 'in_stock',
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY warehouse_id (warehouse_id),
            KEY parcel_id (parcel_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_warehouse_inventory);

        // Table des transferts entre entrepôts
        $table_warehouse_transfers = $wpdb->prefix . 'colis224_warehouse_transfers';
        $sql_warehouse_transfers = "CREATE TABLE $table_warehouse_transfers (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            from_warehouse_id bigint(20) UNSIGNED NOT NULL,
            to_warehouse_id bigint(20) UNSIGNED NOT NULL,
            parcel_id bigint(20) UNSIGNED NOT NULL,
            initiated_by bigint(20) UNSIGNED DEFAULT NULL,
            transfer_date date NOT NULL,
            arrival_date date DEFAULT NULL,
            status enum('pending','in_transit','completed','cancelled') DEFAULT 'pending',
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY from_warehouse_id (from_warehouse_id),
            KEY to_warehouse_id (to_warehouse_id),
            KEY parcel_id (parcel_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_warehouse_transfers);

        // ===== SYSTÈME DE SUPPORT / SAV =====

        // Table des tickets support
        $table_support_tickets = $wpdb->prefix . 'colis224_support_tickets';
        $sql_support_tickets = "CREATE TABLE $table_support_tickets (
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
        dbDelta($sql_support_tickets);

        // Table des messages de tickets
        $table_support_messages = $wpdb->prefix . 'colis224_support_messages';
        $sql_support_messages = "CREATE TABLE $table_support_messages (
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
        dbDelta($sql_support_messages);

        // ===== LIVE CHAT =====

        // Table des conversations de chat
        $table_chat_conversations = $wpdb->prefix . 'colis224_chat_conversations';
        $sql_chat_conversations = "CREATE TABLE IF NOT EXISTS $table_chat_conversations (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id varchar(50) NOT NULL,
            client_id bigint(20) UNSIGNED DEFAULT NULL,
            client_name varchar(255) NOT NULL,
            client_email varchar(255) DEFAULT NULL,
            client_phone varchar(50) DEFAULT NULL,
            assigned_agent_id bigint(20) UNSIGNED DEFAULT NULL,
            status enum('active','waiting','closed') DEFAULT 'waiting',
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_message_at datetime DEFAULT CURRENT_TIMESTAMP,
            closed_at datetime DEFAULT NULL,
            rating tinyint(1) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY conversation_id (conversation_id),
            KEY client_id (client_id),
            KEY assigned_agent_id (assigned_agent_id),
            KEY status (status),
            KEY last_message_at (last_message_at)
        ) $charset_collate;";
        dbDelta($sql_chat_conversations);

        // Table des messages de chat
        $table_chat_messages = $wpdb->prefix . 'colis224_chat_messages';
        $sql_chat_messages = "CREATE TABLE IF NOT EXISTS $table_chat_messages (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id varchar(50) NOT NULL,
            sender_type enum('client','agent','system') DEFAULT 'client',
            sender_id bigint(20) UNSIGNED DEFAULT NULL,
            sender_name varchar(255) NOT NULL,
            message text NOT NULL,
            attachment varchar(255) DEFAULT NULL,
            is_read tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY conversation_id (conversation_id),
            KEY sender_type (sender_type),
            KEY created_at (created_at),
            KEY is_read (is_read)
        ) $charset_collate;";
        dbDelta($sql_chat_messages);

        // Table des agents en ligne
        $table_chat_agents_status = $wpdb->prefix . 'colis224_chat_agents_status';
        $sql_chat_agents_status = "CREATE TABLE IF NOT EXISTS $table_chat_agents_status (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) UNSIGNED NOT NULL,
            agent_name varchar(255) NOT NULL,
            agent_email varchar(255) DEFAULT NULL,
            status enum('online','offline','busy','away') DEFAULT 'offline',
            last_activity datetime DEFAULT CURRENT_TIMESTAMP,
            active_conversations int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY agent_id (agent_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_chat_agents_status);

        // ===== PAIEMENTS PAYPAL =====

        // Table des transactions PayPal
        $table_paypal = $wpdb->prefix . 'colis224_paypal_transactions';
        $sql_paypal = "CREATE TABLE $table_paypal (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id varchar(100) NOT NULL,
            parcel_id bigint(20) UNSIGNED DEFAULT NULL,
            payer_email varchar(255) DEFAULT NULL,
            payer_name varchar(255) DEFAULT NULL,
            amount decimal(15,2) NOT NULL,
            currency varchar(10) DEFAULT 'EUR',
            status enum('created','approved','captured','completed','cancelled','failed') DEFAULT 'created',
            paypal_order_id varchar(100) DEFAULT NULL,
            paypal_capture_id varchar(100) DEFAULT NULL,
            api_response text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY order_id (order_id),
            KEY parcel_id (parcel_id),
            KEY status (status),
            KEY paypal_order_id (paypal_order_id)
        ) $charset_collate;";
        dbDelta($sql_paypal);

        // ===== SYSTÈME DE RELANCES ET SURVEILLANCE =====

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

        // ===== SYSTÈME D'AVIS CLIENTS =====

        // Table des avis
        $table_reviews = $wpdb->prefix . 'colis224_reviews';
        $sql_reviews = "CREATE TABLE $table_reviews (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            parcel_id bigint(20) UNSIGNED NOT NULL,
            client_id bigint(20) UNSIGNED DEFAULT NULL,
            driver_id bigint(20) UNSIGNED DEFAULT NULL,
            overall_rating tinyint(1) DEFAULT 5,
            delivery_rating tinyint(1) DEFAULT 5,
            service_rating tinyint(1) DEFAULT 5,
            packaging_rating tinyint(1) DEFAULT 5,
            comment text DEFAULT NULL,
            would_recommend tinyint(1) DEFAULT 1,
            client_name varchar(255) DEFAULT NULL,
            client_email varchar(255) DEFAULT NULL,
            is_verified tinyint(1) DEFAULT 1,
            is_public tinyint(1) DEFAULT 1,
            admin_response text DEFAULT NULL,
            responded_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY parcel_id (parcel_id),
            KEY client_id (client_id),
            KEY driver_id (driver_id),
            KEY overall_rating (overall_rating),
            KEY is_public (is_public)
        ) $charset_collate;";
        dbDelta($sql_reviews);

        // ===== MODULES ADDITIONNELS V2.8.0 =====

        // Créer les tables des nouveaux modules (avec vérification d'existence)
        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-live-chat.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-live-chat.php';
            if (class_exists('Colis224_Live_Chat')) {
        Colis224_Live_Chat::create_chat_tables();
            }
        }

        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-inventory-advanced.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-inventory-advanced.php';
            if (class_exists('Colis224_Inventory_Advanced')) {
        Colis224_Inventory_Advanced::create_inventory_tables();
            }
        }

        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-marketing.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-marketing.php';
            if (class_exists('Colis224_Marketing')) {
        Colis224_Marketing::create_marketing_tables();
            }
        }

        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-partner-portal.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-partner-portal.php';
            if (class_exists('Colis224_Partner_Portal')) {
        Colis224_Partner_Portal::create_partner_portal_tables();
            }
        }

        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-workflows.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-workflows.php';
            if (class_exists('Colis224_Workflows')) {
        Colis224_Workflows::create_workflow_tables();
            }
        }

        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-multilang.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-multilang.php';
            if (class_exists('Colis224_MultiLang')) {
        Colis224_MultiLang::create_multilang_tables();
            }
        }

        // ===== MODULES ADDITIONNELS V2.9.0 =====

        // Créer les tables des nouveaux modules v2.9.0
        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-fleet-management.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-fleet-management.php';
            if (class_exists('Colis224_Fleet_Management')) {
        Colis224_Fleet_Management::create_fleet_tables();
            }
        }

        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-insurance.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-insurance.php';
            if (class_exists('Colis224_Insurance')) {
        Colis224_Insurance::create_insurance_tables();
            }
        }

        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-scheduling.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-scheduling.php';
            if (class_exists('Colis224_Scheduling')) {
        Colis224_Scheduling::create_scheduling_tables();
            }
        }

        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-analytics.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-analytics.php';
            if (class_exists('Colis224_Analytics')) {
        Colis224_Analytics::create_analytics_tables();
            }
        }

        // ===== MODULES ADDITIONNELS V2.10.0 =====

        // Créer les tables du module Départs et Réservations v2.10.0
        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-departures.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-departures.php';
            if (class_exists('Colis224_Departures')) {
        Colis224_Departures::create_departures_tables();
            }
        }

        // Créer les tables du module Automatisation des Départs v2.10.6
        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-departures-automation.php')) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-departures-automation.php';
            if (class_exists('Colis224_Departures_Automation')) {
        Colis224_Departures_Automation::create_automation_table();
            }
        }

        // ===== MODULE GESTION DES LOTS INTERNATIONAUX V2.14.0 =====

        // Table des lots internationaux
        $table_batches = $wpdb->prefix . 'colis224_international_batches';
        $sql_batches = "CREATE TABLE $table_batches (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_number varchar(50) NOT NULL,
            batch_type varchar(20) NOT NULL,
            origin_country_id bigint(20) UNSIGNED DEFAULT NULL,
            destination_country_id bigint(20) UNSIGNED DEFAULT NULL,
            transport_company varchar(255) DEFAULT NULL,
            container_number varchar(100) DEFAULT NULL,
            flight_number varchar(100) DEFAULT NULL,
            departure_date date DEFAULT NULL,
            arrival_date date DEFAULT NULL,
            estimated_arrival_date date DEFAULT NULL,
            actual_arrival_date date DEFAULT NULL,
            status varchar(50) DEFAULT 'preparing',
            total_parcels int(11) DEFAULT 0,
            total_weight decimal(10,2) DEFAULT 0.00,
            total_value decimal(10,2) DEFAULT 0.00,
            customs_document_url varchar(500) DEFAULT NULL,
            manifest_url varchar(500) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_by bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            closed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY batch_number (batch_number),
            KEY batch_type (batch_type),
            KEY status (status),
            KEY departure_date (departure_date),
            KEY arrival_date (arrival_date)
        ) $charset_collate;";
        dbDelta($sql_batches);

        // Table d'assignation colis → lots
        $table_batch_parcels = $wpdb->prefix . 'colis224_batch_parcels';
        $sql_batch_parcels = "CREATE TABLE $table_batch_parcels (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            batch_id bigint(20) UNSIGNED NOT NULL,
            parcel_id bigint(20) UNSIGNED NOT NULL,
            assigned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            assigned_by bigint(20) UNSIGNED DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY batch_parcel_unique (batch_id, parcel_id),
            KEY batch_id (batch_id),
            KEY parcel_id (parcel_id)
        ) $charset_collate;";
        dbDelta($sql_batch_parcels);

        // ===== SYSTÈME DE RÉCUPÉRATION DE SESSION =====

        // Table des tokens "Se souvenir de moi"
        $table_remember_tokens = $wpdb->prefix . 'colis224_remember_tokens';
        $sql_remember_tokens = "CREATE TABLE $table_remember_tokens (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id bigint(20) UNSIGNED NOT NULL,
            token varchar(255) NOT NULL,
            selector varchar(255) NOT NULL,
            expires_at datetime NOT NULL,
            user_agent text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY selector (selector),
            KEY client_id (client_id),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        dbDelta($sql_remember_tokens);

        // Ajouter la colonne batch_id à la table parcels si elle n'existe pas
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        // Vérifier si la table existe d'abord
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_parcels'") === $table_parcels;
        if ($table_exists) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_parcels LIKE 'batch_id'");
            if (empty($columns)) {
                $wpdb->query("ALTER TABLE $table_parcels ADD COLUMN batch_id bigint(20) UNSIGNED DEFAULT NULL AFTER driver_id");
                $wpdb->query("ALTER TABLE $table_parcels ADD INDEX idx_batch_id (batch_id)");
            }
        }
        
        // Créer les données par défaut (catégories, pays, modes de transport)
        self::insert_default_data();
        
        // Nettoyer TOUTE sortie capturée AVANT de réactiver les erreurs
        if ($ob_started) {
            ob_end_clean();
        } else {
            // Nettoyer tous les niveaux de buffer s'il y en a
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
        
        // Réactiver l'affichage des erreurs
        $wpdb->suppress_errors = false;
        error_reporting($error_reporting);
        ini_set('display_errors', $display_errors);
    }
    
    /**
     * Insérer les données par défaut
     */
    public static function insert_default_data() {
        global $wpdb;
        
        // Catégories de colis
        $categories_table = $wpdb->prefix . 'colis224_parcel_categories';
        $count_categories = $wpdb->get_var("SELECT COUNT(*) FROM $categories_table");
        
        if ($count_categories == 0) {
            $default_categories = array(
                array('name' => 'Vêtements', 'description' => 'Vêtements et accessoires'),
                array('name' => 'Électronique', 'description' => 'Téléphones, ordinateurs, gadgets'),
                array('name' => 'Alimentaire', 'description' => 'Produits alimentaires'),
                array('name' => 'Cosmétiques', 'description' => 'Produits de beauté et cosmétiques'),
                array('name' => 'Livres', 'description' => 'Livres et documents'),
                array('name' => 'Jouets', 'description' => 'Jouets et jeux'),
                array('name' => 'Médicaments', 'description' => 'Produits pharmaceutiques'),
                array('name' => 'Meubles', 'description' => 'Mobilier et décoration'),
                array('name' => 'Sports', 'description' => 'Articles de sport'),
                array('name' => 'Divers', 'description' => 'Autres articles')
            );
            
            foreach ($default_categories as $category) {
                $wpdb->insert($categories_table, $category);
            }
        }
        
        // Pays
        $countries_table = $wpdb->prefix . 'colis224_countries';
        $count_countries = $wpdb->get_var("SELECT COUNT(*) FROM $countries_table");
        
        if ($count_countries == 0) {
            $default_countries = array(
                array('name' => 'Guinée', 'code' => 'GN'),
                array('name' => 'Chine', 'code' => 'CN'),
                array('name' => 'France', 'code' => 'FR'),
                array('name' => 'Sénégal', 'code' => 'SN'),
                array('name' => 'Maroc', 'code' => 'MA'),
                array('name' => 'Côte d\'Ivoire', 'code' => 'CI'),
                array('name' => 'États-Unis', 'code' => 'US'),
                array('name' => 'Canada', 'code' => 'CA')
            );
            
            foreach ($default_countries as $country) {
                $wpdb->insert($countries_table, $country);
            }
        }
        
        // Modes de transport
        $transport_table = $wpdb->prefix . 'colis224_transport_modes';
        $count_transport = $wpdb->get_var("SELECT COUNT(*) FROM $transport_table");
        
        if ($count_transport == 0) {
            $default_transport = array(
                array('name' => 'Avion', 'estimated_days' => 7),
                array('name' => 'Bateau', 'estimated_days' => 30),
                array('name' => 'Express', 'estimated_days' => 3)
            );
            
            foreach ($default_transport as $transport) {
                $wpdb->insert($transport_table, $transport);
            }
        }
        
    }

    /**
     * Suppression de toutes les tables (utilisé lors de la désinstallation)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            'colis224_remember_tokens',
            'colis224_chat_agents_status',
            'colis224_chat_messages',
            'colis224_chat_conversations',
            'colis224_reviews',
            'colis224_reminder_config',
            'colis224_surveillance_logs',
            'colis224_reminders',
            'colis224_paypal_transactions',
            'colis224_support_messages',
            'colis224_support_tickets',
            'colis224_warehouse_transfers',
            'colis224_warehouse_inventory',
            'colis224_warehouses',
            'colis224_loyalty_redemptions',
            'colis224_loyalty_rewards',
            'colis224_loyalty_transactions',
            'colis224_loyalty_members',
            'colis224_loyalty_programs',
            'colis224_sms_logs',
            'colis224_mobile_money_transactions',
            'colis224_notifications',
            'colis224_purchase_requests',
            'colis224_payments',
            'colis224_revenues',
            'colis224_expenses',
            'colis224_expense_categories',
            'colis224_drivers',
            'colis224_team_members',
            'colis224_partner_transactions',
            'colis224_partners',
            'colis224_parcels',
            'colis224_parcel_categories',
            'colis224_transport_modes',
            'colis224_countries',
            'colis224_clients'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table}");
        }
    }
}
