<?php
/**
 * Gestion des lots internationaux (conteneurs et vols)
 *
 * @package Colis224_Logistics
 * @version 2.14.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Batches {

    // Types de lots
    const TYPE_CONTAINER = 'container';
    const TYPE_FLIGHT = 'flight';

    // Statuts
    const STATUS_PREPARING = 'preparing';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_CUSTOMS = 'customs';
    const STATUS_CLEARED = 'cleared';
    const STATUS_CLOSED = 'closed';

    /**
     * Créer un nouveau lot
     *
     * @param array $data Données du lot
     * @return array Résultat
     */
    public static function create_batch($data) {
        global $wpdb;

        $table_batches = $wpdb->prefix . 'colis224_international_batches';
        $current_user = wp_get_current_user();

        // Générer un numéro de lot unique
        $batch_number = self::generate_batch_number($data['batch_type']);

        $insert_data = array(
            'batch_number' => $batch_number,
            'batch_type' => sanitize_text_field($data['batch_type']),
            'origin_country_id' => !empty($data['origin_country_id']) ? intval($data['origin_country_id']) : null,
            'destination_country_id' => !empty($data['destination_country_id']) ? intval($data['destination_country_id']) : null,
            'transport_company' => !empty($data['transport_company']) ? sanitize_text_field($data['transport_company']) : null,
            'container_number' => !empty($data['container_number']) ? sanitize_text_field($data['container_number']) : null,
            'flight_number' => !empty($data['flight_number']) ? sanitize_text_field($data['flight_number']) : null,
            'departure_date' => !empty($data['departure_date']) ? sanitize_text_field($data['departure_date']) : null,
            'estimated_arrival_date' => !empty($data['estimated_arrival_date']) ? sanitize_text_field($data['estimated_arrival_date']) : null,
            'status' => self::STATUS_PREPARING,
            'notes' => !empty($data['notes']) ? sanitize_textarea_field($data['notes']) : null,
            'created_by' => $current_user->ID,
            'created_at' => current_time('mysql')
        );

        $wpdb->insert($table_batches, $insert_data);
        $batch_id = $wpdb->insert_id;

        if ($batch_id) {
            return array(
                'success' => true,
                'message' => '✅ Lot créé avec succès.',
                'batch_id' => $batch_id,
                'batch_number' => $batch_number
            );
        }

        return array('success' => false, 'message' => '❌ Erreur lors de la création du lot.');
    }

    /**
     * Générer un numéro de lot unique
     *
     * @param string $type Type de lot
     * @return string
     */
    private static function generate_batch_number($type) {
        $prefix = ($type === self::TYPE_CONTAINER) ? 'CNT' : 'FLT';
        $date = date('ymd');
        $random = strtoupper(substr(md5(microtime()), 0, 4));

        return $prefix . '-' . $date . '-' . $random;
    }

    /**
     * Assigner des colis à un lot
     *
     * @param int $batch_id ID du lot
     * @param array $parcel_ids IDs des colis
     * @return array Résultat
     */
    public static function assign_parcels($batch_id, $parcel_ids) {
        global $wpdb;

        $table_batch_parcels = $wpdb->prefix . 'colis224_batch_parcels';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $current_user = wp_get_current_user();

        $wpdb->query('START TRANSACTION');

        try {
            $assigned_count = 0;

            foreach ($parcel_ids as $parcel_id) {
                // Vérifier si déjà assigné
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_batch_parcels WHERE batch_id = %d AND parcel_id = %d",
                    $batch_id,
                    $parcel_id
                ));

                if ($exists > 0) {
                    continue;
                }

                // Assigner
                $wpdb->insert($table_batch_parcels, array(
                    'batch_id' => $batch_id,
                    'parcel_id' => $parcel_id,
                    'assigned_at' => current_time('mysql'),
                    'assigned_by' => $current_user->ID
                ));

                // Mettre à jour le colis
                $wpdb->update(
                    $table_parcels,
                    array('batch_id' => $batch_id),
                    array('id' => $parcel_id),
                    array('%d'),
                    array('%d')
                );

                $assigned_count++;
            }

            // Mettre à jour les totaux du lot
            self::update_batch_totals($batch_id);

            $wpdb->query('COMMIT');

            return array(
                'success' => true,
                'message' => sprintf('✅ %d colis assigné(s) au lot.', $assigned_count),
                'assigned_count' => $assigned_count
            );

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');

            return array(
                'success' => false,
                'message' => '❌ Erreur lors de l\'assignation : ' . $e->getMessage()
            );
        }
    }

    /**
     * Retirer des colis d'un lot
     *
     * @param int $batch_id ID du lot
     * @param array $parcel_ids IDs des colis
     * @return array Résultat
     */
    public static function unassign_parcels($batch_id, $parcel_ids) {
        global $wpdb;

        $table_batch_parcels = $wpdb->prefix . 'colis224_batch_parcels';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $wpdb->query('START TRANSACTION');

        try {
            foreach ($parcel_ids as $parcel_id) {
                $wpdb->delete($table_batch_parcels, array(
                    'batch_id' => $batch_id,
                    'parcel_id' => $parcel_id
                ));

                $wpdb->update(
                    $table_parcels,
                    array('batch_id' => null),
                    array('id' => $parcel_id),
                    array('%d'),
                    array('%d')
                );
            }

            self::update_batch_totals($batch_id);

            $wpdb->query('COMMIT');

            return array(
                'success' => true,
                'message' => '✅ Colis retirés du lot.'
            );

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');

            return array(
                'success' => false,
                'message' => '❌ Erreur : ' . $e->getMessage()
            );
        }
    }

    /**
     * Mettre à jour les totaux d'un lot
     *
     * @param int $batch_id ID du lot
     */
    private static function update_batch_totals($batch_id) {
        global $wpdb;

        $table_batches = $wpdb->prefix . 'colis224_international_batches';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_batch_parcels = $wpdb->prefix . 'colis224_batch_parcels';

        // Calculer les totaux
        $totals = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(bp.parcel_id) as total_parcels,
                COALESCE(SUM(p.weight), 0) as total_weight,
                COALESCE(SUM(p.total_amount), 0) as total_value
            FROM $table_batch_parcels bp
            LEFT JOIN $table_parcels p ON bp.parcel_id = p.id
            WHERE bp.batch_id = %d
        ", $batch_id));

        // Mettre à jour
        $wpdb->update(
            $table_batches,
            array(
                'total_parcels' => $totals->total_parcels,
                'total_weight' => $totals->total_weight,
                'total_value' => $totals->total_value
            ),
            array('id' => $batch_id),
            array('%d', '%f', '%f'),
            array('%d')
        );
    }

    /**
     * Mettre à jour le statut d'un lot
     *
     * @param int $batch_id ID du lot
     * @param string $status Nouveau statut
     * @param array $extra_data Données supplémentaires
     * @return array Résultat
     */
    public static function update_status($batch_id, $status, $extra_data = array()) {
        global $wpdb;

        $table_batches = $wpdb->prefix . 'colis224_international_batches';

        $update_data = array('status' => $status);

        // Dates spécifiques selon le statut
        if ($status === self::STATUS_IN_TRANSIT && !empty($extra_data['departure_date'])) {
            $update_data['departure_date'] = $extra_data['departure_date'];
        }

        if ($status === self::STATUS_ARRIVED && !empty($extra_data['actual_arrival_date'])) {
            $update_data['actual_arrival_date'] = $extra_data['actual_arrival_date'];
            $update_data['arrival_date'] = $extra_data['actual_arrival_date'];
        }

        if ($status === self::STATUS_CLOSED) {
            $update_data['closed_at'] = current_time('mysql');
        }

        $wpdb->update($table_batches, $update_data, array('id' => $batch_id));

        return array('success' => true, 'message' => '✅ Statut mis à jour.');
    }

    /**
     * Obtenir un lot par ID
     *
     * @param int $batch_id ID du lot
     * @return object|null
     */
    public static function get_batch($batch_id) {
        global $wpdb;

        $table_batches = $wpdb->prefix . 'colis224_international_batches';

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_batches WHERE id = %d", $batch_id));
    }

    /**
     * Obtenir tous les lots avec filtres
     *
     * @param array $filters Filtres optionnels
     * @return array
     */
    public static function get_batches($filters = array()) {
        global $wpdb;

        $table_batches = $wpdb->prefix . 'colis224_international_batches';
        $where = "1=1";

        if (!empty($filters['status'])) {
            $where .= $wpdb->prepare(" AND status = %s", $filters['status']);
        }

        if (!empty($filters['type'])) {
            $where .= $wpdb->prepare(" AND batch_type = %s", $filters['type']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where .= $wpdb->prepare(" AND (batch_number LIKE %s OR container_number LIKE %s OR flight_number LIKE %s)", $search, $search, $search);
        }

        $results = $wpdb->get_results("
            SELECT *
            FROM $table_batches
            WHERE $where
            ORDER BY created_at DESC
        ");

        return $results ? $results : array();
    }

    /**
     * Obtenir les colis d'un lot
     *
     * @param int $batch_id ID du lot
     * @return array
     */
    public static function get_batch_parcels($batch_id) {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_batch_parcels = $wpdb->prefix . 'colis224_batch_parcels';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT p.*, c.name as client_name, bp.assigned_at
            FROM $table_batch_parcels bp
            LEFT JOIN $table_parcels p ON bp.parcel_id = p.id
            LEFT JOIN $table_clients c ON p.client_id = c.id
            WHERE bp.batch_id = %d
            ORDER BY bp.assigned_at DESC
        ", $batch_id));

        return $results ? $results : array();
    }

    /**
     * Obtenir les statistiques des lots
     *
     * @return array
     */
    public static function get_statistics() {
        global $wpdb;

        $table_batches = $wpdb->prefix . 'colis224_international_batches';

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_batches");
        $preparing = $wpdb->get_var("SELECT COUNT(*) FROM $table_batches WHERE status = 'preparing'");
        $in_transit = $wpdb->get_var("SELECT COUNT(*) FROM $table_batches WHERE status = 'in_transit'");
        $arrived = $wpdb->get_var("SELECT COUNT(*) FROM $table_batches WHERE status IN ('arrived', 'customs', 'cleared')");

        return array(
            'total' => intval($total),
            'preparing' => intval($preparing),
            'in_transit' => intval($in_transit),
            'arrived' => intval($arrived)
        );
    }
}
