<?php
/**
 * MODULE 33: Système de Départs et Réservations de Voyages
 * Gestion et affichage des départs (Avion/Bateau) avec réservation WhatsApp
 *
 * @package Colis224
 * @subpackage Departures
 * @since 2.10.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Departures {

    /**
     * Créer les tables pour les départs
     */
    public static function create_departures_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table des départs
        $sql_departures = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}colis224_departures (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            departure_number varchar(50) NOT NULL UNIQUE,
            departure_city varchar(200) NOT NULL,
            departure_country varchar(100) NOT NULL,
            departure_country_code varchar(2) DEFAULT NULL,
            arrival_city varchar(200) NOT NULL,
            arrival_country varchar(100) NOT NULL,
            arrival_country_code varchar(2) DEFAULT NULL,
            departure_date date NOT NULL,
            departure_time time DEFAULT NULL,
            transport_type enum('plane','boat') DEFAULT 'plane',
            estimated_duration varchar(50) DEFAULT NULL,
            available_seats int(11) DEFAULT NULL,
            price_estimate decimal(15,2) DEFAULT NULL,
            currency varchar(3) DEFAULT 'GNF',
            status enum('scheduled','departed','arrived','cancelled','delayed') DEFAULT 'scheduled',
            notes text,
            whatsapp_number varchar(20) DEFAULT '+224620178930',
            is_active tinyint(1) DEFAULT 1,
            created_by bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_departure_date (departure_date),
            KEY idx_status (status),
            KEY idx_active (is_active),
            KEY idx_transport (transport_type),
            KEY idx_departure_country (departure_country_code),
            KEY idx_arrival_country (arrival_country_code)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_departures);
    }

    /**
     * Liste des codes de pays avec drapeaux
     * DYNAMIQUE: Charge depuis la base de données
     */
    public static function get_countries() {
        global $wpdb;
        $table_countries = $wpdb->prefix . 'colis224_countries';

        // Charger les pays actifs depuis la DB
        $countries_db = $wpdb->get_results(
            "SELECT code, name FROM $table_countries WHERE is_active = 1 ORDER BY name ASC"
        );

        $countries = array();

        foreach ($countries_db as $country) {
            // Si le pays a un code ISO, l'utiliser comme clé
            if (!empty($country->code)) {
                $code = strtoupper($country->code);
                $countries[$code] = array(
                    'name' => $country->name,
                    'flag' => self::get_country_flag($code)
                );
            } else {
                // Sinon, utiliser le nom comme clé (pour compatibilité)
                $countries[$country->name] = array(
                    'name' => $country->name,
                    'flag' => '🌍' // Drapeau par défaut
                );
            }
        }

        // Si aucun pays en DB (installation fraîche), retourner une liste par défaut
        if (empty($countries)) {
            return array(
                'CN' => array('name' => 'Chine', 'flag' => '🇨🇳'),
                'FR' => array('name' => 'France', 'flag' => '🇫🇷'),
                'MA' => array('name' => 'Maroc', 'flag' => '🇲🇦'),
                'SN' => array('name' => 'Sénégal', 'flag' => '🇸🇳'),
                'CI' => array('name' => 'Côte d\'Ivoire', 'flag' => '🇨🇮'),
                'GN' => array('name' => 'Guinée', 'flag' => '🇬🇳'),
            );
        }

        return $countries;
    }

    /**
     * Obtenir le drapeau emoji d'un pays à partir de son code ISO
     */
    private static function get_country_flag($code) {
        // Carte des codes ISO vers les drapeaux emoji
        $flags = array(
            'CN' => '🇨🇳', 'FR' => '🇫🇷', 'MA' => '🇲🇦', 'SN' => '🇸🇳',
            'CI' => '🇨🇮', 'GN' => '🇬🇳', 'ML' => '🇲🇱', 'BF' => '🇧🇫',
            'TG' => '🇹🇬', 'BJ' => '🇧🇯', 'GH' => '🇬🇭', 'NG' => '🇳🇬',
            'CM' => '🇨🇲', 'GA' => '🇬🇦', 'CG' => '🇨🇬', 'CD' => '🇨🇩',
            'AE' => '🇦🇪', 'SA' => '🇸🇦', 'TR' => '🇹🇷', 'US' => '🇺🇸',
            'GB' => '🇬🇧', 'ES' => '🇪🇸', 'IT' => '🇮🇹', 'DE' => '🇩🇪',
            'BE' => '🇧🇪', 'CH' => '🇨🇭', 'CA' => '🇨🇦', 'PT' => '🇵🇹',
            'NL' => '🇳🇱', 'SE' => '🇸🇪', 'NO' => '🇳🇴', 'DK' => '🇩🇰',
            'FI' => '🇫🇮', 'PL' => '🇵🇱', 'CZ' => '🇨🇿', 'HU' => '🇭🇺',
            'AT' => '🇦🇹', 'GR' => '🇬🇷', 'RO' => '🇷🇴', 'BG' => '🇧🇬',
            'HR' => '🇭🇷', 'RS' => '🇷🇸', 'UA' => '🇺🇦', 'RU' => '🇷🇺',
            'JP' => '🇯🇵', 'KR' => '🇰🇷', 'IN' => '🇮🇳', 'PK' => '🇵🇰',
            'BD' => '🇧🇩', 'ID' => '🇮🇩', 'TH' => '🇹🇭', 'VN' => '🇻🇳',
            'PH' => '🇵🇭', 'MY' => '🇲🇾', 'SG' => '🇸🇬', 'AU' => '🇦🇺',
            'NZ' => '🇳🇿', 'BR' => '🇧🇷', 'AR' => '🇦🇷', 'CL' => '🇨🇱',
            'MX' => '🇲🇽', 'CO' => '🇨🇴', 'PE' => '🇵🇪', 'VE' => '🇻🇪',
            'ZA' => '🇿🇦', 'EG' => '🇪🇬', 'DZ' => '🇩🇿', 'TN' => '🇹🇳',
            'LY' => '🇱🇾', 'ET' => '🇪🇹', 'KE' => '🇰🇪', 'TZ' => '🇹🇿',
            'UG' => '🇺🇬', 'RW' => '🇷🇼', 'ZW' => '🇿🇼', 'MZ' => '🇲🇿',
        );

        $code = strtoupper($code);
        return isset($flags[$code]) ? $flags[$code] : '🌍';
    }

    /**
     * Ajouter un départ
     */
    public static function add_departure($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departures';

        $defaults = array(
            'departure_number' => self::generate_departure_number(),
            'departure_city' => '',
            'departure_country' => '',
            'departure_country_code' => '',
            'arrival_city' => '',
            'arrival_country' => '',
            'arrival_country_code' => '',
            'departure_date' => date('Y-m-d'),
            'departure_time' => '08:00:00',
            'transport_type' => 'plane',
            'estimated_duration' => '',
            'available_seats' => null,
            'price_estimate' => null,
            'currency' => 'GNF',
            'status' => 'scheduled',
            'notes' => '',
            'whatsapp_number' => '+224620178930',
            'is_active' => 1,
            'created_by' => get_current_user_id()
        );

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert($table, $data);

        if ($result) {
            $departure_id = $wpdb->insert_id;

            // Notifier les abonnés du nouveau départ
            self::notify_subscribers_new_departure($departure_id);

            return $departure_id;
        }

        return false;
    }

    /**
     * Générer un numéro de départ unique
     */
    private static function generate_departure_number() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departures';

        do {
            $number = 'DEP-' . date('Ymd') . '-' . strtoupper(wp_generate_password(6, false));
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE departure_number = %s",
                $number
            ));
        } while ($exists > 0);

        return $number;
    }

    /**
     * Obtenir les départs avec filtres
     */
    public static function get_departures($filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departures';

        $where = array("is_active = 1");
        $where_values = array();

        // Filtre par pays de départ
        if (!empty($filters['departure_country'])) {
            $where[] = "departure_country_code = %s";
            $where_values[] = $filters['departure_country'];
        }

        // Filtre par pays d'arrivée
        if (!empty($filters['arrival_country'])) {
            $where[] = "arrival_country_code = %s";
            $where_values[] = $filters['arrival_country'];
        }

        // Filtre par type de transport
        if (!empty($filters['transport_type'])) {
            $where[] = "transport_type = %s";
            $where_values[] = $filters['transport_type'];
        }

        // Filtre par mois
        if (!empty($filters['month'])) {
            $where[] = "DATE_FORMAT(departure_date, '%%Y-%%m') = %s";
            $where_values[] = $filters['month'];
        }

        // Filtre par statut
        if (!empty($filters['status'])) {
            $where[] = "status = %s";
            $where_values[] = $filters['status'];
        } else {
            // Par défaut, afficher seulement les départs programmés et futurs
            $where[] = "status IN ('scheduled', 'delayed')";
            $where[] = "departure_date >= CURDATE()";
        }

        $where_clause = implode(' AND ', $where);

        $sql = "SELECT * FROM $table WHERE $where_clause ORDER BY departure_date ASC, departure_time ASC";

        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Obtenir les mois disponibles
     */
    public static function get_available_months() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_departures';

        return $wpdb->get_results(
            "SELECT DISTINCT DATE_FORMAT(departure_date, '%Y-%m') as month,
                DATE_FORMAT(departure_date, '%M %Y') as month_name
            FROM $table
            WHERE is_active = 1 AND departure_date >= CURDATE()
            ORDER BY month ASC"
        );
    }

    /**
     * Afficher le shortcode des départs
     */
    public static function departures_shortcode($atts) {
        // CHARGER LES STYLES ET SCRIPTS
        wp_enqueue_style(
            'colis224-departures-slider',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/departures-slider.css',
            array(),
            '2.10.5'
        );

        wp_enqueue_script(
            'colis224-departures-slider',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/departures-slider.js',
            array('jquery'),
            '2.10.5',
            true
        );

        // Localiser le script pour AJAX
        wp_localize_script('colis224-departures-slider', 'colis224Departures', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('colis224_departures_nonce')
        ));

        $atts = shortcode_atts(array(
            'limit' => 20,
            'transport_type' => '', // plane, boat, ou vide pour tous
            'layout' => 'slider', // slider, grid, list
            'show_filters' => 'yes'
        ), $atts);

        ob_start();
        ?>
        <div class="colis224-departures-container" data-layout="<?php echo esc_attr($atts['layout']); ?>">

            <?php if ($atts['show_filters'] === 'yes'): ?>
            <!-- Filtres Modernes - Layout Pleine Largeur Mobile Friendly -->
            <div class="colis224-departures-filters">

                <!-- Ligne 1: Recherche pleine largeur -->
                <div class="filter-row-fullwidth">
                    <input type="text"
                           id="filter-city-search"
                           class="colis224-filter search-input-fullwidth"
                           placeholder="🔎 Rechercher par ville de départ ou d'arrivée (ex: Conakry, Paris, Dakar...)">
                </div>

                <!-- Ligne 2: Filtres secondaires -->
                <div class="filter-row-secondary">
                    <select id="filter-month" class="colis224-filter filter-select">
                        <option value="">📅 Tous les mois</option>
                        <?php
                        $countries = self::get_countries();
                        $months = self::get_available_months();
                        foreach ($months as $month): ?>
                            <option value="<?php echo esc_attr($month->month); ?>">
                                <?php echo esc_html($month->month_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button id="btn-toggle-advanced" class="btn-filter">
                        ⚙️ <span class="btn-text">Plus de filtres</span> <span class="toggle-arrow">▼</span>
                    </button>

                    <button id="btn-view-toggle" class="btn-filter" title="Changer la vue">
                        📋 <span class="btn-text">Vue</span>
                    </button>
                </div>

                <!-- Filtres avancés (cachés par défaut) -->
                <div class="filter-advanced-section" id="advanced-filters" style="display: none;">
                    <div class="filter-advanced-grid">
                        <div class="filter-item-minimal">
                            <label for="filter-departure-country">🛫 Départ</label>
                            <select id="filter-departure-country" class="colis224-filter">
                                <option value="">Tous les pays</option>
                                <?php foreach ($countries as $code => $country): ?>
                                    <option value="<?php echo esc_attr($code); ?>">
                                        <?php echo $country['flag'] . ' ' . esc_html($country['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-item-minimal">
                            <label for="filter-arrival-country">📍 Arrivée</label>
                            <select id="filter-arrival-country" class="colis224-filter">
                                <option value="">Tous les pays</option>
                                <?php foreach ($countries as $code => $country): ?>
                                    <option value="<?php echo esc_attr($code); ?>">
                                        <?php echo $country['flag'] . ' ' . esc_html($country['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-item-minimal">
                            <label for="filter-transport-type">🚀 Transport</label>
                            <select id="filter-transport-type" class="colis224-filter">
                                <option value="">Tous</option>
                                <option value="plane">✈️ Avion</option>
                                <option value="boat">🚢 Bateau</option>
                            </select>
                        </div>

                        <div class="filter-item-minimal">
                            <button id="btn-filter-reset" class="btn-reset-minimal">
                                🔄 Réinitialiser
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tags des filtres actifs -->
                <div class="active-filters-minimal" id="active-filters" style="display:none;">
                    <span class="filter-tags"></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Zone d'affichage -->
            <div class="colis224-departures-display">
                <!-- Flèches de navigation -->
                <?php if ($atts['layout'] === 'slider'): ?>
                <button class="slider-arrow slider-arrow-left" id="slider-arrow-left">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <?php endif; ?>
                
                <div class="departures-slider" id="departures-slider">
                    <?php echo self::render_departures_html($atts); ?>
                </div>
                
                <!-- Flèche droite -->
                <?php if ($atts['layout'] === 'slider'): ?>
                <button class="slider-arrow slider-arrow-right" id="slider-arrow-right">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
                <?php endif; ?>
            </div>

            <!-- Indicateurs de slide (si mode slider) -->
            <?php if ($atts['layout'] === 'slider'): ?>
            <div class="slider-indicators" id="slider-indicators"></div>
            <?php endif; ?>

            <!-- Calendrier (si filtre mois activé) -->
            <div class="departures-calendar" id="departures-calendar" style="display:none;"></div>

            <!-- Bouton thème flottant en bas à droite -->
            <button id="btn-dark-mode" class="btn-theme-floating" title="Mode sombre/clair">
                🌙
            </button>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Rendu HTML des départs
     */
    private static function render_departures_html($atts) {
        // Utiliser les filtres passés dans $atts ou créer un tableau vide
        $filters = isset($atts['filters']) ? $atts['filters'] : array();
        
        // Ajouter le transport_type si spécifié directement dans $atts
        if (!empty($atts['transport_type']) && empty($filters['transport_type'])) {
            $filters['transport_type'] = $atts['transport_type'];
        }

        $departures = self::get_departures($filters);
        $countries = self::get_countries();

        if (empty($departures)) {
            return '
            <div class="no-departures">
                <div class="no-results-icon">😔</div>
                <h3>Aucun départ trouvé</h3>
                <p>Désolé, aucun départ ne correspond à vos critères de recherche.</p>
                <p class="suggestion">💡 <strong>Suggestions:</strong> Essayez de modifier vos filtres ou contactez notre service client pour plus d\'informations.</p>

                <div class="contact-buttons">
                    <a href="https://wa.me/224626526735?text=' . urlencode('Bonjour, je cherche des informations sur les départs disponibles depuis la Guinée.') . '" target="_blank" class="btn-contact btn-guinea">
                        <span class="btn-icon">📞</span>
                        <div class="btn-content">
                            <strong>Agence Guinée</strong>
                            <span>+224 626 526 735</span>
                        </div>
                    </a>

                    <a href="https://wa.me/224620178930?text=' . urlencode('Bonjour, j\'aimerais avoir des informations sur vos services de départs internationaux.') . '" target="_blank" class="btn-contact btn-international">
                        <span class="btn-icon">🌍</span>
                        <div class="btn-content">
                            <strong>Service International</strong>
                            <span>+224 620 178 930</span>
                        </div>
                    </a>
                </div>

                <div class="no-results-footer">
                    <p>📧 Email: <a href="mailto:contact@colis224.com">contact@colis224.com</a></p>
                    <p>🌐 Site web: <a href="https://www.colis224.com" target="_blank">www.colis224.com</a></p>
                </div>
            </div>';
        }

        $html = '';
        foreach ($departures as $dep) {
            $transport_icon = $dep->transport_type === 'plane' ? '✈️' : '🚢';
            $transport_label = $dep->transport_type === 'plane' ? 'Avion' : 'Bateau';

            $departure_flag = isset($countries[$dep->departure_country_code]) ? $countries[$dep->departure_country_code]['flag'] : '🏳️';
            $arrival_flag = isset($countries[$dep->arrival_country_code]) ? $countries[$dep->arrival_country_code]['flag'] : '🏳️';

            $departure_datetime = new DateTime($dep->departure_date . ' ' . ($dep->departure_time ?? '00:00:00'));
            $formatted_date = $departure_datetime->format('d/m/Y');
            $formatted_time = $dep->departure_time ? $departure_datetime->format('H:i') : '';

            $days_until = floor((strtotime($dep->departure_date) - time()) / 86400);
            $urgent_badge = '';
            if ($days_until <= 7 && $days_until >= 0) {
                $urgent_badge = '<span class="badge-urgent">⚡ Départ imminent</span>';
            }

            $price_html = '';
            if ($dep->price_estimate && $dep->price_estimate > 0) {
                $price_html = '<div class="departure-price">💰 ' . number_format($dep->price_estimate, 0, ',', ' ') . ' ' . $dep->currency . '</div>';
            }

            $seats_html = '';
            if ($dep->available_seats !== null && $dep->available_seats > 0) {
                if ($dep->transport_type === 'boat') {
                    // Pour les bateaux : afficher en CBM
                    $seats_html = '<div class="departure-seats">📦 ' . $dep->available_seats . ' cbm disponible</div>';
                } else {
                    // Pour les avions : afficher en KG
                    $seats_html = '<div class="departure-seats">⚖️ ' . $dep->available_seats . ' kg disponible</div>';
                }
            }

            $whatsapp_message = urlencode("Bonjour, je souhaite réserver pour le départ:\n📍 {$dep->departure_city} → {$dep->arrival_city}\n📅 Date: {$formatted_date}\n{$transport_icon} Transport: {$transport_label}\n\nMerci!");
            $whatsapp_url = "https://wa.me/{$dep->whatsapp_number}?text={$whatsapp_message}";

            $html .= '<div class="departure-card" data-transport="' . esc_attr($dep->transport_type) . '" data-departure-country="' . esc_attr($dep->departure_country_code) . '" data-arrival-country="' . esc_attr($dep->arrival_country_code) . '" data-month="' . date('Y-m', strtotime($dep->departure_date)) . '">';
            $html .= '<div class="departure-header">';
            $html .= '<span class="transport-badge transport-' . esc_attr($dep->transport_type) . '">' . $transport_icon . ' ' . $transport_label . '</span>';
            $html .= $urgent_badge;
            $html .= '</div>';

            $html .= '<div class="departure-route">';
            $html .= '<div class="route-point">';
            $html .= '<div class="flag">' . $departure_flag . '</div>';
            $html .= '<div class="city">' . esc_html($dep->departure_city) . '</div>';
            $html .= '<div class="country">' . esc_html($dep->departure_country) . '</div>';
            $html .= '</div>';

            $html .= '<div class="route-arrow">→</div>';

            $html .= '<div class="route-point">';
            $html .= '<div class="flag">' . $arrival_flag . '</div>';
            $html .= '<div class="city">' . esc_html($dep->arrival_city) . '</div>';
            $html .= '<div class="country">' . esc_html($dep->arrival_country) . '</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="departure-info">';
            $html .= '<div class="departure-date">📅 ' . $formatted_date . ($formatted_time ? ' à ' . $formatted_time : '') . '</div>';
            if ($dep->estimated_duration) {
                $html .= '<div class="departure-duration">⏱️ ' . esc_html($dep->estimated_duration) . '</div>';
            }
            $html .= $price_html;
            $html .= $seats_html;
            $html .= '</div>';

            $html .= '<div class="departure-actions">';
            $html .= '<a href="' . esc_url($whatsapp_url) . '" target="_blank" class="btn-whatsapp">';
            $html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>';
            $html .= ' Réserver via WhatsApp';
            $html .= '</a>';
            $html .= '</div>';

            $html .= '</div>';
        }

        return $html;
    }

    /**
     * AJAX: Charger les départs filtrés
     */
    public static function ajax_load_departures() {
        check_ajax_referer('colis224_departures_nonce', 'nonce');

        $filters = array();

        if (!empty($_POST['departure_country'])) {
            $filters['departure_country'] = sanitize_text_field($_POST['departure_country']);
        }

        if (!empty($_POST['arrival_country'])) {
            $filters['arrival_country'] = sanitize_text_field($_POST['arrival_country']);
        }

        if (!empty($_POST['transport_type'])) {
            $filters['transport_type'] = sanitize_text_field($_POST['transport_type']);
        }

        if (!empty($_POST['month'])) {
            $filters['month'] = sanitize_text_field($_POST['month']);
        }

        // CORRECTION : Passer les filtres dans les attributs
        $atts = array(
            'layout' => 'slider',
            'filters' => $filters
        );
        $html = self::render_departures_html($atts);

        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Abonnement aux notifications email pour nouveaux départs
     */
    public static function ajax_subscribe_notifications() {
        check_ajax_referer('colis224_departures_nonce', 'nonce');

        if (empty($_POST['email'])) {
            wp_send_json_error(array('message' => 'Adresse email manquante.'));
        }

        $email = sanitize_email($_POST['email']);

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Adresse email invalide.'));
        }

        global $wpdb;
        $table_notifications = $wpdb->prefix . 'colis224_departure_notifications';

        // Créer la table si elle n'existe pas
        $sql = "CREATE TABLE IF NOT EXISTS $table_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Vérifier si l'email existe déjà
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_notifications WHERE email = %s",
            $email
        ));

        if ($existing) {
            // Réactiver si désactivé
            $wpdb->update(
                $table_notifications,
                array('is_active' => 1),
                array('email' => $email),
                array('%d'),
                array('%s')
            );

            wp_send_json_success(array(
                'message' => 'Votre abonnement a été réactivé avec succès!'
            ));
        } else {
            // Ajouter un nouvel abonné
            $inserted = $wpdb->insert(
                $table_notifications,
                array(
                    'email' => $email,
                    'is_active' => 1
                ),
                array('%s', '%d')
            );

            if ($inserted) {
                // Envoyer un email de confirmation
                $subject = '✅ Abonnement confirmé - Notifications Départs Colis224';
                $message = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f8f9fa; padding: 30px; }
                            .footer { background: #2d3748; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
                            .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>🚀 Bienvenue chez Colis224!</h1>
                            </div>
                            <div class='content'>
                                <p>Bonjour,</p>
                                <p>Vous êtes maintenant abonné aux notifications de nouveaux départs!</p>
                                <p>Vous recevrez un email chaque fois qu'un nouveau départ est ajouté à notre plateforme.</p>
                                <p><strong>Avantages de votre abonnement:</strong></p>
                                <ul>
                                    <li>✅ Soyez informé en premier des nouveaux départs</li>
                                    <li>✅ Ne manquez plus aucune destination</li>
                                    <li>✅ Réservez rapidement les meilleures places</li>
                                </ul>
                                <p style='text-align: center;'>
                                    <a href='" . home_url() . "' class='btn'>Voir les départs disponibles</a>
                                </p>
                            </div>
                            <div class='footer'>
                                <p>📞 Agence Guinée: +224 626 526 735</p>
                                <p>🌍 Service International: +224 620 178 930</p>
                                <p>📧 Email: contact@colis224.com</p>
                                <p style='font-size: 12px; margin-top: 20px;'>
                                    Pour vous désabonner, <a href='#' style='color: #667eea;'>cliquez ici</a>
                                </p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";

                $headers = array('Content-Type: text/html; charset=UTF-8');
                wp_mail($email, $subject, $message, $headers);

                wp_send_json_success(array(
                    'message' => 'Vous êtes maintenant abonné aux notifications!'
                ));
            } else {
                wp_send_json_error(array('message' => 'Erreur lors de l\'enregistrement.'));
            }
        }
    }

    /**
     * Envoyer une notification email à tous les abonnés pour un nouveau départ
     */
    public static function notify_subscribers_new_departure($departure_id) {
        global $wpdb;

        // Récupérer le départ
        $table = $wpdb->prefix . 'colis224_departures';
        $departure = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $departure_id
        ));

        if (!$departure) {
            return;
        }

        // Récupérer tous les abonnés actifs
        $table_notifications = $wpdb->prefix . 'colis224_departure_notifications';
        $subscribers = $wpdb->get_results(
            "SELECT email FROM $table_notifications WHERE is_active = 1"
        );

        if (empty($subscribers)) {
            return;
        }

        $transport_icon = $departure->transport_type === 'plane' ? '✈️' : '🚢';
        $transport_label = $departure->transport_type === 'plane' ? 'Avion' : 'Bateau';

        $subject = "🚀 Nouveau Départ: {$departure->departure_city} → {$departure->arrival_city}";
        $message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f8f9fa; padding: 30px; }
                    .departure-card { background: white; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #667eea; }
                    .btn-whatsapp { display: inline-block; padding: 15px 30px; background: #25D366; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                    .footer { background: #2d3748; color: white; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🚀 Nouveau Départ Disponible!</h1>
                    </div>
                    <div class='content'>
                        <p>Bonjour,</p>
                        <p>Un nouveau départ vient d'être ajouté et pourrait vous intéresser:</p>

                        <div class='departure-card'>
                            <h2>{$transport_icon} {$departure->departure_city} → {$departure->arrival_city}</h2>
                            <p><strong>📅 Date:</strong> " . date('d/m/Y', strtotime($departure->departure_date)) . "</p>
                            <p><strong>🚀 Transport:</strong> $transport_label</p>
                            <p><strong>🌍 Pays:</strong> {$departure->departure_country} → {$departure->arrival_country}</p>";

        if ($departure->price_estimate) {
            $message .= "<p><strong>💰 Prix:</strong> " . number_format($departure->price_estimate, 0, ',', ' ') . " {$departure->currency}</p>";
        }

        if ($departure->available_seats) {
            if ($departure->transport_type === 'boat') {
                $message .= "<p><strong>📦 Capacité disponible:</strong> {$departure->available_seats} cbm</p>";
            } else {
                $message .= "<p><strong>📦 Capacité disponible:</strong> {$departure->available_seats} kg</p>";
            }
        }

        $whatsapp_message = urlencode("Bonjour, je souhaite réserver pour le nouveau départ {$departure->departure_city} → {$departure->arrival_city} le " . date('d/m/Y', strtotime($departure->departure_date)));
        $whatsapp_url = "https://wa.me/" . ($departure->whatsapp_number ?: '224620178930') . "?text={$whatsapp_message}";

        $message .= "
                        </div>

                        <p style='text-align: center;'>
                            <a href='$whatsapp_url' class='btn-whatsapp'>📱 Réserver via WhatsApp</a>
                        </p>

                        <p style='color: #999; font-size: 14px;'>
                            ⏰ Réservez rapidement pour ne pas manquer cette opportunité!
                        </p>
                    </div>
                    <div class='footer'>
                        <p>📞 Agence Guinée: +224 626 526 735</p>
                        <p>🌍 Service International: +224 620 178 930</p>
                        <p style='font-size: 12px; margin-top: 20px;'>
                            Pour vous désabonner, <a href='#' style='color: #667eea;'>cliquez ici</a>
                        </p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Envoyer à tous les abonnés (en BCC pour la confidentialité)
        $to = get_option('admin_email'); // Email principal
        $bcc_emails = array_map(function($sub) { return $sub->email; }, $subscribers);
        $headers[] = 'Bcc: ' . implode(',', $bcc_emails);

        wp_mail($to, $subject, $message, $headers);
    }
}

// Enregistrer le shortcode
add_shortcode('colis224_departures', array('Colis224_Departures', 'departures_shortcode'));

// AJAX - Chargement des départs filtrés
add_action('wp_ajax_colis224_load_departures', array('Colis224_Departures', 'ajax_load_departures'));
add_action('wp_ajax_nopriv_colis224_load_departures', array('Colis224_Departures', 'ajax_load_departures'));

// AJAX - Abonnement aux notifications email
add_action('wp_ajax_colis224_subscribe_departure_notifications', array('Colis224_Departures', 'ajax_subscribe_notifications'));
add_action('wp_ajax_nopriv_colis224_subscribe_departure_notifications', array('Colis224_Departures', 'ajax_subscribe_notifications'));
