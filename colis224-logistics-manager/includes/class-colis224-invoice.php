<?php
/**
 * Système de Facturation PDF Automatique
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Invoice {

    public function __construct() {
        add_action('wp_ajax_colis224_generate_invoice', array($this, 'ajax_generate_invoice'));
        add_action('wp_ajax_colis224_download_invoice', array($this, 'ajax_download_invoice'));
    }

    /**
     * Générer une facture HTML (convertible en PDF)
     */
    public function generate_invoice_html($parcel_id) {
        global $wpdb;

        $parcel = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name as client_name, c.phone as client_phone, c.email as client_email,
                    c.address as client_address, co.name as origin_country, cd.name as destination_country
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

        // Numéro de facture
        $invoice_number = get_option('colis224_invoice_prefix', 'INV-') . str_pad($parcel_id, 6, '0', STR_PAD_LEFT);

        // Informations entreprise
        $company_name = get_option('colis224_company_name', 'Colis224');
        $company_address = get_option('colis224_company_address', '');
        $company_phone = get_option('colis224_company_phone', '');
        $company_email = get_option('colis224_company_email', '');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Facture <?php echo esc_html($invoice_number); ?></title>
            <style>
                @media print {
                    @page {
                        size: A4;
                        margin: 15mm;
                    }
                    .no-print {
                        display: none !important;
                    }
                }

                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    max-width: 210mm;
                    margin: 0 auto;
                    padding: 20px;
                    color: #333;
                    background: #f5f5f5;
                }

                .invoice-container {
                    background: #fff;
                    border: 3px solid #667eea;
                    padding: 40px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                    border-radius: 10px;
                }

                .invoice-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 40px;
                    padding-bottom: 25px;
                    border-bottom: 4px solid #667eea;
                    background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
                    padding: 25px;
                    border-radius: 8px;
                    margin: -10px -10px 40px -10px;
                }

                .company-info h1 {
                    margin: 0 0 10px 0;
                    color: #667eea;
                    font-size: 36px;
                    font-weight: 700;
                    letter-spacing: -1px;
                }

                .company-info .website {
                    color: #667eea;
                    font-weight: 600;
                    font-size: 13px;
                    margin: 5px 0;
                }

                .company-info p {
                    margin: 6px 0;
                    font-size: 14px;
                    color: #555;
                    line-height: 1.6;
                }

                .company-info p strong {
                    color: #333;
                    font-weight: 600;
                }

                .invoice-details {
                    text-align: right;
                }

                .invoice-title {
                    font-size: 28px;
                    font-weight: bold;
                    color: #667eea;
                    margin: 0 0 10px 0;
                }

                .invoice-number {
                    font-size: 18px;
                    font-weight: bold;
                    margin: 5px 0;
                }

                .invoice-date {
                    font-size: 14px;
                    color: #666;
                }

                .client-section {
                    display: flex;
                    justify-content: space-between;
                    margin: 30px 0;
                }

                .info-box {
                    flex: 1;
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 0 10px;
                }

                .info-box:first-child {
                    margin-left: 0;
                }

                .info-box:last-child {
                    margin-right: 0;
                }

                .info-box h3 {
                    margin: 0 0 15px 0;
                    font-size: 16px;
                    color: #667eea;
                    border-bottom: 2px solid #667eea;
                    padding-bottom: 5px;
                }

                .info-box p {
                    margin: 8px 0;
                    font-size: 14px;
                }

                .items-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 30px 0;
                }

                .items-table thead {
                    background: #667eea;
                    color: #fff;
                }

                .items-table th {
                    padding: 15px;
                    text-align: left;
                    font-size: 14px;
                }

                .items-table td {
                    padding: 15px;
                    border-bottom: 1px solid #ddd;
                    font-size: 14px;
                }

                .items-table tbody tr:hover {
                    background: #f8f9fa;
                }

                .total-section {
                    text-align: right;
                    margin: 30px 0;
                }

                .total-table {
                    display: inline-block;
                    min-width: 300px;
                }

                .total-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 10px 0;
                    font-size: 16px;
                }

                .total-row.subtotal {
                    border-top: 1px solid #ddd;
                }

                .total-row.discount {
                    color: #38ef7d;
                }

                .total-row.grand-total {
                    border-top: 2px solid #667eea;
                    font-size: 20px;
                    font-weight: bold;
                    color: #667eea;
                    padding-top: 15px;
                }

                .payment-info {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 30px 0;
                }

                .payment-info h3 {
                    margin: 0 0 15px 0;
                    color: #667eea;
                }

                .payment-status {
                    display: inline-block;
                    padding: 8px 20px;
                    border-radius: 20px;
                    font-weight: bold;
                    font-size: 14px;
                }

                .payment-status.paid {
                    background: #d4f4dd;
                    color: #0f5323;
                }

                .payment-status.partial {
                    background: #fef7e0;
                    color: #8a6116;
                }

                .payment-status.unpaid {
                    background: #ffd8d8;
                    color: #8b1a1a;
                }

                .footer {
                    margin-top: 50px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
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
                🖨️ Imprimer / Télécharger PDF
            </button>

            <div class="invoice-container">
                <!-- En-tête -->
                <div class="invoice-header">
                    <div class="company-info">
                        <h1><?php echo esc_html($company_name); ?></h1>
                        <p class="website">🌐 www.colis224.com</p>
                        <p><strong>📧 Email:</strong> contact@colis224.com</p>
                        <p><strong>📞 Téléphone:</strong> +224 620 17 89 30</p>
                        <p><strong>📍 Adresse:</strong> Conakry, Guinée</p>
                    </div>

                    <div class="invoice-details">
                        <div class="invoice-title">FACTURE</div>
                        <div class="invoice-number"><?php echo esc_html($invoice_number); ?></div>
                        <div class="invoice-date">Date: <?php echo date('d/m/Y'); ?></div>
                        <div class="invoice-date">N° Suivi: <?php echo esc_html($parcel->tracking_number); ?></div>
                    </div>
                </div>

                <!-- Informations Client et Expédition -->
                <div class="client-section">
                    <div class="info-box">
                        <h3>Client</h3>
                        <p><strong><?php echo esc_html($parcel->client_name ?: 'Client sans compte'); ?></strong></p>
                        <?php if ($parcel->client_phone): ?>
                        <p>Tél: <?php echo esc_html($parcel->client_phone); ?></p>
                        <?php endif; ?>
                        <?php if ($parcel->client_email): ?>
                        <p>Email: <?php echo esc_html($parcel->client_email); ?></p>
                        <?php endif; ?>
                        <?php if ($parcel->client_address): ?>
                        <p><?php echo nl2br(esc_html($parcel->client_address)); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="info-box">
                        <h3>Destinataire</h3>
                        <p><strong><?php echo esc_html($parcel->recipient_name); ?></strong></p>
                        <p>Tél: <?php echo esc_html($parcel->recipient_phone); ?></p>
                        <p><?php echo nl2br(esc_html($parcel->recipient_address)); ?></p>
                    </div>
                </div>

                <!-- Détails de l'expédition -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Origine</th>
                            <th>Destination</th>
                            <th>Poids</th>
                            <th>Prix</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>Expédition de Colis</strong><br>
                                N° de suivi: <?php echo esc_html($parcel->tracking_number); ?>
                            </td>
                            <td><?php echo esc_html($parcel->origin_country ?: 'N/A'); ?></td>
                            <td><?php echo esc_html($parcel->destination_country ?: 'N/A'); ?></td>
                            <td><?php echo esc_html($parcel->weight); ?> kg</td>
                            <td><?php echo number_format($parcel->unit_price, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Totaux -->
                <div class="total-section">
                    <div class="total-table">
                        <div class="total-row subtotal">
                            <span>Sous-total:</span>
                            <strong><?php echo number_format($parcel->unit_price, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></strong>
                        </div>

                        <?php if ($parcel->discount_value > 0): ?>
                        <div class="total-row discount">
                            <span>Réduction (<?php echo $parcel->discount_type === 'percentage' ? $parcel->discount_value . '%' : number_format($parcel->discount_value, 0, ',', ' ') . ' ' . $parcel->currency; ?>):</span>
                            <strong>-<?php
                                $discount_amount = $parcel->discount_type === 'percentage'
                                    ? ($parcel->unit_price * $parcel->discount_value / 100)
                                    : $parcel->discount_value;
                                echo number_format($discount_amount, 0, ',', ' ');
                            ?> <?php echo $parcel->currency; ?></strong>
                        </div>
                        <?php endif; ?>

                        <div class="total-row grand-total">
                            <span>TOTAL:</span>
                            <strong><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Informations de paiement -->
                <div class="payment-info">
                    <h3>Informations de Paiement</h3>
                    <p>
                        <strong>Statut:</strong>
                        <span class="payment-status <?php echo sanitize_title($parcel->payment_status); ?>">
                            <?php echo esc_html($parcel->payment_status); ?>
                        </span>
                    </p>
                    <?php if ($parcel->payment_method): ?>
                    <p><strong>Mode de paiement:</strong> <?php echo esc_html($parcel->payment_method); ?></p>
                    <?php endif; ?>
                    <?php if ($parcel->payment_status !== 'Payé'): ?>
                    <p><strong>Montant payé:</strong> <?php echo number_format($parcel->paid_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></p>
                    <p><strong>Montant restant:</strong> <span style="color: #d63638; font-weight: bold;"><?php echo number_format($parcel->remaining_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></span></p>
                    <?php endif; ?>
                </div>

                <!-- Pied de page -->
                <div class="footer">
                    <p><strong><?php echo esc_html($company_name); ?></strong> - Livraison Internationale Express</p>
                    <p>📧 contact@colis224.com | 📞 +224 620 17 89 30 | 🌐 www.colis224.com</p>
                    <p>📍 Conakry, Guinée</p>
                    <p style="margin-top: 15px; font-style: italic;">
                        Merci de votre confiance ! Pour toute question, n'hésitez pas à nous contacter.
                    </p>
                    <p style="margin-top: 10px; font-size: 11px; color: #999;">
                        Document généré automatiquement le <?php echo date('d/m/Y à H:i'); ?>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Générer facture
     */
    public function ajax_generate_invoice() {
        check_ajax_referer('colis224_nonce', 'nonce');

        $parcel_id = intval($_POST['parcel_id']);
        $invoice_html = $this->generate_invoice_html($parcel_id);

        if (!$invoice_html) {
            wp_send_json_error(array('message' => 'Colis introuvable'));
        }

        wp_send_json_success(array(
            'invoice_html' => $invoice_html
        ));
    }

    /**
     * Bouton de génération de facture
     */
    public static function display_invoice_button($parcel_id) {
        ?>
        <button type="button" class="button button-primary" onclick="colis224GenerateInvoice(<?php echo $parcel_id; ?>)">
            <span class="dashicons dashicons-media-spreadsheet"></span> Générer Facture
        </button>

        <script>
        function colis224GenerateInvoice(parcelId) {
            var invoiceWindow = window.open('', '_blank');
            invoiceWindow.document.write('<html><head><title>Chargement...</title></head><body><h3>Génération de la facture...</h3></body></html>');

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'colis224_generate_invoice',
                    nonce: colis224Ajax.nonce,
                    parcel_id: parcelId
                },
                success: function(response) {
                    if (response.success) {
                        invoiceWindow.document.write(response.data.invoice_html);
                        invoiceWindow.document.close();
                    } else {
                        invoiceWindow.close();
                        alert('Erreur lors de la génération de la facture');
                    }
                }
            });
        }
        </script>
        <?php
    }
}

// Initialiser
new Colis224_Invoice();
