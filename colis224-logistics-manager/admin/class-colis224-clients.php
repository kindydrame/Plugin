<?php
/**
 * MODULE 3: Gestion des Clients
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Clients {

    public static function display_page() {
        // Traitement des actions
        if (isset($_POST['action']) && $_POST['action'] === 'add_client') {
            self::save_client();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_client') {
            self::update_client();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            self::delete_client($_GET['id']);
        }

        // Déterminer la vue
        if (isset($_GET['action']) && $_GET['action'] === 'add') {
            self::display_form();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            self::display_form($_GET['id']);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
            self::display_details($_GET['id']);
        } else {
            self::display_list();
        }
    }

    private static function display_list() {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';

        $where = "1=1";
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (name LIKE %s OR phone LIKE %s OR email LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        if (!empty($type_filter)) {
            $where .= $wpdb->prepare(" AND type = %s", $type_filter);
        }

        $clients = $wpdb->get_results("SELECT * FROM $table_clients WHERE $where ORDER BY created_at DESC");

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-groups"></span>
                Gestion des Clients
                <a href="?page=colis224-clients&action=add" class="page-title-action">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Nouveau Client
                </a>
            </h1>

            <div class="colis224-filters">
                <form method="get">
                    <input type="hidden" name="page" value="colis224-clients">
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>"
                           placeholder="Rechercher..." class="colis224-search-input">
                    <select name="type">
                        <option value="">Tous les types</option>
                        <option value="Particulier" <?php selected($type_filter, 'Particulier'); ?>>Particulier</option>
                        <option value="Entreprise" <?php selected($type_filter, 'Entreprise'); ?>>Entreprise</option>
                    </select>
                    <button type="submit" class="button">Filtrer</button>
                    <a href="?page=colis224-clients" class="button">Réinitialiser</a>
                </form>
            </div>

            <div class="colis224-card">
                <table class="colis224-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Nom</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Solde</th>
                            <th>Remise</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">Aucun client trouvé.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><span class="colis224-badge"><?php echo esc_html($client->type); ?></span></td>
                                <td>
                                    <strong><?php echo esc_html($client->name); ?></strong>
                                    <?php if ($client->company_name): ?>
                                    <br><small><?php echo esc_html($client->company_name); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($client->phone); ?></td>
                                <td><?php echo esc_html($client->email ?: '-'); ?></td>
                                <td><?php echo number_format($client->balance, 0, ',', ' '); ?> GNF</td>
                                <td><?php echo esc_html($client->discount_rate); ?>%</td>
                                <td><?php echo date('d/m/Y', strtotime($client->created_at)); ?></td>
                                <td class="colis224-actions">
                                    <a href="?page=colis224-clients&action=view&id=<?php echo $client->id; ?>"
                                       class="button button-small" title="Voir les détails">
                                        <span class="dashicons dashicons-visibility"></span>
                                        Voir
                                    </a>
                                    <a href="?page=colis224-clients&action=edit&id=<?php echo $client->id; ?>"
                                       class="button button-small" title="Modifier ce client">
                                        <span class="dashicons dashicons-edit"></span>
                                        Modifier
                                    </a>
                                    <a href="?page=colis224-clients&action=delete&id=<?php echo $client->id; ?>"
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?');"
                                       title="Supprimer ce client">
                                        <span class="dashicons dashicons-trash"></span>
                                        Supprimer
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

    private static function display_form($client_id = null) {
        global $wpdb;
        $client = null;

        if ($client_id) {
            $table_clients = $wpdb->prefix . 'colis224_clients';
            $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_clients WHERE id = %d", $client_id));
        }

        $is_edit = ($client !== null);
        $title = $is_edit ? 'Modifier le Client' : 'Nouveau Client';
        $action = $is_edit ? 'edit_client' : 'add_client';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-groups"></span>
                <?php echo $title; ?>
                <a href="?page=colis224-clients" class="page-title-action">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Retour
                </a>
            </h1>

            <div class="colis224-card">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="client_id" value="<?php echo $client->id; ?>">
                    <?php endif; ?>
                    <?php wp_nonce_field('colis224_client_action', 'colis224_client_nonce'); ?>

                    <div class="colis224-form-grid">
                        <div class="colis224-form-group">
                            <label for="type">Type *</label>
                            <select name="type" id="type" required>
                                <option value="Particulier" <?php echo $is_edit && $client->type == 'Particulier' ? 'selected' : ''; ?>>Particulier</option>
                                <option value="Entreprise" <?php echo $is_edit && $client->type == 'Entreprise' ? 'selected' : ''; ?>>Entreprise</option>
                            </select>
                        </div>

                        <div class="colis224-form-group">
                            <label for="name">Nom / Contact *</label>
                            <input type="text" name="name" id="name"
                                   value="<?php echo $is_edit ? esc_attr($client->name) : ''; ?>" required>
                        </div>

                        <div class="colis224-form-group">
                            <label for="company_name">Raison Sociale</label>
                            <input type="text" name="company_name" id="company_name"
                                   value="<?php echo $is_edit ? esc_attr($client->company_name) : ''; ?>">
                        </div>

                        <div class="colis224-form-group">
                            <label for="phone">Téléphone *</label>
                            <input type="text" name="phone" id="phone"
                                   value="<?php echo $is_edit ? esc_attr($client->phone) : ''; ?>" required>
                        </div>

                        <div class="colis224-form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email"
                                   value="<?php echo $is_edit ? esc_attr($client->email) : ''; ?>">
                        </div>

                        <div class="colis224-form-group colis224-full-width">
                            <label for="address">Adresse</label>
                            <textarea name="address" id="address" rows="2"><?php echo $is_edit ? esc_textarea($client->address) : ''; ?></textarea>
                        </div>

                        <div class="colis224-form-group">
                            <label for="discount_rate">Remise (%) </label>
                            <input type="number" name="discount_rate" id="discount_rate" step="0.01" min="0" max="100"
                                   value="<?php echo $is_edit ? esc_attr($client->discount_rate) : '0'; ?>">
                        </div>

                        <div class="colis224-form-group">
                            <label for="balance">Solde (GNF)</label>
                            <input type="number" name="balance" id="balance" step="0.01"
                                   value="<?php echo $is_edit ? esc_attr($client->balance) : '0'; ?>">
                        </div>

                        <div class="colis224-form-group colis224-full-width">
                            <label for="notes">Notes Internes</label>
                            <textarea name="notes" id="notes" rows="3"><?php echo $is_edit ? esc_textarea($client->notes) : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="colis224-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-saved"></span>
                            <?php echo $is_edit ? 'Mettre à Jour' : 'Enregistrer'; ?>
                        </button>
                        <a href="?page=colis224-clients" class="button button-large">
                            <span class="dashicons dashicons-no-alt"></span>
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    private static function display_details($client_id) {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_clients WHERE id = %d", $client_id));

        if (!$client) {
            echo '<div class="notice notice-error"><p>Client introuvable.</p></div>';
            return;
        }

        // Récupérer les colis du client
        $parcels = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_parcels WHERE client_id = %d ORDER BY created_at DESC",
            $client_id
        ));

        $total_parcels = count($parcels);
        $total_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total_amount), 0) FROM $table_parcels WHERE client_id = %d",
            $client_id
        ));

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-groups"></span>
                Détails du Client: <?php echo esc_html($client->name); ?>
                <a href="?page=colis224-clients" class="page-title-action">
                    <span class="dashicons dashicons-arrow-left-alt"></span> Retour
                </a>
                <a href="?page=colis224-clients&action=edit&id=<?php echo $client->id; ?>" class="page-title-action">
                    <span class="dashicons dashicons-edit"></span> Modifier
                </a>
            </h1>

            <div class="colis224-dashboard-grid colis224-grid-3">
                <div class="colis224-card colis224-card-blue">
                    <h4><span class="dashicons dashicons-archive"></span> Total Colis</h4>
                    <p class="colis224-big-number"><?php echo $total_parcels; ?></p>
                </div>
                <div class="colis224-card colis224-card-green">
                    <h4><span class="dashicons dashicons-money-alt"></span> Revenu Total</h4>
                    <p class="colis224-big-number"><?php echo number_format($total_revenue, 0, ',', ' '); ?> GNF</p>
                </div>
                <div class="colis224-card colis224-card-orange">
                    <h4><span class="dashicons dashicons-chart-line"></span> Solde</h4>
                    <p class="colis224-big-number"><?php echo number_format($client->balance, 0, ',', ' '); ?> GNF</p>
                </div>
            </div>

            <div class="colis224-details-grid">
                <div class="colis224-card">
                    <h3><span class="dashicons dashicons-id"></span> Informations du Client</h3>
                    <table class="colis224-details-table">
                        <tr>
                            <th>Type:</th>
                            <td><span class="colis224-badge"><?php echo esc_html($client->type); ?></span></td>
                        </tr>
                        <tr>
                            <th>Nom:</th>
                            <td><?php echo esc_html($client->name); ?></td>
                        </tr>
                        <?php if ($client->company_name): ?>
                        <tr>
                            <th>Raison Sociale:</th>
                            <td><?php echo esc_html($client->company_name); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Téléphone:</th>
                            <td><strong><?php echo esc_html($client->phone); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo esc_html($client->email ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Adresse:</th>
                            <td><?php echo nl2br(esc_html($client->address ?: '-')); ?></td>
                        </tr>
                        <tr>
                            <th>Remise:</th>
                            <td><?php echo esc_html($client->discount_rate); ?>%</td>
                        </tr>
                        <tr>
                            <th>Date de création:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($client->created_at)); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if ($client->notes): ?>
            <div class="colis224-card">
                <h3><span class="dashicons dashicons-edit-large"></span> Notes</h3>
                <p><?php echo nl2br(esc_html($client->notes)); ?></p>
            </div>
            <?php endif; ?>

            <!-- Programme de Fidélité -->
            <?php self::display_loyalty_section($client_id); ?>

            <div class="colis224-card">
                <h3><span class="dashicons dashicons-list-view"></span> Historique des Colis</h3>
                <?php if (empty($parcels)): ?>
                    <p>Aucun colis pour ce client.</p>
                <?php else: ?>
                    <table class="colis224-table">
                        <thead>
                            <tr>
                                <th>N° Suivi</th>
                                <th>Destinataire</th>
                                <th>Statut</th>
                                <th>Montant</th>
                                <th>Paiement</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parcels as $parcel): ?>
                            <tr>
                                <td><strong><?php echo esc_html($parcel->tracking_number); ?></strong></td>
                                <td><?php echo esc_html($parcel->recipient_name); ?></td>
                                <td><span class="colis224-badge colis224-badge-<?php echo sanitize_title($parcel->status); ?>">
                                    <?php echo esc_html($parcel->status); ?>
                                </span></td>
                                <td><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></td>
                                <td><span class="colis224-badge colis224-badge-payment-<?php echo sanitize_title($parcel->payment_status); ?>">
                                    <?php echo esc_html($parcel->payment_status); ?>
                                </span></td>
                                <td><?php echo date('d/m/Y', strtotime($parcel->created_at)); ?></td>
                                <td>
                                    <a href="?page=colis224-parcels&action=view&id=<?php echo $parcel->id; ?>"
                                       class="button button-small">Voir</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private static function save_client() {
        if (!isset($_POST['colis224_client_nonce']) || !wp_verify_nonce($_POST['colis224_client_nonce'], 'colis224_client_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $data = array(
            'type' => sanitize_text_field($_POST['type']),
            'name' => sanitize_text_field($_POST['name']),
            'company_name' => sanitize_text_field($_POST['company_name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'address' => sanitize_textarea_field($_POST['address']),
            'discount_rate' => floatval($_POST['discount_rate']),
            'balance' => floatval($_POST['balance']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $result = $wpdb->insert($table_clients, $data);

        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Client enregistré avec succès!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Erreur lors de l\'enregistrement du client.</p></div>';
        }
    }

    private static function update_client() {
        if (!isset($_POST['colis224_client_nonce']) || !wp_verify_nonce($_POST['colis224_client_nonce'], 'colis224_client_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $client_id = intval($_POST['client_id']);

        $data = array(
            'type' => sanitize_text_field($_POST['type']),
            'name' => sanitize_text_field($_POST['name']),
            'company_name' => sanitize_text_field($_POST['company_name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'address' => sanitize_textarea_field($_POST['address']),
            'discount_rate' => floatval($_POST['discount_rate']),
            'balance' => floatval($_POST['balance']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $result = $wpdb->update($table_clients, $data, array('id' => $client_id));

        if ($result !== false) {
            echo '<div class="notice notice-success is-dismissible"><p>Client mis à jour avec succès!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Erreur lors de la mise à jour.</p></div>';
        }
    }

    private static function delete_client($client_id) {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $result = $wpdb->delete($table_clients, array('id' => intval($client_id)));

        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Client supprimé avec succès!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Erreur lors de la suppression.</p></div>';
        }
    }

    /**
     * Afficher la section fidélité du client
     */
    private static function display_loyalty_section($client_id) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-loyalty.php';
        $loyalty = Colis224_Loyalty::get_client_loyalty($client_id);

        if (!$loyalty) {
            // Client pas encore inscrit
            ?>
            <div class="colis224-card colis224-card-light">
                <h3><span class="dashicons dashicons-awards"></span> Programme de Fidélité</h3>
                <p style="text-align: center; padding: 20px; color: #666;">
                    Ce client n'est pas encore inscrit au programme de fidélité.<br>
                    <small>L'inscription sera automatique lors du prochain colis.</small>
                </p>
            </div>
            <?php
            return;
        }

        // Client inscrit - afficher les détails
        $tier_colors = array(
            'Platinum' => 'background: linear-gradient(135deg, #E5E4E2 0%, #C0C0C0 100%); color: #333;',
            'Gold' => 'background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: #333;',
            'Silver' => 'background: linear-gradient(135deg, #C0C0C0 0%, #A8A8A8 100%); color: #333;',
            'Bronze' => 'background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%); color: #fff;'
        );

        $tier_icons = array(
            'Platinum' => '💎',
            'Gold' => '🥇',
            'Silver' => '🥈',
            'Bronze' => '🥉'
        );

        // Récupérer les dernières transactions
        $transactions = Colis224_Loyalty::get_client_transactions($client_id, 10);

        ?>
        <div class="colis224-card">
            <h3><span class="dashicons dashicons-awards"></span> Programme de Fidélité</h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <!-- Tier -->
                <div style="<?php echo $tier_colors[$loyalty->tier]; ?> padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 48px;"><?php echo $tier_icons[$loyalty->tier]; ?></div>
                    <div style="font-size: 20px; font-weight: bold; margin-top: 10px;"><?php echo esc_html($loyalty->tier); ?></div>
                    <div style="font-size: 12px; margin-top: 5px;">Tier Actuel</div>
                </div>

                <!-- Points Disponibles -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; text-align: center; color: #fff;">
                    <div style="font-size: 14px; opacity: 0.9;">Points Disponibles</div>
                    <div style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo number_format($loyalty->available_points, 0, ',', ' '); ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">Prêts à échanger</div>
                </div>

                <!-- Points Totaux -->
                <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 20px; border-radius: 8px; text-align: center; color: #fff;">
                    <div style="font-size: 14px; opacity: 0.9;">Points Totaux</div>
                    <div style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo number_format($loyalty->total_points, 0, ',', ' '); ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">Cumulés depuis l'inscription</div>
                </div>

                <!-- Points Échangés -->
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 8px; text-align: center; color: #fff;">
                    <div style="font-size: 14px; opacity: 0.9;">Points Échangés</div>
                    <div style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo number_format($loyalty->redeemed_points, 0, ',', ' '); ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">Récompenses obtenues</div>
                </div>
            </div>

            <!-- Progression vers le prochain tier -->
            <?php
            $next_tier = '';
            $points_needed = 0;
            if ($loyalty->tier == 'Bronze') {
                $next_tier = 'Silver';
                $points_needed = 2000 - $loyalty->total_points;
            } elseif ($loyalty->tier == 'Silver') {
                $next_tier = 'Gold';
                $points_needed = 5000 - $loyalty->total_points;
            } elseif ($loyalty->tier == 'Gold') {
                $next_tier = 'Platinum';
                $points_needed = 10000 - $loyalty->total_points;
            }

            if ($points_needed > 0):
            ?>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span><strong>Progression vers <?php echo $tier_icons[$next_tier]; ?> <?php echo $next_tier; ?></strong></span>
                    <span style="color: #667eea; font-weight: bold;">+<?php echo number_format($points_needed, 0, ',', ' '); ?> points requis</span>
                </div>
                <?php
                $progress = ($loyalty->total_points / ($loyalty->total_points + $points_needed)) * 100;
                ?>
                <div style="background: #ddd; height: 20px; border-radius: 10px; overflow: hidden;">
                    <div style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); height: 100%; width: <?php echo $progress; ?>%; transition: width 0.3s;"></div>
                </div>
            </div>
            <?php else: ?>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; color: #fff;">
                <div style="font-size: 48px;">🏆</div>
                <div style="font-size: 18px; font-weight: bold; margin-top: 10px;">Tier Maximum Atteint!</div>
                <div style="font-size: 14px; margin-top: 5px; opacity: 0.9;">Ce client fait partie de nos membres Platinum</div>
            </div>
            <?php endif; ?>

            <!-- Dernières transactions -->
            <?php if (!empty($transactions)): ?>
            <h4 style="margin: 20px 0 10px 0;"><span class="dashicons dashicons-list-view"></span> Dernières Transactions</h4>
            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Points</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $trans): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($trans->created_at)); ?></td>
                        <td>
                            <?php if ($trans->transaction_type == 'earn'): ?>
                                <span class="colis224-badge colis224-badge-green">Gagné</span>
                            <?php else: ?>
                                <span class="colis224-badge colis224-badge-payment-non-paye">Échangé</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="color: <?php echo $trans->points > 0 ? '#00a32a' : '#d63638'; ?>;">
                                <?php echo $trans->points > 0 ? '+' : ''; ?><?php echo number_format($trans->points, 0, ',', ' '); ?>
                            </strong>
                        </td>
                        <td><?php echo esc_html($trans->description); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Informations du programme -->
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea;">
                <p style="margin: 0;"><strong>📋 Programme:</strong> <?php echo esc_html($loyalty->program_name); ?></p>
                <p style="margin: 10px 0 0 0;"><small>
                    <strong>Date d'inscription:</strong> <?php echo date('d/m/Y', strtotime($loyalty->join_date)); ?> |
                    <strong>Dernière activité:</strong> <?php echo date('d/m/Y', strtotime($loyalty->last_activity_date)); ?>
                </small></p>
            </div>
        </div>
        <?php
    }
}
