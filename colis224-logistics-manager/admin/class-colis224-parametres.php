<?php
/**
 * MODULE 7: Paramètres Généraux
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Settings {

    public static function display_page() {
        if (isset($_POST['action']) && $_POST['action'] === 'save_settings') {
            self::save_settings();
        }

        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-admin-settings"></span>
                Paramètres
            </h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=colis224-settings&tab=general" class="nav-tab <?php echo $tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-generic"></span> Général
                </a>
                <a href="?page=colis224-settings&tab=sms" class="nav-tab <?php echo $tab === 'sms' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-email"></span> SMS
                </a>
                <a href="?page=colis224-settings&tab=payments" class="nav-tab <?php echo $tab === 'payments' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-money-alt"></span> Paiements
                </a>
                <a href="?page=colis224-settings&tab=notifications" class="nav-tab <?php echo $tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-bell"></span> Notifications
                </a>
                <a href="?page=colis224-settings&tab=currencies" class="nav-tab <?php echo $tab === 'currencies' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-money"></span> Devises
                </a>
                <a href="?page=colis224-settings&tab=categories" class="nav-tab <?php echo $tab === 'categories' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-category"></span> Catégories
                </a>
            </nav>

            <?php
            if ($tab === 'general') {
                self::display_general_settings();
            } elseif ($tab === 'sms') {
                self::display_sms_settings();
            } elseif ($tab === 'payments') {
                self::display_payment_settings();
            } elseif ($tab === 'notifications') {
                self::display_notification_settings();
            } elseif ($tab === 'currencies') {
                self::display_currency_settings();
            } else {
                self::display_category_settings();
            }
            ?>
        </div>
        <?php
    }

    private static function display_general_settings() {
        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-admin-generic"></span> Paramètres Généraux</h3>
            <form method="post">
                <input type="hidden" name="action" value="save_settings">
                <?php wp_nonce_field('colis224_settings_action', 'colis224_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="company_name">Nom de l'Entreprise</label></th>
                        <td>
                            <input type="text" id="company_name" name="company_name" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_company_name', 'Colis224')); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th><label for="company_address">Adresse</label></th>
                        <td>
                            <textarea id="company_address" name="company_address" class="large-text" rows="3"><?php echo esc_textarea(get_option('colis224_company_address', '')); ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="company_phone">Téléphone</label></th>
                        <td>
                            <input type="text" id="company_phone" name="company_phone" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_company_phone', '')); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th><label for="company_email">Email</label></th>
                        <td>
                            <input type="email" id="company_email" name="company_email" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_company_email', '')); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th><label for="invoice_prefix">Préfixe Facture</label></th>
                        <td>
                            <input type="text" id="invoice_prefix" name="invoice_prefix"
                                   value="<?php echo esc_attr(get_option('colis224_invoice_prefix', 'INV-')); ?>">
                            <p class="description">Préfixe utilisé pour la numérotation des factures</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="parcel_prefix">Préfixe N° Colis</label></th>
                        <td>
                            <input type="text" id="parcel_prefix" name="parcel_prefix"
                                   value="<?php echo esc_attr(get_option('colis224_parcel_prefix', 'PA')); ?>">
                            <p class="description">Préfixe utilisé pour la numérotation des colis</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="default_currency">Devise par Défaut</label></th>
                        <td>
                            <select id="default_currency" name="default_currency">
                                <option value="GNF" <?php selected(get_option('colis224_default_currency', 'GNF'), 'GNF'); ?>>GNF (Franc Guinéen)</option>
                                <option value="EUR" <?php selected(get_option('colis224_default_currency', 'GNF'), 'EUR'); ?>>EUR (Euro)</option>
                                <option value="USD" <?php selected(get_option('colis224_default_currency', 'GNF'), 'USD'); ?>>USD (Dollar)</option>
                                <option value="XOF" <?php selected(get_option('colis224_default_currency', 'GNF'), 'XOF'); ?>>XOF (Franc CFA)</option>
                                <option value="CNY" <?php selected(get_option('colis224_default_currency', 'GNF'), 'CNY'); ?>>CNY (Yuan Chinois)</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="tax_enabled">Activer la TVA</label></th>
                        <td>
                            <input type="checkbox" id="tax_enabled" name="tax_enabled" value="1"
                                   <?php checked(get_option('colis224_tax_enabled', '0'), '1'); ?>>
                            <label for="tax_enabled">Activer le calcul de la TVA</label>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="tax_rate">Taux de TVA (%)</label></th>
                        <td>
                            <input type="number" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100"
                                   value="<?php echo esc_attr(get_option('colis224_tax_rate', '0')); ?>">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Enregistrer les Paramètres</button>
                </p>
            </form>
        </div>
        <?php
    }

    private static function display_notification_settings() {
        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-email"></span> Paramètres de Notifications</h3>
            <form method="post">
                <input type="hidden" name="action" value="save_settings">
                <?php wp_nonce_field('colis224_settings_action', 'colis224_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="email_notifications">Notifications Email</label></th>
                        <td>
                            <input type="checkbox" id="email_notifications" name="email_notifications" value="1"
                                   <?php checked(get_option('colis224_email_notifications', '1'), '1'); ?>>
                            <label for="email_notifications">Activer les notifications par email</label>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="sms_notifications">Notifications SMS</label></th>
                        <td>
                            <input type="checkbox" id="sms_notifications" name="sms_notifications" value="1"
                                   <?php checked(get_option('colis224_sms_notifications', '0'), '1'); ?>>
                            <label for="sms_notifications">Activer les notifications par SMS</label>
                            <p class="description">Nécessite configuration d'une API SMS</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="sms_api_url">URL API SMS</label></th>
                        <td>
                            <input type="url" id="sms_api_url" name="sms_api_url" class="large-text"
                                   value="<?php echo esc_attr(get_option('colis224_sms_api_url', '')); ?>">
                        </td>
                    </tr>

                    <tr>
                        <th><label for="sms_api_key">Clé API SMS</label></th>
                        <td>
                            <input type="text" id="sms_api_key" name="sms_api_key" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_sms_api_key', '')); ?>">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Enregistrer les Paramètres</button>
                </p>
            </form>
        </div>
        <?php
    }

    private static function display_currency_settings() {
        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-money-alt"></span> Taux de Change</h3>
            <form method="post">
                <input type="hidden" name="action" value="save_settings">
                <?php wp_nonce_field('colis224_settings_action', 'colis224_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th colspan="2">
                            <p class="description">Configurez les taux de change par rapport au GNF (Franc Guinéen)</p>
                        </th>
                    </tr>

                    <tr>
                        <th><label for="exchange_rate_eur">1 EUR = ? GNF</label></th>
                        <td>
                            <input type="number" id="exchange_rate_eur" name="exchange_rate_eur" step="0.01" min="0"
                                   value="<?php echo esc_attr(get_option('colis224_exchange_rate_eur', '11000')); ?>">
                            <p class="description">Taux de change Euro vers Franc Guinéen</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="exchange_rate_usd">1 USD = ? GNF</label></th>
                        <td>
                            <input type="number" id="exchange_rate_usd" name="exchange_rate_usd" step="0.01" min="0"
                                   value="<?php echo esc_attr(get_option('colis224_exchange_rate_usd', '10000')); ?>">
                            <p class="description">Taux de change Dollar vers Franc Guinéen</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="exchange_rate_xof">1 XOF (CFA) = ? GNF</label></th>
                        <td>
                            <input type="number" id="exchange_rate_xof" name="exchange_rate_xof" step="0.01" min="0"
                                   value="<?php echo esc_attr(get_option('colis224_exchange_rate_xof', '16')); ?>">
                            <p class="description">Taux de change Franc CFA vers Franc Guinéen</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="exchange_rate_cny">1 CNY (Yuan) = ? GNF</label></th>
                        <td>
                            <input type="number" id="exchange_rate_cny" name="exchange_rate_cny" step="0.01" min="0"
                                   value="<?php echo esc_attr(get_option('colis224_exchange_rate_cny', '1400')); ?>">
                            <p class="description">Taux de change Yuan Chinois vers Franc Guinéen</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Enregistrer les Taux</button>
                </p>
            </form>
        </div>
        <?php
    }

    private static function display_category_settings() {
        global $wpdb;

        if (isset($_POST['action']) && $_POST['action'] === 'add_category') {
            self::add_category();
        }

        $categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}colis224_parcel_categories ORDER BY name");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-plus-alt"></span> Ajouter une Catégorie de Colis</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_category">
                <?php wp_nonce_field('colis224_category_action', 'colis224_category_nonce'); ?>

                <div class="colis224-form-grid">
                    <input type="text" name="category_name" placeholder="Nom de la catégorie" required>
                    <input type="text" name="category_description" placeholder="Description">
                    <button type="submit" class="button button-primary">Ajouter</button>
                </div>
            </form>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-list-view"></span> Catégories de Colis</h3>
            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Date de création</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">Aucune catégorie.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><strong><?php echo esc_html($cat->name); ?></strong></td>
                            <td><?php echo esc_html($cat->description ?: '-'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cat->created_at)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function display_sms_settings() {
        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-email"></span> Configuration SMS</h3>
            <p>Configurez votre fournisseur SMS pour envoyer des notifications automatiques aux clients.</p>

            <form method="post">
                <input type="hidden" name="action" value="save_settings">
                <?php wp_nonce_field('colis224_settings_action', 'colis224_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="sms_provider">Fournisseur SMS</label></th>
                        <td>
                            <select id="sms_provider" name="sms_provider" class="regular-text">
                                <option value="none" <?php selected(get_option('colis224_sms_provider', 'none'), 'none'); ?>>Aucun (Désactivé)</option>
                                <option value="nimbasms" <?php selected(get_option('colis224_sms_provider'), 'nimbasms'); ?>>NimbaSMS (Recommandé pour Guinée)</option>
                                <option value="orange" <?php selected(get_option('colis224_sms_provider'), 'orange'); ?>>Orange SMS API (Guinée)</option>
                                <option value="africas_talking" <?php selected(get_option('colis224_sms_provider'), 'africas_talking'); ?>>Africa's Talking</option>
                                <option value="twilio" <?php selected(get_option('colis224_sms_provider'), 'twilio'); ?>>Twilio</option>
                                <option value="custom" <?php selected(get_option('colis224_sms_provider'), 'custom'); ?>>API Personnalisée</option>
                            </select>
                            <p class="description">Configurez votre fournisseur SMS pour envoyer des notifications automatiques aux clients.</p>
                        </td>
                    </tr>
                </table>

                <h4 style="margin-top: 30px;">🍊 Configuration Orange SMS (Guinée)</h4>
                <table class="form-table">
                    <tr>
                        <th><label for="orange_sms_client_id">Client ID</label></th>
                        <td>
                            <input type="text" id="orange_sms_client_id" name="orange_sms_client_id" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_orange_sms_client_id', '')); ?>">
                            <p class="description">Votre Client ID Orange API</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="orange_sms_client_secret">Client Secret</label></th>
                        <td>
                            <input type="password" id="orange_sms_client_secret" name="orange_sms_client_secret" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_orange_sms_client_secret', '')); ?>">
                            <p class="description">Votre Client Secret Orange API</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="orange_sms_sender">Nom Expéditeur</label></th>
                        <td>
                            <input type="text" id="orange_sms_sender" name="orange_sms_sender" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_orange_sms_sender', 'Colis224')); ?>">
                            <p class="description">Nom qui apparaîtra comme expéditeur (max 11 caractères)</p>
                        </td>
                    </tr>
                </table>

                <h4 style="margin-top: 30px;">📱 NimbaSMS (Guinée)</h4>
                <table class="form-table">
                    <tr>
                        <th><label for="nimbasms_sid">Service ID (SID)</label></th>
                        <td>
                            <input type="text" id="nimbasms_sid" name="nimbasms_sid" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_nimbasms_sid', '48782ece605fcd66fc15da242cc0142c')); ?>">
                            <p class="description">Votre Service ID NimbaSMS</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nimbasms_token">Authorization Token</label></th>
                        <td>
                            <input type="password" id="nimbasms_token" name="nimbasms_token" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_nimbasms_token', 'Basic NDg3ODJlY2U2MDVmY2Q2NmZjMTVkYTI0MmNjMDE0MmM6elhMZGIySU4xVVh2eE5KZWdyakJkX0VGMlE2XzRtemFGM2FFTlE1NW94ZmYyS0lWX1lMNi1KdnE1TUNaRGV0Vm9wc1JVaXYyNkdIUkhpYWVPbmdMU2xnQnNPOS1YNXE0dWkxS1BEUFFOMjQ=')); ?>">
                            <p class="description">Token d'autorisation (format: Basic ...)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nimbasms_from">Nom Expéditeur</label></th>
                        <td>
                            <input type="text" id="nimbasms_from" name="nimbasms_from" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_nimbasms_from', 'Colis224')); ?>"
                                   maxlength="11">
                            <p class="description">Nom qui apparaîtra comme expéditeur (max 11 caractères)</p>
                        </td>
                    </tr>
                </table>

                <h4 style="margin-top: 30px;">🌍 Africa's Talking</h4>
                <table class="form-table">
                    <tr>
                        <th><label for="africastalking_username">Username</label></th>
                        <td>
                            <input type="text" id="africastalking_username" name="africastalking_username" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_africastalking_username', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="africastalking_api_key">API Key</label></th>
                        <td>
                            <input type="password" id="africastalking_api_key" name="africastalking_api_key" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_africastalking_api_key', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="africastalking_from">Sender ID</label></th>
                        <td>
                            <input type="text" id="africastalking_from" name="africastalking_from" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_africastalking_from', 'Colis224')); ?>">
                        </td>
                    </tr>
                </table>

                <h4 style="margin-top: 30px;">📱 Twilio</h4>
                <table class="form-table">
                    <tr>
                        <th><label for="twilio_account_sid">Account SID</label></th>
                        <td>
                            <input type="text" id="twilio_account_sid" name="twilio_account_sid" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_twilio_account_sid', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="twilio_auth_token">Auth Token</label></th>
                        <td>
                            <input type="password" id="twilio_auth_token" name="twilio_auth_token" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_twilio_auth_token', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="twilio_from_number">From Number</label></th>
                        <td>
                            <input type="text" id="twilio_from_number" name="twilio_from_number" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_twilio_from_number', '')); ?>"
                                   placeholder="+1234567890">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-yes"></span> Enregistrer les paramètres SMS
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    private static function display_payment_settings() {
        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3><span class="dashicons dashicons-money-alt"></span> Configuration Paiements Mobile Money</h3>
            <p>Configurez vos moyens de paiement Mobile Money pour accepter les paiements en ligne.</p>

            <form method="post">
                <input type="hidden" name="action" value="save_settings">
                <?php wp_nonce_field('colis224_settings_action', 'colis224_settings_nonce'); ?>

                <h4>🍊 Orange Money (Guinée, Sénégal, Côte d'Ivoire)</h4>
                <table class="form-table">
                    <tr>
                        <th><label for="orange_money_enabled">Activer Orange Money</label></th>
                        <td>
                            <input type="checkbox" id="orange_money_enabled" name="orange_money_enabled" value="1"
                                   <?php checked(get_option('colis224_orange_money_enabled'), '1'); ?>>
                            <label for="orange_money_enabled">Activer les paiements Orange Money</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="orange_money_merchant_id">Merchant ID</label></th>
                        <td>
                            <input type="text" id="orange_money_merchant_id" name="orange_money_merchant_id" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_orange_money_merchant_id', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="orange_money_api_key">API Key</label></th>
                        <td>
                            <input type="password" id="orange_money_api_key" name="orange_money_api_key" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_orange_money_api_key', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="orange_money_api_secret">API Secret</label></th>
                        <td>
                            <input type="password" id="orange_money_api_secret" name="orange_money_api_secret" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_orange_money_api_secret', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="orange_money_environment">Environnement</label></th>
                        <td>
                            <select id="orange_money_environment" name="orange_money_environment">
                                <option value="sandbox" <?php selected(get_option('colis224_orange_money_environment', 'sandbox'), 'sandbox'); ?>>Sandbox (Test)</option>
                                <option value="production" <?php selected(get_option('colis224_orange_money_environment'), 'production'); ?>>Production</option>
                            </select>
                            <p class="description">Utilisez Sandbox pour les tests</p>
                        </td>
                    </tr>
                </table>

                <h4 style="margin-top: 30px;">📱 MTN Money (MTN MoMo)</h4>
                <table class="form-table">
                    <tr>
                        <th><label for="mtn_money_enabled">Activer MTN Money</label></th>
                        <td>
                            <input type="checkbox" id="mtn_money_enabled" name="mtn_money_enabled" value="1"
                                   <?php checked(get_option('colis224_mtn_money_enabled'), '1'); ?>>
                            <label for="mtn_money_enabled">Activer les paiements MTN Money</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mtn_money_api_user">API User</label></th>
                        <td>
                            <input type="text" id="mtn_money_api_user" name="mtn_money_api_user" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_mtn_money_api_user', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mtn_money_api_key">API Key</label></th>
                        <td>
                            <input type="password" id="mtn_money_api_key" name="mtn_money_api_key" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_mtn_money_api_key', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mtn_money_subscription_key">Subscription Key</label></th>
                        <td>
                            <input type="password" id="mtn_money_subscription_key" name="mtn_money_subscription_key" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_mtn_money_subscription_key', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mtn_money_environment">Environnement</label></th>
                        <td>
                            <select id="mtn_money_environment" name="mtn_money_environment">
                                <option value="sandbox" <?php selected(get_option('colis224_mtn_money_environment', 'sandbox'), 'sandbox'); ?>>Sandbox</option>
                                <option value="production" <?php selected(get_option('colis224_mtn_money_environment'), 'production'); ?>>Production</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h4 style="margin-top: 30px;">💚 Moov Money (Côte d'Ivoire)</h4>
                <table class="form-table">
                    <tr>
                        <th><label for="moov_money_enabled">Activer Moov Money</label></th>
                        <td>
                            <input type="checkbox" id="moov_money_enabled" name="moov_money_enabled" value="1"
                                   <?php checked(get_option('colis224_moov_money_enabled'), '1'); ?>>
                            <label for="moov_money_enabled">Activer les paiements Moov Money</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="moov_money_merchant_id">Merchant ID</label></th>
                        <td>
                            <input type="text" id="moov_money_merchant_id" name="moov_money_merchant_id" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_moov_money_merchant_id', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="moov_money_api_key">API Key</label></th>
                        <td>
                            <input type="password" id="moov_money_api_key" name="moov_money_api_key" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_moov_money_api_key', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="moov_money_environment">Environnement</label></th>
                        <td>
                            <select id="moov_money_environment" name="moov_money_environment">
                                <option value="sandbox" <?php selected(get_option('colis224_moov_money_environment', 'sandbox'), 'sandbox'); ?>>Sandbox</option>
                                <option value="production" <?php selected(get_option('colis224_moov_money_environment'), 'production'); ?>>Production</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h4 style="margin-top: 30px;">💳 PayPal</h4>
                <table class="form-table">
                    <tr>
                        <th><label for="paypal_enabled">Activer PayPal</label></th>
                        <td>
                            <input type="checkbox" id="paypal_enabled" name="paypal_enabled" value="1"
                                   <?php checked(get_option('colis224_paypal_enabled'), '1'); ?>>
                            <label for="paypal_enabled">Activer les paiements PayPal</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="paypal_client_id">Client ID</label></th>
                        <td>
                            <input type="text" id="paypal_client_id" name="paypal_client_id" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_paypal_client_id', '')); ?>">
                            <p class="description">Obtenez vos identifiants depuis <a href="https://developer.paypal.com/" target="_blank">PayPal Developer</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="paypal_client_secret">Client Secret</label></th>
                        <td>
                            <input type="password" id="paypal_client_secret" name="paypal_client_secret" class="regular-text"
                                   value="<?php echo esc_attr(get_option('colis224_paypal_client_secret', '')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="paypal_mode">Mode</label></th>
                        <td>
                            <select id="paypal_mode" name="paypal_mode">
                                <option value="sandbox" <?php selected(get_option('colis224_paypal_mode', 'sandbox'), 'sandbox'); ?>>Sandbox (Test)</option>
                                <option value="live" <?php selected(get_option('colis224_paypal_mode'), 'live'); ?>>Live (Production)</option>
                            </select>
                            <p class="description">Utilisez Sandbox pour les tests, Live pour les paiements réels</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-yes"></span> Enregistrer les paramètres de paiement
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    private static function save_settings() {
        if (!isset($_POST['colis224_settings_nonce']) || !wp_verify_nonce($_POST['colis224_settings_nonce'], 'colis224_settings_action')) {
            wp_die('Erreur de sécurité');
        }

        $settings = array(
            // Paramètres généraux
            'company_name', 'company_address', 'company_phone', 'company_email',
            'invoice_prefix', 'parcel_prefix', 'default_currency',
            'tax_enabled', 'tax_rate',

            // Notifications
            'email_notifications', 'sms_notifications', 'sms_api_url', 'sms_api_key',

            // Devises
            'exchange_rate_eur', 'exchange_rate_usd', 'exchange_rate_xof', 'exchange_rate_cny',

            // Configuration SMS
            'sms_provider',
            'nimbasms_sid', 'nimbasms_token', 'nimbasms_from',
            'orange_sms_client_id', 'orange_sms_client_secret', 'orange_sms_sender',
            'africastalking_username', 'africastalking_api_key', 'africastalking_from',
            'twilio_account_sid', 'twilio_auth_token', 'twilio_from_number',

            // Configuration Paiements Mobile Money
            'orange_money_enabled', 'orange_money_merchant_id', 'orange_money_api_key',
            'orange_money_api_secret', 'orange_money_environment',
            'mtn_money_enabled', 'mtn_money_api_user', 'mtn_money_api_key',
            'mtn_money_subscription_key', 'mtn_money_environment',
            'moov_money_enabled', 'moov_money_merchant_id', 'moov_money_api_key',
            'moov_money_environment',

            // Configuration PayPal
            'paypal_enabled', 'paypal_client_id', 'paypal_client_secret', 'paypal_mode'
        );

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option('colis224_' . $setting, sanitize_text_field($_POST[$setting]));
            } else {
                // Pour les checkboxes non cochées
                if (in_array($setting, array('tax_enabled', 'email_notifications', 'sms_notifications',
                                              'orange_money_enabled', 'mtn_money_enabled', 'moov_money_enabled',
                                              'paypal_enabled'))) {
                    update_option('colis224_' . $setting, '0');
                }
            }
        }

        echo '<div class="notice notice-success is-dismissible"><p>Paramètres enregistrés avec succès!</p></div>';
    }

    private static function add_category() {
        if (!isset($_POST['colis224_category_nonce']) || !wp_verify_nonce($_POST['colis224_category_nonce'], 'colis224_category_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_parcel_categories';

        $wpdb->insert($table, array(
            'name' => sanitize_text_field($_POST['category_name']),
            'description' => sanitize_text_field($_POST['category_description'])
        ));

        echo '<div class="notice notice-success is-dismissible"><p>Catégorie ajoutée avec succès!</p></div>';
    }
}
