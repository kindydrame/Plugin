<?php
/**
 * MODULE 6: Rapports et Statistiques
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Reports {

    public static function display_page() {
        global $wpdb;

        // Récupérer les filtres
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : date('Y-m-01');
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : date('Y-m-d');

        // Récupérer les statistiques
        $stats = self::get_statistics($date_from, $date_to);

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-chart-area"></span>
                Rapports et Statistiques
            </h1>

            <!-- Filtres de période -->
            <div class="colis224-card">
                <h3><span class="dashicons dashicons-calendar-alt"></span> Période de Rapport</h3>
                <form method="get" class="colis224-filters">
                    <input type="hidden" name="page" value="colis224-reports">

                    <label>
                        Du:
                        <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" required>
                    </label>

                    <label>
                        Au:
                        <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" required>
                    </label>

                    <button type="submit" class="button button-primary">Générer Rapport</button>

                    <a href="?page=colis224-reports&export=pdf&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                       class="button button-secondary">
                        <span class="dashicons dashicons-pdf"></span> Exporter PDF
                    </a>

                    <a href="?page=colis224-reports&export=csv&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"
                       class="button button-secondary">
                        <span class="dashicons dashicons-media-spreadsheet"></span> Exporter CSV
                    </a>
                </form>
            </div>

            <!-- Indicateurs clés -->
            <div class="colis224-dashboard-grid colis224-grid-4">
                <div class="colis224-card colis224-card-blue">
                    <h4><span class="dashicons dashicons-archive"></span> Colis Total</h4>
                    <p class="colis224-big-number"><?php echo $stats['total_parcels']; ?></p>
                </div>

                <div class="colis224-card colis224-card-green">
                    <h4><span class="dashicons dashicons-money-alt"></span> Revenus</h4>
                    <p class="colis224-big-number"><?php echo number_format($stats['total_revenues'], 0, ',', ' '); ?> GNF</p>
                </div>

                <div class="colis224-card colis224-card-orange">
                    <h4><span class="dashicons dashicons-cart"></span> Dépenses</h4>
                    <p class="colis224-big-number"><?php echo number_format($stats['total_expenses'], 0, ',', ' '); ?> GNF</p>
                </div>

                <div class="colis224-card colis224-card-purple">
                    <h4><span class="dashicons dashicons-chart-line"></span> Bénéfice</h4>
                    <p class="colis224-big-number"><?php echo number_format($stats['profit'], 0, ',', ' '); ?> GNF</p>
                </div>
            </div>

            <!-- Statistiques détaillées -->
            <div class="colis224-dashboard-row">
                <!-- Colis par statut -->
                <div class="colis224-card colis224-half-width">
                    <h3><span class="dashicons dashicons-chart-bar"></span> Colis par Statut</h3>
                    <table class="colis224-table">
                        <thead>
                            <tr>
                                <th>Statut</th>
                                <th>Nombre</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['parcels_by_status'] as $status => $count): ?>
                            <tr>
                                <td><span class="colis224-badge"><?php echo esc_html($status); ?></span></td>
                                <td><?php echo $count; ?></td>
                                <td>
                                    <?php
                                    $pct = $stats['total_parcels'] > 0
                                        ? round(($count / $stats['total_parcels']) * 100, 1)
                                        : 0;
                                    echo $pct . '%';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Colis par pays -->
                <div class="colis224-card colis224-half-width">
                    <h3><span class="dashicons dashicons-admin-site"></span> Colis par Pays d'Origine</h3>
                    <table class="colis224-table">
                        <thead>
                            <tr>
                                <th>Pays</th>
                                <th>Nombre</th>
                                <th>Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['parcels_by_country'] as $data): ?>
                            <tr>
                                <td><?php echo esc_html($data['country']); ?></td>
                                <td><?php echo $data['count']; ?></td>
                                <td><?php echo number_format($data['amount'], 0, ',', ' '); ?> GNF</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Colis par mode de transport -->
            <div class="colis224-card">
                <h3><span class="dashicons dashicons-airplane"></span> Colis par Mode de Transport</h3>
                <table class="colis224-table">
                    <thead>
                        <tr>
                            <th>Mode de Transport</th>
                            <th>Nombre de Colis</th>
                            <th>Montant Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['parcels_by_transport'] as $data): ?>
                        <tr>
                            <td><span class="colis224-badge"><?php echo esc_html($data['transport']); ?></span></td>
                            <td><?php echo $data['count']; ?></td>
                            <td><?php echo number_format($data['amount'], 0, ',', ' '); ?> GNF</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Dépenses par catégorie -->
            <div class="colis224-card">
                <h3><span class="dashicons dashicons-category"></span> Dépenses par Catégorie</h3>
                <table class="colis224-table">
                    <thead>
                        <tr>
                            <th>Catégorie</th>
                            <th>Montant</th>
                            <th>% du Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['expenses_by_category'] as $data): ?>
                        <tr>
                            <td><?php echo esc_html($data['category']); ?></td>
                            <td><?php echo number_format($data['amount'], 0, ',', ' '); ?> GNF</td>
                            <td>
                                <?php
                                $pct = $stats['total_expenses'] > 0
                                    ? round(($data['amount'] / $stats['total_expenses']) * 100, 1)
                                    : 0;
                                echo $pct . '%';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top clients -->
            <div class="colis224-card">
                <h3><span class="dashicons dashicons-star-filled"></span> Top 10 Clients</h3>
                <table class="colis224-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Nombre de Colis</th>
                            <th>Montant Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['top_clients'] as $data): ?>
                        <tr>
                            <td><strong><?php echo esc_html($data['client']); ?></strong></td>
                            <td><?php echo $data['count']; ?></td>
                            <td><?php echo number_format($data['amount'], 0, ',', ' '); ?> GNF</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php

        // Gestion de l'export
        if (isset($_GET['export'])) {
            if ($_GET['export'] === 'csv') {
                self::export_csv($stats, $date_from, $date_to);
            } elseif ($_GET['export'] === 'pdf') {
                self::export_pdf($stats, $date_from, $date_to);
            }
        }
    }

    private static function get_statistics($date_from, $date_to) {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_revenues = $wpdb->prefix . 'colis224_revenues';
        $table_expenses = $wpdb->prefix . 'colis224_expenses';

        // Colis total
        $total_parcels = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_parcels WHERE created_at BETWEEN %s AND %s",
            $date_from . ' 00:00:00',
            $date_to . ' 23:59:59'
        ));

        // Revenus
        $total_revenues = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $table_revenues
            WHERE revenue_date BETWEEN %s AND %s",
            $date_from, $date_to
        ));

        // Dépenses
        $total_expenses = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $table_expenses
            WHERE expense_date BETWEEN %s AND %s",
            $date_from, $date_to
        ));

        // Colis par statut
        $parcels_by_status_data = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count FROM $table_parcels
            WHERE created_at BETWEEN %s AND %s
            GROUP BY status",
            $date_from . ' 00:00:00',
            $date_to . ' 23:59:59'
        ), OBJECT_K);

        $parcels_by_status = array();
        foreach ($parcels_by_status_data as $status => $data) {
            $parcels_by_status[$status] = $data->count;
        }

        // Colis par pays
        $parcels_by_country = $wpdb->get_results($wpdb->prepare(
            "SELECT c.name as country, COUNT(p.id) as count, COALESCE(SUM(p.total_amount), 0) as amount
            FROM $table_parcels p
            LEFT JOIN {$wpdb->prefix}colis224_countries c ON p.origin_country_id = c.id
            WHERE p.created_at BETWEEN %s AND %s
            GROUP BY c.id
            ORDER BY count DESC",
            $date_from . ' 00:00:00',
            $date_to . ' 23:59:59'
        ), ARRAY_A);

        // Colis par mode de transport
        $parcels_by_transport = $wpdb->get_results($wpdb->prepare(
            "SELECT t.name as transport, COUNT(p.id) as count, COALESCE(SUM(p.total_amount), 0) as amount
            FROM $table_parcels p
            LEFT JOIN {$wpdb->prefix}colis224_transport_modes t ON p.transport_mode_id = t.id
            WHERE p.created_at BETWEEN %s AND %s
            GROUP BY t.id",
            $date_from . ' 00:00:00',
            $date_to . ' 23:59:59'
        ), ARRAY_A);

        // Dépenses par catégorie
        $expenses_by_category = $wpdb->get_results($wpdb->prepare(
            "SELECT ec.name as category, COALESCE(SUM(e.amount), 0) as amount
            FROM {$wpdb->prefix}colis224_expense_categories ec
            LEFT JOIN $table_expenses e ON e.category_id = ec.id
                AND e.expense_date BETWEEN %s AND %s
            GROUP BY ec.id
            HAVING amount > 0
            ORDER BY amount DESC",
            $date_from, $date_to
        ), ARRAY_A);

        // Top clients
        $top_clients = $wpdb->get_results($wpdb->prepare(
            "SELECT c.name as client, COUNT(p.id) as count, COALESCE(SUM(p.total_amount), 0) as amount
            FROM $table_parcels p
            LEFT JOIN {$wpdb->prefix}colis224_clients c ON p.client_id = c.id
            WHERE p.created_at BETWEEN %s AND %s AND c.name IS NOT NULL
            GROUP BY c.id
            ORDER BY amount DESC
            LIMIT 10",
            $date_from . ' 00:00:00',
            $date_to . ' 23:59:59'
        ), ARRAY_A);

        return array(
            'total_parcels' => intval($total_parcels),
            'total_revenues' => floatval($total_revenues),
            'total_expenses' => floatval($total_expenses),
            'profit' => floatval($total_revenues) - floatval($total_expenses),
            'parcels_by_status' => $parcels_by_status,
            'parcels_by_country' => $parcels_by_country,
            'parcels_by_transport' => $parcels_by_transport,
            'expenses_by_category' => $expenses_by_category,
            'top_clients' => $top_clients
        );
    }

    private static function export_csv($stats, $date_from, $date_to) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rapport-colis224-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        fputcsv($output, array('RAPPORT COLIS224'));
        fputcsv($output, array('Période: ' . $date_from . ' au ' . $date_to));
        fputcsv($output, array(''));

        fputcsv($output, array('INDICATEURS CLÉS'));
        fputcsv($output, array('Total Colis', $stats['total_parcels']));
        fputcsv($output, array('Revenus (GNF)', $stats['total_revenues']));
        fputcsv($output, array('Dépenses (GNF)', $stats['total_expenses']));
        fputcsv($output, array('Bénéfice (GNF)', $stats['profit']));

        fclose($output);
        exit;
    }

    private static function export_pdf($stats, $date_from, $date_to) {
        // Placeholder pour export PDF (nécessite une bibliothèque PDF comme TCPDF)
        echo '<div class="notice notice-warning"><p>Export PDF sera implémenté avec une bibliothèque PDF comme TCPDF.</p></div>';
    }
}
