<?php
/**
 * MODULE 3: Gestion des Clients
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Clients {

    public static function display_page() {
        // Traitement des actions
        if (isset($_POST['action']) && $_POST['action'] === 'add_client') {
            self::save_client();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_client') {
            self::update_client();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            self::delete_client($_GET['id']);
        }

        // Déterminer la vue
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

    private static function display_list() {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $country_filter = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : ''; // v2.18.27: Filtre pays

        $where = "1=1";
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (name LIKE %s OR phone LIKE %s OR email LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        if (!empty($type_filter)) {
            $where .= $wpdb->prepare(" AND type = %s", $type_filter);
        }

        // v2.18.27: Filtre par pays
        if (!empty($country_filter)) {
            $where .= $wpdb->prepare(" AND country = %s", $country_filter);
        }

        // v2.18.27: Récupérer la liste des pays distincts pour le filtre
        $countries = $wpdb->get_col("SELECT DISTINCT country FROM $table_clients WHERE country IS NOT NULL AND country != '' ORDER BY country ASC");

        $clients = $wpdb->get_results("SELECT * FROM $table_clients WHERE $where ORDER BY created_at DESC");

        // Vérifier si l'utilisateur a un rôle non-admin (v2.18.3)
        $current_user = wp_get_current_user();
        $user_role = 'admin';
        if (class_exists('Colis224_Permissions')) {
            $user_role = Colis224_Permissions::get_user_colis224_role($current_user->ID);
            if (!$user_role) {
                $user_role = 'admin';
            }
        }
        $hide_actions = ($user_role === 'colis224_agent') ||
                        in_array('editor', $current_user->roles) ||
                        in_array('author', $current_user->roles);

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-groups"></span>
                Gestion des Clients
                <a href="?page=colis224-clients&action=add" class="page-title-action">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Nouveau Client
                </a>
            </h1>

            <div class="colis224-filters">
                <form method="get">
                    <input type="hidden" name="page" value="colis224-clients">
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>"
                           placeholder="Rechercher..." class="colis224-search-input">
                    <select name="type">
                        <option value="">Tous les types</option>
                        <option value="Particulier" <?php selected($type_filter, 'Particulier'); ?>>Particulier</option>
                        <option value="Entreprise" <?php selected($type_filter, 'Entreprise'); ?>>Entreprise</option>
                    </select>
                    <!-- v2.18.27: Filtre par pays -->
                    <select name="country">
                        <option value="">Tous les pays</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo esc_attr($country); ?>" <?php selected($country_filter, $country); ?>>
                                <?php echo Colis224_Emojis::get_country_flag($country); ?> <?php echo esc_html($country); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button">Filtrer</button>
                    <a href="?page=colis224-clients" class="button">Réinitialiser</a>
                </form>
            </div>

            <div class="colis224-card">
                <table class="colis224-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Nom</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Pays</th>
                            <th>Solde</th>
                            <th>Remise</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">Aucun client trouvé.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><span class="colis224-badge"><?php echo esc_html($client->type); ?></span></td>
                                <td>
                                    <strong><?php echo esc_html($client->name); ?></strong>
                                    <?php if ($client->company_name): ?>
                                    <br><small><?php echo esc_html($client->company_name); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($client->phone); ?></td>
                                <td><?php echo esc_html($client->email ?: '-'); ?></td>
                                <td>
                                    <?php if (!empty($client->country)): ?>
                                        <?php echo Colis224_Emojis::get_country_flag($client->country); ?> <?php echo esc_html($client->country); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($client->balance, 0, ',', ' '); ?> GNF</td>
                                <td><?php echo esc_html($client->discount_rate); ?>%</td>
                                <td><?php echo date('d/m/Y', strtotime($client->created_at)); ?></td>
                                <td class="colis224-actions">
                                    <a href="?page=colis224-clients&action=view&id=<?php echo $client->id; ?>"
                                       class="button button-small" title="Voir les détails">
                                        <span class="dashicons dashicons-visibility"></span>
                                        Voir
                                    </a>
                                    <?php if (!$hide_actions): // Seuls les admins peuvent modifier/supprimer ?>
                                    <a href="?page=colis224-clients&action=edit&id=<?php echo $client->id; ?>"
                                       class="button button-small" title="Modifier ce client">
                                        <span class="dashicons dashicons-edit"></span>
                                        Modifier
                                    </a>
                                    <a href="?page=colis224-clients&action=delete&id=<?php echo $client->id; ?>"
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?');"
                                       title="Supprimer ce client">
                                        <span class="dashicons dashicons-trash"></span>
                                        Supprimer
                                    </a>
                                    <?php endif; ?>
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

    private static function display_form($client_id = null) {
        global $wpdb;
        $client = null;

        if ($client_id) {
            $table_clients = $wpdb->prefix . 'colis224_clients';
            $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_clients WHERE id = %d", $client_id));
        }

        $is_edit = ($client !== null);
        $title = $is_edit ? 'Modifier le Client' : 'Nouveau Client';
        $action = $is_edit ? 'edit_client' : 'add_client';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-groups"></span>
                <?php echo $title; ?>
                <a href="?page=colis224-clients" class="page-title-action">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Retour
                </a>
            </h1>

            <div class="colis224-card">
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="client_id" value="<?php echo $client->id; ?>">
                    <?php endif; ?>
                    <?php wp_nonce_field('colis224_client_action', 'colis224_client_nonce'); ?>

                    <div class="colis224-form-grid">
                        <div class="colis224-form-group">
                            <label for="type">Type *</label>
                            <select name="type" id="type" required>
                                <option value="Particulier" <?php echo $is_edit && $client->type == 'Particulier' ? 'selected' : ''; ?>>Particulier</option>
                                <option value="Entreprise" <?php echo $is_edit && $client->type == 'Entreprise' ? 'selected' : ''; ?>>Entreprise</option>
                            </select>
                        </div>

                        <div class="colis224-form-group">
                            <label for="name">Nom / Contact *</label>
                            <input type="text" name="name" id="name"
                                   value="<?php echo $is_edit ? esc_attr($client->name) : ''; ?>" required>
                        </div>

                        <div class="colis224-form-group">
                            <label for="company_name">Raison Sociale</label>
                            <input type="text" name="company_name" id="company_name"
                                   value="<?php echo $is_edit ? esc_attr($client->company_name) : ''; ?>">
                        </div>

                        <div class="colis224-form-group">
                            <label for="country">🏳️ Pays</label>
                            <input type="text" name="country" id="country"
                                   value="<?php echo $is_edit ? esc_attr($client->country) : ''; ?>"
                                   placeholder="Ex: France, Guinée, Sénégal...">
                        </div>

                        <div class="colis224-form-group">
                            <label for="phone">Téléphone *</label>
                            <input type="text" name="phone" id="phone"
                                   value="<?php echo $is_edit ? esc_attr($client->phone) : ''; ?>" required>
                        </div>

                        <div class="colis224-form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email"
                                   value="<?php echo $is_edit ? esc_attr($client->email) : ''; ?>">
                        </div>

                        <div class="colis224-form-group colis224-full-width">
                            <label for="address">Adresse</label>
                            <textarea name="address" id="address" rows="2"><?php echo $is_edit ? esc_textarea($client->address) : ''; ?></textarea>
                        </div>

                        <div class="colis224-form-group">
                            <label for="discount_rate">Remise (%) </label>
                            <input type="number" name="discount_rate" id="discount_rate" step="0.01" min="0" max="100"
                                   value="<?php echo $is_edit ? esc_attr($client->discount_rate) : '0'; ?>">
                        </div>

                        <div class="colis224-form-group">
                            <label for="balance">Solde (GNF)</label>
                            <input type="number" name="balance" id="balance" step="0.01"
                                   value="<?php echo $is_edit ? esc_attr($client->balance) : '0'; ?>">
                        </div>

                        <div class="colis224-form-group colis224-full-width">
                            <label for="notes">Notes Internes</label>
                            <textarea name="notes" id="notes" rows="3"><?php echo $is_edit ? esc_textarea($client->notes) : ''; ?></textarea>
                        </div>
                    </div>

                    <div class="colis224-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-saved"></span>
                            <?php echo $is_edit ? 'Mettre à Jour' : 'Enregistrer'; ?>
                        </button>
                        <a href="?page=colis224-clients" class="button button-large">
                            <span class="dashicons dashicons-no-alt"></span>
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    private static function display_details($client_id) {
        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        // Vérifier si l'utilisateur a un rôle non-admin (v2.18.3)
        $current_user = wp_get_current_user();
        $user_role = 'admin';
        if (class_exists('Colis224_Permissions')) {
            $user_role = Colis224_Permissions::get_user_colis224_role($current_user->ID);
            if (!$user_role) {
                $user_role = 'admin';
            }
        }
        $hide_actions = ($user_role === 'colis224_agent') ||
                        in_array('editor', $current_user->roles) ||
                        in_array('author', $current_user->roles);

        $client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_clients WHERE id = %d", $client_id));

        if (!$client) {
            echo '<div class="notice notice-error"><p>Client introuvable.</p></div>';
            return;
        }

        // Récupérer les colis du client
        $parcels = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_parcels WHERE client_id = %d ORDER BY created_at DESC",
            $client_id
        ));

        $total_parcels = count($parcels);
        $total_revenue = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(total_amount), 0) FROM $table_parcels WHERE client_id = %d",
            $client_id
        ));

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-groups"></span>
                Détails du Client: <?php echo esc_html($client->name); ?>
                <a href="?page=colis224-clients" class="page-title-action">
                    <span class="dashicons dashicons-arrow-left-alt"></span> Retour
                </a>
                <?php if (!$hide_actions): // Seuls les admins peuvent modifier ?>
                <a href="?page=colis224-clients&action=edit&id=<?php echo $client->id; ?>" class="page-title-action">
                    <span class="dashicons dashicons-edit"></span> Modifier
                </a>
                <?php endif; ?>
            </h1>

            <div class="colis224-dashboard-grid colis224-grid-3">
                <div class="colis224-card colis224-card-blue">
                    <h4><span class="dashicons dashicons-archive"></span> Total Colis</h4>
                    <p class="colis224-big-number"><?php echo $total_parcels; ?></p>
                </div>
                <div class="colis224-card colis224-card-green">
                    <h4><span class="dashicons dashicons-money-alt"></span> Revenu Total</h4>
                    <p class="colis224-big-number"><?php echo number_format($total_revenue, 0, ',', ' '); ?> GNF</p>
                </div>
                <div class="colis224-card colis224-card-orange">
                    <h4><span class="dashicons dashicons-chart-line"></span> Solde</h4>
                    <p class="colis224-big-number"><?php echo number_format($client->balance, 0, ',', ' '); ?> GNF</p>
                </div>
            </div>

            <div class="colis224-details-grid">
                <div class="colis224-card">
                    <h3><span class="dashicons dashicons-id"></span> Informations du Client</h3>
                    <table class="colis224-details-table">
                        <tr>
                            <th>Type:</th>
                            <td><span class="colis224-badge"><?php echo esc_html($client->type); ?></span></td>
                        </tr>
                        <tr>
                            <th>Nom:</th>
                            <td><?php echo esc_html($client->name); ?></td>
                        </tr>
                        <?php if ($client->company_name): ?>
                        <tr>
                            <th>Raison Sociale:</th>
                            <td><?php echo esc_html($client->company_name); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Téléphone:</th>
                            <td><strong><?php echo esc_html($client->phone); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo esc_html($client->email ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Adresse:</th>
                            <td><?php echo nl2br(esc_html($client->address ?: '-')); ?></td>
                        </tr>
                        <tr>
                            <th>Remise:</th>
                            <td><?php echo esc_html($client->discount_rate); ?>%</td>
                        </tr>
                        <tr>
                            <th>Date de création:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($client->created_at)); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if ($client->notes): ?>
            <div class="colis224-card">
                <h3><span class="dashicons dashicons-edit-large"></span> Notes</h3>
                <p><?php echo nl2br(esc_html($client->notes)); ?></p>
            </div>
            <?php endif; ?>

            <!-- Programme de Fidélité -->
            <?php self::display_loyalty_section($client_id); ?>

            <div class="colis224-card">
                <h3><span class="dashicons dashicons-list-view"></span> Historique des Colis</h3>
                <?php if (empty($parcels)): ?>
                    <p>Aucun colis pour ce client.</p>
                <?php else: ?>
                    <table class="colis224-table">
                        <thead>
                            <tr>
                                <th>N° Suivi</th>
                                <th>Destinataire</th>
                                <th>Statut</th>
                                <th>Montant</th>
                                <th>Paiement</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parcels as $parcel): ?>
                            <tr>
                                <td><strong><?php echo esc_html($parcel->tracking_number); ?></strong></td>
                                <td><?php echo esc_html($parcel->recipient_name); ?></td>
                                <td><span class="colis224-badge colis224-badge-<?php echo sanitize_title($parcel->status); ?>">
                                    <?php echo esc_html($parcel->status); ?>
                                </span></td>
                                <td><?php echo number_format($parcel->total_amount, 0, ',', ' '); ?> <?php echo $parcel->currency; ?></td>
                                <td><span class="colis224-badge colis224-badge-payment-<?php echo sanitize_title($parcel->payment_status); ?>">
                                    <?php echo esc_html($parcel->payment_status); ?>
                                </span></td>
                                <td><?php echo date('d/m/Y', strtotime($parcel->created_at)); ?></td>
                                <td>
                                    <a href="?page=colis224-parcels&action=view&id=<?php echo $parcel->id; ?>"
                                       class="button button-small">Voir</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Afficher l'écran de confirmation avant validation finale
     */
    private static function display_client_confirmation() {
        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-visibility"></span>
                Confirmation - Vérifiez les informations
            </h1>

            <div class="colis224-card" style="background: #f0f8ff; border-left: 4px solid #2271b1;">
                <div style="background: #2271b1; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0;">
                    <h2 style="margin: 0; color: white;">
                        <span class="dashicons dashicons-info" style="vertical-align: middle;"></span>
                        📋 Résumé du Client - Vérifiez Avant de Valider
                    </h2>
                    <p style="margin: 10px 0 0 0; opacity: 0.9;">Une fois validé, ces informations seront enregistrées.</p>
                </div>

                <div class="colis224-form-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <!-- Informations Générales -->
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0; color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px;">
                            <span class="dashicons dashicons-admin-users"></span> Informations Générales
                        </h3>
                        <p><strong>Type:</strong> <?php echo esc_html(sanitize_text_field($_POST['type'])); ?></p>
                        <p><strong>Nom:</strong> <?php echo esc_html(sanitize_text_field($_POST['name'])); ?></p>
                        <?php if (!empty($_POST['company_name'])): ?>
                        <p><strong>Nom de l'entreprise:</strong> <?php echo esc_html(sanitize_text_field($_POST['company_name'])); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Coordonnées -->
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0; color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px;">
                            <span class="dashicons dashicons-phone"></span> Coordonnées
                        </h3>
                        <p><strong>Téléphone:</strong> <?php echo esc_html(sanitize_text_field($_POST['phone'])); ?></p>
                        <p><strong>Email:</strong> <?php echo !empty($_POST['email']) ? esc_html(sanitize_email($_POST['email'])) : 'N/A'; ?></p>
                        <p><strong>Adresse:</strong> <?php echo !empty($_POST['address']) ? esc_html(sanitize_textarea_field($_POST['address'])) : 'N/A'; ?></p>
                    </div>

                    <!-- Informations Financières -->
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0; color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px;">
                            <span class="dashicons dashicons-money-alt"></span> Informations Financières
                        </h3>
                        <p><strong>Taux de remise:</strong> <?php echo esc_html(floatval($_POST['discount_rate'])); ?> %</p>
                        <p><strong>Solde:</strong> <?php echo esc_html(number_format(floatval($_POST['balance']), 0, ',', ' ')); ?> GNF</p>
                    </div>

                    <!-- Notes -->
                    <?php if (!empty($_POST['notes'])): ?>
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="margin-top: 0; color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px;">
                            <span class="dashicons dashicons-edit"></span> Notes
                        </h3>
                        <p><?php echo nl2br(esc_html(sanitize_textarea_field($_POST['notes']))); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Formulaires pour les deux actions -->
                <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ddd; display: flex; gap: 20px; justify-content: center;">
                    <!-- Formulaire pour Modifier -->
                    <form method="post" action="?page=colis224-clients&action=add" style="display: inline;">
                        <?php
                        // Réinjecter toutes les données POST dans des champs cachés
                        foreach ($_POST as $key => $value) {
                            if ($key !== 'colis224_confirmed' && !is_array($value)) {
                                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                            }
                        }
                        ?>
                        <button type="submit" class="button button-secondary" style="padding: 15px 40px; font-size: 16px; height: auto;">
                            <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span>
                            ✏️ Modifier
                        </button>
                    </form>

                    <!-- Formulaire pour Valider -->
                    <form method="post" action="" style="display: inline;">
                        <?php
                        // Réinjecter toutes les données POST dans des champs cachés
                        foreach ($_POST as $key => $value) {
                            if (!is_array($value)) {
                                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                            }
                        }
                        ?>
                        <input type="hidden" name="colis224_confirmed" value="1">
                        <button type="submit" class="button button-primary" style="padding: 15px 40px; font-size: 16px; height: auto; background: #00a32a; border-color: #00a32a;">
                            <span class="dashicons dashicons-yes-alt" style="vertical-align: middle;"></span>
                            ✅ Valider et Enregistrer
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    private static function save_client() {
        if (!isset($_POST['colis224_client_nonce']) || !wp_verify_nonce($_POST['colis224_client_nonce'], 'colis224_client_action')) {
            wp_die('Erreur de sécurité');
        }

        // Vérifier si l'utilisateur a confirmé (étape 2) ou si c'est la première soumission (étape 1)
        $is_confirmed = isset($_POST['colis224_confirmed']) && $_POST['colis224_confirmed'] === '1';

        // Si pas encore confirmé, afficher l'écran de confirmation
        if (!$is_confirmed) {
            self::display_client_confirmation();
            return;
        }

        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';

        // Obtenir l'utilisateur actuel et son rôle
        $current_user = wp_get_current_user();
        $user_role = Colis224_Permissions::get_user_colis224_role($current_user->ID);

        // Déterminer le statut de validation basé sur le rôle
        // Les admins créent des clients pré-validés, les agents doivent attendre validation
        $validation_status = 'validated'; // Par défaut pour admin
        if ($user_role === 'colis224_agent' || in_array('editor', $current_user->roles) || in_array('author', $current_user->roles)) {
            $validation_status = 'pending'; // Les agents, éditeurs et auteurs doivent attendre validation
        }

        $data = array(
            'type' => sanitize_text_field($_POST['type']),
            'name' => sanitize_text_field($_POST['name']),
            'company_name' => sanitize_text_field($_POST['company_name']),
            'country' => sanitize_text_field($_POST['country']), // v2.18.25
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'address' => sanitize_textarea_field($_POST['address']),
            'discount_rate' => floatval($_POST['discount_rate']),
            'balance' => floatval($_POST['balance']),
            'notes' => sanitize_textarea_field($_POST['notes']),
            'created_by' => $current_user->ID,
            'validation_status' => $validation_status
        );

        // Si admin crée le client, le marquer comme auto-validé
        if ($validation_status === 'validated') {
            $data['validated_by'] = $current_user->ID;
            $data['validated_at'] = current_time('mysql');
        }

        $result = $wpdb->insert($table_clients, $data);

        if ($result) {
            // Message de succès adapté au statut de validation
            if ($validation_status === 'pending') {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p>✅ Client enregistré avec succès !</p>';
                echo '<p>⏳ <strong>En attente de validation</strong> par un administrateur. Le client sera visible dans la liste une fois approuvé.</p>';
                echo '</div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Client enregistré et validé avec succès !</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Erreur lors de l\'enregistrement du client.</p></div>';
        }
    }

    private static function update_client() {
        if (!isset($_POST['colis224_client_nonce']) || !wp_verify_nonce($_POST['colis224_client_nonce'], 'colis224_client_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table_clients = $wpdb->prefix . 'colis224_clients';
        $client_id = intval($_POST['client_id']);

        // Récupérer le client actuel pour vérifier le statut de validation
        $current_client = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_clients WHERE id = %d", $client_id));

        if (!$current_client) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Client introuvable.</p></div>';
            return;
        }

        // Vérifier le rôle de l'utilisateur
        $current_user = wp_get_current_user();
        $user_role = Colis224_Permissions::get_user_colis224_role($current_user->ID);

        // RESTRICTIONS AGENTS / EDITORS / AUTHORS (v2.18.3)
        $is_restricted_role = ($user_role === 'colis224_agent') ||
                              in_array('editor', $current_user->roles) ||
                              in_array('author', $current_user->roles);
        $is_admin = current_user_can('manage_options');

        // Bloquer TOUTE modification pour les éditeurs/auteurs (pas seulement les validés)
        if ($is_restricted_role && !$is_admin) {
            echo '<div class="notice notice-error is-dismissible" style="border-left-color: #dc3545;">';
            echo '<p><strong>🔒 MODIFICATION INTERDITE</strong></p>';
            echo '<p style="font-size: 14px;">En tant qu\'éditeur/agent, vous <strong>ne pouvez pas modifier</strong> les clients existants.</p>';
            echo '<p style="font-size: 13px; color: #666;">📌 <em>Seuls les administrateurs peuvent modifier les clients.</em></p>';
            echo '<p style="font-size: 13px;">💡 Vous pouvez créer de nouveaux clients qui seront soumis pour validation.</p>';
            echo '</div>';
            return;
        }

        $data = array(
            'type' => sanitize_text_field($_POST['type']),
            'name' => sanitize_text_field($_POST['name']),
            'company_name' => sanitize_text_field($_POST['company_name']),
            'country' => sanitize_text_field($_POST['country']), // v2.18.25
            'phone' => sanitize_text_field($_POST['phone']),
            'email' => sanitize_email($_POST['email']),
            'address' => sanitize_textarea_field($_POST['address']),
            'discount_rate' => floatval($_POST['discount_rate']),
            'balance' => floatval($_POST['balance']),
            'notes' => sanitize_textarea_field($_POST['notes'])
        );

        $result = $wpdb->update($table_clients, $data, array('id' => $client_id));

        if ($result !== false) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Client mis à jour avec succès !</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Erreur lors de la mise à jour.</p></div>';
        }
    }

    private static function delete_client($client_id) {
        global $wpdb;

        // Vérifier le rôle de l'utilisateur - seuls les administrateurs peuvent supprimer (v2.18.3)
        $current_user = wp_get_current_user();
        $is_admin = in_array('administrator', $current_user->roles) || current_user_can('manage_options');

        if (!$is_admin) {
            echo '<div class="notice notice-error is-dismissible" style="border-left-color: #dc3545;">';
            echo '<p><strong>🚫 SUPPRESSION INTERDITE</strong></p>';
            echo '<p style="font-size: 14px;">Seuls les <strong>administrateurs</strong> peuvent supprimer des clients.</p>';
            echo '<p style="font-size: 13px; color: #666;">📌 <em>Cette restriction garantit l\'intégrité des données.</em></p>';
            echo '<p style="font-size: 13px;">👉 Contactez un administrateur si vous devez supprimer ce client.</p>';
            echo '</div>';
            return;
        }

        $table_clients = $wpdb->prefix . 'colis224_clients';
        $result = $wpdb->delete($table_clients, array('id' => intval($client_id)));

        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Client supprimé avec succès !</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Erreur lors de la suppression.</p></div>';
        }
    }

    /**
     * Afficher la section fidélité du client
     */
    private static function display_loyalty_section($client_id) {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-loyalty.php';
        $loyalty = Colis224_Loyalty::get_client_loyalty($client_id);

        if (!$loyalty) {
            // Client pas encore inscrit
            ?>
            <div class="colis224-card colis224-card-light">
                <h3><span class="dashicons dashicons-awards"></span> Programme de Fidélité</h3>
                <p style="text-align: center; padding: 20px; color: #666;">
                    Ce client n'est pas encore inscrit au programme de fidélité.<br>
                    <small>L'inscription sera automatique lors du prochain colis.</small>
                </p>
            </div>
            <?php
            return;
        }

        // Client inscrit - afficher les détails
        $tier_colors = array(
            'Platinum' => 'background: linear-gradient(135deg, #E5E4E2 0%, #C0C0C0 100%); color: #333;',
            'Gold' => 'background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: #333;',
            'Silver' => 'background: linear-gradient(135deg, #C0C0C0 0%, #A8A8A8 100%); color: #333;',
            'Bronze' => 'background: linear-gradient(135deg, #CD7F32 0%, #8B4513 100%); color: #fff;'
        );

        $tier_icons = array(
            'Platinum' => '💎',
            'Gold' => '🥇',
            'Silver' => '🥈',
            'Bronze' => '🥉'
        );

        // Récupérer les dernières transactions
        $transactions = Colis224_Loyalty::get_client_transactions($client_id, 10);

        ?>
        <div class="colis224-card">
            <h3><span class="dashicons dashicons-awards"></span> Programme de Fidélité</h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <!-- Tier -->
                <div style="<?php echo $tier_colors[$loyalty->tier]; ?> padding: 20px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 48px;"><?php echo $tier_icons[$loyalty->tier]; ?></div>
                    <div style="font-size: 20px; font-weight: bold; margin-top: 10px;"><?php echo esc_html($loyalty->tier); ?></div>
                    <div style="font-size: 12px; margin-top: 5px;">Tier Actuel</div>
                </div>

                <!-- Points Disponibles -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; text-align: center; color: #fff;">
                    <div style="font-size: 14px; opacity: 0.9;">Points Disponibles</div>
                    <div style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo number_format($loyalty->available_points, 0, ',', ' '); ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">Prêts à échanger</div>
                </div>

                <!-- Points Totaux -->
                <div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 20px; border-radius: 8px; text-align: center; color: #fff;">
                    <div style="font-size: 14px; opacity: 0.9;">Points Totaux</div>
                    <div style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo number_format($loyalty->total_points, 0, ',', ' '); ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">Cumulés depuis l'inscription</div>
                </div>

                <!-- Points Échangés -->
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 8px; text-align: center; color: #fff;">
                    <div style="font-size: 14px; opacity: 0.9;">Points Échangés</div>
                    <div style="font-size: 36px; font-weight: bold; margin: 10px 0;"><?php echo number_format($loyalty->redeemed_points, 0, ',', ' '); ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">Récompenses obtenues</div>
                </div>
            </div>

            <!-- Progression vers le prochain tier -->
            <?php
            $next_tier = '';
            $points_needed = 0;
            if ($loyalty->tier == 'Bronze') {
                $next_tier = 'Silver';
                $points_needed = 2000 - $loyalty->total_points;
            } elseif ($loyalty->tier == 'Silver') {
                $next_tier = 'Gold';
                $points_needed = 5000 - $loyalty->total_points;
            } elseif ($loyalty->tier == 'Gold') {
                $next_tier = 'Platinum';
                $points_needed = 10000 - $loyalty->total_points;
            }

            if ($points_needed > 0):
            ?>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span><strong>Progression vers <?php echo $tier_icons[$next_tier]; ?> <?php echo $next_tier; ?></strong></span>
                    <span style="color: #667eea; font-weight: bold;">+<?php echo number_format($points_needed, 0, ',', ' '); ?> points requis</span>
                </div>
                <?php
                $progress = ($loyalty->total_points / ($loyalty->total_points + $points_needed)) * 100;
                ?>
                <div style="background: #ddd; height: 20px; border-radius: 10px; overflow: hidden;">
                    <div style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); height: 100%; width: <?php echo $progress; ?>%; transition: width 0.3s;"></div>
                </div>
            </div>
            <?php else: ?>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; color: #fff;">
                <div style="font-size: 48px;">🏆</div>
                <div style="font-size: 18px; font-weight: bold; margin-top: 10px;">Tier Maximum Atteint!</div>
                <div style="font-size: 14px; margin-top: 5px; opacity: 0.9;">Ce client fait partie de nos membres Platinum</div>
            </div>
            <?php endif; ?>

            <!-- Dernières transactions -->
            <?php if (!empty($transactions)): ?>
            <h4 style="margin: 20px 0 10px 0;"><span class="dashicons dashicons-list-view"></span> Dernières Transactions</h4>
            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Points</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $trans): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($trans->created_at)); ?></td>
                        <td>
                            <?php if ($trans->transaction_type == 'earn'): ?>
                                <span class="colis224-badge colis224-badge-green">Gagné</span>
                            <?php else: ?>
                                <span class="colis224-badge colis224-badge-payment-non-paye">Échangé</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="color: <?php echo $trans->points > 0 ? '#00a32a' : '#d63638'; ?>;">
                                <?php echo $trans->points > 0 ? '+' : ''; ?><?php echo number_format($trans->points, 0, ',', ' '); ?>
                            </strong>
                        </td>
                        <td><?php echo esc_html($trans->description); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Informations du programme -->
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea;">
                <p style="margin: 0;"><strong>📋 Programme:</strong> <?php echo esc_html($loyalty->program_name); ?></p>
                <p style="margin: 10px 0 0 0;"><small>
                    <strong>Date d'inscription:</strong> <?php echo date('d/m/Y', strtotime($loyalty->join_date)); ?> |
                    <strong>Dernière activité:</strong> <?php echo date('d/m/Y', strtotime($loyalty->last_activity_date)); ?>
                </small></p>
            </div>
        </div>
        <?php
    }
}
