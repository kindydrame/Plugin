<?php
/**
 * MODULE: Gestion des Pays
 * Page d'administration pour gérer dynamiquement la liste des pays
 * (provenance et destination)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Countries_Admin {

    public static function display_page() {
        // Traitement des actions
        if (isset($_POST['action']) && $_POST['action'] === 'add_country') {
            self::save_country();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'edit_country') {
            self::update_country();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            self::delete_country($_GET['id']);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
            self::toggle_status($_GET['id']);
        }

        // Déterminer la vue
        if (isset($_GET['action']) && $_GET['action'] === 'add') {
            self::display_form();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            self::display_form($_GET['id']);
        } else {
            self::display_list();
        }
    }

    private static function display_list() {
        global $wpdb;
        $table_countries = $wpdb->prefix . 'colis224_countries';

        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        $where = "1=1";
        if (!empty($search)) {
            $where .= $wpdb->prepare(
                " AND (name LIKE %s OR code LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        if ($status_filter !== '') {
            $where .= $wpdb->prepare(" AND is_active = %d", $status_filter);
        }

        $countries = $wpdb->get_results("SELECT * FROM $table_countries WHERE $where ORDER BY name ASC");

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-admin-site"></span>
                Gestion des Pays
                <a href="?page=colis224-countries&action=add" class="page-title-action">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Nouveau Pays
                </a>
            </h1>

            <div class="colis224-filters">
                <form method="get">
                    <input type="hidden" name="page" value="colis224-countries">
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>"
                           placeholder="Rechercher un pays..." class="colis224-search-input">
                    <select name="status">
                        <option value="">Tous les statuts</option>
                        <option value="1" <?php selected($status_filter, '1'); ?>>Actifs</option>
                        <option value="0" <?php selected($status_filter, '0'); ?>>Désactivés</option>
                    </select>
                    <button type="submit" class="button">Filtrer</button>
                    <a href="?page=colis224-countries" class="button">Réinitialiser</a>
                </form>
            </div>

            <div class="colis224-card">
                <table class="colis224-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Code ISO</th>
                            <th>Statut</th>
                            <th>Utilisation</th>
                            <th>Date de création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($countries)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Aucun pays trouvé.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($countries as $country):
                                // Compter l'utilisation du pays
                                $usage_count = self::get_country_usage($country->id);
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($country->name); ?></strong></td>
                                <td><?php echo esc_html($country->code ? $country->code : '-'); ?></td>
                                <td>
                                    <?php if ($country->is_active): ?>
                                        <span class="colis224-badge colis224-badge-success">Actif</span>
                                    <?php else: ?>
                                        <span class="colis224-badge colis224-badge-warning">Désactivé</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="dashicons dashicons-archive"></span>
                                    <?php echo number_format($usage_count); ?> colis
                                </td>
                                <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($country->created_at))); ?></td>
                                <td>
                                    <a href="?page=colis224-countries&action=edit&id=<?php echo $country->id; ?>"
                                       class="button button-small" title="Modifier">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <a href="?page=colis224-countries&action=toggle_status&id=<?php echo $country->id; ?>&_wpnonce=<?php echo wp_create_nonce('toggle_country_' . $country->id); ?>"
                                       class="button button-small"
                                       title="<?php echo $country->is_active ? 'Désactiver' : 'Activer'; ?>"
                                       onclick="return confirm('Voulez-vous vraiment <?php echo $country->is_active ? 'désactiver' : 'activer'; ?> ce pays ?');">
                                        <span class="dashicons dashicons-<?php echo $country->is_active ? 'hidden' : 'visibility'; ?>"></span>
                                    </a>
                                    <?php if ($usage_count == 0): ?>
                                    <a href="?page=colis224-countries&action=delete&id=<?php echo $country->id; ?>&_wpnonce=<?php echo wp_create_nonce('delete_country_' . $country->id); ?>"
                                       class="button button-small button-link-delete"
                                       title="Supprimer"
                                       onclick="return confirm('Voulez-vous vraiment supprimer ce pays ? Cette action est irréversible.');">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                    <?php else: ?>
                                    <span class="button button-small button-disabled" title="Impossible de supprimer (pays utilisé)">
                                        <span class="dashicons dashicons-trash"></span>
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <style>
            .colis224-wrap {
                margin: 20px 20px 0 0;
            }
            .colis224-title {
                font-size: 23px;
                font-weight: 400;
                margin: 0 0 20px;
                padding: 9px 0 4px;
                line-height: 1.3;
            }
            .colis224-title .dashicons {
                font-size: 28px;
                vertical-align: middle;
                margin-right: 5px;
            }
            .colis224-filters {
                background: #fff;
                padding: 15px;
                margin-bottom: 20px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
            }
            .colis224-search-input {
                min-width: 300px;
                padding: 6px 10px;
            }
            .colis224-card {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                overflow: hidden;
            }
            .colis224-table {
                width: 100%;
                border-collapse: collapse;
            }
            .colis224-table th {
                background: #f0f0f1;
                padding: 12px;
                text-align: left;
                font-weight: 600;
                border-bottom: 1px solid #c3c4c7;
            }
            .colis224-table td {
                padding: 12px;
                border-bottom: 1px solid #dcdcde;
            }
            .colis224-table tbody tr:hover {
                background: #f6f7f7;
            }
            .colis224-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
            }
            .colis224-badge-success {
                background: #d4edda;
                color: #155724;
            }
            .colis224-badge-warning {
                background: #fff3cd;
                color: #856404;
            }
            .button-disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
        </style>
        <?php
    }

    private static function display_form($country_id = null) {
        global $wpdb;
        $table_countries = $wpdb->prefix . 'colis224_countries';

        $country = null;
        if ($country_id) {
            $country = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_countries WHERE id = %d",
                $country_id
            ));

            if (!$country) {
                echo '<div class="notice notice-error"><p>Pays introuvable.</p></div>';
                return;
            }
        }

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-admin-site"></span>
                <?php echo $country ? 'Modifier le Pays' : 'Nouveau Pays'; ?>
            </h1>

            <div class="colis224-card" style="max-width: 800px;">
                <form method="post" action="?page=colis224-countries">
                    <?php wp_nonce_field('colis224_country_form', 'colis224_country_nonce'); ?>
                    <input type="hidden" name="action" value="<?php echo $country ? 'edit_country' : 'add_country'; ?>">
                    <?php if ($country): ?>
                    <input type="hidden" name="country_id" value="<?php echo $country->id; ?>">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="name">Nom du pays <span style="color: red;">*</span></label>
                            </th>
                            <td>
                                <input type="text" name="name" id="name"
                                       value="<?php echo $country ? esc_attr($country->name) : ''; ?>"
                                       class="regular-text" required>
                                <p class="description">Exemple: France, Sénégal, Chine</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="code">Code ISO</label>
                            </th>
                            <td>
                                <input type="text" name="code" id="code"
                                       value="<?php echo $country ? esc_attr($country->code) : ''; ?>"
                                       class="regular-text" maxlength="10">
                                <p class="description">Code ISO du pays (optionnel). Exemple: FR, SN, CN</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="is_active">Statut</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="is_active" id="is_active" value="1"
                                           <?php checked($country ? $country->is_active : 1, 1); ?>>
                                    Pays actif (visible dans les formulaires)
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-yes"></span>
                            <?php echo $country ? 'Mettre à jour' : 'Ajouter le pays'; ?>
                        </button>
                        <a href="?page=colis224-countries" class="button">
                            <span class="dashicons dashicons-no-alt"></span>
                            Annuler
                        </a>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    private static function save_country() {
        // Vérification de sécurité
        if (!isset($_POST['colis224_country_nonce']) ||
            !wp_verify_nonce($_POST['colis224_country_nonce'], 'colis224_country_form')) {
            wp_die('Action non autorisée.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Vous n\'avez pas les permissions nécessaires.');
        }

        global $wpdb;
        $table_countries = $wpdb->prefix . 'colis224_countries';

        $name = sanitize_text_field($_POST['name']);
        $code = sanitize_text_field($_POST['code']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validation
        if (empty($name)) {
            echo '<div class="notice notice-error"><p>Le nom du pays est obligatoire.</p></div>';
            return;
        }

        // Vérifier si le pays existe déjà
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_countries WHERE name = %s",
            $name
        ));

        if ($existing) {
            echo '<div class="notice notice-error"><p>Ce pays existe déjà.</p></div>';
            return;
        }

        // Insertion
        $result = $wpdb->insert(
            $table_countries,
            array(
                'name' => $name,
                'code' => $code,
                'is_active' => $is_active,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s')
        );

        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Pays ajouté avec succès.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Erreur lors de l\'ajout du pays.</p></div>';
        }
    }

    private static function update_country() {
        // Vérification de sécurité
        if (!isset($_POST['colis224_country_nonce']) ||
            !wp_verify_nonce($_POST['colis224_country_nonce'], 'colis224_country_form')) {
            wp_die('Action non autorisée.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Vous n\'avez pas les permissions nécessaires.');
        }

        global $wpdb;
        $table_countries = $wpdb->prefix . 'colis224_countries';

        $country_id = intval($_POST['country_id']);
        $name = sanitize_text_field($_POST['name']);
        $code = sanitize_text_field($_POST['code']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Validation
        if (empty($name)) {
            echo '<div class="notice notice-error"><p>Le nom du pays est obligatoire.</p></div>';
            return;
        }

        // Vérifier si un autre pays existe avec ce nom
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_countries WHERE name = %s AND id != %d",
            $name, $country_id
        ));

        if ($existing) {
            echo '<div class="notice notice-error"><p>Un autre pays existe déjà avec ce nom.</p></div>';
            return;
        }

        // Mise à jour
        $result = $wpdb->update(
            $table_countries,
            array(
                'name' => $name,
                'code' => $code,
                'is_active' => $is_active
            ),
            array('id' => $country_id),
            array('%s', '%s', '%d'),
            array('%d')
        );

        if ($result !== false) {
            echo '<div class="notice notice-success is-dismissible"><p>Pays mis à jour avec succès.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Erreur lors de la mise à jour du pays.</p></div>';
        }
    }

    private static function delete_country($country_id) {
        // Vérification de sécurité
        if (!isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'delete_country_' . $country_id)) {
            wp_die('Action non autorisée.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Vous n\'avez pas les permissions nécessaires.');
        }

        global $wpdb;
        $table_countries = $wpdb->prefix . 'colis224_countries';

        $country_id = intval($country_id);

        // Vérifier si le pays est utilisé
        $usage_count = self::get_country_usage($country_id);

        if ($usage_count > 0) {
            echo '<div class="notice notice-error"><p>Impossible de supprimer ce pays car il est utilisé par ' . $usage_count . ' colis.</p></div>';
            self::display_list();
            return;
        }

        // Suppression
        $result = $wpdb->delete(
            $table_countries,
            array('id' => $country_id),
            array('%d')
        );

        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Pays supprimé avec succès.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Erreur lors de la suppression du pays.</p></div>';
        }
    }

    private static function toggle_status($country_id) {
        // Vérification de sécurité
        if (!isset($_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'toggle_country_' . $country_id)) {
            wp_die('Action non autorisée.');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Vous n\'avez pas les permissions nécessaires.');
        }

        global $wpdb;
        $table_countries = $wpdb->prefix . 'colis224_countries';

        $country_id = intval($country_id);

        // Récupérer le statut actuel
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM $table_countries WHERE id = %d",
            $country_id
        ));

        if ($current_status === null) {
            echo '<div class="notice notice-error"><p>Pays introuvable.</p></div>';
            self::display_list();
            return;
        }

        // Inverser le statut
        $new_status = $current_status ? 0 : 1;

        $result = $wpdb->update(
            $table_countries,
            array('is_active' => $new_status),
            array('id' => $country_id),
            array('%d'),
            array('%d')
        );

        if ($result !== false) {
            $message = $new_status ? 'Pays activé avec succès.' : 'Pays désactivé avec succès.';
            echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Erreur lors du changement de statut.</p></div>';
        }
    }

    /**
     * Compter le nombre de colis utilisant ce pays
     */
    private static function get_country_usage($country_id) {
        global $wpdb;
        $table_parcels = $wpdb->prefix . 'colis224_parcels';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_parcels
            WHERE origin_country_id = %d OR destination_country_id = %d",
            $country_id, $country_id
        ));

        return intval($count);
    }
}
