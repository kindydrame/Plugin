<?php
/**
 * Gestion des messages préenregistrés
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Message_Templates {

    public static function display_page() {
        global $wpdb;

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'messages';

        // Traiter les actions
        if (isset($_POST['save_category']) && check_admin_referer('colis224_save_category')) {
            self::save_category();
        }

        if (isset($_POST['save_message']) && check_admin_referer('colis224_save_message')) {
            self::save_message();
        }

        if (isset($_GET['delete_category']) && check_admin_referer('colis224_delete_category_' . $_GET['delete_category'])) {
            self::delete_category($_GET['delete_category']);
        }

        if (isset($_GET['delete_message']) && check_admin_referer('colis224_delete_message_' . $_GET['delete_message'])) {
            self::delete_message($_GET['delete_message']);
        }

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-email-alt"></span>
                💬 Messages Préenregistrés
            </h1>

            <!-- Onglets -->
            <nav class="nav-tab-wrapper">
                <a href="?page=colis224-message-templates&tab=messages"
                   class="nav-tab <?php echo $tab === 'messages' ? 'nav-tab-active' : ''; ?>">
                    📝 Messages
                </a>
                <a href="?page=colis224-message-templates&tab=categories"
                   class="nav-tab <?php echo $tab === 'categories' ? 'nav-tab-active' : ''; ?>">
                    📂 Catégories
                </a>
            </nav>

            <div style="margin-top: 20px;">
                <?php
                if ($tab === 'categories') {
                    if ($action === 'edit' || $action === 'add') {
                        self::render_category_form($action);
                    } else {
                        self::render_categories_list();
                    }
                } else {
                    if ($action === 'edit' || $action === 'add') {
                        self::render_message_form($action);
                    } else {
                        self::render_messages_list();
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Afficher la liste des messages
     */
    private static function render_messages_list() {
        global $wpdb;

        $table_messages = $wpdb->prefix . 'colis224_message_templates';
        $table_categories = $wpdb->prefix . 'colis224_message_categories';

        $messages = $wpdb->get_results(
            "SELECT m.*, c.name as category_name, c.color as category_color
            FROM {$table_messages} m
            LEFT JOIN {$table_categories} c ON m.category_id = c.id
            ORDER BY c.display_order ASC, m.title ASC"
        );

        ?>
        <div class="colis224-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">📝 Liste des Messages</h2>
                <a href="?page=colis224-message-templates&tab=messages&action=add" class="button button-primary">
                    ➕ Ajouter un message
                </a>
            </div>

            <div style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <p style="margin: 0;"><strong>💡 Astuce :</strong> Les messages préenregistrés vous permettent de gagner du temps en créant des modèles réutilisables pour vos communications.</p>
                <p style="margin: 5px 0 0 0;"><strong>Variables disponibles :</strong> {client_name}, {tracking_number}, {amount}, {delivery_date}, {phone}</p>
            </div>

            <?php if (empty($messages)): ?>
                <div class="colis224-empty-state">
                    <span class="dashicons dashicons-email" style="font-size: 64px; opacity: 0.3;"></span>
                    <p>Aucun message préenregistré</p>
                    <a href="?page=colis224-message-templates&tab=messages&action=add" class="button button-primary">
                        Créer le premier message
                    </a>
                </div>
            <?php else: ?>
                <table class="widefat colis224-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">📝</th>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Type</th>
                            <th>Utilisations</th>
                            <th>Statut</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                        <tr>
                            <td style="text-align: center;">
                                <?php
                                $icons = ['email' => '📧', 'sms' => '📱', 'whatsapp' => '💬', 'all' => '📢'];
                                echo $icons[$message->message_type] ?? '📝';
                                ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($message->title); ?></strong>
                                <?php if ($message->subject): ?>
                                    <div style="font-size: 12px; color: #666;">
                                        Sujet: <?php echo esc_html($message->subject); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="display: inline-block; background: <?php echo esc_attr($message->category_color); ?>; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px;">
                                    <?php echo esc_html($message->category_name); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $types = [
                                    'email' => 'Email',
                                    'sms' => 'SMS',
                                    'whatsapp' => 'WhatsApp',
                                    'all' => 'Tous'
                                ];
                                echo $types[$message->message_type] ?? 'N/A';
                                ?>
                            </td>
                            <td><?php echo number_format($message->usage_count); ?>×</td>
                            <td>
                                <?php if ($message->is_active): ?>
                                    <span style="color: #46b450;">✓ Actif</span>
                                <?php else: ?>
                                    <span style="color: #999;">✗ Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="?page=colis224-message-templates&tab=messages&action=edit&id=<?php echo $message->id; ?>"
                                   class="button button-small">
                                    ✏️ Modifier
                                </a>
                                <a href="<?php echo wp_nonce_url('?page=colis224-message-templates&tab=messages&delete_message=' . $message->id, 'colis224_delete_message_' . $message->id); ?>"
                                   class="button button-small"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                                    🗑️ Supprimer
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Afficher le formulaire de message
     */
    private static function render_message_form($action) {
        global $wpdb;

        $message = null;
        if ($action === 'edit' && isset($_GET['id'])) {
            $message = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}colis224_message_templates WHERE id = %d",
                intval($_GET['id'])
            ));
        }

        $categories = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}colis224_message_categories WHERE is_active = 1 ORDER BY display_order ASC"
        );

        ?>
        <div class="colis224-card">
            <h2><?php echo $action === 'edit' ? '✏️ Modifier le message' : '➕ Nouveau message'; ?></h2>

            <form method="post" action="">
                <?php wp_nonce_field('colis224_save_message'); ?>
                <input type="hidden" name="message_id" value="<?php echo $message ? $message->id : ''; ?>">

                <div class="colis224-form-grid">
                    <div class="colis224-form-group colis224-full-width">
                        <label for="title">Titre du message *</label>
                        <input type="text" name="title" id="title"
                               value="<?php echo $message ? esc_attr($message->title) : ''; ?>"
                               required>
                    </div>

                    <div class="colis224-form-group">
                        <label for="category_id">Catégorie *</label>
                        <select name="category_id" id="category_id" required>
                            <option value="">-- Sélectionner une catégorie --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->id; ?>"
                                    <?php echo ($message && $message->category_id == $cat->id) ? 'selected' : ''; ?>>
                                <?php echo esc_html($cat->name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="colis224-form-group">
                        <label for="message_type">Type de message *</label>
                        <select name="message_type" id="message_type" required>
                            <option value="all" <?php echo ($message && $message->message_type === 'all') ? 'selected' : ''; ?>>📢 Tous (Email + SMS + WhatsApp)</option>
                            <option value="email" <?php echo ($message && $message->message_type === 'email') ? 'selected' : ''; ?>>📧 Email uniquement</option>
                            <option value="sms" <?php echo ($message && $message->message_type === 'sms') ? 'selected' : ''; ?>>📱 SMS uniquement</option>
                            <option value="whatsapp" <?php echo ($message && $message->message_type === 'whatsapp') ? 'selected' : ''; ?>>💬 WhatsApp uniquement</option>
                        </select>
                    </div>

                    <div class="colis224-form-group colis224-full-width">
                        <label for="subject">Sujet (pour emails)</label>
                        <input type="text" name="subject" id="subject"
                               value="<?php echo $message ? esc_attr($message->subject) : ''; ?>"
                               placeholder="Ex: Votre colis {tracking_number} est disponible">
                        <small>Variables disponibles: {client_name}, {tracking_number}, {amount}, etc.</small>
                    </div>

                    <div class="colis224-form-group colis224-full-width">
                        <label for="message_body">Corps du message *</label>
                        <textarea name="message_body" id="message_body" rows="10" required><?php echo $message ? esc_textarea($message->message_body) : ''; ?></textarea>
                        <small>💡 Utilisez des variables : {client_name}, {tracking_number}, {amount}, {delivery_date}, {phone}</small>
                    </div>

                    <div class="colis224-form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1"
                                   <?php echo (!$message || $message->is_active) ? 'checked' : ''; ?>>
                            Activer ce message
                        </label>
                    </div>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" name="save_message" class="button button-primary">
                        💾 Enregistrer
                    </button>
                    <a href="?page=colis224-message-templates&tab=messages" class="button">
                        ❌ Annuler
                    </a>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Afficher la liste des catégories
     */
    private static function render_categories_list() {
        global $wpdb;

        $categories = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}colis224_message_categories ORDER BY display_order ASC"
        );

        ?>
        <div class="colis224-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0;">📂 Liste des Catégories</h2>
                <a href="?page=colis224-message-templates&tab=categories&action=add" class="button button-primary">
                    ➕ Ajouter une catégorie
                </a>
            </div>

            <?php if (empty($categories)): ?>
                <div class="colis224-empty-state">
                    <span class="dashicons dashicons-category" style="font-size: 64px; opacity: 0.3;"></span>
                    <p>Aucune catégorie</p>
                    <a href="?page=colis224-message-templates&tab=categories&action=add" class="button button-primary">
                        Créer la première catégorie
                    </a>
                </div>
            <?php else: ?>
                <table class="widefat colis224-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Ordre</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Couleur</th>
                            <th>Nb. messages</th>
                            <th>Statut</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat):
                            $message_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}colis224_message_templates WHERE category_id = %d",
                                $cat->id
                            ));
                        ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $cat->display_order; ?></td>
                            <td>
                                <span style="display: inline-block; background: <?php echo esc_attr($cat->color); ?>; color: white; padding: 5px 12px; border-radius: 15px; font-weight: bold;">
                                    <?php echo esc_html($cat->name); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($cat->description); ?></td>
                            <td>
                                <span style="display: inline-block; width: 30px; height: 30px; background: <?php echo esc_attr($cat->color); ?>; border-radius: 4px; border: 2px solid #ddd;"></span>
                                <code style="margin-left: 5px;"><?php echo esc_html($cat->color); ?></code>
                            </td>
                            <td><?php echo $message_count; ?> message(s)</td>
                            <td>
                                <?php if ($cat->is_active): ?>
                                    <span style="color: #46b450;">✓ Actif</span>
                                <?php else: ?>
                                    <span style="color: #999;">✗ Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="?page=colis224-message-templates&tab=categories&action=edit&id=<?php echo $cat->id; ?>"
                                   class="button button-small">
                                    ✏️ Modifier
                                </a>
                                <?php if ($message_count == 0): ?>
                                <a href="<?php echo wp_nonce_url('?page=colis224-message-templates&tab=categories&delete_category=' . $cat->id, 'colis224_delete_category_' . $cat->id); ?>"
                                   class="button button-small"
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                    🗑️ Supprimer
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Afficher le formulaire de catégorie
     */
    private static function render_category_form($action) {
        global $wpdb;

        $category = null;
        if ($action === 'edit' && isset($_GET['id'])) {
            $category = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}colis224_message_categories WHERE id = %d",
                intval($_GET['id'])
            ));
        }

        ?>
        <div class="colis224-card">
            <h2><?php echo $action === 'edit' ? '✏️ Modifier la catégorie' : '➕ Nouvelle catégorie'; ?></h2>

            <form method="post" action="">
                <?php wp_nonce_field('colis224_save_category'); ?>
                <input type="hidden" name="category_id" value="<?php echo $category ? $category->id : ''; ?>">

                <div class="colis224-form-grid">
                    <div class="colis224-form-group">
                        <label for="name">Nom de la catégorie *</label>
                        <input type="text" name="name" id="name"
                               value="<?php echo $category ? esc_attr($category->name) : ''; ?>"
                               required>
                    </div>

                    <div class="colis224-form-group">
                        <label for="display_order">Ordre d'affichage</label>
                        <input type="number" name="display_order" id="display_order"
                               value="<?php echo $category ? esc_attr($category->display_order) : '0'; ?>"
                               min="0" step="1">
                    </div>

                    <div class="colis224-form-group colis224-full-width">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" rows="3"><?php echo $category ? esc_textarea($category->description) : ''; ?></textarea>
                    </div>

                    <div class="colis224-form-group">
                        <label for="color">Couleur</label>
                        <input type="color" name="color" id="color"
                               value="<?php echo $category ? esc_attr($category->color) : '#0073aa'; ?>">
                    </div>

                    <div class="colis224-form-group">
                        <label>
                            <input type="checkbox" name="is_active" value="1"
                                   <?php echo (!$category || $category->is_active) ? 'checked' : ''; ?>>
                            Activer cette catégorie
                        </label>
                    </div>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" name="save_category" class="button button-primary">
                        💾 Enregistrer
                    </button>
                    <a href="?page=colis224-message-templates&tab=categories" class="button">
                        ❌ Annuler
                    </a>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Sauvegarder une catégorie
     */
    private static function save_category() {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_message_categories';
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'color' => sanitize_hex_color($_POST['color']),
            'display_order' => intval($_POST['display_order']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );

        if ($category_id > 0) {
            $wpdb->update($table, $data, array('id' => $category_id));
            echo '<div class="notice notice-success"><p>✅ Catégorie mise à jour avec succès !</p></div>';
        } else {
            $wpdb->insert($table, $data);
            echo '<div class="notice notice-success"><p>✅ Catégorie créée avec succès !</p></div>';
        }
    }

    /**
     * Sauvegarder un message
     */
    private static function save_message() {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_message_templates';
        $message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;

        $data = array(
            'category_id' => intval($_POST['category_id']),
            'title' => sanitize_text_field($_POST['title']),
            'subject' => sanitize_text_field($_POST['subject']),
            'message_body' => sanitize_textarea_field($_POST['message_body']),
            'message_type' => sanitize_text_field($_POST['message_type']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );

        if ($message_id > 0) {
            $wpdb->update($table, $data, array('id' => $message_id));
            echo '<div class="notice notice-success"><p>✅ Message mis à jour avec succès !</p></div>';
        } else {
            $data['created_by'] = get_current_user_id();
            $wpdb->insert($table, $data);
            echo '<div class="notice notice-success"><p>✅ Message créé avec succès !</p></div>';
        }
    }

    /**
     * Supprimer une catégorie
     */
    private static function delete_category($id) {
        global $wpdb;

        $wpdb->delete($wpdb->prefix . 'colis224_message_categories', array('id' => intval($id)));
        echo '<div class="notice notice-success"><p>✅ Catégorie supprimée avec succès !</p></div>';
    }

    /**
     * Supprimer un message
     */
    private static function delete_message($id) {
        global $wpdb;

        $wpdb->delete($wpdb->prefix . 'colis224_message_templates', array('id' => intval($id)));
        echo '<div class="notice notice-success"><p>✅ Message supprimé avec succès !</p></div>';
    }

    /**
     * Obtenir les messages par catégorie (pour utilisation AJAX)
     */
    public static function get_messages_by_category($category_id = null) {
        global $wpdb;

        $table = $wpdb->prefix . 'colis224_message_templates';

        if ($category_id) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE category_id = %d AND is_active = 1 ORDER BY title ASC",
                $category_id
            ));
        } else {
            return $wpdb->get_results(
                "SELECT * FROM {$table} WHERE is_active = 1 ORDER BY title ASC"
            );
        }
    }

    /**
     * Remplacer les variables dans un message
     */
    public static function replace_variables($message, $variables) {
        foreach ($variables as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        return $message;
    }
}
