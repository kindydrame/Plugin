<?php
/**
 * Colis224 Pricing Engine
 *
 * Moteur de calcul des tarifs
 *
 * @package Colis224_Logistics_Manager
 * @since 2.19.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Pricing_Engine {

    /**
     * Calculer tarif Chine → Guinée
     *
     * @param string $category Catégorie (essentiels, iphone, macbook, etc.)
     * @param float $quantity Poids (kg) ou Nombre de pièces
     * @return array Résultat du calcul
     */
    public static function calculate_china_guinea($category, $quantity) {
        $rates = Colis224_Pricing_Data::get_china_guinea_rates();

        if (!isset($rates[$category])) {
            return array(
                'success' => false,
                'message' => 'Catégorie invalide'
            );
        }

        if ($quantity <= 0) {
            return array(
                'success' => false,
                'message' => 'La quantité doit être supérieure à 0'
            );
        }

        $rate = $rates[$category];
        $total = $rate['price'] * $quantity;

        return array(
            'success' => true,
            'category' => $rate['label'],
            'price_type' => $rate['type'],
            'unit_price' => $rate['price'],
            'currency' => $rate['currency'],
            'unit' => $rate['unit'],
            'quantity' => $quantity,
            'total' => $total,
            'formatted_unit_price' => number_format($rate['price'], 0, ',', ' '),
            'formatted_total' => number_format($total, 0, ',', ' ')
        );
    }

    /**
     * Calculer tarif CBM Maritime
     *
     * @param float $length Longueur en cm
     * @param float $width Largeur en cm
     * @param float $height Hauteur en cm
     * @param string $unit Unité (cm ou m)
     * @return array Résultat du calcul
     */
    public static function calculate_cbm($length, $width, $height, $unit = 'cm') {
        if ($length <= 0 || $width <= 0 || $height <= 0) {
            return array(
                'success' => false,
                'message' => 'Toutes les dimensions doivent être supérieures à 0'
            );
        }

        // Convertir en mètres si nécessaire
        if ($unit === 'cm') {
            $length = $length / 100;
            $width = $width / 100;
            $height = $height / 100;
        }

        // Calculer le CBM
        $cbm = $length * $width * $height;

        // Récupérer le tarif
        $rate_info = Colis224_Pricing_Data::get_cbm_rate();
        $total = $cbm * $rate_info['price_per_cbm'];

        return array(
            'success' => true,
            'length_m' => $length,
            'width_m' => $width,
            'height_m' => $height,
            'cbm' => $cbm,
            'formatted_cbm' => number_format($cbm, 3, ',', ' '),
            'price_per_cbm' => $rate_info['price_per_cbm'],
            'formatted_price_per_cbm' => number_format($rate_info['price_per_cbm'], 0, ',', ' '),
            'total' => $total,
            'formatted_total' => number_format($total, 0, ',', ' '),
            'currency' => $rate_info['currency']
        );
    }

    /**
     * Calculer tarif Guinée ↔ Monde
     *
     * @param string $country Pays de destination
     * @param string $delivery_mode Mode de livraison (bureau, relais, domicile)
     * @param string $parcel_type Type de colis
     * @param int $quantity Nombre de pièces
     * @param string $city Ville (pour Maroc)
     * @return array Résultat du calcul
     */
    public static function calculate_guinea_world($country, $delivery_mode, $parcel_type, $quantity = 1, $city = '') {
        $rates = Colis224_Pricing_Data::get_guinea_world_rates();

        if (!isset($rates[$country])) {
            return array(
                'success' => false,
                'message' => 'Pays invalide'
            );
        }

        $country_data = $rates[$country];

        // Vérifier le mode de livraison
        if (!in_array($delivery_mode, $country_data['modes'])) {
            return array(
                'success' => false,
                'message' => 'Mode de livraison non disponible pour ce pays'
            );
        }

        // Cas spécial: Maroc avec villes
        if ($country === 'maroc') {
            if (empty($city) || !isset($country_data['cities'][$city])) {
                return array(
                    'success' => false,
                    'message' => 'Veuillez sélectionner une ville pour le Maroc'
                );
            }

            $city_data = $country_data['cities'][$city];

            if (!isset($city_data['rates'][$parcel_type])) {
                return array(
                    'success' => false,
                    'message' => 'Type de colis non disponible pour cette destination'
                );
            }

            $unit_price = $city_data['rates'][$parcel_type];

        } else {
            // Pays normaux
            if (!isset($country_data['rates'][$delivery_mode][$parcel_type])) {
                return array(
                    'success' => false,
                    'message' => 'Type de colis non disponible pour cette destination et ce mode de livraison'
                );
            }

            $unit_price = $country_data['rates'][$delivery_mode][$parcel_type];
        }

        if ($quantity <= 0) {
            return array(
                'success' => false,
                'message' => 'La quantité doit être supérieure à 0'
            );
        }

        $total = $unit_price * $quantity;
        $currency = $country_data['currency'];

        // Format selon la devise
        if ($currency === 'GNF') {
            $formatted_unit = number_format($unit_price, 0, ',', ' ');
            $formatted_total = number_format($total, 0, ',', ' ');
        } else {
            $formatted_unit = number_format($unit_price, 2, ',', ' ');
            $formatted_total = number_format($total, 2, ',', ' ');
        }

        // Récupérer les labels
        $parcel_labels = Colis224_Pricing_Data::get_parcel_type_labels();
        $mode_labels = Colis224_Pricing_Data::get_delivery_mode_labels();

        return array(
            'success' => true,
            'country' => ucfirst($country),
            'country_flag' => $country_data['flag'],
            'city' => $city ? ucfirst($city) : '',
            'delivery_mode' => $mode_labels[$delivery_mode],
            'parcel_type' => $parcel_labels[$parcel_type],
            'unit_price' => $unit_price,
            'quantity' => $quantity,
            'total' => $total,
            'currency' => $currency,
            'formatted_unit_price' => $formatted_unit,
            'formatted_total' => $formatted_total
        );
    }

    /**
     * Formater un prix selon la devise
     *
     * @param float $amount Montant
     * @param string $currency Devise (GNF, EUR, USD)
     * @return string Prix formaté
     */
    public static function format_price($amount, $currency) {
        if ($currency === 'GNF') {
            return number_format($amount, 0, ',', ' ') . ' FG';
        } elseif ($currency === 'EUR') {
            return number_format($amount, 2, ',', ' ') . ' €';
        } elseif ($currency === 'USD') {
            return number_format($amount, 2, ',', ' ') . ' $';
        }

        return number_format($amount, 2, ',', ' ') . ' ' . $currency;
    }
}
