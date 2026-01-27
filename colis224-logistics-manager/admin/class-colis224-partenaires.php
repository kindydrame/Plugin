<?php
/**
 * MODULE 2: Gestion des Partenaires
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Partners {

    public static function display_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-businessman"></span>
                Gestion des Partenaires
            </h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=colis224-partners&tab=list" class="nav-tab <?php echo $tab === 'list' ? 'nav-tab-active' : ''; ?>">
                    Liste des Partenaires
                </a>
                <a href="?page=colis224-partners&tab=transactions" class="nav-tab <?php echo $tab === 'transactions' ? 'nav-tab-active' : ''; ?>">
                    Transactions
                </a>
            </nav>

            <?php
            if ($tab === 'list') {
                self::display_partners_list();
            } elseif ($tab === 'transactions') {
                self::display_transactions();
            }
            ?>
        </div>
        <?php
    }

    private static function display_partners_list() {
        global $wpdb;

        if (isset($_POST['action']) && $_POST['action'] === 'save_partner') {
            self::save_partner();
        }

        $table_partners = $wpdb->prefix . 'colis224_partners';
        $partners = $wpdb->get_results("SELECT * FROM $table_partners ORDER BY created_at DESC");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-plus-alt"></span> Ajouter un Partenaire</h3>
            <form method="post" class="colis224-inline-form">
                <input type="hidden" name="action" value="save_partner">
                <?php wp_nonce_field('colis224_partner_action', 'colis224_partner_nonce'); ?>

                <div class="colis224-form-grid">
                    <input type="text" name="name" placeholder="Nom du partenaire *" required>
                    <input type="text" name="country" placeholder="Pays">
                    <input type="text" name="phone" placeholder="Téléphone">
                    <input type="email" name="email" placeholder="Email">
                    <select name="collaboration_type">
                        <option value="Bidirectionnel">Bidirectionnel</option>
                        <option value="Nous leur confions">Nous leur confions</option>
                        <option value="Ils nous confient">Ils nous confient</option>
                    </select>
                    <button type="submit" class="button button-primary">Ajouter</button>
                </div>
            </form>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-list-view"></span> Liste des Partenaires</h3>
            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Pays</th>
                        <th>Contact</th>
                        <th>Type de Collaboration</th>
                        <th>Solde</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($partners)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Aucun partenaire enregistré.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($partners as $partner): ?>
                        <tr>
                            <td><strong><?php echo esc_html($partner->name); ?></strong></td>
                            <td><?php echo esc_html($partner->country ?: '-'); ?></td>
                            <td>
                                <?php echo esc_html($partner->phone ?: '-'); ?><br>
                                <small><?php echo esc_html($partner->email ?: ''); ?></small>
                            </td>
                            <td><span class="colis224-badge"><?php echo esc_html($partner->collaboration_type); ?></span></td>
                            <td>
                                <?php
                                $color_class = $partner->balance < 0 ? 'text-red' : 'text-green';
                                echo '<span class="' . $color_class . '">';
                                echo number_format($partner->balance, 0, ',', ' ') . ' GNF';
                                echo '</span>';
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($partner->created_at)); ?></td>
                            <td class="colis224-actions">
                                <a href="?page=colis224-partners&tab=transactions&partner_id=<?php echo $partner->id; ?>"
                                   class="button button-small">
                                    <span class="dashicons dashicons-list-view"></span> Transactions
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function display_transactions() {
        global $wpdb;

        if (isset($_POST['action']) && $_POST['action'] === 'save_transaction') {
            self::save_transaction();
        }

        $partner_filter = isset($_GET['partner_id']) ? intval($_GET['partner_id']) : 0;
        $partners = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}colis224_partners ORDER BY name");

        $table_trans = $wpdb->prefix . 'colis224_partner_transactions';
        $table_partners = $wpdb->prefix . 'colis224_partners';

        $where = "1=1";
        if ($partner_filter > 0) {
            $where .= $wpdb->prepare(" AND pt.partner_id = %d", $partner_filter);
        }

        $transactions = $wpdb->get_results("
            SELECT pt.*, p.name as partner_name
            FROM $table_trans pt
            LEFT JOIN $table_partners p ON pt.partner_id = p.id
            WHERE $where
            ORDER BY pt.created_at DESC
        ");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-plus-alt"></span> Nouvelle Transaction Partenaire</h3>
            <form method="post">
                <input type="hidden" name="action" value="save_transaction">
                <?php wp_nonce_field('colis224_transaction_action', 'colis224_transaction_nonce'); ?>

                <div class="colis224-form-grid">
                    <div class="colis224-form-group">
                        <label>Partenaire *</label>
                        <select name="partner_id" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($partners as $p): ?>
                            <option value="<?php echo $p->id; ?>"><?php echo esc_html($p->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Type *</label>
                        <select name="transaction_type" required>
                            <option value="confié">Colis confié au partenaire</option>
                            <option value="reçu">Colis reçu du partenaire</option>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>N° Tracking Partenaire</label>
                        <input type="text" name="partner_tracking_number">
                    </div>

                    <div class="colis224-form-group">
                        <label>N° Tracking Interne</label>
                        <input type="text" name="internal_tracking_number">
                    </div>

                    <div class="colis224-form-group">
                        <label>Poids (kg)</label>
                        <input type="number" name="weight" step="0.01" min="0">
                    </div>

                    <div class="colis224-form-group">
                        <label>Nature</label>
                        <input type="text" name="nature" placeholder="Description du colis">
                    </div>

                    <div class="colis224-form-group">
                        <label>Montant (GNF) *</label>
                        <input type="number" name="amount" step="0.01" min="0" required>
                    </div>

                    <div class="colis224-form-group">
                        <label>Statut Paiement</label>
                        <select name="payment_status">
                            <option value="À payer">À payer</option>
                            <option value="Payé">Payé</option>
                            <option value="Partiel">Partiel</option>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Statut Colis</label>
                        <select name="parcel_status">
                            <option value="Déposé">Déposé</option>
                            <option value="Reçu par partenaire">Reçu par partenaire</option>
                            <option value="En transit">En transit</option>
                            <option value="Livré">Livré</option>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Date Transaction</label>
                        <input type="date" name="transaction_date" value="<?php echo current_time('Y-m-d'); ?>">
                    </div>

                    <div class="colis224-form-group colis224-full-width">
                        <label>Notes</label>
                        <textarea name="notes" rows="2"></textarea>
                    </div>
                </div>

                <button type="submit" class="button button-primary">Enregistrer Transaction</button>
            </form>
        </div>

        <div class="colis224-filters">
            <form method="get">
                <input type="hidden" name="page" value="colis224-partners">
                <input type="hidden" name="tab" value="transactions">
                <select name="partner_id">
                    <option value="0">Tous les partenaires</option>
                    <?php foreach ($partners as $p): ?>
                    <option value="<?php echo $p->id; ?>" <?php selected($partner_filter, $p->id); ?>>
                        <?php echo esc_html($p->name); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button">Filtrer</button>
            </form>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-list-view"></span> Historique des Transactions</h3>
            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Partenaire</th>
                        <th>Type</th>
                        <th>N° Partenaire</th>
                        <th>N° Interne</th>
                        <th>Montant</th>
                        <th>Paiement</th>
                        <th>Statut Colis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">Aucune transaction.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $trans): ?>
                        <tr>
                            <td><?php echo $trans->transaction_date ? date('d/m/Y', strtotime($trans->transaction_date)) : '-'; ?></td>
                            <td><strong><?php echo esc_html($trans->partner_name); ?></strong></td>
                            <td><span class="colis224-badge"><?php echo esc_html($trans->transaction_type); ?></span></td>
                            <td><?php echo esc_html($trans->partner_tracking_number ?: '-'); ?></td>
                            <td><?php echo esc_html($trans->internal_tracking_number ?: '-'); ?></td>
                            <td><?php echo number_format($trans->amount, 0, ',', ' '); ?> GNF</td>
                            <td><span class="colis224-badge colis224-badge-payment-<?php echo sanitize_title($trans->payment_status); ?>">
                                <?php echo esc_html($trans->payment_status); ?>
                            </span></td>
                            <td><span class="colis224-badge"><?php echo esc_html($trans->parcel_status); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function save_partner() {
        if (!isset($_POST['colis224_partner_nonce']) || !wp_verify_nonce($_POST['colis224_partner_nonce'], 'colis224_partner_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_partners';

        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'country' => sanitize_text_field($_POST['country']),
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'collaboration_type' => sanitize_text_field($_POST['collaboration_type'])
        );

        $wpdb->insert($table, $data);
        echo '<div class="notice notice-success is-dismissible"><p>Partenaire ajouté avec succès!</p></div>';
    }

    private static function save_transaction() {
        if (!isset($_POST['colis224_transaction_nonce']) || !wp_verify_nonce($_POST['colis224_transaction_nonce'], 'colis224_transaction_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table_trans = $wpdb->prefix . 'colis224_partner_transactions';
        $table_partners = $wpdb->prefix . 'colis224_partners';

        $partner_id = intval($_POST['partner_id']);
        $amount = floatval($_POST['amount']);
        $transaction_type = sanitize_text_field($_POST['transaction_type']);

        $data = array(
            'partner_id' => $partner_id,
            'transaction_type' => $transaction_type,
            'partner_tracking_number' => sanitize_text_field($_POST['partner_tracking_number']),
            'internal_tracking_number' => sanitize_text_field($_POST['internal_tracking_number']),
            'weight' => floatval($_POST['weight']),
            'nature' => sanitize_text_field($_POST['nature']),
            'amount' => $amount,
            'payment_status' => sanitize_text_field($_POST['payment_status']),
            'parcel_status' => sanitize_text_field($_POST['parcel_status']),
            'transaction_date' => sanitize_text_field($_POST['transaction_date']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $wpdb->insert($table_trans, $data);

        // Mise à jour du solde partenaire
        $balance_change = ($transaction_type === 'confié') ? -$amount : $amount;
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_partners SET balance = balance + %f WHERE id = %d",
            $balance_change,
            $partner_id
        ));

        echo '<div class="notice notice-success is-dismissible"><p>Transaction enregistrée avec succès!</p></div>';
    }
}
