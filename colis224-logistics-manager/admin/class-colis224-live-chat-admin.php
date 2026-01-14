<?php
/**
 * Interface Admin - Live Chat Support
 */

if (!defined('ABSPATH')) {
    exit;
}

class Colis224_Live_Chat_Admin {

    public static function display_page() {
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'conversations';
        $conversation_id = isset($_GET['conversation']) ? sanitize_text_field($_GET['conversation']) : '';

        ?>
        <div class="wrap colis224-wrap">
            <h1 class="colis224-title">
                <span class="dashicons dashicons-format-chat"></span>
                Live Chat Support
            </h1>

            <?php if ($conversation_id): ?>
                <?php self::display_conversation_detail($conversation_id); ?>
            <?php else: ?>
                <nav class="nav-tab-wrapper">
                    <a href="?page=colis224-live-chat&view=conversations"
                       class="nav-tab <?php echo $view === 'conversations' ? 'nav-tab-active' : ''; ?>">
                        💬 Conversations
                    </a>
                    <a href="?page=colis224-live-chat&view=quick-replies"
                       class="nav-tab <?php echo $view === 'quick-replies' ? 'nav-tab-active' : ''; ?>">
                        ⚡ Réponses Rapides
                    </a>
                    <a href="?page=colis224-live-chat&view=stats"
                       class="nav-tab <?php echo $view === 'stats' ? 'nav-tab-active' : ''; ?>">
                        📊 Statistiques
                    </a>
                </nav>

                <?php
                if ($view === 'conversations') {
                    self::display_conversations();
                } elseif ($view === 'quick-replies') {
                    self::display_quick_replies();
                } else {
                    self::display_stats();
                }
                ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function display_conversations() {
        $stats = Colis224_Live_Chat::get_chat_stats();
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'active';

        global $wpdb;
        $table_conversations = $wpdb->prefix . 'colis224_chat_conversations';
        $table_messages = $wpdb->prefix . 'colis224_chat_messages';

        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*,
             COUNT(m.id) as message_count,
             SUM(CASE WHEN m.is_read = 0 AND m.sender_type = 'client' THEN 1 ELSE 0 END) as unread_count
             FROM $table_conversations c
             LEFT JOIN $table_messages m ON c.conversation_id = m.conversation_id
             WHERE c.status = %s
             GROUP BY c.id
             ORDER BY c.last_message_at DESC",
            $status_filter
        ));

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>💬 Conversations</h2>
                <div id="agent-status-selector">
                    <label>Votre statut: </label>
                    <select id="agent-status">
                        <option value="online">🟢 En ligne</option>
                        <option value="away">🟡 Absent</option>
                        <option value="busy">🔴 Occupé</option>
                        <option value="offline">⚫ Hors ligne</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 14px;">Conversations Actives</div>
                    <div style="font-size: 32px; font-weight: bold;"><?php echo $stats['active']; ?></div>
                </div>
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 14px;">En Attente</div>
                    <div style="font-size: 32px; font-weight: bold;"><?php echo $stats['waiting']; ?></div>
                </div>
                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 20px; border-radius: 8px; color: white;">
                    <div style="font-size: 14px;">Aujourd'hui</div>
                    <div style="font-size: 32px; font-weight: bold;"><?php echo $stats['today']; ?></div>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <a href="?page=colis224-live-chat&view=conversations&status=active"
                   class="button <?php echo $status_filter === 'active' ? 'button-primary' : ''; ?>">
                    Active
                </a>
                <a href="?page=colis224-live-chat&view=conversations&status=waiting"
                   class="button <?php echo $status_filter === 'waiting' ? 'button-primary' : ''; ?>">
                    En attente
                </a>
                <a href="?page=colis224-live-chat&view=conversations&status=closed"
                   class="button <?php echo $status_filter === 'closed' ? 'button-primary' : ''; ?>">
                    Fermées
                </a>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Contact</th>
                        <th>Messages</th>
                        <th>Non lus</th>
                        <th>Dernier message</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($conversations)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                Aucune conversation dans cette catégorie
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <tr>
                                <td><strong><?php echo esc_html($conv->client_name); ?></strong></td>
                                <td>
                                    <?php if ($conv->client_phone): ?>
                                        📞 <?php echo esc_html($conv->client_phone); ?><br>
                                    <?php endif; ?>
                                    <?php if ($conv->client_email): ?>
                                        📧 <?php echo esc_html($conv->client_email); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo intval($conv->message_count); ?></td>
                                <td>
                                    <?php if ($conv->unread_count > 0): ?>
                                        <span style="background: #dc3545; color: white; padding: 3px 8px; border-radius: 10px; font-weight: bold;">
                                            <?php echo intval($conv->unread_count); ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo human_time_diff(strtotime($conv->last_message_at), current_time('timestamp')); ?> ago</td>
                                <td>
                                    <?php
                                    $status_badges = array(
                                        'active' => '<span style="color: #28a745;">🟢 Active</span>',
                                        'waiting' => '<span style="color: #ffc107;">🟡 En attente</span>',
                                        'closed' => '<span style="color: #6c757d;">⚫ Fermée</span>'
                                    );
                                    echo $status_badges[$conv->status];
                                    ?>
                                </td>
                                <td>
                                    <a href="?page=colis224-live-chat&conversation=<?php echo $conv->conversation_id; ?>"
                                       class="button button-small">
                                        💬 Ouvrir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Mise à jour du statut agent
            $('#agent-status').on('change', function() {
                var status = $(this).val();
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'colis224_agent_status',
                        status: status
                    }
                });
            });

            // Auto-refresh toutes les 10 secondes (via AJAX plutôt que reload)
            setInterval(function() {
                if (typeof colis224SoftReload === 'function') {
                    // Recharger seulement les données via AJAX au lieu de recharger la page
                    jQuery('.conversations-list').load(window.location.href + ' .conversations-list > *');
                } else {
                    location.reload();
                }
            }, 10000);
        });
        </script>
        <?php
    }

    private static function display_conversation_detail($conversation_id) {
        global $wpdb;
        $table_conversations = $wpdb->prefix . 'colis224_chat_conversations';

        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_conversations WHERE conversation_id = %s",
            $conversation_id
        ));

        if (!$conversation) {
            echo '<div class="notice notice-error"><p>Conversation introuvable</p></div>';
            return;
        }

        $messages = Colis224_Live_Chat::get_messages($conversation_id, 200);

        // Récupérer les réponses rapides
        $quick_replies = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}colis224_chat_quick_replies WHERE is_active = 1 ORDER BY usage_count DESC LIMIT 10"
        );

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2>💬 Conversation avec <?php echo esc_html($conversation->client_name); ?></h2>
                    <p style="margin: 5px 0;">
                        ID: <code><?php echo $conversation->conversation_id; ?></code>
                        | Démarrée: <?php echo date('d/m/Y H:i', strtotime($conversation->started_at)); ?>
                    </p>
                </div>
                <div>
                    <a href="?page=colis224-live-chat" class="button">← Retour</a>
                    <?php if ($conversation->status !== 'closed'): ?>
                        <button id="close-conversation" class="button">Fermer la conversation</button>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 300px; gap: 20px;">
                <!-- Zone de chat -->
                <div>
                    <div id="chat-messages-container" style="height: 500px; overflow-y: auto; background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
                        <?php foreach ($messages as $msg): ?>
                            <div class="chat-message <?php echo $msg->sender_type; ?>">
                                <div class="message-header">
                                    <strong><?php echo esc_html($msg->sender_name); ?></strong>
                                    <span><?php echo date('H:i', strtotime($msg->created_at)); ?></span>
                                </div>
                                <div class="message-content">
                                    <?php echo nl2br(esc_html($msg->message)); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div>
                        <textarea id="message-input" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" placeholder="Tapez votre réponse..."></textarea>
                        <button id="send-message" class="button button-primary" style="margin-top: 10px;">Envoyer</button>
                    </div>
                </div>

                <!-- Réponses rapides -->
                <div>
                    <h3>⚡ Réponses Rapides</h3>
                    <div id="quick-replies-list">
                        <?php foreach ($quick_replies as $reply): ?>
                            <div class="quick-reply-item" data-message="<?php echo esc_attr($reply->message); ?>"
                                 style="padding: 10px; background: white; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px; cursor: pointer;">
                                <strong><?php echo esc_html($reply->title); ?></strong>
                                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                                    <?php echo esc_html(substr($reply->message, 0, 50)); ?>...
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h3 style="margin-top: 30px;">👤 Infos Client</h3>
                    <div style="background: white; padding: 15px; border-radius: 5px;">
                        <p><strong>Nom:</strong> <?php echo esc_html($conversation->client_name); ?></p>
                        <?php if ($conversation->client_email): ?>
                            <p><strong>Email:</strong> <?php echo esc_html($conversation->client_email); ?></p>
                        <?php endif; ?>
                        <?php if ($conversation->client_phone): ?>
                            <p><strong>Téléphone:</strong> <?php echo esc_html($conversation->client_phone); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .chat-message {
                margin-bottom: 20px;
            }
            .chat-message.client {
                text-align: right;
            }
            .chat-message.agent {
                text-align: left;
            }
            .message-header {
                font-size: 12px;
                color: #666;
                margin-bottom: 5px;
            }
            .message-content {
                display: inline-block;
                padding: 10px 15px;
                border-radius: 10px;
                max-width: 70%;
                word-wrap: break-word;
            }
            .chat-message.client .message-content {
                background: #667eea;
                color: white;
            }
            .chat-message.agent .message-content {
                background: white;
                border: 1px solid #ddd;
            }
            .quick-reply-item:hover {
                background: #f8f9fa !important;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var conversationId = '<?php echo $conversation_id; ?>';

            // Envoyer un message
            $('#send-message').on('click', function() {
                var message = $('#message-input').val().trim();
                if (!message) return;

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'colis224_send_chat_message',
                        conversation_id: conversationId,
                        message: message,
                        sender_type: 'agent'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#message-input').val('');
                            // Recharger seulement les messages au lieu de toute la page
                            loadMessages();
                        }
                    }
                });
            });

            // Utiliser une réponse rapide
            $('.quick-reply-item').on('click', function() {
                var message = $(this).data('message');
                $('#message-input').val(message);
            });

            // Fermer la conversation
            $('#close-conversation').on('click', function() {
                if (confirm('Voulez-vous vraiment fermer cette conversation?')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'colis224_close_chat',
                            conversation_id: conversationId
                        },
                        success: function(response) {
                            window.location.href = '?page=colis224-live-chat';
                        }
                    });
                }
            });

            // Scroller vers le bas
            $('#chat-messages-container').scrollTop($('#chat-messages-container')[0].scrollHeight);

            // Auto-refresh messages toutes les 5 secondes
            function loadMessages() {
                $('#chat-messages-container').load(window.location.href + ' #chat-messages-container > *', function() {
                    $('#chat-messages-container').scrollTop($('#chat-messages-container')[0].scrollHeight);
                });
            }
            
            setInterval(function() {
                loadMessages();
            }, 5000);
        });
        </script>
        <?php
    }

    private static function display_quick_replies() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_chat_quick_replies';

        // Ajouter/modifier une réponse rapide
        if (isset($_POST['action']) && $_POST['action'] === 'save_quick_reply') {
            self::save_quick_reply();
        }

        $replies = $wpdb->get_results("SELECT * FROM $table ORDER BY category, title");

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h2>⚡ Réponses Rapides</h2>
            <p>Créez des réponses prédéfinies pour gagner du temps</p>

            <button id="add-new-reply" class="button button-primary">+ Ajouter une réponse</button>

            <div id="reply-form" style="display: none; margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <form method="post">
                    <input type="hidden" name="action" value="save_quick_reply">
                    <?php wp_nonce_field('colis224_quick_reply', 'quick_reply_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th><label for="title">Titre</label></th>
                            <td><input type="text" id="title" name="title" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="message">Message</label></th>
                            <td><textarea id="message" name="message" rows="4" class="large-text" required></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="category">Catégorie</label></th>
                            <td>
                                <select id="category" name="category">
                                    <option value="general">Général</option>
                                    <option value="greeting">Bienvenue</option>
                                    <option value="tracking">Suivi</option>
                                    <option value="payment">Paiement</option>
                                    <option value="delivery">Livraison</option>
                                    <option value="closing">Clôture</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <button type="submit" class="button button-primary">Enregistrer</button>
                    <button type="button" id="cancel-reply" class="button">Annuler</button>
                </form>
            </div>

            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Message</th>
                        <th>Catégorie</th>
                        <th>Utilisations</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($replies as $reply): ?>
                        <tr>
                            <td><strong><?php echo esc_html($reply->title); ?></strong></td>
                            <td><?php echo esc_html(substr($reply->message, 0, 100)); ?>...</td>
                            <td><?php echo esc_html($reply->category); ?></td>
                            <td><?php echo intval($reply->usage_count); ?></td>
                            <td>
                                <?php if ($reply->is_active): ?>
                                    <span style="color: #28a745;">✅ Actif</span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">⏸️ Inactif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#add-new-reply').on('click', function() {
                $('#reply-form').slideDown();
            });

            $('#cancel-reply').on('click', function() {
                $('#reply-form').slideUp();
            });
        });
        </script>
        <?php
    }

    private static function save_quick_reply() {
        if (!isset($_POST['quick_reply_nonce']) || !wp_verify_nonce($_POST['quick_reply_nonce'], 'colis224_quick_reply')) {
            wp_die('Erreur de sécurité');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'colis224_chat_quick_replies';

        $wpdb->insert($table, array(
            'title' => sanitize_text_field($_POST['title']),
            'message' => sanitize_textarea_field($_POST['message']),
            'category' => sanitize_text_field($_POST['category']),
            'is_active' => 1
        ));

        echo '<div class="notice notice-success"><p>Réponse rapide ajoutée avec succès!</p></div>';
    }

    private static function display_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'colis224_chat_conversations';

        // Statistiques
        $total_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $closed_today = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'closed' AND DATE(closed_at) = CURRENT_DATE");
        $avg_messages = $wpdb->get_var(
            "SELECT AVG(message_count) FROM (
                SELECT COUNT(*) as message_count FROM {$wpdb->prefix}colis224_chat_messages GROUP BY conversation_id
            ) as counts"
        );

        ?>
        <div class="colis224-card" style="margin-top: 20px;">
            <h2>📊 Statistiques Live Chat</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px; border-radius: 8px; color: white;">
                    <div style="font-size: 14px; opacity: 0.9;">Total Conversations</div>
                    <div style="font-size: 40px; font-weight: bold;"><?php echo intval($total_conversations); ?></div>
                </div>

                <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 25px; border-radius: 8px; color: white;">
                    <div style="font-size: 14px; opacity: 0.9;">Fermées Aujourd'hui</div>
                    <div style="font-size: 40px; font-weight: bold;"><?php echo intval($closed_today); ?></div>
                </div>

                <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 25px; border-radius: 8px; color: white;">
                    <div style="font-size: 14px; opacity: 0.9;">Messages Moy. / Conv.</div>
                    <div style="font-size: 40px; font-weight: bold;"><?php echo round($avg_messages, 1); ?></div>
                </div>
            </div>

            <p>Plus de statistiques à venir...</p>
        </div>
        <?php
    }
}
