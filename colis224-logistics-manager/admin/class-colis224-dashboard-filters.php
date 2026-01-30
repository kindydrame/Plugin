<?php
/**
 * Colis224 Dashboard Filters
 *
 * Gère la validation et la construction des filtres pour le tableau de bord
 * Version: 2.18.22
 *
 * @package Colis224_Logistics_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Colis224_Dashboard_Filters {

    /**
     * Périodes de temps disponibles
     */
    const TIME_PERIODS = array(
        '1_week' => '1 semaine',
        '1_month' => '1 mois',
        '3_months' => '3 mois',
        '6_months' => '6 mois',
        '12_months' => '12 mois',
        'custom' => 'Personnalisée'
    );

    /**
     * Valider les filtres reçus
     *
     * @param array $raw_filters Filtres bruts depuis AJAX
     * @return array Filtres validés et nettoyés
     */
    public static function validate_filters($raw_filters) {
        $validated = array();

        // Période de temps
        if (isset($raw_filters['time_period']) && array_key_exists($raw_filters['time_period'], self::TIME_PERIODS)) {
            $validated['time_period'] = sanitize_text_field($raw_filters['time_period']);
        } else {
            $validated['time_period'] = '1_month'; // Par défaut: 1 mois
        }

        // Dates personnalisées
        if ($validated['time_period'] === 'custom') {
            $validated['date_start'] = isset($raw_filters['date_start']) ? sanitize_text_field($raw_filters['date_start']) : date('Y-m-d', strtotime('-30 days'));
            $validated['date_end'] = isset($raw_filters['date_end']) ? sanitize_text_field($raw_filters['date_end']) : date('Y-m-d');
        } else {
            $dates = self::calculate_date_range($validated['time_period']);
            $validated['date_start'] = $dates['start'];
            $validated['date_end'] = $dates['end'];
        }

        // Agent ID
        if (isset($raw_filters['agent_id']) && !empty($raw_filters['agent_id'])) {
            $validated['agent_id'] = intval($raw_filters['agent_id']);
        }

        // Pays d'origine
        if (isset($raw_filters['origin_country']) && !empty($raw_filters['origin_country'])) {
            $validated['origin_country'] = sanitize_text_field($raw_filters['origin_country']);
        }

        // Pays de destination
        if (isset($raw_filters['destination_country']) && !empty($raw_filters['destination_country'])) {
            $validated['destination_country'] = sanitize_text_field($raw_filters['destination_country']);
        }

        // Livreur
        if (isset($raw_filters['driver_id']) && !empty($raw_filters['driver_id'])) {
            $validated['driver_id'] = intval($raw_filters['driver_id']);
        }

        // Partenaire
        if (isset($raw_filters['partner_id']) && !empty($raw_filters['partner_id'])) {
            $validated['partner_id'] = intval($raw_filters['partner_id']);
        }

        // Statut de paiement
        if (isset($raw_filters['payment_status']) && in_array($raw_filters['payment_status'], array('Payé', 'Non payé', 'Paiement partiel', 'all'))) {
            $validated['payment_status'] = sanitize_text_field($raw_filters['payment_status']);
        }

        // Statut du colis
        if (isset($raw_filters['parcel_status']) && !empty($raw_filters['parcel_status'])) {
            $validated['parcel_status'] = sanitize_text_field($raw_filters['parcel_status']);
        }

        // Moyen de transport (v2.18.25)
        if (isset($raw_filters['transport_mode_id']) && !empty($raw_filters['transport_mode_id'])) {
            $validated['transport_mode_id'] = intval($raw_filters['transport_mode_id']);
        }

        // Pays du client (v2.18.25)
        if (isset($raw_filters['client_country']) && !empty($raw_filters['client_country'])) {
            $validated['client_country'] = sanitize_text_field($raw_filters['client_country']);
        }

        return $validated;
    }

    /**
     * Calculer la plage de dates selon la période sélectionnée
     *
     * @param string $period Période sélectionnée
     * @return array ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD']
     */
    public static function calculate_date_range($period) {
        $end = date('Y-m-d');
        $start = date('Y-m-d');

        switch ($period) {
            case '1_week':
                $start = date('Y-m-d', strtotime('-7 days'));
                break;
            case '1_month':
                $start = date('Y-m-d', strtotime('-30 days'));
                break;
            case '3_months':
                $start = date('Y-m-d', strtotime('-90 days'));
                break;
            case '6_months':
                $start = date('Y-m-d', strtotime('-180 days'));
                break;
            case '12_months':
                $start = date('Y-m-d', strtotime('-365 days'));
                break;
        }

        return array(
            'start' => $start,
            'end' => $end
        );
    }

    /**
     * Construire la clause WHERE pour les requêtes SQL
     *
     * @param array $filters Filtres validés
     * @param string $table_alias Alias de la table des colis (ex: 'p')
     * @return array ['where' => 'WHERE ...', 'params' => [...]]
     */
    public static function build_where_clause($filters, $table_alias = 'p') {
        global $wpdb;

        $where_clauses = array();
        $params = array();

        // Plage de dates
        if (isset($filters['date_start']) && isset($filters['date_end'])) {
            $where_clauses[] = "{$table_alias}.created_at BETWEEN %s AND %s";
            $params[] = $filters['date_start'] . ' 00:00:00';
            $params[] = $filters['date_end'] . ' 23:59:59';
        }

        // Agent spécifique
        if (isset($filters['agent_id'])) {
            $where_clauses[] = "{$table_alias}.created_by = %d";
            $params[] = $filters['agent_id'];
        }

        // Pays d'origine
        if (isset($filters['origin_country'])) {
            $where_clauses[] = "{$table_alias}.sender_country = %s";
            $params[] = $filters['origin_country'];
        }

        // Pays de destination
        if (isset($filters['destination_country'])) {
            $where_clauses[] = "{$table_alias}.receiver_country = %s";
            $params[] = $filters['destination_country'];
        }

        // Livreur
        if (isset($filters['driver_id'])) {
            $where_clauses[] = "{$table_alias}.driver_id = %d";
            $params[] = $filters['driver_id'];
        }

        // Partenaire
        if (isset($filters['partner_id'])) {
            $where_clauses[] = "{$table_alias}.partner_id = %d";
            $params[] = $filters['partner_id'];
        }

        // Statut de paiement
        if (isset($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $where_clauses[] = "{$table_alias}.payment_status = %s";
            $params[] = $filters['payment_status'];
        }

        // Statut du colis
        if (isset($filters['parcel_status'])) {
            $where_clauses[] = "{$table_alias}.status = %s";
            $params[] = $filters['parcel_status'];
        }

        // Moyen de transport (v2.18.25)
        if (isset($filters['transport_mode_id'])) {
            $where_clauses[] = "{$table_alias}.transport_mode_id = %d";
            $params[] = $filters['transport_mode_id'];
        }

        // Pays du client (v2.18.25)
        if (isset($filters['client_country'])) {
            $clients_table = $wpdb->prefix . 'colis224_clients';
            $where_clauses[] = "{$table_alias}.client_id IN (SELECT id FROM {$clients_table} WHERE country = %s)";
            $params[] = $filters['client_country'];
        }

        // Construire la clause WHERE finale
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        return array(
            'where' => $where_sql,
            'params' => $params
        );
    }

    /**
     * Obtenir la liste des pays disponibles (origine)
     *
     * @return array Liste des pays uniques
     */
    public static function get_available_origin_countries() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'colis224_parcels';

        $countries = $wpdb->get_col("
            SELECT DISTINCT sender_country
            FROM {$table_name}
            WHERE sender_country IS NOT NULL
            AND sender_country != ''
            ORDER BY sender_country ASC
        ");

        return $countries;
    }

    /**
     * Obtenir la liste des pays disponibles (destination)
     *
     * @return array Liste des pays uniques
     */
    public static function get_available_destination_countries() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'colis224_parcels';

        $countries = $wpdb->get_col("
            SELECT DISTINCT receiver_country
            FROM {$table_name}
            WHERE receiver_country IS NOT NULL
            AND receiver_country != ''
            ORDER BY receiver_country ASC
        ");

        return $countries;
    }

    /**
     * Obtenir la liste des agents/créateurs
     *
     * @return array Liste des utilisateurs agents
     */
    public static function get_available_agents() {
        // v2.18.28: Utiliser WP_User_Query pour plus de fiabilité
        $args = array(
            'role__in' => array('administrator', 'editor', 'author'),
            'orderby' => 'display_name',
            'order' => 'ASC'
        );

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();

        // Ajouter les utilisateurs avec le rôle custom colis224_agent
        $agent_args = array(
            'meta_query' => array(
                array(
                    'key' => 'wp_capabilities',
                    'value' => 'colis224_agent',
                    'compare' => 'LIKE'
                )
            ),
            'orderby' => 'display_name',
            'order' => 'ASC'
        );

        $agent_query = new WP_User_Query($agent_args);
        $agents = $agent_query->get_results();

        // Fusionner et dédupliquer par ID
        $all_users = array_merge($users, $agents);
        $unique_users = array();
        $seen_ids = array();

        foreach ($all_users as $user) {
            if (!in_array($user->ID, $seen_ids)) {
                $seen_ids[] = $user->ID;
                $unique_users[] = $user;
            }
        }

        // Trier par display_name
        usort($unique_users, function($a, $b) {
            return strcmp($a->display_name, $b->display_name);
        });

        return $unique_users;
    }

    /**
     * Obtenir la liste des livreurs
     *
     * @return array Liste des livreurs
     */
    public static function get_available_drivers() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'colis224_drivers';

        // Vérifier si la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

        if ($table_exists) {
            $drivers = $wpdb->get_results("
                SELECT id, name, phone
                FROM {$table_name}
                ORDER BY name ASC
            ");
            return $drivers;
        }

        return array();
    }

    /**
     * Obtenir la liste des partenaires
     *
     * @return array Liste des partenaires
     */
    public static function get_available_partners() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'colis224_partners';

        // Vérifier si la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

        if ($table_exists) {
            $partners = $wpdb->get_results("
                SELECT id, name, contact_person
                FROM {$table_name}
                ORDER BY name ASC
            ");
            return $partners;
        }

        return array();
    }

    /**
     * Obtenir la liste des moyens de transport (v2.18.25)
     *
     * @return array Liste des moyens de transport
     */
    public static function get_available_transport_modes() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'colis224_transport_modes';

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

        if ($table_exists) {
            $transport_modes = $wpdb->get_results("
                SELECT id, name
                FROM {$table_name}
                ORDER BY name ASC
            ");
            return $transport_modes;
        }

        return array();
    }

    /**
     * Obtenir la liste des pays des clients (v2.18.25)
     *
     * @return array Liste des pays uniques des clients
     */
    public static function get_available_client_countries() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'colis224_clients';

        $countries = $wpdb->get_col("
            SELECT DISTINCT country
            FROM {$table_name}
            WHERE country IS NOT NULL
            AND country != ''
            ORDER BY country ASC
        ");

        return $countries;
    }

    /**
     * Rendre le panneau de filtres HTML
     *
     * @return string HTML du panneau de filtres
     */
    public static function render_filters_panel() {
        // Obtenir les données pour les dropdowns
        $agents = self::get_available_agents();
        $origin_countries = self::get_available_origin_countries();
        $destination_countries = self::get_available_destination_countries();
        $drivers = self::get_available_drivers();
        $partners = self::get_available_partners();
        $transport_modes = self::get_available_transport_modes(); // v2.18.25
        $client_countries = self::get_available_client_countries(); // v2.18.25

        ob_start();
        ?>
        <div id="colis224-dashboard-filters" class="dashboard-filters-panel">
            <div class="filters-header">
                <h3>🔍 Filtres et Statistiques Avancées</h3>
                <button type="button" class="toggle-filters-btn" id="toggle-filters">
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
            </div>

            <div class="filters-content" id="filters-content" style="display: none;">
                <form id="dashboard-filters-form">
                    <!-- v2.18.28: Nonce en input hidden comme fallback -->
                    <input type="hidden" id="dashboard_nonce_field" value="<?php echo wp_create_nonce('colis224_dashboard_nonce'); ?>">

                    <!-- Période de temps -->
                    <div class="filter-section">
                        <label>📅 Période</label>
                        <div class="time-period-buttons">
                            <?php foreach (self::TIME_PERIODS as $key => $label): ?>
                                <?php if ($key !== 'custom'): ?>
                                    <button type="button" class="period-btn" data-period="<?php echo esc_attr($key); ?>" <?php echo $key === '1_month' ? 'data-active="true"' : ''; ?>>
                                        <?php echo esc_html($label); ?>
                                    </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <!-- Dates personnalisées -->
                        <div class="custom-dates" id="custom-dates" style="display: none;">
                            <input type="date" id="date-start" name="date_start" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                            <span>à</span>
                            <input type="date" id="date-end" name="date_end" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <!-- Agent -->
                    <?php if (!empty($agents)): ?>
                    <div class="filter-section">
                        <label for="filter-agent">👤 Agent / Créateur</label>
                        <select id="filter-agent" name="agent_id">
                            <option value="">Tous les agents</option>
                            <?php foreach ($agents as $agent): ?>
                                <option value="<?php echo esc_attr($agent->ID); ?>">
                                    <?php echo esc_html($agent->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Statut de paiement -->
                    <div class="filter-section">
                        <label for="filter-payment">💰 Statut de paiement</label>
                        <select id="filter-payment" name="payment_status">
                            <option value="all">Tous les statuts</option>
                            <option value="Payé">Payé</option>
                            <option value="Non payé">Non payé</option>
                            <option value="Paiement partiel">Paiement partiel</option>
                        </select>
                    </div>

                    <!-- Pays d'origine (v2.18.26: avec drapeaux) -->
                    <?php if (!empty($origin_countries)): ?>
                    <div class="filter-section">
                        <label for="filter-origin">🌍 Pays d'expédition</label>
                        <select id="filter-origin" name="origin_country">
                            <option value="">Tous les pays</option>
                            <?php foreach ($origin_countries as $country): ?>
                                <option value="<?php echo esc_attr($country); ?>">
                                    <?php echo Colis224_Emojis::get_country_flag($country); ?> <?php echo esc_html($country); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Pays de destination (v2.18.26: avec drapeaux) -->
                    <?php if (!empty($destination_countries)): ?>
                    <div class="filter-section">
                        <label for="filter-destination">🎯 Pays destinataire</label>
                        <select id="filter-destination" name="destination_country">
                            <option value="">Tous les pays</option>
                            <?php foreach ($destination_countries as $country): ?>
                                <option value="<?php echo esc_attr($country); ?>">
                                    <?php echo Colis224_Emojis::get_country_flag($country); ?> <?php echo esc_html($country); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Livreur -->
                    <?php if (!empty($drivers)): ?>
                    <div class="filter-section">
                        <label for="filter-driver">🚚 Livreur</label>
                        <select id="filter-driver" name="driver_id">
                            <option value="">Tous les livreurs</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?php echo esc_attr($driver->id); ?>">
                                    <?php echo esc_html($driver->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Partenaire -->
                    <?php if (!empty($partners)): ?>
                    <div class="filter-section">
                        <label for="filter-partner">🤝 Partenaire</label>
                        <select id="filter-partner" name="partner_id">
                            <option value="">Tous les partenaires</option>
                            <?php foreach ($partners as $partner): ?>
                                <option value="<?php echo esc_attr($partner->id); ?>">
                                    <?php echo esc_html($partner->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Moyen de transport (v2.18.26: avec emojis) -->
                    <?php if (!empty($transport_modes)): ?>
                    <div class="filter-section">
                        <label for="filter-transport">🚚 Moyen de transport</label>
                        <select id="filter-transport" name="transport_mode_id">
                            <?php echo Colis224_Emojis::render_transport_options($transport_modes, ''); ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Pays du client (v2.18.26: avec drapeaux) -->
                    <?php if (!empty($client_countries)): ?>
                    <div class="filter-section">
                        <label for="filter-client-country">🌍 Pays du client</label>
                        <select id="filter-client-country" name="client_country">
                            <option value="">Tous les pays</option>
                            <?php foreach ($client_countries as $country): ?>
                                <option value="<?php echo esc_attr($country); ?>">
                                    <?php echo Colis224_Emojis::get_country_flag($country); ?> <?php echo esc_html($country); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Boutons d'action -->
                    <div class="filter-actions">
                        <button type="submit" class="button button-primary" id="apply-filters">
                            ✅ Appliquer les filtres
                        </button>
                        <button type="button" class="button" id="reset-filters">
                            🔄 Réinitialiser
                        </button>
                        <button type="button" class="button" id="export-stats">
                            📊 Exporter (CSV)
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Zone d'affichage des statistiques filtrées -->
        <div id="filtered-stats-container" style="display: none;">
            <div class="stats-loading" id="stats-loading">
                <span class="dashicons dashicons-update-alt rotating"></span>
                Chargement des statistiques...
            </div>
            <div id="filtered-stats-content"></div>
        </div>
        <?php
        return ob_get_clean();
    }
}
