<?php
/**
 * Classe de gestion des assurances et déclarations de valeur
 * MODULE 30: Assurance complète, sinistres, réclamations, et indemnisations
 *
 * @package Colis224
 * @subpackage Insurance
 * @since 2.9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Insurance {

    /**
     * Créer les tables pour l'assurance
     */
    public static function create_insurance_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table des déclarations de valeur
        $sql_declarations = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_value_declarations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            parcel_id bigint(20) NOT NULL,
            declaration_number varchar(50) NOT NULL UNIQUE,
            declared_value decimal(15,2) NOT NULL,
            currency varchar(3) DEFAULT 'GNF',
            item_description text NOT NULL,
            item_category varchar(100) DEFAULT NULL,
            insurance_premium decimal(10,2) DEFAULT 0,
            premium_rate decimal(5,2) DEFAULT 2.00,
            coverage_type enum('basic','standard','premium','custom') DEFAULT 'standard',
            coverage_amount decimal(15,2) NOT NULL,
            start_date datetime NOT NULL,
            end_date datetime DEFAULT NULL,
            status enum('active','expired','claimed','cancelled') DEFAULT 'active',
            notes text,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_parcel (parcel_id),
            KEY idx_declaration_number (declaration_number),
            KEY idx_status (status)
        ) $charset_collate;";

        // Table des sinistres
        $sql_claims = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_insurance_claims (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            claim_number varchar(50) NOT NULL UNIQUE,
            parcel_id bigint(20) NOT NULL,
            declaration_id bigint(20) DEFAULT NULL,
            claim_type enum('damage','loss','theft','partial_loss','delay','other') DEFAULT 'damage',
            incident_date datetime NOT NULL,
            reported_date datetime NOT NULL,
            claimed_amount decimal(15,2) NOT NULL,
            approved_amount decimal(15,2) DEFAULT 0,
            status enum('submitted','under_review','approved','rejected','paid','closed') DEFAULT 'submitted',
            priority enum('low','medium','high','urgent') DEFAULT 'medium',
            description text NOT NULL,
            evidence_documents text,
            photos text,
            police_report tinyint(1) DEFAULT 0,
            police_report_number varchar(100) DEFAULT NULL,
            reviewer_id bigint(20) DEFAULT NULL,
            reviewer_notes text,
            reviewed_at datetime DEFAULT NULL,
            payment_date datetime DEFAULT NULL,
            payment_method varchar(50) DEFAULT NULL,
            payment_reference varchar(100) DEFAULT NULL,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_claim_number (claim_number),
            KEY idx_parcel (parcel_id),
            KEY idx_status (status),
            KEY idx_priority (priority)
        ) $charset_collate;";

        // Table des remboursements
        $sql_refunds = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_insurance_refunds (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            claim_id bigint(20) NOT NULL,
            refund_number varchar(50) NOT NULL UNIQUE,
            refund_amount decimal(15,2) NOT NULL,
            refund_type enum('full','partial','deductible') DEFAULT 'full',
            deductible_amount decimal(10,2) DEFAULT 0,
            refund_method enum('bank_transfer','mobile_money','cash','check','other') DEFAULT 'bank_transfer',
            beneficiary_name varchar(200) NOT NULL,
            beneficiary_account varchar(200) DEFAULT NULL,
            status enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
            payment_date datetime DEFAULT NULL,
            reference_number varchar(100) DEFAULT NULL,
            notes text,
            processed_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_claim (claim_id),
            KEY idx_status (status)
        ) $charset_collate;";

        // Table de l'historique des réclamations
        $sql_claim_history = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_claim_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            claim_id bigint(20) NOT NULL,
            action varchar(100) NOT NULL,
            old_status varchar(50) DEFAULT NULL,
            new_status varchar(50) DEFAULT NULL,
            comment text,
            user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_claim (claim_id)
        ) $charset_collate;";

        // Table des tarifs d'assurance
        $sql_rates = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_insurance_rates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            coverage_type varchar(50) NOT NULL,
            min_value decimal(15,2) DEFAULT 0,
            max_value decimal(15,2) DEFAULT NULL,
            rate_percentage decimal(5,2) NOT NULL,
            min_premium decimal(10,2) DEFAULT 0,
            description text,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_coverage_type (coverage_type),
            KEY idx_active (is_active)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_declarations);
        dbDelta($sql_claims);
        dbDelta($sql_refunds);
        dbDelta($sql_claim_history);
        dbDelta($sql_rates);

        // Créer les tarifs par défaut
        self::create_default_rates();
    }

    /**
     * Créer les tarifs d'assurance par défaut
     */
    private static function create_default_rates() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_insurance_rates';

        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        if ($existing == 0) {
            $rates = array(
                array(
                    'coverage_type' => 'basic',
                    'min_value' => 0,
                    'max_value' => 500000,
                    'rate_percentage' => 1.00,
                    'min_premium' => 5000,
                    'description' => 'Couverture de base jusqu\'à 500,000 GNF'
                ),
                array(
                    'coverage_type' => 'standard',
                    'min_value' => 500001,
                    'max_value' => 2000000,
                    'rate_percentage' => 2.00,
                    'min_premium' => 10000,
                    'description' => 'Couverture standard de 500K à 2M GNF'
                ),
                array(
                    'coverage_type' => 'premium',
                    'min_value' => 2000001,
                    'max_value' => 10000000,
                    'rate_percentage' => 3.00,
                    'min_premium' => 50000,
                    'description' => 'Couverture premium de 2M à 10M GNF'
                ),
                array(
                    'coverage_type' => 'custom',
                    'min_value' => 10000001,
                    'max_value' => null,
                    'rate_percentage' => 5.00,
                    'min_premium' => 100000,
                    'description' => 'Couverture personnalisée au-delà de 10M GNF'
                )
            );

            foreach ($rates as $rate) {
                $wpdb->insert($table, $rate);
            }
        }
    }

    /**
     * Calculer la prime d'assurance
     */
    public static function calculate_premium($declared_value, $coverage_type = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_insurance_rates';

        if ($coverage_type) {
            $rate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE coverage_type = %s AND is_active = 1",
                $coverage_type
            ));
        } else {
            // Trouver automatiquement le tarif adapté
            $rate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table
                WHERE is_active = 1
                AND min_value <= %d
                AND (max_value IS NULL OR max_value >= %d)
                ORDER BY min_value DESC
                LIMIT 1",
                $declared_value,
                $declared_value
            ));
        }

        if ($rate) {
            $premium = ($declared_value * $rate->rate_percentage) / 100;
            $premium = max($premium, $rate->min_premium);

            return array(
                'premium' => $premium,
                'rate_percentage' => $rate->rate_percentage,
                'coverage_type' => $rate->coverage_type,
                'coverage_amount' => $declared_value
            );
        }

        return false;
    }

    /**
     * Créer une déclaration de valeur
     */
    public static function create_value_declaration($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_value_declarations';

        $defaults = array(
            'parcel_id' => 0,
            'declaration_number' => self::generate_declaration_number(),
            'declared_value' => 0,
            'currency' => 'GNF',
            'item_description' => '',
            'item_category' => '',
            'coverage_type' => 'standard',
            'start_date' => current_time('mysql'),
            'end_date' => null,
            'status' => 'active',
            'notes' => '',
            'created_by' => get_current_user_id()
        );

        $data = wp_parse_args($data, $defaults);

        // Calculer la prime automatiquement
        $premium_data = self::calculate_premium($data['declared_value'], $data['coverage_type']);

        if ($premium_data) {
            $data['insurance_premium'] = $premium_data['premium'];
            $data['premium_rate'] = $premium_data['rate_percentage'];
            $data['coverage_amount'] = $premium_data['coverage_amount'];
        }

        $result = $wpdb->insert($table, $data);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Générer un numéro de déclaration unique
     */
    private static function generate_declaration_number() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_value_declarations';

        do {
            $number = 'DV-' . date('Ymd') . '-' . strtoupper(wp_generate_password(6, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE declaration_number = %s",
                $number
            ));
        } while ($exists > 0);

        return $number;
    }

    /**
     * Créer une réclamation
     */
    public static function create_claim($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_insurance_claims';

        $defaults = array(
            'claim_number' => self::generate_claim_number(),
            'parcel_id' => 0,
            'declaration_id' => null,
            'claim_type' => 'damage',
            'incident_date' => current_time('mysql'),
            'reported_date' => current_time('mysql'),
            'claimed_amount' => 0,
            'approved_amount' => 0,
            'status' => 'submitted',
            'priority' => 'medium',
            'description' => '',
            'evidence_documents' => '',
            'photos' => '',
            'police_report' => 0,
            'police_report_number' => '',
            'created_by' => get_current_user_id()
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert($table, $data);

        if ($result) {
            $claim_id = $wpdb->insert_id;

            // Enregistrer l'historique
            self::add_claim_history($claim_id, 'created', null, 'submitted', 'Réclamation créée');

            // Notifier les admins
            self::notify_admins_new_claim($claim_id);

            return $claim_id;
        }

        return false;
    }

    /**
     * Générer un numéro de réclamation unique
     */
    private static function generate_claim_number() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_insurance_claims';

        do {
            $number = 'CLM-' . date('Ymd') . '-' . strtoupper(wp_generate_password(6, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE claim_number = %s",
                $number
            ));
        } while ($exists > 0);

        return $number;
    }

    /**
     * Mettre à jour le statut d'une réclamation
     */
    public static function update_claim_status($claim_id, $new_status, $comment = '', $approved_amount = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_insurance_claims';

        $claim = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $claim_id
        ));

        if (!$claim) {
            return false;
        }

        $update_data = array(
            'status' => $new_status,
            'reviewer_id' => get_current_user_id(),
            'reviewed_at' => current_time('mysql')
        );

        if ($approved_amount !== null) {
            $update_data['approved_amount'] = $approved_amount;
        }

        if ($new_status === 'approved' && !empty($comment)) {
            $update_data['reviewer_notes'] = $comment;
        }

        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $claim_id)
        );

        if ($result !== false) {
            // Enregistrer l'historique
            self::add_claim_history($claim_id, 'status_changed', $claim->status, $new_status, $comment);

            // Si approuvé, créer le remboursement
            if ($new_status === 'approved' && $approved_amount > 0) {
                self::create_refund($claim_id, $approved_amount);
            }

            return true;
        }

        return false;
    }

    /**
     * Créer un remboursement
     */
    public static function create_refund($claim_id, $amount, $refund_type = 'full', $deductible = 0) {
        global $wpdb;
        $table_refunds = $wpdb->prefix . 'colis224_insurance_refunds';
        $table_claims = $wpdb->prefix . 'colis224_insurance_claims';

        $claim = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_claims WHERE id = %d",
            $claim_id
        ));

        if (!$claim) {
            return false;
        }

        // Récupérer les infos du client
        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_parcels WHERE id = %d",
            $claim->parcel_id
        ));

        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_clients WHERE id = %d",
            $parcel->client_id
        ));

        $refund_data = array(
            'claim_id' => $claim_id,
            'refund_number' => self::generate_refund_number(),
            'refund_amount' => $amount - $deductible,
            'refund_type' => $refund_type,
            'deductible_amount' => $deductible,
            'refund_method' => 'bank_transfer',
            'beneficiary_name' => $client ? $client->name : '',
            'status' => 'pending',
            'processed_by' => get_current_user_id()
        );

        $result = $wpdb->insert($table_refunds, $refund_data);

        if ($result) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Générer un numéro de remboursement unique
     */
    private static function generate_refund_number() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_insurance_refunds';

        do {
            $number = 'RFD-' . date('Ymd') . '-' . strtoupper(wp_generate_password(6, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE refund_number = %s",
                $number
            ));
        } while ($exists > 0);

        return $number;
    }

    /**
     * Ajouter une entrée à l'historique
     */
    private static function add_claim_history($claim_id, $action, $old_status, $new_status, $comment) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_claim_history';

        return $wpdb->insert(
            $table,
            array(
                'claim_id' => $claim_id,
                'action' => $action,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'comment' => $comment,
                'user_id' => get_current_user_id()
            )
        );
    }

    /**
     * Notifier les admins d'une nouvelle réclamation
     */
    private static function notify_admins_new_claim($claim_id) {
        global $wpdb;
        $table_claims = $wpdb->prefix . 'colis224_insurance_claims';

        $claim = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_claims WHERE id = %d",
            $claim_id
        ));

        if (!$claim) {
            return false;
        }

        $admin_email = get_option('admin_email');

        $subject = sprintf('[Colis224] Nouvelle Réclamation: %s', $claim->claim_number);

        $message = sprintf(
            "Une nouvelle réclamation d'assurance a été soumise:\n\n" .
            "Numéro: %s\n" .
            "Type: %s\n" .
            "Montant réclamé: %s GNF\n" .
            "Priorité: %s\n" .
            "Date incident: %s\n\n" .
            "Description:\n%s\n\n" .
            "Veuillez examiner cette réclamation dans votre tableau de bord.",
            $claim->claim_number,
            $claim->claim_type,
            number_format($claim->claimed_amount, 0, ',', ' '),
            $claim->priority,
            date('d/m/Y', strtotime($claim->incident_date)),
            $claim->description
        );

        return wp_mail($admin_email, $subject, $message);
    }

    /**
     * Obtenir les statistiques d'assurance
     */
    public static function get_statistics($period = 30) {
        global $wpdb;
        $start_date = date('Y-m-d', strtotime("-$period days"));

        $stats = array();

        // Total déclarations
        $stats['total_declarations'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_value_declarations WHERE created_at >= %s",
            $start_date
        ));

        // Total primes collectées
        $stats['total_premiums'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(insurance_premium) FROM {$wpdb->prefix}colis224_value_declarations WHERE created_at >= %s",
            $start_date
        )) ?: 0;

        // Total réclamations
        $stats['total_claims'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_insurance_claims WHERE created_at >= %s",
            $start_date
        ));

        // Réclamations approuvées
        $stats['approved_claims'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_insurance_claims WHERE status = 'approved' AND reviewed_at >= %s",
            $start_date
        ));

        // Total remboursements
        $stats['total_refunds'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(refund_amount) FROM {$wpdb->prefix}colis224_insurance_refunds WHERE created_at >= %s",
            $start_date
        )) ?: 0;

        // Ratio sinistralité
        if ($stats['total_premiums'] > 0) {
            $stats['loss_ratio'] = ($stats['total_refunds'] / $stats['total_premiums']) * 100;
        } else {
            $stats['loss_ratio'] = 0;
        }

        return $stats;
    }
}
