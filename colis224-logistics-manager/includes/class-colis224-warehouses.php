<?php
/**
 * MODULE 12: Gestion Multi-Entrepôts
 * Gestion des entrepôts, inventaire et transferts
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Warehouses {

    public function __construct() {
        // Hook pour ajouter automatiquement un colis à l'inventaire lors de sa création
        add_action('colis224_parcel_created', array($this, 'add_to_default_warehouse'), 10, 1);
    }

    /**
     * Créer un nouvel entrepôt
     */
    public static function create_warehouse($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_warehouses';

        // Vérifier si le code existe déjà
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE code = %s",
            $data['code']
        ));

        if ($exists) {
            return array('success' => false, 'message' => 'Ce code d\'entrepôt existe déjà');
        }

        $result = $wpdb->insert($table, array(
            'name' => sanitize_text_field($data['name']),
            'code' => sanitize_text_field($data['code']),
            'address' => sanitize_textarea_field($data['address'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'country_id' => intval($data['country_id'] ?? 0),
            'manager_name' => sanitize_text_field($data['manager_name'] ?? ''),
            'manager_phone' => sanitize_text_field($data['manager_phone'] ?? ''),
            'capacity' => intval($data['capacity'] ?? 0),
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ));

        if ($result) {
            return array('success' => true, 'message' => 'Entrepôt créé avec succès', 'id' => $wpdb->insert_id);
        }

        return array('success' => false, 'message' => 'Erreur lors de la création');
    }

    /**
     * Mettre à jour un entrepôt
     */
    public static function update_warehouse($warehouse_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_warehouses';

        $update_data = array(
            'name' => sanitize_text_field($data['name']),
            'address' => sanitize_textarea_field($data['address'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'country_id' => intval($data['country_id'] ?? 0),
            'manager_name' => sanitize_text_field($data['manager_name'] ?? ''),
            'manager_phone' => sanitize_text_field($data['manager_phone'] ?? ''),
            'capacity' => intval($data['capacity'] ?? 0),
            'is_active' => isset($data['is_active']) ? 1 : 0,
        );

        $result = $wpdb->update($table, $update_data, array('id' => $warehouse_id));

        if ($result !== false) {
            return array('success' => true, 'message' => 'Entrepôt mis à jour avec succès');
        }

        return array('success' => false, 'message' => 'Erreur lors de la mise à jour');
    }

    /**
     * Obtenir tous les entrepôts
     */
    public static function get_all_warehouses($active_only = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_warehouses';

        $where = $active_only ? 'WHERE is_active = 1' : '';

        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY name ASC");
    }

    /**
     * Obtenir un entrepôt par ID
     */
    public static function get_warehouse($warehouse_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_warehouses';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $warehouse_id
        ));
    }

    /**
     * Obtenir l'inventaire d'un entrepôt
     */
    public static function get_warehouse_inventory($warehouse_id, $status = null) {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'colis224_warehouse_inventory';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $where = "WHERE i.warehouse_id = %d";
        $params = array($warehouse_id);

        if ($status) {
            $where .= " AND i.status = %s";
            $params[] = $status;
        }

        $query = $wpdb->prepare(
            "SELECT i.*, p.tracking_number, p.recipient_name, p.recipient_phone,
                    p.weight, p.status as parcel_status
             FROM $table_inventory i
             INNER JOIN $table_parcels p ON i.parcel_id = p.id
             $where
             ORDER BY i.entry_date DESC",
            $params
        );

        return $wpdb->get_results($query);
    }

    /**
     * Ajouter un colis à l'inventaire d'un entrepôt
     */
    public static function add_to_inventory($warehouse_id, $parcel_id, $notes = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_warehouse_inventory';

        // Vérifier si le colis est déjà dans un entrepôt actif
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE parcel_id = %d AND status = 'in_stock' LIMIT 1",
            $parcel_id
        ));

        if ($existing) {
            return array('success' => false, 'message' => 'Ce colis est déjà dans un entrepôt');
        }

        $result = $wpdb->insert($table, array(
            'warehouse_id' => $warehouse_id,
            'parcel_id' => $parcel_id,
            'entry_date' => current_time('mysql'),
            'status' => 'in_stock',
            'notes' => sanitize_text_field($notes),
        ));

        if ($result) {
            return array('success' => true, 'message' => 'Colis ajouté à l\'inventaire');
        }

        return array('success' => false, 'message' => 'Erreur lors de l\'ajout');
    }

    /**
     * Marquer un colis comme sorti de l'entrepôt
     */
    public static function remove_from_inventory($parcel_id, $new_status = 'delivered') {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_warehouse_inventory';

        $result = $wpdb->update($table,
            array(
                'exit_date' => current_time('mysql'),
                'status' => $new_status,
            ),
            array(
                'parcel_id' => $parcel_id,
                'status' => 'in_stock'
            )
        );

        return $result !== false;
    }

    /**
     * Créer un transfert entre entrepôts
     */
    public static function create_transfer($from_warehouse_id, $to_warehouse_id, $parcel_id, $notes = '') {
        global $wpdb;

        $table_transfers = $wpdb->prefix . 'colis224_warehouse_transfers';
        $table_inventory = $wpdb->prefix . 'colis224_warehouse_inventory';

        // Vérifier que le colis est bien dans l'entrepôt source
        $in_stock = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_inventory
             WHERE warehouse_id = %d AND parcel_id = %d AND status = 'in_stock'",
            $from_warehouse_id,
            $parcel_id
        ));

        if (!$in_stock) {
            return array('success' => false, 'message' => 'Le colis n\'est pas dans l\'entrepôt source');
        }

        // Créer le transfert
        $result = $wpdb->insert($table_transfers, array(
            'from_warehouse_id' => $from_warehouse_id,
            'to_warehouse_id' => $to_warehouse_id,
            'parcel_id' => $parcel_id,
            'initiated_by' => get_current_user_id(),
            'transfer_date' => current_time('mysql', false),
            'status' => 'pending',
            'notes' => sanitize_text_field($notes),
        ));

        if ($result) {
            // Marquer le colis comme en transfert dans l'inventaire
            $wpdb->update($table_inventory,
                array('status' => 'in_transit'),
                array('id' => $in_stock->id)
            );

            return array('success' => true, 'message' => 'Transfert créé avec succès', 'transfer_id' => $wpdb->insert_id);
        }

        return array('success' => false, 'message' => 'Erreur lors de la création du transfert');
    }

    /**
     * Confirmer l'arrivée d'un transfert
     */
    public static function complete_transfer($transfer_id) {
        global $wpdb;

        $table_transfers = $wpdb->prefix . 'colis224_warehouse_transfers';
        $table_inventory = $wpdb->prefix . 'colis224_warehouse_inventory';

        // Obtenir le transfert
        $transfer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_transfers WHERE id = %d",
            $transfer_id
        ));

        if (!$transfer || $transfer->status !== 'pending') {
            return array('success' => false, 'message' => 'Transfert introuvable ou déjà traité');
        }

        // Mettre à jour le statut du transfert
        $wpdb->update($table_transfers,
            array(
                'status' => 'completed',
                'arrival_date' => current_time('mysql', false),
            ),
            array('id' => $transfer_id)
        );

        // Sortir le colis de l'entrepôt source
        $wpdb->update($table_inventory,
            array(
                'exit_date' => current_time('mysql'),
                'status' => 'transferred',
            ),
            array(
                'warehouse_id' => $transfer->from_warehouse_id,
                'parcel_id' => $transfer->parcel_id,
                'status' => 'in_transit'
            )
        );

        // Ajouter le colis à l'entrepôt destination
        $wpdb->insert($table_inventory, array(
            'warehouse_id' => $transfer->to_warehouse_id,
            'parcel_id' => $transfer->parcel_id,
            'entry_date' => current_time('mysql'),
            'status' => 'in_stock',
            'notes' => "Transféré de l'entrepôt #{$transfer->from_warehouse_id}",
        ));

        return array('success' => true, 'message' => 'Transfert complété avec succès');
    }

    /**
     * Annuler un transfert
     */
    public static function cancel_transfer($transfer_id) {
        global $wpdb;

        $table_transfers = $wpdb->prefix . 'colis224_warehouse_transfers';
        $table_inventory = $wpdb->prefix . 'colis224_warehouse_inventory';

        $transfer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_transfers WHERE id = %d",
            $transfer_id
        ));

        if (!$transfer || $transfer->status !== 'pending') {
            return array('success' => false, 'message' => 'Impossible d\'annuler ce transfert');
        }

        // Annuler le transfert
        $wpdb->update($table_transfers,
            array('status' => 'cancelled'),
            array('id' => $transfer_id)
        );

        // Remettre le colis en stock dans l'entrepôt source
        $wpdb->update($table_inventory,
            array('status' => 'in_stock'),
            array(
                'warehouse_id' => $transfer->from_warehouse_id,
                'parcel_id' => $transfer->parcel_id,
                'status' => 'in_transit'
            )
        );

        return array('success' => true, 'message' => 'Transfert annulé');
    }

    /**
     * Obtenir tous les transferts
     */
    public static function get_all_transfers($status = null) {
        global $wpdb;

        $table_transfers = $wpdb->prefix . 'colis224_warehouse_transfers';
        $table_warehouses = $wpdb->prefix . 'colis224_warehouses';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $where = $status ? $wpdb->prepare('WHERE t.status = %s', $status) : '';

        return $wpdb->get_results(
            "SELECT t.*,
                    wf.name as from_warehouse_name, wf.code as from_warehouse_code,
                    wt.name as to_warehouse_name, wt.code as to_warehouse_code,
                    p.tracking_number
             FROM $table_transfers t
             LEFT JOIN $table_warehouses wf ON t.from_warehouse_id = wf.id
             LEFT JOIN $table_warehouses wt ON t.to_warehouse_id = wt.id
             LEFT JOIN $table_parcels p ON t.parcel_id = p.id
             $where
             ORDER BY t.created_at DESC"
        );
    }

    /**
     * Obtenir les statistiques d'un entrepôt
     */
    public static function get_warehouse_stats($warehouse_id) {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'colis224_warehouse_inventory';

        $stats = array(
            'total_parcels' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_inventory WHERE warehouse_id = %d",
                $warehouse_id
            )),
            'in_stock' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_inventory WHERE warehouse_id = %d AND status = 'in_stock'",
                $warehouse_id
            )),
            'in_transit' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_inventory WHERE warehouse_id = %d AND status = 'in_transit'",
                $warehouse_id
            )),
            'delivered' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_inventory WHERE warehouse_id = %d AND status = 'delivered'",
                $warehouse_id
            )),
        );

        return $stats;
    }

    /**
     * Trouver dans quel entrepôt se trouve un colis
     */
    public static function find_parcel_location($parcel_id) {
        global $wpdb;

        $table_inventory = $wpdb->prefix . 'colis224_warehouse_inventory';
        $table_warehouses = $wpdb->prefix . 'colis224_warehouses';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, w.name as warehouse_name, w.code as warehouse_code, w.city
             FROM $table_inventory i
             INNER JOIN $table_warehouses w ON i.warehouse_id = w.id
             WHERE i.parcel_id = %d AND i.status = 'in_stock'
             ORDER BY i.entry_date DESC
             LIMIT 1",
            $parcel_id
        ));
    }

    /**
     * Ajouter automatiquement un nouveau colis à l'entrepôt par défaut
     */
    public function add_to_default_warehouse($parcel_id) {
        // Obtenir l'entrepôt par défaut (le premier actif)
        $warehouses = self::get_all_warehouses(true);

        if (empty($warehouses)) {
            return;
        }

        $default_warehouse = $warehouses[0];
        self::add_to_inventory($default_warehouse->id, $parcel_id, 'Ajout automatique lors de la création');
    }

    /**
     * Créer l'entrepôt par défaut
     */
    public static function create_default_warehouse() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_warehouses';

        // Vérifier s'il existe déjà un entrepôt
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($exists > 0) {
            return;
        }

        $wpdb->insert($table, array(
            'name' => 'Entrepôt Principal',
            'code' => 'WH-MAIN',
            'address' => '',
            'city' => 'Conakry',
            'manager_name' => '',
            'manager_phone' => '',
            'capacity' => 1000,
            'is_active' => 1,
        ));
    }
}

// Initialiser
new Colis224_Warehouses();
