<?php
/**
 * Système d'archivage automatique des colis
 *
 * @package Colis224_Logistics
 * @version 2.12.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Archiving {

    // Nombre de jours après livraison avant archivage automatique
    const AUTO_ARCHIVE_DAYS = 90;

    /**
     * Initialisation
     */
    public function __construct() {
        // Planifier l'archivage automatique quotidien
        if (!wp_next_scheduled('colis224_auto_archive_cron')) {
            wp_schedule_event(time(), 'daily', 'colis224_auto_archive_cron');
        }

        add_action('colis224_auto_archive_cron', array($this, 'run_auto_archive'));
    }

    /**
     * Archiver un colis manuellement
     *
     * @param int $parcel_id ID du colis
     * @param string $reason Raison de l'archivage
     * @return array Résultat
     */
    public static function archive_parcel($parcel_id, $reason = 'manual_archive') {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_archived = $wpdb->prefix . 'colis224_archived_parcels';

        // Récupérer le colis
        $parcel = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_parcels WHERE id = %d", $parcel_id));

        if (!$parcel) {
            return array(
                'success' => false,
                'message' => 'Colis introuvable.'
            );
        }

        // Vérifier si déjà archivé
        if ($parcel->is_archived == 1) {
            return array(
                'success' => false,
                'message' => 'Ce colis est déjà archivé.'
            );
        }

        $current_user = wp_get_current_user();

        // Démarrer une transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Copier le colis dans la table d'archives
            $archive_data = array(
                'original_parcel_id' => $parcel->id,
                'tracking_number' => $parcel->tracking_number,
                'client_id' => $parcel->client_id,
                'sender_name' => $parcel->sender_name,
                'recipient_name' => $parcel->recipient_name,
                'recipient_phone' => $parcel->recipient_phone,
                'recipient_address' => $parcel->recipient_address,
                'origin_country_id' => $parcel->origin_country_id,
                'destination_country_id' => $parcel->destination_country_id,
                'transport_mode_id' => $parcel->transport_mode_id,
                'category_id' => $parcel->category_id,
                'weight' => $parcel->weight,
                'unit_price' => $parcel->unit_price,
                'discount_type' => $parcel->discount_type,
                'discount_value' => $parcel->discount_value,
                'total_amount' => $parcel->total_amount,
                'currency' => $parcel->currency,
                'reception_date' => $parcel->reception_date,
                'shipping_date' => $parcel->shipping_date,
                'delivery_date' => $parcel->delivery_date,
                'estimated_delivery_date' => $parcel->estimated_delivery_date,
                'status' => $parcel->status,
                'payment_method' => $parcel->payment_method,
                'payment_status' => $parcel->payment_status,
                'paid_amount' => $parcel->paid_amount,
                'remaining_amount' => $parcel->remaining_amount,
                'driver_id' => $parcel->driver_id,
                'notes' => $parcel->notes,
                'created_by' => isset($parcel->created_by) ? $parcel->created_by : null,
                'validated_by' => isset($parcel->validated_by) ? $parcel->validated_by : null,
                'validated_at' => isset($parcel->validated_at) ? $parcel->validated_at : null,
                'validation_status' => isset($parcel->validation_status) ? $parcel->validation_status : 'approved',
                'validation_notes' => isset($parcel->validation_notes) ? $parcel->validation_notes : null,
                'created_at' => $parcel->created_at,
                'archived_at' => current_time('mysql'),
                'archived_by' => $current_user->ID,
                'archive_reason' => $reason
            );

            $wpdb->insert($table_archived, $archive_data);

            // Marquer le colis comme archivé dans la table principale
            $wpdb->update(
                $table_parcels,
                array(
                    'is_archived' => 1,
                    'archived_at' => current_time('mysql')
                ),
                array('id' => $parcel_id),
                array('%d', '%s'),
                array('%d')
            );

            // Enregistrer dans l'historique
            if (class_exists('Colis224_Parcel_History')) {
                Colis224_Parcel_History::log(
                    $parcel_id,
                    'archived',
                    'archive_status',
                    'active',
                    'archived',
                    'Colis archivé - Raison: ' . $reason
                );
            }

            $wpdb->query('COMMIT');

            return array(
                'success' => true,
                'message' => '✅ Colis archivé avec succès.'
            );

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');

            return array(
                'success' => false,
                'message' => '❌ Erreur lors de l\'archivage : ' . $e->getMessage()
            );
        }
    }

    /**
     * Restaurer un colis archivé
     *
     * @param int $parcel_id ID du colis
     * @return array Résultat
     */
    public static function restore_parcel($parcel_id) {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Récupérer le colis
        $parcel = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_parcels WHERE id = %d", $parcel_id));

        if (!$parcel) {
            return array(
                'success' => false,
                'message' => 'Colis introuvable.'
            );
        }

        if ($parcel->is_archived == 0) {
            return array(
                'success' => false,
                'message' => 'Ce colis n\'est pas archivé.'
            );
        }

        // Démarrer une transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Réactiver le colis
            $wpdb->update(
                $table_parcels,
                array(
                    'is_archived' => 0,
                    'archived_at' => null
                ),
                array('id' => $parcel_id),
                array('%d', '%s'),
                array('%d')
            );

            // Enregistrer dans l'historique
            if (class_exists('Colis224_Parcel_History')) {
                Colis224_Parcel_History::log(
                    $parcel_id,
                    'restored',
                    'archive_status',
                    'archived',
                    'active',
                    'Colis restauré depuis les archives'
                );
            }

            $wpdb->query('COMMIT');

            return array(
                'success' => true,
                'message' => '✅ Colis restauré avec succès.'
            );

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');

            return array(
                'success' => false,
                'message' => '❌ Erreur lors de la restauration : ' . $e->getMessage()
            );
        }
    }

    /**
     * Archivage automatique quotidien
     * Archive les colis livrés depuis plus de X jours
     */
    public function run_auto_archive() {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $days = self::AUTO_ARCHIVE_DAYS;

        // Trouver les colis éligibles à l'archivage
        $cutoff_date = date('Y-m-d', strtotime("-$days days"));

        $eligible_parcels = $wpdb->get_results($wpdb->prepare("
            SELECT id
            FROM $table_parcels
            WHERE status = 'Livré'
            AND is_archived = 0
            AND delivery_date IS NOT NULL
            AND delivery_date <= %s
        ", $cutoff_date));

        $archived_count = 0;

        foreach ($eligible_parcels as $parcel) {
            $result = self::archive_parcel($parcel->id, 'auto_archive_delivered');
            if ($result['success']) {
                $archived_count++;
            }
        }

        // Log du résultat
        error_log(sprintf(
            'Colis224 Auto-Archive: %d colis archivés (livrés avant le %s)',
            $archived_count,
            $cutoff_date
        ));

        return $archived_count;
    }

    /**
     * Obtenir les statistiques d'archivage
     *
     * @return array Statistiques
     */
    public static function get_statistics() {
        global $wpdb;

        $table_archived = $wpdb->prefix . 'colis224_archived_parcels';

        // Vérifier si la table existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_archived'") !== $table_archived) {
            return array(
                'total_archived' => 0,
                'total_revenue' => 0,
                'by_month' => array(),
                'by_year' => array()
            );
        }

        // Total archivés
        $total_archived = $wpdb->get_var("SELECT COUNT(*) FROM $table_archived");

        // Revenu total des archives
        $total_revenue = $wpdb->get_var("SELECT COALESCE(SUM(total_amount), 0) FROM $table_archived");

        // Par mois (12 derniers mois)
        $by_month = $wpdb->get_results("
            SELECT
                DATE_FORMAT(archived_at, '%Y-%m') as month,
                COUNT(*) as count,
                COALESCE(SUM(total_amount), 0) as revenue
            FROM $table_archived
            WHERE archived_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month DESC
        ", ARRAY_A);

        // Par année
        $by_year = $wpdb->get_results("
            SELECT
                YEAR(archived_at) as year,
                COUNT(*) as count,
                COALESCE(SUM(total_amount), 0) as revenue
            FROM $table_archived
            GROUP BY year
            ORDER BY year DESC
        ", ARRAY_A);

        return array(
            'total_archived' => intval($total_archived),
            'total_revenue' => floatval($total_revenue),
            'by_month' => $by_month,
            'by_year' => $by_year
        );
    }

    /**
     * Obtenir la liste des colis archivés
     *
     * @param array $filters Filtres optionnels
     * @param int $limit Limite de résultats
     * @param int $offset Décalage
     * @return array Liste des colis archivés
     */
    public static function get_archived_parcels($filters = array(), $limit = 50, $offset = 0) {
        global $wpdb;

        $table_archived = $wpdb->prefix . 'colis224_archived_parcels';

        $where = "1=1";

        // Filtre par recherche
        if (!empty($filters['search'])) {
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where .= $wpdb->prepare(
                " AND (tracking_number LIKE %s OR recipient_name LIKE %s OR recipient_phone LIKE %s)",
                $search, $search, $search
            );
        }

        // Filtre par date d'archivage
        if (!empty($filters['date_from'])) {
            $where .= $wpdb->prepare(" AND archived_at >= %s", $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $where .= $wpdb->prepare(" AND archived_at <= %s", $filters['date_to'] . ' 23:59:59');
        }

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT *
            FROM $table_archived
            WHERE $where
            ORDER BY archived_at DESC
            LIMIT %d OFFSET %d
        ", $limit, $offset));

        return $results ? $results : array();
    }
}

// Initialiser
new Colis224_Archiving();
