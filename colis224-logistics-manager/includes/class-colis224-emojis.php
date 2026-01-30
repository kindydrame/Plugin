<?php
/**
 * Colis224 Emojis Helper
 *
 * Gère les emojis pour les pays, moyens de transport et statuts
 * Version: 2.18.26
 *
 * @package Colis224_Logistics_Manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Colis224_Emojis {

    /**
     * Mapping des pays vers leurs drapeaux
     */
    private static $country_flags = array(
        // Afrique
        'Guinée' => '🇬🇳',
        'Sénégal' => '🇸🇳',
        'Côte d\'Ivoire' => '🇨🇮',
        'Mali' => '🇲🇱',
        'Burkina Faso' => '🇧🇫',
        'Niger' => '🇳🇪',
        'Togo' => '🇹🇬',
        'Bénin' => '🇧🇯',
        'Ghana' => '🇬🇭',
        'Nigeria' => '🇳🇬',
        'Cameroun' => '🇨🇲',
        'Gabon' => '🇬🇦',
        'Congo' => '🇨🇬',
        'RDC' => '🇨🇩',
        'Angola' => '🇦🇴',
        'Afrique du Sud' => '🇿🇦',
        'Kenya' => '🇰🇪',
        'Éthiopie' => '🇪🇹',
        'Tanzanie' => '🇹🇿',
        'Ouganda' => '🇺🇬',
        'Rwanda' => '🇷🇼',
        'Maroc' => '🇲🇦',
        'Algérie' => '🇩🇿',
        'Tunisie' => '🇹🇳',
        'Égypte' => '🇪🇬',
        'Libye' => '🇱🇾',

        // Europe
        'France' => '🇫🇷',
        'Belgique' => '🇧🇪',
        'Suisse' => '🇨🇭',
        'Espagne' => '🇪🇸',
        'Portugal' => '🇵🇹',
        'Italie' => '🇮🇹',
        'Allemagne' => '🇩🇪',
        'Royaume-Uni' => '🇬🇧',
        'Pays-Bas' => '🇳🇱',
        'Luxembourg' => '🇱🇺',

        // Asie
        'Chine' => '🇨🇳',
        'Japon' => '🇯🇵',
        'Corée du Sud' => '🇰🇷',
        'Inde' => '🇮🇳',
        'Thaïlande' => '🇹🇭',
        'Vietnam' => '🇻🇳',
        'Indonésie' => '🇮🇩',
        'Malaisie' => '🇲🇾',
        'Singapour' => '🇸🇬',
        'Philippines' => '🇵🇭',
        'Émirats Arabes Unis' => '🇦🇪',
        'Arabie Saoudite' => '🇸🇦',
        'Qatar' => '🇶🇦',
        'Koweït' => '🇰🇼',
        'Turquie' => '🇹🇷',

        // Amérique
        'États-Unis' => '🇺🇸',
        'Canada' => '🇨🇦',
        'Mexique' => '🇲🇽',
        'Brésil' => '🇧🇷',
        'Argentine' => '🇦🇷',
        'Chili' => '🇨🇱',
        'Colombie' => '🇨🇴',
        'Pérou' => '🇵🇪',

        // Océanie
        'Australie' => '🇦🇺',
        'Nouvelle-Zélande' => '🇳🇿',
    );

    /**
     * Mapping des moyens de transport vers leurs emojis
     */
    private static $transport_emojis = array(
        'Avion' => '✈️',
        'Bateau' => '🚢',
        'Express' => '⚡',
        'Train' => '🚂',
        'Camion' => '🚛',
        'Voiture' => '🚗',
        'Moto' => '🏍️',
        'Vélo' => '🚴',
    );

    /**
     * Mapping des statuts de colis vers leurs emojis
     */
    private static $parcel_status_emojis = array(
        'En attente' => '⏳',
        'Expédié' => '📦',
        'En transit' => '🚚',
        'Livré' => '✅',
        'Retour' => '↩️',
    );

    /**
     * Mapping des statuts de paiement vers leurs emojis
     */
    private static $payment_status_emojis = array(
        'Payé' => '💰',
        'Partiel' => '💵',
        'Non payé' => '❌',
    );

    /**
     * Obtenir l'emoji d'un pays
     *
     * @param string $country_name Nom du pays
     * @return string Emoji du drapeau ou emoji terre par défaut
     */
    public static function get_country_flag($country_name) {
        if (empty($country_name)) {
            return '🌍';
        }

        // Normaliser le nom (enlever les accents pour matching)
        $normalized = self::normalize_string($country_name);

        // Chercher d'abord avec le nom exact
        if (isset(self::$country_flags[$country_name])) {
            return self::$country_flags[$country_name];
        }

        // Chercher avec normalisation
        foreach (self::$country_flags as $country => $flag) {
            if (self::normalize_string($country) === $normalized) {
                return $flag;
            }
        }

        // Par défaut: emoji terre
        return '🌍';
    }

    /**
     * Obtenir l'emoji d'un moyen de transport
     *
     * @param string $transport_name Nom du moyen de transport
     * @return string Emoji du transport ou emoji colis par défaut
     */
    public static function get_transport_emoji($transport_name) {
        if (empty($transport_name)) {
            return '📦';
        }

        // Normaliser le nom
        $normalized = self::normalize_string($transport_name);

        // Chercher d'abord avec le nom exact
        if (isset(self::$transport_emojis[$transport_name])) {
            return self::$transport_emojis[$transport_name];
        }

        // Chercher avec normalisation
        foreach (self::$transport_emojis as $transport => $emoji) {
            if (self::normalize_string($transport) === $normalized) {
                return $emoji;
            }
        }

        // Chercher par similarité (contient)
        foreach (self::$transport_emojis as $transport => $emoji) {
            if (stripos($transport_name, $transport) !== false || stripos($transport, $transport_name) !== false) {
                return $emoji;
            }
        }

        // Par défaut: emoji colis
        return '📦';
    }

    /**
     * Obtenir l'emoji d'un statut de colis
     *
     * @param string $status Statut du colis
     * @return string Emoji du statut
     */
    public static function get_parcel_status_emoji($status) {
        if (isset(self::$parcel_status_emojis[$status])) {
            return self::$parcel_status_emojis[$status];
        }
        return '📦';
    }

    /**
     * Obtenir l'emoji d'un statut de paiement
     *
     * @param string $status Statut de paiement
     * @return string Emoji du statut
     */
    public static function get_payment_status_emoji($status) {
        if (isset(self::$payment_status_emojis[$status])) {
            return self::$payment_status_emojis[$status];
        }
        return '💵';
    }

    /**
     * Obtenir la liste des pays avec drapeaux pour un select
     *
     * @param array $countries Liste des pays (objets ou tableau)
     * @param string $selected_id ID du pays sélectionné
     * @param string $id_field Nom du champ ID
     * @param string $name_field Nom du champ nom
     * @return string HTML des options
     */
    public static function render_country_options($countries, $selected_id = '', $id_field = 'id', $name_field = 'name') {
        $html = '<option value="">Sélectionner un pays</option>';

        foreach ($countries as $country) {
            $id = is_object($country) ? $country->$id_field : $country[$id_field];
            $name = is_object($country) ? $country->$name_field : $country[$name_field];
            $flag = self::get_country_flag($name);
            $selected = ($id == $selected_id) ? 'selected' : '';

            $html .= sprintf(
                '<option value="%s" %s>%s %s</option>',
                esc_attr($id),
                $selected,
                $flag,
                esc_html($name)
            );
        }

        return $html;
    }

    /**
     * Obtenir la liste des transports avec emojis pour un select
     *
     * @param array $transports Liste des moyens de transport
     * @param string $selected_id ID du transport sélectionné
     * @param string $id_field Nom du champ ID
     * @param string $name_field Nom du champ nom
     * @return string HTML des options
     */
    public static function render_transport_options($transports, $selected_id = '', $id_field = 'id', $name_field = 'name') {
        $html = '<option value="">Sélectionner un mode</option>';

        foreach ($transports as $transport) {
            $id = is_object($transport) ? $transport->$id_field : $transport[$id_field];
            $name = is_object($transport) ? $transport->$name_field : $transport[$name_field];
            $emoji = self::get_transport_emoji($name);
            $selected = ($id == $selected_id) ? 'selected' : '';

            $html .= sprintf(
                '<option value="%s" %s>%s %s</option>',
                esc_attr($id),
                $selected,
                $emoji,
                esc_html($name)
            );
        }

        return $html;
    }

    /**
     * Normaliser une chaîne pour comparaison
     *
     * @param string $str Chaîne à normaliser
     * @return string Chaîne normalisée
     */
    private static function normalize_string($str) {
        // Convertir en minuscules
        $str = mb_strtolower($str, 'UTF-8');

        // Enlever les accents
        $str = remove_accents($str);

        // Enlever les espaces multiples
        $str = preg_replace('/\s+/', ' ', $str);

        // Trim
        $str = trim($str);

        return $str;
    }

    /**
     * Obtenir tous les drapeaux disponibles
     *
     * @return array Tableau pays => drapeau
     */
    public static function get_all_country_flags() {
        return self::$country_flags;
    }

    /**
     * Obtenir tous les emojis de transport
     *
     * @return array Tableau transport => emoji
     */
    public static function get_all_transport_emojis() {
        return self::$transport_emojis;
    }
}
