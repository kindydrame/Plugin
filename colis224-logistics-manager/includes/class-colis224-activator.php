<?php
/**
 * Classe d'activation du plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Activator {

    public static function activate() {
        // Capturer TOUTE sortie dès le début pour éviter les warnings WordPress
        $ob_started = false;
        if (!ob_get_level()) {
            ob_start();
            $ob_started = true;
        }
        
        // Désactiver l'affichage des erreurs
        $error_reporting = error_reporting(0);
        $display_errors = ini_get('display_errors');
        ini_set('display_errors', 0);
        
        try {
            // Création des tables de base de données
            require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-database.php';
            Colis224_Database::create_tables();

            // Création de la table frontend calculator (v2.20.0)
            if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-frontend-calculator.php')) {
                require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-frontend-calculator.php';
                if (class_exists('Colis224_Frontend_Calculator')) {
                    Colis224_Frontend_Calculator::create_table();
                }
            }

            // Insertion des données par défaut
            self::insert_default_data();

            // Définition de la version du plugin
            update_option('colis224_version', COLIS224_VERSION);
            update_option('colis224_activated', time());

            // Flush rewrite rules (peut générer de la sortie, donc on le fait avant le nettoyage)
            flush_rewrite_rules();
        } catch (Exception $e) {
            // En cas d'erreur, logger mais ne pas afficher
            error_log('Colis224 Activation Error: ' . $e->getMessage());
        } catch (Error $e) {
            // Capturer aussi les erreurs fatales PHP 7+
            error_log('Colis224 Activation Fatal Error: ' . $e->getMessage());
        }
        
        // Nettoyer TOUTE sortie capturée (tous les niveaux)
        if ($ob_started) {
            ob_end_clean();
        } else {
            // Nettoyer tous les niveaux de buffer s'il y en a
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
        
        // Réactiver l'affichage des erreurs
        error_reporting($error_reporting);
        ini_set('display_errors', $display_errors);
    }

    private static function insert_default_data() {
        global $wpdb;
        
        // Supprimer les erreurs d'affichage pendant l'insertion
        $wpdb->suppress_errors = true;

        // Insertion des paramètres par défaut
        $default_settings = array(
            'company_name' => 'Colis224',
            'company_address' => '',
            'company_phone' => '',
            'company_email' => '',
            'default_currency' => 'GNF',
            'tax_enabled' => '0',
            'tax_rate' => '0',
            'invoice_prefix' => 'INV-',
            'parcel_prefix' => 'PA',
            'email_notifications' => '1',
            'sms_notifications' => '0',
            'exchange_rate_eur' => '11000',
            'exchange_rate_usd' => '10000',
            'exchange_rate_xof' => '16',
            'exchange_rate_cny' => '1400'
        );

        foreach ($default_settings as $key => $value) {
            update_option('colis224_' . $key, $value);
        }

        // Insertion des catégories de dépenses par défaut
        $expense_categories = array(
            'Carburant',
            'Maintenance',
            'Assurance',
            'Internet',
            'Téléphone',
            'Salaire',
            'Commissions',
            'Loyer',
            'Fournitures',
            'Autres'
        );

        $table_expense_categories = $wpdb->prefix . 'colis224_expense_categories';
        foreach ($expense_categories as $category) {
            $wpdb->insert(
                $table_expense_categories,
                array(
                    'name' => $category,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s')
            );
        }

        // Insertion des pays par défaut
        $countries = array(
            'Chine',
            'France',
            'Maroc',
            'Sénégal',
            'Côte d\'Ivoire',
            'Guinée',
            'Autres'
        );

        $table_countries = $wpdb->prefix . 'colis224_countries';
        foreach ($countries as $country) {
            // Vérifier si le pays existe déjà
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_countries WHERE name = %s",
                $country
            ));
            
            if (!$exists) {
                $wpdb->insert(
                    $table_countries,
                    array(
                        'name' => $country,
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s')
                );
            }
        }

        // Insertion des modes de transport par défaut
        $transport_modes = array(
            array('name' => 'Avion Express', 'estimated_days' => 3),
            array('name' => 'Avion Standard', 'estimated_days' => 7),
            array('name' => 'Bateau', 'estimated_days' => 30)
        );

        $table_transport = $wpdb->prefix . 'colis224_transport_modes';
        foreach ($transport_modes as $mode) {
            // Vérifier si le mode existe déjà
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_transport WHERE name = %s",
                $mode['name']
            ));
            
            if (!$exists) {
                $wpdb->insert(
                    $table_transport,
                    array(
                        'name' => $mode['name'],
                        'estimated_days' => $mode['estimated_days'],
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%d', '%s')
                );
            }
        }

        // Insertion des catégories de produits par défaut
        $parcel_categories = array(
            'Vêtements',
            'Électronique',
            'Livres',
            'Maison & Jardin',
            'Sports & Loisirs',
            'Santé & Beauté',
            'Autres'
        );

        $table_categories = $wpdb->prefix . 'colis224_parcel_categories';
        foreach ($parcel_categories as $category) {
            // Vérifier si la catégorie existe déjà
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_categories WHERE name = %s",
                $category
            ));
            
            if (!$exists) {
                $wpdb->insert(
                    $table_categories,
                    array(
                        'name' => $category,
                        'created_at' => current_time('mysql')
                    ),
                    array('%s', '%s')
                );
            }
        }

        // Création du programme de fidélité par défaut
        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-loyalty.php')) {
            require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-loyalty.php';
            if (class_exists('Colis224_Loyalty')) {
                Colis224_Loyalty::create_default_program();
            }
        }

        // Création de l'entrepôt par défaut
        if (file_exists(COLIS224_PLUGIN_DIR . 'includes/class-colis224-warehouses.php')) {
            require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-warehouses.php';
            if (class_exists('Colis224_Warehouses')) {
                Colis224_Warehouses::create_default_warehouse();
            }
        }
        
        // Réactiver l'affichage des erreurs
        $wpdb->suppress_errors = false;
    }
}
