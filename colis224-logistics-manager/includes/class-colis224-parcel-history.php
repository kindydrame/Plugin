<?php
/**
 * Helper pour l'historique des modifications de colis
 *
 * @package Colis224_Logistics
 * @subpackage Includes
 * @version 2.11.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Parcel_History {

    /**
     * Enregistrer une action dans l'historique
     *
     * @param int    $parcel_id    ID du colis
     * @param string $action       Action effectuée (created, updated_status, updated_amount, validated, rejected, etc.)
     * @param string $field_changed Champ modifié (optionnel)
     * @param mixed  $old_value    Ancienne valeur (optionnel)
     * @param mixed  $new_value    Nouvelle valeur (optionnel)
     * @return bool
     */
    public static function log($parcel_id, $action, $field_changed = null, $old_value = null, $new_value = null) {
        global $wpdb;

        $current_user = wp_get_current_user();
        $table = $wpdb->prefix . 'colis224_parcel_history';

        // Vérifier que la table existe
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return false;
        }

        // Obtenir le rôle Colis224 de l'utilisateur
        $user_role = 'guest';
        if ($current_user->ID > 0) {
            if (class_exists('Colis224_Permissions')) {
                $user_role = Colis224_Permissions::get_user_colis224_role($current_user->ID);
            }
            if (!$user_role) {
                $user_role = 'admin'; // Fallback
            }
        }

        // Préparer les données
        $data = array(
            'parcel_id' => intval($parcel_id),
            'user_id' => $current_user->ID > 0 ? $current_user->ID : null,
            'user_name' => $current_user->ID > 0 ? $current_user->display_name : 'Système',
            'user_role' => $user_role,
            'action' => sanitize_text_field($action),
            'field_changed' => $field_changed ? sanitize_text_field($field_changed) : null,
            'old_value' => $old_value !== null ? maybe_serialize($old_value) : null,
            'new_value' => $new_value !== null ? maybe_serialize($new_value) : null,
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null,
        );

        $result = $wpdb->insert($table, $data);

        return $result !== false;
    }

    /**
     * Enregistrer la création d'un colis
     *
     * @param int   $parcel_id Tracking number du colis
     * @param array $data      Données du colis
     * @return bool
     */
    public static function log_created($parcel_id, $data = array()) {
        return self::log($parcel_id, 'created', 'all', null, $data);
    }

    /**
     * Enregistrer une validation
     *
     * @param int    $parcel_id ID du colis
     * @param string $status    approved ou rejected
     * @param string $notes     Notes de validation
     * @return bool
     */
    public static function log_validation($parcel_id, $status, $notes = '') {
        return self::log($parcel_id, 'validated', 'validation_status', 'pending', $status . ($notes ? ' - ' . $notes : ''));
    }

    /**
     * Enregistrer une modification de statut
     *
     * @param int    $parcel_id ID du colis
     * @param string $old_status Ancien statut
     * @param string $new_status Nouveau statut
     * @return bool
     */
    public static function log_status_change($parcel_id, $old_status, $new_status) {
        return self::log($parcel_id, 'updated_status', 'status', $old_status, $new_status);
    }

    /**
     * Enregistrer une modification de montant
     *
     * @param int   $parcel_id ID du colis
     * @param float $old_amount Ancien montant
     * @param float $new_amount Nouveau montant
     * @return bool
     */
    public static function log_amount_change($parcel_id, $old_amount, $new_amount) {
        return self::log($parcel_id, 'updated_amount', 'total_amount', $old_amount, $new_amount);
    }

    /**
     * Enregistrer une modification de paiement
     *
     * @param int    $parcel_id      ID du colis
     * @param string $old_status     Ancien statut paiement
     * @param string $new_status     Nouveau statut paiement
     * @param float  $amount_paid    Montant payé
     * @return bool
     */
    public static function log_payment_change($parcel_id, $old_status, $new_status, $amount_paid = 0) {
        return self::log($parcel_id, 'updated_payment', 'payment_status', $old_status, $new_status . ' (' . $amount_paid . ')');
    }

    /**
     * Obtenir l'historique d'un colis
     *
     * @param int $parcel_id ID du colis
     * @param int $limit     Nombre de résultats (défaut: 50)
     * @return array
     */
    public static function get_history($parcel_id, $limit = 50) {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_parcel_history';

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT *
            FROM $table
            WHERE parcel_id = %d
            ORDER BY created_at DESC
            LIMIT %d
        ", $parcel_id, $limit));

        return $results ? $results : array();
    }

    /**
     * Afficher l'historique sous forme HTML
     *
     * @param int $parcel_id ID du colis
     * @return string HTML
     */
    public static function display_history($parcel_id) {
        $history = self::get_history($parcel_id);

        if (empty($history)) {
            return '<p><em>Aucun historique disponible.</em></p>';
        }

        $html = '<div class="colis224-history">';
        $html .= '<h3>📋 Historique des Modifications</h3>';
        $html .= '<table class="widefat" style="margin-top: 10px;">';
        $html .= '<thead><tr>';
        $html .= '<th>Date/Heure</th>';
        $html .= '<th>Utilisateur</th>';
        $html .= '<th>Action</th>';
        $html .= '<th>Détails</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';

        foreach ($history as $entry) {
            $action_label = self::get_action_label($entry->action);
            $details = self::format_details($entry);

            $html .= '<tr>';
            $html .= '<td>' . esc_html(mysql2date('d/m/Y H:i', $entry->created_at)) . '</td>';
            $html .= '<td><strong>' . esc_html($entry->user_name) . '</strong><br><small>(' . esc_html($entry->user_role) . ')</small></td>';
            $html .= '<td>' . $action_label . '</td>';
            $html .= '<td>' . $details . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Obtenir le libellé d'une action
     *
     * @param string $action Code action
     * @return string
     */
    private static function get_action_label($action) {
        $labels = array(
            'created' => '✨ Créé',
            'updated_status' => '🔄 Statut modifié',
            'updated_amount' => '💰 Montant modifié',
            'updated_payment' => '💳 Paiement modifié',
            'validated' => '✅ Validé',
            'rejected' => '❌ Rejeté',
        );

        return isset($labels[$action]) ? $labels[$action] : '📝 ' . ucfirst($action);
    }

    /**
     * Formater les détails d'une entrée
     *
     * @param object $entry Entrée historique
     * @return string HTML
     */
    private static function format_details($entry) {
        if (!$entry->field_changed) {
            return '<em>—</em>';
        }

        $html = '<strong>' . esc_html($entry->field_changed) . '</strong><br>';

        if ($entry->old_value !== null) {
            $old = maybe_unserialize($entry->old_value);
            if (is_array($old)) {
                $old = '(données)';
            }
            $html .= '<span style="color: #999;">Avant: ' . esc_html($old) . '</span><br>';
        }

        if ($entry->new_value !== null) {
            $new = maybe_unserialize($entry->new_value);
            if (is_array($new)) {
                $new = '(données)';
            }
            $html .= '<span style="color: #007cba; font-weight: bold;">Après: ' . esc_html($new) . '</span>';
        }

        return $html;
    }

    /**
     * Obtenir l'adresse IP du client
     *
     * @return string
     */
    private static function get_client_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field($ip);
    }
}
