<?php
/**
 * MODULE 5: Comptabilité (Revenus et Dépenses)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Accounting {

    public static function display_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-calculator"></span>
                Comptabilité
            </h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=colis224-accounting&tab=dashboard" class="nav-tab <?php echo $tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-chart-line"></span> Vue d'ensemble
                </a>
                <a href="?page=colis224-accounting&tab=revenues" class="nav-tab <?php echo $tab === 'revenues' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-money-alt"></span> Revenus
                </a>
                <a href="?page=colis224-accounting&tab=expenses" class="nav-tab <?php echo $tab === 'expenses' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-cart"></span> Dépenses
                </a>
            </nav>

            <?php
            if ($tab === 'dashboard') {
                self::display_dashboard();
            } elseif ($tab === 'revenues') {
                self::display_revenues();
            } else {
                self::display_expenses();
            }
            ?>
        </div>
        <?php
    }

    private static function display_dashboard() {
        global $wpdb;

        $table_revenues = $wpdb->prefix . 'colis224_revenues';
        $table_expenses = $wpdb->prefix . 'colis224_expenses';

        // Statistiques globales
        $total_revenues = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $table_revenues");
        $total_expenses = $wpdb->get_var("SELECT COALESCE(SUM(amount), 0) FROM $table_expenses");
        $profit = $total_revenues - $total_expenses;

        // Revenus du mois
        $month_revenues = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $table_revenues
            WHERE MONTH(revenue_date) = %d AND YEAR(revenue_date) = %d",
            date('n'), date('Y')
        ));

        // Dépenses du mois
        $month_expenses = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $table_expenses
            WHERE MONTH(expense_date) = %d AND YEAR(expense_date) = %d",
            date('n'), date('Y')
        ));

        // Dépenses par catégorie
        $expenses_by_category = $wpdb->get_results("
            SELECT ec.name as category, COALESCE(SUM(e.amount), 0) as total
            FROM {$wpdb->prefix}colis224_expense_categories ec
            LEFT JOIN $table_expenses e ON e.category_id = ec.id
            GROUP BY ec.id
            ORDER BY total DESC
        ");

        ?>
        <div class="colis224-dashboard-grid" style="margin-top: 20px;">
            <div class="colis224-card colis224-card-green">
                <div class="colis224-card-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="colis224-card-content">
                    <h3>Revenus Totaux</h3>
                    <p class="colis224-stat-value"><?php echo number_format($total_revenues, 0, ',', ' '); ?> GNF</p>
                    <p class="colis224-stat-label">
                        Ce mois: <?php echo number_format($month_revenues, 0, ',', ' '); ?> GNF
                    </p>
                </div>
            </div>

            <div class="colis224-card colis224-card-orange">
                <div class="colis224-card-icon">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <div class="colis224-card-content">
                    <h3>Dépenses Totales</h3>
                    <p class="colis224-stat-value"><?php echo number_format($total_expenses, 0, ',', ' '); ?> GNF</p>
                    <p class="colis224-stat-label">
                        Ce mois: <?php echo number_format($month_expenses, 0, ',', ' '); ?> GNF
                    </p>
                </div>
            </div>

            <div class="colis224-card <?php echo $profit >= 0 ? 'colis224-card-purple' : 'colis224-card-red'; ?>">
                <div class="colis224-card-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="colis224-card-content">
                    <h3>Bénéfice Net</h3>
                    <p class="colis224-stat-value"><?php echo number_format($profit, 0, ',', ' '); ?> GNF</p>
                    <p class="colis224-stat-label">
                        Revenus - Dépenses
                    </p>
                </div>
            </div>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-chart-bar"></span> Dépenses par Catégorie</h3>
            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th>Montant Total</th>
                        <th>Pourcentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses_by_category as $cat): ?>
                    <tr>
                        <td><?php echo esc_html($cat->category); ?></td>
                        <td><?php echo number_format($cat->total, 0, ',', ' '); ?> GNF</td>
                        <td>
                            <?php
                            $percentage = $total_expenses > 0 ? round(($cat->total / $total_expenses) * 100, 1) : 0;
                            echo $percentage . '%';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function display_revenues() {
        global $wpdb;

        if (isset($_POST['action']) && $_POST['action'] === 'add_revenue') {
            self::save_revenue();
        }

        $table_revenues = $wpdb->prefix . 'colis224_revenues';

        $revenues = $wpdb->get_results("
            SELECT r.*, p.tracking_number
            FROM $table_revenues r
            LEFT JOIN {$wpdb->prefix}colis224_parcels p ON r.parcel_id = p.id
            ORDER BY r.revenue_date DESC
            LIMIT 100
        ");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-plus-alt"></span> Ajouter un Revenu Divers</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_revenue">
                <?php wp_nonce_field('colis224_revenue_action', 'colis224_revenue_nonce'); ?>

                <div class="colis224-form-grid">
                    <div class="colis224-form-group">
                        <label>Montant *</label>
                        <input type="number" name="amount" step="0.01" min="0" required>
                    </div>

                    <div class="colis224-form-group">
                        <label>Devise *</label>
                        <select name="currency" required>
                            <option value="GNF">GNF (Franc Guinéen)</option>
                            <option value="USD">USD (Dollar US)</option>
                            <option value="EUR">EUR (Euro)</option>
                            <option value="XOF">CFA (Franc CFA)</option>
                            <option value="CNY">RMB (Yuan Chinois)</option>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Mode de Paiement</label>
                        <select name="payment_method">
                            <option value="Espèces">Espèces</option>
                            <option value="Virement">Virement</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Carte bancaire">Carte bancaire</option>
                            <option value="PayPal">PayPal</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Type de Revenu *</label>
                        <select name="source_type" id="revenue-source-type">
                            <option value="divers">Revenu Divers</option>
                            <option value="parcel">Colis</option>
                            <option value="service">Service</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Date</label>
                        <input type="date" name="revenue_date" value="<?php echo current_time('Y-m-d'); ?>" required>
                    </div>

                    <div class="colis224-form-group colis224-full-width">
                        <label>Description *</label>
                        <textarea name="description" rows="2" required placeholder="Ex: Commission transfert, Vente de services, etc."></textarea>
                    </div>

                    <div class="colis224-form-group colis224-full-width">
                        <button type="submit" class="button button-primary">Enregistrer Revenu</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-list-view"></span> Historique des Revenus</h3>
            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Description</th>
                        <th>Mode de Paiement</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($revenues)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Aucun revenu enregistré.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($revenues as $revenue): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($revenue->revenue_date)); ?></td>
                            <td>
                                <?php if ($revenue->source_type === 'parcel'): ?>
                                    <span class="colis224-badge">Colis: <?php echo esc_html($revenue->tracking_number); ?></span>
                                <?php else: ?>
                                    <span class="colis224-badge colis224-badge-blue">Autre</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($revenue->description ?: '-'); ?></td>
                            <td><?php echo esc_html($revenue->payment_method ?: '-'); ?></td>
                            <td><strong><?php echo number_format($revenue->amount, 0, ',', ' '); ?> <?php echo $revenue->currency; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function display_expenses() {
        global $wpdb;

        if (isset($_POST['action']) && $_POST['action'] === 'add_expense') {
            self::save_expense();
        }

        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}colis224_expense_categories ORDER BY name");

        $table_expenses = $wpdb->prefix . 'colis224_expenses';
        $expenses = $wpdb->get_results("
            SELECT e.*, ec.name as category_name
            FROM $table_expenses e
            LEFT JOIN {$wpdb->prefix}colis224_expense_categories ec ON e.category_id = ec.id
            ORDER BY e.expense_date DESC
            LIMIT 100
        ");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-plus-alt"></span> Ajouter une Dépense</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_expense">
                <?php wp_nonce_field('colis224_expense_action', 'colis224_expense_nonce'); ?>

                <div class="colis224-form-grid">
                    <div class="colis224-form-group">
                        <label>Catégorie *</label>
                        <select name="category_id" required>
                            <option value="">Sélectionner</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->id; ?>"><?php echo esc_html($cat->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Montant *</label>
                        <input type="number" name="amount" step="0.01" min="0" required>
                    </div>

                    <div class="colis224-form-group">
                        <label>Devise *</label>
                        <select name="currency" required>
                            <option value="GNF">GNF (Franc Guinéen)</option>
                            <option value="USD">USD (Dollar US)</option>
                            <option value="EUR">EUR (Euro)</option>
                            <option value="XOF">CFA (Franc CFA)</option>
                            <option value="CNY">RMB (Yuan Chinois)</option>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Mode de Paiement</label>
                        <select name="payment_method">
                            <option value="Espèces">Espèces</option>
                            <option value="Virement">Virement</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Carte bancaire">Carte bancaire</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Bénéficiaire</label>
                        <input type="text" name="beneficiary" placeholder="Nom du bénéficiaire">
                    </div>

                    <div class="colis224-form-group">
                        <label>Date *</label>
                        <input type="date" name="expense_date" value="<?php echo current_time('Y-m-d'); ?>" required>
                    </div>

                    <div class="colis224-form-group colis224-full-width">
                        <label>Description</label>
                        <textarea name="description" rows="2"></textarea>
                    </div>

                    <div class="colis224-form-group colis224-full-width">
                        <button type="submit" class="button button-primary">Enregistrer Dépense</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-list-view"></span> Historique des Dépenses</h3>
            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Catégorie</th>
                        <th>Description</th>
                        <th>Bénéficiaire</th>
                        <th>Mode de Paiement</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Aucune dépense enregistrée.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($expense->expense_date)); ?></td>
                            <td><span class="colis224-badge"><?php echo esc_html($expense->category_name); ?></span></td>
                            <td><?php echo esc_html($expense->description ?: '-'); ?></td>
                            <td><?php echo esc_html($expense->beneficiary ?: '-'); ?></td>
                            <td><?php echo esc_html($expense->payment_method ?: '-'); ?></td>
                            <td><strong><?php echo number_format($expense->amount, 0, ',', ' '); ?> <?php echo $expense->currency; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function save_revenue() {
        if (!isset($_POST['colis224_revenue_nonce']) || !wp_verify_nonce($_POST['colis224_revenue_nonce'], 'colis224_revenue_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_revenues';

        $data = array(
            'source_type' => isset($_POST['source_type']) ? sanitize_text_field($_POST['source_type']) : 'other',
            'amount' => floatval($_POST['amount']),
            'currency' => isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'GNF',
            'payment_method' => sanitize_text_field($_POST['payment_method']),
            'description' => sanitize_textarea_field($_POST['description']),
            'revenue_date' => sanitize_text_field($_POST['revenue_date'])
        );

        $wpdb->insert($table, $data);
        echo '<div class="notice notice-success is-dismissible"><p>Revenu enregistré avec succès!</p></div>';
    }

    private static function save_expense() {
        if (!isset($_POST['colis224_expense_nonce']) || !wp_verify_nonce($_POST['colis224_expense_nonce'], 'colis224_expense_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_expenses';

        $data = array(
            'category_id' => intval($_POST['category_id']),
            'amount' => floatval($_POST['amount']),
            'currency' => isset($_POST['currency']) ? sanitize_text_field($_POST['currency']) : 'GNF',
            'payment_method' => sanitize_text_field($_POST['payment_method']),
            'beneficiary' => sanitize_text_field($_POST['beneficiary']),
            'description' => sanitize_textarea_field($_POST['description']),
            'expense_date' => sanitize_text_field($_POST['expense_date']),
            'created_by' => get_current_user_id()
        );

        $wpdb->insert($table, $data);
        echo '<div class="notice notice-success is-dismissible"><p>Dépense enregistrée avec succès!</p></div>';
    }
}
