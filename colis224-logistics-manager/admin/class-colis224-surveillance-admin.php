<?php
/**
 * Interface Admin - Surveillance et Relances
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Surveillance_Admin {

    public static function display_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-shield-alt"></span>
                Surveillance & Relances Anti-Vol
            </h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=colis224-surveillance&tab=dashboard" class="nav-tab <?php echo $tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">
                    📊 Tableau de Bord
                </a>
                <a href="?page=colis224-surveillance&tab=parcels" class="nav-tab <?php echo $tab === 'parcels' ? 'nav-tab-active' : ''; ?>">
                    📦 Colis à Risque
                </a>
                <a href="?page=colis224-surveillance&tab=reminders" class="nav-tab <?php echo $tab === 'reminders' ? 'nav-tab-active' : ''; ?>">
                    🔔 Relances Envoyées
                </a>
                <a href="?page=colis224-surveillance&tab=config" class="nav-tab <?php echo $tab === 'config' ? 'nav-tab-active' : ''; ?>">
                    ⚙️ Configuration
                </a>
                <a href="?page=colis224-surveillance&tab=logs" class="nav-tab <?php echo $tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                    📋 Logs
                </a>
            </nav>

            <?php
            if ($tab === 'dashboard') {
                self::display_dashboard();
            } elseif ($tab === 'parcels') {
                self::display_at_risk_parcels();
            } elseif ($tab === 'reminders') {
                self::display_reminders();
            } elseif ($tab === 'config') {
                self::display_config();
            } else {
                self::display_logs();
            }
            ?>
        </div>
        <?php
    }

    private static function display_dashboard() {
        $stats = Colis224_Auto_Reminders::get_surveillance_stats();

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h2>📊 Tableau de Bord Surveillance</h2>
            <p>Vue d'ensemble des colis nécessitant une surveillance</p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <!-- Carte 3 jours -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 14px; opacity: 0.9;">Attente ≥ 3 jours</div>
                    <div style="font-size: 36px; font-weight: bold; margin: 10px 0;">
                        <?php echo intval($stats['waiting_3_days']); ?>
                    </div>
                    <div style="font-size: 12px; opacity: 0.8;">colis à relancer</div>
                </div>

                <!-- Carte 7 jours -->
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 14px; opacity: 0.9;">Attente ≥ 7 jours</div>
                    <div style="font-size: 36px; font-weight: bold; margin: 10px 0;">
                        <?php echo intval($stats['waiting_7_days']); ?>
                    </div>
                    <div style="font-size: 12px; opacity: 0.8;">risque élevé</div>
                </div>

                <!-- Carte Critique -->
                <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 14px; opacity: 0.9;">⚠️ Critique ≥ 14 jours</div>
                    <div style="font-size: 36px; font-weight: bold; margin: 10px 0;">
                        <?php echo intval($stats['critical']); ?>
                    </div>
                    <div style="font-size: 12px; opacity: 0.8;">action immédiate</div>
                </div>

                <!-- Carte Valeur -->
                <div style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 14px; opacity: 0.9;">💰 Valeur en Stock</div>
                    <div style="font-size: 28px; font-weight: bold; margin: 10px 0;">
                        <?php echo number_format(floatval($stats['total_value'] ?? 0), 0, ',', ' '); ?> GNF
                    </div>
                    <div style="font-size: 12px; opacity: 0.8;">total colis non retirés</div>
                </div>

                <!-- Carte Impayé -->
                <div style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 14px; opacity: 0.9;">💳 Total Impayé</div>
                    <div style="font-size: 28px; font-weight: bold; margin: 10px 0;">
                        <?php echo number_format(floatval($stats['total_unpaid'] ?? 0), 0, ',', ' '); ?> GNF
                    </div>
                    <div style="font-size: 12px; opacity: 0.8;">à encaisser</div>
                </div>
            </div>

            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3 style="margin: 0 0 10px 0; color: #856404;">⚡ Actions Recommandées</h3>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php if ($stats['critical'] > 0): ?>
                        <li style="color: #721c24;"><strong>URGENT:</strong> <?php echo $stats['critical']; ?> colis en situation critique (≥14 jours) - Relance immédiate requise</li>
                    <?php endif; ?>
                    <?php if ($stats['waiting_7_days'] > 0): ?>
                        <li style="color: #856404;">Contacter les <?php echo $stats['waiting_7_days']; ?> clients avec colis ≥7 jours</li>
                    <?php endif; ?>
                    <?php if ($stats['waiting_3_days'] > 0): ?>
                        <li>Envoyer rappel aux <?php echo $stats['waiting_3_days']; ?> clients avec colis ≥3 jours</li>
                    <?php endif; ?>
                    <li>Vérifier l'état physique des colis en entrepôt</li>
                    <li>Mettre à jour les statuts de paiement</li>
                </ul>
            </div>

            <div style="margin-top: 20px;">
                <a href="?page=colis224-surveillance&tab=parcels" class="button button-primary">
                    📦 Voir les Colis à Risque
                </a>
                <a href="?page=colis224-surveillance&action=send_manual_report" class="button" style="margin-left: 10px;">
                    📧 Envoyer Rapport Maintenant
                </a>
            </div>
        </div>
        <?php
    }

    private static function display_at_risk_parcels() {
        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $risk_filter = isset($_GET['risk']) ? sanitize_text_field($_GET['risk']) : 'all';

        // Récupérer les colis à risque
        $where = "WHERE p.status IN ('En attente', 'Livré')";

        if ($risk_filter === 'critical') {
            $where .= " AND DATEDIFF(CURRENT_DATE, p.reception_date) >= 14";
        } elseif ($risk_filter === 'high') {
            $where .= " AND DATEDIFF(CURRENT_DATE, p.reception_date) >= 7 AND DATEDIFF(CURRENT_DATE, p.reception_date) < 14";
        } elseif ($risk_filter === 'medium') {
            $where .= " AND DATEDIFF(CURRENT_DATE, p.reception_date) >= 3 AND DATEDIFF(CURRENT_DATE, p.reception_date) < 7";
        }

        $parcels = $wpdb->get_results(
            "SELECT p.*,
             c.name as client_name,
             c.phone as client_phone,
             c.email as client_email,
             DATEDIFF(CURRENT_DATE, p.reception_date) as days_waiting
             FROM $table_parcels p
             LEFT JOIN $table_clients c ON p.client_id = c.id
             $where
             ORDER BY days_waiting DESC"
        );

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h2>📦 Colis à Risque</h2>

            <div style="margin: 20px 0;">
                <a href="?page=colis224-surveillance&tab=parcels&risk=all"
                   class="button <?php echo $risk_filter === 'all' ? 'button-primary' : ''; ?>">
                    Tous
                </a>
                <a href="?page=colis224-surveillance&tab=parcels&risk=critical"
                   class="button <?php echo $risk_filter === 'critical' ? 'button-primary' : ''; ?>"
                   style="margin-left: 5px;">
                    ⚠️ Critique (≥14j)
                </a>
                <a href="?page=colis224-surveillance&tab=parcels&risk=high"
                   class="button <?php echo $risk_filter === 'high' ? 'button-primary' : ''; ?>"
                   style="margin-left: 5px;">
                    ⚡ Élevé (7-13j)
                </a>
                <a href="?page=colis224-surveillance&tab=parcels&risk=medium"
                   class="button <?php echo $risk_filter === 'medium' ? 'button-primary' : ''; ?>"
                   style="margin-left: 5px;">
                    📌 Moyen (3-6j)
                </a>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Risque</th>
                        <th>N° Tracking</th>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>Jours Attente</th>
                        <th>Montant</th>
                        <th>Impayé</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parcels)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px;">
                                ✅ Aucun colis à risque dans cette catégorie
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($parcels as $parcel): ?>
                            <?php
                            $days = intval($parcel->days_waiting);
                            $risk_badge = '';
                            $risk_color = '';

                            if ($days >= 14) {
                                $risk_badge = '⚠️ CRITIQUE';
                                $risk_color = '#dc3545';
                            } elseif ($days >= 7) {
                                $risk_badge = '⚡ ÉLEVÉ';
                                $risk_color = '#fd7e14';
                            } elseif ($days >= 3) {
                                $risk_badge = '📌 MOYEN';
                                $risk_color = '#ffc107';
                            } else {
                                $risk_badge = '✅ BAS';
                                $risk_color = '#28a745';
                            }
                            ?>
                            <tr>
                                <td>
                                    <span style="background: <?php echo $risk_color; ?>; color: white; padding: 5px 10px; border-radius: 3px; font-size: 11px; font-weight: bold;">
                                        <?php echo $risk_badge; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($parcel->tracking_number); ?></strong>
                                </td>
                                <td><?php echo esc_html($parcel->client_name); ?></td>
                                <td>
                                    📞 <?php echo esc_html($parcel->client_phone); ?><br>
                                    <?php if ($parcel->client_email): ?>
                                        📧 <?php echo esc_html($parcel->client_email); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: <?php echo $risk_color; ?>;">
                                        <?php echo $days; ?> jours
                                    </strong>
                                </td>
                                <td><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></td>
                                <td>
                                    <span style="color: #dc3545; font-weight: bold;">
                                        <?php echo number_format($parcel->remaining_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html($parcel->status); ?><br>
                                    <small><?php echo esc_html($parcel->payment_status); ?></small>
                                </td>
                                <td>
                                    <a href="?page=colis224-parcels&action=edit&id=<?php echo $parcel->id; ?>" class="button button-small">
                                        Voir
                                    </a>
                                    <a href="?page=colis224-surveillance&action=send_reminder&parcel=<?php echo $parcel->id; ?>"
                                       class="button button-small" style="margin-top: 5px;">
                                        🔔 Relancer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function display_reminders() {
        global $wpdb;
        $table_reminders = $wpdb->prefix . 'colis224_reminders';

        $reminders = $wpdb->get_results(
            "SELECT r.*, p.tracking_number
             FROM $table_reminders r
             LEFT JOIN {$wpdb->prefix}colis224_parcels p ON r.parcel_id = p.id
             ORDER BY r.created_at DESC
             LIMIT 100"
        );

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h2>🔔 Historique des Relances</h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>N° Colis</th>
                        <th>Type</th>
                        <th>Destinataire</th>
                        <th>Message</th>
                        <th>Envoyé via</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reminders)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                Aucune relance envoyée pour le moment
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reminders as $reminder): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($reminder->created_at)); ?></td>
                                <td><?php echo esc_html($reminder->tracking_number); ?></td>
                                <td>
                                    <?php
                                    $types = array(
                                        'payment' => '💳 Paiement',
                                        'arrival' => '📦 Arrivée',
                                        'pickup' => '🚚 Retrait',
                                        'delivery' => '✅ Livraison',
                                        'waiting' => '⏳ Attente',
                                        'urgent' => '⚠️ Urgent'
                                    );
                                    echo $types[$reminder->reminder_type] ?? $reminder->reminder_type;
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $icons = array(
                                        'client' => '👤',
                                        'agent' => '👷',
                                        'admin' => '👨‍💼',
                                        'manager' => '👔'
                                    );
                                    echo $icons[$reminder->recipient_type] . ' ' . ucfirst($reminder->recipient_type);
                                    ?>
                                </td>
                                <td style="max-width: 300px;">
                                    <small><?php echo esc_html(substr($reminder->message, 0, 100)); ?>...</small>
                                </td>
                                <td>
                                    <?php
                                    $via = array(
                                        'email' => '📧 Email',
                                        'sms' => '📱 SMS',
                                        'both' => '📧📱 Email+SMS',
                                        'notification' => '🔔 Notification'
                                    );
                                    echo $via[$reminder->sent_via] ?? $reminder->sent_via;
                                    ?>
                                </td>
                                <td>
                                    <?php if ($reminder->status === 'sent'): ?>
                                        <span style="color: #28a745;">✅ Envoyé</span>
                                    <?php elseif ($reminder->status === 'failed'): ?>
                                        <span style="color: #dc3545;">❌ Échec</span>
                                    <?php else: ?>
                                        <span style="color: #ffc107;">⏳ En attente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function display_config() {
        global $wpdb;
        $table_config = $wpdb->prefix . 'colis224_reminder_config';

        // Sauvegarder les modifications
        if (isset($_POST['action']) && $_POST['action'] === 'save_config') {
            self::save_reminder_config();
        }

        $configs = $wpdb->get_results("SELECT * FROM $table_config ORDER BY trigger_value ASC");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h2>⚙️ Configuration des Relances Automatiques</h2>
            <p>Personnalisez les règles de relance automatique pour vos colis</p>

            <form method="post">
                <input type="hidden" name="action" value="save_config">
                <?php wp_nonce_field('colis224_config_action', 'colis224_config_nonce'); ?>

                <?php foreach ($configs as $config): ?>
                    <div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #667eea;">
                        <h3><?php echo esc_html($config->reminder_name); ?></h3>

                        <table class="form-table">
                            <tr>
                                <th>Type de déclencheur</th>
                                <td>
                                    <select name="configs[<?php echo $config->id; ?>][trigger_type]">
                                        <option value="days_waiting" <?php selected($config->trigger_type, 'days_waiting'); ?>>Jours d'attente</option>
                                        <option value="payment_pending" <?php selected($config->trigger_type, 'payment_pending'); ?>>Paiement en attente</option>
                                        <option value="arrival" <?php selected($config->trigger_type, 'arrival'); ?>>Arrivée</option>
                                        <option value="delivery" <?php selected($config->trigger_type, 'delivery'); ?>>Livraison</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Valeur (jours)</th>
                                <td>
                                    <input type="number" name="configs[<?php echo $config->id; ?>][trigger_value]"
                                           value="<?php echo $config->trigger_value; ?>" min="0">
                                </td>
                            </tr>
                            <tr>
                                <th>Destinataire</th>
                                <td>
                                    <select name="configs[<?php echo $config->id; ?>][recipient_type]">
                                        <option value="client" <?php selected($config->recipient_type, 'client'); ?>>Client</option>
                                        <option value="agent" <?php selected($config->recipient_type, 'agent'); ?>>Agent</option>
                                        <option value="admin" <?php selected($config->recipient_type, 'admin'); ?>>Admin</option>
                                        <option value="manager" <?php selected($config->recipient_type, 'manager'); ?>>Manager</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>Message</th>
                                <td>
                                    <textarea name="configs[<?php echo $config->id; ?>][message_template]"
                                              rows="4" class="large-text"><?php echo esc_textarea($config->message_template); ?></textarea>
                                    <p class="description">
                                        Variables disponibles: {client_name}, {tracking_number}, {amount}, {remaining_amount}, {currency}, {days_waiting}
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th>Canaux d'envoi</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="configs[<?php echo $config->id; ?>][send_email]" value="1"
                                               <?php checked($config->send_email, 1); ?>>
                                        📧 Email
                                    </label>
                                    <label style="margin-left: 20px;">
                                        <input type="checkbox" name="configs[<?php echo $config->id; ?>][send_sms]" value="1"
                                               <?php checked($config->send_sms, 1); ?>>
                                        📱 SMS
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th>Statut</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="configs[<?php echo $config->id; ?>][is_active]" value="1"
                                               <?php checked($config->is_active, 1); ?>>
                                        ✅ Actif
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endforeach; ?>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        💾 Enregistrer la Configuration
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    private static function save_reminder_config() {
        if (!isset($_POST['colis224_config_nonce']) || !wp_verify_nonce($_POST['colis224_config_nonce'], 'colis224_config_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_reminder_config';

        if (isset($_POST['configs']) && is_array($_POST['configs'])) {
            foreach ($_POST['configs'] as $id => $config) {
                $wpdb->update($table,
                    array(
                        'trigger_type' => sanitize_text_field($config['trigger_type']),
                        'trigger_value' => intval($config['trigger_value']),
                        'recipient_type' => sanitize_text_field($config['recipient_type']),
                        'message_template' => sanitize_textarea_field($config['message_template']),
                        'send_email' => isset($config['send_email']) ? 1 : 0,
                        'send_sms' => isset($config['send_sms']) ? 1 : 0,
                        'is_active' => isset($config['is_active']) ? 1 : 0
                    ),
                    array('id' => intval($id))
                );
            }
        }

        echo '<div class="notice notice-success is-dismissible"><p>✅ Configuration enregistrée avec succès!</p></div>';
    }

    private static function display_logs() {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'colis224_surveillance_logs';

        $logs = $wpdb->get_results(
            "SELECT l.*, p.tracking_number
             FROM $table_logs l
             LEFT JOIN {$wpdb->prefix}colis224_parcels p ON l.parcel_id = p.id
             ORDER BY l.created_at DESC
             LIMIT 200"
        );

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h2>📋 Logs de Surveillance</h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>N° Colis</th>
                        <th>Événement</th>
                        <th>Niveau Risque</th>
                        <th>Jours Attente</th>
                        <th>Montant Impayé</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                Aucun log de surveillance
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($log->created_at)); ?></td>
                                <td><?php echo esc_html($log->tracking_number); ?></td>
                                <td>
                                    <?php
                                    $events = array(
                                        'arrival' => '📦 Arrivée',
                                        'waiting' => '⏳ En attente',
                                        'reminder_sent' => '🔔 Relance envoyée',
                                        'payment_pending' => '💳 Paiement en attente',
                                        'high_risk' => '⚡ Risque élevé',
                                        'picked_up' => '✅ Retiré',
                                        'timeout' => '⚠️ Dépassement délai'
                                    );
                                    echo $events[$log->event_type] ?? $log->event_type;
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $risk_colors = array(
                                        'low' => '#28a745',
                                        'medium' => '#ffc107',
                                        'high' => '#fd7e14',
                                        'critical' => '#dc3545'
                                    );
                                    $risk_labels = array(
                                        'low' => '✅ Bas',
                                        'medium' => '📌 Moyen',
                                        'high' => '⚡ Élevé',
                                        'critical' => '⚠️ Critique'
                                    );
                                    ?>
                                    <span style="background: <?php echo $risk_colors[$log->risk_level]; ?>; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                        <?php echo $risk_labels[$log->risk_level]; ?>
                                    </span>
                                </td>
                                <td><?php echo $log->days_waiting; ?> jours</td>
                                <td><?php echo number_format($log->amount_pending, 0, ',', ' '); ?> GNF</td>
                                <td><small><?php echo esc_html($log->notes); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
