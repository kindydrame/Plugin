<?php
/**
 * Interface de validation des colis (Admin)
 *
 * @package Colis224_Logistics
 * @subpackage Admin
 * @version 2.11.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Validation {

    /**
     * Constructeur - Enregistrer les hooks AJAX
     */
    public function __construct() {
        add_action('wp_ajax_colis224_approve_parcel', array($this, 'ajax_approve_parcel'));
        add_action('wp_ajax_colis224_reject_parcel', array($this, 'ajax_reject_parcel'));
    }

    /**
     * Afficher la page de validation
     */
    public static function display_page() {
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
        }

        // Obtenir les colis en attente
        $pending_parcels = self::get_pending_parcels();

        ?>
        <div class="wrap colis224-wrap">
            <h1>
                <span class="dashicons dashicons-yes-alt"></span>
                Validation des Colis
            </h1>

            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📋 Colis en Attente de Validation</h2>

                <?php if (empty($pending_parcels)): ?>
                    <div style="background: #d1f4e0; border: 1px solid #46b450; padding: 20px; border-radius: 5px; text-align: center; margin-top: 15px;">
                        <p style="margin: 0; font-size: 16px; color: #0a5d2a;">
                            ✅ <strong>Aucun colis en attente de validation</strong>
                        </p>
                        <p style="margin: 10px 0 0 0; color: #666;">
                            Tous les colis créés par les agents ont été traités.
                        </p>
                    </div>
                <?php else: ?>
                    <p>
                        <strong><?php echo count($pending_parcels); ?> colis</strong> créés par des agents nécessitent votre validation.
                    </p>

                    <table class="widefat" style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th>Numéro de Suivi</th>
                                <th>Destinataire</th>
                                <th>Montant Total</th>
                                <th>Créé par</th>
                                <th>Date de Création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_parcels as $parcel): ?>
                                <?php
                                $creator = get_userdata($parcel->created_by);
                                $creator_name = $creator ? $creator->display_name : 'Inconnu';
                                ?>
                                <tr id="parcel-row-<?php echo esc_attr($parcel->id); ?>">
                                    <td>
                                        <strong><?php echo esc_html($parcel->tracking_number); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo esc_html($parcel->recipient_name); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> GNF</strong>
                                    </td>
                                    <td>
                                        <span class="dashicons dashicons-admin-users" style="color: #2271b1;"></span>
                                        <?php echo esc_html($creator_name); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(mysql2date('d/m/Y H:i', $parcel->created_at)); ?>
                                    </td>
                                    <td>
                                        <button
                                            class="button button-primary validate-approve"
                                            data-parcel-id="<?php echo esc_attr($parcel->id); ?>"
                                            data-tracking="<?php echo esc_attr($parcel->tracking_number); ?>"
                                            style="margin-right: 5px;">
                                            <span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
                                            Approuver
                                        </button>
                                        <button
                                            class="button validate-reject"
                                            data-parcel-id="<?php echo esc_attr($parcel->id); ?>"
                                            data-tracking="<?php echo esc_attr($parcel->tracking_number); ?>"
                                            style="background: #dc3232; color: white; border-color: #dc3232;">
                                            <span class="dashicons dashicons-dismiss" style="vertical-align: middle;"></span>
                                            Rejeter
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="colis224-card" style="margin-top: 20px;">
                <h2>📊 Statistiques</h2>
                <?php
                global $wpdb;
                $table = $wpdb->prefix . 'colis224_parcels';

                $stats = array(
                    'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE validation_status = 'pending'"),
                    'approved' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE validation_status = 'approved'"),
                    'rejected' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE validation_status = 'rejected'")
                );
                ?>
                <table class="widefat">
                    <tr>
                        <td><strong>⏳ En attente</strong></td>
                        <td><span style="color: #f0a000; font-weight: bold; font-size: 18px;"><?php echo esc_html($stats['pending']); ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>✅ Approuvés</strong></td>
                        <td><span style="color: #46b450; font-weight: bold; font-size: 18px;"><?php echo esc_html($stats['approved']); ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>❌ Rejetés</strong></td>
                        <td><span style="color: #dc3232; font-weight: bold; font-size: 18px;"><?php echo esc_html($stats['rejected']); ?></span></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Modal de rejet -->
        <div id="reject-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999;">
            <div style="background: white; width: 500px; margin: 100px auto; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                <h2 style="margin: 0 0 20px 0; color: #dc3232;">
                    <span class="dashicons dashicons-dismiss" style="vertical-align: middle;"></span>
                    Rejeter le Colis
                </h2>
                <p>
                    <strong>Numéro de suivi:</strong> <span id="reject-tracking"></span>
                </p>
                <p>
                    <label for="reject-notes" style="display: block; margin-bottom: 5px;"><strong>Motif du rejet:</strong></label>
                    <textarea
                        id="reject-notes"
                        rows="4"
                        style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                        placeholder="Expliquez pourquoi ce colis est rejeté..."></textarea>
                </p>
                <div style="text-align: right; margin-top: 20px;">
                    <button id="reject-cancel" class="button" style="margin-right: 10px;">Annuler</button>
                    <button id="reject-confirm" class="button" style="background: #dc3232; color: white; border-color: #dc3232;">
                        Confirmer le Rejet
                    </button>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var currentParcelId = null;

            // Approuver un colis
            $('.validate-approve').on('click', function() {
                var parcelId = $(this).data('parcel-id');
                var tracking = $(this).data('tracking');
                var button = $(this);

                if (!confirm('Voulez-vous vraiment approuver le colis ' + tracking + ' ?')) {
                    return;
                }

                button.prop('disabled', true).text('Approbation...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'colis224_approve_parcel',
                        parcel_id: parcelId,
                        nonce: '<?php echo wp_create_nonce('colis224_validation_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#parcel-row-' + parcelId).fadeOut(400, function() {
                                $(this).remove();
                                if ($('tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                            alert('✅ Colis approuvé avec succès !');
                        } else {
                            alert('❌ Erreur: ' + response.data.message);
                            button.prop('disabled', false).html('<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Approuver');
                        }
                    },
                    error: function() {
                        alert('❌ Erreur de communication avec le serveur.');
                        button.prop('disabled', false).html('<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Approuver');
                    }
                });
            });

            // Ouvrir modal de rejet
            $('.validate-reject').on('click', function() {
                currentParcelId = $(this).data('parcel-id');
                var tracking = $(this).data('tracking');
                $('#reject-tracking').text(tracking);
                $('#reject-notes').val('');
                $('#reject-modal').fadeIn(200);
            });

            // Annuler le rejet
            $('#reject-cancel').on('click', function() {
                $('#reject-modal').fadeOut(200);
                currentParcelId = null;
            });

            // Confirmer le rejet
            $('#reject-confirm').on('click', function() {
                var notes = $('#reject-notes').val().trim();

                if (notes === '') {
                    alert('⚠️ Veuillez indiquer un motif de rejet.');
                    return;
                }

                var button = $(this);
                button.prop('disabled', true).text('Rejet en cours...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'colis224_reject_parcel',
                        parcel_id: currentParcelId,
                        notes: notes,
                        nonce: '<?php echo wp_create_nonce('colis224_validation_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#parcel-row-' + currentParcelId).fadeOut(400, function() {
                                $(this).remove();
                                if ($('tbody tr').length === 0) {
                                    location.reload();
                                }
                            });
                            $('#reject-modal').fadeOut(200);
                            alert('❌ Colis rejeté avec succès.');
                        } else {
                            alert('❌ Erreur: ' + response.data.message);
                            button.prop('disabled', false).text('Confirmer le Rejet');
                        }
                    },
                    error: function() {
                        alert('❌ Erreur de communication avec le serveur.');
                        button.prop('disabled', false).text('Confirmer le Rejet');
                    }
                });
            });

            // Fermer modal avec Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('#reject-modal').fadeOut(200);
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Obtenir la liste des colis en attente
     *
     * @return array
     */
    private static function get_pending_parcels() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_parcels';

        $results = $wpdb->get_results("
            SELECT *
            FROM $table
            WHERE validation_status = 'pending'
            ORDER BY created_at DESC
        ");

        return $results ? $results : array();
    }

    /**
     * AJAX: Approuver un colis
     */
    public function ajax_approve_parcel() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_validation_nonce')) {
            wp_send_json_error(array('message' => 'Nonce invalide.'));
            return;
        }

        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissions insuffisantes.'));
            return;
        }

        $parcel_id = isset($_POST['parcel_id']) ? intval($_POST['parcel_id']) : 0;

        if ($parcel_id === 0) {
            wp_send_json_error(array('message' => 'ID de colis invalide.'));
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_parcels';
        $current_user = wp_get_current_user();

        // Vérifier que le colis existe et est en attente
        $parcel = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $parcel_id));

        if (!$parcel) {
            wp_send_json_error(array('message' => 'Colis introuvable.'));
            return;
        }

        if ($parcel->validation_status !== 'pending') {
            wp_send_json_error(array('message' => 'Ce colis n\'est pas en attente de validation.'));
            return;
        }

        // Mettre à jour le statut
        $updated = $wpdb->update(
            $table,
            array(
                'validation_status' => 'approved',
                'validated_by' => $current_user->ID,
                'validated_at' => current_time('mysql')
            ),
            array('id' => $parcel_id),
            array('%s', '%d', '%s'),
            array('%d')
        );

        if ($updated === false) {
            wp_send_json_error(array('message' => 'Erreur lors de la mise à jour.'));
            return;
        }

        // Enregistrer dans l'historique
        if (class_exists('Colis224_Parcel_History')) {
            Colis224_Parcel_History::log_validation($parcel_id, 'approved', '');
        }

        wp_send_json_success(array('message' => 'Colis approuvé avec succès.'));
    }

    /**
     * AJAX: Rejeter un colis
     */
    public function ajax_reject_parcel() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_validation_nonce')) {
            wp_send_json_error(array('message' => 'Nonce invalide.'));
            return;
        }

        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissions insuffisantes.'));
            return;
        }

        $parcel_id = isset($_POST['parcel_id']) ? intval($_POST['parcel_id']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if ($parcel_id === 0) {
            wp_send_json_error(array('message' => 'ID de colis invalide.'));
            return;
        }

        if (empty($notes)) {
            wp_send_json_error(array('message' => 'Le motif de rejet est obligatoire.'));
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_parcels';
        $current_user = wp_get_current_user();

        // Vérifier que le colis existe et est en attente
        $parcel = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $parcel_id));

        if (!$parcel) {
            wp_send_json_error(array('message' => 'Colis introuvable.'));
            return;
        }

        if ($parcel->validation_status !== 'pending') {
            wp_send_json_error(array('message' => 'Ce colis n\'est pas en attente de validation.'));
            return;
        }

        // Mettre à jour le statut
        $updated = $wpdb->update(
            $table,
            array(
                'validation_status' => 'rejected',
                'validated_by' => $current_user->ID,
                'validated_at' => current_time('mysql'),
                'validation_notes' => $notes
            ),
            array('id' => $parcel_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );

        if ($updated === false) {
            wp_send_json_error(array('message' => 'Erreur lors de la mise à jour.'));
            return;
        }

        // Enregistrer dans l'historique
        if (class_exists('Colis224_Parcel_History')) {
            Colis224_Parcel_History::log_validation($parcel_id, 'rejected', $notes);
        }

        wp_send_json_success(array('message' => 'Colis rejeté avec succès.'));
    }
}

// Instancier la classe pour enregistrer les hooks AJAX
new Colis224_Validation();
