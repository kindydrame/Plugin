<?php
/**
 * MODULE 8: Service d'Achat
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Purchases {

    public static function display_page() {
        global $wpdb;

        if (isset($_POST['action']) && $_POST['action'] === 'save_purchase') {
            self::save_purchase();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'update_purchase') {
            self::update_purchase();
        }

        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            self::display_form($_GET['id']);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'add') {
            self::display_form();
        } else {
            self::display_list();
        }
    }

    private static function display_list() {
        global $wpdb;

        $table_purchases = $wpdb->prefix . 'colis224_purchase_requests';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        $where = "1=1";
        if (!empty($status_filter)) {
            $where .= $wpdb->prepare(" AND pr.status = %s", $status_filter);
        }

        $purchases = $wpdb->get_results("
            SELECT pr.*, c.name as client_name, c.phone as client_phone
            FROM $table_purchases pr
            LEFT JOIN $table_clients c ON pr.client_id = c.id
            WHERE $where
            ORDER BY pr.created_at DESC
        ");

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-cart"></span>
                Service d'Achat
                <a href="?page=colis224-purchases&action=add" class="page-title-action">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Nouvelle Demande
                </a>
            </h1>

            <!-- Filtres -->
            <div class="colis224-filters">
                <form method="get">
                    <input type="hidden" name="page" value="colis224-purchases">
                    <select name="status">
                        <option value="">Tous les statuts</option>
                        <option value="Demande reçue" <?php selected($status_filter, 'Demande reçue'); ?>>Demande reçue</option>
                        <option value="Devis envoyé" <?php selected($status_filter, 'Devis envoyé'); ?>>Devis envoyé</option>
                        <option value="Validé" <?php selected($status_filter, 'Validé'); ?>>Validé</option>
                        <option value="Acheté" <?php selected($status_filter, 'Acheté'); ?>>Acheté</option>
                        <option value="Expédié" <?php selected($status_filter, 'Expédié'); ?>>Expédié</option>
                        <option value="Livré" <?php selected($status_filter, 'Livré'); ?>>Livré</option>
                        <option value="Annulé" <?php selected($status_filter, 'Annulé'); ?>>Annulé</option>
                    </select>
                    <button type="submit" class="button">Filtrer</button>
                    <a href="?page=colis224-purchases" class="button">Réinitialiser</a>
                </form>
            </div>

            <!-- Liste -->
            <div class="colis224-card">
                <h3><span class="dashicons dashicons-list-view"></span> Demandes d'Achat</h3>
                <table class="colis224-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Article</th>
                            <th>Montant Estimé</th>
                            <th>Frais Service</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($purchases)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">Aucune demande d'achat.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($purchases as $purchase): ?>
                            <tr>
                                <td><strong>#<?php echo $purchase->id; ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($purchase->request_date)); ?></td>
                                <td>
                                    <?php echo esc_html($purchase->client_name); ?><br>
                                    <small><?php echo esc_html($purchase->client_phone); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $desc = esc_html($purchase->item_description);
                                    echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                                    ?>
                                </td>
                                <td><?php echo number_format($purchase->estimated_amount, 0, ',', ' '); ?> <?php echo $purchase->currency; ?></td>
                                <td><?php echo number_format($purchase->service_fee, 0, ',', ' '); ?> <?php echo $purchase->currency; ?></td>
                                <td><strong><?php echo number_format($purchase->total_amount, 0, ',', ' '); ?> <?php echo $purchase->currency; ?></strong></td>
                                <td>
                                    <span class="colis224-badge colis224-badge-<?php echo sanitize_title($purchase->status); ?>">
                                        <?php echo esc_html($purchase->status); ?>
                                    </span>
                                </td>
                                <td class="colis224-actions">
                                    <a href="?page=colis224-purchases&action=edit&id=<?php echo $purchase->id; ?>"
                                       class="button button-small" title="Modifier cette demande">
                                        <span class="dashicons dashicons-edit"></span>
                                        Modifier
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private static function display_form($purchase_id = null) {
        global $wpdb;

        $purchase = null;
        if ($purchase_id) {
            $table = $wpdb->prefix . 'colis224_purchase_requests';
            $purchase = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $purchase_id));
        }

        $clients = $wpdb->get_results("SELECT id, name, phone FROM {$wpdb->prefix}colis224_clients ORDER BY name");
        $parcels = $wpdb->get_results("SELECT id, tracking_number FROM {$wpdb->prefix}colis224_parcels ORDER BY created_at DESC");

        $is_edit = ($purchase !== null);
        $title = $is_edit ? 'Modifier la Demande #' . $purchase->id : 'Nouvelle Demande d\'Achat';
        $action = $is_edit ? 'update_purchase' : 'save_purchase';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-cart"></span>
                <?php echo $title; ?>
                <a href="?page=colis224-purchases" class="page-title-action">
                    <span class="dashicons dashicons-arrow-left-alt"></span> Retour
                </a>
            </h1>

            <div class="colis224-card">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="purchase_id" value="<?php echo $purchase->id; ?>">
                    <?php endif; ?>
                    <?php wp_nonce_field('colis224_purchase_action', 'colis224_purchase_nonce'); ?>

                    <div class="colis224-form-grid">
                        <div class="colis224-form-group">
                            <label for="client_id">Client *</label>
                            <select name="client_id" id="client_id" required>
                                <option value="">Sélectionner un client</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client->id; ?>"
                                        <?php echo $is_edit && $purchase->client_id == $client->id ? 'selected' : ''; ?>>
                                    <?php echo esc_html($client->name . ' - ' . $client->phone); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="colis224-form-group">
                            <label for="request_date">Date de Demande *</label>
                            <input type="date" name="request_date" id="request_date"
                                   value="<?php echo $is_edit ? esc_attr($purchase->request_date) : current_time('Y-m-d'); ?>" required>
                        </div>

                        <div class="colis224-form-group colis224-full-width">
                            <label for="item_description">Description de l'Article *</label>
                            <textarea name="item_description" id="item_description" rows="3" required><?php echo $is_edit ? esc_textarea($purchase->item_description) : ''; ?></textarea>
                        </div>

                        <div class="colis224-form-group colis224-full-width">
                            <label for="item_url">URL de l'Article (Lien)</label>
                            <input type="url" name="item_url" id="item_url" class="large-text"
                                   value="<?php echo $is_edit ? esc_attr($purchase->item_url) : ''; ?>"
                                   placeholder="https://...">
                        </div>

                        <div class="colis224-form-group">
                            <label for="estimated_amount">Montant Estimé (GNF)</label>
                            <input type="number" name="estimated_amount" id="estimated_amount" step="0.01" min="0"
                                   value="<?php echo $is_edit ? esc_attr($purchase->estimated_amount) : '0'; ?>">
                        </div>

                        <div class="colis224-form-group">
                            <label for="service_fee">Frais de Service (GNF)</label>
                            <input type="number" name="service_fee" id="service_fee" step="0.01" min="0"
                                   value="<?php echo $is_edit ? esc_attr($purchase->service_fee) : '0'; ?>">
                        </div>

                        <div class="colis224-form-group">
                            <label for="total_amount">Montant Total (GNF)</label>
                            <input type="number" name="total_amount" id="total_amount" step="0.01" min="0"
                                   value="<?php echo $is_edit ? esc_attr($purchase->total_amount) : '0'; ?>" readonly>
                        </div>

                        <div class="colis224-form-group">
                            <label for="currency">Devise</label>
                            <select name="currency" id="currency">
                                <option value="GNF" <?php echo $is_edit && $purchase->currency == 'GNF' ? 'selected' : ''; ?>>GNF</option>
                                <option value="EUR" <?php echo $is_edit && $purchase->currency == 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                <option value="USD" <?php echo $is_edit && $purchase->currency == 'USD' ? 'selected' : ''; ?>>USD</option>
                            </select>
                        </div>

                        <div class="colis224-form-group">
                            <label for="status">Statut *</label>
                            <select name="status" id="status" required>
                                <option value="Demande reçue" <?php echo $is_edit && $purchase->status == 'Demande reçue' ? 'selected' : ''; ?>>Demande reçue</option>
                                <option value="Devis envoyé" <?php echo $is_edit && $purchase->status == 'Devis envoyé' ? 'selected' : ''; ?>>Devis envoyé</option>
                                <option value="Validé" <?php echo $is_edit && $purchase->status == 'Validé' ? 'selected' : ''; ?>>Validé</option>
                                <option value="Acheté" <?php echo $is_edit && $purchase->status == 'Acheté' ? 'selected' : ''; ?>>Acheté</option>
                                <option value="Expédié" <?php echo $is_edit && $purchase->status == 'Expédié' ? 'selected' : ''; ?>>Expédié</option>
                                <option value="Livré" <?php echo $is_edit && $purchase->status == 'Livré' ? 'selected' : ''; ?>>Livré</option>
                                <option value="Annulé" <?php echo $is_edit && $purchase->status == 'Annulé' ? 'selected' : ''; ?>>Annulé</option>
                            </select>
                        </div>

                        <div class="colis224-form-group">
                            <label for="parcel_id">Colis Lié</label>
                            <select name="parcel_id" id="parcel_id">
                                <option value="">Aucun</option>
                                <?php foreach ($parcels as $parcel): ?>
                                <option value="<?php echo $parcel->id; ?>"
                                        <?php echo $is_edit && $purchase->parcel_id == $parcel->id ? 'selected' : ''; ?>>
                                    <?php echo esc_html($parcel->tracking_number); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="colis224-form-group colis224-full-width">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" rows="3"><?php echo $is_edit ? esc_textarea($purchase->notes) : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="colis224-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-saved"></span>
                            <?php echo $is_edit ? 'Mettre à Jour' : 'Enregistrer'; ?>
                        </button>
                        <a href="?page=colis224-purchases" class="button button-large">
                            <span class="dashicons dashicons-no-alt"></span>
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Calcul automatique du montant total
            function calculateTotal() {
                var estimated = parseFloat($('#estimated_amount').val()) || 0;
                var fee = parseFloat($('#service_fee').val()) || 0;
                var total = estimated + fee;
                $('#total_amount').val(total.toFixed(2));
            }

            $('#estimated_amount, #service_fee').on('input', calculateTotal);
        });
        </script>
        <?php
    }

    private static function save_purchase() {
        if (!isset($_POST['colis224_purchase_nonce']) || !wp_verify_nonce($_POST['colis224_purchase_nonce'], 'colis224_purchase_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_purchase_requests';

        $data = array(
            'client_id' => intval($_POST['client_id']),
            'item_description' => sanitize_textarea_field($_POST['item_description']),
            'item_url' => esc_url_raw($_POST['item_url']),
            'estimated_amount' => floatval($_POST['estimated_amount']),
            'service_fee' => floatval($_POST['service_fee']),
            'total_amount' => floatval($_POST['total_amount']),
            'currency' => sanitize_text_field($_POST['currency']),
            'status' => sanitize_text_field($_POST['status']),
            'parcel_id' => !empty($_POST['parcel_id']) ? intval($_POST['parcel_id']) : null,
            'notes' => sanitize_textarea_field($_POST['notes']),
            'request_date' => sanitize_text_field($_POST['request_date'])
        );

        $wpdb->insert($table, $data);
        echo '<div class="notice notice-success is-dismissible"><p>Demande d\'achat enregistrée avec succès!</p></div>';
    }

    private static function update_purchase() {
        if (!isset($_POST['colis224_purchase_nonce']) || !wp_verify_nonce($_POST['colis224_purchase_nonce'], 'colis224_purchase_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_purchase_requests';
        $purchase_id = intval($_POST['purchase_id']);

        $data = array(
            'client_id' => intval($_POST['client_id']),
            'item_description' => sanitize_textarea_field($_POST['item_description']),
            'item_url' => esc_url_raw($_POST['item_url']),
            'estimated_amount' => floatval($_POST['estimated_amount']),
            'service_fee' => floatval($_POST['service_fee']),
            'total_amount' => floatval($_POST['total_amount']),
            'currency' => sanitize_text_field($_POST['currency']),
            'status' => sanitize_text_field($_POST['status']),
            'parcel_id' => !empty($_POST['parcel_id']) ? intval($_POST['parcel_id']) : null,
            'notes' => sanitize_textarea_field($_POST['notes']),
            'request_date' => sanitize_text_field($_POST['request_date'])
        );

        $wpdb->update($table, $data, array('id' => $purchase_id));
        echo '<div class="notice notice-success is-dismissible"><p>Demande mise à jour avec succès!</p></div>';
    }
}
