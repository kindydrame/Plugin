<?php
/**
 * MODULE 11: Programme de Fidélité
 * Gestion du système de points, récompenses et tiers
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Loyalty {

    public function __construct() {
        // Hook pour attribuer des points lors de la création d'un colis
        add_action('colis224_parcel_created', array($this, 'award_points_for_parcel'), 10, 2);

        // Hook pour attribuer des points lors d'un paiement
        add_action('colis224_payment_received', array($this, 'award_points_for_payment'), 10, 2);

        // AJAX handler pour récupérer les informations de fidélité d'un client
        add_action('wp_ajax_colis224_get_client_loyalty', array($this, 'ajax_get_client_loyalty'));
    }

    /**
     * AJAX: Récupérer les informations de fidélité d'un client
     */
    public function ajax_get_client_loyalty() {
        check_ajax_referer('colis224_nonce', 'nonce');

        $client_id = intval($_GET['client_id']);
        if (!$client_id) {
            wp_send_json_error(array('message' => 'Client ID requis'));
        }

        $loyalty = self::get_client_loyalty($client_id);

        if ($loyalty) {
            // Récupérer le programme actif pour connaître les points par colis
            global $wpdb;
            $table_programs = $wpdb->prefix . 'colis224_loyalty_programs';
            $program = $wpdb->get_row("SELECT * FROM $table_programs WHERE is_active = 1 LIMIT 1");

            $data = array(
                'tier' => $loyalty->tier,
                'total_points' => intval($loyalty->total_points),
                'available_points' => intval($loyalty->available_points),
                'redeemed_points' => intval($loyalty->redeemed_points),
                'points_per_parcel' => $program ? intval($program->points_per_parcel) : 10,
                'program_name' => $loyalty->program_name
            );

            wp_send_json_success($data);
        } else {
            // Client pas encore inscrit au programme
            wp_send_json_success(array(
                'tier' => 'Bronze',
                'total_points' => 0,
                'available_points' => 0,
                'redeemed_points' => 0,
                'points_per_parcel' => 10,
                'program_name' => 'Programme Colis224'
            ));
        }
    }

    /**
     * Inscrire un client au programme de fidélité
     */
    public static function enroll_client($client_id, $program_id = 1) {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_loyalty_members';

        // Vérifier si déjà inscrit
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE client_id = %d",
            $client_id
        ));

        if ($exists) {
            return false;
        }

        return $wpdb->insert($table, array(
            'client_id' => $client_id,
            'program_id' => $program_id,
            'total_points' => 0,
            'available_points' => 0,
            'redeemed_points' => 0,
            'tier' => 'Bronze',
            'join_date' => current_time('mysql', false),
            'last_activity_date' => current_time('mysql')
        ));
    }

    /**
     * Obtenir les informations de fidélité d'un client
     */
    public static function get_client_loyalty($client_id) {
        global $wpdb;

        $table_members = $wpdb->prefix . 'colis224_loyalty_members';
        $table_programs = $wpdb->prefix . 'colis224_loyalty_programs';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, p.name as program_name, p.description as program_description
             FROM $table_members m
             LEFT JOIN $table_programs p ON m.program_id = p.id
             WHERE m.client_id = %d",
            $client_id
        ));
    }

    /**
     * Ajouter des points à un membre
     */
    public static function add_points($client_id, $points, $description, $reference_type = 'manual', $reference_id = null) {
        global $wpdb;

        $member = self::get_client_loyalty($client_id);
        if (!$member) {
            // Auto-enroll si pas encore inscrit
            self::enroll_client($client_id);
            $member = self::get_client_loyalty($client_id);
        }

        if (!$member) {
            return false;
        }

        $table_members = $wpdb->prefix . 'colis224_loyalty_members';
        $table_trans = $wpdb->prefix . 'colis224_loyalty_transactions';

        // Enregistrer la transaction
        $wpdb->insert($table_trans, array(
            'member_id' => $member->id,
            'transaction_type' => 'earn',
            'points' => $points,
            'description' => $description,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
        ));

        // Mettre à jour le solde du membre
        $new_total = $member->total_points + $points;
        $new_available = $member->available_points + $points;

        $wpdb->update($table_members, array(
            'total_points' => $new_total,
            'available_points' => $new_available,
            'last_activity_date' => current_time('mysql')
        ), array('id' => $member->id));

        // Vérifier et mettre à jour le tier
        $old_tier = $member->tier;
        self::update_tier($member->id, $new_total);

        // Envoyer une notification au client
        self::send_points_notification($client_id, $points, $description, $new_available, $old_tier, $new_total);

        return true;
    }

    /**
     * Envoyer une notification de points gagnés au client
     */
    private static function send_points_notification($client_id, $points, $description, $new_balance, $old_tier, $total_points) {
        global $wpdb;

        // Récupérer les informations du client
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT name, email, phone FROM $table_clients WHERE id = %d",
            $client_id
        ));

        if (!$client) {
            return;
        }

        // Vérifier le nouveau tier
        $new_tier = 'Bronze';
        if ($total_points >= 10000) {
            $new_tier = 'Platinum';
        } elseif ($total_points >= 5000) {
            $new_tier = 'Gold';
        } elseif ($total_points >= 2000) {
            $new_tier = 'Silver';
        }

        $tier_upgraded = ($new_tier !== $old_tier);

        // Message personnalisé
        $tier_icons = array('Bronze' => '🥉', 'Silver' => '🥈', 'Gold' => '🥇', 'Platinum' => '💎');
        $message = "Félicitations {$client->name}! 🎉\n\n";
        $message .= "Vous venez de gagner {$points} points de fidélité!\n";
        $message .= "Raison: {$description}\n\n";
        $message .= "💰 Solde actuel: {$new_balance} points\n";

        if ($tier_upgraded) {
            $message .= "\n🎊 PROMOTION! Vous êtes maintenant {$tier_icons[$new_tier]} {$new_tier}!\n";
        } else {
            $message .= "📊 Tier actuel: {$tier_icons[$new_tier]} {$new_tier}\n";
        }

        $message .= "\nMerci de votre fidélité!\n- Colis224";

        // Envoyer par email si disponible
        if (!empty($client->email) && is_email($client->email)) {
            $subject = "🎁 Vous avez gagné {$points} points de fidélité!";
            $html_message = "<html><body style='font-family: Arial, sans-serif;'>";
            $html_message .= "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; color: #fff; border-radius: 10px;'>";
            $html_message .= "<h1 style='margin: 0;'>🎉 Félicitations {$client->name}!</h1>";
            $html_message .= "<p style='font-size: 18px; margin: 20px 0;'>Vous venez de gagner <strong>{$points} points</strong> de fidélité!</p>";
            $html_message .= "</div>";
            $html_message .= "<div style='padding: 30px; background: #f8f9fa; margin-top: 20px; border-radius: 10px;'>";
            $html_message .= "<p><strong>Raison:</strong> {$description}</p>";
            $html_message .= "<p style='font-size: 24px; text-align: center; margin: 20px 0;'><strong>💰 Solde: {$new_balance} points</strong></p>";

            if ($tier_upgraded) {
                $html_message .= "<div style='background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 20px; text-align: center; color: #fff; border-radius: 10px; margin: 20px 0;'>";
                $html_message .= "<h2 style='margin: 0;'>🎊 PROMOTION!</h2>";
                $html_message .= "<p style='font-size: 20px; margin: 10px 0;'>Vous êtes maintenant {$tier_icons[$new_tier]} <strong>{$new_tier}</strong>!</p>";
                $html_message .= "</div>";
            } else {
                $html_message .= "<p style='text-align: center;'>📊 Tier actuel: {$tier_icons[$new_tier]} <strong>{$new_tier}</strong></p>";
            }

            $html_message .= "</div>";
            $html_message .= "<div style='text-align: center; padding: 20px; color: #666;'>";
            $html_message .= "<p>Merci de votre fidélité!<br><strong>- Colis224</strong></p>";
            $html_message .= "<p style='font-size: 12px;'>📧 contact@colis224.com | 📞 +224 620 17 89 30</p>";
            $html_message .= "</div>";
            $html_message .= "</body></html>";

            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($client->email, $subject, $html_message, $headers);
        }

        // Envoyer par SMS si disponible
        if (!empty($client->phone)) {
            // Utiliser le système de notification SMS existant
            do_action('colis224_send_sms', $client->phone, $message);
        }
    }

    /**
     * Déduire des points (lors d'un échange de récompense)
     */
    public static function redeem_points($client_id, $points, $description, $reference_id = null) {
        global $wpdb;

        $member = self::get_client_loyalty($client_id);
        if (!$member || $member->available_points < $points) {
            return false;
        }

        $table_members = $wpdb->prefix . 'colis224_loyalty_members';
        $table_trans = $wpdb->prefix . 'colis224_loyalty_transactions';

        // Enregistrer la transaction
        $wpdb->insert($table_trans, array(
            'member_id' => $member->id,
            'transaction_type' => 'redeem',
            'points' => -$points,
            'description' => $description,
            'reference_type' => 'manual',
            'reference_id' => $reference_id,
        ));

        // Mettre à jour le solde
        $new_available = $member->available_points - $points;
        $new_redeemed = $member->redeemed_points + $points;

        $wpdb->update($table_members, array(
            'available_points' => $new_available,
            'redeemed_points' => $new_redeemed,
            'last_activity_date' => current_time('mysql')
        ), array('id' => $member->id));

        return true;
    }

    /**
     * Mettre à jour le tier en fonction du nombre total de points
     */
    private static function update_tier($member_id, $total_points) {
        global $wpdb;

        $tier = 'Bronze';
        if ($total_points >= 10000) {
            $tier = 'Platinum';
        } elseif ($total_points >= 5000) {
            $tier = 'Gold';
        } elseif ($total_points >= 2000) {
            $tier = 'Silver';
        }

        $table = $wpdb->prefix . 'colis224_loyalty_members';
        $wpdb->update($table, array('tier' => $tier), array('id' => $member_id));
    }

    /**
     * Attribuer des points lors de la création d'un colis
     */
    public function award_points_for_parcel($parcel_id, $client_id) {
        if (!$client_id) {
            return;
        }

        global $wpdb;
        $table_programs = $wpdb->prefix . 'colis224_loyalty_programs';

        $program = $wpdb->get_row("SELECT * FROM $table_programs WHERE is_active = 1 LIMIT 1");
        if (!$program) {
            return;
        }

        // Points fixes par colis
        if ($program->points_per_parcel > 0) {
            self::add_points(
                $client_id,
                $program->points_per_parcel,
                "Points pour l'envoi du colis #$parcel_id",
                'parcel',
                $parcel_id
            );
        }
    }

    /**
     * Attribuer des points lors d'un paiement
     */
    public function award_points_for_payment($amount, $client_id) {
        if (!$client_id || $amount <= 0) {
            return;
        }

        global $wpdb;
        $table_programs = $wpdb->prefix . 'colis224_loyalty_programs';

        $program = $wpdb->get_row("SELECT * FROM $table_programs WHERE is_active = 1 LIMIT 1");
        if (!$program || $program->points_per_gnf <= 0) {
            return;
        }

        // Calculer les points en fonction du montant
        $points = floor($amount * $program->points_per_gnf);

        if ($points > 0) {
            self::add_points(
                $client_id,
                $points,
                "Points pour paiement de $amount GNF",
                'payment',
                null
            );
        }
    }

    /**
     * Créer une récompense
     */
    public static function create_reward($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_loyalty_rewards';

        return $wpdb->insert($table, array(
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'points_required' => intval($data['points_required']),
            'reward_type' => sanitize_text_field($data['reward_type']),
            'reward_value' => floatval($data['reward_value'] ?? 0),
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'validity_days' => intval($data['validity_days'] ?? 30),
            'stock_quantity' => isset($data['stock_quantity']) ? intval($data['stock_quantity']) : null,
        ));
    }

    /**
     * Échanger des points contre une récompense
     */
    public static function redeem_reward($client_id, $reward_id) {
        global $wpdb;

        $table_rewards = $wpdb->prefix . 'colis224_loyalty_rewards';
        $table_redemptions = $wpdb->prefix . 'colis224_loyalty_redemptions';

        // Vérifier la récompense
        $reward = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_rewards WHERE id = %d AND is_active = 1",
            $reward_id
        ));

        if (!$reward) {
            return array('success' => false, 'message' => 'Récompense non disponible');
        }

        // Vérifier le stock
        if ($reward->stock_quantity !== null && $reward->stock_quantity <= 0) {
            return array('success' => false, 'message' => 'Stock épuisé');
        }

        // Vérifier les points du client
        $member = self::get_client_loyalty($client_id);
        if (!$member || $member->available_points < $reward->points_required) {
            return array('success' => false, 'message' => 'Points insuffisants');
        }

        // Déduire les points
        if (!self::redeem_points($client_id, $reward->points_required, "Échange: $reward->name", $reward_id)) {
            return array('success' => false, 'message' => 'Erreur lors de la déduction des points');
        }

        // Calculer la date d'expiration
        $expiry_date = date('Y-m-d H:i:s', strtotime("+{$reward->validity_days} days"));

        // Enregistrer l'échange
        $wpdb->insert($table_redemptions, array(
            'member_id' => $member->id,
            'reward_id' => $reward_id,
            'points_spent' => $reward->points_required,
            'status' => 'pending',
            'redeemed_date' => current_time('mysql'),
            'expiry_date' => $expiry_date,
        ));

        // Mettre à jour le stock
        if ($reward->stock_quantity !== null) {
            $wpdb->update($table_rewards,
                array('stock_quantity' => $reward->stock_quantity - 1),
                array('id' => $reward_id)
            );
        }

        return array(
            'success' => true,
            'message' => 'Récompense échangée avec succès',
            'redemption_id' => $wpdb->insert_id
        );
    }

    /**
     * Obtenir l'historique des transactions d'un client
     */
    public static function get_client_transactions($client_id, $limit = 50) {
        global $wpdb;

        $table_members = $wpdb->prefix . 'colis224_loyalty_members';
        $table_trans = $wpdb->prefix . 'colis224_loyalty_transactions';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT t.*
             FROM $table_trans t
             INNER JOIN $table_members m ON t.member_id = m.id
             WHERE m.client_id = %d
             ORDER BY t.created_at DESC
             LIMIT %d",
            $client_id,
            $limit
        ));
    }

    /**
     * Obtenir toutes les récompenses actives
     */
    public static function get_active_rewards() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_loyalty_rewards';

        return $wpdb->get_results(
            "SELECT * FROM $table
             WHERE is_active = 1
             AND (stock_quantity IS NULL OR stock_quantity > 0)
             ORDER BY points_required ASC"
        );
    }

    /**
     * Obtenir les échanges d'un client
     */
    public static function get_client_redemptions($client_id) {
        global $wpdb;

        $table_members = $wpdb->prefix . 'colis224_loyalty_members';
        $table_redemptions = $wpdb->prefix . 'colis224_loyalty_redemptions';
        $table_rewards = $wpdb->prefix . 'colis224_loyalty_rewards';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, rw.name as reward_name, rw.reward_type, rw.reward_value
             FROM $table_redemptions r
             INNER JOIN $table_members m ON r.member_id = m.id
             INNER JOIN $table_rewards rw ON r.reward_id = rw.id
             WHERE m.client_id = %d
             ORDER BY r.redeemed_date DESC",
            $client_id
        ));
    }

    /**
     * Obtenir les statistiques globales du programme de fidélité
     */
    public static function get_loyalty_stats() {
        global $wpdb;

        $table_members = $wpdb->prefix . 'colis224_loyalty_members';
        $table_trans = $wpdb->prefix . 'colis224_loyalty_transactions';
        $table_redemptions = $wpdb->prefix . 'colis224_loyalty_redemptions';

        $stats = array(
            'total_members' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_members")),
            'total_points_awarded' => intval($wpdb->get_var("SELECT SUM(points) FROM $table_trans WHERE transaction_type = 'earn'")),
            'total_points_redeemed' => abs(intval($wpdb->get_var("SELECT SUM(points) FROM $table_trans WHERE transaction_type = 'redeem'"))),
            'total_redemptions' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_redemptions")),
            'members_by_tier' => array(
                'Bronze' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_members WHERE tier = 'Bronze'")),
                'Silver' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_members WHERE tier = 'Silver'")),
                'Gold' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_members WHERE tier = 'Gold'")),
                'Platinum' => intval($wpdb->get_var("SELECT COUNT(*) FROM $table_members WHERE tier = 'Platinum'")),
            )
        );

        return $stats;
    }

    /**
     * Créer le programme de fidélité par défaut
     */
    public static function create_default_program() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_loyalty_programs';

        // Vérifier s'il existe déjà un programme
        $exists = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($exists > 0) {
            return;
        }

        $wpdb->insert($table, array(
            'name' => 'Programme Colis224',
            'description' => 'Gagnez des points à chaque envoi et échangez-les contre des récompenses',
            'points_per_gnf' => 0.01, // 1 point pour 100 GNF
            'points_per_parcel' => 10, // 10 points par colis
            'min_points_threshold' => 0,
            'is_active' => 1,
        ));
    }
}

// Initialiser
new Colis224_Loyalty();
