<?php
/**
 * Système d'alertes et notifications admin
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Admin_Alerts {

    public function __construct() {
        // Hook pour afficher les notifications admin
        add_action('admin_notices', array($this, 'display_admin_notifications'));

        // Ajouter les styles pour les notifications
        add_action('admin_head', array($this, 'add_notification_styles'));

        // Ajouter le script pour les relances rapides
        add_action('admin_footer', array($this, 'add_quick_remind_script'));

        // AJAX handler pour envoi rapide de message
        add_action('wp_ajax_colis224_quick_remind', array($this, 'ajax_quick_remind'));
    }

    /**
     * Afficher les notifications admin
     */
    public function display_admin_notifications() {
        // Vérifier si l'utilisateur peut voir les colis
        if (!current_user_can('colis224_view_parcels')) {
            return;
        }

        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // 1. Notification : Colis en attente de validation
        $pending_validation = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_parcels}
            WHERE validation_status IN ('pending', 'draft')"
        );

        if ($pending_validation > 0) {
            $url = admin_url('admin.php?page=colis224-parcels&filter_validation=pending');
            echo '<div class="notice notice-warning colis224-alert-validation">';
            echo '<p>';
            echo '<span class="dashicons dashicons-warning" style="color: #f0ad4e;"></span> ';
            echo '<strong>⚠️ ' . $pending_validation . ' colis en attente de validation</strong> ';
            echo '— <a href="' . esc_url($url) . '" class="button button-small">Voir les colis</a>';
            echo '</p>';
            echo '</div>';
        }

        // 2. Notification : Colis disponibles à Conakry non retirés (plus de 3 jours)
        // CORRECTION v2.20.12: Exclure les colis déjà payés totalement
        $parcels_waiting_pickup = $wpdb->get_results(
            "SELECT p.*, c.name as client_name, c.phone as client_phone, c.email as client_email
            FROM {$table_parcels} p
            LEFT JOIN {$wpdb->prefix}colis224_clients c ON p.client_id = c.id
            WHERE p.status = 'Livré'
            AND p.delivery_date IS NOT NULL
            AND DATEDIFF(CURDATE(), p.delivery_date) >= 3
            AND p.delivery_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND (p.payment_status IS NULL OR p.payment_status != 'paid')
            ORDER BY p.delivery_date ASC
            LIMIT 10"
        );

        if (!empty($parcels_waiting_pickup)) {
            echo '<div class="notice notice-info colis224-alert-pickup">';
            echo '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">';
            echo '<span class="dashicons dashicons-phone" style="color: #0073aa; font-size: 20px;"></span>';
            echo '<strong>📞 ' . count($parcels_waiting_pickup) . ' clients à relancer pour retrait à Conakry</strong>';
            echo '</div>';

            echo '<div style="max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 10px; border-radius: 5px;">';
            echo '<table class="widefat" style="background: white;">';
            echo '<thead><tr>';
            echo '<th>📦 N° Suivi</th>';
            echo '<th>👤 Client</th>';
            echo '<th>📱 Téléphone</th>';
            echo '<th>📅 Disponible depuis</th>';
            echo '<th>⚡ Actions</th>';
            echo '</tr></thead><tbody>';

            foreach ($parcels_waiting_pickup as $parcel) {
                $days_waiting = floor((time() - strtotime($parcel->delivery_date)) / (60 * 60 * 24));
                $urgency_class = $days_waiting >= 7 ? 'style="background: #fff3cd;"' : '';

                echo '<tr ' . $urgency_class . '>';
                echo '<td><strong>' . esc_html($parcel->tracking_number) . '</strong></td>';
                echo '<td>' . esc_html($parcel->client_name ?: $parcel->recipient_name) . '</td>';
                echo '<td><a href="tel:' . esc_attr($parcel->client_phone ?: $parcel->recipient_phone) . '">';
                echo esc_html($parcel->client_phone ?: $parcel->recipient_phone) . '</a></td>';
                echo '<td>' . esc_html($parcel->delivery_date) . ' <span style="color: #999;">(' . $days_waiting . ' jours)</span></td>';
                echo '<td>';

                // Bouton SMS/Email rapide
                if ($parcel->client_phone || $parcel->recipient_phone) {
                    $phone = $parcel->client_phone ?: $parcel->recipient_phone;
                    echo '<a href="#" class="button button-small colis224-quick-remind" ';
                    echo 'data-parcel-id="' . $parcel->id . '" ';
                    echo 'data-phone="' . esc_attr($phone) . '" ';
                    echo 'data-tracking="' . esc_attr($parcel->tracking_number) . '" ';
                    echo 'data-email="' . esc_attr($parcel->client_email) . '" ';
                    echo 'title="Envoyer une relance">💬 Relancer</a> ';
                }

                echo '<a href="' . admin_url('admin.php?page=colis224-parcels&action=edit&id=' . $parcel->id) . '" ';
                echo 'class="button button-small">Voir</a>';
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * Ajouter les styles pour les notifications
     */
    public function add_notification_styles() {
        ?>
        <style>
            .colis224-alert-validation,
            .colis224-alert-pickup {
                border-left-width: 4px;
                padding: 15px;
            }

            .colis224-alert-validation {
                border-left-color: #f0ad4e;
            }

            .colis224-alert-pickup {
                border-left-color: #0073aa;
            }

            .colis224-quick-remind {
                background: #2271b1 !important;
                border-color: #2271b1 !important;
                color: white !important;
            }

            .colis224-quick-remind:hover {
                background: #135e96 !important;
                border-color: #135e96 !important;
            }
        </style>
        <?php
    }

    /**
     * Ajouter le script pour les relances rapides
     */
    public function add_quick_remind_script() {
        global $wpdb;

        // Ne charger que sur les pages du plugin
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'colis224') === false) {
            return;
        }

        // Charger les catégories et messages
        $categories = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}colis224_message_categories WHERE is_active = 1 ORDER BY display_order ASC"
        );

        $messages = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}colis224_message_templates WHERE is_active = 1 ORDER BY title ASC"
        );

        ?>
        <script>
        jQuery(document).ready(function($) {
            // Handler pour le bouton de relance rapide
            $('.colis224-quick-remind').on('click', function(e) {
                e.preventDefault();

                var btn = $(this);
                var parcelId = btn.data('parcel-id');
                var phone = btn.data('phone');
                var email = btn.data('email');
                var tracking = btn.data('tracking');

                // Créer une modale pour sélectionner le message
                var modalHtml = '<div id="colis224-remind-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 999999; display: flex; align-items: center; justify-content: center;">';
                modalHtml += '<div style="background: white; border-radius: 8px; padding: 30px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">';
                modalHtml += '<h2 style="margin-top: 0;">📨 Envoyer une relance</h2>';
                modalHtml += '<p><strong>Colis:</strong> ' + tracking + '</p>';
                modalHtml += '<p><strong>Contact:</strong> ' + (phone || email || 'N/A') + '</p>';

                // Liste des messages par catégorie
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $cat_messages = array_filter($messages, function($m) use ($cat) {
                        return $m->category_id == $cat->id;
                    });
                    if (empty($cat_messages)) continue;
                    ?>
                    modalHtml += '<div style="margin: 20px 0;">';
                    modalHtml += '<h3 style="color: <?php echo esc_js($cat->color); ?>; margin-bottom: 10px;">📁 <?php echo esc_js($cat->name); ?></h3>';
                    <?php foreach ($cat_messages as $msg): ?>
                        modalHtml += '<button class="button colis224-select-message" data-message-id="<?php echo $msg->id; ?>" style="margin: 5px; display: block; text-align: left; width: 100%;">';
                        modalHtml += '<?php echo esc_js($msg->title); ?>';
                        modalHtml += '</button>';
                    <?php endforeach; ?>
                    modalHtml += '</div>';
                <?php endforeach; ?>

                modalHtml += '<div style="margin-top: 20px; text-align: right;">';
                modalHtml += '<button id="colis224-close-modal" class="button">❌ Fermer</button>';
                modalHtml += '</div>';
                modalHtml += '</div></div>';

                $('body').append(modalHtml);

                // Handler pour fermer la modale
                $('#colis224-close-modal, #colis224-remind-modal').on('click', function(e) {
                    if (e.target === this) {
                        $('#colis224-remind-modal').remove();
                    }
                });

                // Handler pour sélectionner un message
                $('.colis224-select-message').on('click', function() {
                    var messageId = $(this).data('message-id');

                    // Envoyer via AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'colis224_quick_remind',
                            parcel_id: parcelId,
                            message_id: messageId,
                            phone: phone,
                            email: email,
                            _ajax_nonce: '<?php echo wp_create_nonce('colis224_quick_remind'); ?>'
                        },
                        beforeSend: function() {
                            $('.colis224-select-message').prop('disabled', true).text('⏳ Envoi en cours...');
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('✅ Message envoyé avec succès !');
                                $('#colis224-remind-modal').remove();
                            } else {
                                alert('❌ Erreur: ' + (response.data || 'Erreur inconnue'));
                                $('.colis224-select-message').prop('disabled', false).each(function() {
                                    $(this).text($(this).data('original-text'));
                                });
                            }
                        },
                        error: function() {
                            alert('❌ Erreur de connexion');
                            $('.colis224-select-message').prop('disabled', false);
                        }
                    });
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler pour envoi rapide de message
     */
    public function ajax_quick_remind() {
        check_ajax_referer('colis224_quick_remind');

        if (!current_user_can('colis224_manage_all')) {
            wp_send_json_error('Permission refusée');
            return;
        }

        global $wpdb;

        $parcel_id = intval($_POST['parcel_id']);
        $message_id = intval($_POST['message_id']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);

        // Récupérer le colis
        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name as client_name
            FROM {$wpdb->prefix}colis224_parcels p
            LEFT JOIN {$wpdb->prefix}colis224_clients c ON p.client_id = c.id
            WHERE p.id = %d",
            $parcel_id
        ));

        if (!$parcel) {
            wp_send_json_error('Colis introuvable');
            return;
        }

        // Récupérer le message
        $message_template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}colis224_message_templates WHERE id = %d",
            $message_id
        ));

        if (!$message_template) {
            wp_send_json_error('Message introuvable');
            return;
        }

        // Préparer les variables
        $variables = array(
            'client_name' => $parcel->client_name ?: $parcel->recipient_name,
            'tracking_number' => $parcel->tracking_number,
            'amount' => number_format($parcel->total_amount, 0, ',', ' '),
            'delivery_date' => $parcel->delivery_date,
            'phone' => $phone
        );

        // Remplacer les variables dans le message
        $subject = Colis224_Message_Templates::replace_variables($message_template->subject, $variables);
        $body = Colis224_Message_Templates::replace_variables($message_template->message_body, $variables);

        // Envoyer le message selon le type
        $sent_email = false;
        $sent_sms = false;

        // Envoyer EMAIL
        if (($message_template->message_type === 'email' || $message_template->message_type === 'all') && $email) {
            if (class_exists('Colis224_Notifications')) {
                $notif = new Colis224_Notifications();
                $sent_email = $notif->send_email($email, $subject, $body, $parcel);
            }
        }

        // Envoyer SMS
        $sms_error_message = '';
        if (($message_template->message_type === 'sms' || $message_template->message_type === 'whatsapp' || $message_template->message_type === 'all') && $phone) {
            if (class_exists('Colis224_SMS_API')) {
                $sms_api = new Colis224_SMS_API();
                $sms_result = $sms_api->send_sms($phone, $body, $parcel_id);
                $sent_sms = $sms_result['success'];
                if (!$sent_sms) {
                    $sms_error_message = $sms_result['message'] ?? 'Erreur lors de l\'envoi du SMS';
                }
            }
        }

        // Incrémenter le compteur d'utilisation
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}colis224_message_templates SET usage_count = usage_count + 1 WHERE id = %d",
            $message_id
        ));

        // Résultat
        if ($sent_email || $sent_sms) {
            $messages = array();
            if ($sent_email) $messages[] = 'Email';
            if ($sent_sms) $messages[] = 'SMS';
            wp_send_json_success(implode(' et ', $messages) . ' envoyé(s) avec succès !');
        } else {
            // Afficher le message d'erreur spécifique
            $error_msg = 'Impossible d\'envoyer le message';
            if (!empty($sms_error_message)) {
                $error_msg = $sms_error_message;
            }
            wp_send_json_error($error_msg);
        }
    }
}

// Initialiser
new Colis224_Admin_Alerts();
