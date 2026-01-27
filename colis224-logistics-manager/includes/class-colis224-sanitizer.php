<?php
/**
 * Classe utilitaire pour le nettoyage et la sécurisation des données
 * Gère les apostrophes, caractères spéciaux et validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Sanitizer {
    
    /**
     * Nettoyer un texte simple
     * Gère les apostrophes typographiques et caractères spéciaux
     */
    public static function clean_text($text) {
        if (empty($text)) {
            return '';
        }
        
        // Trim et conversion des caractères spéciaux
        $text = trim($text);
        
        // Remplacer les apostrophes problématiques par l'apostrophe standard
        $text = str_replace(["\xE2\x80\x98", "\xE2\x80\x99", "\xE2\x80\x9B", "\xC2\xB4", "`"], "'", $text);
        
        // Remplacer les guillemets typographiques par des guillemets standards
        $text = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x9E", "\xE2\x80\x9F"], '"', $text);
        
        // Échapper pour HTML
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        
        return $text;
    }
    
    /**
     * Nettoyer un nom (personne, ville, etc.)
     * Supprime les balises et normalise la casse
     */
    public static function clean_name($name) {
        if (empty($name)) {
            return '';
        }
        
        // Supprimer les balises HTML
        $name = strip_tags($name);
        
        // Normaliser les espaces multiples
        $name = preg_replace('/\s+/', ' ', $name);
        
        // Normaliser les apostrophes
        $name = str_replace(["\xE2\x80\x98", "\xE2\x80\x99", "\xE2\x80\x9B"], "'", $name);
        
        // Trim
        $name = trim($name);
        
        // Capitaliser proprement (première lettre de chaque mot en majuscule)
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
        
        return $name;
    }
    
    /**
     * Nettoyer un email
     */
    public static function clean_email($email) {
        if (empty($email)) {
            return '';
        }
        
        $email = trim(strtolower($email));
        return sanitize_email($email);
    }
    
    /**
     * Nettoyer un numéro de téléphone
     */
    public static function clean_phone($phone) {
        if (empty($phone)) {
            return '';
        }
        
        // Garder seulement les chiffres, +, espaces et tirets
        $phone = preg_replace('/[^0-9+\s-]/', '', $phone);
        $phone = trim($phone);
        
        return $phone;
    }
    
    /**
     * Nettoyer pour requête SQL (sécurité renforcée)
     * Utilise les fonctions WordPress + normalisation des apostrophes
     */
    public static function clean_for_sql($value) {
        if (empty($value)) {
            return '';
        }
        
        global $wpdb;
        
        // Normaliser les apostrophes d'abord
        $value = str_replace(["\xE2\x80\x98", "\xE2\x80\x99", "\xE2\x80\x9B"], "'", $value);
        
        // Utiliser esc_sql de WordPress pour la sécurité
        $value = esc_sql($value);
        
        return $value;
    }
    
    /**
     * Nettoyer un tableau de données
     * Applique le nettoyage approprié selon le type
     */
    public static function clean_array($data, $type = 'text') {
        if (!is_array($data)) {
            return array();
        }
        
        $cleaned = array();
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $cleaned[$key] = self::clean_array($value, $type);
            } else {
                switch ($type) {
                    case 'name':
                        $cleaned[$key] = self::clean_name($value);
                        break;
                    case 'email':
                        $cleaned[$key] = self::clean_email($value);
                        break;
                    case 'phone':
                        $cleaned[$key] = self::clean_phone($value);
                        break;
                    case 'sql':
                        $cleaned[$key] = self::clean_for_sql($value);
                        break;
                    default:
                        $cleaned[$key] = self::clean_text($value);
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Échapper pour JSON
     * Encode de manière sécurisée pour éviter XSS
     */
    public static function escape_for_json($value) {
        return json_encode($value, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Nettoyer une URL
     */
    public static function clean_url($url) {
        return esc_url_raw($url);
    }
    
    /**
     * Valider et nettoyer un code postal
     */
    public static function clean_postal_code($code) {
        if (empty($code)) {
            return '';
        }
        
        return preg_replace('/[^0-9A-Za-z\s-]/', '', $code);
    }
    
    /**
     * Nettoyer un numéro de suivi
     * Garde seulement alphanumériques et tirets
     */
    public static function clean_tracking_number($tracking) {
        if (empty($tracking)) {
            return '';
        }
        
        return preg_replace('/[^A-Za-z0-9-]/', '', strtoupper(trim($tracking)));
    }
    
    /**
     * Nettoyer et valider un montant
     */
    public static function clean_amount($amount) {
        if (empty($amount)) {
            return 0.00;
        }
        
        // Convertir en float et arrondir à 2 décimales
        $amount = floatval(str_replace(',', '.', $amount));
        return round($amount, 2);
    }
    
    /**
     * Nettoyer un message (textarea)
     * Garde les sauts de ligne mais nettoie le HTML
     */
    public static function clean_message($message) {
        if (empty($message)) {
            return '';
        }
        
        // Trim d'abord
        $message = trim($message);
        
        // Normaliser les apostrophes typographiques uniquement
        $message = str_replace(["\xE2\x80\x98", "\xE2\x80\x99", "\xE2\x80\x9B"], "'", $message);
        $message = str_replace(["\xE2\x80\x9C", "\xE2\x80\x9D", "\xE2\x80\x9E", "\xE2\x80\x9F"], '"', $message);
        
        // Normaliser les sauts de ligne
        $message = str_replace("\r\n", "\n", $message);
        $message = str_replace("\r", "\n", $message);
        
        // Supprimer les balises HTML dangereuses
        $message = strip_tags($message);
        
        return $message;
    }
    
    /**
     * Nettoyer les données POST du formulaire
     * Fonction helper pour nettoyer rapidement $_POST
     */
    public static function clean_post_data($keys_types = array()) {
        $cleaned = array();
        
        foreach ($keys_types as $key => $type) {
            if (isset($_POST[$key])) {
                switch ($type) {
                    case 'name':
                        $cleaned[$key] = self::clean_name($_POST[$key]);
                        break;
                    case 'email':
                        $cleaned[$key] = self::clean_email($_POST[$key]);
                        break;
                    case 'phone':
                        $cleaned[$key] = self::clean_phone($_POST[$key]);
                        break;
                    case 'amount':
                        $cleaned[$key] = self::clean_amount($_POST[$key]);
                        break;
                    case 'message':
                        $cleaned[$key] = self::clean_message($_POST[$key]);
                        break;
                    case 'tracking':
                        $cleaned[$key] = self::clean_tracking_number($_POST[$key]);
                        break;
                    case 'url':
                        $cleaned[$key] = self::clean_url($_POST[$key]);
                        break;
                    default:
                        $cleaned[$key] = self::clean_text($_POST[$key]);
                }
            } else {
                $cleaned[$key] = '';
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Valider un email
     */
    public static function is_valid_email($email) {
        return is_email($email);
    }
    
    /**
     * Valider un téléphone (format simple)
     */
    public static function is_valid_phone($phone) {
        $phone = self::clean_phone($phone);
        // Minimum 8 chiffres
        return preg_match('/[0-9]{8,}/', $phone);
    }
    
    /**
     * Valider un montant
     */
    public static function is_valid_amount($amount) {
        return is_numeric($amount) && $amount >= 0;
    }
}
