<?php
/**
 * Système de Notifications Automatiques (Email + SMS + WhatsApp)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Notifications {

    public function __construct() {
        // Hooks pour notifications automatiques
        add_action('colis224_parcel_status_changed', array($this, 'notify_status_change'), 10, 3);
        add_action('colis224_parcel_created', array($this, 'notify_new_parcel'), 10, 2);
        add_action('colis224_payment_received', array($this, 'notify_payment'), 10, 2);
    }

    /**
     * Notification de changement de statut
     */
    public function notify_status_change($parcel_id, $old_status, $new_status) {
        global $wpdb;

        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name as client_name, c.email as client_email, c.phone as client_phone
            FROM {$wpdb->prefix}colis224_parcels p
            LEFT JOIN {$wpdb->prefix}colis224_clients c ON p.client_id = c.id
            WHERE p.id = %d",
            $parcel_id
        ));

        if (!$parcel) {
            return;
        }

        $message = $this->get_status_message($new_status, $parcel->tracking_number);

        // Envoyer email
        if (get_option('colis224_email_notifications', '1') === '1') {
            $this->send_email(
                $parcel->client_email ?: $parcel->recipient_name,
                "Mise à jour de votre colis " . $parcel->tracking_number,
                $message,
                $parcel
            );
        }

        // Envoyer SMS
        if (get_option('colis224_sms_notifications', '0') === '1') {
            $phone = $parcel->client_phone ?: $parcel->recipient_phone;
            $this->send_sms($phone, $message);
        }

        // Envoyer WhatsApp (si configuré)
        if (get_option('colis224_whatsapp_notifications', '0') === '1') {
            $phone = $parcel->client_phone ?: $parcel->recipient_phone;
            $this->send_whatsapp($phone, $message, $parcel);
        }

        // Enregistrer la notification
        $this->log_notification($parcel_id, 'status_change', $message);
    }

    /**
     * Notification de création de colis
     */
    public function notify_new_parcel($parcel_id, $parcel_data) {
        global $wpdb;

        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.email as client_email, c.phone as client_phone
            FROM {$wpdb->prefix}colis224_parcels p
            LEFT JOIN {$wpdb->prefix}colis224_clients c ON p.client_id = c.id
            WHERE p.id = %d",
            $parcel_id
        ));

        $message = "Bonjour ! Votre colis {$parcel->tracking_number} a été enregistré chez Colis224. Vous pouvez le suivre en ligne.";

        if (get_option('colis224_email_notifications', '1') === '1' && $parcel->client_email) {
            $this->send_email(
                $parcel->client_email,
                "Nouveau colis enregistré - " . $parcel->tracking_number,
                $message,
                $parcel
            );
        }
    }

    /**
     * Notification de paiement
     */
    public function notify_payment($parcel_id, $amount) {
        global $wpdb;

        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.email as client_email, c.phone as client_phone
            FROM {$wpdb->prefix}colis224_parcels p
            LEFT JOIN {$wpdb->prefix}colis224_clients c ON p.client_id = c.id
            WHERE p.id = %d",
            $parcel_id
        ));

        $message = "Paiement de " . number_format($amount, 0, ',', ' ') . " GNF reçu pour votre colis {$parcel->tracking_number}. Merci !";

        if ($parcel->client_email) {
            $this->send_email(
                $parcel->client_email,
                "Confirmation de paiement - Colis224",
                $message,
                $parcel
            );
        }
    }

    /**
     * Envoyer un email
     */
    public function send_email($to, $subject, $message, $parcel = null) {
        $company_name = get_option('colis224_company_name', 'Colis224');
        $company_email = get_option('colis224_company_email', get_option('admin_email'));

        // En-tête HTML
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $headers[] = "From: {$company_name} <{$company_email}>";

        // Template email
        $email_body = $this->get_email_template($subject, $message, $parcel);

        // Envoyer
        $result = wp_mail($to, $subject, $email_body, $headers);

        // Logger
        if (!$result) {
            error_log("Colis224: Échec envoi email à {$to}");
        }

        return $result;
    }

    /**
     * Template email HTML
     */
    private function get_email_template($subject, $message, $parcel = null) {
        $company_name = get_option('colis224_company_name', 'Colis224');
        $company_phone = get_option('colis224_company_phone', '');
        $company_email = get_option('colis224_company_email', '');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background: #fff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .email-header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: #fff;
                    padding: 30px 20px;
                    text-align: center;
                }
                .email-header h1 {
                    margin: 0;
                    font-size: 28px;
                }
                .email-body {
                    padding: 30px 20px;
                }
                .email-message {
                    background: #f8f9fa;
                    border-left: 4px solid #667eea;
                    padding: 15px;
                    margin: 20px 0;
                }
                .parcel-info {
                    background: #fff;
                    border: 2px solid #e1e4e8;
                    border-radius: 8px;
                    padding: 20px;
                    margin: 20px 0;
                }
                .parcel-info h3 {
                    margin-top: 0;
                    color: #667eea;
                }
                .parcel-info table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .parcel-info td {
                    padding: 8px 0;
                    border-bottom: 1px solid #f0f0f1;
                }
                .parcel-info td:first-child {
                    font-weight: bold;
                    width: 40%;
                }
                .track-button {
                    display: inline-block;
                    background: #667eea;
                    color: #fff !important;
                    padding: 12px 30px;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .email-footer {
                    background: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    font-size: 14px;
                    color: #646970;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1><?php echo esc_html($company_name); ?></h1>
                    <p>Livraison Internationale</p>
                </div>

                <div class="email-body">
                    <h2><?php echo esc_html($subject); ?></h2>

                    <div class="email-message">
                        <?php echo nl2br(esc_html($message)); ?>
                    </div>

                    <?php if ($parcel): ?>
                    <div class="parcel-info">
                        <h3>Informations du Colis</h3>
                        <table>
                            <tr>
                                <td>N° de suivi:</td>
                                <td><strong><?php echo esc_html($parcel->tracking_number); ?></strong></td>
                            </tr>
                            <tr>
                                <td>Destinataire:</td>
                                <td><?php echo esc_html($parcel->recipient_name); ?></td>
                            </tr>
                            <tr>
                                <td>Statut:</td>
                                <td><strong><?php echo esc_html($parcel->status); ?></strong></td>
                            </tr>
                            <tr>
                                <td>Montant:</td>
                                <td><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></td>
                            </tr>
                        </table>
                    </div>

                    <center>
                        <a href="<?php echo home_url('/suivi-colis'); ?>" class="track-button">
                            Suivre Mon Colis
                        </a>
                    </center>
                    <?php endif; ?>
                </div>

                <div class="email-footer">
                    <p><strong><?php echo esc_html($company_name); ?></strong></p>
                    <?php if ($company_phone): ?>
                    <p>Téléphone: <?php echo esc_html($company_phone); ?></p>
                    <?php endif; ?>
                    <?php if ($company_email): ?>
                    <p>Email: <?php echo esc_html($company_email); ?></p>
                    <?php endif; ?>
                    <p style="margin-top: 20px; font-size: 12px;">
                        Cet email a été envoyé automatiquement, merci de ne pas y répondre.
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Envoyer SMS
     */
    public function send_sms($phone, $message) {
        $api_url = get_option('colis224_sms_api_url', '');
        $api_key = get_option('colis224_sms_api_key', '');

        if (empty($api_url) || empty($api_key)) {
            error_log("Colis224: Configuration SMS manquante");
            return false;
        }

        // Nettoyer le numéro
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Exemple d'intégration (à adapter selon votre fournisseur SMS)
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'to' => $phone,
                'message' => $message,
                'sender' => 'Colis224'
            )),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            error_log("Colis224: Erreur SMS - " . $response->get_error_message());
            return false;
        }

        return true;
    }

    /**
     * Envoyer WhatsApp (via WhatsApp Business API)
     */
    public function send_whatsapp($phone, $message, $parcel = null) {
        $api_url = get_option('colis224_whatsapp_api_url', '');
        $api_key = get_option('colis224_whatsapp_api_key', '');

        if (empty($api_url) || empty($api_key)) {
            return false;
        }

        // Nettoyer le numéro (format international)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }

        // Template WhatsApp
        $whatsapp_message = $message;
        if ($parcel) {
            $whatsapp_message .= "\n\n";
            $whatsapp_message .= "📦 *Colis:* " . $parcel->tracking_number . "\n";
            $whatsapp_message .= "📍 *Statut:* " . $parcel->status . "\n";
            $whatsapp_message .= "🔗 Suivre: " . home_url('/suivi-colis');
        }

        // Exemple d'intégration WhatsApp Business API
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'text',
                'text' => array(
                    'body' => $whatsapp_message
                )
            )),
            'timeout' => 15
        ));

        if (is_wp_error($response)) {
            error_log("Colis224: Erreur WhatsApp - " . $response->get_error_message());
            return false;
        }

        return true;
    }

    /**
     * Messages selon le statut
     */
    private function get_status_message($status, $tracking_number) {
        $messages = array(
            'En attente' => "Votre colis {$tracking_number} est enregistré et en attente d'expédition.",
            'Expédié' => "Votre colis {$tracking_number} a été expédié ! Il est en route vers sa destination.",
            'En transit' => "Votre colis {$tracking_number} est en transit. Livraison prochaine.",
            'Livré' => "Votre colis {$tracking_number} a été livré avec succès ! Merci de votre confiance.",
            'Retour' => "Votre colis {$tracking_number} est en cours de retour. Contactez-nous pour plus d'informations."
        );

        return isset($messages[$status]) ? $messages[$status] : "Mise à jour de votre colis {$tracking_number}";
    }

    /**
     * Enregistrer la notification dans la base
     */
    private function log_notification($parcel_id, $type, $message) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'colis224_notifications',
            array(
                'recipient_type' => 'client',
                'recipient_id' => $parcel_id,
                'notification_type' => 'both',
                'subject' => $type,
                'message' => $message,
                'status' => 'sent',
                'sent_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Envoyer une notification manuelle
     */
    public static function send_custom_notification($recipient_email, $recipient_phone, $subject, $message) {
        $notifications = new self();

        // Email
        if ($recipient_email) {
            $notifications->send_email($recipient_email, $subject, $message);
        }

        // SMS
        if ($recipient_phone && get_option('colis224_sms_notifications', '0') === '1') {
            $notifications->send_sms($recipient_phone, $message);
        }

        return true;
    }
}

// Initialiser les notifications
new Colis224_Notifications();
