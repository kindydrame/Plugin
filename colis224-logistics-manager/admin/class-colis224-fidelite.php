<?php
/**
 * Page Admin: Programme de Fidélité
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Loyalty_Admin {

    public static function display_page() {
        // Gestion des actions
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_reward':
                    self::handle_create_reward();
                    break;
                case 'add_points':
                    self::handle_add_points();
                    break;
                case 'update_program':
                    self::handle_update_program();
                    break;
            }
        }

        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'members';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-awards"></span>
                Programme de Fidélité
            </h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=colis224-loyalty&tab=members" class="nav-tab <?php echo $tab === 'members' ? 'nav-tab-active' : ''; ?>">
                    Membres
                </a>
                <a href="?page=colis224-loyalty&tab=rewards" class="nav-tab <?php echo $tab === 'rewards' ? 'nav-tab-active' : ''; ?>">
                    Récompenses
                </a>
                <a href="?page=colis224-loyalty&tab=settings" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    Paramètres
                </a>
                <a href="?page=colis224-loyalty&tab=stats" class="nav-tab <?php echo $tab === 'stats' ? 'nav-tab-active' : ''; ?>">
                    Statistiques
                </a>
            </nav>

            <?php
            if ($tab === 'members') {
                self::display_members_tab();
            } elseif ($tab === 'rewards') {
                self::display_rewards_tab();
            } elseif ($tab === 'settings') {
                self::display_settings_tab();
            } else {
                self::display_stats_tab();
            }
            ?>
        </div>
        <?php
    }

    private static function display_members_tab() {
        global $wpdb;

        $table_members = $wpdb->prefix . 'colis224_loyalty_members';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $members = $wpdb->get_results(
            "SELECT m.*, c.name as client_name, c.phone as client_phone
             FROM $table_members m
             INNER JOIN $table_clients c ON m.client_id = c.id
             ORDER BY m.total_points DESC"
        );

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-groups"></span> Membres du Programme</h3>

            <!-- Formulaire d'ajout de points manuel -->
            <form method="post" style="background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <input type="hidden" name="action" value="add_points">
                <?php wp_nonce_field('colis224_loyalty_action', 'colis224_loyalty_nonce'); ?>

                <h4>Ajouter/Retirer des Points Manuellement</h4>
                <div class="colis224-inline-form">
                    <select name="client_id" required>
                        <option value="">Sélectionner un client...</option>
                        <?php
                        $all_clients = $wpdb->get_results("SELECT * FROM $table_clients ORDER BY name");
                        foreach ($all_clients as $client):
                        ?>
                            <option value="<?php echo $client->id; ?>"><?php echo esc_html($client->name . ' - ' . $client->phone); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="points" placeholder="Points (- pour retirer)" required>
                    <input type="text" name="description" placeholder="Description" required>
                    <button type="submit" class="button button-primary">Enregistrer</button>
                </div>
            </form>

            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Téléphone</th>
                        <th>Tier</th>
                        <th>Points Totaux</th>
                        <th>Points Disponibles</th>
                        <th>Points Échangés</th>
                        <th>Date d'Inscription</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">Aucun membre inscrit</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($members as $member): ?>
                        <tr>
                            <td><strong><?php echo esc_html($member->client_name); ?></strong></td>
                            <td><?php echo esc_html($member->client_phone); ?></td>
                            <td>
                                <span class="colis224-badge colis224-badge-<?php
                                    echo $member->tier === 'Platinum' ? 'blue' :
                                        ($member->tier === 'Gold' ? 'green' :
                                        ($member->tier === 'Silver' ? 'gray' : 'gray'));
                                ?>">
                                    <?php echo esc_html($member->tier); ?>
                                </span>
                            </td>
                            <td><strong><?php echo number_format($member->total_points); ?></strong></td>
                            <td><?php echo number_format($member->available_points); ?></td>
                            <td><?php echo number_format($member->redeemed_points); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($member->join_date)); ?></td>
                            <td>
                                <a href="?page=colis224-clients&view=<?php echo $member->client_id; ?>" class="button button-small">Voir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function display_rewards_tab() {
        global $wpdb;

        $table_rewards = $wpdb->prefix . 'colis224_loyalty_rewards';
        $rewards = $wpdb->get_results("SELECT * FROM $table_rewards ORDER BY points_required ASC");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-star-filled"></span> Créer une Récompense</h3>

            <form method="post">
                <input type="hidden" name="action" value="create_reward">
                <?php wp_nonce_field('colis224_loyalty_action', 'colis224_loyalty_nonce'); ?>

                <div class="colis224-form-grid">
                    <div class="colis224-form-group">
                        <label>Nom de la Récompense *</label>
                        <input type="text" name="name" required>
                    </div>

                    <div class="colis224-form-group">
                        <label>Points Requis *</label>
                        <input type="number" name="points_required" required min="1">
                    </div>

                    <div class="colis224-form-group">
                        <label>Type de Récompense *</label>
                        <select name="reward_type" required>
                            <option value="discount_percentage">Réduction (%)</option>
                            <option value="discount_fixed">Réduction Fixe (GNF)</option>
                            <option value="free_shipping">Livraison Gratuite</option>
                            <option value="cash_back">Remboursement</option>
                            <option value="gift">Cadeau</option>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Valeur de la Récompense</label>
                        <input type="number" name="reward_value" step="0.01" min="0">
                        <small>Montant ou pourcentage selon le type</small>
                    </div>

                    <div class="colis224-form-group">
                        <label>Validité (jours)</label>
                        <input type="number" name="validity_days" value="30" min="1">
                    </div>

                    <div class="colis224-form-group">
                        <label>Stock Disponible</label>
                        <input type="number" name="stock_quantity" min="0">
                        <small>Laisser vide pour illimité</small>
                    </div>

                    <div class="colis224-form-group colis224-full-width">
                        <label>Description</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>

                    <div class="colis224-form-group colis224-full-width">
                        <label>
                            <input type="checkbox" name="is_active" value="1" checked>
                            Récompense Active
                        </label>
                    </div>
                </div>

                <button type="submit" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span> Créer la Récompense
                </button>
            </form>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-list-view"></span> Récompenses Disponibles</h3>

            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Points Requis</th>
                        <th>Valeur</th>
                        <th>Validité</th>
                        <th>Stock</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rewards)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Aucune récompense</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($rewards as $reward): ?>
                        <tr>
                            <td><strong><?php echo esc_html($reward->name); ?></strong><br>
                                <small><?php echo esc_html($reward->description); ?></small>
                            </td>
                            <td><?php
                                $types = array(
                                    'discount_percentage' => 'Réduction %',
                                    'discount_fixed' => 'Réduction Fixe',
                                    'free_shipping' => 'Livraison Gratuite',
                                    'cash_back' => 'Remboursement',
                                    'gift' => 'Cadeau',
                                );
                                echo $types[$reward->reward_type] ?? $reward->reward_type;
                            ?></td>
                            <td><strong><?php echo number_format($reward->points_required); ?></strong> pts</td>
                            <td><?php echo $reward->reward_value > 0 ? number_format($reward->reward_value, 0) : '-'; ?></td>
                            <td><?php echo $reward->validity_days; ?> jours</td>
                            <td><?php echo $reward->stock_quantity !== null ? $reward->stock_quantity : '∞'; ?></td>
                            <td>
                                <span class="colis224-badge <?php echo $reward->is_active ? 'colis224-badge-green' : 'colis224-badge-gray'; ?>">
                                    <?php echo $reward->is_active ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function display_settings_tab() {
        global $wpdb;

        $table_programs = $wpdb->prefix . 'colis224_loyalty_programs';
        $program = $wpdb->get_row("SELECT * FROM $table_programs WHERE is_active = 1 LIMIT 1");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-admin-generic"></span> Paramètres du Programme de Fidélité</h3>

            <form method="post">
                <input type="hidden" name="action" value="update_program">
                <?php wp_nonce_field('colis224_loyalty_action', 'colis224_loyalty_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label>Nom du Programme</label></th>
                        <td>
                            <input type="text" name="name" class="regular-text"
                                   value="<?php echo esc_attr($program->name ?? 'Programme Colis224'); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th><label>Description</label></th>
                        <td>
                            <textarea name="description" class="large-text" rows="3"><?php echo esc_textarea($program->description ?? ''); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th><label>Points par GNF</label></th>
                        <td>
                            <input type="number" name="points_per_gnf" step="0.0001" min="0"
                                   value="<?php echo esc_attr($program->points_per_gnf ?? 0.01); ?>">
                            <p class="description">Exemple: 0.01 = 1 point pour 100 GNF dépensés</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label>Points par Colis</label></th>
                        <td>
                            <input type="number" name="points_per_parcel" min="0"
                                   value="<?php echo esc_attr($program->points_per_parcel ?? 10); ?>">
                            <p class="description">Points fixes attribués à chaque envoi de colis</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label>Seuil Minimum</label></th>
                        <td>
                            <input type="number" name="min_points_threshold" min="0"
                                   value="<?php echo esc_attr($program->min_points_threshold ?? 0); ?>">
                            <p class="description">Points minimum pour commencer à gagner des points</p>
                        </td>
                    </tr>
                </table>

                <h4>Paliers de Tier</h4>
                <table class="widefat" style="margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th>Tier</th>
                            <th>Points Requis</th>
                            <th>Avantages</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>🥉 Bronze</strong></td>
                            <td>0 - 1,999</td>
                            <td>Tier par défaut</td>
                        </tr>
                        <tr>
                            <td><strong>🥈 Silver</strong></td>
                            <td>2,000 - 4,999</td>
                            <td>Priorité de traitement</td>
                        </tr>
                        <tr>
                            <td><strong>🥇 Gold</strong></td>
                            <td>5,000 - 9,999</td>
                            <td>Réductions exclusives</td>
                        </tr>
                        <tr>
                            <td><strong>💎 Platinum</strong></td>
                            <td>10,000+</td>
                            <td>Service VIP</td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="button button-primary">Enregistrer les Paramètres</button>
            </form>
        </div>
        <?php
    }

    private static function display_stats_tab() {
        $stats = Colis224_Loyalty::get_loyalty_stats();

        ?>
        <div class="colis224-dashboard-grid" style="margin-top: 20px;">
            <div class="colis224-card colis224-card-blue">
                <div class="colis224-card-icon"><span class="dashicons dashicons-groups"></span></div>
                <div class="colis224-card-content">
                    <h4>Total Membres</h4>
                    <div class="colis224-big-number"><?php echo number_format($stats['total_members']); ?></div>
                </div>
            </div>

            <div class="colis224-card colis224-card-green">
                <div class="colis224-card-icon"><span class="dashicons dashicons-star-filled"></span></div>
                <div class="colis224-card-content">
                    <h4>Points Distribués</h4>
                    <div class="colis224-big-number"><?php echo number_format($stats['total_points_awarded']); ?></div>
                </div>
            </div>

            <div class="colis224-card colis224-card-orange">
                <div class="colis224-card-icon"><span class="dashicons dashicons-awards"></span></div>
                <div class="colis224-card-content">
                    <h4>Points Échangés</h4>
                    <div class="colis224-big-number"><?php echo number_format($stats['total_points_redeemed']); ?></div>
                </div>
            </div>

            <div class="colis224-card colis224-card-purple">
                <div class="colis224-card-icon"><span class="dashicons dashicons-tickets"></span></div>
                <div class="colis224-card-content">
                    <h4>Récompenses Échangées</h4>
                    <div class="colis224-big-number"><?php echo number_format($stats['total_redemptions']); ?></div>
                </div>
            </div>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-chart-bar"></span> Répartition par Tier</h3>

            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Tier</th>
                        <th>Nombre de Membres</th>
                        <th>Pourcentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = $stats['total_members'];
                    foreach ($stats['members_by_tier'] as $tier => $count):
                        $percentage = $total > 0 ? ($count / $total * 100) : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo $tier; ?></strong></td>
                        <td><?php echo number_format($count); ?></td>
                        <td>
                            <div style="background: #ddd; border-radius: 4px; height: 20px; width: 100%; max-width: 300px;">
                                <div style="background: #2271b1; height: 100%; border-radius: 4px; width: <?php echo $percentage; ?>%; text-align: center; color: #fff; font-size: 11px; line-height: 20px;">
                                    <?php echo number_format($percentage, 1); ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function handle_create_reward() {
        if (!isset($_POST['colis224_loyalty_nonce']) || !wp_verify_nonce($_POST['colis224_loyalty_nonce'], 'colis224_loyalty_action')) {
            wp_die('Erreur de sécurité');
        }

        $result = Colis224_Loyalty::create_reward($_POST);

        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Récompense créée avec succès!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Erreur lors de la création de la récompense.</p></div>';
        }
    }

    private static function handle_add_points() {
        if (!isset($_POST['colis224_loyalty_nonce']) || !wp_verify_nonce($_POST['colis224_loyalty_nonce'], 'colis224_loyalty_action')) {
            wp_die('Erreur de sécurité');
        }

        $client_id = intval($_POST['client_id']);
        $points = intval($_POST['points']);
        $description = sanitize_text_field($_POST['description']);

        if ($points > 0) {
            $result = Colis224_Loyalty::add_points($client_id, $points, $description, 'manual');
        } else {
            $result = Colis224_Loyalty::redeem_points($client_id, abs($points), $description);
        }

        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Points mis à jour avec succès!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Erreur lors de la mise à jour des points.</p></div>';
        }
    }

    private static function handle_update_program() {
        if (!isset($_POST['colis224_loyalty_nonce']) || !wp_verify_nonce($_POST['colis224_loyalty_nonce'], 'colis224_loyalty_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_loyalty_programs';

        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'points_per_gnf' => floatval($_POST['points_per_gnf']),
            'points_per_parcel' => intval($_POST['points_per_parcel']),
            'min_points_threshold' => intval($_POST['min_points_threshold']),
        );

        $program = $wpdb->get_row("SELECT * FROM $table WHERE is_active = 1 LIMIT 1");

        if ($program) {
            $wpdb->update($table, $data, array('id' => $program->id));
        } else {
            $data['is_active'] = 1;
            $wpdb->insert($table, $data);
        }

        echo '<div class="notice notice-success is-dismissible"><p>Programme mis à jour avec succès!</p></div>';
    }
}
