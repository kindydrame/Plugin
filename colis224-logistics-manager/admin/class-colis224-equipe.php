<?php
/**
 * MODULE 4: Gestion de l'Équipe (Agents et Livreurs)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Team {

    public static function display_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'agents';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-groups"></span>
                Gestion de l'Équipe
            </h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=colis224-team&tab=agents" class="nav-tab <?php echo $tab === 'agents' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-businessman"></span> Agents de Bureau
                </a>
                <a href="?page=colis224-team&tab=drivers" class="nav-tab <?php echo $tab === 'drivers' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-car"></span> Livreurs
                </a>
            </nav>

            <?php
            if ($tab === 'agents') {
                self::display_agents();
            } else {
                self::display_drivers();
            }
            ?>
        </div>
        <?php
    }

    private static function display_agents() {
        global $wpdb;

        // Traiter les actions
        if (isset($_POST['action']) && $_POST['action'] === 'save_agent') {
            self::save_agent();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'update_agent') {
            self::update_agent();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            self::delete_agent($_GET['id']);
        }

        $table_team = $wpdb->prefix . 'colis224_team_members';

        // Vérifier si on est en mode édition
        $edit_mode = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
        $agent_to_edit = null;

        if ($edit_mode) {
            $agent_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_team WHERE id = %d", $_GET['id']));
        }

        $agents = $wpdb->get_results("SELECT * FROM $table_team ORDER BY created_at DESC");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3>
                <span class="dashicons dashicons-<?php echo $edit_mode ? 'edit' : 'plus-alt'; ?>"></span>
                <?php echo $edit_mode ? 'Modifier l\'Agent' : 'Ajouter un Agent'; ?>
            </h3>
            <form method="post">
                <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_agent' : 'save_agent'; ?>">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="agent_id" value="<?php echo $agent_to_edit->id; ?>">
                <?php endif; ?>
                <?php wp_nonce_field('colis224_agent_action', 'colis224_agent_nonce'); ?>

                <div class="colis224-form-grid">
                    <div class="colis224-form-group">
                        <label>Nom complet *</label>
                        <input type="text" name="name" value="<?php echo $edit_mode ? esc_attr($agent_to_edit->name) : ''; ?>" placeholder="Nom complet *" required>
                    </div>

                    <div class="colis224-form-group">
                        <label>Téléphone principal</label>
                        <input type="text" name="phone" value="<?php echo $edit_mode ? esc_attr($agent_to_edit->phone) : ''; ?>" placeholder="Téléphone principal">
                    </div>

                    <div class="colis224-form-group">
                        <label>Numéros supplémentaires</label>
                        <input type="text" name="additional_phones" value="<?php echo $edit_mode ? esc_attr($agent_to_edit->additional_phones) : ''; ?>" placeholder="Ex: +224611223344, +224622334455">
                        <small>Séparez les numéros par des virgules</small>
                    </div>

                    <div class="colis224-form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo $edit_mode ? esc_attr($agent_to_edit->email) : ''; ?>" placeholder="Email">
                    </div>

                    <div class="colis224-form-group">
                        <label>Rôle/Poste</label>
                        <input type="text" name="role" value="<?php echo $edit_mode ? esc_attr($agent_to_edit->role) : 'Agent'; ?>" placeholder="Rôle/Poste">
                    </div>

                    <div class="colis224-form-group">
                        <label>Salaire (GNF)</label>
                        <input type="number" name="salary" step="0.01" min="0" value="<?php echo $edit_mode ? esc_attr($agent_to_edit->salary) : ''; ?>" placeholder="Salaire (GNF)">
                    </div>

                    <?php if ($edit_mode): ?>
                    <div class="colis224-form-group">
                        <label>Statut</label>
                        <select name="is_active">
                            <option value="1" <?php selected($agent_to_edit->is_active, 1); ?>>Actif</option>
                            <option value="0" <?php selected($agent_to_edit->is_active, 0); ?>>Inactif</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="colis224-form-group colis224-full-width">
                        <button type="submit" class="button button-primary">
                            <?php echo $edit_mode ? 'Mettre à Jour' : 'Ajouter'; ?>
                        </button>
                        <?php if ($edit_mode): ?>
                            <a href="?page=colis224-team&tab=agents" class="button">Annuler</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-list-view"></span> Liste des Agents</h3>
            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Contact</th>
                        <th>Rôle</th>
                        <th>Salaire</th>
                        <th>Statut</th>
                        <th>Date d'embauche</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agents)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Aucun agent enregistré.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($agents as $agent): ?>
                        <tr>
                            <td><strong><?php echo esc_html($agent->name); ?></strong></td>
                            <td>
                                <?php echo esc_html($agent->phone ?: '-'); ?><br>
                                <small><?php echo esc_html($agent->email ?: ''); ?></small>
                            </td>
                            <td><span class="colis224-badge"><?php echo esc_html($agent->role); ?></span></td>
                            <td><?php echo number_format($agent->salary, 0, ',', ' '); ?> GNF</td>
                            <td>
                                <?php if ($agent->is_active): ?>
                                    <span class="colis224-badge colis224-badge-green">Actif</span>
                                <?php else: ?>
                                    <span class="colis224-badge colis224-badge-gray">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($agent->created_at)); ?></td>
                            <td class="colis224-actions">
                                <a href="?page=colis224-team&tab=agents&action=edit&id=<?php echo $agent->id; ?>"
                                   class="button button-small" title="Modifier">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="?page=colis224-team&tab=agents&action=delete&id=<?php echo $agent->id; ?>"
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet agent ?');"
                                   title="Supprimer">
                                    <span class="dashicons dashicons-trash"></span>
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

    private static function display_drivers() {
        global $wpdb;

        // Traiter les actions
        if (isset($_POST['action']) && $_POST['action'] === 'save_driver') {
            self::save_driver();
        } elseif (isset($_POST['action']) && $_POST['action'] === 'update_driver') {
            self::update_driver();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            self::delete_driver($_GET['id']);
        }

        $table_drivers = $wpdb->prefix . 'colis224_drivers';

        // Vérifier si on est en mode édition
        $edit_mode = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
        $driver_to_edit = null;

        if ($edit_mode) {
            $driver_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_drivers WHERE id = %d", $_GET['id']));
        }

        $drivers = $wpdb->get_results("SELECT * FROM $table_drivers ORDER BY created_at DESC");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h3>
                <span class="dashicons dashicons-<?php echo $edit_mode ? 'edit' : 'plus-alt'; ?>"></span>
                <?php echo $edit_mode ? 'Modifier le Livreur' : 'Ajouter un Livreur'; ?>
            </h3>
            <form method="post">
                <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update_driver' : 'save_driver'; ?>">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="driver_id" value="<?php echo $driver_to_edit->id; ?>">
                <?php endif; ?>
                <?php wp_nonce_field('colis224_driver_action', 'colis224_driver_nonce'); ?>

                <div class="colis224-form-grid">
                    <div class="colis224-form-group">
                        <label>Nom complet *</label>
                        <input type="text" name="name" value="<?php echo $edit_mode ? esc_attr($driver_to_edit->name) : ''; ?>" required>
                    </div>

                    <div class="colis224-form-group">
                        <label>Téléphone principal *</label>
                        <input type="text" name="phone" value="<?php echo $edit_mode ? esc_attr($driver_to_edit->phone) : ''; ?>" required>
                    </div>

                    <div class="colis224-form-group">
                        <label>Numéros supplémentaires</label>
                        <input type="text" name="additional_phones" value="<?php echo $edit_mode ? esc_attr($driver_to_edit->additional_phones) : ''; ?>" placeholder="Ex: +224611223344, +224622334455">
                        <small>Séparez les numéros par des virgules</small>
                    </div>

                    <div class="colis224-form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo $edit_mode ? esc_attr($driver_to_edit->email) : ''; ?>">
                    </div>

                    <div class="colis224-form-group">
                        <label>Zone d'intervention</label>
                        <input type="text" name="zone" value="<?php echo $edit_mode ? esc_attr($driver_to_edit->zone) : ''; ?>" placeholder="Ex: Conakry Centre">
                    </div>

                    <div class="colis224-form-group">
                        <label>Type de commission</label>
                        <select name="commission_type">
                            <option value="percentage" <?php echo $edit_mode && $driver_to_edit->commission_type === 'percentage' ? 'selected' : ''; ?>>Pourcentage (%)</option>
                            <option value="fixed" <?php echo $edit_mode && $driver_to_edit->commission_type === 'fixed' ? 'selected' : ''; ?>>Montant Fixe (GNF)</option>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label>Valeur commission</label>
                        <input type="number" name="commission_value" step="0.01" min="0"
                               value="<?php echo $edit_mode ? esc_attr($driver_to_edit->commission_value) : '0'; ?>">
                    </div>

                    <?php if ($edit_mode): ?>
                    <div class="colis224-form-group">
                        <label>Statut</label>
                        <select name="is_active">
                            <option value="1" <?php selected($driver_to_edit->is_active, 1); ?>>Actif</option>
                            <option value="0" <?php selected($driver_to_edit->is_active, 0); ?>>Inactif</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="colis224-form-group colis224-full-width">
                        <button type="submit" class="button button-primary">
                            <?php echo $edit_mode ? 'Mettre à Jour' : 'Ajouter Livreur'; ?>
                        </button>
                        <?php if ($edit_mode): ?>
                            <a href="?page=colis224-team&tab=drivers" class="button">Annuler</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="colis224-card">
            <h3><span class="dashicons dashicons-list-view"></span> Liste des Livreurs</h3>
            <table class="colis224-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Téléphone</th>
                        <th>Zone</th>
                        <th>Commission</th>
                        <th>Total Gagné</th>
                        <th>Payé</th>
                        <th>Solde</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($drivers)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">Aucun livreur enregistré.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($drivers as $driver): ?>
                        <tr>
                            <td><strong><?php echo esc_html($driver->name); ?></strong></td>
                            <td><?php echo esc_html($driver->phone); ?></td>
                            <td><?php echo esc_html($driver->zone ?: '-'); ?></td>
                            <td>
                                <?php
                                echo esc_html($driver->commission_value);
                                echo ($driver->commission_type === 'percentage') ? '%' : ' GNF';
                                ?>
                            </td>
                            <td><?php echo number_format($driver->total_earned, 0, ',', ' '); ?> GNF</td>
                            <td><?php echo number_format($driver->total_paid, 0, ',', ' '); ?> GNF</td>
                            <td>
                                <strong><?php echo number_format($driver->balance, 0, ',', ' '); ?> GNF</strong>
                            </td>
                            <td>
                                <?php if ($driver->is_active): ?>
                                    <span class="colis224-badge colis224-badge-green">Actif</span>
                                <?php else: ?>
                                    <span class="colis224-badge colis224-badge-gray">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td class="colis224-actions">
                                <a href="?page=colis224-team&tab=drivers&action=edit&id=<?php echo $driver->id; ?>"
                                   class="button button-small" title="Modifier">
                                    <span class="dashicons dashicons-edit"></span>
                                </a>
                                <a href="?page=colis224-team&tab=drivers&action=delete&id=<?php echo $driver->id; ?>"
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce livreur ?');"
                                   title="Supprimer">
                                    <span class="dashicons dashicons-trash"></span>
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

    private static function save_agent() {
        if (!isset($_POST['colis224_agent_nonce']) || !wp_verify_nonce($_POST['colis224_agent_nonce'], 'colis224_agent_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_team_members';

        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'additional_phones' => sanitize_text_field($_POST['additional_phones']),
            'email' => sanitize_email($_POST['email']),
            'role' => sanitize_text_field($_POST['role']),
            'salary' => floatval($_POST['salary'])
        );

        $wpdb->insert($table, $data);
        echo '<div class="notice notice-success is-dismissible"><p>Agent ajouté avec succès!</p></div>';
    }

    private static function update_agent() {
        if (!isset($_POST['colis224_agent_nonce']) || !wp_verify_nonce($_POST['colis224_agent_nonce'], 'colis224_agent_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_team_members';
        $agent_id = intval($_POST['agent_id']);

        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'additional_phones' => sanitize_text_field($_POST['additional_phones']),
            'email' => sanitize_email($_POST['email']),
            'role' => sanitize_text_field($_POST['role']),
            'salary' => floatval($_POST['salary']),
            'is_active' => intval($_POST['is_active'])
        );

        $wpdb->update($table, $data, array('id' => $agent_id));
        echo '<div class="notice notice-success is-dismissible"><p>Agent mis à jour avec succès!</p></div>';
    }

    private static function delete_agent($agent_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_team_members';

        $wpdb->delete($table, array('id' => intval($agent_id)));
        echo '<div class="notice notice-success is-dismissible"><p>Agent supprimé avec succès!</p></div>';
    }

    private static function save_driver() {
        if (!isset($_POST['colis224_driver_nonce']) || !wp_verify_nonce($_POST['colis224_driver_nonce'], 'colis224_driver_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_drivers';

        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'additional_phones' => sanitize_text_field($_POST['additional_phones']),
            'email' => sanitize_email($_POST['email']),
            'zone' => sanitize_text_field($_POST['zone']),
            'commission_type' => sanitize_text_field($_POST['commission_type']),
            'commission_value' => floatval($_POST['commission_value'])
        );

        $wpdb->insert($table, $data);
        echo '<div class="notice notice-success is-dismissible"><p>Livreur ajouté avec succès!</p></div>';
    }

    private static function update_driver() {
        if (!isset($_POST['colis224_driver_nonce']) || !wp_verify_nonce($_POST['colis224_driver_nonce'], 'colis224_driver_action')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_drivers';
        $driver_id = intval($_POST['driver_id']);

        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'additional_phones' => sanitize_text_field($_POST['additional_phones']),
            'email' => sanitize_email($_POST['email']),
            'zone' => sanitize_text_field($_POST['zone']),
            'commission_type' => sanitize_text_field($_POST['commission_type']),
            'commission_value' => floatval($_POST['commission_value']),
            'is_active' => intval($_POST['is_active'])
        );

        $wpdb->update($table, $data, array('id' => $driver_id));
        echo '<div class="notice notice-success is-dismissible"><p>Livreur mis à jour avec succès!</p></div>';
    }

    private static function delete_driver($driver_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_drivers';

        $wpdb->delete($table, array('id' => intval($driver_id)));
        echo '<div class="notice notice-success is-dismissible"><p>Livreur supprimé avec succès!</p></div>';
    }
}
