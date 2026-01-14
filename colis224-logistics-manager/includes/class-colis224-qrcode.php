<?php
/**
 * Système de QR Codes et Codes-Barres
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_QRCode {

    public function __construct() {
        // Hook pour ajouter le bouton de génération de QR code
        add_action('admin_init', array($this, 'init'));
        add_action('wp_ajax_colis224_generate_qr_code', array($this, 'ajax_generate_qr_code'));
        add_action('wp_ajax_colis224_print_label', array($this, 'ajax_print_label'));
    }

    public function init() {
        // Rien pour l'instant
    }

    /**
     * Générer un QR code pour un colis
     */
    public function generate_qr_code($tracking_number, $size = 300, $parcel_data = null) {
        // URL de suivi simple - toujours utiliser l'URL au lieu de vCard
        $qr_content = home_url('?colis224_track=' . urlencode($tracking_number));

        // Utiliser l'API QR Server pour générer le QR code
        $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query(array(
            'size' => $size . 'x' . $size,
            'data' => $qr_content,
            'format' => 'png',
            'ecc' => 'M' // Error correction level
        ));

        return $qr_code_url;
    }

    /**
     * Générer un code-barres (Code 128)
     */
    public function generate_barcode($tracking_number, $width = 2, $height = 50) {
        // Utiliser l'API de Barcode Generator
        $barcode_url = 'https://barcode.tec-it.com/barcode.ashx?' . http_build_query(array(
            'data' => $tracking_number,
            'code' => 'Code128',
            'dpi' => 96,
            'dataseparator' => '',
            'width' => $width,
            'height' => $height
        ));

        return $barcode_url;
    }

    /**
     * Générer une étiquette d'expédition complète
     */
    public function generate_shipping_label($parcel_id) {
        global $wpdb;

        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name as client_name, co.name as origin_country, cd.name as destination_country
            FROM {$wpdb->prefix}colis224_parcels p
            LEFT JOIN {$wpdb->prefix}colis224_clients c ON p.client_id = c.id
            LEFT JOIN {$wpdb->prefix}colis224_countries co ON p.origin_country_id = co.id
            LEFT JOIN {$wpdb->prefix}colis224_countries cd ON p.destination_country_id = cd.id
            WHERE p.id = %d",
            $parcel_id
        ));

        if (!$parcel) {
            return false;
        }

        // Générer QR code avec données enrichies et code-barres
        $qr_code_url = $this->generate_qr_code($parcel->tracking_number, 300, $parcel);
        $barcode_url = $this->generate_barcode($parcel->tracking_number);

        // HTML de l'étiquette
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Étiquette - <?php echo esc_html($parcel->tracking_number); ?></title>
            <style>
                @media print {
                    @page {
                        size: 10cm 15cm;
                        margin: 0;
                    }
                    body {
                        margin: 0;
                        padding: 0;
                    }
                    .no-print {
                        display: none !important;
                    }
                }

                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 10px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                }

                .label-container {
                    width: 10cm;
                    min-height: 15cm;
                    border: 3px solid #000;
                    padding: 15px;
                    box-sizing: border-box;
                    position: relative;
                    background: #fff;
                }

                .label-header {
                    text-align: center;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                    margin-bottom: 10px;
                }

                .label-header h1 {
                    margin: 0;
                    font-size: 24px;
                    color: #667eea;
                }

                .label-header p {
                    margin: 5px 0 0 0;
                    font-size: 12px;
                }

                .tracking-section {
                    text-align: center;
                    margin: 15px 0;
                }

                .tracking-number {
                    font-size: 28px;
                    font-weight: bold;
                    letter-spacing: 2px;
                    margin: 10px 0;
                }

                .qr-code {
                    text-align: center;
                    margin: 15px 0;
                }

                .qr-code img {
                    width: 120px;
                    height: 120px;
                }

                .barcode {
                    text-align: center;
                    margin: 15px 0;
                }

                .barcode img {
                    width: 200px;
                    height: 60px;
                }

                .recipient-section {
                    border: 1px solid #000;
                    padding: 10px;
                    margin: 10px 0;
                }

                .recipient-section h3 {
                    margin: 0 0 10px 0;
                    font-size: 14px;
                    background: #000;
                    color: #fff;
                    padding: 5px;
                }

                .recipient-info {
                    font-size: 12px;
                    line-height: 1.5;
                }

                .recipient-info strong {
                    display: block;
                    font-size: 14px;
                    margin-bottom: 5px;
                }

                .label-footer {
                    position: relative;
                    margin-top: 20px;
                    padding-top: 10px;
                    text-align: center;
                    font-size: 12px;
                    border-top: 2px solid #000;
                    font-weight: 600;
                }

                .print-button {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 30px;
                    background: #667eea;
                    color: #fff;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 16px;
                    z-index: 1000;
                }

                .print-button:hover {
                    background: #764ba2;
                }
            </style>
        </head>
        <body>
            <button onclick="window.print()" class="print-button no-print">
                🖨️ Imprimer l'Étiquette
            </button>

            <div class="label-container">
                <div class="label-header">
                    <p style="margin: 0; font-size: 11px; color: #667eea; font-weight: bold;">www.colis224.com</p>
                    <h1><?php echo esc_html(get_option('colis224_company_name', 'COLIS224')); ?></h1>
                    <p>Livraison Internationale Express</p>
                </div>

                <div class="tracking-section">
                    <div class="tracking-number"><?php echo esc_html($parcel->tracking_number); ?></div>
                </div>

                <div class="qr-code">
                    <img src="<?php echo esc_url($qr_code_url); ?>" alt="QR Code">
                </div>

                <div class="barcode">
                    <img src="<?php echo esc_url($barcode_url); ?>" alt="Code-barres">
                </div>

                <div class="recipient-section">
                    <h3>📦 DESTINATAIRE</h3>
                    <div class="recipient-info">
                        <strong><?php echo esc_html($parcel->recipient_name); ?></strong>
                        📞 <?php echo esc_html($parcel->recipient_phone); ?><br>
                        📍 <?php echo nl2br(esc_html($parcel->recipient_address)); ?>
                    </div>
                </div>

                <?php if ($parcel->sender_name): ?>
                <div class="recipient-section" style="margin-top: 10px; border-color: #667eea;">
                    <h3 style="background: #667eea;">📤 EXPÉDITEUR</h3>
                    <div class="recipient-info">
                        <strong><?php echo esc_html($parcel->sender_name); ?></strong>
                        <?php if ($parcel->client_name): ?>
                            <br>Client: <?php echo esc_html($parcel->client_name); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="info-section" style="margin-top: 10px; padding: 8px; background: #f8f9fa; border-radius: 4px; font-size: 11px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span><strong>Poids:</strong> <?php echo esc_html($parcel->weight); ?> kg</span>
                        <span><strong>Montant:</strong> <?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span><strong>Statut:</strong> <?php echo esc_html($parcel->status); ?></span>
                        <span><strong>Paiement:</strong> <?php echo esc_html($parcel->payment_status); ?></span>
                    </div>
                </div>

                <div class="label-footer">
                    <?php echo esc_html($parcel->origin_country); ?> → <?php echo esc_html($parcel->destination_country); ?> |
                    Date: <?php echo date('d/m/Y', strtotime($parcel->created_at)); ?>
                    <?php if ($parcel->estimated_delivery_date): ?>
                        | Livraison prévue: <?php echo date('d/m/Y', strtotime($parcel->estimated_delivery_date)); ?>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Générer QR code
     */
    public function ajax_generate_qr_code() {
        check_ajax_referer('colis224_nonce', 'nonce');

        $tracking_number = sanitize_text_field($_POST['tracking_number']);
        $qr_code_url = $this->generate_qr_code($tracking_number);

        wp_send_json_success(array(
            'qr_code_url' => $qr_code_url
        ));
    }

    /**
     * AJAX: Imprimer étiquette
     */
    public function ajax_print_label() {
        check_ajax_referer('colis224_nonce', 'nonce');

        $parcel_id = intval($_POST['parcel_id']);
        $label_html = $this->generate_shipping_label($parcel_id);

        if (!$label_html) {
            wp_send_json_error(array('message' => 'Colis introuvable'));
        }

        wp_send_json_success(array(
            'label_html' => $label_html
        ));
    }

    /**
     * Afficher le bouton d'impression d'étiquette simple
     */
    public static function display_qr_code_button($parcel_id, $tracking_number) {
        ?>
        <button type="button" class="button button-primary" onclick="colis224PrintLabel(<?php echo $parcel_id; ?>)" style="margin-right: 10px;">
            <span class="dashicons dashicons-printer"></span> Imprimer l'Étiquette
        </button>

        <script>
        function colis224PrintLabel(parcelId) {
            var printWindow = window.open('', '_blank');
            printWindow.document.write('<html><head><title>Chargement...</title><style>body{font-family:Arial;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f5f5f5;}.loader{text-align:center;}.spinner{border:4px solid #f3f3f3;border-top:4px solid #667eea;border-radius:50%;width:50px;height:50px;animation:spin 1s linear infinite;margin:0 auto 20px;}@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}</style></head><body><div class="loader"><div class="spinner"></div><p style="color:#667eea;font-weight:bold;">Chargement de l\'étiquette...</p></div></body></html>');

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'colis224_print_label',
                    nonce: colis224Ajax.nonce,
                    parcel_id: parcelId
                },
                success: function(response) {
                    if (response.success) {
                        printWindow.document.open();
                        printWindow.document.write(response.data.label_html);
                        printWindow.document.close();
                    } else {
                        printWindow.close();
                        alert('Erreur lors de la génération de l\'étiquette');
                    }
                },
                error: function() {
                    printWindow.close();
                    alert('Erreur de connexion');
                }
            });
        }
        </script>
        <?php
    }

    /**
     * Afficher le QR code dans une section séparée
     */
    public static function display_qr_code_section($parcel_id, $tracking_number) {
        $qrcode = new self();
        $qr_code_url = $qrcode->generate_qr_code($tracking_number, 200);

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-smartphone"></span> QR Code de Suivi</h3>
            <div style="text-align: center; padding: 20px; background: #fff; border-radius: 8px;">
                <img src="<?php echo esc_url($qr_code_url); ?>" alt="QR Code" style="max-width: 200px; border: 1px solid #ddd; padding: 10px;">
            </div>
        </div>
        <?php
    }
}

// Initialiser
new Colis224_QRCode();
