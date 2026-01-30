<?php
/**
 * Colis224 Pricing Data
 *
 * Gestion des données tarifaires pour tous les services de livraison
 *
 * @package Colis224_Logistics_Manager
 * @since 2.19.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Pricing_Data {

    /**
     * Tarifs Chine → Guinée (Aérien)
     *
     * @return array
     */
    public static function get_china_guinea_rates() {
        return array(
            // Articles facturés au kg
            'essentiels' => array(
                'label' => '📦 Essentiels du quotidien',
                'type' => 'per_kg',
                'price' => 200000,
                'currency' => 'GNF',
                'unit' => 'kg'
            ),
            'sensibles' => array(
                'label' => '🔋 Batteries / Liquides',
                'type' => 'per_kg',
                'price' => 250000,
                'currency' => 'GNF',
                'unit' => 'kg'
            ),
            'express' => array(
                'label' => '⚡ Express (4-7 jours)',
                'type' => 'per_kg',
                'price' => 300000,
                'currency' => 'GNF',
                'unit' => 'kg'
            ),

            // Articles facturés à la pièce
            'macbook' => array(
                'label' => '💻 MacBook Portable',
                'type' => 'per_piece',
                'price' => 1000000,
                'currency' => 'GNF',
                'unit' => 'pièce'
            ),
            'autres_ordinateurs' => array(
                'label' => '💻 Autres ordinateurs portables',
                'type' => 'per_piece',
                'price' => 800000,
                'currency' => 'GNF',
                'unit' => 'pièce'
            ),
            'iphone' => array(
                'label' => '📱 iPhone',
                'type' => 'per_piece',
                'price' => 1000000,
                'currency' => 'GNF',
                'unit' => 'pièce'
            ),
            'samsung' => array(
                'label' => '📱 Téléphones Samsung',
                'type' => 'per_piece',
                'price' => 700000,
                'currency' => 'GNF',
                'unit' => 'pièce'
            ),
            'autres_telephones' => array(
                'label' => '📱 Autres téléphones',
                'type' => 'per_piece',
                'price' => 550000,
                'currency' => 'GNF',
                'unit' => 'pièce'
            )
        );
    }

    /**
     * Tarif Maritime CBM
     *
     * @return array
     */
    public static function get_cbm_rate() {
        return array(
            'price_per_cbm' => 4700000,
            'currency' => 'GNF',
            'unit' => 'CBM',
            'label' => '🚢 Expédition Maritime'
        );
    }

    /**
     * Tarifs Conteneur France → Conakry (v2.19.1)
     * Transport maritime depuis entrepôt Paris
     *
     * @return array
     */
    public static function get_container_france_conakry_rates() {
        return array(
            // Emballages et volumes
            'carton_standard' => array(
                'label' => '📦 Carton standard',
                'price' => 120,
                'unit' => 'pièce',
                'category' => 'Emballages'
            ),
            'fut' => array(
                'label' => '🛢️ Fut',
                'price' => 180,
                'unit' => 'pièce',
                'category' => 'Emballages'
            ),
            'tv_metre' => array(
                'label' => '📺 TV (au mètre)',
                'price' => 3.5,
                'unit' => 'mètre',
                'category' => 'Électronique'
            ),
            'm3' => array(
                'label' => '📐 M³ (mètre cube)',
                'price' => 650,
                'unit' => 'm³',
                'category' => 'Volume'
            ),
            'poids_kg' => array(
                'label' => '⚖️ Poids (au kg)',
                'price' => 4,
                'unit' => 'kg',
                'category' => 'Poids'
            ),
            'metre_lineaire' => array(
                'label' => '📏 Mètre linéaire',
                'price' => 1500,
                'unit' => 'mètre',
                'category' => 'Volume'
            ),

            // Meubles
            'canape' => array(
                'label' => '🛋️ Canapé',
                'price' => 120,
                'unit' => 'place',
                'category' => 'Meubles'
            ),
            'matelas' => array(
                'label' => '🛏️ Matelas',
                'price' => 50,
                'unit' => 'place',
                'category' => 'Meubles'
            ),
            'chaise_encombrant_pliant' => array(
                'label' => '💺 Chaise encombrant (pliant)',
                'price' => 15,
                'unit' => 'pièce',
                'category' => 'Meubles'
            ),
            'chaise_encombrant_non_pliant' => array(
                'label' => '💺 Chaise encombrant (non pliant)',
                'price' => 25,
                'unit' => 'pièce',
                'category' => 'Meubles'
            ),
            'chaise_bureau' => array(
                'label' => '🪑 Chaise de bureau',
                'price' => 55,
                'unit' => 'pièce',
                'category' => 'Meubles'
            ),
            'grande_chaise_bureau' => array(
                'label' => '🪑 Grande chaise de bureau',
                'price' => 80,
                'unit' => 'pièce',
                'category' => 'Meubles'
            ),
            'table_encombrant' => array(
                'label' => '🪑 Table encombrant',
                'price' => 0, // À définir selon dimensions
                'unit' => 'pièce',
                'category' => 'Meubles',
                'note' => 'Prix à définir selon dimensions'
            ),

            // Électroménager
            'machine_laver_gaziniere' => array(
                'label' => '🧺 Machine à laver / Gazinière',
                'price' => 140,
                'unit' => 'pièce',
                'category' => 'Électroménager'
            ),
            'micro_ondes' => array(
                'label' => '🔥 Micro-ondes',
                'price' => 50,
                'unit' => 'pièce',
                'category' => 'Électroménager'
            ),
            'frigo_americain' => array(
                'label' => '🧊 Frigo américain',
                'price' => 550,
                'unit' => 'pièce',
                'category' => 'Électroménager'
            ),

            // Portes
            'porte_avec_bati' => array(
                'label' => '🚪 Porte avec bâti',
                'price' => 50,
                'unit' => 'pièce',
                'category' => 'Portes'
            ),
            'porte_sans_bati' => array(
                'label' => '🚪 Porte sans bâti',
                'price' => 40,
                'unit' => 'pièce',
                'category' => 'Portes'
            ),
            'porte_vitree_1_battant' => array(
                'label' => '🚪 Porte vitrée (1 battant)',
                'price' => 80,
                'unit' => 'pièce',
                'category' => 'Portes'
            ),
            'porte_vitree_2_battants' => array(
                'label' => '🚪 Porte vitrée (2 battants)',
                'price' => 120,
                'unit' => 'pièce',
                'category' => 'Portes'
            ),

            // Friperie
            'friperie_45kg' => array(
                'label' => '👔 Friperie 45 kg',
                'price' => 60,
                'unit' => 'ballot',
                'category' => 'Friperie'
            ),
            'friperie_55kg' => array(
                'label' => '👔 Friperie 55 kg',
                'price' => 80,
                'unit' => 'ballot',
                'category' => 'Friperie'
            ),

            // Véhicules/Pièces
            'velo_petit' => array(
                'label' => '🚲 Vélo petit',
                'price' => 30,
                'unit' => 'pièce',
                'category' => 'Vélos'
            ),
            'velo_moyen' => array(
                'label' => '🚲 Vélo moyen',
                'price' => 40,
                'unit' => 'pièce',
                'category' => 'Vélos'
            ),
            'velo_grand' => array(
                'label' => '🚲 Vélo grand',
                'price' => 60,
                'unit' => 'pièce',
                'category' => 'Vélos'
            ),
            'moteur_sans_boite' => array(
                'label' => '⚙️ Moteur sans boîte',
                'price' => 300,
                'unit' => 'pièce',
                'category' => 'Pièces auto'
            ),
            'moteur_avec_boite' => array(
                'label' => '⚙️ Moteur avec boîte',
                'price' => 350,
                'unit' => 'pièce',
                'category' => 'Pièces auto'
            ),

            // Autres
            'mal_cantine' => array(
                'label' => '📦 Mal cantine',
                'price' => 1.5,
                'unit' => 'cm',
                'category' => 'Autres'
            )
        );
    }

    /**
     * Catégories pour tarifs conteneur
     *
     * @return array
     */
    public static function get_container_categories() {
        return array(
            'Emballages',
            'Volume',
            'Poids',
            'Meubles',
            'Électroménager',
            'Portes',
            'Friperie',
            'Vélos',
            'Pièces auto',
            'Électronique',
            'Autres'
        );
    }

    /**
     * Tarifs Guinée ↔ Monde
     * Structure: pays -> mode_livraison -> type_colis -> prix
     *
     * @return array
     */
    public static function get_guinea_world_rates() {
        return array(
            // France (EUR)
            'france' => array(
                'flag' => '🇫🇷',
                'currency' => 'EUR',
                'modes' => array('bureau', 'relais', 'domicile'),
                'rates' => array(
                    'bureau' => array(
                        'colis' => 12, 'papier' => 25, 'marques' => 25, 'permis' => 25,
                        'passeport_10' => 80, 'passeport_5' => 70, 'cosmetique' => 19,
                        'nassi_litre' => 60, 'nassi_cipa' => 35, 'nassi_coton' => 30,
                        'grigris' => 40, 'huile' => 17, 'poisson' => 17, 'dossier' => 100
                    ),
                    'relais' => array(
                        'colis' => 19, 'papier' => 30, 'marques' => 35, 'permis' => 30,
                        'passeport_10' => 90, 'passeport_5' => 75, 'cosmetique' => 24,
                        'nassi_litre' => 65, 'nassi_cipa' => 40, 'nassi_coton' => 35,
                        'grigris' => 45, 'huile' => 19, 'poisson' => 19, 'dossier' => 110
                    ),
                    'domicile' => array(
                        'colis' => 22, 'papier' => 35, 'marques' => 38, 'permis' => 35,
                        'passeport_10' => 95, 'passeport_5' => 80, 'cosmetique' => 27,
                        'nassi_litre' => 70, 'nassi_cipa' => 45, 'nassi_coton' => 40,
                        'grigris' => 50, 'huile' => 22, 'poisson' => 22, 'dossier' => 120
                    )
                )
            ),

            // Portugal (EUR)
            'portugal' => array(
                'flag' => '🇵🇹',
                'currency' => 'EUR',
                'modes' => array('relais', 'domicile'),
                'rates' => array(
                    'relais' => array(
                        'colis' => 25, 'papier' => 45, 'permis' => 50, 'dossier' => 90
                    ),
                    'domicile' => array(
                        'colis' => 30, 'papier' => 50, 'permis' => 55, 'dossier' => 100
                    )
                )
            ),

            // Espagne (EUR)
            'espagne' => array(
                'flag' => '🇪🇸',
                'currency' => 'EUR',
                'modes' => array('relais', 'domicile'),
                'rates' => array(
                    'relais' => array(
                        'colis' => 27, 'papier' => 50, 'marques' => 55, 'permis' => 60,
                        'passeport_10' => 90, 'passeport_5' => 80, 'cosmetique' => 30,
                        'nassi_litre' => 70, 'nassi_cipa' => 80, 'nassi_coton' => 55,
                        'grigris' => 70, 'huile' => 24, 'poisson' => 24, 'dossier' => 95
                    ),
                    'domicile' => array(
                        'colis' => 35, 'papier' => 55, 'marques' => 60, 'permis' => 65,
                        'passeport_10' => 100, 'passeport_5' => 90, 'cosmetique' => 35,
                        'nassi_litre' => 80, 'nassi_cipa' => 90, 'nassi_coton' => 60,
                        'grigris' => 80, 'huile' => 27, 'poisson' => 27, 'dossier' => 105
                    )
                )
            ),

            // Allemagne (EUR)
            'allemagne' => array(
                'flag' => '🇩🇪',
                'currency' => 'EUR',
                'modes' => array('domicile'),
                'rates' => array(
                    'domicile' => array(
                        'colis' => 28, 'papier' => 40, 'marques' => 45, 'permis' => 60,
                        'passeport_10' => 90, 'passeport_5' => 70, 'cosmetique' => 40,
                        'nassi_litre' => 75, 'nassi_cipa' => 60, 'nassi_coton' => 50,
                        'grigris' => 60, 'huile' => 25, 'poisson' => 25, 'dossier' => 95
                    )
                )
            ),

            // Belgique (EUR)
            'belgique' => array(
                'flag' => '🇧🇪',
                'currency' => 'EUR',
                'modes' => array('relais', 'domicile'),
                'rates' => array(
                    'relais' => array(
                        'colis' => 19, 'papier' => 25, 'marques' => 40, 'permis' => 80,
                        'passeport_10' => 110, 'passeport_5' => 100, 'cosmetique' => 30,
                        'nassi_litre' => 70, 'nassi_cipa' => 75, 'nassi_coton' => 50,
                        'grigris' => 60, 'huile' => 24, 'poisson' => 24, 'dossier' => 95
                    ),
                    'domicile' => array(
                        'colis' => 24, 'papier' => 30, 'marques' => 45, 'permis' => 90,
                        'passeport_10' => 130, 'passeport_5' => 110, 'cosmetique' => 35,
                        'nassi_litre' => 75, 'nassi_cipa' => 80, 'nassi_coton' => 55,
                        'grigris' => 65, 'huile' => 27, 'poisson' => 27, 'dossier' => 105
                    )
                )
            ),

            // Luxembourg (EUR)
            'luxembourg' => array(
                'flag' => '🇱🇺',
                'currency' => 'EUR',
                'modes' => array('relais', 'domicile'),
                'rates' => array(
                    'relais' => array(
                        'colis' => 27, 'papier' => 40, 'marques' => 40, 'permis' => 50,
                        'passeport_10' => 90, 'passeport_5' => 80, 'cosmetique' => 30,
                        'nassi_litre' => 70, 'nassi_cipa' => 75, 'nassi_coton' => 55,
                        'grigris' => 60, 'huile' => 24, 'poisson' => 24, 'dossier' => 95
                    ),
                    'domicile' => array(
                        'colis' => 30, 'papier' => 45, 'marques' => 45, 'permis' => 55,
                        'passeport_10' => 100, 'passeport_5' => 85, 'cosmetique' => 35,
                        'nassi_litre' => 75, 'nassi_cipa' => 80, 'nassi_coton' => 60,
                        'grigris' => 65, 'huile' => 27, 'poisson' => 27, 'dossier' => 105
                    )
                )
            ),

            // Italie (EUR)
            'italie' => array(
                'flag' => '🇮🇹',
                'currency' => 'EUR',
                'modes' => array('domicile'),
                'rates' => array(
                    'domicile' => array(
                        'colis' => 32, 'papier' => 50, 'marques' => 40, 'permis' => 50,
                        'passeport_10' => 95, 'passeport_5' => 80, 'cosmetique' => 35,
                        'nassi_litre' => 70, 'nassi_cipa' => 75, 'nassi_coton' => 55,
                        'grigris' => 55, 'huile' => 24, 'poisson' => 24, 'dossier' => 100
                    )
                )
            ),

            // Hollande (EUR)
            'hollande' => array(
                'flag' => '🇳🇱',
                'currency' => 'EUR',
                'modes' => array('relais', 'domicile'),
                'rates' => array(
                    'relais' => array(
                        'colis' => 27, 'papier' => 35, 'marques' => 40, 'permis' => 50,
                        'passeport_10' => 90, 'passeport_5' => 80, 'cosmetique' => 35,
                        'nassi_litre' => 70, 'nassi_cipa' => 80, 'nassi_coton' => 50,
                        'grigris' => 55, 'huile' => 24, 'poisson' => 24, 'dossier' => 95
                    ),
                    'domicile' => array(
                        'colis' => 30, 'papier' => 40, 'marques' => 40, 'permis' => 55,
                        'passeport_10' => 100, 'passeport_5' => 90, 'cosmetique' => 40,
                        'nassi_litre' => 80, 'nassi_cipa' => 90, 'nassi_coton' => 55,
                        'grigris' => 60, 'huile' => 27, 'poisson' => 27, 'dossier' => 105
                    )
                )
            ),

            // Autriche (EUR)
            'autriche' => array(
                'flag' => '🇦🇹',
                'currency' => 'EUR',
                'modes' => array('domicile'),
                'rates' => array(
                    'domicile' => array(
                        'colis' => 35, 'papier' => 55, 'marques' => 45, 'permis' => 55,
                        'passeport_10' => 100, 'passeport_5' => 90, 'cosmetique' => 40,
                        'nassi_litre' => 80, 'nassi_cipa' => 90, 'nassi_coton' => 70,
                        'grigris' => 75, 'huile' => 24, 'poisson' => 27, 'dossier' => 110
                    )
                )
            ),

            // Angleterre UK (EUR)
            'uk' => array(
                'flag' => '🇬🇧',
                'currency' => 'EUR',
                'modes' => array('domicile'),
                'rates' => array(
                    'domicile' => array(
                        'colis' => 45, 'papier' => 60, 'marques' => 65, 'permis' => 80,
                        'passeport_10' => 110, 'passeport_5' => 90, 'cosmetique' => 50,
                        'nassi_litre' => 80, 'nassi_cipa' => 100, 'nassi_coton' => 90,
                        'grigris' => 80, 'huile' => 55, 'poisson' => 55, 'dossier' => 115
                    )
                )
            ),

            // Canada (USD)
            'canada' => array(
                'flag' => '🇨🇦',
                'currency' => 'USD',
                'modes' => array('bureau', 'relais', 'domicile'),
                'rates' => array(
                    'bureau' => array(
                        'colis' => 45, 'papier' => 50, 'marques' => 60, 'permis' => 70,
                        'passeport_10' => 110, 'passeport_5' => 100, 'cosmetique' => 55,
                        'nassi_litre' => 90, 'nassi_cipa' => 90, 'nassi_coton' => 90,
                        'grigris' => 80, 'huile' => 50, 'poisson' => 50, 'dossier' => 100
                    ),
                    'relais' => array(
                        'colis' => 55, 'papier' => 55, 'marques' => 80, 'permis' => 80,
                        'passeport_10' => 120, 'passeport_5' => 110, 'cosmetique' => 60,
                        'nassi_litre' => 110, 'nassi_cipa' => 110, 'nassi_coton' => 100,
                        'grigris' => 100, 'huile' => 55, 'poisson' => 55, 'dossier' => 110
                    ),
                    'domicile' => array(
                        'colis' => 75, 'papier' => 75, 'marques' => 90, 'permis' => 90,
                        'passeport_10' => 145, 'passeport_5' => 125, 'cosmetique' => 100,
                        'nassi_litre' => 125, 'nassi_cipa' => 125, 'nassi_coton' => 125,
                        'grigris' => 125, 'huile' => 70, 'poisson' => 70, 'dossier' => 120
                    )
                )
            ),

            // USA (USD)
            'usa' => array(
                'flag' => '🇺🇸',
                'currency' => 'USD',
                'modes' => array('bureau', 'relais', 'domicile'),
                'rates' => array(
                    'bureau' => array(
                        'colis' => 30, 'papier' => 50, 'marques' => 70, 'permis' => 80,
                        'passeport_10' => 100, 'passeport_5' => 110, 'cosmetique' => 55,
                        'nassi_litre' => 90, 'nassi_cipa' => 100, 'nassi_coton' => 80,
                        'grigris' => 100, 'huile' => 45, 'poisson' => 47, 'dossier' => 95
                    ),
                    'relais' => array(
                        'colis' => 45, 'papier' => 55, 'marques' => 90, 'permis' => 95,
                        'passeport_10' => 110, 'passeport_5' => 120, 'cosmetique' => 60,
                        'nassi_litre' => 90, 'nassi_cipa' => 110, 'nassi_coton' => 90,
                        'grigris' => 120, 'huile' => 50, 'poisson' => 55, 'dossier' => 105
                    ),
                    'domicile' => array(
                        'colis' => 60, 'papier' => 70, 'marques' => 120, 'permis' => 120,
                        'passeport_10' => 135, 'passeport_5' => 135, 'cosmetique' => 75,
                        'nassi_litre' => 100, 'nassi_cipa' => 120, 'nassi_coton' => 120,
                        'grigris' => 130, 'huile' => 70, 'poisson' => 65, 'dossier' => 115
                    )
                )
            ),

            // Abidjan (GNF)
            'abidjan' => array(
                'flag' => '🇨🇮',
                'currency' => 'GNF',
                'modes' => array('relais'),
                'rates' => array(
                    'relais' => array(
                        'colis' => 180000, 'papier' => 200000, 'marques' => 200000, 'permis' => 400000,
                        'passeport_10' => 750000, 'passeport_5' => 750000, 'cosmetique' => 190000,
                        'nassi_litre' => 270000, 'nassi_cipa' => 300000, 'nassi_coton' => 300000,
                        'grigris' => 270000, 'huile' => 200000, 'poisson' => 200000, 'dossier' => 800000
                    )
                )
            ),

            // Dakar (GNF)
            'dakar' => array(
                'flag' => '🇸🇳',
                'currency' => 'GNF',
                'modes' => array('relais'),
                'rates' => array(
                    'relais' => array(
                        'colis' => 130000, 'papier' => 250000, 'marques' => 200000, 'permis' => 400000,
                        'passeport_10' => 750000, 'passeport_5' => 600000, 'cosmetique' => 200000,
                        'nassi_litre' => 350000, 'nassi_cipa' => 400000, 'nassi_coton' => 400000,
                        'grigris' => 370000, 'huile' => 160000, 'poisson' => 180000, 'dossier' => 800000
                    )
                )
            ),

            // Maroc (GNF)
            'maroc' => array(
                'flag' => '🇲🇦',
                'currency' => 'GNF',
                'modes' => array('relais'),
                'cities' => array(
                    'casablanca' => array(
                        'label' => 'Casablanca',
                        'rates' => array(
                            'colis' => 150000, 'papier' => 200000, 'marques' => 195000, 'permis' => 250000,
                            'passeport_10' => 850000, 'passeport_5' => 750000, 'cosmetique' => 220000,
                            'nassi_litre' => 350000, 'nassi_cipa' => 400000, 'nassi_coton' => 350000,
                            'grigris' => 370000, 'huile' => 200000, 'poisson' => 210000, 'dossier' => 800000
                        )
                    ),
                    'rabat' => array(
                        'label' => 'Rabat',
                        'rates' => array(
                            'colis' => 170000, 'papier' => 250000, 'marques' => 220000, 'permis' => 300000,
                            'passeport_10' => 950000, 'passeport_5' => 800000, 'cosmetique' => 250000,
                            'nassi_litre' => 400000, 'nassi_cipa' => 430000, 'nassi_coton' => 400000,
                            'grigris' => 400000, 'huile' => 220000, 'poisson' => 220000, 'dossier' => 900000
                        )
                    )
                )
            )
        );
    }

    /**
     * Labels des types de colis
     *
     * @return array
     */
    public static function get_parcel_type_labels() {
        return array(
            'colis' => '📦 Colis pesables (nourriture, vêtements)',
            'papier' => '📄 Papiers administratifs (extrait, enveloppe)',
            'marques' => '👔 Vêtements/chaussures de marques',
            'permis' => '🪪 Permis de conduire',
            'passeport_5' => '📘 Passeport 5 ans',
            'passeport_10' => '📕 Passeport 10 ans',
            'cosmetique' => '💄 Cosmétiques',
            'nassi_litre' => '🥘 Nassi 1L',
            'nassi_cipa' => '🥘 Nassi petit cipa (x3)',
            'nassi_coton' => '🥘 Nassi coton 1kg',
            'grigris' => '🔮 Grigris',
            'huile' => '🛢️ Huile rouge',
            'poisson' => '🐟 Poisson conconé',
            'dossier' => '📁 Dossier (documents groupés jusqu\'à 1kg)'
        );
    }

    /**
     * Labels des modes de livraison
     *
     * @return array
     */
    public static function get_delivery_mode_labels() {
        return array(
            'bureau' => '🏢 Bureau',
            'relais' => '📍 Point relais',
            'domicile' => '🏠 À domicile'
        );
    }
}
