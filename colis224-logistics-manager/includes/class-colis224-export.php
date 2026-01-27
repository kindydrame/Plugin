<?php
/**
 * Système d'Export Avancé (PDF, Excel, CSV)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Export {

    public function __construct() {
        add_action('wp_ajax_colis224_export_parcels_csv', array($this, 'export_parcels_csv'));
        add_action('wp_ajax_colis224_export_parcels_excel', array($this, 'export_parcels_excel'));
        add_action('wp_ajax_colis224_export_accounting_pdf', array($this, 'export_accounting_pdf'));
        add_action('wp_ajax_colis224_export_statistics_pdf', array($this, 'export_statistics_pdf'));
    }

    /**
     * Exporter les colis en CSV
     */
    public function export_parcels_csv() {
        check_ajax_referer('colis224_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_parcels';

        // Filtres
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

        $where = '1=1';
        if ($status) {
            $where .= $wpdb->prepare(' AND status = %s', $status);
        }
        if ($date_from) {
            $where .= $wpdb->prepare(' AND created_at >= %s', $date_from);
        }
        if ($date_to) {
            $where .= $wpdb->prepare(' AND created_at <= %s', $date_to . ' 23:59:59');
        }

        $parcels = $wpdb->get_results("SELECT * FROM $table WHERE $where ORDER BY created_at DESC", ARRAY_A);

        // Générer le CSV
        $filename = 'colis_export_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM UTF-8 pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // En-têtes
        $headers = array(
            'ID', 'N° Suivi', 'Client', 'Expéditeur', 'Destinataire', 'Téléphone',
            'Adresse', 'Poids', 'Montant', 'Devise', 'Statut', 'Paiement',
            'Date Création', 'Date Livraison'
        );
        fputcsv($output, $headers, ';');

        // Données
        foreach ($parcels as $parcel) {
            $row = array(
                $parcel['id'],
                $parcel['tracking_number'],
                $parcel['client_id'],
                $parcel['sender_name'],
                $parcel['recipient_name'],
                $parcel['recipient_phone'],
                $parcel['recipient_address'],
                $parcel['weight'],
                $parcel['total_amount'],
                $parcel['currency'],
                $parcel['status'],
                $parcel['payment_status'],
                $parcel['created_at'],
                $parcel['delivery_date']
            );
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Exporter les colis en Excel (format HTML qui s'ouvre dans Excel)
     */
    public function export_parcels_excel() {
        check_ajax_referer('colis224_nonce', 'nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_parcels';

        $parcels = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 1000");

        $filename = 'colis_export_' . date('Y-m-d_His') . '.xls';

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"><style>
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #4472C4; color: white; font-weight: bold; padding: 8px; border: 1px solid #000; }
            td { padding: 6px; border: 1px solid #ddd; }
            tr:nth-child(even) { background-color: #f2f2f2; }
        </style></head><body>';

        echo '<table border="1">';
        echo '<thead><tr>';
        echo '<th>ID</th><th>N° Suivi</th><th>Destinataire</th><th>Téléphone</th>';
        echo '<th>Poids (kg)</th><th>Montant</th><th>Statut</th><th>Paiement</th><th>Date</th>';
        echo '</tr></thead><tbody>';

        foreach ($parcels as $parcel) {
            echo '<tr>';
            echo '<td>' . $parcel->id . '</td>';
            echo '<td>' . $parcel->tracking_number . '</td>';
            echo '<td>' . $parcel->recipient_name . '</td>';
            echo '<td>' . $parcel->recipient_phone . '</td>';
            echo '<td>' . $parcel->weight . '</td>';
            echo '<td>' . number_format($parcel->total_amount, 0, ',', ' ') . ' ' . $parcel->currency . '</td>';
            echo '<td>' . $parcel->status . '</td>';
            echo '<td>' . $parcel->payment_status . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($parcel->created_at)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></body></html>';
        exit;
    }

    /**
     * Exporter le rapport comptable en PDF
     */
    public function export_accounting_pdf() {
        check_ajax_referer('colis224_nonce', 'nonce');

        global $wpdb;

        $month = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : date('Y-m');

        // Statistiques du mois
        $table_revenues = $wpdb->prefix . 'colis224_revenues';
        $table_expenses = $wpdb->prefix . 'colis224_expenses';

        $revenues = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $table_revenues WHERE DATE_FORMAT(revenue_date, '%%Y-%%m') = %s",
            $month
        ));

        $expenses = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM $table_expenses WHERE DATE_FORMAT(expense_date, '%%Y-%%m') = %s",
            $month
        ));

        $profit = $revenues - $expenses;

        // Générer le HTML du PDF
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Rapport Comptable - <?php echo $month; ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                h1 { color: #667eea; text-align: center; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #667eea; padding-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background: #667eea; color: white; padding: 12px; text-align: left; }
                td { padding: 10px; border-bottom: 1px solid #ddd; }
                .total { font-size: 18px; font-weight: bold; background: #f0f0f0; }
                .profit { color: green; font-weight: bold; }
                .loss { color: red; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo get_option('colis224_company_name', 'COLIS224'); ?></h1>
                <h2>Rapport Comptable - <?php echo date('F Y', strtotime($month . '-01')); ?></h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th style="text-align: right;">Montant (GNF)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Total Revenus</strong></td>
                        <td style="text-align: right; color: green;"><?php echo number_format($revenues, 0, ',', ' '); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Dépenses</strong></td>
                        <td style="text-align: right; color: red;"><?php echo number_format($expenses, 0, ',', ' '); ?></td>
                    </tr>
                    <tr class="total">
                        <td>BÉNÉFICE NET</td>
                        <td style="text-align: right;" class="<?php echo $profit >= 0 ? 'profit' : 'loss'; ?>">
                            <?php echo number_format($profit, 0, ',', ' '); ?> GNF
                        </td>
                    </tr>
                </tbody>
            </table>

            <p style="text-align: center; margin-top: 50px; color: #666;">
                Généré le <?php echo date('d/m/Y à H:i'); ?>
            </p>
        </body>
        </html>
        <?php
        $html = ob_get_clean();

        // Pour le moment, on génère du HTML qui peut être imprimé en PDF
        // En production, utiliser une vraie lib PDF comme TCPDF ou Dompdf
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    /**
     * Exporter les statistiques en PDF
     */
    public function export_statistics_pdf() {
        check_ajax_referer('colis224_nonce', 'nonce');

        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $stats = array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels"),
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels WHERE status = 'En attente'"),
            'transit' => $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels WHERE status = 'En transit'"),
            'delivered' => $wpdb->get_var("SELECT COUNT(*) FROM $table_parcels WHERE status = 'Livré'"),
        );

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Statistiques Colis224</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                h1 { color: #667eea; }
                .stat-box { display: inline-block; width: 22%; margin: 1%; padding: 20px; text-align: center; border: 2px solid #667eea; border-radius: 8px; }
                .stat-number { font-size: 36px; font-weight: bold; color: #667eea; }
                .stat-label { font-size: 14px; color: #666; margin-top: 10px; }
            </style>
        </head>
        <body>
            <h1>Statistiques des Colis</h1>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Colis</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">En Attente</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['transit']; ?></div>
                <div class="stat-label">En Transit</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['delivered']; ?></div>
                <div class="stat-label">Livrés</div>
            </div>
        </body>
        </html>
        <?php
        echo ob_get_clean();
        exit;
    }
}

// Initialiser
new Colis224_Export();
