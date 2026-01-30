<?php
/**
 * Colis224 Dashboard Advanced Statistics
 *
 * Calcule les statistiques avancées avec filtres
 * Version: 2.18.22
 *
 * @package Colis224_Logistics_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Colis224_Dashboard_Advanced_Stats {

    /**
     * Obtenir les statistiques par agent
     *
     * @param array $filters Filtres validés
     * @return array Statistiques par agent
     */
    public static function get_stats_by_agent($filters = array()) {
        global $wpdb;
        $parcels_table = $wpdb->prefix . 'colis224_parcels';

        // Construire la clause WHERE
        $where_data = Colis224_Dashboard_Filters::build_where_clause($filters, 'p');
        $where_sql = $where_data['where'];
        $params = $where_data['params'];

        // Si pas de clause WHERE, ajouter une condition par défaut pour éviter erreur SQL
        if (empty($where_sql)) {
            $where_sql = 'WHERE 1=1';
        }

        // Requête SQL
        $query = "
            SELECT
                u.ID as agent_id,
                u.display_name as agent_name,
                u.user_email as agent_email,
                COUNT(p.id) as total_parcels,
                SUM(CASE WHEN p.payment_status = 'Payé' THEN 1 ELSE 0 END) as paid_parcels,
                SUM(CASE WHEN p.payment_status = 'Non payé' THEN 1 ELSE 0 END) as unpaid_parcels,
                SUM(CASE WHEN p.payment_status = 'Paiement partiel' THEN 1 ELSE 0 END) as partial_parcels,
                COALESCE(SUM(p.total_amount), 0) as total_revenue,
                COALESCE(SUM(CASE WHEN p.payment_status = 'Payé' THEN p.total_amount ELSE 0 END), 0) as paid_revenue,
                COALESCE(SUM(CASE WHEN p.payment_status = 'Non payé' THEN p.total_amount ELSE 0 END), 0) as unpaid_revenue,
                COALESCE(SUM(CASE WHEN p.payment_status = 'Paiement partiel' THEN p.amount_paid ELSE 0 END), 0) as partial_paid,
                COALESCE(SUM(CASE WHEN p.payment_status = 'Paiement partiel' THEN (p.total_amount - p.amount_paid) ELSE 0 END), 0) as partial_remaining
            FROM {$wpdb->users} u
            INNER JOIN {$parcels_table} p ON p.created_by = u.ID
            {$where_sql}
            GROUP BY u.ID, u.display_name, u.user_email
            ORDER BY total_revenue DESC
        ";

        // Préparer la requête si on a des paramètres
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        $results = $wpdb->get_results($query);

        return $results;
    }

    /**
     * Obtenir les statistiques globales de paiement
     *
     * @param array $filters Filtres validés
     * @return array Statistiques de paiement
     */
    public static function get_payment_stats($filters = array()) {
        global $wpdb;
        $parcels_table = $wpdb->prefix . 'colis224_parcels';

        // Construire la clause WHERE
        $where_data = Colis224_Dashboard_Filters::build_where_clause($filters, 'p');
        $where_sql = $where_data['where'];
        $params = $where_data['params'];

        if (empty($where_sql)) {
            $where_sql = 'WHERE 1=1';
        }

        // Requête SQL
        $query = "
            SELECT
                COUNT(*) as total_parcels,
                SUM(CASE WHEN payment_status = 'Payé' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN payment_status = 'Non payé' THEN 1 ELSE 0 END) as unpaid_count,
                SUM(CASE WHEN payment_status = 'Paiement partiel' THEN 1 ELSE 0 END) as partial_count,
                COALESCE(SUM(total_amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN payment_status = 'Payé' THEN total_amount ELSE 0 END), 0) as paid_amount,
                COALESCE(SUM(CASE WHEN payment_status = 'Non payé' THEN total_amount ELSE 0 END), 0) as unpaid_amount,
                COALESCE(SUM(CASE WHEN payment_status = 'Paiement partiel' THEN amount_paid ELSE 0 END), 0) as partial_paid_amount,
                COALESCE(SUM(CASE WHEN payment_status = 'Paiement partiel' THEN (total_amount - amount_paid) ELSE 0 END), 0) as partial_remaining_amount
            FROM {$parcels_table} p
            {$where_sql}
        ";

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        $result = $wpdb->get_row($query);

        return $result;
    }

    /**
     * Obtenir les statistiques géographiques
     *
     * @param array $filters Filtres validés
     * @return array Statistiques par pays
     */
    public static function get_geographic_stats($filters = array()) {
        global $wpdb;
        $parcels_table = $wpdb->prefix . 'colis224_parcels';

        // Construire la clause WHERE
        $where_data = Colis224_Dashboard_Filters::build_where_clause($filters, 'p');
        $where_sql = $where_data['where'];
        $params = $where_data['params'];

        if (empty($where_sql)) {
            $where_sql = 'WHERE 1=1';
        }

        // Statistiques par pays d'origine
        $query_origin = "
            SELECT
                sender_country as country,
                'origin' as type,
                COUNT(*) as parcel_count,
                COALESCE(SUM(total_amount), 0) as total_revenue
            FROM {$parcels_table} p
            {$where_sql}
            AND sender_country IS NOT NULL
            AND sender_country != ''
            GROUP BY sender_country
            ORDER BY parcel_count DESC
            LIMIT 10
        ";

        // Statistiques par pays de destination
        $query_destination = "
            SELECT
                receiver_country as country,
                'destination' as type,
                COUNT(*) as parcel_count,
                COALESCE(SUM(total_amount), 0) as total_revenue
            FROM {$parcels_table} p
            {$where_sql}
            AND receiver_country IS NOT NULL
            AND receiver_country != ''
            GROUP BY receiver_country
            ORDER BY parcel_count DESC
            LIMIT 10
        ";

        if (!empty($params)) {
            $query_origin = $wpdb->prepare($query_origin, $params);
            $query_destination = $wpdb->prepare($query_destination, $params);
        }

        $origin_stats = $wpdb->get_results($query_origin);
        $destination_stats = $wpdb->get_results($query_destination);

        return array(
            'origin' => $origin_stats,
            'destination' => $destination_stats
        );
    }

    /**
     * Obtenir les performances des livreurs
     *
     * @param array $filters Filtres validés
     * @return array Statistiques par livreur
     */
    public static function get_driver_performance($filters = array()) {
        global $wpdb;
        $parcels_table = $wpdb->prefix . 'colis224_parcels';
        $drivers_table = $wpdb->prefix . 'colis224_drivers';

        // Vérifier si la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$drivers_table}'");
        if (!$table_exists) {
            return array();
        }

        // Construire la clause WHERE
        $where_data = Colis224_Dashboard_Filters::build_where_clause($filters, 'p');
        $where_sql = $where_data['where'];
        $params = $where_data['params'];

        if (empty($where_sql)) {
            $where_sql = 'WHERE 1=1';
        }

        // Requête SQL
        $query = "
            SELECT
                d.id as driver_id,
                d.name as driver_name,
                d.phone as driver_phone,
                COUNT(p.id) as total_parcels,
                SUM(CASE WHEN p.status = 'Livré' THEN 1 ELSE 0 END) as delivered_parcels,
                SUM(CASE WHEN p.status = 'En transit' THEN 1 ELSE 0 END) as in_transit_parcels,
                COALESCE(SUM(p.total_amount), 0) as total_revenue
            FROM {$drivers_table} d
            LEFT JOIN {$parcels_table} p ON p.driver_id = d.id
            {$where_sql}
            GROUP BY d.id, d.name, d.phone
            HAVING total_parcels > 0
            ORDER BY total_parcels DESC
        ";

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        $results = $wpdb->get_results($query);

        return $results;
    }

    /**
     * Obtenir l'activité des partenaires
     *
     * @param array $filters Filtres validés
     * @return array Statistiques par partenaire
     */
    public static function get_partner_activity($filters = array()) {
        global $wpdb;
        $parcels_table = $wpdb->prefix . 'colis224_parcels';
        $partners_table = $wpdb->prefix . 'colis224_partners';

        // Vérifier si la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$partners_table}'");
        if (!$table_exists) {
            return array();
        }

        // Construire la clause WHERE
        $where_data = Colis224_Dashboard_Filters::build_where_clause($filters, 'p');
        $where_sql = $where_data['where'];
        $params = $where_data['params'];

        if (empty($where_sql)) {
            $where_sql = 'WHERE 1=1';
        }

        // Requête SQL
        $query = "
            SELECT
                pt.id as partner_id,
                pt.name as partner_name,
                pt.contact_person,
                COUNT(p.id) as total_parcels,
                COALESCE(SUM(p.total_amount), 0) as total_revenue
            FROM {$partners_table} pt
            LEFT JOIN {$parcels_table} p ON p.partner_id = pt.id
            {$where_sql}
            GROUP BY pt.id, pt.name, pt.contact_person
            HAVING total_parcels > 0
            ORDER BY total_revenue DESC
        ";

        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }

        $results = $wpdb->get_results($query);

        return $results;
    }

    /**
     * Obtenir un résumé complet des statistiques
     *
     * @param array $filters Filtres validés
     * @return array Toutes les statistiques combinées
     */
    public static function get_complete_stats($filters = array()) {
        return array(
            'agents' => self::get_stats_by_agent($filters),
            'payment' => self::get_payment_stats($filters),
            'geographic' => self::get_geographic_stats($filters),
            'drivers' => self::get_driver_performance($filters),
            'partners' => self::get_partner_activity($filters),
            'filters_applied' => $filters
        );
    }

    /**
     * Rendre les statistiques au format HTML
     *
     * @param array $stats Statistiques calculées
     * @return string HTML des statistiques
     */
    public static function render_stats_html($stats) {
        ob_start();
        ?>
        <div class="advanced-stats-container">

            <!-- Statistiques de paiement globales -->
            <?php if (isset($stats['payment'])): ?>
            <div class="stats-section payment-stats">
                <h3>💰 Statistiques de paiement</h3>
                <div class="stats-cards">
                    <div class="stat-card green">
                        <div class="stat-value"><?php echo number_format($stats['payment']->paid_count); ?></div>
                        <div class="stat-label">Colis payés</div>
                        <div class="stat-amount"><?php echo number_format($stats['payment']->paid_amount, 0, ',', ' '); ?> FCFA</div>
                    </div>
                    <div class="stat-card red">
                        <div class="stat-value"><?php echo number_format($stats['payment']->unpaid_count); ?></div>
                        <div class="stat-label">Colis non payés</div>
                        <div class="stat-amount"><?php echo number_format($stats['payment']->unpaid_amount, 0, ',', ' '); ?> FCFA</div>
                    </div>
                    <div class="stat-card orange">
                        <div class="stat-value"><?php echo number_format($stats['payment']->partial_count); ?></div>
                        <div class="stat-label">Paiements partiels</div>
                        <div class="stat-amount">
                            Payé: <?php echo number_format($stats['payment']->partial_paid_amount, 0, ',', ' '); ?> FCFA<br>
                            Restant: <?php echo number_format($stats['payment']->partial_remaining_amount, 0, ',', ' '); ?> FCFA
                        </div>
                    </div>
                    <div class="stat-card blue">
                        <div class="stat-value"><?php echo number_format($stats['payment']->total_parcels); ?></div>
                        <div class="stat-label">Total colis</div>
                        <div class="stat-amount"><?php echo number_format($stats['payment']->total_amount, 0, ',', ' '); ?> FCFA</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Statistiques par agent -->
            <?php if (isset($stats['agents']) && !empty($stats['agents'])): ?>
            <div class="stats-section agent-stats">
                <h3>👥 Performances par agent</h3>
                <div class="stats-table-wrapper">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Total colis</th>
                                <th>Payés</th>
                                <th>Non payés</th>
                                <th>Partiels</th>
                                <th>Chiffre d'affaires</th>
                                <th>CA payé</th>
                                <th>CA non payé</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['agents'] as $agent): ?>
                            <tr>
                                <td><strong><?php echo esc_html($agent->agent_name); ?></strong></td>
                                <td><?php echo number_format($agent->total_parcels); ?></td>
                                <td class="text-green"><?php echo number_format($agent->paid_parcels); ?></td>
                                <td class="text-red"><?php echo number_format($agent->unpaid_parcels); ?></td>
                                <td class="text-orange"><?php echo number_format($agent->partial_parcels); ?></td>
                                <td><strong><?php echo number_format($agent->total_revenue, 0, ',', ' '); ?> FCFA</strong></td>
                                <td class="text-green"><?php echo number_format($agent->paid_revenue, 0, ',', ' '); ?> FCFA</td>
                                <td class="text-red"><?php echo number_format($agent->unpaid_revenue, 0, ',', ' '); ?> FCFA</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Statistiques géographiques -->
            <?php if (isset($stats['geographic'])): ?>
            <div class="stats-section geographic-stats">
                <h3>🌍 Statistiques géographiques</h3>
                <div class="geo-stats-grid">
                    <!-- Pays d'origine -->
                    <?php if (!empty($stats['geographic']['origin'])): ?>
                    <div class="geo-stats-column">
                        <h4>📤 Top 10 pays d'expédition</h4>
                        <table class="wp-list-table widefat">
                            <thead>
                                <tr>
                                    <th>Pays</th>
                                    <th>Colis</th>
                                    <th>CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['geographic']['origin'] as $country): ?>
                                <tr>
                                    <td><?php echo esc_html($country->country); ?></td>
                                    <td><?php echo number_format($country->parcel_count); ?></td>
                                    <td><?php echo number_format($country->total_revenue, 0, ',', ' '); ?> FCFA</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <!-- Pays de destination -->
                    <?php if (!empty($stats['geographic']['destination'])): ?>
                    <div class="geo-stats-column">
                        <h4>📥 Top 10 pays destinataires</h4>
                        <table class="wp-list-table widefat">
                            <thead>
                                <tr>
                                    <th>Pays</th>
                                    <th>Colis</th>
                                    <th>CA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['geographic']['destination'] as $country): ?>
                                <tr>
                                    <td><?php echo esc_html($country->country); ?></td>
                                    <td><?php echo number_format($country->parcel_count); ?></td>
                                    <td><?php echo number_format($country->total_revenue, 0, ',', ' '); ?> FCFA</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Performances des livreurs -->
            <?php if (isset($stats['drivers']) && !empty($stats['drivers'])): ?>
            <div class="stats-section driver-stats">
                <h3>🚚 Performances des livreurs</h3>
                <div class="stats-table-wrapper">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Livreur</th>
                                <th>Téléphone</th>
                                <th>Total colis</th>
                                <th>Livrés</th>
                                <th>En transit</th>
                                <th>CA total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['drivers'] as $driver): ?>
                            <tr>
                                <td><strong><?php echo esc_html($driver->driver_name); ?></strong></td>
                                <td><?php echo esc_html($driver->driver_phone); ?></td>
                                <td><?php echo number_format($driver->total_parcels); ?></td>
                                <td class="text-green"><?php echo number_format($driver->delivered_parcels); ?></td>
                                <td class="text-orange"><?php echo number_format($driver->in_transit_parcels); ?></td>
                                <td><?php echo number_format($driver->total_revenue, 0, ',', ' '); ?> FCFA</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Activité des partenaires -->
            <?php if (isset($stats['partners']) && !empty($stats['partners'])): ?>
            <div class="stats-section partner-stats">
                <h3>🤝 Activité des partenaires</h3>
                <div class="stats-table-wrapper">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Partenaire</th>
                                <th>Contact</th>
                                <th>Total colis</th>
                                <th>CA total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['partners'] as $partner): ?>
                            <tr>
                                <td><strong><?php echo esc_html($partner->partner_name); ?></strong></td>
                                <td><?php echo esc_html($partner->contact_person); ?></td>
                                <td><?php echo number_format($partner->total_parcels); ?></td>
                                <td><?php echo number_format($partner->total_revenue, 0, ',', ' '); ?> FCFA</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Exporter les statistiques en CSV
     *
     * @param array $stats Statistiques calculées
     * @param string $filename Nom du fichier
     */
    public static function export_to_csv($stats, $filename = 'colis224-stats.csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM UTF-8 pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // En-têtes et données - Statistiques par agent
        if (isset($stats['agents']) && !empty($stats['agents'])) {
            fputcsv($output, array('STATISTIQUES PAR AGENT'), ';');
            fputcsv($output, array('Agent', 'Total colis', 'Payés', 'Non payés', 'Partiels', 'CA total', 'CA payé', 'CA non payé'), ';');

            foreach ($stats['agents'] as $agent) {
                fputcsv($output, array(
                    $agent->agent_name,
                    $agent->total_parcels,
                    $agent->paid_parcels,
                    $agent->unpaid_parcels,
                    $agent->partial_parcels,
                    $agent->total_revenue,
                    $agent->paid_revenue,
                    $agent->unpaid_revenue
                ), ';');
            }
            fputcsv($output, array()); // Ligne vide
        }

        // Statistiques de paiement
        if (isset($stats['payment'])) {
            fputcsv($output, array('STATISTIQUES DE PAIEMENT'), ';');
            fputcsv($output, array('Colis payés', $stats['payment']->paid_count, $stats['payment']->paid_amount . ' FCFA'), ';');
            fputcsv($output, array('Colis non payés', $stats['payment']->unpaid_count, $stats['payment']->unpaid_amount . ' FCFA'), ';');
            fputcsv($output, array('Paiements partiels', $stats['payment']->partial_count, $stats['payment']->partial_paid_amount . ' FCFA'), ';');
            fputcsv($output, array('Total', $stats['payment']->total_parcels, $stats['payment']->total_amount . ' FCFA'), ';');
            fputcsv($output, array()); // Ligne vide
        }

        fclose($output);
        exit;
    }
}
