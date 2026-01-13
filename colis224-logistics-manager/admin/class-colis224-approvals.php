<?php
/**
 * Page Admin: Validation des Actions Agents
 * Version: 2.18.0
 *
 * Permet aux administrateurs de valider ou rejeter les actions soumises par les agents:
 * - Colis créés par les agents (status: pending)
 * - Départs créés par les agents (status: pending)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Approvals_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'), 25);
        add_action('admin_post_colis224_approve_action', array($this, 'handle_approve'));
        add_action('admin_post_colis224_reject_action', array($this, 'handle_reject'));
    }

    /**
     * Ajouter la page au menu admin
     */
    public function add_menu_page() {
        global $wpdb;

        // Compter les items en attente
        $pending_count = $wpdb->get_var("
            SELECT
                (SELECT COUNT(*) FROM {$wpdb->prefix}colis224_parcels WHERE approval_status = 'pending') +
                (SELECT COUNT(*) FROM {$wpdb->prefix}colis224_departures WHERE approval_status = 'pending')
        ");

        $menu_title = $pending_count > 0 ?
            sprintf('Validations <span class="awaiting-mod">%d</span>', $pending_count) :
            'Validations';

        add_submenu_page(
            'colis224-dashboard',
            'Validation des Actions',
            $menu_title,
            'manage_options',
            'colis224-approvals',
            array($this, 'render_page')
        );
    }

    /**
     * Rendu de la page
     */
    public function render_page() {
        global $wpdb;

        // Récupérer les colis en attente
        $pending_parcels = $wpdb->get_results("
            SELECT p.*, c.name as client_name, c.phone as client_phone, u.display_name as agent_name
            FROM {$wpdb->prefix}colis224_parcels p
            LEFT JOIN {$wpdb->prefix}colis224_clients c ON p.client_id = c.id
            LEFT JOIN {$wpdb->prefix}users u ON p.created_by = u.ID
            WHERE p.approval_status = 'pending'
            ORDER BY p.created_at DESC
        ");

        // Récupérer les départs en attente
        $pending_departures = $wpdb->get_results("
            SELECT d.*, u.display_name as agent_name
            FROM {$wpdb->prefix}colis224_departures d
            LEFT JOIN {$wpdb->prefix}users u ON d.created_by = u.ID
            WHERE d.approval_status = 'pending'
            ORDER BY d.created_at DESC
        ");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-yes-alt"></span> Validation des Actions Agents
            </h1>

            <?php if (isset($_GET['approved'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>✅ Action approuvée avec succès !</strong></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['rejected'])): ?>
                <div class="notice notice-warning is-dismissible">
                    <p><strong>⛔ Action rejetée.</strong></p>
                </div>
            <?php endif; ?>

            <hr class="wp-header-end">

            <?php if (empty($pending_parcels) && empty($pending_departures)): ?>
                <div class="notice notice-info inline">
                    <p>✅ Aucune action en attente de validation.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($pending_parcels)): ?>
                <h2>📦 Colis en Attente (<?php echo count($pending_parcels); ?>)</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>N° Suivi</th>
                            <th>Client</th>
                            <th>Destinataire</th>
                            <th>Poids</th>
                            <th>Agent</th>
                            <th>Date Création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_parcels as $parcel): ?>
                            <tr>
                                <td><strong><?php echo esc_html($parcel->tracking_number); ?></strong></td>
                                <td><?php echo esc_html($parcel->client_name); ?><br>
                                    <small><?php echo esc_html($parcel->client_phone); ?></small></td>
                                <td><?php echo esc_html($parcel->recipient_name); ?><br>
                                    <small><?php echo esc_html($parcel->recipient_phone); ?></small></td>
                                <td><?php echo esc_html($parcel->weight); ?> kg</td>
                                <td><span class="dashicons dashicons-admin-users"></span>
                                    <?php echo esc_html($parcel->agent_name ?: 'N/A'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($parcel->created_at)); ?></td>
                                <td>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                                        <?php wp_nonce_field('colis224_approve_parcel_' . $parcel->id); ?>
                                        <input type="hidden" name="action" value="colis224_approve_action">
                                        <input type="hidden" name="type" value="parcel">
                                        <input type="hidden" name="id" value="<?php echo $parcel->id; ?>">
                                        <button type="submit" class="button button-primary">
                                            <span class="dashicons dashicons-yes"></span> Approuver
                                        </button>
                                    </form>

                                    <button type="button" class="button button-secondary" onclick="showRejectModal('parcel', <?php echo $parcel->id; ?>)">
                                        <span class="dashicons dashicons-no"></span> Rejeter
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <br>
            <?php endif; ?>

            <?php if (!empty($pending_departures)): ?>
                <h2>✈️ Départs en Attente (<?php echo count($pending_departures); ?>)</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>N° Départ</th>
                            <th>Route</th>
                            <th>Date Départ</th>
                            <th>Transport</th>
                            <th>Agent</th>
                            <th>Date Création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_departures as $departure): ?>
                            <tr>
                                <td><strong><?php echo esc_html($departure->departure_number); ?></strong></td>
                                <td><?php echo esc_html($departure->departure_city); ?> → <?php echo esc_html($departure->arrival_city); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($departure->departure_date)); ?></td>
                                <td><?php echo $departure->transport_type === 'plane' ? '✈️ Avion' : '🚢 Bateau'; ?></td>
                                <td><span class="dashicons dashicons-admin-users"></span>
                                    <?php echo esc_html($departure->agent_name ?: 'N/A'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($departure->created_at)); ?></td>
                                <td>
                                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline-block;">
                                        <?php wp_nonce_field('colis224_approve_departure_' . $departure->id); ?>
                                        <input type="hidden" name="action" value="colis224_approve_action">
                                        <input type="hidden" name="type" value="departure">
                                        <input type="hidden" name="id" value="<?php echo $departure->id; ?>">
                                        <button type="submit" class="button button-primary">
                                            <span class="dashicons dashicons-yes"></span> Approuver
                                        </button>
                                    </form>

                                    <button type="button" class="button button-secondary" onclick="showRejectModal('departure', <?php echo $departure->id; ?>)">
                                        <span class="dashicons dashicons-no"></span> Rejeter
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Modal de Rejet -->
        <div id="reject-modal" style="display:none;">
            <div class="reject-modal-overlay" onclick="hideRejectModal()"></div>
            <div class="reject-modal-content">
                <h2>Rejeter l'Action</h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="reject-form">
                    <?php wp_nonce_field('colis224_reject_action'); ?>
                    <input type="hidden" name="action" value="colis224_reject_action">
                    <input type="hidden" name="type" id="reject-type">
                    <input type="hidden" name="id" id="reject-id">

                    <p><label for="reject-reason"><strong>Raison du rejet:</strong></label></p>
                    <textarea name="reason" id="reject-reason" rows="4" style="width: 100%;" required
                              placeholder="Expliquez pourquoi cette action est rejetée..."></textarea>

                    <p>
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-no"></span> Confirmer le Rejet
                        </button>
                        <button type="button" class="button" onclick="hideRejectModal()">Annuler</button>
                    </p>
                </form>
            </div>
        </div>

        <style>
        .reject-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 99999;
        }
        .reject-modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 100000;
            min-width: 500px;
            max-width: 600px;
        }
        .reject-modal-content h2 {
            margin-top: 0;
        }
        </style>

        <script>
        function showRejectModal(type, id) {
            document.getElementById('reject-type').value = type;
            document.getElementById('reject-id').value = id;
            document.getElementById('reject-modal').style.display = 'block';
        }

        function hideRejectModal() {
            document.getElementById('reject-modal').style.display = 'none';
            document.getElementById('reject-form').reset();
        }
        </script>
        <?php
    }

    /**
     * Gérer l'approbation
     */
    public function handle_approve() {
        $type = sanitize_text_field($_POST['type']);
        $id = intval($_POST['id']);

        // Vérifier le nonce
        check_admin_referer('colis224_approve_' . $type . '_' . $id);

        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }

        global $wpdb;
        $user = wp_get_current_user();

        // Mettre à jour le statut
        $table = $type === 'parcel' ? 'colis224_parcels' : 'colis224_departures';
        $wpdb->update(
            $wpdb->prefix . $table,
            array(
                'approval_status' => 'approved',
                'approved_by' => $user->ID,
                'approved_at' => current_time('mysql')
            ),
            array('id' => $id),
            array('%s', '%d', '%s'),
            array('%d')
        );

        // Logger l'action
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-db-migration.php';
        Colis224_DB_Migration::log_approval_action($type, $id, 'approved');

        // Rediriger avec message
        wp_redirect(add_query_arg('approved', '1', wp_get_referer()));
        exit;
    }

    /**
     * Gérer le rejet
     */
    public function handle_reject() {
        check_admin_referer('colis224_reject_action');

        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }

        $type = sanitize_text_field($_POST['type']);
        $id = intval($_POST['id']);
        $reason = sanitize_textarea_field($_POST['reason']);

        global $wpdb;
        $user = wp_get_current_user();

        // Mettre à jour le statut
        $table = $type === 'parcel' ? 'colis224_parcels' : 'colis224_departures';
        $wpdb->update(
            $wpdb->prefix . $table,
            array(
                'approval_status' => 'rejected',
                'approved_by' => $user->ID,
                'approved_at' => current_time('mysql'),
                'rejection_reason' => $reason
            ),
            array('id' => $id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );

        // Logger l'action
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-db-migration.php';
        Colis224_DB_Migration::log_approval_action($type, $id, 'rejected', $reason);

        // Rediriger avec message
        wp_redirect(add_query_arg('rejected', '1', wp_get_referer()));
        exit;
    }
}
