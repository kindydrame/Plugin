<?php
/**
 * Interface Admin - Gestion des Tickets Support/SAV
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Support_Admin {

    public static function display_page() {
        require_once COLIS224_PLUGIN_DIR . 'includes/class-colis224-support-tickets.php';

        // Traitement des actions
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create_ticket':
                    self::handle_create_ticket();
                    break;
                case 'reply_ticket':
                    self::handle_reply_ticket();
                    break;
                case 'update_status':
                    self::handle_update_status();
                    break;
                case 'assign_ticket':
                    self::handle_assign_ticket();
                    break;
            }
        }

        // Déterminer la vue
        if (isset($_GET['action']) && $_GET['action'] === 'add') {
            self::display_form();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
            self::display_ticket_details($_GET['id']);
        } else {
            self::display_list();
        }
    }

    /**
     * Liste des tickets
     */
    private static function display_list() {
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $priority_filter = isset($_GET['priority']) ? sanitize_text_field($_GET['priority']) : '';
        $category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

        $filters = array();
        if ($status_filter) $filters['status'] = $status_filter;
        if ($priority_filter) $filters['priority'] = $priority_filter;
        if ($category_filter) $filters['category'] = $category_filter;

        $tickets = Colis224_Support_Tickets::get_all_tickets($filters);
        $stats = Colis224_Support_Tickets::get_stats();

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-sos"></span>
                Tickets Support / SAV
                <a href="?page=colis224-support&action=add" class="page-title-action">
                    <span class="dashicons dashicons-plus-alt"></span> Nouveau Ticket
                </a>
            </h1>

            <!-- Statistiques rapides -->
            <div class="colis224-dashboard-grid colis224-grid-6">
                <div class="colis224-card colis224-card-light">
                    <h4><span class="dashicons dashicons-tickets-alt"></span> Total</h4>
                    <p class="colis224-big-number"><?php echo $stats['total']; ?></p>
                </div>
                <div class="colis224-card colis224-card-blue">
                    <h4><span class="dashicons dashicons-unlock"></span> Ouverts</h4>
                    <p class="colis224-big-number"><?php echo $stats['ouvert']; ?></p>
                </div>
                <div class="colis224-card colis224-card-orange">
                    <h4><span class="dashicons dashicons-update"></span> En cours</h4>
                    <p class="colis224-big-number"><?php echo $stats['en_cours']; ?></p>
                </div>
                <div class="colis224-card colis224-card-green">
                    <h4><span class="dashicons dashicons-yes-alt"></span> Résolus</h4>
                    <p class="colis224-big-number"><?php echo $stats['resolu']; ?></p>
                </div>
                <div class="colis224-card colis224-card-purple">
                    <h4><span class="dashicons dashicons-lock"></span> Fermés</h4>
                    <p class="colis224-big-number"><?php echo $stats['ferme']; ?></p>
                </div>
                <div class="colis224-card colis224-card-red">
                    <h4><span class="dashicons dashicons-warning"></span> Urgents</h4>
                    <p class="colis224-big-number"><?php echo $stats['urgente']; ?></p>
                </div>
            </div>

            <!-- Filtres -->
            <div class="colis224-filters">
                <form method="get">
                    <input type="hidden" name="page" value="colis224-support">

                    <select name="status">
                        <option value="">Tous les statuts</option>
                        <option value="ouvert" <?php selected($status_filter, 'ouvert'); ?>>Ouvert</option>
                        <option value="en_cours" <?php selected($status_filter, 'en_cours'); ?>>En cours</option>
                        <option value="resolu" <?php selected($status_filter, 'resolu'); ?>>Résolu</option>
                        <option value="ferme" <?php selected($status_filter, 'ferme'); ?>>Fermé</option>
                    </select>

                    <select name="priority">
                        <option value="">Toutes priorités</option>
                        <option value="basse" <?php selected($priority_filter, 'basse'); ?>>Basse</option>
                        <option value="normale" <?php selected($priority_filter, 'normale'); ?>>Normale</option>
                        <option value="haute" <?php selected($priority_filter, 'haute'); ?>>Haute</option>
                        <option value="urgente" <?php selected($priority_filter, 'urgente'); ?>>Urgente</option>
                    </select>

                    <select name="category">
                        <option value="">Toutes catégories</option>
                        <option value="colis" <?php selected($category_filter, 'colis'); ?>>Colis</option>
                        <option value="paiement" <?php selected($category_filter, 'paiement'); ?>>Paiement</option>
                        <option value="reclamation" <?php selected($category_filter, 'reclamation'); ?>>Réclamation</option>
                        <option value="information" <?php selected($category_filter, 'information'); ?>>Information</option>
                        <option value="technique" <?php selected($category_filter, 'technique'); ?>>Technique</option>
                        <option value="autre" <?php selected($category_filter, 'autre'); ?>>Autre</option>
                    </select>

                    <button type="submit" class="button">Filtrer</button>
                    <a href="?page=colis224-support" class="button">Réinitialiser</a>
                </form>
            </div>

            <!-- Liste des tickets -->
            <div class="colis224-card">
                <table class="colis224-table">
                    <thead>
                        <tr>
                            <th>N° Ticket</th>
                            <th>Client</th>
                            <th>Sujet</th>
                            <th>Catégorie</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th>Créé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">Aucun ticket trouvé.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><strong><?php echo esc_html($ticket->ticket_number); ?></strong></td>
                                <td>
                                    <?php echo esc_html($ticket->client_name ?: 'Client anonyme'); ?><br>
                                    <small><?php echo esc_html($ticket->client_phone ?: '-'); ?></small>
                                </td>
                                <td><?php echo esc_html($ticket->subject); ?></td>
                                <td>
                                    <span class="colis224-badge">
                                        <?php
                                        $categories = array(
                                            'colis' => '📦 Colis',
                                            'paiement' => '💰 Paiement',
                                            'reclamation' => '⚠️ Réclamation',
                                            'information' => 'ℹ️ Information',
                                            'technique' => '🔧 Technique',
                                            'autre' => '📋 Autre'
                                        );
                                        echo $categories[$ticket->category] ?? $ticket->category;
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="colis224-badge colis224-badge-<?php
                                        echo $ticket->priority === 'urgente' ? 'red' :
                                            ($ticket->priority === 'haute' ? 'orange' :
                                            ($ticket->priority === 'normale' ? 'blue' : 'light'));
                                    ?>">
                                        <?php
                                        $priorities = array(
                                            'basse' => '⬇️ Basse',
                                            'normale' => '➡️ Normale',
                                            'haute' => '⬆️ Haute',
                                            'urgente' => '🚨 Urgente'
                                        );
                                        echo $priorities[$ticket->priority] ?? $ticket->priority;
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="colis224-badge colis224-badge-<?php
                                        echo $ticket->status === 'resolu' ? 'green' :
                                            ($ticket->status === 'en_cours' ? 'orange' :
                                            ($ticket->status === 'ferme' ? 'purple' : 'blue'));
                                    ?>">
                                        <?php
                                        $statuses = array(
                                            'ouvert' => 'Ouvert',
                                            'en_cours' => 'En cours',
                                            'resolu' => 'Résolu',
                                            'ferme' => 'Fermé'
                                        );
                                        echo $statuses[$ticket->status] ?? $ticket->status;
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($ticket->created_at)); ?></td>
                                <td class="colis224-actions">
                                    <a href="?page=colis224-support&action=view&id=<?php echo $ticket->id; ?>"
                                       class="button button-small button-primary">
                                        <span class="dashicons dashicons-visibility"></span> Voir
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
     * Formulaire de création de ticket
     */
    private static function display_form() {
        global $wpdb;

        $clients = $wpdb->get_results("SELECT id, name, phone FROM {$wpdb->prefix}colis224_clients ORDER BY name");
        $parcels = $wpdb->get_results("SELECT id, tracking_number FROM {$wpdb->prefix}colis224_parcels ORDER BY created_at DESC LIMIT 100");

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-sos"></span>
                Nouveau Ticket Support
                <a href="?page=colis224-support" class="page-title-action">
                    <span class="dashicons dashicons-arrow-left-alt"></span> Retour à la liste
                </a>
            </h1>

            <div class="colis224-card">
                <form method="post">
                    <input type="hidden" name="action" value="create_ticket">
                    <?php wp_nonce_field('colis224_ticket_action', 'colis224_ticket_nonce'); ?>

                    <div class="colis224-form-grid">
                        <!-- Client -->
                        <div class="colis224-form-group">
                            <label for="client_id">Client (optionnel)</label>
                            <select name="client_id" id="client_id">
                                <option value="">Sélectionner un client</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client->id; ?>">
                                    <?php echo esc_html($client->name . ' - ' . $client->phone); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Colis -->
                        <div class="colis224-form-group">
                            <label for="parcel_id">Colis concerné (optionnel)</label>
                            <select name="parcel_id" id="parcel_id">
                                <option value="">Aucun colis</option>
                                <?php foreach ($parcels as $parcel): ?>
                                <option value="<?php echo $parcel->id; ?>">
                                    <?php echo esc_html($parcel->tracking_number); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Catégorie -->
                        <div class="colis224-form-group">
                            <label for="category">Catégorie *</label>
                            <select name="category" id="category" required>
                                <option value="colis">📦 Colis</option>
                                <option value="paiement">💰 Paiement</option>
                                <option value="reclamation">⚠️ Réclamation</option>
                                <option value="information">ℹ️ Information</option>
                                <option value="technique">🔧 Technique</option>
                                <option value="autre">📋 Autre</option>
                            </select>
                        </div>

                        <!-- Priorité -->
                        <div class="colis224-form-group">
                            <label for="priority">Priorité *</label>
                            <select name="priority" id="priority" required>
                                <option value="basse">⬇️ Basse</option>
                                <option value="normale" selected>➡️ Normale</option>
                                <option value="haute">⬆️ Haute</option>
                                <option value="urgente">🚨 Urgente</option>
                            </select>
                        </div>

                        <!-- Sujet -->
                        <div class="colis224-form-group colis224-full-width">
                            <label for="subject">Sujet *</label>
                            <input type="text" name="subject" id="subject" required
                                   placeholder="Brève description du problème">
                        </div>

                        <!-- Message -->
                        <div class="colis224-form-group colis224-full-width">
                            <label for="message">Message *</label>
                            <textarea name="message" id="message" rows="6" required
                                      placeholder="Décrivez votre demande ou problème en détail..."></textarea>
                        </div>
                    </div>

                    <div class="colis224-form-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-saved"></span>
                            Créer le Ticket
                        </button>
                        <a href="?page=colis224-support" class="button button-large">
                            <span class="dashicons dashicons-no-alt"></span>
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Détails d'un ticket
     */
    private static function display_ticket_details($ticket_id) {
        $ticket = Colis224_Support_Tickets::get_ticket($ticket_id);

        if (!$ticket) {
            echo '<div class="notice notice-error"><p>Ticket introuvable.</p></div>';
            return;
        }

        $messages = Colis224_Support_Tickets::get_ticket_messages($ticket_id, true);
        $team_members = get_users(array('role' => 'administrator'));

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-sos"></span>
                Ticket: <?php echo esc_html($ticket->ticket_number); ?>
                <a href="?page=colis224-support" class="page-title-action">
                    <span class="dashicons dashicons-arrow-left-alt"></span> Retour
                </a>
            </h1>

            <div class="colis224-details-grid">
                <!-- Informations du ticket -->
                <div class="colis224-card">
                    <h3><span class="dashicons dashicons-info"></span> Informations</h3>
                    <table class="colis224-details-table">
                        <tr>
                            <th>N° Ticket:</th>
                            <td><strong><?php echo esc_html($ticket->ticket_number); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Client:</th>
                            <td>
                                <?php echo esc_html($ticket->client_name ?: 'Client anonyme'); ?><br>
                                <small><?php echo esc_html($ticket->client_phone ?: '-'); ?></small><br>
                                <small><?php echo esc_html($ticket->client_email ?: '-'); ?></small>
                            </td>
                        </tr>
                        <?php if ($ticket->tracking_number): ?>
                        <tr>
                            <th>Colis:</th>
                            <td><strong><?php echo esc_html($ticket->tracking_number); ?></strong></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Sujet:</th>
                            <td><strong><?php echo esc_html($ticket->subject); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Catégorie:</th>
                            <td><?php echo esc_html($ticket->category); ?></td>
                        </tr>
                        <tr>
                            <th>Priorité:</th>
                            <td>
                                <span class="colis224-badge colis224-badge-<?php
                                    echo $ticket->priority === 'urgente' ? 'red' :
                                        ($ticket->priority === 'haute' ? 'orange' : 'blue');
                                ?>">
                                    <?php echo esc_html($ticket->priority); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Statut:</th>
                            <td>
                                <span class="colis224-badge colis224-badge-<?php
                                    echo $ticket->status === 'resolu' ? 'green' :
                                        ($ticket->status === 'en_cours' ? 'orange' : 'blue');
                                ?>">
                                    <?php echo esc_html($ticket->status); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Créé le:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($ticket->created_at)); ?></td>
                        </tr>
                        <?php if ($ticket->resolved_at): ?>
                        <tr>
                            <th>Résolu le:</th>
                            <td><?php echo date('d/m/Y H:i', strtotime($ticket->resolved_at)); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Actions rapides -->
                <div class="colis224-card">
                    <h3><span class="dashicons dashicons-admin-tools"></span> Actions Rapides</h3>

                    <!-- Changer statut -->
                    <form method="post" style="margin-bottom: 15px;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket->id; ?>">
                        <?php wp_nonce_field('colis224_ticket_action', 'colis224_ticket_nonce'); ?>

                        <label><strong>Changer le statut:</strong></label>
                        <select name="status" class="regular-text" style="margin-top: 5px;">
                            <option value="ouvert" <?php selected($ticket->status, 'ouvert'); ?>>Ouvert</option>
                            <option value="en_cours" <?php selected($ticket->status, 'en_cours'); ?>>En cours</option>
                            <option value="resolu" <?php selected($ticket->status, 'resolu'); ?>>Résolu</option>
                            <option value="ferme" <?php selected($ticket->status, 'ferme'); ?>>Fermé</option>
                        </select>
                        <button type="submit" class="button button-primary" style="margin-top: 5px;">Mettre à jour</button>
                    </form>

                    <!-- Assigner -->
                    <form method="post">
                        <input type="hidden" name="action" value="assign_ticket">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket->id; ?>">
                        <?php wp_nonce_field('colis224_ticket_action', 'colis224_ticket_nonce'); ?>

                        <label><strong>Assigner à:</strong></label>
                        <select name="assigned_to" class="regular-text" style="margin-top: 5px;">
                            <option value="">Non assigné</option>
                            <?php foreach ($team_members as $member): ?>
                            <option value="<?php echo $member->ID; ?>" <?php selected($ticket->assigned_to, $member->ID); ?>>
                                <?php echo esc_html($member->display_name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="button" style="margin-top: 5px;">Assigner</button>
                    </form>
                </div>
            </div>

            <!-- Messages du ticket -->
            <div class="colis224-card" style="margin-top: 20px;">
                <h3><span class="dashicons dashicons-email"></span> Conversation</h3>

                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; max-height: 500px; overflow-y: auto; margin-bottom: 20px;">
                    <?php foreach ($messages as $msg): ?>
                    <div style="background: <?php echo $msg->user_type === 'client' ? '#fff' : ($msg->is_internal ? '#fff3cd' : '#e7f3ff'); ?>;
                                padding: 15px; border-radius: 8px; margin-bottom: 15px;
                                border-left: 4px solid <?php echo $msg->user_type === 'client' ? '#667eea' : ($msg->is_internal ? '#ffc107' : '#2271b1'); ?>;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <strong>
                                <?php echo $msg->user_type === 'client' ? '👤 Client' : '👨‍💼 Support'; ?>
                                <?php if ($msg->is_internal): ?>
                                    <span style="background: #ffc107; color: #000; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 5px;">NOTE INTERNE</span>
                                <?php endif; ?>
                            </strong>
                            <small style="color: #666;"><?php echo date('d/m/Y H:i', strtotime($msg->created_at)); ?></small>
                        </div>
                        <div><?php echo nl2br(esc_html($msg->message)); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Formulaire de réponse -->
                <form method="post">
                    <input type="hidden" name="action" value="reply_ticket">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket->id; ?>">
                    <?php wp_nonce_field('colis224_ticket_action', 'colis224_ticket_nonce'); ?>

                    <textarea name="message" rows="5" class="large-text" placeholder="Votre réponse..." required></textarea>

                    <div style="margin-top: 10px;">
                        <label>
                            <input type="checkbox" name="is_internal" value="1">
                            Note interne (non visible par le client)
                        </label>
                    </div>

                    <div class="colis224-form-actions" style="margin-top: 15px;">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-email-alt"></span>
                            Envoyer la Réponse
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Gérer création de ticket
     */
    private static function handle_create_ticket() {
        if (!isset($_POST['colis224_ticket_nonce']) || !wp_verify_nonce($_POST['colis224_ticket_nonce'], 'colis224_ticket_action')) {
            wp_die('Erreur de sécurité');
        }

        $_POST['user_id'] = get_current_user_id();
        $result = Colis224_Support_Tickets::create_ticket($_POST);

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . ' - N° ' . $result['ticket_number'] . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }

    /**
     * Gérer réponse au ticket
     */
    private static function handle_reply_ticket() {
        if (!isset($_POST['colis224_ticket_nonce']) || !wp_verify_nonce($_POST['colis224_ticket_nonce'], 'colis224_ticket_action')) {
            wp_die('Erreur de sécurité');
        }

        $result = Colis224_Support_Tickets::add_reply(
            intval($_POST['ticket_id']),
            $_POST['message'],
            'admin',
            get_current_user_id(),
            !empty($_POST['is_internal'])
        );

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }

    /**
     * Gérer mise à jour statut
     */
    private static function handle_update_status() {
        if (!isset($_POST['colis224_ticket_nonce']) || !wp_verify_nonce($_POST['colis224_ticket_nonce'], 'colis224_ticket_action')) {
            wp_die('Erreur de sécurité');
        }

        $result = Colis224_Support_Tickets::update_status(
            intval($_POST['ticket_id']),
            sanitize_text_field($_POST['status'])
        );

        if ($result['success']) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
        }
    }

    /**
     * Gérer assignation
     */
    private static function handle_assign_ticket() {
        if (!isset($_POST['colis224_ticket_nonce']) || !wp_verify_nonce($_POST['colis224_ticket_nonce'], 'colis224_ticket_action')) {
            wp_die('Erreur de sécurité');
        }

        $user_id = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        $result = Colis224_Support_Tickets::assign_ticket(intval($_POST['ticket_id']), $user_id);

        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Ticket assigné avec succès!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Erreur lors de l\'assignation.</p></div>';
        }
    }

}
