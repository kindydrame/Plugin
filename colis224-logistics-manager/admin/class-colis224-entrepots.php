<?php
/**
 * Page Admin: Gestion des Entrepôts
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Warehouses_Admin {

    public static function display_page() {
        // Gestion des actions
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_warehouse':
                    self::handle_create_warehouse();
                    break;
                case 'update_warehouse':
                    self::handle_update_warehouse();
                    break;
                case 'add_to_inventory':
                    self::handle_add_to_inventory();
                    break;
                case 'create_transfer':
                    self::handle_create_transfer();
                    break;
                case 'complete_transfer':
                    self::handle_complete_transfer();
                    break;
                case 'cancel_transfer':
                    self::handle_cancel_transfer();
                    break;
            }
        }

        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'warehouses';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-store"></span>
                Gestion des Entrepôts
            </h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=colis224-warehouses&tab=warehouses" class="nav-tab <?php echo $tab === 'warehouses' ? 'nav-tab-active' : ''; ?>">
                    Entrepôts
                </a>
                <a href="?page=colis224-warehouses&tab=inventory" class="nav-tab <?php echo $tab === 'inventory' ? 'nav-tab-active' : ''; ?>">
                    Inventaire
                </a>
                <a href="?page=colis224-warehouses&tab=transfers" class="nav-tab <?php echo $tab === 'transfers' ? 'nav-tab-active' : ''; ?>">
                    Transferts
                </a>
            </nav>

            <?php
            if ($tab === 'warehouses') {
                self::display_warehouses_tab();
            } elseif ($tab === 'inventory') {
                self::display_inventory_tab();
            } else {
                self::display_transfers_tab();
            }
            ?>
        </div>
        <?php
    }

    private static function display_warehouses_tab() {
        global $wpdb;

        // Mode édition
        $edit_mode = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $warehouse_to_edit = null;

        if ($edit_mode) {
            $warehouse_to_edit = Colis224_Warehouses::get_warehouse($edit_mode);
        }

        $warehouses = Colis224_Warehouses::get_all_warehouses();
        $table_countries = $wpdb->prefix . 'colis224_countries';
        $countries = $wpdb->get_results("SELECT * FROM $table_countries ORDER BY name");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3>
                <span class="dashicons dashicons-<?php echo $edit_mode ? 'edit' : 'plus-alt'; ?>"></span>
                <?php echo $edit_mode ? 'Modifier l\'Entrepôt' : 'Créer un Nouvel Entrepôt'; ?>
            </h3>

            <form method="post">
                <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_warehouse' : 'create_warehouse'; ?>">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="warehouse_id" value="<?php echo $edit_mode; ?>">
                <?php endif; ?>
                <?php wp_nonce_field('colis224_warehouse_action', 'colis224_warehouse_nonce'); ?>

                <div class="colis224-form-grid">
                    <div class="colis224-form-group">
                        <label>Nom de l'Entrepôt *</label>
                        <input type="text" name="name" required
                               value="<?php echo $warehouse_to_edit ? esc_attr($warehouse_to_edit->name) : ''; ?>">
                    </div>

                    <div class="colis224-form-group">
                        <label>Code *</label>
                        <input type="text" name="code" required
                               <?php echo $edit_mode ? 'readonly' : ''; ?>
                               value="<?php echo $warehouse_to_edit ? esc_attr($warehouse_to_edit->code) : ''; ?>"
                               placeholder="WH-XXX">
                        <?php if ($edit_mode): ?>
                            <small>Le code ne peut pas être modifié</small>
                        <?php endif; ?>
                    </div>

                    <div class="colis224-form-group">
                        <label>Ville</label>
                        <input type="text" name="city"
                               value="<?php echo $warehouse_to_edit ? esc_attr($warehouse_to_edit->city) : ''; ?>">
                    </div>

                    <div class="colis224-form-group">
                        <label>Pays</label>
                        <select name="country_id">
                            <option value="">Sélectionner...</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?php echo $country->id; ?>"
                                        <?php selected($warehouse_to_edit ? $warehouse_to_edit->country_id : 0, $country->id); ?>>
                                    <?php echo esc_html($country->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Responsable</label>
                        <input type="text" name="manager_name"
                               value="<?php echo $warehouse_to_edit ? esc_attr($warehouse_to_edit->manager_name) : ''; ?>">
                    </div>

                    <div class="colis224-form-group">
                        <label>Téléphone Responsable</label>
                        <input type="tel" name="manager_phone"
                               value="<?php echo $warehouse_to_edit ? esc_attr($warehouse_to_edit->manager_phone) : ''; ?>">
                    </div>

                    <div class="colis224-form-group">
                        <label>Capacité (nombre de colis)</label>
                        <input type="number" name="capacity" min="0"
                               value="<?php echo $warehouse_to_edit ? esc_attr($warehouse_to_edit->capacity) : '1000'; ?>">
                    </div>

                    <div class="colis224-form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1"
                                   <?php checked($warehouse_to_edit ? $warehouse_to_edit->is_active : 1, 1); ?>>
                            Entrepôt Actif
                        </label>
                    </div>

                    <div class="colis224-form-group colis224-full-width">
                        <label>Adresse</label>
                        <textarea name="address" rows="3"><?php echo $warehouse_to_edit ? esc_textarea($warehouse_to_edit->address) : ''; ?></textarea>
                    </div>
                </div>

                <div class="colis224-form-actions">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-<?php echo $edit_mode ? 'yes' : 'plus-alt'; ?>"></span>
                        <?php echo $edit_mode ? 'Mettre à Jour' : 'Créer l\'Entrepôt'; ?>
                    </button>
                    <?php if ($edit_mode): ?>
                        <a href="?page=colis224-warehouses&tab=warehouses" class="button">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-list-view"></span> Liste des Entrepôts</h3>

            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Nom</th>
                        <th>Ville</th>
                        <th>Responsable</th>
                        <th>Capacité</th>
                        <th>En Stock</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($warehouses)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">Aucun entrepôt</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($warehouses as $warehouse):
                            $stats = Colis224_Warehouses::get_warehouse_stats($warehouse->id);
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($warehouse->code); ?></strong></td>
                            <td><?php echo esc_html($warehouse->name); ?></td>
                            <td><?php echo esc_html($warehouse->city); ?></td>
                            <td><?php echo esc_html($warehouse->manager_name); ?><br>
                                <small><?php echo esc_html($warehouse->manager_phone); ?></small>
                            </td>
                            <td><?php echo number_format($warehouse->capacity); ?></td>
                            <td><strong><?php echo number_format($stats['in_stock']); ?></strong></td>
                            <td>
                                <span class="colis224-badge <?php echo $warehouse->is_active ? 'colis224-badge-green' : 'colis224-badge-gray'; ?>">
                                    <?php echo $warehouse->is_active ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="colis224-actions">
                                    <a href="?page=colis224-warehouses&tab=inventory&warehouse_id=<?php echo $warehouse->id; ?>" class="button button-small">
                                        Inventaire
                                    </a>
                                    <a href="?page=colis224-warehouses&tab=warehouses&edit=<?php echo $warehouse->id; ?>" class="button button-small">
                                        Modifier
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function display_inventory_tab() {
        global $wpdb;

        $selected_warehouse = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : 0;
        $warehouses = Colis224_Warehouses::get_all_warehouses(true);

        if ($selected_warehouse) {
            $inventory = Colis224_Warehouses::get_warehouse_inventory($selected_warehouse, 'in_stock');
            $warehouse = Colis224_Warehouses::get_warehouse($selected_warehouse);
        } else {
            $inventory = array();
            $warehouse = null;
        }

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $parcels = $wpdb->get_results("SELECT id, tracking_number FROM $table_parcels ORDER BY id DESC LIMIT 100");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-plus-alt"></span> Ajouter un Colis à l'Inventaire</h3>

            <form method="post">
                <input type="hidden" name="action" value="add_to_inventory">
                <?php wp_nonce_field('colis224_warehouse_action', 'colis224_warehouse_nonce'); ?>

                <div class="colis224-inline-form">
                    <select name="warehouse_id" required>
                        <option value="">Sélectionner un entrepôt...</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?php echo $wh->id; ?>"><?php echo esc_html($wh->name . ' (' . $wh->code . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="parcel_id" required>
                        <option value="">Sélectionner un colis...</option>
                        <?php foreach ($parcels as $parcel): ?>
                            <option value="<?php echo $parcel->id; ?>"><?php echo esc_html($parcel->tracking_number); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" name="notes" placeholder="Notes (optionnel)">
                    <button type="submit" class="button button-primary">Ajouter</button>
                </div>
            </form>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-archive"></span> Inventaire</h3>

            <div class="colis224-filters">
                <select onchange="window.location.href='?page=colis224-warehouses&tab=inventory&warehouse_id=' + this.value">
                    <option value="">Tous les entrepôts</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?php echo $wh->id; ?>" <?php selected($selected_warehouse, $wh->id); ?>>
                            <?php echo esc_html($wh->name . ' (' . $wh->code . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($warehouse): ?>
                <div style="background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 4px;">
                    <strong><?php echo esc_html($warehouse->name); ?></strong> - <?php echo esc_html($warehouse->city); ?><br>
                    <small>En stock: <strong><?php echo count($inventory); ?></strong> colis</small>
                </div>
            <?php endif; ?>

            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>N° Suivi</th>
                        <th>Destinataire</th>
                        <th>Téléphone</th>
                        <th>Poids</th>
                        <th>Statut Colis</th>
                        <th>Date d'Entrée</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">
                            <?php echo $selected_warehouse ? 'Aucun colis en stock' : 'Sélectionnez un entrepôt'; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                        <tr>
                            <td><strong><?php echo esc_html($item->tracking_number); ?></strong></td>
                            <td><?php echo esc_html($item->recipient_name); ?></td>
                            <td><?php echo esc_html($item->recipient_phone); ?></td>
                            <td><?php echo number_format($item->weight, 2); ?> kg</td>
                            <td>
                                <span class="colis224-badge colis224-badge-<?php
                                    echo $item->parcel_status === 'Livré' ? 'livre' :
                                        ($item->parcel_status === 'En transit' ? 'en-transit' : 'en-attente');
                                ?>">
                                    <?php echo esc_html($item->parcel_status); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($item->entry_date)); ?></td>
                            <td><small><?php echo esc_html($item->notes); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function display_transfers_tab() {
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null;
        $transfers = Colis224_Warehouses::get_all_transfers($status_filter);
        $warehouses = Colis224_Warehouses::get_all_warehouses(true);

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-randomize"></span> Créer un Transfert</h3>

            <form method="post">
                <input type="hidden" name="action" value="create_transfer">
                <?php wp_nonce_field('colis224_warehouse_action', 'colis224_warehouse_nonce'); ?>

                <div class="colis224-inline-form">
                    <select name="from_warehouse_id" required>
                        <option value="">De l'entrepôt...</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?php echo $wh->id; ?>"><?php echo esc_html($wh->name); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="to_warehouse_id" required>
                        <option value="">Vers l'entrepôt...</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?php echo $wh->id; ?>"><?php echo esc_html($wh->name); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" name="tracking_number" placeholder="N° Suivi du colis" required>
                    <input type="text" name="notes" placeholder="Notes (optionnel)">
                    <button type="submit" class="button button-primary">Créer le Transfert</button>
                </div>
            </form>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-list-view"></span> Liste des Transferts</h3>

            <div class="colis224-filters">
                <select onchange="window.location.href='?page=colis224-warehouses&tab=transfers&status=' + this.value">
                    <option value="">Tous les statuts</option>
                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>En attente</option>
                    <option value="in_transit" <?php selected($status_filter, 'in_transit'); ?>>En transit</option>
                    <option value="completed" <?php selected($status_filter, 'completed'); ?>>Terminé</option>
                    <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Annulé</option>
                </select>
            </div>

            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>N° Suivi</th>
                        <th>De</th>
                        <th>Vers</th>
                        <th>Date Transfert</th>
                        <th>Date Arrivée</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transfers)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Aucun transfert</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($transfers as $transfer): ?>
                        <tr>
                            <td><strong><?php echo esc_html($transfer->tracking_number); ?></strong></td>
                            <td><?php echo esc_html($transfer->from_warehouse_name); ?><br>
                                <small><?php echo esc_html($transfer->from_warehouse_code); ?></small>
                            </td>
                            <td><?php echo esc_html($transfer->to_warehouse_name); ?><br>
                                <small><?php echo esc_html($transfer->to_warehouse_code); ?></small>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($transfer->transfer_date)); ?></td>
                            <td><?php echo $transfer->arrival_date ? date('d/m/Y', strtotime($transfer->arrival_date)) : '-'; ?></td>
                            <td>
                                <span class="colis224-badge colis224-badge-<?php
                                    echo $transfer->status === 'completed' ? 'green' :
                                        ($transfer->status === 'cancelled' ? 'gray' :
                                        ($transfer->status === 'in_transit' ? 'blue' : 'gray'));
                                ?>">
                                    <?php
                                    $statuses = array(
                                        'pending' => 'En attente',
                                        'in_transit' => 'En transit',
                                        'completed' => 'Terminé',
                                        'cancelled' => 'Annulé',
                                    );
                                    echo $statuses[$transfer->status] ?? $transfer->status;
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($transfer->status === 'pending'): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="complete_transfer">
                                        <input type="hidden" name="transfer_id" value="<?php echo $transfer->id; ?>">
                                        <?php wp_nonce_field('colis224_warehouse_action', 'colis224_warehouse_nonce'); ?>
                                        <button type="submit" class="button button-small button-primary">Confirmer</button>
                                    </form>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="cancel_transfer">
                                        <input type="hidden" name="transfer_id" value="<?php echo $transfer->id; ?>">
                                        <?php wp_nonce_field('colis224_warehouse_action', 'colis224_warehouse_nonce'); ?>
                                        <button type="submit" class="button button-small">Annuler</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function handle_create_warehouse() {
        if (!isset($_POST['colis224_warehouse_nonce']) || !wp_verify_nonce($_POST['colis224_warehouse_nonce'], 'colis224_warehouse_action')) {
            wp_die('Erreur de sécurité');
        }

        $result = Colis224_Warehouses::create_warehouse($_POST);

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }

    private static function handle_update_warehouse() {
        if (!isset($_POST['colis224_warehouse_nonce']) || !wp_verify_nonce($_POST['colis224_warehouse_nonce'], 'colis224_warehouse_action')) {
            wp_die('Erreur de sécurité');
        }

        $warehouse_id = intval($_POST['warehouse_id']);
        $result = Colis224_Warehouses::update_warehouse($warehouse_id, $_POST);

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }

    private static function handle_add_to_inventory() {
        if (!isset($_POST['colis224_warehouse_nonce']) || !wp_verify_nonce($_POST['colis224_warehouse_nonce'], 'colis224_warehouse_action')) {
            wp_die('Erreur de sécurité');
        }

        $result = Colis224_Warehouses::add_to_inventory(
            intval($_POST['warehouse_id']),
            intval($_POST['parcel_id']),
            sanitize_text_field($_POST['notes'] ?? '')
        );

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }

    private static function handle_create_transfer() {
        if (!isset($_POST['colis224_warehouse_nonce']) || !wp_verify_nonce($_POST['colis224_warehouse_nonce'], 'colis224_warehouse_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Trouver le colis par tracking number
        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_parcels WHERE tracking_number = %s",
            sanitize_text_field($_POST['tracking_number'])
        ));

        if (!$parcel) {
            echo '<div class="notice notice-error is-dismissible"><p>Colis introuvable</p></div>';
            return;
        }

        $result = Colis224_Warehouses::create_transfer(
            intval($_POST['from_warehouse_id']),
            intval($_POST['to_warehouse_id']),
            $parcel->id,
            sanitize_text_field($_POST['notes'] ?? '')
        );

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }

    private static function handle_complete_transfer() {
        if (!isset($_POST['colis224_warehouse_nonce']) || !wp_verify_nonce($_POST['colis224_warehouse_nonce'], 'colis224_warehouse_action')) {
            wp_die('Erreur de sécurité');
        }

        $result = Colis224_Warehouses::complete_transfer(intval($_POST['transfer_id']));

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }

    private static function handle_cancel_transfer() {
        if (!isset($_POST['colis224_warehouse_nonce']) || !wp_verify_nonce($_POST['colis224_warehouse_nonce'], 'colis224_warehouse_action')) {
            wp_die('Erreur de sécurité');
        }

        $result = Colis224_Warehouses::cancel_transfer(intval($_POST['transfer_id']));

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }
}
