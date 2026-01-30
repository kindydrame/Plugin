<?php
/**
 * Classe de gestion du tableau de bord analytique avancé
 * MODULE 32: KPIs temps réel, graphiques, tendances, prévisions
 *
 * @package Colis224
 * @subpackage Analytics
 * @since 2.9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Analytics {

    /**
     * Créer les tables pour l'analytique
     */
    public static function create_analytics_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table des KPIs historiques
        $sql_kpis = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_kpi_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            kpi_type varchar(50) NOT NULL,
            kpi_value decimal(15,2) NOT NULL,
            comparison_value decimal(15,2) DEFAULT NULL,
            percentage_change decimal(5,2) DEFAULT NULL,
            period_type enum('daily','weekly','monthly','quarterly','yearly') DEFAULT 'daily',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_date (date),
            KEY idx_kpi_type (kpi_type),
            KEY idx_period (period_type)
        ) $charset_collate;";

        // Table des rapports personnalisés
        $sql_reports = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_custom_reports (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            report_name varchar(200) NOT NULL,
            report_type varchar(50) NOT NULL,
            parameters text,
            filters text,
            chart_type varchar(50) DEFAULT 'bar',
            is_scheduled tinyint(1) DEFAULT 0,
            schedule_frequency varchar(50) DEFAULT NULL,
            last_generated datetime DEFAULT NULL,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_report_type (report_type),
            KEY idx_created_by (created_by)
        ) $charset_collate;";

        // Table des snapshots de données
        $sql_snapshots = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_data_snapshots (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            snapshot_date datetime NOT NULL,
            total_parcels int(11) DEFAULT 0,
            total_clients int(11) DEFAULT 0,
            total_revenue decimal(15,2) DEFAULT 0,
            total_expenses decimal(15,2) DEFAULT 0,
            net_profit decimal(15,2) DEFAULT 0,
            active_drivers int(11) DEFAULT 0,
            active_vehicles int(11) DEFAULT 0,
            pending_claims int(11) DEFAULT 0,
            customer_satisfaction decimal(3,2) DEFAULT 0,
            data_json text,
            PRIMARY KEY (id),
            KEY idx_snapshot_date (snapshot_date)
        ) $charset_collate;";

        // Table des prévisions
        $sql_forecasts = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_forecasts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            forecast_type varchar(50) NOT NULL,
            forecast_date date NOT NULL,
            predicted_value decimal(15,2) NOT NULL,
            actual_value decimal(15,2) DEFAULT NULL,
            confidence_level decimal(3,2) DEFAULT 0.75,
            method varchar(50) DEFAULT 'linear_regression',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_forecast_date (forecast_date),
            KEY idx_forecast_type (forecast_type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_kpis);
        dbDelta($sql_reports);
        dbDelta($sql_snapshots);
        dbDelta($sql_forecasts);
    }

    /**
     * Calculer les KPIs en temps réel
     */
    public static function get_realtime_kpis($period = 30) {
        global $wpdb;
        $start_date = date('Y-m-d', strtotime("-$period days"));
        $previous_start = date('Y-m-d', strtotime("-" . ($period * 2) . " days"));
        $previous_end = date('Y-m-d', strtotime("-" . ($period + 1) . " days"));

        $kpis = array();

        // Total colis
        $current_parcels = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_parcels WHERE created_at >= %s",
            $start_date
        ));

        $previous_parcels = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_parcels WHERE created_at BETWEEN %s AND %s",
            $previous_start,
            $previous_end
        ));

        $kpis['total_parcels'] = array(
            'value' => $current_parcels,
            'previous' => $previous_parcels,
            'change' => self::calculate_percentage_change($current_parcels, $previous_parcels),
            'trend' => self::get_trend($current_parcels, $previous_parcels)
        );

        // Revenus
        $current_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}colis224_payments WHERE payment_date >= %s",
            $start_date
        )) ?: 0;

        $previous_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}colis224_payments WHERE payment_date BETWEEN %s AND %s",
            $previous_start,
            $previous_end
        )) ?: 0;

        $kpis['total_revenue'] = array(
            'value' => $current_revenue,
            'previous' => $previous_revenue,
            'change' => self::calculate_percentage_change($current_revenue, $previous_revenue),
            'trend' => self::get_trend($current_revenue, $previous_revenue)
        );

        // Nouveaux clients
        $current_clients = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_clients WHERE created_at >= %s",
            $start_date
        ));

        $previous_clients = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_clients WHERE created_at BETWEEN %s AND %s",
            $previous_start,
            $previous_end
        ));

        $kpis['new_clients'] = array(
            'value' => $current_clients,
            'previous' => $previous_clients,
            'change' => self::calculate_percentage_change($current_clients, $previous_clients),
            'trend' => self::get_trend($current_clients, $previous_clients)
        );

        // Taux de livraison
        $delivered = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_parcels WHERE status = 'Livré' AND delivery_date >= %s",
            $start_date
        ));

        $delivery_rate = $current_parcels > 0 ? ($delivered / $current_parcels) * 100 : 0;

        $previous_delivered = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_parcels WHERE status = 'Livré' AND delivery_date BETWEEN %s AND %s",
            $previous_start,
            $previous_end
        ));

        $previous_rate = $previous_parcels > 0 ? ($previous_delivered / $previous_parcels) * 100 : 0;

        $kpis['delivery_rate'] = array(
            'value' => round($delivery_rate, 2),
            'previous' => round($previous_rate, 2),
            'change' => self::calculate_percentage_change($delivery_rate, $previous_rate),
            'trend' => self::get_trend($delivery_rate, $previous_rate)
        );

        // Satisfaction client (basé sur les avis)
        $avg_rating = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(overall_rating) FROM {$wpdb->prefix}colis224_reviews WHERE created_at >= %s",
            $start_date
        )) ?: 0;

        $previous_rating = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(overall_rating) FROM {$wpdb->prefix}colis224_reviews WHERE created_at BETWEEN %s AND %s",
            $previous_start,
            $previous_end
        )) ?: 0;

        $kpis['customer_satisfaction'] = array(
            'value' => round($avg_rating, 2),
            'previous' => round($previous_rating, 2),
            'change' => self::calculate_percentage_change($avg_rating, $previous_rating),
            'trend' => self::get_trend($avg_rating, $previous_rating)
        );

        // Valeur moyenne par colis
        $avg_value = $current_parcels > 0 ? $current_revenue / $current_parcels : 0;
        $prev_avg_value = $previous_parcels > 0 ? $previous_revenue / $previous_parcels : 0;

        $kpis['average_parcel_value'] = array(
            'value' => round($avg_value, 2),
            'previous' => round($prev_avg_value, 2),
            'change' => self::calculate_percentage_change($avg_value, $prev_avg_value),
            'trend' => self::get_trend($avg_value, $prev_avg_value)
        );

        return $kpis;
    }

    /**
     * Calculer le pourcentage de changement
     */
    private static function calculate_percentage_change($current, $previous) {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Déterminer la tendance
     */
    private static function get_trend($current, $previous) {
        if ($current > $previous) {
            return 'up';
        } elseif ($current < $previous) {
            return 'down';
        } else {
            return 'stable';
        }
    }

    /**
     * Obtenir les données pour les graphiques
     */
    public static function get_chart_data($chart_type, $period = 30) {
        global $wpdb;
        $start_date = date('Y-m-d', strtotime("-$period days"));

        $data = array();

        switch ($chart_type) {
            case 'parcels_timeline':
                $data = $wpdb->get_results($wpdb->prepare(
                    "SELECT DATE(created_at) as date, COUNT(*) as count
                    FROM {$wpdb->prefix}colis224_parcels
                    WHERE created_at >= %s
                    GROUP BY DATE(created_at)
                    ORDER BY date",
                    $start_date
                ), ARRAY_A);
                break;

            case 'revenue_timeline':
                $data = $wpdb->get_results($wpdb->prepare(
                    "SELECT DATE(payment_date) as date, SUM(amount) as total
                    FROM {$wpdb->prefix}colis224_payments
                    WHERE payment_date >= %s
                    GROUP BY DATE(payment_date)
                    ORDER BY date",
                    $start_date
                ), ARRAY_A);
                break;

            case 'status_distribution':
                $data = $wpdb->get_results(
                    "SELECT status, COUNT(*) as count
                    FROM {$wpdb->prefix}colis224_parcels
                    GROUP BY status
                    ORDER BY count DESC",
                    ARRAY_A
                );
                break;

            case 'top_clients':
                $data = $wpdb->get_results($wpdb->prepare(
                    "SELECT c.name, COUNT(p.id) as parcel_count, SUM(pay.amount) as total_spent
                    FROM {$wpdb->prefix}colis224_clients c
                    INNER JOIN {$wpdb->prefix}colis224_parcels p ON c.id = p.client_id
                    LEFT JOIN {$wpdb->prefix}colis224_payments pay ON p.id = pay.parcel_id
                    WHERE p.created_at >= %s
                    GROUP BY c.id
                    ORDER BY parcel_count DESC
                    LIMIT 10",
                    $start_date
                ), ARRAY_A);
                break;

            case 'driver_performance':
                $data = $wpdb->get_results($wpdb->prepare(
                    "SELECT d.name,
                        COUNT(p.id) as total_deliveries,
                        SUM(CASE WHEN p.status = 'Livré' THEN 1 ELSE 0 END) as successful_deliveries,
                        AVG(DATEDIFF(p.delivery_date, p.created_at)) as avg_delivery_time
                    FROM {$wpdb->prefix}colis224_drivers d
                    LEFT JOIN {$wpdb->prefix}colis224_parcels p ON d.id = p.driver_id
                    WHERE p.created_at >= %s
                    GROUP BY d.id
                    ORDER BY successful_deliveries DESC
                    LIMIT 10",
                    $start_date
                ), ARRAY_A);
                break;

            case 'monthly_comparison':
                $data = $wpdb->get_results(
                    "SELECT
                        DATE_FORMAT(created_at, '%Y-%m') as month,
                        COUNT(*) as parcels,
                        SUM(price) as revenue
                    FROM {$wpdb->prefix}colis224_parcels
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                    GROUP BY month
                    ORDER BY month",
                    ARRAY_A
                );
                break;
        }

        return $data;
    }

    /**
     * Générer des prévisions basées sur l'historique
     */
    public static function generate_forecast($forecast_type, $days_ahead = 30) {
        global $wpdb;

        // Récupérer les données historiques des 90 derniers jours
        $historical_data = array();

        switch ($forecast_type) {
            case 'parcels':
                $historical_data = $wpdb->get_results(
                    "SELECT DATE(created_at) as date, COUNT(*) as value
                    FROM {$wpdb->prefix}colis224_parcels
                    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date",
                    ARRAY_A
                );
                break;

            case 'revenue':
                $historical_data = $wpdb->get_results(
                    "SELECT DATE(payment_date) as date, SUM(amount) as value
                    FROM {$wpdb->prefix}colis224_payments
                    WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                    GROUP BY DATE(payment_date)
                    ORDER BY date",
                    ARRAY_A
                );
                break;
        }

        if (empty($historical_data)) {
            return array();
        }

        // Régression linéaire simple
        $forecasts = self::linear_regression_forecast($historical_data, $days_ahead);

        // Enregistrer les prévisions en base
        foreach ($forecasts as $forecast) {
            $wpdb->insert(
                $wpdb->prefix . 'colis224_forecasts',
                array(
                    'forecast_type' => $forecast_type,
                    'forecast_date' => $forecast['date'],
                    'predicted_value' => $forecast['value'],
                    'confidence_level' => 0.75,
                    'method' => 'linear_regression'
                )
            );
        }

        return $forecasts;
    }

    /**
     * Prévision par régression linéaire
     */
    private static function linear_regression_forecast($data, $days_ahead) {
        $n = count($data);
        if ($n < 2) {
            return array();
        }

        // Calculer la moyenne des x et y
        $sum_x = 0;
        $sum_y = 0;
        $sum_xy = 0;
        $sum_x2 = 0;

        foreach ($data as $index => $point) {
            $x = $index;
            $y = floatval($point['value']);

            $sum_x += $x;
            $sum_y += $y;
            $sum_xy += $x * $y;
            $sum_x2 += $x * $x;
        }

        // Calculer la pente (slope) et l'ordonnée à l'origine (intercept)
        $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);
        $intercept = ($sum_y - $slope * $sum_x) / $n;

        // Générer les prévisions
        $forecasts = array();
        $last_date = end($data)['date'];

        for ($i = 1; $i <= $days_ahead; $i++) {
            $x = $n + $i - 1;
            $predicted_value = $slope * $x + $intercept;

            // Ne pas avoir de valeurs négatives
            $predicted_value = max(0, $predicted_value);

            $forecast_date = date('Y-m-d', strtotime($last_date . " +$i days"));

            $forecasts[] = array(
                'date' => $forecast_date,
                'value' => round($predicted_value, 2)
            );
        }

        return $forecasts;
    }

    /**
     * Créer un snapshot quotidien des données
     */
    public static function create_daily_snapshot() {
        global $wpdb;

        $snapshot = array(
            'snapshot_date' => current_time('mysql'),
            'total_parcels' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}colis224_parcels"),
            'total_clients' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}colis224_clients"),
            'total_revenue' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}colis224_payments") ?: 0,
            'total_expenses' => $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}colis224_expenses") ?: 0,
            'active_drivers' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}colis224_drivers WHERE status = 'Actif'"),
            'active_vehicles' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}colis224_vehicles WHERE status = 'active'"),
            'pending_claims' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}colis224_insurance_claims WHERE status IN ('submitted', 'under_review')"),
            'customer_satisfaction' => $wpdb->get_var("SELECT AVG(overall_rating) FROM {$wpdb->prefix}colis224_reviews") ?: 0
        );

        $snapshot['net_profit'] = $snapshot['total_revenue'] - $snapshot['total_expenses'];

        // Ajouter données JSON supplémentaires
        $additional_data = array(
            'parcels_by_status' => $wpdb->get_results(
                "SELECT status, COUNT(*) as count FROM {$wpdb->prefix}colis224_parcels GROUP BY status",
                ARRAY_A
            ),
            'top_5_clients' => $wpdb->get_results(
                "SELECT c.name, COUNT(p.id) as parcel_count
                FROM {$wpdb->prefix}colis224_clients c
                INNER JOIN {$wpdb->prefix}colis224_parcels p ON c.id = p.client_id
                GROUP BY c.id
                ORDER BY parcel_count DESC
                LIMIT 5",
                ARRAY_A
            )
        );

        $snapshot['data_json'] = json_encode($additional_data);

        return $wpdb->insert(
            $wpdb->prefix . 'colis224_data_snapshots',
            $snapshot
        );
    }

    /**
     * Comparer les performances entre deux périodes
     */
    public static function compare_periods($start1, $end1, $start2, $end2) {
        global $wpdb;

        $comparison = array();

        // Période 1
        $period1_parcels = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_parcels WHERE created_at BETWEEN %s AND %s",
            $start1,
            $end1
        ));

        $period1_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}colis224_payments WHERE payment_date BETWEEN %s AND %s",
            $start1,
            $end1
        )) ?: 0;

        // Période 2
        $period2_parcels = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_parcels WHERE created_at BETWEEN %s AND %s",
            $start2,
            $end2
        ));

        $period2_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}colis224_payments WHERE payment_date BETWEEN %s AND %s",
            $start2,
            $end2
        )) ?: 0;

        $comparison = array(
            'period1' => array(
                'parcels' => $period1_parcels,
                'revenue' => $period1_revenue,
                'avg_value' => $period1_parcels > 0 ? $period1_revenue / $period1_parcels : 0
            ),
            'period2' => array(
                'parcels' => $period2_parcels,
                'revenue' => $period2_revenue,
                'avg_value' => $period2_parcels > 0 ? $period2_revenue / $period2_parcels : 0
            ),
            'changes' => array(
                'parcels_change' => self::calculate_percentage_change($period2_parcels, $period1_parcels),
                'revenue_change' => self::calculate_percentage_change($period2_revenue, $period1_revenue)
            )
        );

        return $comparison;
    }

    /**
     * Exporter un rapport personnalisé
     */
    public static function export_custom_report($report_id, $format = 'csv') {
        global $wpdb;

        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_custom_reports WHERE id = %d",
            $report_id
        ));

        if (!$report) {
            return false;
        }

        // Générer les données du rapport basées sur les paramètres
        $data = self::generate_report_data($report);

        // Exporter selon le format
        if ($format === 'csv') {
            return self::export_to_csv($data, $report->report_name);
        } elseif ($format === 'json') {
            return json_encode($data);
        }

        return $data;
    }

    /**
     * Générer les données d'un rapport
     */
    private static function generate_report_data($report) {
        // Cette fonction serait personnalisée selon le type de rapport
        // Pour l'instant, retourner les données de base
        return array(
            'report_name' => $report->report_name,
            'generated_at' => current_time('mysql'),
            'data' => array()
        );
    }

    /**
     * Exporter en CSV
     */
    private static function export_to_csv($data, $filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '.csv"');

        $output = fopen('php://output', 'w');

        // Ajouter BOM pour UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // En-têtes
        if (!empty($data) && is_array($data[0])) {
            fputcsv($output, array_keys($data[0]), ';');
        }

        // Données
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }
}
