<?php
/**
 * MODULE 1: Gestion des Colis
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Parcels {

    public static function display_page() {
        global $wpdb;

        // Traitement des actions
        if (isset($_POST['action']) && $_POST['action'] === 'add_parcel') {
            self::save_parcel();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_parcel') {
            self::update_parcel();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            self::delete_parcel($_GET['id']);
        }

        // Déterminer la vue à afficher
        if (isset($_GET['action']) && $_GET['action'] === 'add') {
            self::display_form();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            self::display_form($_GET['id']);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
            self::display_details($_GET['id']);
        } else {
            self::display_list();
        }
    }

    /**
     * Afficher la liste des colis
     */
    private static function display_list() {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $table_countries = $wpdb->prefix . 'colis224_countries';
        $table_transport = $wpdb->prefix . 'colis224_transport_modes';

        // Filtres
        $where = "1=1";
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $payment_filter = isset($_GET['payment']) ? sanitize_text_field($_GET['payment']) : '';

        // RESTRICTION AGENTS : Masquer les colis rejetés (v2.11.0)
        $current_user = wp_get_current_user();
        $user_role = 'admin'; // Default
        if (class_exists('Colis224_Permissions')) {
            $user_role = Colis224_Permissions::get_user_colis224_role($current_user->ID);
            if (!$user_role) {
                $user_role = 'admin';
            }
        }

        // Les agents ne voient pas les colis rejetés
        if ($user_role === 'colis224_agent') {
            $where .= " AND p.validation_status != 'rejected'";
        }

        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (p.tracking_number LIKE %s OR p.recipient_name LIKE %s OR p.recipient_phone LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        if (!empty($status_filter)) {
            $where .= $wpdb->prepare(" AND p.status = %s", $status_filter);
        }

        if (!empty($payment_filter)) {
            $where .= $wpdb->prepare(" AND p.payment_status = %s", $payment_filter);
        }

        // Récupération des colis
        $parcels = $wpdb->get_results("
            SELECT p.*, c.name as client_name, co.name as origin_country, t.name as transport_mode
            FROM $table_parcels p
            LEFT JOIN $table_clients c ON p.client_id = c.id
            LEFT JOIN $table_countries co ON p.origin_country_id = co.id
            LEFT JOIN $table_transport t ON p.transport_mode_id = t.id
            WHERE $where
            ORDER BY p.created_at DESC
        ");

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-archive"></span>
                Gestion des Colis
                <a href="?page=colis224-parcels&action=add" class="page-title-action">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Nouveau Colis
                </a>
            </h1>

            <!-- Filtres -->
            <div class="colis224-filters">
                <form method="get">
                    <input type="hidden" name="page" value="colis224-parcels">

                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>"
                           placeholder="Rechercher (n° suivi, nom, téléphone)..."
                           class="colis224-search-input">

                    <select name="status">
                        <option value="">Tous les statuts</option>
                        <option value="En attente" <?php selected($status_filter, 'En attente'); ?>>En attente</option>
                        <option value="Expédié" <?php selected($status_filter, 'Expédié'); ?>>Expédié</option>
                        <option value="En transit" <?php selected($status_filter, 'En transit'); ?>>En transit</option>
                        <option value="Livré" <?php selected($status_filter, 'Livré'); ?>>Livré</option>
                        <option value="Retour" <?php selected($status_filter, 'Retour'); ?>>Retour</option>
                    </select>

                    <select name="payment">
                        <option value="">Tous les paiements</option>
                        <option value="Payé" <?php selected($payment_filter, 'Payé'); ?>>Payé</option>
                        <option value="Partiel" <?php selected($payment_filter, 'Partiel'); ?>>Partiel</option>
                        <option value="Non payé" <?php selected($payment_filter, 'Non payé'); ?>>Non payé</option>
                    </select>

                    <button type="submit" class="button">Filtrer</button>
                    <a href="?page=colis224-parcels" class="button">Réinitialiser</a>
                </form>
            </div>

            <!-- Liste des colis -->
            <div class="colis224-card">
                <table class="colis224-table">
                    <thead>
                        <tr>
                            <th>N° Suivi</th>
                            <th>Client</th>
                            <th>Destinataire</th>
                            <th>Origine</th>
                            <th>Transport</th>
                            <th>Statut</th>
                            <th>Montant</th>
                            <th>Paiement</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($parcels)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center;">Aucun colis trouvé.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($parcels as $parcel): ?>
                            <tr>
                                <td><strong><?php echo esc_html($parcel->tracking_number); ?></strong></td>
                                <td><?php echo esc_html($parcel->client_name ?: '-'); ?></td>
                                <td>
                                    <?php echo esc_html($parcel->recipient_name); ?><br>
                                    <small><?php echo esc_html($parcel->recipient_phone); ?></small>
                                </td>
                                <td><?php echo esc_html($parcel->origin_country ?: '-'); ?></td>
                                <td><?php echo esc_html($parcel->transport_mode ?: '-'); ?></td>
                                <td>
                                    <span class="colis224-badge colis224-badge-<?php echo sanitize_title($parcel->status); ?>">
                                        <?php echo esc_html($parcel->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?>
                                </td>
                                <td>
                                    <span class="colis224-badge colis224-badge-payment-<?php echo sanitize_title($parcel->payment_status); ?>">
                                        <?php echo esc_html($parcel->payment_status); ?>
                                    </span>
                                    <?php if ($parcel->payment_status === 'Partiel'): ?>
                                    <br><small>Payé: <?php echo number_format($parcel->paid_amount, 0, ',', ' '); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($parcel->created_at)); ?></td>
                                <td class="colis224-actions">
                                    <a href="?page=colis224-parcels&action=view&id=<?php echo $parcel->id; ?>"
                                       class="button button-small" title="Voir les détails">
                                        <span class="dashicons dashicons-visibility"></span>
                                        Voir
                                    </a>
                                    <a href="?page=colis224-parcels&action=edit&id=<?php echo $parcel->id; ?>"
                                       class="button button-small" title="Modifier ce colis">
                                        <span class="dashicons dashicons-edit"></span>
                                        Modifier
                                    </a>
                                    <a href="?page=colis224-parcels&action=delete&id=<?php echo $parcel->id; ?>"
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce colis ?');"
                                       title="Supprimer ce colis">
                                        <span class="dashicons dashicons-trash"></span>
                                        Supprimer
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Afficher le formulaire d'ajout/modification
     */
    private static function display_form($parcel_id = null) {
        global $wpdb;

        $parcel = null;
        if ($parcel_id) {
            $table_parcels = $wpdb->prefix . 'colis224_parcels';
            $parcel = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_parcels WHERE id = %d", $parcel_id));
        }

        // Récupération des données pour les listes déroulantes
        $clients = $wpdb->get_results("SELECT id, name, phone FROM {$wpdb->prefix}colis224_clients ORDER BY name");
        $countries = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}colis224_countries ORDER BY name");
        $transport_modes = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}colis224_transport_modes ORDER BY name");
        $categories = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}colis224_parcel_categories ORDER BY name");
        $drivers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}colis224_drivers WHERE is_active = 1 ORDER BY name");

        $is_edit = ($parcel !== null);
        $title = $is_edit ? 'Modifier le Colis' : 'Nouveau Colis';
        $action = $is_edit ? 'edit_parcel' : 'add_parcel';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-archive"></span>
                <?php echo $title; ?>
                <a href="?page=colis224-parcels" class="page-title-action">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Retour à la liste
                </a>
            </h1>

            <div class="colis224-card">
                <form method="post" id="colis224-parcel-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="parcel_id" value="<?php echo $parcel->id; ?>">
                    <?php endif; ?>
                    <?php wp_nonce_field('colis224_parcel_action', 'colis224_parcel_nonce'); ?>

                    <div class="colis224-form-grid">
                        <!-- Numéro de suivi -->
                        <div class="colis224-form-group">
                            <label for="tracking_number">Numéro de Suivi *</label>
                            <input type="text" name="tracking_number" id="tracking_number"
                                   value="<?php echo $is_edit ? esc_attr($parcel->tracking_number) : ''; ?>"
                                   placeholder="PA + 4 derniers chiffres téléphone"
                                   <?php echo !$is_edit ? '' : 'readonly'; ?> required>
                            <?php if (!$is_edit): ?>
                            <small>Laissez vide pour génération automatique ou saisissez manuellement</small>
                            <?php endif; ?>
                        </div>

                        <!-- Client avec autocomplete -->
                        <div class="colis224-form-group">
                            <label for="client_search">Client (recherche par nom ou téléphone)</label>
                            <input type="text" id="client_search" class="colis224-autocomplete-input"
                                   placeholder="Tapez un nom ou numéro de téléphone..."
                                   value="<?php
                                   if ($is_edit && $parcel->client_id) {
                                       $client = $wpdb->get_row($wpdb->prepare("SELECT name, phone FROM {$wpdb->prefix}colis224_clients WHERE id = %d", $parcel->client_id));
                                       if ($client) echo esc_attr($client->name . ' - ' . $client->phone);
                                   }
                                   ?>">
                            <input type="hidden" name="client_id" id="client_id" value="<?php echo $is_edit ? esc_attr($parcel->client_id) : ''; ?>">
                            <small>Commencez à taper pour rechercher un client existant</small>
                        </div>
                    </div>

                    <!-- Widget Fidélité Client -->
                    <div id="loyalty-widget" class="colis224-card colis224-card-purple" style="display: none; margin: 20px 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <h3 style="margin: 0; color: #fff;">
                                    <span class="dashicons dashicons-awards"></span> Programme de Fidélité
                                </h3>
                            </div>
                            <div style="text-align: right;">
                                <div id="loyalty-tier" style="font-size: 24px; font-weight: bold; color: #fff;"></div>
                            </div>
                        </div>
                        <div style="margin-top: 15px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                            <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px;">
                                <div style="font-size: 13px; opacity: 0.9; color: #fff;">Points Disponibles</div>
                                <div id="loyalty-available" style="font-size: 28px; font-weight: bold; margin: 5px 0; color: #fff;">0</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px;">
                                <div style="font-size: 13px; opacity: 0.9; color: #fff;">Points Totaux</div>
                                <div id="loyalty-total" style="font-size: 28px; font-weight: bold; margin: 5px 0; color: #fff;">0</div>
                            </div>
                            <div style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 8px;">
                                <div style="font-size: 13px; opacity: 0.9; color: #fff;">Ce Colis Rapportera</div>
                                <div id="loyalty-earn" style="font-size: 28px; font-weight: bold; margin: 5px 0; color: #fff;">
                                    <span class="dashicons dashicons-plus-alt" style="font-size: 20px; vertical-align: middle;"></span> 10 pts
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="colis224-form-grid">

                        <!-- Nom expéditeur avec autocomplete -->
                        <div class="colis224-form-group">
                            <label for="sender_name">Nom Expéditeur</label>
                            <input type="text" name="sender_name" id="sender_name" class="colis224-autocomplete-sender"
                                   placeholder="Tapez pour rechercher dans l'historique..."
                                   value="<?php echo $is_edit ? esc_attr($parcel->sender_name) : ''; ?>">
                            <small>Suggestions basées sur l'historique des expéditions</small>
                        </div>

                        <!-- Nom destinataire avec autocomplete -->
                        <div class="colis224-form-group">
                            <label for="recipient_name">Nom Destinataire *</label>
                            <input type="text" name="recipient_name" id="recipient_name" class="colis224-autocomplete-recipient"
                                   placeholder="Tapez pour rechercher dans l'historique..."
                                   value="<?php echo $is_edit ? esc_attr($parcel->recipient_name) : ''; ?>" required>
                            <small>Suggestions basées sur l'historique des livraisons</small>
                        </div>

                        <!-- Téléphone destinataire -->
                        <div class="colis224-form-group">
                            <label for="recipient_phone">Téléphone Destinataire *</label>
                            <input type="text" name="recipient_phone" id="recipient_phone"
                                   value="<?php echo $is_edit ? esc_attr($parcel->recipient_phone) : ''; ?>" required>
                        </div>

                        <!-- Adresse destinataire -->
                        <div class="colis224-form-group colis224-full-width">
                            <label for="recipient_address">Adresse Destinataire *</label>
                            <textarea name="recipient_address" id="recipient_address" rows="2" required><?php echo $is_edit ? esc_textarea($parcel->recipient_address) : ''; ?></textarea>
                        </div>

                        <!-- Pays d'origine -->
                        <div class="colis224-form-group">
                            <label for="origin_country_id">Pays d'Origine</label>
                            <select name="origin_country_id" id="origin_country_id">
                                <option value="">Sélectionner</option>
                                <?php foreach ($countries as $country): ?>
                                <option value="<?php echo $country->id; ?>"
                                        <?php echo $is_edit && $parcel->origin_country_id == $country->id ? 'selected' : ''; ?>>
                                    <?php echo esc_html($country->name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Pays de destination -->
                        <div class="colis224-form-group">
                            <label for="destination_country_id">Pays de Destination</label>
                            <select name="destination_country_id" id="destination_country_id">
                                <option value="">Sélectionner</option>
                                <?php foreach ($countries as $country): ?>
                                <option value="<?php echo $country->id; ?>"
                                        <?php echo $is_edit && $parcel->destination_country_id == $country->id ? 'selected' : ''; ?>>
                                    <?php echo esc_html($country->name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Mode de transport -->
                        <div class="colis224-form-group">
                            <label for="transport_mode_id">Mode de Transport</label>
                            <select name="transport_mode_id" id="transport_mode_id">
                                <option value="">Sélectionner</option>
                                <?php foreach ($transport_modes as $mode): ?>
                                <option value="<?php echo $mode->id; ?>"
                                        <?php echo $is_edit && $parcel->transport_mode_id == $mode->id ? 'selected' : ''; ?>>
                                    <?php echo esc_html($mode->name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Catégorie -->
                        <div class="colis224-form-group">
                            <label for="category_id">Catégorie</label>
                            <select name="category_id" id="category_id">
                                <option value="">Sélectionner</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category->id; ?>"
                                        <?php echo $is_edit && $parcel->category_id == $category->id ? 'selected' : ''; ?>>
                                    <?php echo esc_html($category->name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Poids -->
                        <div class="colis224-form-group">
                            <label for="weight">Poids (kg)</label>
                            <input type="number" name="weight" id="weight" step="0.01" min="0"
                                   value="<?php echo $is_edit ? esc_attr($parcel->weight) : ''; ?>">
                        </div>

                        <!-- Prix unitaire -->
                        <div class="colis224-form-group">
                            <label for="unit_price">Tarif Unitaire *</label>
                            <input type="number" name="unit_price" id="unit_price" step="0.01" min="0"
                                   value="<?php echo $is_edit ? esc_attr($parcel->unit_price) : ''; ?>" required>
                        </div>

                        <!-- Type de réduction -->
                        <div class="colis224-form-group">
                            <label for="discount_type">Type de Réduction</label>
                            <select name="discount_type" id="discount_type">
                                <option value="percentage" <?php echo $is_edit && $parcel->discount_type == 'percentage' ? 'selected' : ''; ?>>Pourcentage (%)</option>
                                <option value="fixed" <?php echo $is_edit && $parcel->discount_type == 'fixed' ? 'selected' : ''; ?>>Montant Fixe</option>
                            </select>
                        </div>

                        <!-- Valeur de réduction -->
                        <div class="colis224-form-group">
                            <label for="discount_value">Valeur de Réduction</label>
                            <input type="number" name="discount_value" id="discount_value" step="0.01" min="0"
                                   value="<?php echo $is_edit ? esc_attr($parcel->discount_value) : '0'; ?>">
                        </div>

                        <!-- Montant total (calculé automatiquement) -->
                        <div class="colis224-form-group">
                            <label for="total_amount">Montant Total *</label>
                            <input type="number" name="total_amount" id="total_amount" step="0.01" min="0"
                                   value="<?php echo $is_edit ? esc_attr($parcel->total_amount) : ''; ?>" required readonly>
                        </div>

                        <!-- Devise -->
                        <div class="colis224-form-group">
                            <label for="currency">Devise</label>
                            <select name="currency" id="currency">
                                <option value="GNF" <?php echo $is_edit && $parcel->currency == 'GNF' ? 'selected' : ''; ?>>GNF (Franc Guinéen)</option>
                                <option value="EUR" <?php echo $is_edit && $parcel->currency == 'EUR' ? 'selected' : ''; ?>>EUR (Euro)</option>
                                <option value="USD" <?php echo $is_edit && $parcel->currency == 'USD' ? 'selected' : ''; ?>>USD (Dollar)</option>
                                <option value="XOF" <?php echo $is_edit && $parcel->currency == 'XOF' ? 'selected' : ''; ?>>XOF (Franc CFA)</option>
                                <option value="CNY" <?php echo $is_edit && $parcel->currency == 'CNY' ? 'selected' : ''; ?>>CNY (Yuan Chinois)</option>
                            </select>
                        </div>

                        <!-- Date de réception -->
                        <div class="colis224-form-group">
                            <label for="reception_date">Date de Réception</label>
                            <input type="date" name="reception_date" id="reception_date"
                                   value="<?php echo $is_edit ? esc_attr($parcel->reception_date) : ''; ?>">
                        </div>

                        <!-- Date d'expédition -->
                        <div class="colis224-form-group">
                            <label for="shipping_date">Date d'Expédition</label>
                            <input type="date" name="shipping_date" id="shipping_date"
                                   value="<?php echo $is_edit ? esc_attr($parcel->shipping_date) : ''; ?>">
                        </div>

                        <!-- Date de livraison estimée -->
                        <div class="colis224-form-group">
                            <label for="estimated_delivery_date">Date de Livraison Estimée</label>
                            <input type="date" name="estimated_delivery_date" id="estimated_delivery_date"
                                   value="<?php echo $is_edit ? esc_attr($parcel->estimated_delivery_date) : ''; ?>">
                        </div>

                        <!-- Date de livraison réelle -->
                        <div class="colis224-form-group">
                            <label for="delivery_date">Date de Livraison Réelle</label>
                            <input type="date" name="delivery_date" id="delivery_date"
                                   value="<?php echo $is_edit ? esc_attr($parcel->delivery_date) : ''; ?>">
                        </div>

                        <!-- Statut -->
                        <div class="colis224-form-group">
                            <label for="status">Statut *</label>
                            <select name="status" id="status" required>
                                <option value="En attente" <?php echo $is_edit && $parcel->status == 'En attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="Expédié" <?php echo $is_edit && $parcel->status == 'Expédié' ? 'selected' : ''; ?>>Expédié</option>
                                <option value="En transit" <?php echo $is_edit && $parcel->status == 'En transit' ? 'selected' : ''; ?>>En transit</option>
                                <option value="Livré" <?php echo $is_edit && $parcel->status == 'Livré' ? 'selected' : ''; ?>>Livré</option>
                                <option value="Retour" <?php echo $is_edit && $parcel->status == 'Retour' ? 'selected' : ''; ?>>Retour</option>
                            </select>
                        </div>

                        <!-- Livreur -->
                        <div class="colis224-form-group">
                            <label for="driver_id">Livreur Assigné</label>
                            <select name="driver_id" id="driver_id">
                                <option value="">Aucun</option>
                                <?php foreach ($drivers as $driver): ?>
                                <option value="<?php echo $driver->id; ?>"
                                        <?php echo $is_edit && $parcel->driver_id == $driver->id ? 'selected' : ''; ?>>
                                    <?php echo esc_html($driver->name); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Mode de paiement -->
                        <div class="colis224-form-group">
                            <label for="payment_method">Mode de Paiement</label>
                            <select name="payment_method" id="payment_method">
                                <option value="">Sélectionner</option>
                                <option value="Espèces" <?php echo $is_edit && $parcel->payment_method == 'Espèces' ? 'selected' : ''; ?>>Espèces</option>
                                <option value="Virement" <?php echo $is_edit && $parcel->payment_method == 'Virement' ? 'selected' : ''; ?>>Virement</option>
                                <option value="Mobile Money" <?php echo $is_edit && $parcel->payment_method == 'Mobile Money' ? 'selected' : ''; ?>>Mobile Money</option>
                                <option value="PayPal" <?php echo $is_edit && $parcel->payment_method == 'PayPal' ? 'selected' : ''; ?>>PayPal</option>
                                <option value="Ria" <?php echo $is_edit && $parcel->payment_method == 'Ria' ? 'selected' : ''; ?>>Ria</option>
                                <option value="Western Union" <?php echo $is_edit && $parcel->payment_method == 'Western Union' ? 'selected' : ''; ?>>Western Union</option>
                                <option value="Carte" <?php echo $is_edit && $parcel->payment_method == 'Carte' ? 'selected' : ''; ?>>Carte</option>
                                <option value="Autre" <?php echo $is_edit && $parcel->payment_method == 'Autre' ? 'selected' : ''; ?>>Autre</option>
                            </select>
                        </div>

                        <!-- Statut paiement -->
                        <div class="colis224-form-group">
                            <label for="payment_status">Statut Paiement *</label>
                            <select name="payment_status" id="payment_status" required>
                                <option value="Non payé" <?php echo $is_edit && $parcel->payment_status == 'Non payé' ? 'selected' : ''; ?>>Non payé</option>
                                <option value="Partiel" <?php echo $is_edit && $parcel->payment_status == 'Partiel' ? 'selected' : ''; ?>>Partiel</option>
                                <option value="Payé" <?php echo $is_edit && $parcel->payment_status == 'Payé' ? 'selected' : ''; ?>>Payé</option>
                            </select>
                        </div>

                        <!-- Montant payé -->
                        <div class="colis224-form-group">
                            <label for="paid_amount">Montant Payé</label>
                            <input type="number" name="paid_amount" id="paid_amount" step="0.01" min="0"
                                   value="<?php echo $is_edit ? esc_attr($parcel->paid_amount) : '0'; ?>">
                        </div>

                        <!-- Notes -->
                        <div class="colis224-form-group colis224-full-width">
                            <label for="notes">Notes Internes</label>
                            <textarea name="notes" id="notes" rows="3"><?php echo $is_edit ? esc_textarea($parcel->notes) : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="colis224-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-saved"></span>
                            <?php echo $is_edit ? 'Mettre à Jour' : 'Enregistrer'; ?>
                        </button>
                        <a href="?page=colis224-parcels" class="button button-large">
                            <span class="dashicons dashicons-no-alt"></span>
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Calcul automatique du montant total
            function calculateTotal() {
                var unitPrice = parseFloat($('#unit_price').val()) || 0;
                var discountType = $('#discount_type').val();
                var discountValue = parseFloat($('#discount_value').val()) || 0;
                var total = unitPrice;

                if (discountType === 'percentage') {
                    total = unitPrice - (unitPrice * discountValue / 100);
                } else {
                    total = unitPrice - discountValue;
                }

                total = Math.max(0, total);
                $('#total_amount').val(total.toFixed(2));
            }

            $('#unit_price, #discount_type, #discount_value').on('input change', calculateTotal);

            // Génération automatique du numéro de suivi
            $('#recipient_phone').on('blur', function() {
                if ($('#tracking_number').val() === '') {
                    var phone = $(this).val().replace(/\s/g, '');
                    var last4 = phone.slice(-4);
                    if (last4.length === 4) {
                        $('#tracking_number').val('PA' + last4);
                    }
                }
            });

            // === AUTOCOMPLETE CLIENTS ===
            $('#client_search').autocomplete({
                minLength: 2,
                source: function(request, response) {
                    $.ajax({
                        url: colis224Ajax.ajaxurl,
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            action: 'colis224_search_clients',
                            term: request.term,
                            nonce: colis224Ajax.autocomplete_nonce
                        },
                        success: function(data) {
                            if (data.success && data.data) {
                                response(data.data);
                            } else {
                                response([]);
                            }
                        },
                        error: function() {
                            response([]);
                        }
                    });
                },
                select: function(event, ui) {
                    $('#client_id').val(ui.item.id);
                    $('#client_search').val(ui.item.label);
                    loadLoyaltyInfo(ui.item.id);
                    return false;
                },
                focus: function(event, ui) {
                    $('#client_search').val(ui.item.label);
                    return false;
                }
            });

            // Charger les infos de fidélité au chargement si client déjà sélectionné
            <?php if ($is_edit && $parcel->client_id): ?>
            loadLoyaltyInfo(<?php echo $parcel->client_id; ?>);
            <?php endif; ?>

            // Fonction pour charger les informations de fidélité
            function loadLoyaltyInfo(clientId) {
                if (!clientId) {
                    $('#loyalty-widget').slideUp();
                    return;
                }

                $.ajax({
                    url: colis224Ajax.ajaxurl,
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        action: 'colis224_get_client_loyalty',
                        client_id: clientId,
                        nonce: colis224Ajax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            var loyalty = response.data;

                            // Afficher le widget
                            $('#loyalty-widget').slideDown();

                            // Mettre à jour les valeurs
                            $('#loyalty-tier').text('🏆 ' + loyalty.tier);
                            $('#loyalty-available').text(loyalty.available_points.toLocaleString());
                            $('#loyalty-total').text(loyalty.total_points.toLocaleString());

                            // Calculer et afficher les points à gagner
                            var pointsToEarn = loyalty.points_per_parcel || 10;
                            $('#loyalty-earn').html('<span class="dashicons dashicons-plus-alt" style="font-size: 20px; vertical-align: middle;"></span> ' + pointsToEarn + ' pts');
                        } else {
                            $('#loyalty-widget').slideUp();
                        }
                    },
                    error: function() {
                        $('#loyalty-widget').slideUp();
                    }
                });
            }

            // === AUTOCOMPLETE DESTINATAIRES ===
            $('#recipient_name').autocomplete({
                minLength: 2,
                source: function(request, response) {
                    $.ajax({
                        url: colis224Ajax.ajaxurl,
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            action: 'colis224_search_recipients',
                            term: request.term,
                            nonce: colis224Ajax.autocomplete_nonce
                        },
                        success: function(data) {
                            if (data.success && data.data) {
                                response(data.data);
                            } else {
                                response([]);
                            }
                        },
                        error: function() {
                            response([]);
                        }
                    });
                },
                select: function(event, ui) {
                    $('#recipient_name').val(ui.item.name);
                    $('#recipient_phone').val(ui.item.phone);
                    $('#recipient_address').val(ui.item.address);
                    return false;
                },
                focus: function(event, ui) {
                    $('#recipient_name').val(ui.item.name);
                    return false;
                }
            });

            // === AUTOCOMPLETE EXPÉDITEURS ===
            $('#sender_name').autocomplete({
                minLength: 2,
                source: function(request, response) {
                    $.ajax({
                        url: colis224Ajax.ajaxurl,
                        type: 'GET',
                        dataType: 'json',
                        data: {
                            action: 'colis224_search_senders',
                            term: request.term,
                            nonce: colis224Ajax.autocomplete_nonce
                        },
                        success: function(data) {
                            if (data.success && data.data) {
                                response(data.data);
                            } else {
                                response([]);
                            }
                        },
                        error: function() {
                            response([]);
                        }
                    });
                },
                select: function(event, ui) {
                    $('#sender_name').val(ui.item.value);
                    return false;
                }
            });

            // === POPUP CONFIRMATION AGENT (v2.11.0) ===
            <?php
            // Vérifier si l'utilisateur est un agent
            $current_user = wp_get_current_user();
            $user_role = 'admin';
            if (class_exists('Colis224_Permissions')) {
                $user_role = Colis224_Permissions::get_user_colis224_role($current_user->ID);
                if (!$user_role) {
                    $user_role = 'admin';
                }
            }
            $is_agent = ($user_role === 'colis224_agent');
            $is_new_parcel = ($parcel === null);
            ?>

            <?php if ($is_agent && $is_new_parcel): ?>
            // Popup de confirmation pour les agents lors de la création
            $('#colis224-parcel-form').on('submit', function(e) {
                var confirmed = confirm(
                    '⚠️ CONFIRMATION REQUISE\n\n' +
                    'Vous êtes sur le point d\'enregistrer ce colis.\n\n' +
                    'IMPORTANT :\n' +
                    '• Le colis sera en attente de validation par un administrateur\n' +
                    '• Vous ne pourrez pas modifier les montants après validation\n' +
                    '• Vérifiez bien toutes les informations avant de continuer\n\n' +
                    'Voulez-vous continuer ?'
                );

                if (!confirmed) {
                    e.preventDefault();
                    return false;
                }
            });
            <?php endif; ?>
        });
        </script>
        <?php
    }

    /**
     * Afficher les détails d'un colis
     */
    private static function display_details($parcel_id) {
        global $wpdb;

        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $parcel = $wpdb->get_row($wpdb->prepare("
            SELECT p.*, c.name as client_name, c.phone as client_phone,
                   co.name as origin_country, cd.name as destination_country,
                   t.name as transport_mode, cat.name as category_name,
                   d.name as driver_name
            FROM $table_parcels p
            LEFT JOIN {$wpdb->prefix}colis224_clients c ON p.client_id = c.id
            LEFT JOIN {$wpdb->prefix}colis224_countries co ON p.origin_country_id = co.id
            LEFT JOIN {$wpdb->prefix}colis224_countries cd ON p.destination_country_id = cd.id
            LEFT JOIN {$wpdb->prefix}colis224_transport_modes t ON p.transport_mode_id = t.id
            LEFT JOIN {$wpdb->prefix}colis224_parcel_categories cat ON p.category_id = cat.id
            LEFT JOIN {$wpdb->prefix}colis224_drivers d ON p.driver_id = d.id
            WHERE p.id = %d
        ", $parcel_id));

        if (!$parcel) {
            echo '<div class="notice notice-error"><p>Colis introuvable.</p></div>';
            return;
        }

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-visibility"></span>
                Détails du Colis: <?php echo esc_html($parcel->tracking_number); ?>
                <a href="?page=colis224-parcels" class="page-title-action">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Retour à la liste
                </a>
                <a href="?page=colis224-parcels&action=edit&id=<?php echo $parcel->id; ?>" class="page-title-action">
                    <span class="dashicons dashicons-edit"></span>
                    Modifier
                </a>
            </h1>

            <!-- Actions rapides -->
            <div class="colis224-card" style="margin-bottom: 20px; background: #f8f9fa;">
                <h3><span class="dashicons dashicons-admin-tools"></span> Actions Rapides</h3>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php
                    // Afficher boutons facture et étiquette
                    Colis224_Invoice::display_invoice_button($parcel->id);
                    Colis224_QRCode::display_qr_code_button($parcel->id, $parcel->tracking_number);
                    ?>
                </div>
            </div>

            <div class="colis224-details-grid">
                <!-- Informations principales -->
                <div class="colis224-card">
                    <h3><span class="dashicons dashicons-info"></span> Informations Principales</h3>
                    <table class="colis224-details-table">
                        <tr>
                            <th>Numéro de Suivi:</th>
                            <td><strong><?php echo esc_html($parcel->tracking_number); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Statut:</th>
                            <td><span class="colis224-badge colis224-badge-<?php echo sanitize_title($parcel->status); ?>">
                                <?php echo esc_html($parcel->status); ?>
                            </span></td>
                        </tr>
                        <tr>
                            <th>Client:</th>
                            <td><?php echo esc_html($parcel->client_name ?: 'Aucun'); ?></td>
                        </tr>
                        <tr>
                            <th>Expéditeur:</th>
                            <td><?php echo esc_html($parcel->sender_name ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Destinataire:</th>
                            <td>
                                <?php echo esc_html($parcel->recipient_name); ?><br>
                                <strong><?php echo esc_html($parcel->recipient_phone); ?></strong><br>
                                <?php echo nl2br(esc_html($parcel->recipient_address)); ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Transport et délai -->
                <div class="colis224-card">
                    <h3><span class="dashicons dashicons-admin-site"></span> Transport et Trajet</h3>
                    <table class="colis224-details-table">
                        <tr>
                            <th>Pays d'Origine:</th>
                            <td><?php echo esc_html($parcel->origin_country ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Pays de Destination:</th>
                            <td><?php echo esc_html($parcel->destination_country ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Mode de Transport:</th>
                            <td><?php echo esc_html($parcel->transport_mode ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Catégorie:</th>
                            <td><?php echo esc_html($parcel->category_name ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Poids:</th>
                            <td><?php echo esc_html($parcel->weight); ?> kg</td>
                        </tr>
                        <tr>
                            <th>Livreur:</th>
                            <td><?php echo esc_html($parcel->driver_name ?: 'Non assigné'); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Paiement -->
                <div class="colis224-card">
                    <h3><span class="dashicons dashicons-money-alt"></span> Informations de Paiement</h3>
                    <table class="colis224-details-table">
                        <tr>
                            <th>Montant Total:</th>
                            <td><strong><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></strong></td>
                        </tr>
                        <tr>
                            <th>Prix Unitaire:</th>
                            <td><?php echo number_format($parcel->unit_price, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></td>
                        </tr>
                        <tr>
                            <th>Réduction:</th>
                            <td>
                                <?php
                                if ($parcel->discount_value > 0) {
                                    echo esc_html($parcel->discount_value);
                                    echo ($parcel->discount_type === 'percentage') ? '%' : ' ' . $parcel->currency;
                                } else {
                                    echo 'Aucune';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Statut Paiement:</th>
                            <td><span class="colis224-badge colis224-badge-payment-<?php echo sanitize_title($parcel->payment_status); ?>">
                                <?php echo esc_html($parcel->payment_status); ?>
                            </span></td>
                        </tr>
                        <tr>
                            <th>Mode de Paiement:</th>
                            <td><?php echo esc_html($parcel->payment_method ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Montant Payé:</th>
                            <td><?php echo number_format($parcel->paid_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></td>
                        </tr>
                        <tr>
                            <th>Montant Restant:</th>
                            <td><?php echo number_format($parcel->remaining_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Dates -->
                <div class="colis224-card">
                    <h3><span class="dashicons dashicons-calendar-alt"></span> Dates</h3>
                    <table class="colis224-details-table">
                        <tr>
                            <th>Date de Réception:</th>
                            <td><?php echo $parcel->reception_date ? date('d/m/Y', strtotime($parcel->reception_date)) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Date d'Expédition:</th>
                            <td><?php echo $parcel->shipping_date ? date('d/m/Y', strtotime($parcel->shipping_date)) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Livraison Estimée:</th>
                            <td><?php echo $parcel->estimated_delivery_date ? date('d/m/Y', strtotime($parcel->estimated_delivery_date)) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Date de Livraison:</th>
                            <td><?php echo $parcel->delivery_date ? date('d/m/Y', strtotime($parcel->delivery_date)) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Date de Création:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($parcel->created_at)); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if ($parcel->notes): ?>
            <div class="colis224-card">
                <h3><span class="dashicons dashicons-edit-large"></span> Notes Internes</h3>
                <p><?php echo nl2br(esc_html($parcel->notes)); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Enregistrer un nouveau colis
     */
    private static function save_parcel() {
        if (!isset($_POST['colis224_parcel_nonce']) || !wp_verify_nonce($_POST['colis224_parcel_nonce'], 'colis224_parcel_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Génération du numéro de suivi si vide
        $tracking_number = sanitize_text_field($_POST['tracking_number']);
        if (empty($tracking_number)) {
            $phone = sanitize_text_field($_POST['recipient_phone']);
            $last4 = substr(preg_replace('/\s/', '', $phone), -4);
            
            // Boucle pour garantir l'unicité du numéro de suivi
            $attempts = 0;
            $max_attempts = 10;
            
            do {
                $attempts++;
                
                // Première tentative : PA + 4 derniers chiffres du téléphone
                if ($attempts === 1) {
                    $tracking_number = 'PA' . $last4;
                } else {
                    // Tentatives suivantes : Ajouter un suffixe avec timestamp + random
                    $suffix = substr(time(), -3) . rand(10, 99);
                    $tracking_number = 'PA' . $last4 . '-' . $suffix;
                }
                
                // Vérifier l'unicité
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_parcels WHERE tracking_number = %s", 
                    $tracking_number
                ));
                
                // Si on a essayé 10 fois sans succès, utiliser un numéro complètement unique
                if ($attempts >= $max_attempts && $count > 0) {
                    $tracking_number = 'PA' . strtoupper(wp_generate_password(8, false, false));
                    $count = 0; // Forcer la sortie de la boucle
                }
                
            } while ($count > 0 && $attempts < $max_attempts);
        } else {
            // Si un tracking number est fourni manuellement, vérifier qu'il n'existe pas déjà
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_parcels WHERE tracking_number = %s", 
                $tracking_number
            ));
            
            if ($count > 0) {
                echo '<div class="notice notice-error"><p>❌ Erreur : Le numéro de suivi ' . esc_html($tracking_number) . ' existe déjà. Veuillez en choisir un autre.</p></div>';
                return;
            }
        }

        // Calcul du montant restant
        $total_amount = floatval($_POST['total_amount']);
        $paid_amount = floatval($_POST['paid_amount']);
        $remaining_amount = $total_amount - $paid_amount;

        // Obtenir l'utilisateur actuel et son rôle
        $current_user = wp_get_current_user();
        $user_role = Colis224_Permissions::get_user_colis224_role($current_user->ID);

        // Déterminer le statut de validation basé sur le rôle
        // Les admins créent des colis pré-approuvés, les agents doivent attendre validation
        $validation_status = 'approved'; // Par défaut
        if ($user_role === 'colis224_agent') {
            $validation_status = 'pending'; // Les agents doivent attendre validation
        }

        $data = array(
            'tracking_number' => $tracking_number,
            'client_id' => !empty($_POST['client_id']) ? intval($_POST['client_id']) : null,
            'sender_name' => sanitize_text_field($_POST['sender_name']),
            'recipient_name' => sanitize_text_field($_POST['recipient_name']),
            'recipient_phone' => sanitize_text_field($_POST['recipient_phone']),
            'recipient_address' => sanitize_textarea_field($_POST['recipient_address']),
            'origin_country_id' => !empty($_POST['origin_country_id']) ? intval($_POST['origin_country_id']) : null,
            'destination_country_id' => !empty($_POST['destination_country_id']) ? intval($_POST['destination_country_id']) : null,
            'transport_mode_id' => !empty($_POST['transport_mode_id']) ? intval($_POST['transport_mode_id']) : null,
            'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
            'weight' => floatval($_POST['weight']),
            'unit_price' => floatval($_POST['unit_price']),
            'discount_type' => sanitize_text_field($_POST['discount_type']),
            'discount_value' => floatval($_POST['discount_value']),
            'total_amount' => $total_amount,
            'currency' => sanitize_text_field($_POST['currency']),
            'reception_date' => !empty($_POST['reception_date']) ? sanitize_text_field($_POST['reception_date']) : null,
            'shipping_date' => !empty($_POST['shipping_date']) ? sanitize_text_field($_POST['shipping_date']) : null,
            'delivery_date' => !empty($_POST['delivery_date']) ? sanitize_text_field($_POST['delivery_date']) : null,
            'estimated_delivery_date' => !empty($_POST['estimated_delivery_date']) ? sanitize_text_field($_POST['estimated_delivery_date']) : null,
            'status' => sanitize_text_field($_POST['status']),
            'payment_method' => sanitize_text_field($_POST['payment_method']),
            'payment_status' => sanitize_text_field($_POST['payment_status']),
            'paid_amount' => $paid_amount,
            'remaining_amount' => $remaining_amount,
            'driver_id' => !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null,
            'notes' => sanitize_textarea_field($_POST['notes']),
            // Nouveaux champs v2.11.0 (validation hiérarchique)
            'created_by' => $current_user->ID,
            'validation_status' => $validation_status
        );

        // Si admin crée le colis, le marquer comme auto-validé
        if ($validation_status === 'approved') {
            $data['validated_by'] = $current_user->ID;
            $data['validated_at'] = current_time('mysql');
        }

        $result = $wpdb->insert($table_parcels, $data);

        if ($result) {
            $parcel_id = $wpdb->insert_id;

            // Enregistrer dans l'historique (v2.11.0)
            if (class_exists('Colis224_Parcel_History')) {
                Colis224_Parcel_History::log_created($parcel_id, array(
                    'tracking_number' => $tracking_number,
                    'recipient_name' => $data['recipient_name'],
                    'total_amount' => $total_amount,
                    'validation_status' => $validation_status
                ));
            }

            // Créer un revenu si le colis est payé
            if ($paid_amount > 0) {
                $table_revenues = $wpdb->prefix . 'colis224_revenues';
                $wpdb->insert($table_revenues, array(
                    'source_type' => 'parcel',
                    'parcel_id' => $parcel_id,
                    'amount' => $paid_amount,
                    'currency' => $data['currency'],
                    'payment_method' => $data['payment_method'],
                    'description' => 'Paiement colis ' . $tracking_number,
                    'revenue_date' => current_time('Y-m-d')
                ));

                // Déclencher l'action pour les points de paiement
                do_action('colis224_payment_received', $paid_amount, $data['client_id']);
            }

            // Déclencher l'action pour attribuer les points de fidélité
            do_action('colis224_parcel_created', $parcel_id, $data['client_id']);

            // Message de succès adapté au statut de validation
            if ($validation_status === 'pending') {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p>✅ Colis enregistré avec succès ! <strong>Tracking: ' . esc_html($tracking_number) . '</strong></p>';
                echo '<p>⏳ <strong>En attente de validation</strong> par un administrateur. Le colis sera visible dans la liste une fois approuvé.</p>';
                echo '</div>';
            } else {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>✅ Colis enregistré et validé avec succès ! <strong>Tracking: ' . esc_html($tracking_number) . '</strong></p>';
                echo '</div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Erreur lors de l\'enregistrement du colis.</p></div>';
        }
    }

    /**
     * Mettre à jour un colis
     */
    private static function update_parcel() {
        if (!isset($_POST['colis224_parcel_nonce']) || !wp_verify_nonce($_POST['colis224_parcel_nonce'], 'colis224_parcel_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';
        $parcel_id = intval($_POST['parcel_id']);

        // Récupérer le colis actuel pour comparaison
        $current_parcel = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_parcels WHERE id = %d", $parcel_id));

        if (!$current_parcel) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Colis introuvable.</p></div>';
            return;
        }

        // Vérifier le rôle de l'utilisateur
        $current_user = wp_get_current_user();
        $user_role = 'admin'; // Default
        if (class_exists('Colis224_Permissions')) {
            $user_role = Colis224_Permissions::get_user_colis224_role($current_user->ID);
            if (!$user_role) {
                $user_role = 'admin';
            }
        }

        // RESTRICTIONS AGENTS (v2.11.0)
        $is_agent = ($user_role === 'colis224_agent');
        $is_validated = ($current_parcel->validation_status === 'approved');

        // Calcul du montant restant
        $total_amount = floatval($_POST['total_amount']);
        $paid_amount = floatval($_POST['paid_amount']);
        $remaining_amount = $total_amount - $paid_amount;

        // Bloquer les modifications sensibles pour les agents après validation admin
        if ($is_agent && $is_validated) {
            // Vérifier si l'agent tente de modifier des champs interdits
            $forbidden_changes = array();

            // Montants interdits
            if (abs($total_amount - $current_parcel->total_amount) > 0.01) {
                $forbidden_changes[] = 'montant total';
            }
            if (abs(floatval($_POST['unit_price']) - $current_parcel->unit_price) > 0.01) {
                $forbidden_changes[] = 'prix unitaire';
            }
            if (abs(floatval($_POST['discount_value']) - $current_parcel->discount_value) > 0.01) {
                $forbidden_changes[] = 'remise';
            }

            // Paiement interdit
            if (sanitize_text_field($_POST['payment_status']) !== $current_parcel->payment_status) {
                $forbidden_changes[] = 'statut de paiement';
            }
            if (abs($paid_amount - $current_parcel->paid_amount) > 0.01) {
                $forbidden_changes[] = 'montant payé';
            }

            // Si des modifications interdites sont détectées
            if (!empty($forbidden_changes)) {
                $fields_list = implode(', ', $forbidden_changes);
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>❌ MODIFICATION REFUSÉE</strong></p>';
                echo '<p>En tant qu\'agent, vous ne pouvez pas modifier ces champs après validation admin :</p>';
                echo '<ul style="margin-left: 20px;">';
                foreach ($forbidden_changes as $field) {
                    echo '<li>' . esc_html($field) . '</li>';
                }
                echo '</ul>';
                echo '<p>Contactez un administrateur pour effectuer ces modifications.</p>';
                echo '</div>';
                return;
            }

            // Forcer les valeurs d'origine pour les champs sensibles
            $total_amount = $current_parcel->total_amount;
            $paid_amount = $current_parcel->paid_amount;
            $remaining_amount = $current_parcel->remaining_amount;
            $_POST['unit_price'] = $current_parcel->unit_price;
            $_POST['discount_value'] = $current_parcel->discount_value;
            $_POST['payment_status'] = $current_parcel->payment_status;
        }

        $data = array(
            'client_id' => !empty($_POST['client_id']) ? intval($_POST['client_id']) : null,
            'sender_name' => sanitize_text_field($_POST['sender_name']),
            'recipient_name' => sanitize_text_field($_POST['recipient_name']),
            'recipient_phone' => sanitize_text_field($_POST['recipient_phone']),
            'recipient_address' => sanitize_textarea_field($_POST['recipient_address']),
            'origin_country_id' => !empty($_POST['origin_country_id']) ? intval($_POST['origin_country_id']) : null,
            'destination_country_id' => !empty($_POST['destination_country_id']) ? intval($_POST['destination_country_id']) : null,
            'transport_mode_id' => !empty($_POST['transport_mode_id']) ? intval($_POST['transport_mode_id']) : null,
            'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
            'weight' => floatval($_POST['weight']),
            'unit_price' => floatval($_POST['unit_price']),
            'discount_type' => sanitize_text_field($_POST['discount_type']),
            'discount_value' => floatval($_POST['discount_value']),
            'total_amount' => $total_amount,
            'currency' => sanitize_text_field($_POST['currency']),
            'reception_date' => !empty($_POST['reception_date']) ? sanitize_text_field($_POST['reception_date']) : null,
            'shipping_date' => !empty($_POST['shipping_date']) ? sanitize_text_field($_POST['shipping_date']) : null,
            'delivery_date' => !empty($_POST['delivery_date']) ? sanitize_text_field($_POST['delivery_date']) : null,
            'estimated_delivery_date' => !empty($_POST['estimated_delivery_date']) ? sanitize_text_field($_POST['estimated_delivery_date']) : null,
            'status' => sanitize_text_field($_POST['status']),
            'payment_method' => sanitize_text_field($_POST['payment_method']),
            'payment_status' => sanitize_text_field($_POST['payment_status']),
            'paid_amount' => $paid_amount,
            'remaining_amount' => $remaining_amount,
            'driver_id' => !empty($_POST['driver_id']) ? intval($_POST['driver_id']) : null,
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $result = $wpdb->update($table_parcels, $data, array('id' => $parcel_id));

        if ($result !== false) {
            // Enregistrer dans l'historique (v2.11.0)
            if (class_exists('Colis224_Parcel_History')) {
                // Log changement de statut
                if ($data['status'] !== $current_parcel->status) {
                    Colis224_Parcel_History::log_status_change($parcel_id, $current_parcel->status, $data['status']);
                }

                // Log changement de montant
                if (abs($data['total_amount'] - $current_parcel->total_amount) > 0.01) {
                    Colis224_Parcel_History::log_amount_change($parcel_id, $current_parcel->total_amount, $data['total_amount']);
                }

                // Log changement de paiement
                if ($data['payment_status'] !== $current_parcel->payment_status || abs($data['paid_amount'] - $current_parcel->paid_amount) > 0.01) {
                    Colis224_Parcel_History::log_payment_change($parcel_id, $current_parcel->payment_status, $data['payment_status'], $data['paid_amount']);
                }
            }

            echo '<div class="notice notice-success is-dismissible"><p>✅ Colis mis à jour avec succès!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Erreur lors de la mise à jour du colis.</p></div>';
        }
    }


    /**
     * Supprimer un colis
     */
    private static function delete_parcel($parcel_id) {
        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $result = $wpdb->delete($table_parcels, array('id' => intval($parcel_id)));

        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Colis supprimé avec succès!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Erreur lors de la suppression du colis.</p></div>';
        }
    }
}
