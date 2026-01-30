<?php
/**
 * Colis224 Statistics AJAX Handlers
 *
 * Gère les requêtes AJAX pour les statistiques avancées
 * Version: 2.18.22
 *
 * @package Colis224_Logistics_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Colis224_Stats_AJAX {

    /**
     * Initialiser les hooks AJAX
     */
    public static function init() {
        // AJAX pour utilisateurs connectés
        add_action('wp_ajax_colis224_get_filtered_stats', array(__CLASS__, 'ajax_get_filtered_stats'));
        add_action('wp_ajax_colis224_export_stats_csv', array(__CLASS__, 'ajax_export_stats_csv'));
        add_action('wp_ajax_colis224_get_filter_options', array(__CLASS__, 'ajax_get_filter_options'));
    }

    /**
     * AJAX: Obtenir les statistiques filtrées
     */
    public static function ajax_get_filtered_stats() {
        // v2.18.25: Vérifier le nonce avec meilleur logging
        if (!isset($_POST['nonce'])) {
            wp_send_json_error(array('message' => '🚫 Nonce manquant. Veuillez rafraîchir la page.'));
            return;
        }

        if (!wp_verify_nonce($_POST['nonce'], 'colis224_dashboard_nonce')) {
            wp_send_json_error(array('message' => '🚫 Nonce invalide. Veuillez rafraîchir la page.'));
            return;
        }

        // Vérifier les permissions
        if (!current_user_can('manage_options') && !current_user_can('colis224_view_parcels')) {
            wp_send_json_error(array('message' => '🚫 Permission refusée.'));
            return;
        }

        // v2.18.25: Récupérer les filtres (supporter JSON et array)
        $raw_filters = isset($_POST['filters']) ? $_POST['filters'] : array();

        // Si c'est une chaîne JSON, la décoder
        if (is_string($raw_filters)) {
            $raw_filters = json_decode(stripslashes($raw_filters), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $raw_filters = array();
            }
        }

        // Valider les filtres
        $validated_filters = Colis224_Dashboard_Filters::validate_filters($raw_filters);

        // Obtenir les statistiques complètes
        $stats = Colis224_Dashboard_Advanced_Stats::get_complete_stats($validated_filters);

        // Générer le HTML
        $html = Colis224_Dashboard_Advanced_Stats::render_stats_html($stats);

        // Préparer les données du résumé
        $summary = array(
            'period' => isset($validated_filters['time_period']) ? $validated_filters['time_period'] : '1_month',
            'date_start' => $validated_filters['date_start'],
            'date_end' => $validated_filters['date_end'],
            'total_parcels' => $stats['payment']->total_parcels,
            'total_revenue' => $stats['payment']->total_amount,
            'paid_count' => $stats['payment']->paid_count,
            'unpaid_count' => $stats['payment']->unpaid_count
        );

        wp_send_json_success(array(
            'html' => $html,
            'summary' => $summary,
            'filters' => $validated_filters
        ));
    }

    /**
     * AJAX: Exporter les statistiques en CSV
     */
    public static function ajax_export_stats_csv() {
        // v2.18.25: Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_dashboard_nonce')) {
            wp_die('🚫 Erreur de sécurité. Veuillez rafraîchir la page.');
            return;
        }

        // Vérifier les permissions
        if (!current_user_can('manage_options') && !current_user_can('colis224_view_parcels')) {
            wp_die('🚫 Permission refusée.');
            return;
        }

        // v2.18.25: Récupérer les filtres (supporter JSON et array)
        $raw_filters = isset($_POST['filters']) ? $_POST['filters'] : array();

        // Si c'est une chaîne JSON, la décoder
        if (is_string($raw_filters)) {
            $raw_filters = json_decode(stripslashes($raw_filters), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $raw_filters = array();
            }
        }

        // Valider les filtres
        $validated_filters = Colis224_Dashboard_Filters::validate_filters($raw_filters);

        // Obtenir les statistiques
        $stats = Colis224_Dashboard_Advanced_Stats::get_complete_stats($validated_filters);

        // Générer le nom du fichier
        $filename = 'colis224-stats-' . date('Y-m-d-His') . '.csv';

        // Exporter en CSV (cette fonction termine le script avec exit)
        Colis224_Dashboard_Advanced_Stats::export_to_csv($stats, $filename);
    }

    /**
     * AJAX: Obtenir les options de filtres dynamiques
     * Utilisé pour mettre à jour les dropdowns après un premier filtrage
     */
    public static function ajax_get_filter_options() {
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'colis224_dashboard_nonce')) {
            wp_send_json_error(array('message' => '🚫 Erreur de sécurité.'));
            return;
        }

        // Vérifier les permissions
        if (!current_user_can('manage_options') && !current_user_can('colis224_view_parcels')) {
            wp_send_json_error(array('message' => '🚫 Permission refusée.'));
            return;
        }

        // Obtenir les options
        $options = array(
            'agents' => Colis224_Dashboard_Filters::get_available_agents(),
            'origin_countries' => Colis224_Dashboard_Filters::get_available_origin_countries(),
            'destination_countries' => Colis224_Dashboard_Filters::get_available_destination_countries(),
            'drivers' => Colis224_Dashboard_Filters::get_available_drivers(),
            'partners' => Colis224_Dashboard_Filters::get_available_partners()
        );

        wp_send_json_success($options);
    }
}

// Initialiser les hooks AJAX
Colis224_Stats_AJAX::init();
